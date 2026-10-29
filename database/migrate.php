<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function schema_statements(string $sql): array
{
    // Các migration của dự án chỉ dùng DDL/DML thường; không dùng DELIMITER hoặc procedure.
    $sql=preg_replace('/^\s*--[^\r\n]*(?:\r?\n|$)/m','',$sql);
    return array_values(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',trim($sql)))));
}

function alter_parts(string $body): array
{
    $parts=[];$start=0;$depth=0;$quote=null;$length=strlen($body);
    for ($i=0;$i<$length;$i++) {
        $char=$body[$i];
        if ($quote!==null) {
            if ($char==='\\') { $i++;continue; }
            if ($char===$quote) { if ($i+1<$length && $body[$i+1]===$quote) { $i++; } else { $quote=null; } }
            continue;
        }
        if ($char==="'" || $char==='"' || $char==='`') { $quote=$char;continue; }
        if ($char==='(') { $depth++; }
        elseif ($char===')') { $depth--; }
        elseif ($char===',' && $depth===0) { $parts[]=trim(substr($body,$start,$i-$start));$start=$i+1; }
    }
    $parts[]=trim(substr($body,$start));return $parts;
}

function execute_schema_statement(PDO $pdo,string $sql): void
{
    if (!preg_match('/^ALTER TABLE ([A-Za-z0-9_]+)\s+(.+)$/s',$sql,$match)) { $pdo->exec($sql);return; }
    $table=$match[1];
    // ADD được kiểm tra từng thành phần, để có thể chạy tiếp sau DDL bị gián đoạn.
    foreach (alter_parts($match[2]) as $part) {
        if (preg_match('/^DROP CHECK ([A-Za-z0-9_]+)/',$part,$item)) {
            $exists=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?');
            $exists->execute([$table,$item[1]]);
            if ((int)$exists->fetchColumn()) { $pdo->exec('ALTER TABLE '.$table.' '.$part); }
            continue;
        }
        if (preg_match('/^ADD COLUMN ([A-Za-z0-9_]+)/',$part,$item)) {
            $check=$pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        } elseif (preg_match('/^ADD CONSTRAINT ([A-Za-z0-9_]+)/',$part,$item)) {
            $check=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?');
        } else { throw new RuntimeException('ALTER không được hỗ trợ: '.$part); }
        $check->execute([$table,$item[1]]);
        if (!(int)$check->fetchColumn()) { $pdo->exec('ALTER TABLE '.$table.' '.$part); }
    }
}

function run_schema_migrations(PDO $pdo): void
{
    $database=$pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!$database) { throw new RuntimeException('Chưa chọn database.'); }
    $lock=$pdo->prepare('SELECT GET_LOCK(?,10)');$lockName='fbn_migrate_'.$database;$lock->execute([$lockName]);
    if ((int)$lock->fetchColumn()!==1) { throw new RuntimeException('Có tiến trình đang cập nhật schema.'); }
    try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS SchemaMigration (
            phienBan VARCHAR(64) PRIMARY KEY,checksum CHAR(64) NOT NULL,
            apDungLuc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        foreach (glob(__DIR__.'/migrations/*.sql') as $file) {
            $version=basename($file,'.sql');$checksum=hash_file('sha256',$file);
            $check=$pdo->prepare('SELECT checksum FROM SchemaMigration WHERE phienBan=?');$check->execute([$version]);$old=$check->fetchColumn();
            if ($old!==false) {
                if (!hash_equals($old,$checksum)) { throw new RuntimeException('Migration đã áp dụng bị sửa: '.$version.'. Hãy tạo migration mới.'); }
                echo 'SKIP '.$version."\n";continue;
            }
            foreach (schema_statements(file_get_contents($file)) as $statement) { execute_schema_statement($pdo,$statement); }
            $pdo->prepare('INSERT INTO SchemaMigration(phienBan,checksum) VALUES (?,?)')->execute([$version,$checksum]);
            echo 'APPLIED '.$version."\n";
        }
    } finally { $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]); }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '')===__FILE__) {
    $host=getenv('RESTAURANT_SETUP_HOST') ?: '127.0.0.1';$port=(int)(getenv('RESTAURANT_SETUP_PORT') ?: 3306);
    $pdo=new PDO("mysql:host=$host;port=$port;dbname=restaurant_chain_scope_v2;charset=utf8mb4",
        getenv('RESTAURANT_SETUP_USER') ?: 'root',getenv('RESTAURANT_SETUP_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    run_schema_migrations($pdo);
    require_once __DIR__.'/install_reservation_timer.php';
    install_reservation_timer($pdo);
}
