# Bản tích hợp ngày 01/11/2026

Nhánh `integration/team-prs-20261101` ghép các PR #1, #2, #3, #7, #9, #10 và #5 từ main `79c50db`. Nhánh giữ lịch sử commit của từng phần; chưa đưa thay đổi vào main.

## Ghép chức năng

- Giữ màn hình/bộ xử lý bếp của #3 và nối dữ liệu đơn từ #5.
- Giữ quản lý nhân viên của #1, ghép tạo/duyệt yêu cầu điều chuyển nhân sự SD-29 của #5. Bộ xử lý POST được phân tuyến để không trả lỗi cho action của phần còn lại.
- Giữ danh mục/tồn kho của #10, ghép tạo yêu cầu nhập nguyên liệu SD-28 của #5. Nạp CSS/JavaScript của từng module cùng với tài nguyên chi nhánh #7.
- Giữ quản lý món #9 và định mức #2; giữ kiểm tra xóa chi nhánh và quyền Chủ chuỗi duyệt toàn bộ đã được nhóm xác nhận.

## Sửa trong lúc tích hợp

- POST thanh toán kiểm tra `payment.manage`; người chỉ có quyền xem không được thanh toán.
- Các thời điểm nghiệp vụ của #5 dùng `NOW()` theo múi giờ kết nối +07:00.
- Tạo đơn yêu cầu bàn đã `DANG_PHUC_VU`; từ chối bàn chưa tiếp nhận khách hoặc ngoài chi nhánh.
- Kho dùng chi nhánh từ session thay vì gán CN01; nhãn và số liệu theo chi nhánh được chọn, Chủ chuỗi có thể xem toàn chuỗi.
- Thao tác ghi danh mục kho kiểm tra `inventory.manage` và CSRF. Ngừng nguyên liệu thay vì xóa bản ghi; chặn nếu còn tồn hoặc cam kết điều chuyển. Giữ `page=inventory` khi tìm kiếm; thống nhất tên bảng với schema.
- Cập nhật hợp đồng nghiệp vụ R06/R07/R09 theo quyết định đã xác nhận của nhóm.

## Kiểm chứng đã chạy

- PHP 8.3.30: kiểm tra cú pháp 44 file PHP; Node: kiểm tra cú pháp 4 file JavaScript.
- `git diff --check`: đạt sau khi xử lý khoảng trắng thừa.
- MySQL 8.4.3 riêng trên localhost:33317, không dùng database Laragon hiện tại. Chạy setup/migration 001, 002, 004, 005 thành công; chạy lại setup giữ nguyên tài khoản và bỏ qua migration đã áp dụng.
- 35 kiểm tra HTTP: đăng nhập 5 vai trò, 9 màn hình của Chủ chuỗi, kiểm tra quyền thanh toán/ghi kho và CSRF, SD-28/29 cùng bộ xử lý kho/nhân sự, gọi món → bếp → hủy/thanh toán, từ chối bàn trống và bàn khác chi nhánh.
- 19 kiểm tra dữ liệu/controller: lưu/chống sửa công thức cũ; tạo/ngừng nhân viên và khóa tài khoản; tạo/ngừng/xóa chi nhánh, từ chối dấu vân tay cũ và rollback; lập/chống phân bổ quá lượng/hủy phiếu NCC và mở lại yêu cầu.
- 6 kiểm tra bổ sung: Chủ chuỗi duyệt và hủy điều chuyển, lưu đúng người duyệt, không đổi tồn vật lý; kho CN02 dùng nhãn/số dư CN02; không ngừng NVL còn tồn.
- Tổng 60 kiểm tra có dữ liệu giả trên môi trường riêng. Chưa thử tải đồng thời và chưa kiểm tra toàn bộ giao diện bằng trình duyệt.

## Khi nhận vào main

1. Sao lưu database của máy đang dùng trước khi cập nhật.
2. Cập nhật code, chạy `php database/setup.php` theo hướng dẫn dự án để áp dụng migration còn thiếu và giữ mật khẩu tài khoản hiện có. Không sao chép `config/database.php` của người khác.
3. Kiểm tra nhanh các màn hình theo vai trò và chọn đúng chi nhánh.
4. Tránh merge lại riêng các PR thành viên bằng cách squash sau khi đã nhận bản tổng; chúng đã nằm trong lịch sử nhánh tích hợp. Đối chiếu trạng thái GitHub rồi đóng những PR còn mở nếu cần.

## Giới hạn còn lại

Bản này tích hợp phần đã nộp, không tuyên bố hoàn tất cả 32 sequence. Các màn hình đặt bàn/báo cáo chưa có PR triển khai trong tập này vẫn giữ baseline. Luồng POS cần dữ liệu bàn đã tiếp nhận khách hợp lệ. Thanh toán và mã QR trong bài nhóm chưa phải tích hợp ngân hàng được kiểm chứng. Hủy kế hoạch nhập/điều chuyển vẫn yêu cầu người dùng xác nhận chưa xuất/nhập hàng; chưa tự đối chiếu chứng từ thực hiện kho.
