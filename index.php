<?php
declare(strict_types=1);
define('APP_ENTRY', true);
require_once __DIR__.'/includes/bootstrap.php';
$pages=require APP_ROOT.'/config/pages.php';
$authUser=current_user();
$requested=$_GET['page'] ?? ($authUser?home_page():'floor');
$pageKey=is_string($requested)?$requested:'';
$notFound=!isset($pages[$pageKey]);$forbidden=false;
if (!$notFound && !in_array($pageKey,['booking','tracking'],true)) {
    $authUser=require_login($pageKey);
    if (!can_page($pageKey)) { http_response_code(403);$forbidden=true; }
}
$branches=$authUser?active_branches():[];
if ($authUser && isset($_GET['branch'])) {
    $branch=is_string($_GET['branch'])?$_GET['branch']:'';
    if ($authUser['maVaiTro']==='CHU_CHUOI') {
        if ($branch==='all') { unset($_SESSION['branch']); }
        elseif (in_array($branch,array_column($branches,'maChiNhanh'),true)) { $_SESSION['branch']=$branch; }
        else { http_response_code(400);exit('Chi nhánh không hợp lệ.'); }
    } elseif ($branch!==$authUser['maChiNhanh']) { http_response_code(403);$forbidden=true; }
}
if ($notFound) { http_response_code(404);$page=['title'=>'Không tìm thấy trang','eyebrow'=>'F&B Nodes']; }
elseif ($forbidden) { $page=['title'=>'Bạn không có quyền truy cập','eyebrow'=>'Phân quyền hệ thống']; }
else { $page=$pages[$pageKey]; }
$isCustomer=in_array($pageKey,['booking','tracking'],true) || !$authUser;
$moduleContent='';
// Render nội dung trước layout để các POST có thể redirect và đặt HTTP status trước khi gửi HTML.
if (!$notFound && !$forbidden) {
    ob_start();require APP_ROOT.'/'.$page['view'];$moduleContent=ob_get_clean();
}
require APP_ROOT.'/layouts/header.php';
if (!$isCustomer) { require APP_ROOT.'/layouts/sidebar.php'; }
?>
<main class="<?= $isCustomer?'customer-main':'workspace' ?>" id="main-content" tabindex="-1">
<?php if (!$isCustomer) { require APP_ROOT.'/layouts/topbar.php'; } ?>
<?php if ($notFound || $forbidden): ?>
<div class="screen-content"><section class="card forbidden-panel"><span class="eyebrow accent"><?= $forbidden?'403 · KHÔNG ĐỦ QUYỀN':'404 · KHÔNG TÌM THẤY' ?></span><h2 class="title title-md"><?= $forbidden?'Tài khoản của bạn chưa được cấp quyền cho trang này.':'Đường dẫn này không tồn tại.' ?></h2><p><?= $forbidden?'Hãy chọn chức năng trong menu phù hợp với vai trò của bạn hoặc liên hệ người quản lý.':'Kiểm tra đường dẫn hoặc quay lại trang chính.' ?></p><a class="btn btn-primary" href="<?= e($authUser?page_url(home_page()):url('login.php')) ?>">Về trang của tôi</a></section></div>
<?php else: echo $moduleContent; endif; ?>
</main>
<?php require APP_ROOT.'/layouts/footer.php'; ?>
