<?php
if(!defined('APP_ENTRY')){http_response_code(404);exit;}
?>
<div class="customer-experience" data-reservations-live>
<?php customer_header('tracking'); ?>
<div class="guest-container">
  <nav class="guest-breadcrumb" aria-label="Đường dẫn"><a href="<?= e(url('login.php')) ?>">Trang chủ</a><?= customer_icon('chevron') ?><span aria-current="page">Theo dõi đặt bàn</span></nav>
  <header class="guest-page-heading"><h1>Theo dõi đặt bàn</h1><p>Nhập số điện thoại đã dùng khi đặt bàn để xem các yêu cầu và trạng thái mới nhất.</p></header>
  <div class="guest-layout">
  <div class="guest-main-column">
    <div data-guest-error><?php reservation_notice($reservationError,null,[]); ?></div>
    <form class="guest-lookup-form" id="guest-lookup" method="post" action="<?= e(page_url('tracking')) ?>" data-guest-form>
      <?= csrf_input() ?><input type="hidden" name="action" value="track">
      <div class="guest-field"><label class="guest-field-label" for="lookup-phone">Số điện thoại<span>*</span></label><div class="guest-lookup-row"><input id="lookup-phone" type="tel" inputmode="tel" name="soDienThoai" required maxlength="30" autocomplete="tel" placeholder="Ví dụ 0912345678" value="<?= reservation_value($oldInput,'soDienThoai') ?>"<?= customer_invalid('soDienThoai',$reservationFields) ?>><button class="guest-button guest-button-primary" type="submit" data-loading-label="Đang tra cứu…"><?= customer_icon('search') ?>Tra cứu</button></div><?php customer_field_error('soDienThoai',$reservationFields); ?></div>
      <p class="guest-lookup-hint">Dùng đúng số điện thoại đã nhập khi gửi yêu cầu đặt bàn.</p><p class="guest-contact-policy"><?= customer_icon('phone') ?><span><strong>Nhân viên sẽ gọi điện</strong> theo số bạn cung cấp để xác nhận hoặc thông báo hủy đặt bàn.</span></p>
    </form>
    <?php if($trackingRows): ?>
    <div class="guest-results-heading"><div><h2><?= count($trackingRows) ?> yêu cầu đặt bàn</h2><p class="guest-results-meta">Cập nhật lúc <?= date('H:i') ?> · Tra cứu lại để làm mới trạng thái</p></div><button class="guest-refresh" form="guest-lookup" type="submit">Cập nhật</button></div>
    <div class="guest-result-list">
    <?php foreach($trackingRows as $r): [$statusClass,$statusLabel,$statusDescription]=customer_tracking_status($r); ?>
      <article class="guest-result">
        <header class="guest-result-header"><h3><?= e($r['tenChiNhanh']) ?></h3><span class="guest-status <?= e($statusClass) ?>"><?= e($statusLabel) ?></span></header>
        <p class="guest-result-code"><span>MÃ ĐẶT BÀN</span><?= e($r['maDatBan']) ?></p>
        <dl><div><dt>Ngày giờ đến</dt><dd><time datetime="<?= e(str_replace(' ','T',$r['thoiGianDen']).'+07:00') ?>"><?= e(reservation_time($r['thoiGianDen'])) ?></time></dd></div><div><dt>Số khách</dt><dd><?= (int)$r['soKhach'] ?> khách</dd></div><div><dt>Bàn</dt><dd><?= e($r['tenBan']?:'Chưa gán bàn') ?></dd></div></dl>
        <p class="guest-result-message"><?= e($statusDescription) ?></p>
        <?php if($r['trangThai']==='DA_XAC_NHAN'&&!$r['thoiGianKhachDen']): ?><p class="guest-result-hold"><?= customer_icon('clock') ?><span>Giữ chỗ đến <strong><?= e(reservation_time($r['hanGiuCho'])) ?></strong>.</span></p><?php elseif($r['thoiGianKhachDen']&&in_array($r['trangThai'],['DA_XAC_NHAN','DANG_PHUC_VU'],true)): ?><p class="guest-result-hold"><?= customer_icon('check') ?><span>Đã ghi nhận khách đến lúc <?= e(reservation_time($r['thoiGianKhachDen'])) ?>.</span></p><?php endif; ?>
      </article>
    <?php endforeach; ?>
    </div>
    <?php elseif($trackingSearched): ?>
    <section class="guest-empty"><div class="guest-empty-icon"><?= customer_icon('search') ?></div><h2>Chưa tìm thấy đặt bàn</h2><p>Không tìm thấy đơn đặt bàn theo số điện thoại đã nhập.<br>Kiểm tra lại số điện thoại rồi tra cứu lần nữa.</p><a class="guest-text-link" href="<?= e(page_url('booking')) ?>">Tạo yêu cầu đặt bàn mới</a></section>
    <?php elseif(!$reservationError): ?>
    <section class="guest-empty"><div class="guest-empty-icon"><?= customer_icon('calendar') ?></div><h2>Thông tin đặt bàn sẽ hiển thị tại đây</h2><p>Bạn có thể xem chi nhánh, ngày giờ đến, bàn được xác nhận và trạng thái của từng yêu cầu.</p></section>
    <?php endif; ?>
  </div>
  <aside class="guest-aside" aria-label="Hướng dẫn trạng thái đặt bàn"><img class="guest-aside-image" src="<?= e(url('assets/images/welcome-dining.jpg')) ?>" alt="Không gian bàn ăn tại nhà hàng" width="2000" height="1368"><div class="guest-aside-content"><h2>Trạng thái đặt bàn</h2><p>Nhà hàng liên hệ qua điện thoại khi xác nhận hoặc hủy yêu cầu.</p><ul class="guest-status-guide"><li><strong>Chờ xác nhận</strong><span>Nhà hàng đang xem yêu cầu của bạn.</span></li><li><strong>Đã xác nhận</strong><span>Bàn đã được giữ theo giờ hẹn.</span></li><li><strong>Đang phục vụ</strong><span>Khách đã được tiếp nhận vào bàn.</span></li><li><strong>Đã hoàn tất / Đã hủy</strong><span>Yêu cầu đã kết thúc hoặc không còn hiệu lực.</span></li></ul><div class="guest-aside-note"><p>Bàn đã xác nhận được giữ tối đa 15 phút sau giờ đặt và không quá 22:00.</p></div></div><a class="guest-aside-link" href="<?= e(page_url('booking')) ?>">Đặt bàn mới <?= customer_icon('arrow') ?></a></aside>
  </div>
</div>
<?php customer_footer(); ?>
</div>
