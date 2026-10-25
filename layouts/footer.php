</div>
<?php if ($isCustomer): ?>
<div class="customer-demo-label">Bản giao diện · Dữ liệu minh họa</div>
<div class="customer-switch"><a class="btn btn-secondary" href="<?= e(page_url($pageKey === 'booking' ? 'tracking' : 'booking')) ?>"><?= $pageKey === 'booking' ? 'Tra cứu đặt bàn' : 'Đặt bàn mới' ?></a><a class="btn btn-primary" href="<?= e($authUser ? page_url(home_page()) : url('login.php')) ?>"><?= $authUser ? 'Trang nội bộ' : 'Đăng nhập nhân viên' ?></a></div>
<?php endif; ?>
<div id="modal-root"></div><div class="toast-stack" id="toast-stack" role="status" aria-live="polite"></div>
<?php require APP_ROOT . '/includes/modal-templates.php'; ?>
</body>
</html>
