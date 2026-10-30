<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }
require_once __DIR__.'/giao-dien.php';
require_once __DIR__.'/data.php';branch_purge_require_owner();
$id=$_GET['id'] ?? null;
if (!is_string($id) || trim($id)==='' || strlen($id)>10) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Mã chi nhánh không hợp lệ.</p></div>';return; }
$target=chi_nhanh_chi_tiet($id);
if (!$target) { http_response_code(404);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Không tìm thấy chi nhánh.</p></div>';return; }
if ($target['trangThai']!=='NGUNG') { http_response_code(422);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Chỉ được xóa vĩnh viễn chi nhánh đã ngừng hoạt động.</p><a class="btn btn-secondary" href="'.e(page_url('branches')).'">Về danh sách</a></div>';return; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();$operation=post_text('operation');$confirmation=$_SESSION['purge_forms'][$operation] ?? null;
    if (post_text('action')!=='purge' || post_text('confirm')!=='1' || !is_array($confirmation) || ($confirmation['id'] ?? '')!==$id) { http_response_code(400);echo '<div class="screen-content chi-nhanh-module"><p role="alert">Xác nhận không hợp lệ. Vui lòng mở lại thông tin chi nhánh và xác nhận xóa.</p></div>';return; }
    try {
        branch_purge($id,$confirmation['fingerprint']);unset($_SESSION['purge_forms'][$operation]);
        if (($_SESSION['branch'] ?? null)===$id) { unset($_SESSION['branch']); }
        $_SESSION['branch_notice']='Đã xóa vĩnh viễn chi nhánh '.$target['tenChiNhanh'].' và dữ liệu riêng của chi nhánh.';
        redirect_to('index.php?page=branches');
    } catch (DomainException $e) { $error=$e->getMessage();http_response_code(422); }
    catch (PDOException $e) { error_log('Branch purge: '.$e->getMessage());$error='Chưa xóa được dữ liệu. Toàn bộ thao tác đã được hoàn tác; vui lòng kiểm tra liên kết dữ liệu hoặc thử lại.';http_response_code(503); }
}
$summary=branch_purge_summary($id);$blockers=branch_purge_blockers($id);
$operation=bin2hex(random_bytes(16));$_SESSION['purge_forms'][$operation]=['id'=>$id,'fingerprint'=>branch_purge_fingerprint($summary)];
while (count($_SESSION['purge_forms'])>10) { array_shift($_SESSION['purge_forms']); }
?>
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
