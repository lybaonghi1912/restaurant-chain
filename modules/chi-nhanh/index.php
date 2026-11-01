<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }
require_permission('branches.view');
require_once __DIR__ . '/ChiNhanh.php';
// Boundary: nhận dữ liệu form, gọi Control, rồi hiển thị/redirect.
$branchAction=$_GET['action'] ?? '';
$branchErrorStatus=static fn(string $code): int => match ($code) {
    'NOT_FOUND'=>404, 'FORBIDDEN'=>403, 'CONFLICT'=>409, 'SERVER_ERROR'=>503, default=>422,
};

// 1. Xử lý dữ liệu: Thêm/Xem → Cập nhật → Xóa.
if (!in_array($branchAction,['edit','delete','purge'],true)) {
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
        $ketQua=ChiNhanh::them(array_replace($branchForm,['menu'=>$submittedMenu]));
        if ($ketQua['ok']) {
            $_SESSION['branch_notice']=$ketQua['message'];
            redirect_to('index.php?page=branches&id='.rawurlencode($ketQua['data']['maChiNhanh']).'#branch-detail');
        }
        $createError=$ketQua['message'];
        http_response_code($branchErrorStatus($ketQua['code']));
    }
    $branchMenu=$createBranch?ChiNhanh::danhSachMon():[];
    $branchList=ChiNhanh::danhSach();
    $branchDetail=null;
    $branchError='';
    if (array_key_exists('id',$_GET)) {
        $id=$_GET['id'];
        if (!is_string($id) || trim($id)==='' || strlen($id)>10) {
            http_response_code(400);
            $branchError='Mã chi nhánh không hợp lệ.';
        } else {
            $branchDetail=ChiNhanh::chiTiet($id);
            if (!$branchDetail) {
                http_response_code(404);
                $branchError='Không tìm thấy chi nhánh.';
            }
        }
    }
    $branchText=static fn (?string $value): string => $value!==null && trim($value)!=='' ? $value : 'Chưa cập nhật';
} elseif ($branchAction==='edit') {
    // Cập nhật chi nhánh.
    require_permission('branches.manage');
    $id=$_GET['id'] ?? null;
    if (!is_string($id) || $id==='' || strlen($id)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã chi nhánh không hợp lệ.</p></div>';return; }
    $editing=ChiNhanh::chiTiet($id);
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
        $ketQua=ChiNhanh::sua($id,array_replace($form,['menu'=>$menu]));
        if ($ketQua['ok']) {
            $_SESSION['branch_notice']=$ketQua['message'];
            redirect_to('index.php?page=branches&id='.rawurlencode($id).'#branch-detail');
        }
        $error=$ketQua['message'];
        http_response_code($branchErrorStatus($ketQua['code']));
    }
    $managers=ChiNhanh::danhSachQuanLy($id);$menuOptions=ChiNhanh::thucDonSua($id);
} elseif ($branchAction==='delete') {
    // Xóa chi nhánh: chuyển sang ngừng hoạt động.
    require_permission('branches.manage');
    $id=$_GET['id'] ?? null;
    if (!is_string($id) || trim($id)==='' || strlen($id)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã chi nhánh không hợp lệ.</p></div>';return; }
    $deleting=ChiNhanh::chiTiet($id);
    if (!$deleting) { http_response_code(404);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Không tìm thấy chi nhánh.</p></div>';return; }
    $error='';
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        require_csrf();
        if (post_text('action')!=='deactivate' || post_text('confirm')!=='1') { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Vui lòng xác nhận ngừng hoạt động chi nhánh.</p></div>';return; }
        $ketQua=ChiNhanh::ngungHoatDong($id);
        if ($ketQua['ok']) {
            $_SESSION['branch_notice']=$ketQua['message'];
            if (($_SESSION['branch'] ?? null)===$id) { unset($_SESSION['branch']); }
            redirect_to('index.php?page=branches');
        }
        $error=$ketQua['message'];
        http_response_code($branchErrorStatus($ketQua['code']));
    }
} elseif ($branchAction==='purge') {
    // Xóa vĩnh viễn: xác nhận riêng cho chi nhánh đã ngừng.
    ChiNhanh::yeuCauChuChuoi();
    $id=$_GET['id'] ?? null;
    if (!is_string($id) || trim($id)==='' || strlen($id)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã chi nhánh không hợp lệ.</p></div>';return; }
    $target=ChiNhanh::chiTiet($id);
    if (!$target) { http_response_code(404);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Không tìm thấy chi nhánh.</p></div>';return; }
    if ($target['trangThai']!=='NGUNG') { http_response_code(422);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Chỉ được xóa vĩnh viễn chi nhánh đã ngừng hoạt động.</p><a class="btn btn-secondary" href="'.e(page_url('branches')).'">Về danh sách</a></div>';return; }
    $error='';
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        require_csrf();$operation=post_text('operation');$confirmation=$_SESSION['purge_forms'][$operation] ?? null;
        if (post_text('action')!=='purge' || post_text('confirm')!=='1' || !is_array($confirmation) || ($confirmation['id'] ?? '')!==$id) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Xác nhận không hợp lệ. Vui lòng mở lại thông tin chi nhánh và xác nhận xóa.</p></div>';return; }
        $ketQua=ChiNhanh::xoaVinhVien($id,$confirmation['fingerprint']);
        if ($ketQua['ok']) {
            unset($_SESSION['purge_forms'][$operation]);
            if (($_SESSION['branch'] ?? null)===$id) { unset($_SESSION['branch']); }
            $_SESSION['branch_notice']='Đã xóa vĩnh viễn chi nhánh '.$target['tenChiNhanh'].' và dữ liệu riêng của chi nhánh.';
            redirect_to('index.php?page=branches');
        }
        $error=$ketQua['message'];
        http_response_code($branchErrorStatus($ketQua['code']));
    }
    $summary=ChiNhanh::thongKeDuLieu($id);$blockers=ChiNhanh::lyDoKhongTheXoa($id);
    $operation=bin2hex(random_bytes(16));$_SESSION['purge_forms'][$operation]=['id'=>$id,'fingerprint'=>ChiNhanh::dauVanTay($summary)];
    while (count($_SESSION['purge_forms'])>10) { array_shift($_SESSION['purge_forms']); }
}
?>
<?php // 2. Hiển thị biểu mẫu theo chức năng đã chọn. ?>
<?php if (!in_array($branchAction,['edit','delete','purge'],true)): ?>
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
<?php elseif ($branchAction==='edit'): ?>
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
<?php elseif ($branchAction==='delete'): ?>
<div class="screen-content chi-nhanh-module"><section class="card account-card" aria-labelledby="delete-branch-title">
    <h2 class="title title-md" id="delete-branch-title">Xác nhận xóa chi nhánh</h2>
    <?php if ($error): ?><p class="account-message error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <div class="table-scroll"><table class="data-table"><tbody>
        <tr><th scope="row">Mã chi nhánh</th><td><?= e($deleting['maChiNhanh']) ?></td></tr>
        <tr><th scope="row">Tên chi nhánh</th><td><?= e($deleting['tenChiNhanh']) ?></td></tr>
        <tr><th scope="row">Địa chỉ</th><td><?= e($deleting['diaChi'] ?? 'Chưa cập nhật') ?></td></tr>
        <tr><th scope="row">Trạng thái</th><td><?= $deleting['trangThai']==='HOAT_DONG'?'Hoạt động':'Ngừng hoạt động' ?></td></tr>
    </tbody></table></div>
    <?php if ($deleting['trangThai']==='HOAT_DONG'): ?>
    <p class="cn-deactivate-description">Chi nhánh sẽ chuyển sang <strong>Ngừng hoạt động</strong>. Thông tin chi nhánh và dữ liệu lịch sử vẫn được giữ để tra cứu.</p>
    <p class="muted cn-paragraph-bottom">Chỉ thực hiện được khi không còn nhân viên đang làm, đơn hàng chưa hoàn tất hoặc lịch đặt còn hiệu lực.</p>
    <form method="post" action="<?= e(page_url('branches').'&action=delete&id='.rawurlencode($id)) ?>">
        <?= csrf_input() ?><input type="hidden" name="action" value="deactivate">
        <div class="management-actions"><button type="submit" class="btn btn-danger" name="confirm" value="1">Xác nhận ngừng hoạt động</button><a class="btn btn-secondary" href="<?= e(page_url('branches')) ?>">Về danh sách</a></div>
    </form>
    <?php else: ?>
    <p class="cn-deactivate-status" role="status">Chi nhánh này đã ngừng hoạt động.</p><a class="btn btn-secondary" href="<?= e(page_url('branches')) ?>">Về danh sách</a>
    <?php endif; ?>
</section></div>
<?php elseif ($branchAction==='purge'): ?>
<div class="screen-content chi-nhanh-module"><section class="card account-card" aria-labelledby="purge-title">
<h2 class="title title-md" id="purge-title">Xác nhận xóa vĩnh viễn chi nhánh</h2>
<p class="cn-branch-caption"><strong><?= e($target['tenChiNhanh']) ?></strong> · <?= e($id) ?> · Ngừng hoạt động</p>
<p class="cn-purge-warning">Thao tác này sẽ xóa chi nhánh cùng các dữ liệu riêng được liệt kê bên dưới khỏi database. <strong>Không thể hoàn tác trên hệ thống.</strong> Các danh mục dùng chung như món ăn, nguyên liệu và nhà cung cấp được giữ lại.</p>
<?php if ($error): ?><p class="account-message error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php foreach ($blockers as $blocker): ?><p class="account-message error" role="alert"><?= e($blocker) ?></p><?php endforeach; ?>
<div class="table-scroll"><table class="data-table"><thead><tr><th scope="col">Dữ liệu sẽ xóa</th><th scope="col">Số bản ghi</th></tr></thead><tbody>
<?php foreach ($summary as $row): ?><?php if ($row['count']>0): ?><tr><td><?= e($row['label']) ?></td><td><?= $row['count'] ?></td></tr><?php endif; ?><?php endforeach; ?>
</tbody></table></div>
<form class="cn-space-top" method="post" action="<?= e(page_url('branches').'&action=purge&id='.rawurlencode($id)) ?>">
<?= csrf_input() ?><input type="hidden" name="action" value="purge"><input type="hidden" name="operation" value="<?= e($operation) ?>">
<div class="management-actions"><?php if (!$blockers): ?><button type="submit" class="btn btn-danger" name="confirm" value="1">Xác nhận xóa vĩnh viễn toàn bộ dữ liệu chi nhánh</button><?php endif; ?><a class="btn btn-secondary" href="<?= e(page_url('branches').'&id='.rawurlencode($id).'#branch-detail') ?>">Quay lại chi tiết</a></div>
</form>
</section></div>
<?php endif; ?>
