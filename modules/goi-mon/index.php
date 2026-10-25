<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="pos-layout">
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
<div class="card menu-card " data-add-dish="Gỏi cuốn tôm thịt">
<div class="food-thumb food-GV">GV</div>
<div class="menu-info">
<span class="badge badge-success">Còn 24 phần</span>
<strong>Gỏi cuốn tôm thịt</strong>
<span>Khai vị</span>
<b>89.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-add-dish="Bò nướng lá lốt">
<div class="food-thumb food-BL">BL</div>
<div class="menu-info">
<span class="badge badge-success">Còn 8 phần</span>
<strong>Bò nướng lá lốt</strong>
<span>Khai vị</span>
<b>149.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-add-dish="Cá chẽm sốt chanh">
<div class="food-thumb food-CC">CC</div>
<div class="menu-info">
<span class="badge badge-success">Còn 12 phần</span>
<strong>Cá chẽm sốt chanh</strong>
<span>Món chính</span>
<b>249.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-add-dish="Bò lúc lắc">
<div class="food-thumb food-BB">BB</div>
<div class="menu-info">
<span class="badge badge-warning">Sắp hết · 3</span>
<strong>Bò lúc lắc</strong>
<span>Món chính</span>
<b>229.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card " data-add-dish="Cơm chiên hải sản">
<div class="food-thumb food-CH">CH</div>
<div class="menu-info">
<span class="badge badge-success">Còn 18 phần</span>
<strong>Cơm chiên hải sản</strong>
<span>Món chính</span>
<b>169.000đ</b>
</div>
<span class="add-circle">+</span>
</div>
<div class="card menu-card disabled-card" data-add-dish="Lẩu nấm đặc biệt">
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
<div class="cart">
<div class="cart-head">
<div>
<span class="eyebrow">ĐƠN HÀNG</span>
<h2 class="title title-md">Bàn A06</h2>
</div>
<button type="button" class="btn btn-secondary ">•••</button>
</div>
<div class="segmented full">
<button type="button" class="btn btn-primary " data-order-mode="Tại bàn">Tại bàn · A06</button>
<button type="button" class="btn btn-ghost " data-order-mode="Mang về">Mang về</button>
</div>
<div class="guest-line">
<span>Khách lẻ · 4 khách</span>
<button type="button" class="btn btn-ghost ">Chỉnh sửa</button>
</div>
<div class="cart-items">
<div class="cart-item">
<div class="item-count">2</div>
<div class="item-detail">
<strong>Gỏi cuốn tôm thịt</strong>
<span>178.000đ</span>
<small>Không hành · Ít cay</small>
</div>
<div class="qty">
<button type="button" class="btn btn-ghost " data-qty-minus="0">−</button>
<strong>2</strong>
<button type="button" class="btn btn-ghost " data-qty-plus="0">+</button>
</div>
</div>
<div class="cart-item">
<div class="item-count">1</div>
<div class="item-detail">
<strong>Cá chẽm sốt chanh</strong>
<span>249.000đ</span>
</div>
<div class="qty">
<button type="button" class="btn btn-ghost " data-qty-minus="1">−</button>
<strong>1</strong>
<button type="button" class="btn btn-ghost " data-qty-plus="1">+</button>
</div>
</div>
<div class="cart-item">
<div class="item-count">2</div>
<div class="item-detail">
<strong>Nước suối</strong>
<span>40.000đ</span>
</div>
<div class="qty">
<button type="button" class="btn btn-ghost " data-qty-minus="2">−</button>
<strong>2</strong>
<button type="button" class="btn btn-ghost " data-qty-plus="2">+</button>
</div>
</div>
</div>
<button type="button" class="btn btn-secondary note-button">+ Thêm ghi chú cho bếp</button>
<div class="cart-summary">
<div>
<span>Tạm tính</span>
<strong>467.000đ</strong>
</div>
<div>
<span>Phí dịch vụ</span>
<strong>0đ</strong>
</div>
<div class="grand">
<span>Tổng cộng</span>
<strong>467.000đ</strong>
</div>
</div>
<button type="button" class="btn btn-primary send-kitchen">Gửi bếp <span>3 món · 467.000đ</span>
</button>
</div>
</div>