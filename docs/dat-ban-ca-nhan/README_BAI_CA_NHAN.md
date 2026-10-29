# Bài cá nhân Lý Bảo Nghi — module đặt bàn

Phạm vi: sáu chức năng SD-1 đến SD-6 theo báo cáo giữa kỳ. PHP thuần/PDO, MySQL 8.4, dùng database V2 hiện có. Không đổi schema; không viết thay module của thành viên khác. Branch cá nhân: feature/dat-ban.

## Chạy bài và demo

Trên máy đã setup V2: bật Apache/MySQL và mở các URL dưới đây. Năm tài khoản demo vẫn dùng mật khẩu đã có (máy nhóm trưởng là 123456).

1. Khách mở `index.php?page=booking`, chọn chi nhánh, ngày giờ tương lai, số khách, tên và SĐT; gửi form. Ghi lại mã đặt bàn. Yêu cầu lưu CHO_XAC_NHAN, chưa giữ bàn.
2. Khách mở `index.php?page=tracking`, nhập cùng SĐT để xem trạng thái.
3. Đăng nhập `phuc_vu` (CN01); mở `index.php?page=floor`, tìm theo tên/SĐT/mã và chọn Chi tiết.
4. Chọn giờ kết thúc dự kiến sau giờ đến, bấm Kiểm tra bàn phù hợp, chọn bàn rồi Xác nhận. Tải lại tra cứu khách để thấy Đã xác nhận.
5. Mở Hủy đặt bàn, thử nút Không trước; trạng thái không đổi. Mở lại, nhập lý do và Xác nhận hủy; yêu cầu chuyển Đã hủy, lịch được giải phóng, lịch sử giữ nguyên.
6. Tại trang floor, mở Tạo đặt bàn tại quầy; chọn bàn trống, số khách và giờ kết thúc dự kiến trong tương lai, tạo. Đơn và bàn DANG_PHUC_VU. Tên/SĐT khách quầy không bắt buộc; nếu nhập SĐT thì phải hợp lệ.

Khi demo dùng ngày giờ mới ở tương lai và đúng chi nhánh của nhân viên. Không hủy lượt đang phục vụ: chức năng hủy theo đặc tả chỉ áp dụng CHO_XAC_NHAN/DA_XAC_NHAN. Module không tự hoàn tất phiên phục vụ vì đó không thuộc sáu use case.

## Cấu trúc code và cách giải thích

- Boundary: dat-ban.php, theo-doi.php, index.php trong modules/dat-ban; form GET/POST giữ giao diện chung.
- web.php: adapter HTTP, CSRF, kiểm tra quyền hiện có, khóa gửi lặp theo phiên và redirect sau POST thành công.
- DatBanControl.php: kiểm tra nghiệp vụ, phạm vi, điều phối Entity trong cùng transaction. PDO được truyền từ db(), không tạo kết nối riêng.
- Entities.php: ChiNhanhEntity, BanEntity, DatBanEntity, mỗi Entity chịu trách nhiệm truy vấn dữ liệu tương ứng bằng prepared statements.
- assets/js/reservations.js: điều hướng và hộp thoại hủy. Các trang đặt bàn không dùng JS dữ liệu giả của app.js.
- assets/css/reservations.css: bổ sung bố cục cho danh sách/form, không thay CSS của module khác.
- Layout chung chỉ đổi lựa chọn CSS/JS và nhãn dữ liệu cho booking/tracking/floor; các trang khác giữ script cũ.

Control điều phối nhiều Entity vì việc gán bàn hoặc nhận tại quầy phải nguyên tử. Sơ đồ cập nhật phản ánh đúng code này và luồng Control trong báo cáo gốc; không tách thành các thao tác rời làm mất transaction.

## Quy tắc chính

- Chỉ PHUC_VU xử lý ghi tại chi nhánh của mình. Chủ chuỗi/Quản lý có thể xem theo quyền trang hiện có; Bếp/Kho không truy cập floor. Khách tạo và theo dõi không đăng nhập.
- Ngày giờ web phải ở tương lai, số khách là số nguyên dương (giới hạn lưu SMALLINT 32767), tên 1–100 ký tự; SĐT chuẩn hóa khoảng trắng/dấu chấm/gạch ngang rồi phải có 10 số bắt đầu 0; ghi chú tối đa 500.
- Khung giờ [bắt đầu,kết thúc) được xem trùng khi đầu cũ < cuối mới và cuối cũ > đầu mới. Hai khoảng sát giờ được phép.
- Khóa ChiNhanh FOR SHARE → Ban FOR UPDATE → DatBan FOR UPDATE, đọc lại trạng thái/lịch sau khóa. Danh sách bàn chỉ là gợi ý trước khi commit. Test XN08/TQ08 thực thi hai process đồng thời.
- Xác nhận lưu khoảng thời gian trong DatBan; nhãn Đã đặt là lịch, không thay trạng thái vật lý Ban thành Đã đặt vĩnh viễn. Bàn đang phục vụ hiện tại có thể nhận lịch tương lai không trùng; tại quầy phải có trạng thái TRONG.
- Hủy giữ maBan và lý do/người hủy, không UPDATE Ban=TRONG vô điều kiện. Nếu trạng thái/maBan đổi trong lúc chờ khóa thì từ chối và yêu cầu tải lại.
- POST có CSRF; tạo web/tại quầy dùng request_key theo phiên để ngăn gửi lại form thành công. Dùng POST/Redirect/GET. Theo dõi dùng POST, không đưa SĐT lên URL; chỉ trả các trường cần thiết và giới hạn 20 lần/phút theo phiên.

## Chạy bộ test service

Trong Laragon Terminal tại thư mục dự án, chạy:

```bash
php tests/dat-ban/run.php
```

Script dùng tài khoản quản trị qua RESTAURANT_SETUP_HOST/PORT/USER/PASSWORD (mặc định root localhost không mật khẩu), tự tạo database tên ngẫu nhiên fbn_reservation_test_*, chạy schema/seed và tự xóa khi kết thúc. Không dùng dữ liệu thật của restaurant_chain_scope_v2. Kết quả JSON mặc định tests/dat-ban/results.json, được loại khỏi Git; có thể đổi đường dẫn bằng RESERVATION_TEST_RESULTS. Cần CLI PHP có pdo_mysql, mbstring và proc_open cho hai test đồng thời; test rollback dùng CREATE TRIGGER trong database tạm.

## Kết quả và giới hạn

63 test case đạt: 41 service/PDO, 18 HTTP và 4 thao tác trình duyệt. Kiểm thử HTTP dùng bản sao source cùng database tạm. Hồi quy đăng nhập vẫn qua 70 tổ hợp trang theo năm vai trò. Số test không đồng nghĩa chứng minh không còn lỗi; cần chạy lại sau mỗi sửa đổi. Chưa kiểm thử MariaDB/XAMPP hoặc triển khai công khai.

Theo dõi bằng SĐT giữ đúng đặc tả gốc, chưa thêm OTP/tài khoản khách. Giới hạn theo phiên không thay thế cơ chế chống lạm dụng tại hạ tầng nếu đưa lên internet. Xem TEST_CASES.md, results.json và SEQUENCE_CAP_NHAT.md để cập nhật bài cá nhân. Chưa sửa file Word báo cáo giữa kỳ gốc.
