<section class="card module-placeholder">
    <span class="eyebrow accent">Module <?= e($page['module']) ?></span>
    <h2 class="title"><?= e($page['title']) ?></h2>
    <p>Khung trang đã sẵn sàng. Nội dung chức năng sẽ được bổ sung tại module tương ứng.</p>
    <dl class="module-details">
        <div><dt>Thư mục</dt><dd><code>modules/<?= e($page['module']) ?>/</code></dd></div>
        <div><dt>Phụ trách</dt><dd><?= e($page['owner'] ?: 'Nhóm cần chốt phân công') ?></dd></div>
        <div><dt>Trạng thái</dt><dd>Chưa triển khai nghiệp vụ</dd></div>
    </dl>
    <a class="btn btn-secondary" href="<?= e(page_url('floor')) ?>">Xem trang sơ đồ bàn mẫu</a>
</section>
