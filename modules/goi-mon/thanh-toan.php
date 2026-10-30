<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

$user = current_user();
if (!$user || !can('checkout.view')) {
    http_response_code(403);
    echo '<section class="card"><h2 class="title title-md">Bạn không có quyền truy cập chức năng thanh toán.</h2></section>';
    return;
}

$conn = db();
$branchId = current_branch_id();
$checkoutUrl = static fn(array $query = []): string => url('index.php?' . http_build_query(['page' => 'checkout'] + $query));
$errorMessage = '';
$paymentMethods = ['cash' => 'TIEN_MAT', 'qr' => 'CHUYEN_KHOAN'];
// CẤU HÌNH TÀI KHOẢN NGÂN HÀNG TEST (Lấy từ SePay bạn vừa tạo)
$bankId      = 'VietinBank';             // Mã ngân hàng (ví dụ: MBBank, VCB, ACB, ICB...)
$accountNo   = '0000000001';         // Số tài khoản bạn đã tạo trên SePay
$accountName = 'FBNODES';     // Tên chủ tài khoản

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay_order') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!is_string($csrfToken) || !hash_equals(csrf_token(), $csrfToken)) {
        $errorMessage = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang rồi thử lại.';
        http_response_code(403);
    } else {
        $orderId = $_POST['maDon'] ?? null;
        $method = $_POST['phuongThucThanhToan'] ?? null;
        $cashInput = $_POST['tienKhachDua'] ?? null;
        if (!is_string($orderId) || $orderId === '' || strlen($orderId) > 20 || !is_string($method) || !in_array($method, array_keys($paymentMethods), true)) {
            $errorMessage = 'Thông tin đơn hàng hoặc phương thức thanh toán không hợp lệ.';
            http_response_code(400);
        } else {
            try {
                $conn->beginTransaction();
                $orderSql = "SELECT d.maDon, d.maBan, d.maChiNhanh, d.trangThai, b.soBan, b.tenBan, c.tenChiNhanh
                    FROM DonGoiMon d
                    JOIN Ban b ON b.maBan = d.maBan AND b.maChiNhanh = d.maChiNhanh
                    JOIN ChiNhanh c ON c.maChiNhanh = d.maChiNhanh
                    WHERE d.maDon = :maDon AND b.khuVuc <> 'Mang về'";
                $orderParams = [':maDon' => $orderId];
                if ($branchId !== null) {
                    $orderSql .= ' AND d.maChiNhanh = :maChiNhanh';
                    $orderParams[':maChiNhanh'] = $branchId;
                }
                $orderSql .= ' FOR UPDATE';
                $orderStatement = $conn->prepare($orderSql);
                $orderStatement->execute($orderParams);
                $lockedOrder = $orderStatement->fetch();

                if (!$lockedOrder || $lockedOrder['trangThai'] !== 'DANG_PHUC_VU') {
                    throw new DomainException('Đơn không tồn tại, không thuộc phạm vi chi nhánh của bạn hoặc đã được thanh toán.');
                }

                $itemsStatement = $conn->prepare(
                    "SELECT ct.maMon, COALESCE(ct.tenMon, m.tenMon) AS tenMon, ct.soLuong, ct.donGia,
                            (ct.soLuong * ct.donGia) AS thanhTien
                     FROM ChiTietDonMon ct
                     JOIN MonAn m ON m.maMon = ct.maMon
                     WHERE ct.maDon = :maDon AND ct.maChiNhanh = :maChiNhanh AND ct.trangThai <> 'DA_HUY'
                     ORDER BY ct.gioGuiBep, ct.maCTDon"
                );
                $itemsStatement->execute([
                    ':maDon' => $lockedOrder['maDon'],
                    ':maChiNhanh' => $lockedOrder['maChiNhanh'],
                ]);
                $orderItems = $itemsStatement->fetchAll();
                if ($orderItems === []) {
                    throw new DomainException('Đơn hàng không có món hợp lệ để thanh toán.');
                }
                $subtotal = array_reduce($orderItems, static fn(float $sum, array $item): float => $sum + (float)$item['thanhTien'], 0.0);
                $vat = 0.0;
                $discount = 0.0;
                $grandTotal = max(0.0, $subtotal + $vat - $discount);

                if ($method === 'cash') {
                    if (!is_string($cashInput) || !preg_match('/^\d{1,12}$/D', $cashInput) || (float)$cashInput < $grandTotal) {
                        throw new DomainException('Tiền khách đưa không hợp lệ hoặc chưa đủ thanh toán.');
                    }
                }

                $invoiceTableStatement = $conn->prepare(
                    "SELECT COUNT(*) FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'HoaDon'"
                );
                $invoiceTableStatement->execute();
                if ((int)$invoiceTableStatement->fetchColumn() > 0) {
                    $invoiceId = 'HD' . bin2hex(random_bytes(9));
                    $invoiceStatement = $conn->prepare(
                        "INSERT INTO HoaDon
                            (maHoaDon, maDon, maNhanVien, tongTien, phuongThucThanhToan, thoiGianThanhToan)
                         VALUES
                            (:maHoaDon, :maDon, :maNhanVien, :tongTien, :phuongThucThanhToan, UTC_TIMESTAMP())"
                    );
                    $invoiceStatement->execute([
                        ':maHoaDon' => $invoiceId,
                        ':maDon' => $lockedOrder['maDon'],
                        ':maNhanVien' => (string)$user['maNhanVien'],
                        ':tongTien' => $grandTotal,
                        ':phuongThucThanhToan' => $paymentMethods[$method],
                    ]);
                }

                $finishOrder = $conn->prepare(
                    "UPDATE DonGoiMon SET trangThai = 'HOAN_TAT'
                     WHERE maDon = :maDon AND maChiNhanh = :maChiNhanh AND trangThai = 'DANG_PHUC_VU'"
                );
                $finishOrder->execute([
                    ':maDon' => $lockedOrder['maDon'],
                    ':maChiNhanh' => $lockedOrder['maChiNhanh'],
                ]);
                if ($finishOrder->rowCount() !== 1) {
                    throw new DomainException('Đơn đã được xử lý bởi một giao dịch khác. Vui lòng tải lại trang.');
                }

                $releaseTable = $conn->prepare(
                    "UPDATE Ban
                     SET trangThai = CASE
                         WHEN EXISTS (
                             SELECT 1 FROM DonGoiMon activeOrder
                             WHERE activeOrder.maBan = Ban.maBan
                               AND activeOrder.maChiNhanh = Ban.maChiNhanh
                               AND activeOrder.trangThai = 'DANG_PHUC_VU'
                         ) THEN 'DANG_PHUC_VU'
                         ELSE 'TRONG'
                     END
                     WHERE maBan = :maBan AND maChiNhanh = :maChiNhanh"
                );
                $releaseTable->execute([
                    ':maBan' => $lockedOrder['maBan'],
                    ':maChiNhanh' => $lockedOrder['maChiNhanh'],
                ]);

                $conn->commit();
                header('Location: ' . $checkoutUrl(['don' => $lockedOrder['maDon']]), true, 303);
                exit;
            } catch (DomainException $error) {
                if ($conn->inTransaction()) { $conn->rollBack(); }
                $errorMessage = $error->getMessage();
                http_response_code(400);
            } catch (Throwable $error) {
                if ($conn->inTransaction()) { $conn->rollBack(); }
                error_log('POS checkout payment failed: ' . $error->getMessage());
                $errorMessage = 'Không thể hoàn tất thanh toán. Vui lòng thử lại.';
                http_response_code(500);
            }
        }
    }
}

$requestedOrderId = trim((string)($_GET['don'] ?? ''));
$requestedTableId = trim((string)($_GET['ban'] ?? ''));
$hasOrderSelection = $requestedOrderId !== '' || $requestedTableId !== '';
$order = null;
$orderItems = [];
$subtotal = 0.0;
$vat = 0.0;
$discount = 0.0;
$grandTotal = 0.0;

// 1. Xây dựng truy vấn tìm đơn hàng
$orderSql = "SELECT d.maDon, d.maBan, d.maChiNhanh, d.trangThai, d.thoiGianTao, d.tongTien,
                    b.soBan, b.tenBan, c.tenChiNhanh
             FROM DonGoiMon d
             LEFT JOIN Ban b ON b.maBan = d.maBan
             LEFT JOIN ChiNhanh c ON c.maChiNhanh = d.maChiNhanh
             WHERE b.khuVuc <> 'Mang về'";
$orderParams = [];

if ($branchId !== null) {
    $orderSql .= ' AND d.maChiNhanh = :maChiNhanh';
    $orderParams[':maChiNhanh'] = $branchId;
}

if ($requestedOrderId !== '') {
    $orderSql .= ' AND d.maDon = :maDon';
    $orderParams[':maDon'] = $requestedOrderId;
} elseif ($requestedTableId !== '') {
    $orderSql .= " AND d.maBan = :maBan AND d.trangThai = 'DANG_PHUC_VU'";
    $orderParams[':maBan'] = $requestedTableId;
} else {
    // Chờ nhân viên chọn bàn; không tự mở đơn bất kỳ nào.
    $orderSql .= " AND 1 = 0";
}

$orderSql .= ' ORDER BY d.thoiGianTao DESC LIMIT 1';
$orderStatement = $conn->prepare($orderSql);
$orderStatement->execute($orderParams);
$order = $orderStatement->fetch() ?: null;

$tableSql = "SELECT b.maBan, b.soBan, b.tenBan, COUNT(d.maDon) AS soDon
             FROM Ban b
             JOIN DonGoiMon d ON d.maBan = b.maBan AND d.maChiNhanh = b.maChiNhanh
             WHERE b.trangThai <> 'NGUNG' AND b.khuVuc <> 'Mang về'
               AND d.trangThai = 'DANG_PHUC_VU'";
$tableParams = [];
if ($branchId !== null) {
    $tableSql .= ' AND b.maChiNhanh = :maChiNhanh';
    $tableParams[':maChiNhanh'] = $branchId;
}
$tableSql .= ' GROUP BY b.maBan, b.soBan, b.tenBan ORDER BY b.soBan';
$tableStatement = $conn->prepare($tableSql);
$tableStatement->execute($tableParams);
$checkoutTables = $tableStatement->fetchAll();

if ($order) {
    $itemsStatement = $conn->prepare(
        "SELECT ct.maMon, COALESCE(ct.tenMon, m.tenMon) AS tenMon, ct.soLuong, ct.donGia,
                (ct.soLuong * ct.donGia) AS thanhTien
         FROM ChiTietDonMon ct
         JOIN MonAn m ON m.maMon = ct.maMon
         WHERE ct.maDon = :maDon AND ct.maChiNhanh = :maChiNhanh
           AND ct.trangThai <> 'DA_HUY'
         ORDER BY ct.gioGuiBep, ct.maCTDon"
    );
    $itemsStatement->execute([
        ':maDon' => $order['maDon'],
        ':maChiNhanh' => $order['maChiNhanh'],
    ]);
    $orderItems = $itemsStatement->fetchAll();
    $subtotal = array_reduce($orderItems, static fn(float $sum, array $item): float => $sum + (float)$item['thanhTien'], 0.0);
    $grandTotal = max(0.0, $subtotal + $vat - $discount);
}

$formatMoney = static fn(float $amount): string => number_format($amount, 0, ',', '.') . 'đ';
$isPaid = $order && $order['trangThai'] === 'HOAN_TAT';
$canPay = $order && !$isPaid && $orderItems !== [];
// Tạo link ảnh VietQR động với tài khoản VietinBank SePay của bạn
$bankId      = 'ICB';         // Mã VietQR của VietinBank
$accountNo   = '0000000001';  // Số tài khoản SePay của bạn
$accountName = 'FBNODES';     // Tên chủ tài khoản

$qrContent = 'DON_' . (string)($order['maDon'] ?? '');
$qrAmount  = (int)round($grandTotal);
$vietQrUrl = "https://img.vietqr.io/image/{$bankId}-{$accountNo}-compact2.png?" . http_build_query([
    'amount'      => $qrAmount,
    'addInfo'     => $qrContent,
    'accountName' => $accountName
]);

$isCheckoutPage = true;
require __DIR__ . '/index.php';
