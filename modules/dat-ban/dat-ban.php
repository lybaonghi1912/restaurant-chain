<?php
if(!defined('APP_ENTRY')){http_response_code(404);exit;}
require __DIR__.'/web.php';
$bookingBranches=$reservations->danhSachChiNhanh();
?>
<div class="booking-layout" data-reservations-live>
<div class="booking-visual"><div class="booking-overlay"></div>
<div class="customer-nav on-image"><div class="brand"><span class="brand-mark light">N</span><strong>F&amp;B Nodes</strong></div><span>Trải nghiệm ẩm thực, trọn vẹn từng khoảnh khắc.</span></div>
<div class="visual-copy"><span class="badge badge-accent">ĐẶT BÀN TẠI F&amp;B NODES</span><h1 class="title title-lg">Một bữa tối đáng nhớ bắt đầu từ đây.</h1><p>Chọn chi nhánh và gửi yêu cầu. Nhân viên sẽ kiểm tra bàn phù hợp trước khi xác nhận.</p><div class="visual-stats"><div><strong><?= count($bookingBranches) ?></strong><span>Chi nhánh hoạt động</span></div><div><strong>Trực tuyến</strong><span>Theo dõi bằng số điện thoại</span></div></div></div></div>
<div class="booking-form-wrap"><div class="mobile-brand brand brand-dark"><span class="brand-mark">N</span><strong>F&amp;B Nodes</strong></div><span class="eyebrow accent">ĐẶT BÀN TRỰC TUYẾN</span><h1 class="title title-lg">Chọn bàn cho dịp đặc biệt</h1><p class="lead">Gửi thông tin đặt bàn. Yêu cầu chờ xác nhận chưa được gán hoặc giữ bàn.</p>
<?php reservation_notice($reservationError,$reservationFlash,$reservationFields); ?>
<?php if(!$bookingBranches): ?><div class="res-notice">Hiện chưa có chi nhánh hoạt động để tiếp nhận yêu cầu.</div><?php else: ?>
<form method="post" action="<?= e(page_url('booking')) ?>" class="res-form">
<?= csrf_input() ?><?= reservation_nonce('web_create') ?><input type="hidden" name="action" value="web_create">
<div class="form-grid">
<label class="field"><span class="field-label">Chi nhánh *</span><select class="control" name="maChiNhanh" required><option value="">Chọn chi nhánh</option><?php foreach($bookingBranches as $branch): ?><option value="<?= e($branch['maChiNhanh']) ?>" <?= post_text('maChiNhanh')===$branch['maChiNhanh']?'selected':'' ?>><?= e($branch['tenChiNhanh']) ?></option><?php endforeach; ?></select></label>
<label class="field"><span class="field-label">Ngày giờ đến *</span><input class="control" type="datetime-local" name="thoiGianDen" required min="<?= date('Y-m-d\TH:i') ?>" value="<?= reservation_value('thoiGianDen') ?>"></label>
<label class="field"><span class="field-label">Số khách *</span><input class="control" type="number" name="soKhach" min="1" max="32767" step="1" required value="<?= reservation_value('soKhach','2') ?>"></label>
<label class="field"><span class="field-label">Họ và tên *</span><input class="control" name="tenKhach" maxlength="100" autocomplete="name" required value="<?= reservation_value('tenKhach') ?>"></label>
<label class="field"><span class="field-label">Số điện thoại *</span><input class="control" type="tel" name="soDienThoai" maxlength="30" autocomplete="tel" placeholder="0912345678" required value="<?= reservation_value('soDienThoai') ?>"><span class="field-hint">10 chữ số, bắt đầu bằng 0. Dùng để theo dõi trạng thái.</span></label>
<label class="field"><span class="field-label">Ghi chú</span><textarea class="control textarea" name="ghiChu" maxlength="500" placeholder="Ghế trẻ em, dị ứng thực phẩm, dịp đặc biệt…"><?= reservation_value('ghiChu') ?></textarea></label>
</div><button class="btn btn-primary full-button" type="submit">Gửi yêu cầu đặt bàn <span>→</span></button></form><?php endif; ?>
<p class="privacy">Theo dõi kết quả tại trang tra cứu bằng số điện thoại đã dùng khi đặt.</p></div></div>
