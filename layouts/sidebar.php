<?php
$navGroups=[
    'Khách hàng'=>['booking','tracking'],
    'Vận hành'=>['floor','pos','checkout','kds'],
    'Quản trị'=>['menu','inventory','transfer','staff','payroll','bom','branches','executive'],
    'Tài khoản'=>['accounts','password'],
];
$navLabels=['booking'=>'Đặt bàn trực tuyến','tracking'=>'Tra cứu đặt bàn','floor'=>'Sơ đồ bàn','pos'=>'Gọi món · POS','checkout'=>'Thanh toán','kds'=>'Màn hình bếp','menu'=>'Quản lý món ăn','inventory'=>'Kho & cảnh báo','transfer'=>'Điều chuyển kho','staff'=>'Nhân sự','payroll'=>'Chấm công & lương','bom'=>'Định mức NVL','branches'=>'Chi nhánh','executive'=>'Thống kê báo cáo','accounts'=>'Tài khoản & phân quyền','password'=>'Đổi mật khẩu'];
?>
<aside class="sidebar" id="sidebar" aria-label="Điều hướng chính">
<div class="sidebar-brand"><span class="brand-mark light">N</span><div><strong>F&B Nodes</strong><span>Restaurant OS</span></div><button type="button" class="btn btn-ghost mobile-close" data-menu-close aria-label="Đóng menu">×</button></div>
<nav aria-label="Các module"><?php foreach ($navGroups as $label=>$keys): $visible=array_values(array_filter($keys,'can_page'));if (!$visible) { continue; } ?>
<div class="nav-group"><span class="nav-label"><?= e($label) ?></span><?php foreach ($visible as $key): $item=$pages[$key]; ?>
<a class="btn btn-ghost nav-item <?= $pageKey===$key?'active':'' ?>" href="<?= e(page_url($key)) ?>" <?= $pageKey===$key?'aria-current="page"':'' ?>><span class="nav-icon" aria-hidden="true"><?= e($item['short']) ?></span><span><?= e($navLabels[$key]) ?></span></a>
<?php endforeach; ?></div><?php endforeach; ?></nav>
<div class="sidebar-footer"><div class="avatar"><?= e(initials($authUser['hoTen'])) ?></div><div><strong><?= e($authUser['hoTen']) ?></strong><span><?= e($authUser['tenVaiTro']) ?></span><a href="<?= e(page_url('password')) ?>">Đổi mật khẩu</a></div><form class="auth-logout" method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_input() ?><button type="submit" class="btn btn-ghost" aria-label="Đăng xuất">Thoát</button></form></div>
</aside><button type="button" class="sidebar-overlay" data-menu-close aria-label="Đóng menu"></button>
