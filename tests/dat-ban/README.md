# Kiểm thử kỹ thuật module đặt bàn

Các lệnh chạy tại thư mục dự án:

```text
php tests/dat-ban/run.php
php tests/dat-ban/infrastructure.php
python tests/dat-ban/http/run.py
```

- `run.php`: 63 ca nghiệp vụ, bao gồm dữ liệu sai, phân quyền chi nhánh, rollback, tranh chấp bàn và giờ mở cửa.
- `infrastructure.php`: 2 ca migration và Event tự hủy.
- `http/run.py`: 28 ca HTTP, bao gồm form, CSRF, chuyển hướng, chống gửi lặp, MVC, bộ lọc và tài nguyên giao diện. Cần Python 3 và PHP trong PATH; có thể đặt RESTAURANT_PHP thành đường dẫn php.exe.

Các bộ test tạo database tạm riêng, không reset database dự án. Cần quyền tạo/xóa database; kiểm thử rollback cần trigger, kiểm thử đồng thời cần proc_open, kiểm thử hẹn giờ cần quyền Event. Test HTTP tạo bản sao source và chỉ cố định clock trong bản sao controller ở 17:30 hôm nay, không đổi đồng hồ hệ thống hoặc source đang chạy. Sau kiểm thử sẽ dọn bản sao và database tạm.

Kết quả ghi ra results.json, infrastructure-results.json và http-results.json trong thư mục này, được loại khỏi Git. Có thể đổi vị trí bằng RESERVATION_TEST_RESULTS, RESERVATION_INFRA_RESULTS và RESERVATION_HTTP_RESULTS.

Nghiệp vụ và HTTP dùng RESTAURANT_SETUP_HOST, PORT, USER, PASSWORD nếu có; mặc định MySQL root tại 127.0.0.1:3306. Không ghi mật khẩu thật vào source. Các ca tự động dùng tài khoản và dữ liệu tạm.

Bộ test tự động tách biệt với 44 ca thủ công trong tài liệu Word của bài cá nhân. Môi trường đã kiểm tra là PHP 8.3.30 và MySQL 8.4.3; chưa xác nhận MariaDB.
