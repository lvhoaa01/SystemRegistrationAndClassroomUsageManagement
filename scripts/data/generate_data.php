<?php

declare(strict_types=1);

const DATA_SEED = 20261004;
const TERM_CODE = '2026-HK1';
const CSV_HEADER = [
    'SchemaVersion', 'MaDong', 'MaHocKy', 'Ngay', 'MaPhong', 'TietBatDau',
    'TietKetThuc', 'LoaiHoatDong', 'MaLopHocPhan', 'MaGiangVien', 'SiSo',
    'TenHoatDong', 'LoaiBuoi', 'GhiChu',
];

$root = dirname(__DIR__, 2);
$seedDir = $root . '/database/seeds';
$validDir = $root . '/database/fixtures/csv/valid';
$invalidDir = $root . '/database/fixtures/csv/invalid';
$fixtureDir = $root . '/database/fixtures';

foreach ([$seedDir, $validDir, $invalidDir] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục: ' . $directory);
    }
}

mt_srand(DATA_SEED);

function sqlValue(mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $value) . "'";
}

function sqlInsert(string $table, array $columns, array $rows, int $chunkSize = 250): string
{
    if ($rows === []) {
        return '';
    }

    $output = '';
    foreach (array_chunk($rows, $chunkSize) as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $values[] = '(' . implode(', ', array_map('sqlValue', $row)) . ')';
        }
        $output .= 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ") VALUES\n  ";
        $output .= implode(",\n  ", $values) . ";\n\n";
    }

    return $output;
}

function writeText(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Không thể ghi tệp: ' . $path);
    }
}

function writeCsv(string $path, array $header, array $rows): void
{
    $handle = fopen($path, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Không thể ghi CSV: ' . $path);
    }

    try {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $header, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }
    } finally {
        fclose($handle);
    }
}

function jsonValue(mixed $value): string
{
    return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function dateOfTeachingWeek(DateTimeImmutable $firstMonday, int $week, int $dayOffset): string
{
    return $firstMonday->modify('+' . (($week - 1) * 7 + $dayOffset) . ' days')->format('Y-m-d');
}

function periodKeys(int $roomId, string $date, int $start, int $end): array
{
    $keys = [];
    for ($period = $start; $period <= $end; ++$period) {
        $keys[] = $roomId . '|' . $date . '|' . $period;
    }
    return $keys;
}

function resourceKeys(int $resourceId, string $date, int $start, int $end): array
{
    $keys = [];
    for ($period = $start; $period <= $end; ++$period) {
        $keys[] = $resourceId . '|' . $date . '|' . $period;
    }
    return $keys;
}

function keysAreFree(array $store, array $keys): bool
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $store)) {
            return false;
        }
    }
    return true;
}

function reserveKeys(array &$store, array $keys, array $owner): void
{
    foreach ($keys as $key) {
        if (isset($store[$key])) {
            throw new RuntimeException('Trùng slot trong generator: ' . $key);
        }
        $store[$key] = $owner;
    }
}

function releaseOwnedKeys(array &$store, array $keys, string $type, int $id): void
{
    foreach ($keys as $key) {
        if (($store[$key]['type'] ?? null) === $type && ($store[$key]['id'] ?? null) === $id) {
            unset($store[$key]);
        }
    }
}

function deterministicUuid(int $number): string
{
    return sprintf('00000000-0000-4000-8000-%012d', $number);
}

function csvRow(array $event): array
{
    return [
        '1', $event['code'], TERM_CODE, $event['date'], $event['roomCode'],
        (string) $event['start'], (string) $event['end'], $event['activityType'],
        $event['classCode'] ?? '', $event['lecturerCode'] ?? '',
        $event['size'] === null ? '' : (string) $event['size'],
        $event['title'], $event['sessionType'], $event['note'],
    ];
}

// -----------------------------------------------------------------------------
// 1. Reference data
// -----------------------------------------------------------------------------

$periods = [
    [1, '07:00:00', '07:50:00', 'SANG'],
    [2, '07:50:00', '08:40:00', 'SANG'],
    [3, '08:40:00', '09:50:00', 'SANG'],
    [4, '09:50:00', '10:40:00', 'SANG'],
    [5, '10:40:00', '11:30:00', 'SANG'],
    [6, '13:00:00', '13:50:00', 'CHIEU'],
    [7, '13:50:00', '14:40:00', 'CHIEU'],
    [8, '14:40:00', '15:50:00', 'CHIEU'],
    [9, '15:50:00', '16:40:00', 'CHIEU'],
    [10, '16:40:00', '17:30:00', 'CHIEU'],
    [11, '18:30:00', '19:20:00', 'TOI'],
    [12, '19:20:00', '20:10:00', 'TOI'],
    [13, '20:10:00', '21:00:00', 'TOI'],
];

$devices = [
    [1, 'MAY_CHIEU', 'Máy chiếu', 'cái'],
    [2, 'BANG_TRANG', 'Bảng trắng', 'cái'],
    [3, 'DIEU_HOA', 'Điều hòa', 'cái'],
    [4, 'INTERNET', 'Kết nối Internet', 'bộ'],
    [5, 'MAY_TINH_GV', 'Máy tính giảng viên', 'cái'],
    [6, 'MAY_TINH_SV', 'Máy tính sinh viên', 'cái'],
    [7, 'AM_THANH', 'Hệ thống âm thanh', 'bộ'],
    [8, 'LAB_MANG', 'Bộ thiết bị thực hành mạng', 'bộ'],
];

$referenceSql = "-- Dữ liệu tham chiếu phục vụ phát triển.\nUSE room_booking;\nSET NAMES utf8mb4;\n\n";
$referenceSql .= sqlInsert('HocKy', [
    'HocKyID', 'MaHocKy', 'TenHocKy', 'NgayBatDau', 'NgayKetThuc', 'TrangThai',
    'DotImportHienHanhID', 'SoGioBaoTruocTuDong', 'SoNgayDatTruocToiDa',
    'SoTietToiDaMoiPhieu', 'SoPhieuHoatDongToiDaMoiGV', 'TuanTamNghi', 'TuanThiCuoiKy',
], [[1, TERM_CODE, 'Học kỳ 1 năm học 2026-2027', '2026-09-05', '2026-12-20', 'NHAP', null, 24, 45, 5, 8, 8, 13]]);

$periodRows = [];
foreach ($periods as [$number, $start, $end, $session]) {
    $periodRows[] = [$number, 1, $number, $start, $end, $session];
}
$referenceSql .= sqlInsert('KhungTiet', ['KhungTietID', 'HocKyID', 'SoTiet', 'GioBatDau', 'GioKetThuc', 'Buoi'], $periodRows);
$referenceSql .= sqlInsert('NgayNghi', ['NgayNghiID', 'HocKyID', 'Ngay', 'LyDo'], [
    [1, 1, '2026-12-20', 'Ngày nghỉ cuối học kỳ'],
]);
$referenceSql .= sqlInsert('ThietBi', ['ThietBiID', 'MaThietBi', 'TenThietBi', 'DonViTinh', 'TrangThai'],
    array_map(static fn (array $row): array => [...$row, 'HOAT_DONG'], $devices));
writeText($seedDir . '/001_reference.sql', $referenceSql);

// -----------------------------------------------------------------------------
// 2. Facilities: 5 buildings, 46 provisional rooms
// -----------------------------------------------------------------------------

$buildings = [
    [1, 'G2', 'Giảng đường G2', 'HOAT_DONG'],
    [2, 'G3', 'Giảng đường G3', 'HOAT_DONG'],
    [3, 'G6', 'Giảng đường G6', 'HOAT_DONG'],
    [4, 'G8', 'Giảng đường G8', 'HOAT_DONG'],
    [5, 'NĐN', 'Tòa NĐN', 'HOAT_DONG'],
];

$roomCodes = [];
foreach ([1, 3, 4] as $floor) {
    for ($number = 1; $number <= 4; ++$number) {
        $roomCodes[] = ['G2.' . $floor . sprintf('%02d', $number), 1, $floor];
    }
}
for ($number = 1; $number <= 4; ++$number) {
    $roomCodes[] = ['G3.3' . sprintf('%02d', $number), 2, 3];
}
for ($floor = 1; $floor <= 3; ++$floor) {
    for ($number = 1; $number <= 4; ++$number) {
        $roomCodes[] = ['G6.' . $floor . sprintf('%02d', $number), 3, $floor];
    }
}
for ($floor = 1; $floor <= 2; ++$floor) {
    for ($number = 1; $number <= 4; ++$number) {
        $roomCodes[] = ['G8.' . $floor . sprintf('%02d', $number), 4, $floor];
    }
}
foreach ([['NĐN.101', 1], ['NĐN.102', 1], ['NĐN.202', 2], ['NĐN.203', 2], ['NĐN.204', 2], ['NĐN.205', 2], ['NĐN.206', 2], ['NĐN.207', 2], ['NĐN.707', 7], ['NĐN.710', 7]] as [$code, $floor]) {
    $roomCodes[] = [$code, 5, $floor];
}

$rooms = [];
$roomByCode = [];
$theoryRoomIds = [];
$labRoomIds = [];
foreach ($roomCodes as $index => [$code, $buildingId, $floor]) {
    $id = $index + 1;
    $isLab = in_array($code, ['NĐN.101', 'NĐN.102'], true);
    $type = $isLab ? 'THUC_HANH' : 'LY_THUYET';
    $capacity = $isLab ? 30 : 60;
    $rooms[] = [$id, $buildingId, $code, $floor, 'Phòng ' . $code, $type, $capacity, true, 'HOAT_DONG', null];
    $roomByCode[$code] = ['id' => $id, 'code' => $code, 'type' => $type, 'capacity' => $capacity];
    if ($isLab) {
        $labRoomIds[] = $id;
    } else {
        $theoryRoomIds[] = $id;
    }
}

$roomEquipmentRows = [];
foreach ($rooms as $room) {
    $roomId = $room[0];
    $isLab = $room[5] === 'THUC_HANH';
    foreach ([[1, 1], [2, 1], [3, 2], [4, 1]] as [$deviceId, $quantity]) {
        $roomEquipmentRows[] = [$roomId, $deviceId, $quantity, 0, '2026-10-04 08:00:00.000000'];
    }
    if ($isLab) {
        $roomEquipmentRows[] = [$roomId, 5, 1, 0, '2026-10-04 08:00:00.000000'];
        $roomEquipmentRows[] = [$roomId, 6, 30, 0, '2026-10-04 08:00:00.000000'];
        $roomEquipmentRows[] = [$roomId, 8, 15, 0, '2026-10-04 08:00:00.000000'];
    } elseif ($roomId % 4 === 0) {
        $roomEquipmentRows[] = [$roomId, 7, 1, 0, '2026-10-04 08:00:00.000000'];
    }
}

$facilitiesSql = "-- Danh mục 46 phòng phục vụ phát triển; cần đối chiếu trước khi vận hành thực tế.\nUSE room_booking;\nSET NAMES utf8mb4;\n\n";
$facilitiesSql .= sqlInsert('ToaNha', ['ToaNhaID', 'MaToa', 'TenToa', 'TrangThai'], $buildings);
$facilitiesSql .= sqlInsert('Phong', ['PhongID', 'ToaNhaID', 'MaPhong', 'Tang', 'TenPhong', 'LoaiPhong', 'SucChua', 'ChoPhepDat', 'TrangThai', 'GhiChu'], $rooms);
$facilitiesSql .= sqlInsert('PhongThietBi', ['PhongID', 'ThietBiID', 'SoLuongTot', 'SoLuongHong', 'CapNhatLuc'], $roomEquipmentRows);
writeText($seedDir . '/002_facilities.sql', $facilitiesSql);

// -----------------------------------------------------------------------------
// 3. Users
// -----------------------------------------------------------------------------

$lecturerNames = [
    'Phạm Thị Thu Thúy', 'Nguyễn Mạnh Cương', 'Nguyễn Đình Hưng', 'Phạm Văn Nam',
    'Nguyễn Thủy Đoan Trang', 'Huỳnh Tuấn Anh', 'Nguyễn Thị Hương Lý',
    'Bùi Thị Hồng Minh', 'Ngô Nguyễn Tường Nghi', 'Nguyễn Văn Rạng',
    'Nguyễn Đình Hoàng Sơn', 'Bùi Chí Thành', 'Mai Cường Thọ', 'Nguyễn Hải Triều',
];
$passwordHash = '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO';
$userRows = [];
$lecturers = [];
foreach ($lecturerNames as $index => $name) {
    $id = $index + 1;
    $code = 'GV' . sprintf('%03d', $id);
    $userRows[] = [$id, $code, $name, 'gv' . sprintf('%03d', $id) . '@ntu.edu.vn', $passwordHash, 'GIANG_VIEN', true, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'];
    $lecturers[$id] = ['id' => $id, 'code' => $code, 'name' => $name];
}
$userRows[] = [15, 'QL001', 'Cán bộ quản lý', 'quanly@ntu.edu.vn', $passwordHash, 'QUAN_LY', false, 'KHONG_AP_DUNG', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'];
$userRows[] = [16, 'ADMIN001', 'Quản trị viên', 'admin@ntu.edu.vn', $passwordHash, 'ADMIN', false, 'KHONG_AP_DUNG', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'];

$usersSql = "-- Mật khẩu khởi tạo dùng chung: Ntu@123456\nUSE room_booking;\nSET NAMES utf8mb4;\n\n";
$usersSql .= sqlInsert('NguoiDung', ['NguoiDungID', 'MaNguoiDung', 'HoTen', 'Email', 'MatKhauHash', 'VaiTro', 'LaGiangVien', 'TrangThaiGiangDay', 'TrangThaiTaiKhoan', 'TaoLuc', 'CapNhatLuc'], $userRows);
writeText($seedDir . '/003_lecturers.sql', $usersSql);

// -----------------------------------------------------------------------------
// 4. Academic catalog: 30 courses, 30 groups, 56 sections
// -----------------------------------------------------------------------------

$courseDefinitions = [
    ['CNTT', 'Cơ sở lập trình', false, false],
    ['CNTT', 'Cấu trúc dữ liệu và giải thuật', false, true],
    ['CNTT', 'Cơ sở dữ liệu', false, true],
    ['CNTT', 'Mạng máy tính', true, true],
    ['CNTT', 'Lập trình hướng đối tượng', false, true],
    ['CNTT', 'Công nghệ phần mềm', false, true],
    ['CNTT', 'Phát triển ứng dụng Web', true, true],
    ['CNTT', 'Hệ quản trị cơ sở dữ liệu', true, true],
    ['CNTT', 'An toàn thông tin', false, true],
    ['CNTT', 'Điện toán đám mây', false, true],
    ['CNTT', 'Phân tích thiết kế hệ thống', false, true],
    ['CNTT', 'Kiểm thử phần mềm', false, true],
    ['KHMT', 'Toán rời rạc', false, false],
    ['KHMT', 'Trí tuệ nhân tạo', true, true],
    ['KHMT', 'Học máy', true, true],
    ['KHMT', 'Khai phá dữ liệu', false, true],
    ['KHMT', 'Xử lý ngôn ngữ tự nhiên', false, true],
    ['KHMT', 'Thị giác máy tính', false, true],
    ['KHMT', 'Phân tích dữ liệu', false, true],
    ['KHMT', 'Thuật toán nâng cao', false, true],
    ['KHMT', 'Khoa học dữ liệu', false, true],
    ['HTTTQL', 'Nhập môn quản trị học', false, false],
    ['HTTTQL', 'Phân tích nghiệp vụ', false, true],
    ['HTTTQL', 'Hệ thống thông tin quản lý', false, true],
    ['HTTTQL', 'Hệ thống ERP', true, true],
    ['HTTTQL', 'Thương mại điện tử', false, true],
    ['HTTTQL', 'Kho dữ liệu và BI', true, true],
    ['HTTTQL', 'Quản trị dự án CNTT', false, true],
    ['HTTTQL', 'Phân tích dữ liệu kinh doanh', false, true],
    ['HTTTQL', 'Chuyển đổi số doanh nghiệp', false, true],
];

$courses = [];
$courseRows = [];
$coursesByProgram = ['CNTT' => [], 'KHMT' => [], 'HTTTQL' => []];
foreach ($courseDefinitions as $index => [$program, $name, $lab, $specialized]) {
    $id = $index + 1;
    $code = 'HP' . sprintf('%03d', $id);
    $periodCount = $specialized ? 3 : 2;
    $requirements = $lab
        ? [['maThietBi' => 'MAY_TINH_SV', 'soLuong' => 30], ['maThietBi' => 'INTERNET', 'soLuong' => 1]]
        : [['maThietBi' => 'MAY_CHIEU', 'soLuong' => 1]];
    $course = [
        'id' => $id, 'code' => $code, 'program' => $program, 'name' => $name,
        'lab' => $lab, 'specialized' => $specialized, 'periods' => $periodCount,
        'requirements' => $requirements,
    ];
    $courses[$id] = $course;
    $coursesByProgram[$program][] = $course;
    $courseRows[] = [$id, $code, $name, $program, $specialized ? 'CHUYEN_NGANH' : 'THONG_THUONG', 2, $periodCount, 12, jsonValue($requirements), 'HOAT_DONG'];
}

$studentGroups = [];
foreach ([65, 66] as $cohort) {
    foreach (['CNTT', 'TTMMT', 'HTTT'] as $specialization) {
        $studentGroups[] = ['code' => 'K' . $cohort . '.' . $specialization, 'program' => 'CNTT'];
    }
}
foreach ([67, 68] as $cohort) {
    for ($class = 1; $class <= 4; ++$class) {
        $studentGroups[] = ['code' => 'K' . $cohort . '.CNTT-' . $class, 'program' => 'CNTT'];
    }
}
foreach ([65, 66, 67, 68] as $cohort) {
    for ($class = 1; $class <= 2; ++$class) {
        $studentGroups[] = ['code' => 'K' . $cohort . '.KHMT-' . $class, 'program' => 'KHMT'];
        $studentGroups[] = ['code' => 'K' . $cohort . '.HTTTQL-' . $class, 'program' => 'HTTTQL'];
    }
}

if (count($studentGroups) !== 30) {
    throw new RuntimeException('Số nhóm sinh viên phải là 30.');
}

$sections = [];
$sectionRows = [];
$assignmentRows = [];
$lecturerSections = array_fill(1, 14, []);
for ($index = 0; $index < 56; ++$index) {
    $id = $index + 1;
    $group = $studentGroups[$index % count($studentGroups)];
    $programCourses = $coursesByProgram[$group['program']];
    $courseIndex = ($index * 5 + intdiv($index, count($studentGroups))) % count($programCourses);
    $course = $programCourses[$courseIndex];
    $lecturerId = ($index % 14) + 1;
    $code = 'LHP2026' . sprintf('%03d', $id);
    $size = $course['lab'] ? 30 : 60;
    $section = [
        'id' => $id, 'code' => $code, 'group' => $group['code'], 'course' => $course,
        'lecturerId' => $lecturerId, 'lecturerCode' => $lecturers[$lecturerId]['code'],
        'size' => $size,
    ];
    $sections[$id] = $section;
    $lecturerSections[$lecturerId][] = $id;
    $sectionRows[] = [$id, 1, $course['id'], $code, $group['code'], $size, 2, $course['periods'], 12, $course['lab'] ? 'THUC_HANH' : 'LY_THUYET', 'MO'];
    $assignmentRows[] = [$id, $lecturerId, 'CHINH'];
}
// Một phân công phối hợp để fixture class_conflict có hai giảng viên hợp lệ.
$assignmentRows[] = [1, 2, 'PHOI_HOP'];

$academicSql = "-- Danh mục học thuật tổng hợp; tên học phần đã được làm sạch tối thiểu, chưa phải export học vụ chính thức.\nUSE room_booking;\nSET NAMES utf8mb4;\n\n";
$academicSql .= sqlInsert('HocPhan', ['HocPhanID', 'MaHocPhan', 'TenHocPhan', 'NhomDaoTao', 'LoaiHocPhan', 'SoBuoiMoiTuanMacDinh', 'SoTietMoiBuoiMacDinh', 'SoTuanDayMacDinh', 'YeuCauThietBiJSON', 'TrangThai'], $courseRows);
$academicSql .= sqlInsert('LopHocPhan', ['LopHocPhanID', 'HocKyID', 'HocPhanID', 'MaLopHocPhan', 'MaNhomSinhVien', 'SiSo', 'SoBuoiMoiTuan', 'SoTietMoiBuoi', 'SoTuanDay', 'LoaiBuoiMacDinh', 'TrangThai'], $sectionRows);
$academicSql .= sqlInsert('PhanCongGiangDay', ['LopHocPhanID', 'GiangVienID', 'VaiTroGiangDay'], $assignmentRows);
writeText($seedDir . '/004_academic.sql', $academicSql);

// -----------------------------------------------------------------------------
// 5. Official schedule
// -----------------------------------------------------------------------------

$weeklyCandidatesByLength = [
    2 => [],
    3 => [],
];
for ($day = 0; $day <= 5; ++$day) {
    foreach ([1, 3, 4, 6, 8, 9, 11, 12] as $start) {
        $weeklyCandidatesByLength[2][] = ['day' => $day, 'start' => $start, 'end' => $start + 1];
    }
    foreach ([1, 3, 6, 8, 11] as $start) {
        $weeklyCandidatesByLength[3][] = ['day' => $day, 'start' => $start, 'end' => $start + 2];
    }
}

$weeklyLecturer = [];
$weeklyGroup = [];
$weeklyRoom = [];
$sectionPlans = [];

foreach ($sections as $section) {
    $periodCount = $section['course']['periods'];
    $candidates = $weeklyCandidatesByLength[$periodCount];
    $offset = ($section['id'] * 7) % count($candidates);
    $candidates = array_merge(array_slice($candidates, $offset), array_slice($candidates, 0, $offset));
    $candidateRooms = $section['course']['lab'] ? $labRoomIds : $theoryRoomIds;
    $roomOffset = ($section['id'] * 5) % count($candidateRooms);
    $candidateRooms = array_merge(array_slice($candidateRooms, $roomOffset), array_slice($candidateRooms, 0, $roomOffset));
    $chosen = null;

    foreach ($candidateRooms as $roomId) {
        foreach ($candidates as $first) {
            foreach ($candidates as $second) {
                if ($first['day'] === $second['day'] || abs($first['day'] - $second['day']) < 2) {
                    continue;
                }
                $slots = [$first, $second];
                $free = true;
                foreach ($slots as $slot) {
                    for ($period = $slot['start']; $period <= $slot['end']; ++$period) {
                        $lecturerKey = $section['lecturerId'] . '|' . $slot['day'] . '|' . $period;
                        $groupKey = $section['group'] . '|' . $slot['day'] . '|' . $period;
                        $roomKey = $roomId . '|' . $slot['day'] . '|' . $period;
                        if (isset($weeklyLecturer[$lecturerKey]) || isset($weeklyGroup[$groupKey]) || isset($weeklyRoom[$roomKey])) {
                            $free = false;
                            break 2;
                        }
                    }
                }
                if (!$free) {
                    continue;
                }

                $chosen = ['roomId' => $roomId, 'slots' => $slots];
                break 3;
            }
        }
    }

    if ($chosen === null) {
        throw new RuntimeException('Không xếp được lịch tuần cho ' . $section['code']);
    }

    foreach ($chosen['slots'] as $slot) {
        for ($period = $slot['start']; $period <= $slot['end']; ++$period) {
            $weeklyLecturer[$section['lecturerId'] . '|' . $slot['day'] . '|' . $period] = $section['id'];
            $weeklyGroup[$section['group'] . '|' . $slot['day'] . '|' . $period] = $section['id'];
            $weeklyRoom[$chosen['roomId'] . '|' . $slot['day'] . '|' . $period] = $section['id'];
        }
    }
    $sectionPlans[$section['id']] = $chosen;
}

$roomById = [];
foreach ($rooms as $room) {
    $roomById[$room[0]] = ['id' => $room[0], 'code' => $room[2], 'type' => $room[5], 'capacity' => $room[6]];
}

$events = [];
$roomOccupancy = [];
$lecturerOccupancy = [];
$classOccupancy = [];
$eventId = 0;
$firstMonday = new DateTimeImmutable('2026-09-07');
$teachingWeeks = [1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12];

foreach ($sections as $section) {
    $plan = $sectionPlans[$section['id']];
    foreach ($teachingWeeks as $week) {
        foreach ($plan['slots'] as $slot) {
            ++$eventId;
            $date = dateOfTeachingWeek($firstMonday, $week, $slot['day']);
            $event = [
                'id' => $eventId,
                'code' => 'LCT' . sprintf('%05d', $eventId),
                'date' => $date,
                'roomId' => $plan['roomId'],
                'roomCode' => $roomById[$plan['roomId']]['code'],
                'start' => $slot['start'],
                'end' => $slot['end'],
                'activityType' => 'LICH_HOC',
                'classId' => $section['id'],
                'classCode' => $section['code'],
                'lecturerId' => $section['lecturerId'],
                'lecturerCode' => $section['lecturerCode'],
                'size' => $section['size'],
                'title' => $section['course']['name'],
                'sessionType' => $section['course']['lab'] ? 'THUC_HANH' : 'LY_THUYET',
                'note' => 'Tuần ' . $week . ' - dữ liệu tổng hợp',
                'state' => 'HOAT_DONG',
            ];
            $events[$eventId] = $event;
            reserveKeys($roomOccupancy, periodKeys($event['roomId'], $date, $event['start'], $event['end']), ['type' => 'L', 'id' => $eventId]);
            reserveKeys($lecturerOccupancy, resourceKeys($event['lecturerId'], $date, $event['start'], $event['end']), ['type' => 'L', 'id' => $eventId]);
            reserveKeys($classOccupancy, resourceKeys($event['classId'], $date, $event['start'], $event['end']), ['type' => 'L', 'id' => $eventId]);
        }
    }
}

// Mỗi lớp có một lịch thi tuần 13.
$examDates = [];
for ($day = 0; $day <= 5; ++$day) {
    $examDates[] = dateOfTeachingWeek($firstMonday, 13, $day);
}
$examBlocks = [[1, 2], [3, 4], [6, 7], [8, 9], [11, 12]];
foreach ($sections as $section) {
    $placed = false;
    foreach ($examDates as $date) {
        foreach ($examBlocks as [$start, $end]) {
            foreach ($theoryRoomIds as $roomId) {
                $roomKeys = periodKeys($roomId, $date, $start, $end);
                $classKeys = resourceKeys($section['id'], $date, $start, $end);
                if (!keysAreFree($roomOccupancy, $roomKeys) || !keysAreFree($classOccupancy, $classKeys)) {
                    continue;
                }
                ++$eventId;
                $event = [
                    'id' => $eventId,
                    'code' => 'THI' . sprintf('%05d', $eventId),
                    'date' => $date,
                    'roomId' => $roomId,
                    'roomCode' => $roomById[$roomId]['code'],
                    'start' => $start,
                    'end' => $end,
                    'activityType' => 'LICH_THI',
                    'classId' => $section['id'],
                    'classCode' => $section['code'],
                    'lecturerId' => null,
                    'lecturerCode' => null,
                    'size' => $section['size'],
                    'title' => 'Thi cuối kỳ - ' . $section['course']['name'],
                    'sessionType' => 'THI',
                    'note' => 'Tuần 13',
                    'state' => 'HOAT_DONG',
                ];
                $events[$eventId] = $event;
                reserveKeys($roomOccupancy, $roomKeys, ['type' => 'L', 'id' => $eventId]);
                reserveKeys($classOccupancy, $classKeys, ['type' => 'L', 'id' => $eventId]);
                $placed = true;
                break 3;
            }
        }
    }
    if (!$placed) {
        throw new RuntimeException('Không xếp được lịch thi cho ' . $section['code']);
    }
}

// 1.800 khoảng bận ngoài khoa, chỉ chiếm phòng.
$allDates = [];
for ($date = new DateTimeImmutable('2026-09-05'); $date <= new DateTimeImmutable('2026-12-19'); $date = $date->modify('+1 day')) {
    if ((int) $date->format('N') <= 6) {
        $allDates[] = $date->format('Y-m-d');
    }
}
$externalBlocks = [[1, 2], [3, 5], [6, 7], [8, 10], [11, 13]];
$externalCount = 0;
$attempt = 0;
while ($externalCount < 1800 && $attempt < 200000) {
    ++$attempt;
    $roomId = mt_rand(1, count($rooms));
    $date = $allDates[mt_rand(0, count($allDates) - 1)];
    [$start, $end] = $externalBlocks[mt_rand(0, count($externalBlocks) - 1)];
    $keys = periodKeys($roomId, $date, $start, $end);
    if (!keysAreFree($roomOccupancy, $keys)) {
        continue;
    }
    ++$eventId;
    ++$externalCount;
    $event = [
        'id' => $eventId,
        'code' => 'BNK' . sprintf('%05d', $externalCount),
        'date' => $date,
        'roomId' => $roomId,
        'roomCode' => $roomById[$roomId]['code'],
        'start' => $start,
        'end' => $end,
        'activityType' => 'BAN_NGOAI_KHOA',
        'classId' => null,
        'classCode' => null,
        'lecturerId' => null,
        'lecturerCode' => null,
        'size' => null,
        'title' => 'Phòng đã có lịch đơn vị khác',
        'sessionType' => 'KHAC',
        'note' => 'Khoảng bận tổng hợp ngoài phạm vi khoa',
        'state' => 'HOAT_DONG',
    ];
    $events[$eventId] = $event;
    reserveKeys($roomOccupancy, $keys, ['type' => 'L', 'id' => $eventId]);
}

if ($externalCount !== 1800) {
    throw new RuntimeException('Không sinh đủ khoảng bận ngoài khoa.');
}

ksort($events);
$currentCsvRows = array_map('csvRow', array_values($events));
$currentCsvPath = $validDir . '/official_schedule.csv';
writeCsv($currentCsvPath, CSV_HEADER, $currentCsvRows);

// Replacement: chuyển 25 dòng ngoài khoa sang phòng trống khác, giữ business key.
$replacementEvents = $events;
$replacementOccupancy = [];
foreach ($replacementEvents as $event) {
    reserveKeys($replacementOccupancy, periodKeys($event['roomId'], $event['date'], $event['start'], $event['end']), ['type' => 'L', 'id' => $event['id']]);
}
$moved = 0;
foreach ($replacementEvents as $id => &$event) {
    if ($event['activityType'] !== 'BAN_NGOAI_KHOA' || $moved >= 25) {
        continue;
    }
    $oldKeys = periodKeys($event['roomId'], $event['date'], $event['start'], $event['end']);
    releaseOwnedKeys($replacementOccupancy, $oldKeys, 'L', $event['id']);
    $newRoomId = null;
    foreach ($theoryRoomIds as $candidateRoomId) {
        if ($candidateRoomId === $event['roomId']) {
            continue;
        }
        $newKeys = periodKeys($candidateRoomId, $event['date'], $event['start'], $event['end']);
        if (keysAreFree($replacementOccupancy, $newKeys)) {
            $newRoomId = $candidateRoomId;
            reserveKeys($replacementOccupancy, $newKeys, ['type' => 'L', 'id' => $event['id']]);
            break;
        }
    }
    if ($newRoomId === null) {
        reserveKeys($replacementOccupancy, $oldKeys, ['type' => 'L', 'id' => $event['id']]);
        continue;
    }
    $event['roomId'] = $newRoomId;
    $event['roomCode'] = $roomById[$newRoomId]['code'];
    $event['note'] = 'Điều chỉnh: đổi phòng cho khoảng bận ngoài khoa';
    ++$moved;
}
unset($event);
if ($moved !== 25) {
    throw new RuntimeException('Không tạo đủ 25 thay đổi cho replacement CSV.');
}
$replacementCsvPath = $validDir . '/official_schedule_replacement.csv';
writeCsv($replacementCsvPath, CSV_HEADER, array_map('csvRow', array_values($replacementEvents)));

// -----------------------------------------------------------------------------
// 6. Operational state after current CSV was published
// -----------------------------------------------------------------------------

$releaseRows = [];
$releaseCandidates = array_values(array_filter($events, static fn (array $event): bool =>
    $event['activityType'] === 'LICH_HOC' && $event['date'] >= '2026-10-12'));
usort($releaseCandidates, static fn (array $a, array $b): int => [$a['date'], $a['id']] <=> [$b['date'], $b['id']]);
foreach (array_slice($releaseCandidates, 0, 30) as $index => $event) {
    $requestId = $index + 1;
    $state = $index < 10 ? 'DA_XAC_NHAN' : ($index < 20 ? 'BI_TU_CHOI' : 'CHO_DUYET');
    $processedBy = $state === 'CHO_DUYET' ? null : 15;
    $processedAt = $state === 'CHO_DUYET' ? null : '2026-10-04 10:' . sprintf('%02d', $index) . ':00.000000';
    $reason = $state === 'BI_TU_CHOI' ? 'Lớp vẫn sử dụng phòng theo xác nhận mới nhất' : ($state === 'DA_XAC_NHAN' ? 'Đã xác nhận giảng viên không sử dụng phòng' : null);
    $releaseRows[] = [$requestId, $event['id'], $event['lecturerId'], 'Giảng viên báo bận công tác #' . $requestId, $state, $processedBy, $reason, '2026-10-03 09:' . sprintf('%02d', $index) . ':00.000000', $processedAt];

    if ($state === 'DA_XAC_NHAN') {
        $events[$event['id']]['state'] = 'DA_GIAI_PHONG';
        releaseOwnedKeys($roomOccupancy, periodKeys($event['roomId'], $event['date'], $event['start'], $event['end']), 'L', $event['id']);
        releaseOwnedKeys($lecturerOccupancy, resourceKeys($event['lecturerId'], $event['date'], $event['start'], $event['end']), 'L', $event['id']);
        releaseOwnedKeys($classOccupancy, resourceKeys($event['classId'], $event['date'], $event['start'], $event['end']), 'L', $event['id']);
    }
}

$bookingStates = array_merge(
    array_fill(0, 100, 'TU_DONG_XAC_NHAN'),
    array_fill(0, 60, 'DA_DUYET'),
    array_fill(0, 60, 'CHO_DUYET'),
    array_fill(0, 30, 'BI_TU_CHOI'),
    array_fill(0, 30, 'DA_HUY'),
    array_fill(0, 20, 'CAN_BO_TRI_LAI'),
);
$bookingRows = [];
$bookingRoomRows = [];
$bookingCountByState = [];
$bookingDates = [];
for ($date = new DateTimeImmutable('2026-10-05'); $date <= new DateTimeImmutable('2026-12-15'); $date = $date->modify('+1 day')) {
    $bookingDates[] = $date->format('Y-m-d');
}
$bookingBlocks = [[1, 2], [3, 4], [6, 7], [8, 9], [9, 10], [11, 12]];

foreach ($bookingStates as $index => $state) {
    $bookingId = $index + 1;
    $lecturerId = ($index % 14) + 1;
    $sectionId = $lecturerSections[$lecturerId][$index % count($lecturerSections[$lecturerId])];
    $isTwoRoom = ($bookingId % 25 === 0) && in_array($state, ['DA_DUYET', 'CHO_DUYET'], true);
    $active = in_array($state, ['TU_DONG_XAC_NHAN', 'DA_DUYET'], true);
    $purpose = $isTwoRoom ? 'THUC_HANH_HAI_PHONG' : match ($state) {
        'DA_DUYET', 'CHO_DUYET' => ($bookingId % 2 === 0 ? 'DAY_BU' : 'SU_KIEN'),
        'BI_TU_CHOI' => 'KHAC',
        default => ($bookingId % 2 === 0 ? 'HUONG_DAN' : 'HOC_NHOM'),
    };
    $roomType = $isTwoRoom ? 'THUC_HANH' : (($bookingId % 8 === 0) ? 'THUC_HANH' : 'LY_THUYET');
    $candidateRooms = $roomType === 'THUC_HANH' ? $labRoomIds : $theoryRoomIds;
    $placed = null;

    for ($try = 0; $try < 2000; ++$try) {
        $date = $bookingDates[($bookingId * 17 + $try * 11) % count($bookingDates)];
        $dayOfWeek = (int) (new DateTimeImmutable($date))->format('N');
        if ($state === 'TU_DONG_XAC_NHAN' && $dayOfWeek === 7) {
            continue;
        }
        [$start, $end] = $bookingBlocks[($bookingId + $try) % count($bookingBlocks)];
        $selectedRooms = $isTwoRoom ? $labRoomIds : [$candidateRooms[($bookingId * 7 + $try) % count($candidateRooms)]];
        $roomFree = true;
        foreach ($selectedRooms as $roomId) {
            if (!keysAreFree($roomOccupancy, periodKeys($roomId, $date, $start, $end))) {
                $roomFree = false;
                break;
            }
        }
        if (!$roomFree
            || !keysAreFree($lecturerOccupancy, resourceKeys($lecturerId, $date, $start, $end))
            || !keysAreFree($classOccupancy, resourceKeys($sectionId, $date, $start, $end))) {
            continue;
        }
        $placed = compact('date', 'start', 'end', 'selectedRooms');
        break;
    }

    if ($placed === null) {
        throw new RuntimeException('Không đặt được fixture booking #' . $bookingId);
    }

    $people = $isTwoRoom ? 60 : ($roomType === 'THUC_HANH' ? 28 : 40 + ($bookingId % 21));
    $createdAt = '2026-10-04 ' . sprintf('%02d', 8 + ($bookingId % 10)) . ':' . sprintf('%02d', $bookingId % 60) . ':00.000000';
    $processedBy = in_array($state, ['DA_DUYET', 'BI_TU_CHOI'], true) ? 15 : null;
    $processedAt = $processedBy === null ? null : '2026-10-04 19:' . sprintf('%02d', $bookingId % 60) . ':00.000000';
    $reviewReason = in_array($state, ['DA_DUYET', 'CHO_DUYET'], true) ? ($isTwoRoom ? 'TWO_LABS' : 'PURPOSE_REVIEW') : null;
    $rejectReason = $state === 'BI_TU_CHOI' ? 'Không phù hợp với thứ tự ưu tiên sử dụng phòng' : null;
    $requirements = $roomType === 'THUC_HANH'
        ? [['maThietBi' => 'MAY_TINH_SV', 'soLuong' => 30]]
        : [['maThietBi' => 'MAY_CHIEU', 'soLuong' => 1]];

    $bookingRows[] = [
        $bookingId, 'PDP' . sprintf('%04d', $bookingId), deterministicUuid($bookingId),
        1, $lecturerId, $sectionId, $placed['date'], $placed['start'], $placed['end'],
        $purpose, $people, $roomType, jsonValue($requirements), 'Phiếu dữ liệu tổng hợp #' . $bookingId,
        $state, $reviewReason, $rejectReason, $processedBy, $processedAt, $createdAt, $createdAt,
    ];
    $bookingCountByState[$state] = ($bookingCountByState[$state] ?? 0) + 1;

    if ($isTwoRoom) {
        $bookingRoomRows[] = [$bookingId, $placed['selectedRooms'][0], 30];
        $bookingRoomRows[] = [$bookingId, $placed['selectedRooms'][1], 30];
    } else {
        $bookingRoomRows[] = [$bookingId, $placed['selectedRooms'][0], $people];
    }

    if ($active) {
        foreach ($placed['selectedRooms'] as $roomId) {
            reserveKeys($roomOccupancy, periodKeys($roomId, $placed['date'], $placed['start'], $placed['end']), ['type' => 'P', 'id' => $bookingId]);
        }
        reserveKeys($lecturerOccupancy, resourceKeys($lecturerId, $placed['date'], $placed['start'], $placed['end']), ['type' => 'P', 'id' => $bookingId]);
        reserveKeys($classOccupancy, resourceKeys($sectionId, $placed['date'], $placed['start'], $placed['end']), ['type' => 'P', 'id' => $bookingId]);
    }
}

// Closures are placed only on free room slots in their active state.
$closureRows = [];
for ($id = 1; $id <= 15; ++$id) {
    $state = $id <= 5 ? 'HOAT_DONG' : ($id <= 10 ? 'KET_THUC' : 'DA_HUY');
    $date = $state === 'KET_THUC' ? '2026-09-' . sprintf('%02d', 8 + $id) : '2026-12-' . sprintf('%02d', 1 + $id);
    $roomId = (($id * 3) % count($rooms)) + 1;
    $start = 11;
    $end = 13;
    $tries = 0;
    while ($state === 'HOAT_DONG' && !keysAreFree($roomOccupancy, periodKeys($roomId, $date, $start, $end))) {
        $roomId = ($roomId % count($rooms)) + 1;
        if (++$tries > count($rooms)) {
            throw new RuntimeException('Không bố trí được khoảng khóa phòng.');
        }
    }
    $closureRows[] = [
        $id, $roomId, $date, $date, $start, $end,
        $id % 2 === 0 ? 'SU_CO' : 'BAO_TRI',
        $id % 2 === 0 ? 'KHAN_CAP' : 'KE_HOACH',
        ($id % 2 === 0 ? 'Xử lý sự cố phòng #' : 'Bảo trì phòng #') . $id, $state, 15,
        '2026-10-04 07:' . sprintf('%02d', $id) . ':00.000000',
        $state === 'KET_THUC' ? '2026-10-01 17:00:00.000000' : null,
    ];
}

// Build final slot rows from the occupancy map.
$slotRows = [];
ksort($roomOccupancy);
foreach ($roomOccupancy as $key => $owner) {
    [$roomId, $date, $period] = explode('|', $key);
    $slotRows[] = [
        (int) $roomId, $date, (int) $period,
        $owner['type'] === 'L' ? 'LICH_CHINH_THUC' : 'PHIEU_DAT_PHONG',
        $owner['type'] === 'L' ? $owner['id'] : null,
        $owner['type'] === 'P' ? $owner['id'] : null,
        '2026-10-04 12:00:00.000000',
    ];
}

$scheduleRows = [];
foreach ($events as $event) {
    $scheduleRows[] = [
        $event['id'], 1, $event['code'], $event['date'], $event['roomId'], $event['start'], $event['end'],
        $event['activityType'], $event['classId'], $event['lecturerId'], $event['size'], $event['title'],
        $event['sessionType'], $event['note'], $event['state'], null,
    ];
}

$notificationRows = [];
$notificationId = 0;
foreach ($bookingRows as $booking) {
    ++$notificationId;
    $notificationRows[] = [$notificationId, $booking[4], 'Cập nhật phiếu ' . $booking[1], 'Phiếu đang ở trạng thái ' . $booking[14], 'PHIEU', $booking[0], $booking[0] % 3 === 0, $booking[19], $booking[0] % 3 === 0 ? $booking[20] : null];
}
foreach ($releaseRows as $release) {
    ++$notificationId;
    $notificationRows[] = [$notificationId, $release[2], 'Yêu cầu giải phóng lịch', 'Yêu cầu đang ở trạng thái ' . $release[4], 'GIAI_PHONG', $release[0], false, $release[7], null];
}
for ($userId = 1; $userId <= 15; ++$userId) {
    ++$notificationId;
    $notificationRows[] = [$notificationId, $userId, 'Lịch chính thức đã phát hành', 'Đợt lịch chính thức đã được phát hành.', 'IMPORT', 1, $userId % 2 === 0, '2026-10-04 08:30:00.000000', $userId % 2 === 0 ? '2026-10-04 09:00:00.000000' : null];
}

$auditRows = [];
$auditId = 0;
foreach ($bookingRows as $booking) {
    ++$auditId;
    $auditRows[] = [$auditId, $booking[4], 'BOOKING_SEEDED', 'PhieuDatPhong', (string) $booking[0], null, jsonValue(['trangThai' => $booking[14]]), '127.0.0.1', $booking[19]];
}
++ $auditId;
$auditRows[] = [$auditId, 15, 'SCHEDULE_PUBLISHED', 'DotImportLich', '1', null, jsonValue(['soDong' => count($events)]), '127.0.0.1', '2026-10-04 08:30:00.000000'];
foreach ($releaseRows as $release) {
    ++$auditId;
    $auditRows[] = [$auditId, $release[2], 'RELEASE_REQUEST_SEEDED', 'YeuCauGiaiPhongLich', (string) $release[0], null, jsonValue(['trangThai' => $release[4]]), '127.0.0.1', $release[7]];
}
foreach ($closureRows as $closure) {
    ++$auditId;
    $auditRows[] = [$auditId, 15, 'ROOM_CLOSURE_SEEDED', 'PhongBiKhoa', (string) $closure[0], null, jsonValue(['trangThai' => $closure[9]]), '127.0.0.1', $closure[11]];
}

$currentHash = hash_file('sha256', $currentCsvPath);
$operationalSql = "-- Trạng thái vận hành sau khi official_schedule.csv đã được phát hành.\n";
$operationalSql .= "USE room_booking;\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 1;\nSTART TRANSACTION;\n\n";
$operationalSql .= sqlInsert('DotImportLich', ['DotImportLichID', 'HocKyID', 'TenTep', 'MaBamSHA256', 'TrangThai', 'SoDong', 'SoDongLoi', 'BaoCaoLoiJSON', 'NguoiTaiID', 'TaiLenLuc', 'PhatHanhLuc'], [[1, 1, 'official_schedule.csv', $currentHash, 'DA_PHAT_HANH', count($events), 0, jsonValue([]), 15, '2026-10-04 08:00:00.000000', '2026-10-04 08:30:00.000000']]);
$operationalSql .= sqlInsert('LichChinhThuc', ['LichChinhThucID', 'DotImportLichID', 'MaDong', 'Ngay', 'PhongID', 'TietBatDau', 'TietKetThuc', 'LoaiHoatDong', 'LopHocPhanID', 'GiangVienID', 'SiSo', 'TenHoatDong', 'LoaiBuoi', 'GhiChu', 'TrangThai', 'LyDoCanBoTriLai'], $scheduleRows);
$operationalSql .= sqlInsert('PhieuDatPhong', ['PhieuDatPhongID', 'MaPhieu', 'MaGui', 'HocKyID', 'NguoiYeuCauID', 'LopHocPhanID', 'Ngay', 'TietBatDau', 'TietKetThuc', 'MucDich', 'SoNguoi', 'LoaiPhongYeuCau', 'YeuCauThietBiJSON', 'GhiChu', 'TrangThai', 'LyDoChuyenDuyet', 'LyDoTuChoi', 'NguoiXuLyID', 'XuLyLuc', 'TaoLuc', 'CapNhatLuc'], $bookingRows);
$operationalSql .= sqlInsert('PhieuDatPhongPhong', ['PhieuDatPhongID', 'PhongID', 'SoNguoiDuKien'], $bookingRoomRows);
$operationalSql .= sqlInsert('SlotPhong', ['PhongID', 'Ngay', 'SoTiet', 'LoaiNguon', 'LichChinhThucID', 'PhieuDatPhongID', 'TaoLuc'], $slotRows);
$operationalSql .= sqlInsert('YeuCauGiaiPhongLich', ['YeuCauGiaiPhongLichID', 'LichChinhThucID', 'NguoiYeuCauID', 'LyDo', 'TrangThai', 'NguoiXuLyID', 'LyDoXuLy', 'TaoLuc', 'XuLyLuc'], $releaseRows);
$operationalSql .= sqlInsert('PhongBiKhoa', ['PhongBiKhoaID', 'PhongID', 'TuNgay', 'DenNgay', 'TietBatDau', 'TietKetThuc', 'Loai', 'MucDo', 'LyDo', 'TrangThai', 'NguoiTaoID', 'TaoLuc', 'KetThucLuc'], $closureRows);
$operationalSql .= sqlInsert('ThongBao', ['ThongBaoID', 'NguoiNhanID', 'TieuDe', 'NoiDung', 'LoaiDoiTu', 'DoiTuID', 'DaDoc', 'TaoLuc', 'DocLuc'], $notificationRows);
$operationalSql .= sqlInsert('NhatKyHeThong', ['NhatKyHeThongID', 'NguoiThucHienID', 'HanhDong', 'LoaiDoiTu', 'DoiTuID', 'DuLieuTruocJSON', 'DuLieuSauJSON', 'DiaChiIP', 'TaoLuc'], $auditRows);
$operationalSql .= "UPDATE HocKy SET DotImportHienHanhID = 1, TrangThai = 'MO_DAT_PHONG' WHERE HocKyID = 1;\n\nCOMMIT;\n";
writeText($seedDir . '/005_operational.sql', $operationalSql);

// -----------------------------------------------------------------------------
// 7. Intentionally invalid CSV fixtures
// -----------------------------------------------------------------------------

$base = csvRow(array_values(array_filter($events, static fn (array $event): bool => $event['activityType'] === 'LICH_HOC'))[0]);
$second = csvRow(array_values(array_filter($events, static fn (array $event): bool => $event['activityType'] === 'LICH_HOC'))[1]);

writeCsv($invalidDir . '/invalid_header.csv', array_slice(CSV_HEADER, 0, -1), [array_slice($base, 0, -1)]);

$roomConflictA = $base;
$roomConflictB = $second;
$roomConflictA[1] = 'ERR_ROOM_001';
$roomConflictB[1] = 'ERR_ROOM_002';
$roomConflictB[3] = $roomConflictA[3];
$roomConflictB[4] = $roomConflictA[4];
$roomConflictB[5] = $roomConflictA[5];
$roomConflictB[6] = $roomConflictA[6];
writeCsv($invalidDir . '/room_conflict.csv', CSV_HEADER, [$roomConflictA, $roomConflictB]);

$lecturerConflictA = $base;
$lecturerConflictB = $second;
$lecturerConflictA[1] = 'ERR_LECTURER_001';
$lecturerConflictB[1] = 'ERR_LECTURER_002';
$lecturerConflictB[3] = $lecturerConflictA[3];
$lecturerConflictB[5] = $lecturerConflictA[5];
$lecturerConflictB[6] = $lecturerConflictA[6];
$lecturerConflictB[9] = $lecturerConflictA[9];
$lecturerConflictB[4] = $lecturerConflictA[4] === 'G2.101' ? 'G2.102' : 'G2.101';
$lecturerConflictB[8] = 'LHP2026015';
writeCsv($invalidDir . '/lecturer_conflict.csv', CSV_HEADER, [$lecturerConflictA, $lecturerConflictB]);

$classConflictA = $base;
$classConflictB = $base;
$classConflictA[1] = 'ERR_CLASS_001';
$classConflictB[1] = 'ERR_CLASS_002';
$classConflictB[4] = $classConflictA[4] === 'G2.101' ? 'G2.102' : 'G2.101';
$classConflictB[9] = 'GV002';
writeCsv($invalidDir . '/class_conflict.csv', CSV_HEADER, [$classConflictA, $classConflictB]);

$capacityRow = $base;
$capacityRow[1] = 'ERR_CAPACITY_001';
$capacityRow[4] = 'G2.101';
$capacityRow[10] = '100';
writeCsv($invalidDir . '/capacity_exceeded.csv', CSV_HEADER, [$capacityRow]);

$assignmentRow = $base;
$assignmentRow[1] = 'ERR_ASSIGNMENT_001';
$assignmentRow[9] = 'GV999';
writeCsv($invalidDir . '/assignment_missing.csv', CSV_HEADER, [$assignmentRow]);

// -----------------------------------------------------------------------------
// 8. Manifest
// -----------------------------------------------------------------------------

$generatedFiles = [
    'database/seeds/001_reference.sql',
    'database/seeds/002_facilities.sql',
    'database/seeds/003_lecturers.sql',
    'database/seeds/004_academic.sql',
    'database/seeds/005_operational.sql',
    'database/fixtures/csv/valid/official_schedule.csv',
    'database/fixtures/csv/valid/official_schedule_replacement.csv',
    'database/fixtures/csv/invalid/invalid_header.csv',
    'database/fixtures/csv/invalid/room_conflict.csv',
    'database/fixtures/csv/invalid/lecturer_conflict.csv',
    'database/fixtures/csv/invalid/class_conflict.csv',
    'database/fixtures/csv/invalid/capacity_exceeded.csv',
    'database/fixtures/csv/invalid/assignment_missing.csv',
];
$hashes = [];
foreach ($generatedFiles as $relativePath) {
    $hashes[$relativePath] = hash_file('sha256', $root . '/' . $relativePath);
}

$manifest = [
    'dataset' => 'NTU_CNTT_ROOM_BOOKING',
    'seed' => DATA_SEED,
    'generatedAt' => '2026-10-04T12:00:00+07:00',
    'databaseTarget' => ['MySQL 8.x', 'MariaDB 10.4.x'],
    'counts' => [
        'tables' => 20,
        'buildings' => count($buildings),
        'rooms' => count($rooms),
        'equipmentTypes' => count($devices),
        'activeLecturers' => count($lecturers),
        'studentGroups' => count($studentGroups),
        'courses' => count($courses),
        'courseSections' => count($sections),
        'officialScheduleRows' => count($events),
        'internalScheduleRows' => count(array_filter($events, static fn (array $event): bool => $event['activityType'] !== 'BAN_NGOAI_KHOA')),
        'externalBusyRows' => $externalCount,
        'roomSlots' => count($slotRows),
        'bookings' => count($bookingRows),
        'bookingsByState' => $bookingCountByState,
        'releaseRequests' => count($releaseRows),
        'roomClosures' => count($closureRows),
        'notifications' => count($notificationRows),
        'auditRows' => count($auditRows),
        'replacementChangedRows' => $moved,
    ],
    'assumptions' => [
        'Dữ liệu được tổng hợp để phát triển và chưa phải dữ liệu vận hành chính thức của trường.',
        'Khung giờ dùng đúng bảng 13 tiết hiện có; mâu thuẫn tiết 3+8/3+9 chưa được xác nhận.',
        'Tuần 8 không xếp buổi học thường; tuần 13 dùng cho lịch thi.',
        'NĐN.101 và NĐN.102 là hai phòng thực hành 30 chỗ.',
        'Các khoảng BAN_NGOAI_KHOA chỉ là occupancy tổng hợp để tăng độ phủ dữ liệu.',
    ],
    'files' => $hashes,
];
writeText($fixtureDir . '/data-manifest.json', jsonValue($manifest) . PHP_EOL);

echo "Đã sinh bộ dữ liệu phát triển.\n";
foreach ($manifest['counts'] as $name => $count) {
    echo str_pad($name, 28) . ': ' . (is_array($count) ? jsonValue($count) : $count) . "\n";
}
