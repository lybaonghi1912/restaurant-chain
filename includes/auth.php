<?php
declare(strict_types=1);
require_once APP_ROOT . '/includes/database.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('fbn_session');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => BASE_URL . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Phiên biểu mẫu không hợp lệ. Vui lòng tải lại trang rồi thử lại.');
    }
}

function post_text(string $key): string
{
    return is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
}

function redirect_to(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function find_user(string $username): ?array
{
    $statement = db()->prepare('SELECT t.tenDangNhap,t.matKhau,t.trangThai AS trangThaiTaiKhoan,t.phienBan,
        n.maNhanVien,n.hoTen,n.maVaiTro,n.maChiNhanh,n.trangThai AS trangThaiNhanVien,
        v.tenVaiTro,c.tenChiNhanh,c.trangThai AS trangThaiChiNhanh
        FROM TaiKhoan t JOIN NhanVien n ON n.maNhanVien=t.maNhanVien
        JOIN VaiTro v ON v.maVaiTro=n.maVaiTro LEFT JOIN ChiNhanh c ON c.maChiNhanh=n.maChiNhanh
        WHERE t.tenDangNhap=? LIMIT 1');
    $statement->execute([$username]);
    return $statement->fetch() ?: null;
}

function user_is_active(array $user): bool
{
    return $user['trangThaiTaiKhoan'] === 'HOAT_DONG' && $user['trangThaiNhanVien'] === 'DANG_LAM'
        && ($user['maVaiTro'] === 'CHU_CHUOI' || $user['trangThaiChiNhanh'] === 'HOAT_DONG');
}

function current_user(): ?array
{
    static $loaded = false, $user = null;
    if ($loaded) { return $user; }
    $loaded = true;
    if (!isset($_SESSION['username'], $_SESSION['auth_version'], $_SESSION['last_activity'], $_SESSION['signed_in'])) { return null; }
    if (time() - $_SESSION['last_activity'] > 1800 || time() - $_SESSION['signed_in'] > 28800) {
        unset($_SESSION['username'], $_SESSION['auth_version'], $_SESSION['branch']);
        return null;
    }
    $candidate = find_user($_SESSION['username']);
    if (!$candidate || !user_is_active($candidate) || (int)$candidate['phienBan'] !== (int)$_SESSION['auth_version']) {
        unset($_SESSION['username'], $_SESSION['auth_version'], $_SESSION['branch']);
        return null;
    }
    $_SESSION['last_activity'] = time();
    unset($candidate['matKhau']);
    $user = $candidate;
    return $user;
}

function can(string $permission): bool
{
    $user = current_user();
    if (!$user) { return false; }
    static $permissions = null;
    $permissions ??= require APP_ROOT . '/config/permissions.php';
    return in_array($permission, $permissions[$user['maVaiTro']] ?? [], true);
}

function page_permission(string $key): ?string
{
    return [
        'booking'=>null,'tracking'=>null,'password'=>'authenticated',
        'floor'=>'floor.view','pos'=>'pos.view','checkout'=>'checkout.view','kds'=>'kds.view',
        'menu'=>'menu.view','inventory'=>'inventory.view','transfer'=>'transfer.view','staff'=>'staff.view',
        'payroll'=>'payroll.view','bom'=>'bom.view','branches'=>'branches.view','executive'=>'reports.view',
        'accounts'=>'accounts.view',
    ][$key] ?? (in_array($key, ['booking','tracking'], true) ? null : 'DENY');
}

function can_page(string $key): bool
{
    $permission = page_permission($key);
    return $permission === null || ($permission === 'authenticated' ? current_user() !== null : can($permission));
}

function require_login(string $next = ''): array
{
    $user = current_user();
    if (!$user) { redirect_to('login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : '')); }
    return $user;
}

function require_permission(string $permission): void
{
    require_login();
    if (!can($permission)) {
        http_response_code(403);
        exit('Bạn không có quyền thực hiện thao tác này.');
    }
}

function home_page(): string
{
    return match (current_user()['maVaiTro'] ?? '') {
        'CHU_CHUOI','QUAN_LY' => 'executive', 'BEP' => 'kds', 'KHO' => 'inventory', default => 'floor',
    };
}

function active_branches(): array
{
    return db()->query("SELECT maChiNhanh,tenChiNhanh FROM ChiNhanh WHERE trangThai='HOAT_DONG' ORDER BY maChiNhanh")->fetchAll();
}

function current_branch_id(): ?string
{
    $user = current_user();
    if (!$user) { return null; }
    return $user['maVaiTro'] === 'CHU_CHUOI' ? ($_SESSION['branch'] ?? null) : $user['maChiNhanh'];
}

function require_branch_access(string $branchId): void
{
    $user = require_login();
    if ($user['maVaiTro'] !== 'CHU_CHUOI' && $user['maChiNhanh'] !== $branchId) {
        http_response_code(403); exit('Bạn không được truy cập dữ liệu chi nhánh khác.');
    }
}

// Dùng trong truy vấn dữ liệu thật: [$where,$params] = branch_filter('n.maChiNhanh');
function branch_filter(string $column = 'maChiNhanh'): array
{
    require_login();
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?$/D', $column)) {
        throw new InvalidArgumentException('Tên cột phạm vi không hợp lệ.');
    }
    $id = current_branch_id();
    return $id === null ? ['1=1', []] : [$column . ' = ?', [$id]];
}

function throttle_keys(string $username): array
{
    return [hash('sha256', 'user:' . strtolower($username)), hash('sha256', 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'local'))];
}

function login_is_throttled(array $keys): bool
{
    $statement=db()->prepare('SELECT MAX(khoaDen) FROM GioiHanDangNhap WHERE khoa IN (?,?)');
    $statement->execute($keys);
    return (int)$statement->fetchColumn() > time();
}

function record_failed_login(array $keys): void
{
    $statement=db()->prepare('INSERT INTO GioiHanDangNhap(khoa,soLanSai,batDau,khoaDen) VALUES (?,1,?,0)
        ON DUPLICATE KEY UPDATE soLanSai=IF(batDau < ?,1,soLanSai+1),
        batDau=IF(batDau < ?,?,batDau),khoaDen=IF(soLanSai >= ?,?,khoaDen)');
    foreach ($keys as $index=>$key) {
        $now=time(); $statement->execute([$key,$now,$now-900,$now-900,$now,$index===0?5:30,$now+900]);
    }
}

function attempt_login(string $username, string $password): bool
{
    $keys=throttle_keys($username);
    if (login_is_throttled($keys)) { return false; }
    $user=find_user($username);
    // Hash băm sẵn của giá trị giả, không phải mật khẩu của tài khoản hệ thống.
    $dummy='$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
    $valid=password_verify($password,$user['matKhau'] ?? $dummy);
    if (!$user || !$valid || !user_is_active($user)) { record_failed_login($keys); return false; }
    if (password_needs_rehash($user['matKhau'], PASSWORD_ARGON2ID)) {
        db()->prepare('UPDATE TaiKhoan SET matKhau=? WHERE tenDangNhap=?')->execute([password_hash($password,PASSWORD_ARGON2ID),$user['tenDangNhap']]);
    }
    db()->prepare('DELETE FROM GioiHanDangNhap WHERE khoa=?')->execute([$keys[0]]);
    db()->prepare('UPDATE TaiKhoan SET lanDangNhapCuoi=UTC_TIMESTAMP() WHERE tenDangNhap=?')->execute([$user['tenDangNhap']]);
    session_regenerate_id(true);
    $_SESSION = ['username'=>$user['tenDangNhap'],'auth_version'=>(int)$user['phienBan'],
        'signed_in'=>time(),'last_activity'=>time(),'csrf'=>bin2hex(random_bytes(32))];
    return true;
}

function initials(string $name): string
{
    $parts=preg_split('/\s+/u', trim($name));
    return mb_strtoupper(implode('',array_map(fn($part)=>mb_substr($part,0,1),array_slice($parts,-2))));
}
