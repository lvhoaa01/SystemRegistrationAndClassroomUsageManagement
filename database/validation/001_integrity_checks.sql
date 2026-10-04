USE room_booking;

-- Mỗi kết quả phải bằng 0.
SELECT 'slot_bad_lich_state' AS CheckName, COUNT(*) AS Violations
FROM SlotPhong s
JOIN LichChinhThuc l ON l.LichChinhThucID = s.LichChinhThucID
WHERE s.LoaiNguon = 'LICH_CHINH_THUC' AND l.TrangThai <> 'HOAT_DONG';

SELECT 'slot_bad_booking_state' AS CheckName, COUNT(*) AS Violations
FROM SlotPhong s
JOIN PhieuDatPhong p ON p.PhieuDatPhongID = s.PhieuDatPhongID
WHERE s.LoaiNguon = 'PHIEU_DAT_PHONG'
  AND p.TrangThai NOT IN ('TU_DONG_XAC_NHAN', 'DA_DUYET');

SELECT 'active_lich_slot_mismatch' AS CheckName, COUNT(*) AS Violations
FROM (
  SELECT l.LichChinhThucID,
         l.TietKetThuc - l.TietBatDau + 1 AS ExpectedSlots,
         COUNT(s.SoTiet) AS ActualSlots
  FROM LichChinhThuc l
  LEFT JOIN SlotPhong s ON s.LichChinhThucID = l.LichChinhThucID
  WHERE l.TrangThai = 'HOAT_DONG'
  GROUP BY l.LichChinhThucID
  HAVING ExpectedSlots <> ActualSlots
) mismatches;

SELECT 'active_booking_slot_mismatch' AS CheckName, COUNT(*) AS Violations
FROM (
  SELECT p.PhieuDatPhongID,
         (p.TietKetThuc - p.TietBatDau + 1) * COUNT(DISTINCT pp.PhongID) AS ExpectedSlots,
         COUNT(DISTINCT CONCAT(s.PhongID, '/', s.SoTiet)) AS ActualSlots
  FROM PhieuDatPhong p
  JOIN PhieuDatPhongPhong pp ON pp.PhieuDatPhongID = p.PhieuDatPhongID
  LEFT JOIN SlotPhong s ON s.PhieuDatPhongID = p.PhieuDatPhongID
  WHERE p.TrangThai IN ('TU_DONG_XAC_NHAN', 'DA_DUYET')
  GROUP BY p.PhieuDatPhongID
  HAVING ExpectedSlots <> ActualSlots
) mismatches;

SELECT 'official_lecturer_overlap' AS CheckName, COUNT(*) AS Violations
FROM LichChinhThuc a
JOIN LichChinhThuc b
  ON a.LichChinhThucID < b.LichChinhThucID
 AND a.GiangVienID = b.GiangVienID
 AND a.Ngay = b.Ngay
 AND a.TietBatDau <= b.TietKetThuc
 AND a.TietKetThuc >= b.TietBatDau
WHERE a.TrangThai = 'HOAT_DONG'
  AND b.TrangThai = 'HOAT_DONG'
  AND a.GiangVienID IS NOT NULL;

SELECT 'official_class_overlap' AS CheckName, COUNT(*) AS Violations
FROM LichChinhThuc a
JOIN LichChinhThuc b
  ON a.LichChinhThucID < b.LichChinhThucID
 AND a.LopHocPhanID = b.LopHocPhanID
 AND a.Ngay = b.Ngay
 AND a.TietBatDau <= b.TietKetThuc
 AND a.TietKetThuc >= b.TietBatDau
WHERE a.TrangThai = 'HOAT_DONG'
  AND b.TrangThai = 'HOAT_DONG'
  AND a.LopHocPhanID IS NOT NULL;

SELECT 'booking_lecturer_overlap' AS CheckName, COUNT(*) AS Violations
FROM PhieuDatPhong a
JOIN PhieuDatPhong b
  ON a.PhieuDatPhongID < b.PhieuDatPhongID
 AND a.NguoiYeuCauID = b.NguoiYeuCauID
 AND a.Ngay = b.Ngay
 AND a.TietBatDau <= b.TietKetThuc
 AND a.TietKetThuc >= b.TietBatDau
WHERE a.TrangThai IN ('TU_DONG_XAC_NHAN', 'DA_DUYET')
  AND b.TrangThai IN ('TU_DONG_XAC_NHAN', 'DA_DUYET');

SELECT 'official_booking_lecturer_overlap' AS CheckName, COUNT(*) AS Violations
FROM LichChinhThuc l
JOIN PhieuDatPhong p
  ON l.GiangVienID = p.NguoiYeuCauID
 AND l.Ngay = p.Ngay
 AND l.TietBatDau <= p.TietKetThuc
 AND l.TietKetThuc >= p.TietBatDau
WHERE l.TrangThai = 'HOAT_DONG'
  AND p.TrangThai IN ('TU_DONG_XAC_NHAN', 'DA_DUYET');

SELECT 'active_closure_slot_overlap' AS CheckName, COUNT(*) AS Violations
FROM PhongBiKhoa c
JOIN SlotPhong s
  ON s.PhongID = c.PhongID
 AND s.Ngay BETWEEN c.TuNgay AND c.DenNgay
 AND (c.TietBatDau IS NULL OR s.SoTiet BETWEEN c.TietBatDau AND c.TietKetThuc)
WHERE c.TrangThai = 'HOAT_DONG';

SELECT 'official_capacity_exceeded' AS CheckName, COUNT(*) AS Violations
FROM LichChinhThuc l
JOIN Phong p ON p.PhongID = l.PhongID
WHERE l.SiSo IS NOT NULL AND l.SiSo > p.SucChua;

SELECT 'booking_capacity_exceeded' AS CheckName, COUNT(*) AS Violations
FROM PhieuDatPhongPhong pp
JOIN Phong p ON p.PhongID = pp.PhongID
WHERE pp.SoNguoiDuKien > p.SucChua;
