import urllib.request, urllib.error, urllib.parse, http.cookiejar, re, json, subprocess, datetime,sys
from pathlib import Path
if hasattr(sys.stdout, "reconfigure"):sys.stdout.reconfigure(encoding="utf-8")
import os, shutil, tempfile, secrets, socket, time
ROOT=Path(__file__).resolve().parents[3]
PHP=os.environ.get('RESTAURANT_PHP') or shutil.which('php')
if not PHP:raise RuntimeError('Không thấy php trong PATH. Mở Laragon Terminal hoặc cấu hình RESTAURANT_PHP.')
FIX=str(Path(__file__).with_name('fixture.php'))
SCRATCH=Path(tempfile.mkdtemp(prefix='fbn-http-'))
folder=SCRATCH/'restaurant-chain';folder.mkdir()
for item in ROOT.iterdir():
    if item.name in ['.git','config','docs','tests']:continue
    if item.is_dir():shutil.copytree(item,folder/item.name)
    elif item.suffix not in ['.zip','.rar','.docx']:shutil.copy2(item,folder/item.name)
(folder/'config').mkdir()
for item in (ROOT/'config').iterdir():
    if item.is_file() and item.name!='database.php':shutil.copy2(item,folder/'config'/item.name)
# Cố định clock chỉ trong bản sao controller, không sửa code đang chạy.
controller=folder/'modules/dat-ban/controllers/DatBanController.php'
source=controller.read_text(encoding='utf8')
clock_expr="new \\DateTimeImmutable('now',new \\DateTimeZone('Asia/Ho_Chi_Minh'))"
if source.count(clock_expr)!=1:
    raise RuntimeError('Không tìm thấy duy nhất clock mặc định trong DatBanController.')
controller.write_text(source.replace(clock_expr,clock_expr.replace("'now'", "'today 17:30:00'")),encoding='utf8')
state={'database':'fbn_booking_http_'+secrets.token_hex(5)}
server=None
import atexit

def cleanup():
    try:
        if server is not None:
            server.terminate()
            try:server.wait(timeout=10)
            except subprocess.TimeoutExpired:server.kill();server.wait()
        subprocess.run([PHP,FIX,'cleanup',state['database']],check=True)
    finally:
        # SCRATCH là thư mục riêng do mkdtemp tạo cho lượt test này.
        if SCRATCH.parent.resolve()!=Path(tempfile.gettempdir()).resolve() or not SCRATCH.name.startswith('fbn-http-'):
            raise RuntimeError('Đường dẫn dọn bản sao kiểm thử không hợp lệ.')
        shutil.rmtree(SCRATCH)

atexit.register(cleanup)
subprocess.run([PHP,FIX,'setup',state['database'],str(folder)],check=True,stdout=subprocess.DEVNULL)
sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close()
BASE=f'http://127.0.0.1:{port}/restaurant-chain/'
server=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(SCRATCH)],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
for _ in range(50):
    try:urllib.request.urlopen(BASE+'login.php',timeout=1);break
    except (OSError,urllib.error.URLError):time.sleep(.1)
cases=[]
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,req,fp,code,msg,headers,newurl):return None
class Client:
    def __init__(self):self.cookies=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies),NoRedirect())
    def req(self,path,data=None):
        r=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode() if data is not None else None)
        try:resp=self.opener.open(r,timeout=15)
        except urllib.error.HTTPError as e:resp=e
        html=resp.read().decode('utf8');assert not re.search('Fatal error|Warning:|Parse error',html),html[:1000]
        return resp.code,resp.headers,html
    def form(self,path,action):
        status,_,html=self.req(path);assert status==200,(path,status,html[:500])
        csrf=re.search(r'name="csrf_token" value="([a-f0-9]+)"',html).group(1)
        key=re.search(r'name="request_key" value="([a-f0-9]+)"',html)
        return dict(action=action,csrf_token=csrf,**({'request_key':key.group(1)} if key else {}))
    def login(self,user):
        token=self.form('login.php','login')['csrf_token'];status,_,_=self.req('login.php',dict(username=user,password='BookingTest@2026!',csrf_token=token));assert status==303,(user,status)
def inspect():return json.loads(subprocess.check_output([PHP,FIX,'inspect',state['database']],text=True,encoding='utf8'))
def case(id,function,title,expected,fn):
    try:fn();status='PASS';actual='HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.'
    except Exception as e:status='FAIL';actual=str(e)
    cases.append(dict(id=id,function=function,title=title,input='Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm.',expected=expected,status=status,actual=actual));print(status,id,title,flush=True)
def assert_that(v,why='Assertion failed'):
    if not v:raise AssertionError(why)
guest=Client();staff=Client();staff.login('phuc_vu');other=Client();other.login('phuc_vu2');owner=Client();owner.login('chu_chuoi')
start=(datetime.datetime.now()+datetime.timedelta(days=3)).replace(hour=18,minute=0,second=0).strftime('%Y-%m-%dT%H:%M');end=start[:11]+'20:00'
data=dict(maChiNhanh='CN01',tenKhach='Khách thử giao diện',soDienThoai='0987654321',soKhach='4',thoiGianDen=start,ghiChu='HTTP test')
case('WEB01','Đặt bàn','Trang khách hiển thị chi nhánh MySQL và form thật','HTTP 200; không tải app.js mẫu',lambda:assert_that('name="maChiNhanh"' in guest.req('index.php?page=booking')[2] and 'reservations.js' in guest.req('index.php?page=booking')[2]))
case('WEB02','Đặt bàn','Từ chối POST thiếu CSRF','HTTP 403; không ghi dữ liệu',lambda:assert_that(guest.req('index.php?page=booking',dict(action='web_create',**data))[0]==403 and len(inspect()['bookings'])==0))
request=guest.form('index.php?page=booking','web_create');request.update(data)
def create():
    status,headers,_=guest.req('index.php?page=booking',request);assert_that(status==303 and 'page=booking' in headers['Location']);assert_that(len(inspect()['bookings'])==1)
case('WEB03','Đặt bàn','Gửi form hợp lệ và chuyển hướng sau POST','HTTP 303; lưu đúng một CHO_XAC_NHAN',create)
booking=inspect()['bookings'][0]['maDatBan']
case('WEB04','Đặt bàn','Refresh và gửi lại cùng request_key','GET không tạo thêm; POST cũ HTTP 409',lambda:assert_that(guest.req('index.php?page=booking')[0]==200 and guest.req('index.php?page=booking',request)[0]==409 and len(inspect()['bookings'])==1))
case('WEB05','Theo dõi trạng thái','SĐT sai trên form công khai','HTTP 422 và thông báo lỗi',lambda:assert_that(guest.req('index.php?page=tracking',dict(guest.form('index.php?page=tracking','track'),soDienThoai='abc'))[0]==422))
case('WEB06','Theo dõi trạng thái','Tra cứu sau khi đặt','HTML có mã và Chờ xác nhận',lambda:assert_that(booking in guest.req('index.php?page=tracking',dict(guest.form('index.php?page=tracking','track'),soDienThoai=data['soDienThoai']))[2]))
case('WEB07','Tra cứu đặt bàn','Khách vào trang nhân viên','HTTP 303 về login',lambda:assert_that(guest.req('index.php?page=floor')[0]==303))
case('WEB08','Tra cứu đặt bàn','Phục vụ lọc tên và xem chi tiết','HTML có mã đúng',lambda:assert_that(booking in staff.req('index.php?page=floor&q='+urllib.parse.quote('Khách thử'))[2] and staff.req('index.php?page=floor&reservation='+booking)[0]==200))
case('WEB09','Tra cứu đặt bàn','Phục vụ CN02 xem mã CN01','HTTP 403',lambda:assert_that(other.req('index.php?page=floor&reservation='+booking)[0]==403))
confirm=dict(staff.form('index.php?page=floor&reservation='+booking,'confirm'),maDatBan=booking,maBan='C1-A01')
case('WEB10','Xác nhận đặt bàn','Chủ chuỗi chỉ xem, không giả POST xác nhận','HTTP 403; đơn vẫn chờ',lambda:assert_that(owner.req('index.php?page=floor',dict(confirm,csrf_token=owner.form('index.php?page=floor','confirm')['csrf_token']))[0]==403 and inspect()['bookings'][0]['trangThai']=='CHO_XAC_NHAN'))
case('WEB11','Xác nhận đặt bàn','Kiểm tra bàn phù hợp và xác nhận bằng form','GET có C1-A01; POST 303; DB DA_XAC_NHAN',lambda:assert_that('C1-A01' in staff.req('index.php?page=floor&reservation='+booking+'&check_tables=1')[2] and staff.req('index.php?page=floor',confirm)[0]==303 and inspect()['bookings'][0]['trangThai']=='DA_XAC_NHAN'))
case('WEB12','Theo dõi trạng thái','Khách thấy trạng thái mới sau xác nhận','HTML Đã xác nhận',lambda:assert_that('Đã xác nhận' in guest.req('index.php?page=tracking',dict(guest.form('index.php?page=tracking','track'),soDienThoai=data['soDienThoai']))[2]))
cancel=dict(staff.form('index.php?page=floor&reservation='+booking,'cancel'),maDatBan=booking,lyDoHuy='Khách đổi kế hoạch')
case('WEB13','Hủy đặt bàn','Thiếu lý do không được hủy','HTTP 422; đơn còn DA_XAC_NHAN',lambda:assert_that(staff.req('index.php?page=floor',dict(cancel,lyDoHuy=''))[0]==422 and inspect()['bookings'][0]['trangThai']=='DA_XAC_NHAN'))
case('WEB14','Hủy đặt bàn','Hủy hợp lệ qua HTTP','POST 303; DB DA_HUY; khách thấy Đã hủy',lambda:assert_that(staff.req('index.php?page=floor',cancel)[0]==303 and inspect()['bookings'][0]['trangThai']=='DA_HUY' and 'Đã hủy' in guest.req('index.php?page=tracking',dict(guest.form('index.php?page=tracking','track'),soDienThoai=data['soDienThoai']))[2]))
counter=dict(staff.form('index.php?page=floor&view=counter','counter_create'),maBan='C1-A02',soKhach='2')
case('WEB15','Tạo đặt bàn tại quầy','Nhận khách qua form','POST 303; đơn và bàn DANG_PHUC_VU',lambda:assert_that(staff.req('index.php?page=floor',counter)[0]==303 and any(d['nguon']=='TAI_QUAY' and d['trangThai']=='DANG_PHUC_VU' for d in inspect()['bookings']) and next(t for t in inspect()['tables'] if t['maBan']=='C1-A02')['trangThai']=='DANG_PHUC_VU'))
case('WEB16','Tạo đặt bàn tại quầy','Gửi lại form quầy cũ','HTTP 409; không tạo thêm',lambda:assert_that(staff.req('index.php?page=floor',counter)[0]==409 and len(inspect()['bookings'])==2))
xss=dict(guest.form('index.php?page=booking','web_create'),**dict(data,tenKhach='<script>alert(1)</script>'))
def check_xss():
    assert_that(guest.req('index.php?page=booking',xss)[0]==303)
    html=staff.req('index.php?page=floor')[2];assert_that('&lt;script&gt;alert(1)&lt;/script&gt;' in html and '<script>alert(1)</script>' not in html)
case('WEB17','Đặt bàn','HTML escape tên khách','Tên script hiển thị dạng text, không thành thẻ script',check_xss)
case('WEB18','Theo dõi trạng thái','Không có kết quả','HTML thông báo Không tìm thấy',lambda:assert_that('Không tìm thấy' in guest.req('index.php?page=tracking',dict(guest.form('index.php?page=tracking','track'),soDienThoai='0999999999'))[2]))

case('WEB19','Xác nhận đặt bàn','Biểu mẫu bỏ giờ kết thúc dự kiến','HTML không có input thoiGianKetThucDuKien',lambda:assert_that('name="thoiGianKetThucDuKien"' not in staff.req('index.php?page=floor')[2]))
def arrival_web():
    fresh=dict(guest.form('index.php?page=booking','web_create'),**dict(data,tenKhach='Khách đến đúng hạn',soDienThoai='0901111222'))
    assert_that(guest.req('index.php?page=booking',fresh)[0]==303)
    b=next(d for d in inspect()['bookings'] if d['tenKhach']=='Khách đến đúng hạn')['maDatBan']
    payload=dict(staff.form('index.php?page=floor','confirm'),maDatBan=b,maBan='C1-A01')
    assert_that(staff.req('index.php?page=floor',payload)[0]==303)
    assert_that('Khách đã đến' in staff.req('index.php?page=floor&reservation='+b)[2])
    arrive=dict(staff.form('index.php?page=floor','arrive'),maDatBan=b)
    assert_that(staff.req('index.php?page=floor',arrive)[0]==303)
    r=next(d for d in inspect()['bookings'] if d['maDatBan']==b)
    assert_that(r['trangThai']=='DANG_PHUC_VU' and r['thoiGianKhachDen'] is not None and r['thoiGianKetThucDuKien'] is None)
case('WEB20','Xác nhận đặt bàn','Ghi nhận khách đến qua form thật','POST 303; lưu giờ đến; đơn và bàn DANG_PHUC_VU',arrival_web)

case('WEB21','Trang vào','Khách thấy rõ hai chức năng không cần đăng nhập','Login có nav khách và hai đường dẫn công khai',lambda:assert_that(all(text in guest.req('login.php')[2] for text in ['page=booking','page=tracking','Theo dõi trạng thái','Không cần tài khoản','name="username"','name="password"'])))
# Mở kết quả của lần đặt trước, rồi chọn Đặt bàn khác để lấy form mới.
guest.req('index.php?page=booking')
case('WEB22','Giờ mở cửa','POST đặt ngoài giờ bị chặn trên server','22:00 bị HTTP 422 dù bỏ qua kiểm tra trình duyệt',lambda:assert_that(guest.req('index.php?page=booking',dict(guest.form('index.php?page=booking','web_create'),**dict(data,thoiGianDen=start[:11]+'22:00')))[0]==422))
# Kiểm tra các màn hình và bộ lọc sau khi tách MVC.
case('WEB23','Tra cứu đặt bàn','Tìm trống với bộ lọc mặc định','HTTP 422 và lỗi nhập từ khóa',lambda:assert_that(staff.req('index.php?page=floor&q=&date=&status=active')[0]==422 and 'Vui lòng nhập thông tin tìm kiếm.' in staff.req('index.php?page=floor&q=&date=&status=active')[2]))
case('WEB24','Tra cứu đặt bàn','Danh sách mặc định chỉ còn đơn cần xử lý','Hiện đơn chờ; ẩn đơn đã hủy và đang phục vụ',lambda:assert_that(booking not in staff.req('index.php?page=floor')[2] and '&lt;script&gt;alert(1)&lt;/script&gt;' in staff.req('index.php?page=floor')[2] and 'Khách đến đúng hạn' not in staff.req('index.php?page=floor')[2]))
case('WEB25','Tra cứu đặt bàn','Lọc tất cả và lịch sử hủy không cần từ khóa','HTTP 200; tìm lại được đơn đã hủy',lambda:assert_that(staff.req('index.php?page=floor&q=&date=&status=')[0]==200 and booking in staff.req('index.php?page=floor&q=&date=&status=DA_HUY')[2]))
case('WEB26','Tra cứu đặt bàn','Không có kết quả phù hợp','Hiện thông báo không tìm thấy',lambda:assert_that('Không tìm thấy thông tin đặt bàn phù hợp' in staff.req('index.php?page=floor&q=KhachKhongTonTai999&date=&status=')[2]))
case('WEB27','Tạo đặt bàn tại quầy','Màn hình riêng có chọn bàn và số khách','Có form counter_create và nhóm radio maBan',lambda:assert_that(all(text in staff.req('index.php?page=floor&view=counter')[2] for text in ['name="action" value="counter_create"','type="radio" name="maBan"','name="soKhach"','Quay lại Quản lý đặt bàn'])))

def assets_available():
    for client,path in [(guest,'login.php'),(guest,'index.php?page=booking'),(guest,'index.php?page=tracking'),(staff,'index.php?page=floor'),(staff,'index.php?page=floor&view=counter')]:
        html=client.req(path)[2]
        for asset in re.findall(r'(?:src|href)="([^" ]*assets/[^" ]+)"',html):
            url=urllib.parse.urljoin(BASE,asset.replace('&amp;','&'))
            with urllib.request.urlopen(url,timeout=10) as response:
                assert_that(response.status==200,url)
case('WEB28','Giao diện','CSS JS font và ảnh được đóng gói đủ','Tất cả tài nguyên assets được tham chiếu trả HTTP 200',assets_available)

result=dict(total=len(cases),passed=sum(c['status']=='PASS' for c in cases),base_url=BASE,isolated_database=True,test_clock="HTTP source copy only: today 17:30 VN",cases=cases)
Path(os.environ.get('RESERVATION_HTTP_RESULTS') or str(Path(__file__).parents[1]/'http-results.json')).write_text(json.dumps(result,ensure_ascii=False,indent=2),encoding='utf8')
sys.exit(0 if result['passed']==len(cases) else 1)
