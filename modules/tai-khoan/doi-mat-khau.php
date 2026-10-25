<?php
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_login();
$success=$_SESSION['password_changed'] ?? false;unset($_SESSION['password_changed']);$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $old=post_text('current_password');$new=post_text('new_password');$confirm=post_text('confirm_password');
    $record=find_user($authUser['tenDangNhap']);
    if (strlen($old)>256 || !password_verify($old,$record['matKhau'])) { $error='Mật khẩu hiện tại chưa đúng.'; }
    elseif (mb_strlen($new,'UTF-8')<12 || strlen($new)>128) { $error='Mật khẩu mới cần ít nhất 12 ký tự, tối đa 128 byte.'; }
    elseif ($new!==$confirm) { $error='Mật khẩu xác nhận chưa khớp.'; }
    elseif (password_verify($new,$record['matKhau'])) { $error='Mật khẩu mới phải khác mật khẩu hiện tại.'; }
    else {
        db()->prepare('UPDATE TaiKhoan SET matKhau=?,phienBan=phienBan+1 WHERE tenDangNhap=?')->execute([password_hash($new,PASSWORD_ARGON2ID),$authUser['tenDangNhap']]);
        $_SESSION['auth_version']=(int)$record['phienBan']+1;$_SESSION['csrf']=bin2hex(random_bytes(32));session_regenerate_id(true);
        $_SESSION['password_changed']=true;redirect_to('index.php?page=password');
    }
}
?>
<div class="screen-content"><section class="card account-card">
<h2 class="title title-md">Đổi mật khẩu</h2><p class="account-banner">Tài khoản <?= e($authUser['tenDangNhap']) ?>. Đổi mật khẩu sẽ thu hồi các phiên đăng nhập khác của tài khoản.</p>
<?php if ($success): ?><p class="account-message success" role="status">Đã đổi mật khẩu thành công.</p><?php endif; ?>
<?php if ($error): ?><p class="account-message error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="password-form" action="<?= e(page_url('password')) ?>"><?= csrf_input() ?>
<label class="field"><span class="field-label">Mật khẩu hiện tại</span><input class="control" type="password" name="current_password" maxlength="256" autocomplete="current-password" required></label>
<label class="field"><span class="field-label">Mật khẩu mới</span><input class="control" type="password" name="new_password" minlength="12" maxlength="128" autocomplete="new-password" required></label>
<label class="field"><span class="field-label">Xác nhận mật khẩu mới</span><input class="control" type="password" name="confirm_password" minlength="12" maxlength="128" autocomplete="new-password" required></label>
<div><button class="btn btn-primary" type="submit">Lưu mật khẩu</button></div>
</form></section></div>
