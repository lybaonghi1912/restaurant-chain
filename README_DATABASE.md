# F&B Nodes — Database V2 theo 32 sequence đã phân công

Đọc docs/RA_SOAT_32_SEQUENCE.md trước khi lập trình. Đây là bộ PHP thuần/PDO + MySQL 8.4 dùng Laragon, không yêu cầu framework khác.

## Thành phần

- database/schema.sql: schema đầy đủ cho database trống, 32 bảng và 3 view.
- database/seed.sql: danh mục mẫu, không có lượng tồn/doanh thu giả và không chứa mật khẩu tài khoản.
- database/setup.php, migrate.php và migrations/: cài mới/tiếp tục đúng baseline V2.
- docs/: rà 32 hình, quy tắc giao dịch, từ điển, ERD/domain/class, nội dung cập nhật báo cáo.
- evidence/: 32 ảnh sequence trích từ báo cáo gốc.
- verification/: script kiểm thử và kết quả; chỉ chạy trên database tạm tự tạo.

## Cài trên máy thành viên

1. Nhận source PHP từ repository chung. Chép database/ từ bộ này vào source, giữ đúng cấu trúc config/includes hiện có. Nếu từng dùng V1, không chép chồng migrations làm sót 002_common_database.sql trong thư mục active; nhận source baseline V2 đã dọn và lưu V1 trong archive.
2. Khởi động Apache/MySQL Laragon. Mở Laragon Terminal tại thư mục restaurant-chain.
3. Chạy `php database/setup.php`. Script mặc định tạo restaurant_chain_scope_v2, migration, seed, tài khoản MySQL ứng dụng và config/database.php nếu máy chưa có config. Nhập mật khẩu khởi tạo demo theo hướng dẫn; nhóm đang dùng 123456 cho 5 tài khoản demo. Không ghi password trực tiếp vào seed.sql.
4. Nếu có config trỏ DB cũ, setup dừng với thông báo rõ; không tự sửa/xóa dữ liệu cũ. Sao lưu và thực hiện quy trình chuyển đổi trước. Không import bộ mới đè lên database V1 51 bảng.
5. Sau khi có baseline này, thay đổi tiếp theo phải là migration 003_*.sql; không sửa 001/002 đã áp dụng. Chạy `php database/migrate.php` bằng tài khoản quản trị cục bộ khi cần DDL. Tài khoản website chỉ có SELECT/INSERT/UPDATE/DELETE.

Cách import thủ công cho người đã hiểu cấu hình: tạo database trống utf8mb4_unicode_ci; chọn DB đó rồi import schema.sql và seed.sql; chạy migrate.php để ghi/check baseline; tạo cấu hình/tài khoản riêng phù hợp. Setup là cách ưu tiên cho nhóm.

## Trạng thái máy nhóm trưởng sau áp dụng

Database đang dùng: restaurant_chain_scope_v2. Database cũ restaurant_chain giữ nguyên, không DROP. Danh mục và toàn bộ tài khoản hiện có được sao chép, mật khẩu băm/phiên bản/trạng thái giữ nguyên. Năm tài khoản demo chu_chuoi, quan_ly, phuc_vu, bep, kho vẫn dùng 123456. Tài khoản thực tế không nằm trong ZIP; không chia sẻ config/database.php hoặc bản dump chứa hash.

Đăng nhập/phân quyền đã có trong source. Database này cung cấp hợp đồng dữ liệu; chưa biến các màn hình nghiệp vụ mẫu thành chức năng thật. Các trang mẫu thanh toán/lương/tài chính cũ chưa phải chức năng trong phạm vi; nhóm bỏ khỏi phần mô tả nghiệm thu. Không suy từ giao diện có sẵn rằng đã có dữ liệu hoặc đã hoàn tất use case.

## Thứ tự làm tiếp

1. Nhóm trưởng gửi bộ V2, rà các điểm sửa SD-3/6/9/11/12/17/18/27/28/31, cập nhật báo cáo bằng docs/CAP_NHAT_BAO_CAO.md.
2. Tất cả thành viên import và dùng cùng một baseline; đối chiếu ERD/ma trận trước khi thêm cột/bảng.
3. Làm nhóm danh mục trước: chi nhánh/món/nhân viên/định mức, rồi đặt bàn/gọi món/bếp, cuối cùng yêu cầu NVL/kho/báo cáo; phối hợp dữ liệu đầu vào cho Phiếu đề xuất và NCC thay vì mở chức năng chưa ai nhận.
4. Mỗi người làm branch riêng, tạo Pull Request; mọi thay đổi chung hoặc DB được review. Kiểm tra transaction theo QUY_TAC_GIAO_DICH.md, tránh sửa trực tiếp DB cá nhân rồi quên migration.

Chạy lại kiểm thử schema độc lập: `php verification/test_schema.php`; script tạo database tạm và tự xóa sau kiểm thử. Kết quả trong verification/ không chứa tài khoản hay bí mật MySQL. Các kiểm thử đăng nhập 70 tổ hợp trang xác nhận tương thích source hiện có, không thay thế kiểm thử quyền nghiệp vụ sau khi thành viên viết module thật.
