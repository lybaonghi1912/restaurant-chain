<?php
declare(strict_types=1);

function assignable_roles(): array
{
    $roles=db()->query('SELECT maVaiTro,tenVaiTro FROM VaiTro ORDER BY maVaiTro')->fetchAll();
    return array_values(array_filter($roles,fn($role)=>current_user()['maVaiTro']==='CHU_CHUOI'
        || in_array($role['maVaiTro'],['PHUC_VU','BEP','KHO'],true)));
}

function can_manage_account(array $target): bool
{
    $actor=current_user();
    return $actor && can('accounts.manage') && ($actor['maVaiTro']==='CHU_CHUOI'
        || ($target['maChiNhanh']===$actor['maChiNhanh'] && in_array($target['maVaiTro'],['PHUC_VU','BEP','KHO'],true)));
}

function save_account(string $action, array $input): array
{
    require_permission('accounts.manage');
    $actor=current_user();$pdo=db();$username=trim($input['username']);
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/D',$username)) { return [false,'Tên đăng nhập gồm 3–50 ký tự chữ, số, dấu chấm, gạch dưới hoặc gạch ngang.']; }
    if (!in_array($action,['create','update','reset'],true)) { return [false,'Thao tác không hợp lệ.']; }
    $pdo->beginTransaction();
    try {
        // Khóa các chủ chuỗi trước khi cập nhật để không thể khóa/hạ quyền chủ chuỗi cuối cùng.
        $owners=$pdo->query("SELECT t.tenDangNhap FROM TaiKhoan t JOIN NhanVien n ON n.maNhanVien=t.maNhanVien
            WHERE n.maVaiTro='CHU_CHUOI' AND n.trangThai='DANG_LAM' AND t.trangThai='HOAT_DONG' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $lock=$pdo->prepare('SELECT tenDangNhap FROM TaiKhoan WHERE tenDangNhap=? FOR UPDATE');$lock->execute([$username]);
        $target=find_user($username);
        if ($action==='create' && $target) { throw new DomainException('Tên đăng nhập đã tồn tại.'); }
        if ($action!=='create' && (!$target || !can_manage_account($target))) { throw new DomainException('Không thể quản lý tài khoản này trong phạm vi được cấp.'); }
        if ($action==='reset') {
            if ($target['tenDangNhap']===$actor['tenDangNhap']) { throw new DomainException('Dùng trang Đổi mật khẩu để đổi mật khẩu của chính bạn.'); }
            $temporary='Fbn@'.bin2hex(random_bytes(8));
            $pdo->prepare('UPDATE TaiKhoan SET matKhau=?,phienBan=phienBan+1 WHERE tenDangNhap=?')->execute([password_hash($temporary,PASSWORD_ARGON2ID),$username]);
            $pdo->prepare('DELETE FROM GioiHanDangNhap WHERE khoa=?')->execute([hash('sha256','user:'.strtolower($username))]);
            $pdo->commit();return [true,'Mật khẩu mới của '.$username.': '.$temporary.'. Hãy chuyển riêng cho nhân viên để họ đổi lại.'];
        }
        $name=trim($input['name']);$role=$input['role'];$status=$input['status'];
        if ($name==='' || mb_strlen($name)>100) { throw new DomainException('Họ tên phải từ 1 đến 100 ký tự.'); }
        if (!in_array($role,array_column(assignable_roles(),'maVaiTro'),true)) { throw new DomainException('Bạn không được cấp vai trò này.'); }
        if (!in_array($status,['HOAT_DONG','KHOA'],true)) { throw new DomainException('Trạng thái không hợp lệ.'); }
        $branch=$role==='CHU_CHUOI'?null:($actor['maVaiTro']==='CHU_CHUOI'?$input['branch']:$actor['maChiNhanh']);
        if ($role!=='CHU_CHUOI' && !in_array($branch,array_column(active_branches(),'maChiNhanh'),true)) { throw new DomainException('Vui lòng chọn chi nhánh đang hoạt động.'); }
        if ($target && $target['tenDangNhap']===$actor['tenDangNhap'] && ($role!==$actor['maVaiTro'] || $branch!==$actor['maChiNhanh'] || $status!=='HOAT_DONG')) {
            throw new DomainException('Không tự đổi vai trò, chi nhánh hoặc khóa tài khoản đang đăng nhập.');
        }
        if ($target && in_array($target['tenDangNhap'],$owners,true) && ($role!=='CHU_CHUOI' || $status!=='HOAT_DONG') && count($owners)<=1) {
            throw new DomainException('Phải giữ ít nhất một tài khoản Chủ chuỗi hoạt động.');
        }
        if ($action==='create') {
            $password=$input['password'];
            if (mb_strlen($password,'UTF-8')<12 || strlen($password)>128) { throw new DomainException('Mật khẩu cần ít nhất 12 ký tự, tối đa 128 byte.'); }
            $id='NV'.strtoupper(bin2hex(random_bytes(4)));
            $pdo->prepare('INSERT INTO NhanVien(maNhanVien,hoTen,maVaiTro,maChiNhanh) VALUES (?,?,?,?)')->execute([$id,$name,$role,$branch]);
            $pdo->prepare('INSERT INTO TaiKhoan(tenDangNhap,matKhau,maNhanVien,trangThai) VALUES (?,?,?,?)')->execute([$username,password_hash($password,PASSWORD_ARGON2ID),$id,$status]);
        } else {
            $pdo->prepare('UPDATE NhanVien SET hoTen=?,maVaiTro=?,maChiNhanh=? WHERE maNhanVien=?')->execute([$name,$role,$branch,$target['maNhanVien']]);
            $pdo->prepare('UPDATE TaiKhoan SET trangThai=?,phienBan=phienBan+1 WHERE tenDangNhap=?')->execute([$status,$username]);
            if ($target['tenDangNhap']===$actor['tenDangNhap']) { $_SESSION['auth_version']=(int)$target['phienBan']+1; }
        }
        $pdo->commit();return [true,$action==='create'?'Đã tạo tài khoản và lưu vào MySQL.':'Đã cập nhật tài khoản. Các phiên cũ của tài khoản được thu hồi.'];
    } catch (DomainException $error) { $pdo->rollBack();return [false,$error->getMessage()]; }
    catch (PDOException $error) {
        $pdo->rollBack();error_log('FBN account: '.$error->getMessage());
        return [false,'Không lưu được tài khoản. Kiểm tra tên đăng nhập có bị trùng rồi thử lại.'];
    }
}
