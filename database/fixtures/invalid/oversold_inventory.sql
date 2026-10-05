-- Expected failure: CHECK constraint prevents reserved inventory above total.
USE hotel_booking;
UPDATE TonPhongNgay
SET SoLuongDaGiu = TongSoLuong + 1
WHERE LoaiPhongID = 1 AND NgayLuuTru = '2026-10-05';
