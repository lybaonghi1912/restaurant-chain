# PHP Restaurant Chain

Hệ thống quản lý chuỗi nhà hàng của nhóm F&B Nodes.


## Bắt đầu cho thành viên mới

Đọc [hướng dẫn dự án nhóm](docs/BAT_DAU_DU_AN_NHOM.md). Bật Apache/MySQL Laragon, mở Laragon Terminal tại dự án và chạy `php database/setup.php`. Nhập `123456` để tạo bộ tài khoản demo của nhóm, rồi mở `http://localhost/restaurant-chain/login.php`.

Baseline hiện hành: **V2, 32 sequence đã phân công**, 30 bảng nghiệp vụ + 2 bảng kỹ thuật. Đăng nhập/phân quyền/tài khoản và sáu chức năng đặt bàn đã dùng MySQL thật trên branch feature/dat-ban; các module khác tiếp tục theo phân công. Xem [README database](README_DATABASE.md) và [quy tắc giao dịch](docs/database_v2/QUY_TAC_GIAO_DICH.md).

## Môi trường

- PHP 8.3, MySQL 8.4, Apache trên Laragon.
- Kết nối database bằng PDO.
- Mở dự án qua http://localhost/restaurant-chain/ khi Apache đang chạy.

## Cấu trúc thư mục

```text
restaurant-chain/
|-- config/                 Cấu hình và kết nối PDO
|-- includes/               Kiểm tra đăng nhập, hàm hỗ trợ
|-- layouts/                Header, sidebar và footer chung
|-- assets/
|   |-- css/                CSS theo giao diện mẫu
|   |-- js/                 Xử lý tương tác giao diện
|   `-- images/             Hình ảnh
|-- modules/
|   |-- dat-ban/            Lý Bảo Nghi
|   |-- goi-mon/            Trần Thị Như Ý
|   |-- mon-an/             Trần Như Ý
|   |-- chi-nhanh/          Phạm Hiếu Hòa
|   |-- nhan-su/            Lê Xuân Lộc
|   |-- bep/                Lê Xuân Lộc
|   |-- dinh-muc/           Lê Xuân Lộc
|   |-- kho/                Lương Nguyễn Thành Vinh
|   `-- bao-cao/            Lương Nguyễn Thành Vinh
|-- database/
|   |-- schema.sql          Cấu trúc database chung
|   |-- seed.sql            Dữ liệu mẫu
|   `-- migrations/         Các lần thay đổi database
|-- index.php               Trang vào hệ thống
|-- .gitignore
`-- README.md
```

Các file .gitkeep giúp Git lưu các thư mục đang trống.
Database V2 bám 32 sequence đã phân công: 30 bảng nghiệp vụ, 2 bảng kỹ thuật và 3 view kho. Xem README_DATABASE.md và docs/database_v2/. Bản V1 mở rộng được giữ trong archive, không còn là baseline chung.
Các module nghiệp vụ có giao diện PHP với dữ liệu mẫu. Đăng nhập, phân quyền và quản lý tài khoản đã dùng MySQL thật.
Phân công chức năng chi tiết theo báo cáo của nhóm; thư mục là nơi tổ chức source.

## Quy ước làm việc

1. Thống nhất tên file, class, biến, phương thức và bảng MySQL.
2. Dùng chung một bộ kết nối PDO và schema MySQL.
3. Mỗi thành viên làm branch riêng, merge qua Pull Request.
4. Thống nhất hợp đồng phương thức giữa các module trước khi tích hợp.
5. Không tự ý ghi đè code người khác; thay đổi phần dùng chung phải review.
6. Dùng Git tag và backup source/database định kỳ.

Không commit mật khẩu, cấu hình riêng của từng máy hoặc bản sao lưu database vào Git.
Khi triển khai kết nối, tạo config/database.example.php làm mẫu và config/database.php cho máy cá nhân.
## Giao diện theo mẫu

Giao diện chuyển từ mẫu F&B Restaurant Management System. Website chạy bằng PHP, HTML, CSS và JavaScript thuần; không cần chạy React, Vite hoặc npm để sử dụng.

1. Mở Laragon và Start All.
2. Truy cập http://localhost/restaurant-chain/ và nhấn Ctrl+F5 nếu còn thấy CSS cũ.
3. Dùng menu để chuyển 14 màn hình. Trang khách đặt bàn và tra cứu có bố cục riêng như mẫu gốc.

### Các đường dẫn màn hình

- index.php?page=floor: sơ đồ bàn và yêu cầu đặt bàn.
- index.php?page=booking: đặt bàn trực tuyến.
- index.php?page=tracking: tra cứu theo số điện thoại.
- index.php?page=pos: gọi món và giỏ món.
- index.php?page=checkout: thanh toán tiền mặt hoặc QR minh họa.
- index.php?page=kds: màn hình bếp.
- index.php?page=inventory: kho và cảnh báo.
- index.php?page=transfer: điều chuyển, nhập nhà cung cấp.
- index.php?page=staff: nhân sự.
- index.php?page=payroll: chấm công, lương.
- index.php?page=bom: định mức nguyên vật liệu.
- index.php?page=executive: tổng quan chuỗi và báo cáo.
- index.php?page=menu: quản lý món ăn (bổ sung theo nhóm chức năng trong báo cáo).
- index.php?page=branches: quản lý chi nhánh (bổ sung theo nhóm chức năng trong báo cáo).

### Cách ghép trang

index.php đọc danh sách cho phép trong config/pages.php, nạp header, sidebar, topbar, file view của module và footer. Mỗi file module chỉ chứa phần nội dung; không viết lại toàn bộ HTML hoặc menu. Trang khách không nạp sidebar quản trị.

assets/css/app.css giữ kiểu dáng gốc và các điều chỉnh nhỏ cho PHP. Font Be Vietnam Pro và ảnh nền từ mẫu đã lưu trong assets để chạy cục bộ. assets/js/app.js xử lý tương tác minh họa; includes/modal-templates.php chứa các hộp thoại dùng chung.

### Phạm vi hiện tại

- Các số liệu, khách, nhân viên, tồn kho, doanh thu, giá và thuế là dữ liệu mẫu từ giao diện tham khảo.
- Các tương tác nghiệp vụ đặt bàn, POS, bếp, kho, lương vẫn là dữ liệu mẫu trên trang. Đăng nhập, phân quyền, tạo/khóa tài khoản và đổi mật khẩu đã ghi MySQL thật.
- QR là hình minh họa, không dùng chuyển tiền. Xuất bảng công hiện xuất dữ liệu mẫu dạng CSV.
- Đặt bàn chờ xác nhận chưa giữ bàn. Hộp thoại xác nhận yêu cầu chọn bàn phù hợp; hủy yêu cầu đã xác nhận chỉ giải phóng bàn của yêu cầu đó trong bản mẫu.
- Kiểm tra đồng thời và giao dịch nguyên tử cần triển khai phía PHP/PDO/MySQL trước khi dùng thật.
- Các khoảng thời gian phản hồi, dữ liệu tháng 06/2025 và ngưỡng số liệu hiển thị giữ theo mẫu, chưa phải cam kết vận hành.

### Bước triển khai tiếp theo

Baseline V2 đã chốt theo 32 sequence. Thành viên thay dữ liệu minh họa bằng PDO đúng phần được giao, theo docs/database_v2/QUY_TAC_GIAO_DICH.md; không triển khai các chức năng chưa phân công.

## Đăng nhập và phân quyền

Mở http://localhost/restaurant-chain/login.php. Có 5 vai trò: CHU_CHUOI, QUAN_LY, PHUC_VU, BEP, KHO. Khách mở trang đặt bàn/tra cứu không cần tài khoản.

- Chủ chuỗi quản lý tài khoản và phân quyền toàn chuỗi.
- Quản lý chi nhánh chỉ quản lý tài khoản Phục vụ, Bếp, Kho thuộc chi nhánh của mình.
- Nhân viên được chuyển về trang phù hợp vai trò sau đăng nhập. Gõ URL ngoài quyền trả HTTP 403.
- Mật khẩu băm bằng Argon2id; đăng nhập tạo lại mã phiên. Cookie HttpOnly, SameSite=Lax; Secure tự bật khi dùng HTTPS.
- Phiên hết hạn sau 30 phút không hoạt động hoặc tối đa 8 giờ. Khóa tài khoản, đổi quyền hoặc đổi mật khẩu thu hồi phiên cũ.
- Đăng nhập sai 5 lần cho một tên đăng nhập sẽ tạm khóa đăng nhập 15 phút; đồng thời giới hạn theo địa chỉ IP.
- Tạo/đổi tài khoản, đổi mật khẩu, đăng xuất dùng POST có CSRF. Không gửi mật khẩu qua URL.

Hướng dẫn tích hợp module và bảng quyền nằm trong docs/PHAN_QUYEN.md.

### Cài trên máy thành viên khác

1. Bật Apache và MySQL trong Laragon; dùng PHP 8.3 có pdo_mysql, mbstring và Argon2id.
2. Đặt source trong thư mục www/restaurant-chain (hoặc sửa BASE_URL trong config/app.php).
3. Trong Terminal Laragon, vào thư mục dự án rồi chạy: php database/setup.php
4. Nhập mật khẩu khởi tạo tài khoản dùng thử khi được hỏi (ít nhất 6 ký tự; bộ demo nhóm dùng 123456).
5. Script tạo database restaurant_chain_scope_v2 và tài khoản PDO giới hạn quyền trong database đó; sinh config/database.php riêng máy. Không commit file này.
6. Nếu MySQL root có mật khẩu, đặt biến môi trường RESTAURANT_SETUP_PASSWORD trong terminal của máy trước khi chạy setup. Không ghi mật khẩu vào source.

Tên đăng nhập dùng thử: chu_chuoi, quan_ly, phuc_vu, bep, kho. Mật khẩu là mật khẩu đã chọn khi setup. Chạy lại setup giữ nguyên mật khẩu tài khoản đã có. Đổi mật khẩu dùng thử trong menu Đổi mật khẩu trước khi sử dụng dữ liệu thật.

Các thư mục config, includes, layouts, modules, database bị chặn truy cập trực tiếp qua Apache. Các trang module được nạp qua index.php. setup.php chỉ chạy bằng CLI.

Tài liệu tham khảo PHP: https://www.php.net/manual/en/function.password-hash.php và https://www.php.net/manual/en/features.session.security.management.php


## Database chung V2 ngày 25/10/2026

Đã áp dụng 001_auth và 002_scope_sequences vào restaurant_chain_scope_v2. Bản cũ restaurant_chain được giữ nguyên; database/archive/v1_expanded_51 và docs/archive_v1 chỉ để đối chiếu, không dùng làm baseline hiện hành. Xem README_DATABASE.md và docs/database_v2/. Các module nghiệp vụ vẫn cần được lập trình theo phân công; schema không tự biến giao diện mẫu thành chức năng thật.

## Bài cá nhân đặt bàn

Branch `feature/dat-ban` triển khai SD-1 đến SD-6 bằng PDO thật. Xem [hướng dẫn và demo](docs/dat-ban-ca-nhan/README_BAI_CA_NHAN.md), [test case](docs/dat-ban-ca-nhan/TEST_CASES.md), [sequence cập nhật](docs/dat-ban-ca-nhan/SEQUENCE_CAP_NHAT.md). Chạy `php tests/dat-ban/run.php` để kiểm thử nghiệp vụ bằng database tạm; script không reset database dự án.

### Cập nhật giữ chỗ 15 phút trên feature/dat-ban

Chạy `php database/migrate.php` để cập nhật các migration còn thiếu, gồm 003 và 004. Không nhập giờ kết thúc dự kiến. Bộ kiểm thử kỹ thuật gồm 63 ca nghiệp vụ, 28 ca HTTP và 2 ca migration/Event; bộ test thủ công trong Word gồm 44 ca. Xem hướng dẫn bài cá nhân và `tests/dat-ban/README.md`.
