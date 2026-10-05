-- Expected failure: a booking can have at most one review.
USE hotel_booking;
INSERT INTO DanhGia (DatChoID, CoSoLuuTruID, KhachHangID, DiemTong, TrangThai)
SELECT DatChoID, CoSoLuuTruID, KhachHangID, 10, 'PUBLISHED'
FROM DatCho WHERE DatChoID = 1;
