<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_once __DIR__.'/giao-dien.php';
require_permission('branches.manage');
$id=$_GET['id'] ?? null;
if (!is_string($id) || $id==='' || strlen($id)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã chi nhánh không hợp lệ.</p></div>';return; }
$editing=chi_nhanh_chi_tiet($id);
if (!$editing) { http_response_code(404);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Không tìm thấy chi nhánh.</p></div>';return; }
if ($editing['trangThai']!=='HOAT_DONG') { http_response_code(422);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Chi nhánh đã ngừng hoạt động, không thể cập nhật.</p></div>';return; }
$form=['tenChiNhanh'=>$editing['tenChiNhanh'],'diaChi'=>$editing['diaChi'] ?? '', 'soDienThoai'=>$editing['soDienThoai'] ?? '',
    'maQuanLy'=>$editing['maQuanLy'] ?? '', 'soBan'=>(string)$editing['soBanSuDung'],'sucChua'=>'4',
    'menu'=>array_column(array_filter($editing['thucDon'],static fn(array $m):bool=>$m['trangThai']==='DANG_BAN'),'maMon')];
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    if (post_text('action')!=='update') { http_response_code(400);echo 'Thao tác không hợp lệ.';return; }
    foreach (['tenChiNhanh','diaChi','soDienThoai','maQuanLy','soBan','sucChua'] as $field) { $form[$field]=trim(post_text($field)); }
    $menu=$_POST['menu'] ?? [];
    $form['menu']=is_array($menu)?array_values(array_filter($menu,'is_string')):[];
    try {
        if (!is_array($menu) || count($form['menu'])!==count($menu)) { throw new DomainException('Danh sách món ăn không hợp lệ.'); }
        chi_nhanh_cap_nhat($id,$form);
        $_SESSION['branch_notice']='Cập nhật chi nhánh thành công.';
        redirect_to('index.php?page=branches&id='.rawurlencode($id).'#branch-detail');
    } catch (DomainException $e) { $error=$e->getMessage();http_response_code(422); }
    catch (PDOException $e) { error_log('SD-15: '.$e->getMessage());$error='Chưa lưu được thay đổi. Vui lòng thử lại.';http_response_code(503); }
}
$managers=chi_nhanh_quan_ly($id);$menuOptions=chi_nhanh_menu_sua($id);
?>
<div class="screen-content chi-nhanh-module"><section class="card account-card" aria-labelledby="edit-branch-title">
<h2 class="title title-md" id="edit-branch-title">Cập nhật thông tin chi nhánh</h2>
<p class="muted"><?= e($editing['maChiNhanh'].' · '.$editing['tenChiNhanh']) ?></p>
<?php if ($error): ?><p class="account-message error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="account-form" action="<?= e(page_url('branches').'&action=edit&id='.rawurlencode($id)) ?>">
<?= csrf_input() ?><input type="hidden" name="action" value="update">
<label class="field"><span class="field-label">Tên chi nhánh *</span><input class="control" name="tenChiNhanh" maxlength="100" value="<?= e($form['tenChiNhanh']) ?>" required></label>
<label class="field"><span class="field-label">Số điện thoại *</span><input class="control" type="tel" name="soDienThoai" pattern="0[0-9]{9,10}" maxlength="11" value="<?= e($form['soDienThoai']) ?>" required></label>
<label class="field cn-full-width"><span class="field-label">Địa chỉ *</span><input class="control" name="diaChi" maxlength="255" value="<?= e($form['diaChi']) ?>" required></label>
<label class="field cn-full-width"><span class="field-label">Người quản lý</span><select class="control" name="maQuanLy"><option value="">Chưa phân công</option><?php foreach ($managers as $manager): ?><option value="<?= e($manager['maNhanVien']) ?>" <?= $form['maQuanLy']===$manager['maNhanVien']?'selected':'' ?>><?= e($manager['hoTen']) ?></option><?php endforeach; ?></select><span class="muted">Chỉ chọn quản lý đang làm tại chi nhánh này.</span></label>
<label class="field"><span class="field-label">Số bàn đang sử dụng *</span><input class="control" type="number" name="soBan" min="1" max="100" step="1" value="<?= e($form['soBan']) ?>" required><span class="muted">Giảm số lượng sẽ ngừng sử dụng bàn trống, giữ lại lịch sử.</span></label>
<label class="field"><span class="field-label">Số người mỗi bàn mới *</span><input class="control" type="number" name="sucChua" min="1" max="20" step="1" value="<?= e($form['sucChua']) ?>" required><span class="muted">Áp dụng khi cần tạo thêm bàn; bàn có sẵn giữ sức chứa hiện tại.</span></label>
<fieldset class="cn-menu-options"><legend>Thực đơn chi nhánh</legend>
<?php foreach ($menuOptions as $dish): ?><label class="cn-menu-option"><input type="checkbox" name="menu[]" value="<?= e($dish['maMon']) ?>" <?= in_array($dish['maMon'],$form['menu'],true)?'checked':'' ?>> <?= e($dish['tenMon']) ?> · <?= number_format((float)$dish['donGia'],0,',','.') ?>đ<?= $dish['trangThai']==='NGUNG'?' (ngừng kinh doanh)':'' ?></label><?php endforeach; ?>
<?php if (!$menuOptions): ?><p class="muted">Chưa có món ăn để chọn.</p><?php endif; ?>
</fieldset>
<div class="account-form-actions"><button type="submit" class="btn btn-primary">Lưu thay đổi</button> <a class="btn btn-secondary" href="<?= e(page_url('branches').'&id='.rawurlencode($id).'#branch-detail') ?>">Về chi tiết</a></div>
</form></section></div>
