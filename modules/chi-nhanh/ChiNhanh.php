<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404); exit; }

/** Lỗi nghiệp vụ theo mẫu NhanSuLoi/DinhMucLoi của nhóm. */
final class ChiNhanhLoi extends RuntimeException
{
    public function __construct(public readonly string $ma, string $thongBao)
    {
        parent::__construct($thongBao);
    }
}

/**
 * ChiNhanh — SD-13–16, theo cách tách Control/Boundary của phần nhân sự.
 * Dùng db(), require_permission() và transaction chung; không tạo PDO riêng.
 * Phương thức ghi trả ['ok', 'code', 'message', 'data'] theo hợp đồng của nhóm.
 * Các câu SQL, kiểm tra liên kết và thứ tự khóa giữ từ bản đã triển khai.
 */
final class ChiNhanh
{
    public static function them(array $duLieu): array
    {
        require_permission('branches.manage');
        $duLieu += ['tenChiNhanh'=>'','diaChi'=>'','soDienThoai'=>'','soBan'=>'1','sucChua'=>'4','menu'=>[]];
        return self::ghi(
            static fn(): array => ['maChiNhanh'=>self::luuMoi($duLieu)],
            'Thêm chi nhánh thành công.',
            'Chưa lưu được chi nhánh. Vui lòng thử lại.'
        );
    }

    public static function sua(string $maChiNhanh, array $duLieu): array
    {
        require_permission('branches.manage');
        $duLieu += ['tenChiNhanh'=>'','diaChi'=>'','soDienThoai'=>'','maQuanLy'=>'','soBan'=>'1','sucChua'=>'4','menu'=>[]];
        return self::ghi(static function() use ($maChiNhanh,$duLieu): array {
            self::luuCapNhat($maChiNhanh,$duLieu);
            return ['maChiNhanh'=>$maChiNhanh];
        }, 'Cập nhật chi nhánh thành công.', 'Chưa lưu được thay đổi. Vui lòng thử lại.');
    }

    public static function ngungHoatDong(string $maChiNhanh): array
    {
        require_permission('branches.manage');
        $ketQua=self::ghi(
            static fn(): array => ['maChiNhanh'=>$maChiNhanh,'daThayDoi'=>self::luuNgungHoatDong($maChiNhanh)],
            'Chi nhánh đã ngừng hoạt động.',
            'Chưa thể ngừng hoạt động chi nhánh. Vui lòng thử lại.'
        );
        if ($ketQua['ok'] && !$ketQua['data']['daThayDoi']) {
            $ketQua['message']='Chi nhánh này đã ngừng hoạt động trước đó.';
        }
        return $ketQua;
    }

    public static function xoaVinhVien(string $maChiNhanh, string $dauVanTay): array
    {
        self::yeuCauChuChuoi();
        return self::ghi(static function() use ($maChiNhanh,$dauVanTay): array {
            self::luuXoaVinhVien($maChiNhanh,$dauVanTay);
            return ['maChiNhanh'=>$maChiNhanh];
        }, 'Đã xóa vĩnh viễn dữ liệu riêng của chi nhánh.',
            'Chưa xóa được dữ liệu. Toàn bộ thao tác đã được hoàn tác; vui lòng kiểm tra liên kết dữ liệu hoặc thử lại.');
    }

    // SD-14: dữ liệu chỉ đọc, dùng kết nối PDO chung của dự án.
    public static function danhSach(): array
    {
        require_permission('branches.view');
        return db()->query('SELECT c.maChiNhanh,c.tenChiNhanh,c.diaChi,c.soDienThoai,c.trangThai,
            n.hoTen AS tenQuanLy,
            (SELECT COUNT(*) FROM Ban b WHERE b.maChiNhanh=c.maChiNhanh) AS soBan
            FROM ChiNhanh c LEFT JOIN NhanVien n ON n.maNhanVien=c.maQuanLy
            ORDER BY c.maChiNhanh')->fetchAll();
    }

    public static function chiTiet(string $maChiNhanh): ?array
    {
        require_permission('branches.view');
        $query=db()->prepare('SELECT c.maChiNhanh,c.tenChiNhanh,c.diaChi,c.soDienThoai,c.trangThai,c.maQuanLy,
            n.hoTen AS tenQuanLy,n.soDienThoai AS dienThoaiQuanLy,
            (SELECT COUNT(*) FROM Ban b WHERE b.maChiNhanh=c.maChiNhanh) AS soBan,
            (SELECT COUNT(*) FROM Ban b WHERE b.maChiNhanh=c.maChiNhanh AND b.trangThai<>\'NGUNG\') AS soBanSuDung
            FROM ChiNhanh c LEFT JOIN NhanVien n ON n.maNhanVien=c.maQuanLy
            WHERE c.maChiNhanh=?');
        $query->execute([$maChiNhanh]);
        $branch=$query->fetch();
        if (!$branch) { return null; }
        $query=db()->prepare('SELECT m.maMon,m.tenMon,COALESCE(t.giaBan,m.donGia) AS giaBan,
            t.trangThai,m.trangThai AS trangThaiMon
            FROM ThucDonChiNhanh t JOIN MonAn m ON m.maMon=t.maMon
            WHERE t.maChiNhanh=? ORDER BY m.tenMon,m.maMon');
        $query->execute([$maChiNhanh]);
        $branch['thucDon']=$query->fetchAll();
        return $branch;
    }

    public static function danhSachMon(): array
    {
        require_permission('branches.manage');
        return db()->query("SELECT m.maMon,m.tenMon,m.donGia FROM MonAn m JOIN NhomMon n ON n.maNhom=m.maNhom WHERE m.trangThai='HOAT_DONG' AND n.maChuoi='FBN' ORDER BY m.tenMon,m.maMon")->fetchAll();
    }

    // Khóa chuỗi để hai yêu cầu thêm cùng tên không cùng vượt qua kiểm tra trùng.
    private static function luuMoi(array $input): string
    {
        require_permission('branches.manage');
        $name=trim($input['tenChiNhanh']);
        $address=trim($input['diaChi']);
        $phone=trim($input['soDienThoai']);
        $tables=filter_var($input['soBan'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);
        $capacity=filter_var($input['sucChua'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>20]]);
        if ($name==='' || mb_strlen($name)>100) { throw new ChiNhanhLoi('VALIDATION_ERROR','Tên chi nhánh phải có từ 1 đến 100 ký tự.'); }
        if ($address==='' || mb_strlen($address)>255) { throw new ChiNhanhLoi('VALIDATION_ERROR','Địa chỉ phải có từ 1 đến 255 ký tự.'); }
        if (!preg_match('/^0[0-9]{9,10}$/D',$phone)) { throw new ChiNhanhLoi('VALIDATION_ERROR','Số điện thoại phải gồm 10 hoặc 11 chữ số và bắt đầu bằng 0.'); }
        if ($tables===false || $capacity===false) { throw new ChiNhanhLoi('VALIDATION_ERROR','Số bàn phải từ 1 đến 100; sức chứa mỗi bàn từ 1 đến 20 người.'); }
        $menu=$input['menu'];
        if (!is_array($menu) || count($menu)>1000) { throw new ChiNhanhLoi('VALIDATION_ERROR','Danh sách món ăn không hợp lệ.'); }
        foreach ($menu as $id) {
            if (!is_string($id) || $id==='' || strlen($id)>10) { throw new ChiNhanhLoi('VALIDATION_ERROR','Món ăn được chọn không hợp lệ.'); }
        }
        $menu=array_values(array_unique($menu));
        $pdo=db();
        $pdo->beginTransaction();
        try {
            $chain=$pdo->query("SELECT maChuoi FROM ChuoiNhaHang WHERE maChuoi='FBN' FOR UPDATE")->fetchColumn();
            if ($chain===false) { throw new ChiNhanhLoi('VALIDATION_ERROR','Chưa có thông tin chuỗi nhà hàng FBN.'); }
            $check=$pdo->prepare('SELECT maChiNhanh FROM ChiNhanh WHERE maChuoi=? AND TRIM(tenChiNhanh)=? LIMIT 1');
            $check->execute([$chain,$name]);
            if ($check->fetchColumn()!==false) { throw new ChiNhanhLoi('CONFLICT','Tên chi nhánh đã tồn tại trong chuỗi. Vui lòng nhập tên khác.'); }
            if ($menu) {
                $check=$pdo->prepare('SELECT m.maMon FROM MonAn m JOIN NhomMon n ON n.maNhom=m.maNhom WHERE m.maMon IN ('.implode(',',array_fill(0,count($menu),'?')).") AND m.trangThai='HOAT_DONG' AND n.maChuoi='FBN' ORDER BY m.maMon FOR UPDATE");
                $check->execute($menu);
                if (count($check->fetchAll())!==count($menu)) { throw new ChiNhanhLoi('CONFLICT','Có món ăn không còn hoạt động. Vui lòng chọn lại thực đơn.'); }
            }
            $branchId='CN'.strtoupper(bin2hex(random_bytes(4)));
            $pdo->prepare("INSERT INTO ChiNhanh(maChiNhanh,tenChiNhanh,diaChi,soDienThoai,maChuoi,trangThai) VALUES (?,?,?,?,?,'HOAT_DONG')")
                ->execute([$branchId,$name,$address,$phone,$chain]);
            $insertTable=$pdo->prepare('INSERT INTO Ban(maBan,maChiNhanh,soBan,tenBan,sucChua) VALUES (?,?,?,?,?)');
            for ($i=1;$i<=$tables;$i++) {
                $insertTable->execute(['B'.strtoupper(bin2hex(random_bytes(4))),$branchId,$i,'Bàn '.$i,$capacity]);
            }
            $insertMenu=$pdo->prepare('INSERT INTO ThucDonChiNhanh(maChiNhanh,maMon) VALUES (?,?)');
            foreach ($menu as $id) { $insertMenu->execute([$branchId,$id]); }
            $pdo->commit();
            return $branchId;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $error;
        }
    }

    public static function danhSachQuanLy(string $id): array
    {
        require_permission('branches.manage');
        $q=db()->prepare("SELECT maNhanVien,hoTen FROM NhanVien WHERE maChiNhanh=? AND maVaiTro='QUAN_LY' AND trangThai='DANG_LAM' ORDER BY hoTen");
        $q->execute([$id]);return $q->fetchAll();
    }

    public static function thucDonSua(string $id): array
    {
        require_permission('branches.manage');
        $q=db()->prepare("SELECT m.maMon,m.tenMon,m.donGia,m.trangThai FROM MonAn m JOIN NhomMon n ON n.maNhom=m.maNhom
            LEFT JOIN ThucDonChiNhanh t ON t.maMon=m.maMon AND t.maChiNhanh=?
            WHERE n.maChuoi='FBN' AND (m.trangThai='HOAT_DONG' OR t.trangThai='DANG_BAN') ORDER BY m.tenMon,m.maMon");
        $q->execute([$id]);return $q->fetchAll();
    }

    private static function luuCapNhat(string $id,array $input): void
    {
        require_permission('branches.manage');
        $name=trim($input['tenChiNhanh']);$address=trim($input['diaChi']);$phone=trim($input['soDienThoai']);
        if ($name==='' || mb_strlen($name)>100) { throw new ChiNhanhLoi('VALIDATION_ERROR','Tên chi nhánh phải có từ 1 đến 100 ký tự.'); }
        if ($address==='' || mb_strlen($address)>255) { throw new ChiNhanhLoi('VALIDATION_ERROR','Địa chỉ phải có từ 1 đến 255 ký tự.'); }
        if (!preg_match('/^0[0-9]{9,10}$/D',$phone)) { throw new ChiNhanhLoi('VALIDATION_ERROR','Số điện thoại phải gồm 10 hoặc 11 chữ số và bắt đầu bằng 0.'); }
        $target=filter_var($input['soBan'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);
        $capacity=filter_var($input['sucChua'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>20]]);
        if ($target===false || $capacity===false) { throw new ChiNhanhLoi('VALIDATION_ERROR','Số bàn phải từ 1 đến 100; sức chứa bàn mới từ 1 đến 20 người.'); }
        $menu=$input['menu'];
        if (!is_array($menu) || count($menu)>1000) { throw new ChiNhanhLoi('VALIDATION_ERROR','Danh sách món ăn không hợp lệ.'); }
        foreach ($menu as $dish) {
            if (!is_string($dish) || $dish==='' || strlen($dish)>10) { throw new ChiNhanhLoi('VALIDATION_ERROR','Món ăn được chọn không hợp lệ.'); }
        }
        $menu=array_values(array_unique($menu));
        $manager=$input['maQuanLy'];
        $pdo=db();$pdo->beginTransaction();
        try {
            // Cùng thứ tự khóa với thao tác thêm: chuỗi, chi nhánh, dữ liệu phụ thuộc.
            $pdo->query("SELECT maChuoi FROM ChuoiNhaHang WHERE maChuoi='FBN' FOR UPDATE")->fetchColumn();
            $q=$pdo->prepare('SELECT maChiNhanh,maChuoi,trangThai FROM ChiNhanh WHERE maChiNhanh=? FOR UPDATE');$q->execute([$id]);$branch=$q->fetch();
            if (!$branch || $branch['maChuoi']!=='FBN') { throw new ChiNhanhLoi('NOT_FOUND','Không tìm thấy chi nhánh.'); }
            if ($branch['trangThai']!=='HOAT_DONG') { throw new ChiNhanhLoi('CONFLICT','Chi nhánh đã ngừng hoạt động, không thể cập nhật.'); }
            $q=$pdo->prepare('SELECT maChiNhanh FROM ChiNhanh WHERE maChuoi=? AND TRIM(tenChiNhanh)=? AND maChiNhanh<>? LIMIT 1');$q->execute([$branch['maChuoi'],$name,$id]);
            if ($q->fetchColumn()!==false) { throw new ChiNhanhLoi('CONFLICT','Tên chi nhánh đã tồn tại ở chi nhánh khác.'); }
            if ($manager!=='') {
                $q=$pdo->prepare("SELECT maNhanVien FROM NhanVien WHERE maNhanVien=? AND maChiNhanh=? AND maVaiTro='QUAN_LY' AND trangThai='DANG_LAM' FOR UPDATE");$q->execute([$manager,$id]);
                if (!$q->fetchColumn()) { throw new ChiNhanhLoi('VALIDATION_ERROR','Quản lý phải là nhân viên quản lý đang làm tại chính chi nhánh này.'); }
            }
            $q=$pdo->prepare('SELECT maMon,trangThai FROM ThucDonChiNhanh WHERE maChiNhanh=? ORDER BY maMon FOR UPDATE');$q->execute([$id]);$oldMenu=$q->fetchAll(PDO::FETCH_KEY_PAIR);
            if ($menu) {
                $q=$pdo->prepare('SELECT m.maMon,m.trangThai FROM MonAn m JOIN NhomMon n ON n.maNhom=m.maNhom WHERE n.maChuoi=\'FBN\' AND m.maMon IN ('.implode(',',array_fill(0,count($menu),'?')).') ORDER BY m.maMon FOR UPDATE');$q->execute($menu);$valid=$q->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($menu as $dish) {
                    if (!isset($valid[$dish]) || ($valid[$dish]!=='HOAT_DONG' && ($oldMenu[$dish] ?? '')!=='DANG_BAN')) { throw new ChiNhanhLoi('CONFLICT','Có món ăn không còn hoạt động. Vui lòng chọn lại thực đơn.'); }
                }
            }
            $q=$pdo->prepare('SELECT maBan,soBan,trangThai FROM Ban WHERE maChiNhanh=? ORDER BY soBan FOR UPDATE');$q->execute([$id]);$tables=$q->fetchAll();
            $active=array_values(array_filter($tables,static fn(array $b):bool=>$b['trangThai']!=='NGUNG'));
            if ($target<count($active)) {
                $needed=count($active)-$target;$candidates=[];
                $booking=$pdo->prepare("SELECT maDatBan FROM DatBan WHERE maBan=? AND trangThai IN ('DA_XAC_NHAN','DANG_PHUC_VU') AND (trangThai='DANG_PHUC_VU' OR thoiGianKetThucDuKien>NOW()) FOR UPDATE");
                $order=$pdo->prepare("SELECT maDon FROM DonGoiMon WHERE maBan=? AND trangThai='DANG_PHUC_VU' FOR UPDATE");
                foreach (array_reverse($active) as $table) {
                    if ($table['trangThai']!=='TRONG') { continue; }
                    $booking->execute([$table['maBan']]);$hasBooking=$booking->fetchAll();
                    $order->execute([$table['maBan']]);$hasOrder=$order->fetchAll();
                    if (!$hasBooking && !$hasOrder) { $candidates[]=$table['maBan']; }
                    if (count($candidates)===$needed) { break; }
                }
                if (count($candidates)<$needed) { throw new ChiNhanhLoi('CONFLICT','Không thể giảm số bàn: có bàn đang phục vụ hoặc có lịch đặt còn hiệu lực.'); }
                $q=$pdo->prepare("UPDATE Ban SET trangThai='NGUNG' WHERE maBan=? AND maChiNhanh=?");
                foreach ($candidates as $tableId) { $q->execute([$tableId,$id]); }
            } elseif ($target>count($active)) {
                $needed=$target-count($active);
                $q=$pdo->prepare("UPDATE Ban SET trangThai='TRONG' WHERE maBan=? AND maChiNhanh=?");
                foreach ($tables as $table) {
                    if ($table['trangThai']==='NGUNG' && $needed>0) { $q->execute([$table['maBan'],$id]);$needed--; }
                }
                $next=$tables?max(array_column($tables,'soBan'))+1:1;
                $q=$pdo->prepare('INSERT INTO Ban(maBan,maChiNhanh,soBan,tenBan,sucChua) VALUES (?,?,?,?,?)');
                while ($needed-->0) { $q->execute(['B'.strtoupper(bin2hex(random_bytes(4))),$id,$next,'Bàn '.$next,$capacity]);$next++; }
            }
            $q=$pdo->prepare('UPDATE ChiNhanh SET tenChiNhanh=?,diaChi=?,soDienThoai=?,maQuanLy=? WHERE maChiNhanh=?');$q->execute([$name,$address,$phone,$manager!==''?$manager:null,$id]);
            $q=$pdo->prepare("UPDATE ThucDonChiNhanh SET trangThai='TAM_NGUNG' WHERE maChiNhanh=?");$q->execute([$id]);
            $q=$pdo->prepare("INSERT INTO ThucDonChiNhanh(maChiNhanh,maMon,trangThai) VALUES (?,?,'DANG_BAN') ON DUPLICATE KEY UPDATE trangThai='DANG_BAN'");
            foreach ($menu as $dish) { $q->execute([$id,$dish]); }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }throw $error;
        }
    }

    private static function luuNgungHoatDong(string $id): bool
    {
        require_permission('branches.manage');
        $pdo=db();$pdo->beginTransaction();
        try {
            // Giữ thứ tự khóa nhất quán với Thêm/Cập nhật; các module ghi khác
            // cũng phải kiểm tra chi nhánh còn hoạt động theo R06.
            $pdo->query("SELECT maChuoi FROM ChuoiNhaHang WHERE maChuoi='FBN' FOR UPDATE")->fetchColumn();
            $q=$pdo->prepare('SELECT maChiNhanh,maChuoi,trangThai FROM ChiNhanh WHERE maChiNhanh=? FOR UPDATE');$q->execute([$id]);$branch=$q->fetch();
            if (!$branch || $branch['maChuoi']!=='FBN') { throw new ChiNhanhLoi('NOT_FOUND','Không tìm thấy chi nhánh.'); }
            if ($branch['trangThai']==='NGUNG') { $pdo->commit();return false; }
            // Khóa bàn trước đơn/lịch đặt như hợp đồng R02/R04.
            $q=$pdo->prepare('SELECT maBan FROM Ban WHERE maChiNhanh=? ORDER BY maBan FOR UPDATE');$q->execute([$id]);$q->fetchAll();
            $q=$pdo->prepare("SELECT maNhanVien FROM NhanVien WHERE maChiNhanh=? AND trangThai='DANG_LAM' FOR UPDATE");$q->execute([$id]);
            if ($q->fetchAll()) { throw new ChiNhanhLoi('CONFLICT','Chưa thể ngừng hoạt động: chi nhánh còn nhân viên đang làm việc.'); }
            $q=$pdo->prepare("SELECT maDon FROM DonGoiMon WHERE maChiNhanh=? AND trangThai='DANG_PHUC_VU' FOR UPDATE");$q->execute([$id]);
            if ($q->fetchAll()) { throw new ChiNhanhLoi('CONFLICT','Chưa thể ngừng hoạt động: chi nhánh còn đơn hàng chưa hoàn tất.'); }
            $q=$pdo->prepare("SELECT maDatBan FROM DatBan WHERE maChiNhanh=? AND
                (trangThai='DANG_PHUC_VU' OR (trangThai IN ('CHO_XAC_NHAN','DA_XAC_NHAN')
                AND COALESCE(thoiGianKetThucDuKien,thoiGianDen)>=NOW())) FOR UPDATE");$q->execute([$id]);
            if ($q->fetchAll()) { throw new ChiNhanhLoi('CONFLICT','Chưa thể ngừng hoạt động: chi nhánh còn yêu cầu đặt bàn hoặc lịch đặt còn hiệu lực.'); }
            $q=$pdo->prepare("UPDATE ChiNhanh SET trangThai='NGUNG' WHERE maChiNhanh=? AND trangThai='HOAT_DONG'");$q->execute([$id]);
            $pdo->commit();return true;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }throw $error;
        }
    }

    // Xóa vĩnh viễn dữ liệu riêng của chi nhánh.
    public static function yeuCauChuChuoi(): void
    {
        $user=require_login();
        if ($user['maVaiTro']!=='CHU_CHUOI' || !can('branches.manage')) { http_response_code(403);exit('Chỉ Chủ chuỗi được xóa vĩnh viễn dữ liệu chi nhánh.'); }
    }

    // Chỉ các bản ghi thuộc chi nhánh. Danh mục chung của chuỗi không nằm trong phạm vi.
    // Thứ tự dưới đây là thứ tự xóa con trước cha, không tắt kiểm tra khóa ngoại.
    private static function phamViXoa(): array
    {
        return [
            'ChiTietDonMon'=>['Chi tiết gọi món','t.maChiNhanh=?'],
            'DonGoiMon'=>['Đơn gọi món','t.maChiNhanh=?'],
            'DatBan'=>['Lịch đặt bàn','t.maChiNhanh=?'],
            'ChiTietGiaoDichKho'=>['Chi tiết biến động kho','t.maChiNhanh=?'],
            'GiaoDichKho'=>['Biến động kho','t.maChiNhanh=?'],
            'TonKhoTheoLo'=>['Lô tồn kho','t.maChiNhanh=?'],
            'ChiTietDonNhapNCC'=>['Chi tiết đơn nhập nhà cung cấp','t.maDonNhap IN (SELECT d.maDonNhap FROM DonNhapNCC d WHERE d.maChiNhanh=?)'],
            'DonNhapNCC'=>['Đơn nhập nhà cung cấp','t.maChiNhanh=?'],
            'YeuCauNhapKho'=>['Yêu cầu bổ sung nguyên liệu','t.maChiNhanh=?'],
            'ChiTietDeXuat'=>['Chi tiết đề xuất','t.maPhieu IN (SELECT p.maPhieu FROM PhieuDeXuat p WHERE p.maChiNhanh=?)'],
            'PhieuDeXuat'=>['Phiếu đề xuất','t.maChiNhanh=?'],
            'YeuCauDieuChuyen'=>['Yêu cầu hỗ trợ nhân sự','t.maChiNhanh=?'],
            'ThucDonChiNhanh'=>['Món trong thực đơn chi nhánh','t.maChiNhanh=?'],
            'Ban'=>['Bàn','t.maChiNhanh=?'],
            'NhatKyNhanSu'=>['Lịch sử nhân sự của chi nhánh','t.maNhanVien IN (SELECT n.maNhanVien FROM NhanVien n WHERE n.maChiNhanh=?)'],
            'TaiKhoan'=>['Tài khoản nhân viên chi nhánh','t.maNhanVien IN (SELECT n.maNhanVien FROM NhanVien n WHERE n.maChiNhanh=?)'],
            'NhanVien'=>['Hồ sơ nhân viên chi nhánh','t.maChiNhanh=?'],
            'ChiNhanh'=>['Chi nhánh','t.maChiNhanh=?'],
        ];
    }

    public static function thongKeDuLieu(string $id): array
    {
        self::yeuCauChuChuoi();
        $summary=[];
        foreach (self::phamViXoa() as $table=>[$label,$where]) {
            $q=db()->prepare('SELECT COUNT(*) FROM '.$table.' t WHERE '.$where);$q->execute([$id]);
            $summary[$table]=['label'=>$label,'count'=>(int)$q->fetchColumn()];
        }
        return $summary;
    }

    public static function dauVanTay(array $summary): string
    {
        return hash('sha256',json_encode($summary,JSON_THROW_ON_ERROR));
    }

    public static function lyDoKhongTheXoa(string $id,bool $locking=false): array
    {
        self::yeuCauChuChuoi();
        $pdo=db();$blockers=[];$lock=$locking?' FOR UPDATE':'';$scope=self::phamViXoa();
        // Một điều chuyển chứa lịch sử của cả hai chi nhánh, không được xóa theo một phía.
        $q=$pdo->prepare('SELECT maDieuChuyen FROM DieuChuyenKho WHERE maChiNhanhNguon=? OR maChiNhanhNhan=? LIMIT 1'.$lock);$q->execute([$id,$id]);
        if ($q->fetchColumn()!==false) { $blockers[]='Chi nhánh có dữ liệu điều chuyển nguyên liệu dùng chung với chi nhánh khác.'; }
        // Chặn mọi tham chiếu từ ngoài phạm vi, kể cả bảng mới bổ sung về sau.
        $fk=$pdo->query("SELECT TABLE_NAME,CONSTRAINT_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE()
            AND REFERENCED_TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION")->fetchAll();
        $groups=[];$canonical=[];
        foreach (array_keys($scope) as $table) { $canonical[strtolower($table)]=$table; }
        foreach ($fk as $row) {
            foreach (['TABLE_NAME','COLUMN_NAME','REFERENCED_TABLE_NAME','REFERENCED_COLUMN_NAME'] as $key) {
                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D',$row[$key])) { throw new RuntimeException('Unsupported schema identifier'); }
            }
            $parent=$canonical[strtolower($row['REFERENCED_TABLE_NAME'])] ?? null;
            if ($parent===null) { continue; }
            // MySQL/MariaDB trên Windows có thể trả tên bảng ở dạng chữ thường.
            $row['REFERENCED_TABLE_NAME']=$parent;
            $row['TABLE_NAME']=$canonical[strtolower($row['TABLE_NAME'])] ?? $row['TABLE_NAME'];
            $key=$row['TABLE_NAME'].':'.$row['CONSTRAINT_NAME'];$groups[$key][]=$row;
        }
        foreach ($groups as $rows) {
            $child=$rows[0]['TABLE_NAME'];$parent=$rows[0]['REFERENCED_TABLE_NAME'];$join=[];
            foreach ($rows as $row) { $join[]='c.'.$row['COLUMN_NAME'].'=t.'.$row['REFERENCED_COLUMN_NAME']; }
            $where=$scope[$parent][1];$params=[$id];
            if (isset($scope[$child])) { $where.=' AND NOT ('.str_replace('t.','c.',$scope[$child][1]).')';$params[]=$id; }
            $q=$pdo->prepare('SELECT 1 FROM '.$child.' c JOIN '.$parent.' t ON '.implode(' AND ',$join).' WHERE ('.$where.') LIMIT 1'.$lock);
            $q->execute($params);
            if ($q->fetchColumn()!==false) {
                error_log('Branch purge blocked by external reference '.$child.' -> '.$parent);
                $blockers[]='Có dữ liệu bên ngoài chi nhánh đang tham chiếu đến '.$scope[$parent][0].'. Cần giữ lại để bảo toàn lịch sử dùng chung.';
            }
        }
        return array_values(array_unique($blockers));
    }

    private static function luuXoaVinhVien(string $id,string $fingerprint): void
    {
        self::yeuCauChuChuoi();
        if ($id==='' || strlen($id)>10) { throw new ChiNhanhLoi('VALIDATION_ERROR','Mã chi nhánh không hợp lệ.'); }
        $pdo=db();$pdo->beginTransaction();
        try {
            $pdo->query("SELECT maChuoi FROM ChuoiNhaHang WHERE maChuoi='FBN' FOR UPDATE")->fetchColumn();
            $q=$pdo->prepare('SELECT trangThai,maChuoi FROM ChiNhanh WHERE maChiNhanh=? FOR UPDATE');$q->execute([$id]);$branch=$q->fetch();
            if (!$branch || $branch['maChuoi']!=='FBN') { throw new ChiNhanhLoi('NOT_FOUND','Không tìm thấy chi nhánh.'); }
            if ($branch['trangThai']!=='NGUNG') { throw new ChiNhanhLoi('CONFLICT','Chỉ được xóa vĩnh viễn chi nhánh đã ngừng hoạt động.'); }
            foreach (self::phamViXoa() as $table=>[$label,$where]) {
                $q=$pdo->prepare('SELECT 1 FROM '.$table.' t WHERE '.$where.' FOR UPDATE');$q->execute([$id]);$q->fetchAll();
            }
            $blockers=self::lyDoKhongTheXoa($id,true);
            if ($blockers) { throw new ChiNhanhLoi('CONFLICT',implode(' ',$blockers)); }
            if (!hash_equals($fingerprint,self::dauVanTay(self::thongKeDuLieu($id)))) { throw new ChiNhanhLoi('CONFLICT','Dữ liệu chi nhánh đã thay đổi. Vui lòng mở lại màn hình xác nhận trước khi xóa.'); }
            // Gỡ liên kết vòng ChiNhanh → NhanVien trước khi xóa hồ sơ của chính chi nhánh.
            $pdo->prepare('UPDATE ChiNhanh SET maQuanLy=NULL WHERE maChiNhanh=?')->execute([$id]);
            foreach (self::phamViXoa() as $table=>[$label,$where]) {
                $q=$pdo->prepare('DELETE t FROM '.$table.' t WHERE '.$where);$q->execute([$id]);
            }
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); }throw $e; }
    }

    // Các phương thức nghiệp vụ ở trên giữ transaction và rollback của từng thao tác.
    private static function ghi(callable $congViec, string $thongBao, string $loiMayChu): array
    {
        try {
            return self::ketQua(true,'OK',$thongBao,$congViec());
        } catch (ChiNhanhLoi $loi) {
            return self::ketQua(false,$loi->ma,$loi->getMessage());
        } catch (Throwable $loi) {
            error_log('FBN chi-nhanh: '.$loi->getMessage());
            return self::ketQua(false,'SERVER_ERROR',$loiMayChu);
        }
    }

    private static function ketQua(bool $ok, string $code, string $message, array $data = []): array
    {
        return ['ok'=>$ok,'code'=>$code,'message'=>$message,'data'=>$data];
    }
}
