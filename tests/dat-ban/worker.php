<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__,2).'/modules/dat-ban/DatBanControl.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
$name=$argv[1]??'';if(!preg_match('/^fbn_reservation_test_[a-f0-9]{10}$/D',$name))exit(2);
$pdo=new PDO('mysql:host='.(getenv('RESTAURANT_SETUP_HOST')?:'127.0.0.1').';port='.(getenv('RESTAURANT_SETUP_PORT')?:3306).';dbname='.$name.';charset=utf8mb4',getenv('RESTAURANT_SETUP_USER')?:'root',getenv('RESTAURANT_SETUP_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
$s=new \Fbn\Reservations\DatBanControl($pdo,fn()=>new DateTimeImmutable($argv[4]));$actor=['maNhanVien'=>'TSWAIT1','maVaiTro'=>'PHUC_VU','maChiNhanh'=>'CN01'];
try{if($argv[2]==='confirm')$s->xacNhanDatBan($actor,$argv[3],'C1-A01');else $s->taoDonTaiQuay($actor,['maBan'=>'C1-A01','soKhach'=>'4']);echo json_encode(['code'=>'OK']);}catch(\Fbn\Reservations\BookingError $e){echo json_encode(['code'=>$e->errorCode]);}catch(Throwable $e){echo json_encode(['code'=>'SYSTEM_ERROR','message'=>$e->getMessage()]);}
