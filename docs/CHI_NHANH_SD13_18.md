# Chi nhánh và xử lý yêu cầu nguyên liệu — SD-13–18

## Cấu trúc

- `modules/chi-nhanh/index.php`: danh sách, thêm và xem chi tiết chi nhánh.
- `edit.php`, `delete.php`, `purge.php`: cập nhật, ngừng hoạt động và xác nhận xóa vĩnh viễn dữ liệu riêng khi đủ điều kiện.
- `data.php`: các hàm PDO liên quan đến chi nhánh.
- `dieu-chuyen.php`, `nhap-ncc.php`, `huy-phieu.php`: xem nguồn cung, lập phương án điều chuyển/nhập NCC và xác nhận hủy.
- `transfer-data.php`: các hàm PDO cho yêu cầu, phân bổ, duyệt và hủy.
- `giao-dien.php`: hàm hiển thị dùng chung trong module; không nạp CSS hoặc JavaScript.
- `assets/css/chi-nhanh.css`: kiểu hiển thị riêng, được giới hạn trong module.
- `assets/js/chi-nhanh.js`: chọn tất cả món và chuyển nguyên liệu cần xem nguồn cung.
- `layouts/header.php`: nạp tài nguyên riêng chỉ tại `branches` và `transfer`. Khi merge cùng PR giao diện khác, giữ cả các khối nạp tài nguyên của họ.

Không sửa Config, Core hoặc module của thành viên khác. Đường dẫn trong `config/pages.php` đã có nên giữ nguyên. Dùng `db()`, đăng nhập, phân quyền và CSRF chung của dự án.

## Database và phối hợp

PR có migration **005_cancel_supply_plans.sql** phục vụ chức năng hủy mà người phụ trách đã báo nhóm đồng ý. Cần review SQL trước khi áp dụng vào database từng máy; code không tự chạy migration khi mở trang. Không sửa schema.sql, migration 001/002 hoặc trình migration.

Migration 005 thêm `DA_HUY`, `maNguoiHuy`, `thoiGianHuy`, `lyDoHuy` và ràng buộc trên `DieuChuyenKho`, `DonNhapNCC`. Không gửi kèm dữ liệu demo, dump database hoặc cấu hình kết nối cá nhân. Các bản nháp 003/004 đã rút không nằm trong PR này.

Theo quyết định nghiệp vụ đã chốt với nhóm trưởng: Chủ chuỗi duyệt điều chuyển trực tiếp bằng `DA_DUYET`, lưu người/thời gian tại hai cột V2 có sẵn `maNguoiXacNhanNguon`, `ngayXacNhanNguon`. Không giả danh quản lý nguồn. Thành viên phụ trách SD-31 cần đồng bộ để không yêu cầu xác nhận nguồn lần nữa.

Phiếu bị hủy vẫn giữ lịch sử, không tính vào lượng phân bổ/cam kết. Khi thiếu lại, yêu cầu gốc quay về chờ xử lý. Các phần xuất/nhận kho cần chặn thực hiện từ phiếu đã hủy.

Module hiện lập phương án, chưa xuất/nhận kho vật lý. Điều kiện chưa thực hiện khi hủy được Chủ chuỗi xác nhận trên biểu mẫu; chưa có chứng từ kho gắn mã phiếu để tự kiểm tra. Khi nối xuất/nhận kho phải kiểm tra chứng từ trong cùng transaction hủy; checkbox không thay thế kiểm tra này.

## Kiểm tra và giới hạn

- Trên XAMPP cục bộ đã thử các luồng CRUD, duyệt, hủy, phân quyền, CSRF, lượng phân bổ, gửi lặp và tranh chấp nguồn trong các đợt triển khai trước; dữ liệu QA riêng đã dọn.
- Lượt tổ chức lại source đối chiếu 30 hàm nghiệp vụ giữ nguyên, kiểm tra PHP/JavaScript và render chỉ đọc trên 23 trường hợp. Kiểm tra nạp tài nguyên từ header cho hai trang thuộc module và không nạp vào các trang khác.
- Trên trình duyệt đã kiểm tra chọn tất cả/chọn một phần/bỏ chọn món, chuyển nguyên liệu qua GET và kiểu hiển thị sau khi tách assets. Không gửi thao tác lưu/hủy/xóa dữ liệu nghiệp vụ trong lượt tổ chức lại.
- Chưa kiểm thử tích hợp với code chưa merge của các thành viên khác. Cần nhóm review cả thay đổi layout, migration và các điểm nối nêu trên trước khi merge.
