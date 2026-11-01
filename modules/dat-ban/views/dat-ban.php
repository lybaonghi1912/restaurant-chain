<?php
if(!defined('APP_ENTRY')){http_response_code(404);exit;}
?>
<div class="customer-experience" data-reservations-live>
<?php customer_header('booking'); ?>
<div class="guest-container">
  <nav class="guest-breadcrumb" aria-label="Đường dẫn"><a href="<?= e(url('login.php')) ?>">Trang chủ</a><?= customer_icon('chevron') ?><span aria-current="page">Đặt bàn</span></nav>
  <header class="guest-page-heading"><h1>Đặt bàn</h1><p>Nhân viên sẽ gọi điện theo số bạn cung cấp để xác nhận hoặc thông báo hủy đặt bàn.</p></header>
  <div class="guest-layout">
  <div class="guest-main-column">
  <?php if($sentBooking): ?>
    <section class="guest-success" aria-labelledby="booking-success" role="status">
      <div class="guest-success-symbol"><?= customer_icon('check') ?></div>
      <h2 id="booking-success">Yêu cầu đã được gửi, vui lòng chờ xác nhận</h2><p>Nhân viên sẽ gọi điện theo số bạn cung cấp để xác nhận hoặc thông báo hủy đặt bàn. Bạn có thể theo dõi trạng thái bằng số điện thoại đã dùng khi đặt.</p>
      <div class="guest-success-code"><div><small>MÃ ĐẶT BÀN</small><strong><?= e($sentBooking['maDatBan']) ?></strong></div><button type="button" class="guest-copy-button" data-copy-booking="<?= e($sentBooking['maDatBan']) ?>" aria-label="Sao chép mã đặt bàn"><?= customer_icon('copy') ?></button></div><p class="guest-copy-feedback" data-copy-feedback aria-live="polite"></p>
      <dl class="guest-success-details"><div><dt>Chi nhánh</dt><dd><?= e($sentBranchName) ?></dd></div><div><dt>Trạng thái</dt><dd><span class="guest-status pending">Chờ xác nhận</span></dd></div><div><dt>Ngày giờ đến</dt><dd><?= e(reservation_time($sentBooking['thoiGianDen'])) ?></dd></div><div><dt>Số khách</dt><dd><?= (int)$sentBooking['soKhach'] ?> khách</dd></div></dl>
      <div class="guest-success-actions"><a class="guest-button guest-button-primary" href="<?= e(page_url('tracking')) ?>">Theo dõi trạng thái <?= customer_icon('arrow') ?></a><a class="guest-text-link" href="<?= e(page_url('booking')) ?>">Đặt bàn khác</a></div>
    </section>
  <?php else: ?>
    <div data-guest-error><?php reservation_notice($reservationError,$reservationFlash,[]); ?></div>
    <?php if(!$bookingBranches): ?><section class="guest-empty"><div class="guest-empty-icon"><?= customer_icon('calendar') ?></div><h2>Hiện chưa thể nhận đặt bàn</h2><p>Chưa có chi nhánh hoạt động để tiếp nhận yêu cầu. Vui lòng quay lại sau.</p><a class="guest-text-link" href="<?= e(url('login.php')) ?>">Về trang chủ</a></section>
    <?php else: ?>
    <form method="post" action="<?= e(page_url('booking')) ?>" class="guest-reservation-form" data-guest-form>
      <?= csrf_input() ?><input type="hidden" name="request_key" value="<?= e($createRequestKey) ?>"><input type="hidden" name="action" value="web_create">
      <fieldset class="guest-fieldset"><legend><span>01</span>Thông tin đặt bàn</legend><div class="guest-field-grid">
        <label class="guest-field"><span class="guest-field-label">Chi nhánh<span>*</span></span><select name="maChiNhanh" required<?= customer_invalid('maChiNhanh',$reservationFields) ?>><option value="">Chọn chi nhánh</option><?php foreach($bookingBranches as $branch): ?><option value="<?= e($branch['maChiNhanh']) ?>" <?= ($oldInput['maChiNhanh']??'')===$branch['maChiNhanh']?'selected':'' ?>><?= e($branch['tenChiNhanh']) ?></option><?php endforeach; ?></select><?php customer_field_error('maChiNhanh',$reservationFields); ?></label>
        <label class="guest-field"><span class="guest-field-label">Số khách<span>*</span></span><input type="number" name="soKhach" min="1" max="32767" step="1" required value="<?= reservation_value($oldInput,'soKhach','2') ?>"<?= customer_invalid('soKhach',$reservationFields) ?>><?php customer_field_error('soKhach',$reservationFields); ?></label>
        <label class="guest-field guest-field-full"><span class="guest-field-label">Ngày giờ đến<span>*</span></span><input type="datetime-local" name="thoiGianDen" required step="60" min="<?= e($arrivalMin) ?>" value="<?= reservation_value($oldInput,'thoiGianDen') ?>" data-arrival-hours<?= customer_invalid('thoiGianDen',$reservationFields) ?>><span class="guest-field-hint">Chọn giờ đến từ 08:00 đến trước 22:00.</span><?php customer_field_error('thoiGianDen',$reservationFields); ?></label>
      </div></fieldset>
      <fieldset class="guest-fieldset"><legend><span>02</span>Thông tin liên hệ</legend><div class="guest-field-grid">
        <label class="guest-field"><span class="guest-field-label">Họ và tên<span>*</span></span><input name="tenKhach" maxlength="100" autocomplete="name" placeholder="Nhập họ và tên" required value="<?= reservation_value($oldInput,'tenKhach') ?>"<?= customer_invalid('tenKhach',$reservationFields) ?>><?php customer_field_error('tenKhach',$reservationFields); ?></label>
        <label class="guest-field"><span class="guest-field-label">Số điện thoại<span>*</span></span><input type="tel" inputmode="tel" name="soDienThoai" maxlength="30" autocomplete="tel" placeholder="Ví dụ 0912345678" required value="<?= reservation_value($oldInput,'soDienThoai') ?>"<?= customer_invalid('soDienThoai',$reservationFields) ?>><span class="guest-field-hint">Số để nhân viên gọi xác nhận hoặc thông báo hủy.</span><?php customer_field_error('soDienThoai',$reservationFields); ?></label>
      </div><details class="guest-notes" <?= ($oldInput['ghiChu']??'')!==''||isset($reservationFields['ghiChu'])?'open':'' ?>><summary>Thêm ghi chú <span class="guest-optional-note">(không bắt buộc)</span></summary><label class="guest-field"><span class="guest-field-label">Ghi chú cho nhà hàng</span><textarea name="ghiChu" maxlength="500" placeholder="Ví dụ: cần ghế trẻ em, dị ứng thực phẩm…"<?= customer_invalid('ghiChu',$reservationFields) ?>><?= reservation_value($oldInput,'ghiChu') ?></textarea><?php customer_field_error('ghiChu',$reservationFields); ?></label></details></fieldset>
      <div class="guest-submit-area"><p>Gửi yêu cầu chưa giữ bàn. Bàn được giữ sau khi nhà hàng xác nhận.</p><button class="guest-button guest-button-primary" type="submit" data-loading-label="Đang gửi yêu cầu…">Gửi yêu cầu đặt bàn <?= customer_icon('arrow') ?></button></div>
    </form>
    <?php endif; ?>
  <?php endif; ?>
  </div>
  <aside class="guest-aside" aria-label="Thông tin trước khi đặt bàn"><img class="guest-aside-image" src="<?= e(url('assets/images/welcome-dining.jpg')) ?>" alt="Bàn ăn được chuẩn bị tại nhà hàng" width="2000" height="1368"><div class="guest-aside-content"><h2>Trước khi đặt bàn</h2><ul class="guest-guidelines"><li><?= customer_icon('clock') ?><span><strong>08:00 – trước 22:00</strong><br>Giờ nhận khách hằng ngày.</span></li><li><?= customer_icon('calendar') ?><span>Nhân viên gọi điện khi xác nhận hoặc hủy yêu cầu. Vui lòng cung cấp số điện thoại có thể liên hệ.</span></li><li><?= customer_icon('check') ?><span>Sau xác nhận, giữ chỗ tối đa <strong>15 phút</strong> sau giờ đặt và không quá 22:00.</span></li></ul><div class="guest-aside-note"><p>Lịch 21:55 được giữ đến 22:00. Vui lòng có mặt trước giờ đóng cửa.</p></div></div><a class="guest-aside-link" href="<?= e(page_url('tracking')) ?>">Đã đặt bàn? Theo dõi tại đây <?= customer_icon('arrow') ?></a></aside>
  </div>
</div>
<?php customer_footer(); ?>
</div>
