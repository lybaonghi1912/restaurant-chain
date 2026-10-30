<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_once __DIR__.'/giao-dien.php';
require_permission('branches.manage');
$id=$_GET['id'] ?? null;
if (!is_string($id) || trim($id)==='' || strlen($id)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã chi nhánh không hợp lệ.</p></div>';return; }
$deleting=chi_nhanh_chi_tiet($id);
if (!$deleting) { http_response_code(404);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Không tìm thấy chi nhánh.</p></div>';return; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    if (post_text('action')!=='deactivate' || post_text('confirm')!=='1') { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Vui lòng xác nhận ngừng hoạt động chi nhánh.</p></div>';return; }
    try {
        $changed=chi_nhanh_ngung_hoat_dong($id);
        $_SESSION['branch_notice']=$changed?'Chi nhánh đã ngừng hoạt động.':'Chi nhánh này đã ngừng hoạt động trước đó.';
        if (($_SESSION['branch'] ?? null)===$id) { unset($_SESSION['branch']); }
        redirect_to('index.php?page=branches');
    } catch (DomainException $e) { $error=$e->getMessage();http_response_code(422); }
    catch (PDOException $e) { error_log('SD-16: '.$e->getMessage());$error='Chưa thể ngừng hoạt động chi nhánh. Vui lòng thử lại.';http_response_code(503); }
}
?>
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
