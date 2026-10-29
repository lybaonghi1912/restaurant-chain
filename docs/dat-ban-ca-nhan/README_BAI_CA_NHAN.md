# Bài cá nhân Lý Bảo Nghi module đặt bàn

Phiên bản giữ chỗ 15 phút ngày 30/10/2026, branch feature/dat-ban. Dùng sáu use case SD-01..SD-06 theo phân công. Bản này thay hoàn toàn mô tả nhập giờ kết thúc và kết quả 63 test cũ.

## Chuẩn bị và chạy
1. Bật Apache/MySQL trong Laragon. Tại thư mục dự án chạy `php database/migrate.php` để áp dụng migration 003 và cài tác vụ hết hạn. Không reset database đang dùng. Máy mới dùng `php database/setup.php` theo README chung.
2. Khách mở `index.php?page=booking`, điền ngày giờ tương lai và gửi; lưu mã đặt bàn. Dùng `index.php?page=tracking` và cùng SĐT để xem trạng thái.
3. Đăng nhập `phuc_vu` mật khẩu demo `123456` trên máy nhóm trưởng; mở `index.php?page=floor`, tìm mã và chọn Chi tiết. Năm tài khoản cũ được giữ nguyên.
4. Chọn Kiểm tra bàn phù hợp → chọn bàn → Xác nhận; không nhập giờ kết thúc. Đặt 18:00 thì hạn giữ chỗ là 18:15.
5. Khi khách đến đúng hạn, bấm Khách đã đến. Bàn trống: đơn/bàn DANG_PHUC_VU; bàn bận: ghi nhận đã đến và chờ, khi trống bấm Tiếp nhận vào bàn. Không tự hủy khách đã đến hoặc đang ăn.
6. Thử hủy trên một đơn CHO_XAC_NHAN/DA_XAC_NHAN khác: nút Không giữ dữ liệu; Xác nhận hủy có lý do lưu DA_HUY. Không hủy lượt đang phục vụ.
7. Thử Tạo đặt bàn tại quầy trên bàn trống, đủ chỗ: nhập số khách; tên/SĐT tùy chọn, không nhập giờ kết thúc; nhận ngay và lưu giờ khách đến/nhận bàn.

Mốc 18:15:00 vẫn được nhận; 18:15:01 chưa đến thì tự hủy. Đơn CHO_XAC_NHAN chưa giữ bàn nên tác vụ không tự hủy; yêu cầu quá hạn không thể xác nhận. Tự hủy chỉ bỏ lịch giữ chỗ của đơn, không ép bàn đang phục vụ người khác về Trống. Các khoảng giữ chỗ tính cả hai đầu; 18:00–18:15 xung đột với lịch 18:15, không giao với 18:16. Khi đã phục vụ/chờ nhận bàn, bàn bị chặn cho tới khi hoàn tất theo luồng vận hành khác.

MySQL Event chạy mỗi phút khi MySQL đang chạy; đọc trang đặt bàn cũng đồng bộ ngay đơn quá hạn. Trang đang mở cần tải lại/tra cứu lại; chưa có push realtime. Sau khi khởi động lại MySQL, nếu event_scheduler chưa ON trong my.ini, chạy `php database/install_reservation_timer.php` bằng tài khoản quản trị. Chưa có chức năng hoàn tất phục vụ trong sáu use case cá nhân; không dự đoán thời gian khách ăn xong.

## Tài liệu và code dùng để nộp
- `Bai_ca_nhan_Ly_Bao_Nghi_Dat_ban.docx`: đặc tả và bảng test theo đúng 5 cột giảng viên, có dữ liệu D0/K0 cụ thể. Đây là bản mới thay nội dung file cũ.
- `TEST_CASES.md` và `Test_case_dat_ban.csv`: cùng 51 ca nghiệp vụ, cùng test ID với code.
- `SEQUENCE_CAP_NHAT.md`: quy tắc BR, message MSG và ánh xạ code; `sequence/SD-01.puml` đến `SD-06.puml`: nguồn vẽ lại sequence. SD-03 có thêm nhánh ghi nhận khách đến; SD-02 có tác vụ hết hạn nội bộ.
- `results.json`: kết quả mới 73/73, tách 51 nghiệp vụ, 20 HTTP, 2 CSDL/Event. Không tính lại 4 GUI của bản cũ. Ảnh trong evidence/ là ảnh giao diện lịch sử, không phải minh chứng quy tắc 15 phút mới.
- `modules/dat-ban/DatBanControl.php`: nghiệp vụ, nhận chung PDO và clock kiểm thử. `Entities.php`: truy vấn prepared statements. View và `web.php`: giao diện, quyền, CSRF và chống gửi lặp.

File Word báo cáo giữa kỳ gốc chưa bị ghi đè. Dùng các đặc tả và .puml mới để sửa lại sáu hình trong Visual Paradigm và đưa vào báo cáo. Không ghi rằng hình gốc đã tự được cập nhật.

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
