<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<div class="screen-content menu-management">
<div class="section-head"><div><span class="eyebrow accent">THỰC ĐƠN TOÀN CHUỖI</span><h2 class="title title-md">Danh sách món ăn</h2></div><button type="button" class="btn btn-primary" data-modal="Thêm món ăn">+ Thêm món ăn</button></div>
<div class="catalog">
<div class="catalog-tools">
<div class="search-wide">
<input class="control " type="text" placeholder="Tìm món ăn, mã món..."/>
</div>
<div class="category-tabs">
<button type="button" class="btn btn-primary " data-category="Tất cả">Tất cả</button>
<button type="button" class="btn btn-ghost " data-category="Khai vị">Khai vị</button>
<button type="button" class="btn btn-ghost " data-category="Món chính">Món chính</button>
<button type="button" class="btn btn-ghost " data-category="Tráng miệng">Tráng miệng</button>
<button type="button" class="btn btn-ghost " data-category="Đồ uống">Đồ uống</button>
</div>
</div>
<div class="menu-grid">
<div class="card menu-card " data-edit-dish="Gỏi cuốn tôm thịt">
<div class="food-thumb food-GV">GV</div>
<div class="menu-info">
<span class="badge badge-success">Còn 24 phần</span>
<strong>Gỏi cuốn tôm thịt</strong>
<span>Khai vị</span>
<b>89.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-edit-dish="Bò nướng lá lốt">
<div class="food-thumb food-BL">BL</div>
<div class="menu-info">
<span class="badge badge-success">Còn 8 phần</span>
<strong>Bò nướng lá lốt</strong>
<span>Khai vị</span>
<b>149.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-edit-dish="Cá chẽm sốt chanh">
<div class="food-thumb food-CC">CC</div>
<div class="menu-info">
<span class="badge badge-success">Còn 12 phần</span>
<strong>Cá chẽm sốt chanh</strong>
<span>Món chính</span>
<b>249.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-edit-dish="Bò lúc lắc">
<div class="food-thumb food-BB">BB</div>
<div class="menu-info">
<span class="badge badge-warning">Sắp hết · 3</span>
<strong>Bò lúc lắc</strong>
<span>Món chính</span>
<b>229.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-edit-dish="Cơm chiên hải sản">
<div class="food-thumb food-CH">CH</div>
<div class="menu-info">
<span class="badge badge-success">Còn 18 phần</span>
<strong>Cơm chiên hải sản</strong>
<span>Món chính</span>
<b>169.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card disabled-card" data-edit-dish="Lẩu nấm đặc biệt">
<div class="food-thumb food-LN">LN</div>
<div class="menu-info">
<span class="badge badge-danger">Hết NVL</span>
<strong>Lẩu nấm đặc biệt</strong>
<span>Món chính</span>
<b>329.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
</div>
</div>
<p class="sample-footnote">Chọn món để xem chi tiết và chỉnh sửa thông tin mẫu.</p></div>