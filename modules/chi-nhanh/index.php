<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<div class="screen-content"><div class="card data-card">
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