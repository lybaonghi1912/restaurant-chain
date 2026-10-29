<?php
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
/*
 * Màn hình bếp — SD-09 Tiếp nhận đơn tại bếp. Người lập trình: Lê Xuân Lộc. Actor: Bếp.
 * Chủ chuỗi / Quản lý chỉ xem. Nghiệp vụ trong BepControl.php.
 * Không dùng .ticket / .kds-tabs b / data-ticket / data-kds-filter của giao diện mẫu để app.js không chen vào.
 * Ngoài phạm vi SD-09 nên đã bỏ: "Báo thiếu NVL", đơn mang về, nút hoàn thành/trả món.
 */
require_permission('kds.view');
require_once __DIR__ . '/BepControl.php';

$tab = ($_GET['tab'] ?? '') === 'da-nhan' ? 'da-nhan' : 'cho';
$kdsUrl = fn(array $q = []): string => 'index.php?' . http_build_query(['page' => 'kds'] + $q);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $kq = match (post_text('hanhDong')) {
        'nhan_mon' => BepControl::tiepNhan(post_text('maCTDon')),
        'nhan_ca_don' => BepControl::tiepNhanCaDon(post_text('maDon'), is_array($_POST['maCTDon'] ?? null) ? $_POST['maCTDon'] : []),
        default => ['ok' => false, 'code' => 'VALIDATION_ERROR', 'message' => 'Thao tác không hợp lệ.'],
    };
    $_SESSION['bep_thong_bao'] = ['ok' => $kq['ok'], 'msg' => $kq['message']];
    redirect_to($kdsUrl());
}

$thongBao = $_SESSION['bep_thong_bao'] ?? null; unset($_SESSION['bep_thong_bao']);
$phieu = BepControl::danhSachPhieuCho();
$daNhan = BepControl::daTiepNhanHomNay();
$soMonCho = array_sum(array_map(fn($p) => count(array_filter($p['mon'], fn($m) => $m['trangThai'] === 'CHO_CHE_BIEN')), $phieu));
$xemToanChuoi = current_branch_id() === null;
$coTheThaoTac = BepControl::coTheTiepNhan();
$phut = fn(?string $t): int => $t ? max(0, (int)floor((time() - strtotime($t)) / 60)) : 0;
$mucCho = fn(int $p): string => $p >= 20 ? 'danger' : ($p >= 10 ? 'warning' : 'success');
?>
<style>
.kb-bar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.kb-tabs{display:flex;gap:4px;flex-wrap:wrap}
.kb-tabs .btn span{background:#00000014;border-radius:9px;min-width:18px;height:18px;display:inline-grid;place-items:center;font-size:8px;padding:0 5px}
.kb-tabs .btn-primary span{background:#ffffff33}
.kb-meta{font-size:10px;color:var(--muted);display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.kb-ticket{border-top:5px solid var(--success);overflow:hidden;display:flex;flex-direction:column}
.kb-ticket.kb-warning{border-top-color:var(--warning)}.kb-ticket.kb-danger{border-top-color:var(--danger)}
.kb-ticket .ticket-number{width:auto;min-width:36px;padding:0 8px}
.kb-sub{color:var(--muted);font-size:9px;display:block;margin-top:2px}
.kb-timer{text-align:right;border-radius:9px;padding:6px 9px}
.kb-timer span{display:block;font-size:7px;font-weight:700;letter-spacing:.06em}
.kb-timer strong{font-size:16px}
.kb-timer.success{background:var(--success-soft);color:#08794c}.kb-timer.warning{background:var(--warning-soft);color:#9b6104}.kb-timer.danger{background:var(--danger-soft);color:var(--danger)}
.kb-lines{padding:4px 14px 10px;flex:1}
.kb-line{display:grid;grid-template-columns:32px 1fr auto;gap:8px;align-items:center;padding:11px 0;border-bottom:1px solid var(--line)}
.kb-line:last-child{border-bottom:0}
.kb-line .kb-qty{color:var(--accent);font-size:13px;font-weight:700}
.kb-line strong{font-size:11px;display:block}
.kb-line small{display:block;font-size:9px;color:var(--accent-dark)}
.kb-line .kb-time{font-size:8px;color:var(--muted)}
.kb-line.kb-done{opacity:.7}
.kb-line.kb-done .kb-qty{color:var(--success)}
.kb-check{font-size:9px;color:#08794c;font-weight:600;text-align:right}
.kb-line form{margin:0}
.kb-foot{background:var(--panel-soft);padding:12px 14px}
.kb-foot form{margin:0}.kb-foot .btn{width:100%}
.kb-empty{padding:60px 20px;text-align:center;color:var(--muted);font-size:12px}
.kb-empty strong{display:block;font-size:15px;color:var(--ink);margin-bottom:6px}
.kb-msg{margin-bottom:14px}
.kb-note{font-size:10px;color:var(--muted);margin-bottom:12px}
</style>
<div class="kds-screen">
<div class="kb-bar">
<nav class="kb-tabs" aria-label="Lọc phiếu bếp">
<a class="btn <?= $tab === 'cho' ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e(url($kdsUrl())) ?>" <?= $tab === 'cho' ? 'aria-current="page"' : '' ?>>Chờ tiếp nhận <span><?= $soMonCho ?></span></a>
<a class="btn <?= $tab === 'da-nhan' ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e(url($kdsUrl(['tab' => 'da-nhan']))) ?>" <?= $tab === 'da-nhan' ? 'aria-current="page"' : '' ?>>Đã tiếp nhận hôm nay <span><?= count($daNhan) ?></span></a>
</nav>
<div class="kb-meta"><span>Cập nhật lúc <?= date('H:i:s') ?> · tự làm mới mỗi 30 giây</span>
<a class="btn btn-secondary compact" href="<?= e(url($kdsUrl($tab === 'cho' ? [] : ['tab' => $tab]))) ?>">Làm mới</a></div>
</div>
<?php if ($thongBao): ?><div class="account-message kb-msg <?= $thongBao['ok'] ? 'success' : 'error' ?>" role="status"><?= e($thongBao['msg']) ?></div><?php endif; ?>
<?php if (!$coTheThaoTac): ?><p class="kb-note">Bạn đang xem màn hình bếp ở chế độ chỉ xem. Chỉ nhân viên Bếp của chi nhánh được tiếp nhận món.</p><?php endif; ?>

<?php if ($tab === 'cho'): ?>
<?php if (!$phieu): ?>
<div class="card kb-empty"><strong>Không có món nào đang chờ</strong>Món mới do phục vụ gửi bếp sẽ hiển thị ở đây.</div>
<?php else: ?>
<div class="ticket-grid">
<?php foreach ($phieu as $p):
    $cho = array_values(array_filter($p['mon'], fn($m) => $m['trangThai'] === 'CHO_CHE_BIEN'));
    $daCho = $phut($p['choSomNhat']); $muc = $mucCho($daCho);
    $thaoTac = BepControl::coTheTiepNhan($p['maChiNhanh']); ?>
<article class="card kb-ticket kb-<?= $muc ?>" aria-label="Phiếu bàn <?= e($p['tenBan']) ?>">
<div class="ticket-head"><div><span class="ticket-number"><?= e($p['tenBan']) ?></span>
<div><h2 class="title title-md">Bàn <?= e($p['tenBan']) ?></h2>
<span class="kb-sub">Đơn <?= e($p['maDon']) ?><?= $xemToanChuoi ? ' · ' . e($p['tenChiNhanh']) : '' ?> · Gửi lúc <?= e(date('H:i', strtotime($p['choSomNhat']))) ?></span></div></div>
<div class="kb-timer <?= $muc ?>"><span>ĐÃ CHỜ</span><strong><?= $daCho ?>′</strong></div></div>
<div class="kb-lines">
<?php foreach ($p['mon'] as $m): $laCho = $m['trangThai'] === 'CHO_CHE_BIEN'; ?>
<div class="kb-line<?= $laCho ? '' : ' kb-done' ?>"><span class="kb-qty"><?= (int)$m['soLuong'] ?>×</span>
<div><strong><?= e($m['tenMon']) ?></strong><?php if ($m['ghiChu']): ?><small><?= e($m['ghiChu']) ?></small><?php endif; ?>
<span class="kb-time">Gửi <?= e(date('H:i', strtotime($m['gioGuiBep']))) ?></span></div>
<?php if (!$laCho): ?><span class="kb-check">✓ Đã nhận <?= e(date('H:i', strtotime($m['gioTiepNhan']))) ?><br><?= e($m['nguoiNhan'] ?? '') ?></span>
<?php elseif ($thaoTac): ?><form method="post" action="<?= e(page_url('kds')) ?>"><?= csrf_input() ?>
<input type="hidden" name="hanhDong" value="nhan_mon"><input type="hidden" name="maCTDon" value="<?= e($m['maCTDon']) ?>">
<button type="submit" class="btn btn-secondary compact">Nhận món</button></form>
<?php else: ?><span class="badge badge-warning">Chờ tiếp nhận</span><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php if ($thaoTac): ?>
<div class="kb-foot"><form method="post" action="<?= e(page_url('kds')) ?>"><?= csrf_input() ?>
<input type="hidden" name="hanhDong" value="nhan_ca_don"><input type="hidden" name="maDon" value="<?= e($p['maDon']) ?>">
<?php foreach ($cho as $m): ?><input type="hidden" name="maCTDon[]" value="<?= e($m['maCTDon']) ?>"><?php endforeach; ?>
<button type="submit" class="btn btn-primary">Nhận tất cả <?= count($cho) ?> món chờ</button></form></div>
<?php endif; ?>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: ?>
<section class="card data-card">
<?php if (!$daNhan): ?><div class="kb-empty"><strong>Chưa có món nào được tiếp nhận hôm nay</strong></div><?php else: ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Giờ nhận</th><th>Bàn</th><?php if ($xemToanChuoi): ?><th>Chi nhánh</th><?php endif; ?><th>Món</th><th>Ghi chú</th><th>Gửi bếp</th><th>Người nhận</th></tr></thead><tbody>
<?php foreach ($daNhan as $m): ?><tr><td><?= e(date('H:i', strtotime($m['gioTiepNhan']))) ?></td><td><?= e($m['tenBan']) ?></td>
<?php if ($xemToanChuoi): ?><td><?= e($m['tenChiNhanh']) ?></td><?php endif; ?>
<td><?= (int)$m['soLuong'] ?>× <?= e($m['tenMon']) ?></td><td><?= e($m['ghiChu'] ?? '') ?></td>
<td><?= e(date('H:i', strtotime($m['gioGuiBep']))) ?></td><td><?= e($m['nguoiNhan'] ?? '') ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section>
<?php endif; ?>
</div>
<script>
// Tự tải lại bằng GET để thấy món mới; không gửi lại form.
setTimeout(() => { if (!document.querySelector('#modal-root .modal')) location.replace(location.pathname + location.search); }, 30000);
</script>
