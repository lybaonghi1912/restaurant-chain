<?php
declare(strict_types=1);
if (!defined('APP_ENTRY')) { http_response_code(404);exit; }

/** SD-17/18: nghiệp vụ nguyên liệu, theo cách tách Boundary/Control của nhóm. */
final class NguyenLieu
{
    public static function taoDieuChuyen(string $requestId,string $nvl,string $source,string $quantity,string $operation): array
    {
        return self::ghi(static fn(): array => ['maDieuChuyen'=>self::luuDieuChuyen($requestId,$nvl,$source,$quantity,$operation)],
            'Đã duyệt phiếu điều chuyển.', 'Chưa lưu được yêu cầu điều chuyển. Vui lòng thử lại.');
    }

    public static function duyetDieuChuyenCu(string $id): array
    {
        return self::ghi(static fn(): array => ['maDieuChuyen'=>self::luuDuyetCu($id)],
            'Đã duyệt phiếu điều chuyển.', 'Chưa duyệt được phiếu. Vui lòng thử lại.');
    }

    public static function taoNhapNCC(string $requestId,string $nvl,string $supplier,string $quantity,string $operation): array
    {
        return self::ghi(static fn(): array => ['maDonNhap'=>self::luuNhapNCC($requestId,$nvl,$supplier,$quantity,$operation)],
            'Đã lưu yêu cầu nhập nguyên liệu.', 'Chưa lưu được yêu cầu nhập nguyên liệu. Vui lòng thử lại.');
    }

    public static function huyPhieu(string $kind,string $id,string $reason,bool $confirmed): array
    {
        return self::ghi(static fn(): array => ['maYeuCau'=>self::luuHuy($kind,$id,$reason,$confirmed)],
            'Đã hủy phiếu.', 'Chưa hủy được phiếu. Vui lòng thử lại.');
    }

    public static function yeuCauChuChuoi(): array
    {
        $user=require_login();
        if ($user['maVaiTro']!=='CHU_CHUOI' || !can('transfer.approve')) { http_response_code(403);exit('Chỉ Chủ chuỗi được xử lý yêu cầu bổ sung nguyên liệu.'); }
        return $user;
    }

    // DECIMAL(18,3) được so sánh bằng số nguyên phần nghìn, không làm tròn float.
    public static function soLuongPhanNghin(string $value): int
    {
        if (!preg_match('/^(0|[1-9][0-9]{0,14})(?:\.([0-9]{1,3}))?$/D',$value,$m)) { throw new DomainException('Số lượng phải là số không âm, tối đa 3 chữ số thập phân; dùng dấu chấm để nhập phần lẻ.'); }
        return (int)$m[1]*1000+(int)str_pad($m[2] ?? '',3,'0');
    }

    public static function soLuongThapPhan(int $units): string
    {
        return intdiv($units,1000).'.'.str_pad((string)($units%1000),3,'0',STR_PAD_LEFT);
    }

    public static function danhSachYeuCau(): array
    {
        $scope=current_branch_id();
        $q=db()->prepare("SELECT y.maYeuCau,y.ngayTao,c.tenChiNhanh,p.lyDo
            FROM YeuCauNhapKho y JOIN ChiNhanh c ON c.maChiNhanh=y.maChiNhanh
            JOIN PhieuDeXuat p ON p.maPhieu=y.maPhieu
            WHERE y.trangThai='CHO_XU_LY' AND c.trangThai='HOAT_DONG' AND c.maChuoi='FBN'".
            ($scope!==null?' AND y.maChiNhanh=?':'').' ORDER BY y.ngayTao,y.maYeuCau');
        $q->execute($scope!==null?[$scope]:[]);return $q->fetchAll();
    }

    public static function chiTietYeuCau(string $id): ?array
    {
        $q=db()->prepare("SELECT y.*,c.tenChiNhanh,c.trangThai AS trangThaiChiNhanh,c.viDo,c.kinhDo,p.lyDo
            FROM YeuCauNhapKho y JOIN ChiNhanh c ON c.maChiNhanh=y.maChiNhanh JOIN PhieuDeXuat p ON p.maPhieu=y.maPhieu
            WHERE y.maYeuCau=? AND c.maChuoi='FBN'");$q->execute([$id]);return $q->fetch() ?: null;
    }

    public static function nguyenLieuYeuCau(string $id): array
    {
        $q=db()->prepare("SELECT ct.maNVL,n.tenNVL,n.donViTinh,n.trangThai,ct.soLuong,
            COALESCE(a.daPhanBo,0)+COALESCE(b.daPhanBo,0) AS daPhanBo,
            GREATEST(ct.soLuong-COALESCE(a.daPhanBo,0)-COALESCE(b.daPhanBo,0),0) AS conThieu
            FROM YeuCauNhapKho y JOIN ChiTietDeXuat ct ON ct.maPhieu=y.maPhieu JOIN NguyenVatLieu n ON n.maNVL=ct.maNVL
            LEFT JOIN (SELECT t.maNVL,SUM(t.soLuongYeuCau) AS daPhanBo FROM DieuChuyenKho d
                JOIN ChiTietDieuChuyenKho t ON t.maDieuChuyen=d.maDieuChuyen
                WHERE d.maYeuCau=? AND d.trangThai IN ('CHO_XAC_NHAN_NGUON','DA_DUYET') GROUP BY t.maNVL) a ON a.maNVL=ct.maNVL
            LEFT JOIN (SELECT t.maNVL,SUM(t.soLuong) AS daPhanBo FROM DonNhapNCC d
                JOIN ChiTietDonNhapNCC t ON t.maDonNhap=d.maDonNhap WHERE d.maYeuCau=? AND d.trangThai='CHO_NHAP_HANG' GROUP BY t.maNVL) b ON b.maNVL=ct.maNVL
            WHERE y.maYeuCau=? ORDER BY ct.maNVL");$q->execute([$id,$id,$id]);return $q->fetchAll();
    }

    public static function nguonChiNhanh(array $request,string $nvl): array
    {
        $q=db()->prepare("SELECT s.*,COALESCE(a.camKet,0) AS camKet,
            GREATEST(s.tonConHan-COALESCE(a.camKet,0),0) AS khaDung FROM
            (SELECT c.maChiNhanh,c.tenChiNhanh,c.viDo,c.kinhDo,SUM(l.soLuongTon) AS tonConHan,MIN(l.hanSuDung) AS hanGanNhat
             FROM TonKhoTheoLo l JOIN ChiNhanh c ON c.maChiNhanh=l.maChiNhanh
             WHERE l.maNVL=? AND c.maChiNhanh<>? AND c.maChuoi='FBN' AND c.trangThai='HOAT_DONG'
               AND l.trangThai='HOAT_DONG' AND l.hanSuDung>=CURRENT_DATE AND l.soLuongTon>0
             GROUP BY c.maChiNhanh,c.tenChiNhanh,c.viDo,c.kinhDo) s
            LEFT JOIN (SELECT d.maChiNhanhNguon,SUM(t.soLuongYeuCau) AS camKet FROM DieuChuyenKho d
                JOIN ChiTietDieuChuyenKho t ON t.maDieuChuyen=d.maDieuChuyen
                WHERE t.maNVL=? AND d.trangThai='DA_DUYET' GROUP BY d.maChiNhanhNguon) a ON a.maChiNhanhNguon=s.maChiNhanh
            WHERE s.tonConHan>COALESCE(a.camKet,0) ORDER BY s.tenChiNhanh,s.maChiNhanh");
        $q->execute([$nvl,$request['maChiNhanh'],$nvl]);$rows=$q->fetchAll();
        foreach ($rows as &$row) {
            $row['khoangCach']=null;
            if ($request['viDo']!==null && $request['kinhDo']!==null && $row['viDo']!==null && $row['kinhDo']!==null) {
                $lat1=deg2rad((float)$request['viDo']);$lat2=deg2rad((float)$row['viDo']);
                $a=sin(($lat2-$lat1)/2)**2+cos($lat1)*cos($lat2)*sin(deg2rad((float)$row['kinhDo']-(float)$request['kinhDo'])/2)**2;
                $row['khoangCach']=round(6371*2*asin(sqrt(max(0,min(1,$a)))),1);
            }
        }unset($row);return $rows;
    }

    public static function lichSuDieuChuyen(): array
    {
        $scope=current_branch_id();
        $q=db()->prepare("SELECT d.maDieuChuyen,d.maYeuCau,d.ngayTao,d.trangThai,d.maNguoiXacNhanNguon,d.ngayXacNhanNguon,d.maNguoiHuy,d.thoiGianHuy,d.lyDoHuy,h.hoTen AS nguoiHuy,v.hoTen AS nguoiDuyet,s.tenChiNhanh AS tenNguon,r.tenChiNhanh AS tenNhan,
            n.tenNVL,n.donViTinh,t.soLuongYeuCau FROM DieuChuyenKho d
            JOIN ChiNhanh s ON s.maChiNhanh=d.maChiNhanhNguon JOIN ChiNhanh r ON r.maChiNhanh=d.maChiNhanhNhan
            LEFT JOIN NhanVien v ON v.maNhanVien=d.maNguoiXacNhanNguon
            LEFT JOIN NhanVien h ON h.maNhanVien=d.maNguoiHuy
            JOIN ChiTietDieuChuyenKho t ON t.maDieuChuyen=d.maDieuChuyen JOIN NguyenVatLieu n ON n.maNVL=t.maNVL
            WHERE r.maChuoi='FBN'".($scope!==null?' AND d.maChiNhanhNhan=?':'').' ORDER BY d.ngayTao DESC,d.maDieuChuyen,t.maNVL LIMIT 30');
        $q->execute($scope!==null?[$scope]:[]);return $q->fetchAll();
    }

    private static function luuDieuChuyen(string $requestId,string $nvl,string $source,string $quantity,string $operation): string
    {
        $user=self::yeuCauChuChuoi();$units=self::soLuongPhanNghin($quantity);
        if ($units<=0) { throw new DomainException('Số lượng điều chuyển phải lớn hơn 0.'); }
        foreach ([[$requestId,20],[$nvl,10],[$source,10]] as [$value,$length]) {
            if ($value==='' || strlen($value)>$length) { throw new DomainException('Thông tin yêu cầu, nguyên liệu hoặc chi nhánh không hợp lệ.'); }
        }
        if (!preg_match('/^[a-f0-9]{18}$/D',$operation)) { throw new DomainException('Mã thao tác không hợp lệ. Vui lòng mở lại biểu mẫu.'); }
        $id='DC'.$operation;$pdo=db();$pdo->beginTransaction();
        try {
            $q=$pdo->prepare('SELECT * FROM YeuCauNhapKho WHERE maYeuCau=? FOR UPDATE');$q->execute([$requestId]);$request=$q->fetch();
            if (!$request) { throw new DomainException('Không tìm thấy yêu cầu bổ sung.'); }
            // Cùng mã thao tác chỉ tạo một phương án, kể cả sau khi yêu cầu đã đủ lượng.
            $q=$pdo->prepare('SELECT d.maYeuCau,d.maChiNhanhNguon,d.trangThai,t.maNVL,t.soLuongYeuCau FROM DieuChuyenKho d JOIN ChiTietDieuChuyenKho t ON t.maDieuChuyen=d.maDieuChuyen WHERE d.maDieuChuyen=?');$q->execute([$id]);$old=$q->fetch();
            if ($old) {
                if ($old['trangThai']!=='DA_DUYET') { throw new DomainException('Phiếu của thao tác này chưa được duyệt hoặc đã bị từ chối. Vui lòng xem lại phiếu trong lịch sử.'); }
                if ($old['maYeuCau']!==$requestId || $old['maChiNhanhNguon']!==$source || $old['maNVL']!==$nvl || self::soLuongPhanNghin($old['soLuongYeuCau'])!==$units) { throw new DomainException('Thao tác này đã được sử dụng. Vui lòng mở lại biểu mẫu.'); }
                $pdo->commit();return $id;
            }
            if ($request['maChiNhanh']===$source) { throw new DomainException('Chi nhánh cung cấp phải khác chi nhánh nhận.'); }
            $q=$pdo->prepare('SELECT maChiNhanh,trangThai,maChuoi FROM ChiNhanh WHERE maChiNhanh IN (?,?) ORDER BY maChiNhanh FOR UPDATE');$q->execute([$request['maChiNhanh'],$source]);$branches=$q->fetchAll();
            if (count($branches)!==2) { throw new DomainException('Chi nhánh cung cấp hoặc chi nhánh nhận không tồn tại.'); }
            foreach ($branches as $branch) {
                if ($branch['maChuoi']!=='FBN' || $branch['trangThai']!=='HOAT_DONG') { throw new DomainException('Chi nhánh cung cấp và chi nhánh nhận phải đang hoạt động trong chuỗi.'); }
            }
            $q=$pdo->prepare('SELECT maNVL,trangThai,maChuoi FROM NguyenVatLieu WHERE maNVL=? FOR UPDATE');$q->execute([$nvl]);$material=$q->fetch();
            if (!$material || $material['trangThai']!=='HOAT_DONG' || $material['maChuoi']!=='FBN') { throw new DomainException('Nguyên liệu không tồn tại hoặc đã ngừng sử dụng.'); }
            $lines=self::nguyenLieuYeuCau($requestId);$selected=null;
            foreach ($lines as $line) { if ($line['maNVL']===$nvl) { $selected=$line;break; } }
            if (!$selected) { throw new DomainException('Nguyên liệu không thuộc yêu cầu bổ sung này.'); }
            if ($units>self::soLuongPhanNghin($selected['conThieu'])) { throw new DomainException('Số lượng vượt phần còn thiếu của yêu cầu. Vui lòng tải lại thông tin.'); }
            // Chủ chuỗi được nhóm cho quyền xác nhận thay nguồn; giữ nguyên schema V2. Khóa trước khi kiểm tra và ghi DA_DUYET.
            $q=$pdo->prepare('SELECT soLuongTon,hanSuDung,trangThai,(hanSuDung>=CURRENT_DATE) AS conHan FROM TonKhoTheoLo WHERE maChiNhanh=? AND maNVL=? ORDER BY maChiNhanh,maLo FOR UPDATE');$q->execute([$source,$nvl]);$stock=0;
            foreach ($q->fetchAll() as $lot) { if ($lot['trangThai']==='HOAT_DONG' && (int)$lot['conHan']===1) { $stock+=self::soLuongPhanNghin($lot['soLuongTon']); } }
            $q=$pdo->prepare("SELECT t.soLuongYeuCau FROM DieuChuyenKho d JOIN ChiTietDieuChuyenKho t ON t.maDieuChuyen=d.maDieuChuyen WHERE d.maChiNhanhNguon=? AND t.maNVL=? AND d.trangThai='DA_DUYET' ORDER BY d.maDieuChuyen FOR UPDATE");$q->execute([$source,$nvl]);$committed=0;
            foreach ($q->fetchAll() as $row) { $committed+=self::soLuongPhanNghin($row['soLuongYeuCau']); }
            if ($units>max(0,$stock-$committed)) { throw new DomainException('Chi nhánh cung cấp không đủ tồn kho còn hạn có thể điều chuyển.'); }
            $q=$pdo->prepare('SELECT lyDo FROM PhieuDeXuat WHERE maPhieu=?');$q->execute([$request['maPhieu']]);$reason=$q->fetchColumn();
            $q=$pdo->prepare("INSERT INTO DieuChuyenKho(maDieuChuyen,maYeuCau,maChiNhanhNhan,maChiNhanhNguon,maNguoiTao,lyDo,trangThai,maNguoiXacNhanNguon,ngayXacNhanNguon) VALUES (?,?,?,?,?,?,'DA_DUYET',?,NOW())");
            $q->execute([$id,$requestId,$request['maChiNhanh'],$source,$user['maNhanVien'],$reason,$user['maNhanVien']]);
            $pdo->prepare('INSERT INTO ChiTietDieuChuyenKho(maDieuChuyen,maNVL,soLuongYeuCau) VALUES (?,?,?)')->execute([$id,$nvl,self::soLuongThapPhan($units)]);
            $complete=true;
            // Đọc lại sau INSERT trong cùng giao dịch, tính cả phương án nhập NCC.
            foreach (self::nguyenLieuYeuCau($requestId) as $line) { if (self::soLuongPhanNghin($line['conThieu'])>0) { $complete=false;break; } }
            $pdo->prepare('UPDATE YeuCauNhapKho SET trangThai=? WHERE maYeuCau=?')->execute([$complete?'DA_LAP_PHUONG_AN':'CHO_XU_LY',$requestId]);
            $pdo->commit();return $id;
        } catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); }throw $error; }
    }

    // Chủ chuỗi duyệt phiếu đang chờ, ghi người/thời gian vào các trường V2 theo quyết định nhóm.
    private static function luuDuyetCu(string $id): string
    {
        $user=self::yeuCauChuChuoi();
        if (!preg_match('/^[A-Za-z0-9]{1,20}$/D',$id)) { throw new DomainException('Mã phiếu không hợp lệ.'); }
        $pdo=db();$q=$pdo->prepare('SELECT * FROM DieuChuyenKho WHERE maDieuChuyen=?');$q->execute([$id]);$initial=$q->fetch();
        if (!$initial) { throw new DomainException('Không tìm thấy phiếu điều chuyển.'); }
        $pdo->beginTransaction();
        try {
            $q=$pdo->prepare('SELECT * FROM YeuCauNhapKho WHERE maYeuCau=? FOR UPDATE');$q->execute([$initial['maYeuCau']]);$request=$q->fetch();
            if (!$request || $request['maChiNhanh']!==$initial['maChiNhanhNhan']) { throw new DomainException('Yêu cầu gốc không còn phù hợp.'); }
            $q=$pdo->prepare('SELECT maChiNhanh,trangThai,maChuoi FROM ChiNhanh WHERE maChiNhanh IN (?,?) ORDER BY maChiNhanh FOR UPDATE');$q->execute([$initial['maChiNhanhNguon'],$initial['maChiNhanhNhan']]);$branches=$q->fetchAll();
            if (count($branches)!==2) { throw new DomainException('Chi nhánh cung cấp hoặc nhận không còn tồn tại.'); }
            foreach ($branches as $b) { if ($b['trangThai']!=='HOAT_DONG' || $b['maChuoi']!=='FBN') { throw new DomainException('Hai chi nhánh phải đang hoạt động trong chuỗi.'); } }
            $q=$pdo->prepare('SELECT * FROM ChiTietDieuChuyenKho WHERE maDieuChuyen=? ORDER BY maNVL');$q->execute([$id]);$details=$q->fetchAll();
            if (!$details) { throw new DomainException('Phiếu chưa có nguyên liệu.'); }
            $stock=[];
            foreach ($details as $line) {
                $q=$pdo->prepare('SELECT trangThai,maChuoi FROM NguyenVatLieu WHERE maNVL=? FOR UPDATE');$q->execute([$line['maNVL']]);$m=$q->fetch();
                if (!$m || $m['trangThai']!=='HOAT_DONG' || $m['maChuoi']!=='FBN') { throw new DomainException('Nguyên liệu đã ngừng sử dụng hoặc không thuộc chuỗi.'); }
                $q=$pdo->prepare('SELECT soLuongTon,trangThai,(hanSuDung>=CURRENT_DATE) AS conHan FROM TonKhoTheoLo WHERE maChiNhanh=? AND maNVL=? ORDER BY maChiNhanh,maLo FOR UPDATE');$q->execute([$initial['maChiNhanhNguon'],$line['maNVL']]);$total=0;
                foreach ($q->fetchAll() as $lot) { if ($lot['trangThai']==='HOAT_DONG' && (int)$lot['conHan']===1) { $total+=self::soLuongPhanNghin($lot['soLuongTon']); } }
                $stock[$line['maNVL']]=$total;
            }
            $q=$pdo->prepare('SELECT * FROM DieuChuyenKho WHERE maDieuChuyen=? FOR UPDATE');$q->execute([$id]);$current=$q->fetch();
            if (!$current || $current['maYeuCau']!==$initial['maYeuCau'] || $current['maChiNhanhNguon']!==$initial['maChiNhanhNguon'] || $current['maChiNhanhNhan']!==$initial['maChiNhanhNhan']) { throw new DomainException('Phiếu đã thay đổi. Vui lòng tải lại.'); }
            if ($current['trangThai']==='DA_DUYET') { $pdo->commit();return $id; }
            if ($current['trangThai']!=='CHO_XAC_NHAN_NGUON') { throw new DomainException('Chỉ được duyệt phiếu còn chờ xác nhận.'); }
            $lines=[];foreach (self::nguyenLieuYeuCau($initial['maYeuCau']) as $line) { $lines[$line['maNVL']]=$line; }
            foreach ($details as $line) {
                $m=$line['maNVL'];$amount=self::soLuongPhanNghin($line['soLuongYeuCau']);
                if ($amount<=0 || !isset($lines[$m]) || self::soLuongPhanNghin($lines[$m]['daPhanBo'])>self::soLuongPhanNghin($lines[$m]['soLuong'])) { throw new DomainException('Lượng điều chuyển không phù hợp với yêu cầu gốc.'); }
                $q=$pdo->prepare("SELECT t.soLuongYeuCau FROM DieuChuyenKho d JOIN ChiTietDieuChuyenKho t ON t.maDieuChuyen=d.maDieuChuyen WHERE d.maChiNhanhNguon=? AND t.maNVL=? AND d.trangThai IN ('DA_DUYET') ORDER BY d.maDieuChuyen FOR UPDATE");$q->execute([$initial['maChiNhanhNguon'],$m]);$committed=0;
                foreach ($q->fetchAll() as $row) { $committed+=self::soLuongPhanNghin($row['soLuongYeuCau']); }
                if ($amount>max(0,$stock[$m]-$committed)) { throw new DomainException('Chi nhánh cung cấp không đủ tồn còn hạn để duyệt phiếu.'); }
            }
            $pdo->prepare("UPDATE DieuChuyenKho SET trangThai='DA_DUYET',maNguoiXacNhanNguon=?,ngayXacNhanNguon=NOW() WHERE maDieuChuyen=? AND trangThai='CHO_XAC_NHAN_NGUON'")->execute([$user['maNhanVien'],$id]);
            $pdo->commit();return $id;
        } catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); }throw $error; }
    }

    // Yêu cầu nhập từ nhà cung cấp.
    public static function nhaCungCap(string $nvl): array
    {
        $q=db()->prepare("SELECT n.maNCC,n.tenNCC,n.soDienThoai,n.email,n.diaChi FROM NhaCungCap n
            JOIN NhaCungCapNguyenLieu m ON m.maNCC=n.maNCC
            WHERE m.maNVL=? AND n.trangThai='HOAT_DONG' AND m.trangThai='HOAT_DONG' ORDER BY n.tenNCC,n.maNCC");
        $q->execute([$nvl]);return $q->fetchAll();
    }

    public static function lichSuNhapNCC(): array
    {
        $scope=current_branch_id();
        $q=db()->prepare("SELECT d.maDonNhap,d.maYeuCau,d.ngayTao,d.trangThai,d.maNguoiHuy,d.thoiGianHuy,d.lyDoHuy,h.hoTen AS nguoiHuy,n.tenNCC,c.tenChiNhanh,
            v.tenNVL,v.donViTinh,t.soLuong FROM DonNhapNCC d
            LEFT JOIN NhanVien h ON h.maNhanVien=d.maNguoiHuy
            JOIN NhaCungCap n ON n.maNCC=d.maNCC JOIN ChiNhanh c ON c.maChiNhanh=d.maChiNhanh
            JOIN ChiTietDonNhapNCC t ON t.maDonNhap=d.maDonNhap JOIN NguyenVatLieu v ON v.maNVL=t.maNVL
            WHERE c.maChuoi='FBN'".($scope!==null?' AND d.maChiNhanh=?':'').' ORDER BY d.ngayTao DESC,d.maDonNhap,t.maNVL LIMIT 30');
        $q->execute($scope!==null?[$scope]:[]);return $q->fetchAll();
    }

    private static function luuNhapNCC(string $requestId,string $nvl,string $supplier,string $quantity,string $operation): string
    {
        $user=self::yeuCauChuChuoi();$units=self::soLuongPhanNghin($quantity);
        if ($units<=0) { throw new DomainException('Số lượng cần nhập phải lớn hơn 0.'); }
        foreach ([[$requestId,20],[$nvl,10],[$supplier,10]] as [$value,$length]) {
            if ($value==='' || strlen($value)>$length) { throw new DomainException('Thông tin yêu cầu, nguyên liệu hoặc nhà cung cấp không hợp lệ.'); }
        }
        if (!preg_match('/^[a-f0-9]{18}$/D',$operation)) { throw new DomainException('Mã thao tác không hợp lệ. Vui lòng mở lại biểu mẫu.'); }
        $id='DN'.$operation;$pdo=db();$pdo->beginTransaction();
        try {
            // Khóa chung yêu cầu với SD-17: không phân bổ quá lượng khi hai phương án chạy đồng thời.
            $q=$pdo->prepare('SELECT * FROM YeuCauNhapKho WHERE maYeuCau=? FOR UPDATE');$q->execute([$requestId]);$request=$q->fetch();
            if (!$request) { throw new DomainException('Không tìm thấy yêu cầu bổ sung.'); }
            $q=$pdo->prepare('SELECT d.maYeuCau,d.maNCC,d.trangThai,t.maNVL,t.soLuong FROM DonNhapNCC d JOIN ChiTietDonNhapNCC t ON t.maDonNhap=d.maDonNhap WHERE d.maDonNhap=?');$q->execute([$id]);$old=$q->fetch();
            if ($old) {
                if ($old['trangThai']!=='CHO_NHAP_HANG') { throw new DomainException('Phiếu của thao tác này đã hủy. Vui lòng mở lại biểu mẫu để lập phiếu mới.'); }
                if ($old['maYeuCau']!==$requestId || $old['maNCC']!==$supplier || $old['maNVL']!==$nvl || self::soLuongPhanNghin($old['soLuong'])!==$units) { throw new DomainException('Thao tác này đã được sử dụng. Vui lòng mở lại biểu mẫu.'); }
                $pdo->commit();return $id;
            }
            $q=$pdo->prepare('SELECT trangThai,maChuoi FROM ChiNhanh WHERE maChiNhanh=? FOR UPDATE');$q->execute([$request['maChiNhanh']]);$branch=$q->fetch();
            if (!$branch || $branch['maChuoi']!=='FBN' || $branch['trangThai']!=='HOAT_DONG') { throw new DomainException('Chi nhánh nhận không tồn tại hoặc đã ngừng hoạt động.'); }
            $q=$pdo->prepare('SELECT trangThai,maChuoi FROM NguyenVatLieu WHERE maNVL=? FOR UPDATE');$q->execute([$nvl]);$material=$q->fetch();
            if (!$material || $material['maChuoi']!=='FBN' || $material['trangThai']!=='HOAT_DONG') { throw new DomainException('Nguyên liệu không tồn tại hoặc đã ngừng sử dụng.'); }
            $selected=null;
            foreach (self::nguyenLieuYeuCau($requestId) as $line) { if ($line['maNVL']===$nvl) { $selected=$line;break; } }
            if (!$selected) { throw new DomainException('Nguyên liệu không thuộc yêu cầu bổ sung này.'); }
            if ($units>self::soLuongPhanNghin($selected['conThieu'])) { throw new DomainException('Số lượng vượt phần còn thiếu của yêu cầu. Vui lòng tải lại thông tin.'); }
            $q=$pdo->prepare('SELECT trangThai FROM NhaCungCap WHERE maNCC=? FOR UPDATE');$q->execute([$supplier]);$supplierStatus=$q->fetchColumn();
            if ($supplierStatus!=='HOAT_DONG') { throw new DomainException('Nhà cung cấp không tồn tại hoặc đã ngừng hoạt động.'); }
            $q=$pdo->prepare('SELECT trangThai FROM NhaCungCapNguyenLieu WHERE maNCC=? AND maNVL=? FOR UPDATE');$q->execute([$supplier,$nvl]);
            if ($q->fetchColumn()!=='HOAT_DONG') { throw new DomainException('Nhà cung cấp không cung cấp nguyên liệu này hoặc đã ngừng cung cấp.'); }
            $q=$pdo->prepare('SELECT lyDo FROM PhieuDeXuat WHERE maPhieu=?');$q->execute([$request['maPhieu']]);$reason=$q->fetchColumn();
            $q=$pdo->prepare("INSERT INTO DonNhapNCC(maDonNhap,maYeuCau,maChiNhanh,maNCC,maNguoiLap,ghiChu,trangThai) VALUES (?,?,?,?,?,?,'CHO_NHAP_HANG')");
            $q->execute([$id,$requestId,$request['maChiNhanh'],$supplier,$user['maNhanVien'],$reason]);
            $pdo->prepare('INSERT INTO ChiTietDonNhapNCC(maDonNhap,maNVL,soLuong) VALUES (?,?,?)')->execute([$id,$nvl,self::soLuongThapPhan($units)]);
            $complete=true;
            foreach (self::nguyenLieuYeuCau($requestId) as $line) { if (self::soLuongPhanNghin($line['conThieu'])>0) { $complete=false;break; } }
            $pdo->prepare('UPDATE YeuCauNhapKho SET trangThai=? WHERE maYeuCau=?')->execute([$complete?'DA_LAP_PHUONG_AN':'CHO_XU_LY',$requestId]);
            $pdo->commit();return $id;
        } catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); }throw $error; }
    }


    // Hủy phương án đã lập.
    public static function loaiPhieu(string $kind): array
    {
        return match ($kind) {
            'transfer'=>['DieuChuyenKho','maDieuChuyen','maChiNhanhNhan','DA_DUYET','phiếu điều chuyển'],
            'supplier'=>['DonNhapNCC','maDonNhap','maChiNhanh','CHO_NHAP_HANG','yêu cầu nhập từ nhà cung cấp'],
            default=>throw new DomainException('Loại phiếu không hợp lệ.')
        };
    }

    public static function chiTietPhieu(string $kind,string $id): array
    {
        self::yeuCauChuChuoi();
        [$table,$key,$branchKey]=self::loaiPhieu($kind);
        if (!preg_match('/^[A-Za-z0-9]{1,20}$/D',$id)) { throw new DomainException('Mã phiếu không hợp lệ.'); }
        $q=db()->prepare("SELECT d.*,c.tenChiNhanh FROM $table d JOIN ChiNhanh c ON c.maChiNhanh=d.$branchKey WHERE d.$key=? AND c.maChuoi='FBN'");
        $q->execute([$id]);$row=$q->fetch();
        if (!$row) { throw new DomainException('Không tìm thấy phiếu trong chuỗi.'); }
        if ($kind==='transfer') {
            $sql='SELECT t.maNVL,n.tenNVL,n.donViTinh,t.soLuongYeuCau AS soLuong FROM ChiTietDieuChuyenKho t JOIN NguyenVatLieu n ON n.maNVL=t.maNVL WHERE t.maDieuChuyen=? ORDER BY t.maNVL';
        } else {
            $sql='SELECT t.maNVL,n.tenNVL,n.donViTinh,t.soLuong FROM ChiTietDonNhapNCC t JOIN NguyenVatLieu n ON n.maNVL=t.maNVL WHERE t.maDonNhap=? ORDER BY t.maNVL';
        }
        $q=db()->prepare($sql);$q->execute([$id]);$row['details']=$q->fetchAll();return $row;
    }

    // Module hiện chỉ lập phương án, chưa có xuất/nhận hàng gắn với mã phiếu.
    // Khi nhóm nối quy trình thực hiện kho, phải kiểm tra chứng từ thực hiện tại đây
    // trong cùng giao dịch; không suy diễn từ tổng tồn kho hay ngày giao dịch.
    private static function luuHuy(string $kind,string $id,string $reason,bool $confirmed): string
    {
        $user=self::yeuCauChuChuoi();
        [$table,$key,$branchKey,$allowed]=self::loaiPhieu($kind);
        $reason=trim($reason);
        if (!$confirmed) { throw new DomainException('Vui lòng xác nhận phiếu chưa xuất hoặc nhận hàng.'); }
        if ($reason==='' || mb_strlen($reason,'UTF-8')>500) { throw new DomainException('Nhập lý do hủy từ 1 đến 500 ký tự.'); }
        $initial=self::chiTietPhieu($kind,$id);$pdo=db();$pdo->beginTransaction();
        try {
            // Khóa cùng yêu cầu như hai luồng tạo: tính lại lượng phân bổ được tuần tự hóa.
            $q=$pdo->prepare('SELECT * FROM YeuCauNhapKho WHERE maYeuCau=? FOR UPDATE');$q->execute([$initial['maYeuCau']]);$request=$q->fetch();
            if (!$request) { throw new DomainException('Không tìm thấy yêu cầu gốc.'); }
            $q=$pdo->prepare("SELECT * FROM $table WHERE $key=? FOR UPDATE");$q->execute([$id]);$current=$q->fetch();
            if (!$current || $current['maYeuCau']!==$request['maYeuCau'] || $current[$branchKey]!==$request['maChiNhanh']) { throw new DomainException('Phiếu đã thay đổi. Vui lòng tải lại.'); }
            if ($current['trangThai']==='DA_HUY') { $pdo->commit();return $request['maYeuCau']; }
            if ($current['trangThai']!==$allowed) { throw new DomainException('Trạng thái hiện tại không cho phép hủy phiếu này.'); }
            $q=$pdo->prepare("UPDATE $table SET trangThai='DA_HUY',maNguoiHuy=?,thoiGianHuy=NOW(),lyDoHuy=? WHERE $key=? AND trangThai=?");
            $q->execute([$user['maNhanVien'],$reason,$id,$allowed]);
            if ($q->rowCount()!==1) { throw new DomainException('Phiếu đã thay đổi. Vui lòng tải lại.'); }
            $complete=true;
            foreach (self::nguyenLieuYeuCau($request['maYeuCau']) as $line) { if (self::soLuongPhanNghin($line['conThieu'])>0) { $complete=false;break; } }
            $pdo->prepare('UPDATE YeuCauNhapKho SET trangThai=? WHERE maYeuCau=?')->execute([$complete?'DA_LAP_PHUONG_AN':'CHO_XU_LY',$request['maYeuCau']]);
            $pdo->commit();return $request['maYeuCau'];
        } catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); }throw $error; }
    }

    private static function ghi(callable $congViec,string $thongBao,string $loiMayChu): array
    {
        try {
            return ['ok'=>true,'code'=>'OK','message'=>$thongBao,'data'=>$congViec()];
        } catch (DomainException $loi) {
            return ['ok'=>false,'code'=>'VALIDATION_ERROR','message'=>$loi->getMessage(),'data'=>[]];
        } catch (PDOException $loi) {
            error_log('FBN nguyen-lieu: '.$loi->getMessage());
            return ['ok'=>false,'code'=>'SERVER_ERROR','message'=>$loiMayChu,'data'=>[]];
        }
    }
}
