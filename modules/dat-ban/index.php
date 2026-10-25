<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="screen-content">
<div class="actionbar">
<div class="search-wide">
<span class="search-symbol">⌕</span>
<input class="control " type="text" placeholder="Tìm tên khách, số điện thoại..."/>
</div>
<div class="legend">
<span>
<i class="legend-dot empty">
</i>Trống 6</span>
<span>
<i class="legend-dot reserved">
</i>Đã đặt 3</span>
<span>
<i class="legend-dot serving">
</i>Đang phục vụ 3</span>
</div>
<button type="button" class="btn btn-primary " data-modal="Tạo đặt bàn tại quầy">+ Tạo đặt bàn</button>
</div>
<div class="floor-layout">
<div class="floor-main">
<div class="section-head">
<div>
<h2 class="title title-md">Sơ đồ khu vực trong nhà</h2>
<span class="muted">12 bàn · 54 chỗ ngồi</span>
</div>
<div class="segmented">
<button type="button" class="btn btn-primary " data-area="Trong nhà">Trong nhà</button>
<button type="button" class="btn btn-ghost " data-area="Ngoài trời">Ngoài trời</button>
</div>
</div>
<div class="table-grid">
<div class="card table-card table-empty">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A01</span>
<strong>Trống</strong>
<small>4 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-serving">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A02</span>
<strong>Đang phục vụ</strong>
<small>2 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-reserved">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A03</span>
<strong>Đã đặt · 18:30</strong>
<small>4 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-empty">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A04</span>
<strong>Trống</strong>
<small>6 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-serving">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A05</span>
<strong>Đang phục vụ</strong>
<small>4 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-serving">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A06</span>
<strong>Đang phục vụ</strong>
<small>4 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-reserved">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A07</span>
<strong>Đã đặt · 19:00</strong>
<small>6 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-empty">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>A08</span>
<strong>Trống</strong>
<small>2 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-empty">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>B01</span>
<strong>Trống</strong>
<small>4 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-reserved">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>B02</span>
<strong>Đã đặt · 19:30</strong>
<small>8 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-empty">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>B03</span>
<strong>Trống</strong>
<small>4 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
<div class="card table-card table-empty">
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
<div class="table-surface">
<span>B04</span>
<strong>Trống</strong>
<small>6 ghế</small>
</div>
<div class="table-chair-row">
<i>
</i>
<i>
</i>
</div>
</div>
</div>
</div>
<div class="card request-panel">
<div class="section-head">
<div>
<h3 class="title title-sm">Yêu cầu từ web</h3>
<span class="muted">Cần xử lý trong 15 phút</span>
</div>
<span class="badge badge-danger">2 mới</span>
</div>
<div class="request">
<div class="request-time">
<strong>18:30</strong>
<span>Hôm nay</span>
</div>
<div class="request-info">
<strong>Nguyễn Minh Anh</strong>
<span>4 khách · 0903 221 884</span>
<small>Ưu tiên bàn cạnh cửa sổ</small>
</div>
<div class="request-actions">
<button type="button" class="btn btn-primary compact" data-confirm-reservation="Nguyễn Minh Anh">Xác nhận</button>
<button type="button" class="btn btn-ghost compact danger-text" data-modal="Hủy đặt bàn · Nguyễn Minh Anh">Hủy</button>
</div>
</div>
<div class="request">
<div class="request-time">
<strong>19:00</strong>
<span>Hôm nay</span>
</div>
<div class="request-info">
<strong>Trần Hải Yến</strong>
<span>6 khách · 0938 450 112</span>
<small>Sinh nhật · Cần bánh</small>
</div>
<div class="request-actions">
<button type="button" class="btn btn-primary compact" data-confirm-reservation="Trần Hải Yến">Xác nhận</button>
<button type="button" class="btn btn-ghost compact danger-text" data-modal="Hủy đặt bàn · Trần Hải Yến">Hủy</button>
</div>
</div>
<button type="button" class="btn btn-secondary full-button">Xem tất cả 5 yêu cầu</button>
</div>
</div>
</div>