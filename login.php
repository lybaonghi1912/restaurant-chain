<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
if (current_user()) { redirect_to('index.php'); }
$next=is_string($_GET['next'] ?? null) ? $_GET['next'] : '';
$pages=require APP_ROOT.'/config/pages.php';
if (!isset($pages[$next]) || in_array($next,['booking','tracking'],true)) { $next=''; }
$error=''; $username='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $username=trim(post_text('username'));$password=post_text('password');
    if (preg_match('/^[A-Za-z0-9._-]{3,50}$/D',$username) && strlen($password)<=256 && attempt_login($username,$password)) {
        redirect_to('index.php'.($next!==''?'?page='.rawurlencode($next):''));
    }
    http_response_code(401);
    $error='Không thể đăng nhập. Kiểm tra thông tin, trạng thái tài khoản hoặc thử lại sau nếu đã nhập sai nhiều lần.';
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Đăng nhập | F&B Nodes</title>
<link rel="icon" href="<?= e(url('assets/images/favicon.svg')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>"></head>
<body class="auth-page"><main class="auth-shell">
<section class="auth-visual"><a class="brand auth-brand" href="<?= e(page_url('booking')) ?>"><span class="brand-mark">N</span><strong>F&B Nodes</strong></a><div><span class="eyebrow">RESTAURANT CHAIN MANAGEMENT</span><h1>Mỗi chi nhánh.<br>Một trải nghiệm.</h1><p>Không gian làm việc chung cho đội ngũ nhà hàng.<br>Đăng nhập để bắt đầu ca làm của bạn.</p></div><span class="auth-caption">F&B Nodes · Hệ thống quản lý chuỗi nhà hàng</span></section>
<section class="auth-form-panel"><div class="auth-form-wrap"><span class="eyebrow accent">DÀNH CHO NHÂN VIÊN</span><h2>Chào mừng trở lại</h2><p class="auth-lead">Sử dụng tài khoản do quản lý cấp để truy cập hệ thống.</p>
<?php if (isset($_GET['logged_out'])): ?><p class="auth-success" role="status">Bạn đã đăng xuất thành công.</p><?php endif; ?>
<?php if ($error): ?><p class="auth-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="auth-form" action="<?= e(url('login.php').($next!==''?'?next='.rawurlencode($next):'')) ?>">
<?= csrf_input() ?>
<label class="field"><span class="field-label">Tên đăng nhập</span><input class="control" name="username" value="<?= e($username) ?>" autocomplete="username" maxlength="50" placeholder="Nhập tên đăng nhập" required autofocus></label>
<label class="field"><span class="field-label">Mật khẩu</span><input class="control" name="password" type="password" autocomplete="current-password" maxlength="256" placeholder="Nhập mật khẩu" required></label>
<button class="btn btn-primary full-button" type="submit">Đăng nhập <span aria-hidden="true">→</span></button>
</form><p class="auth-help">Cần tài khoản hoặc quên mật khẩu? Liên hệ người quản lý của bạn.</p>
<div class="auth-public-links"><a href="<?= e(page_url('booking')) ?>">Đặt bàn cho khách</a><span>·</span><a href="<?= e(page_url('tracking')) ?>">Tra cứu đặt bàn</a></div>
<p class="auth-footnote">Tài khoản được phân quyền theo vai trò và chi nhánh.</p>
</div></section></main></body></html>
