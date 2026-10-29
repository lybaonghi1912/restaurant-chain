<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * BepControl — SD-09 Tiếp nhận đơn tại bếp. Người lập trình: Lê Xuân Lộc. Actor: Bếp.
 *
 * Quy tắc R04 (docs/database_v2/QUY_TAC_GIAO_DICH.md):
 *  - Lấy các dòng ChiTietDonMon trạng thái CHO_CHE_BIEN của chi nhánh trong phiên.
 *  - Tiếp nhận: CHO_CHE_BIEN → DANG_CHE_BIEN, lưu maNhanVienBep và gioTiepNhan; UPDATE có điều kiện trạng thái cũ,
 *    số dòng cập nhật phải đúng bằng số dòng cần nhận.
 *  - Thứ tự khóa: DonGoiMon → ChiTietDonMon theo maCTDon tăng dần. Người giữ khóa trước quyết định,
 *    người sau đọc lại trạng thái và nhận thông báo xung đột.
 *  - Không tự động trừ kho; không làm màn hình hoàn thành/thanh toán (chưa được phân công).
 * Phương thức ghi trả mảng ['ok','code','message','data'].
 */
final class BepLoi extends RuntimeException
{
    public function __construct(public readonly string $ma, string $thongBao)
    {
        parent::__construct($thongBao);
    }
}

final class BepControl
{
    /**
     * Phiếu chờ tiếp nhận: mỗi đơn đang phục vụ có ít nhất một món CHO_CHE_BIEN, kèm các món đã nhận của cùng đơn.
     * Chi nhánh lấy từ phiên (Bếp/Quản lý: chi nhánh mình; Chủ chuỗi: chi nhánh đang chọn hoặc toàn chuỗi).
     */
    public static function danhSachPhieuCho(): array
    {
        require_permission('kds.view');
        [$where, $params] = branch_filter('d.maChiNhanh');
        $lenh = db()->prepare("SELECT d.maDon,d.maChiNhanh,c.tenChiNhanh,b.tenBan,d.thoiGianGuiBep,
                ct.maCTDon,ct.tenMon,ct.soLuong,ct.ghiChu,ct.trangThai,ct.gioGuiBep,ct.gioTiepNhan,nv.hoTen AS nguoiNhan
            FROM DonGoiMon d
            JOIN Ban b ON b.maBan=d.maBan AND b.maChiNhanh=d.maChiNhanh
            JOIN ChiNhanh c ON c.maChiNhanh=d.maChiNhanh
            JOIN ChiTietDonMon ct ON ct.maDon=d.maDon AND ct.maChiNhanh=d.maChiNhanh AND ct.trangThai IN ('CHO_CHE_BIEN','DANG_CHE_BIEN')
            LEFT JOIN NhanVien nv ON nv.maNhanVien=ct.maNhanVienBep
            WHERE d.trangThai='DANG_PHUC_VU' AND $where
              AND EXISTS (SELECT 1 FROM ChiTietDonMon x WHERE x.maDon=d.maDon AND x.trangThai='CHO_CHE_BIEN')
            ORDER BY ct.maCTDon");
        $lenh->execute($params);
        $phieu = [];
        foreach ($lenh->fetchAll() as $r) {
            $ma = $r['maDon'];
            $phieu[$ma] ??= ['maDon' => $ma, 'maChiNhanh' => $r['maChiNhanh'], 'tenChiNhanh' => $r['tenChiNhanh'], 'tenBan' => $r['tenBan'],
                'choSomNhat' => null, 'mon' => []];
            $phieu[$ma]['mon'][] = $r;
            if ($r['trangThai'] === 'CHO_CHE_BIEN' && ($phieu[$ma]['choSomNhat'] === null || $r['gioGuiBep'] < $phieu[$ma]['choSomNhat'])) {
                $phieu[$ma]['choSomNhat'] = $r['gioGuiBep'];
            }
        }
        $phieu = array_values($phieu);
        usort($phieu, fn($a, $b) => strcmp($a['choSomNhat'], $b['choSomNhat'])); // món chờ lâu nhất lên trước
        return $phieu;
    }

    /** Các món đã tiếp nhận trong ngày (chỉ xem), mới nhất trước. */
    public static function daTiepNhanHomNay(int $gioiHan = 50): array
    {
        require_permission('kds.view');
        [$where, $params] = branch_filter('ct.maChiNhanh');
        $lenh = db()->prepare("SELECT ct.maCTDon,ct.maDon,ct.tenMon,ct.soLuong,ct.ghiChu,ct.gioGuiBep,ct.gioTiepNhan,b.tenBan,c.tenChiNhanh,
                nv.hoTen AS nguoiNhan
            FROM ChiTietDonMon ct JOIN DonGoiMon d ON d.maDon=ct.maDon AND d.maChiNhanh=ct.maChiNhanh
            JOIN Ban b ON b.maBan=d.maBan AND b.maChiNhanh=d.maChiNhanh
            JOIN ChiNhanh c ON c.maChiNhanh=ct.maChiNhanh
            LEFT JOIN NhanVien nv ON nv.maNhanVien=ct.maNhanVienBep
            WHERE ct.trangThai='DANG_CHE_BIEN' AND ct.gioTiepNhan >= CURDATE() AND $where
            ORDER BY ct.gioTiepNhan DESC, ct.maCTDon LIMIT " . max(1, min(200, $gioiHan)));
        $lenh->execute($params);
        return $lenh->fetchAll();
    }

    public static function coTheTiepNhan(?string $maChiNhanh = null): bool
    {
        $actor = current_user();
        return $actor && can('kitchen.manage') && $actor['maChiNhanh'] !== null
            && ($maChiNhanh === null || $maChiNhanh === $actor['maChiNhanh']);
    }

    /** tiepNhan(maCTDon): bếp nhận một dòng món. */
    public static function tiepNhan(string $maCTDon): array
    {
        require_permission('kitchen.manage');
        try {
            $dong = self::timDong($maCTDon);
            $kq = self::giaoDich(function (PDO $pdo) use ($dong): array {
                $don = self::khoaDon($pdo, $dong['maDon'], $dong['maChiNhanh']);
                $lenh = $pdo->prepare('SELECT ct.maCTDon,ct.maDon,ct.tenMon,ct.soLuong,ct.trangThai,ct.gioTiepNhan,nv.hoTen nguoiNhan
                    FROM ChiTietDonMon ct LEFT JOIN NhanVien nv ON nv.maNhanVien=ct.maNhanVienBep
                    WHERE ct.maCTDon=? AND ct.maChiNhanh=? FOR UPDATE OF ct');
                $lenh->execute([$dong['maCTDon'], $dong['maChiNhanh']]);
                $moi = $lenh->fetch();
                if (!$moi || $moi['maDon'] !== $dong['maDon']) { throw new BepLoi('NOT_FOUND', 'Không tìm thấy món trong đơn.'); }
                self::kiemTraChoCheBien($moi);
                self::capNhatTiepNhan($pdo, [$moi['maCTDon']], $dong['maChiNhanh']);
                return ['tenBan' => $don['tenBan'], 'mon' => $moi['soLuong'] . '× ' . $moi['tenMon']];
            });
            return self::ketQua(true, 'OK', 'Đã tiếp nhận ' . $kq['mon'] . ' — bàn ' . $kq['tenBan'] . '.');
        } catch (BepLoi $loi) {
            return self::ketQua(false, $loi->ma, $loi->getMessage());
        }
    }

    /**
     * Nhận tất cả món đang chờ của một đơn mà bếp đang nhìn thấy trên phiếu ($dsMaCTDon).
     * Món gửi bếp sau khi màn hình được tải không bị nhận ngầm; món đã bị hủy/được nhận trước sẽ được bỏ qua và báo lại.
     */
    public static function tiepNhanCaDon(string $maDon, array $dsMaCTDon): array
    {
        require_permission('kitchen.manage');
        $dsMaCTDon = array_values(array_unique(array_filter($dsMaCTDon, fn($m) => is_string($m) && preg_match('/^[A-Za-z0-9_-]{1,20}$/D', $m))));
        if (!$dsMaCTDon) { return self::ketQua(false, 'VALIDATION_ERROR', 'Không có món nào được chọn để tiếp nhận.'); }
        try {
            $timDon = db()->prepare('SELECT maChiNhanh FROM DonGoiMon WHERE maDon=?');
            $timDon->execute([$maDon]);
            $maChiNhanh = $timDon->fetchColumn();
            if ($maChiNhanh === false) { throw new BepLoi('NOT_FOUND', 'Không tìm thấy đơn gọi món.'); }
            if (!self::coTheTiepNhan($maChiNhanh)) { throw new BepLoi('FORBIDDEN', 'Bạn chỉ tiếp nhận đơn của chi nhánh mình.'); }
            $kq = self::giaoDich(function (PDO $pdo) use ($maDon, $maChiNhanh, $dsMaCTDon): array {
                $don = self::khoaDon($pdo, $maDon, $maChiNhanh);
                $lenh = $pdo->prepare('SELECT maCTDon,trangThai FROM ChiTietDonMon WHERE maDon=? AND maChiNhanh=? ORDER BY maCTDon FOR UPDATE');
                $lenh->execute([$maDon, $maChiNhanh]);
                $trangThai = array_column($lenh->fetchAll(), 'trangThai', 'maCTDon');
                $nhan = array_values(array_filter($dsMaCTDon, fn($m) => ($trangThai[$m] ?? null) === 'CHO_CHE_BIEN'));
                sort($nhan, SORT_STRING);
                if (!$nhan) { throw new BepLoi('CONFLICT', 'Các món trên phiếu đã được tiếp nhận hoặc đã bị hủy. Màn hình đã được làm mới.'); }
                self::capNhatTiepNhan($pdo, $nhan, $maChiNhanh);
                return ['tenBan' => $don['tenBan'], 'soNhan' => count($nhan), 'boQua' => count($dsMaCTDon) - count($nhan)];
            });
            return self::ketQua(true, 'OK', 'Đã tiếp nhận ' . $kq['soNhan'] . ' món của bàn ' . $kq['tenBan'] . '.'
                . ($kq['boQua'] ? ' Bỏ qua ' . $kq['boQua'] . ' món đã được nhận trước hoặc đã bị hủy.' : ''), $kq);
        } catch (BepLoi $loi) {
            return self::ketQua(false, $loi->ma, $loi->getMessage());
        }
    }

    // ------------------------------------------------------------------ Nội bộ

    private static function timDong(string $maCTDon): array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,20}$/D', $maCTDon)) { throw new BepLoi('NOT_FOUND', 'Không tìm thấy món trong đơn.'); }
        $lenh = db()->prepare('SELECT maCTDon,maDon,maChiNhanh FROM ChiTietDonMon WHERE maCTDon=?');
        $lenh->execute([$maCTDon]);
        $dong = $lenh->fetch();
        if (!$dong) { throw new BepLoi('NOT_FOUND', 'Không tìm thấy món trong đơn.'); }
        if (!self::coTheTiepNhan($dong['maChiNhanh'])) { throw new BepLoi('FORBIDDEN', 'Bạn chỉ tiếp nhận món của chi nhánh mình.'); }
        return $dong;
    }

    /** Khóa đơn trước (R04) và kiểm tra đơn còn đang phục vụ. */
    private static function khoaDon(PDO $pdo, string $maDon, string $maChiNhanh): array
    {
        $lenh = $pdo->prepare('SELECT d.maDon,d.trangThai,b.tenBan FROM DonGoiMon d JOIN Ban b ON b.maBan=d.maBan AND b.maChiNhanh=d.maChiNhanh
            WHERE d.maDon=? AND d.maChiNhanh=? FOR UPDATE OF d');
        $lenh->execute([$maDon, $maChiNhanh]);
        $don = $lenh->fetch();
        if (!$don) { throw new BepLoi('NOT_FOUND', 'Không tìm thấy đơn gọi món.'); }
        if ($don['trangThai'] !== 'DANG_PHUC_VU') { throw new BepLoi('CONFLICT', 'Đơn của bàn ' . $don['tenBan'] . ' đã kết thúc hoặc đã bị hủy.'); }
        return $don;
    }

    private static function kiemTraChoCheBien(array $dong): void
    {
        if ($dong['trangThai'] === 'CHO_CHE_BIEN') { return; }
        $ten = $dong['soLuong'] . '× ' . $dong['tenMon'];
        throw new BepLoi('CONFLICT', match ($dong['trangThai']) {
            'DA_HUY' => 'Món ' . $ten . ' đã bị phục vụ hủy, không tiếp nhận được.',
            'DANG_CHE_BIEN' => 'Món ' . $ten . ' đã được ' . ($dong['nguoiNhan'] ?? 'bếp khác') . ' tiếp nhận lúc '
                . date('H:i', strtotime((string)$dong['gioTiepNhan'])) . '.',
            default => 'Món ' . $ten . ' không còn ở trạng thái chờ chế biến.',
        });
    }

    /** UPDATE có điều kiện trạng thái cũ; số dòng bị ảnh hưởng phải đúng bằng số món cần nhận. */
    private static function capNhatTiepNhan(PDO $pdo, array $dsMaCTDon, string $maChiNhanh): void
    {
        $lenh = $pdo->prepare("UPDATE ChiTietDonMon SET trangThai='DANG_CHE_BIEN', maNhanVienBep=?, gioTiepNhan=NOW()
            WHERE maCTDon=? AND maChiNhanh=? AND trangThai='CHO_CHE_BIEN'");
        $maBep = current_user()['maNhanVien'];
        foreach ($dsMaCTDon as $ma) {
            $lenh->execute([$maBep, $ma, $maChiNhanh]);
            if ($lenh->rowCount() !== 1) {
                throw new BepLoi('CONFLICT', 'Trạng thái món vừa thay đổi. Vui lòng xem lại màn hình bếp.');
            }
        }
    }

    private static function giaoDich(callable $congViec): mixed
    {
        $pdo = db();
        for ($lan = 1; ; $lan++) {
            $pdo->beginTransaction();
            try {
                $ketQua = $congViec($pdo);
                $pdo->commit();
                return $ketQua;
            } catch (Throwable $loi) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                if ($loi instanceof BepLoi) { throw $loi; }
                if ($loi instanceof PDOException && in_array((int)($loi->errorInfo[1] ?? 0), [1213, 1205], true) && $lan < 3) { continue; }
                error_log('FBN bep: ' . $loi->getMessage());
                throw new BepLoi('SERVER_ERROR', 'Không cập nhật được trạng thái món. Vui lòng thử lại.');
            }
        }
    }

    private static function ketQua(bool $ok, string $code, string $message, array $data = []): array
    {
        return ['ok' => $ok, 'code' => $code, 'message' => $message, 'data' => $data];
    }
}
