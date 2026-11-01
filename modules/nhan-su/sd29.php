<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$user = current_user();
$branchId = current_branch_id();
$transferMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review_staff_transfer_request') {
    if (!$user || $user['maVaiTro'] !== 'CHU_CHUOI' || !can('staff.transfer.approve')) {
        http_response_code(403);
        $transferMessage = ['type' => 'error', 'text' => 'Chỉ Chủ chuỗi được duyệt hoặc từ chối yêu cầu điều chuyển nhân viên.'];
    } elseif (!is_string($_POST['csrf_token'] ?? null)
        || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(403);
        $transferMessage = ['type' => 'error', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'];
    } else {
        $requestId = $_POST['maYeuCauDC'] ?? null;
        $decision = $_POST['quyetDinh'] ?? null;
        $rejectionReason = trim(is_string($_POST['lyDoTuChoi'] ?? null) ? $_POST['lyDoTuChoi'] : '');

        if (!is_string($requestId) || $requestId === '' || strlen($requestId) > 20
            || !is_string($decision) || !in_array($decision, ['DA_DUYET', 'TU_CHOI'], true)
            || ($decision === 'TU_CHOI' && ($rejectionReason === '' || mb_strlen($rejectionReason, 'UTF-8') > 500))) {
            http_response_code(400);
            $transferMessage = [
                'type' => 'error',
                'text' => 'Thông tin xử lý không hợp lệ. Khi từ chối, cần nhập lý do tối đa 500 ký tự.',
            ];
        } else {
            $conn = null;
            try {
                $conn = db();
                $conn->beginTransaction();

                $requestStatement = $conn->prepare(
                    "SELECT maYeuCauDC, trangThai
                     FROM YeuCauDieuChuyen
                     WHERE maYeuCauDC = :maYeuCauDC
                     FOR UPDATE"
                );
                $requestStatement->execute([':maYeuCauDC' => $requestId]);
                $request = $requestStatement->fetch();
                if (!$request || $request['trangThai'] !== 'CHO_DUYET') {
                    throw new DomainException('Yêu cầu không tồn tại hoặc đã được xử lý trước đó.');
                }

                $updateStatement = $conn->prepare(
                    "UPDATE YeuCauDieuChuyen
                     SET trangThai = :trangThai,
                         maNguoiDuyet = :maNguoiDuyet,
                         ngayDuyet = NOW(),
                         lyDoTuChoi = :lyDoTuChoi
                     WHERE maYeuCauDC = :maYeuCauDC AND trangThai = 'CHO_DUYET'"
                );
                $updateStatement->execute([
                    ':trangThai' => $decision,
                    ':maNguoiDuyet' => (string)$user['maNhanVien'],
                    ':lyDoTuChoi' => $decision === 'TU_CHOI' ? $rejectionReason : null,
                    ':maYeuCauDC' => $requestId,
                ]);
                if ($updateStatement->rowCount() !== 1) {
                    throw new DomainException('Yêu cầu vừa được xử lý bởi một thao tác khác. Vui lòng tải lại.');
                }

                $conn->commit();
                $_SESSION['staff_transfer_message'] = [
                    'type' => 'success',
                    'text' => $decision === 'DA_DUYET'
                        ? 'Đã duyệt yêu cầu điều chuyển nhân viên. Thao tác này chưa tự chuyển nhân viên giữa các chi nhánh.'
                        : 'Đã từ chối yêu cầu điều chuyển nhân viên.',
                ];
                header('Location: ' . page_url('staff'), true, 303);
                exit;
            } catch (DomainException $error) {
                if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
                http_response_code(409);
                $transferMessage = ['type' => 'error', 'text' => $error->getMessage()];
            } catch (Throwable $error) {
                if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
                error_log('Staff transfer request review failed: ' . $error->getMessage());
                http_response_code(500);
                $transferMessage = [
                    'type' => 'error',
                    'text' => 'Không thể xử lý yêu cầu điều chuyển lúc này. Vui lòng thử lại.',
                ];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_staff_transfer_request') {
    if (!$user || $user['maVaiTro'] !== 'QUAN_LY' || !can('staff.transfer.request') || $branchId === null) {
        http_response_code(403);
        $transferMessage = ['type' => 'error', 'text' => 'Chỉ quản lý chi nhánh mới được tạo yêu cầu điều chuyển nhân viên.'];
    } elseif (!is_string($_POST['csrf_token'] ?? null)
        || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(403);
        $transferMessage = ['type' => 'error', 'text' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'];
    } else {
        $position = trim(is_string($_POST['viTri'] ?? null) ? $_POST['viTri'] : '');
        $quantityInput = $_POST['soLuong'] ?? null;
        $quantity = is_string($quantityInput)
            ? filter_var($quantityInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]])
            : false;
        $startInput = $_POST['thoiGianBatDau'] ?? null;
        $endInput = $_POST['thoiGianKetThuc'] ?? null;
        $reason = trim(is_string($_POST['lyDo'] ?? null) ? $_POST['lyDo'] : '');

        $start = is_string($startInput)
            ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $startInput)
            : false;
        $end = is_string($endInput)
            ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $endInput)
            : false;

        if ($position === '' || mb_strlen($position, 'UTF-8') > 100
            || $quantity === false
            || $start === false || $end === false
            || $start->format('Y-m-d\TH:i') !== $startInput
            || $end->format('Y-m-d\TH:i') !== $endInput
            || $end <= $start
            || $reason === '' || mb_strlen($reason, 'UTF-8') > 500) {
            http_response_code(400);
            $transferMessage = [
                'type' => 'error',
                'text' => 'Vui lòng nhập vị trí, số lượng nguyên dương, thời gian hợp lệ (kết thúc sau bắt đầu) và lý do không quá 500 ký tự.',
            ];
        } else {
            try {
                $requestId = 'DC' . bin2hex(random_bytes(8));
                $statement = db()->prepare(
                    "INSERT INTO YeuCauDieuChuyen
                        (maYeuCauDC, maChiNhanh, viTri, soLuong, thoiGianBatDau,
                         thoiGianKetThuc, lyDo, maNguoiGui, ngayGui, trangThai)
                     VALUES
                        (:maYeuCauDC, :maChiNhanh, :viTri, :soLuong, :thoiGianBatDau,
                         :thoiGianKetThuc, :lyDo, :maNguoiGui, NOW(), 'CHO_DUYET')"
                );
                $statement->execute([
                    ':maYeuCauDC' => $requestId,
                    ':maChiNhanh' => $branchId,
                    ':viTri' => $position,
                    ':soLuong' => $quantity,
                    ':thoiGianBatDau' => $start->format('Y-m-d H:i:s'),
                    ':thoiGianKetThuc' => $end->format('Y-m-d H:i:s'),
                    ':lyDo' => $reason,
                    ':maNguoiGui' => (string)$user['maNhanVien'],
                ]);
                $_SESSION['staff_transfer_message'] = [
                    'type' => 'success',
                    'text' => 'Đã gửi yêu cầu ' . $requestId . ' lên Chủ chuỗi ở trạng thái Chờ duyệt.',
                ];
                header('Location: ' . page_url('staff'), true, 303);
                exit;
            } catch (Throwable $error) {
                error_log('Staff transfer request creation failed: ' . $error->getMessage());
                http_response_code(500);
                $transferMessage = [
                    'type' => 'error',
                    'text' => 'Không thể lưu yêu cầu điều chuyển lúc này. Vui lòng thử lại.',
                ];
            }
        }
    }
}

if ($transferMessage === null && isset($_SESSION['staff_transfer_message'])) {
    $transferMessage = $_SESSION['staff_transfer_message'];
    unset($_SESSION['staff_transfer_message']);
}

$staffTransferRequests = [];
{
    $requestsSql = "SELECT y.maYeuCauDC, y.viTri, y.soLuong, y.thoiGianBatDau, y.thoiGianKetThuc,
                           y.lyDo, y.ngayGui, y.trangThai, y.ngayDuyet, y.lyDoTuChoi,
                           n.hoTen, c.tenChiNhanh, reviewer.hoTen AS tenNguoiDuyet
         FROM YeuCauDieuChuyen y
         JOIN NhanVien n ON n.maNhanVien = y.maNguoiGui
         LEFT JOIN NhanVien reviewer ON reviewer.maNhanVien = y.maNguoiDuyet
         JOIN ChiNhanh c ON c.maChiNhanh = y.maChiNhanh";
    $requestsParams = [];
    if ($branchId !== null) {
        $requestsSql .= ' WHERE y.maChiNhanh = :maChiNhanh';
        $requestsParams[':maChiNhanh'] = $branchId;
    }
    $requestsSql .= ' ORDER BY y.ngayGui DESC, y.maYeuCauDC DESC LIMIT 20';
    $requestsStatement = db()->prepare($requestsSql);
    $requestsStatement->execute($requestsParams);
    $staffTransferRequests = $requestsStatement->fetchAll();
}
?>
<?php if ($transferMessage): ?>
  <p class="<?= $transferMessage['type'] === 'success' ? 'success-banner' : 'badge badge-danger' ?>"
     role="<?= $transferMessage['type'] === 'success' ? 'status' : 'alert' ?>">
    <?= e($transferMessage['text']) ?>
  </p>
<?php endif; ?>

<?php if ($user && $user['maVaiTro'] === 'QUAN_LY'): ?>
<section class="card data-card staff-transfer-card">
  <div class="section-head">
    <div>
      <h2 class="title title-sm">Yêu cầu điều chuyển nhân viên</h2>
      <span class="muted">Tạo yêu cầu hỗ trợ nhân sự; yêu cầu sẽ chờ Chủ chuỗi xử lý.</span>
    </div>
  </div>
  <form method="post" action="<?= e(page_url('staff')) ?>" class="staff-transfer-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create_staff_transfer_request">
    <div class="form-grid">
      <label class="field">
        <span class="field-label">Vị trí cần hỗ trợ</span>
        <input class="control" name="viTri" maxlength="100" required>
      </label>
      <label class="field">
        <span class="field-label">Số lượng nhân viên</span>
        <input class="control" type="number" name="soLuong" min="1" step="1" required>
      </label>
      <label class="field">
        <span class="field-label">Thời gian bắt đầu</span>
        <input class="control" type="datetime-local" name="thoiGianBatDau" required>
      </label>
      <label class="field">
        <span class="field-label">Thời gian kết thúc</span>
        <input class="control" type="datetime-local" name="thoiGianKetThuc" required>
      </label>
      <label class="field">
        <span class="field-label">Lý do</span>
        <textarea class="control" name="lyDo" maxlength="500" required></textarea>
      </label>
    </div>
    <button class="btn btn-primary" type="submit">Gửi yêu cầu</button>
  </form>
</section>
<?php endif; ?>

<?php if ($staffTransferRequests !== []): ?>
<section class="card data-card staff-transfer-card">
  <div class="section-head">
    <div>
      <h2 class="title title-sm">Yêu cầu hỗ trợ nhân sự</h2>
      <span class="muted">Yêu cầu chờ Chủ chuỗi xử lý; duyệt không tự điều chuyển nhân viên.</span>
    </div>
  </div>
    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Mã yêu cầu</th><th>Chi nhánh</th><th>Vị trí</th><th>Số lượng</th>
            <th>Thời gian</th><th>Lý do</th><th>Người gửi</th><th>Trạng thái</th>
            <th>Người xử lý</th><th>Thời điểm xử lý</th><th>Lý do từ chối</th>
            <?php if ($user && $user['maVaiTro'] === 'CHU_CHUOI'): ?><th>Thao tác</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($staffTransferRequests as $request): ?>
          <tr>
            <td><?= e((string)$request['maYeuCauDC']) ?></td>
            <td><?= e((string)$request['tenChiNhanh']) ?></td>
            <td><?= e((string)$request['viTri']) ?></td>
            <td><?= (int)$request['soLuong'] ?></td>
            <td><?= e((string)$request['thoiGianBatDau']) ?> – <?= e((string)$request['thoiGianKetThuc']) ?></td>
            <td><?= e((string)$request['lyDo']) ?></td>
            <td><?= e((string)$request['hoTen']) ?></td>
            <td>
              <span class="badge <?= match ($request['trangThai']) {
                  'DA_DUYET' => 'badge-success',
                  'TU_CHOI' => 'badge-danger',
                  default => 'badge-warning',
              } ?>">
                <?= match ($request['trangThai']) {
                    'DA_DUYET' => 'Đã duyệt',
                    'TU_CHOI' => 'Từ chối',
                    default => 'Chờ duyệt',
                } ?>
              </span>
            </td>
            <td><?= e((string)($request['tenNguoiDuyet'] ?? '—')) ?></td>
            <td><?= e((string)($request['ngayDuyet'] ?? '—')) ?></td>
            <td><?= e((string)($request['lyDoTuChoi'] ?? '—')) ?></td>
            <?php if ($user && $user['maVaiTro'] === 'CHU_CHUOI'): ?>
              <td>
                <?php if ($request['trangThai'] === 'CHO_DUYET'): ?>
                  <div class="staff-transfer-actions">
                    <form method="post" action="<?= e(page_url('staff')) ?>">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="review_staff_transfer_request">
                      <input type="hidden" name="maYeuCauDC" value="<?= e((string)$request['maYeuCauDC']) ?>">
                      <input type="hidden" name="quyetDinh" value="DA_DUYET">
                      <button class="btn btn-primary" type="submit">Duyệt yêu cầu</button>
                    </form>
                    <form method="post" action="<?= e(page_url('staff')) ?>">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="review_staff_transfer_request">
                      <input type="hidden" name="maYeuCauDC" value="<?= e((string)$request['maYeuCauDC']) ?>">
                      <input type="hidden" name="quyetDinh" value="TU_CHOI">
                      <input class="control" name="lyDoTuChoi" maxlength="500" placeholder="Lý do từ chối" required>
                      <button class="btn btn-danger" type="submit">Từ chối</button>
                    </form>
                  </div>
                <?php else: ?>
                  <span class="muted">Đã xử lý</span>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
</section>
<?php endif; ?>
