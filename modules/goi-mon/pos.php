<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

// ==============================================================================
// 1. BACKEND API: TẠO/THÊM MÓN (SD-25/26) VÀ HỦY MÓN (SD-27)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
        $respond(500, ['success' => false, 'message' => 'Không thể xác thực phiên làm việc.']);
    }
    if (!$user) {
        $respond(401, ['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
    }
    if (($user['maVaiTro'] ?? '') !== 'PHUC_VU') {
        $respond(403, ['success' => false, 'message' => 'Chỉ nhân viên phục vụ mới được thực hiện thao tác này.']);
    }

    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!is_string($csrfToken) || !hash_equals(csrf_token(), $csrfToken)) {
        $respond(403, ['success' => false, 'message' => 'Phiên làm việc không hợp lệ (CSRF). Vui lòng tải lại trang.']);
    }

    $maChiNhanh = (string)($user['maChiNhanh'] ?? '');
    $maNhanVien = (string)($user['maNhanVien'] ?? '');

    // --------------------------------------------------------------------------
    // A. TẠO ĐƠN HOẶC THÊM MÓN VÀO ĐƠN (SD-25/26)
    // --------------------------------------------------------------------------
    if ($action === 'send_kitchen') {
        try {
            $maBan = $_POST['maBan'] ?? null;
            $rawItems = $_POST['items'] ?? null;
            if (!is_string($maBan) || $maBan === '' || strlen($maBan) > 10
                || !is_string($rawItems)) {
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

        $conn = null;
        try {
            $conn = db();
            $conn->beginTransaction();

            $stmtBan = $conn->prepare(
                "SELECT maBan, trangThai FROM Ban
                 WHERE maBan = :maBan AND maChiNhanh = :maChiNhanh
                   AND khuVuc <> 'Mang về'
                 FOR UPDATE"
            );
            $stmtBan->execute([':maBan' => $maBan, ':maChiNhanh' => $maChiNhanh]);
            $table = $stmtBan->fetch();
            if (!$table || $table['trangThai'] === 'NGUNG') {
                throw new DomainException('Bàn không tồn tại hoặc hiện không thể phục vụ tại chi nhánh của bạn.');
            }

            $stmtActiveOrder = $conn->prepare(
                "SELECT maDon FROM DonGoiMon
                 WHERE maBan = :maBan AND maChiNhanh = :maChiNhanh AND trangThai = 'DANG_PHUC_VU'
                 ORDER BY thoiGianTao DESC, maDon DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtActiveOrder->execute([':maBan' => $maBan, ':maChiNhanh' => $maChiNhanh]);
            $activeOrder = $stmtActiveOrder->fetch();
            $isAdditionalOrder = (bool)$activeOrder;

            if ($activeOrder) {
                $orderId = (string)$activeOrder['maDon'];
                $stmtOrderTimestamp = $conn->prepare(
                    'UPDATE DonGoiMon SET thoiGianGuiBep = UTC_TIMESTAMP()
                     WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh'
                );
                $stmtOrderTimestamp->execute([':maDon' => $orderId, ':maChiNhanh' => $maChiNhanh]);
            } else {
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
            }

            $stmtDish = $conn->prepare(
                "SELECT m.maMon, m.tenMon, COALESCE(td.giaBan, m.donGia) AS donGia
                 FROM MonAn m
                 JOIN NhomMon n ON n.maNhom = m.maNhom
                 JOIN ThucDonChiNhanh td ON td.maMon = m.maMon
                 WHERE m.maMon = :maMon
                   AND m.trangThai = 'HOAT_DONG'
                   AND n.trangThai = 'HOAT_DONG'
                   AND td.maChiNhanh = :maChiNhanh
                   AND td.trangThai = 'DANG_BAN'"
            );
            $stmtDetail = $conn->prepare(
                "INSERT INTO ChiTietDonMon
                    (maCTDon, maDon, maChiNhanh, maMon, tenMon, soLuong, donGia, ghiChu, trangThai, gioGuiBep)
                 VALUES
                    (:maCTDon, :maDon, :maChiNhanh, :maMon, :tenMon, :soLuong, :donGia, :ghiChu, 'CHO_CHE_BIEN', UTC_TIMESTAMP())"
            );
            $stmtPendingItem = $conn->prepare(
                "SELECT maCTDon, soLuong
                 FROM ChiTietDonMon
                 WHERE maDon = :maDon
                   AND maChiNhanh = :maChiNhanh
                   AND maMon = :maMon
                   AND donGia = :donGia
                   AND ghiChu <=> :ghiChu
                   AND trangThai = 'CHO_CHE_BIEN'
                 ORDER BY gioGuiBep, maCTDon
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtIncreaseQuantity = $conn->prepare(
                "UPDATE ChiTietDonMon
                 SET soLuong = soLuong + :soLuong, gioGuiBep = UTC_TIMESTAMP()
                 WHERE maCTDon = :maCTDon
                   AND maChiNhanh = :maChiNhanh
                   AND trangThai = 'CHO_CHE_BIEN'"
            );

            foreach ($validatedItems as $item) {
                $stmtDish->execute([':maMon' => $item['id'], ':maChiNhanh' => $maChiNhanh]);
                $dish = $stmtDish->fetch();
                if (!$dish) {
                    throw new DomainException('Một món trong đơn không còn được bán tại chi nhánh này.');
                }

                $detailParams = [
                    ':maChiNhanh' => $maChiNhanh,
                    ':maMon' => $dish['maMon'],
                    ':donGia' => $dish['donGia'],
                    ':ghiChu' => $item['note'] !== '' ? $item['note'] : null,
                ];
                $stmtPendingItem->execute(array_merge([
                    ':maDon' => $orderId,
                ], $detailParams));
                $pendingItem = $stmtPendingItem->fetch();

                if ($pendingItem) {
                    if ((int)$pendingItem['soLuong'] > 2147483647 - $item['qty']) {
                        throw new DomainException('Số lượng món vượt quá giới hạn cho phép.');
                    }
                    $stmtIncreaseQuantity->execute([
                        ':soLuong' => $item['qty'],
                        ':maCTDon' => $pendingItem['maCTDon'],
                        ':maChiNhanh' => $maChiNhanh,
                    ]);
                    if ($stmtIncreaseQuantity->rowCount() !== 1) {
                        throw new DomainException('Món đã được bếp tiếp nhận. Vui lòng tải lại đơn hàng.');
                    }
                    continue;
                }

                $stmtDetail->execute(array_merge([
                    ':maCTDon' => 'CT' . bin2hex(random_bytes(9)),
                    ':maDon' => $orderId,
                    ':tenMon' => $dish['tenMon'],
                    ':soLuong' => $item['qty'],
                ], $detailParams));
            }

            $stmtTotal = $conn->prepare(
                "UPDATE DonGoiMon
                 SET tongTien = (
                     SELECT COALESCE(SUM(soLuong * donGia), 0)
                     FROM ChiTietDonMon
                     WHERE maDon = :maDonChiTiet AND maChiNhanh = :maChiNhanhChiTiet
                       AND trangThai <> 'DA_HUY'
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

            $stmtOrderItems = $conn->prepare(
                "SELECT ct.maCTDon, ct.maMon, ct.tenMon, ct.soLuong, ct.donGia, ct.ghiChu,
                        (ct.soLuong * ct.donGia) AS thanhTien, ct.trangThai
                 FROM ChiTietDonMon ct
                 WHERE ct.maDon = :maDon AND ct.maChiNhanh = :maChiNhanh AND ct.trangThai <> 'DA_HUY'
                 ORDER BY ct.gioGuiBep, ct.maCTDon"
            );
            $stmtOrderItems->execute([':maDon' => $orderId, ':maChiNhanh' => $maChiNhanh]);
            $updatedItems = $stmtOrderItems->fetchAll();

            $conn->commit();
            $respond(200, [
                'success' => true,
                'message' => $isAdditionalOrder ? 'Đã gửi thêm món vào bếp thành công!' : 'Đã gửi bếp thành công',
                'maDon' => $orderId,
                'isAdditionalOrder' => $isAdditionalOrder,
                'items' => $updatedItems,
            ]);
        } catch (DomainException $error) {
            if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
            $respond(400, ['success' => false, 'message' => $error->getMessage()]);
        } catch (Throwable $error) {
            if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
            error_log('POS send-to-kitchen failed: ' . $error->getMessage());
            $respond(500, ['success' => false, 'message' => 'Lỗi khi lưu đơn. Vui lòng thử lại.']);
        }
    }

    // --------------------------------------------------------------------------
    // B. HỦY MÓN ĐANG CHỜ BẾP KÈM LÝ DO (SD-27)
    // --------------------------------------------------------------------------
    if ($action === 'cancel_order_item') {
        $maCTDon = $_POST['maCTDon'] ?? null;
        $lyDoHuy = $_POST['lyDoHuy'] ?? null;
        $maDon = $_POST['maDon'] ?? null;
        if (!is_string($maCTDon) || !is_string($lyDoHuy)
            || !is_string($maDon)) {
            $respond(400, ['success' => false, 'message' => 'Thông tin món hoặc lý do hủy không hợp lệ.']);
        }
        $maCTDon = trim($maCTDon);
        $lyDoHuy = trim($lyDoHuy);
        if ($maCTDon === '' || strlen($maCTDon) > 20
            || $maDon === '' || strlen($maDon) > 20
            || $lyDoHuy === '' || mb_strlen($lyDoHuy, 'UTF-8') > 255) {
            $respond(400, ['success' => false, 'message' => 'Mã món, mã đơn hoặc lý do hủy không hợp lệ.']);
        }

        $conn = null;
        try {
            $conn = db();
            $conn->beginTransaction();

            $stmtOrder = $conn->prepare(
                "SELECT maDon, trangThai
                 FROM DonGoiMon
                 WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh
                 FOR UPDATE"
            );
            $stmtOrder->execute([':maDon' => $maDon, ':maChiNhanh' => $maChiNhanh]);
            $order = $stmtOrder->fetch();
            if (!$order || $order['trangThai'] !== 'DANG_PHUC_VU') {
                throw new DomainException('Đơn không tồn tại, không thuộc chi nhánh của bạn hoặc không còn mở.');
            }

            $stmtCheck = $conn->prepare(
                "SELECT maDon, trangThai
                 FROM ChiTietDonMon
                 WHERE maCTDon = :maCTDon AND maDon = :maDon AND maChiNhanh = :maChiNhanh
                 FOR UPDATE"
            );
            $stmtCheck->execute([
                ':maCTDon' => $maCTDon,
                ':maDon' => $order['maDon'],
                ':maChiNhanh' => $maChiNhanh,
            ]);
            $ct = $stmtCheck->fetch();

            if (!$ct) {
                throw new DomainException('Không tìm thấy dòng món cần hủy hoặc món không thuộc chi nhánh.');
            }
            if ($ct['trangThai'] !== 'CHO_CHE_BIEN') {
                throw new DomainException('Chỉ có thể hủy món đang chờ bếp tiếp nhận.');
            }

            $stmtCancel = $conn->prepare(
                "UPDATE ChiTietDonMon
                 SET trangThai = 'DA_HUY', lyDoHuy = :lyDoHuy
                 WHERE maCTDon = :maCTDon AND maDon = :maDon
                   AND maChiNhanh = :maChiNhanh AND trangThai = 'CHO_CHE_BIEN'"
            );
            $stmtCancel->execute([
                ':lyDoHuy' => $lyDoHuy,
                ':maCTDon' => $maCTDon,
                ':maDon' => $order['maDon'],
                ':maChiNhanh' => $maChiNhanh,
            ]);
            if ($stmtCancel->rowCount() !== 1) {
                throw new DomainException('Món đã được bếp tiếp nhận hoặc xử lý bởi một thao tác khác.');
            }

            $stmtUpdateTotal = $conn->prepare(
                "UPDATE DonGoiMon
                 SET tongTien = (
                     SELECT COALESCE(SUM(soLuong * donGia), 0)
                     FROM ChiTietDonMon
                     WHERE maDon = :maDonChiTiet AND maChiNhanh = :maChiNhanhChiTiet AND trangThai <> 'DA_HUY'
                 )
                 WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh"
            );
            $stmtUpdateTotal->execute([
                ':maDonChiTiet' => $order['maDon'],
                ':maChiNhanhChiTiet' => $maChiNhanh,
                ':maDon' => $order['maDon'],
                ':maChiNhanh' => $maChiNhanh,
            ]);

            $conn->commit();
            $respond(200, ['success' => true, 'message' => 'Đã hủy món đang chờ bếp và cập nhật lại tổng đơn.']);
        } catch (DomainException $error) {
            if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
            $respond(400, ['success' => false, 'message' => $error->getMessage()]);
        } catch (Throwable $error) {
            if ($conn instanceof PDO && $conn->inTransaction()) { $conn->rollBack(); }
            error_log('POS cancel-item failed: ' . $error->getMessage());
            $respond(500, ['success' => false, 'message' => 'Lỗi khi hủy món. Vui lòng thử lại.']);
        }
    }

    $respond(400, ['success' => false, 'message' => 'Hành động không hợp lệ.']);
}

// ==============================================================================
// 2. KHỞI TẠO DỮ LIỆU HIỂN THỊ (SD-25/26)
// ==============================================================================
$conn = db();
$user = current_user();
$maChiNhanh = (string)(current_branch_id() ?? '');

// 1. Lấy danh sách bàn theo chi nhánh
$stmtBan = $conn->prepare("
    SELECT maBan, soBan, tenBan, sucChua, trangThai
    FROM Ban
    WHERE maChiNhanh = :maChiNhanh
      AND trangThai <> 'NGUNG'
      AND khuVuc <> 'Mang về'
    ORDER BY soBan ASC
");
$stmtBan->execute([':maChiNhanh' => $maChiNhanh]);
$danhSachBan = $stmtBan->fetchAll();

// Xác định bàn đang chọn
$currentBan = null;
$requestedTableId = $_GET['ban'] ?? null;
$hasRequestedTable = is_string($requestedTableId) && $requestedTableId !== '';
foreach ($danhSachBan as $b) {
    if ($hasRequestedTable && in_array($requestedTableId, [
        (string)$b['maBan'],
        (string)$b['tenBan'],
        (string)$b['soBan'],
    ], true)) {
        $currentBan = $b;
        break;
    }
}

// Lấy đơn hàng đang phục vụ của bàn (nếu có)
$activeOrder = null;
$sentOrderItems = [];
$sentOrderSubtotal = 0.0;
if ($currentBan) {
    $stmtActiveOrder = $conn->prepare(
        "SELECT maDon, tongTien FROM DonGoiMon
         WHERE maBan = :maBan AND maChiNhanh = :maChiNhanh AND trangThai = 'DANG_PHUC_VU'
         ORDER BY thoiGianTao DESC
         LIMIT 1"
    );
    $stmtActiveOrder->execute([
        ':maBan' => $currentBan['maBan'],
        ':maChiNhanh' => $maChiNhanh,
    ]);
    $activeOrder = $stmtActiveOrder->fetch() ?: null;

    if ($activeOrder) {
        $stmtSentItems = $conn->prepare(
            "SELECT ct.maCTDon, ct.maDon, ct.maMon, ct.tenMon, ct.soLuong, ct.donGia, ct.ghiChu, ct.trangThai,
                    (ct.soLuong * ct.donGia) AS thanhTien
             FROM ChiTietDonMon ct
             JOIN DonGoiMon d ON d.maDon = ct.maDon AND d.maChiNhanh = ct.maChiNhanh
             WHERE d.maBan = :maBan AND d.maChiNhanh = :maChiNhanh
               AND d.trangThai = 'DANG_PHUC_VU'
               AND ct.trangThai <> 'DA_HUY'
             ORDER BY d.thoiGianTao, ct.gioGuiBep, ct.maCTDon"
        );
        $stmtSentItems->execute([
            ':maBan' => $currentBan['maBan'],
            ':maChiNhanh' => $maChiNhanh,
        ]);
        $sentOrderItems = $stmtSentItems->fetchAll();
        $sentOrderSubtotal = array_reduce(
            $sentOrderItems,
            static fn(float $total, array $item): float => $total + (float)$item['thanhTien'],
            0.0
        );
    }
}

// 2. Lấy danh sách nhóm món
$stmtNhom = $conn->query(
    "SELECT maNhom, tenNhom FROM NhomMon
     WHERE trangThai = 'HOAT_DONG'
     ORDER BY maNhom ASC"
);
$danhSachNhom = $stmtNhom->fetchAll();

// 3. Lấy thực đơn món ăn từ database
$stmtMon = $conn->prepare("
    SELECT m.maMon, m.tenMon, COALESCE(td.giaBan, m.donGia) AS donGia, m.hinhAnh, n.tenNhom
    FROM MonAn m
    JOIN ThucDonChiNhanh td ON td.maMon = m.maMon
    JOIN NhomMon n ON m.maNhom = n.maNhom
    WHERE td.maChiNhanh = :maChiNhanh
      AND td.trangThai = 'DANG_BAN'
      AND m.trangThai = 'HOAT_DONG'
      AND n.trangThai = 'HOAT_DONG'
    ORDER BY n.maNhom ASC, m.tenMon ASC
");
$stmtMon->execute([':maChiNhanh' => $maChiNhanh]);
$danhSachMon = $stmtMon->fetchAll();

function get_food_code(string $name): string {
    $words = preg_split('/\s+/u', trim($name));
    if (count($words) >= 2) {
        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
    return mb_strtoupper(mb_substr($name, 0, 2));
}

$isCheckoutPage = false;
require __DIR__ . '/index.php';
