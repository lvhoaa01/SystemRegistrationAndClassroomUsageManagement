# ĐỐI CHIẾU CƠ SỞ LƯU TRÚ TẠI THÀNH PHỐ HỒ CHÍ MINH

**Ngày đối chiếu:** 05/10/2026  
**Tệp nguồn:** `huongDan/data/CoSoLuuTruDuLich.xlsx`  
**Tệp bộ sinh sử dụng:** `database/source/hcmc_accommodations_verified.csv`

## 1. Kết luận

Tệp Excel có 308 dòng dữ liệu nhưng không có địa chỉ hoặc tọa độ. Sau khi chuẩn hóa tên, có 293 tên khác nhau, 12 nhóm trùng tên và 15 dòng trùng vượt quá bản ghi đầu tiên. Vì vậy không thể chỉ dựa vào tên để tự động khẳng định toàn bộ 308 dòng tương ứng với địa điểm nào trên Google Maps.

Đợt đầu chọn 30 cơ sở có thể xác định rõ bằng tên kết hợp địa chỉ, đúng với quy mô 30 cơ sở đã chốt cho bộ dữ liệu phát triển:

- 24 cơ sở khớp chính xác;
- 6 cơ sở khớp theo tên cũ hoặc biệt danh;
- 278 dòng còn lại chưa được đối chiếu, không có nghĩa là sai hoặc không tồn tại;
- toàn bộ 30 cơ sở được chọn đều thuộc Thành phố Hồ Chí Minh;
- chưa nhập tọa độ và số sao vì tệp nguồn không cung cấp các giá trị này.

## 2. Cách đối chiếu

1. Giữ nguyên số dòng để có thể truy ngược; tên nguồn chỉ được cắt khoảng trắng thừa, gộp xuống dòng và chuẩn hóa cách lưu Unicode.
2. Tìm theo tên cơ sở; với tên chung hoặc tên cũ, kết hợp địa chỉ và tên thương mại hiện tại.
3. Ưu tiên kết quả Google Maps/Google Hotels và trang chính thức của cơ sở.
4. Chỉ chọn trường hợp có tên và địa chỉ đủ rõ; không tự suy diễn tọa độ, số sao hoặc thông tin kinh doanh.

Hai loại kết quả:

- `KHOP_CHINH_XAC`: tên nguồn và tên đang dùng nhận diện cùng một cơ sở rõ ràng;
- `KHOP_TEN_CU_BIET_DANH`: nguồn dùng tên cũ, tên pháp lý hoặc đã ghi biệt danh trong ngoặc.

Liên kết “Bản đồ” bên dưới mở tìm kiếm Google Maps bằng cả tên và địa chỉ. Kết quả có thể thay đổi khi Google cập nhật dữ liệu.

## 3. Danh sách được đưa vào dữ liệu khởi tạo

| Mã | Dòng Excel | Tên trong nguồn | Tên và địa chỉ đã đối chiếu | Kết quả | Kiểm tra |
|---:|---:|---|---|---|---|
| CS001 | 10 | SILVERLAND YEN | Silverland Yen Hotel — 73-75 Thủ Khoa Huân | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Silverland+Yen+Hotel+73-75+Thu+Khoa+Huan+Ho+Chi+Minh) |
| CS002 | 154 | KHÁCH SẠN THE MYST ĐỒNG KHỞI | The Myst Dong Khoi — 6-8 Hồ Huấn Nghiệp | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=The+Myst+Dong+Khoi+6-8+Ho+Huan+Nghiep+Ho+Chi+Minh) |
| CS003 | 162 | LIBERTY CENTRAL SAIGON RIVERSIDE | Liberty Central Saigon Riverside — 17 Tôn Đức Thắng | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Liberty+Central+Saigon+Riverside+17+Ton+Duc+Thang+Ho+Chi+Minh) |
| CS004 | 164 | LIBERTY CENTRAL SAIGON CENTRE | Liberty Central Saigon Centre — 179 Lê Thánh Tôn | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Liberty+Central+Saigon+Centre+179+Le+Thanh+Ton+Ho+Chi+Minh) |
| CS005 | 166 | KHÁCH SẠN LIBERTY CENTRAL SAIGON CITYPOINT | Liberty Central Saigon Citypoint — 59 Pasteur | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Liberty+Central+Saigon+Citypoint+59+Pasteur+Ho+Chi+Minh) |
| CS006 | 167 | KHÁCH SẠN CENTRAL PALACE | Central Palace Hotel — 39-39A Nguyễn Trung Trực | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Central+Palace+Hotel+39-39A+Nguyen+Trung+Truc+Ho+Chi+Minh) |
| CS007 | 173 | KHÁCH SẠN RAMANA SÀI GÒN | Ramana Saigon Hotel — 323 Lê Văn Sỹ | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Ramana+Saigon+Hotel+323+Le+Van+Sy+Ho+Chi+Minh) |
| CS008 | 176 | KHÁCH SẠN VISSAI SÀI GÒN | Vissai Saigon Hotel — 144 Nguyễn Văn Trỗi | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Vissai+Saigon+Hotel+144+Nguyen+Van+Troi+Ho+Chi+Minh) |
| CS009 | 177 | KHÁCH SẠN ĐỆ NHẤT | First Hotel — 18 Hoàng Việt | Tên cũ/biệt danh | [Bản đồ](https://www.google.com/maps/search/?api=1&query=First+Hotel+18+Hoang+Viet+Ho+Chi+Minh) |
| CS010 | 180 | KHÁCH SẠN MAI HOUSE SAIGON | Mai House Saigon Hotel — 157 Nam Kỳ Khởi Nghĩa | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Mai+House+Saigon+Hotel+157+Nam+Ky+Khoi+Nghia+Ho+Chi+Minh) |
| CS011 | 181 | KHÁCH SẠN ĐỒNG KHỞI (GRAND HOTEL) | Hotel Grand Saigon — 8 Đồng Khởi | Tên cũ/biệt danh | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Hotel+Grand+Saigon+8+Dong+Khoi+Ho+Chi+Minh) |
| CS012 | 182 | KHÁCH SẠN BẾN THÀNH (REX) | Rex Hotel Saigon — 141 Nguyễn Huệ | Tên cũ/biệt danh | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Rex+Hotel+Saigon+141+Nguyen+Hue+Ho+Chi+Minh) |
| CS013 | 183 | KHÁCH SẠN RENAISSANCE RIVERSIDE HOTEL SAIGON | Renaissance Riverside Hotel Saigon — 8-15 Tôn Đức Thắng | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Renaissance+Riverside+Hotel+Saigon+8-15+Ton+Duc+Thang+Ho+Chi+Minh) |
| CS014 | 184 | KHÁCH SẠN SHERATON | Sheraton Saigon Grand Opera Hotel — 88 Đồng Khởi | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Sheraton+Saigon+Grand+Opera+Hotel+88+Dong+Khoi+Ho+Chi+Minh) |
| CS015 | 185 | KHÁCH SẠN NEW WORLD | New World Saigon Hotel — 76 Lê Lai | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=New+World+Saigon+Hotel+76+Le+Lai+Ho+Chi+Minh) |
| CS016 | 186 | KHÁCH SẠN CỬU LONG (MAJESTIC) | Hotel Majestic Saigon — 1 Đồng Khởi | Tên cũ/biệt danh | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Hotel+Majestic+Saigon+1+Dong+Khoi+Ho+Chi+Minh) |
| CS017 | 187 | KHÁCH SẠN SOFITEL PLAZA | Sofitel Saigon Plaza — 17 Lê Duẩn | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Sofitel+Saigon+Plaza+17+Le+Duan+Ho+Chi+Minh) |
| CS018 | 189 | KHÁCH SẠN LOTTE LEGEND | Lotte Hotel Saigon — 2A-4A Tôn Đức Thắng | Tên cũ/biệt danh | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Lotte+Hotel+Saigon+2A-4A+Ton+Duc+Thang+Ho+Chi+Minh) |
| CS019 | 190 | KHÁCH SẠN PULLMAN SAIGON CENTRE | Pullman Saigon Centre — 148 Trần Hưng Đạo | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Pullman+Saigon+Centre+148+Tran+Hung+Dao+Ho+Chi+Minh) |
| CS020 | 191 | KHÁCH SẠN PARK HYATT | Park Hyatt Saigon — 2 Công Trường Lam Sơn | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Park+Hyatt+Saigon+2+Cong+Truong+Lam+Son+Ho+Chi+Minh) |
| CS021 | 192 | KHÁCH SẠN CARAVELLE | Caravelle Saigon — 19-23 Công Trường Lam Sơn | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Caravelle+Saigon+19-23+Cong+Truong+Lam+Son+Ho+Chi+Minh) |
| CS022 | 193 | KHÁCH SẠN NIKKO SAIGON | Hotel Nikko Saigon — 235 Nguyễn Văn Cừ | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Hotel+Nikko+Saigon+235+Nguyen+Van+Cu+Ho+Chi+Minh) |
| CS023 | 194 | KHÁCH SẠN THE REVERIE SAIGON | The Reverie Saigon Hotel — 22-36 Nguyễn Huệ | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=The+Reverie+Saigon+Hotel+22-36+Nguyen+Hue+Ho+Chi+Minh) |
| CS024 | 195 | LE MERIDIEN SAIGON | Le Méridien Saigon — 3C Tôn Đức Thắng | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Le+Meridien+Saigon+3C+Ton+Duc+Thang+Ho+Chi+Minh) |
| CS025 | 196 | KHÁCH SẠN LA VELA SG | La Vela Saigon Hotel — 280 Nam Kỳ Khởi Nghĩa | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=La+Vela+Saigon+Hotel+280+Nam+Ky+Khoi+Nghia+Ho+Chi+Minh) |
| CS026 | 197 | KHÁCH SẠN HOTEL DES ARTS SAIGON | Hôtel des Arts Saigon - MGallery Collection — 76-78 Nguyễn Thị Minh Khai | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Hotel+des+Arts+Saigon+76-78+Nguyen+Thi+Minh+Khai+Ho+Chi+Minh) |
| CS027 | 199 | THƯƠNG MẠI AN ĐÔNG (WINDSOR PLAZA HOTEL) | Windsor Plaza Hotel — 18 An Dương Vương | Tên cũ/biệt danh | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Windsor+Plaza+Hotel+18+An+Duong+Vuong+Ho+Chi+Minh) |
| CS028 | 100 | SILVERLAND MIN | Silverland Min Hotel — 81 Hai Bà Trưng | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Silverland+Min+Hotel+81+Hai+Ba+Trung+Ho+Chi+Minh) |
| CS029 | 134 | KHÁCH SẠN VIỄN ĐÔNG | Vien Dong Hotel — 275A Phạm Ngũ Lão | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Vien+Dong+Hotel+275A+Pham+Ngu+Lao+Ho+Chi+Minh) |
| CS030 | 157 | KHÁCH SẠN CONTINENTAL | Hotel Continental Saigon — 132-134 Đồng Khởi | Chính xác | [Bản đồ](https://www.google.com/maps/search/?api=1&query=Hotel+Continental+Saigon+132-134+Dong+Khoi+Ho+Chi+Minh) |

Địa chỉ trong cơ sở dữ liệu được lưu kèm “Thành phố Hồ Chí Minh”; bảng trên lược phần này để dễ đọc.

## 4. Các nguồn kiểm tra đại diện

- [Silverland Yen – trang liên hệ chính thức](https://silverlandyenhotel.com/contact-us/)
- [Liberty Central Saigon Riverside – trang liên hệ chính thức](https://www.libertycentralsaigonriverside.com/en/contact-us/)
- [Hôtel des Arts Saigon – trang vị trí chính thức](https://www.hoteldesartssaigon.com/location/)
- [The Myst Dong Khoi – Google Maps](https://www.google.com/maps?q=Ho+Huan+Nghiep+Street%2C+Ben+Nghe+Ward%2C+District+1%2C+6-8%2CThe+Myst+Dong+Khoi)
- [Hotel Continental Saigon – Google Hotels/Maps](https://www.google.com.vn/travel/hotels/entity/ChYIrsL5scbfwt5xGgovbS8wM2N3N2dyEAE)

## 5. Phạm vi dữ liệu được phép hiểu

Kết quả này chỉ xác nhận danh tính và địa chỉ của 30 cơ sở được chọn tại thời điểm kiểm tra. Nó không xác nhận quyền sử dụng thương mại, số sao, loại phòng, ảnh, tiện nghi, giá, số phòng còn bán, chính sách, đánh giá hoặc giao dịch. Các dữ liệu đó trong bộ khởi tạo đều là dữ liệu tổng hợp dành cho việc viết và thử chương trình.
