<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(2);
$name=$argv[2]??'';if(!preg_match('/^fbn_booking_http_[a-f0-9]{10}$/D',$name))exit(2);
$root=dirname(__DIR__,3);
$pdo=new PDO('mysql:host='.(getenv('RESTAURANT_SETUP_HOST')?:'127.0.0.1').';port='.(getenv('RESTAURANT_SETUP_PORT')?:3306).';charset=utf8mb4',getenv('RESTAURANT_SETUP_USER')?:'root',getenv('RESTAURANT_SETUP_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($argv[1]==='cleanup'){$pdo->exec("DROP DATABASE IF EXISTS `$name`");exit;}
if($argv[1]==='inspect'){$pdo->exec("USE `$name`");echo json_encode(['bookings'=>$pdo->query('SELECT * FROM DatBan ORDER BY ngayTao,maDatBan')->fetchAll(PDO::FETCH_ASSOC),'tables'=>$pdo->query('SELECT maBan,trangThai FROM Ban')->fetchAll(PDO::FETCH_ASSOC)]);exit;}
require $root.'/database/migrate.php';
$pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$pdo->exec("USE `$name`");
run_schema_migrations($pdo);foreach(schema_statements(file_get_contents($root.'/database/seed.sql'))as $sql)$pdo->exec($sql);
foreach([['phuc_vu','HTWAIT1','PHUC_VU','CN01'],['phuc_vu2','HTWAIT2','PHUC_VU','CN02'],['chu_chuoi','HTOWNER','CHU_CHUOI',null],['bep','HTBEP','BEP','CN01'],['quan_ly','HTMGR','QUAN_LY','CN01']]as [$user,$id,$role,$branch]){
    $pdo->prepare('INSERT INTO NhanVien(maNhanVien,hoTen,maVaiTro,maChiNhanh) VALUES (?,?,?,?)')->execute([$id,'Nhân viên kiểm thử '.$user,$role,$branch]);
    $pdo->prepare('INSERT INTO TaiKhoan(tenDangNhap,matKhau,maNhanVien) VALUES (?,?,?)')->execute([$user,password_hash('BookingTest@2026!',PASSWORD_ARGON2ID),$id]);
}
$folder=$argv[3];$config=['host'=>getenv('RESTAURANT_SETUP_HOST')?:'127.0.0.1','port'=>(int)(getenv('RESTAURANT_SETUP_PORT')?:3306),'database'=>$name,'username'=>getenv('RESTAURANT_SETUP_USER')?:'root','password'=>getenv('RESTAURANT_SETUP_PASSWORD')?:''];
file_put_contents($folder.'/config/database.php',"<?php return ".var_export($config,true).';');
echo "HTTP fixture database ready.\n";
