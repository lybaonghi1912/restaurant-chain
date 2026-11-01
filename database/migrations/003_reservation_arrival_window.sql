-- Han giu cho 15 phut sau gio dat, khong phai gio ket thuc bua an.
ALTER TABLE DatBan
    ADD COLUMN thoiGianKhachDen DATETIME NULL,
    ADD COLUMN hanGiuCho DATETIME GENERATED ALWAYS AS (DATE_ADD(thoiGianDen, INTERVAL 15 MINUTE)) STORED;
-- Bao toan luot dang phuc vu tu ban cu truoc khi them rang buoc.
UPDATE DatBan SET thoiGianKhachDen=COALESCE(thoiGianNhanBan,thoiGianDen), thoiGianNhanBan=COALESCE(thoiGianNhanBan,thoiGianDen) WHERE trangThai='DANG_PHUC_VU' AND thoiGianKhachDen IS NULL;
ALTER TABLE DatBan DROP CHECK ck_datban_6;
ALTER TABLE DatBan
    ADD CONSTRAINT ck_datban_assigned CHECK (trangThai NOT IN ('DA_XAC_NHAN','DANG_PHUC_VU') OR maBan IS NOT NULL),
    ADD CONSTRAINT ck_datban_arrived CHECK (trangThai<>'DANG_PHUC_VU' OR (thoiGianKhachDen IS NOT NULL AND thoiGianNhanBan IS NOT NULL));
