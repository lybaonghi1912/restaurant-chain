<?php
declare(strict_types=1);
if(!defined('APP_ROOT')){http_response_code(404);exit;}

function customer_icon(string $name):string
{
    $paths=[
        'arrow'=>'<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'phone'=>'<path d="M5 3h4l2 5-3 2a15 15 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2C10 21 3 14 3 5a2 2 0 0 1 2-2Z"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4m8-4v4M3 11h18"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'chevron'=>'<path d="m9 5 7 7-7 7"/>',
        'copy'=>'<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M15 8V3H3v12h5"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['arrow']).'</svg>';
}
function customer_header(string $active):void
{
    $staff=current_user();$staffUrl=$staff?page_url(home_page()):($active==='home'?'#staff-login':url('login.php#staff-login'));
    ?>
    <header class="guest-header">
    <div class="guest-header-inner">
      <a class="guest-brand" href="<?= e(url('login.php')) ?>" aria-label="F&B Nodes trang chủ"><span class="guest-brand-mark">N</span><span>F&amp;B <strong>Nodes</strong></span></a>
      <nav class="guest-nav" aria-label="Điều hướng khách hàng">
        <a href="<?= e(url('login.php')) ?>" <?= $active==='home'?'aria-current="page"':'' ?>>Trang chủ</a>
        <a href="<?= e(page_url('booking')) ?>" <?= $active==='booking'?'aria-current="page"':'' ?>>Đặt bàn</a>
        <a href="<?= e(page_url('tracking')) ?>" <?= $active==='tracking'?'aria-current="page"':'' ?>>Theo dõi đặt bàn</a>
        <a class="guest-staff-link" href="<?= e($staffUrl) ?>" <?= $active==='home'&&!$staff?'data-open-staff':'' ?>><?= $staff?'Trang nội bộ':'Đăng nhập' ?></a>
      </nav>
    </div>
    </header>
    <?php
}
function customer_footer():void
{
    ?>
    <footer class="guest-footer"><div class="guest-footer-inner"><a class="guest-footer-brand" href="<?= e(url('login.php')) ?>">F&amp;B Nodes</a><p>Đón khách hằng ngày từ 08:00 đến trước 22:00.</p><nav aria-label="Liên kết cuối trang"><a href="<?= e(page_url('booking')) ?>">Đặt bàn</a><a href="<?= e(page_url('tracking')) ?>">Theo dõi đặt bàn</a></nav></div></footer>
    <?php
}
function customer_field_error(string $key,array $fields):void
{
    if(isset($fields[$key]))echo '<span class="guest-field-error" id="error-'.e($key).'">'.e((string)$fields[$key]).'</span>';
}
function customer_invalid(string $key,array $fields):string
{
    return isset($fields[$key])?' aria-invalid="true" aria-describedby="error-'.e($key).'"':'';
}
function customer_tracking_status(array $row):array
{
    $status=$row['trangThai'];
    if($status==='DA_XAC_NHAN' && $row['thoiGianKhachDen'])return ['confirmed','Đã xác nhận','Nhà hàng đã ghi nhận khách có mặt, đang chờ bố trí bàn.'];
    return match($status){
        'CHO_XAC_NHAN'=>['pending','Chờ xác nhận','Nhân viên sẽ gọi điện để xác nhận hoặc thông báo hủy yêu cầu. Bàn chỉ được giữ sau khi xác nhận.'],
        'DA_XAC_NHAN'=>['confirmed','Đã xác nhận','Bàn đã được xác nhận. Nhân viên liên hệ qua điện thoại để xác nhận thông tin đặt bàn.'],
        'DANG_PHUC_VU'=>['serving','Đang phục vụ','Khách đã được tiếp nhận vào bàn.'],
        'DA_HOAN_TAT'=>['completed','Đã hoàn tất','Lượt phục vụ đã hoàn tất. Cảm ơn quý khách.'],
        'DA_HUY'=>['cancelled','Đã hủy','Yêu cầu này không còn hiệu lực. Khi hủy đặt bàn, nhân viên gọi điện để thông báo và xác nhận với khách.'],
        default=>['pending','Đang cập nhật','Vui lòng tra cứu lại để xem trạng thái mới nhất.'],
    };
}
