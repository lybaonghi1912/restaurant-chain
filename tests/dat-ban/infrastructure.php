<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(2);
$root=dirname(__DIR__,2);
require $root.'/database/migrate.php';
require $root.'/database/install_reservation_timer.php';
$db=new PDO('mysql:host=127.0.0.1;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$name='fbn_reservation_test_'.bin2hex(random_bytes(5));$results=[];
try{
 $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$db->exec("USE `$name`");$db->exec("SET time_zone='+07:00'");
 foreach(['001_auth','002_scope_sequences']as$v)foreach(schema_statements(file_get_contents($root.'/database/migrations/'.$v.'.sql'))as$s)execute_schema_statement($db,$s);
 foreach(schema_statements(file_get_contents($root.'/database/seed.sql'))as$s)$db->exec($s);
 $db->exec("INSERT INTO DatBan(maDatBan,maChiNhanh,maBan,nguon,soKhach,thoiGianDen,thoiGianNhanBan,thoiGianKetThucDuKien,trangThai) VALUES ('TSLEGACY','CN01','C1-A01','TAI_QUAY',4,'2026-10-01 18:00:00','2026-10-01 18:02:00','2026-10-01 20:00:00','DANG_PHUC_VU')");
 foreach(schema_statements(file_get_contents($root.'/database/migrations/003_reservation_arrival_window.sql'))as$s)execute_schema_statement($db,$s);
 foreach(schema_statements(file_get_contents($root.'/database/migrations/004_booking_opening_hours.sql'))as$s)execute_schema_statement($db,$s);
 $r=$db->query("SELECT * FROM DatBan WHERE maDatBan='TSLEGACY'")->fetch();
 $ok=$r['thoiGianKhachDen']==='2026-10-01 18:02:00'&&$r['trangThai']==='DANG_PHUC_VU';
 $results[]=['id'=>'SYS01','function'=>'Cập nhật CSDL','title'=>'Nâng cấp dữ liệu đang phục vụ từ bản cũ','input'=>'Lượt TAI_QUAY 01/10/2026 18:00; nhận bàn 18:02; DANG_PHUC_VU; migration 003','expected'=>'Giữ nguyên lượt; bổ sung giờ khách đến 18:02; ràng buộc mới hợp lệ','status'=>$ok?'PASS':'FAIL'];
 $db->exec("INSERT INTO DatBan(maDatBan,maChiNhanh,maBan,tenKhach,soDienThoai,soKhach,thoiGianDen,trangThai) VALUES ('TSTIMER','CN01','C1-A02','Khách thử timer','0912345678',2,DATE_SUB(NOW(), INTERVAL 16 MINUTE),'DA_XAC_NHAN')");
 install_reservation_timer($db);$start=microtime(true);$ok=false;
 do{$state=$db->query("SELECT trangThai FROM DatBan WHERE maDatBan='TSTIMER'")->fetchColumn();if($state==='DA_HUY'){$ok=true;break;}sleep(2);}while(microtime(true)-$start<70);
 $still=$db->query("SELECT trangThai FROM DatBan WHERE maDatBan='TSLEGACY'")->fetchColumn();$ok=$ok&&$still==='DANG_PHUC_VU';
 $results[]=['id'=>'SYS02','function'=>'Theo dõi trạng thái','title'=>'MySQL tự hủy khi không có lượt mở trang','input'=>'TSTIMER đã xác nhận, chưa đến; giờ đặt = NOW VN trừ 16 phút; Event mỗi phút; chỉ đọc SQL, không gọi Control','expected'=>'Trong một chu kỳ Event, TSTIMER DA_HUY; TSLEGACY vẫn DANG_PHUC_VU','status'=>$ok?'PASS':'FAIL','elapsed_seconds'=>round(microtime(true)-$start,1)];
}finally{$db->exec("DROP DATABASE IF EXISTS `$name`");}
$out=['executed_at'=>date(DATE_ATOM),'isolated_database'=>true,'total'=>count($results),'passed'=>count(array_filter($results,fn($r)=>$r['status']==='PASS')),'cases'=>$results];
file_put_contents((getenv('RESERVATION_INFRA_RESULTS')?:__DIR__.'/infrastructure-results.json'),json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo json_encode($out,JSON_UNESCAPED_UNICODE);exit($out['passed']===$out['total']?0:1);
