# Bộ kiểm thử đặt bàn

## Chạy lại kiểm thử
Tại thư mục dự án, trong Laragon Terminal:
```
php tests/dat-ban/run.php
php tests/dat-ban/infrastructure.php
```
Hai lệnh lần lượt chạy 51 và 2 ca. Cần quyền tạo/xóa database tạm; test rollback dùng trigger; test đồng thời dùng proc_open. Các test dùng database riêng fbn_reservation_test_*, tự xóa; không reset dữ liệu dự án. Kết quả lần lượt tests/dat-ban/results.json và infrastructure-results.json, không đưa vào Git. Có thể đặt RESERVATION_TEST_RESULTS và RESERVATION_INFRA_RESULTS để đổi đường dẫn.

20 ca HTTP cần Python 3 (chỉ thư viện chuẩn), chạy `python tests/dat-ban/http/run.py`; PHP phải có trong PATH hoặc đặt RESTAURANT_PHP. Script tự tạo bản sao source, database riêng fbn_booking_http_*, web server cổng tạm và dọn khi kết thúc. Kết quả http-results.json. Các ca web dùng ngày thực tế cộng 3 ngày và tài khoản tạm; không dùng 5 tài khoản thật. Đây là kiểm thử HTTP, không tuyên bố đã thao tác GUI lại.

Thông số MySQL lấy RESTAURANT_SETUP_HOST/PORT/USER/PASSWORD, mặc định root 127.0.0.1:3306 mật khẩu rỗng trong môi trường học tập; config/database.php của web không được commit. D0/K0 và clock 2030 chỉ ở test nghiệp vụ, không đổi đồng hồ máy. Mỗi ca độc lập. Chỉ cần chạy lại nhóm test bị ảnh hưởng khi sửa code, đối chiếu dữ liệu và nhánh trước khi cập nhật PASS.

Đã kiểm thử PHP 8.3.30 và MySQL 8.4.3 trên Laragon. Chưa xác nhận MariaDB/XAMPP; không coi việc cài được XAMPP là đã chạy qua migration, CHECK và Event của bản này.
