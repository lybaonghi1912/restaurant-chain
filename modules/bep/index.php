<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="kds-screen">
<div class="kds-tabs">
<div>
<button type="button" class="btn btn-primary " data-kds-filter="Tất cả">Tất cả <b>12</b>
</button>
<button type="button" class="btn btn-ghost " data-kds-filter="Chờ tiếp nhận">Chờ tiếp nhận <b>4</b>
</button>
<button type="button" class="btn btn-ghost " data-kds-filter="Đang chế biến">Đang chế biến <b>6</b>
</button>
<button type="button" class="btn btn-ghost " data-kds-filter="Chờ trả món">Chờ trả món <b>2</b>
</button>
</div>
<span class="badge badge-success">Kết nối ổn định</span>
</div>
<div class="ticket-grid">
<div class="card ticket ticket-danger">
<div class="ticket-head">
<div>
<span class="ticket-number">#42</span>
<div>
<h2 class="title title-md">Bàn A06</h2>
<span>Tại chỗ · Gửi lúc 12:48</span>
</div>
</div>
<div class="timer timer-danger">
<span>ĐÃ CHỜ</span>
<strong>04:32</strong>
</div>
</div>
<div class="ticket-status">
<span class="badge badge-warning">Chờ tiếp nhận</span>
</div>
<div class="dish-list">
<div>
<span class="dish-qty">2×</span>
<div>
<strong>Gỏi cuốn tôm thịt</strong>
<small>Không hành · Ít cay</small>
</div>
<i class="">
</i>
</div>
<div>
<span class="dish-qty">1×</span>
<div>
<strong>Cá chẽm sốt chanh</strong>
</div>
<i class="">
</i>
</div>
<div>
<span class="dish-qty">1×</span>
<div>
<strong>Cơm chiên hải sản</strong>
</div>
<i class="">
</i>
</div>
</div>
<div class="ticket-actions">
<button type="button" class="btn btn-danger ">Báo thiếu NVL</button>
<button type="button" class="btn btn-primary " data-ticket="42">Tiếp nhận đơn</button>
</div>
</div>
<div class="card ticket ticket-warning">
<div class="ticket-head">
<div>
<span class="ticket-number">#43</span>
<div>
<h2 class="title title-md">Mang về</h2>
<span>#TK-031 · Gửi lúc 12:51</span>
</div>
</div>
<div class="timer timer-warning">
<span>ĐÃ CHỜ</span>
<strong>01:20</strong>
</div>
</div>
<div class="ticket-status">
<span class="badge badge-warning">Chờ tiếp nhận</span>
</div>
<div class="dish-list">
<div>
<span class="dish-qty">1×</span>
<div>
<strong>Bò lúc lắc</strong>
</div>
<i class="">
</i>
</div>
<div>
<span class="dish-qty">2×</span>
<div>
<strong>Canh chua cá</strong>
</div>
<i class="">
</i>
</div>
</div>
<div class="ticket-actions">
<button type="button" class="btn btn-danger ">Báo thiếu NVL</button>
<button type="button" class="btn btn-primary " data-ticket="43">Tiếp nhận đơn</button>
</div>
</div>
<div class="card ticket ticket-success">
<div class="ticket-head">
<div>
<span class="ticket-number">#44</span>
<div>
<h2 class="title title-md">Bàn B02</h2>
<span>Tại chỗ · Gửi lúc 12:52</span>
</div>
</div>
<div class="timer timer-success">
<span>ĐÃ CHỜ</span>
<strong>00:48</strong>
</div>
</div>
<div class="ticket-status">
<span class="badge badge-success">Hoàn thành · Chờ trả món</span>
</div>
<div class="dish-list">
<div>
<span class="dish-qty">2×</span>
<div>
<strong>Bò nướng lá lốt</strong>
</div>
<i class="check">✓</i>
</div>
<div>
<span class="dish-qty">1×</span>
<div>
<strong>Lẩu nấm đặc biệt</strong>
</div>
<i class="check">✓</i>
</div>
</div>
<div class="ticket-actions">
<button type="button" class="btn btn-danger ">Báo thiếu NVL</button>
<button type="button" class="btn btn-primary " data-ticket="44">Tiếp nhận đơn</button>
</div>
</div>
</div>
</div>