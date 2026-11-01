<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Dùng tài khoản quản trị MySQL chỉ khi khởi tạo. Website dùng tài khoản riêng chỉ có quyền dữ liệu.
$host=getenv('RESTAURANT_SETUP_HOST') ?: '127.0.0.1';
$port=(int)(getenv('RESTAURANT_SETUP_PORT') ?: 3306);
$adminUser=getenv('RESTAURANT_SETUP_USER') ?: 'root';
$adminPassword=getenv('RESTAURANT_SETUP_PASSWORD') ?: '';
$configPath=dirname(__DIR__).'/config/database.php';
if (is_file($configPath)) {
    $existing=require $configPath;
    if (($existing['database'] ?? '')!=='restaurant_chain_scope_v2') { throw new RuntimeException('Config dang tro database khac; dung quy trinh chuyen doi, khong tu ghi de.'); }
}

$pdo=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",$adminUser,$adminPassword,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS restaurant_chain_scope_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE restaurant_chain_scope_v2');
$pdo->exec("SET time_zone = '+07:00'");
require_once __DIR__.'/migrate.php';
run_schema_migrations($pdo);
$pdo->exec(file_get_contents(__DIR__.'/seed.sql'));

if (!is_file($configPath)) {
    $appUser='restaurant_chain_scope_v2_app';
    $exists=$pdo->prepare("SELECT COUNT(*) FROM mysql.user WHERE User=? AND Host='localhost'");
    $exists->execute([$appUser]);
    if ((int)$exists->fetchColumn()>0) { $appUser='fbn_'.bin2hex(random_bytes(6)); }
    $appPassword=bin2hex(random_bytes(24));
    $account=$pdo->quote($appUser)."@'localhost'";
    $pdo->exec('CREATE USER '.$account.' IDENTIFIED BY '.$pdo->quote($appPassword));
    $pdo->exec('GRANT SELECT,INSERT,UPDATE,DELETE ON restaurant_chain_scope_v2.* TO '.$account);
    $config=['host'=>$host,'port'=>$port,'database'=>'restaurant_chain_scope_v2','username'=>$appUser,'password'=>$appPassword];
    file_put_contents($configPath,"<?php\ndeclare(strict_types=1);\n// Cấu hình riêng máy này. Không commit vào Git.\nreturn ".var_export($config,true).";\n");
}

$password=getenv('RESTAURANT_DEMO_PASSWORD');
if (!is_string($password) || strlen($password)<6) {
    fwrite(STDOUT,"Nhap mat khau khoi tao tai khoan demo (toi thieu 6 ky tu; demo nhom: 123456): ");
    $password=trim((string)fgets(STDIN));
    if (strlen($password)<6) { throw new RuntimeException('Mat khau qua ngan.'); }
}
$samples=[
    ['chu_chuoi','NV0001','Chủ chuỗi F&B Nodes','CHU_CHUOI',null],
    ['quan_ly','NV0002','Quản lý Thảo Điền','QUAN_LY','CN01'],
    ['phuc_vu','NV0003','Phục vụ Thảo Điền','PHUC_VU','CN01'],
    ['bep','NV0004','Bếp Thảo Điền','BEP','CN01'],
    ['kho','NV0005','Kho Thảo Điền','KHO','CN01'],
];
$pdo->beginTransaction();
try {
    foreach ($samples as [$username,$id,$name,$role,$branch]) {
        $check=$pdo->prepare('SELECT COUNT(*) FROM TaiKhoan WHERE tenDangNhap=?');$check->execute([$username]);
        if ((int)$check->fetchColumn()>0) { echo "KEEP $username (giu mat khau cu)\n";continue; }
        $pdo->prepare('INSERT INTO NhanVien(maNhanVien,hoTen,maVaiTro,maChiNhanh) VALUES (?,?,?,?)')->execute([$id,$name,$role,$branch]);
        $pdo->prepare('INSERT INTO TaiKhoan(tenDangNhap,matKhau,maNhanVien) VALUES (?,?,?)')->execute([$username,password_hash($password,PASSWORD_ARGON2ID),$id]);
        echo "CREATED $username\n";
    }
    $pdo->commit();
} catch (Throwable $error) { $pdo->rollBack();throw $error; }
echo "Database restaurant_chain_scope_v2 scope V2 (32 sequences) ready. Existing passwords were kept.\n";

require_once __DIR__.'/install_reservation_timer.php';
install_reservation_timer($pdo);
