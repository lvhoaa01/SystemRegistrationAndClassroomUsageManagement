# KIỂM KÊ VÀ GIẢM SCHEMA MVP ĐẶT CHỖ LƯU TRÚ

**Ngày chốt:** 06/10/2026  
**Schema trước:** 38 bảng  
**Schema mục tiêu:** 22 bảng  
**Mức giảm:** 16 bảng (`MERGE`: 14, `REMOVE`: 2, `ADD`: 0)

## 1. Nguyên tắc chốt

Schema mới vẫn là mini OTA nhiều cơ sở, không phải CRUD khách sạn. Những ranh giới không được phá gồm:

- cơ sở, loại phòng, sản phẩm, tồn, đơn và thanh toán là các khái niệm riêng;
- tồn được quản lý theo `LoaiPhongID + NgayLuuTru`, không đặt trên sản phẩm;
- booking nhiều phòng luôn có `HangMucDatCho`;
- preview không giữ tồn;
- tạo, hủy, đổi ngày và no-show cập nhật bộ đếm cùng ledger trong một giao dịch;
- giá và chính sách lịch sử được chụp lại, không tính lại từ danh mục hiện tại;
- JSON chỉ dùng cho danh sách con thuộc một aggregate, tồn tại ngắn hoặc ít cần truy vấn độc lập.

## 2. Danh sách chính xác 22 bảng cuối

| # | Bảng mới | Module | Trách nhiệm | Giữ/gộp từ bảng cũ |
|---:|---|---|---|---|
| 1 | `NguoiDung` | Identity | tài khoản khách, đối tác, quản trị | giữ |
| 2 | `ToChucDoiTac` | Identity | tổ chức sở hữu nhiều cơ sở | giữ |
| 3 | `ThanhVienDoiTac` | Identity | thành viên và ranh giới tổ chức | giữ |
| 4 | `TienNghi` | Properties | danh mục tiện nghi có thể lọc | giữ |
| 5 | `CoSoLuuTru` | Properties | hồ sơ, duyệt, provenance, loại cơ sở, Google Place ID, ảnh, cấu hình thuế/phí | giữ và nhận dữ liệu từ 5 bảng cũ |
| 6 | `LoaiPhong` | Properties | sức chứa, cấu hình giường, ảnh loại phòng | giữ và nhận `AnhLoaiPhong` |
| 7 | `CoSoTienNghi` | Properties | tiện nghi cấp cơ sở | giữ |
| 8 | `LoaiPhongTienNghi` | Properties | tiện nghi cấp loại phòng | giữ |
| 9 | `ChinhSachHuy` | Commercial | mẫu hủy và phí no-show theo cơ sở | giữ |
| 10 | `SanPhamPhong` | Commercial | sản phẩm bán: loại phòng, tên gói, bữa ăn, thanh toán, chính sách | giữ và nhận `GoiGia`, `GoiBuaAn` |
| 11 | `GiaPhongNgay` | Commercial | giá và điều kiện mở bán theo sản phẩm/ngày | giữ, bổ sung restriction |
| 12 | `KhuyenMai` | Commercial | khuyến mãi phần trăm cấp cơ sở | giữ, giản lược phạm vi áp dụng |
| 13 | `TonPhongNgay` | Inventory | số lượng mở bán và đang giữ theo loại phòng/ngày | giữ |
| 14 | `GiuTonPhongDem` | Inventory | ledger giữ/trả tồn theo booking item/ngày | giữ |
| 15 | `XemTruocDatCho` | Bookings | token, tổng tiền, allocation và snapshot JSON tối đa 15 phút | giữ và nhận `XemTruocHangMuc` |
| 16 | `DatCho` | Bookings | aggregate root, trạng thái, hủy trực tiếp, no-show, idempotency | giữ và nhận metadata hủy |
| 17 | `HangMucDatCho` | Bookings | một phòng được đặt, khách JSON, snapshot sản phẩm/chính sách | giữ và nhận `KhachLuuTru` |
| 18 | `GiaDemDatCho` | Bookings | bản chụp giá từng đêm để đối soát và báo cáo | giữ |
| 19 | `ThanhToan` | Bookings | payment mock một-một với booking, retry và refund đơn giản | giữ và nhận `LanThanhToan`, `HoanTien` |
| 20 | `YeuCauHuyMienPhi` | Bookings | yêu cầu waiver và quyết định của đối tác | giữ |
| 21 | `DanhGia` | Reviews | đánh giá đã xác minh theo booking | giữ |
| 22 | `NhatKyHeThong` | Admin | audit và lịch sử sửa đơn/thử thanh toán | giữ và nhận `DieuChinhDatCho` |

```text
OLD TABLE COUNT = 38
NEW TABLE COUNT = 22
REDUCTION = 16 tables
MERGED = 14 tables
REMOVED = 2 tables
ADDED = 0 tables
```

## 3. Kiểm kê 38 bảng cũ và quyết định migration

| # | Bảng cũ | Mục đích cũ | Có cần độc lập trong MVP? | Xử lý | Bảng mới/nơi lưu |
|---:|---|---|---|---|---|
| 1 | `ThanhPho` | danh mục thành phố | Không, phạm vi chỉ TP.HCM | MERGE | `CoSoLuuTru.MaThanhPho`, CHECK chỉ `HCM` |
| 2 | `LoaiCoSo` | danh mục loại cơ sở | Không, tập giá trị nhỏ và ổn định | MERGE | `CoSoLuuTru.LoaiCoSo`, CHECK |
| 3 | `TienNghi` | tiện nghi dùng để lọc | Có | KEEP | `TienNghi` |
| 4 | `GoiBuaAn` | danh mục bữa ăn nhỏ | Không | MERGE | `SanPhamPhong.LoaiBuaAn`, CHECK |
| 5 | `NguoiDung` | tài khoản và vai trò | Có | KEEP | `NguoiDung` |
| 6 | `ToChucDoiTac` | tổ chức sở hữu cơ sở | Có | KEEP | `ToChucDoiTac` |
| 7 | `ThanhVienDoiTac` | nhiều nhân viên/tổ chức | Có | KEEP | `ThanhVienDoiTac` |
| 8 | `CoSoLuuTru` | aggregate cơ sở | Có | KEEP | `CoSoLuuTru` mở rộng có kiểm soát |
| 9 | `DoiChieuGooglePlaces` | nhiều ứng viên Google | Chưa phải use case MVP | MERGE | ba trường Google trên `CoSoLuuTru` |
| 10 | `BoNhoDemGooglePlaces` | cache Places 30 ngày | Không cần persistence nghiệp vụ | REMOVE | cache tệp/session khi tích hợp sau |
| 11 | `AnhCoSo` | danh sách ảnh cơ sở | Không cần query độc lập | MERGE | `CoSoLuuTru.AnhJSON` |
| 12 | `LoaiPhong` | sức chứa/cấu hình phòng | Có | KEEP | `LoaiPhong` |
| 13 | `AnhLoaiPhong` | danh sách ảnh loại phòng | Không cần query độc lập | MERGE | `LoaiPhong.AnhJSON` |
| 14 | `CoSoTienNghi` | lọc tiện nghi cơ sở | Có | KEEP | `CoSoTienNghi` |
| 15 | `LoaiPhongTienNghi` | lọc tiện nghi phòng | Có | KEEP | `LoaiPhongTienNghi` |
| 16 | `ThuePhiCoSo` | thuế/phí cấu hình theo thời gian | Không cần lịch hiệu lực trong MVP | MERGE | các cột thuế/phí trên `CoSoLuuTru`; booking giữ snapshot |
| 17 | `ChinhSachHuy` | mẫu chính sách dùng lại | Có | KEEP | `ChinhSachHuy` |
| 18 | `GoiGia` | tên gói giá | Không khác aggregate sản phẩm đủ lớn | MERGE | mã/tên trực tiếp trên `SanPhamPhong` |
| 19 | `SanPhamPhong` | đơn vị bán | Có | KEEP | `SanPhamPhong` |
| 20 | `TonPhongNgay` | tồn theo loại phòng/ngày | Có | KEEP | `TonPhongNgay` |
| 21 | `GiaPhongNgay` | giá theo sản phẩm/ngày | Có | KEEP | thêm đóng bán, min/max stay, advance days |
| 22 | `KhuyenMai` | khuyến mãi | Có, nhưng chỉ cấp cơ sở | KEEP | `KhuyenMai` |
| 23 | `KhuyenMaiSanPham` | ánh xạ khuyến mãi/sản phẩm | Không sau khi giản lược khuyến mãi cấp cơ sở | MERGE | quy tắc áp dụng trong `KhuyenMai` |
| 24 | `XemTruocDatCho` | preview có hạn | Có | KEEP | `XemTruocDatCho` |
| 25 | `XemTruocHangMuc` | item preview ngắn hạn | Không | MERGE | `XemTruocDatCho.PhanBoJSON` |
| 26 | `DatCho` | aggregate booking | Có | KEEP | `DatCho` bổ sung metadata hủy/no-show |
| 27 | `HangMucDatCho` | mỗi phòng trong booking | Có | KEEP | `HangMucDatCho` |
| 28 | `KhachLuuTru` | danh sách khách theo phòng | Không cần query độc lập | MERGE | `HangMucDatCho.KhachJSON` |
| 29 | `GiaDemDatCho` | snapshot giá từng đêm | Có | KEEP | `GiaDemDatCho` |
| 30 | `GiuTonPhongDem` | ledger chống overselling | Có | KEEP | `GiuTonPhongDem` |
| 31 | `DieuChinhDatCho` | lịch sử sửa đơn | Không cần aggregate riêng | MERGE | trạng thái hiện tại trong booking; trước/sau ở `NhatKyHeThong` |
| 32 | `ThanhToan` | payment aggregate | Có | KEEP | `ThanhToan` mở rộng retry/refund |
| 33 | `LanThanhToan` | từng payment attempt | Không với cổng mô phỏng | MERGE | số lần, idempotency và kết quả cuối trong `ThanhToan`; từng lần ở audit |
| 34 | `HoanTien` | nhiều refund/payment | Không, MVP chỉ một kết quả hoàn cho hủy toàn đơn | MERGE | các cột hoàn tiền trên `ThanhToan` |
| 35 | `YeuCauHuyMienPhi` | waiver có người xử lý | Có | KEEP | `YeuCauHuyMienPhi` |
| 36 | `DanhGia` | review có kiểm chứng | Có | KEEP | `DanhGia` |
| 37 | `ThongBao` | notification center | Không phải năng lực lõi | REMOVE | thông báo phiên/giao diện; gửi thật để giai đoạn sau |
| 38 | `NhatKyHeThong` | audit append-only | Có | KEEP | `NhatKyHeThong` |

## 4. Vì sao các phép gộp không làm thành “God Table”

- `CoSoLuuTru` chỉ nhận metadata thuộc chính hồ sơ cơ sở: loại, provenance, ảnh, thuế/phí đơn giản và một Google Place ID. Nó không nhận phòng, giá, tồn hay booking.
- `SanPhamPhong` nhận tên gói và loại bữa ăn vì các giá trị này chỉ mô tả chính sản phẩm bán; tồn vẫn ở `TonPhongNgay`, giá theo ngày vẫn ở `GiaPhongNgay`.
- `XemTruocDatCho.PhanBoJSON` chứa dữ liệu tồn tại tối đa 15 phút và luôn được kiểm tra lại. Không dùng JSON này làm nguồn quyết định cuối.
- `HangMucDatCho.KhachJSON` là danh sách con thuộc đúng một phòng được đặt; dữ liệu giá từng đêm vẫn chuẩn hóa trong `GiaDemDatCho`.
- `ThanhToan` chỉ giữ một payment aggregate cho một booking. Chi tiết thử lại không còn là nghiệp vụ cần báo cáo độc lập và được audit.
- `DatCho` nhận metadata hủy/no-show vì đây là kết quả kết thúc vòng đời booking, không phải một aggregate bán hàng khác.

## 5. Kiểm tra không mất năng lực lõi

| Năng lực | Bảng/cơ chế sau giảm | Kết luận |
|---|---|---|
| nhiều cơ sở và tổ chức đối tác | `ToChucDoiTac`, `ThanhVienDoiTac`, `CoSoLuuTru` | giữ nguyên |
| loại phòng và sản phẩm | `LoaiPhong`, `SanPhamPhong` | giữ nguyên ranh giới |
| giá và điều kiện bán | `GiaPhongNgay` | rõ hơn schema cũ |
| tồn theo ngày | `TonPhongNgay` | giữ nguyên |
| tìm kiếm/availability | query giá + tồn; revalidate | giữ nguyên |
| preview | `XemTruocDatCho` + JSON có schema | giữ, không hold |
| booking nhiều phòng | `DatCho`, `HangMucDatCho` | giữ nguyên |
| lịch sử giá/chính sách | `HangMucDatCho`, `GiaDemDatCho` | giữ nguyên |
| chống overselling | row lock + counter + `GiuTonPhongDem` | giữ nguyên |
| trả tại cơ sở/online mock | `ThanhToan` một-một booking | đơn giản nhưng đủ trạng thái |
| hủy và hoàn tiền | metadata `DatCho` + `ThanhToan` + ledger + audit | đủ idempotency và linkage |
| đổi ngày | booking snapshots + ledger + audit | giữ rollback an toàn |
| no-show | fee snapshot + release ledger cùng transaction | sửa mâu thuẫn cũ |
| review | `DanhGia` | giữ nguyên |
| admin duyệt property | state/approval fields trên `CoSoLuuTru` + audit | giữ nguyên |
| báo cáo | query 22 bảng; không có bảng báo cáo riêng | đủ MVP |

## 6. Rủi ro và kiểm soát

| Trade-off | Rủi ro | Kiểm soát |
|---|---|---|
| ảnh lưu JSON | khó query từng ảnh | ảnh chỉ hiển thị theo aggregate; kiểm tra schema JSON ở application |
| khách lưu trú lưu JSON | không tìm kiếm khách theo SQL | MVP không có use case đó; giới hạn PII và validate từng phần tử |
| preview item lưu JSON | không có FK tới từng product bên trong | preview ngắn hạn, lưu fingerprint và revalidate toàn bộ trước booking |
| bỏ payment attempt table | ít chi tiết báo cáo retry | giữ `SoLanThu`, kết quả cuối và audit từng lần |
| bỏ refund table | không hỗ trợ nhiều đợt refund | MVP hủy toàn đơn và tối đa một quy trình refund |
| Google metadata nằm trên property | không lưu nhiều ứng viên | MVP chỉ lưu kết quả đã duyệt; candidate/cache để ngoài DB khi tích hợp sau |
| promotion cấp cơ sở | không chọn subset sản phẩm | chấp nhận để giảm bảng; snapshot booking vẫn chính xác |

## 7. Kết luận audit

Schema 22 bảng nằm trong mục tiêu 18–25, giảm được 16 bảng mà không xóa aggregate lõi hoặc cơ chế transaction. Không cần bảng mới. Việc giảm tập trung vào danh mục nhỏ, dữ liệu con 1-n ít cần query, cache tích hợp ngoài lõi và độ sâu payment/notification không cần thiết cho bài tập lớn.
