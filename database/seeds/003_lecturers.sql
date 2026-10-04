-- Mật khẩu khởi tạo dùng chung: Ntu@123456
USE room_booking;
SET NAMES utf8mb4;

INSERT INTO NguoiDung (NguoiDungID, MaNguoiDung, HoTen, Email, MatKhauHash, VaiTro, LaGiangVien, TrangThaiGiangDay, TrangThaiTaiKhoan, TaoLuc, CapNhatLuc) VALUES
  (1, 'GV001', 'Phạm Thị Thu Thúy', 'gv001@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (2, 'GV002', 'Nguyễn Mạnh Cương', 'gv002@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (3, 'GV003', 'Nguyễn Đình Hưng', 'gv003@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (4, 'GV004', 'Phạm Văn Nam', 'gv004@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (5, 'GV005', 'Nguyễn Thủy Đoan Trang', 'gv005@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (6, 'GV006', 'Huỳnh Tuấn Anh', 'gv006@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (7, 'GV007', 'Nguyễn Thị Hương Lý', 'gv007@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (8, 'GV008', 'Bùi Thị Hồng Minh', 'gv008@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (9, 'GV009', 'Ngô Nguyễn Tường Nghi', 'gv009@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (10, 'GV010', 'Nguyễn Văn Rạng', 'gv010@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (11, 'GV011', 'Nguyễn Đình Hoàng Sơn', 'gv011@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (12, 'GV012', 'Bùi Chí Thành', 'gv012@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (13, 'GV013', 'Mai Cường Thọ', 'gv013@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (14, 'GV014', 'Nguyễn Hải Triều', 'gv014@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'GIANG_VIEN', 1, 'DANG_DAY', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (15, 'QL001', 'Cán bộ quản lý', 'quanly@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'QUAN_LY', 0, 'KHONG_AP_DUNG', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000'),
  (16, 'ADMIN001', 'Quản trị viên', 'admin@ntu.edu.vn', '$2y$10$6e1V.I7AYrvoS3vtwUz4/OEXinJJfGTpCboBR6Dcn121CiiwcUmoO', 'ADMIN', 0, 'KHONG_AP_DUNG', 'HOAT_DONG', '2026-09-01 08:00:00.000000', '2026-09-01 08:00:00.000000');

