<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * DinhMucControl — SD-12 Định mức nguyên vật liệu. Người lập trình: Lê Xuân Lộc. Actor: Chủ chuỗi.
 *
 * Quy tắc R05 (docs/database_v2/QUY_TAC_GIAO_DICH.md):
 *  - Khóa MonAn, lấy công thức cũ, kiểm tra NVL hợp lệ và mỗi NVL xuất hiện một lần; lượng > 0 theo đơn vị cơ sở.
 *  - Thay toàn bộ dòng DinhMucNVL, ghi LichSuDinhMuc (JSON trước/sau, người sửa, thời gian) trong cùng transaction.
 *  - Cập nhật MonAn.trangThaiDinhMuc cùng công thức. Không trừ kho, không tạo/sửa NguyenVatLieu.
 * Phương thức ghi trả mảng ['ok','code','message','data'].
 */
final class DinhMucLoi extends RuntimeException
{
    public function __construct(public readonly string $ma, string $thongBao, public readonly array $loiDong = [])
    {
        parent::__construct($thongBao);
    }
}

final class DinhMucControl
{
    public const SO_DONG_TOI_DA = 40;
    public const DINH_LUONG_TOI_DA = 1000;

    /** Danh sách món cho cột trái (đọc). */
    public static function danhSachMon(string $tuKhoa = ''): array
    {
        require_permission('bom.view');
        $sql = 'SELECT m.maMon,m.tenMon,m.trangThai,m.trangThaiDinhMuc,n.tenNhom,
                (SELECT COUNT(*) FROM DinhMucNVL d WHERE d.maMon=m.maMon) soThanhPhan
            FROM MonAn m JOIN NhomMon n ON n.maNhom=m.maNhom';
        $params = [];
        $tuKhoa = trim(mb_substr($tuKhoa, 0, 100));
        if ($tuKhoa !== '') {
            $sql .= ' WHERE m.tenMon LIKE ? OR m.maMon LIKE ?';
            $like = '%' . addcslashes($tuKhoa, '%_\\') . '%';
            $params = [$like, $like];
        }
        $lenh = db()->prepare($sql . " ORDER BY m.trangThai='NGUNG', n.tenNhom, m.tenMon");
        $lenh->execute($params);
        return $lenh->fetchAll();
    }

    /** Thông tin món + công thức hiện tại; null nếu không tồn tại. */
    public static function chiTietMon(string $maMon): ?array
    {
        require_permission('bom.view');
        $lenh = db()->prepare('SELECT m.*, n.tenNhom FROM MonAn m JOIN NhomMon n ON n.maNhom=m.maNhom WHERE m.maMon=?');
        $lenh->execute([$maMon]);
        $mon = $lenh->fetch();
        if (!$mon) { return null; }
        $mon['congThuc'] = self::docCongThuc(db(), $maMon, false);
        $mon['banGhi'] = self::dauVanTay($mon['congThuc']);
        return $mon;
    }

    /** Danh mục NVL toàn chuỗi để chọn (gồm cả NVL ngừng để hiển thị công thức cũ). */
    public static function danhSachNVL(): array
    {
        require_permission('bom.view');
        return db()->query("SELECT maNVL,tenNVL,donViTinh,trangThai FROM NguyenVatLieu ORDER BY trangThai='NGUNG', tenNVL")->fetchAll();
    }

    public static function lichSu(string $maMon, int $gioiHan = 10): array
    {
        require_permission('bom.view');
        $lenh = db()->prepare('SELECT thoiGian,nguoiSua,noiDung,congThucCu,congThucMoi FROM LichSuDinhMuc
            WHERE maMon=? ORDER BY thoiGian DESC, maLichSu DESC LIMIT ' . max(1, min(50, $gioiHan)));
        $lenh->execute([$maMon]);
        return $lenh->fetchAll();
    }

    /**
     * luuCongThuc(maMon, danhSachNVL) — thay toàn bộ công thức của món.
     * @param list<array{maNVL:string,dinhMuc:string}> $danhSach các dòng form (dòng trống đã được bỏ qua ở đây)
     * @param string $banGhi dấu vân tay công thức lúc mở form, phát hiện người khác vừa lưu
     */
    public static function luuCongThuc(string $maMon, array $danhSach, string $banGhi): array
    {
        require_permission('bom.manage');
        $actor = current_user();
        try {
            $dong = self::chuanHoa($danhSach);
            $ketQua = self::giaoDich(function (PDO $pdo) use ($maMon, $dong, $banGhi, $actor): array {
                $khoa = $pdo->prepare('SELECT maMon,tenMon,trangThai FROM MonAn WHERE maMon=? FOR UPDATE');
                $khoa->execute([$maMon]);
                $mon = $khoa->fetch();
                if (!$mon) { throw new DinhMucLoi('NOT_FOUND', 'Món ăn không tồn tại.'); }
                if ($mon['trangThai'] !== 'HOAT_DONG') { throw new DinhMucLoi('CONFLICT', 'Món đã ngừng kinh doanh; không cập nhật định mức.'); }

                $cu = self::docCongThuc($pdo, $maMon, true);
                if (!hash_equals(self::dauVanTay($cu), $banGhi)) {
                    throw new DinhMucLoi('CONFLICT', 'Công thức vừa được người khác cập nhật. Vui lòng xem lại công thức mới rồi nhập lại thay đổi.');
                }

                // KiemTraTinhHopLe: NVL phải tồn tại và đang sử dụng.
                $ma = array_column($dong, 'maNVL');
                $lenh = $pdo->prepare('SELECT maNVL,tenNVL,donViTinh,trangThai FROM NguyenVatLieu WHERE maNVL IN ('
                    . implode(',', array_fill(0, count($ma), '?')) . ') FOR SHARE');
                $lenh->execute($ma);
                $nvl = [];
                foreach ($lenh->fetchAll() as $r) { $nvl[$r['maNVL']] = $r; }
                $loiDong = [];
                foreach ($dong as $i => $d) {
                    if (!isset($nvl[$d['maNVL']])) { $loiDong[$i] = 'Nguyên vật liệu không tồn tại.'; }
                    elseif ($nvl[$d['maNVL']]['trangThai'] !== 'HOAT_DONG') { $loiDong[$i] = $nvl[$d['maNVL']]['tenNVL'] . ' đã ngừng sử dụng; hãy bỏ hoặc thay nguyên liệu khác.'; }
                }
                if ($loiDong) { throw new DinhMucLoi('VALIDATION_ERROR', 'Có nguyên vật liệu không hợp lệ.', $loiDong); }

                $moi = [];
                foreach ($dong as $d) {
                    $moi[] = ['maNVL' => $d['maNVL'], 'tenNVL' => $nvl[$d['maNVL']]['tenNVL'], 'dinhMuc' => $d['dinhMuc'],
                        'donViTinh' => $nvl[$d['maNVL']]['donViTinh']];
                }
                usort($moi, fn($a, $b) => strcmp($a['maNVL'], $b['maNVL']));
                $noiDung = self::moTaThayDoi($cu, $moi);
                if ($noiDung === '') { return ['coThayDoi' => false, 'tenMon' => $mon['tenMon']]; }

                $pdo->prepare('DELETE FROM DinhMucNVL WHERE maMon=?')->execute([$maMon]);
                $them = $pdo->prepare('INSERT INTO DinhMucNVL(maMon,maNVL,dinhMuc) VALUES (?,?,?)');
                foreach ($moi as $d) { $them->execute([$maMon, $d['maNVL'], $d['dinhMuc']]); }
                $pdo->prepare("UPDATE MonAn SET trangThaiDinhMuc='DA_CO' WHERE maMon=?")->execute([$maMon]);
                $json = fn(array $v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $pdo->prepare('INSERT INTO LichSuDinhMuc(maLichSu,maMon,maNguoiSua,nguoiSua,thoiGian,noiDung,congThucCu,congThucMoi)
                    VALUES (?,?,?,?,NOW(),?,?,?)')
                    ->execute([self::sinhMaChungTu('LS'), $maMon, $actor['maNhanVien'], $actor['hoTen'], mb_substr($noiDung, 0, 500),
                        $cu ? $json($cu) : null, $json($moi)]);
                return ['coThayDoi' => true, 'tenMon' => $mon['tenMon'], 'soThanhPhan' => count($moi)];
            });
            if (!$ketQua['coThayDoi']) { return self::ketQua(true, 'OK', 'Công thức không thay đổi nên không cần lưu.'); }
            return self::ketQua(true, 'OK', 'Đã lưu định mức cho món ' . $ketQua['tenMon'] . ' (' . $ketQua['soThanhPhan']
                . ' thành phần). Lịch sử thay đổi đã được ghi lại.');
        } catch (DinhMucLoi $loi) {
            return self::ketQua(false, $loi->ma, $loi->getMessage(), ['loiDong' => $loi->loiDong]);
        }
    }

    // ------------------------------------------------------------------ Nội bộ

    /** Kiểm tra cú pháp từng dòng; bỏ dòng trống hoàn toàn. Giữ nguyên chỉ số dòng để báo lỗi đúng vị trí. */
    private static function chuanHoa(array $danhSach): array
    {
        $dong = []; $loi = []; $daCo = [];
        if (count($danhSach) > self::SO_DONG_TOI_DA) {
            throw new DinhMucLoi('VALIDATION_ERROR', 'Một công thức tối đa ' . self::SO_DONG_TOI_DA . ' nguyên vật liệu.');
        }
        foreach (array_values($danhSach) as $i => $d) {
            $ma = trim((string)($d['maNVL'] ?? ''));
            $sl = str_replace([' ', ','], ['', '.'], trim((string)($d['dinhMuc'] ?? '')));
            if ($ma === '' && $sl === '') { continue; }
            if ($ma === '' || !preg_match('/^[A-Za-z0-9_-]{1,10}$/D', $ma)) { $loi[$i] = 'Chọn nguyên vật liệu.'; continue; }
            if (!preg_match('/^\d{1,4}(\.\d{1,3})?$/D', $sl) || (float)$sl <= 0 || (float)$sl > self::DINH_LUONG_TOI_DA) {
                $loi[$i] = 'Định lượng phải là số dương, tối đa 3 chữ số thập phân và không quá ' . self::DINH_LUONG_TOI_DA . '.'; continue;
            }
            if (isset($daCo[$ma])) { $loi[$i] = 'Nguyên vật liệu này đã có ở dòng ' . ($daCo[$ma] + 1) . '.'; continue; }
            $daCo[$ma] = $i;
            $dong[$i] = ['maNVL' => $ma, 'dinhMuc' => number_format((float)$sl, 3, '.', '')];
        }
        if ($loi) { throw new DinhMucLoi('VALIDATION_ERROR', 'Định mức chưa hợp lệ. Vui lòng kiểm tra các dòng được đánh dấu.', $loi); }
        if (!$dong) { throw new DinhMucLoi('VALIDATION_ERROR', 'Công thức cần ít nhất một nguyên vật liệu.'); }
        return $dong;
    }

    private static function docCongThuc(PDO $pdo, string $maMon, bool $khoa): array
    {
        $lenh = $pdo->prepare('SELECT d.maNVL,v.tenNVL,d.dinhMuc,v.donViTinh,v.trangThai FROM DinhMucNVL d
            JOIN NguyenVatLieu v ON v.maNVL=d.maNVL WHERE d.maMon=? ORDER BY d.maNVL' . ($khoa ? ' FOR UPDATE OF d' : ''));
        $lenh->execute([$maMon]);
        return array_map(fn($r) => ['maNVL' => $r['maNVL'], 'tenNVL' => $r['tenNVL'], 'dinhMuc' => $r['dinhMuc'],
            'donViTinh' => $r['donViTinh']] + ($khoa ? [] : ['trangThai' => $r['trangThai']]), $lenh->fetchAll());
    }

    public static function dauVanTay(array $congThuc): string
    {
        return hash('sha256', implode('|', array_map(fn($d) => $d['maNVL'] . ':' . $d['dinhMuc'], $congThuc)));
    }

    /** Mô tả ngắn: thêm/bỏ/đổi định lượng; chuỗi rỗng nếu không có thay đổi. */
    private static function moTaThayDoi(array $cu, array $moi): string
    {
        $a = array_column($cu, null, 'maNVL'); $b = array_column($moi, null, 'maNVL');
        $so = fn(string $v): string => str_replace('.', ',', rtrim(rtrim($v, '0'), '.'));
        $phan = [];
        foreach ($b as $ma => $d) {
            if (!isset($a[$ma])) { $phan[] = 'thêm ' . $d['tenNVL'] . ' ' . $so($d['dinhMuc']) . ' ' . $d['donViTinh']; }
            elseif ((float)$a[$ma]['dinhMuc'] !== (float)$d['dinhMuc']) {
                $phan[] = 'đổi ' . $d['tenNVL'] . ' ' . $so($a[$ma]['dinhMuc']) . ' → ' . $so($d['dinhMuc']) . ' ' . $d['donViTinh'];
            }
        }
        foreach ($a as $ma => $d) { if (!isset($b[$ma])) { $phan[] = 'bỏ ' . $d['tenNVL']; } }
        if (!$phan) { return ''; }
        return ($cu ? 'Cập nhật định mức: ' : 'Thiết lập định mức lần đầu: ') . implode('; ', $phan) . '.';
    }

    private static function sinhMaChungTu(string $tienTo): string
    {
        $bayGio = microtime(true);
        return $tienTo . date('ymdHis', (int)$bayGio) . sprintf('%03d', (int)(($bayGio - floor($bayGio)) * 1000))
            . substr(bin2hex(random_bytes(2)), 0, 3);
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
                if ($loi instanceof DinhMucLoi) { throw $loi; }
                if ($loi instanceof PDOException && in_array((int)($loi->errorInfo[1] ?? 0), [1213, 1205], true) && $lan < 3) { continue; }
                error_log('FBN dinh-muc: ' . $loi->getMessage());
                throw new DinhMucLoi('SERVER_ERROR', 'Không lưu được định mức. Vui lòng thử lại sau.');
            }
        }
    }

    private static function ketQua(bool $ok, string $code, string $message, array $data = []): array
    {
        return ['ok' => $ok, 'code' => $code, 'message' => $message, 'data' => $data];
    }
}
