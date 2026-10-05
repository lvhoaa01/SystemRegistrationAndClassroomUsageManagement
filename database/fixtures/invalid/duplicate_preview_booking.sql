-- Expected failure: a preview can create at most one booking.
USE hotel_booking;
INSERT INTO DatCho (
  MaDatCho, ClientRequestID, XemTruocDatChoID, KhachHangID, CoSoLuuTruID,
  NgayNhanPhong, NgayTraPhong, TenNguoiDat, EmailNguoiDat, DienThoaiNguoiDat,
  ThoiDiemThanhToan, TienTe, TongTien, TrangThai
)
SELECT 'BK9999999998', 'ffffffff-ffff-4fff-8fff-ffffffffffff', XemTruocDatChoID,
       KhachHangID, CoSoLuuTruID, NgayNhanPhong, NgayTraPhong, TenNguoiDat,
       EmailNguoiDat, DienThoaiNguoiDat, ThoiDiemThanhToan, TienTe, TongTien, TrangThai
FROM DatCho WHERE DatChoID = 1;
