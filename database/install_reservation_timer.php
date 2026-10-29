<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
function install_reservation_timer(PDO $pdo):void
{
    $database=$pdo->query('SELECT DATABASE()')->fetchColumn();
    if(!is_string($database)||!preg_match('/^[A-Za-z0-9_]+$/D',$database))throw new RuntimeException('Database không hợp lệ.');
    $mode=(string)$pdo->query('SELECT @@global.event_scheduler')->fetchColumn();
    if(strtoupper($mode)==='DISABLED')throw new RuntimeException('MySQL event_scheduler bị DISABLED; cần bật trong cấu hình MySQL rồi khởi động lại.');
    if(strtoupper($mode)!=='ON')$pdo->exec('SET GLOBAL event_scheduler=ON');
    $pdo->exec("CREATE EVENT IF NOT EXISTS `$database`.ev_reservation_no_show ON SCHEDULE EVERY 1 MINUTE ENABLE DO UPDATE `$database`.DatBan SET trangThai='DA_HUY',lyDoHuy='Tự động hủy: khách không đến trong 15 phút sau giờ đặt',maNguoiHuy=NULL WHERE trangThai='DA_XAC_NHAN' AND thoiGianKhachDen IS NULL AND hanGiuCho<DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 HOUR)");
    echo "Reservation timer enabled: every minute; page reads also reconcile expiry immediately.\n";
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
    $host=getenv('RESTAURANT_SETUP_HOST')?:'127.0.0.1';$port=getenv('RESTAURANT_SETUP_PORT')?:3306;
    $pdo=new PDO("mysql:host=$host;port=$port;dbname=restaurant_chain_scope_v2;charset=utf8mb4",getenv('RESTAURANT_SETUP_USER')?:'root',getenv('RESTAURANT_SETUP_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    install_reservation_timer($pdo);
}
