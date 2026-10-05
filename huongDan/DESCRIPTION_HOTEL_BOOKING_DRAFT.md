# ĐẶC TẢ NGHIỆP VỤ HỆ THỐNG TÌM KIẾM VÀ ĐẶT CHỖ LƯU TRÚ

**Phạm vi:** Mini OTA nhiều cơ sở lưu trú, tham khảo luồng nghiệp vụ công khai của Booking.com và chỉ làm nhánh lưu trú.  
**Phiên bản:** 0.6 – thu gọn schema MVP còn chính xác 22 bảng  
**Ngày cập nhật:** 06/10/2026  
**Mục tiêu triển khai:** Bài tập lớn nhóm dùng PHP và MySQL, ưu tiên đúng nghiệp vụ, dễ chia việc và có thể giải thích.

> Tài liệu ưu tiên tiếng Việt. Tên tiếng Anh chỉ giữ khi là tên riêng, mã trạng thái, công nghệ hoặc thuật ngữ cần khớp chính xác với chương trình.

---

## 1. Mục tiêu và giới hạn

Hệ thống cho phép khách tìm kiếm, so sánh và đặt chỗ tại nhiều cơ sở lưu trú. Một tổ chức đối tác có thể quản lý nhiều cơ sở, loại phòng, sản phẩm bán, giá, tồn mở bán và đơn phát sinh.

MVP phải hỗ trợ:

- nhiều cơ sở lưu trú và nhiều tổ chức đối tác;
- tìm theo điểm đến, ngày, số phòng và phân bổ người ở;
- kiểm tra giá, điều kiện mở bán và tồn của mọi đêm;
- một booking chỉ thuộc một cơ sở nhưng có thể gồm nhiều phòng;
- preview trước booking và preview không giữ tồn;
- chống bán vượt bằng giao dịch, khóa dòng tồn và ledger;
- trả tại cơ sở hoặc thanh toán trực tuyến mô phỏng;
- hủy, xin miễn phí hủy, đổi ngày trong phạm vi cho phép và no-show;
- đánh giá từ booking đã hoàn tất;
- quản trị viên duyệt hoặc tạm ngừng cơ sở;
- nhật ký cho thao tác quan trọng.

Giới hạn vận hành:

- tối đa 5 phòng, 10 người lớn, 10 trẻ em và 30 đêm cho một yêu cầu;
- ngày nhận không ở quá khứ và nằm trong tối đa 365 ngày đã mở bán;
- mỗi phòng có ít nhất một người lớn;
- trẻ em từ 0 đến 17 tuổi và vẫn chiếm sức chứa;
- chỉ dùng VND, làm tròn đến một đồng;
- mọi phòng trong một booking dùng chung ngày nhận và trả;
- hủy hoặc đổi ngày áp dụng toàn booking, không hủy riêng một phòng;
- đổi ngày chỉ dành cho booking trả tại cơ sở, chưa bị hủy/no-show/hoàn tất;
- ảnh chỉ lưu đường dẫn trong JSON, không lưu tệp nhị phân trong MySQL.

Đây không phải hệ thống quản lý vận hành khách sạn. MVP không quản lý số phòng vật lý, dọn phòng, khóa cửa, minibar, quầy lễ tân, hoa hồng hoặc quyết toán đối tác.

---

## 2. Mô hình nghiệp vụ cốt lõi

### 2.1. Cơ sở, loại phòng, sản phẩm và giá ngày

1. **Cơ sở lưu trú (`CoSoLuuTru`)**: hồ sơ khách sạn/căn hộ/khu nghỉ dưỡng thuộc một tổ chức đối tác.
2. **Loại phòng (`LoaiPhong`)**: nhóm phòng có cùng sức chứa và cấu hình giường; không phải phòng vật lý như 301.
3. **Sản phẩm phòng (`SanPhamPhong`)**: cách bán một loại phòng, gồm tên gói, bữa ăn, cách thanh toán và chính sách hủy.
4. **Giá ngày (`GiaPhongNgay`)**: giá cùng điều kiện mở bán của một sản phẩm tại một ngày lưu trú.

`SanPhamPhong` thay luôn vai trò của bảng gói giá cũ. Bữa ăn và cách thanh toán là tập giá trị cố định bằng CHECK, không phải danh mục do quản trị viên sửa động.

### 2.2. Tồn phòng và khả năng đặt

Tồn được quản lý theo:

```text
LoaiPhongID + NgayLuuTru
```

Mọi sản phẩm cùng một loại phòng dùng chung tồn. Một sản phẩm có giá chưa chắc đặt được nếu một đêm hết tồn, bị đóng bán, không đạt thời gian đặt trước, hoặc không thỏa số đêm tối thiểu/tối đa.

### 2.3. Luồng chính

```text
Tìm kiếm
  → xem cơ sở và sản phẩm còn bán
  → tạo preview có hạn
  → kiểm tra lại tại màn hình xác nhận
  → tạo booking trong transaction
  → xác nhận ngay hoặc chờ thanh toán online
```

Preview trả về phân bổ từng phòng, khách, giá từng đêm, thuế/phí, tổng tiền, cách thanh toán và snapshot hủy. Preview không làm tăng `SoLuongDaGiu`.

### 2.4. Booking nhiều phòng

- `DatCho` là phần đầu booking và luôn thuộc đúng một cơ sở.
- Mỗi `HangMucDatCho` đại diện đúng một phòng được đặt.
- Hai phòng giống nhau vẫn là hai hạng mục để giữ đúng khách và snapshot.
- Danh sách khách của một hạng mục nằm trong `KhachJSON` có cấu trúc cố định.
- Nhu cầu được cộng theo loại phòng và từng đêm trước khi khóa tồn.

### 2.5. Snapshot lịch sử

Booking phải giữ được lịch sử dù đối tác sửa dữ liệu hiện tại:

- tên loại phòng, tên sản phẩm và bữa ăn;
- khách/phân bổ của từng phòng;
- giá gốc, giảm giá, thuế/phí và thành tiền từng đêm;
- chính sách hủy, phí no-show và điều kiện thanh toán;
- dữ liệu trước/sau khi đổi ngày trong nhật ký.

`GiaDemDatCho` vẫn là bảng riêng vì giá lịch sử cần đối soát và báo cáo theo ngày. JSON chỉ dùng cho snapshot điều kiện hoặc danh sách con không cần truy vấn độc lập.

### 2.6. Schema MVP đã chốt

Schema có chính xác **22 bảng**, giảm từ 38 bảng. Danh sách và lý do gộp/bỏ chi tiết nằm trong `huongDan/SCHEMA_MVP_REDUCTION_AUDIT.md`.

Không gộp `DatCho`, `ThanhToan` và `TonPhongNgay`; không bỏ `HangMucDatCho` hoặc `GiuTonPhongDem`.

---

## 3. Dữ liệu cơ sở và Google Places

### 3.1. Phạm vi địa lý

MVP chỉ dùng cơ sở tại Thành phố Hồ Chí Minh. `CoSoLuuTru.MaThanhPho` cố định là `HCM`; không cần bảng thành phố riêng.

### 3.2. Nguồn hiện có

`huongDan/data/CoSoLuuTruDuLich.xlsx` có 308 dòng, 293 tên sau chuẩn hóa, 12 nhóm trùng tên và 15 dòng trùng vượt bản ghi đầu tiên. Tệp không cung cấp địa chỉ hoặc tọa độ.

Bộ dữ liệu hiện chọn 30 cơ sở có kết quả đối chiếu rõ:

- 24 khớp trực tiếp theo tên và địa chỉ;
- 6 khớp theo tên cũ hoặc biệt danh;
- 278 dòng còn lại chưa đủ chắc chắn, không bị kết luận là sai.

### 3.3. Provenance bắt buộc

`CoSoLuuTru` phải phân biệt:

- `TenTrongNguon`, `NguonTen`, `MaBanGhiNguon`: tên và dòng từ danh mục ban đầu;
- `DiaChi`, `NguonDiaChi`, `DiaChiXacMinhLuc`: địa chỉ dùng trong hệ thống, nguồn đối chiếu và thời điểm kiểm tra;
- `GooglePlaceID`, `GoogleMatchStatus`, `GoogleVerifiedAt`: định danh Google đã duyệt, nếu có;
- `ViDo`, `KinhDo`, `NguonToaDo`, `ToaDoXacMinhLuc`: tọa độ lâu dài chỉ từ đối tác, cơ quan quản lý hoặc nguồn mở có giấy phép phù hợp.

Địa chỉ đối chiếu hiện tại không được mô tả như master data do Google cấp hoặc như dữ liệu chính thức của khách sạn. Trước khi vận hành công khai, đối tác phải xác nhận lại hồ sơ.

### 3.4. Google Places trong MVP

Google Places chỉ là phần làm giàu/xác minh, không phải aggregate lõi:

- bỏ hai bảng `DoiChieuGooglePlaces` và `BoNhoDemGooglePlaces`;
- chỉ giữ một kết quả đã duyệt trực tiếp trên `CoSoLuuTru`;
- không lưu danh sách ứng viên trong cơ sở dữ liệu nghiệp vụ;
- nội dung Places cần dùng tạm được lưu ở tệp cache/session và tuân thủ thời hạn của nhà cung cấp;
- không sao chép tên, địa chỉ hoặc tọa độ trả về từ Places thành dữ liệu lâu dài không rõ quyền lưu;
- hiện chưa gọi API, chưa có điểm ghim, sắp xếp khoảng cách hoặc chỉ đường.

Trong giai đoạn sau, Google Place ID có thể dùng để mở đúng địa điểm hoặc chỉ đường. Tính năng đó không làm thay đổi 22 bảng MVP.

---

## 4. Tồn, giá và điều kiện bán

### 4.1. Khoảng lưu trú

Khoảng ngày là nửa kín:

```text
[NgayNhanPhong, NgayTraPhong)
```

Nhận ngày 10, trả ngày 12 là hai đêm 10 và 11. Ngày trả phải sau ngày nhận và chênh lệch không quá 30 đêm.

### 4.2. Tồn theo ngày

`TonPhongNgay` có:

- `TongSoLuong`: số phòng mở bán trên nền tảng, không phải tổng phòng vật lý của khách sạn;
- `SoLuongDaGiu`: số ledger `ACTIVE` của booking đang `PENDING_PAYMENT` hoặc `CONFIRMED`;
- `PhienBan`: hỗ trợ kiểm soát cập nhật cạnh tranh.

Điều kiện đủ tồn cho nhu cầu `q`:

```text
SoLuongDaGiu + q <= TongSoLuong
```

`GiuTonPhongDem` ghi một dòng cho mỗi booking item và mỗi đêm. Bộ đếm cùng ledger phải thay đổi trong một transaction và luôn thỏa:

```text
SoLuongDaGiu = COUNT(ledger ACTIVE của cùng LoaiPhongID + NgayLuuTru)
```

### 4.3. Giá và restriction nằm ở đâu

`GiaPhongNgay` chứa rõ:

- `SanPhamPhongID`, `NgayLuuTru`;
- `GiaCoBan`;
- `DongBan`;
- `SoDemToiThieu`;
- `SoDemToiDa`, có thể để trống;
- `SoNgayDatTruoc`;
- `PhienBan`, `CapNhatLuc`.

Mọi đêm phải có giá và không đóng bán. Quy tắc số đêm và đặt trước lấy từ dòng giá của **ngày nhận phòng**. Không tạo bảng restriction riêng cho MVP.

### 4.4. Thuế, phí và khuyến mãi

Thuế/phí đơn giản nằm trên `CoSoLuuTru`: tỷ lệ thuế, tỷ lệ phí dịch vụ và cờ đã bao gồm. Khi booking, số tiền thực tế được chụp vào từng `GiaDemDatCho`.

Khuyến mãi MVP áp dụng toàn cơ sở, giảm theo phần trăm, có cửa sổ đặt và lưu trú, không cộng dồn. Nếu nhiều khuyến mãi hợp lệ, lấy phần trăm lớn nhất rồi mã nhỏ nhất. Không còn bảng nối khuyến mãi–sản phẩm.

### 4.5. Cấu hình giường

`LoaiPhong.CauHinhGiuongJSON` dùng schema:

```json
[
  {"type": "DOUBLE", "quantity": 1},
  {"type": "SINGLE", "quantity": 1}
]
```

Loại giường dùng tập giá trị cố định; số lượng là số nguyên dương. Đây chỉ là thông tin mô tả/filter, không quản lý từng giường vật lý.

---

## 5. Tác nhân và quyền

### 5.1. Khách hàng

- tìm kiếm không cần đăng nhập;
- xem chi tiết, tạo preview;
- đăng nhập để xác nhận, xem, sửa, hủy booking;
- gửi yêu cầu miễn phí hủy;
- đánh giá booking đủ điều kiện.

### 5.2. Đối tác

- quản lý cơ sở thuộc tổ chức mình;
- quản lý loại phòng, tiện nghi, sản phẩm, giá và tồn;
- xem booking của cơ sở mình;
- xử lý waiver, đánh dấu no-show;
- xem báo cáo bán trên tồn mở bán.

Mọi truy vấn đối tác phải giới hạn theo `ToChucDoiTacID` ở máy chủ.

### 5.3. Quản trị viên

- quản lý tài khoản và tiện nghi;
- duyệt, yêu cầu sửa, từ chối hoặc tạm ngừng cơ sở;
- hỗ trợ tra cứu booking;
- ẩn đánh giá vi phạm;
- xem audit và báo cáo toàn hệ thống.

Loại cơ sở, bữa ăn và cách thanh toán không còn là danh mục quản trị động; chúng là CHECK/config constant.

---

## 6. Chức năng nghiệp vụ

### 6.1. Đăng ký và duyệt cơ sở

```text
DRAFT → PENDING_REVIEW → ACTIVE ↔ SUSPENDED
              ↓
       NEEDS_CHANGES → PENDING_REVIEW
              ↓
           REJECTED
```

Trước khi gửi duyệt phải có tên, loại, địa chỉ, giờ nhận/trả, ít nhất một loại phòng, sản phẩm đang dùng, ảnh, giá và tồn cho khoảng mở bán. Người duyệt, thời điểm và ghi chú được giữ trên cơ sở và audit.

### 6.2. Quản lý loại phòng và sản phẩm

- `LoaiPhong` giữ sức chứa, cấu hình giường JSON, ảnh JSON và trạng thái;
- tiện nghi có bảng nối để lọc bằng SQL;
- `SanPhamPhong` giữ mã/tên gói, loại bữa ăn, `PAY_AT_PROPERTY` hoặc `PAY_ONLINE`, chính sách hủy và trạng thái;
- dữ liệu đã xuất hiện trong booking chỉ được ngừng dùng, không xóa cứng.

### 6.3. Search

Đầu vào gồm điểm đến, ngày, số phòng, người lớn, trẻ em và tuổi. Bộ lọc gồm giá, số sao nếu có nguồn, điểm đánh giá, tiện nghi, loại cơ sở, bữa ăn, hủy miễn phí và cách thanh toán.

Pipeline:

```text
validate đầu vào
→ lọc cơ sở ACTIVE
→ đọc sản phẩm + GiaPhongNgay đủ mọi đêm
→ kiểm tra restriction ngày nhận
→ đọc MIN(tồn còn) của từng loại phòng
→ phân bổ tối đa 5 phòng bằng backtracking có giới hạn
→ tính giá từ tổ hợp rẻ nhất
→ sắp xếp và phân trang
```

Kết quả search chỉ để hiển thị; preview và booking luôn đọc dữ liệu mới. Không dùng kết quả search cũ để quyết định cuối.

### 6.4. Preview

`XemTruocDatCho` lưu token băm, khách, cơ sở, ngày, tổng tiền, fingerprint, hạn dùng và `PhanBoJSON` gồm các phòng đã chọn, khách, sản phẩm, giá và chính sách.

- hết hạn tối đa 15 phút;
- chỉ dùng một lần;
- không giữ tồn;
- JSON phải được kiểm tra theo schema nội bộ;
- khi tạo booking phải đối chiếu lại product, price version, restriction và tồn.

### 6.5. Booking và payment

#### Trả tại cơ sở

- tạo booking `CONFIRMED` và giữ tồn trong cùng transaction;
- tạo một `ThanhToan` trạng thái `NOT_TRACKED`;
- nền tảng không ghi nhận khách đã trả tiền mặt/thẻ tại khách sạn;
- booking có thể `COMPLETED` trong khi payment vẫn `NOT_TRACKED`; đây là trạng thái hợp lệ.

#### Trả trực tuyến mô phỏng

- tạo booking `PENDING_PAYMENT`, payment `PROCESSING` và hold có hạn;
- commit trước khi gọi adapter thanh toán;
- thành công: payment `PAID`, booking `CONFIRMED`;
- thất bại/hết hạn: payment `FAILED`, booking `PAYMENT_FAILED`, trả tồn đúng một lần.

`ThanhToan` là một dòng duy nhất cho booking, giữ idempotency key ổn định, số lần thử và kết quả cuối. Mỗi lần thử được audit; retry không tạo payment hoặc booking mới.

### 6.6. Sửa thông tin và đổi ngày

Khách được sửa thông tin liên hệ, yêu cầu đặc biệt và danh sách khách. Đổi ngày chỉ cho booking `CONFIRMED + PAY_AT_PROPERTY + NOT_TRACKED` và còn trước hạn nghiệp vụ.

Khi đổi ngày:

1. lập preview thay đổi nhưng không giữ tồn;
2. bắt đầu transaction và khóa booking;
3. hợp nhất các dòng tồn khoảng cũ/mới rồi khóa theo thứ tự cố định;
4. kiểm tra lại giá, restriction và tồn mới;
5. release ledger cũ, tạo ledger mới, cập nhật counter;
6. cập nhật booking item và giá từng đêm;
7. ghi before/after vào `NhatKyHeThong` với `RequestID` duy nhất;
8. commit.

Nếu bất kỳ bước nào thất bại, rollback toàn bộ và booking cũ không đổi. Không đổi cơ sở hoặc sản phẩm; trường hợp đó phải hủy rồi đặt lại.

### 6.7. Hủy trực tiếp

Không tạo bảng cancellation riêng. `DatCho` giữ:

- `HuyIdempotencyKey`;
- `NguoiHuyID`, `HuyLuc`, `LyDoHuy`;
- `PhiHuy`, `SoTienHoan`;
- `TinhHuySnapshotJSON`.

Luồng:

```text
tính phí từ snapshot
→ khách xác nhận idempotency key
→ BEGIN
→ khóa booking, ledger ACTIVE và inventory
→ chuyển CANCELLED
→ release ledger/counter đúng một lần
→ ghi refund PENDING trên ThanhToan nếu cần
→ ghi audit
→ COMMIT
→ gọi mock provider ngoài transaction
→ transaction ngắn cập nhật kết quả refund
```

Gửi lại cùng `HuyIdempotencyKey` trả kết quả cũ, không hủy/hoàn tiền lần hai.

### 6.8. Yêu cầu miễn phí hủy

Yêu cầu `PENDING` không đổi booking hoặc tồn. Đối tác cùng tổ chức được duyệt/từ chối. Duyệt gọi primitive hủy với phí 0; từ chối giữ booking `CONFIRMED`. Mỗi booking chỉ có một yêu cầu đang chờ.

### 6.9. No-show

Quy tắc duy nhất cho MVP:

```text
CONFIRMED
  → khóa booking + ledger + inventory
  → tính và chụp phí no-show
  → release mọi ledger còn ACTIVE và giảm counter
  → chuyển NO_SHOW
  → audit
```

Phí no-show và quyền bán lại tồn là hai việc độc lập. Booking `NO_SHOW` không được giữ ledger `ACTIVE`. Đối tác chỉ được thực hiện từ giờ nhận phòng đến hết ngày kế tiếp theo múi giờ cơ sở.

### 6.10. Hoàn tất

Tác vụ PHP hằng ngày chuyển booking `CONFIRMED` đã qua ngày trả sang `COMPLETED`, release mọi ledger còn `ACTIVE` và ghi audit trong transaction chạy lặp an toàn.

### 6.11. Đánh giá

Chỉ khách sở hữu booking `COMPLETED`, chưa đánh giá và chưa quá 90 ngày sau ngày trả được tạo một đánh giá. Quản trị viên có thể ẩn/hiện; không sửa nội dung thay khách.

### 6.12. Thông báo

Không có notification center và không có bảng `ThongBao` trong MVP. Kết quả thao tác được hiển thị bằng thông báo phiên/giao diện. Email, SMS, push và hộp thư trong ứng dụng thuộc giai đoạn sau.

---

## 7. Vòng đời trạng thái

### 7.1. Preview

```text
VALID → USED
VALID → INVALIDATED
```

Preview `VALID` có `HetHanLuc <= now` được xem là hết hạn dù chưa cần ghi trạng thái `EXPIRED`.

### 7.2. Booking

```text
PENDING_PAYMENT → CONFIRMED → COMPLETED
       ↓             ├→ CANCELLED
PAYMENT_FAILED      └→ NO_SHOW
```

`CANCELLED`, `PAYMENT_FAILED`, `NO_SHOW`, `COMPLETED` là trạng thái kết thúc và không có ledger `ACTIVE`.

### 7.3. Payment

```text
PAY_AT_PROPERTY: NOT_TRACKED

PAY_ONLINE: PROCESSING → PAID
                   └──→ FAILED

Refund: NONE → PENDING → SUCCEEDED/FAILED
```

`NOT_TRACKED` không có nghĩa khách chưa trả; nó nghĩa nền tảng không theo dõi giao dịch tại cơ sở.

### 7.4. Waiver và review

```text
Waiver: PENDING → APPROVED/REJECTED/WITHDRAWN
Review: PUBLISHED ↔ HIDDEN
```

---

## 8. Transaction bắt buộc sau scale-down

### 8.1. Tạo booking

```text
BEGIN
→ SELECT preview FOR UPDATE
→ kiểm tra token, hạn, khách, state và fingerprint
→ đọc lại product/rate/restriction
→ gom nhu cầu RoomType + StayDate
→ SELECT inventory FOR UPDATE theo thứ tự ổn định
→ kiểm tra đủ tồn
→ INSERT DatCho, HangMucDatCho, GiaDemDatCho
→ INSERT ledger ACTIVE và tăng counter
→ INSERT ThanhToan ban đầu
→ UPDATE preview USED
→ INSERT audit
COMMIT
```

### 8.2. Thanh toán online

Không gọi provider khi đang giữ khóa inventory. Kết quả provider được hoàn tất trong transaction ngắn, có khóa booking/payment và kiểm tra trạng thái hiện tại.

### 8.3. Hủy, đổi ngày, no-show và hoàn tất

Mọi luồng phải:

- khóa booking trước;
- khóa ledger và inventory theo thứ tự `LoaiPhongID → NgayLuuTru`;
- chỉ release dòng `ACTIVE`;
- giảm counter đúng số dòng vừa release;
- kiểm tra trạng thái để retry không tạo hậu quả lần hai;
- ghi audit trong cùng transaction với thay đổi nghiệp vụ.

---

## 9. Báo cáo

Báo cáo MVP:

- số booking theo ngày/tháng/trạng thái;
- tổng giá trị booking, không gọi là doanh thu nền tảng;
- tỷ lệ hủy và no-show;
- số room-night đã bán theo loại phòng;
- **tỷ lệ bán trên tồn mở bán**;
- điểm đánh giá trung bình;
- số lần áp dụng khuyến mãi;
- lỗi thanh toán cuối cùng.

Không dùng tên “tỷ lệ lấp đầy khách sạn” hoặc “hotel occupancy rate”. Mẫu số chỉ là tổng `TongSoLuong` đã mở bán trên nền tảng, không đại diện toàn bộ phòng vật lý của khách sạn.

---

## 10. Yêu cầu kỹ thuật và bảo mật

- InnoDB, khóa ngoại và CHECK trên MySQL 8/MariaDB 10.4;
- tiền dùng `DECIMAL(15,0)` hoặc số nguyên VND; PHP không cộng tiền bằng float;
- timestamp lưu UTC, ngày lưu trú theo `Asia/Ho_Chi_Minh`;
- PDO prepared statement, allow-list sort/filter;
- session cookie an toàn và CSRF cho thao tác ghi;
- `password_hash()`/`password_verify()`;
- public booking code khó đoán;
- token preview chỉ lưu hash;
- không lưu PAN, CVV, mật khẩu hoặc bí mật provider trong audit;
- ảnh kiểm tra MIME/kích thước, tên ngẫu nhiên và nằm ngoài vùng thực thi;
- phân trang cho cơ sở, booking, review và audit;
- đối tác luôn bị giới hạn theo tổ chức;
- có hướng dẫn backup/restore;
- dùng `EXPLAIN` với fixture chuẩn cho search, booking list và báo cáo chính.

---

## 11. Ngoài phạm vi MVP

- chatbot, RAG, gợi ý bằng AI hoặc recommendation ML;
- microservice, Redis, Kafka, queue hoặc Elasticsearch;
- chuyến bay, xe, taxi, vé tham quan;
- mở rộng ngoài Thành phố Hồ Chí Minh;
- PMS, phòng vật lý, dọn phòng, nhận phòng tại quầy;
- loyalty, nhiều tiền tệ, hóa đơn điện tử;
- payment gateway thật, nhiều lần partial refund;
- notification center, email/SMS/push thật;
- Google Places runtime, điểm ghim, tính khoảng cách và chỉ đường;
- điều kiện giá nâng cao như đóng riêng ngày đến/ngày đi;
- hoa hồng và quyết toán đối tác;
- sao chép giao diện hoặc thuật toán nội bộ của Booking.com.

---

## 12. Tiêu chí chấp nhận

1. Schema có chính xác 22 bảng, nằm trong giới hạn 18–25.
2. Mọi bảng đều có use case và module sở hữu rõ ràng.
3. Không có use case, transaction, route hoặc test tham chiếu bảng đã gộp/bỏ.
4. Đối tác tạo hồ sơ, loại phòng, sản phẩm, giá và tồn; quản trị viên duyệt `ACTIVE`.
5. Search chỉ trả cơ sở có thể phân bổ đủ mọi phòng/mọi đêm và không dùng kết quả stale làm quyết định cuối.
6. Preview trả đúng phân bổ, giá, payment/cancellation snapshot, hết hạn tối đa 15 phút và không giữ tồn.
7. Preview hết hạn/đã dùng hoặc fingerprint sai không tạo booking.
8. Hai request tranh unit cuối chỉ một booking giữ được tồn.
9. Booking nhiều phòng giữ đủ mọi đêm hoặc rollback toàn bộ.
10. Counter tồn bằng số ledger `ACTIVE`, không âm và không vượt tổng.
11. `PAY_AT_PROPERTY` xác nhận với payment `NOT_TRACKED`; booking hoàn tất không buộc ghi nhận tiền thu tại khách sạn.
12. `PAY_ONLINE` chỉ xác nhận sau `PAID`; retry không tạo payment hoặc booking trùng.
13. Không gọi provider khi đang giữ inventory lock.
14. Hủy lưu đủ người, lúc, lý do, phí, refund, idempotency key và snapshot.
15. Hủy trả tồn đúng một lần; refund retry không hoàn hai lần.
16. Waiver đang chờ không đổi booking hoặc trả tồn.
17. Đổi ngày thất bại không thay booking, ledger hoặc giá cũ.
18. `NO_SHOW` chụp phí và release toàn bộ ledger `ACTIVE`, nên không vi phạm inventory invariant.
19. Booking history không đổi khi đối tác sửa giá, thuế/phí hoặc chính sách hiện tại.
20. Chỉ booking hoàn tất và còn hạn mới được review; mỗi booking tối đa một review.
21. Đối tác không truy cập dữ liệu tổ chức khác.
22. Cơ sở `SUSPENDED` không xuất hiện trong search nhưng lịch sử booking vẫn đọc được.
23. Dữ liệu Google chỉ là định danh/xác minh, không bị biến thành master data sai provenance.
24. Không gọi báo cáo tồn mở bán là occupancy khách sạn.
25. `EXPLAIN` dùng đúng index với fixture mục tiêu.
26. Seed, import và validator chạy thành công trên MySQL 8 hoặc MariaDB 10.4.
27. Toàn bộ vi phạm của validator bằng 0.

---

## 13. Các quyết định đã chốt

| Mã | Nội dung | Quyết định |
|---|---|---|
| XN-01 | Phạm vi | chỉ TP.HCM, VND, tối đa 5 phòng/30 đêm |
| XN-02 | Schema | chính xác 22 bảng |
| XN-03 | Loại cơ sở/bữa ăn/payment timing | CHECK/config, không có bảng danh mục |
| XN-04 | Tiện nghi | giữ bảng và hai bảng nối để lọc |
| XN-05 | Ảnh | JSON trong cơ sở/loại phòng |
| XN-06 | Giường | JSON có schema, không quản lý giường vật lý |
| XN-07 | Product | gộp gói giá và bữa ăn vào `SanPhamPhong` |
| XN-08 | Restriction | đặt trên `GiaPhongNgay` của ngày nhận |
| XN-09 | Promotion | cấp cơ sở, không có bảng nối sản phẩm |
| XN-10 | Preview item | JSON trong `XemTruocDatCho` |
| XN-11 | Guest list | JSON trong `HangMucDatCho` |
| XN-12 | Night price | giữ `GiaDemDatCho` riêng |
| XN-13 | Payment | một `ThanhToan`/booking; attempt/refund giản lược |
| XN-14 | PAY_AT_PROPERTY | nền tảng không theo dõi thu tiền thực tế |
| XN-15 | Hủy | metadata trên `DatCho`, audit ở `NhatKyHeThong` |
| XN-16 | No-show | tính phí rồi release mọi hold trong cùng transaction |
| XN-17 | Notification | bỏ khỏi MVP |
| XN-18 | Google Places | ba trường trên property; cache ngoài DB |
| XN-19 | Báo cáo tồn | gọi là tỷ lệ bán trên tồn mở bán |

---

## 14. Dữ liệu khởi tạo và kiểm thử

### 14.1. Nguồn và quy mô

- 30 cơ sở tại TP.HCM;
- 10 tổ chức đối tác;
- 120 loại phòng, 180 sản phẩm;
- 21.600 dòng tồn và 32.400 dòng giá;
- 500 khách hàng, 2.000 booking, 2.500 hạng mục;
- dữ liệu phòng, giá, tồn, giao dịch và review là dữ liệu tổng hợp.

Không sinh bảng Google cache, preview item, khách lưu trú, modification, payment attempt, refund hoặc notification riêng sau refactor.

### 14.2. Thứ tự nhập

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

Bộ sinh phải deterministic, manifest có SHA-256 và validator kiểm tra số bảng bằng 22 cùng các invariant nghiệp vụ.

---

## 15. Nguồn tham khảo

Các nguồn Booking.com chỉ dùng để hiểu luồng công khai, không khẳng định mô hình nội bộ hoặc lấy dữ liệu thương mại:

- [Tìm và đặt chỗ](https://developers.booking.com/demand/docs/accommodations/accommodation-tutorial)
- [Tìm cơ sở còn chỗ](https://developers.booking.com/demand/docs/accommodations/search-for-available-properties)
- [Giá và khả năng bán](https://developers.booking.com/connectivity/docs/ari)
- [Loại phòng và gói giá](https://developers.booking.com/connectivity/docs/room-type-and-rate-plan-management/understanding-room-types-and-rate-plans)
- [Preview và tạo đơn](https://developers.booking.com/demand/docs/orders-api/order-preview-create)
- [Chính sách hủy](https://developers.booking.com/demand/docs/orders-api/cancellation-policies)

Google Places chỉ dùng theo tài liệu và điều khoản chính thức:

- [Place ID](https://developers.google.com/maps/documentation/places/web-service/place-id)
- [Điều khoản riêng Google Maps Platform](https://cloud.google.com/maps-platform/terms/maps-service-terms)

---

## 16. Bất biến bắt buộc

1. Một cơ sở thuộc đúng một tổ chức; quyền đối tác luôn theo tổ chức.
2. Room type, product, policy, booking item và booking phải cùng cơ sở.
3. Booking thuộc một cơ sở, ngày trả sau ngày nhận và tối đa 30 đêm.
4. Mỗi item là một phòng, có ít nhất một người lớn và không vượt sức chứa.
5. Tồn dùng chung ở `LoaiPhong + NgayLuuTru`, không ở product.
6. `0 <= SoLuongDaGiu <= TongSoLuong` và counter bằng ledger `ACTIVE`.
7. Chỉ booking `PENDING_PAYMENT` hoặc `CONFIRMED` được có ledger `ACTIVE`.
8. Mọi trạng thái kết thúc, gồm `NO_SHOW`, phải release ledger đúng một lần.
9. Preview không giữ tồn, có hạn, dùng một lần và luôn được revalidate.
10. `ClientRequestID` không tạo hai booking cho cùng khách.
11. Payment retry dùng một aggregate/idempotency, không tạo dòng payment mới.
12. Hủy có idempotency key, snapshot phí và kết quả refund; retry không lặp side effect.
13. Waiver đang chờ không đổi booking hoặc tồn.
14. Đổi ngày là all-or-nothing; thất bại giữ nguyên dữ liệu cũ.
15. Snapshot booking không phụ thuộc giá/policy hiện tại.
16. PAY_AT_PROPERTY `NOT_TRACKED` không bị diễn giải là chưa trả tiền.
17. Review chỉ cho booking `COMPLETED`, đúng khách, trong 90 ngày và tối đa một lần.
18. Không lưu bí mật thanh toán trong database hoặc audit.
19. Google Place ID chỉ là metadata xác minh; không lưu lâu dài nội dung Google không được phép.
20. JSON ảnh, giường, preview và khách phải đúng schema ứng dụng; JSON không thay thế inventory, payment hoặc nightly price.

---

Tài liệu kỹ thuật tương ứng là `huongDan/DESIGN_HOTEL_BOOKING_DRAFT.md`. Audit giảm bảng nằm tại `huongDan/SCHEMA_MVP_REDUCTION_AUDIT.md`. Khi có khác biệt, quyết định và bất biến trong tài liệu này được ưu tiên.
