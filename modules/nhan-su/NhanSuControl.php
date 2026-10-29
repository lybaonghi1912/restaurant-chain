<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/**
 * NhanSuControl — SD-07 Sửa, SD-08 Thêm, SD-10 Xem, SD-11 Xóa (nghỉ việc) thông tin nhân viên.
 * Người lập trình: Lê Xuân Lộc. Actor: Quản lý chi nhánh (Chủ chuỗi dùng cùng màn hình cho toàn chuỗi).
 *
 * Quy tắc áp dụng: docs/database_v2/QUY_TAC_GIAO_DICH.md — R03 và "Hợp đồng lỗi dùng chung".
 *  - Phạm vi lấy từ phiên đăng nhập, không tin mã chi nhánh/người tạo gửi từ trình duyệt.
 *  - Quản lý: chỉ Phục vụ/Bếp/Kho thuộc chi nhánh của mình. Chủ chuỗi: thêm vai trò Quản lý.
 *    Tài khoản Chủ chuỗi chỉ quản lý ở trang Tài khoản.
 *  - Thêm = NhanVien + TaiKhoan + NhatKyNhanSu trong một transaction.
 *  - Sửa = khóa NhanVien → TaiKhoan, ghi nhật ký; đổi vai trò/chi nhánh thì tăng phienBan.
 *  - Xóa = NGHI_VIEC + khóa tài khoản + tăng phienBan + nhật ký; không DELETE.
 * Mọi phương thức ghi trả mảng ['ok','code','message','data'].
 */
final class NhanSuLoi extends RuntimeException
{
    /** @param array<string,string> $loiTruong lỗi theo từng ô nhập */
    public function __construct(public readonly string $ma, string $thongBao, public readonly array $loiTruong = [])
    {
        parent::__construct($thongBao);
    }
}

final class NhanSuControl
{
    /** Vai trò mỗi actor được quản lý ở màn hình nhân sự. */
    private const VAI_TRO_QUAN_LY_DUOC = [
        'CHU_CHUOI' => ['QUAN_LY', 'PHUC_VU', 'BEP', 'KHO'],
        'QUAN_LY' => ['PHUC_VU', 'BEP', 'KHO'],
    ];
    /** Quy ước giới tính theo báo cáo gốc: 1 = Nam, 0 = Nữ. */
    public const GIOI_TINH = [1 => 'Nam', 0 => 'Nữ'];
    public const TUOI_TOI_THIEU = 18;
    public const SO_DONG_MOI_TRANG = 20;
    /** Các cột hồ sơ được phép sửa; dùng cho nhật ký và so sánh thay đổi. */
    private const COT_HO_SO = ['hoTen', 'maVaiTro', 'maChiNhanh', 'ngaySinh', 'gioiTinh', 'soCCCD', 'soDienThoai',
        'email', 'diaChiThuongTru', 'ngayBatDauLamViec', 'maCa'];
    public const NHAN_COT = ['hoTen' => 'Họ tên', 'maVaiTro' => 'Vai trò', 'maChiNhanh' => 'Chi nhánh', 'ngaySinh' => 'Ngày sinh',
        'gioiTinh' => 'Giới tính', 'soCCCD' => 'Số CCCD', 'soDienThoai' => 'Số điện thoại', 'email' => 'Email',
        'diaChiThuongTru' => 'Địa chỉ thường trú', 'ngayBatDauLamViec' => 'Ngày bắt đầu làm việc', 'maCa' => 'Ca làm việc',
        'trangThai' => 'Trạng thái', 'tenDangNhap' => 'Tên đăng nhập', 'lyDo' => 'Lý do'];

    // ------------------------------------------------------------------ Phạm vi

    public static function vaiTroDuocQuanLy(): array
    {
        return self::VAI_TRO_QUAN_LY_DUOC[current_user()['maVaiTro'] ?? ''] ?? [];
    }

    /** Actor có được thêm/sửa/cho nghỉ hồ sơ mang vai trò và chi nhánh này không. */
    public static function coTheQuanLy(string $maVaiTro, ?string $maChiNhanh): bool
    {
        $actor = current_user();
        if (!$actor || !can('staff.manage') || !in_array($maVaiTro, self::vaiTroDuocQuanLy(), true)) { return false; }
        return $actor['maVaiTro'] === 'CHU_CHUOI' || ($maChiNhanh !== null && $maChiNhanh === $actor['maChiNhanh']);
    }

    /** SD-10: Quản lý chỉ xem nhân viên chi nhánh mình; Chủ chuỗi xem toàn chuỗi. */
    public static function coTheXem(array $nhanVien): bool
    {
        $actor = current_user();
        return $actor && can('staff.view')
            && ($actor['maVaiTro'] === 'CHU_CHUOI' || ($nhanVien['maChiNhanh'] ?? null) === $actor['maChiNhanh']);
    }

    // ------------------------------------------------------------------ SD-10 Xem

    /**
     * Danh sách nhân viên theo tiêu chí. Điều kiện chi nhánh luôn lấy từ phiên (branch_filter),
     * tách khỏi bộ lọc trên giao diện.
     * @param array{tuKhoa?:string,vaiTro?:string,trangThai?:string,trang?:int} $tieuChi
     */
    public static function xem(array $tieuChi): array
    {
        require_permission('staff.view');
        [$where, $params] = branch_filter('n.maChiNhanh');
        $dieuKien = [$where];
        $tuKhoa = trim((string)($tieuChi['tuKhoa'] ?? ''));
        if ($tuKhoa !== '') {
            $like = '%' . addcslashes(mb_substr($tuKhoa, 0, 100), '%_\\') . '%';
            $dieuKien[] = '(n.hoTen LIKE ? OR n.maNhanVien LIKE ? OR n.soDienThoai LIKE ? OR n.soCCCD LIKE ? OR t.tenDangNhap LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $vaiTro = (string)($tieuChi['vaiTro'] ?? '');
        if ($vaiTro !== '') { $dieuKien[] = 'n.maVaiTro = ?'; $params[] = $vaiTro; }
        $trangThai = (string)($tieuChi['trangThai'] ?? '');
        if (in_array($trangThai, ['DANG_LAM', 'NGHI_VIEC'], true)) { $dieuKien[] = 'n.trangThai = ?'; $params[] = $trangThai; }
        $tu = ' FROM NhanVien n JOIN VaiTro v ON v.maVaiTro=n.maVaiTro
            LEFT JOIN ChiNhanh c ON c.maChiNhanh=n.maChiNhanh
            LEFT JOIN CaLamViec ca ON ca.maCa=n.maCa
            LEFT JOIN TaiKhoan t ON t.maNhanVien=n.maNhanVien
            WHERE ' . implode(' AND ', $dieuKien);
        $dem = db()->prepare('SELECT COUNT(*)' . $tu);
        $dem->execute($params);
        $tong = (int)$dem->fetchColumn();
        $soTrang = max(1, (int)ceil($tong / self::SO_DONG_MOI_TRANG));
        $trang = min(max(1, (int)($tieuChi['trang'] ?? 1)), $soTrang);
        $sql = 'SELECT n.maNhanVien,n.hoTen,n.maVaiTro,v.tenVaiTro,n.maChiNhanh,c.tenChiNhanh,n.trangThai,n.soDienThoai,n.soCCCD,
                ca.tenCa,ca.thoiGianBatDau,ca.thoiGianKetThuc,t.tenDangNhap,t.trangThai AS trangThaiTaiKhoan'
            . $tu . ' ORDER BY n.trangThai, n.maChiNhanh IS NOT NULL, n.maChiNhanh, n.maNhanVien LIMIT '
            . self::SO_DONG_MOI_TRANG . ' OFFSET ' . (($trang - 1) * self::SO_DONG_MOI_TRANG);
        $lenh = db()->prepare($sql);
        $lenh->execute($params);
        return ['danhSach' => $lenh->fetchAll(), 'tong' => $tong, 'trang' => $trang, 'soTrang' => $soTrang];
    }

    /** Số liệu tổng hợp trong phạm vi chi nhánh của phiên. */
    public static function thongKe(): array
    {
        require_permission('staff.view');
        [$where, $params] = branch_filter('maChiNhanh');
        $lenh = db()->prepare("SELECT COUNT(*) tong, COALESCE(SUM(trangThai='DANG_LAM'),0) dangLam,
            COALESCE(SUM(trangThai='NGHI_VIEC'),0) nghiViec, COALESCE(SUM(trangThai='DANG_LAM' AND soCCCD IS NULL),0) thieuCCCD
            FROM NhanVien WHERE $where");
        $lenh->execute($params);
        return array_map('intval', $lenh->fetch());
    }

    /** Chi tiết một nhân viên (đã kiểm tra phạm vi xem); null nếu không có hoặc ngoài phạm vi. */
    public static function chiTiet(string $maNhanVien): ?array
    {
        require_permission('staff.view');
        $nv = self::docNhanVien(db(), $maNhanVien, false);
        return $nv && self::coTheXem($nv) ? $nv : null;
    }

    public static function nhatKy(string $maNhanVien, int $gioiHan = 20): array
    {
        $lenh = db()->prepare('SELECT thoiGian,nguoiSua,noiDung,duLieuCu,duLieuMoi FROM NhatKyNhanSu
            WHERE maNhanVien=? ORDER BY thoiGian DESC, maNhatKy DESC LIMIT ' . max(1, min(100, $gioiHan)));
        $lenh->execute([$maNhanVien]);
        return $lenh->fetchAll();
    }

    // ------------------------------------------------------------------ SD-08 Thêm

    /** @param array<string,string> $duLieu dữ liệu form */
    public static function them(array $duLieu): array
    {
        require_permission('staff.manage');
        $actor = current_user();
        try {
            $hoSo = self::chuanHoa($duLieu, null);
            $tenDangNhap = trim($duLieu['tenDangNhap'] ?? '');
            if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/D', $tenDangNhap)) {
                throw new NhanSuLoi('VALIDATION_ERROR', 'Dữ liệu chưa hợp lệ.',
                    ['tenDangNhap' => 'Tên đăng nhập gồm 3–50 ký tự chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.']);
            }
            $matKhauTam = 'Fbn@' . bin2hex(random_bytes(8));
            $maMoi = self::giaoDich(function (PDO $pdo) use ($hoSo, $tenDangNhap, $matKhauTam, $actor): string {
                self::kiemTraChiNhanhHoatDong($pdo, $hoSo['maChiNhanh']);
                self::kiemTraTrungCCCD($pdo, $hoSo['soCCCD'], null);
                $trung = $pdo->prepare('SELECT 1 FROM TaiKhoan WHERE tenDangNhap=? FOR UPDATE');
                $trung->execute([$tenDangNhap]);
                if ($trung->fetchColumn()) {
                    throw new NhanSuLoi('VALIDATION_ERROR', 'Dữ liệu chưa hợp lệ.', ['tenDangNhap' => 'Tên đăng nhập đã tồn tại.']);
                }
                // YeuCauSinhMaNV: mã kế tiếp; nếu trùng do tạo đồng thời, khóa chính từ chối và giaoDich() thử lại.
                $ma = self::sinhMaNhanVien($pdo);
                $cot = self::COT_HO_SO;
                $pdo->prepare('INSERT INTO NhanVien(maNhanVien,' . implode(',', $cot) . ',trangThai) VALUES (?'
                    . str_repeat(',?', count($cot)) . ",'DANG_LAM')")
                    ->execute(array_merge([$ma], array_map(fn($c) => $hoSo[$c], $cot)));
                $pdo->prepare("INSERT INTO TaiKhoan(tenDangNhap,matKhau,maNhanVien,trangThai) VALUES (?,?,?,'HOAT_DONG')")
                    ->execute([$tenDangNhap, password_hash($matKhauTam, PASSWORD_ARGON2ID), $ma]);
                self::ghiNhatKy($pdo, $ma, $actor, 'Thêm nhân viên mới và tạo tài khoản ' . $tenDangNhap . '.',
                    null, $hoSo + ['trangThai' => 'DANG_LAM', 'tenDangNhap' => $tenDangNhap]);
                return $ma;
            }, 'fbn_nhan_su_them');
            return self::ketQua(true, 'OK', 'Đã thêm nhân viên ' . $hoSo['hoTen'] . ' (' . $maMoi . ').',
                ['maNhanVien' => $maMoi, 'tenDangNhap' => $tenDangNhap, 'matKhauTam' => $matKhauTam]);
        } catch (NhanSuLoi $loi) {
            return self::ketQua(false, $loi->ma, $loi->getMessage(), ['loiTruong' => $loi->loiTruong]);
        }
    }

    // ------------------------------------------------------------------ SD-07 Sửa

    /** @param string $banGhi dấu vân tay hồ sơ lúc mở form, để phát hiện người khác vừa sửa */
    public static function sua(string $maNhanVien, array $duLieu, string $banGhi): array
    {
        require_permission('staff.manage');
        $actor = current_user();
        try {
            $ketQua = self::giaoDich(function (PDO $pdo) use ($maNhanVien, $duLieu, $banGhi, $actor): array {
                $cu = self::docNhanVien($pdo, $maNhanVien, true); // khóa NhanVien → TaiKhoan
                if (!$cu || !self::coTheQuanLy($cu['maVaiTro'], $cu['maChiNhanh'])) {
                    throw new NhanSuLoi('FORBIDDEN', 'Bạn không được sửa hồ sơ nhân viên này.');
                }
                if ($cu['trangThai'] !== 'DANG_LAM') {
                    throw new NhanSuLoi('CONFLICT', 'Nhân viên đã nghỉ việc; hồ sơ chỉ được xem.');
                }
                if (!hash_equals(self::dauVanTay($cu), $banGhi)) {
                    throw new NhanSuLoi('CONFLICT', 'Hồ sơ vừa được người khác cập nhật. Vui lòng mở lại form và nhập lại thay đổi.');
                }
                $moi = self::chuanHoa($duLieu, $cu);
                $thayDoi = array_values(array_filter(self::COT_HO_SO, fn($c) => (string)$cu[$c] !== (string)$moi[$c]));
                if (!$thayDoi) { return ['coThayDoi' => false]; }
                $doiPhamVi = $cu['maVaiTro'] !== $moi['maVaiTro'] || $cu['maChiNhanh'] !== $moi['maChiNhanh'];
                if ($doiPhamVi) {
                    self::kiemTraKhongLaQuanLyChiNhanh($pdo, $maNhanVien, 'đổi vai trò hoặc chi nhánh');
                    self::kiemTraChiNhanhHoatDong($pdo, $moi['maChiNhanh']);
                }
                self::kiemTraTrungCCCD($pdo, $moi['soCCCD'], $maNhanVien);
                $pdo->prepare('UPDATE NhanVien SET ' . implode('=?,', self::COT_HO_SO) . '=? WHERE maNhanVien=?')
                    ->execute(array_merge(array_map(fn($c) => $moi[$c], self::COT_HO_SO), [$maNhanVien]));
                if ($doiPhamVi && $cu['tenDangNhap'] !== null) {
                    // Đổi vai trò/phạm vi: phiên đăng nhập cũ của nhân viên mất hiệu lực.
                    $pdo->prepare('UPDATE TaiKhoan SET phienBan=phienBan+1 WHERE maNhanVien=?')->execute([$maNhanVien]);
                }
                $truoc = $sau = [];
                foreach ($thayDoi as $c) { $truoc[$c] = $cu[$c]; $sau[$c] = $moi[$c]; }
                self::ghiNhatKy($pdo, $maNhanVien, $actor, 'Cập nhật ' . implode(', ', array_map(fn($c) => mb_strtolower(self::NHAN_COT[$c]), $thayDoi))
                    . ($doiPhamVi ? '; thu hồi phiên đăng nhập cũ.' : '.'), $truoc, $sau);
                return ['coThayDoi' => true, 'hoTen' => $moi['hoTen'], 'doiPhamVi' => $doiPhamVi];
            });
            if (!$ketQua['coThayDoi']) { return self::ketQua(true, 'OK', 'Không có thay đổi nào để lưu.'); }
            return self::ketQua(true, 'OK', 'Đã cập nhật hồ sơ ' . $ketQua['hoTen'] . '.'
                . ($ketQua['doiPhamVi'] ? ' Các phiên đăng nhập cũ của nhân viên đã bị thu hồi.' : ''), ['maNhanVien' => $maNhanVien]);
        } catch (NhanSuLoi $loi) {
            return self::ketQua(false, $loi->ma, $loi->getMessage(), ['loiTruong' => $loi->loiTruong]);
        }
    }

    // ------------------------------------------------------------------ SD-11 Xóa (nghỉ việc)

    public static function nghiViec(string $maNhanVien, string $lyDo): array
    {
        require_permission('staff.manage');
        $actor = current_user();
        $lyDo = trim($lyDo);
        if (mb_strlen($lyDo) > 500) {
            return self::ketQua(false, 'VALIDATION_ERROR', 'Lý do tối đa 500 ký tự.');
        }
        try {
            $hoTen = self::giaoDich(function (PDO $pdo) use ($maNhanVien, $lyDo, $actor): string {
                $cu = self::docNhanVien($pdo, $maNhanVien, true);
                if (!$cu || !self::coTheQuanLy($cu['maVaiTro'], $cu['maChiNhanh']) || $cu['maNhanVien'] === $actor['maNhanVien']) {
                    throw new NhanSuLoi('FORBIDDEN', 'Bạn không được xóa hồ sơ nhân viên này.');
                }
                if ($cu['trangThai'] === 'NGHI_VIEC') {
                    throw new NhanSuLoi('CONFLICT', 'Nhân viên này đã ở trạng thái nghỉ việc.');
                }
                self::kiemTraKhongLaQuanLyChiNhanh($pdo, $maNhanVien, 'cho nghỉ việc');
                $pdo->prepare("UPDATE NhanVien SET trangThai='NGHI_VIEC' WHERE maNhanVien=?")->execute([$maNhanVien]);
                if ($cu['tenDangNhap'] !== null) {
                    $pdo->prepare("UPDATE TaiKhoan SET trangThai='KHOA',phienBan=phienBan+1 WHERE maNhanVien=?")->execute([$maNhanVien]);
                }
                self::ghiNhatKy($pdo, $maNhanVien, $actor,
                    'Chuyển trạng thái nghỉ việc, khóa tài khoản và thu hồi phiên đăng nhập.' . ($lyDo !== '' ? ' Lý do: ' . $lyDo : ''),
                    ['trangThai' => 'DANG_LAM', 'trangThaiTaiKhoan' => $cu['trangThaiTaiKhoan']],
                    ['trangThai' => 'NGHI_VIEC', 'trangThaiTaiKhoan' => $cu['tenDangNhap'] !== null ? 'KHOA' : null, 'lyDo' => $lyDo ?: null]);
                return $cu['hoTen'];
            });
            return self::ketQua(true, 'OK', 'Đã chuyển ' . $hoTen . ' sang nghỉ việc và khóa tài khoản. Lịch sử hồ sơ được giữ nguyên.',
                ['maNhanVien' => $maNhanVien]);
        } catch (NhanSuLoi $loi) {
            return self::ketQua(false, $loi->ma, $loi->getMessage());
        }
    }

    // ------------------------------------------------------------------ Danh mục cho form

    public static function danhSachVaiTro(): array
    {
        return db()->query("SELECT maVaiTro,tenVaiTro FROM VaiTro
            ORDER BY FIELD(maVaiTro,'CHU_CHUOI','QUAN_LY','PHUC_VU','BEP','KHO')")->fetchAll();
    }

    public static function danhSachCa(): array
    {
        return db()->query("SELECT maCa,tenCa,thoiGianBatDau,thoiGianKetThuc FROM CaLamViec WHERE trangThai='HOAT_DONG' ORDER BY thoiGianBatDau")->fetchAll();
    }

    public static function dauVanTay(array $nv): string
    {
        $giaTri = [];
        foreach (self::COT_HO_SO as $c) { $giaTri[$c] = $nv[$c] === null ? null : (string)$nv[$c]; }
        $giaTri['trangThai'] = $nv['trangThai'];
        return hash('sha256', json_encode($giaTri, JSON_UNESCAPED_UNICODE));
    }

    // ------------------------------------------------------------------ Nội bộ

    /** Đọc hồ sơ kèm tài khoản; $khoa=true khóa NhanVien rồi TaiKhoan theo R03. */
    private static function docNhanVien(PDO $pdo, string $ma, bool $khoa): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,10}$/D', $ma)) { return null; }
        $lenh = $pdo->prepare('SELECT n.*, v.tenVaiTro, c.tenChiNhanh, ca.tenCa, ca.thoiGianBatDau, ca.thoiGianKetThuc
            FROM NhanVien n JOIN VaiTro v ON v.maVaiTro=n.maVaiTro LEFT JOIN ChiNhanh c ON c.maChiNhanh=n.maChiNhanh
            LEFT JOIN CaLamViec ca ON ca.maCa=n.maCa WHERE n.maNhanVien=?' . ($khoa ? ' FOR UPDATE OF n' : ''));
        $lenh->execute([$ma]);
        $nv = $lenh->fetch();
        if (!$nv) { return null; }
        $tk = $pdo->prepare('SELECT tenDangNhap,trangThai,lanDangNhapCuoi FROM TaiKhoan WHERE maNhanVien=?' . ($khoa ? ' FOR UPDATE' : ''));
        $tk->execute([$ma]);
        $taiKhoan = $tk->fetch() ?: ['tenDangNhap' => null, 'trangThai' => null, 'lanDangNhapCuoi' => null];
        $nv['tenDangNhap'] = $taiKhoan['tenDangNhap'];
        $nv['trangThaiTaiKhoan'] = $taiKhoan['trangThai'];
        $nv['lanDangNhapCuoi'] = $taiKhoan['lanDangNhapCuoi'];
        if ($nv['gioiTinh'] !== null) { $nv['gioiTinh'] = (int)$nv['gioiTinh']; }
        return $nv;
    }

    /**
     * KiemTraCuPhap: chuẩn hóa và kiểm tra dữ liệu form. $cu = null khi thêm mới.
     * Chi nhánh của Quản lý luôn lấy từ phiên; vai trò phải thuộc phạm vi được quản lý.
     */
    private static function chuanHoa(array $d, ?array $cu): array
    {
        $actor = current_user();
        $loi = [];
        $chu = fn(string $k): string => trim(preg_replace('/\s+/u', ' ', (string)($d[$k] ?? '')));
        $hoSo = [];

        $hoSo['hoTen'] = $chu('hoTen');
        if ($hoSo['hoTen'] === '' || mb_strlen($hoSo['hoTen']) > 100) { $loi['hoTen'] = 'Họ tên bắt buộc, tối đa 100 ký tự.'; }
        elseif (!preg_match('/^[\p{L}][\p{L}\s.\'-]*$/u', $hoSo['hoTen'])) { $loi['hoTen'] = 'Họ tên chỉ gồm chữ cái và khoảng trắng.'; }

        $hoSo['maVaiTro'] = (string)($d['maVaiTro'] ?? '');
        if (!in_array($hoSo['maVaiTro'], self::vaiTroDuocQuanLy(), true)) { $loi['maVaiTro'] = 'Bạn không được gán vai trò này.'; }

        $hoSo['maChiNhanh'] = $actor['maVaiTro'] === 'CHU_CHUOI' ? (string)($d['maChiNhanh'] ?? '') : $actor['maChiNhanh'];
        if (!in_array($hoSo['maChiNhanh'], array_column(active_branches(), 'maChiNhanh'), true)) {
            $loi['maChiNhanh'] = 'Vui lòng chọn chi nhánh đang hoạt động.';
        }

        $hoSo['ngaySinh'] = self::ngay($chu('ngaySinh'));
        if ($hoSo['ngaySinh'] === null) { $loi['ngaySinh'] = 'Ngày sinh bắt buộc, đúng định dạng ngày.'; }
        else {
            $tuoi = (new DateTimeImmutable($hoSo['ngaySinh']))->diff(new DateTimeImmutable('today'));
            if ($tuoi->invert || $tuoi->y < self::TUOI_TOI_THIEU) { $loi['ngaySinh'] = 'Nhân viên phải đủ ' . self::TUOI_TOI_THIEU . ' tuổi.'; }
            elseif ($tuoi->y > 80) { $loi['ngaySinh'] = 'Ngày sinh không hợp lệ.'; }
        }

        $gioiTinh = (string)($d['gioiTinh'] ?? '');
        $hoSo['gioiTinh'] = in_array($gioiTinh, ['0', '1'], true) ? (int)$gioiTinh : null;
        if ($hoSo['gioiTinh'] === null) { $loi['gioiTinh'] = 'Vui lòng chọn giới tính.'; }

        // CCCD: bắt buộc với hồ sơ mới; hồ sơ cũ chưa có CCCD được để trống, nhưng đã có thì không xóa trắng.
        $hoSo['soCCCD'] = preg_replace('/\s+/', '', $chu('soCCCD'));
        if ($hoSo['soCCCD'] === '') {
            $hoSo['soCCCD'] = null;
            if ($cu === null || $cu['soCCCD'] !== null) { $loi['soCCCD'] = 'Số CCCD bắt buộc.'; }
        } elseif (!preg_match('/^[0-9]{12}$/D', $hoSo['soCCCD'])) { $loi['soCCCD'] = 'Số CCCD gồm đúng 12 chữ số.'; }

        $hoSo['soDienThoai'] = preg_replace('/[\s.]+/', '', $chu('soDienThoai'));
        if (!preg_match('/^0[0-9]{9}$/D', $hoSo['soDienThoai'])) { $loi['soDienThoai'] = 'Số điện thoại gồm 10 chữ số, bắt đầu bằng 0.'; }

        $hoSo['email'] = $chu('email');
        if ($hoSo['email'] === '') { $hoSo['email'] = null; }
        elseif (mb_strlen($hoSo['email']) > 100 || !filter_var($hoSo['email'], FILTER_VALIDATE_EMAIL)) { $loi['email'] = 'Email không hợp lệ.'; }

        $hoSo['diaChiThuongTru'] = $chu('diaChiThuongTru');
        if ($hoSo['diaChiThuongTru'] === '') { $hoSo['diaChiThuongTru'] = null; }
        elseif (mb_strlen($hoSo['diaChiThuongTru']) > 255) { $loi['diaChiThuongTru'] = 'Địa chỉ tối đa 255 ký tự.'; }

        $hoSo['ngayBatDauLamViec'] = self::ngay($chu('ngayBatDauLamViec'));
        if ($hoSo['ngayBatDauLamViec'] === null) { $loi['ngayBatDauLamViec'] = 'Ngày bắt đầu làm việc bắt buộc.'; }
        elseif ($hoSo['ngaySinh'] !== null && $hoSo['ngayBatDauLamViec'] <= $hoSo['ngaySinh']) { $loi['ngayBatDauLamViec'] = 'Ngày bắt đầu phải sau ngày sinh.'; }
        elseif ($hoSo['ngayBatDauLamViec'] > (new DateTimeImmutable('today +1 year'))->format('Y-m-d')) { $loi['ngayBatDauLamViec'] = 'Ngày bắt đầu không quá 1 năm kể từ hôm nay.'; }

        $hoSo['maCa'] = (string)($d['maCa'] ?? '');
        if ($hoSo['maCa'] === '') { $hoSo['maCa'] = null; }
        elseif (!in_array($hoSo['maCa'], array_column(self::danhSachCa(), 'maCa'), true)
            && !($cu !== null && $cu['maCa'] === $hoSo['maCa'])) { $loi['maCa'] = 'Ca làm việc không hợp lệ.'; }

        if ($loi) { throw new NhanSuLoi('VALIDATION_ERROR', 'Dữ liệu chưa hợp lệ. Vui lòng kiểm tra các ô được đánh dấu.', $loi); }
        if ($cu !== null && !self::coTheQuanLy($hoSo['maVaiTro'], $hoSo['maChiNhanh'])) {
            throw new NhanSuLoi('FORBIDDEN', 'Bạn không được chuyển nhân viên ra ngoài phạm vi quản lý.');
        }
        return $hoSo;
    }

    private static function ngay(string $giaTri): ?string
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $giaTri);
        return $d && $d->format('Y-m-d') === $giaTri && $giaTri >= '1900-01-01' ? $giaTri : null;
    }

    private static function kiemTraTrungCCCD(PDO $pdo, ?string $cccd, ?string $tru): void
    {
        if ($cccd === null) { return; }
        $lenh = $pdo->prepare('SELECT maNhanVien FROM NhanVien WHERE soCCCD=? AND maNhanVien<>? FOR SHARE');
        $lenh->execute([$cccd, $tru ?? '']);
        if ($lenh->fetchColumn()) {
            throw new NhanSuLoi('VALIDATION_ERROR', 'Dữ liệu chưa hợp lệ.', ['soCCCD' => 'Số CCCD đã thuộc hồ sơ nhân viên khác.']);
        }
    }

    private static function kiemTraChiNhanhHoatDong(PDO $pdo, string $maChiNhanh): void
    {
        $lenh = $pdo->prepare('SELECT trangThai FROM ChiNhanh WHERE maChiNhanh=? FOR SHARE');
        $lenh->execute([$maChiNhanh]);
        if ($lenh->fetchColumn() !== 'HOAT_DONG') {
            throw new NhanSuLoi('CONFLICT', 'Chi nhánh không còn hoạt động.');
        }
    }

    /** ChiNhanh.maQuanLy tham chiếu (maNhanVien,maChiNhanh); cần đổi quản lý ở module Chi nhánh trước. */
    private static function kiemTraKhongLaQuanLyChiNhanh(PDO $pdo, string $maNhanVien, string $thaoTac): void
    {
        $lenh = $pdo->prepare('SELECT tenChiNhanh FROM ChiNhanh WHERE maQuanLy=? FOR SHARE');
        $lenh->execute([$maNhanVien]);
        $ten = $lenh->fetchColumn();
        if ($ten !== false) {
            throw new NhanSuLoi('CONFLICT', 'Nhân viên đang là quản lý của chi nhánh ' . $ten
                . '. Cần đổi quản lý chi nhánh ở trang Chi nhánh trước khi ' . $thaoTac . '.');
        }
    }

    private static function sinhMaNhanVien(PDO $pdo): string
    {
        $so = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(maNhanVien,3) AS UNSIGNED)),0)
            FROM NhanVien WHERE maNhanVien REGEXP '^NV[0-9]{4,8}$'")->fetchColumn();
        return 'NV' . str_pad((string)($so + 1), 4, '0', STR_PAD_LEFT);
    }

    /** Mã 20 ký tự: tiền tố + yyMMddHHmmss + mili giây + 3 hex ngẫu nhiên; tăng theo thời gian, không dùng COUNT(*)+1. */
    private static function sinhMaChungTu(string $tienTo): string
    {
        $bayGio = microtime(true);
        return $tienTo . date('ymdHis', (int)$bayGio) . sprintf('%03d', (int)(($bayGio - floor($bayGio)) * 1000))
            . substr(bin2hex(random_bytes(2)), 0, 3);
    }

    private static function ghiNhatKy(PDO $pdo, string $maNhanVien, array $actor, string $noiDung, ?array $cu, ?array $moi): void
    {
        $json = fn(?array $v) => $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $pdo->prepare('INSERT INTO NhatKyNhanSu(maNhatKy,maNhanVien,thoiGian,maNguoiSua,nguoiSua,noiDung,duLieuCu,duLieuMoi)
            VALUES (?,?,NOW(),?,?,?,?,?)')
            ->execute([self::sinhMaChungTu('NK'), $maNhanVien, $actor['maNhanVien'], $actor['hoTen'],
                mb_substr($noiDung, 0, 1000), $json($cu), $json($moi)]);
    }

    /**
     * Chạy một transaction; rollback khi có lỗi bất kỳ. Thử lại tối đa 3 lần khi deadlock/lock timeout
     * hoặc khi mã nhân viên sinh ra bị trùng do hai người thêm cùng lúc.
     * $khoaTen: khóa tên của MySQL (GET_LOCK) để xếp hàng các thao tác cùng loại (thêm nhân viên → sinh mã kế tiếp),
     * tránh tranh chấp sinh mã và deadlock khóa khoảng khi kiểm tra trùng; không khóa bảng dùng chung.
     */
    private static function giaoDich(callable $congViec, ?string $khoaTen = null): mixed
    {
        $pdo = db();
        if ($khoaTen !== null) {
            $lay = $pdo->prepare('SELECT GET_LOCK(?, 10)');
            $lay->execute([$khoaTen]);
            if ((int)$lay->fetchColumn() !== 1) { throw new NhanSuLoi('CONFLICT', 'Hệ thống đang bận xử lý yêu cầu khác. Vui lòng thử lại.'); }
            try { return self::giaoDich($congViec); }
            finally { $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$khoaTen]); }
        }
        for ($lan = 1; ; $lan++) {
            $pdo->beginTransaction();
            try {
                $ketQua = $congViec($pdo);
                $pdo->commit();
                return $ketQua;
            } catch (Throwable $loi) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                if ($loi instanceof NhanSuLoi) { throw $loi; }
                $thongDiep = $loi->getMessage();
                $thuLai = $loi instanceof PDOException && (in_array((int)($loi->errorInfo[1] ?? 0), [1213, 1205], true)
                    || ((int)($loi->errorInfo[1] ?? 0) === 1062 && stripos($thongDiep, 'nhanvien.PRIMARY') !== false));
                if ($thuLai && $lan < 3) { continue; }
                if ($loi instanceof PDOException && (int)($loi->errorInfo[1] ?? 0) === 1062) {
                    if (stripos($thongDiep, 'cccd') !== false) {
                        throw new NhanSuLoi('VALIDATION_ERROR', 'Dữ liệu chưa hợp lệ.', ['soCCCD' => 'Số CCCD đã thuộc hồ sơ nhân viên khác.']);
                    }
                    if (stripos($thongDiep, 'taikhoan') !== false) {
                        throw new NhanSuLoi('VALIDATION_ERROR', 'Dữ liệu chưa hợp lệ.', ['tenDangNhap' => 'Tên đăng nhập đã tồn tại.']);
                    }
                }
                error_log('FBN nhan-su: ' . $thongDiep);
                throw new NhanSuLoi('SERVER_ERROR', 'Không lưu được dữ liệu. Vui lòng thử lại sau.');
            }
        }
    }

    private static function ketQua(bool $ok, string $code, string $message, array $data = []): array
    {
        return ['ok' => $ok, 'code' => $code, 'message' => $message, 'data' => $data];
    }
}
