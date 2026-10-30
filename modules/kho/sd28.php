<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$user = current_user();
$branchId = current_branch_id();
$inventoryMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_requisition') {
    if (!$user || $user['maVaiTro'] !== 'KHO' || !can('requisition.create') || $branchId === null) {
        http_response_code(403);
        $inventoryMessage = ['type' => 'error', 'text' => 'Chỉ nhân viên kho được tạo yêu cầu tại chi nhánh của mình.'];
    } elseif (!is_string($_POST['csrf_token'] ?? null)
        || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(403);
        $inventoryMessage = ['type' => 'error', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'];
    } else {
        $proposalId = $_POST['maPhieu'] ?? null;
        if (!is_string($proposalId) || $proposalId === '' || strlen($proposalId) > 20) {
            http_response_code(400);
            $inventoryMessage = ['type' => 'error', 'text' => 'Mã phiếu đề xuất không hợp lệ.'];
        } else {
            $conn = null;
            try {
                $conn = db();
                $conn->beginTransaction();

                $proposalStatement = $conn->prepare(
                    "SELECT maPhieu
                     FROM PhieuDeXuat
                     WHERE maPhieu = :maPhieu AND maChiNhanh = :maChiNhanh
                       AND trangThai = 'CHO_XU_LY'
                     FOR UPDATE"
                );
                $proposalStatement->execute([':maPhieu' => $proposalId, ':maChiNhanh' => $branchId]);
                if (!$proposalStatement->fetch()) {
                    throw new DomainException('Phiếu không tồn tại tại chi nhánh này hoặc đã được xử lý.');
                }

                $detailsStatement = $conn->prepare(
                    "SELECT maNVL
                     FROM ChiTietDeXuat
                     WHERE maPhieu = :maPhieu
                     ORDER BY maNVL
                     FOR UPDATE"
                );
                $detailsStatement->execute([':maPhieu' => $proposalId]);
                if ($detailsStatement->fetchColumn() === false) {
                    throw new DomainException('Phiếu đề xuất chưa có nguyên vật liệu nên chưa thể tạo yêu cầu.');
                }

                $requestId = 'YN' . bin2hex(random_bytes(8));
                $createRequest = $conn->prepare(
                    "INSERT INTO YeuCauNhapKho (maYeuCau, maPhieu, maChiNhanh, maNguoiTao, ngayTao, trangThai)
                     VALUES (:maYeuCau, :maPhieu, :maChiNhanh, :maNguoiTao, UTC_TIMESTAMP(), 'CHO_XU_LY')"
                );
                $createRequest->execute([
                    ':maYeuCau' => $requestId,
                    ':maPhieu' => $proposalId,
                    ':maChiNhanh' => $branchId,
                    ':maNguoiTao' => (string)$user['maNhanVien'],
                ]);

                $updateProposal = $conn->prepare(
                    "UPDATE PhieuDeXuat
                     SET trangThai = 'DA_TAO_YEU_CAU'
                     WHERE maPhieu = :maPhieu AND maChiNhanh = :maChiNhanh
                       AND trangThai = 'CHO_XU_LY'"
                );
                $updateProposal->execute([':maPhieu' => $proposalId, ':maChiNhanh' => $branchId]);
                if ($updateProposal->rowCount() !== 1) {
                    throw new DomainException('Phiếu đã được xử lý bởi một thao tác khác. Vui lòng tải lại.');
                }

                $conn->commit();
                $_SESSION['inventory_message'] = [
                    'type' => 'success',
                    'text' => 'Đã tạo yêu cầu nhập nguyên vật liệu ' . $requestId . ' và chuyển phiếu sang Đã tạo yêu cầu.',
                ];
                header('Location: ' . page_url('inventory'), true, 303);
                exit;
            } catch (DomainException $error) {
                if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
                http_response_code(400);
                $inventoryMessage = ['type' => 'error', 'text' => $error->getMessage()];
            } catch (Throwable $error) {
                if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
                error_log('Inventory requisition creation failed: ' . $error->getMessage());
                http_response_code(500);
                $inventoryMessage = ['type' => 'error', 'text' => 'Không thể tạo yêu cầu nhập kho lúc này. Vui lòng thử lại.'];
            }
        }
    }
}

if ($inventoryMessage === null && isset($_SESSION['inventory_message'])) {
    $inventoryMessage = $_SESSION['inventory_message'];
    unset($_SESSION['inventory_message']);
}

$pendingProposals = [];
$createdRequisitions = [];
if ($branchId !== null && $user && $user['maVaiTro'] === 'KHO') {
    $pendingStatement = db()->prepare(
        "SELECT p.maPhieu, p.ngayLap, p.lyDo, COUNT(cd.maNVL) AS soMatHang
         FROM PhieuDeXuat p
         LEFT JOIN ChiTietDeXuat cd ON cd.maPhieu = p.maPhieu
         WHERE p.maChiNhanh = :maChiNhanh AND p.trangThai = 'CHO_XU_LY'
         GROUP BY p.maPhieu, p.ngayLap, p.lyDo
         ORDER BY p.ngayLap, p.maPhieu"
    );
    $pendingStatement->execute([':maChiNhanh' => $branchId]);
    $pendingProposals = $pendingStatement->fetchAll();
}

$requestsSql = "SELECT y.maYeuCau, y.maPhieu, y.ngayTao, y.trangThai, c.tenChiNhanh
                FROM YeuCauNhapKho y
                JOIN ChiNhanh c ON c.maChiNhanh = y.maChiNhanh";
$requestsParams = [];
if ($branchId !== null && (!$user || $user['maVaiTro'] !== 'CHU_CHUOI')) {
    $requestsSql .= ' WHERE y.maChiNhanh = :maChiNhanh';
    $requestsParams[':maChiNhanh'] = $branchId;
}
$requestsSql .= ' ORDER BY y.ngayTao DESC, y.maYeuCau DESC LIMIT 20';
$requestsStatement = db()->prepare($requestsSql);
$requestsStatement->execute($requestsParams);
$createdRequisitions = $requestsStatement->fetchAll();
?>
<?php if ($inventoryMessage): ?>
  <p class="<?= $inventoryMessage['type'] === 'success' ? 'success-banner' : 'badge badge-danger' ?>"
     role="<?= $inventoryMessage['type'] === 'success' ? 'status' : 'alert' ?>">
    <?= e($inventoryMessage['text']) ?>
  </p>
<?php endif; ?>
<?php if ($user && $user['maVaiTro'] === 'KHO' && $pendingProposals !== []): ?>
<section class="card data-card inventory-requisition-card">
  <div class="section-head">
    <div>
      <h2 class="title title-sm">SD-28 · Lập yêu cầu nhập nguyên vật liệu</h2>
      <span class="muted">Tạo yêu cầu từ phiếu đề xuất đang chờ xử lý của chi nhánh này.</span>
    </div>
  </div>
    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr><th>Mã phiếu</th><th>Ngày lập</th><th>Lý do</th><th>Số mặt hàng</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($pendingProposals as $proposal): ?>
          <tr>
            <td><?= e((string)$proposal['maPhieu']) ?></td>
            <td><?= e((string)$proposal['ngayLap']) ?></td>
            <td><?= e((string)$proposal['lyDo']) ?></td>
            <td><?= (int)$proposal['soMatHang'] ?></td>
            <td>
              <?php if ($user && $user['maVaiTro'] === 'KHO'): ?>
                <form method="post" action="<?= e(page_url('inventory')) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="create_requisition">
                  <input type="hidden" name="maPhieu" value="<?= e((string)$proposal['maPhieu']) ?>">
                  <button class="btn btn-primary" type="submit"
                          <?= (int)$proposal['soMatHang'] < 1 ? 'disabled' : '' ?>>
                    Tạo yêu cầu
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
</section>
<?php endif; ?>

<?php if ($createdRequisitions !== [] || ($user && $user['maVaiTro'] === 'CHU_CHUOI')): ?>
<section class="card data-card inventory-requisition-card">
  <div class="section-head">
    <div>
      <h2 class="title title-sm"><?= $user && $user['maVaiTro'] === 'CHU_CHUOI' ? 'Yêu cầu nhập kho chờ xử lý' : 'Yêu cầu đã gửi Chủ chuỗi' ?></h2>
      <span class="muted"><?= $user && $user['maVaiTro'] === 'CHU_CHUOI' ? 'Danh sách yêu cầu từ tất cả chi nhánh trong chuỗi.' : 'Theo dõi các yêu cầu nhập từ chi nhánh này.' ?></span>
    </div>
  </div>
  <?php if ($createdRequisitions === []): ?>
    <p class="muted">Chưa có yêu cầu nhập nguyên vật liệu nào được gửi lên.</p>
  <?php else: ?>
    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Mã yêu cầu</th><th>Mã phiếu</th><th>Chi nhánh</th><th>Ngày tạo</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach ($createdRequisitions as $request): ?>
          <tr>
            <td><?= e((string)$request['maYeuCau']) ?></td>
            <td><?= e((string)$request['maPhieu']) ?></td>
            <td><?= e((string)$request['tenChiNhanh']) ?></td>
            <td><?= e((string)$request['ngayTao']) ?></td>
            <td>
              <span class="badge <?= $request['trangThai'] === 'CHO_XU_LY' ? 'badge-info' : 'badge-success' ?>">
                <?= $request['trangThai'] === 'CHO_XU_LY' ? 'Chờ xử lý' : 'Đã lập phương án' ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>
