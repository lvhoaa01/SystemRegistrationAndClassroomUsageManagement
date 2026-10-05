# THIẾT KẾ KỸ THUẬT HỆ THỐNG TÌM KIẾM VÀ ĐẶT CHỖ LƯU TRÚ

**Phạm vi:** Modular Monolith cho mini OTA nhiều cơ sở lưu trú  
**Phiên bản:** 0.4 – tương ứng đặc tả nghiệp vụ 0.6  
**Ngày cập nhật:** 06/10/2026  
**Nền tảng:** PHP 8.2, MySQL 8/MariaDB 10.4, HTML/CSS/JavaScript; không bắt buộc framework

---

## 1. Mục tiêu thiết kế

Thiết kế phải đủ nghiêm túc cho booking thực tế nhưng vừa sức bài tập lớn:

- một tiến trình PHP và một cơ sở dữ liệu;
- kiến trúc nguyên khối chia module theo chức năng;
- chính xác 22 bảng;
- transaction inventory rõ và kiểm thử được;
- không microservice, hàng đợi, Redis hoặc công cụ tìm kiếm riêng;
- không tạo abstraction chỉ để giống hệ thống production lớn.

Ba nhóm giao diện:

- khách: tìm kiếm → chi tiết → preview → booking → quản lý → review;
- đối tác: cơ sở → phòng/sản phẩm → lịch giá/tồn → booking/waiver/no-show → báo cáo;
- quản trị: duyệt cơ sở → tài khoản → tiện nghi → review → audit/báo cáo.

## 2. Quy ước kiến trúc

Mỗi module có cấu trúc tối thiểu:

```text
src/Modules/<Module>/
├── Domain/          # rule/value object cần thiết
├── Application/     # use case, DTO, transaction boundary
├── Infrastructure/  # PDO repository, storage/cache adapter
├── Http/            # controller và request validation
└── README.md        # ownership, contract và invariant
```

Luồng phụ thuộc:

```text
Route → Middleware → Controller → Application Service → Repository → PDO
                                      ↓
                               Domain rules
```

Quy tắc:

- controller không dùng PDO trực tiếp;
- repository không quyết định chuyển trạng thái;
- application service mở transaction cho use case ghi;
- module khác gọi contract công khai, không tự sửa bảng không thuộc mình;
- Search/Reports được dùng truy vấn đọc tối ưu nhưng không ghi nghiệp vụ;
- timestamp lưu UTC; ngày lưu trú là `DATE` theo `Asia/Ho_Chi_Minh`.

---

## 3. Cây thư mục mục tiêu

```text
public/
├── index.php
└── assets/{css,js,images}/
src/
├── Bootstrap/
├── Config/
├── Http/Middleware/
├── Modules/
│   ├── Identity/
│   ├── Properties/
│   ├── Commercial/
│   ├── Inventory/
│   ├── Search/
│   ├── Bookings/
│   ├── Reviews/
│   └── Admin/
├── Shared/{Clock,Csv,Database,Security,Storage}/
└── routes/{web.php,api.php,console.php}
views/
├── layouts/
├── components/
└── modules/{customer,partner,admin}/
database/{migrations,seeds,source,fixtures,validation}/
scripts/data/
storage/{uploads,logs,tmp,cache}/
tests/{Unit,Integration,Concurrency,Fixtures}/
huongDan/
├── DESCRIPTION_HOTEL_BOOKING_DRAFT.md
├── DESIGN_HOTEL_BOOKING_DRAFT.md
└── SCHEMA_MVP_REDUCTION_AUDIT.md
```

Không còn module độc lập `Partners`, `Catalog`, `Pricing`, `Payments`, `Cancellations`, `Notifications` hoặc `Reports`. Trách nhiệm của chúng được nhóm vào aggregate gần nhất, không bị xóa nghiệp vụ lõi.

---

## 4. Module ownership và số bảng

| Module | Bảng sở hữu | Số bảng | Trách nhiệm |
|---|---|---:|---|
| Identity | `NguoiDung`, `ToChucDoiTac`, `ThanhVienDoiTac` | 3 | đăng nhập, vai trò, ranh giới tổ chức |
| Properties | `TienNghi`, `CoSoLuuTru`, `LoaiPhong`, `CoSoTienNghi`, `LoaiPhongTienNghi` | 5 | hồ sơ, duyệt, phòng, ảnh JSON, tiện nghi, provenance |
| Commercial | `ChinhSachHuy`, `SanPhamPhong`, `GiaPhongNgay`, `KhuyenMai` | 4 | product, giá, restriction, policy, promotion |
| Inventory | `TonPhongNgay`, `GiuTonPhongDem` | 2 | availability, counter, ledger, lock/release |
| Search | không sở hữu bảng | 0 | lọc, phân bổ, tính giá đọc |
| Bookings | `XemTruocDatCho`, `DatCho`, `HangMucDatCho`, `GiaDemDatCho`, `ThanhToan`, `YeuCauHuyMienPhi` | 6 | preview, booking, payment mock, cancel/modify/no-show |
| Reviews | `DanhGia` | 1 | review có booking xác minh |
| Admin | `NhatKyHeThong` | 1 | audit, support query và báo cáo đọc |
| **Tổng** |  | **22** |  |

`GiuTonPhongDem` do Inventory sở hữu nhưng tham chiếu `HangMucDatCho`. Bookings phải gọi `InventoryReservationService` trong cùng transaction, không tự cập nhật counter rời rạc.

---

## 5. Mô hình dữ liệu 22 bảng

### 5.1. Identity — 3 bảng

| Bảng | Khóa/ràng buộc chính |
|---|---|
| `NguoiDung` | mã và email duy nhất; vai trò `CUSTOMER/PARTNER/ADMIN`; password hash; trạng thái |
| `ToChucDoiTac` | mã duy nhất; tên, liên hệ, trạng thái |
| `ThanhVienDoiTac` | khóa kép tổ chức–người dùng; một user partner thuộc tối đa một tổ chức trong MVP |

Quyền partner không suy ra từ route. Mọi repository partner nhận bắt buộc `ToChucDoiTacID` và ghép qua ownership của `CoSoLuuTru`.

### 5.2. Properties — 5 bảng

#### `TienNghi`

- mã, tên, phạm vi `PROPERTY/ROOM`, trạng thái;
- giữ độc lập vì search cần lọc many-to-many bằng SQL.

#### `CoSoLuuTru`

Nhóm trường:

- định danh/ownership: mã, tổ chức, slug;
- phạm vi cố định: `MaThanhPho='HCM'`, `LoaiCoSo` CHECK;
- nội dung: tên, địa chỉ, mô tả, giờ nhận/trả, múi giờ, `AnhJSON`;
- provenance tên: `TenTrongNguon`, `NguonTen`, `MaBanGhiNguon`;
- provenance địa chỉ: `NguonDiaChi`, `DiaChiXacMinhLuc`;
- Google: `GooglePlaceID`, `GoogleMatchStatus`, `GoogleVerifiedAt`;
- tọa độ lâu dài: vĩ/kinh độ, nguồn, lúc xác minh; tất cả cùng NULL hoặc cùng có giá trị;
- thuế/phí: tỷ lệ VAT, tỷ lệ phí dịch vụ, cờ đã bao gồm;
- duyệt: trạng thái, người duyệt, lúc duyệt, ghi chú;
- metadata tạo/cập nhật.

`GooglePlaceID` là nullable unique. Không lưu cache Places trong database. `GoogleMatchStatus` chỉ gồm `NOT_CHECKED/MATCHED/AMBIGUOUS/NOT_FOUND`.

`AnhJSON` là mảng tối đa 12 phần tử:

```json
[{"path":"/uploads/properties/CS001/a.jpg","alt":"Mặt tiền","sort":1,"cover":true}]
```

Application bảo đảm đúng một ảnh cover khi mảng không rỗng.

#### `LoaiPhong`

- thuộc một cơ sở; mã duy nhất trong cơ sở;
- sức chứa tổng/người lớn/trẻ em;
- `CauHinhGiuongJSON`, diện tích, hút thuốc, `AnhJSON`, trạng thái;
- JSON giường chỉ nhận type allow-list và quantity nguyên dương.

#### Hai bảng nối tiện nghi

- `CoSoTienNghi(CoSoLuuTruID, TienNghiID, MienPhi, GhiChu)`;
- `LoaiPhongTienNghi(LoaiPhongID, TienNghiID, SoLuong, GhiChu)`;
- service kiểm tra scope tiện nghi đúng `PROPERTY` hoặc `ROOM`.

### 5.3. Commercial — 4 bảng

#### `ChinhSachHuy`

- thuộc cơ sở;
- mã/tên, loại policy;
- giờ miễn phí, loại phạt, giá trị phạt;
- tỷ lệ phí no-show;
- trạng thái.

Không cần `YeuCauTraTruoc`; payment timing nằm trên product và policy snapshot mô tả khoản cần trả.

#### `SanPhamPhong`

- thuộc `LoaiPhong` và `ChinhSachHuy` cùng cơ sở;
- mã/tên sản phẩm;
- `LoaiBuaAn`: `ROOM_ONLY/BREAKFAST_INCLUDED/HALF_BOARD`;
- `ThoiDiemThanhToan`: `PAY_AT_PROPERTY/PAY_ONLINE`;
- trạng thái.

Không còn `GoiGia` hoặc `GoiBuaAn`. Không đặt inventory trên product.

#### `GiaPhongNgay`

```text
PRIMARY KEY (SanPhamPhongID, NgayLuuTru)
GiaCoBan > 0
DongBan = 0/1
1 <= SoDemToiThieu <= 30
SoDemToiDa IS NULL OR SoDemToiThieu <= SoDemToiDa <= 30
SoNgayDatTruoc >= 0
PhienBan >= 1
```

Mọi đêm phải có giá/mở bán; restriction dùng dòng ngày nhận. Việc cập nhật rate tăng `PhienBan` để fingerprint preview phát hiện stale.

#### `KhuyenMai`

- thuộc một cơ sở và áp dụng toàn bộ product đang hoạt động của cơ sở;
- phần trăm giảm, cửa sổ đặt, cửa sổ lưu trú, trạng thái;
- không còn `KhuyenMaiSanPham`.

### 5.4. Inventory — 2 bảng

#### `TonPhongNgay`

```text
PRIMARY KEY (LoaiPhongID, NgayLuuTru)
TongSoLuong >= 0
0 <= SoLuongDaGiu <= TongSoLuong
PhienBan >= 1
```

`TongSoLuong` là tồn mở bán trên nền tảng. Đóng bán nằm ở `GiaPhongNgay`, tránh hai nguồn quyết định.

#### `GiuTonPhongDem`

- unique `HangMucDatChoID + NgayLuuTru`;
- loại phòng, ngày, trạng thái `ACTIVE/RELEASED`;
- hạn hold chỉ có với booking `PENDING_PAYMENT`;
- thời điểm/lý do release;
- một dòng tương ứng một phòng trong một đêm.

### 5.5. Bookings — 6 bảng

#### `XemTruocDatCho`

- token hash unique, customer, property, ngày;
- tổng, trả ngay, trả tại cơ sở, tiền tệ;
- `PhanBoJSON`, fingerprint, trạng thái, hạn, thời điểm dùng;
- trạng thái `VALID/USED/INVALIDATED`; hết hạn được suy ra từ `HetHanLuc`.

Schema `PhanBoJSON`:

```json
{
  "version": 1,
  "rooms": [{
    "roomOrder": 1,
    "productId": 10,
    "roomTypeId": 3,
    "adults": 2,
    "childAges": [7],
    "nightPrices": [],
    "cancellationSnapshot": {},
    "paymentSnapshot": {}
  }]
}
```

Preview là dữ liệu máy chủ, không lấy JSON do client gửi lại làm nguồn tin cậy.

#### `DatCho`

Ngoài dữ liệu đầu booking và trạng thái, phải có:

- `ClientRequestID`, preview unique, public code;
- payment timing, tổng tiền, contact và yêu cầu đặc biệt;
- hạn thanh toán, lúc xác nhận/hoàn tất;
- hủy: `HuyIdempotencyKey` nullable unique, người/lúc/lý do, phí, số hoàn, snapshot JSON;
- no-show: người/lúc, phí, snapshot JSON.

FK `NguoiHuyID` và `NguoiDanhDauNoShowID` trỏ `NguoiDung`. CHECK bảo đảm metadata chỉ xuất hiện phù hợp trạng thái.

#### `HangMucDatCho`

- một dòng là một phòng được đặt;
- FK booking, product, room type; thứ tự phòng duy nhất trong booking;
- snapshot tên loại phòng, tên sản phẩm, bữa ăn;
- `KhachJSON` gồm người lớn/trẻ em và tuổi;
- tổng hạng mục, snapshot điều kiện và hủy.

Application kiểm tra số người trong JSON khớp sức chứa đã chụp.

#### `GiaDemDatCho`

- PK item + ngày;
- giá gốc, giảm, thuế/phí đã gồm, khoản trả tại cơ sở, thành tiền;
- promotion nullable và snapshot JSON;
- mọi ngày đúng khoảng `[check-in, checkout)`.

#### `ThanhToan`

Một dòng duy nhất cho booking:

- timing và trạng thái `NOT_TRACKED/PROCESSING/PAID/FAILED`;
- tổng cần trả, đã trả, đã hoàn;
- provider mock, `IdempotencyKey` nullable unique, provider reference, số lần thử, lỗi cuối;
- refund: idempotency key, trạng thái `NONE/PENDING/SUCCEEDED/FAILED`, reference, lý do, lúc hoàn tất;
- metadata tạo/cập nhật.

Với `PAY_AT_PROPERTY`, trạng thái luôn `NOT_TRACKED`, không có idempotency/provider reference và hệ thống không cập nhật tiền khách trả tại cơ sở.

#### `YeuCauHuyMienPhi`

- booking, người yêu cầu, lý do;
- trạng thái, người xử lý, phản hồi và thời điểm;
- service + row lock bảo đảm tối đa một request `PENDING` cho booking.

### 5.6. Reviews và Admin — 2 bảng

#### `DanhGia`

- booking unique, property, customer;
- điểm tổng 1–10 và các điểm thành phần nullable;
- bình luận, trạng thái `PUBLISHED/HIDDEN`, thời điểm.

#### `NhatKyHeThong`

- actor, action, entity, entity ID;
- `RequestID` nullable unique để hỗ trợ idempotency cho modification/command không có khóa riêng;
- before/after JSON đã loại bí mật/PII không cần thiết;
- IP và thời điểm.

Audit là append-only ở tầng ứng dụng. Không dùng audit thay cho trạng thái nghiệp vụ hiện tại.

---

## 6. Search và phân bổ phòng

Đầu vào chuẩn hóa:

```text
Destination = HCM hoặc keyword
CheckIn, CheckOut
RoomAllocations[{adults, childAges[]}]
Filters + Sort + Page
```

Pipeline:

1. kiểm tra giới hạn ngày/phòng/khách;
2. lọc `CoSoLuuTru.ACTIVE`, phạm vi HCM và filter property;
3. lấy room type/product active;
4. bắt buộc đủ `GiaPhongNgay` cho mọi đêm và `DongBan=0`;
5. áp dụng min/max stay và advance days từ dòng ngày nhận;
6. tính `MIN(TongSoLuong-SoLuongDaGiu)` theo room type trên mọi đêm;
7. backtracking tối đa 5 allocation, trừ tồn tạm theo room type;
8. tính tổng và chọn tổ hợp rẻ nhất;
9. sắp xếp/phân trang.

Search không khóa dòng và có thể stale. Trang chi tiết, preview và transaction booking luôn tính lại.

## 7. Bộ tính giá

`PriceCalculator` nhận product, ngày, thời điểm đặt và allocation; trả:

```text
NightPrice[]
Subtotal
DiscountTotal
IncludedTaxFeeTotal
PayAtPropertyTotal
GrandTotal
SelectedPromotion
CancellationSnapshot
PaymentSnapshot
Fingerprint
```

Giá trị tiền dùng integer VND hoặc `DECIMAL(15,0)`. Snapshot bao gồm version rate, cấu hình thuế/phí và policy để booking phát hiện thay đổi.

---

## 8. Transaction tạo booking

### 8.1. Phần chung

```text
BEGIN
  SELECT XemTruocDatCho FOR UPDATE
  validate token, owner, VALID, not expired, not used
  load and revalidate products/rates/restrictions/fingerprint
  aggregate demand by LoaiPhongID + NgayLuuTru
  SELECT TonPhongNgay FOR UPDATE theo LoaiPhongID, NgayLuuTru
  validate all inventory rows and availability
  INSERT DatCho
  INSERT HangMucDatCho + GiaDemDatCho snapshots
  INSERT GiuTonPhongDem ACTIVE
  UPDATE TonPhongNgay counters
  INSERT ThanhToan initial state
  UPDATE preview USED
  INSERT audit
COMMIT
```

Unique `(KhachHangID, ClientRequestID)` và unique preview trên booking xử lý double-click. Khi gặp duplicate, service đọc và trả booking đã có.

### 8.2. PAY_AT_PROPERTY

- booking tạo thẳng `CONFIRMED`;
- ledger không có expiry;
- payment `NOT_TRACKED`;
- không có lifecycle thu tiền mặt tại khách sạn.

### 8.3. PAY_ONLINE

- booking `PENDING_PAYMENT`;
- ledger expiry 10 phút;
- payment `PROCESSING`, idempotency key ổn định;
- commit rồi mới gọi mock/sandbox adapter.

Kết quả provider:

- thành công: transaction ngắn khóa booking/payment, chuyển `PAID + CONFIRMED`;
- thất bại/hết hạn: khóa booking, active ledger và inventory; chuyển `FAILED + PAYMENT_FAILED`, release đúng một lần;
- callback/retry đọc trạng thái hiện tại trước khi ghi;
- mỗi attempt tăng `SoLanThu` và ghi audit, không insert payment mới.

---

## 9. Hủy, waiver, sửa ngày và no-show

### 9.1. Hủy trực tiếp

1. đọc snapshot và tính fee preview;
2. nhận `HuyIdempotencyKey` khi khách xác nhận;
3. transaction khóa booking → ledger active → inventory theo thứ tự;
4. kiểm tra lại fee/time/state;
5. ghi metadata hủy trên booking;
6. release ledger/counter đúng một lần;
7. nếu cần hoàn online, chuyển refund substate sang `PENDING`;
8. audit và commit;
9. gọi adapter ngoài transaction;
10. transaction ngắn hoàn tất refund `SUCCEEDED/FAILED`.

Booking liên kết payment bằng quan hệ one-to-one nên không cần `HoanTienID`. Validator kiểm tra `DatCho.SoTienHoan = ThanhToan.DaHoan` khi refund thành công.

### 9.2. Waiver

- `PENDING` không đổi booking/tồn;
- partner cùng organization mới được xử lý;
- approve gọi cancel primitive fee 0;
- reject/withdraw chỉ đóng request;
- retry approve không release hoặc refund lần hai.

### 9.3. Sửa ngày

- tạo preview thay đổi trong bộ nhớ/application, không hold;
- transaction khóa booking;
- hợp nhất inventory cũ/mới, sort rồi khóa;
- nếu ngày mới không đủ, rollback không ghi gì;
- nếu đủ, release ledger cũ, tạo ledger mới, cập nhật counter;
- thay ngày booking, snapshots item và nightly price;
- insert audit before/after với `RequestID` unique;
- không dùng bảng `DieuChinhDatCho`.

### 9.4. No-show

```text
BEGIN
  lock CONFIRMED booking
  validate local no-show window
  calculate fee from snapshot
  lock active ledgers + inventory
  release every active ledger and decrement counters
  set booking NO_SHOW + actor/time/fee/snapshot
  insert audit
COMMIT
```

`NO_SHOW` tuyệt đối không còn ledger `ACTIVE`. Phí và release inventory là hai kết quả độc lập trong cùng command.

### 9.5. Completion

Tác vụ hằng ngày xử lý từng booking đủ điều kiện trong transaction nhỏ: release active ledger, chuyển `COMPLETED`, ghi audit. Chạy lại không tạo side effect.

---

## 10. Route map tối thiểu

### Khách/public

```text
GET  /search
GET  /properties/{slug}
POST /previews
GET  /checkout/{token}
POST /bookings
GET  /account/bookings
GET  /account/bookings/{code}
POST /account/bookings/{code}/cancel-preview
POST /account/bookings/{code}/cancel
POST /account/bookings/{code}/waiver
POST /account/bookings/{code}/date-change-preview
POST /account/bookings/{code}/date-change
POST /account/bookings/{code}/review
```

### Đối tác

```text
/partner/properties
/partner/properties/{id}/rooms
/partner/properties/{id}/products
/partner/properties/{id}/calendar
/partner/properties/{id}/promotions
/partner/bookings
/partner/bookings/{code}/no-show
/partner/cancellation-requests
/partner/reports
```

### Quản trị

```text
/admin/properties/review
/admin/accounts
/admin/facilities
/admin/reviews
/admin/bookings
/admin/audit
/admin/reports
```

Mọi mutation dùng CSRF và kiểm tra quyền đối tượng ở server. Không có route notification center, Google candidate hoặc danh mục payment/meal/property type trong MVP.

---

## 11. Bảo mật và dữ liệu cá nhân

- rotate session ID sau đăng nhập;
- cookie `HttpOnly`, `SameSite=Lax`, `Secure` khi HTTPS;
- CSRF token cho POST/PATCH/DELETE;
- prepared statement và allow-list sort;
- token preview chỉ lưu hash; booking code opaque;
- JSON khách chỉ lưu dữ liệu cần cho kỳ ở;
- không log password, token thô, PAN, CVV hoặc provider secret;
- upload kiểm tra MIME/kích thước, tên ngẫu nhiên, ngoài vùng thực thi;
- partner repository bắt buộc organization scope;
- admin support access luôn tạo audit.

---

## 12. Index và EXPLAIN

Index bắt buộc:

- property: slug unique; `(MaThanhPho, TrangThai, LoaiCoSo)`; `(ToChucDoiTacID, TrangThai)`; Google Place ID unique nullable;
- room type: `(CoSoLuuTruID, TrangThai)`;
- facility maps: PK hai FK và index đảo theo `TienNghiID` để lọc;
- product: `(LoaiPhongID, TrangThai)`, combination unique;
- daily rate: PK `(SanPhamPhongID, NgayLuuTru)`, index `(NgayLuuTru, DongBan, GiaCoBan)`;
- inventory: PK `(LoaiPhongID, NgayLuuTru)`;
- promotion: `(CoSoLuuTruID, TrangThai, DatTu, DatDen, LuuTruTu, LuuTruDen)`;
- preview: token unique, `(TrangThai, HetHanLuc)`, customer/time;
- booking: public code unique, preview unique, `(KhachHangID, ClientRequestID)` unique, customer/time, property/check-in/state;
- item: `(DatChoID, ThuTuPhong)` unique;
- nightly price: PK item/date, index date;
- ledger: item/date unique, `(LoaiPhongID, NgayLuuTru, TrangThai)`, expiry;
- payment: booking unique, payment/refund idempotency keys unique nullable;
- waiver: booking/state;
- review: booking unique, property/state/time;
- audit: request ID unique nullable, entity/time, actor/time.

Chạy `EXPLAIN` ít nhất cho:

1. tìm product đủ giá/tồn mọi đêm;
2. danh sách booking của khách;
3. danh sách booking của property theo ngày/state;
4. ledger active đối chiếu counter;
5. review công khai của property;
6. tỷ lệ bán trên tồn mở bán.

Tiêu chí là dùng index phù hợp trên fixture chuẩn, không yêu cầu loại bỏ mọi full scan ở bảng danh mục nhỏ.

---

## 13. Kiểm thử bắt buộc

### Unit

- khoảng ngày nửa kín;
- capacity và JSON giường/khách;
- restriction ở ngày nhận;
- promotion tie-break;
- làm tròn VND và breakdown;
- cancellation/no-show fee từ snapshot;
- state transition, gồm `NOT_TRACKED` và `NO_SHOW`.

### Integration

- tenant isolation;
- search đủ rate/tồn mọi đêm và không dùng stale result;
- preview JSON schema, expiry, single-use;
- PAY_AT_PROPERTY + `NOT_TRACKED`;
- online success/fail/timeout/retry;
- cancel metadata + release/refund idempotent;
- waiver approve/reject;
- date modification rollback;
- no-show release active ledger;
- completion idempotent;
- review eligibility;
- provenance Google/address.

### Concurrency

- hai request tranh unit cuối: đúng một booking thắng;
- double-click cùng client request: một booking;
- payment callback/retry đồng thời: một kết quả;
- cancel và payment finalize đồng thời: một terminal result hợp lệ;
- no-show và cancel đồng thời: một terminal result;
- giảm inventory đồng thời booking: không dưới reserved;
- đổi ngày khóa chéo: thứ tự ổn định, không phá booking cũ.

### Data validator

- chính xác 22 bảng và không có tên bảng cũ;
- FK/orphan và tenant consistency;
- JSON bắt buộc hợp lệ ở mức cấu trúc do script PHP kiểm tra;
- counter bằng active ledger, không oversold;
- active ledger chỉ thuộc pending payment/confirmed;
- terminal booking, đặc biệt no-show, không có active ledger;
- booking/item/product/property consistency;
- nightly rows đúng `[check-in, checkout)` và tổng tiền;
- preview tổng/JSON/fingerprint nhất quán;
- payment state phù hợp timing/booking;
- cancellation/refund amounts và idempotency;
- review eligibility;
- address/name provenance và Google fields không giả dữ liệu.

---

## 14. Dữ liệu và thứ tự import

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

Vai trò seed sau refactor:

- `001_reference.sql`: chỉ còn `TienNghi`;
- `002_accounts_partners.sql`: ba bảng Identity;
- `003_properties.sql`: property, room, facility mappings;
- `004_products_policies.sql`: policy, product, promotion;
- `005_inventory_prices.sql`: inventory và daily rate/restriction;
- `006_operational.sql`: preview, booking, item, nightly price, ledger, payment, waiver, review, audit.

Generator phải deterministic, manifest lưu SHA-256 và `tableCount=22`. Không sinh row cho các bảng đã bỏ/gộp. Tên/address 30 cơ sở có provenance; dữ liệu vận hành là tổng hợp.

---

## 15. Chia việc nhóm

| Gói | Phạm vi | Phụ thuộc |
|---|---|---|
| A | nền tảng, router, PDO, error, session, CSRF, Identity | không |
| B | Properties: property/room/facility/approval/upload JSON | A |
| C | Commercial: product/policy/rate/restriction/promotion | B |
| D | Inventory service, ledger và concurrency tests | B |
| E | Search, allocation, detail và preview JSON | B, C, D |
| F | Booking transaction và payment mock | D, E |
| G | cancel, waiver, modification, no-show, completion | F |
| H | review, audit, admin support và reports | F |

Mỗi gói chỉ merge khi contract bảng dùng chung đã chốt. Không để hai nhánh tự sửa migration mà không cập nhật DESIGN và validator.

---

## 16. Hoàn thành MVP khi

- migration tạo chính xác 22 bảng;
- 27 tiêu chí chấp nhận trong DESCRIPTION có test/evidence;
- seed nhập được trên MySQL 8 và MariaDB 10.4;
- validator trả mọi vi phạm bằng 0;
- không còn code/doc/query tham chiếu 16 bảng đã loại;
- transaction search/preview/booking/payment/cancel/modify/no-show thống nhất;
- race cuối chỉ một booking giữ được tồn;
- PAY_AT_PROPERTY không có lifecycle thu tiền giả;
- NO_SHOW không giữ inventory;
- partner không vượt organization;
- EXPLAIN dùng index phù hợp với fixture;
- README mô tả cài đặt, tài khoản, tác vụ dọn hold/hoàn tất và backup/restore;
- giao diện ba vai trò có trạng thái rỗng/lỗi/đang tải và hiển thị giá/hủy rõ ràng.

---

Chi tiết 38 → 22 và trade-off nằm tại `huongDan/SCHEMA_MVP_REDUCTION_AUDIT.md`. Khi thiết kế này khác DESCRIPTION, bất biến nghiệp vụ trong DESCRIPTION được ưu tiên.
