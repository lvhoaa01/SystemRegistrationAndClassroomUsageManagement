<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$manifestPath = $root . '/database/fixtures/data-manifest.json';
$errors = [];

if (!is_file($manifestPath)) {
    throw new RuntimeException('Chưa có data-manifest.json. Hãy chạy generate_data.php trước.');
}

$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$requiredFiles = [
    'database/migrations/001_create_schema.sql',
    'database/source/hcmc_accommodations_verified.csv',
    'database/seeds/001_reference.sql',
    'database/seeds/002_accounts_partners.sql',
    'database/seeds/003_properties.sql',
    'database/seeds/004_products_policies.sql',
    'database/seeds/005_inventory_prices.sql',
    'database/seeds/006_operational.sql',
];

foreach ($requiredFiles as $relativePath) {
    $path = $root . '/' . $relativePath;
    if (!is_file($path)) {
        $errors[] = 'Thiếu file: ' . $relativePath;
        continue;
    }
    $expectedHash = $manifest['files'][$relativePath] ?? $manifest['sourceFiles'][$relativePath] ?? null;
    if ($relativePath !== 'database/migrations/001_create_schema.sql') {
        $actualHash = hash_file('sha256', $path);
        if ($expectedHash !== $actualHash) {
            $errors[] = 'Hash không khớp: ' . $relativePath;
        }
    }
    $contents = (string) file_get_contents($path);
    if (stripos($contents, 'room_booking') !== false || stripos($contents, 'SlotPhong') !== false || stripos($contents, 'LichChinhThuc') !== false) {
        $errors[] = 'Còn thuật ngữ hệ thống cũ trong: ' . $relativePath;
    }
}

$sourceCsvPath = $root . '/database/source/hcmc_accommodations_verified.csv';
if (is_file($sourceCsvPath)) {
    $expectedHeaders = ['property_id', 'source_row', 'source_name', 'verified_name', 'address', 'match_type', 'checked_at'];
    $handle = fopen($sourceCsvPath, 'rb');
    if ($handle === false) {
        $errors[] = 'Không mở được CSV nguồn để kiểm tra.';
    } else {
        try {
            $headers = fgetcsv($handle);
            if ($headers !== $expectedHeaders) {
                $errors[] = 'Tiêu đề CSV nguồn không đúng cấu trúc quy định.';
            } else {
                $rowNumber = 1;
                $propertyIds = [];
                $sourceRows = [];
                while (($values = fgetcsv($handle)) !== false) {
                    ++$rowNumber;
                    if (count($values) !== count($headers)) {
                        $errors[] = 'Sai số cột tại dòng CSV ' . $rowNumber . '.';
                        continue;
                    }
                    $row = array_combine($headers, $values);
                    $propertyId = filter_var($row['property_id'], FILTER_VALIDATE_INT);
                    $sourceRow = filter_var($row['source_row'], FILTER_VALIDATE_INT);
                    if ($propertyId === false || $propertyId < 1 || $propertyId > 30 || isset($propertyIds[$propertyId])) {
                        $errors[] = 'Mã cơ sở không hợp lệ hoặc bị trùng tại dòng CSV ' . $rowNumber . '.';
                    } else {
                        $propertyIds[$propertyId] = true;
                    }
                    if ($sourceRow === false || $sourceRow < 2 || isset($sourceRows[$sourceRow])) {
                        $errors[] = 'Số dòng Excel không hợp lệ hoặc bị trùng tại dòng CSV ' . $rowNumber . '.';
                    } else {
                        $sourceRows[$sourceRow] = true;
                    }
                    foreach (['source_name', 'verified_name', 'address'] as $requiredColumn) {
                        if (trim($row[$requiredColumn]) === '') {
                            $errors[] = 'Thiếu ' . $requiredColumn . ' tại dòng CSV ' . $rowNumber . '.';
                        }
                    }
                    if (!str_contains($row['address'], 'Thành phố Hồ Chí Minh')) {
                        $errors[] = 'Địa chỉ ngoài Thành phố Hồ Chí Minh tại dòng CSV ' . $rowNumber . '.';
                    }
                    if (!in_array($row['match_type'], ['KHOP_CHINH_XAC', 'KHOP_TEN_CU_BIET_DANH'], true)) {
                        $errors[] = 'Loại đối chiếu không hợp lệ tại dòng CSV ' . $rowNumber . '.';
                    }
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['checked_at'])) {
                        $errors[] = 'Ngày đối chiếu không hợp lệ tại dòng CSV ' . $rowNumber . '.';
                    }
                }
                if (count($propertyIds) !== 30 || array_keys($propertyIds) !== range(1, 30)) {
                    $errors[] = 'CSV nguồn phải có đủ mã cơ sở liên tục từ 1 đến 30.';
                }
            }
        } finally {
            fclose($handle);
        }
    }
}

$expectedCounts = [
    'facilities' => 24,
    'users' => 511,
    'partnerOrganizations' => 10,
    'properties' => 30,
    'roomTypes' => 120,
    'roomProducts' => 180,
    'dailyInventory' => 21600,
    'dailyPrices' => 32400,
    'previews' => 2000,
    'bookings' => 2000,
    'bookingItems' => 2500,
    'nightPriceSnapshots' => 8000,
    'inventoryLedgerRows' => 8000,
    'payments' => 2000,
    'cancellationRequests' => 200,
    'reviews' => 800,
    'promotions' => 60,
    'googlePlacesMatches' => 0,
];
foreach ($expectedCounts as $name => $expected) {
    $actual = $manifest['counts'][$name] ?? null;
    if ($actual !== $expected) {
        $errors[] = sprintf('Count %s: expected %d, got %s', $name, $expected, var_export($actual, true));
    }
}

if (($manifest['syntheticData'] ?? null) !== true) {
    $errors[] = 'Manifest phải đánh dấu syntheticData=true.';
}
if (($manifest['tableCount'] ?? null) !== 22) {
    $errors[] = 'Manifest phải khai báo tableCount=22.';
}

if ($errors !== []) {
    fwrite(STDERR, "Dữ liệu không hợp lệ:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "Bộ dữ liệu accommodation booking hợp lệ ở mức file/manifest.\n";
foreach ($manifest['counts'] as $name => $count) {
    echo str_pad((string) $name, 28) . ': ' . $count . PHP_EOL;
}
echo "Hãy chạy database/validation/001_integrity_checks.sql sau khi import để kiểm tra invariant trong database.\n";
