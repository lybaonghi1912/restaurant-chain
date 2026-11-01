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
require_once __DIR__.'/layouts/customer-shell.php';
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>F&amp;B Nodes | Nhà hàng &amp; Đặt bàn</title>
<link rel="icon" href="<?= e(url('assets/images/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css').'?v='.filemtime(__DIR__.'/assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/customer.css').'?v='.filemtime(__DIR__.'/assets/css/customer.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/welcome.css').'?v='.filemtime(__DIR__.'/assets/css/welcome.css')) ?>">
<script src="<?= e(url('assets/js/customer.js').'?v='.filemtime(__DIR__.'/assets/js/customer.js')) ?>" defer></script>
</head>
<body class="entry-page">
<div class="customer-experience">
<?php customer_header('home'); ?>
<main id="guest-main" class="entry-main">
<section class="entry-hero" aria-labelledby="entry-heading">
  <figure class="entry-photo"><img src="<?= e(url('assets/images/welcome-dining.jpg')) ?>" alt="Không gian nhà hàng với bàn ăn được bài trí trang trọng và hoa tươi" width="2000" height="1368" fetchpriority="high"><figcaption><span>F&amp;B Nodes</span><span>Nhà hàng &amp; Đặt bàn trực tuyến</span></figcaption></figure>
  <div class="entry-content">
    <p class="entry-eyebrow">NHÀ HÀNG &amp; ĐẶT BÀN</p>
    <h1 id="entry-heading">Đặt bàn tại<br><strong>F&amp;B Nodes</strong></h1>
    <p class="entry-intro">Chọn không gian cho cuộc hẹn của bạn. Nhân viên sẽ gọi điện để xác nhận hoặc thông báo hủy đặt bàn theo số điện thoại bạn cung cấp.</p>
    <div class="entry-actions">
      <a class="guest-button guest-button-primary" href="<?= e(page_url('booking')) ?>">Đặt bàn trực tuyến <?= customer_icon('arrow') ?></a>
      <a class="entry-tracking-link" href="<?= e(page_url('tracking')) ?>"><span>Đã đặt bàn? <strong>Theo dõi trạng thái</strong></span><?= customer_icon('chevron') ?></a>
    </div>
    <div class="entry-information"><div><span>GIỜ NHẬN KHÁCH</span><strong>08:00 – trước 22:00</strong></div><div><span>ĐẶT BÀN TRỰC TUYẾN</span><strong>Không cần tài khoản</strong></div></div>
    <details id="staff-login" class="entry-staff" <?= ($error!==''||$next!==''||isset($_GET['logged_out']))?'open':'' ?>>
      <summary><span>Đăng nhập</span><span class="entry-disclosure" aria-hidden="true">+</span></summary>
      <div class="entry-staff-body">
        <p class="entry-staff-intro">Dành cho chủ chuỗi, quản lý và nhân sự nhà hàng.</p>
        <?php if(isset($_GET['logged_out'])): ?><p class="guest-notice" role="status">Bạn đã đăng xuất thành công.</p><?php endif; ?>
        <?php if($error): ?><p class="guest-notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="entry-staff-form" data-guest-form action="<?= e(url('login.php').($next!==''?'?next='.rawurlencode($next):'')) ?>">
          <?= csrf_input() ?>
          <label class="guest-field"><span>Tên đăng nhập</span><input name="username" value="<?= e($username) ?>" autocomplete="username" maxlength="50" placeholder="Nhập tên đăng nhập" required></label>
          <label class="guest-field"><span>Mật khẩu</span><input name="password" type="password" autocomplete="current-password" maxlength="256" placeholder="Nhập mật khẩu" required></label>
          <button class="guest-button guest-button-dark" type="submit" data-loading-label="Đang đăng nhập…">Đăng nhập</button>
        </form>
        <p class="entry-staff-help">Cần hỗ trợ tài khoản? Liên hệ quản lý chi nhánh.</p>
      </div>
    </details>
  </div>
</section>
</main>
<?php customer_footer(); ?>
</div>
</body>
</html>
