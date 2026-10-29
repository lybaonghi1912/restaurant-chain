<?php
declare(strict_types=1);

namespace Fbn\Reservations;

require_once __DIR__.'/Entities.php';

final class BookingError extends \RuntimeException
{
    public function __construct(public readonly string $errorCode,string $message,public readonly int $status=422,public readonly array $fields=[]){parent::__construct($message);}
}

/** Điều phối nghiệp vụ SD-1..6; transaction bao trọn các Entity liên quan. */
final class DatBanControl
{
    private DatBanEntity $don;
    private BanEntity $ban;
    private ChiNhanhEntity $chiNhanh;
    public const STATES=['CHO_XAC_NHAN'=>'Chờ xác nhận','DA_XAC_NHAN'=>'Đã xác nhận','DANG_PHUC_VU'=>'Đang phục vụ','DA_HOAN_TAT'=>'Đã hoàn tất','DA_HUY'=>'Đã hủy'];
    public function __construct(private \PDO $pdo)
    {
        $this->don=new DatBanEntity($pdo);$this->ban=new BanEntity($pdo);$this->chiNhanh=new ChiNhanhEntity($pdo);
    }
    public function danhSachChiNhanh(): array {return $this->chiNhanh->danhSach();}
    public function danhSachBan(?array $actor,?string $branch=null): array {return $this->ban->danhSach($this->phamVi($actor,$branch));}
    private function now(): \DateTimeImmutable {return new \DateTimeImmutable('now',new \DateTimeZone('Asia/Ho_Chi_Minh'));}
    private function text(array $data,string $key,int $max,bool $required=false): string
    {
        $v=is_string($data[$key]??null)?trim($data[$key]):'';
        if(!mb_check_encoding($v,'UTF-8') || ($required && $v==='') || mb_strlen($v)>$max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$v))throw new BookingError('VALIDATION_ERROR','Vui lòng kiểm tra thông tin đã nhập.',422,[$key=>"Trường này ".($required?'bắt buộc và ':'')."không quá $max ký tự."]);
        return $v;
    }
    private function guests(array $data): int
    {
        $v=$data['soKhach']??'';
        if(!is_scalar($v)||!preg_match('/^[1-9][0-9]*$/D',(string)$v)||(int)$v>32767)throw new BookingError('VALIDATION_ERROR','Số khách phải là số nguyên dương, tối đa 32767.',422,['soKhach'=>'Nhập số nguyên dương hợp lệ.']);
        return (int)$v;
    }
    public function soDienThoai(string $raw,bool $optional=false): string
    {
        if(!mb_check_encoding($raw,'UTF-8'))throw new BookingError('VALIDATION_ERROR','Số điện thoại không hợp lệ.');
        $p=preg_replace('/[\s.\-]+/u','',trim($raw));
        if($optional && $p==='')return '';
        if(!preg_match('/^0[0-9]{9}$/D',$p))throw new BookingError('VALIDATION_ERROR','Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.',422,['soDienThoai'=>'Nhập số điện thoại hợp lệ.']);
        return $p;
    }
    private function time(string $raw,string $field): \DateTimeImmutable
    {
        $t=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$raw,new \DateTimeZone('Asia/Ho_Chi_Minh'));$errors=\DateTimeImmutable::getLastErrors();
        if(!$t || ($errors && ($errors['warning_count']||$errors['error_count'])) || $t->format('Y-m-d\TH:i')!==$raw)throw new BookingError('VALIDATION_ERROR','Ngày giờ không hợp lệ.',422,[$field=>'Chọn ngày giờ hợp lệ.']);
        return $t;
    }
    private function phamVi(?array $actor,?string $branch=null): ?string
    {
        if(!$actor || !in_array($actor['maVaiTro']??'',['PHUC_VU','QUAN_LY','CHU_CHUOI'],true))throw new BookingError('FORBIDDEN','Bạn không có quyền xem đặt bàn.',403);
        if($actor['maVaiTro']==='CHU_CHUOI')return $branch;
        $own=$actor['maChiNhanh']??'';
        if(!$own || ($branch!==null && $own!==$branch))throw new BookingError('FORBIDDEN','Bạn không được truy cập chi nhánh khác.',403);
        return $own;
    }
    private function staff(?array $actor): array
    {
        if(!$actor || ($actor['maVaiTro']??'')!=='PHUC_VU' || empty($actor['maNhanVien']) || empty($actor['maChiNhanh']))throw new BookingError('FORBIDDEN','Chỉ nhân viên phục vụ được xử lý đặt bàn tại chi nhánh của mình.',403);
        return $actor;
    }
    private function access(array $d,array $actor): void
    {
        if($d['maChiNhanh']!==$actor['maChiNhanh'])throw new BookingError('FORBIDDEN','Bạn không được xử lý đặt bàn của chi nhánh khác.',403);
    }
    private function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try{$r=$fn();$this->pdo->commit();return $r;}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }
    private function branch(string $id,bool $lock=false): void
    {
        if(!$this->chiNhanh->dangHoatDong($id,$lock))throw new BookingError('VALIDATION_ERROR','Chi nhánh không tồn tại hoặc đã ngừng hoạt động.',422,['maChiNhanh'=>'Chọn chi nhánh đang hoạt động.']);
    }
    private function found(string $id,bool $lock=false): array
    {
        $d=$this->don->lay($id,$lock);
        if(!$d)throw new BookingError('NOT_FOUND','Không tìm thấy yêu cầu đặt bàn.',404);
        return $d;
    }
    /** SD-1: chưa gán/giữ bàn. */
    public function taoDonDatBan(array $data): array
    {
        $branch=$this->text($data,'maChiNhanh',10,true);$name=$this->text($data,'tenKhach',100,true);
        $phone=$this->soDienThoai($this->text($data,'soDienThoai',30,true));$guests=$this->guests($data);
        $start=$this->time($this->text($data,'thoiGianDen',16,true),'thoiGianDen');$note=$this->text($data,'ghiChu',500);
        if($start<=$this->now())throw new BookingError('VALIDATION_ERROR','Ngày giờ đến phải ở tương lai.',422,['thoiGianDen'=>'Chọn thời gian trong tương lai.']);
        return $this->transaction(function()use($branch,$name,$phone,$guests,$start,$note){
            $this->branch($branch,true);$id='DB'.strtoupper(bin2hex(random_bytes(9)));
            $this->don->tao(['maDatBan'=>$id,'maChiNhanh'=>$branch,'tenKhach'=>$name,'soDienThoai'=>$phone,'soKhach'=>$guests,'thoiGianDen'=>$start->format('Y-m-d H:i:s'),'ghiChu'=>$note?:null]);
            return $this->found($id);
        });
    }
    /** SD-2: chỉ trả các trường khách cần xem, không trả hồ sơ/ghi chú nội bộ. */
    public function theoDoiTrangThai(string $phone): array {return $this->don->theoSoDienThoai($this->soDienThoai($phone));}
    /** SD-5. */
    public function traCuuDatBan(?array $actor,string $term='',string $date='',string $status='',?string $branch=null): array
    {
        $scope=$this->phamVi($actor,$branch);
        if(mb_strlen($term)>100)throw new BookingError('VALIDATION_ERROR','Từ khóa tối đa 100 ký tự.');
        if($date!=='' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))))throw new BookingError('VALIDATION_ERROR','Ngày lọc không hợp lệ.');
        if($status!=='' && !array_key_exists($status,self::STATES))throw new BookingError('VALIDATION_ERROR','Trạng thái lọc không hợp lệ.');
        return $this->don->traCuu($scope,trim($term),$date,$status);
    }
    public function chiTiet(?array $actor,string $id,?string $branch=null): array
    {
        $scope=$this->phamVi($actor,$branch);$d=$this->found($id);
        if($scope!==null && $d['maChiNhanh']!==$scope)throw new BookingError('FORBIDDEN','Bạn không được xem đặt bàn chi nhánh khác.',403);
        return $d;
    }
    public function kiemTraBanPhuHop(?array $actor,string $id,string $end): array
    {
        $actor=$this->staff($actor);$d=$this->found($id);$this->access($d,$actor);$this->branch($d['maChiNhanh']);
        $until=$this->time($end,'thoiGianKetThucDuKien');
        if($d['trangThai']!=='CHO_XAC_NHAN')throw new BookingError('CONFLICT','Chỉ kiểm tra xác nhận yêu cầu đang chờ.',409);
        if($until->format('Y-m-d H:i:s')<=$d['thoiGianDen'])throw new BookingError('VALIDATION_ERROR','Giờ kết thúc phải sau giờ đến.');
        return $this->ban->danhSachPhuHop($d['maChiNhanh'],(int)$d['soKhach'],$d['thoiGianDen'],$until->format('Y-m-d H:i:s'));
    }
    /** SD-3: khóa ChiNhanh → Ban → DatBan, kiểm tra lại rồi ghi trong một transaction. */
    public function xacNhanDatBan(?array $actor,string $id,string $table,string $end): array
    {
        $actor=$this->staff($actor);$until=$this->time($end,'thoiGianKetThucDuKien');
        return $this->transaction(function()use($actor,$id,$table,$until){
            $this->branch($actor['maChiNhanh'],true);
            $b=$this->ban->lay($table,true);$d=$this->found($id,true);$this->access($d,$actor);
            if($d['trangThai']!=='CHO_XAC_NHAN')throw new BookingError('CONFLICT','Yêu cầu không còn ở trạng thái Chờ xác nhận.',409);
            if($d['thoiGianDen']<=$this->now()->format('Y-m-d H:i:s'))throw new BookingError('CONFLICT','Yêu cầu đã quá giờ đến; liên hệ khách và hủy nếu không tiếp tục.',409);
            if($until->format('Y-m-d H:i:s')<=$d['thoiGianDen'])throw new BookingError('VALIDATION_ERROR','Giờ kết thúc phải sau giờ đến.');
            if(!$b || $b['maChiNhanh']!==$d['maChiNhanh'])throw new BookingError('VALIDATION_ERROR','Bàn không thuộc chi nhánh của yêu cầu.');
            if($b['trangThai']==='NGUNG' || (int)$b['sucChua']<(int)$d['soKhach'])throw new BookingError('CONFLICT','Bàn không đủ sức chứa hoặc đã ngừng sử dụng.',409);
            if($this->don->coLichTrung($table,$d['thoiGianDen'],$until->format('Y-m-d H:i:s')))throw new BookingError('CONFLICT','Bàn không còn trống trong khung giờ này. Vui lòng chọn bàn khác.',409);
            $this->don->xacNhan($id,$table,$until->format('Y-m-d H:i:s'),$actor['maNhanVien']);
            return $this->found($id);
        });
    }
    /** SD-4: tiếp nhận ngay; không tự tạo đơn gọi món. */
    public function taoDonTaiQuay(?array $actor,array $data): array
    {
        $actor=$this->staff($actor);$table=$this->text($data,'maBan',10,true);$guests=$this->guests($data);
        $name=$this->text($data,'tenKhach',100);$phone=$this->soDienThoai($this->text($data,'soDienThoai',30),true);$note=$this->text($data,'ghiChu',500);
        $until=$this->time($this->text($data,'thoiGianKetThucDuKien',16,true),'thoiGianKetThucDuKien');
        return $this->transaction(function()use($actor,$table,$guests,$name,$phone,$note,$until){
            $this->branch($actor['maChiNhanh'],true);$b=$this->ban->lay($table,true);$start=$this->now();
            if($until<=$start)throw new BookingError('VALIDATION_ERROR','Giờ kết thúc phải sau thời điểm tiếp nhận hiện tại.');
            if(!$b || $b['maChiNhanh']!==$actor['maChiNhanh'])throw new BookingError('VALIDATION_ERROR','Bàn không thuộc chi nhánh của bạn.');
            if($b['trangThai']!=='TRONG' || (int)$b['sucChua']<$guests)throw new BookingError('CONFLICT','Bàn không trống hoặc không đủ sức chứa.',409);
            if($this->don->coLichTrung($table,$start->format('Y-m-d H:i:s'),$until->format('Y-m-d H:i:s')))throw new BookingError('CONFLICT','Bàn đã có lịch trong thời gian phục vụ dự kiến.',409);
            $id='DB'.strtoupper(bin2hex(random_bytes(9)));
            $this->don->tao(['maDatBan'=>$id,'maChiNhanh'=>$actor['maChiNhanh'],'maBan'=>$table,'nguon'=>'TAI_QUAY','tenKhach'=>$name?:null,'soDienThoai'=>$phone?:null,'soKhach'=>$guests,'thoiGianDen'=>$start->format('Y-m-d H:i:s'),'thoiGianKetThucDuKien'=>$until->format('Y-m-d H:i:s'),'thoiGianNhanBan'=>$start->format('Y-m-d H:i:s'),'trangThai'=>'DANG_PHUC_VU','maNguoiTao'=>$actor['maNhanVien'],'ghiChu'=>$note?:null]);
            $this->ban->capNhatDangPhucVu($table);return $this->found($id);
        });
    }
    /** SD-6: giữ lịch sử, chỉ giải phóng khoảng của đơn bị hủy. */
    public function huyDatBan(?array $actor,string $id,string $reason): array
    {
        $actor=$this->staff($actor);$reason=$this->text(['lyDoHuy'=>$reason],'lyDoHuy',255,true);
        // Đọc gợi ý trước; sau khóa phải đọc lại, không tin trạng thái đã đọc.
        $hint=$this->found($id);$this->access($hint,$actor);
        return $this->transaction(function()use($actor,$id,$reason,$hint){
            $this->branch($actor['maChiNhanh'],true);
            if($hint['maBan']!==null)$this->ban->lay($hint['maBan'],true);
            $d=$this->found($id,true);$this->access($d,$actor);
            if($d['maBan']!==$hint['maBan'])throw new BookingError('CONFLICT','Yêu cầu vừa được gán bàn. Vui lòng tải lại rồi hủy.',409);
            if($d['trangThai']==='DA_HUY')return $d;
            if(!in_array($d['trangThai'],['CHO_XAC_NHAN','DA_XAC_NHAN'],true))throw new BookingError('CONFLICT','Chỉ hủy yêu cầu Chờ xác nhận hoặc Đã xác nhận.',409);
            $this->don->huy($id,$reason,$actor['maNhanVien']);
            // Không UPDATE Ban=TRONG: lịch đã hủy không chiếm chỗ, bàn vật lý có thể đang phục vụ khách khác.
            return $this->found($id);
        });
    }
}
