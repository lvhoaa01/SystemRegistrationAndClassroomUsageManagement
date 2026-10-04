# NTU CNTT Room Booking

Khung Modular Monolith cho bài tập lớn PHP 8.2 + MySQL 8/MariaDB 10.4. Phạm vi chỉ gồm Khoa Công nghệ Thông tin – Trường Đại học Nha Trang.

## Khởi tạo nhanh trên XAMPP

```powershell
C:\DDrive\xampp\php\php.exe scripts\data\generate_data.php
C:\DDrive\xampp\php\php.exe scripts\data\validate_data.php
```

Tạo database bằng phpMyAdmin hoặc CLI rồi chạy theo thứ tự:

```text
database/migrations/001_create_schema.sql
database/seeds/001_reference.sql
database/seeds/002_facilities.sql
database/seeds/003_lecturers.sql
database/seeds/004_academic.sql
database/seeds/005_operational.sql
```

Sau khi nạp, chạy `database/validation/001_integrity_checks.sql`; toàn bộ cột `Violations` phải bằng `0`.

Hai CSV hợp lệ nằm tại `database/fixtures/csv/valid/`. Các CSV trong `invalid/` cố ý sai để kiểm thử importer.

## Ranh giới quan trọng

- Toàn bộ dữ liệu đi kèm là dữ liệu tổng hợp phục vụ phát triển, không phải dữ liệu vận hành chính thức của trường.
- `SlotPhong` là ledger duy nhất chống trùng phòng.
- Controller không gọi PDO trực tiếp; thay đổi trạng thái đi qua service và transaction.
- Mỗi module sở hữu bảng của mình như mô tả trong `DESIGN_ROOM_BOOKING_DRAFT.md`.
