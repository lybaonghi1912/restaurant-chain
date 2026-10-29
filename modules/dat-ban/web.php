<?php
declare(strict_types=1);
if(!defined('APP_ENTRY')){http_response_code(404);exit;}
require_once __DIR__.'/DatBanControl.php';

use Fbn\Reservations\DatBanControl;
use Fbn\Reservations\BookingError;

$reservations=new DatBanControl(db());$reservations->tuHuyQuaHan();
$reservationError=null;$reservationFields=[];
$reservationFlash=$_SESSION['reservation_flash']??null;unset($_SESSION['reservation_flash']);
function reservation_value(string $key,string $default=''): string {return e(is_string($_POST[$key]??null)?$_POST[$key]:$default);}
function reservation_nonce(string $action): string
{
    $_SESSION['reservation_nonce'][$action]??=bin2hex(random_bytes(24));
    return '<input type="hidden" name="request_key" value="'.e($_SESSION['reservation_nonce'][$action]).'">';
}
function reservation_status(string $status): string {return DatBanControl::STATES[$status]??$status;}
function reservation_time(?string $time): string {return $time?date('d/m/Y H:i',strtotime($time)):'Chưa xác định';}
function reservation_notice(?string $error,?array $flash,array $fields=[]): void
{
    if($error){echo '<div class="res-notice error" role="alert"><strong>'.e($error).'</strong>';foreach($fields as $label)echo '<div>'.e($label).'</div>';echo '</div>';}
    if($flash)echo '<div class="res-notice success" role="status">'.e($flash['message']).'</div>';
}
function reservation_url(array $extra=[]): string {return url('index.php?'.http_build_query(['page'=>'floor']+$extra));}

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $action=post_text('action');
    try{
        if(!in_array($action,['web_create','track','counter_create','confirm','cancel','arrive'],true))throw new BookingError('VALIDATION_ERROR','Thao tác không hợp lệ.');
        if(($pageKey==='booking' && $action!=='web_create')||($pageKey==='tracking' && $action!=='track')||($pageKey==='floor' && !in_array($action,['counter_create','confirm','cancel','arrive'],true)))throw new BookingError('VALIDATION_ERROR','Thao tác không thuộc trang này.');
        if(in_array($action,['web_create','counter_create'],true)){
            $key=post_text('request_key');
            if(empty($_SESSION['reservation_nonce'][$action]) || !hash_equals($_SESSION['reservation_nonce'][$action],$key))throw new BookingError('CONFLICT','Biểu mẫu đã gửi hoặc hết hiệu lực. Tải lại trang trước khi tạo yêu cầu mới.',409);
        }
        if(in_array($action,['counter_create','confirm','cancel','arrive'],true))require_permission('reservation.manage');
        if($action==='web_create'){
            $d=$reservations->taoDonDatBan($_POST);unset($_SESSION['reservation_nonce'][$action]);
            $_SESSION['reservation_flash']=['message'=>'Yêu cầu đã được gửi, vui lòng chờ xác nhận. Mã đặt bàn: '.$d['maDatBan'].'. Gửi yêu cầu chưa giữ bàn.'];
            redirect_to('index.php?page=booking');
        }
        if($action==='counter_create'){
            $d=$reservations->taoDonTaiQuay(current_user(),$_POST);unset($_SESSION['reservation_nonce'][$action]);
            $_SESSION['reservation_flash']=['message'=>'Tạo đặt bàn tại quầy thành công. Mã: '.$d['maDatBan']];
            redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
        }
        if($action==='confirm'){
            $d=$reservations->xacNhanDatBan(current_user(),post_text('maDatBan'),post_text('maBan'));
            $_SESSION['reservation_flash']=['message'=>'Xác nhận đặt bàn thành công.'];
            redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
        }
        if($action==='arrive'){
            $d=$reservations->ghiNhanKhachDen(current_user(),post_text('maDatBan'));
            $_SESSION['reservation_flash']=['message'=>!empty($d['expired'])?'Yêu cầu đã quá hạn giữ chỗ và được tự hủy.':(!empty($d['waiting'])?'Đã ghi nhận khách đến đúng hạn. Bàn đang bận; chờ bố trí bàn.':'Đã ghi nhận khách đến và tiếp nhận vào bàn.')];
            redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
        }
        if($action==='cancel'){
            $d=$reservations->huyDatBan(current_user(),post_text('maDatBan'),post_text('lyDoHuy'));
            $_SESSION['reservation_flash']=['message'=>'Đã hủy yêu cầu đặt bàn. Lịch của yêu cầu đã được giải phóng.'];
            redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
        }
    }catch(BookingError $error){http_response_code($error->status);$reservationError=$error->getMessage();$reservationFields=$error->fields;}
}
