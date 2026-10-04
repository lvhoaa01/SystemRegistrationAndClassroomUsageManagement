-- Dữ liệu tham chiếu phục vụ phát triển.
USE room_booking;
SET NAMES utf8mb4;

INSERT INTO HocKy (HocKyID, MaHocKy, TenHocKy, NgayBatDau, NgayKetThuc, TrangThai, DotImportHienHanhID, SoGioBaoTruocTuDong, SoNgayDatTruocToiDa, SoTietToiDaMoiPhieu, SoPhieuHoatDongToiDaMoiGV, TuanTamNghi, TuanThiCuoiKy) VALUES
  (1, '2026-HK1', 'Học kỳ 1 năm học 2026-2027', '2026-09-05', '2026-12-20', 'NHAP', NULL, 24, 45, 5, 8, 8, 13);

INSERT INTO KhungTiet (KhungTietID, HocKyID, SoTiet, GioBatDau, GioKetThuc, Buoi) VALUES
  (1, 1, 1, '07:00:00', '07:50:00', 'SANG'),
  (2, 1, 2, '07:50:00', '08:40:00', 'SANG'),
  (3, 1, 3, '08:40:00', '09:50:00', 'SANG'),
  (4, 1, 4, '09:50:00', '10:40:00', 'SANG'),
  (5, 1, 5, '10:40:00', '11:30:00', 'SANG'),
  (6, 1, 6, '13:00:00', '13:50:00', 'CHIEU'),
  (7, 1, 7, '13:50:00', '14:40:00', 'CHIEU'),
  (8, 1, 8, '14:40:00', '15:50:00', 'CHIEU'),
  (9, 1, 9, '15:50:00', '16:40:00', 'CHIEU'),
  (10, 1, 10, '16:40:00', '17:30:00', 'CHIEU'),
  (11, 1, 11, '18:30:00', '19:20:00', 'TOI'),
  (12, 1, 12, '19:20:00', '20:10:00', 'TOI'),
  (13, 1, 13, '20:10:00', '21:00:00', 'TOI');

INSERT INTO NgayNghi (NgayNghiID, HocKyID, Ngay, LyDo) VALUES
  (1, 1, '2026-12-20', 'Ngày nghỉ cuối học kỳ');

INSERT INTO ThietBi (ThietBiID, MaThietBi, TenThietBi, DonViTinh, TrangThai) VALUES
  (1, 'MAY_CHIEU', 'Máy chiếu', 'cái', 'HOAT_DONG'),
  (2, 'BANG_TRANG', 'Bảng trắng', 'cái', 'HOAT_DONG'),
  (3, 'DIEU_HOA', 'Điều hòa', 'cái', 'HOAT_DONG'),
  (4, 'INTERNET', 'Kết nối Internet', 'bộ', 'HOAT_DONG'),
  (5, 'MAY_TINH_GV', 'Máy tính giảng viên', 'cái', 'HOAT_DONG'),
  (6, 'MAY_TINH_SV', 'Máy tính sinh viên', 'cái', 'HOAT_DONG'),
  (7, 'AM_THANH', 'Hệ thống âm thanh', 'bộ', 'HOAT_DONG'),
  (8, 'LAB_MANG', 'Bộ thiết bị thực hành mạng', 'bộ', 'HOAT_DONG');

