
<?php
if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit('Truy cập không hợp lệ.');
}

require_permission('inventory.view');
$requisitionContent = (static function (): string {
    ob_start();
    require __DIR__ . '/sd28.php';
    return ob_get_clean();
})();
$pdo = db();

$maChuoi = 'FBN';
$maChiNhanh = current_branch_id();
$branchLabel = 'Toàn chuỗi';
if ($maChiNhanh !== null) {
    $branchStatement = $pdo->prepare('SELECT tenChiNhanh FROM ChiNhanh WHERE maChiNhanh=?');
    $branchStatement->execute([$maChiNhanh]);
    $branchLabel = (string)$branchStatement->fetchColumn();
}
$thongBao = '';
$loi = '';

$keyword = trim(is_string($_GET['keyword'] ?? null) ? $_GET['keyword'] : '');
function chuyenTrang(string $keyword = ''): void
{
    $url = '?page=inventory';
    if ($keyword !== '') {
        $url .= '&keyword=' . urlencode($keyword);
    }
    header('Location: ' . $url);
    exit;
}

/* XỬ LÝ THÊM, SỬA, XÓA */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_text('action') !== 'create_requisition') {
    require_permission('inventory.manage');
    require_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'them') {
            $maNVL = strtoupper(trim($_POST['maNVL'] ?? ''));
            $tenNVL = trim($_POST['tenNVL'] ?? '');
            $donVi = trim($_POST['donViTinh'] ?? '');

            if ($maNVL === '' || $tenNVL === '' || $donVi === '') {
                throw new RuntimeException('Vui lòng nhập đầy đủ thông tin.');
            }

            if (mb_strlen($maNVL) > 10) {
                throw new RuntimeException('Mã NVL không được quá 10 ký tự.');
            }

            if (mb_strlen($tenNVL) > 100) {
                throw new RuntimeException('Tên NVL không được quá 100 ký tự.');
            }

            if (mb_strlen($donVi) > 20) {
                throw new RuntimeException('Đơn vị tính không được quá 20 ký tự.');
            }

            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM NguyenVatLieu WHERE maNVL = ?"
            );
            $check->execute([$maNVL]);

            if ((int)$check->fetchColumn() > 0) {
                throw new RuntimeException('Mã nguyên vật liệu đã tồn tại.');
            }

            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM NguyenVatLieu
                 WHERE maChuoi = ? AND LOWER(tenNVL) = LOWER(?)"
            );
            $check->execute([$maChuoi, $tenNVL]);

            if ((int)$check->fetchColumn() > 0) {
                throw new RuntimeException('Tên nguyên vật liệu đã tồn tại.');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO NguyenVatLieu
                    (maNVL, maChuoi, tenNVL, donViTinh, trangThai)
                 VALUES (?, ?, ?, ?, 'HOAT_DONG')"
            );
            $stmt->execute([$maNVL, $maChuoi, $tenNVL, $donVi]);

            chuyenTrang($keyword);
        }

        if ($action === 'sua') {
            $maNVL = trim($_POST['maNVL'] ?? '');
            $tenNVL = trim($_POST['tenNVL'] ?? '');
            $donVi = trim($_POST['donViTinh'] ?? '');

            if ($maNVL === '' || $tenNVL === '' || $donVi === '') {
                throw new RuntimeException('Vui lòng nhập đầy đủ thông tin.');
            }

            if (mb_strlen($tenNVL) > 100 || mb_strlen($donVi) > 20) {
                throw new RuntimeException('Tên hoặc đơn vị tính vượt quá giới hạn.');
            }

            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM NguyenVatLieu
                 WHERE maChuoi = ? AND LOWER(tenNVL) = LOWER(?)
                 AND maNVL <> ?"
            );
            $check->execute([$maChuoi, $tenNVL, $maNVL]);

            if ((int)$check->fetchColumn() > 0) {
                throw new RuntimeException('Tên nguyên vật liệu đã được sử dụng.');
            }

            $stmt = $pdo->prepare(
                "UPDATE NguyenVatLieu
                 SET tenNVL = ?, donViTinh = ?
                 WHERE maNVL = ? AND maChuoi = ?"
            );
            $stmt->execute([$tenNVL, $donVi, $maNVL, $maChuoi]);

            chuyenTrang($keyword);
        }

        if ($action === 'xoa') {
            $maNVL = trim($_POST['maNVL'] ?? '');

            $stmt = $pdo->prepare(
                "UPDATE NguyenVatLieu SET trangThai='NGUNG'
                 WHERE maNVL = ? AND maChuoi = ?
                   AND NOT EXISTS (SELECT 1 FROM TonKhoTheoLo t WHERE t.maNVL=NguyenVatLieu.maNVL AND t.soLuongTon>0)
                   AND NOT EXISTS (SELECT 1 FROM ChiTietDieuChuyenKho ct JOIN DieuChuyenKho d ON d.maDieuChuyen=ct.maDieuChuyen
                       WHERE ct.maNVL=NguyenVatLieu.maNVL AND d.trangThai IN ('CHO_XAC_NHAN_NGUON','DA_DUYET'))"
            );
            $stmt->execute([$maNVL, $maChuoi]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Không thể ngừng nguyên vật liệu còn tồn kho hoặc có phiếu điều chuyển chưa kết thúc.');
            }

            chuyenTrang($keyword);
        }
    } catch (RuntimeException $ex) {
        $loi = $ex->getMessage();
    } catch (PDOException $ex) {
        $loi = 'Không thể thực hiện thao tác. NVL có thể đang được sử dụng trong dữ liệu khác.';
    }
}

/* LẤY DANH SÁCH NVL VÀ TỒN KHO */
$sql = "
    SELECT
        n.maNVL,
        n.tenNVL,
        n.donViTinh,
        COALESCE(SUM(
            CASE
                WHEN t.trangThai = 'HOAT_DONG'
                 AND t.hanSuDung >= CURDATE()
                THEN t.soLuongTon
                ELSE 0
            END
        ), 0) AS soLuongTon,
        MIN(
            CASE
                WHEN t.trangThai = 'HOAT_DONG'
                 AND t.soLuongTon > 0
                 AND t.hanSuDung >= CURDATE()
                THEN t.hanSuDung
                ELSE NULL
            END
        ) AS hanSuDung
    FROM NguyenVatLieu n
    LEFT JOIN TonKhoTheoLo t
        ON t.maNVL = n.maNVL
       AND (:allBranches = 1 OR t.maChiNhanh = :maChiNhanh)
    WHERE n.maChuoi = :maChuoi
      AND n.trangThai = 'HOAT_DONG'
";

$params = [
    ':allBranches' => $maChiNhanh === null ? 1 : 0,
    ':maChiNhanh' => $maChiNhanh,
    ':maChuoi' => $maChuoi
];

if ($keyword !== '') {
    $sql .= " AND (n.maNVL LIKE :ma1 OR n.tenNVL LIKE :ma2)";
    $params[':ma1'] = '%' . $keyword . '%';
    $params[':ma2'] = '%' . $keyword . '%';
}

$sql .= "
    GROUP BY n.maNVL, n.tenNVL, n.donViTinh
    ORDER BY n.maNVL
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$nguyenVatLieus = $stmt->fetchAll();

$tongNguyenVatLieu = count($nguyenVatLieus);
$soMatHangHetTon = 0;
$soMatHangGanHetHan = 0;

foreach ($nguyenVatLieus as $item) {
    if ((float)$item['soLuongTon'] <= 0) {
        $soMatHangHetTon++;
    }

    if (!empty($item['hanSuDung'])) {
        $han = strtotime($item['hanSuDung']);
        $homNay = strtotime(date('Y-m-d'));
        $baNgayToi = strtotime('+3 days', $homNay);

        if ($han >= $homNay && $han <= $baNgayToi) {
            $soMatHangGanHetHan++;
        }
    }
}
?>

<div class="screen-content">
    <?= $requisitionContent ?>

    <?php if ($loi !== ''): ?>
        <div class="card" style="padding:12px;color:#b42318;margin-bottom:12px;">
            <?= e($loi) ?>
        </div>
    <?php endif; ?>

    <div class="stat-row">
        <div class="card stat">
            <div class="stat-top"><span class="muted">Tổng mặt hàng</span></div>
            <div class="stat-value"><?= $tongNguyenVatLieu ?></div>
            <span class="muted">Nguyên vật liệu đang hoạt động</span>
        </div>

        <div class="card stat">
            <div class="stat-top"><span class="muted">Chưa có tồn</span></div>
            <div class="stat-value"><?= $soMatHangHetTon ?></div>
            <span class="muted">Mặt hàng có số lượng tồn bằng 0</span>
        </div>

        <div class="card stat">
            <div class="stat-top"><span class="muted">Gần hết hạn</span></div>
            <div class="stat-value"><?= $soMatHangGanHetHan ?></div>
            <span class="muted">Trong 3 ngày tới</span>
        </div>
    </div>

    <div class="card data-card">

        <div class="data-toolbar">
            <form method="GET" class="toolbar-actions">
                <input type="hidden" name="page" value="inventory">
                <input
                    class="control"
                    type="text"
                    name="keyword"
                    placeholder="Tìm mã, tên NVL..."
                    value="<?= e($keyword) ?>"
                >
                <button type="submit" class="btn btn-primary">
                    Tìm kiếm
                </button>
            </form>
        </div>

        <details class="card" style="padding:14px;margin:12px 0;">
            <summary style="cursor:pointer;font-weight:bold;">
                + Thêm nguyên vật liệu
            </summary>

            <form method="POST" style="display:grid;gap:10px;margin-top:12px;">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="them">

                <label>Mã nguyên vật liệu</label>
                <input class="control" name="maNVL" maxlength="10"
                       placeholder="Ví dụ: NVL009" required>

                <label>Tên nguyên vật liệu</label>
                <input class="control" name="tenNVL" maxlength="100"
                       placeholder="Nhập tên NVL" required>

                <label>Đơn vị tính</label>
                <input class="control" name="donViTinh" maxlength="20"
                       placeholder="Ví dụ: kg, lit, hộp" required>

                <button class="btn btn-primary" type="submit">
                    Lưu nguyên vật liệu
                </button>
            </form>
        </details>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mã NVL</th>
                        <th>Nguyên vật liệu</th>
                        <th>Chi nhánh</th>
                        <th>Tồn kho</th>
                        <th>Đơn vị</th>
                        <th>Hạn sử dụng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($nguyenVatLieus): ?>
                    <?php foreach ($nguyenVatLieus as $item): ?>
                        <?php
                        $soLuong = (float)$item['soLuongTon'];
                        $han = $item['hanSuDung'];

                        if ($soLuong <= 0) {
                            $trangThai = 'Chưa có tồn';
                            $class = 'badge-warning';
                        } elseif ($han && strtotime($han) <= strtotime('+3 days')) {
                            $trangThai = 'Gần hết hạn';
                            $class = 'badge-warning';
                        } else {
                            $trangThai = 'Còn hàng';
                            $class = 'badge-success';
                        }
                        ?>

                        <tr>
                            <td><?= e($item['maNVL']) ?></td>
                            <td><?= e($item['tenNVL']) ?></td>
                            <td><?= e($branchLabel) ?></td>
                            <td><?= number_format($soLuong, 3, ',', '.') ?></td>
                            <td><?= e($item['donViTinh']) ?></td>
                            <td><?= $han ? date('d/m/Y', strtotime($han)) : '—' ?></td>
                            <td>
                                <span class="badge <?= $class ?>">
                                    <?= e($trangThai) ?>
                                </span>
                            </td>
                            <td>
                                <details>
                                    <summary style="cursor:pointer;">•••</summary>

                                    <form method="POST" style="display:grid;gap:6px;padding:8px;">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="sua">
                                        <input type="hidden" name="maNVL" value="<?= e($item['maNVL']) ?>">

                                        <label>Tên NVL</label>
                                        <input class="control" name="tenNVL"
                                               maxlength="100"
                                               value="<?= e($item['tenNVL']) ?>" required>

                                        <label>Đơn vị tính</label>
                                        <input class="control" name="donViTinh"
                                               maxlength="20"
                                               value="<?= e($item['donViTinh']) ?>" required>

                                        <button class="btn btn-primary" type="submit">
                                            Lưu chỉnh sửa
                                        </button>
                                    </form>

                                    <form method="POST"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa nguyên vật liệu này?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="xoa">
                                        <input type="hidden" name="maNVL" value="<?= e($item['maNVL']) ?>">
                                        <button class="btn btn-ghost" type="submit">
                                            Xóa nguyên vật liệu
                                        </button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;">
                            Không tìm thấy nguyên vật liệu.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Hiển thị <?= count($nguyenVatLieus) ?> nguyên vật liệu
        </div>
    </div>
</div>
