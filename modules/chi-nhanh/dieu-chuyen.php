<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_once __DIR__ . '/NguyenLieu.php';
NguyenLieu::yeuCauChuChuoi();
// Màn hình và thao tác hủy phiếu.
if (isset($_GET['cancel_type']) || isset($_GET['cancel_id'])) {
NguyenLieu::yeuCauChuChuoi();$error='';$reason='';$info=null;
$kind=$_GET['cancel_type'] ?? '';$id=$_GET['cancel_id'] ?? '';
if (!is_string($kind) || !is_string($id)) { http_response_code(400);echo 'Thông tin phiếu không hợp lệ.';return; }
try { $definition=NguyenLieu::loaiPhieu($kind);$info=NguyenLieu::chiTietPhieu($kind,$id); }
catch (DomainException $ex) { http_response_code(404);$error=$ex->getMessage(); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    if (post_text('action')!=='cancel_supply') { http_response_code(400);echo 'Thao tác không hợp lệ.';return; }
    $reason=post_text('reason');
    if ($info) {
        $ketQua=NguyenLieu::huyPhieu($kind,$id,$reason,post_text('confirmed')==='1');
        if ($ketQua['ok']) {
            $_SESSION['transfer_notice']='Đã hủy phiếu '.$id.'. Phần nguyên liệu tương ứng đã trở lại phần cần xử lý của yêu cầu gốc.';
            redirect_to('index.php?page=transfer&request='.rawurlencode($ketQua['data']['maYeuCau']));
        }
        $error=$ketQua['message'];http_response_code($ketQua['code']==='SERVER_ERROR'?503:422);
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
<?php
return;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && post_text('action')==='approve_pending_transfer') {
    require_csrf();
    $ketQua=NguyenLieu::duyetDieuChuyenCu(post_text('transfer_id'));
    if ($ketQua['ok']) {
        $_SESSION['transfer_notice']='Chủ chuỗi đã duyệt phiếu điều chuyển '.$ketQua['data']['maDieuChuyen'].'.';
    } else { $_SESSION['transfer_error']=$ketQua['message']; }
    redirect_to('index.php?page=transfer');
}
$mode=$_GET['mode'] ?? 'review';
if (!is_string($mode) || !in_array($mode,['review','transfer','supplier'],true)) { http_response_code(400);echo 'Chức năng không hợp lệ.';return; }
if (empty($_GET['request']) && $_SERVER['REQUEST_METHOD']==='GET') { $mode='review'; }
// Màn hình và thao tác nhập từ nhà cung cấp.
if ($mode==='supplier') {
NguyenLieu::yeuCauChuChuoi();
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
    $ketQua=NguyenLieu::taoNhapNCC($requestId,$nvl,$form['supplier'],$form['quantity'],$operation);
    if ($ketQua['ok']) {
        $_SESSION['supplier_notice']='Đã lưu yêu cầu nhập nguyên liệu '.$ketQua['data']['maDonNhap'].' từ nhà cung cấp, chờ nhập hàng.';
        redirect_to('index.php?page=transfer&mode=supplier&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl));
    }
    $error=$ketQua['message'];http_response_code($ketQua['code']==='SERVER_ERROR'?503:422);
}
$request=null;$lines=[];$selected=null;$suppliers=[];
if ($requestId!=='') {
    $request=NguyenLieu::chiTietYeuCau($requestId);
    if (!$request) { http_response_code(404);$error='Không tìm thấy yêu cầu bổ sung.'; }
    else {
        $lines=NguyenLieu::nguyenLieuYeuCau($requestId);
        if ($nvl==='') {
            $remaining=array_values(array_filter($lines,static fn(array $line):bool=>NguyenLieu::soLuongPhanNghin($line['conThieu'])>0));
            if ($remaining) { $nvl=$remaining[0]['maNVL']; }
        }
        foreach ($lines as $line) { if ($line['maNVL']===$nvl) { $selected=$line;break; } }
        if ($nvl!=='' && !$selected) { http_response_code(404);$error='Nguyên liệu không thuộc yêu cầu này.'; }
        if ($selected && NguyenLieu::soLuongPhanNghin($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG') { $suppliers=NguyenLieu::nhaCungCap($nvl); }
    }
}
if ($selected && $suppliers && $operation==='') {
    $operation=bin2hex(random_bytes(9));$_SESSION['supplier_forms'][$operation]=[$requestId,$nvl];
    while (count($_SESSION['supplier_forms'])>30) { array_shift($_SESSION['supplier_forms']); }
}
$remainingLines=array_values(array_filter($lines,static fn(array $line):bool=>NguyenLieu::soLuongPhanNghin($line['conThieu'])>0));
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
<?php if ($selected && NguyenLieu::soLuongPhanNghin($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG'): ?>
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
<?php
return;
}

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
    $ketQua=NguyenLieu::taoDieuChuyen($requestId,$nvl,$form['source'],$form['quantity'],$operation);
    if ($ketQua['ok']) {
        $_SESSION['transfer_notice']='Chủ chuỗi đã duyệt phiếu điều chuyển '.$ketQua['data']['maDieuChuyen'].'.';
        redirect_to('index.php?page=transfer&mode=transfer&request='.rawurlencode($requestId).'&nvl='.rawurlencode($nvl));
    }
    $error=$ketQua['message'];http_response_code($ketQua['code']==='SERVER_ERROR'?503:422);
}
$requests=$requestId===''?NguyenLieu::danhSachYeuCau():[];$request=null;$lines=[];$selected=null;$sources=[];
if ($requestId!=='') {
    $request=NguyenLieu::chiTietYeuCau($requestId);
    if (!$request) { http_response_code(404);$error='Không tìm thấy yêu cầu bổ sung.'; }
    else {
        $lines=NguyenLieu::nguyenLieuYeuCau($requestId);
        if ($nvl==='') {
            $remaining=array_values(array_filter($lines,static fn(array $line):bool=>NguyenLieu::soLuongPhanNghin($line['conThieu'])>0));
            if ($remaining) { $nvl=$remaining[0]['maNVL']; }
        }
        foreach ($lines as $line) { if ($line['maNVL']===$nvl) { $selected=$line;break; } }
        if ($nvl!=='' && !$selected) { http_response_code(404);$error='Nguyên liệu không thuộc yêu cầu này.'; }
        if ($selected && NguyenLieu::soLuongPhanNghin($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG') { $sources=NguyenLieu::nguonChiNhanh($request,$nvl); }
    }
}
if ($mode==='transfer' && $selected && $sources && $operation==='') {
    $operation=bin2hex(random_bytes(9));
    $_SESSION['transfer_forms'][$operation]=[$requestId,$nvl];
    while (count($_SESSION['transfer_forms'])>30) { array_shift($_SESSION['transfer_forms']); }
}
$remainingLines=array_values(array_filter($lines,static fn(array $line):bool=>NguyenLieu::soLuongPhanNghin($line['conThieu'])>0));
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
<?php if ($selected && NguyenLieu::soLuongPhanNghin($selected['conThieu'])>0 && $selected['trangThai']==='HOAT_DONG' && $request['trangThaiChiNhanh']==='HOAT_DONG'): ?>
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
<?php
// Các phần hiển thị dùng chung của trang nguyên liệu.
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
<?php foreach ($lines as $line): ?><option value="<?= e($line['maNVL']) ?>" <?= $line['maNVL']===$nvl?'selected':'' ?> <?= $line['trangThai']!=='HOAT_DONG'?'disabled':'' ?>><?= e($line['tenNVL'].' — '.(NguyenLieu::soLuongPhanNghin($line['conThieu'])>0?'còn thiếu '.$line['conThieu'].' '.$line['donViTinh']:'đã đủ phương án')) ?></option><?php endforeach; ?>
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
$recent=NguyenLieu::lichSuDieuChuyen();
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
<?php $recent=NguyenLieu::lichSuNhapNCC();chi_nhanh_lich_su_nhap($recent); ?>
</section>
<?php
}

