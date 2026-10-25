<?php 
if (!defined('APP_ENTRY')) { http_response_code(404); exit; } 

$conn = db();
$user = current_user();
// Lấy chi nhánh của nhân viên phục vụ đang đăng nhập (Prepared Statement an toàn)
$maChiNhanh = (int)($user['maChiNhanh'] ?? 1);

// 1. Lấy danh sách bàn của chi nhánh hiện tại
$stmtBan = $conn->prepare("
    SELECT maBan, soBan, sucChua, trangThai 
    FROM Ban 
    WHERE maChiNhanh = :maChiNhanh 
    ORDER BY soBan ASC
");
$stmtBan->execute([':maChiNhanh' => $maChiNhanh]);
$danhSachBan = $stmtBan->fetchAll();

// Mặc định chọn bàn A06 nếu có trong CSDL, hoặc lấy bàn đầu tiên
$currentBan = null;
foreach ($danhSachBan as $b) {
    if ((string)$b['soBan'] === 'A06' || (string)$b['soBan'] === '6') {
        $currentBan = $b;
        break;
    }
}
if (!$currentBan && !empty($danhSachBan)) {
    $currentBan = $danhSachBan[0];
}

// 2. Lấy danh sách nhóm món
$stmtNhom = $conn->query("SELECT maNhom, tenNhom FROM NhomMon ORDER BY maNhom ASC");
$danhSachNhom = $stmtNhom->fetchAll();

// 3. Lấy danh sách món ăn từ database
$stmtMon = $conn->query("
    SELECT m.maMon, m.tenMon, m.donGia, m.hinhAnh, n.tenNhom 
    FROM MonAn m 
    LEFT JOIN NhomMon n ON m.maNhom = n.maNhom 
    ORDER BY n.maNhom ASC, m.tenMon ASC
");
$danhSachMon = $stmtMon->fetchAll();

// Hàm hỗ trợ tạo mã viết tắt 2 ký tự (GV, BL, CC, BB,...) cho CSS cũ
function get_food_code(string $name): string {
    $words = preg_split('/\s+/u', trim($name));
    if (count($words) >= 2) {
        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
    return mb_strtoupper(mb_substr($name, 0, 2));
}
?>

<div class="pos-layout">
  <div class="catalog">
    <div class="catalog-tools">
      <div class="search-wide">
        <input class="control " type="text" placeholder="Tìm món ăn, mã món..."/>
      </div>
      <div class="category-tabs">
        <button type="button" class="btn btn-primary " data-category="Tất cả">Tất cả</button>
        <?php if (!empty($danhSachNhom)): ?>
          <?php foreach ($danhSachNhom as $nhom): ?>
            <button type="button" class="btn btn-ghost " data-category="<?= e($nhom['tenNhom']) ?>">
              <?= e($nhom['tenNhom']) ?>
            </button>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Giữ nguyên tab mẫu nếu CSDL chưa nạp nhóm món -->
          <button type="button" class="btn btn-ghost " data-category="Khai vị">Khai vị</button>
          <button type="button" class="btn btn-ghost " data-category="Món chính">Món chính</button>
          <button type="button" class="btn btn-ghost " data-category="Tráng miệng">Tráng miệng</button>
          <button type="button" class="btn btn-ghost " data-category="Đồ uống">Đồ uống</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="menu-grid">
      <?php if (!empty($danhSachMon)): ?>
        <?php foreach ($danhSachMon as $mon): 
          $code = get_food_code($mon['tenMon']);
        ?>
          <div class="card menu-card " data-dish-id="<?= e((string)$mon['maMon']) ?>" data-add-dish="<?= e($mon['tenMon']) ?>">
            <div class="food-thumb food-<?= e($code) ?>"><?= e($code) ?></div>
            <div class="menu-info">
              <span class="badge badge-success">Sẵn sàng</span>
              <strong><?= e($mon['tenMon']) ?></strong>
              <span><?= e($mon['tenNhom'] ?? 'Món chính') ?></span>
              <b><?= number_format((float)$mon['donGia'], 0, ',', '.') ?>đ</b>
            </div>
            <span class="add-circle">+</span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Giữ lại danh sách thẻ mẫu nguyên bản nếu CSDL trống -->
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
      <?php endif; ?>
    </div>
  </div>

  <div class="cart">
    <div class="cart-head">
      <div>
        <span class="eyebrow">ĐƠN HÀNG</span>
        <h2 class="title title-md"><?= $currentBan ? ('Bàn ' . e((string)$currentBan['soBan'])) : 'Bàn A06' ?></h2>
      </div>
      <button type="button" class="btn btn-secondary ">•••</button>
    </div>

    <div class="segmented full">
      <button type="button" class="btn btn-primary " data-order-mode="Tại bàn">
        Tại bàn · <?= $currentBan ? e((string)$currentBan['soBan']) : 'A06' ?>
      </button>
      <button type="button" class="btn btn-ghost " data-order-mode="Mang về">Mang về</button>
    </div>

    <div class="guest-line">
      <span>Khách lẻ · <?= $currentBan ? e((string)$currentBan['sucChua']) : '4' ?> khách</span>
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

    <button type="button" class="btn btn-primary send-kitchen">
      Gửi bếp <span>3 món · 467.000đ</span>
    </button>
  </div>
</div>