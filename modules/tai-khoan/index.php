<?php
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_permission('accounts.view');
require_once APP_ROOT.'/includes/accounts.php';
$notice=$_SESSION['account_notice'] ?? null;unset($_SESSION['account_notice']);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    [$ok,$message]=save_account(post_text('action'),[
        'username'=>post_text('username'),'name'=>post_text('name'),'role'=>post_text('role'),
        'branch'=>post_text('branch'),'status'=>post_text('status'),'password'=>post_text('password'),
    ]);
    $_SESSION['account_notice']=[$ok,$message];redirect_to('index.php?page=accounts');
}
$edit=is_string($_GET['edit'] ?? null)?$_GET['edit']:'';
$editing=$edit!==''?find_user($edit):null;
if ($edit!=='' && (!$editing || !can_manage_account($editing))) { http_response_code(403);echo '<section class="screen-content"><h2>Không thể quản lý tài khoản này.</h2></section>';return; }
[$scope,$params]=branch_filter('n.maChiNhanh');
$statement=db()->prepare('SELECT t.tenDangNhap,t.trangThai,n.hoTen,n.maVaiTro,n.maChiNhanh,v.tenVaiTro,c.tenChiNhanh
    FROM TaiKhoan t JOIN NhanVien n ON n.maNhanVien=t.maNhanVien JOIN VaiTro v ON v.maVaiTro=n.maVaiTro
    LEFT JOIN ChiNhanh c ON c.maChiNhanh=n.maChiNhanh WHERE '.$scope.' ORDER BY t.tenDangNhap');
$statement->execute($params);$accounts=$statement->fetchAll();$roles=assignable_roles();
?>
<div class="screen-content">
<p class="account-banner">Tài khoản và quyền truy cập ở trang này được lưu thật trong MySQL. Quản lý chi nhánh chỉ được tạo và quản lý nhân viên Phục vụ, Bếp, Kho của chi nhánh mình.</p>
<?php if ($notice): ?><div class="account-message <?= $notice[0]?'success':'error' ?>" role="status"><?= e($notice[1]) ?></div><?php endif; ?>
<section class="card account-card"><h2 class="title title-md"><?= $editing?'Chỉnh sửa tài khoản':'Tạo tài khoản nội bộ' ?></h2><p class="account-banner">Không có đăng ký công khai. Mỗi tài khoản gắn với một nhân viên và vai trò.</p>
<form method="post" class="account-form" action="<?= e(page_url('accounts')) ?>">
<?= csrf_input() ?><input type="hidden" name="action" value="<?= $editing?'update':'create' ?>">
<label class="field"><span class="field-label">Tên đăng nhập</span><input class="control" name="username" value="<?= e($editing['tenDangNhap'] ?? '') ?>" pattern="[A-Za-z0-9._\-]{3,50}" maxlength="50" required <?= $editing?'readonly':'' ?>></label>
<label class="field"><span class="field-label">Họ tên</span><input class="control" name="name" maxlength="100" value="<?= e($editing['hoTen'] ?? '') ?>" required></label>
<label class="field"><span class="field-label">Vai trò</span><select class="control" name="role" required><?php foreach ($roles as $role): ?><option value="<?= e($role['maVaiTro']) ?>" <?= ($editing['maVaiTro'] ?? 'PHUC_VU')===$role['maVaiTro']?'selected':'' ?>><?= e($role['tenVaiTro']) ?></option><?php endforeach; ?></select></label>
<label class="field"><span class="field-label">Chi nhánh (Chủ chuỗi không gắn chi nhánh)</span><?php if ($authUser['maVaiTro']==='CHU_CHUOI'): ?><select class="control" name="branch"><option value="">Toàn chuỗi — chỉ dành cho Chủ chuỗi</option><?php foreach ($branches as $branch): ?><option value="<?= e($branch['maChiNhanh']) ?>" <?= ($editing['maChiNhanh'] ?? current_branch_id() ?? 'CN01')===$branch['maChiNhanh']?'selected':'' ?>><?= e($branch['tenChiNhanh']) ?></option><?php endforeach; ?></select><?php else: ?><input class="control" value="<?= e($authUser['tenChiNhanh']) ?>" readonly><?php endif; ?></label>
<label class="field"><span class="field-label">Trạng thái</span><select class="control" name="status"><option value="HOAT_DONG">Hoạt động</option><option value="KHOA" <?= ($editing['trangThaiTaiKhoan'] ?? '')==='KHOA'?'selected':'' ?>>Khóa</option></select></label>
<?php if (!$editing): ?><label class="field"><span class="field-label">Mật khẩu ban đầu (ít nhất 12 ký tự)</span><input class="control" name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required></label><?php endif; ?>
<div class="account-form-actions"><button type="submit" class="btn btn-primary"><?= $editing?'Lưu phân quyền':'Tạo tài khoản' ?></button><?php if ($editing): ?> <a class="btn btn-secondary" href="<?= e(page_url('accounts')) ?>">Hủy chỉnh sửa</a><?php endif; ?></div>
</form></section>
<section class="card data-card account-table"><div class="data-toolbar"><h2 class="title title-sm">Danh sách tài khoản · <?= count($accounts) ?></h2></div><div class="table-scroll"><table class="data-table"><thead><tr><th>Tên đăng nhập</th><th>Nhân viên</th><th>Vai trò</th><th>Chi nhánh</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach ($accounts as $account): ?><tr><td><?= e($account['tenDangNhap']) ?></td><td><?= e($account['hoTen']) ?></td><td><?= e($account['tenVaiTro']) ?></td><td><?= e($account['tenChiNhanh'] ?? 'Toàn chuỗi') ?></td><td><span class="badge badge-<?= $account['trangThai']==='HOAT_DONG'?'success':'danger' ?>"><?= $account['trangThai']==='HOAT_DONG'?'Hoạt động':'Khóa' ?></span></td><td><?php if (can_manage_account($account)): ?><div class="management-actions"><a class="btn btn-secondary" href="<?= e(page_url('accounts').'&edit='.rawurlencode($account['tenDangNhap'])) ?>">Sửa quyền</a><?php if ($account['tenDangNhap']!==$authUser['tenDangNhap']): ?><form method="post" action="<?= e(page_url('accounts')) ?>"><?= csrf_input() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="username" value="<?= e($account['tenDangNhap']) ?>"><button class="btn btn-secondary" type="submit">Cấp lại mật khẩu</button></form><?php endif; ?></div><?php else: ?><span class="permission-tag">Ngoài quyền quản lý</span><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
</div>
