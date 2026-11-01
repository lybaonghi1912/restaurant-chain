<?php
declare(strict_types=1);

namespace Fbn\Reservations;

require_once dirname(__DIR__).'/BookingError.php';

final class DatBan
{
    public function __construct(private \PDO $pdo) {}
    public function lay(string $id,bool $lock=false): ?array
    {
        $s=$this->pdo->prepare('SELECT * FROM DatBan WHERE maDatBan=?'.($lock?' FOR UPDATE':''));
        $s->execute([$id]);return $s->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    public function tao(array $d): void
    {
        $cols=array_keys($d);
        $sql='INSERT INTO DatBan('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
        $this->pdo->prepare($sql)->execute(array_values($d));
    }
    public function coLichTrung(string $table,string $start,string $end): bool
    {
        // Locking read đọc dữ liệu mới nhất sau khi chờ khóa Ban, không dùng snapshot cũ.
        $s=$this->pdo->prepare("SELECT maDatBan FROM DatBan WHERE maBan=? AND (trangThai='DANG_PHUC_VU' OR (trangThai='DA_XAC_NHAN' AND (thoiGianKhachDen IS NOT NULL OR (thoiGianDen<=? AND hanGiuCho>=?)))) ORDER BY maDatBan FOR UPDATE");
        $s->execute([$table,$end,$start]);return $s->fetchColumn()!==false;
    }
    public function xacNhan(string $id,string $table,string $staff): void
    {
        $s=$this->pdo->prepare("UPDATE DatBan SET maBan=?,thoiGianKetThucDuKien=NULL,trangThai='DA_XAC_NHAN',maNguoiXacNhan=? WHERE maDatBan=? AND trangThai='CHO_XAC_NHAN'");
        $s->execute([$table,$staff,$id]);if($s->rowCount()!==1)throw new BookingError('CONFLICT','Yêu cầu đã thay đổi trạng thái. Vui lòng tải lại.',409);
    }
    public function huy(string $id,string $reason,?string $staff): void
    {
        $this->pdo->prepare("UPDATE DatBan SET trangThai='DA_HUY',lyDoHuy=?,maNguoiHuy=? WHERE maDatBan=?")->execute([$reason,$staff,$id]);
    }
    public function tuHuyQuaHan(string $now): int
    {
        $s=$this->pdo->prepare("UPDATE DatBan SET trangThai='DA_HUY',lyDoHuy='Tự động hủy: khách chưa đến trước hạn giữ chỗ hoặc giờ đóng cửa',maNguoiHuy=NULL WHERE trangThai='DA_XAC_NHAN' AND thoiGianKhachDen IS NULL AND (hanGiuCho<? OR (TIME(hanGiuCho)='22:00:00' AND hanGiuCho<=?))");
        $s->execute([$now,$now]);return $s->rowCount();
    }
    public function ghiNhanKhachDen(string $id,string $time,bool $waiting,string $seatedAt): void
    {
        $s=$this->pdo->prepare("UPDATE DatBan SET thoiGianKhachDen=?,thoiGianNhanBan=?,trangThai=? WHERE maDatBan=?");
        $s->execute([$time,$waiting?null:$seatedAt,$waiting?'DA_XAC_NHAN':'DANG_PHUC_VU',$id]);
    }
    public function traCuu(?string $branch,string $term,string $date,string $status): array
    {
        $where=[];$args=[];
        if($branch!==null){$where[]='d.maChiNhanh=?';$args[]=$branch;}
        if($term!==''){
            // Escape ký tự LIKE bằng dấu = để %/_ do người dùng nhập chỉ là chữ.
            $word='%'.str_replace(['=','%','_'],['==','=%','=_'],$term).'%';
            $where[]="(d.tenKhach LIKE ? ESCAPE '=' OR d.soDienThoai LIKE ? ESCAPE '=' OR d.maDatBan LIKE ? ESCAPE '=')";
            array_push($args,$word,$word,$word);
        }
        if($date!==''){$where[]='d.thoiGianDen>=? AND d.thoiGianDen<?';$args[]=$date.' 00:00:00';$args[]=(new \DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d').' 00:00:00';}
        if($status==='active'){$where[]="d.trangThai IN ('CHO_XAC_NHAN','DA_XAC_NHAN')";}
        elseif($status!==''){$where[]='d.trangThai=?';$args[]=$status;}
        $s=$this->pdo->prepare('SELECT d.*,c.tenChiNhanh,b.tenBan,b.sucChua FROM DatBan d JOIN ChiNhanh c ON c.maChiNhanh=d.maChiNhanh LEFT JOIN Ban b ON b.maBan=d.maBan WHERE '.($where?implode(' AND ',$where):'1=1').' ORDER BY d.thoiGianDen DESC,d.maDatBan DESC LIMIT 200');
        $s->execute($args);return $s->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function theoSoDienThoai(string $phone): array
    {
        $s=$this->pdo->prepare('SELECT d.maDatBan,c.tenChiNhanh,d.thoiGianDen,d.soKhach,d.trangThai,d.hanGiuCho,d.thoiGianKhachDen,b.tenBan FROM DatBan d JOIN ChiNhanh c ON c.maChiNhanh=d.maChiNhanh LEFT JOIN Ban b ON b.maBan=d.maBan WHERE d.soDienThoai=? ORDER BY d.thoiGianDen DESC,d.maDatBan DESC LIMIT 100');
        $s->execute([$phone]);return $s->fetchAll(\PDO::FETCH_ASSOC);
    }
}
