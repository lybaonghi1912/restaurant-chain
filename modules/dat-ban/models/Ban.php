<?php
declare(strict_types=1);

namespace Fbn\Reservations;

final class Ban
{
    public function __construct(private \PDO $pdo) {}
    public function lay(string $id,bool $lock=false): ?array
    {
        $s=$this->pdo->prepare('SELECT * FROM Ban WHERE maBan=?'.($lock?' FOR UPDATE':''));
        $s->execute([$id]);return $s->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    public function danhSach(?string $branch): array
    {
        $s=$this->pdo->prepare('SELECT b.*,c.tenChiNhanh FROM Ban b JOIN ChiNhanh c ON c.maChiNhanh=b.maChiNhanh WHERE c.trangThai=\'HOAT_DONG\''.($branch===null?'':' AND b.maChiNhanh=?').' ORDER BY b.maChiNhanh,b.soBan');
        $s->execute($branch===null?[]:[$branch]);return $s->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function danhSachPhuHop(string $branch,int $guests,string $start,string $end,bool $now=false): array
    {
        $sql="SELECT b.* FROM Ban b WHERE b.maChiNhanh=? AND b.sucChua>=? AND b.trangThai<>'NGUNG'";
        if($now)$sql.=" AND b.trangThai='TRONG'";
        $sql.=" AND NOT EXISTS (SELECT 1 FROM DatBan d WHERE d.maBan=b.maBan AND (d.trangThai='DANG_PHUC_VU' OR (d.trangThai='DA_XAC_NHAN' AND (d.thoiGianKhachDen IS NOT NULL OR (d.thoiGianDen<=? AND d.hanGiuCho>=?))))) ORDER BY b.sucChua,b.soBan";
        $s=$this->pdo->prepare($sql);$s->execute([$branch,$guests,$end,$start]);return $s->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function capNhatDangPhucVu(string $id): void
    {
        $this->pdo->prepare("UPDATE Ban SET trangThai='DANG_PHUC_VU' WHERE maBan=?")->execute([$id]);
    }
}
