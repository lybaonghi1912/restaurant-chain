# Đăng nhập và phân quyền F&B Nodes

> Tài liệu này mô tả quyền mở các trang trong giao diện mẫu hiện tại. Phạm vi nghiệp vụ là 32 sequence V2; các trang thanh toán/lương/tài chính không phải chức năng được giao. Khi viết module thật, đối chiếu actor/phạm vi chi nhánh theo docs/database_v2/RA_SOAT_32_SEQUENCE.md; không suy quyền xử lý nghiệp vụ từ quyền xem trang mẫu.

## Quyền mở trang

| Trang | Chủ chuỗi | Quản lý chi nhánh | Phục vụ | Bếp | Kho |
|---|---|---|---|---|---|
| Đặt bàn và tra cứu của khách | Công khai | Công khai | Công khai | Công khai | Công khai |
| Sơ đồ bàn, gọi món, thanh toán | Xem | Xem | Xem và thao tác | Không | Không |
| Màn hình bếp | Xem | Xem | Không | Xem và thao tác | Không |
| Món ăn, định mức, chi nhánh | Quản lý | Không | Không | Không | Không |
| Kho | Xem | Xem | Không | Không | Quản lý |
| Điều chuyển, nhập nhà cung cấp | Xử lý, duyệt | Không | Không | Không | Xem, tiếp nhận |
| Nhân sự, chấm công và lương | Quản lý | Quản lý chi nhánh mình | Không | Không | Không |
| Báo cáo | Toàn chuỗi hoặc chọn chi nhánh | Chi nhánh mình | Không | Không | Không |
| Tài khoản và phân quyền | Toàn chuỗi | Phục vụ/Bếp/Kho chi nhánh mình | Không | Không | Không |
| Đổi mật khẩu cá nhân | Có | Có | Có | Có | Có |

Quyền mở trang đã kiểm tra tại index.php. Nội dung nghiệp vụ nhà hàng còn là dữ liệu mẫu, chưa có bảng giao dịch hoặc endpoint ghi thật. Chỉ phần tài khoản được lưu thật và giới hạn dữ liệu bằng MySQL. Không coi việc ẩn hoặc vô hiệu hóa nút JavaScript là bảo vệ endpoint.

## Các file dùng chung

- config/database.php: cấu hình riêng máy, loại khỏi Git; setup.php tự tạo.
- config/database.example.php: mẫu cấu hình kết nối, có thể commit.
- includes/database.php: hàm db() trả về một kết nối PDO dùng chung.
- includes/bootstrap.php: nạp cấu hình, hàm, PDO và khởi động session.
- includes/auth.php: đăng nhập, CSRF, kiểm tra quyền và chi nhánh.
- config/permissions.php: quyền từng vai trò; thay đổi phải review.
- config/pages.php: danh sách trang được phép include.
- includes/accounts.php: tạo, cập nhật, khóa, cấp lại mật khẩu có kiểm tra quyền.
- modules/tai-khoan/: giao diện quản lý tài khoản và đổi mật khẩu.

## Tích hợp một bộ xử lý POST

Ví dụ khung cho việc lưu đơn gọi món trong tương lai; chưa phải endpoint đã triển khai:

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

require_permission('order.manage');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}
require_csrf();

// Đọc bản ghi cần cập nhật từ MySQL bằng truy vấn tham số hóa.
// Không tin mã chi nhánh do trình duyệt gửi lên.
// require_branch_access($order['maChiNhanh']);

// Kiểm tra nghiệp vụ và thực hiện giao dịch PDO trước khi thông báo thành công.
```

Các thư mục modules đang bị chặn truy cập HTTP trực tiếp bằng .htaccess. Khi thêm endpoint thật, cần thống nhất một bộ định tuyến xử lý POST được phép truy cập; không tự xóa lớp chặn thư mục rồi bỏ kiểm tra quyền. Có thể dùng index.php xử lý POST trước khi render layout như module tài khoản hiện tại.

## Giới hạn truy vấn theo chi nhánh

```php
[$where, $params] = branch_filter('n.maChiNhanh');
$statement = db()->prepare('SELECT ... FROM ... n WHERE ' . $where);
$statement->execute($params);
```

Quản lý và nhân viên luôn lấy chi nhánh từ hồ sơ MySQL của tài khoản. Chủ chuỗi có thể xem toàn chuỗi hoặc chọn một chi nhánh. Tên cột truyền vào branch_filter là hằng số do lập trình viên viết, không lấy từ người dùng. Khi sửa bản ghi theo ID, luôn đọc bản ghi và gọi require_branch_access trước khi sửa; không chỉ lọc giao diện danh sách.

## Quy tắc quản lý tài khoản

- Không có đăng ký tài khoản công khai hoặc tự chọn vai trò trên trang đăng nhập.
- Quản lý chi nhánh không thể cấp vai trò Chủ chuỗi/Quản lý và không thể sửa tài khoản của chi nhánh khác.
- Không tự khóa hoặc hạ quyền tài khoản đang đăng nhập; không khóa/hạ quyền Chủ chuỗi hoạt động cuối cùng.
- Cập nhật quyền/trạng thái hoặc cấp lại mật khẩu tăng phienBan của tài khoản; các phiên cũ bị từ chối ở yêu cầu tiếp theo.
- Đổi mật khẩu cá nhân cần đúng mật khẩu hiện tại; chỉ giữ phiên đang đổi, thu hồi phiên khác.
- Cấp lại mật khẩu tạo một mật khẩu ngẫu nhiên và chỉ hiển thị trong thông báo sau thao tác. Chuyển riêng cho nhân viên; không commit mật khẩu vào repository.

## Database và kiểm thử

Schema chung V1 đã có đầy đủ nhóm dữ liệu theo phạm vi báo cáo. Giữ một tài khoản đăng nhập trong TaiKhoan, liên kết NhanVien và VaiTro. Chủ chuỗi không gắn chi nhánh; các vai trò còn lại bắt buộc gắn một ChiNhanh.

Bản V1 gồm 51 bảng vật lý và 5 view, đã mở rộng NhanVien/ChiNhanh. Xem docs/01_CHOT_DATABASE_V1.md và docs/05_TU_DIEN_DU_LIEU.md. Mọi thay đổi tiếp theo dùng migration mới, không sửa file 001/002 đã áp dụng.

Đã kiểm tra trên localhost: đăng nhập 5 vai trò, 70 tổ hợp quyền trang, truy cập module trực tiếp, CSRF, tài khoản khác chi nhánh, giả mạo vai trò, khóa tài khoản thu hồi phiên, đổi mật khẩu thu hồi phiên khác, hạn chế đăng nhập sai và đăng xuất bằng POST. Tài khoản kiểm thử được tạo riêng rồi xóa sau kiểm thử.
