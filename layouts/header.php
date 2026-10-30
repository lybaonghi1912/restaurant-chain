<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page['title'] . ' | ' . APP_NAME) ?></title>
    <link rel="icon" href="<?= e(url('assets/images/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/auth.css') ?>">
    <?php if (in_array($pageKey, ['pos', 'checkout'], true)): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/goi-mon.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/goi-mon.css') ?>">
    <?php endif; ?>
    <?php if ($pageKey === 'staff'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/staff-transfer.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/staff-transfer.css') ?>">
    <?php endif; ?>
    <?php if ($pageKey === 'inventory'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/inventory-requisition.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/inventory-requisition.css') ?>">
    <?php endif; ?>
    <script src="<?= e(url('assets/js/app.js')) ?>?v=<?= filemtime(APP_ROOT . '/assets/js/app.js') ?>" defer></script>
    <?php if ($pageKey === 'pos'): ?>
    <script src="<?= e(url('assets/js/pos.js')) ?>?v=<?= filemtime(APP_ROOT . '/assets/js/pos.js') ?>" defer></script>
    <?php elseif ($pageKey === 'checkout'): ?>
    <script src="<?= e(url('assets/js/checkout.js')) ?>?v=<?= filemtime(APP_ROOT . '/assets/js/checkout.js') ?>" defer></script>
    <?php endif; ?>
</head>
<body data-page="<?= e($pageKey) ?>" data-can-edit="<?= can(['floor'=>'reservation.manage','pos'=>'order.manage','checkout'=>'payment.manage','kds'=>'kitchen.manage','menu'=>'menu.manage','inventory'=>'inventory.manage','transfer'=>'transfer.approve','staff'=>'staff.manage','payroll'=>'payroll.manage','bom'=>'bom.manage','branches'=>'branches.manage','executive'=>'transfer.approve'][$pageKey] ?? 'DENY') ? '1' : '0' ?>">
<a class="skip-link" href="#main-content">Đến nội dung chính</a>
<div class="app <?= $isCustomer ? 'customer-mode' : '' ?>">
