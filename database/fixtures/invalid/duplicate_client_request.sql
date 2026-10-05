-- Expected failure: one customer/client request ID may create only one booking.
USE hotel_booking;
START TRANSACTION;
INSERT INTO XemTruocDatCho (
  XemTruocDatChoID, TokenHash, KhachHangID, CoSoLuuTruID, NgayNhanPhong,
  NgayTraPhong, TienTe, TongTien, SoTienTraNgay, SoTienTraTaiCoSo,
  PhanBoJSON, Fingerprint, TrangThai, HetHanLuc, TaoLuc, DaDungLuc
)
SELECT 2001, SHA2('invalid-duplicate-client-request', 256), KhachHangID,
       CoSoLuuTruID, NgayNhanPhong, NgayTraPhong, TienTe, TongTien,
       SoTienTraNgay, SoTienTraTaiCoSo, PhanBoJSON, SHA2('invalid-fingerprint', 256),
       'USED', '2027-01-01 00:00:00.000000', TaoLuc, DaDungLuc
FROM XemTruocDatCho WHERE XemTruocDatChoID = 1;
INSERT INTO DatCho (
  MaDatCho, ClientRequestID, XemTruocDatChoID, KhachHangID, CoSoLuuTruID,
  NgayNhanPhong, NgayTraPhong, TenNguoiDat, EmailNguoiDat, DienThoaiNguoiDat,
  ThoiDiemThanhToan, TienTe, TongTien, TrangThai
)
SELECT 'BK9999999999', ClientRequestID, 2001, KhachHangID, CoSoLuuTruID,
       NgayNhanPhong, NgayTraPhong, TenNguoiDat, EmailNguoiDat, DienThoaiNguoiDat,
       ThoiDiemThanhToan, TienTe, TongTien, TrangThai
FROM DatCho WHERE DatChoID = 1;
ROLLBACK;
