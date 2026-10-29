<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
/*
 * Định mức nguyên vật liệu — SD-12. Người lập trình: Lê Xuân Lộc. Actor: Chủ chuỗi.
 * Nghiệp vụ trong DinhMucControl.php. Giao diện không dùng data-dish / data-add-ingredient / .recipe-footer
 * của giao diện mẫu để assets/js/app.js không chen vào form thật.
 */
require_permission('bom.view');
require_once __DIR__ . '/DinhMucControl.php';

$bomUrl = fn(array $q = []): string => 'index.php?' . http_build_query(['page' => 'bom'] + $q);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $maMon = post_text('maMon');
    $dsNVL = is_array($_POST['nvl'] ?? null) ? $_POST['nvl'] : [];
    $dsSL = is_array($_POST['dinhMuc'] ?? null) ? $_POST['dinhMuc'] : [];
    $dong = [];
    foreach (array_values($dsNVL) as $i => $ma) {
        $dong[] = ['maNVL' => is_string($ma) ? $ma : '', 'dinhMuc' => is_string($dsSL[$i] ?? null) ? $dsSL[$i] : ''];
    }
    $kq = DinhMucControl::luuCongThuc($maMon, $dong, post_text('banGhi'));
    $_SESSION['dm_thong_bao'] = ['ok' => $kq['ok'], 'msg' => $kq['message']];
    if (!$kq['ok'] && $kq['code'] === 'VALIDATION_ERROR') {
        $_SESSION['dm_form'] = ['maMon' => $maMon, 'dong' => $dong, 'loi' => $kq['data']['loiDong'] ?? [], 'banGhi' => post_text('banGhi')];
    }
    redirect_to($bomUrl(['mon' => $maMon]));
}

$thongBao = $_SESSION['dm_thong_bao'] ?? null; unset($_SESSION['dm_thong_bao']);
$formCu = $_SESSION['dm_form'] ?? null; unset($_SESSION['dm_form']);
$tuKhoa = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$maChon = is_string($_GET['mon'] ?? null) ? $_GET['mon'] : '';
$dsMon = DinhMucControl::danhSachMon($tuKhoa);
if ($maChon === '' && $dsMon) { $maChon = $dsMon[0]['maMon']; }
$mon = $maChon !== '' ? DinhMucControl::chiTietMon($maChon) : null;
if ($maChon !== '' && !$mon) { http_response_code(404); $thongBao = ['ok' => false, 'msg' => 'Không tìm thấy món ăn.']; }
$dsNVL = DinhMucControl::danhSachNVL();
$nvlTheoMa = array_column($dsNVL, null, 'maNVL');
$coTheSua = can('bom.manage') && $mon && $mon['trangThai'] === 'HOAT_DONG';
$so = fn($v): string => rtrim(rtrim(number_format((float)$v, 3, ',', ''), '0'), ',');
$tien = fn($v): string => number_format((float)$v, 0, ',', '.') . 'đ';
$vietTat = fn(string $ten): string => mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/u', trim($ten)), 0, 2))));

// Dòng hiển thị: dữ liệu form vừa gửi lỗi, hoặc công thức hiện tại; thêm 2 dòng trống để nhập không cần JavaScript.
$dongForm = [];
$loiDong = [];
if ($mon) {
    if ($formCu && $formCu['maMon'] === $mon['maMon']) {
        $dongForm = $formCu['dong']; $loiDong = $formCu['loi'];
    } else {
        foreach ($mon['congThuc'] as $d) { $dongForm[] = ['maNVL' => $d['maNVL'], 'dinhMuc' => $so($d['dinhMuc'])]; }
    }
    $dongForm[] = ['maNVL' => '', 'dinhMuc' => ''];
    if (!$mon['congThuc']) { $dongForm[] = ['maNVL' => '', 'dinhMuc' => '']; }
    $lichSu = DinhMucControl::lichSu($mon['maMon']);
}
$tuyChonNVL = function (string $chon) use ($dsNVL): string {
    $html = '<option value="" data-unit="">Chọn nguyên vật liệu</option>';
    foreach ($dsNVL as $n) {
        if ($n['trangThai'] !== 'HOAT_DONG' && $n['maNVL'] !== $chon) { continue; }
        $html .= '<option value="' . e($n['maNVL']) . '" data-unit="' . e($n['donViTinh']) . '"' . ($n['maNVL'] === $chon ? ' selected' : '') . '>'
            . e($n['tenNVL'] . ' (' . $n['donViTinh'] . ')' . ($n['trangThai'] !== 'HOAT_DONG' ? ' — ngừng sử dụng' : '')) . '</option>';
    }
    return $html;
};
?>
<style>
.dm-side{align-self:start}
.dm-search{display:flex;gap:6px}
.dm-dish{display:flex;align-items:center;gap:9px;padding:10px 13px;border-bottom:1px solid var(--line);color:inherit;text-decoration:none}
.dm-dish:hover{background:#fffaf8}
.dm-dish.active{background:var(--accent-soft);border-left:3px solid var(--accent);padding-left:10px}
.dm-dish .dm-thumb{width:34px;height:34px;border-radius:9px;background:var(--panel-soft);display:grid;place-items:center;font-size:10px;font-weight:700;color:var(--accent-dark);flex:none}
.dm-dish>div{display:flex;flex-direction:column;flex:1;min-width:0}
.dm-dish strong{font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dm-dish span.muted{font-size:8px}
.dm-empty{padding:30px 16px;color:var(--muted);font-size:11px;text-align:center}
.dm-row{display:grid;grid-template-columns:28px minmax(180px,1fr) 130px 80px 36px;gap:9px;align-items:end;padding:10px 0;border-bottom:1px solid var(--line)}
.dm-row .dm-idx{font-size:11px;font-weight:700;color:var(--muted);padding-bottom:12px}
.dm-row .dm-unit{height:40px;display:flex;align-items:center;font-size:11px;font-weight:600;color:var(--ink);background:var(--panel-soft);border-radius:9px;padding:0 10px}
.dm-row .dm-remove{padding:0;min-height:40px}
.dm-row .field-error{grid-column:2/-1;color:var(--danger);font-size:10px}
.dm-row.invalid .control{border-color:var(--danger)}
.dm-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;background:#f8fafc;margin-top:16px;flex-wrap:wrap}
.dm-foot span{color:var(--muted);font-size:9px;line-height:1.7;max-width:560px}
.dm-msg{margin:16px 18px 0}
.dm-history{padding:6px 18px 18px}
.dm-history li{list-style:none;border-bottom:1px solid var(--line);padding:10px 0;font-size:11px;line-height:1.7}
.dm-history ul{margin:0;padding:0}
.dm-history time{display:block;color:var(--muted);font-size:9px}
.dm-history details{font-size:10px;color:var(--muted)}
.dm-history table{border-collapse:collapse;margin-top:4px}
.dm-history td{padding:2px 12px 2px 0}
.dm-readonly{padding:16px 18px;font-size:11px;color:var(--muted)}
@media(max-width:600px){.dm-row{grid-template-columns:24px 1fr 90px 32px}.dm-row .dm-unit-col{display:none}}
</style>
<div class="bom-layout">
<aside class="card dish-sidebar dm-side">
<div class="dish-search"><h3 class="title title-sm">Danh sách món · <?= count($dsMon) ?></h3>
<form method="get" action="<?= e(url('index.php')) ?>" class="dm-search" role="search"><input type="hidden" name="page" value="bom">
<input class="control" type="search" name="q" value="<?= e($tuKhoa) ?>" maxlength="100" placeholder="Tìm món ăn..." aria-label="Tìm món ăn">
<button type="submit" class="btn btn-secondary">Tìm</button></form></div>
<nav aria-label="Món ăn">
<?php if (!$dsMon): ?><div class="dm-empty">Không có món phù hợp.</div><?php endif; ?>
<?php foreach ($dsMon as $m): ?>
<a class="dm-dish <?= $mon && $m['maMon'] === $mon['maMon'] ? 'active' : '' ?>" href="<?= e(url($bomUrl(array_filter(['mon' => $m['maMon'], 'q' => $tuKhoa])))) ?>" <?= $mon && $m['maMon'] === $mon['maMon'] ? 'aria-current="page"' : '' ?>>
<span class="dm-thumb"><?= e($vietTat($m['tenMon'])) ?></span>
<div><strong><?= e($m['tenMon']) ?></strong><span class="muted"><?= e($m['tenNhom']) ?> · <?= (int)$m['soThanhPhan'] ?> NVL</span></div>
<?php if ($m['trangThai'] !== 'HOAT_DONG'): ?><span class="badge badge-neutral">Ngừng bán</span>
<?php elseif ($m['trangThaiDinhMuc'] === 'DA_CO'): ?><span class="badge badge-success">Đã thiết lập</span>
<?php else: ?><span class="badge badge-warning">Chưa thiết lập</span><?php endif; ?>
</a>
<?php endforeach; ?>
</nav>
</aside>

<section class="card recipe-builder">
<?php if ($thongBao): ?><div class="account-message dm-msg <?= $thongBao['ok'] ? 'success' : 'error' ?>" role="status"><?= e($thongBao['msg']) ?></div><?php endif; ?>
<?php if (!$mon): ?>
<div class="dm-empty">Chọn một món ở danh sách bên trái để xem và thiết lập định mức.</div>
<?php else: $capNhat = $lichSu[0] ?? null; ?>
<div class="recipe-head"><div><span class="eyebrow">ĐỊNH MỨC CHO 1 PHẦN · <?= e($mon['maMon']) ?></span>
<h2 class="title title-md"><?= e($mon['tenMon']) ?></h2>
<span class="muted" style="font-size:10px"><?= $capNhat ? 'Cập nhật lần cuối bởi ' . e($capNhat['nguoiSua']) . ' · ' . e(date('H:i d/m/Y', strtotime($capNhat['thoiGian']))) : 'Chưa có lịch sử cập nhật' ?></span></div>
<?= $mon['trangThaiDinhMuc'] === 'DA_CO' ? '<span class="badge badge-success">Đã thiết lập</span>' : '<span class="badge badge-warning">Chưa thiết lập</span>' ?></div>
<div class="recipe-metrics">
<div><span>Thành phần</span><strong><?= count($mon['congThuc']) ?></strong></div>
<div><span>Nhóm món</span><strong><?= e($mon['tenNhom']) ?></strong></div>
<div><span>Giá niêm yết</span><strong><?= $tien($mon['donGia']) ?></strong></div>
<div><span>Trạng thái món</span><strong><?= $mon['trangThai'] === 'HOAT_DONG' ? 'Đang kinh doanh' : 'Ngừng kinh doanh' ?></strong></div>
</div>
<?php if ($coTheSua): ?>
<form method="post" action="<?= e(page_url('bom')) ?>" id="dm-form" novalidate>
<?= csrf_input() ?><input type="hidden" name="maMon" value="<?= e($mon['maMon']) ?>"><input type="hidden" name="banGhi" value="<?= e($formCu['banGhi'] ?? $mon['banGhi']) ?>">
<div class="ingredient-head"><h3 class="title title-sm">Thành phần nguyên vật liệu</h3>
<button type="button" class="btn btn-secondary" id="dm-add">+ Thêm dòng</button></div>
<div class="ingredients" id="dm-rows">
<?php foreach ($dongForm as $i => $d): $unit = $nvlTheoMa[$d['maNVL']]['donViTinh'] ?? '—'; ?>
<div class="dm-row<?= isset($loiDong[$i]) ? ' invalid' : '' ?>">
<span class="dm-idx"><?= $i + 1 ?></span>
<label class="field"><span class="field-label">Nguyên vật liệu</span><select class="control dm-nvl" name="nvl[]"><?= $tuyChonNVL($d['maNVL']) ?></select></label>
<label class="field"><span class="field-label">Định lượng</span><input class="control" name="dinhMuc[]" value="<?= e($d['dinhMuc']) ?>" inputmode="decimal" maxlength="12" placeholder="vd: 0,25"></label>
<div class="field dm-unit-col"><span class="field-label">Đơn vị</span><span class="dm-unit"><?= e($unit) ?></span></div>
<button type="button" class="btn btn-ghost dm-remove" aria-label="Bỏ dòng <?= $i + 1 ?>">×</button>
<?php if (isset($loiDong[$i])): ?><span class="field-error"><?= e($loiDong[$i]) ?></span><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<div class="dm-foot"><span><b>Lưu ý:</b> Định lượng tính cho 1 phần, theo <b>đơn vị cơ sở</b> của nguyên vật liệu (vd: 250 g cá nhập 0,25 kg). Mỗi nguyên vật liệu chỉ xuất hiện một lần; dòng để trống sẽ được bỏ qua. Lưu công thức không làm thay đổi tồn kho.</span>
<button type="submit" class="btn btn-primary">Lưu công thức</button></div>
</form>
<template id="dm-row-template"><div class="dm-row"><span class="dm-idx"></span>
<label class="field"><span class="field-label">Nguyên vật liệu</span><select class="control dm-nvl" name="nvl[]"><?= $tuyChonNVL('') ?></select></label>
<label class="field"><span class="field-label">Định lượng</span><input class="control" name="dinhMuc[]" inputmode="decimal" maxlength="12" placeholder="vd: 0,25"></label>
<div class="field dm-unit-col"><span class="field-label">Đơn vị</span><span class="dm-unit">—</span></div>
<button type="button" class="btn btn-ghost dm-remove" aria-label="Bỏ dòng">×</button></div></template>
<script>
(() => {
  const rows = document.getElementById('dm-rows'), tpl = document.getElementById('dm-row-template');
  const max = <?= DinhMucControl::SO_DONG_TOI_DA ?>;
  const renumber = () => rows.querySelectorAll('.dm-row').forEach((row, i) => {
    row.querySelector('.dm-idx').textContent = i + 1;
    row.querySelector('.dm-remove').setAttribute('aria-label', 'Bỏ dòng ' + (i + 1));
  });
  document.getElementById('dm-add').addEventListener('click', () => {
    if (rows.querySelectorAll('.dm-row').length >= max) { alert('Một công thức tối đa ' + max + ' nguyên vật liệu.'); return; }
    rows.append(tpl.content.firstElementChild.cloneNode(true)); renumber();
    rows.lastElementChild.querySelector('select').focus();
  });
  rows.addEventListener('click', event => {
    const btn = event.target.closest('.dm-remove'); if (!btn) return;
    const row = btn.closest('.dm-row');
    if (rows.querySelectorAll('.dm-row').length > 1) row.remove();
    else { row.querySelector('select').value = ''; row.querySelector('input').value = ''; row.querySelector('.dm-unit').textContent = '—'; }
    renumber();
  });
  rows.addEventListener('change', event => {
    if (!event.target.matches('.dm-nvl')) return;
    const option = event.target.selectedOptions[0];
    event.target.closest('.dm-row').querySelector('.dm-unit').textContent = option && option.dataset.unit ? option.dataset.unit : '—';
  });
})();
</script>
<?php else: ?>
<div class="ingredient-head"><h3 class="title title-sm">Thành phần nguyên vật liệu</h3></div>
<?php if (!$mon['congThuc']): ?><div class="dm-readonly">Món chưa có định mức.</div><?php else: ?>
<div class="table-scroll" style="padding:0 18px"><table class="data-table"><thead><tr><th>#</th><th>Nguyên vật liệu</th><th>Định lượng</th><th>Đơn vị</th></tr></thead><tbody>
<?php foreach ($mon['congThuc'] as $i => $d): ?><tr><td><?= $i + 1 ?></td><td><?= e($d['tenNVL']) ?></td><td><?= $so($d['dinhMuc']) ?></td><td><?= e($d['donViTinh']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<div class="dm-readonly"><?= $mon['trangThai'] !== 'HOAT_DONG' ? 'Món đã ngừng kinh doanh nên không cập nhật định mức.' : 'Bạn chỉ có quyền xem định mức.' ?></div>
<?php endif; ?>

<div class="ingredient-head" style="margin-top:8px"><h3 class="title title-sm">Lịch sử thay đổi định mức</h3></div>
<div class="dm-history">
<?php if (!$lichSu): ?><p class="muted" style="font-size:11px">Chưa có lần cập nhật nào được ghi nhận.</p><?php else: ?><ul>
<?php foreach ($lichSu as $ls): $truoc = json_decode((string)$ls['congThucCu'], true) ?: []; $sau = json_decode((string)$ls['congThucMoi'], true) ?: []; ?>
<li><time><?= e(date('H:i d/m/Y', strtotime($ls['thoiGian']))) ?> · <?= e($ls['nguoiSua']) ?></time><?= e($ls['noiDung']) ?>
<details><summary>Xem công thức trước / sau</summary><table>
<tr><td><b>Trước</b></td><td><?= $truoc ? e(implode('; ', array_map(fn($d) => $d['tenNVL'] . ' ' . $so($d['dinhMuc']) . ' ' . $d['donViTinh'], $truoc))) : '(chưa có)' ?></td></tr>
<tr><td><b>Sau</b></td><td><?= e(implode('; ', array_map(fn($d) => $d['tenNVL'] . ' ' . $so($d['dinhMuc']) . ' ' . $d['donViTinh'], $sau))) ?></td></tr>
</table></details></li>
<?php endforeach; ?></ul><?php endif; ?>
</div>
<?php endif; ?>
</section>
</div>
