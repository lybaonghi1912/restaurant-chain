ALTER TABLE YeuCauDieuChuyen
    MODIFY COLUMN trangThai ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    ADD COLUMN maNguoiDuyet VARCHAR(10) NULL,
    ADD COLUMN ngayDuyet DATETIME NULL,
    ADD COLUMN lyDoTuChoi VARCHAR(500) NULL,
    ADD CONSTRAINT fk_yeucaudieuchuyen_3 FOREIGN KEY (maNguoiDuyet) REFERENCES NhanVien(maNhanVien),
    ADD CONSTRAINT ck_yeucaudieuchuyen_3 CHECK (
        (trangThai = 'CHO_DUYET' AND maNguoiDuyet IS NULL AND ngayDuyet IS NULL AND lyDoTuChoi IS NULL)
        OR (trangThai = 'DA_DUYET' AND maNguoiDuyet IS NOT NULL AND ngayDuyet IS NOT NULL AND lyDoTuChoi IS NULL)
        OR (trangThai = 'TU_CHOI' AND maNguoiDuyet IS NOT NULL AND ngayDuyet IS NOT NULL
            AND lyDoTuChoi IS NOT NULL AND CHAR_LENGTH(TRIM(lyDoTuChoi)) > 0)
    );
