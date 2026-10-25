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
