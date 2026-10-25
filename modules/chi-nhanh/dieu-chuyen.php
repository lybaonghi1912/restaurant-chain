<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="screen-content">
<div class="transfer-head">
<div class="search-wide">
<input class="control " type="text" placeholder="Tìm nguyên vật liệu để so sánh..."/>
</div>
<button type="button" class="btn btn-primary " data-modal="Tạo yêu cầu điều chuyển / nhập NCC">+ Tạo yêu cầu mới</button>
</div>
<div class="card comparison">
<div class="comparison-title">
<div>
<span class="badge badge-warning">SẮP HẾT</span>
<h2 class="title title-md">Thăn bò Úc</h2>
<span class="muted">NVL-001 · Đơn vị: kg</span>
</div>
<div class="need">
<span>Nhu cầu 3 ngày tới</span>
<strong>18,0 kg</strong>
</div>
</div>
<div class="branch-stock">
<div class="card stock-card">
<div>
<strong>Thảo Điền</strong>
<span>Bạn đang ở đây</span>
</div>
<div class="stock-number">
<span>Tồn hiện tại</span>
<strong>8,5 kg</strong>
</div>
<span class="badge badge-warning">Thiếu 9,5 kg</span>
<small>Lô gần nhất hết hạn: 02/07/2025</small>
</div>
<div class="card stock-card">
<div>
<strong>Quận 1</strong>
<span>Cách 5,2 km</span>
</div>
<div class="stock-number">
<span>Tồn hiện tại</span>
<strong>32,8 kg</strong>
</div>
<span class="badge badge-success">Có thể chuyển 14 kg</span>
<small>Lô gần nhất hết hạn: 02/07/2025</small>
</div>
<div class="card stock-card">
<div>
<strong>Phú Mỹ Hưng</strong>
<span>Cách 12,7 km</span>
</div>
<div class="stock-number">
<span>Tồn hiện tại</span>
<strong>18,2 kg</strong>
</div>
<span class="badge badge-success">Có thể chuyển 4 kg</span>
<small>Lô gần nhất hết hạn: 02/07/2025</small>
</div>
</div>
</div>
<div class="card data-card">
<div class="section-head">
<div>
<h3 class="title title-sm">Yêu cầu gần đây</h3>
<span class="muted">Theo dõi điều chuyển và đơn nhập NCC</span>
</div>
<select class="control">
<option>Tất cả loại yêu cầu</option>
</select>
</div>
<div class="table-scroll">
<table class="data-table">
<thead>
<tr>
<th>Mã phiếu</th>
<th>Loại</th>
<th>Từ / Nhà cung cấp</th>
<th>Đến</th>
<th>Ngày tạo</th>
<th>Trạng thái</th>
</tr>
</thead>
<tbody>
<tr>
<td>#DC-0284</td>
<td>Điều chuyển</td>
<td>Quận 1</td>
<td>Thảo Điền</td>
<td>25/06 · 10:24</td>
<td>
<span class="badge badge-warning">Chờ duyệt</span>
</td>
</tr>
<tr>
<td>#PO-0188</td>
<td>Nhập NCC</td>
<td>Fresh Foods Co.</td>
<td>Phú Mỹ Hưng</td>
<td>25/06 · 09:12</td>
<td>
<span class="badge badge-info">Chờ nhập hàng</span>
</td>
</tr>
<tr>
<td>#DC-0282</td>
<td>Điều chuyển</td>
<td>Phú Mỹ Hưng</td>
<td>Quận 1</td>
<td>24/06 · 15:48</td>
<td>
<span class="badge badge-success">Hoàn tất</span>
</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>