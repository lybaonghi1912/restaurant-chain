<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="screen-content">
<div class="stat-row">
<div class="card stat">
<div class="stat-top">
<span class="muted">Tổng mặt hàng</span>
<span class="stat-dot dot-accent">
</span>
</div>
<div class="stat-value">248</div>
<span class="text-success">+12 tháng này</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Sắp hết hàng</span>
<span class="stat-dot dot-warning">
</span>
</div>
<div class="stat-value">14</div>
<span class="text-success">Cần nhập thêm</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Gần hết hạn</span>
<span class="stat-dot dot-danger">
</span>
</div>
<div class="stat-value">8</div>
<span class="text-danger">Trong 3 ngày</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Giá trị tồn kho</span>
<span class="stat-dot dot-info">
</span>
</div>
<div class="stat-value">284,6 tr</div>
<span class="text-success">+3,2% so tháng trước</span>
</div>
</div>
<div class="card data-card">
<div class="data-toolbar">
<div class="shift-tabs">
<button type="button" class="btn btn-primary " data-shift="Đầu ca">Đầu ca</button>
<button type="button" class="btn btn-ghost " data-shift="Cuối ca">Cuối ca</button>
<button type="button" class="btn btn-ghost " data-shift="Cuối ngày">Cuối ngày</button>
</div>
<div class="toolbar-actions">
<input class="control " type="text" placeholder="Tìm mã, tên NVL..."/>
<select class="control">
<option>Tất cả trạng thái</option>
</select>
<button type="button" class="btn btn-primary " data-modal="Lập phiếu đề xuất nhập NVL">+ Tạo đề xuất</button>
</div>
</div>
<div class="table-scroll">
<table class="data-table">
<thead>
<tr>
<th>Mã NVL</th>
<th>Nguyên vật liệu</th>
<th>Chi nhánh</th>
<th>Tồn kho</th>
<th>Đơn vị</th>
<th>Hạn sử dụng</th>
<th>Trạng thái</th>
<th>
</th>
</tr>
</thead>
<tbody>
<tr>
<td>NVL-001</td>
<td>Thăn bò Úc</td>
<td>Thảo Điền</td>
<td>8,5</td>
<td>kg</td>
<td>28/06/2025</td>
<td>
<span class="badge badge-warning">Sắp hết</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết nguyên vật liệu · Thăn bò Úc">•••</button>
</td>
</tr>
<tr>
<td>NVL-014</td>
<td>Cá chẽm phi lê</td>
<td>Thảo Điền</td>
<td>4,2</td>
<td>kg</td>
<td>26/06/2025</td>
<td>
<span class="badge badge-warning">Gần hết hạn</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết nguyên vật liệu · Cá chẽm phi lê">•••</button>
</td>
</tr>
<tr>
<td>NVL-032</td>
<td>Nấm đùi gà</td>
<td>Thảo Điền</td>
<td>12</td>
<td>kg</td>
<td>02/07/2025</td>
<td>
<span class="badge badge-success">Còn hàng</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết nguyên vật liệu · Nấm đùi gà">•••</button>
</td>
</tr>
<tr>
<td>NVL-045</td>
<td>Sữa dừa</td>
<td>Thảo Điền</td>
<td>0</td>
<td>hộp</td>
<td>10/08/2025</td>
<td>
<span class="badge badge-danger">Hết hàng</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết nguyên vật liệu · Sữa dừa">•••</button>
</td>
</tr>
<tr>
<td>NVL-061</td>
<td>Rau thơm</td>
<td>Thảo Điền</td>
<td>6,4</td>
<td>kg</td>
<td>27/06/2025</td>
<td>
<span class="badge badge-warning">Gần hết hạn</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết nguyên vật liệu · Rau thơm">•••</button>
</td>
</tr>
</tbody>
</table>
</div>
<div class="table-footer">
<span>Ca đầu ca · Trang 1 · Hiển thị 1–5 trong 248 nguyên vật liệu</span>
<div>
<button type="button" class="btn btn-secondary " data-demo-page="true">‹</button>
<button type="button" class="btn btn-primary " data-demo-page="true">1</button>
<button type="button" class="btn btn-secondary " data-demo-page="true">2</button>
<button type="button" class="btn btn-secondary " data-demo-page="true">›</button>
</div>
</div>
</div>
</div>