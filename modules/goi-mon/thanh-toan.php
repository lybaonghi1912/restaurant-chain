<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="checkout-layout">
<div class="card bill">
<div class="bill-brand">
<div class="brand brand-dark">
<span class="brand-mark">N</span>
<strong>F&amp;B Nodes</strong>
</div>
<span class="muted">Chi nhánh Thảo Điền</span>
</div>
<div class="receipt-meta">
<div>
<span>BÀN</span>
<strong>A06</strong>
</div>
<div>
<span>THU NGÂN</span>
<strong>Minh N.</strong>
</div>
<div>
<span>THỜI GIAN</span>
<strong>20:42</strong>
</div>
</div>
<div class="receipt-items">
<div>
<span>
<b>2×</b> Gỏi cuốn tôm thịt</span>
<strong>178.000đ</strong>
</div>
<div>
<span>
<b>1×</b> Cá chẽm sốt chanh</span>
<strong>249.000đ</strong>
</div>
<div>
<span>
<b>2×</b> Nước suối</span>
<strong>40.000đ</strong>
</div>
</div>
<div class="receipt-total">
<div>
<span>Tạm tính</span>
<strong>467.000đ</strong>
</div>
<div>
<span>VAT (8%)</span>
<strong>37.360đ</strong>
</div>
<div class="grand">
<span>Tổng thanh toán</span>
<strong>504.360đ</strong>
</div>
</div>
</div>
<div class="payment">
<span class="eyebrow">PHƯƠNG THỨC THANH TOÁN</span>
<h2 class="title title-md">Khách muốn thanh toán thế nào?</h2>
<div class="payment-methods">
<div class="card " data-method="cash">
<span class="payment-icon">₫</span>
<div>
<strong>Tiền mặt</strong>
<span>Nhận tiền trực tiếp</span>
</div>
</div>
<div class="card selected" data-method="qr">
<span class="payment-icon qr-mini">
</span>
<div>
<strong>Mã QR</strong>
<span>Chuyển khoản ngân hàng</span>
</div>
</div>
</div>
<div class="card qr-box">
<span class="badge badge-info">ĐANG CHỜ THANH TOÁN</span>
<div class="fake-qr" aria-label="Mã QR minh họa, không dùng thanh toán">
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
<i class="">
</i>
<i class="">
</i>
<i class="fill">
</i>
</div>
<div class="qr-amount">
<span>Số tiền chính xác</span>
<strong>504.360đ</strong>
</div>
<p>QR minh họa giao diện · Không dùng để chuyển tiền</p>
</div>
<button type="button" class="btn btn-primary verify-payment" data-demo-payment="true">Xác nhận đã nhận tiền</button>
</div>
</div>