<?php
if(!defined('APP_ENTRY')){http_response_code(404);exit;}
require __DIR__.'/web.php';$trackingRows=[];$trackingSearched=false;
if($_SERVER['REQUEST_METHOD']==='POST' && post_text('action')==='track' && !$reservationError){
    try{
        // Giới hạn đơn giản theo phiên cho cổng tra cứu công khai; không lưu SĐT trong URL.
        $window=$_SESSION['reservation_lookup_window']??['start'=>time(),'count'=>0];
        if(time()-$window['start']>=60)$window=['start'=>time(),'count'=>0];
        $window['count']++;$_SESSION['reservation_lookup_window']=$window;
        if($window['count']>20)throw new \Fbn\Reservations\BookingError('RATE_LIMITED','Bạn tra cứu quá nhanh. Vui lòng thử lại sau một phút.',429);
        $trackingRows=$reservations->theoDoiTrangThai(post_text('soDienThoai'));$trackingSearched=true;
    }catch(\Fbn\Reservations\BookingError $error){$reservationError=$error->getMessage();$reservationFields=$error->fields;http_response_code($error->status);}
}
?>
<div class="customer-page tracking-page" data-reservations-live><div class="customer-nav"><div class="brand brand-dark"><span class="brand-mark">N</span><strong>F&amp;B Nodes</strong></div><a class="btn btn-secondary" href="<?= e(page_url('booking')) ?>">Đặt bàn mới</a></div>
<div class="tracking-wrap"><span class="eyebrow accent">THEO DÕI TRẠNG THÁI ĐẶT BÀN</span><h1 class="title title-lg">Tra cứu đặt bàn của bạn</h1><p class="lead">Nhập số điện thoại đã dùng để đặt bàn để xem trạng thái mới nhất.</p>
<?php reservation_notice($reservationError,null,$reservationFields); ?>
<form class="card res-panel" method="post" action="<?= e(page_url('tracking')) ?>"><?= csrf_input() ?><input type="hidden" name="action" value="track"><label class="field"><span class="field-label">Số điện thoại</span><div class="search-row"><input class="control" type="tel" name="soDienThoai" required maxlength="30" placeholder="Ví dụ 0912345678" value="<?= reservation_value('soDienThoai') ?>"><button class="btn btn-primary" type="submit">Tra cứu</button></div></label></form>
<?php if($trackingRows): ?><p class="muted"><?= count($trackingRows) ?> yêu cầu gần nhất. Gửi lại tra cứu để cập nhật trạng thái.</p><div class="res-list"><?php foreach($trackingRows as $r): ?>
<article class="card res-panel"><div class="section-head"><strong><?= e($r['tenChiNhanh']) ?></strong><span class="badge <?= $r['trangThai']==='DA_HUY'?'badge-danger':'badge-accent' ?>"><?= e(reservation_status($r['trangThai'])) ?></span></div><p>Mã <?= e($r['maDatBan']) ?></p><p>Hạn giữ chỗ <?= e(reservation_time($r['hanGiuCho'])) ?><?= $r['thoiGianKhachDen']?' · Khách đã đến':'' ?></p><p><?= e(reservation_time($r['thoiGianDen'])) ?> · <?= (int)$r['soKhach'] ?> khách · <?= $r['tenBan']?'Bàn '.e($r['tenBan']):'Chưa gán bàn' ?></p></article>
<?php endforeach; ?></div><?php elseif($trackingSearched): ?><div class="empty-lookup"><strong>Không tìm thấy đơn đặt bàn theo số điện thoại đã nhập.</strong><span>Kiểm tra số điện thoại rồi tra cứu lại.</span></div><?php elseif(!$reservationError): ?><div class="empty-lookup"><span class="lookup-icon">⌕</span><strong>Sẵn sàng tra cứu</strong><span>Kết quả sẽ xuất hiện sau khi bạn gửi số điện thoại.</span></div><?php endif; ?>
</div></div>
