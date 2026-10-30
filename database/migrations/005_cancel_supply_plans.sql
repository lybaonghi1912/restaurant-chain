-- Nhóm đã đồng ý bổ sung hủy cả hai loại phiếu. Không xóa/chuyển trạng thái phiếu cũ.
-- Tên bảng có backtick để migration runner hiện tại thực thi nguyên khối ALTER.
ALTER TABLE `DieuChuyenKho`
 MODIFY COLUMN trangThai ENUM('CHO_XAC_NHAN_NGUON','DA_DUYET','TU_CHOI','DA_HUY') NOT NULL DEFAULT 'CHO_XAC_NHAN_NGUON',
 ADD COLUMN maNguoiHuy VARCHAR(10) NULL,
 ADD COLUMN thoiGianHuy DATETIME NULL,
 ADD COLUMN lyDoHuy VARCHAR(500) NULL,
 ADD CONSTRAINT fk_dieuchuyenkho_huy FOREIGN KEY (maNguoiHuy) REFERENCES NhanVien(maNhanVien),
 ADD CONSTRAINT ck_dieuchuyenkho_huy CHECK (
 (trangThai='DA_HUY' AND maNguoiHuy IS NOT NULL AND thoiGianHuy IS NOT NULL AND lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)
 OR (trangThai<>'DA_HUY' AND maNguoiHuy IS NULL AND thoiGianHuy IS NULL AND lyDoHuy IS NULL));

ALTER TABLE `DonNhapNCC`
 MODIFY COLUMN trangThai ENUM('CHO_NHAP_HANG','DA_HUY') NOT NULL DEFAULT 'CHO_NHAP_HANG',
 ADD COLUMN maNguoiHuy VARCHAR(10) NULL,
 ADD COLUMN thoiGianHuy DATETIME NULL,
 ADD COLUMN lyDoHuy VARCHAR(500) NULL,
 ADD CONSTRAINT fk_donnhapncc_huy FOREIGN KEY (maNguoiHuy) REFERENCES NhanVien(maNhanVien),
 ADD CONSTRAINT ck_donnhapncc_huy CHECK (
 (trangThai='DA_HUY' AND maNguoiHuy IS NOT NULL AND thoiGianHuy IS NOT NULL AND lyDoHuy IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoHuy))>0)
 OR (trangThai<>'DA_HUY' AND maNguoiHuy IS NULL AND thoiGianHuy IS NULL AND lyDoHuy IS NULL));
