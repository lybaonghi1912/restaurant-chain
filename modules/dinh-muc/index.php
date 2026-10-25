<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="bom-layout">
<div class="card dish-sidebar">
<div class="dish-search">
<h3 class="title title-sm">Danh sách món</h3>
<input class="control " type="text" placeholder="Tìm món ăn..."/>
</div>
<div class="dish-list-menu">
<div class="" data-dish="Gỏi cuốn tôm thịt">
<div class="food-thumb small food-GV">GV</div>
<div>
<strong>Gỏi cuốn tôm thịt</strong>
<span>Khai vị</span>
</div>
<span class="badge badge-success">Đã thiết lập</span>
</div>
<div class="" data-dish="Bò nướng lá lốt">
<div class="food-thumb small food-BL">BL</div>
<div>
<strong>Bò nướng lá lốt</strong>
<span>Khai vị</span>
</div>
<span class="badge badge-success">Đã thiết lập</span>
</div>
<div class="active" data-dish="Cá chẽm sốt chanh">
<div class="food-thumb small food-CC">CC</div>
<div>
<strong>Cá chẽm sốt chanh</strong>
<span>Món chính</span>
</div>
<span class="badge badge-success">Đã thiết lập</span>
</div>
<div class="" data-dish="Bò lúc lắc">
<div class="food-thumb small food-BB">BB</div>
<div>
<strong>Bò lúc lắc</strong>
<span>Món chính</span>
</div>
<span class="badge badge-success">Đã thiết lập</span>
</div>
<div class="" data-dish="Cơm chiên hải sản">
<div class="food-thumb small food-CH">CH</div>
<div>
<strong>Cơm chiên hải sản</strong>
<span>Món chính</span>
</div>
<span class="badge badge-success">Đã thiết lập</span>
</div>
<div class="" data-dish="Lẩu nấm đặc biệt">
<div class="food-thumb small food-LN">LN</div>
<div>
<strong>Lẩu nấm đặc biệt</strong>
<span>Món chính</span>
</div>
<span class="badge badge-warning">Chưa thiết lập</span>
</div>
</div>
</div>
<div class="card recipe-builder">
<div class="recipe-head">
<div>
<span class="eyebrow">ĐỊNH MỨC CHO 1 PHẦN</span>
<h2 class="title title-md">Cá chẽm sốt chanh</h2>
<span class="muted">Cập nhật lần cuối bởi Trần Minh · 24/06/2025</span>
</div>
<span class="badge badge-success">Đã thiết lập</span>
</div>
<div class="recipe-metrics">
<div>
<span>Thành phần</span>
<strong>4</strong>
</div>
<div>
<span>Giá vốn ước tính</span>
<strong>84.200đ</strong>
</div>
<div>
<span>Giá bán</span>
<strong>249.000đ</strong>
</div>
<div>
<span>Food cost</span>
<strong>33,8%</strong>
</div>
</div>
<div class="ingredient-head">
<h3 class="title title-sm">Thành phần nguyên vật liệu</h3>
<button type="button" class="btn btn-secondary " data-add-ingredient="true">+ Thêm nguyên liệu</button>
</div>
<div class="ingredients">
<div class="ingredient-row">
<span class="row-index">1</span>
<label class="field">
<span class="field-label">Nguyên vật liệu</span>
<select class="control">
<option>Cá chẽm phi lê</option>
<option>Muối biển</option>
<option>Dầu ăn</option>
</select>
</label>
<label class="field">
<span class="field-label">Định lượng</span>
<input class="control " type="text" value="250"/>
</label>
<label class="field">
<span class="field-label">Đơn vị</span>
<select class="control">
<option>g</option>
<option>ml</option>
</select>
</label>
<button type="button" class="btn btn-ghost " data-remove-ingredient="0">×</button>
</div>
<div class="ingredient-row">
<span class="row-index">2</span>
<label class="field">
<span class="field-label">Nguyên vật liệu</span>
<select class="control">
<option>Chanh vàng</option>
<option>Muối biển</option>
<option>Dầu ăn</option>
</select>
</label>
<label class="field">
<span class="field-label">Định lượng</span>
<input class="control " type="text" value="35"/>
</label>
<label class="field">
<span class="field-label">Đơn vị</span>
<select class="control">
<option>g</option>
<option>ml</option>
</select>
</label>
<button type="button" class="btn btn-ghost " data-remove-ingredient="1">×</button>
</div>
<div class="ingredient-row">
<span class="row-index">3</span>
<label class="field">
<span class="field-label">Nguyên vật liệu</span>
<select class="control">
<option>Bơ lạt</option>
<option>Muối biển</option>
<option>Dầu ăn</option>
</select>
</label>
<label class="field">
<span class="field-label">Định lượng</span>
<input class="control " type="text" value="20"/>
</label>
<label class="field">
<span class="field-label">Đơn vị</span>
<select class="control">
<option>g</option>
<option>ml</option>
</select>
</label>
<button type="button" class="btn btn-ghost " data-remove-ingredient="2">×</button>
</div>
<div class="ingredient-row">
<span class="row-index">4</span>
<label class="field">
<span class="field-label">Nguyên vật liệu</span>
<select class="control">
<option>Rau củ theo mùa</option>
<option>Muối biển</option>
<option>Dầu ăn</option>
</select>
</label>
<label class="field">
<span class="field-label">Định lượng</span>
<input class="control " type="text" value="120"/>
</label>
<label class="field">
<span class="field-label">Đơn vị</span>
<select class="control">
<option>g</option>
<option>ml</option>
</select>
</label>
<button type="button" class="btn btn-ghost " data-remove-ingredient="3">×</button>
</div>
</div>
<div class="recipe-footer">
<span>
<b>Lưu ý:</b> Thay đổi định mức sẽ cập nhật tồn kho khả dụng trên POS.</span>
<button type="button" class="btn btn-primary ">Lưu định mức</button>
</div>
</div>
</div>