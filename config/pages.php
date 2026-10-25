<?php
declare(strict_types=1);

// Danh sách trang được phép mở; không include trực tiếp đường dẫn từ URL.
return [
    'booking' => ['title' => 'Đặt bàn trực tuyến', 'eyebrow' => 'Cổng thông tin khách hàng', 'module' => 'dat-ban', 'owner' => 'Lý Bảo Nghi', 'short' => 'ĐB', 'view' => 'modules/dat-ban/dat-ban.php'],
    'tracking' => ['title' => 'Theo dõi trạng thái đặt bàn', 'eyebrow' => 'Cổng thông tin khách hàng', 'module' => 'dat-ban', 'owner' => 'Lý Bảo Nghi', 'short' => 'TC', 'view' => 'modules/dat-ban/theo-doi.php'],
    'floor' => ['title' => 'Quản lý bàn & đặt bàn', 'eyebrow' => 'Vận hành nhà hàng', 'module' => 'dat-ban', 'owner' => 'Lý Bảo Nghi', 'short' => 'SB', 'view' => 'modules/dat-ban/index.php'],
    'pos' => ['title' => 'Tạo & cập nhật đơn gọi món', 'eyebrow' => 'Vận hành nhà hàng', 'module' => 'goi-mon', 'owner' => 'Trần Thị Như Ý', 'short' => 'PO', 'view' => 'modules/goi-mon/index.php'],
    'checkout' => ['title' => 'Thanh toán', 'eyebrow' => 'Vận hành nhà hàng', 'module' => 'goi-mon', 'owner' => 'Trần Thị Như Ý', 'short' => 'TT', 'view' => 'modules/goi-mon/thanh-toan.php'],
    'kds' => ['title' => 'Tiếp nhận & chế biến', 'eyebrow' => 'Màn hình bếp', 'module' => 'bep', 'owner' => 'Lê Xuân Lộc', 'short' => 'BP', 'view' => 'modules/bep/index.php'],
    'menu' => ['title' => 'Quản lý món ăn', 'eyebrow' => 'Thực đơn toàn chuỗi', 'module' => 'mon-an', 'owner' => 'Trần Như Ý', 'short' => 'MA', 'view' => 'modules/mon-an/index.php'],
    'inventory' => ['title' => 'Quản lý kho & cảnh báo NVL', 'eyebrow' => 'Quản trị nhà hàng', 'module' => 'kho', 'owner' => 'Lương Nguyễn Thành Vinh', 'short' => 'KH', 'view' => 'modules/kho/index.php'],
    'transfer' => ['title' => 'Điều chuyển & nhập NCC', 'eyebrow' => 'Chuỗi cung ứng', 'module' => 'chi-nhanh', 'owner' => 'Phạm Hiếu Hòa', 'short' => 'ĐC', 'view' => 'modules/chi-nhanh/dieu-chuyen.php'],
    'staff' => ['title' => 'Quản lý nhân viên & điều chuyển', 'eyebrow' => 'Quản trị nhân sự', 'module' => 'nhan-su', 'owner' => 'Lê Xuân Lộc', 'short' => 'NS', 'view' => 'modules/nhan-su/index.php'],
    'payroll' => ['title' => 'Chấm công & tính lương', 'eyebrow' => 'Quản trị nhân sự', 'module' => 'nhan-su', 'owner' => '', 'short' => 'CL', 'view' => 'modules/nhan-su/cham-cong.php'],
    'bom' => ['title' => 'Định mức nguyên vật liệu', 'eyebrow' => 'Cấu hình thực đơn', 'module' => 'dinh-muc', 'owner' => 'Lê Xuân Lộc', 'short' => 'DM', 'view' => 'modules/dinh-muc/index.php'],
    'branches' => ['title' => 'Quản lý chi nhánh', 'eyebrow' => 'Điều hành toàn chuỗi', 'module' => 'chi-nhanh', 'owner' => 'Phạm Hiếu Hòa', 'short' => 'CN', 'view' => 'modules/chi-nhanh/index.php'],
    'executive' => ['title' => 'Tổng quan chuỗi', 'eyebrow' => 'Thống kê & báo cáo', 'module' => 'bao-cao', 'owner' => 'Lương Nguyễn Thành Vinh', 'short' => 'BC', 'view' => 'modules/bao-cao/index.php'],
    'accounts' => ['title'=>'Tài khoản và phân quyền','eyebrow'=>'Quản trị truy cập','module'=>'tai-khoan','short'=>'TK','view'=>'modules/tai-khoan/index.php'],
    'password' => ['title'=>'Đổi mật khẩu','eyebrow'=>'Bảo mật tài khoản','module'=>'tai-khoan','short'=>'MK','view'=>'modules/tai-khoan/doi-mat-khau.php'],
];
