<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }

function chi_nhanh_tieu_de_bo_sung(): void
{
?>
<section class="cn-supply-header" aria-labelledby="supply-processing-title">
<span class="cn-supply-label">BỔ SUNG NGUYÊN LIỆU</span>
<h2 class="title title-md cn-supply-title" id="supply-processing-title">Xử lý yêu cầu nhập nguyên liệu</h2>
<p class="muted cn-heading-description">Xem yêu cầu và nguồn cung trước khi chọn cách bổ sung nguyên liệu.</p>
</section>
<?php
}

function chi_nhanh_chi_tiet_yeu_cau(array $request, array $lines, array $remainingLines, string $nvl, string $requestDetailTitle): void
{
?>
<section class="card data-card cn-space-top" aria-labelledby="<?= e($requestDetailTitle) ?>">
    <div class="data-toolbar"><div><span class="eyebrow accent"><?= e($request['maYeuCau']) ?></span><h3 class="title title-sm" id="<?= e($requestDetailTitle) ?>">Chi nhánh gửi yêu cầu: <?= e($request['tenChiNhanh']) ?></h3><p class="muted">Ngày tạo: <?= e($request['ngayTao']) ?></p><p class="muted">Lý do: <?= e($request['lyDo']) ?></p></div><span class="badge badge-info"><?= $request['trangThai']==='DA_LAP_PHUONG_AN'?'Đã lập đủ phương án':'Chờ xử lý phần còn thiếu' ?></span></div>
    <?php if ($request['trangThaiChiNhanh']!=='HOAT_DONG'): ?><p class="account-message error">Chi nhánh gửi yêu cầu đã ngừng hoạt động.</p><?php endif; ?>
    <p class="muted cn-table-note" role="status">Yêu cầu có <?= count($lines) ?> nguyên liệu; còn <?= count($remainingLines) ?> nguyên liệu chưa lập đủ phương án.</p>
    <?php if (count($lines)>1): ?><p class="muted cn-table-note">Chọn nguyên liệu ở ô bên dưới để xem nguồn cung. Mỗi nguyên liệu có thể chọn cách bổ sung khác nhau.</p><?php endif; ?>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>Nguyên liệu</th><th>Đơn vị</th><th>Đề nghị</th><th>Đã phân bổ</th><th>Còn thiếu</th></tr></thead><tbody>
    <?php foreach ($lines as $line): ?><tr><td><?= e($line['tenNVL']) ?><?php if ($line['maNVL']===$nvl): ?> <span class="badge badge-info">Đang xem</span><?php endif; ?><?php if ($line['trangThai']!=='HOAT_DONG'): ?> <span class="muted">Ngừng sử dụng</span><?php endif; ?></td><td><?= e($line['donViTinh']) ?></td><td><?= e($line['soLuong']) ?></td><td><?= e($line['daPhanBo']) ?></td><td><?= e($line['conThieu']) ?></td></tr><?php endforeach; ?>
    <?php if (!$lines): ?><tr><td colspan="5" class="empty-filter">Yêu cầu chưa có nguyên liệu.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php
}

function chi_nhanh_chon_nguyen_lieu(?array $request, array $lines, array $remainingLines, string $requestId, string $nvl): void
{
if (!$request || count($lines)<2 || !$remainingLines || $request['trangThaiChiNhanh']!=='HOAT_DONG') { return; }
?>
<section class="card cn-material-card" aria-label="Chọn nguyên liệu để xem nguồn">
<form method="get" action="<?= e(url('index.php')) ?>">
<input type="hidden" name="page" value="transfer"><input type="hidden" name="request" value="<?= e($requestId) ?>">
<label class="field"><span class="field-label cn-material-label">Nguyên liệu cần xem nguồn cung</span>
<select class="control cn-material-select" name="nvl" data-material-select>
<?php foreach ($lines as $line): ?><option value="<?= e($line['maNVL']) ?>" <?= $line['maNVL']===$nvl?'selected':'' ?> <?= $line['trangThai']!=='HOAT_DONG'?'disabled':'' ?>><?= e($line['tenNVL'].' — '.(dc_units($line['conThieu'])>0?'còn thiếu '.$line['conThieu'].' '.$line['donViTinh']:'đã đủ phương án')) ?></option><?php endforeach; ?>
</select></label>
<noscript><button class="btn btn-secondary" type="submit">Xem nguồn cung</button></noscript>
</form>
</section>
<?php
}

function chi_nhanh_chon_phuong_an(array $sources, string $requestId, string $nvl): void
{
?>
<div class="cn-padding">
<p class="muted">Cân nhắc số lượng cần bổ sung, nhu cầu của chi nhánh nguồn, khoảng cách, hạn sử dụng và thời gian cần hàng theo lý do yêu cầu. Số có thể cung cấp đã trừ các lượng điều chuyển đã cam kết; chưa trừ nhu cầu sử dụng riêng của chi nhánh nguồn.</p>
<div class="management-actions cn-space-top-small">
<?php if ($sources): ?><a class="btn btn-primary" href="<?= e(page_url('transfer').'&mode=transfer&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl)) ?>">Điều chuyển giữa các chi nhánh</a><?php endif; ?>
<a class="btn btn-secondary" href="<?= e(page_url('transfer').'&mode=supplier&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl)) ?>">Nhập từ nhà cung cấp</a>
</div></div>
<?php
}

function chi_nhanh_thong_tin_huy(array $item): void
{
?>
<div class="muted cn-space-top-tight">Người hủy: <?= e($item['nguoiHuy'] ?? $item['maNguoiHuy']) ?><br><?= e($item['thoiGianHuy']) ?><br>Lý do: <?= e($item['lyDoHuy']) ?></div>
<?php
}

function chi_nhanh_lich_su_nhap(array $recent): void
{
?>
<section class="card data-card cn-space-top" aria-labelledby="recent-supplier-title">
    <div class="data-toolbar"><h3 class="title title-sm" id="recent-supplier-title">Yêu cầu nhập từ nhà cung cấp gần đây</h3></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>Mã yêu cầu nhập</th><th>Yêu cầu gốc</th><th>Nhà cung cấp</th><th>Chi nhánh nhận</th><th>Nguyên liệu</th><th>Số lượng</th><th>Trạng thái</th></tr></thead><tbody>
    <?php foreach ($recent as $item): ?><tr><td><?= e($item['maDonNhap']) ?></td><td><?= e($item['maYeuCau']) ?></td><td><?= e($item['tenNCC']) ?></td><td><?= e($item['tenChiNhanh']) ?></td><td><?= e($item['tenNVL']) ?></td><td><?= e($item['soLuong'].' '.$item['donViTinh']) ?></td><td><span class="badge <?= $item['trangThai']==='DA_HUY'?'badge-danger':'badge-info' ?>"><?= $item['trangThai']==='DA_HUY'?'Đã hủy':'Chờ nhập hàng' ?></span><?php if ($item['trangThai']==='DA_HUY'): ?><?php chi_nhanh_thong_tin_huy($item); ?><?php elseif ($item['trangThai']==='CHO_NHAP_HANG'): ?><div class="cn-space-top-tight"><a class="btn btn-secondary" href="<?= e(page_url('transfer').'&cancel_type=supplier&cancel_id='.rawurlencode($item['maDonNhap'])) ?>">Hủy phiếu</a></div><?php endif; ?></td></tr><?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="7" class="empty-filter">Chưa có yêu cầu nhập từ nhà cung cấp.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php
}

function chi_nhanh_lich_su_bo_sung(): void
{
$recent=dc_recent();
$statusLabels=['CHO_XAC_NHAN_NGUON'=>'Chờ Chủ chuỗi duyệt','DA_DUYET'=>'Đã duyệt','TU_CHOI'=>'Đã từ chối','DA_HUY'=>'Đã hủy'];
?>
<section class="cn-history" aria-labelledby="supply-history-title">
<div class="cn-history-header">
<span class="cn-history-label">LỊCH SỬ THAM KHẢO</span>
<h3 class="title title-sm cn-history-title" id="supply-history-title">Các phiếu đã lập</h3>
<p class="muted cn-heading-description">Xem lại các phiếu điều chuyển và yêu cầu nhập từ nhà cung cấp đã lưu. Đây không phải bước tiếp theo của yêu cầu đang xử lý.</p>
</div>
<section class="card data-card cn-space-top" aria-labelledby="recent-transfer-title">
    <div class="data-toolbar"><h3 class="title title-sm" id="recent-transfer-title">Điều chuyển gần đây</h3></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>Mã điều chuyển</th><th>Yêu cầu gốc</th><th>Từ</th><th>Đến</th><th>Nguyên liệu</th><th>Số lượng</th><th>Trạng thái</th></tr></thead><tbody>
    <?php foreach ($recent as $item): ?><tr><td><?= e($item['maDieuChuyen']) ?></td><td><?= e($item['maYeuCau']) ?></td><td><?= e($item['tenNguon']) ?></td><td><?= e($item['tenNhan']) ?></td><td><?= e($item['tenNVL']) ?></td><td><?= e($item['soLuongYeuCau'].' '.$item['donViTinh']) ?></td><td><span class="badge <?= $item['trangThai']==='DA_HUY'?'badge-danger':'badge-info' ?>"><?= e($statusLabels[$item['trangThai']] ?? $item['trangThai']) ?></span><?php if ($item['trangThai']==='CHO_XAC_NHAN_NGUON'): ?><form class="cn-space-top-tight" method="post" action="<?= e(page_url('transfer')) ?>"><?= csrf_input() ?><input type="hidden" name="action" value="approve_pending_transfer"><input type="hidden" name="transfer_id" value="<?= e($item['maDieuChuyen']) ?>"><button class="btn btn-secondary" type="submit">Chủ chuỗi duyệt</button></form><?php elseif ($item['trangThai']==='DA_HUY'): ?><?php chi_nhanh_thong_tin_huy($item); ?><?php elseif ($item['trangThai']==='DA_DUYET'): ?><div class="muted cn-space-top-tight">Người duyệt: <?= e($item['nguoiDuyet']) ?><br><?= e($item['ngayXacNhanNguon']) ?></div><a class="btn btn-secondary cn-space-top-tight" href="<?= e(page_url('transfer').'&cancel_type=transfer&cancel_id='.rawurlencode($item['maDieuChuyen'])) ?>">Hủy phiếu</a><?php endif; ?></td></tr><?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="7" class="empty-filter">Chưa có yêu cầu điều chuyển.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php $recent=ncc_recent();chi_nhanh_lich_su_nhap($recent); ?>
</section>
<?php
}

