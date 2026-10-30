<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_once __DIR__.'/giao-dien.php';
require_once __DIR__.'/transfer-data.php';
dc_require_owner();
if (isset($_GET['cancel_type']) || isset($_GET['cancel_id'])) { require __DIR__.'/huy-phieu.php';return; }
if ($_SERVER['REQUEST_METHOD']==='POST' && post_text('action')==='approve_pending_transfer') {
    require_csrf();
    try {
        $id=dc_approve_pending(post_text('transfer_id'));
        $_SESSION['transfer_notice']='Chủ chuỗi đã duyệt phiếu điều chuyển '.$id.'.';
    } catch (DomainException $error) { $_SESSION['transfer_error']=$error->getMessage(); }
    catch (PDOException $error) { error_log('SD17 approval: '.$error->getMessage());$_SESSION['transfer_error']='Chưa duyệt được phiếu. Vui lòng thử lại.'; }
    redirect_to('index.php?page=transfer');
}
$mode=$_GET['mode'] ?? 'review';
if (!is_string($mode) || !in_array($mode,['review','transfer','supplier'],true)) { http_response_code(400);echo 'Chức năng không hợp lệ.';return; }
if (empty($_GET['request']) && $_SERVER['REQUEST_METHOD']==='GET') { $mode='review'; }
if ($mode==='supplier') { require __DIR__.'/nhap-ncc.php';return; }
$notice=$_SESSION['transfer_notice'] ?? '';unset($_SESSION['transfer_notice']);
$error=$_SESSION['transfer_error'] ?? '';unset($_SESSION['transfer_error']);$requestId=$_GET['request'] ?? '';$nvl=$_GET['nvl'] ?? '';
if (!is_string($requestId) || strlen($requestId)>20 || !is_string($nvl) || strlen($nvl)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã yêu cầu hoặc nguyên liệu không hợp lệ.</p></div>';return; }
$form=['source'=>'','quantity'=>''];$operation='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    if ($mode!=='transfer') { http_response_code(400);echo 'Vui lòng chọn phương án điều chuyển trước khi xác nhận.';return; }
    if (post_text('action')!=='create_transfer') { http_response_code(400);echo 'Thao tác không hợp lệ.';return; }
    $form=['source'=>trim(post_text('source')),'quantity'=>trim(post_text('quantity'))];$operation=post_text('operation');
    $context=$_SESSION['transfer_forms'][$operation] ?? null;
    if (!$context || $context!==[$requestId,$nvl]) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Biểu mẫu đã hết hiệu lực. Vui lòng mở lại yêu cầu.</p></div>';return; }
    try {
        $id=dc_create($requestId,$nvl,$form['source'],$form['quantity'],$operation);
        $_SESSION['transfer_notice']='Chủ chuỗi đã duyệt phiếu điều chuyển '.$id.'.';
        redirect_to('index.php?page=transfer&mode=transfer&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl));
    } catch (DomainException $e) { $error=$e->getMessage();http_response_code(422); }
    catch (PDOException $e) { error_log('SD-17: '.$e->getMessage());$error='Chưa lưu được yêu cầu điều chuyển. Vui lòng thử lại.';http_response_code(503); }
}
$requests=$requestId===''?dc_requests():[];$request=null;$lines=[];$selected=null;$sources=[];
if ($requestId!=='') {
    $request=dc_request($requestId);
    if (!$request) { http_response_code(404);$error='Không tìm thấy yêu cầu bổ sung.'; }
    else {
        $lines=dc_lines($requestId);
        if ($nvl==='') {
            $remaining=array_values(array_filter($lines,static fn(array $line):bool=>dc_units($line['conThieu'])>0));
            if ($remaining) { $nvl=$remaining[0]['maNVL']; }
        }
        foreach ($lines as $line) { if ($line['maNVL']===$nvl) { $selected=$line;break; } }
        if ($nvl!=='' && !$selected) { http_response_code(404);$error='Nguyên liệu không thuộc yêu cầu này.'; }
        if ($selected && dc_units($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG') { $sources=dc_sources($request,$nvl); }
    }
}
if ($mode==='transfer' && $selected && $sources && $operation==='') {
    $operation=bin2hex(random_bytes(9));
    $_SESSION['transfer_forms'][$operation]=[$requestId,$nvl];
    while (count($_SESSION['transfer_forms'])>30) { array_shift($_SESSION['transfer_forms']); }
}
$remainingLines=array_values(array_filter($lines,static fn(array $line):bool=>dc_units($line['conThieu'])>0));
?>
<div class="screen-content chi-nhanh-module">
<?php chi_nhanh_tieu_de_bo_sung(); ?>
<?php if ($mode==='transfer'): ?><div class="management-actions cn-space-bottom"><a class="btn btn-secondary" href="<?= e(page_url('transfer').'&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl)) ?>">Xem lại yêu cầu và nguồn cung</a></div><?php endif; ?>
<?php if ($notice): ?><p class="account-message success" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="account-message error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($requestId===''): ?>
<section class="card data-card" aria-labelledby="pending-request-title">
    <div class="data-toolbar"><h3 class="title title-sm" id="pending-request-title">Yêu cầu bổ sung cần xử lý</h3></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>Mã yêu cầu</th><th>Chi nhánh gửi yêu cầu</th><th>Ngày tạo</th><th>Lý do</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($requests as $item): ?><tr><td><?= e($item['maYeuCau']) ?></td><td><?= e($item['tenChiNhanh']) ?></td><td><?= e($item['ngayTao']) ?></td><td><?= e($item['lyDo']) ?></td><td><a class="btn btn-secondary" href="<?= e(page_url('transfer').'&request='.rawurlencode($item['maYeuCau'])) ?>">Xem yêu cầu</a></td></tr><?php endforeach; ?>
    <?php if (!$requests): ?><tr><td colspan="5" class="empty-filter">Chưa có yêu cầu bổ sung cần xử lý.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php endif; ?>
<?php if ($request): ?><div class="management-actions cn-space-top-small"><a class="btn btn-secondary" href="<?= e(page_url('transfer')) ?>">Danh sách yêu cầu</a></div>
<?php $requestDetailTitle='request-detail-title';chi_nhanh_chi_tiet_yeu_cau($request,$lines,$remainingLines,$nvl,$requestDetailTitle); ?>
<?php endif; ?>
<?php chi_nhanh_chon_nguyen_lieu($request,$lines,$remainingLines,$requestId,$nvl); ?>
<?php if ($selected && dc_units($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG'): ?>
<section class="card data-card cn-space-top" aria-labelledby="source-stock-title">
    <div class="data-toolbar"><div><h3 class="title title-sm" id="source-stock-title"><?= e($selected['tenNVL']) ?> — tồn kho tại các chi nhánh khác</h3><span class="muted">Số lượng theo <?= e($selected['donViTinh']) ?>. Khoảng cách đường thẳng ước tính, không phải quãng đường vận chuyển.</span></div></div>
    <?php if (str_starts_with($request['lyDo'],'[DEMO')): ?><p class="muted cn-table-note">Khoảng cách trong mẫu được tính từ tọa độ minh họa.</p><?php endif; ?>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>Chi nhánh cung cấp</th><th>Tồn còn hạn</th><th>Đã cam kết</th><th>Có thể cung cấp</th><th>Hạn gần nhất</th><th>Khoảng cách</th></tr></thead><tbody>
    <?php foreach ($sources as $source): ?><tr><td><?= e($source['tenChiNhanh']) ?></td><td><?= e($source['tonConHan']) ?></td><td><?= e($source['camKet']) ?></td><td><?= e($source['khaDung']) ?></td><td><?= e($source['hanGanNhat']) ?></td><td><?= $source['khoangCach']===null?'Chưa có tọa độ':e(number_format($source['khoangCach'],1,',','.').' km') ?></td></tr><?php endforeach; ?>
    <?php if (!$sources): ?><tr><td colspan="6" class="empty-filter">Không có chi nhánh đáp ứng. Bạn có thể bổ sung bằng phương án nhập từ nhà cung cấp.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php if ($mode==='review'): ?><?php chi_nhanh_chon_phuong_an($sources,$requestId,$nvl); ?><?php endif; ?>
    <?php if ($mode==='transfer' && $sources): ?>
    <form method="post" class="account-form cn-padding" action="<?= e(page_url('transfer').'&mode=transfer&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl)) ?>">
        <?= csrf_input() ?><input type="hidden" name="action" value="create_transfer"><input type="hidden" name="operation" value="<?= e($operation) ?>">
        <label class="field"><span class="field-label">Chi nhánh cung cấp *</span><select class="control" name="source" required><option value="">Chọn chi nhánh</option><?php foreach ($sources as $source): ?><option value="<?= e($source['maChiNhanh']) ?>" <?= $form['source']===$source['maChiNhanh']?'selected':'' ?>><?= e($source['tenChiNhanh']) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span class="field-label">Số lượng cần điều chuyển (<?= e($selected['donViTinh']) ?>) *</span><input class="control" type="text" inputmode="decimal" name="quantity" maxlength="19" pattern="[0-9]+(\.[0-9]{1,3})?" value="<?= e($form['quantity']) ?>" required><span class="muted">Tối đa <?= e($selected['conThieu']) ?>; nhập phần lẻ bằng dấu chấm, ví dụ 1.5.</span></label>
        <div class="account-form-actions"><button type="submit" class="btn btn-primary">Xác nhận điều chuyển</button></div>
    </form>
    <p class="muted cn-form-note">Khi Chủ chuỗi xác nhận, phiếu được lưu là Đã duyệt. Số lượng tồn kho vật lý chưa thay đổi.</p>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php chi_nhanh_lich_su_bo_sung(); ?>
</div>
