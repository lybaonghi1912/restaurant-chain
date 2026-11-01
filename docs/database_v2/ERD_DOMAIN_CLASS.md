# ERD, domain và class theo 32 sequence

## ERD vật lý đầy đủ

Hai sơ đồ dưới hợp lại là cùng một ERD; ChiNhanh/NhanVien xuất hiện ở cả hai để dễ đọc. Hai bảng kỹ thuật được tách khỏi domain. Thuộc tính chi tiết xem TU_DIEN_DATABASE.md.

### Nhóm 1

```mermaid
erDiagram
    ChuoiNhaHang {
        string maChuoi PK
    }
    ChiNhanh {
        string maChiNhanh PK
        string maChuoi
        string maQuanLy
    }
    VaiTro {
        string maVaiTro PK
    }
    NhanVien {
        string maNhanVien PK
        string maVaiTro
        string maChiNhanh
        string maCa
    }
    TaiKhoan {
        string tenDangNhap PK
        string maNhanVien
    }
    CaLamViec {
        string maCa PK
    }
    NhatKyNhanSu {
        string maNhatKy PK
        string maNhanVien
        string maNguoiSua
    }
    Ban {
        string maBan PK
        string maChiNhanh
    }
    DatBan {
        datetime thoiGianDen
        datetime hanGiuCho "generated +15 minutes"
        datetime thoiGianKhachDen "nullable"
        datetime thoiGianNhanBan "nullable"
        string maDatBan PK
        string maChiNhanh
        string maBan
        string maNguoiTao
        string maNguoiXacNhan
        string maNguoiHuy
    }
    NhomMon {
        string maNhom PK
        string maChuoi
    }
    MonAn {
        string maMon PK
        string maNhom
    }
    ThucDonChiNhanh {
        string maChiNhanh PK
        string maMon PK
    }
    DinhMucNVL {
        string maMon PK
        string maNVL PK
    }
    NguyenVatLieu {
        string maNVL PK
        string maChuoi
    }
    LichSuDinhMuc {
        string maLichSu PK
        string maMon
        string maNguoiSua
    }
    DonGoiMon {
        string maDon PK
        string maChiNhanh
        string maBan
        string maNhanVien
    }
    ChiTietDonMon {
        string maCTDon PK
        string maDon
        string maChiNhanh
        string maMon
        string maNhanVienBep
    }
    Ban |o--o{ DatBan : "maBan,maChiNhanh"
    Ban ||--o{ DonGoiMon : "maBan,maChiNhanh"
    ChiNhanh ||--o{ Ban : "maChiNhanh"
    ChiNhanh ||--o{ DatBan : "maChiNhanh"
    ChiNhanh ||--o{ DonGoiMon : "maChiNhanh"
    ChiNhanh |o--o{ NhanVien : "maChiNhanh"
    ChiNhanh ||--o{ ThucDonChiNhanh : "maChiNhanh"
    ChuoiNhaHang ||--o{ ChiNhanh : "maChuoi"
    ChuoiNhaHang ||--o{ NguyenVatLieu : "maChuoi"
    ChuoiNhaHang ||--o{ NhomMon : "maChuoi"
    DonGoiMon ||--o{ ChiTietDonMon : "maDon,maChiNhanh"
    MonAn ||--o{ DinhMucNVL : "maMon"
    MonAn ||--o{ LichSuDinhMuc : "maMon"
    MonAn ||--o{ ThucDonChiNhanh : "maMon"
    NguyenVatLieu ||--o{ DinhMucNVL : "maNVL"
    NhanVien |o--o{ ChiTietDonMon : "maNhanVienBep"
    NhanVien |o--o{ DatBan : "maNguoiHuy"
    NhanVien |o--o{ DatBan : "maNguoiTao"
    NhanVien |o--o{ DatBan : "maNguoiXacNhan"
    NhanVien ||--o{ DonGoiMon : "maNhanVien"
    NhanVien |o--o{ LichSuDinhMuc : "maNguoiSua"
    NhanVien |o--o{ NhatKyNhanSu : "maNguoiSua"
    NhanVien ||--o{ NhatKyNhanSu : "maNhanVien"
    NhanVien ||--o| TaiKhoan : "maNhanVien"
    NhomMon ||--o{ MonAn : "maNhom"
    ThucDonChiNhanh ||--o{ ChiTietDonMon : "maChiNhanh,maMon"
    VaiTro ||--o{ NhanVien : "maVaiTro"
```

### Nhóm 2

```mermaid
erDiagram
    ChiNhanh {
        string maChiNhanh PK
        string maChuoi
        string maQuanLy
    }
    NhanVien {
        string maNhanVien PK
        string maVaiTro
        string maChiNhanh
        string maCa
    }
    NguyenVatLieu {
        string maNVL PK
        string maChuoi
    }
    PhieuDeXuat {
        string maPhieu PK
        string maChiNhanh
        string maNguoiLap
    }
    ChiTietDeXuat {
        string maPhieu PK
        string maNVL PK
    }
    YeuCauNhapKho {
        string maYeuCau PK
        string maPhieu
        string maChiNhanh
        string maNguoiTao
    }
    NhaCungCap {
        string maNCC PK
        string maSoThue
    }
    NhaCungCapNguyenLieu {
        string maNCC PK
        string maNVL PK
    }
    DonNhapNCC {
        string maDonNhap PK
        string maYeuCau
        string maChiNhanh
        string maNCC
        string maNguoiLap
    }
    ChiTietDonNhapNCC {
        string maDonNhap PK
        string maNVL PK
    }
    TonKhoTheoLo {
        string maChiNhanh PK
        string maLo PK
        string maNVL
    }
    DieuChuyenKho {
        string maDieuChuyen PK
        string maYeuCau
        string maChiNhanhNhan
        string maChiNhanhNguon
        string maNguoiTao
        string maNguoiXacNhanNguon
    }
    ChiTietDieuChuyenKho {
        string maDieuChuyen PK
        string maNVL PK
    }
    GiaoDichKho {
        string maGiaoDich PK
        string maChiNhanh
        string maNguoiLap
    }
    ChiTietGiaoDichKho {
        string maGiaoDich PK
        string maChiNhanh
        string maLo PK
        string maNVL
    }
    YeuCauDieuChuyen {
        string maYeuCauDC PK
        string maChiNhanh
        string maNguoiGui
    }
    ChiNhanh ||--o{ DieuChuyenKho : "maChiNhanhNguon"
    ChiNhanh ||--o{ GiaoDichKho : "maChiNhanh"
    ChiNhanh |o--o{ NhanVien : "maChiNhanh"
    ChiNhanh ||--o{ PhieuDeXuat : "maChiNhanh"
    ChiNhanh ||--o{ TonKhoTheoLo : "maChiNhanh"
    ChiNhanh ||--o{ YeuCauDieuChuyen : "maChiNhanh"
    DieuChuyenKho ||--o{ ChiTietDieuChuyenKho : "maDieuChuyen"
    DonNhapNCC ||--o{ ChiTietDonNhapNCC : "maDonNhap"
    GiaoDichKho ||--o{ ChiTietGiaoDichKho : "maGiaoDich,maChiNhanh"
    NguyenVatLieu ||--o{ ChiTietDeXuat : "maNVL"
    NguyenVatLieu ||--o{ ChiTietDieuChuyenKho : "maNVL"
    NguyenVatLieu ||--o{ ChiTietDonNhapNCC : "maNVL"
    NguyenVatLieu ||--o{ NhaCungCapNguyenLieu : "maNVL"
    NguyenVatLieu ||--o{ TonKhoTheoLo : "maNVL"
    NhaCungCap ||--o{ DonNhapNCC : "maNCC"
    NhaCungCap ||--o{ NhaCungCapNguyenLieu : "maNCC"
    NhanVien ||--o{ DieuChuyenKho : "maNguoiTao"
    NhanVien |o--o{ DieuChuyenKho : "maNguoiXacNhanNguon"
    NhanVien ||--o{ DonNhapNCC : "maNguoiLap"
    NhanVien ||--o{ GiaoDichKho : "maNguoiLap"
    NhanVien ||--o{ PhieuDeXuat : "maNguoiLap"
    NhanVien ||--o{ YeuCauDieuChuyen : "maNguoiGui"
    NhanVien ||--o{ YeuCauNhapKho : "maNguoiTao"
    PhieuDeXuat ||--o{ ChiTietDeXuat : "maPhieu"
    PhieuDeXuat ||--o{ YeuCauNhapKho : "maPhieu,maChiNhanh"
    TonKhoTheoLo ||--o{ ChiTietGiaoDichKho : "maChiNhanh,maLo,maNVL"
    YeuCauNhapKho ||--o{ DieuChuyenKho : "maYeuCau,maChiNhanhNhan"
    YeuCauNhapKho ||--o{ DonNhapNCC : "maYeuCau,maChiNhanh"
```

Các cạnh ERD thể hiện tham chiếu; FK nullable cho phép vắng cha ở dòng cụ thể (ví dụ DatBan.maBan chưa gán, ChiNhanh.maQuanLy chưa chọn). TaiKhoan.maNhanVien là UNIQUE: một nhân viên tối đa một tài khoản. Xem FK ghép trong SQL để biết điều kiện cùng chi nhánh.

## Domain khái niệm

```mermaid
flowchart LR
    ChiNhanh --> Ban --> DatBan
    ChiNhanh --> ThucDonChiNhanh --> MonAn
    Ban --> DonGoiMon --> ChiTietDonMon
    MonAn --> DinhMucNVL --> NguyenVatLieu
    ChiNhanh --> NhanVien --> TaiKhoan
    NhanVien --> YeuCauDieuChuyen
    PhieuDeXuat --> YeuCauNhapKho
    YeuCauNhapKho --> DieuChuyenKho
    YeuCauNhapKho --> DonNhapNCC
    NhaCungCap --> NhaCungCapNguyenLieu --> NguyenVatLieu
    NguyenVatLieu --> TonKhoTheoLo
    GiaoDichKho --> ChiTietGiaoDichKho --> TonKhoTheoLo
```

## Control và phương thức theo nhóm sequence

```mermaid
classDiagram
    class DatBanControl {
        <<Control>>
        +taoDonDatBan(data)
        +theoDoiTrangThai(soDienThoai)
        +kiemTraBanPhuHop(actor,maDatBan)
        +xacNhanDatBan(actor,maDatBan,maBan)
        +ghiNhanKhachDen(actor,maDatBan)
        +taoDonTaiQuay(actor,data)
        +traCuuDatBan(actor,term,date,status,branch)
        +chiTiet(actor,maDatBan,branch)
        +tuHuyQuaHan()
        +huyDatBan(actor,maDatBan,lyDo)
    }
    class NhanSuControl {
        <<Control>>
        +xem(tieuChi)
        +them(data)
        +sua(maNhanVien,data)
        +nghiViec(maNhanVien)
    }
    class BepControl {
        <<Control>>
        +tiepNhan(maCTDon)
    }
    class DinhMucControl {
        <<Control>>
        +luuCongThuc(maMon,danhSachNVL)
    }
    class ChiNhanhControl {
        <<Control>>
        +them(data)
        +xem(maChiNhanh)
        +sua(maChiNhanh,data)
        +ngungHoatDong(maChiNhanh)
    }
    class BoSungKhoControl {
        <<Control>>
        +taoTuPhieu(maPhieu)
        +lapDieuChuyen(maYeuCau,data)
        +lapNhapNCC(maYeuCau,data)
        +xacNhanNguon(maDieuChuyen,quyetDinh,lyDo)
    }
    class MonAnControl {
        <<Control>>
        +them(data)
        +danhSach()
        +sua(maMon,data)
        +ngungBan(maMon)
        +tim(tuKhoa)
        +chiTiet(maMon)
    }
    class GoiMonControl {
        <<Control>>
        +taoDon(maBan,dsMon)
        +themMon(maDon,dsMon)
        +huyMon(maCTDon,lyDo)
    }
    class DieuChuyenNhanVienControl {
        <<Control>>
        +taoYeuCau(data)
    }
    class KhoControl {
        <<Control>>
        +danhSach(tieuChi)
        +them(data)
        +sua(data)
        +ngungSuDung(ma)
        +ghiBienDong(data)
    }
    class BaoCaoKhoControl {
        <<Control>>
        +xem(boLoc)
        +xuatExcel(boLoc)
        +xuatPDF(boLoc)
    }
    DatBanControl ..> DatBan : dung
    DatBanControl ..> Ban : dung
    DatBanControl ..> ChiNhanh : dung
    NhanSuControl ..> NhanVien : dung
    NhanSuControl ..> TaiKhoan : dung
    NhanSuControl ..> NhatKyNhanSu : dung
    BepControl ..> DonGoiMon : dung
    BepControl ..> ChiTietDonMon : dung
    DinhMucControl ..> MonAn : dung
    DinhMucControl ..> DinhMucNVL : dung
    DinhMucControl ..> NguyenVatLieu : dung
    DinhMucControl ..> LichSuDinhMuc : dung
    ChiNhanhControl ..> ChiNhanh : dung
    BoSungKhoControl ..> PhieuDeXuat : dung
    BoSungKhoControl ..> YeuCauNhapKho : dung
    BoSungKhoControl ..> DieuChuyenKho : dung
    BoSungKhoControl ..> DonNhapNCC : dung
    BoSungKhoControl ..> NhaCungCapNguyenLieu : dung
    BoSungKhoControl ..> TonKhoTheoLo : dung
    MonAnControl ..> MonAn : dung
    MonAnControl ..> NhomMon : dung
    GoiMonControl ..> Ban : dung
    GoiMonControl ..> DonGoiMon : dung
    GoiMonControl ..> ChiTietDonMon : dung
    GoiMonControl ..> ThucDonChiNhanh : dung
    DieuChuyenNhanVienControl ..> YeuCauDieuChuyen : dung
    KhoControl ..> NguyenVatLieu : dung
    KhoControl ..> TonKhoTheoLo : dung
    KhoControl ..> GiaoDichKho : dung
    KhoControl ..> ChiTietGiaoDichKho : dung
    BaoCaoKhoControl ..> TonKhoTheoLo : dung
    BaoCaoKhoControl ..> GiaoDichKho : dung
    BaoCaoKhoControl ..> ChiTietGiaoDichKho : dung
```

BCE áp dụng chung: Actor → Boundary của use case → Control tương ứng → Entity/repository PDO. Các lớp Control trên là đề xuất phân tách cho PHP thuần, không bắt buộc framework. Không thêm phương thức ngoài 32 use case. `KhoControl.ghiBienDong` là thao tác nội bộ để giữ sổ khi quản lý kho, không là use case mới.
## Bổ sung đặt bàn ngày 30/10/2026

DatBanControl ở trên dùng tên phương thức đã hiện thực trong modules/dat-ban; các Control module khác vẫn là hợp đồng đề xuất. Domain DatBan phân biệt thời điểm đặt dự kiến, hạn giữ chỗ, giờ khách thực sự đến và giờ được nhận bàn. Khách đến đúng hạn nhưng bàn bận không bị coi là vắng mặt. Hai thuộc tính mới không tạo thêm thực thể/quan hệ hay bảng nghiệp vụ. Xem ../dat-ban-ca-nhan/SEQUENCE_CAP_NHAT.md để đối chiếu sáu sequence với code và test.
