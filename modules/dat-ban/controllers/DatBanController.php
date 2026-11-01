<?php
declare(strict_types=1);

namespace Fbn\Reservations;

require_once dirname(__DIR__).'/models/DatBan.php';
require_once dirname(__DIR__).'/models/ChiNhanh.php';
require_once dirname(__DIR__).'/models/Ban.php';
require_once dirname(__DIR__).'/BookingError.php';

/** Nhận yêu cầu HTTP, kiểm tra nghiệp vụ và điều phối các Model dùng chung PDO. */
final class DatBanController
{
    private DatBan $don;
    private Ban $ban;
    private ChiNhanh $chiNhanh;
    public const STATES=['CHO_XAC_NHAN'=>'Chờ xác nhận','DA_XAC_NHAN'=>'Đã xác nhận','DANG_PHUC_VU'=>'Đang phục vụ','DA_HOAN_TAT'=>'Đã hoàn tất','DA_HUY'=>'Đã hủy'];
    public function __construct(private \PDO $pdo, private ?\Closure $clock=null)
    {
        $this->don=new DatBan($pdo);$this->ban=new Ban($pdo);$this->chiNhanh=new ChiNhanh($pdo);
    }

    /** Điểm vào duy nhất của ba màn hình thuộc module đặt bàn. */
    public function hienThiTrang(string $pageKey): void
    {
        $views=['booking'=>'dat-ban.php','tracking'=>'theo-doi.php','floor'=>'quan-ly.php'];
        if(!isset($views[$pageKey])){http_response_code(404);return;}
        require_once dirname(__DIR__).'/views/helpers.php';
        require_once APP_ROOT.'/layouts/customer-shell.php';

        $this->tuHuyQuaHan();
        $reservationFlash=$_SESSION['reservation_flash']??null;
        unset($_SESSION['reservation_flash']);
        $result=$this->xuLyPost($pageKey);
        $reservationError=$result['reservationError'];
        $reservationFields=$result['reservationFields'];
        $oldInput=array_filter($_POST,'is_string');
        $postAction=post_text('action');
        $stateLabels=self::STATES;

        if($pageKey==='booking'){
            $bookingBranches=$this->danhSachChiNhanh();
            $sentBooking=is_array($reservationFlash['booking']??null)?$reservationFlash['booking']:null;
            $sentBranchName='';
            if($sentBooking){
                $sentBranchName=$sentBooking['maChiNhanh'];
                foreach($bookingBranches as $branch){
                    if($branch['maChiNhanh']===$sentBooking['maChiNhanh']){$sentBranchName=$branch['tenChiNhanh'];break;}
                }
            }
            $createRequestKey=$this->khoaBieuMau('web_create');
            $arrivalMin=date('Y-m-d\TH:i');
        }elseif($pageKey==='tracking'){
            $trackingRows=[];$trackingSearched=false;
            if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && post_text('action')==='track' && !$reservationError){
                try{
                    // Giới hạn đơn giản theo phiên cho cổng tra cứu công khai; không lưu SĐT trong URL.
                    $window=$_SESSION['reservation_lookup_window']??['start'=>time(),'count'=>0];
                    if(time()-$window['start']>=60)$window=['start'=>time(),'count'=>0];
                    $window['count']++;$_SESSION['reservation_lookup_window']=$window;
                    if($window['count']>20)throw new \Fbn\Reservations\BookingError('RATE_LIMITED','Bạn tra cứu quá nhanh. Vui lòng thử lại sau một phút.',429);
                    $trackingRows=$this->theoDoiTrangThai(post_text('soDienThoai'));$trackingSearched=true;
                }catch(\Fbn\Reservations\BookingError $error){$reservationError=$error->getMessage();$reservationFields=$error->fields;http_response_code($error->status);}
            }
        }else{
            $term=is_string($_GET['q']??null)?$_GET['q']:'';$day=is_string($_GET['date']??null)?$_GET['date']:'';$state=is_string($_GET['status']??null)?$_GET['status']:'active';
            $selected=is_string($_GET['reservation']??null)?$_GET['reservation']:post_text('maDatBan');
            $check=isset($_GET['check_tables']);
            $rows=[];$tables=[];$detail=null;$available=null;
            try{
                // Mở trang không có q vẫn lấy danh sách; gửi tìm kiếm phải có từ khóa.
                $searchSubmitted=($_SERVER['REQUEST_METHOD']??'GET')==='GET' && array_key_exists('q',$_GET);
                if($searchSubmitted && trim($term)==='' && $day==='' && $state==='active'){
                    $reservationError='Vui lòng nhập thông tin tìm kiếm.';
                    $reservationFields['q']='Vui lòng nhập tên khách hoặc số điện thoại.';
                    http_response_code(422);
                }else{
                    $rows=$this->traCuuDatBan(current_user(),$term,$day,$state,current_branch_id());
                }
                $tables=$this->danhSachBan(current_user(),current_branch_id());
                if($selected!=='')$detail=$this->chiTiet(current_user(),$selected,current_branch_id());
                if($detail && $detail['trangThai']==='CHO_XAC_NHAN' && $check && can('reservation.manage'))$available=$this->kiemTraBanPhuHop(current_user(),$selected);
            }catch(BookingError $error){$reservationError=$error->getMessage();$reservationFields=$error->fields;http_response_code($error->status);}
            $canWrite=can('reservation.manage');
            $showBranchName=current_branch_id()===null;
            $counterTables=array_values(array_filter($tables,fn($table)=>$table['trangThai']==='TRONG'));
            $counterRequestKey=$canWrite?$this->khoaBieuMau('counter_create'):'';
            $counterScreen=($_GET['view']??'')==='counter' || $postAction==='counter_create';
            if($counterScreen){
                require_permission('reservation.manage');
                require dirname(__DIR__).'/views/tai-quay.php';
                return;
            }
        }
        require dirname(__DIR__).'/views/'.$views[$pageKey];
    }

    /** Nhận POST, kiểm tra CSRF/quyền và chuyển đến phương thức nghiệp vụ tương ứng. */
    private function xuLyPost(string $pageKey): array
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            require_csrf();
            $action=post_text('action');
            try{
                if(!in_array($action,['web_create','track','counter_create','confirm','cancel','arrive'],true))throw new BookingError('VALIDATION_ERROR','Thao tác không hợp lệ.');
                if(($pageKey==='booking' && $action!=='web_create')||($pageKey==='tracking' && $action!=='track')||($pageKey==='floor' && !in_array($action,['counter_create','confirm','cancel','arrive'],true)))throw new BookingError('VALIDATION_ERROR','Thao tác không thuộc trang này.');
                if(in_array($action,['web_create','counter_create'],true)){
                    $key=post_text('request_key');
                    if(empty($_SESSION['reservation_nonce'][$action]) || !hash_equals($_SESSION['reservation_nonce'][$action],$key))throw new BookingError('CONFLICT','Biểu mẫu đã gửi hoặc hết hiệu lực. Tải lại trang trước khi tạo yêu cầu mới.',409);
                }
                if(in_array($action,['counter_create','confirm','cancel','arrive'],true))require_permission('reservation.manage');
                if($action==='web_create'){
                    $d=$this->taoDonDatBan($_POST);unset($_SESSION['reservation_nonce'][$action]);
                    $_SESSION['reservation_flash']=['message'=>'Yêu cầu đã được gửi, vui lòng chờ xác nhận. Mã đặt bàn: '.$d['maDatBan'].'. Gửi yêu cầu chưa giữ bàn.','booking'=>array_intersect_key($d,array_flip(['maDatBan','maChiNhanh','thoiGianDen','soKhach']))];
                    redirect_to('index.php?page=booking');
                }
                if($action==='counter_create'){
                    $d=$this->taoDonTaiQuay(current_user(),$_POST);unset($_SESSION['reservation_nonce'][$action]);
                    $_SESSION['reservation_flash']=['message'=>'Tạo đơn tại quầy thành công. Mã: '.$d['maDatBan']];
                    redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
                }
                if($action==='confirm'){
                    $d=$this->xacNhanDatBan(current_user(),post_text('maDatBan'),post_text('maBan'));
                    $_SESSION['reservation_flash']=['message'=>'Xác nhận đặt bàn thành công.'];
                    redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
                }
                if($action==='arrive'){
                    $d=$this->ghiNhanKhachDen(current_user(),post_text('maDatBan'));
                    $_SESSION['reservation_flash']=['message'=>!empty($d['expired'])?'Yêu cầu đã quá hạn giữ chỗ và được tự hủy.':(!empty($d['waiting'])?'Đã ghi nhận khách đến đúng hạn. Bàn đang bận; chờ bố trí bàn.':'Đã ghi nhận khách đến và tiếp nhận vào bàn.')];
                    redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
                }
                if($action==='cancel'){
                    $d=$this->huyDatBan(current_user(),post_text('maDatBan'),post_text('lyDoHuy'));
                    $_SESSION['reservation_flash']=['message'=>'Hủy đặt bàn thành công.'];
                    redirect_to('index.php?page=floor&reservation='.rawurlencode($d['maDatBan']));
                }
            }catch(BookingError $error){http_response_code($error->status);return ['reservationError'=>$error->getMessage(),'reservationFields'=>$error->fields];}
        }
        return ['reservationError'=>null,'reservationFields'=>[]];
    }

    private function khoaBieuMau(string $action): string
    {
        $_SESSION['reservation_nonce'][$action]??=bin2hex(random_bytes(24));
        return $_SESSION['reservation_nonce'][$action];
    }

    public function danhSachChiNhanh(): array {return $this->chiNhanh->danhSach();}
    public function danhSachBan(?array $actor,?string $branch=null): array {return $this->ban->danhSach($this->phamVi($actor,$branch));}
    private function now(): \DateTimeImmutable {return $this->clock?($this->clock)():new \DateTimeImmutable('now',new \DateTimeZone('Asia/Ho_Chi_Minh'));}
    public const OPEN_TIME='08:00:00';
    public const CLOSE_TIME='22:00:00';
    private function requireOpen(\DateTimeImmutable $time,string $field='thoiGianDen'): void
    {
        $local=$time->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        if($local->format('H:i:s')<self::OPEN_TIME || $local->format('H:i:s')>=self::CLOSE_TIME)
            throw new BookingError('VALIDATION_ERROR','Nhà hàng nhận khách từ 08:00 đến trước 22:00. Vui lòng chọn thời gian trong giờ mở cửa.',422,[$field=>'Giờ đến từ 08:00 đến trước 22:00.']);
    }
    private function pastHold(array $d): bool
    {
        $now=$this->now()->format('Y-m-d H:i:s');
        return $now>$d['hanGiuCho'] || (substr($d['hanGiuCho'],11)===self::CLOSE_TIME && $now>=$d['hanGiuCho']);
    }
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
        $this->requireOpen($start);
        if($start<=$this->now())throw new BookingError('VALIDATION_ERROR','Ngày giờ đến phải ở tương lai.',422,['thoiGianDen'=>'Chọn thời gian trong tương lai.']);
        return $this->transaction(function()use($branch,$name,$phone,$guests,$start,$note){
            $this->branch($branch,true);$id='DB'.strtoupper(bin2hex(random_bytes(9)));
            $this->don->tao(['maDatBan'=>$id,'maChiNhanh'=>$branch,'tenKhach'=>$name,'soDienThoai'=>$phone,'soKhach'=>$guests,'thoiGianDen'=>$start->format('Y-m-d H:i:s'),'ghiChu'=>$note?:null]);
            return $this->found($id);
        });
    }
    /** SD-2: chỉ trả các trường khách cần xem, không trả hồ sơ/ghi chú nội bộ. */
    public function theoDoiTrangThai(string $phone): array {$this->tuHuyQuaHan();return $this->don->theoSoDienThoai($this->soDienThoai($phone));}
    /** SD-5. */
    public function traCuuDatBan(?array $actor,string $term='',string $date='',string $status='',?string $branch=null): array
    {
        $scope=$this->phamVi($actor,$branch);$this->tuHuyQuaHan();
        if(mb_strlen($term)>100)throw new BookingError('VALIDATION_ERROR','Từ khóa tối đa 100 ký tự.');
        if($date!=='' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))))throw new BookingError('VALIDATION_ERROR','Ngày lọc không hợp lệ.');
        if($status!=='' && $status!=='active' && !array_key_exists($status,self::STATES))throw new BookingError('VALIDATION_ERROR','Trạng thái lọc không hợp lệ.');
        return $this->don->traCuu($scope,trim($term),$date,$status);
    }
    public function chiTiet(?array $actor,string $id,?string $branch=null): array
    {
        $scope=$this->phamVi($actor,$branch);$d=$this->found($id);
        if($scope!==null && $d['maChiNhanh']!==$scope)throw new BookingError('FORBIDDEN','Bạn không được xem đặt bàn chi nhánh khác.',403);
        return $d;
    }
    public function kiemTraBanPhuHop(?array $actor,string $id): array
    {
        $actor=$this->staff($actor);$d=$this->found($id);$this->access($d,$actor);$this->branch($d['maChiNhanh']);$this->requireOpen(new \DateTimeImmutable($d['thoiGianDen']));
        $until=new \DateTimeImmutable($d['hanGiuCho']);
        if($d['trangThai']!=='CHO_XAC_NHAN')throw new BookingError('CONFLICT','Chỉ kiểm tra xác nhận yêu cầu đang chờ.',409);
        if($this->pastHold($d))throw new BookingError('CONFLICT','Yêu cầu đã quá hạn giữ chỗ hoặc đã đến giờ đóng cửa.',409);
        return $this->ban->danhSachPhuHop($d['maChiNhanh'],(int)$d['soKhach'],$d['thoiGianDen'],$until->format('Y-m-d H:i:s'));
    }
    /** SD-3: khóa ChiNhanh → Ban → DatBan, kiểm tra lại rồi ghi trong một transaction. */
    public function xacNhanDatBan(?array $actor,string $id,string $table): array
    {
        $actor=$this->staff($actor);$this->tuHuyQuaHan();
        return $this->transaction(function()use($actor,$id,$table){
            $this->branch($actor['maChiNhanh'],true);
            $b=$this->ban->lay($table,true);$d=$this->found($id,true);$this->access($d,$actor);
            if($d['trangThai']!=='CHO_XAC_NHAN')throw new BookingError('CONFLICT','Yêu cầu không còn ở trạng thái Chờ xác nhận.',409);
            $this->requireOpen(new \DateTimeImmutable($d['thoiGianDen']));
            $until=new \DateTimeImmutable($d['hanGiuCho']);
            if($this->pastHold($d))throw new BookingError('CONFLICT','Yêu cầu đã quá hạn giữ chỗ hoặc đã đến giờ đóng cửa.',409);
            if(!$b || $b['maChiNhanh']!==$d['maChiNhanh'])throw new BookingError('VALIDATION_ERROR','Bàn không thuộc chi nhánh của yêu cầu.');
            if($b['trangThai']==='NGUNG' || (int)$b['sucChua']<(int)$d['soKhach'])throw new BookingError('CONFLICT','Bàn không đủ sức chứa hoặc đã ngừng sử dụng.',409);
            if($this->don->coLichTrung($table,$d['thoiGianDen'],$until->format('Y-m-d H:i:s')))throw new BookingError('CONFLICT','Bàn không còn trống. Vui lòng kiểm tra và chọn bàn khác.',409);
            $this->don->xacNhan($id,$table,$actor['maNhanVien']);
            return $this->found($id);
        });
    }
    /** SD-4: tiếp nhận ngay; không tự tạo đơn gọi món. */
    public function taoDonTaiQuay(?array $actor,array $data): array
    {
        $actor=$this->staff($actor);$table=$this->text($data,'maBan',10,true);$guests=$this->guests($data);
        $name=$this->text($data,'tenKhach',100);$phone=$this->soDienThoai($this->text($data,'soDienThoai',30),true);$note=$this->text($data,'ghiChu',500);
        $this->tuHuyQuaHan();
        return $this->transaction(function()use($actor,$table,$guests,$name,$phone,$note){
            $this->branch($actor['maChiNhanh'],true);$b=$this->ban->lay($table,true);$start=$this->now();
            $this->requireOpen($start);
            $until=$start->modify('+15 minutes');
            if(!$b || $b['maChiNhanh']!==$actor['maChiNhanh'])throw new BookingError('VALIDATION_ERROR','Bàn không thuộc chi nhánh của bạn.');
            if($b['trangThai']!=='TRONG' || (int)$b['sucChua']<$guests)throw new BookingError('CONFLICT','Bàn không trống hoặc không đủ sức chứa.',409);
            if($this->don->coLichTrung($table,$start->format('Y-m-d H:i:s'),$until->format('Y-m-d H:i:s')))throw new BookingError('CONFLICT','Bàn đang được giữ chỗ hoặc có lịch trong 15 phút tới.',409);
            $id='DB'.strtoupper(bin2hex(random_bytes(9)));
            $this->don->tao(['maDatBan'=>$id,'maChiNhanh'=>$actor['maChiNhanh'],'maBan'=>$table,'nguon'=>'TAI_QUAY','tenKhach'=>$name?:null,'soDienThoai'=>$phone?:null,'soKhach'=>$guests,'thoiGianDen'=>$start->format('Y-m-d H:i:s'),'thoiGianKhachDen'=>$start->format('Y-m-d H:i:s'),'thoiGianNhanBan'=>$start->format('Y-m-d H:i:s'),'trangThai'=>'DANG_PHUC_VU','maNguoiTao'=>$actor['maNhanVien'],'ghiChu'=>$note?:null]);
            $this->ban->capNhatDangPhucVu($table);return $this->found($id);
        });
    }
    /** Đồng bộ hết hạn; không thay đổi trạng thái vật lý bàn. */
    public function tuHuyQuaHan(): int
    {
        return $this->don->tuHuyQuaHan($this->now()->format('Y-m-d H:i:s'));
    }
    /** Nhánh ghi nhận khách đến của xác nhận/theo dõi đặt bàn. */
    public function ghiNhanKhachDen(?array $actor,string $id): array
    {
        $actor=$this->staff($actor);$this->tuHuyQuaHan();$hint=$this->found($id);$this->access($hint,$actor);
        return $this->transaction(function()use($actor,$id,$hint){
            $this->branch($actor['maChiNhanh'],true);
            $b=$hint['maBan']?$this->ban->lay($hint['maBan'],true):null;
            $d=$this->found($id,true);$this->access($d,$actor);
            if($hint['maBan']!==$d['maBan'])throw new BookingError('CONFLICT','Bàn vừa thay đổi. Vui lòng tải lại.',409);
            if($d['trangThai']==='DANG_PHUC_VU')return $d+['waiting'=>false];
            if($d['trangThai']!=='DA_XAC_NHAN'||!$b)throw new BookingError('CONFLICT','Chỉ ghi nhận khách đến cho yêu cầu đã xác nhận.',409);
            $now=$this->now()->format('Y-m-d H:i:s');
            if($d['thoiGianKhachDen']===null && $this->pastHold($d)){
                $this->don->huy($id,'Tự động hủy: khách chưa đến trước hạn giữ chỗ hoặc giờ đóng cửa',null);
                return $this->found($id)+['expired'=>true,'waiting'=>false];
            }
            if($d['thoiGianKhachDen']===null)$this->requireOpen($this->now());
            // Khách đến đúng hạn nhưng bàn bận vẫn được ghi nhận có mặt, không bị tự hủy.
            $waiting=$b['trangThai']!=='TRONG';
            $this->don->ghiNhanKhachDen($id,$d['thoiGianKhachDen']??$now,$waiting,$now);
            if(!$waiting)$this->ban->capNhatDangPhucVu($b['maBan']);
            return $this->found($id)+['waiting'=>$waiting];
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
