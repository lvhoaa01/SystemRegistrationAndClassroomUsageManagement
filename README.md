# Accommodation Booking

MVP marketplace tìm kiếm và đặt chỗ lưu trú đa cơ sở, chỉ phạm vi Accommodation. Dự án dùng PHP 8.2 + MySQL 8/MariaDB 10.4 và kiến trúc Modular Monolith feature-first.

Dữ liệu khởi tạo hiện chỉ thuộc Thành phố Hồ Chí Minh. Tên của 30 cơ sở lấy từ danh mục nguồn; địa chỉ là dữ liệu đối chiếu từ nguồn công khai và được lưu provenance riêng. Schema 22 bảng chỉ dành ba trường trực tiếp trên cơ sở cho kết quả Google Places (`GooglePlaceID`, trạng thái, thời điểm xác minh), chưa gọi API và không có bảng cache Google. Loại phòng, tiện nghi, giá, tồn phòng và dữ liệu giao dịch là dữ liệu tổng hợp phục vụ phát triển.

Đặc tả chính:

- `huongDan/DESCRIPTION_HOTEL_BOOKING_DRAFT.md`
- `huongDan/DESIGN_HOTEL_BOOKING_DRAFT.md`
- `huongDan/SCHEMA_MVP_REDUCTION_AUDIT.md`
- `huongDan/DOI_CHIEU_CSLT_TPHCM_GOOGLE_MAPS.md`

Nguồn dữ liệu cơ sở:

- `huongDan/data/CoSoLuuTruDuLich.xlsx`: tệp nguồn được giữ nguyên;
- `database/source/hcmc_accommodations_verified.csv`: 30 dòng đã làm sạch và đối chiếu mà bộ sinh sử dụng.

## Sinh và kiểm tra dữ liệu

```powershell
C:\DDrive\xampp\php\php.exe scripts\data\generate_data.php
C:\DDrive\xampp\php\php.exe scripts\data\validate_data.php
```

## Import bằng phpMyAdmin

Chạy đúng thứ tự trên database sạch:

```text
database/migrations/001_create_schema.sql
database/seeds/001_reference.sql
database/seeds/002_accounts_partners.sql
database/seeds/003_properties.sql
database/seeds/004_products_policies.sql
database/seeds/005_inventory_prices.sql
database/seeds/006_operational.sql
database/validation/001_integrity_checks.sql
```

Toàn bộ dòng `Violations` phải bằng `0`. Các file trong `database/fixtures/invalid/` cố ý vi phạm ràng buộc và không thuộc thứ tự import bình thường.

Database mặc định: `hotel_booking`.

Tài khoản khởi tạo:

- admin: `ADMIN001`;
- partner: `DT001` đến `DT010`;
- customer: `KH0001` đến `KH0500`;
- mật khẩu chung: `Ntu@123456`.

## Bất biến quan trọng

- Inventory dùng chung ở mức `LoaiPhong + NgayLuuTru`, không tách kho theo sản phẩm phòng.
- `SoLuongDaGiu` phải khớp ledger `GiuTonPhongDem` đang active.
- Preview không giữ inventory, có expiry và single-use.
- `NO_SHOW` chốt phí rồi nhả toàn bộ hold còn active; phí và quyền bán lại tồn là hai vấn đề độc lập.
- `PAY_AT_PROPERTY` luôn có payment `NOT_TRACKED`; nền tảng không ghi nhận tiền mặt thực tế tại cơ sở.
- Không gọi payment gateway trong transaction đang giữ row lock.
- Partner chỉ truy cập property thuộc organization của mình.
- Chỉ danh tính và địa chỉ cơ sở là dữ liệu đã đối chiếu; dữ liệu vận hành đi kèm là dữ liệu tổng hợp, không phải dữ liệu của Booking.com hoặc của khách sạn thật.
