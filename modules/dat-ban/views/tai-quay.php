<?php
if(!defined('APP_ENTRY')){http_response_code(404);exit;}
?>
<div class="screen-content res-counter-page">
<a class="res-counter-back" href="<?= e(page_url('floor')) ?>">← Quay lại Quản lý đặt bàn</a>
<div class="res-page-actions"><div><h2 class="title title-md">Tạo đặt bàn tại quầy</h2><p class="muted">Chọn bàn trống và nhập số khách để tiếp nhận ngay.</p></div></div>
<?php reservation_notice($reservationError,$reservationFlash,$reservationFields); ?>
<?php if(!$counterTables): ?>
<section class="card res-panel"><p class="res-notice" role="status">Hiện không có bàn trống</p><a class="btn btn-secondary" href="<?= e(page_url('floor').'&view=counter') ?>">Tải lại danh sách bàn</a></section>
<?php else: ?>
<form method="post" action="<?= e(page_url('floor').'&view=counter') ?>" class="res-counter-layout">
<?= csrf_input() ?><input type="hidden" name="request_key" value="<?= e($counterRequestKey) ?>"><input type="hidden" name="action" value="counter_create">
<section class="card res-panel"><fieldset><legend>1. Chọn bàn tiếp nhận *</legend><p class="muted"><?= count($counterTables) ?> bàn trống · Xem sức chứa và vị trí trước khi chọn.</p><div class="res-counter-options">
<?php foreach($counterTables as $b): ?>
<label class="res-counter-option"><input type="radio" name="maBan" value="<?= e($b['maBan']) ?>" required <?= ($oldInput['maBan']??'')===$b['maBan']?'checked':'' ?>><span><strong><?= e($b['tenBan']) ?></strong><small><?= (int)$b['sucChua'] ?> ghế</small><small>Vị trí: <?= e($b['khuVuc']) ?><?= !empty($b['viTri'])?' · '.e($b['viTri']):'' ?></small><?php if($showBranchName): ?><small><?= e($b['tenChiNhanh']) ?></small><?php endif; ?></span></label>
<?php endforeach; ?>
</div></fieldset></section>
<section class="card res-panel res-counter-fields"><h3 class="title title-md">2. Thông tin tiếp nhận</h3>
<label class="field"><span class="field-label">Số khách *</span><input class="control" type="number" name="soKhach" min="1" max="32767" step="1" required value="<?= reservation_value($oldInput,'soKhach','2') ?>"></label>
<label class="field"><span class="field-label">Tên khách (không bắt buộc)</span><input class="control" name="tenKhach" maxlength="100" value="<?= reservation_value($oldInput,'tenKhach') ?>" autocomplete="name"></label>
<label class="field"><span class="field-label">Số điện thoại (không bắt buộc)</span><input class="control" type="tel" name="soDienThoai" maxlength="30" value="<?= reservation_value($oldInput,'soDienThoai') ?>" autocomplete="tel"></label>
<label class="field"><span class="field-label">Ghi chú (không bắt buộc)</span><textarea class="control" name="ghiChu" maxlength="500"><?= reservation_value($oldInput,'ghiChu') ?></textarea></label>
<p class="field-hint">Tạo đơn sẽ tiếp nhận khách vào bàn đã chọn, với trạng thái “Đang phục vụ”.</p>
<div class="res-counter-buttons"><button class="btn btn-primary" type="submit">Tạo đơn</button><a class="btn btn-secondary" href="<?= e(page_url('floor')) ?>">Quay lại</a></div>
</section></form>
<?php endif; ?>
</div>
