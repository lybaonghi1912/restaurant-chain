<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_once __DIR__.'/giao-dien.php';
require_once __DIR__.'/transfer-data.php';
dc_require_owner();
$notice=$_SESSION['supplier_notice'] ?? '';unset($_SESSION['supplier_notice']);
$error='';$requestId=$_GET['request'] ?? '';$nvl=$_GET['nvl'] ?? '';
if (!is_string($requestId) || strlen($requestId)>20 || !is_string($nvl) || strlen($nvl)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã yêu cầu hoặc nguyên liệu không hợp lệ.</p></div>';return; }
$form=['supplier'=>'','quantity'=>''];$operation='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    if (post_text('action')!=='create_supplier') { http_response_code(400);echo 'Thao tác không hợp lệ.';return; }
    $form=['supplier'=>trim(post_text('supplier')),'quantity'=>trim(post_text('quantity'))];$operation=post_text('operation');
    $context=$_SESSION['supplier_forms'][$operation] ?? null;
    if (!$context || $context!==[$requestId,$nvl]) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Biểu mẫu đã hết hiệu lực. Vui lòng mở lại yêu cầu.</p></div>';return; }
    try {
        $id=ncc_create($requestId,$nvl,$form['supplier'],$form['quantity'],$operation);
        $_SESSION['supplier_notice']='Đã lưu yêu cầu nhập nguyên liệu '.$id.' từ nhà cung cấp, chờ nhập hàng.';
        redirect_to('index.php?page=transfer&mode=supplier&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl));
    } catch (DomainException $e) { $error=$e->getMessage();http_response_code(422); }
    catch (PDOException $e) { error_log('SD-18: '.$e->getMessage());$error='Chưa lưu được yêu cầu nhập nguyên liệu. Vui lòng thử lại.';http_response_code(503); }
}
$request=null;$lines=[];$selected=null;$suppliers=[];
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
        if ($selected && dc_units($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG') { $suppliers=ncc_options($nvl); }
    }
}
if ($selected && $suppliers && $operation==='') {
    $operation=bin2hex(random_bytes(9));$_SESSION['supplier_forms'][$operation]=[$requestId,$nvl];
    while (count($_SESSION['supplier_forms'])>30) { array_shift($_SESSION['supplier_forms']); }
}
$remainingLines=array_values(array_filter($lines,static fn(array $line):bool=>dc_units($line['conThieu'])>0));
?>
<div class="screen-content chi-nhanh-module">
<?php chi_nhanh_tieu_de_bo_sung(); ?>
<div class="management-actions cn-back-link"><a class="btn btn-secondary" href="<?= e(page_url('transfer').'&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl)) ?>">Xem lại yêu cầu và nguồn cung</a></div>
<?php if ($notice): ?><p class="account-message success" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="account-message error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($request): ?>
<?php $requestDetailTitle='supplier-detail-title';chi_nhanh_chi_tiet_yeu_cau($request,$lines,$remainingLines,$nvl,$requestDetailTitle); ?>
<?php endif; ?>
<?php chi_nhanh_chon_nguyen_lieu($request,$lines,$remainingLines,$requestId,$nvl); ?>
<?php if ($selected && dc_units($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG'): ?>
<section class="card data-card cn-space-top" aria-labelledby="supplier-options-title">
    <div class="data-toolbar"><h3 class="title title-sm" id="supplier-options-title"><?= e($selected['tenNVL']) ?> — nhà cung cấp phù hợp</h3></div>
    <div class="table-scroll"><table class="data-table"><thead><tr><th>Nhà cung cấp</th><th>Điện thoại</th><th>Email</th><th>Địa chỉ</th></tr></thead><tbody>
    <?php foreach ($suppliers as $supplier): ?><tr><td><?= e($supplier['tenNCC']) ?></td><td><?= e($supplier['soDienThoai'] ?? 'Chưa cập nhật') ?></td><td><?= e($supplier['email'] ?? 'Chưa cập nhật') ?></td><td><?= e($supplier['diaChi'] ?? 'Chưa cập nhật') ?></td></tr><?php endforeach; ?>
    <?php if (!$suppliers): ?><tr><td colspan="4" class="empty-filter">Không có nhà cung cấp phù hợp. Yêu cầu bổ sung vẫn chưa được xử lý hết.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php if ($suppliers): ?>
    <form method="post" class="account-form cn-padding" action="<?= e(page_url('transfer').'&mode=supplier&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl)) ?>">
        <?= csrf_input() ?><input type="hidden" name="action" value="create_supplier"><input type="hidden" name="operation" value="<?= e($operation) ?>">
        <label class="field"><span class="field-label">Nhà cung cấp *</span><select class="control" name="supplier" required><option value="">Chọn nhà cung cấp</option><?php foreach ($suppliers as $supplier): ?><option value="<?= e($supplier['maNCC']) ?>" <?= $form['supplier']===$supplier['maNCC']?'selected':'' ?>><?= e($supplier['tenNCC']) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span class="field-label">Số lượng cần nhập (<?= e($selected['donViTinh']) ?>) *</span><input class="control" type="text" inputmode="decimal" name="quantity" maxlength="19" pattern="[0-9]+(\.[0-9]{1,3})?" value="<?= e($form['quantity']) ?>" required><span class="muted">Tối đa <?= e($selected['conThieu']) ?>; nhập phần lẻ bằng dấu chấm, ví dụ 1.5.</span></label>
        <div class="account-form-actions"><button type="submit" class="btn btn-primary">Lưu yêu cầu nhập</button></div>
    </form>
    <p class="muted cn-form-note">Yêu cầu được lưu ở trạng thái Chờ nhập hàng; số lượng tồn kho chưa thay đổi.</p>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php chi_nhanh_lich_su_bo_sung(); ?>
</div>
