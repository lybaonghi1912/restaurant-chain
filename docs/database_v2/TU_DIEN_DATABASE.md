# Từ điển database V2 — 32 sequence

30 bảng nghiệp vụ + GioiHanDangNhap và SchemaMigration (kỹ thuật); 3 view kho. Tên PascalCase/camelCase giữ theo báo cáo và source hiện có; không trộn một bộ tên snake_case mới. Toàn bộ InnoDB, utf8mb4_unicode_ci. Khóa ngoại mặc định RESTRICT/NO ACTION; không cascade xóa lịch sử.

## Ma trận truy vết bảng → sequence

| Bảng | SD sử dụng / lý do |
|---|---|
| Ban | SD-03, SD-04, SD-05, SD-06, SD-13, SD-14, SD-15, SD-25 |
| CaLamViec | SD-08, SD-10 |
| ChiNhanh | SD-01, SD-02, SD-08, SD-10, SD-13, SD-14, SD-15, SD-16, SD-17, SD-29, SD-30, SD-32 |
| ChiTietDeXuat | SD-17, SD-18, SD-28 |
| ChiTietDieuChuyenKho | SD-17, SD-31 |
| ChiTietDonMon | SD-09, SD-22, SD-25, SD-26, SD-27 |
| ChiTietDonNhapNCC | SD-18 |
| ChiTietGiaoDichKho | SD-30, SD-32 |
| ChuoiNhaHang | SD-13 |
| DatBan | SD-01, SD-02, SD-03, SD-04, SD-05, SD-06, SD-16 |
| DieuChuyenKho | SD-17, SD-31 |
| DinhMucNVL | SD-12, SD-22, SD-24 |
| DonGoiMon | SD-09, SD-16, SD-25, SD-26, SD-27 |
| DonNhapNCC | SD-18 |
| GiaoDichKho | SD-30, SD-32 |
| GioiHanDangNhap | Kỹ thuật đăng nhập |
| LichSuDinhMuc | SD-12 |
| MonAn | SD-12, SD-13, SD-14, SD-15, SD-19, SD-20, SD-21, SD-22, SD-23, SD-24, SD-25, SD-26 |
| NguyenVatLieu | SD-12, SD-17, SD-18, SD-24, SD-28, SD-30, SD-31, SD-32 |
| NhaCungCap | SD-18 |
| NhaCungCapNguyenLieu | SD-18 |
| NhanVien | SD-07, SD-08, SD-10, SD-11, SD-13, SD-14, SD-15, SD-16, SD-29 |
| NhatKyNhanSu | SD-07, SD-08, SD-11 |
| NhomMon | SD-19, SD-20, SD-21, SD-23, SD-24, SD-25, SD-26 |
| PhieuDeXuat | SD-17, SD-18, SD-28 |
| SchemaMigration | Kỹ thuật phiên bản schema |
| TaiKhoan | SD-07, SD-08, SD-11 |
| ThucDonChiNhanh | SD-13, SD-14, SD-15, SD-22, SD-25, SD-26 |
| TonKhoTheoLo | SD-17, SD-30, SD-31, SD-32 |
| VaiTro | SD-08, SD-10 |
| YeuCauDieuChuyen | SD-29 |
| YeuCauNhapKho | SD-17, SD-18, SD-28 |

## Ban

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maBan | varchar(10) | NO | PRI |
| maChiNhanh | varchar(10) | NO | MUL |
| soBan | int | NO |  |
| tenBan | varchar(30) | NO |  |
| sucChua | smallint | NO |  |
| khuVuc | varchar(50) | NO |  |
| viTri | varchar(100) | YES |  |
| trangThai | enum('TRONG','DANG_PHUC_VU','NGUNG') | NO |  |

Mục đích: Bàn vật lý tại chi nhánh; trạng thái phục vụ khác lịch giữ bàn.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- CHECK `soBan>0`.
- CHECK `sucChua>0`.
- UNIQUE (maChiNhanh,soBan).
- UNIQUE (maBan,maChiNhanh).

## CaLamViec

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maCa | varchar(10) | NO | PRI |
| tenCa | varchar(50) | NO |  |
| thoiGianBatDau | time | NO |  |
| thoiGianKetThuc | time | NO |  |
| quaNgay | tinyint(1) | NO |  |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |

Mục đích: Danh mục ca làm việc, hỗ trợ ca qua đêm.
- CHECK `thoiGianBatDau <> thoiGianKetThuc`.
- CHECK `(quaNgay=0 AND thoiGianKetThuc>thoiGianBatDau) OR (quaNgay=1 AND thoiGianKetThuc<thoiGianBatDau)`.

## ChiNhanh

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maChiNhanh | varchar(10) | NO | PRI |
| tenChiNhanh | varchar(100) | NO |  |
| diaChi | varchar(255) | YES |  |
| soDienThoai | varchar(15) | YES |  |
| maChuoi | varchar(10) | NO | MUL |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |
| maQuanLy | varchar(10) | YES | MUL |
| viDo | decimal(10,7) | YES |  |
| kinhDo | decimal(10,7) | YES |  |

## ChiTietDeXuat

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maPhieu | varchar(20) | NO | PRI |
| maNVL | varchar(10) | NO | PRI |
| soLuong | decimal(18,3) | NO |  |
| ghiChu | varchar(255) | YES |  |

Mục đích: Các nguyên liệu và lượng cần bổ sung trong phiếu.
- FK (maPhieu) → PhieuDeXuat(maPhieu).
- FK (maNVL) → NguyenVatLieu(maNVL).
- CHECK `soLuong>0`.

## ChiTietDieuChuyenKho

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maDieuChuyen | varchar(20) | NO | PRI |
| maNVL | varchar(10) | NO | PRI |
| soLuongYeuCau | decimal(18,3) | NO |  |

Mục đích: Nguyên liệu và số lượng đề nghị điều chuyển; chưa phân bổ/xuất lô.
- FK (maDieuChuyen) → DieuChuyenKho(maDieuChuyen).
- FK (maNVL) → NguyenVatLieu(maNVL).
- CHECK `soLuongYeuCau>0`.

## ChiTietDonMon

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maCTDon | varchar(20) | NO | PRI |
| maDon | varchar(20) | NO | MUL |
| maChiNhanh | varchar(10) | NO | MUL |
| maMon | varchar(10) | NO |  |
| tenMon | varchar(100) | NO |  |
| soLuong | int | NO |  |
| donGia | decimal(18,2) | NO |  |
| ghiChu | varchar(255) | YES |  |
| trangThai | enum('CHO_CHE_BIEN','DANG_CHE_BIEN','HOAN_THANH','DA_PHUC_VU','DA_HUY') | NO |  |
| lyDoHuy | varchar(255) | YES |  |
| gioGuiBep | datetime | NO |  |
| gioTiepNhan | datetime | YES |  |
| gioHoanThanh | datetime | YES |  |
| maNhanVienBep | varchar(10) | YES | MUL |

Mục đích: Mỗi lượt gọi món giữ đơn giá và tên món tại thời điểm gọi.
- FK (maDon,maChiNhanh) → DonGoiMon(maDon,maChiNhanh).
- FK (maChiNhanh,maMon) → ThucDonChiNhanh(maChiNhanh,maMon).
- FK (maNhanVienBep) → NhanVien(maNhanVien).
- CHECK `soLuong>0`.
- CHECK `donGia>=0`.
- CHECK `trangThai<>'DA_HUY' OR (lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)`.
- CHECK `gioHoanThanh IS NULL OR (gioTiepNhan IS NOT NULL AND gioHoanThanh>=gioTiepNhan)`.
- UNIQUE (maCTDon,maChiNhanh).

## ChiTietDonNhapNCC

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maDonNhap | varchar(20) | NO | PRI |
| maNVL | varchar(10) | NO | PRI |
| soLuong | decimal(18,3) | NO |  |

Mục đích: Nguyên liệu và số lượng yêu cầu NCC, không phải hóa đơn.
- FK (maDonNhap) → DonNhapNCC(maDonNhap).
- FK (maNVL) → NguyenVatLieu(maNVL).
- CHECK `soLuong>0`.

## ChiTietGiaoDichKho

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maGiaoDich | varchar(20) | NO | PRI |
| maChiNhanh | varchar(10) | NO | MUL |
| maLo | varchar(20) | NO | PRI |
| maNVL | varchar(10) | NO |  |
| soLuong | decimal(18,3) | NO |  |

Mục đích: Số lượng biến động theo lô; chiều tăng/giảm lấy từ loại giao dịch.
- FK (maGiaoDich,maChiNhanh) → GiaoDichKho(maGiaoDich,maChiNhanh).
- FK (maChiNhanh,maLo,maNVL) → TonKhoTheoLo(maChiNhanh,maLo,maNVL).
- CHECK `soLuong>0`.

## ChuoiNhaHang

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maChuoi | varchar(10) | NO | PRI |
| tenChuoi | varchar(100) | NO |  |

## DatBan

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maDatBan | varchar(20) | NO | PRI |
| maChiNhanh | varchar(10) | NO | MUL |
| maBan | varchar(10) | YES | MUL |
| nguon | enum('WEB','TAI_QUAY') | NO |  |
| tenKhach | varchar(100) | YES |  |
| soDienThoai | varchar(15) | YES | MUL |
| soKhach | smallint | NO |  |
| thoiGianDen | datetime | NO |  |
| thoiGianKetThucDuKien | datetime | YES |  |
| thoiGianNhanBan | datetime | YES |  |
| thoiGianKetThucThucTe | datetime | YES |  |
| trangThai | enum('CHO_XAC_NHAN','DA_XAC_NHAN','DANG_PHUC_VU','DA_HOAN_TAT','DA_HUY') | NO |  |
| maNguoiTao | varchar(10) | YES | MUL |
| maNguoiXacNhan | varchar(10) | YES | MUL |
| maNguoiHuy | varchar(10) | YES | MUL |
| lyDoHuy | varchar(255) | YES |  |
| ghiChu | varchar(500) | YES |  |
| ngayTao | datetime | NO |  |

Mục đích: Yêu cầu đặt bàn trực tuyến hoặc tiếp nhận tại quầy.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- FK (maNguoiTao) → NhanVien(maNhanVien).
- FK (maNguoiXacNhan) → NhanVien(maNhanVien).
- FK (maNguoiHuy) → NhanVien(maNhanVien).
- FK (maBan,maChiNhanh) → Ban(maBan,maChiNhanh).
- CHECK `soKhach>0`.
- CHECK `thoiGianKetThucDuKien IS NULL OR thoiGianKetThucDuKien>thoiGianDen`.
- CHECK `nguon<>'WEB' OR (tenKhach IS NOT NULL AND CHAR_LENGTH(TRIM(tenKhach))>0 AND soDienThoai IS NOT NULL AND soDienThoai REGEXP '^0[0-9]{9}$')`.
- CHECK `trangThai<>'DA_HUY' OR (lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)`.
- CHECK `thoiGianKetThucThucTe IS NULL OR (thoiGianNhanBan IS NOT NULL AND thoiGianKetThucThucTe>=thoiGianNhanBan)`.
- CHECK `trangThai NOT IN ('DA_XAC_NHAN','DANG_PHUC_VU') OR (maBan IS NOT NULL AND thoiGianKetThucDuKien IS NOT NULL)`.
- CHECK `trangThai<>'CHO_XAC_NHAN' OR maBan IS NULL`.
- UNIQUE (maDatBan,maChiNhanh).

## DieuChuyenKho

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maDieuChuyen | varchar(20) | NO | PRI |
| maYeuCau | varchar(20) | NO | MUL |
| maChiNhanhNhan | varchar(10) | NO |  |
| maChiNhanhNguon | varchar(10) | NO | MUL |
| maNguoiTao | varchar(10) | NO | MUL |
| maNguoiXacNhanNguon | varchar(10) | YES | MUL |
| ngayXacNhanNguon | datetime | YES |  |
| lyDo | varchar(500) | NO |  |
| ngayTao | datetime | NO |  |
| trangThai | enum('CHO_XAC_NHAN_NGUON','DA_DUYET','TU_CHOI') | NO |  |
| lyDoTuChoi | varchar(500) | YES |  |

Mục đích: Yêu cầu NVL SD-17 và xác nhận chi nhánh nguồn SD-31; không xuất/nhận thực tế.
- FK (maYeuCau,maChiNhanhNhan) → YeuCauNhapKho(maYeuCau,maChiNhanh).
- FK (maChiNhanhNguon) → ChiNhanh(maChiNhanh).
- FK (maNguoiTao) → NhanVien(maNhanVien).
- FK (maNguoiXacNhanNguon) → NhanVien(maNhanVien).
- CHECK `maChiNhanhNguon<>maChiNhanhNhan`.
- CHECK `trangThai='CHO_XAC_NHAN_NGUON' OR (maNguoiXacNhanNguon IS NOT NULL AND ngayXacNhanNguon IS NOT NULL)`.
- CHECK `trangThai<>'TU_CHOI' OR (lyDoTuChoi IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoTuChoi))>0)`.
- UNIQUE (maDieuChuyen,maChiNhanhNguon).
- UNIQUE (maDieuChuyen,maChiNhanhNhan).

## DinhMucNVL

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maMon | varchar(10) | NO | PRI |
| maNVL | varchar(10) | NO | PRI |
| dinhMuc | decimal(18,3) | NO |  |

Mục đích: Công thức một phần món; định lượng dùng đơn vị cơ sở của nguyên liệu.
- FK (maMon) → MonAn(maMon).
- FK (maNVL) → NguyenVatLieu(maNVL).
- CHECK `dinhMuc>0`.

## DonGoiMon

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maDon | varchar(20) | NO | PRI |
| maChiNhanh | varchar(10) | NO | MUL |
| maBan | varchar(10) | NO | MUL |
| maNhanVien | varchar(10) | NO | MUL |
| thoiGianTao | datetime | NO |  |
| trangThai | enum('DANG_PHUC_VU','HOAN_TAT','DA_HUY') | NO |  |
| tongTien | decimal(18,2) | NO |  |
| ghiChu | varchar(500) | YES |  |
| lyDoHuy | varchar(255) | YES |  |
| thoiGianGuiBep | datetime | YES |  |

Mục đích: Đơn gọi món tại chỗ; không bao gồm thanh toán hoặc mang về.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- FK (maBan,maChiNhanh) → Ban(maBan,maChiNhanh).
- FK (maNhanVien) → NhanVien(maNhanVien).
- CHECK `tongTien>=0`.
- CHECK `trangThai<>'DA_HUY' OR (lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)`.
- UNIQUE (maDon,maChiNhanh).

## DonNhapNCC

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maDonNhap | varchar(20) | NO | PRI |
| maYeuCau | varchar(20) | NO | MUL |
| maChiNhanh | varchar(10) | NO |  |
| maNCC | varchar(10) | NO | MUL |
| maNguoiLap | varchar(10) | NO | MUL |
| ngayTao | datetime | NO |  |
| ghiChu | varchar(500) | YES |  |
| trangThai | enum('CHO_NHAP_HANG') | NO |  |

Mục đích: Yêu cầu nhập NCC theo SD-18; phạm vi dừng ở tạo yêu cầu, chưa nhận hàng.
- FK (maYeuCau,maChiNhanh) → YeuCauNhapKho(maYeuCau,maChiNhanh).
- FK (maNCC) → NhaCungCap(maNCC).
- FK (maNguoiLap) → NhanVien(maNhanVien).
- UNIQUE (maDonNhap,maChiNhanh).

## GiaoDichKho

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maGiaoDich | varchar(20) | NO | PRI |
| maChiNhanh | varchar(10) | NO | MUL |
| maNguoiLap | varchar(10) | NO | MUL |
| loai | enum('TON_DAU','NHAP','XUAT','DIEU_CHINH_TANG','DIEU_CHINH_GIAM') | NO |  |
| thoiGian | datetime | NO |  |
| lyDo | varchar(500) | NO |  |
| trangThai | enum('DA_GHI_SO') | NO |  |
| khoaChongLap | varchar(100) | NO | UNI |

Mục đích: Lịch sử biến động cho quản lý kho SD-30 và báo cáo nhập/xuất/tồn SD-32; không thêm quy trình nhận hàng.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- FK (maNguoiLap) → NhanVien(maNhanVien).
- UNIQUE (khoaChongLap).
- UNIQUE (maGiaoDich,maChiNhanh).

## GioiHanDangNhap

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| khoa | char(64) | NO | PRI |
| soLanSai | int unsigned | NO |  |
| batDau | bigint unsigned | NO | MUL |
| khoaDen | bigint unsigned | NO |  |

## LichSuDinhMuc

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maLichSu | varchar(20) | NO | PRI |
| maMon | varchar(10) | NO | MUL |
| maNguoiSua | varchar(10) | YES | MUL |
| nguoiSua | varchar(100) | NO |  |
| thoiGian | datetime | NO |  |
| noiDung | varchar(500) | NO |  |
| congThucCu | json | YES |  |
| congThucMoi | json | NO |  |

Mục đích: Ảnh chụp công thức trước và sau mỗi lần sửa.
- FK (maMon) → MonAn(maMon).
- FK (maNguoiSua) → NhanVien(maNhanVien).

## MonAn

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maMon | varchar(10) | NO | PRI |
| maNhom | varchar(10) | NO | MUL |
| tenMon | varchar(100) | NO |  |
| donGia | decimal(18,2) | NO |  |
| hinhAnh | varchar(255) | YES |  |
| moTa | varchar(500) | YES |  |
| trangThaiDinhMuc | enum('CHUA_CO','DA_CO') | NO |  |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |

Mục đích: Danh mục món; giá niêm yết và trạng thái định mức.
- FK (maNhom) → NhomMon(maNhom).
- CHECK `donGia>=0`.

## NguyenVatLieu

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maNVL | varchar(10) | NO | PRI |
| maChuoi | varchar(10) | NO | MUL |
| tenNVL | varchar(100) | NO |  |
| donViTinh | varchar(20) | NO |  |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |

Mục đích: Danh mục nguyên liệu toàn chuỗi; không chứa tồn kho gộp.
- FK (maChuoi) → ChuoiNhaHang(maChuoi).
- UNIQUE (maChuoi,tenNVL).

## NhaCungCap

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maNCC | varchar(10) | NO | PRI |
| tenNCC | varchar(150) | NO |  |
| soDienThoai | varchar(15) | YES |  |
| email | varchar(100) | YES |  |
| diaChi | varchar(255) | YES |  |
| maSoThue | varchar(20) | YES | UNI |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |

Mục đích: Nhà cung cấp nguyên liệu và thông tin liên hệ.
- UNIQUE (maSoThue).

## NhaCungCapNguyenLieu

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maNCC | varchar(10) | NO | PRI |
| maNVL | varchar(10) | NO | PRI |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |

Mục đích: Danh mục khả năng cung cấp phục vụ layNhaCungCapPhuHop của SD-18.
- FK (maNCC) → NhaCungCap(maNCC).
- FK (maNVL) → NguyenVatLieu(maNVL).

## NhanVien

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maNhanVien | varchar(10) | NO | PRI |
| hoTen | varchar(100) | NO |  |
| maVaiTro | varchar(10) | NO | MUL |
| maChiNhanh | varchar(10) | YES | MUL |
| trangThai | enum('DANG_LAM','NGHI_VIEC') | NO |  |
| ngaySinh | date | YES |  |
| gioiTinh | tinyint | YES |  |
| soCCCD | varchar(12) | YES | UNI |
| soDienThoai | varchar(15) | YES |  |
| email | varchar(100) | YES |  |
| diaChiThuongTru | varchar(255) | YES |  |
| ngayBatDauLamViec | date | YES |  |
| maCa | varchar(10) | YES | MUL |

## NhatKyNhanSu

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maNhatKy | varchar(20) | NO | PRI |
| maNhanVien | varchar(10) | NO | MUL |
| thoiGian | datetime | NO |  |
| maNguoiSua | varchar(10) | YES | MUL |
| nguoiSua | varchar(100) | NO |  |
| noiDung | varchar(1000) | NO |  |
| duLieuCu | json | YES |  |
| duLieuMoi | json | YES |  |

Mục đích: Lịch sử thay đổi nhân viên, giữ cả nội dung và người sửa.
- FK (maNhanVien) → NhanVien(maNhanVien).
- FK (maNguoiSua) → NhanVien(maNhanVien).

## NhomMon

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maNhom | varchar(10) | NO | PRI |
| maChuoi | varchar(10) | NO | MUL |
| tenNhom | varchar(100) | NO |  |
| moTa | varchar(255) | YES |  |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |

Mục đích: Danh mục nhóm món dùng chung trong chuỗi.
- FK (maChuoi) → ChuoiNhaHang(maChuoi).
- UNIQUE (maChuoi,tenNhom).

## PhieuDeXuat

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maPhieu | varchar(20) | NO | PRI |
| maChiNhanh | varchar(10) | NO | MUL |
| maNguoiLap | varchar(10) | NO | MUL |
| ngayLap | datetime | NO |  |
| lyDo | varchar(500) | NO |  |
| trangThai | enum('CHO_XU_LY','DA_TAO_YEU_CAU') | NO |  |

Mục đích: Đề xuất bổ sung nguyên liệu do kho lập sau kiểm kho.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- FK (maNguoiLap) → NhanVien(maNhanVien).
- UNIQUE (maPhieu,maChiNhanh).

## SchemaMigration

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| phienBan | varchar(64) | NO | PRI |
| checksum | char(64) | NO |  |
| apDungLuc | datetime | NO |  |

## TaiKhoan

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| tenDangNhap | varchar(50) | NO | PRI |
| matKhau | varchar(255) | NO |  |
| maNhanVien | varchar(10) | NO | UNI |
| trangThai | enum('HOAT_DONG','KHOA') | NO |  |
| phienBan | int unsigned | NO |  |
| ngayTao | datetime | NO |  |
| lanDangNhapCuoi | datetime | YES |  |

## ThucDonChiNhanh

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maChiNhanh | varchar(10) | NO | PRI |
| maMon | varchar(10) | NO | PRI |
| giaBan | decimal(18,2) | YES |  |
| trangThai | enum('DANG_BAN','TAM_NGUNG') | NO |  |

Mục đích: Món được bán tại từng chi nhánh, có thể ghi đè giá niêm yết.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- FK (maMon) → MonAn(maMon).
- CHECK `giaBan IS NULL OR giaBan>=0`.

## TonKhoTheoLo

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maChiNhanh | varchar(10) | NO | PRI |
| maLo | varchar(20) | NO | PRI |
| maNVL | varchar(10) | NO | MUL |
| soLoNCC | varchar(100) | YES |  |
| ngaySanXuat | date | YES |  |
| hanSuDung | date | NO |  |
| soLuongTon | decimal(18,3) | NO |  |
| trangThai | enum('HOAT_DONG','NGUNG') | NO |  |
| capNhatLuc | datetime | NO |  |

Mục đích: Lô tại chi nhánh và số dư; gộp thông tin lô phục vụ tra cứu còn hạn SD-17/30.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- FK (maNVL) → NguyenVatLieu(maNVL).
- CHECK `soLuongTon>=0`.
- CHECK `ngaySanXuat IS NULL OR hanSuDung>=ngaySanXuat`.
- UNIQUE (maChiNhanh,maLo,maNVL).

## VaiTro

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maVaiTro | varchar(10) | NO | PRI |
| tenVaiTro | varchar(50) | NO | UNI |

## YeuCauDieuChuyen

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maYeuCauDC | varchar(20) | NO | PRI |
| maChiNhanh | varchar(10) | NO | MUL |
| viTri | varchar(100) | NO |  |
| soLuong | int | NO |  |
| thoiGianBatDau | datetime | NO |  |
| thoiGianKetThuc | datetime | NO |  |
| lyDo | varchar(500) | NO |  |
| maNguoiGui | varchar(10) | NO | MUL |
| ngayGui | datetime | NO |  |
| trangThai | enum('CHO_DUYET') | NO |  |

Mục đích: Yêu cầu hỗ trợ nhân viên SD-29; chưa duyệt hoặc điều động nhân viên thực tế.
- FK (maChiNhanh) → ChiNhanh(maChiNhanh).
- FK (maNguoiGui) → NhanVien(maNhanVien).
- CHECK `soLuong>0`.
- CHECK `thoiGianKetThuc>thoiGianBatDau`.
- UNIQUE (maYeuCauDC,maChiNhanh).

## YeuCauNhapKho

| Cột | Kiểu | NULL | Khóa |
|---|---|---|---|
| maYeuCau | varchar(20) | NO | PRI |
| maPhieu | varchar(20) | NO | UNI |
| maChiNhanh | varchar(10) | NO |  |
| maNguoiTao | varchar(10) | NO | MUL |
| ngayTao | datetime | NO |  |
| trangThai | enum('CHO_XU_LY','DA_LAP_PHUONG_AN') | NO |  |

Mục đích: Yêu cầu bổ sung từ phiếu; Chủ chuỗi lập phương án SD-17 hoặc SD-18.
- FK (maPhieu,maChiNhanh) → PhieuDeXuat(maPhieu,maChiNhanh).
- FK (maNguoiTao) → NhanVien(maNhanVien).
- UNIQUE (maPhieu).
- UNIQUE (maYeuCau,maChiNhanh).
## Ràng buộc phần lõi dùng chung

- ChiNhanh.maChuoi → ChuoiNhaHang; NhanVien.maVaiTro → VaiTro; NhanVien.maChiNhanh → ChiNhanh; maCa → CaLamViec.
- Chủ chuỗi có maChiNhanh NULL; bốn vai trò khác bắt buộc có chi nhánh (CHECK). CCCD nếu có phải 12 chữ số và duy nhất; gioiTinh nếu có chỉ 0/1 theo báo cáo gốc.
- ChiNhanh.(maQuanLy,maChiNhanh) → NhanVien.(maNhanVien,maChiNhanh); tọa độ giới hạn miền hợp lệ. Việc quản lý phải có vai trò QUAN_LY/đang làm được service kiểm tra.
- TaiKhoan.maNhanVien → NhanVien và UNIQUE; một nhân viên tối đa một tài khoản. tenDangNhap ASCII không phân biệt hoa thường, matKhau chỉ chứa hash. Trạng thái HOAT_DONG/KHOA; phienBan thu hồi phiên.
- GioiHanDangNhap lưu khóa băm của đầu vào giới hạn đăng nhập; SchemaMigration lưu tên và SHA-256 file migration. Hai bảng kỹ thuật không là chức năng nghiệp vụ mới.
- MySQL Windows có thể hiển thị tên bảng chữ thường trong information_schema; source/SQL vẫn dùng đúng tên PascalCase nhất quán để chạy trên hệ thống phân biệt hoa thường.
