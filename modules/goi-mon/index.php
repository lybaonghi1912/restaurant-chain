<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
if (!isset($isCheckoutPage)) { http_response_code(404); exit; }

/**
 * The POS and checkout controllers prepare different data before including this shared layout.
 *
 * @var bool $isCheckoutPage
 * @var array<string, mixed>|null $currentBan
 * @var list<array<string, mixed>> $danhSachBan
 * @var list<array<string, mixed>> $danhSachMon
 * @var list<array<string, mixed>> $danhSachNhom
 * @var array<string, mixed>|null $activeOrder
 * @var list<array<string, mixed>> $sentOrderItems
 * @var float $sentOrderSubtotal
 * @var \Closure(array<string, mixed>=): string $checkoutUrl
 * @var string $errorMessage
 * @var array<string, mixed>|null $order
 * @var list<array<string, mixed>> $orderItems
 * @var float $subtotal
 * @var float $vat
 * @var float $discount
 * @var float $grandTotal
 * @var bool $hasOrderSelection
 * @var list<array<string, mixed>> $checkoutTables
 * @var string $requestedTableId
 * @var \Closure(float): string $formatMoney
 * @var bool $isPaid
 * @var bool $canPay
 * @var string $accountName
 * @var string $accountNo
 * @var string $qrContent
 * @var string $vietQrUrl
 */
?>
<?php if ($isCheckoutPage): ?>
<div class="checkout-layout" data-checkout-total="<?= e((string)$grandTotal) ?>">
  <header class="checkout-heading">
    <div><span class="eyebrow">HÓA ĐƠN &amp; THANH TOÁN</span><h2 class="title title-md">Thanh toán tại bàn</h2><p class="muted">Kiểm tra món đã gọi, nhận tiền và hoàn tất lượt phục vụ.</p></div>
    <a class="btn btn-secondary" href="<?= e(page_url('pos')) ?>">← Quay lại gọi món</a>
  </header>
  <div class="checkout-main">
  <section class="card checkout-table-picker" aria-label="Chọn bàn thanh toán">
    <div class="checkout-picker-heading"><span class="checkout-step">01</span><div><h3 class="title title-sm">Chọn hóa đơn</h3><p class="muted">Bàn có đơn gọi món chưa thanh toán sẽ xuất hiện ở đây.</p></div></div>
    <form id="checkout-table-form" method="get" action="<?= e($checkoutUrl()) ?>">
      <input type="hidden" name="page" value="checkout">
      <label class="field">
        <span class="field-label">Chọn bàn cần thanh toán</span>
        <select class="control" name="ban" required <?= $checkoutTables === [] && !$isPaid ? 'disabled' : '' ?>>
          <option value="">-- Chọn bàn đang phục vụ --</option>
          <?php if ($order && $order['trangThai'] === 'HOAN_TAT' && !array_filter($checkoutTables, static fn(array $table): bool => (string)$table['maBan'] === (string)$order['maBan'])): ?>
            <option value="<?= e((string)$order['maBan']) ?>" selected>
              Bàn <?= e((string)$order['tenBan']) ?> · Đơn vừa thanh toán
            </option>
          <?php endif; ?>
          <?php foreach ($checkoutTables as $table): ?>
            <option value="<?= e((string)$table['maBan']) ?>"
              <?= $requestedTableId === (string)$table['maBan'] || ($order && (string)$order['maBan'] === (string)$table['maBan']) ? 'selected' : '' ?>>
              Bàn <?= e((string)$table['tenBan']) ?><?= (int)$table['soDon'] > 1 ? ' · ' . (int)$table['soDon'] . ' đơn đang phục vụ' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit" class="btn btn-primary" <?= $checkoutTables === [] && !$isPaid ? 'disabled' : '' ?>>Xem hóa đơn</button>
    </form>
    <?php if ($checkoutTables === []): ?>
      <p class="muted">Chưa có đơn gọi món cần thanh toán trong phạm vi chi nhánh đang chọn.</p>
    <?php endif; ?>
  </section>

  <?php if (!$order): ?>
    <section class="card bill checkout-empty">
      <div class="checkout-empty-symbol" aria-hidden="true">₫</div>
      <span class="eyebrow">THANH TOÁN</span>
      <?php if ($hasOrderSelection): ?>
        <h2 class="title title-md">Không tìm thấy đơn đang phục vụ</h2>
        <p class="muted">Bàn đã chọn chưa có đơn cần thanh toán hoặc đơn không thuộc chi nhánh được phép truy cập.</p>
      <?php else: ?>
        <h2 class="title title-md"><?= $checkoutTables === [] ? 'Chưa có hóa đơn cần thanh toán' : 'Sẵn sàng kiểm tra hóa đơn' ?></h2>
        <p class="muted"><?= $checkoutTables === [] ? 'Bàn mới được tiếp nhận nhưng chưa gọi món sẽ chưa xuất hiện ở đây. Hãy vào Gọi món · POS, chọn bàn và gửi món xuống bếp.' : 'Chọn bàn ở phía trên rồi bấm “Xem hóa đơn” để kiểm tra danh sách món và tổng tiền.' ?></p>
      <?php endif; ?>
      <a class="btn btn-secondary" href="<?= e(page_url('pos')) ?>">Quay lại gọi món</a>
    </section>
  <?php else: ?>
    <section class="card bill" aria-label="Chi tiết hóa đơn">
      <div class="bill-brand">
        <div class="brand brand-dark"><span class="brand-mark">N</span><strong>F&amp;B Nodes</strong></div>
        <span class="muted"><?= e((string)$order['tenChiNhanh']) ?></span>
      </div>
      <div class="receipt-meta">
        <div><span>BÀN</span><strong><?= e((string)$order['tenBan']) ?></strong></div>
        <div><span>MÃ ĐƠN</span><strong><?= e((string)$order['maDon']) ?></strong></div>
        <div><span>THỜI GIAN</span><strong><?= e(date('H:i d/m/Y', strtotime((string)$order['thoiGianTao']))) ?></strong></div>
      </div>
      <?php if ($isPaid): ?>
        <div class="confirm-visual">
          <span class="success-check">✓</span>
          <div><h2 class="title title-sm">Thanh toán thành công</h2><span>Đơn đã hoàn tất. Bàn được giải phóng khi không còn đơn gọi món đang phục vụ.</span></div>
        </div>
      <?php endif; ?>
      <div class="receipt-items">
        <?php foreach ($orderItems as $item): ?>
          <div>
            <span><b><?= (int)$item['soLuong'] ?>×</b> <?= e((string)$item['tenMon']) ?></span>
            <strong><?= e($formatMoney((float)$item['thanhTien'])) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="receipt-total">
        <div><span>Tạm tính</span><strong><?= e($formatMoney($subtotal)) ?></strong></div>
        <div><span>VAT</span><strong><?= e($formatMoney($vat)) ?></strong></div>
        <div><span>Giảm giá</span><strong><?= e($formatMoney($discount)) ?></strong></div>
        <div class="grand"><span>Tổng thanh toán</span><strong><?= e($formatMoney($grandTotal)) ?></strong></div>
      </div>
      <?php if ($isPaid): ?>
        <button type="button" class="btn btn-secondary" onclick="window.print()">In hóa đơn</button>
        <a class="btn btn-primary" href="<?= e(page_url('pos')) ?>">Quay lại gọi món</a>
      <?php endif; ?>
    </section>

    <?php if ($orderItems === [] && !$isPaid): ?>
      <div class="alert-box" role="alert">
        <strong>Đơn chưa có món hợp lệ để thanh toán.</strong>
        <a class="btn btn-secondary" href="<?= e(page_url('pos')) ?>">Quay lại gọi món</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
  </div>
  <?php if (!$canPay): ?>
    <aside class="card checkout-guide" aria-label="Hướng dẫn thanh toán">
      <span class="eyebrow">QUY TRÌNH PHỤC VỤ</span>
      <h2 class="title title-md"><?= $isPaid ? 'Đã hoàn tất thanh toán' : 'Từ gọi món đến thanh toán' ?></h2>
      <ol class="checkout-guide-steps">
        <li><span>01</span><div><strong>Gọi món cho bàn</strong><p>Chọn bàn tại Gọi món · POS, thêm món và gửi bếp để tạo đơn.</p></div></li>
        <li><span>02</span><div><strong>Kiểm tra hóa đơn</strong><p>Chọn bàn cần thanh toán, đối chiếu số lượng món và tổng tiền với khách.</p></div></li>
        <li><span>03</span><div><strong>Nhận tiền và hoàn tất</strong><p>Nhập tiền khách đưa, kiểm tra tiền thối lại rồi xác nhận đã nhận tiền.</p></div></li>
      </ol>
      <p class="checkout-guide-note">Bàn đang phục vụ chưa chắc đã có hóa đơn: cần gửi ít nhất một món để có đơn gọi món.</p>
    </aside>
  <?php endif; ?>
  <?php if ($canPay): ?>
    <section class="payment">
        <span class="eyebrow">PHƯƠNG THỨC THANH TOÁN</span>
        <h2 class="title title-md">Khách muốn thanh toán thế nào?</h2>
        <?php if ($errorMessage !== ''): ?>
          <p class="alert-box" role="alert"><?= e($errorMessage) ?></p>
        <?php endif; ?>
        <div class="payment-methods" role="group" aria-label="Chọn phương thức thanh toán">
          <button type="button" class="card selected" data-checkout-method="cash" aria-pressed="true">
            <span class="payment-icon">₫</span><span><strong>Tiền mặt</strong><span>Nhận tiền trực tiếp</span></span>
          </button>
          <button type="button" class="card" data-checkout-method="qr" aria-pressed="false">
            <span class="payment-icon qr-mini"></span><span><strong>Quét mã QR</strong><span>Chuyển khoản ngân hàng</span></span>
          </button>
        </div>

        <form method="post" action="<?= e($checkoutUrl(['don' => (string)$order['maDon']])) ?>" class="checkout-payment-form">
          <input type="hidden" name="action" value="pay_order">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="maDon" value="<?= e((string)$order['maDon']) ?>">
          <input type="hidden" name="phuongThucThanhToan" value="cash" data-payment-method-value>
          <div class="card cash-box" data-cash-panel>
            <label class="field">
              <span class="field-label">Tiền khách đưa</span>
              <input class="control" type="number" name="tienKhachDua" min="<?= (int)ceil($grandTotal) ?>" step="1000" value="<?= (int)ceil($grandTotal) ?>" required data-cash-input>
            </label>
            <div><span>Tiền thối lại</span> <strong data-cash-change><?= e($formatMoney(0)) ?></strong></div>
          </div>
          <div class="card qr-box qr-panel" data-qr-panel hidden>
            <span class="badge badge-success qr-label">
              VIETQR CHUYỂN KHOẢN
            </span>

            <div class="qr-image-container">
              <img src="<?= e($vietQrUrl) ?>" alt="Mã QR VietinBank" class="qr-image">
            </div>

            <div class="summary-list qr-summary-list">
              <div><span>Ngân hàng</span><strong>VietinBank (<?= e($accountName) ?>)</strong></div>
              <div><span>Số tài khoản</span><strong><?= e($accountNo) ?></strong></div>
              <div><span>Nội dung chuyển khoản</span><strong class="qr-transfer-reference"><?= e($qrContent) ?></strong></div>
              <div><span>Số tiền chính xác</span><strong class="qr-amount"><?= e($formatMoney($grandTotal)) ?></strong></div>
            </div>

            <p class="muted qr-instructions">
              * Khách quét mã để chuyển khoản. Hệ thống tự động ghi nhận mã đơn <strong><?= e($qrContent) ?></strong>.
            </p>
          </div>
          <button type="submit" class="btn btn-primary verify-payment">Xác nhận đã nhận tiền &amp; Xuất hóa đơn</button>
        </form>
    </section>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="pos-layout"
     data-table-id="<?= e((string)($currentBan['maBan'] ?? '')) ?>"
     data-table-number="<?= e((string)($currentBan['tenBan'] ?? '')) ?>"
     data-order-id="<?= e((string)($activeOrder['maDon'] ?? '')) ?>"
     data-sent-items="<?= e(json_encode(array_map(static fn(array $item): array => [
        'id' => (string)$item['maMon'],
        'lineId' => (string)$item['maCTDon'],
        'name' => (string)$item['tenMon'],
        'price' => (float)$item['donGia'],
        'qty' => (int)$item['soLuong'],
        'note' => (string)($item['ghiChu'] ?? ''),
        'status' => (string)$item['trangThai'],
     ], $sentOrderItems), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)) ?>"
     data-csrf-token="<?= e(csrf_token()) ?>">

  <!-- CỘT TRÁI: THỰC ĐƠN VÀ LỌC MÓN -->
  <div class="catalog">
    <div class="catalog-tools">
      <div class="search-wide">
        <input class="control" id="posSearchDish" type="text" placeholder="Tìm món ăn, mã món..."/>
      </div>
      <div class="category-tabs">
        <button type="button" class="btn btn-primary" data-category="Tất cả">Tất cả</button>
        <?php if (!empty($danhSachNhom)): ?>
          <?php foreach ($danhSachNhom as $nhom): ?>
            <button type="button" class="btn btn-ghost" data-category="<?= e($nhom['tenNhom']) ?>">
              <?= e($nhom['tenNhom']) ?>
            </button>
          <?php endforeach; ?>
        <?php else: ?>
          <button type="button" class="btn btn-ghost" data-category="Khai vị">Khai vị</button>
          <button type="button" class="btn btn-ghost" data-category="Món chính">Món chính</button>
          <button type="button" class="btn btn-ghost" data-category="Tráng miệng">Tráng miệng</button>
          <button type="button" class="btn btn-ghost" data-category="Đồ uống">Đồ uống</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="menu-grid">
      <?php if (!empty($danhSachMon)): ?>
        <?php foreach ($danhSachMon as $mon):
          $code = get_food_code($mon['tenMon']);
        ?>
          <div class="card menu-card" data-dish-id="<?= e((string)$mon['maMon']) ?>" data-add-dish="<?= e($mon['tenMon']) ?>" data-dish-category="<?= e($mon['tenNhom'] ?? 'Món chính') ?>">
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
        <p class="muted no-dishes">Không có món ăn nào khả dụng tại chi nhánh.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- CỘT PHẢI: GIỎ HÀNG, ĐƠN HIỆN TẠI & THANH TOÁN -->
  <div class="cart">
    <div class="cart-head">
      <div>
        <span class="eyebrow">ĐƠN HÀNG</span>
        <h2 class="title title-md"><?= $currentBan ? 'Bàn ' . e((string)$currentBan['tenBan']) : 'Chưa chọn bàn' ?></h2>
      </div>
      <span class="badge <?= $activeOrder ? 'badge-info' : 'badge-success' ?>">
        <?= $activeOrder ? 'Đang phục vụ' : ($currentBan ? 'Bàn trống' : 'Chưa chọn bàn') ?>
      </span>
    </div>

    <div class="guest-line">
      <span>Khách lẻ · <?= $currentBan ? e((string)$currentBan['sucChua']) : '0' ?> khách</span>
      <button type="button" class="btn btn-ghost">Chỉnh sửa</button>
    </div>

    <label class="field">
      <span class="field-label">Chọn bàn</span>
      <select class="control" data-pos-table-select>
        <option value="" disabled <?= $currentBan ? '' : 'selected' ?>>Chọn bàn phục vụ</option>
      <?php foreach ($danhSachBan as $ban): ?>
          <option value="<?= e((string)$ban['maBan']) ?>" <?= $currentBan && (string)$ban['maBan'] === (string)$currentBan['maBan'] ? 'selected' : '' ?>>
          Bàn <?= e((string)$ban['tenBan']) ?>
          · <?= $ban['trangThai'] === 'DANG_PHUC_VU' ? 'Đang phục vụ' : 'Trống' ?>
        </option>
        <?php endforeach; ?>
      </select>
    </label>

    <!-- MÓN ĐÃ GỬI BẾP (SD-25/26) -->
    <div class="sent-order-items" aria-live="polite">
      <?php if ($sentOrderItems !== []): ?>
        <h3 class="title title-sm">Đã gửi bếp (<?= array_sum(array_column($sentOrderItems, 'soLuong')) ?> món)</h3>
        <?php foreach ($sentOrderItems as $item): ?>
          <div class="cart-item sent-cart-item" data-order-line="<?= e((string)$item['maCTDon']) ?>">
            <div class="item-count"><?= (int)$item['soLuong'] ?></div>
            <div class="item-detail">
              <strong><?= e((string)$item['tenMon']) ?></strong>
              <span><?= e(number_format((float)$item['thanhTien'], 0, ',', '.') . 'đ') ?></span>
              <?php if (!empty($item['ghiChu'])): ?>
                <small><?= e((string)$item['ghiChu']) ?></small>
              <?php endif; ?>
            </div>
            <div class="sent-item-actions">
              <span class="badge badge-info">
                <?= e([
                    'CHO_CHE_BIEN' => 'Đã gửi bếp',
                    'DANG_CHE_BIEN' => 'Đang nấu',
                    'HOAN_THANH' => 'Bếp xong',
                    'DA_PHUC_VU' => 'Đã phục vụ',
                ][$item['trangThai']] ?? 'Đã gửi bếp') ?>
              </span>
              <?php if ($item['trangThai'] === 'CHO_CHE_BIEN'): ?>
              <button type="button" class="btn btn-ghost btn-cancel-sent-dish"
                      data-line-id="<?= e((string)$item['maCTDon']) ?>"
                      data-dish-name="<?= e((string)$item['tenMon']) ?>"
                      aria-label="<?= e('Hủy ' . (string)$item['tenMon']) ?>">
                Hủy
              </button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php elseif ($activeOrder): ?>
        <p class="cart-empty">Đơn đang phục vụ chưa có món hợp lệ để hiển thị.</p>
      <?php endif; ?>
    </div>

    <!-- KHU VỰC 2: MÓN MỚI CHỌN CHUẨN BỊ GỬI THÊM -->
    <div class="cart-items"></div>

    <div class="cart-summary">
      <div>
        <span>Đã gửi bếp</span>
        <strong><?= e(number_format($sentOrderSubtotal, 0, ',', '.') . 'đ') ?></strong>
      </div>
      <div>
        <span>Món mới chọn</span>
        <strong><?= e(number_format(0, 0, ',', '.') . 'đ') ?></strong>
      </div>
      <div class="grand">
        <span>Tổng cộng dự kiến</span>
        <strong><?= e(number_format($sentOrderSubtotal, 0, ',', '.') . 'đ') ?></strong>
      </div>
    </div>

    <!-- NÚT GỬI BẾP TÁCH BẠCH RÕ RÀNG NHÃN & SỐ TIỀN -->
    <button type="button" class="btn btn-primary send-kitchen" id="btnGuiBep">
      <span data-send-action><?= $activeOrder ? 'Gửi thêm món vào bếp' : 'Gửi bếp' ?></span>
      <span data-send-summary>0 món · 0đ</span>
    </button>
  </div>
</div>



<!-- ==============================================================================
     MODAL HỘP THOẠI HỦY MÓN ĐANG CHỜ BẾP (SD-27)
============================================================================== -->
<div id="modalHuyMon" class="cancel-modal-overlay">
  <div class="cancel-modal-dialog">
    <h3 class="cancel-modal-title">Hủy món đang chờ bếp (SD-27)</h3>
    <p class="cancel-modal-description">
      Món hủy: <strong id="modalTenMonHuy" class="cancel-modal-dish"></strong>
    </p>

    <label class="cancel-modal-label">
      Lý do hủy bắt buộc (Kiểm toán & Báo cáo):
    </label>
    <select id="selectLyDoHuy" class="control cancel-modal-reason">
      <option value="Khách đổi ý không dùng nữa">Khách đổi ý không dùng nữa</option>
      <option value="Bếp báo hết nguyên liệu (NVL)">Bếp báo hết nguyên liệu (NVL)</option>
      <option value="Thời gian chế biến quá lâu">Thời gian chế biến quá lâu</option>
      <option value="Nhân viên phục vụ nhập nhầm món">Nhân viên phục vụ nhập nhầm món</option>
      <option value="other">-- Lý do khác (Nhập tay) --</option>
    </select>

    <textarea id="inputLyDoKhac" class="control cancel-modal-custom-reason" placeholder="Nhập lý do chi tiết..."></textarea>

    <div class="cancel-modal-actions">
      <button type="button" class="btn btn-ghost" id="btnDongModalHuy">Đóng</button>
      <button type="button" class="btn btn-primary cancel-modal-confirm" id="btnXacNhanHuyMon">Xác nhận hủy món</button>
    </div>
  </div>
</div>
<?php endif; ?>
