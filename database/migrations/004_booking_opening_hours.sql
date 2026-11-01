-- Gio nhan khach 08:00 <= gio den < 22:00, han giu cho khong vuot 22:00.
-- Khong xoa/doi gio den cua don cu. Control chan xac nhan don ngoai gio.
ALTER TABLE DatBan MODIFY COLUMN hanGiuCho DATETIME GENERATED ALWAYS AS (LEAST(DATE_ADD(thoiGianDen, INTERVAL 15 MINUTE), TIMESTAMP(DATE(thoiGianDen), '22:00:00'))) STORED;
