<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="customer-page tracking-page">
<div class="customer-nav">
<div class="brand brand-dark">
<span class="brand-mark">N</span>
<strong>F&amp;B Nodes</strong>
</div>
<span class="badge badge-success">Hỗ trợ 24/7</span>
</div>
<div class="tracking-wrap">
<span class="eyebrow accent">RESERVATION LOOKUP</span>
<h1 class="title title-lg">Tra cứu đặt bàn của bạn</h1>
<p class="lead">Nhập số điện thoại đã dùng để đặt bàn. Chúng tôi sẽ hiển thị các yêu cầu đang hoạt động.</p>
<div class="card search-card">
<label class="field">
<span class="field-label">Số điện thoại</span>
<div class="search-row">
<input class="control " type="text" placeholder="Ví dụ: 0903 123 456" value=""/>
<button type="button" class="btn btn-primary " data-search-booking="true">Tìm đặt bàn</button>
</div>
</label>
</div>
<div class="empty-lookup">
<span class="lookup-icon">⌕</span>
<strong>Sẵn sàng tra cứu</strong>
<span>Kết quả đặt bàn sẽ xuất hiện tại đây.</span>
</div>
</div>
</div>