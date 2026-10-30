<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_once __DIR__.'/giao-dien.php';
require_once __DIR__.'/transfer-data.php';
dc_require_owner();$error='';$reason='';$info=null;
$kind=$_GET['cancel_type'] ?? '';$id=$_GET['cancel_id'] ?? '';
if (!is_string($kind) || !is_string($id)) { http_response_code(400);echo 'Thông tin phiếu không hợp lệ.';return; }
try { $definition=supply_cancel_kind($kind);$info=supply_cancel_info($kind,$id); }
catch (DomainException $ex) { http_response_code(404);$error=$ex->getMessage(); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    if (post_text('action')!=='cancel_supply') { http_response_code(400);echo 'Thao tác không hợp lệ.';return; }
    $reason=post_text('reason');
    if ($info) {
        try {
            $requestId=supply_cancel($kind,$id,$reason,post_text('confirmed')==='1');
            $_SESSION['transfer_notice']='Đã hủy phiếu '.$id.'. Phần nguyên liệu tương ứng đã trở lại phần cần xử lý của yêu cầu gốc.';
            redirect_to('index.php?page=transfer&request='.rawurlencode($requestId));
        } catch (DomainException $ex) { http_response_code(422);$error=$ex->getMessage(); }
        catch (PDOException $ex) { http_response_code(503);error_log('Supply cancellation: '.$ex->getMessage());$error='Chưa hủy được phiếu. Vui lòng thử lại.'; }
    }
}
?>
<div class="screen-content chi-nhanh-module">
<section class="card cn-cancel-card">
<h2 class="title title-md">Xác nhận hủy phiếu</h2>
<?php if ($error!==''): ?><p class="account-message error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($info): ?>
<p class="cn-space-top-small"><?= e(ucfirst($definition[4])) ?>: <strong><?= e($id) ?></strong></p>
<p>Yêu cầu gốc: <?= e($info['maYeuCau']) ?> · Chi nhánh gửi yêu cầu: <?= e($info['tenChiNhanh']) ?></p>
<ul class="cn-cancel-lines"><?php foreach ($info['details'] as $line): ?><li><?= e($line['tenNVL'].' — '.$line['soLuong'].' '.$line['donViTinh']) ?></li><?php endforeach; ?></ul>
<?php if ($info['trangThai']==='DA_HUY'): ?>
<p role="status">Phiếu đã hủy lúc <?= e($info['thoiGianHuy']) ?>. Lý do: <?= e($info['lyDoHuy']) ?></p>
<?php elseif ($info['trangThai']===$definition[3]): ?>
<p>Hủy toàn bộ phiếu này. Các phiếu khác của cùng yêu cầu được giữ nguyên. Phiếu bị hủy vẫn được lưu trong lịch sử.</p>
<form method="post" class="account-form" action="<?= e(page_url('transfer').'&cancel_type='.rawurlencode($kind).'&cancel_id='.rawurlencode($id)) ?>">
<?= csrf_input() ?><input type="hidden" name="action" value="cancel_supply">
<label class="field"><span class="field-label">Lý do hủy *</span><textarea class="control" name="reason" rows="3" required maxlength="500"><?= e($reason) ?></textarea></label>
<label class="cn-cancel-confirmation"><input type="checkbox" name="confirmed" value="1" required><span>Tôi xác nhận phiếu chưa được xuất hoặc nhận hàng.<?php if ($kind==='supplier'): ?> Nếu đã đặt hàng với nhà cung cấp, tôi đã thống nhất việc hủy với họ.<?php endif; ?></span></label>
<div class="account-form-actions"><button class="btn btn-primary" type="submit">Xác nhận hủy phiếu</button><a class="btn btn-secondary" href="<?= e(page_url('transfer').'&request='.rawurlencode($info['maYeuCau'])) ?>">Quay lại</a></div>
</form>
<?php else: ?><p role="alert">Trạng thái hiện tại không cho phép hủy phiếu này.</p><?php endif; ?>
<?php else: ?><a class="btn btn-secondary" href="<?= e(page_url('transfer')) ?>">Quay lại</a><?php endif; ?>
</section></div>
