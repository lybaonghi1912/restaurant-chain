<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
require_once __DIR__.'/giao-dien.php';
require_permission('branches.view');
require_once __DIR__.'/data.php';
if (($_GET['action'] ?? '')==='edit') { require __DIR__.'/edit.php';return; }
if (($_GET['action'] ?? '')==='delete') { require __DIR__.'/delete.php';return; }
if (($_GET['action'] ?? '')==='purge') { require __DIR__.'/purge.php';return; }

$createBranch=($_GET['action'] ?? '')==='create';
$createError='';
$branchForm=['tenChiNhanh'=>'','diaChi'=>'','soDienThoai'=>'','soBan'=>'1','sucChua'=>'4','menu'=>[]];
$branchNotice=$_SESSION['branch_notice'] ?? null;
unset($_SESSION['branch_notice']);
if ($createBranch) { require_permission('branches.manage'); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_permission('branches.manage');
    require_csrf();
    if (post_text('action')!=='create') { http_response_code(400); echo 'Thao tác không hợp lệ.'; return; }
    $createBranch=true;
    foreach (['tenChiNhanh','diaChi','soDienThoai','soBan','sucChua'] as $field) { $branchForm[$field]=trim(post_text($field)); }
    $submittedMenu=$_POST['menu'] ?? [];
    $branchForm['menu']=is_array($submittedMenu)?array_values(array_filter($submittedMenu,'is_string')):[];
    try {
        if (!is_array($submittedMenu) || count($branchForm['menu'])!==count($submittedMenu)) { throw new DomainException('Danh sách món ăn không hợp lệ.'); }
        $newBranchId=chi_nhanh_them($branchForm);
        $_SESSION['branch_notice']='Thêm chi nhánh thành công.';
        redirect_to('index.php?page=branches&id='.rawurlencode($newBranchId).'#branch-detail');
    } catch (DomainException $error) {
        $createError=$error->getMessage();
        http_response_code(422);
    } catch (PDOException $error) {
        error_log('SD-13: '.$error->getMessage());
        $createError='Chưa lưu được chi nhánh. Vui lòng thử lại.';
        http_response_code(503);
    }
}
$branchMenu=$createBranch?chi_nhanh_mon_dang_ban():[];
$branchList=chi_nhanh_danh_sach();
$branchDetail=null;
$branchError='';
if (array_key_exists('id',$_GET)) {
    $id=$_GET['id'];
    if (!is_string($id) || trim($id)==='' || strlen($id)>10) {
        http_response_code(400);
        $branchError='Mã chi nhánh không hợp lệ.';
    } else {
        $branchDetail=chi_nhanh_chi_tiet($id);
        if (!$branchDetail) {
            http_response_code(404);
            $branchError='Không tìm thấy chi nhánh.';
        }
    }
}
$branchText=static fn (?string $value): string => $value!==null && trim($value)!=='' ? $value : 'Chưa cập nhật';
?>
<div class="screen-content chi-nhanh-module">
<?php if ($branchNotice): ?><p class="account-message success" role="status"><?= e($branchNotice) ?></p><?php endif; ?>
<?php if ($createBranch): ?>
<section class="card account-card cn-space-bottom" aria-labelledby="create-branch-title">
    <h2 class="title title-md" id="create-branch-title">Thêm chi nhánh mới</h2>
    <?php if ($createError): ?><p class="account-message error" role="alert"><?= e($createError) ?></p><?php endif; ?>
    <form method="post" action="<?= e(page_url('branches').'&action=create') ?>" class="account-form">
        <?= csrf_input() ?><input type="hidden" name="action" value="create">
        <label class="field"><span class="field-label">Tên chi nhánh *</span><input class="control" name="tenChiNhanh" maxlength="100" value="<?= e($branchForm['tenChiNhanh']) ?>" required></label>
        <label class="field"><span class="field-label">Số điện thoại *</span><input class="control" type="tel" name="soDienThoai" pattern="0[0-9]{9,10}" maxlength="11" value="<?= e($branchForm['soDienThoai']) ?>" placeholder="Ví dụ 0901234567" required></label>
        <label class="field cn-full-width"><span class="field-label">Địa chỉ *</span><input class="control" name="diaChi" maxlength="255" value="<?= e($branchForm['diaChi']) ?>" required></label>
        <label class="field"><span class="field-label">Số bàn ban đầu *</span><input class="control" type="number" name="soBan" min="1" max="100" step="1" value="<?= e($branchForm['soBan']) ?>" required></label>
        <label class="field"><span class="field-label">Số người mỗi bàn *</span><input class="control" type="number" name="sucChua" min="1" max="20" step="1" value="<?= e($branchForm['sucChua']) ?>" required></label>
        <label class="field cn-full-width"><span class="field-label">Người quản lý</span><input class="control" value="Chưa phân công" disabled><span class="muted">Phân công sau khi có nhân viên quản lý thuộc chi nhánh mới.</span></label>
        <fieldset class="cn-menu-options" id="initial-branch-menu">
            <legend>Thực đơn ban đầu</legend>
            <?php if ($branchMenu): ?><label class="cn-menu-select-all" data-menu-select-all-label hidden><input type="checkbox" data-menu-select-all> Chọn tất cả</label><?php endif; ?>
            <?php foreach ($branchMenu as $dish): ?>
            <label class="cn-menu-option"><input type="checkbox" name="menu[]" value="<?= e($dish['maMon']) ?>" <?= in_array($dish['maMon'],$branchForm['menu'],true)?'checked':'' ?>> <?= e($dish['tenMon']) ?> · <?= number_format((float)$dish['donGia'],0,',','.') ?>đ</label>
            <?php endforeach; ?>
            <?php if (!$branchMenu): ?><p class="muted">Chưa có món ăn hoạt động để chọn.</p><?php endif; ?>
        </fieldset>
        <div class="account-form-actions"><button type="submit" class="btn btn-primary">Lưu chi nhánh</button> <a class="btn btn-secondary" href="<?= e(page_url('branches')) ?>">Về danh sách</a></div>
    </form>
</section>
<?php endif; ?>
<section class="card data-card <?= $createBranch?'cn-list-after-form':'' ?>" aria-labelledby="branch-list-title">
    <div class="data-toolbar">
        <div><h2 class="title title-sm" id="branch-list-title">Danh sách chi nhánh</h2><span class="muted"><?= count($branchList) ?> chi nhánh trong chuỗi</span></div>
        <?php if (can('branches.manage')): ?><a class="btn btn-primary" href="<?= e(page_url('branches').'&action=create') ?>">+ Thêm chi nhánh mới</a><?php endif; ?>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th scope="col">Mã</th><th scope="col">Chi nhánh</th><th scope="col">Địa chỉ</th><th scope="col">Điện thoại</th><th scope="col">Quản lý</th><th scope="col">Số bàn</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($branchList as $item): ?>
                <tr>
                    <td><?= e($item['maChiNhanh']) ?></td><td><?= e($item['tenChiNhanh']) ?></td>
                    <td><?= e($branchText($item['diaChi'])) ?></td><td><?= e($branchText($item['soDienThoai'])) ?></td>
                    <td><?= e($item['tenQuanLy'] ?? 'Chưa phân công') ?></td><td><?= (int)$item['soBan'] ?></td>
                    <td><span class="badge <?= $item['trangThai']==='HOAT_DONG'?'badge-success':'badge-neutral' ?>"><?= $item['trangThai']==='HOAT_DONG'?'Hoạt động':'Ngừng hoạt động' ?></span></td>
                    <td><div class="management-actions"><a class="btn btn-ghost" href="<?= e(page_url('branches').'&id='.rawurlencode($item['maChiNhanh']).'#branch-detail') ?>" aria-label="<?= e('Xem chi tiết '.$item['tenChiNhanh']) ?>">Xem chi tiết</a><?php if (can('branches.manage') && $item['trangThai']==='HOAT_DONG'): ?><a class="btn btn-secondary" href="<?= e(page_url('branches').'&action=edit&id='.rawurlencode($item['maChiNhanh'])) ?>" aria-label="<?= e('Cập nhật '.$item['tenChiNhanh']) ?>">Cập nhật</a><a class="btn btn-danger" href="<?= e(page_url('branches').'&action=delete&id='.rawurlencode($item['maChiNhanh'])) ?>" aria-label="<?= e('Xóa '.$item['tenChiNhanh']) ?>">Xóa</a><?php endif; ?></div></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$branchList): ?><tr><td colspan="8" class="empty-filter">Chưa có chi nhánh.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($branchError): ?>
<section class="card data-card cn-space-top" id="branch-detail"><div class="data-toolbar"><p role="alert"><?= e($branchError) ?></p><a class="btn btn-secondary" href="<?= e(page_url('branches')) ?>">Về danh sách</a></div></section>
<?php elseif ($branchDetail): ?>
<section class="card data-card cn-space-top" id="branch-detail" aria-labelledby="branch-detail-title">
    <div class="data-toolbar"><div><span class="eyebrow accent">CHI TIẾT CHI NHÁNH</span><h2 class="title title-sm" id="branch-detail-title"><?= e($branchDetail['tenChiNhanh']) ?></h2></div><a class="btn btn-secondary" href="<?= e(page_url('branches')) ?>">Đóng chi tiết</a></div>
    <div class="table-scroll"><table class="data-table branch-info-table"><tbody>
        <tr><th scope="row">Mã chi nhánh</th><td><?= e($branchDetail['maChiNhanh']) ?></td></tr>
        <tr><th scope="row">Địa chỉ</th><td><?= e($branchText($branchDetail['diaChi'])) ?></td></tr>
        <tr><th scope="row">Số điện thoại</th><td><?= e($branchText($branchDetail['soDienThoai'])) ?></td></tr>
        <tr><th scope="row">Quản lý</th><td><?= e($branchDetail['tenQuanLy'] ?? 'Chưa phân công') ?></td></tr>
        <tr><th scope="row">Điện thoại quản lý</th><td><?= e($branchText($branchDetail['dienThoaiQuanLy'])) ?></td></tr>
        <tr><th scope="row">Tổng số bàn</th><td><?= (int)$branchDetail['soBan'] ?></td></tr>
        <tr><th scope="row">Số bàn đang sử dụng</th><td><?= (int)$branchDetail['soBanSuDung'] ?></td></tr>
        <tr><th scope="row">Trạng thái</th><td><span class="badge branch-status <?= $branchDetail['trangThai']==='HOAT_DONG'?'badge-success':'badge-danger' ?>"><?= $branchDetail['trangThai']==='HOAT_DONG'?'Hoạt động':'Ngừng hoạt động' ?></span></td></tr>
    </tbody></table></div>
    <div class="data-toolbar"><h3 class="title title-sm">Thực đơn chi nhánh</h3><span class="muted"><?= count($branchDetail['thucDon']) ?> món</span></div>
    <div class="table-scroll"><table class="data-table">
        <thead><tr><th scope="col">Mã món</th><th scope="col">Tên món</th><th scope="col">Giá bán</th><th scope="col">Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach ($branchDetail['thucDon'] as $dish): ?>
            <tr><td><?= e($dish['maMon']) ?></td><td><?= e($dish['tenMon']) ?></td><td><?= number_format((float)$dish['giaBan'],0,',','.') ?>đ</td><td><span class="badge branch-status <?= $dish['trangThaiMon']==='NGUNG'?'badge-danger':($dish['trangThai']==='DANG_BAN'?'badge-success':'badge-warning') ?>"><?= $dish['trangThaiMon']==='NGUNG'?'Ngừng kinh doanh':($dish['trangThai']==='DANG_BAN'?'Đang bán':'Tạm ngừng') ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (!$branchDetail['thucDon']): ?><tr><td colspan="4" class="empty-filter">Chi nhánh chưa có thực đơn.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php if ($branchDetail['trangThai']==='NGUNG' && can('branches.manage') && current_user()['maVaiTro']==='CHU_CHUOI'): ?>
    <div class="cn-purge-section"><h3 class="title title-sm">Xóa vĩnh viễn dữ liệu chi nhánh</h3><p class="muted cn-purge-description">Chi nhánh đã ngừng hoạt động. Xóa vĩnh viễn sẽ loại bỏ dữ liệu riêng của chi nhánh khỏi hệ thống và không thể hoàn tác.</p><a class="btn btn-danger" href="<?= e(page_url('branches').'&action=purge&id='.rawurlencode($branchDetail['maChiNhanh'])) ?>">Xóa toàn bộ dữ liệu chi nhánh</a></div>
    <?php endif; ?>
</section>
<?php endif; ?>
</div>
