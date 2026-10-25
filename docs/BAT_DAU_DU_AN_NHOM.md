# Bắt đầu dự án nhóm F&B Nodes

## Chạy trên máy thành viên

1. Cài/bật Laragon có PHP 8.3 và MySQL 8.4; máy nhóm trưởng dùng PHP 8.3.30, MySQL 8.4.3.
2. Clone repo vào `C:\laragon\www\restaurant-chain` (hoặc thư mục www tương ứng nếu Laragon đặt nơi khác).
3. Bật Apache/MySQL. Mở **Laragon Terminal** tại thư mục dự án; chạy `php -v`, `mysql --version` để kiểm tra terminal nhận chương trình.
4. Chạy `php database/setup.php`; nhập `123456` khi được hỏi mật khẩu khởi tạo 5 tài khoản demo. Nếu MySQL quản trị có mật khẩu, đặt RESTAURANT_SETUP_PASSWORD chỉ trong terminal máy mình theo README_DATABASE.md.
5. Mở `http://localhost/restaurant-chain/login.php`. Tài khoản: `chu_chuoi`, `quan_ly`, `phuc_vu`, `bep`, `kho`; mật khẩu là giá trị đã nhập khi setup. Khách đặt bàn/tra cứu không cần đăng nhập.

Script tạo database `restaurant_chain_scope_v2`, seed danh mục và config/database.php riêng máy. Không cần import SQL thủ công thêm lần nữa. Chạy lại setup giữ nguyên mật khẩu tài khoản đã có. Không chép config/database.php của nhóm trưởng cho các máy khác. Nếu có cấu hình từ V1, không import đè; báo nhóm trưởng để chuyển đổi đúng baseline.

## Các tài liệu phải đọc

- [Database và cài đặt](../README_DATABASE.md)
- [Rà soát 32 sequence](database_v2/RA_SOAT_32_SEQUENCE.md)
- [Từ điển và bảng → sequence](database_v2/TU_DIEN_DATABASE.md)
- [Quy tắc giao dịch](database_v2/QUY_TAC_GIAO_DICH.md)
- [ERD, domain và class](database_v2/ERD_DOMAIN_CLASS.md)
- [Nội dung cập nhật báo cáo](database_v2/CAP_NHAT_BAO_CAO.md)

## Phân công

| Thành viên | Thư mục chính | Sequence |
|---|---|---|
| Lý Bảo Nghi | modules/dat-ban | SD-1–6 |
| Lê Xuân Lộc | modules/nhan-su, modules/bep, modules/dinh-muc | SD-7–12 |
| Phạm Hiếu Hòa | modules/chi-nhanh | SD-13–18 |
| Trần Như Ý | modules/mon-an | SD-19–24 |
| Trần Thị Như Ý | modules/goi-mon; phối hợp thư mục cho SD-28/29 | SD-25–29 |
| Lương Nguyễn Thành Vinh | modules/kho, modules/bao-cao | SD-30–32 |

Thư mục chỉ tổ chức source; các use case dùng chung dữ liệu vẫn theo người được phân công. Thống nhất file cần thêm trước khi hai người cùng sửa module kho/nhân sự. Người lập trình không đồng nghĩa actor của use case; ví dụ SD-31 do Vinh làm nhưng actor là quản lý chi nhánh nguồn.

## Làm trên branch riêng

Từ nhánh main mới nhất, tạo nhánh theo chức năng, ví dụ:

```bash
git switch main
git pull --ff-only
git switch -c feature/dat-ban
```

Viết một chức năng có dữ liệu thật, tự kiểm tra, commit và push nhánh rồi mở Pull Request về main. Nhóm trưởng review trước khi ghép. Không force-push main; không tự sửa file chung hoặc schema đã chốt mà chưa trao đổi.

Mọi module dùng `db()` trong includes/database.php; quyền/phiên đăng nhập lấy từ includes/auth.php. Không tạo kết nối PDO hoặc tài khoản đăng nhập riêng trong module. Đọc cách tích hợp qua index.php và config/pages.php hiện có; các file nội bộ bị chặn truy cập trực tiếp qua Apache.

Mọi thay đổi database sau baseline là migration mới 003_*.sql trở đi. Không sửa 001/002 đã áp dụng, không thêm bảng chỉ trên database cá nhân. Dùng transaction và khóa theo QUY_TAC_GIAO_DICH.md.

## Trạng thái bản khởi đầu

Đăng nhập, phân quyền và quản lý tài khoản đã dùng MySQL thật. Các module nghiệp vụ còn giao diện/dữ liệu mẫu để thành viên tiếp tục. Database không tự làm các chức năng này hoạt động. Một số trang mẫu có thanh toán/lương/chấm công/tài chính ngoài phạm vi; không triển khai chúng trong 32 use case. Ma trận quyền trang mẫu hiện tại cần được đối chiếu tiếp khi từng module thật hoàn thành, đặc biệt báo cáo kho và xác nhận nguồn.

Mốc đầu tiên: mỗi người chạy được dự án, xác nhận bảng dữ liệu liên quan và hoàn thành một chức năng nhỏ có đọc/ghi MySQL. Nhóm trưởng thống nhất hạn nộp riêng với nhóm.
