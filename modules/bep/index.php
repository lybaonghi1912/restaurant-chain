<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$user = current_user();
$branchId = current_branch_id();
$kitchenMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept_kitchen_order') {
    if (!$user || !can('kitchen.manage') || $branchId === null) {
        http_response_code(403);
        $kitchenMessage = ['type' => 'error', 'text' => 'Bạn không có quyền tiếp nhận món tại chi nhánh này.'];
    } elseif (!is_string($_POST['csrf_token'] ?? null)
        || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(403);
        $kitchenMessage = ['type' => 'error', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'];
    } else {
        $orderId = $_POST['maDon'] ?? null;
        if (!is_string($orderId) || $orderId === '' || strlen($orderId) > 20) {
            http_response_code(400);
            $kitchenMessage = ['type' => 'error', 'text' => 'Mã đơn không hợp lệ.'];
        } else {
            $conn = null;
            try {
                $conn = db();
                $conn->beginTransaction();

                $orderStatement = $conn->prepare(
                    "SELECT maDon, trangThai
                     FROM DonGoiMon
                     WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh
                     FOR UPDATE"
                );
                $orderStatement->execute([':maDon' => $orderId, ':maChiNhanh' => $branchId]);
                $order = $orderStatement->fetch();
                if (!$order || $order['trangThai'] !== 'DANG_PHUC_VU') {
                    throw new DomainException('Đơn không tồn tại, không thuộc chi nhánh của bạn hoặc không còn mở.');
                }

                $itemsStatement = $conn->prepare(
                    "SELECT maCTDon
                     FROM ChiTietDonMon
                     WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh
                       AND trangThai = 'CHO_CHE_BIEN'
                     ORDER BY maCTDon
                     FOR UPDATE"
                );
                $itemsStatement->execute([':maDon' => $orderId, ':maChiNhanh' => $branchId]);
                $pendingItems = $itemsStatement->fetchAll(PDO::FETCH_COLUMN);
                if ($pendingItems === []) {
                    throw new DomainException('Đơn không còn món đang chờ bếp tiếp nhận.');
                }

                $acceptStatement = $conn->prepare(
                    "UPDATE ChiTietDonMon
                     SET trangThai = 'DANG_CHE_BIEN',
                         maNhanVienBep = :maNhanVienBep,
                         gioTiepNhan = UTC_TIMESTAMP()
                     WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh
                       AND trangThai = 'CHO_CHE_BIEN'"
                );
                $acceptStatement->execute([
                    ':maNhanVienBep' => (string)$user['maNhanVien'],
                    ':maDon' => $orderId,
                    ':maChiNhanh' => $branchId,
                ]);
                if ($acceptStatement->rowCount() !== count($pendingItems)) {
                    throw new DomainException('Trạng thái món đã thay đổi. Vui lòng tải lại màn hình bếp.');
                }

                $conn->commit();
                $_SESSION['kds_message'] = [
                    'type' => 'success',
                    'text' => 'Đã tiếp nhận ' . count($pendingItems) . ' món. Các món này không còn ở trạng thái chờ bếp.',
                ];
                header('Location: ' . page_url('kds'), true, 303);
                exit;
            } catch (DomainException $error) {
                if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
                http_response_code(400);
                $kitchenMessage = ['type' => 'error', 'text' => $error->getMessage()];
            } catch (Throwable $error) {
                if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
                error_log('Kitchen order acceptance failed: ' . $error->getMessage());
                http_response_code(500);
                $kitchenMessage = ['type' => 'error', 'text' => 'Không thể tiếp nhận món lúc này. Vui lòng thử lại.'];
            }
        }
    }
}

if ($kitchenMessage === null && isset($_SESSION['kds_message'])) {
    $kitchenMessage = $_SESSION['kds_message'];
    unset($_SESSION['kds_message']);
}

$orders = [];
$pendingOrderCount = 0;
$processingOrderCount = 0;
if ($branchId !== null) {
    $statement = db()->prepare(
        "SELECT d.maDon, d.thoiGianGuiBep, b.tenBan,
                ct.maCTDon, ct.tenMon, ct.soLuong, ct.ghiChu, ct.trangThai
         FROM DonGoiMon d
         JOIN Ban b ON b.maBan = d.maBan AND b.maChiNhanh = d.maChiNhanh
         JOIN ChiTietDonMon ct ON ct.maDon = d.maDon AND ct.maChiNhanh = d.maChiNhanh
         WHERE d.maChiNhanh = :maChiNhanh
           AND d.trangThai = 'DANG_PHUC_VU'
           AND ct.trangThai IN ('CHO_CHE_BIEN', 'DANG_CHE_BIEN')
         ORDER BY d.thoiGianGuiBep, d.maDon, ct.gioGuiBep, ct.maCTDon"
    );
    $statement->execute([':maChiNhanh' => $branchId]);
    foreach ($statement->fetchAll() as $row) {
        $orderId = (string)$row['maDon'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id' => $orderId,
                'table' => (string)$row['tenBan'],
                'sentAt' => (string)$row['thoiGianGuiBep'],
                'items' => [],
            ];
        }
        $orders[$orderId]['items'][] = $row;
    }
}

foreach ($orders as $order) {
    if (array_filter($order['items'], static fn(array $item): bool => $item['trangThai'] === 'CHO_CHE_BIEN')) {
        $pendingOrderCount++;
    } else {
        $processingOrderCount++;
    }
}
?>
<div class="kds-screen">
  <?php if ($kitchenMessage): ?>
    <div class="<?= $kitchenMessage['type'] === 'success' ? 'success-banner' : 'badge badge-danger' ?>"
         role="<?= $kitchenMessage['type'] === 'success' ? 'status' : 'alert' ?>">
      <?= e($kitchenMessage['text']) ?>
    </div>
  <?php endif; ?>

  <div class="kds-tabs">
    <div>
      <button type="button" class="btn btn-primary" data-kds-filter="Tất cả">
        Tất cả <b><?= count($orders) ?></b>
      </button>
      <button type="button" class="btn btn-ghost" data-kds-filter="Chờ tiếp nhận">
        Chờ tiếp nhận <b><?= $pendingOrderCount ?></b>
      </button>
      <button type="button" class="btn btn-ghost" data-kds-filter="Đang chế biến">
        Đang chế biến <b><?= $processingOrderCount ?></b>
      </button>
      <button type="button" class="btn btn-ghost" data-kds-filter="Chờ trả món">
        Chờ trả món <b>0</b>
      </button>
    </div>
    <span class="badge badge-success">Dữ liệu trực tiếp</span>
  </div>

  <div class="ticket-grid">
    <?php foreach ($orders as $order): ?>
      <?php
      $hasPendingItems = (bool)array_filter(
          $order['items'],
          static fn(array $item): bool => $item['trangThai'] === 'CHO_CHE_BIEN'
      );
      ?>
      <section class="card ticket <?= $hasPendingItems ? 'ticket-warning' : 'ticket-success' ?>">
        <div class="ticket-head">
          <div>
            <span class="ticket-number"><?= e($order['id']) ?></span>
            <div>
              <h2 class="title title-md">Bàn <?= e($order['table']) ?></h2>
              <span>Gửi lúc <?= e($order['sentAt']) ?></span>
            </div>
          </div>
        </div>
        <div class="ticket-status">
          <span class="badge <?= $hasPendingItems ? 'badge-warning' : 'badge-info' ?>">
            <?= $hasPendingItems ? 'Chờ tiếp nhận' : 'Đang chế biến' ?>
          </span>
        </div>
        <div class="dish-list">
          <?php foreach ($order['items'] as $item): ?>
            <div>
              <span class="dish-qty"><?= (int)$item['soLuong'] ?>×</span>
              <div>
                <strong><?= e((string)$item['tenMon']) ?></strong>
                <?php if (is_string($item['ghiChu']) && $item['ghiChu'] !== ''): ?>
                  <small><?= e($item['ghiChu']) ?></small>
                <?php endif; ?>
                <small><?= $item['trangThai'] === 'CHO_CHE_BIEN' ? 'Chờ bếp tiếp nhận' : 'Đang chế biến' ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($hasPendingItems): ?>
          <form class="ticket-actions" method="post" action="<?= e(page_url('kds')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="accept_kitchen_order">
            <input type="hidden" name="maDon" value="<?= e($order['id']) ?>">
            <button type="submit" class="btn btn-primary">Tiếp nhận đơn</button>
          </form>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
    <?php if ($orders === []): ?>
      <p class="muted">Hiện không có món nào đang chờ bếp hoặc chế biến tại chi nhánh này.</p>
    <?php endif; ?>
  </div>
</div>
