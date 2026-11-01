
<?php
if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit;
}

require_permission('menu.view');

$pdo = db();
$maChuoi = 'FBN';
$error = '';

function mon_an_redirect(string $message): never
{
    redirect_to('index.php?page=menu&message=' . rawurlencode($message));
}

function mon_an_duplicate(PDO $pdo, string $maNhom, string $tenMon, ?string $exclude = null): bool
{
    $sql = "SELECT tenMon FROM MonAn WHERE maNhom = ?";
    $params = [$maNhom];

    if ($exclude !== null) {
        $sql .= " AND maMon <> ?";
        $params[] = $exclude;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $target = mb_strtolower(preg_replace('/\s+/u', ' ', trim($tenMon)));

    foreach ($stmt->fetchAll() as $row) {
        $existing = mb_strtolower(preg_replace('/\s+/u', ' ', trim($row['tenMon'])));
        if ($existing === $target) {
            return true;
        }
    }

    return false;
}

function mon_an_generate_id(PDO $pdo): string
{
    for ($i = 0; $i < 10; $i++) {
        $id = 'MA' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT 1 FROM MonAn WHERE maMon = ?');
        $stmt->execute([$id]);

        if (!$stmt->fetchColumn()) {
            return $id;
        }
    }

    throw new RuntimeException('Không thể tạo mã món ăn. Vui lòng thử lại.');
}

// Xử lý thêm, cập nhật và ngừng kinh doanh.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('menu.manage');
    require_csrf();

    $action = post_text('action');

    try {
        if ($action === 'add' || $action === 'update') {
            $maMon = trim(post_text('maMon'));
            $maNhom = trim(post_text('maNhom'));
            $tenMon = trim(post_text('tenMon'));
            $donGia = trim(post_text('donGia'));
            $hinhAnh = trim(post_text('hinhAnh'));
            $moTa = trim(post_text('moTa'));

            if ($tenMon === '' || mb_strlen($tenMon) > 100) {
                throw new RuntimeException('Tên món không được để trống và tối đa 100 ký tự.');
            }

            if (!preg_match('/^\d{1,16}(\.\d{1,2})?$/', $donGia)) {
                throw new RuntimeException('Đơn giá phải là số không âm, tối đa 2 chữ số thập phân.');
            }

            if (mb_strlen($hinhAnh) > 255 || mb_strlen($moTa) > 500) {
                throw new RuntimeException('Hình ảnh tối đa 255 ký tự, mô tả tối đa 500 ký tự.');
            }

            // Kiểm tra nhóm món còn hoạt động.
            $groupStmt = $pdo->prepare(
                "SELECT maNhom FROM NhomMon
                 WHERE maNhom = ? AND maChuoi = ? AND trangThai = 'HOAT_DONG'"
            );
            $groupStmt->execute([$maNhom, $maChuoi]);

            if (!$groupStmt->fetch()) {
                throw new RuntimeException('Nhóm món không tồn tại hoặc đã ngừng hoạt động.');
            }

            // Khi cập nhật, kiểm tra món có tồn tại.
            if ($action === 'update') {
                if ($maMon === '') {
                    throw new RuntimeException('Mã món không hợp lệ.');
                }

                $checkDish = $pdo->prepare(
                    "SELECT 1
                     FROM MonAn m
                     JOIN NhomMon n ON n.maNhom = m.maNhom
                     WHERE m.maMon = ? AND n.maChuoi = ?"
                );
                $checkDish->execute([$maMon, $maChuoi]);

                if (!$checkDish->fetchColumn()) {
                    throw new RuntimeException('Không tìm thấy món ăn cần cập nhật.');
                }
            }

            // Kiểm tra tên món bị trùng trong cùng nhóm.
            if (mon_an_duplicate(
                $pdo,
                $maNhom,
                $tenMon,
                $action === 'update' ? $maMon : null
            )) {
                throw new RuntimeException('Tên món đã tồn tại trong nhóm món này.');
            }

            if ($action === 'add') {
                $maMon = mon_an_generate_id($pdo);

                $stmt = $pdo->prepare(
                    "INSERT INTO MonAn
                    (maMon, maNhom, tenMon, donGia, hinhAnh, moTa)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $maMon,
                    $maNhom,
                    $tenMon,
                    $donGia,
                    $hinhAnh !== '' ? $hinhAnh : null,
                    $moTa !== '' ? $moTa : null
                ]);

                mon_an_redirect('added');
            }

            $stmt = $pdo->prepare(
                "UPDATE MonAn
                 SET maNhom = ?, tenMon = ?, donGia = ?, hinhAnh = ?, moTa = ?
                 WHERE maMon = ?"
            );

            $stmt->execute([
                $maNhom,
                $tenMon,
                $donGia,
                $hinhAnh !== '' ? $hinhAnh : null,
                $moTa !== '' ? $moTa : null,
                $maMon
            ]);

            mon_an_redirect('updated');
        }

        if ($action === 'disable') {
            $maMon = trim(post_text('maMon'));

            if ($maMon === '') {
                throw new RuntimeException('Mã món không hợp lệ.');
            }

            $pdo->beginTransaction();

            // Kiểm tra món thuộc chuỗi và tồn tại.
            $check = $pdo->prepare(
                "SELECT m.trangThai
                 FROM MonAn m
                 JOIN NhomMon n ON n.maNhom = m.maNhom
                 WHERE m.maMon = ? AND n.maChuoi = ?
                 FOR UPDATE"
            );
            $check->execute([$maMon, $maChuoi]);
            $dish = $check->fetch();

            if (!$dish) {
                throw new RuntimeException('Không tìm thấy món ăn.');
            }

            if ($dish['trangThai'] === 'NGUNG') {
                throw new RuntimeException('Món ăn đã ngừng kinh doanh.');
            }

            // Ngừng kinh doanh món ăn.
            $stmt = $pdo->prepare(
                "UPDATE MonAn
                 SET trangThai = 'NGUNG'
                 WHERE maMon = ?"
            );
            $stmt->execute([$maMon]);

            // Ngừng bán món tại các chi nhánh.
            $stmt = $pdo->prepare(
                "UPDATE ThucDonChiNhanh
                 SET trangThai = 'TAM_NGUNG'
                 WHERE maMon = ?"
            );
            $stmt->execute([$maMon]);

            $pdo->commit();
            mon_an_redirect('disabled');
        }

        throw new RuntimeException('Thao tác không hợp lệ.');

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof RuntimeException) {
            $error = $e->getMessage();
        } else {
            error_log('MonAn module: ' . $e->getMessage());
            $error = 'Có lỗi xảy ra khi xử lý dữ liệu. Vui lòng thử lại.';
        }
    }
}

// Thông báo thao tác.
$messages = [
    'added' => 'Thêm món ăn thành công.',
    'updated' => 'Cập nhật món ăn thành công.',
    'disabled' => 'Đã ngừng kinh doanh món ăn.'
];

$messageKey = is_string($_GET['message'] ?? null) ? $_GET['message'] : '';
$success = $messages[$messageKey] ?? '';

// Lấy nhóm món.
$groupStmt = $pdo->prepare(
    "SELECT maNhom, tenNhom
     FROM NhomMon
     WHERE maChuoi = ? AND trangThai = 'HOAT_DONG'
     ORDER BY tenNhom"
);
$groupStmt->execute([$maChuoi]);
$groups = $groupStmt->fetchAll();

// Tìm kiếm và lọc nhóm món.
$keyword = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$selectedGroup = trim(is_string($_GET['group'] ?? null) ? $_GET['group'] : '');

$sql = "SELECT m.maMon, m.tenMon, m.donGia, m.hinhAnh, m.moTa,
               m.trangThai, m.trangThaiDinhMuc,
               n.tenNhom, n.maNhom
        FROM MonAn m
        JOIN NhomMon n ON n.maNhom = m.maNhom
        WHERE n.maChuoi = ?";

$params = [$maChuoi];

if ($keyword !== '') {
    $sql .= " AND (m.maMon LIKE ? OR m.tenMon LIKE ?)";
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
}

if ($selectedGroup !== '') {
    $sql .= " AND m.maNhom = ?";
    $params[] = $selectedGroup;
}

$sql .= " ORDER BY m.trangThai ASC, n.tenNhom, m.tenMon";

$listStmt = $pdo->prepare($sql);
$listStmt->execute($params);
$dishes = $listStmt->fetchAll();

// Xem chi tiết món ăn.
$detail = null;
$detailId = trim(is_string($_GET['detail'] ?? null) ? $_GET['detail'] : '');

if ($detailId !== '') {
    $stmt = $pdo->prepare(
        "SELECT m.*, n.tenNhom
         FROM MonAn m
         JOIN NhomMon n ON n.maNhom = m.maNhom
         WHERE m.maMon = ? AND n.maChuoi = ?"
    );
    $stmt->execute([$detailId, $maChuoi]);
    $detail = $stmt->fetch() ?: null;
}

$canManage = can('menu.manage');

// Lấy món cần cập nhật.
$editDish = null;
$editId = trim(is_string($_GET['edit'] ?? null) ? $_GET['edit'] : '');

if ($editId !== '' && $canManage) {
    $stmt = $pdo->prepare(
        "SELECT m.*
         FROM MonAn m
         JOIN NhomMon n ON n.maNhom = m.maNhom
         WHERE m.maMon = ? AND n.maChuoi = ?"
    );
    $stmt->execute([$editId, $maChuoi]);
    $editDish = $stmt->fetch() ?: null;
}

// Chỉ mở form khi người dùng chọn thêm hoặc cập nhật.
$addMode = ($_GET['add'] ?? '') === '1';
$showForm = $addMode || $editId !== '';
?>

<div class="screen-content menu-management">

    <div class="section-head">
        <div>
            <span class="eyebrow accent">THỰC ĐƠN TOÀN CHUỖI</span>
            <h2 class="title title-md">Danh sách món ăn</h2>
        </div>

        <?php if ($canManage): ?>
            <a href="<?= e(page_url('menu')) ?>&add=1#form-mon-an"
               class="btn btn-primary">
                + Thêm món ăn
            </a>
        <?php endif; ?>
    </div>

    <?php if ($success !== ''): ?>
        <div class="success-banner"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert-box"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($detailId !== ''): ?>
        <div class="card" style="padding:20px;margin-bottom:20px;">
            <h3>Chi tiết món ăn</h3>

            <?php if ($detail): ?>
                <p><strong>Mã món:</strong> <?= e($detail['maMon']) ?></p>
                <p><strong>Tên món:</strong> <?= e($detail['tenMon']) ?></p>
                <p><strong>Nhóm món:</strong> <?= e($detail['tenNhom']) ?></p>
                <p><strong>Đơn giá:</strong> <?= number_format((float)$detail['donGia'], 0, ',', '.') ?>đ</p>
                <p><strong>Mô tả:</strong> <?= e($detail['moTa'] ?? '') ?></p>
                <p><strong>Hình ảnh:</strong> <?= e($detail['hinhAnh'] ?? '') ?></p>
                <p><strong>Định mức nguyên vật liệu:</strong> <?= e($detail['trangThaiDinhMuc']) ?></p>
                <p><strong>Trạng thái:</strong> <?= e($detail['trangThai']) ?></p>

                <?php if ($canManage): ?>
                    <a class="btn btn-primary"
                       href="<?= e(page_url('menu')) ?>&edit=<?= rawurlencode($detail['maMon']) ?>">
                        Cập nhật món ăn
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <p>Không tìm thấy món ăn.</p>
            <?php endif; ?>

            <a class="btn btn-ghost" href="<?= e(page_url('menu')) ?>">
                Quay lại danh sách
            </a>
        </div>
    <?php endif; ?>

    <div class="catalog">
        <div class="catalog-tools">

            <form method="get" action="index.php" class="search-wide">
                <input type="hidden" name="page" value="menu">
                <input class="control" type="text" name="q"
                       value="<?= e($keyword) ?>"
                       placeholder="Tìm món ăn, mã món...">
                <button type="submit" class="btn btn-primary">
                    Tìm kiếm
                </button>
            </form>

            <div class="category-tabs">
                <a class="btn <?= $selectedGroup === '' ? 'btn-primary' : 'btn-ghost' ?>"
                   href="<?= e(page_url('menu')) ?>">
                    Tất cả
                </a>

                <?php foreach ($groups as $group): ?>
                    <a class="btn <?= $selectedGroup === $group['maNhom'] ? 'btn-primary' : 'btn-ghost' ?>"
                       href="<?= e(page_url('menu')) ?>&group=<?= rawurlencode($group['maNhom']) ?>">
                        <?= e($group['tenNhom']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="menu-grid">
            <?php if (!$dishes): ?>
                <div class="card" style="padding:20px;">
                    Không tìm thấy món ăn phù hợp.
                </div>
            <?php endif; ?>

            <?php foreach ($dishes as $dish): ?>
                <div class="card menu-card <?= $dish['trangThai'] === 'NGUNG' ? 'disabled-card' : '' ?>">

                    <div class="food-thumb">
                        <?= e(mb_strtoupper(mb_substr($dish['tenMon'], 0, 2))) ?>
                    </div>

                    <div class="menu-info">

                        <?php if ($dish['trangThai'] === 'HOAT_DONG'): ?>
                            <span class="badge badge-success">Đang kinh doanh</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Ngừng kinh doanh</span>
                        <?php endif; ?>

                        <strong style="display:block; white-space:normal; overflow:visible; overflow-wrap:anywhere; word-break:normal; line-height:1.5;">
                            <?= e($dish['tenMon']) ?>
                        </strong>

                        <span><?= e($dish['tenNhom']) ?></span>

                        <b><?= number_format((float)$dish['donGia'], 0, ',', '.') ?>đ</b>

                        <small><?= e($dish['maMon']) ?></small>

                        <a class="btn btn-ghost"
                           href="<?= e(page_url('menu')) ?>&detail=<?= rawurlencode($dish['maMon']) ?>">
                            Xem chi tiết
                        </a>

                        <?php if ($canManage): ?>
                            <a class="btn btn-secondary"
                               href="<?= e(page_url('menu')) ?>&edit=<?= rawurlencode($dish['maMon']) ?>">
                                Cập nhật
                            </a>

                            <?php if ($dish['trangThai'] === 'HOAT_DONG'): ?>
                                <form method="post"
                                      onsubmit="return confirm('Bạn có chắc muốn ngừng kinh doanh món này?');">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="disable">
                                    <input type="hidden" name="maMon" value="<?= e($dish['maMon']) ?>">
                                    <button type="submit" class="btn btn-danger">
                                        Ngừng kinh doanh
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($canManage && $showForm): ?>
        <div class="card" id="form-mon-an" style="padding:20px;margin-top:24px;">

            <h3><?= $editDish ? 'Cập nhật món ăn' : 'Thêm món ăn mới' ?></h3>

            <?php if ($editId !== '' && !$editDish): ?>
                <p>Không tìm thấy món ăn cần cập nhật.</p>
            <?php else: ?>

                <form method="post">
                    <?= csrf_input() ?>

                    <input type="hidden" name="action"
                           value="<?= $editDish ? 'update' : 'add' ?>">

                    <?php if ($editDish): ?>
                        <input type="hidden" name="maMon"
                               value="<?= e($editDish['maMon']) ?>">
                    <?php endif; ?>

                    <div class="form-grid">

                        <div class="field">
                            <label class="field-label">Tên món ăn</label>
                            <input class="control" type="text" name="tenMon"
                                   maxlength="100" required
                                   value="<?= e($editDish['tenMon'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label class="field-label">Nhóm món</label>
                            <select class="control" name="maNhom" required>
                                <option value="">-- Chọn nhóm món --</option>

                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= e($group['maNhom']) ?>"
                                        <?= ($editDish['maNhom'] ?? '') === $group['maNhom'] ? 'selected' : '' ?>>
                                        <?= e($group['tenNhom']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="field">
                            <label class="field-label">Đơn giá (VNĐ)</label>
                            <input class="control" type="number" name="donGia"
                                   min="0" step="0.01" required
                                   value="<?= e((string)($editDish['donGia'] ?? '')) ?>">
                        </div>

                        <div class="field">
                            <label class="field-label">Đường dẫn hình ảnh (nếu có)</label>
                            <input class="control" type="text" name="hinhAnh"
                                   maxlength="255"
                                   value="<?= e($editDish['hinhAnh'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label class="field-label">Mô tả</label>
                            <textarea class="control textarea" name="moTa"
                                      maxlength="500"><?= e($editDish['moTa'] ?? '') ?></textarea>
                        </div>

                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $editDish ? 'Lưu cập nhật' : 'Thêm món ăn' ?>
                        </button>

                        <a href="<?= e(page_url('menu')) ?>" class="btn btn-ghost">
                            Hủy
                        </a>
                    </div>

                </form>

            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
