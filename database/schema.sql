-- FBN SCOPE V2: 30 bang nghiep vu + 2 bang ky thuat. MySQL 8.4 / PHP PDO.
-- Schema khởi tạo phần đăng nhập và phân quyền. Chưa thay thế schema nghiệp vụ toàn hệ thống.
CREATE TABLE IF NOT EXISTS ChuoiNhaHang (
    maChuoi VARCHAR(10) PRIMARY KEY,
    tenChuoi VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ChiNhanh (
    maChiNhanh VARCHAR(10) PRIMARY KEY,
    tenChiNhanh VARCHAR(100) NOT NULL,
    diaChi VARCHAR(255) NULL,
    soDienThoai VARCHAR(15) NULL,
    maChuoi VARCHAR(10) NOT NULL,
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    FOREIGN KEY (maChuoi) REFERENCES ChuoiNhaHang(maChuoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS VaiTro (
    maVaiTro VARCHAR(10) PRIMARY KEY,
    tenVaiTro VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS NhanVien (
    maNhanVien VARCHAR(10) PRIMARY KEY,
    hoTen VARCHAR(100) NOT NULL,
    maVaiTro VARCHAR(10) NOT NULL,
    maChiNhanh VARCHAR(10) NULL,
    trangThai ENUM('DANG_LAM','NGHI_VIEC') NOT NULL DEFAULT 'DANG_LAM',
    FOREIGN KEY (maVaiTro) REFERENCES VaiTro(maVaiTro),
    FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT ck_nhanvien_scope CHECK (
        (maVaiTro = 'CHU_CHUOI' AND maChiNhanh IS NULL)
        OR (maVaiTro <> 'CHU_CHUOI' AND maChiNhanh IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS TaiKhoan (
    tenDangNhap VARCHAR(50) CHARACTER SET ascii COLLATE ascii_general_ci PRIMARY KEY,
    matKhau VARCHAR(255) NOT NULL,
    maNhanVien VARCHAR(10) NOT NULL UNIQUE,
    trangThai ENUM('HOAT_DONG','KHOA') NOT NULL DEFAULT 'HOAT_DONG',
    phienBan INT UNSIGNED NOT NULL DEFAULT 1,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lanDangNhapCuoi DATETIME NULL,
    FOREIGN KEY (maNhanVien) REFERENCES NhanVien(maNhanVien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS GioiHanDangNhap (
    khoa CHAR(64) CHARACTER SET ascii PRIMARY KEY,
    soLanSai INT UNSIGNED NOT NULL DEFAULT 0,
    batDau BIGINT UNSIGNED NOT NULL,
    khoaDen BIGINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_gioihan_batdau (batDau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BASELINE PHAM VI 32 SEQUENCE: dung voi database moi; khong chay tren V1.
-- Danh mục ca làm việc, hỗ trợ ca qua đêm
CREATE TABLE IF NOT EXISTS CaLamViec (
    maCa VARCHAR(10) PRIMARY KEY,
    tenCa VARCHAR(50) NOT NULL,
    thoiGianBatDau TIME NOT NULL,
    thoiGianKetThuc TIME NOT NULL,
    quaNgay BOOLEAN NOT NULL DEFAULT FALSE,
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    CONSTRAINT ck_calamviec_1 CHECK (thoiGianBatDau <> thoiGianKetThuc),
    CONSTRAINT ck_calamviec_2 CHECK ((quaNgay=0 AND thoiGianKetThuc>thoiGianBatDau) OR (quaNgay=1 AND thoiGianKetThuc<thoiGianBatDau))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Mở rộng nhân viên theo báo cáo; cho NULL để bảo toàn tài khoản chưa nhập hồ sơ.
ALTER TABLE NhanVien
    ADD COLUMN ngaySinh DATE NULL,
    ADD COLUMN gioiTinh TINYINT NULL,
    ADD COLUMN soCCCD VARCHAR(12) NULL,
    ADD COLUMN soDienThoai VARCHAR(15) NULL,
    ADD COLUMN email VARCHAR(100) NULL,
    ADD COLUMN diaChiThuongTru VARCHAR(255) NULL,
    ADD COLUMN ngayBatDauLamViec DATE NULL,
    ADD COLUMN maCa VARCHAR(10) NULL,
    ADD CONSTRAINT uq_nhanvien_cccd UNIQUE (soCCCD),
    ADD CONSTRAINT uq_nhanvien_branch UNIQUE (maNhanVien,maChiNhanh),
    ADD CONSTRAINT fk_nhanvien_ca FOREIGN KEY (maCa) REFERENCES CaLamViec(maCa),
    ADD CONSTRAINT ck_nhanvien_gioitinh CHECK (gioiTinh IS NULL OR gioiTinh IN (0,1)),
    ADD CONSTRAINT ck_nhanvien_cccd CHECK (soCCCD IS NULL OR soCCCD REGEXP '^[0-9]{12}$');

ALTER TABLE ChiNhanh
    ADD COLUMN maQuanLy VARCHAR(10) NULL,
    ADD COLUMN viDo DECIMAL(10,7) NULL,
    ADD COLUMN kinhDo DECIMAL(10,7) NULL,
    ADD CONSTRAINT fk_chinhanh_quanly FOREIGN KEY (maQuanLy,maChiNhanh) REFERENCES NhanVien(maNhanVien,maChiNhanh),
    ADD CONSTRAINT ck_chinhanh_vido CHECK (viDo IS NULL OR viDo BETWEEN -90 AND 90),
    ADD CONSTRAINT ck_chinhanh_kinhdo CHECK (kinhDo IS NULL OR kinhDo BETWEEN -180 AND 180);


-- Lịch sử thay đổi nhân viên, giữ cả nội dung và người sửa
CREATE TABLE IF NOT EXISTS NhatKyNhanSu (
    maNhatKy VARCHAR(20) PRIMARY KEY,
    maNhanVien VARCHAR(10) NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maNguoiSua VARCHAR(10) NULL,
    nguoiSua VARCHAR(100) NOT NULL,
    noiDung VARCHAR(1000) NOT NULL,
    duLieuCu JSON NULL,
    duLieuMoi JSON NULL,
    CONSTRAINT fk_nhatkynhansu_1 FOREIGN KEY (maNhanVien) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT fk_nhatkynhansu_2 FOREIGN KEY (maNguoiSua) REFERENCES NhanVien(maNhanVien),
    INDEX idx_nhatkynhansu_1 (maNhanVien,thoiGian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bàn vật lý tại chi nhánh; trạng thái phục vụ khác lịch giữ bàn
CREATE TABLE IF NOT EXISTS Ban (
    maBan VARCHAR(10) PRIMARY KEY,
    maChiNhanh VARCHAR(10) NOT NULL,
    soBan INT NOT NULL,
    tenBan VARCHAR(30) NOT NULL,
    sucChua SMALLINT NOT NULL,
    khuVuc VARCHAR(50) NOT NULL DEFAULT 'Trong nhà',
    viTri VARCHAR(100) NULL,
    trangThai ENUM('TRONG','DANG_PHUC_VU','NGUNG') NOT NULL DEFAULT 'TRONG',
    CONSTRAINT uq_ban_1 UNIQUE (maChiNhanh,soBan),
    CONSTRAINT uq_ban_2 UNIQUE (maBan,maChiNhanh),
    CONSTRAINT fk_ban_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT ck_ban_1 CHECK (soBan>0),
    CONSTRAINT ck_ban_2 CHECK (sucChua>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yêu cầu đặt bàn trực tuyến hoặc tiếp nhận tại quầy
CREATE TABLE IF NOT EXISTS DatBan (
    maDatBan VARCHAR(20) PRIMARY KEY,
    maChiNhanh VARCHAR(10) NOT NULL,
    maBan VARCHAR(10) NULL,
    nguon ENUM('WEB','TAI_QUAY') NOT NULL DEFAULT 'WEB',
    tenKhach VARCHAR(100) NULL,
    soDienThoai VARCHAR(15) NULL,
    soKhach SMALLINT NOT NULL,
    thoiGianDen DATETIME NOT NULL,
    thoiGianKetThucDuKien DATETIME NULL,
    thoiGianNhanBan DATETIME NULL,
    thoiGianKetThucThucTe DATETIME NULL,
    trangThai ENUM('CHO_XAC_NHAN','DA_XAC_NHAN','DANG_PHUC_VU','DA_HOAN_TAT','DA_HUY') NOT NULL DEFAULT 'CHO_XAC_NHAN',
    maNguoiTao VARCHAR(10) NULL,
    maNguoiXacNhan VARCHAR(10) NULL,
    maNguoiHuy VARCHAR(10) NULL,
    lyDoHuy VARCHAR(255) NULL,
    ghiChu VARCHAR(500) NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_datban_1 UNIQUE (maDatBan,maChiNhanh),
    CONSTRAINT fk_datban_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_datban_2 FOREIGN KEY (maNguoiTao) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT fk_datban_3 FOREIGN KEY (maNguoiXacNhan) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT fk_datban_4 FOREIGN KEY (maNguoiHuy) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT fk_datban_5 FOREIGN KEY (maBan,maChiNhanh) REFERENCES Ban(maBan,maChiNhanh),
    CONSTRAINT ck_datban_1 CHECK (soKhach>0),
    CONSTRAINT ck_datban_2 CHECK (thoiGianKetThucDuKien IS NULL OR thoiGianKetThucDuKien>thoiGianDen),
    CONSTRAINT ck_datban_3 CHECK (nguon<>'WEB' OR (tenKhach IS NOT NULL AND CHAR_LENGTH(TRIM(tenKhach))>0 AND soDienThoai IS NOT NULL AND soDienThoai REGEXP '^0[0-9]{9}$')),
    CONSTRAINT ck_datban_4 CHECK (trangThai<>'DA_HUY' OR (lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)),
    CONSTRAINT ck_datban_5 CHECK (thoiGianKetThucThucTe IS NULL OR (thoiGianNhanBan IS NOT NULL AND thoiGianKetThucThucTe>=thoiGianNhanBan)),
    CONSTRAINT ck_datban_6 CHECK (trangThai NOT IN ('DA_XAC_NHAN','DANG_PHUC_VU') OR (maBan IS NOT NULL AND thoiGianKetThucDuKien IS NOT NULL)),
    CONSTRAINT ck_datban_7 CHECK (trangThai<>'CHO_XAC_NHAN' OR maBan IS NULL),
    INDEX idx_datban_1 (soDienThoai,thoiGianDen),
    INDEX idx_datban_2 (maChiNhanh,trangThai,thoiGianDen),
    INDEX idx_datban_3 (maBan,trangThai,thoiGianDen,thoiGianKetThucDuKien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Danh mục nhóm món dùng chung trong chuỗi
CREATE TABLE IF NOT EXISTS NhomMon (
    maNhom VARCHAR(10) PRIMARY KEY,
    maChuoi VARCHAR(10) NOT NULL,
    tenNhom VARCHAR(100) NOT NULL,
    moTa VARCHAR(255) NULL,
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    CONSTRAINT uq_nhommon_1 UNIQUE (maChuoi,tenNhom),
    CONSTRAINT fk_nhommon_1 FOREIGN KEY (maChuoi) REFERENCES ChuoiNhaHang(maChuoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Danh mục món; giá niêm yết và trạng thái định mức
CREATE TABLE IF NOT EXISTS MonAn (
    maMon VARCHAR(10) PRIMARY KEY,
    maNhom VARCHAR(10) NOT NULL,
    tenMon VARCHAR(100) NOT NULL,
    donGia DECIMAL(18,2) NOT NULL,
    hinhAnh VARCHAR(255) NULL,
    moTa VARCHAR(500) NULL,
    trangThaiDinhMuc ENUM('CHUA_CO','DA_CO') NOT NULL DEFAULT 'CHUA_CO',
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    CONSTRAINT fk_monan_1 FOREIGN KEY (maNhom) REFERENCES NhomMon(maNhom),
    CONSTRAINT ck_monan_1 CHECK (donGia>=0),
    INDEX idx_monan_1 (maNhom,trangThai,tenMon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Món được bán tại từng chi nhánh, có thể ghi đè giá niêm yết
CREATE TABLE IF NOT EXISTS ThucDonChiNhanh (
    maChiNhanh VARCHAR(10) NOT NULL,
    maMon VARCHAR(10) NOT NULL,
    giaBan DECIMAL(18,2) NULL,
    trangThai ENUM('DANG_BAN','TAM_NGUNG') NOT NULL DEFAULT 'DANG_BAN',
    PRIMARY KEY(maChiNhanh,maMon),
    CONSTRAINT fk_thucdonchinhanh_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_thucdonchinhanh_2 FOREIGN KEY (maMon) REFERENCES MonAn(maMon),
    CONSTRAINT ck_thucdonchinhanh_1 CHECK (giaBan IS NULL OR giaBan>=0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Danh mục nguyên liệu toàn chuỗi; không chứa tồn kho gộp
CREATE TABLE IF NOT EXISTS NguyenVatLieu (
    maNVL VARCHAR(10) PRIMARY KEY,
    maChuoi VARCHAR(10) NOT NULL,
    tenNVL VARCHAR(100) NOT NULL,
    donViTinh VARCHAR(20) NOT NULL,
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    CONSTRAINT uq_nguyenvatlieu_1 UNIQUE (maChuoi,tenNVL),
    CONSTRAINT fk_nguyenvatlieu_1 FOREIGN KEY (maChuoi) REFERENCES ChuoiNhaHang(maChuoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Công thức một phần món; định lượng dùng đơn vị cơ sở của nguyên liệu
CREATE TABLE IF NOT EXISTS DinhMucNVL (
    maMon VARCHAR(10) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    dinhMuc DECIMAL(18,3) NOT NULL,
    PRIMARY KEY(maMon,maNVL),
    CONSTRAINT fk_dinhmucnvl_1 FOREIGN KEY (maMon) REFERENCES MonAn(maMon),
    CONSTRAINT fk_dinhmucnvl_2 FOREIGN KEY (maNVL) REFERENCES NguyenVatLieu(maNVL),
    CONSTRAINT ck_dinhmucnvl_1 CHECK (dinhMuc>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ảnh chụp công thức trước và sau mỗi lần sửa
CREATE TABLE IF NOT EXISTS LichSuDinhMuc (
    maLichSu VARCHAR(20) PRIMARY KEY,
    maMon VARCHAR(10) NOT NULL,
    maNguoiSua VARCHAR(10) NULL,
    nguoiSua VARCHAR(100) NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    noiDung VARCHAR(500) NOT NULL,
    congThucCu JSON NULL,
    congThucMoi JSON NOT NULL,
    CONSTRAINT fk_lichsudinhmuc_1 FOREIGN KEY (maMon) REFERENCES MonAn(maMon),
    CONSTRAINT fk_lichsudinhmuc_2 FOREIGN KEY (maNguoiSua) REFERENCES NhanVien(maNhanVien),
    INDEX idx_lichsudinhmuc_1 (maMon,thoiGian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đơn gọi món tại chỗ; không bao gồm thanh toán hoặc mang về
CREATE TABLE IF NOT EXISTS DonGoiMon (
    maDon VARCHAR(20) PRIMARY KEY,
    maChiNhanh VARCHAR(10) NOT NULL,
    maBan VARCHAR(10) NOT NULL,
    maNhanVien VARCHAR(10) NOT NULL,
    thoiGianTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('DANG_PHUC_VU','HOAN_TAT','DA_HUY') NOT NULL DEFAULT 'DANG_PHUC_VU',
    tongTien DECIMAL(18,2) NOT NULL DEFAULT 0,
    ghiChu VARCHAR(500) NULL,
    lyDoHuy VARCHAR(255) NULL,
    thoiGianGuiBep DATETIME NULL,
    CONSTRAINT uq_dongoimon_1 UNIQUE (maDon,maChiNhanh),
    CONSTRAINT fk_dongoimon_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_dongoimon_2 FOREIGN KEY (maBan,maChiNhanh) REFERENCES Ban(maBan,maChiNhanh),
    CONSTRAINT fk_dongoimon_3 FOREIGN KEY (maNhanVien) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT ck_dongoimon_1 CHECK (tongTien>=0),
    CONSTRAINT ck_dongoimon_2 CHECK (trangThai<>'DA_HUY' OR (lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)),
    INDEX idx_dongoimon_1 (maChiNhanh,thoiGianTao,trangThai),
    INDEX idx_dongoimon_2 (maBan,trangThai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mỗi lượt gọi món giữ đơn giá và tên món tại thời điểm gọi
CREATE TABLE IF NOT EXISTS ChiTietDonMon (
    maCTDon VARCHAR(20) PRIMARY KEY,
    maDon VARCHAR(20) NOT NULL,
    maChiNhanh VARCHAR(10) NOT NULL,
    maMon VARCHAR(10) NOT NULL,
    tenMon VARCHAR(100) NOT NULL,
    soLuong INT NOT NULL,
    donGia DECIMAL(18,2) NOT NULL,
    ghiChu VARCHAR(255) NULL,
    trangThai ENUM('CHO_CHE_BIEN','DANG_CHE_BIEN','HOAN_THANH','DA_PHUC_VU','DA_HUY') NOT NULL DEFAULT 'CHO_CHE_BIEN',
    lyDoHuy VARCHAR(255) NULL,
    gioGuiBep DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    gioTiepNhan DATETIME NULL,
    gioHoanThanh DATETIME NULL,
    maNhanVienBep VARCHAR(10) NULL,
    CONSTRAINT uq_chitietdonmon_1 UNIQUE (maCTDon,maChiNhanh),
    CONSTRAINT fk_chitietdonmon_1 FOREIGN KEY (maDon,maChiNhanh) REFERENCES DonGoiMon(maDon,maChiNhanh),
    CONSTRAINT fk_chitietdonmon_2 FOREIGN KEY (maChiNhanh,maMon) REFERENCES ThucDonChiNhanh(maChiNhanh,maMon),
    CONSTRAINT fk_chitietdonmon_3 FOREIGN KEY (maNhanVienBep) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT ck_chitietdonmon_1 CHECK (soLuong>0),
    CONSTRAINT ck_chitietdonmon_2 CHECK (donGia>=0),
    CONSTRAINT ck_chitietdonmon_3 CHECK (trangThai<>'DA_HUY' OR (lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)),
    CONSTRAINT ck_chitietdonmon_4 CHECK (gioHoanThanh IS NULL OR (gioTiepNhan IS NOT NULL AND gioHoanThanh>=gioTiepNhan)),
    INDEX idx_chitietdonmon_1 (maDon,trangThai),
    INDEX idx_chitietdonmon_2 (maChiNhanh,trangThai,gioGuiBep)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đề xuất bổ sung nguyên liệu do kho lập sau kiểm kho
CREATE TABLE IF NOT EXISTS PhieuDeXuat (
    maPhieu VARCHAR(20) PRIMARY KEY,
    maChiNhanh VARCHAR(10) NOT NULL,
    maNguoiLap VARCHAR(10) NOT NULL,
    ngayLap DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lyDo VARCHAR(500) NOT NULL,
    trangThai ENUM('CHO_XU_LY','DA_TAO_YEU_CAU') NOT NULL DEFAULT 'CHO_XU_LY',
    CONSTRAINT uq_phieudexuat_1 UNIQUE (maPhieu,maChiNhanh),
    CONSTRAINT fk_phieudexuat_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_phieudexuat_2 FOREIGN KEY (maNguoiLap) REFERENCES NhanVien(maNhanVien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Các nguyên liệu và lượng cần bổ sung trong phiếu
CREATE TABLE IF NOT EXISTS ChiTietDeXuat (
    maPhieu VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLuong DECIMAL(18,3) NOT NULL,
    ghiChu VARCHAR(255) NULL,
    PRIMARY KEY(maPhieu,maNVL),
    CONSTRAINT fk_chitietdexuat_1 FOREIGN KEY (maPhieu) REFERENCES PhieuDeXuat(maPhieu),
    CONSTRAINT fk_chitietdexuat_2 FOREIGN KEY (maNVL) REFERENCES NguyenVatLieu(maNVL),
    CONSTRAINT ck_chitietdexuat_1 CHECK (soLuong>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yêu cầu bổ sung từ phiếu; Chủ chuỗi lập phương án SD-17 hoặc SD-18
CREATE TABLE IF NOT EXISTS YeuCauNhapKho (
    maYeuCau VARCHAR(20) PRIMARY KEY,
    maPhieu VARCHAR(20) NOT NULL,
    maChiNhanh VARCHAR(10) NOT NULL,
    maNguoiTao VARCHAR(10) NOT NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('CHO_XU_LY','DA_LAP_PHUONG_AN') NOT NULL DEFAULT 'CHO_XU_LY',
    CONSTRAINT uq_yeucaunhapkho_1 UNIQUE (maPhieu),
    CONSTRAINT uq_yeucaunhapkho_2 UNIQUE (maYeuCau,maChiNhanh),
    CONSTRAINT fk_yeucaunhapkho_1 FOREIGN KEY (maPhieu,maChiNhanh) REFERENCES PhieuDeXuat(maPhieu,maChiNhanh),
    CONSTRAINT fk_yeucaunhapkho_2 FOREIGN KEY (maNguoiTao) REFERENCES NhanVien(maNhanVien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nhà cung cấp nguyên liệu và thông tin liên hệ
CREATE TABLE IF NOT EXISTS NhaCungCap (
    maNCC VARCHAR(10) PRIMARY KEY,
    tenNCC VARCHAR(150) NOT NULL,
    soDienThoai VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    diaChi VARCHAR(255) NULL,
    maSoThue VARCHAR(20) NULL,
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    CONSTRAINT uq_nhacungcap_1 UNIQUE (maSoThue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Danh mục khả năng cung cấp phục vụ layNhaCungCapPhuHop của SD-18
CREATE TABLE IF NOT EXISTS NhaCungCapNguyenLieu (
    maNCC VARCHAR(10) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    PRIMARY KEY(maNCC,maNVL),
    CONSTRAINT fk_nhacungcapnguyenlieu_1 FOREIGN KEY (maNCC) REFERENCES NhaCungCap(maNCC),
    CONSTRAINT fk_nhacungcapnguyenlieu_2 FOREIGN KEY (maNVL) REFERENCES NguyenVatLieu(maNVL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yêu cầu nhập NCC theo SD-18; phạm vi dừng ở tạo yêu cầu, chưa nhận hàng
CREATE TABLE IF NOT EXISTS DonNhapNCC (
    maDonNhap VARCHAR(20) PRIMARY KEY,
    maYeuCau VARCHAR(20) NOT NULL,
    maChiNhanh VARCHAR(10) NOT NULL,
    maNCC VARCHAR(10) NOT NULL,
    maNguoiLap VARCHAR(10) NOT NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ghiChu VARCHAR(500) NULL,
    trangThai ENUM('CHO_NHAP_HANG') NOT NULL DEFAULT 'CHO_NHAP_HANG',
    CONSTRAINT uq_donnhapncc_1 UNIQUE (maDonNhap,maChiNhanh),
    CONSTRAINT fk_donnhapncc_1 FOREIGN KEY (maYeuCau,maChiNhanh) REFERENCES YeuCauNhapKho(maYeuCau,maChiNhanh),
    CONSTRAINT fk_donnhapncc_2 FOREIGN KEY (maNCC) REFERENCES NhaCungCap(maNCC),
    CONSTRAINT fk_donnhapncc_3 FOREIGN KEY (maNguoiLap) REFERENCES NhanVien(maNhanVien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nguyên liệu và số lượng yêu cầu NCC, không phải hóa đơn
CREATE TABLE IF NOT EXISTS ChiTietDonNhapNCC (
    maDonNhap VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLuong DECIMAL(18,3) NOT NULL,
    PRIMARY KEY(maDonNhap,maNVL),
    CONSTRAINT fk_chitietdonnhapncc_1 FOREIGN KEY (maDonNhap) REFERENCES DonNhapNCC(maDonNhap),
    CONSTRAINT fk_chitietdonnhapncc_2 FOREIGN KEY (maNVL) REFERENCES NguyenVatLieu(maNVL),
    CONSTRAINT ck_chitietdonnhapncc_1 CHECK (soLuong>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lô tại chi nhánh và số dư; gộp thông tin lô phục vụ tra cứu còn hạn SD-17/30
CREATE TABLE IF NOT EXISTS TonKhoTheoLo (
    maChiNhanh VARCHAR(10) NOT NULL,
    maLo VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLoNCC VARCHAR(100) NULL,
    ngaySanXuat DATE NULL,
    hanSuDung DATE NOT NULL,
    soLuongTon DECIMAL(18,3) NOT NULL DEFAULT 0,
    trangThai ENUM('HOAT_DONG','NGUNG') NOT NULL DEFAULT 'HOAT_DONG',
    capNhatLuc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(maChiNhanh,maLo),
    CONSTRAINT uq_tonkhotheolo_1 UNIQUE (maChiNhanh,maLo,maNVL),
    CONSTRAINT fk_tonkhotheolo_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_tonkhotheolo_2 FOREIGN KEY (maNVL) REFERENCES NguyenVatLieu(maNVL),
    CONSTRAINT ck_tonkhotheolo_1 CHECK (soLuongTon>=0),
    CONSTRAINT ck_tonkhotheolo_2 CHECK (ngaySanXuat IS NULL OR hanSuDung>=ngaySanXuat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yêu cầu NVL SD-17 và xác nhận chi nhánh nguồn SD-31; không xuất/nhận thực tế
CREATE TABLE IF NOT EXISTS DieuChuyenKho (
    maDieuChuyen VARCHAR(20) PRIMARY KEY,
    maYeuCau VARCHAR(20) NOT NULL,
    maChiNhanhNhan VARCHAR(10) NOT NULL,
    maChiNhanhNguon VARCHAR(10) NOT NULL,
    maNguoiTao VARCHAR(10) NOT NULL,
    maNguoiXacNhanNguon VARCHAR(10) NULL,
    ngayXacNhanNguon DATETIME NULL,
    lyDo VARCHAR(500) NOT NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('CHO_XAC_NHAN_NGUON','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_XAC_NHAN_NGUON',
    lyDoTuChoi VARCHAR(500) NULL,
    CONSTRAINT uq_dieuchuyenkho_1 UNIQUE (maDieuChuyen,maChiNhanhNguon),
    CONSTRAINT uq_dieuchuyenkho_2 UNIQUE (maDieuChuyen,maChiNhanhNhan),
    CONSTRAINT fk_dieuchuyenkho_1 FOREIGN KEY (maYeuCau,maChiNhanhNhan) REFERENCES YeuCauNhapKho(maYeuCau,maChiNhanh),
    CONSTRAINT fk_dieuchuyenkho_2 FOREIGN KEY (maChiNhanhNguon) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_dieuchuyenkho_3 FOREIGN KEY (maNguoiTao) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT fk_dieuchuyenkho_4 FOREIGN KEY (maNguoiXacNhanNguon) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT ck_dieuchuyenkho_1 CHECK (maChiNhanhNguon<>maChiNhanhNhan),
    CONSTRAINT ck_dieuchuyenkho_2 CHECK (trangThai='CHO_XAC_NHAN_NGUON' OR (maNguoiXacNhanNguon IS NOT NULL AND ngayXacNhanNguon IS NOT NULL)),
    CONSTRAINT ck_dieuchuyenkho_3 CHECK (trangThai<>'TU_CHOI' OR (lyDoTuChoi IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoTuChoi))>0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nguyên liệu và số lượng đề nghị điều chuyển; chưa phân bổ/xuất lô
CREATE TABLE IF NOT EXISTS ChiTietDieuChuyenKho (
    maDieuChuyen VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLuongYeuCau DECIMAL(18,3) NOT NULL,
    PRIMARY KEY(maDieuChuyen,maNVL),
    CONSTRAINT fk_chitietdieuchuyenkho_1 FOREIGN KEY (maDieuChuyen) REFERENCES DieuChuyenKho(maDieuChuyen),
    CONSTRAINT fk_chitietdieuchuyenkho_2 FOREIGN KEY (maNVL) REFERENCES NguyenVatLieu(maNVL),
    CONSTRAINT ck_chitietdieuchuyenkho_1 CHECK (soLuongYeuCau>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lịch sử biến động cho quản lý kho SD-30 và báo cáo nhập/xuất/tồn SD-32; không thêm quy trình nhận hàng
CREATE TABLE IF NOT EXISTS GiaoDichKho (
    maGiaoDich VARCHAR(20) PRIMARY KEY,
    maChiNhanh VARCHAR(10) NOT NULL,
    maNguoiLap VARCHAR(10) NOT NULL,
    loai ENUM('TON_DAU','NHAP','XUAT','DIEU_CHINH_TANG','DIEU_CHINH_GIAM') NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lyDo VARCHAR(500) NOT NULL,
    trangThai ENUM('DA_GHI_SO') NOT NULL DEFAULT 'DA_GHI_SO',
    khoaChongLap VARCHAR(100) NOT NULL,
    CONSTRAINT uq_giaodichkho_1 UNIQUE (khoaChongLap),
    CONSTRAINT uq_giaodichkho_2 UNIQUE (maGiaoDich,maChiNhanh),
    CONSTRAINT fk_giaodichkho_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_giaodichkho_2 FOREIGN KEY (maNguoiLap) REFERENCES NhanVien(maNhanVien),
    INDEX idx_giaodichkho_1 (maChiNhanh,thoiGian)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Số lượng biến động theo lô; chiều tăng/giảm lấy từ loại giao dịch
CREATE TABLE IF NOT EXISTS ChiTietGiaoDichKho (
    maGiaoDich VARCHAR(20) NOT NULL,
    maChiNhanh VARCHAR(10) NOT NULL,
    maLo VARCHAR(20) NOT NULL,
    maNVL VARCHAR(10) NOT NULL,
    soLuong DECIMAL(18,3) NOT NULL,
    PRIMARY KEY(maGiaoDich,maLo),
    CONSTRAINT fk_chitietgiaodichkho_1 FOREIGN KEY (maGiaoDich,maChiNhanh) REFERENCES GiaoDichKho(maGiaoDich,maChiNhanh),
    CONSTRAINT fk_chitietgiaodichkho_2 FOREIGN KEY (maChiNhanh,maLo,maNVL) REFERENCES TonKhoTheoLo(maChiNhanh,maLo,maNVL),
    CONSTRAINT ck_chitietgiaodichkho_1 CHECK (soLuong>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yêu cầu hỗ trợ nhân viên; Chủ chuỗi có thể duyệt/từ chối nhưng không tự điều động
CREATE TABLE IF NOT EXISTS YeuCauDieuChuyen (
    maYeuCauDC VARCHAR(20) PRIMARY KEY,
    maChiNhanh VARCHAR(10) NOT NULL,
    viTri VARCHAR(100) NOT NULL,
    soLuong INT NOT NULL,
    thoiGianBatDau DATETIME NOT NULL,
    thoiGianKetThuc DATETIME NOT NULL,
    lyDo VARCHAR(500) NOT NULL,
    maNguoiGui VARCHAR(10) NOT NULL,
    ngayGui DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    maNguoiDuyet VARCHAR(10) NULL,
    ngayDuyet DATETIME NULL,
    lyDoTuChoi VARCHAR(500) NULL,
    CONSTRAINT uq_yeucaudieuchuyen_1 UNIQUE (maYeuCauDC,maChiNhanh),
    CONSTRAINT fk_yeucaudieuchuyen_1 FOREIGN KEY (maChiNhanh) REFERENCES ChiNhanh(maChiNhanh),
    CONSTRAINT fk_yeucaudieuchuyen_2 FOREIGN KEY (maNguoiGui) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT fk_yeucaudieuchuyen_3 FOREIGN KEY (maNguoiDuyet) REFERENCES NhanVien(maNhanVien),
    CONSTRAINT ck_yeucaudieuchuyen_1 CHECK (soLuong>0),
    CONSTRAINT ck_yeucaudieuchuyen_2 CHECK (thoiGianKetThuc>thoiGianBatDau),
    CONSTRAINT ck_yeucaudieuchuyen_3 CHECK (
        (trangThai = 'CHO_DUYET' AND maNguoiDuyet IS NULL AND ngayDuyet IS NULL AND lyDoTuChoi IS NULL)
        OR (trangThai = 'DA_DUYET' AND maNguoiDuyet IS NOT NULL AND ngayDuyet IS NOT NULL AND lyDoTuChoi IS NULL)
        OR (trangThai = 'TU_CHOI' AND maNguoiDuyet IS NOT NULL AND ngayDuyet IS NOT NULL
            AND lyDoTuChoi IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoTuChoi)) > 0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW V_TonKho AS
SELECT l.maChiNhanh,l.maNVL,n.tenNVL,n.donViTinh,SUM(l.soLuongTon) soLuongTon,
SUM(CASE WHEN l.hanSuDung>=CURRENT_DATE AND l.trangThai='HOAT_DONG' THEN l.soLuongTon ELSE 0 END) soLuongConHan,
SUM(CASE WHEN l.hanSuDung<CURRENT_DATE THEN l.soLuongTon ELSE 0 END) soLuongHetHan
FROM TonKhoTheoLo l JOIN NguyenVatLieu n ON n.maNVL=l.maNVL
GROUP BY l.maChiNhanh,l.maNVL,n.tenNVL,n.donViTinh;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW V_BienDongKho AS
SELECT g.maGiaoDich,g.maChiNhanh,g.thoiGian,g.loai,c.maLo,c.maNVL,c.soLuong,
CASE WHEN g.loai IN ('TON_DAU','NHAP','DIEU_CHINH_TANG') THEN c.soLuong ELSE -c.soLuong END soLuongCoDau
FROM GiaoDichKho g JOIN ChiTietGiaoDichKho c ON c.maGiaoDich=g.maGiaoDich AND c.maChiNhanh=g.maChiNhanh;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW V_DoiSoatKho AS
SELECT l.maChiNhanh,l.maLo,l.maNVL,l.soLuongTon,COALESCE(SUM(b.soLuongCoDau),0) soLuongTheoSo,
l.soLuongTon-COALESCE(SUM(b.soLuongCoDau),0) chenhLech
FROM TonKhoTheoLo l LEFT JOIN V_BienDongKho b ON b.maChiNhanh=l.maChiNhanh AND b.maLo=l.maLo
GROUP BY l.maChiNhanh,l.maLo,l.maNVL,l.soLuongTon;

CREATE TABLE IF NOT EXISTS SchemaMigration (
phienBan VARCHAR(64) PRIMARY KEY,checksum CHAR(64) NOT NULL,apDungLuc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
