# Module đặt bàn Lý Bảo Nghi

Module triển khai sáu chức năng bằng PHP MVC, PDO và MySQL: đặt bàn trực tuyến, tạo tại quầy, hủy, tra cứu, xác nhận và theo dõi trạng thái.

## Chạy dự án

1. Bật Apache và MySQL. Máy mới làm theo README ở thư mục gốc và chạy `php database/setup.php`.
2. Máy đã có dữ liệu chạy `php database/migrate.php` để áp dụng các migration còn thiếu, gồm 003 và 004. Không import lại seed vào dữ liệu đang dùng.
3. Khách vào `login.php`, chọn Đặt bàn hoặc Theo dõi đặt bàn. Khách không cần tài khoản.
4. Nhân viên phục vụ đăng nhập, mở Quản lý đặt bàn. Danh sách mặc định chỉ hiện Chờ xác nhận và Đã xác nhận. Đổi bộ lọc để xem lịch sử hủy, hoàn tất hoặc tất cả.
5. Chọn Chi tiết để kiểm tra bàn phù hợp, xác nhận hoặc hủy có lý do. Nút Không đóng hộp thoại và giữ nguyên đơn.
6. Chọn Tạo đặt bàn tại quầy để mở màn hình riêng: chọn bàn, nhập số khách, rồi tạo đơn. Tên, SĐT và ghi chú là tùy chọn.

Giờ nhận khách từ 08:00 đến trước 22:00. Đơn trực tuyến mới ở trạng thái Chờ xác nhận, chưa giữ bàn. Sau xác nhận, giữ chỗ tối đa 15 phút sau giờ đến và không quá 22:00. Không nhập giờ khách về. Hủy giữ lịch sử và không đổi trạng thái bàn vật lý. Khách đã được tiếp nhận không bị tự hủy.

MySQL Event đồng bộ hết hạn mỗi phút; khi mở trang hệ thống cũng kiểm tra lại. Nếu Event chưa bật sau khi khởi động MySQL, chạy `php database/install_reservation_timer.php` bằng tài khoản quản trị. Trang đang mở cần tải lại để thấy trạng thái mới.

## Cấu trúc code

- `modules/dat-ban/controllers/DatBanController.php`: nhận yêu cầu, kiểm tra nghiệp vụ và điều phối giao dịch.
- `modules/dat-ban/models/`: DatBan, Ban và ChiNhanh truy vấn qua PDO.
- `modules/dat-ban/views/`: đặt bàn, theo dõi, quản lý, tại quầy và hàm hiển thị dùng chung.
- Các file PHP ở gốc module là điểm vào hoặc lớp tương thích cho code cũ.
- `database/migrations/003_reservation_arrival_window.sql` và `004_booking_opening_hours.sql`: thay đổi CSDL cần đi cùng module.

## Tài liệu nộp

- `Sau_dac_ta_dat_ban_theo_mau_bao_cao_cap_nhat.docx`: sáu đặc tả đã cập nhật.
- `Test_case_6_chuc_nang_hoan_chinh.docx`: 44 ca kiểm thử thủ công, có dữ liệu, kết quả thực tế và đánh giá.
- `sequence/`: sáu ảnh sequence đã chỉnh và file Visual Paradigm người dùng cung cấp. Dùng các file này khi cập nhật báo cáo.

Kiểm thử tự động kỹ thuật nằm trong `tests/dat-ban/`, tách khỏi 44 ca trong tài liệu nộp. Xem hướng dẫn ở đó. Kết quả tự động không thay thế kết quả kiểm thử thủ công.

Môi trường đã kiểm tra: PHP 8.3.30, MySQL 8.4.3 trên Laragon. Thành viên dùng XAMPP cần kiểm tra phiên bản database; chưa xác nhận tương thích MariaDB. Không commit `config/database.php` hay bản sao dữ liệu cá nhân.
