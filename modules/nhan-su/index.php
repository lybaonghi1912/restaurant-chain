<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
/*
 * Quản lý nhân viên — SD-10 Xem, SD-08 Thêm, SD-07 Sửa, SD-11 Xóa (chuyển nghỉ việc).
 * Người lập trình: Lê Xuân Lộc. Xử lý nghiệp vụ nằm trong NhanSuControl.php; file này là giao diện (Boundary).
 * Không dùng các thuộc tính data-* / chữ nút mà assets/js/app.js đang bắt cho giao diện mẫu.
 */
require_permission('staff.view');
require_once __DIR__ . '/NhanSuControl.php';

// Process SD-29 before the staff form dispatcher; buffer its view until the layout.
$staffTransferContent = (static function (): string {
    ob_start();
    require __DIR__ . '/sd29.php';
    return ob_get_clean();
})();

$staffUrl = fn(array $q = []): string => 'index.php?' . http_build_query(['page' => 'staff'] + $q);
$cotForm = ['hoTen', 'maVaiTro', 'maChiNhanh', 'ngaySinh', 'gioiTinh', 'soCCCD', 'soDienThoai', 'email',
    'diaChiThuongTru', 'ngayBatDauLamViec', 'maCa', 'tenDangNhap'];

// ------------------------------------------------------------- Xử lý POST (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array(post_text('action'), ['create_staff_transfer_request', 'review_staff_transfer_request'], true)) {
    require_csrf();
    $hanhDong = post_text('hanhDong');
    $ma = post_text('maNhanVien');
    $duLieu = [];
    foreach ($cotForm as $cot) { $duLieu[$cot] = post_text($cot); }
    if ($hanhDong === 'them') {
        $kq = NhanSuControl::them($duLieu);
        if ($kq['ok']) {
            $_SESSION['ns_thong_bao'] = ['ok' => true, 'msg' => $kq['message'],
                'taiKhoan' => [$kq['data']['tenDangNhap'], $kq['data']['matKhauTam']]];
            redirect_to($staffUrl(['xem' => $kq['data']['maNhanVien']]));
        }
        $_SESSION['ns_form'] = ['input' => $duLieu, 'loi' => $kq['data']['loiTruong'] ?? []];
        $_SESSION['ns_thong_bao'] = ['ok' => false, 'msg' => $kq['message']];
        redirect_to($staffUrl(['them' => 1]));
    }
    if ($hanhDong === 'sua') {
        $kq = NhanSuControl::sua($ma, $duLieu, post_text('banGhi'));
        $_SESSION['ns_thong_bao'] = ['ok' => $kq['ok'], 'msg' => $kq['message']];
        if (!$kq['ok'] && $kq['code'] === 'VALIDATION_ERROR') {
            $_SESSION['ns_form'] = ['input' => $duLieu, 'loi' => $kq['data']['loiTruong'] ?? [], 'banGhi' => post_text('banGhi')];
            redirect_to($staffUrl(['sua' => $ma]));
        }
        redirect_to($kq['code'] === 'FORBIDDEN' ? $staffUrl() : $staffUrl(['xem' => $ma]));
    }
    if ($hanhDong === 'nghi_viec') {
        $kq = NhanSuControl::nghiViec($ma, post_text('lyDo'));
        $_SESSION['ns_thong_bao'] = ['ok' => $kq['ok'], 'msg' => $kq['message']];
        redirect_to($kq['code'] === 'FORBIDDEN' ? $staffUrl() : $staffUrl(['xem' => $ma]));
    }
    http_response_code(400);
    echo '<div class="screen-content"><p class="account-message error">Thao tác không hợp lệ.</p></div>';
    return;
}

// ------------------------------------------------------------- Dữ liệu hiển thị
$thongBao = $_SESSION['ns_thong_bao'] ?? null; unset($_SESSION['ns_thong_bao']);
$formCu = $_SESSION['ns_form'] ?? null; unset($_SESSION['ns_form']);
$getStr = fn(string $k): string => is_string($_GET[$k] ?? null) ? trim($_GET[$k]) : '';
$s = fn($v): string => e((string)($v ?? ''));
$ngayVN = fn(?string $d): string => $d ? date('d/m/Y', strtotime($d)) : '—';
$gioVN = fn(?string $d): string => $d ? date('H:i d/m/Y', strtotime($d)) : '—';
$vaiTroTen = array_column(NhanSuControl::danhSachVaiTro(), 'tenVaiTro', 'maVaiTro');
$caTen = [];
foreach (db()->query('SELECT maCa,tenCa,thoiGianBatDau,thoiGianKetThuc FROM CaLamViec')->fetchAll() as $ca) {
    $caTen[$ca['maCa']] = $ca['tenCa'] . ' · ' . substr($ca['thoiGianBatDau'], 0, 5) . '–' . substr($ca['thoiGianKetThuc'], 0, 5);
}
$chiNhanhTen = array_column(db()->query('SELECT maChiNhanh,tenChiNhanh FROM ChiNhanh')->fetchAll(), 'tenChiNhanh', 'maChiNhanh');
$trangThaiBadge = fn(string $t): string => $t === 'DANG_LAM'
    ? '<span class="badge badge-success">Đang làm việc</span>' : '<span class="badge badge-neutral">Đã nghỉ việc</span>';
$giaTriNhatKy = function (string $cot, $v) use ($vaiTroTen, $caTen, $chiNhanhTen, $ngayVN): string {
    if ($v === null || $v === '') { return '(trống)'; }
    return match ($cot) {
        'maVaiTro' => $vaiTroTen[$v] ?? (string)$v,
        'maChiNhanh' => $chiNhanhTen[$v] ?? (string)$v,
        'maCa' => $caTen[$v] ?? (string)$v,
        'gioiTinh' => NhanSuControl::GIOI_TINH[(int)$v] ?? (string)$v,
        'ngaySinh', 'ngayBatDauLamViec' => $ngayVN((string)$v),
        'trangThai' => ['DANG_LAM' => 'Đang làm việc', 'NGHI_VIEC' => 'Đã nghỉ việc'][$v] ?? (string)$v,
        'trangThaiTaiKhoan' => ['HOAT_DONG' => 'Hoạt động', 'KHOA' => 'Khóa'][$v] ?? (string)$v,
        default => (string)$v,
    };
};

$cheDo = 'danhSach';
$nhanVien = null;
foreach (['xem', 'sua', 'nghi'] as $k) {
    if ($getStr($k) !== '') {
        $nhanVien = NhanSuControl::chiTiet($getStr($k));
        if (!$nhanVien) {
            http_response_code(404);
            $thongBao = ['ok' => false, 'msg' => 'Không tìm thấy nhân viên trong phạm vi chi nhánh của bạn.'];
        } else { $cheDo = $k; }
        break;
    }
}
if ($getStr('them') !== '') { $cheDo = can('staff.manage') ? 'them' : 'danhSach'; }
$coTheQuanLyNV = $nhanVien && NhanSuControl::coTheQuanLy($nhanVien['maVaiTro'], $nhanVien['maChiNhanh']);
if (in_array($cheDo, ['sua', 'nghi'], true) && (!$coTheQuanLyNV || $nhanVien['trangThai'] !== 'DANG_LAM')) {
    http_response_code(403);
    $thongBao = ['ok' => false, 'msg' => 'Bạn không được thay đổi hồ sơ này (ngoài phạm vi quản lý hoặc nhân viên đã nghỉ việc).'];
    $cheDo = 'xem';
}
?>
<style>
.ns-wrap .account-message{margin-bottom:18px}
.ns-filter{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:13px 15px;border-bottom:1px solid var(--line);background:#fcfdff}
.ns-filter .control{width:auto;min-width:150px}
.ns-filter .ns-search{flex:1;min-width:220px}
.ns-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:15px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.ns-head-actions{display:flex;gap:8px;flex-wrap:wrap}
.ns-pager{display:flex;justify-content:space-between;align-items:center;padding:12px 15px;font-size:10px;color:var(--muted);border-top:1px solid var(--line)}
.ns-pager div{display:flex;gap:6px}
.ns-row-actions{display:flex;gap:6px;align-items:center;flex-wrap:nowrap}
.ns-name strong{display:block;font-size:10px}.ns-name span{font-size:8px;color:var(--muted)}
.ns-empty{padding:40px 15px;text-align:center;color:var(--muted);font-size:12px}
.ns-card{padding:22px;margin-bottom:18px}
.ns-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:18px}
.ns-hero h2{margin:6px 0}
.ns-badges{display:flex;gap:6px;flex-wrap:wrap}
.ns-info{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;border:1px solid var(--line);border-radius:12px;overflow:hidden}
.ns-info div{padding:12px 14px;border-bottom:1px solid var(--line);border-right:1px solid var(--line)}
.ns-info dt{font-size:8px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
.ns-info dd{margin:0;font-size:11px;font-weight:600;overflow-wrap:anywhere}
.ns-log{list-style:none;margin:0;padding:0}
.ns-log li{border-bottom:1px solid var(--line);padding:12px 0;font-size:11px;line-height:1.7}
.ns-log time{color:var(--muted);font-size:9px;display:block}
.ns-log table{margin-top:6px;border-collapse:collapse;font-size:10px}
.ns-log td{padding:3px 10px 3px 0;vertical-align:top}
.ns-log td:first-child{color:var(--muted)}
.ns-old{text-decoration:line-through;color:var(--muted)}
.ns-form .field-error{color:var(--danger);font-size:10px}
.ns-form .control.invalid{border-color:var(--danger)}
.ns-form .full{grid-column:1/-1}
.ns-req{color:var(--danger)}
.ns-secret{background:var(--warning-soft);color:#7a4c00;border-radius:10px;padding:14px 16px;margin-bottom:18px;font-size:12px;line-height:1.8}
.ns-secret code{font-family:monospace;font-size:14px;font-weight:700;background:#fff;padding:2px 8px;border-radius:6px}
.ns-danger-box{background:var(--danger-soft);color:#8f1d27;border-radius:10px;padding:14px 16px;font-size:12px;line-height:1.8;margin-bottom:16px}
.ns-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}
@media(max-width:900px){.ns-info{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.ns-info{grid-template-columns:1fr}.ns-filter .control{width:100%}}
</style>
<div class="screen-content ns-wrap">
<?= $staffTransferContent ?>
<p class="account-banner">Hồ sơ nhân viên ở trang này đọc và ghi trực tiếp MySQL. <?= $authUser['maVaiTro'] === 'CHU_CHUOI'
    ? 'Chủ chuỗi quản lý Quản lý chi nhánh, Phục vụ, Bếp, Kho trên toàn chuỗi; tài khoản Chủ chuỗi quản lý ở trang Tài khoản.'
    : 'Quản lý chi nhánh chỉ xem và quản lý nhân viên Phục vụ, Bếp, Kho của chi nhánh ' . e($authUser['tenChiNhanh'] ?? '') . '.' ?></p>
<?php if ($thongBao): ?><div class="account-message <?= $thongBao['ok'] ? 'success' : 'error' ?>" role="status"><?= e($thongBao['msg']) ?></div><?php endif; ?>
<?php if (!empty($thongBao['taiKhoan'])): ?>
<div class="ns-secret" role="alert"><strong>Tài khoản đăng nhập đã được tạo.</strong><br>
Tên đăng nhập: <code><?= e($thongBao['taiKhoan'][0]) ?></code> · Mật khẩu tạm: <code><?= e($thongBao['taiKhoan'][1]) ?></code><br>
Mật khẩu chỉ hiển thị một lần. Hãy chuyển riêng cho nhân viên và yêu cầu đổi mật khẩu sau lần đăng nhập đầu tiên.</div>
<?php endif; ?>

<?php if ($cheDo === 'danhSach'):
    $tieuChi = ['tuKhoa' => $getStr('q'), 'vaiTro' => $getStr('vaitro'), 'trangThai' => $getStr('trangthai') === '' ? 'DANG_LAM' : $getStr('trangthai'),
        'trang' => max(1, (int)$getStr('trang'))];
    if ($tieuChi['trangThai'] === 'ALL') { $tieuChi['trangThai'] = ''; }
    $kq = NhanSuControl::xem($tieuChi);
    $tk = NhanSuControl::thongKe();
    $locTrangThai = $getStr('trangthai') === '' ? 'DANG_LAM' : $getStr('trangthai');
?>
<div class="stat-row">
<div class="card stat"><div class="stat-top"><span class="muted">Tổng hồ sơ</span><span class="stat-dot dot-accent"></span></div><div class="stat-value"><?= $tk['tong'] ?></div><span class="muted">Trong phạm vi đang xem</span></div>
<div class="card stat"><div class="stat-top"><span class="muted">Đang làm việc</span><span class="stat-dot dot-success"></span></div><div class="stat-value"><?= $tk['dangLam'] ?></div><span class="text-success">Tài khoản có thể đăng nhập</span></div>
<div class="card stat"><div class="stat-top"><span class="muted">Đã nghỉ việc</span><span class="stat-dot dot-warning"></span></div><div class="stat-value"><?= $tk['nghiViec'] ?></div><span class="muted">Giữ lịch sử, tài khoản đã khóa</span></div>
<div class="card stat"><div class="stat-top"><span class="muted">Thiếu số CCCD</span><span class="stat-dot dot-info"></span></div><div class="stat-value"><?= $tk['thieuCCCD'] ?></div><span class="muted">Hồ sơ cũ cần bổ sung</span></div>
</div>
<section class="card data-card">
<div class="ns-head">
<h2 class="title title-sm">Danh sách nhân sự · <?= $kq['tong'] ?></h2>
<div class="ns-head-actions">
<?php /* Nút mẫu SD-29 "Yêu cầu điều chuyển" thuộc phần Trần Thị Như Ý — giữ nguyên, không xử lý ở module này. */ ?>
<button type="button" class="btn btn-ghost" data-modal="Yêu cầu điều chuyển nhân sự">Yêu cầu điều chuyển</button>
<?php if (can('staff.manage')): ?><a class="btn btn-primary" href="<?= e(url($staffUrl(['them' => 1]))) ?>">+ Thêm nhân viên</a><?php endif; ?>
</div></div>
<form class="ns-filter" method="get" action="<?= e(url('index.php')) ?>" role="search">
<input type="hidden" name="page" value="staff">
<input class="control ns-search" type="search" name="q" value="<?= e($tieuChi['tuKhoa']) ?>" maxlength="100" placeholder="Tìm tên, mã NV, SĐT, CCCD, tên đăng nhập..." aria-label="Từ khóa tìm kiếm">
<select class="control" name="vaitro" aria-label="Lọc vai trò"><option value="">Tất cả vai trò</option>
<?php foreach ($vaiTroTen as $maVT => $tenVT): ?><option value="<?= e($maVT) ?>" <?= $tieuChi['vaiTro'] === $maVT ? 'selected' : '' ?>><?= e($tenVT) ?></option><?php endforeach; ?></select>
<select class="control" name="trangthai" aria-label="Lọc trạng thái">
<option value="DANG_LAM" <?= $locTrangThai === 'DANG_LAM' ? 'selected' : '' ?>>Đang làm việc</option>
<option value="NGHI_VIEC" <?= $locTrangThai === 'NGHI_VIEC' ? 'selected' : '' ?>>Đã nghỉ việc</option>
<option value="ALL" <?= $locTrangThai === 'ALL' ? 'selected' : '' ?>>Tất cả trạng thái</option></select>
<button type="submit" class="btn btn-secondary">Lọc</button>
<?php if ($tieuChi['tuKhoa'] !== '' || $tieuChi['vaiTro'] !== '' || $locTrangThai !== 'DANG_LAM'): ?><a class="btn btn-ghost" href="<?= e(url($staffUrl())) ?>">Bỏ lọc</a><?php endif; ?>
</form>
<?php if (!$kq['danhSach']): ?>
<div class="ns-empty">Không có nhân viên phù hợp với điều kiện lọc.</div>
<?php else: ?>
<div class="table-scroll"><table class="data-table">
<thead><tr><th>Mã NV</th><th>Nhân viên</th><th>Vai trò</th><?php if ($authUser['maVaiTro'] === 'CHU_CHUOI'): ?><th>Chi nhánh</th><?php endif; ?><th>Ca làm việc</th><th>Số điện thoại</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
<tbody>
<?php foreach ($kq['danhSach'] as $nv): $quanLyDuoc = $nv['trangThai'] === 'DANG_LAM' && NhanSuControl::coTheQuanLy($nv['maVaiTro'], $nv['maChiNhanh']); ?>
<tr>
<td><?= e($nv['maNhanVien']) ?></td>
<td class="ns-name"><strong><?= e($nv['hoTen']) ?></strong><span><?= $nv['tenDangNhap'] ? '@' . e($nv['tenDangNhap']) : 'Chưa có tài khoản' ?></span></td>
<td><?= e($nv['tenVaiTro']) ?></td>
<?php if ($authUser['maVaiTro'] === 'CHU_CHUOI'): ?><td><?= $s($nv['tenChiNhanh'] ?? 'Toàn chuỗi') ?></td><?php endif; ?>
<td><?= $nv['tenCa'] ? e($nv['tenCa'] . ' · ' . substr($nv['thoiGianBatDau'], 0, 5) . '–' . substr($nv['thoiGianKetThuc'], 0, 5)) : '<span class="muted">Chưa xếp</span>' ?></td>
<td><?= $s($nv['soDienThoai'] ?: '—') ?></td>
<td><?= $trangThaiBadge($nv['trangThai']) ?></td>
<td><div class="ns-row-actions">
<a class="btn btn-secondary compact" href="<?= e(url($staffUrl(['xem' => $nv['maNhanVien']]))) ?>">Xem</a>
<?php if ($quanLyDuoc): ?><a class="btn btn-secondary compact" href="<?= e(url($staffUrl(['sua' => $nv['maNhanVien']]))) ?>">Sửa</a>
<a class="btn btn-ghost compact" href="<?= e(url($staffUrl(['nghi' => $nv['maNhanVien']]))) ?>">Xóa</a><?php endif; ?>
</div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if ($kq['soTrang'] > 1): $q = array_filter(['q' => $tieuChi['tuKhoa'], 'vaitro' => $tieuChi['vaiTro'], 'trangthai' => $locTrangThai]); ?>
<div class="ns-pager"><span>Trang <?= $kq['trang'] ?>/<?= $kq['soTrang'] ?></span><div>
<?php if ($kq['trang'] > 1): ?><a class="btn btn-secondary compact" href="<?= e(url($staffUrl($q + ['trang' => $kq['trang'] - 1]))) ?>">‹ Trước</a><?php endif; ?>
<?php if ($kq['trang'] < $kq['soTrang']): ?><a class="btn btn-secondary compact" href="<?= e(url($staffUrl($q + ['trang' => $kq['trang'] + 1]))) ?>">Sau ›</a><?php endif; ?>
</div></div>
<?php endif; ?>
<?php endif; ?>
</section>

<?php elseif ($cheDo === 'xem'): $nv = $nhanVien; $nhatKy = NhanSuControl::nhatKy($nv['maNhanVien']); ?>
<section class="card ns-card">
<div class="ns-hero"><div><span class="eyebrow accent">Hồ sơ nhân viên · <?= e($nv['maNhanVien']) ?></span>
<h2 class="title title-md"><?= e($nv['hoTen']) ?></h2>
<div class="ns-badges"><?= $trangThaiBadge($nv['trangThai']) ?><span class="badge badge-info"><?= e($nv['tenVaiTro']) ?></span>
<?php if ($nv['tenDangNhap']): ?><span class="badge badge-<?= $nv['trangThaiTaiKhoan'] === 'HOAT_DONG' ? 'success' : 'danger' ?>">Tài khoản <?= $nv['trangThaiTaiKhoan'] === 'HOAT_DONG' ? 'hoạt động' : 'đã khóa' ?></span><?php endif; ?></div></div>
<a class="btn btn-secondary" href="<?= e(url($staffUrl())) ?>">‹ Danh sách</a></div>
<dl class="ns-info">
<div><dt>Chi nhánh</dt><dd><?= $s($nv['tenChiNhanh'] ?? 'Toàn chuỗi') ?></dd></div>
<div><dt>Ca làm việc</dt><dd><?= $nv['maCa'] ? e($caTen[$nv['maCa']] ?? $nv['maCa']) : 'Chưa xếp ca' ?></dd></div>
<div><dt>Ngày bắt đầu làm việc</dt><dd><?= $ngayVN($nv['ngayBatDauLamViec']) ?></dd></div>
<div><dt>Ngày sinh</dt><dd><?= $ngayVN($nv['ngaySinh']) ?></dd></div>
<div><dt>Giới tính</dt><dd><?= $nv['gioiTinh'] === null ? '—' : e(NhanSuControl::GIOI_TINH[$nv['gioiTinh']]) ?></dd></div>
<div><dt>Số CCCD</dt><dd><?= $s($nv['soCCCD'] ?: 'Chưa bổ sung') ?></dd></div>
<div><dt>Số điện thoại</dt><dd><?= $s($nv['soDienThoai'] ?: '—') ?></dd></div>
<div><dt>Email</dt><dd><?= $s($nv['email'] ?: '—') ?></dd></div>
<div><dt>Tên đăng nhập</dt><dd><?= $s($nv['tenDangNhap'] ?: 'Chưa có tài khoản') ?></dd></div>
<div style="grid-column:1/-1"><dt>Địa chỉ thường trú</dt><dd><?= $s($nv['diaChiThuongTru'] ?: '—') ?></dd></div>
</dl>
<?php if ($coTheQuanLyNV && $nv['trangThai'] === 'DANG_LAM'): ?>
<div class="ns-actions"><a class="btn btn-primary" href="<?= e(url($staffUrl(['sua' => $nv['maNhanVien']]))) ?>">Sửa thông tin</a>
<a class="btn btn-secondary" href="<?= e(url($staffUrl(['nghi' => $nv['maNhanVien']]))) ?>">Xóa nhân viên (nghỉ việc)</a></div>
<?php endif; ?>
</section>
<section class="card ns-card"><h3 class="title title-sm">Nhật ký thay đổi</h3>
<?php if (!$nhatKy): ?><p class="muted" style="font-size:11px;margin-top:10px">Chưa có thay đổi nào được ghi nhận cho hồ sơ này.</p><?php else: ?>
<ul class="ns-log"><?php foreach ($nhatKy as $dong): $cu = json_decode((string)$dong['duLieuCu'], true) ?: []; $moi = json_decode((string)$dong['duLieuMoi'], true) ?: []; ?>
<li><time><?= $gioVN($dong['thoiGian']) ?> · <?= e($dong['nguoiSua']) ?></time><?= e($dong['noiDung']) ?>
<?php if ($cu || $moi): ?><table><?php foreach (array_unique(array_merge(array_keys($cu), array_keys($moi))) as $cot): ?>
<tr><td><?= e(NhanSuControl::NHAN_COT[$cot] ?? ($cot === 'trangThaiTaiKhoan' ? 'Tài khoản' : $cot)) ?></td><td>
<?php if (array_key_exists($cot, $cu)): ?><span class="ns-old"><?= e($giaTriNhatKy($cot, $cu[$cot])) ?></span> → <?php endif; ?><?= e($giaTriNhatKy($cot, $moi[$cot] ?? null)) ?></td></tr>
<?php endforeach; ?></table><?php endif; ?></li>
<?php endforeach; ?></ul><?php endif; ?>
</section>

<?php elseif ($cheDo === 'nghi'): $nv = $nhanVien; ?>
<section class="card ns-card" style="max-width:640px">
<span class="eyebrow accent">Xóa thông tin nhân viên</span>
<h2 class="title title-md" style="margin:8px 0 14px">Cho <?= e($nv['hoTen']) ?> (<?= e($nv['maNhanVien']) ?>) nghỉ việc?</h2>
<div class="ns-danger-box">Hồ sơ không bị xóa khỏi hệ thống để giữ lịch sử đơn hàng và yêu cầu. Khi xác nhận, hệ thống sẽ:
<br>• chuyển trạng thái nhân viên sang <strong>Đã nghỉ việc</strong>;
<br>• <strong>khóa tài khoản</strong> <?= $nv['tenDangNhap'] ? e($nv['tenDangNhap']) : '(nhân viên chưa có tài khoản)' ?> và đăng xuất mọi phiên đang mở;
<br>• ghi nhật ký người thực hiện và thời gian.</div>
<form method="post" action="<?= e(page_url('staff')) ?>" class="ns-form">
<?= csrf_input() ?><input type="hidden" name="hanhDong" value="nghi_viec"><input type="hidden" name="maNhanVien" value="<?= e($nv['maNhanVien']) ?>">
<label class="field"><span class="field-label">Lý do (không bắt buộc, tối đa 500 ký tự)</span>
<textarea class="control" name="lyDo" maxlength="500" rows="3" style="height:auto;padding:10px 12px"></textarea></label>
<div class="ns-actions"><button type="submit" class="btn btn-danger">Xác nhận nghỉ việc</button>
<a class="btn btn-secondary" href="<?= e(url($staffUrl(['xem' => $nv['maNhanVien']]))) ?>">Hủy bỏ</a></div>
</form></section>

<?php else: /* them | sua */
    $laSua = $cheDo === 'sua';
    $nguon = $formCu['input'] ?? ($laSua ? $nhanVien : ['ngayBatDauLamViec' => date('Y-m-d'), 'maVaiTro' => 'PHUC_VU',
        'maChiNhanh' => $authUser['maVaiTro'] === 'CHU_CHUOI' ? (current_branch_id() ?? '') : $authUser['maChiNhanh']]);
    $loi = $formCu['loi'] ?? [];
    $v = fn(string $k): string => e((string)($nguon[$k] ?? ''));
    $loiO = fn(string $k): string => isset($loi[$k]) ? '<span class="field-error">' . e($loi[$k]) . '</span>' : '';
    $cls = fn(string $k): string => 'control' . (isset($loi[$k]) ? ' invalid' : '');
    $banGhi = $laSua ? ($formCu['banGhi'] ?? NhanSuControl::dauVanTay($nhanVien)) : '';
    $vaiTroChon = array_intersect_key($vaiTroTen, array_flip(NhanSuControl::vaiTroDuocQuanLy()));
?>
<section class="card account-card">
<div class="ns-hero"><div><span class="eyebrow accent"><?= $laSua ? 'Chỉnh sửa thông tin nhân viên · ' . e($nhanVien['maNhanVien']) : 'Thêm thông tin nhân viên' ?></span>
<h2 class="title title-md"><?= $laSua ? e($nhanVien['hoTen']) : 'Hồ sơ nhân viên mới' ?></h2></div>
<a class="btn btn-secondary" href="<?= e(url($laSua ? $staffUrl(['xem' => $nhanVien['maNhanVien']]) : $staffUrl())) ?>">‹ Quay lại</a></div>
<p class="account-banner"><?= $laSua
    ? 'Đổi vai trò hoặc chi nhánh sẽ đăng xuất các phiên đang mở của nhân viên. Mọi thay đổi được ghi vào nhật ký.'
    : 'Mã nhân viên được hệ thống sinh tự động. Hệ thống tạo tài khoản đăng nhập kèm mật khẩu tạm và chỉ hiển thị mật khẩu một lần sau khi lưu.' ?>
 Ô có dấu <span class="ns-req">*</span> là bắt buộc.</p>
<form method="post" action="<?= e(page_url('staff')) ?>" class="account-form ns-form" novalidate>
<?= csrf_input() ?><input type="hidden" name="hanhDong" value="<?= $laSua ? 'sua' : 'them' ?>">
<?php if ($laSua): ?><input type="hidden" name="maNhanVien" value="<?= e($nhanVien['maNhanVien']) ?>"><input type="hidden" name="banGhi" value="<?= e($banGhi) ?>"><?php endif; ?>
<label class="field full"><span class="field-label">Họ tên <span class="ns-req">*</span></span><input class="<?= $cls('hoTen') ?>" name="hoTen" value="<?= $v('hoTen') ?>" maxlength="100" required><?= $loiO('hoTen') ?></label>
<label class="field"><span class="field-label">Vai trò <span class="ns-req">*</span></span><select class="<?= $cls('maVaiTro') ?>" name="maVaiTro" required>
<?php foreach ($vaiTroChon as $maVT => $tenVT): ?><option value="<?= e($maVT) ?>" <?= ($nguon['maVaiTro'] ?? '') === $maVT ? 'selected' : '' ?>><?= e($tenVT) ?></option><?php endforeach; ?>
</select><?= $loiO('maVaiTro') ?></label>
<label class="field"><span class="field-label">Chi nhánh <span class="ns-req">*</span></span>
<?php if ($authUser['maVaiTro'] === 'CHU_CHUOI'): ?><select class="<?= $cls('maChiNhanh') ?>" name="maChiNhanh" required><option value="">Chọn chi nhánh</option>
<?php foreach ($branches as $cn): ?><option value="<?= e($cn['maChiNhanh']) ?>" <?= ($nguon['maChiNhanh'] ?? '') === $cn['maChiNhanh'] ? 'selected' : '' ?>><?= e($cn['tenChiNhanh']) ?></option><?php endforeach; ?></select>
<?php else: ?><input class="control" value="<?= e($authUser['tenChiNhanh']) ?>" readonly><?php endif; ?><?= $loiO('maChiNhanh') ?></label>
<label class="field"><span class="field-label">Ngày sinh <span class="ns-req">*</span></span><input class="<?= $cls('ngaySinh') ?>" type="date" name="ngaySinh" value="<?= $v('ngaySinh') ?>" max="<?= date('Y-m-d', strtotime('-' . NhanSuControl::TUOI_TOI_THIEU . ' years')) ?>" required><?= $loiO('ngaySinh') ?></label>
<label class="field"><span class="field-label">Giới tính <span class="ns-req">*</span></span><select class="<?= $cls('gioiTinh') ?>" name="gioiTinh" required><option value="">Chọn giới tính</option>
<?php foreach (NhanSuControl::GIOI_TINH as $maGT => $tenGT): ?><option value="<?= $maGT ?>" <?= isset($nguon['gioiTinh']) && $nguon['gioiTinh'] !== '' && $nguon['gioiTinh'] !== null && (int)$nguon['gioiTinh'] === $maGT ? 'selected' : '' ?>><?= e($tenGT) ?></option><?php endforeach; ?></select><?= $loiO('gioiTinh') ?></label>
<label class="field"><span class="field-label">Số CCCD (12 số) <?= !$laSua || $nhanVien['soCCCD'] !== null ? '<span class="ns-req">*</span>' : '' ?></span><input class="<?= $cls('soCCCD') ?>" name="soCCCD" value="<?= $v('soCCCD') ?>" inputmode="numeric" maxlength="12" pattern="[0-9]{12}"><?= $loiO('soCCCD') ?></label>
<label class="field"><span class="field-label">Số điện thoại <span class="ns-req">*</span></span><input class="<?= $cls('soDienThoai') ?>" name="soDienThoai" value="<?= $v('soDienThoai') ?>" inputmode="tel" maxlength="15" placeholder="0xxxxxxxxx" required><?= $loiO('soDienThoai') ?></label>
<label class="field"><span class="field-label">Email</span><input class="<?= $cls('email') ?>" type="email" name="email" value="<?= $v('email') ?>" maxlength="100"><?= $loiO('email') ?></label>
<label class="field"><span class="field-label">Ngày bắt đầu làm việc <span class="ns-req">*</span></span><input class="<?= $cls('ngayBatDauLamViec') ?>" type="date" name="ngayBatDauLamViec" value="<?= $v('ngayBatDauLamViec') ?>" required><?= $loiO('ngayBatDauLamViec') ?></label>
<label class="field"><span class="field-label">Ca làm việc</span><select class="<?= $cls('maCa') ?>" name="maCa"><option value="">Chưa xếp ca</option>
<?php foreach (NhanSuControl::danhSachCa() as $ca): ?><option value="<?= e($ca['maCa']) ?>" <?= ($nguon['maCa'] ?? '') === $ca['maCa'] ? 'selected' : '' ?>><?= e($caTen[$ca['maCa']]) ?></option><?php endforeach; ?></select><?= $loiO('maCa') ?></label>
<label class="field full"><span class="field-label">Địa chỉ thường trú</span><input class="<?= $cls('diaChiThuongTru') ?>" name="diaChiThuongTru" value="<?= $v('diaChiThuongTru') ?>" maxlength="255"><?= $loiO('diaChiThuongTru') ?></label>
<?php if (!$laSua): ?>
<label class="field full"><span class="field-label">Tên đăng nhập <span class="ns-req">*</span></span><input class="<?= $cls('tenDangNhap') ?>" name="tenDangNhap" value="<?= $v('tenDangNhap') ?>" maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="off" placeholder="vd: lan.nguyen" required><?= $loiO('tenDangNhap') ?></label>
<?php endif; ?>
<div class="account-form-actions ns-actions" style="margin-top:4px"><button type="submit" class="btn btn-primary"><?= $laSua ? 'Lưu thay đổi' : 'Lưu nhân viên' ?></button>
<a class="btn btn-secondary" href="<?= e(url($laSua ? $staffUrl(['xem' => $nhanVien['maNhanVien']]) : $staffUrl())) ?>">Hủy</a></div>
</form>
</section>
<?php endif; ?>
</div>
