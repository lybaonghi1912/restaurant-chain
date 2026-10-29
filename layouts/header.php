<?php $reservationLive=in_array($pageKey,['booking','tracking','floor'],true);$pageScript=$reservationLive?'reservations.js':'app.js'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page['title'] . ' | ' . APP_NAME) ?></title>
    <link rel="icon" href="<?= e(url('assets/images/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>?v=<?= filemtime(APP_ROOT . '/assets/css/auth.css') ?>">
    <?php if($reservationLive): ?><link rel="stylesheet" href="<?= e(url('assets/css/reservations.css')) ?>?v=<?= filemtime(APP_ROOT.'/assets/css/reservations.css') ?>"><?php endif; ?>
    <script src="<?= e(url('assets/js/'.$pageScript)) ?>?v=<?= filemtime(APP_ROOT.'/assets/js/'.$pageScript) ?>" defer></script>
</head>
<body data-page="<?= e($pageKey) ?>" data-can-edit="<?= can(['floor'=>'reservation.manage','pos'=>'order.manage','checkout'=>'payment.manage','kds'=>'kitchen.manage','menu'=>'menu.manage','inventory'=>'inventory.manage','transfer'=>'transfer.approve','staff'=>'staff.manage','payroll'=>'payroll.manage','bom'=>'bom.manage','branches'=>'branches.manage','executive'=>'transfer.approve'][$pageKey] ?? 'DENY') ? '1' : '0' ?>">
<a class="skip-link" href="#main-content">Đến nội dung chính</a>
<div class="app <?= $isCustomer ? 'customer-mode' : '' ?>">
