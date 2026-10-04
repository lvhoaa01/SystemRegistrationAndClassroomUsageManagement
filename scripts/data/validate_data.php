<?php

declare(strict_types=1);

const EXPECTED_HEADER = [
    'SchemaVersion', 'MaDong', 'MaHocKy', 'Ngay', 'MaPhong', 'TietBatDau',
    'TietKetThuc', 'LoaiHoatDong', 'MaLopHocPhan', 'MaGiangVien', 'SiSo',
    'TenHoatDong', 'LoaiBuoi', 'GhiChu',
];

$root = dirname(__DIR__, 2);
$manifestPath = $root . '/database/fixtures/data-manifest.json';
$errors = [];

function fail(array &$errors, string $message): void
{
    $errors[] = $message;
}

function readCsvFile(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Không mở được CSV: ' . $path);
    }

    try {
        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header === false) {
            throw new RuntimeException('CSV rỗng: ' . $path);
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }
            $rows[] = $row;
        }
        return [$header, $rows];
    } finally {
        fclose($handle);
    }
}

function validateSchedule(string $path, int $expectedRows, array &$errors): array
{
    [$header, $rows] = readCsvFile($path);
    if ($header !== EXPECTED_HEADER) {
        fail($errors, basename($path) . ': header không đúng contract.');
        return [];
    }
    if (count($rows) !== $expectedRows) {
        fail($errors, basename($path) . ': số dòng ' . count($rows) . ', kỳ vọng ' . $expectedRows . '.');
    }

    $byCode = [];
    $roomSlots = [];
    $lecturerSlots = [];
    $classSlots = [];

    foreach ($rows as $lineIndex => $row) {
        $line = $lineIndex + 2;
        if (count($row) !== count(EXPECTED_HEADER)) {
            fail($errors, basename($path) . ": dòng {$line} không đủ 14 cột.");
            continue;
        }
        [$version, $code, $term, $date, $room, $startRaw, $endRaw, $activity, $class, $lecturer, $size] = $row;
        if ($version !== '1' || $term !== '2026-HK1') {
            fail($errors, basename($path) . ": dòng {$line} sai schema/học kỳ.");
        }
        if (isset($byCode[$code])) {
            fail($errors, basename($path) . ": trùng MaDong {$code}.");
        }
        $byCode[$code] = $row;
        if (!preg_match('/^2026-\d{2}-\d{2}$/', $date) || $date < '2026-09-05' || $date > '2026-12-20') {
            fail($errors, basename($path) . ": dòng {$line} có ngày ngoài học kỳ.");
        }
        $start = filter_var($startRaw, FILTER_VALIDATE_INT);
        $end = filter_var($endRaw, FILTER_VALIDATE_INT);
        if ($start === false || $end === false || $start < 1 || $end > 13 || $start > $end) {
            fail($errors, basename($path) . ": dòng {$line} có khoảng tiết sai.");
            continue;
        }
        if ($activity === 'LICH_HOC' && ($class === '' || $lecturer === '' || $size === '')) {
            fail($errors, basename($path) . ": dòng {$line} thiếu dữ liệu lịch học.");
        }
        for ($period = $start; $period <= $end; ++$period) {
            $roomKey = $room . '|' . $date . '|' . $period;
            if (isset($roomSlots[$roomKey])) {
                fail($errors, basename($path) . ": trùng phòng tại {$roomKey} ({$roomSlots[$roomKey]} và {$code}).");
            }
            $roomSlots[$roomKey] = $code;

            if ($lecturer !== '') {
                $lecturerKey = $lecturer . '|' . $date . '|' . $period;
                if (isset($lecturerSlots[$lecturerKey])) {
                    fail($errors, basename($path) . ": trùng giảng viên tại {$lecturerKey}.");
                }
                $lecturerSlots[$lecturerKey] = $code;
            }
            if ($class !== '') {
                $classKey = $class . '|' . $date . '|' . $period;
                if (isset($classSlots[$classKey])) {
                    fail($errors, basename($path) . ": trùng lớp tại {$classKey}.");
                }
                $classSlots[$classKey] = $code;
            }
        }
    }

    return $byCode;
}

if (!is_file($manifestPath)) {
    throw new RuntimeException('Chưa có data-manifest.json. Hãy chạy generate_data.php trước.');
}
$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

foreach ($manifest['files'] as $relativePath => $expectedHash) {
    $path = $root . '/' . $relativePath;
    if (!is_file($path)) {
        fail($errors, 'Thiếu tệp: ' . $relativePath);
        continue;
    }
    $actualHash = hash_file('sha256', $path);
    if (!hash_equals($expectedHash, $actualHash)) {
        fail($errors, 'SHA-256 không khớp: ' . $relativePath);
    }
}

$requiredSql = [
    'database/migrations/001_create_schema.sql',
    'database/seeds/001_reference.sql',
    'database/seeds/002_facilities.sql',
    'database/seeds/003_lecturers.sql',
    'database/seeds/004_academic.sql',
    'database/seeds/005_operational.sql',
];
foreach ($requiredSql as $relativePath) {
    $path = $root . '/' . $relativePath;
    if (!is_file($path) || filesize($path) < 100) {
        fail($errors, 'SQL thiếu hoặc rỗng: ' . $relativePath);
    }
}

$expectedRows = (int) $manifest['counts']['officialScheduleRows'];
$current = validateSchedule($root . '/database/fixtures/csv/valid/official_schedule.csv', $expectedRows, $errors);
$replacement = validateSchedule($root . '/database/fixtures/csv/valid/official_schedule_replacement.csv', $expectedRows, $errors);

$changed = 0;
foreach ($current as $code => $row) {
    if (!isset($replacement[$code])) {
        fail($errors, 'Replacement thiếu MaDong: ' . $code);
        continue;
    }
    if ($replacement[$code] !== $row) {
        ++$changed;
    }
}
if ($changed !== (int) $manifest['counts']['replacementChangedRows']) {
    fail($errors, "Replacement đổi {$changed} dòng, kỳ vọng " . $manifest['counts']['replacementChangedRows'] . '.');
}

$counts = $manifest['counts'];
if ($counts['tables'] !== 20 || $counts['rooms'] !== 46 || $counts['activeLecturers'] !== 14) {
    fail($errors, 'Quy mô catalog không đúng mục tiêu 20 bảng/46 phòng/14 giảng viên.');
}
if ($counts['courseSections'] < 50 || $counts['courseSections'] > 70) {
    fail($errors, 'Số lớp học phần ngoài khoảng 50-70.');
}
if ($counts['internalScheduleRows'] < 1200 || $counts['internalScheduleRows'] > 1700) {
    fail($errors, 'Số dòng lịch nội bộ ngoài khoảng 1.200-1.700.');
}
if ($counts['externalBusyRows'] < 1500 || $counts['externalBusyRows'] > 3000) {
    fail($errors, 'Số dòng bận ngoài khoa ngoài khoảng 1.500-3.000.');
}
if ($counts['roomSlots'] < 6000 || $counts['roomSlots'] > 12000) {
    fail($errors, 'Số SlotPhong ngoài khoảng 6.000-12.000.');
}
if ($counts['bookings'] < 200 || $counts['bookings'] > 400) {
    fail($errors, 'Số phiếu ngoài khoảng 200-400.');
}

[$invalidHeader] = readCsvFile($root . '/database/fixtures/csv/invalid/invalid_header.csv');
if ($invalidHeader === EXPECTED_HEADER) {
    fail($errors, 'invalid_header.csv không còn sai header.');
}

foreach (['room_conflict.csv', 'lecturer_conflict.csv', 'class_conflict.csv'] as $fixture) {
    [$header, $rows] = readCsvFile($root . '/database/fixtures/csv/invalid/' . $fixture);
    if ($header !== EXPECTED_HEADER || count($rows) !== 2) {
        fail($errors, $fixture . ' không đúng hình dạng fixture 2 dòng.');
    }
}

[, $capacityRows] = readCsvFile($root . '/database/fixtures/csv/invalid/capacity_exceeded.csv');
if (($capacityRows[0][10] ?? '') !== '100') {
    fail($errors, 'capacity_exceeded.csv không chứa sĩ số 100.');
}
[, $assignmentRows] = readCsvFile($root . '/database/fixtures/csv/invalid/assignment_missing.csv');
if (($assignmentRows[0][9] ?? '') !== 'GV999') {
    fail($errors, 'assignment_missing.csv không chứa giảng viên không tồn tại.');
}

if ($errors !== []) {
    fwrite(STDERR, "Bộ dữ liệu KHÔNG hợp lệ:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "Bộ dữ liệu phát triển hợp lệ.\n";
echo 'CSV rows                 : ' . $expectedRows . PHP_EOL;
echo 'Replacement changed rows : ' . $changed . PHP_EOL;
echo 'Room slots               : ' . $counts['roomSlots'] . PHP_EOL;
echo 'Bookings                 : ' . $counts['bookings'] . PHP_EOL;
