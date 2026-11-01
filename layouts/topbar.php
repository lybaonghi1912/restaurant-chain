<header class="topbar">
<button type="button" class="btn btn-ghost menu-toggle" data-menu-open aria-controls="sidebar" aria-expanded="false" aria-label="Mở menu">☰</button>
<div><span class="eyebrow"><?= e($page['eyebrow']) ?></span><h1 class="title title-lg"><?= e($page['title']) ?></h1></div>
<div class="top-actions">
<?php if ($authUser['maVaiTro']==='CHU_CHUOI'): ?>
<form method="get" action="<?= e(url('index.php')) ?>" class="branch-select"><input type="hidden" name="page" value="<?= e($pageKey) ?>"><label><span class="eyebrow">Phạm vi</span><select class="control" id="branch-select" name="branch" aria-label="Phạm vi chi nhánh"><option value="all">Toàn chuỗi</option><?php foreach ($branches as $branch): ?><option value="<?= e($branch['maChiNhanh']) ?>" <?= current_branch_id()===$branch['maChiNhanh']?'selected':'' ?>><?= e($branch['tenChiNhanh']) ?></option><?php endforeach; ?></select></label><noscript><button type="submit" class="btn btn-secondary">Xem</button></noscript></form>
<?php else: ?><div class="branch-select"><span>Chi nhánh</span><strong class="branch-static"><?= e($authUser['tenChiNhanh']) ?></strong></div><?php endif; ?>
<div class="top-avatar" aria-label="<?= e($authUser['hoTen']) ?>"><?= e(initials($authUser['hoTen'])) ?></div>
<form class="auth-logout" method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_input() ?><button type="submit" class="btn btn-secondary">Đăng xuất</button></form>
</div></header>
