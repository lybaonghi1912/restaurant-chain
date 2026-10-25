<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="screen-content">
<div class="stat-row">
<div class="card stat">
<div class="stat-top">
<span class="muted">Tổng nhân sự</span>
<span class="stat-dot dot-accent">
</span>
</div>
<div class="stat-value">86</div>
<span class="text-success">+4 tháng này</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Đang trong ca</span>
<span class="stat-dot dot-success">
</span>
</div>
<div class="stat-value">42</div>
<span class="text-success">48,8% nhân sự</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Tạm nghỉ</span>
<span class="stat-dot dot-warning">
</span>
</div>
<div class="stat-value">5</div>
<span class="text-success">2 cần theo dõi</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Yêu cầu hỗ trợ</span>
<span class="stat-dot dot-info">
</span>
</div>
<div class="stat-value">3</div>
<span class="text-success">Đang chờ duyệt</span>
</div>
</div>
<div class="card data-card">
<div class="data-toolbar">
<div class="shift-tabs">
<button type="button" class="btn btn-primary ">Danh sách nhân sự</button>
<button type="button" class="btn btn-ghost " data-modal="Yêu cầu điều chuyển nhân sự">Yêu cầu điều chuyển <span class="badge badge-warning">3</span>
</button>
</div>
<div class="toolbar-actions">
<input class="control " type="text" placeholder="Tìm tên, SĐT, mã NV..."/>
<select class="control">
<option>Tất cả vai trò</option>
<option>Bếp</option>
<option>Phục vụ</option>
<option>Thu ngân</option>
</select>
<button type="button" class="btn btn-primary " data-modal="Thêm nhân viên">+ Thêm nhân viên</button>
</div>
</div>
<div class="table-scroll">
<table class="data-table">
<thead>
<tr>
<th>Mã NV</th>
<th>Nhân viên</th>
<th>Vai trò</th>
<th>Ca làm việc</th>
<th>Số điện thoại</th>
<th>Trạng thái</th>
<th>
</th>
</tr>
</thead>
<tbody>
<tr>
<td>NV-0082</td>
<td>Nguyễn Thị Lan</td>
<td>Phục vụ</td>
<td>Ca tối · 16:00–23:00</td>
<td>0982 442 901</td>
<td>
<span class="badge badge-success">Đang làm việc</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Hồ sơ nhân viên · Nguyễn Thị Lan">•••</button>
</td>
</tr>
<tr>
<td>NV-0061</td>
<td>Trần Minh Khoa</td>
<td>Bếp</td>
<td>Ca sáng · 08:00–16:00</td>
<td>0903 118 292</td>
<td>
<span class="badge badge-success">Đang làm việc</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Hồ sơ nhân viên · Trần Minh Khoa">•••</button>
</td>
</tr>
<tr>
<td>NV-0094</td>
<td>Lê Hoàng Yến</td>
<td>Thu ngân</td>
<td>Ca tối · 16:00–23:00</td>
<td>0918 702 449</td>
<td>
<span class="badge badge-warning">Tạm nghỉ</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Hồ sơ nhân viên · Lê Hoàng Yến">•••</button>
</td>
</tr>
<tr>
<td>NV-0048</td>
<td>Phạm Quốc Huy</td>
<td>Kho</td>
<td>Hành chính</td>
<td>0932 115 088</td>
<td>
<span class="badge badge-success">Đang làm việc</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Hồ sơ nhân viên · Phạm Quốc Huy">•••</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>