<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="screen-content executive">
<div class="stat-row">
<div class="card stat">
<div class="stat-top">
<span class="muted">Doanh thu toàn chuỗi</span>
<span class="stat-dot dot-accent">
</span>
</div>
<div class="stat-value">2,84 tỷ</div>
<span class="text-success">+12,4% so kỳ trước</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Lợi nhuận gộp</span>
<span class="stat-dot dot-success">
</span>
</div>
<div class="stat-value">986,2 tr</div>
<span class="text-success">+8,2% so kỳ trước</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Giá vốn F&amp;B</span>
<span class="stat-dot dot-info">
</span>
</div>
<div class="stat-value">34,7%</div>
<span class="text-success">-1,8% tích cực</span>
</div>
<div class="card stat">
<div class="stat-top">
<span class="muted">Đơn hoàn tất</span>
<span class="stat-dot dot-warning">
</span>
</div>
<div class="stat-value">8.426</div>
<span class="text-success">+642 đơn</span>
</div>
</div>
<div class="chart-row">
<div class="card chart-card">
<div class="section-head">
<div>
<h3 class="title title-sm">Doanh thu &amp; chi phí</h3>
<span class="muted">6 tháng gần nhất · triệu đồng</span>
</div>
<div class="shift-tabs">
<button type="button" class="btn btn-ghost " data-period="Tháng">Tháng</button>
<button type="button" class="btn btn-primary " data-period="Quý">Quý</button>
<button type="button" class="btn btn-ghost " data-period="Năm">Năm</button>
</div>
</div>
<div class="line-chart">
<div class="y-labels">
<span>800</span>
<span>600</span>
<span>400</span>
<span>200</span>
<span>0</span>
</div>
<svg viewBox="0 0 600 220" preserveAspectRatio="none">
<defs>
<linearGradient id="area" x1="0" y1="0" x2="0" y2="1">
<stop offset="0" stop-color="#e05638" stop-opacity=".2">
</stop>
<stop offset="1" stop-color="#e05638" stop-opacity="0">
</stop>
</linearGradient>
</defs>
<path class="area" d="M0,180 C70,155 90,125 150,136 S240,70 300,92 S390,45 450,68 S530,22 600,34 L600,220 L0,220 Z">
</path>
<path class="revenue-line" d="M0,180 C70,155 90,125 150,136 S240,70 300,92 S390,45 450,68 S530,22 600,34">
</path>
<path class="cost-line" d="M0,198 C80,184 100,171 150,178 S240,145 300,158 S390,132 450,142 S540,112 600,118">
</path>
</svg>
<div class="x-labels">
<span>T1</span>
<span>T2</span>
<span>T3</span>
<span>T4</span>
<span>T5</span>
<span>T6</span>
</div>
</div>
<div class="chart-legend">
<span>
<i class="line revenue">
</i>Doanh thu</span>
<span>
<i class="line cost">
</i>Chi phí</span>
</div>
</div>
<div class="card approval-card">
<div class="section-head">
<div>
<h3 class="title title-sm">Trung tâm phê duyệt</h3>
<span class="muted">6 yêu cầu đang chờ</span>
</div>
<button type="button" class="btn btn-ghost ">Xem tất cả</button>
</div>
<div class="approval">
<span class="approval-icon dot-warning">
</span>
<div>
<strong>Điều chuyển kho</strong>
<span>Q1 → Thảo Điền · 14kg thịt bò</span>
<small>24 phút trước</small>
</div>
<button type="button" class="btn btn-secondary ">Duyệt</button>
</div>
<div class="approval">
<span class="approval-icon dot-info">
</span>
<div>
<strong>Hỗ trợ nhân sự</strong>
<span>2 Phục vụ · Thảo Điền → Q1</span>
<small>1 giờ trước</small>
</div>
<button type="button" class="btn btn-secondary ">Duyệt</button>
</div>
<div class="approval">
<span class="approval-icon dot-accent">
</span>
<div>
<strong>Đơn mua hàng</strong>
<span>Fresh Foods · 28,6 triệu</span>
<small>2 giờ trước</small>
</div>
<button type="button" class="btn btn-secondary ">Duyệt</button>
</div>
</div>
</div>
<div class="card data-card">
<div class="data-toolbar">
<div>
<h3 class="title title-sm">Hiệu suất chi nhánh</h3>
<span class="muted">So sánh tháng 06/2025</span>
</div>
<button type="button" class="btn btn-primary " data-modal="Thêm chi nhánh mới">+ Thêm chi nhánh</button>
</div>
<div class="table-scroll">
<table class="data-table">
<thead>
<tr>
<th>Chi nhánh</th>
<th>Quản lý</th>
<th>Doanh thu</th>
<th>Chi phí</th>
<th>Lợi nhuận</th>
<th>Công suất bàn</th>
<th>Trạng thái</th>
<th>
</th>
</tr>
</thead>
<tbody>
<tr>
<td>Thảo Điền</td>
<td>Phạm Ngọc Anh</td>
<td>1,12 tỷ</td>
<td>718,4 tr</td>
<td>401,6 tr</td>
<td>82%</td>
<td>
<span class="badge badge-success">Hoạt động</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết chi nhánh · Thảo Điền">•••</button>
</td>
</tr>
<tr>
<td>Quận 1</td>
<td>Nguyễn Hoàng Long</td>
<td>986,2 tr</td>
<td>664,8 tr</td>
<td>321,4 tr</td>
<td>76%</td>
<td>
<span class="badge badge-success">Hoạt động</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết chi nhánh · Quận 1">•••</button>
</td>
</tr>
<tr>
<td>Phú Mỹ Hưng</td>
<td>Trần Minh Thư</td>
<td>734,8 tr</td>
<td>471,6 tr</td>
<td>263,2 tr</td>
<td>68%</td>
<td>
<span class="badge badge-success">Hoạt động</span>
</td>
<td>
<button type="button" class="btn btn-ghost table-action" data-modal="Chi tiết chi nhánh · Phú Mỹ Hưng">•••</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>