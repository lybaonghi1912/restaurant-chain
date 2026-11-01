<?php
declare(strict_types=1);

namespace Fbn\Reservations;

final class ChiNhanh
{
    public function __construct(private \PDO $pdo) {}
    public function danhSach(): array
    {
        return $this->pdo->query("SELECT maChiNhanh,tenChiNhanh FROM ChiNhanh WHERE trangThai='HOAT_DONG' ORDER BY maChiNhanh")->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function dangHoatDong(string $id, bool $lock=false): bool
    {
        $s=$this->pdo->prepare('SELECT trangThai FROM ChiNhanh WHERE maChiNhanh=?'.($lock?' FOR SHARE':''));
        $s->execute([$id]);return $s->fetchColumn()==='HOAT_DONG';
    }
}
