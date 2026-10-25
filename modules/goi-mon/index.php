<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_kitchen') {
    $respond = static function (int $status, array $payload): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    };

    try {
        $user = current_user();
    } catch (Throwable $error) {
        error_log('POS send-to-kitchen auth lookup failed: ' . $error->getMessage());
        $respond(500, ['success' => false, 'message' => 'Không thể xác thực phiên làm việc. Vui lòng thử lại.']);
    }
    if (!$user) {
        $respond(401, ['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
    }
    if (($user['maVaiTro'] ?? '') !== 'PHUC_VU') {
        $respond(403, ['success' => false, 'message' => 'Chỉ nhân viên phục vụ mới được gửi món xuống bếp.']);
    }

    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!is_string($csrfToken) || !hash_equals(csrf_token(), $csrfToken)) {
        $respond(403, ['success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.']);
    }

    try {
        $maBan = $_POST['maBan'] ?? null;
        $rawItems = $_POST['items'] ?? null;
        if (!is_string($maBan) || $maBan === '' || strlen($maBan) > 10 || !is_string($rawItems)) {
            throw new DomainException('Thông tin bàn hoặc danh sách món không hợp lệ.');
        }

        try {
            $items = json_decode($rawItems, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new DomainException('Danh sách món gửi lên không hợp lệ.');
        }
        if (!is_array($items) || $items === [] || count($items) > 100) {
            throw new DomainException('Đơn cần có từ 1 đến 100 món.');
        }

        $validatedItems = [];
        foreach ($items as $item) {
            if (!is_array($item) || !is_string($item['id'] ?? null) || $item['id'] === '' || strlen($item['id']) > 10) {
                throw new DomainException('Mã món không hợp lệ.');
            }
            $quantity = $item['qty'] ?? null;
            if (!is_int($quantity) || $quantity < 1 || $quantity > 999) {
                throw new DomainException('Số lượng món phải từ 1 đến 999.');
            }
            $note = $item['note'] ?? '';
            if (!is_string($note) || mb_strlen($note, 'UTF-8') > 255) {
                throw new DomainException('Ghi chú món không được vượt quá 255 ký tự.');
            }
            $validatedItems[] = ['id' => $item['id'], 'qty' => $quantity, 'note' => $note];
        }
    } catch (DomainException $error) {
        $respond(400, ['success' => false, 'message' => $error->getMessage()]);
    }

    $maChiNhanh = (string)($user['maChiNhanh'] ?? '');
    $maNhanVien = (string)($user['maNhanVien'] ?? '');
    if ($maChiNhanh === '' || $maNhanVien === '') {
        $respond(403, ['success' => false, 'message' => 'Tài khoản chưa được gán chi nhánh hoặc mã nhân viên.']);
    }

    $conn = null;
    try {
        $conn = db();
        $conn->beginTransaction();

        $stmtBan = $conn->prepare(
            "SELECT maBan, trangThai FROM Ban
             WHERE maBan = :maBan AND maChiNhanh = :maChiNhanh
             FOR UPDATE"
        );
        $stmtBan->execute([':maBan' => $maBan, ':maChiNhanh' => $maChiNhanh]);
        $table = $stmtBan->fetch();
        if (!$table || $table['trangThai'] === 'NGUNG') {
            throw new DomainException('Bàn không tồn tại hoặc hiện không thể phục vụ tại chi nhánh của bạn.');
        }

        // DonGoiMon and ChiTietDonMon use VARCHAR primary keys, not AUTO_INCREMENT IDs.
        $orderId = 'DG' . bin2hex(random_bytes(9));
        $stmtOrder = $conn->prepare(
            "INSERT INTO DonGoiMon
                (maDon, maChiNhanh, maBan, maNhanVien, thoiGianTao, thoiGianGuiBep, tongTien, trangThai)
             VALUES
                (:maDon, :maChiNhanh, :maBan, :maNhanVien, UTC_TIMESTAMP(), UTC_TIMESTAMP(), 0, 'DANG_PHUC_VU')"
        );
        $stmtOrder->execute([
            ':maDon' => $orderId,
            ':maChiNhanh' => $maChiNhanh,
            ':maBan' => $maBan,
            ':maNhanVien' => $maNhanVien,
        ]);

        $stmtDish = $conn->prepare(
            "SELECT m.maMon, m.tenMon, COALESCE(td.giaBan, m.donGia) AS donGia
             FROM MonAn m
             JOIN ThucDonChiNhanh td ON td.maMon = m.maMon
             WHERE m.maMon = :maMon
               AND m.trangThai = 'HOAT_DONG'
               AND td.maChiNhanh = :maChiNhanh
               AND td.trangThai = 'DANG_BAN'"
        );
        $stmtDetail = $conn->prepare(
            "INSERT INTO ChiTietDonMon
                (maCTDon, maDon, maChiNhanh, maMon, tenMon, soLuong, donGia, ghiChu, trangThai, gioGuiBep)
             VALUES
                (:maCTDon, :maDon, :maChiNhanh, :maMon, :tenMon, :soLuong, :donGia, :ghiChu, 'CHO_CHE_BIEN', UTC_TIMESTAMP())"
        );

        foreach ($validatedItems as $item) {
            $stmtDish->execute([':maMon' => $item['id'], ':maChiNhanh' => $maChiNhanh]);
            $dish = $stmtDish->fetch();
            if (!$dish) {
                throw new DomainException('Một món trong đơn không còn được bán tại chi nhánh này.');
            }

            $stmtDetail->execute([
                ':maCTDon' => 'CT' . bin2hex(random_bytes(9)),
                ':maDon' => $orderId,
                ':maChiNhanh' => $maChiNhanh,
                ':maMon' => $dish['maMon'],
                ':tenMon' => $dish['tenMon'],
                ':soLuong' => $item['qty'],
                ':donGia' => $dish['donGia'],
                ':ghiChu' => $item['note'] !== '' ? $item['note'] : null,
            ]);
        }

        $stmtTotal = $conn->prepare(
            "UPDATE DonGoiMon
             SET tongTien = (
                 SELECT COALESCE(SUM(soLuong * donGia), 0)
                 FROM ChiTietDonMon
                 WHERE maDon = :maDonChiTiet AND maChiNhanh = :maChiNhanhChiTiet
             )
             WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh"
        );
        $stmtTotal->execute([
            ':maDonChiTiet' => $orderId,
            ':maChiNhanhChiTiet' => $maChiNhanh,
            ':maDon' => $orderId,
            ':maChiNhanh' => $maChiNhanh,
        ]);

        $stmtUpdateBan = $conn->prepare(
            "UPDATE Ban SET trangThai = 'DANG_PHUC_VU'
             WHERE maBan = :maBan AND maChiNhanh = :maChiNhanh"
        );
        $stmtUpdateBan->execute([':maBan' => $maBan, ':maChiNhanh' => $maChiNhanh]);

        $conn->commit();
        $respond(200, ['success' => true, 'message' => 'Gửi bếp thành công', 'maDon' => $orderId]);
    } catch (DomainException $error) {
        if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
        $respond(400, ['success' => false, 'message' => $error->getMessage()]);
    } catch (Throwable $error) {
        if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
        error_log('POS send-to-kitchen failed: ' . $error->getMessage());
        $respond(500, ['success' => false, 'message' => 'Lỗi khi lưu đơn. Vui lòng thử lại.']);
    }
}

$conn = db();
$user = current_user();
$maChiNhanh = (string)($user['maChiNhanh'] ?? '');

// 1. Lấy danh sách bàn của chi nhánh hiện tại
$stmtBan = $conn->prepare("
    SELECT maBan, soBan, sucChua, trangThai 
    FROM Ban 
    WHERE maChiNhanh = :maChiNhanh
      AND trangThai <> 'NGUNG'
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
$stmtMon = $conn->prepare("
    SELECT m.maMon, m.tenMon, COALESCE(td.giaBan, m.donGia) AS donGia, m.hinhAnh, n.tenNhom
    FROM MonAn m
    JOIN ThucDonChiNhanh td ON td.maMon = m.maMon
    LEFT JOIN NhomMon n ON m.maNhom = n.maNhom
    WHERE td.maChiNhanh = :maChiNhanh
      AND td.trangThai = 'DANG_BAN'
      AND m.trangThai = 'HOAT_DONG'
    ORDER BY n.maNhom ASC, m.tenMon ASC
");
$stmtMon->execute([':maChiNhanh' => $maChiNhanh]);
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

<div class="pos-layout"
     data-table-id="<?= e((string)($currentBan['maBan'] ?? '')) ?>"
     data-table-number="<?= e((string)($currentBan['soBan'] ?? '')) ?>"
     data-csrf-token="<?= e(csrf_token()) ?>">
  <div class="catalog">
    <div class="catalog-tools">
      <div class="search-wide">
        <input class="control " id="posSearchDish" type="text" placeholder="Tìm món ăn, mã món..."/>
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

    <script>
    (() => {
      const initializePosCart = () => {
        const pos = document.querySelector('.pos-layout');
        if (!pos) return;

        const cartRoot = pos.querySelector('.cart-items');
        const sendButton = pos.querySelector('.send-kitchen');
        const searchInput = pos.querySelector('#posSearchDish');
        const categoryButtons = [...pos.querySelectorAll('.category-tabs button')];
        let cart = [];
        let activeCategory = 'Tất cả';
        const cancellationLog = [];
        let sendingOrder = false;

      const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      })[char]);
      const normalize = value => String(value)
        .toLocaleLowerCase('vi')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd');
      const formatCurrency = value => `${Math.round(value).toLocaleString('vi-VN')}đ`;
      const getDishDetails = card => {
        const name = card.querySelector('.menu-info strong')?.textContent.trim() ?? '';
        const priceText = card.querySelector('.menu-info b')?.textContent ?? '';
        const category = card.dataset.dishCategory
          ?? card.querySelector('.menu-info span:not(.badge)')?.textContent.trim()
          ?? '';

        return {
          id: card.dataset.dishId || name,
          name,
          price: Number(priceText.replace(/[^\d]/g, '')) || 0,
          category,
        };
      };

      function renderCart() {
        if (!cartRoot) return;

        if (cart.length === 0) {
          cartRoot.innerHTML = '<p class="muted">Chưa có món nào được chọn. Nhấp dấu (+) trên món ăn để thêm vào đơn.</p>';
        } else {
          cartRoot.innerHTML = cart.map((item, index) => `
            <div class="cart-item" data-id="${escapeHtml(item.id)}">
              <div class="item-count">${item.qty}</div>
              <div class="item-detail">
                <strong>${escapeHtml(item.name)}</strong>
                <span>${formatCurrency(item.price * item.qty)}</span>${item.note ? `<small>${escapeHtml(item.note)}</small>` : ''}
              </div>
              <div class="qty">
                <button type="button" class="btn btn-ghost btn-qty-minus" data-qty-minus="${index}" aria-label="Giảm ${escapeHtml(item.name)}">−</button>
                <strong>${item.qty}</strong>
                <button type="button" class="btn btn-ghost btn-qty-plus" data-qty-plus="${index}" aria-label="Tăng ${escapeHtml(item.name)}">+</button>
                <button type="button" class="btn btn-ghost btn-cancel-item" data-cancel-item="${escapeHtml(item.id)}" aria-label="Hủy ${escapeHtml(item.name)}">Hủy</button>
              </div>
            </div>
          `).join('');
        }

        const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
        const summaryValues = pos.querySelectorAll('.cart-summary strong');
        if (summaryValues[0]) summaryValues[0].textContent = formatCurrency(subtotal);
        if (summaryValues[1]) summaryValues[1].textContent = formatCurrency(0);
        if (summaryValues[2]) summaryValues[2].textContent = formatCurrency(subtotal);

        const itemCount = cart.length;
        const sendLabel = sendButton?.querySelector('span');
        if (sendLabel) sendLabel.textContent = sendingOrder ? 'Đang gửi...' : `${itemCount} món · ${formatCurrency(subtotal)}`;
        if (sendButton) sendButton.disabled = itemCount === 0 || !pos.dataset.tableId || sendingOrder;
      }

      function filterMenu() {
        const searchTerm = normalize(searchInput?.value ?? '');
        pos.querySelectorAll('.menu-card').forEach(card => {
          const dish = getDishDetails(card);
          const matchesCategory = activeCategory === 'Tất cả'
            || normalize(dish.category) === normalize(activeCategory);
          const matchesSearch = normalize(dish.name).includes(searchTerm);
          card.hidden = !matchesCategory || !matchesSearch;
        });
      }

      pos.addEventListener('click', event => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const categoryButton = target.closest('.category-tabs button[data-category]');
        if (categoryButton && pos.contains(categoryButton)) {
          event.stopPropagation();
          activeCategory = categoryButton.dataset.category ?? 'Tất cả';
          categoryButtons.forEach(button => {
            const active = button === categoryButton;
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-ghost', !active);
            button.setAttribute('aria-pressed', String(active));
          });
          filterMenu();
          return;
        }

        const quantityButton = target.closest('[data-qty-plus], [data-qty-minus]');
        if (quantityButton && pos.contains(quantityButton)) {
          event.stopPropagation();
          if (sendingOrder) return;
          const index = Number(quantityButton.dataset.qtyPlus ?? quantityButton.dataset.qtyMinus);
          if (!Number.isInteger(index) || !cart[index]) return;

          if (quantityButton.hasAttribute('data-qty-plus')) {
            cart[index].qty += 1;
          } else if (cart[index].qty <= 1) {
            window.openPosCancellation?.(cart[index].id);
            return;
          } else {
            cart[index].qty -= 1;
          }
          renderCart();
          return;
        }

        const cancelButton = target.closest('[data-cancel-item]');
        if (cancelButton && pos.contains(cancelButton)) {
          event.stopPropagation();
          if (sendingOrder) return;
          window.openPosCancellation?.(cancelButton.dataset.cancelItem);
          return;
        }

        const card = target.closest('.menu-card');
        if (!card || !pos.contains(card) || !card.hasAttribute('data-add-dish')) return;
        if (sendingOrder) return;
        if (card.classList.contains('disabled-card') || card.getAttribute('aria-disabled') === 'true') return;

        event.stopPropagation();
        const dish = getDishDetails(card);
        if (!dish.name) return;

        const existing = cart.find(item => item.id === dish.id);
        if (existing) {
          existing.qty += 1;
        } else {
          cart.push({ id: dish.id, name: dish.name, price: dish.price, qty: 1, note: '' });
        }
        renderCart();
      });

      searchInput?.addEventListener('input', event => {
        event.stopPropagation();
        filterMenu();
      });

      // Hộp thoại gửi bếp dùng chung lấy dữ liệu từ giỏ POS hiện tại.
      window.posCartForConfirmation = () => cart;
      window.posCancellationLog = cancellationLog;
      window.cancelPosCartItem = (itemId, quantity, reason, note) => {
        const index = cart.findIndex(item => item.id === itemId);
        const item = cart[index];
        if (!item || !Number.isInteger(quantity) || quantity < 1 || quantity > item.qty || !reason) return false;

        const cancellation = {
          id: item.id,
          name: item.name,
          soLuongHuy: quantity,
          lyDoHuy: reason,
          ghiChuHuy: note,
          thoiGianHuy: new Date().toISOString(),
        };
        cancellationLog.push(cancellation);

        if (quantity === item.qty) {
          cart.splice(index, 1);
        } else {
          item.qty -= quantity;
          item.cancellationHistory ??= [];
          item.cancellationHistory.push(cancellation);
          item.lyDoHuy = reason;
        }

        renderCart();
        return true;
      };

      window.submitPosOrder = async () => {
        if (sendingOrder) throw new Error('Đơn đang được gửi. Vui lòng đợi trong giây lát.');
        if (!pos.dataset.tableId) throw new Error('Chưa có bàn hợp lệ để gửi món.');
        if (cart.length === 0) throw new Error('Đơn cần có ít nhất một món.');

        sendingOrder = true;
        renderCart();
        try {
          const body = new URLSearchParams({
            action: 'send_kitchen',
            csrf_token: pos.dataset.csrfToken ?? '',
            maBan: pos.dataset.tableId,
            items: JSON.stringify(cart.map(item => ({
              id: item.id,
              qty: item.qty,
              note: item.note ?? '',
            }))),
          });
          const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body,
            credentials: 'same-origin',
          });
          let result;
          try {
            result = await response.json();
          } catch {
            throw new Error('Máy chủ trả về phản hồi không hợp lệ. Vui lòng thử lại.');
          }
          if (!response.ok || !result.success) {
            throw new Error(result.message || 'Không thể gửi đơn xuống bếp.');
          }

          const tableNumber = pos.dataset.tableNumber || pos.dataset.tableId;
          cart = [];
          renderCart();
          return { message: `Đã gửi bếp thành công cho bàn ${tableNumber}!` };
        } finally {
          sendingOrder = false;
          renderCart();
        }
      };

        renderCart();
      };

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePosCart, { once: true });
      } else {
        initializePosCart();
      }
    })();
    </script>

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
        <h2 class="title title-md"><?= $currentBan ? ('Bàn ' . e((string)$currentBan['soBan'])) : 'Chưa chọn bàn' ?></h2>
      </div>
      <button type="button" class="btn btn-secondary ">•••</button>
    </div>

    <div class="segmented full">
      <button type="button" class="btn btn-primary " data-order-mode="Tại bàn">
        Tại bàn · <?= $currentBan ? e((string)$currentBan['soBan']) : 'Chưa chọn bàn' ?>
      </button>
      <button type="button" class="btn btn-ghost " data-order-mode="Mang về">Mang về</button>
    </div>

    <div class="guest-line">
      <span>Khách lẻ · <?= $currentBan ? e((string)$currentBan['sucChua']) : '0' ?> khách</span>
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