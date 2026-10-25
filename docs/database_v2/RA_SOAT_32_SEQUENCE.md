# Rà soát 32 sequence đã phân công — database V2

Nguồn: báo cáo `Bao_cao_giua_ky_nhom2_F_B_Nodes_.docx`, các hình SD-1 đến SD-32. Đã đọc hình sequence gốc, đối chiếu đặc tả và lớp. Các ảnh trích nguyên bản trong `evidence/`; không chỉnh sửa tài liệu Word gốc. Ngày rà soát: 25/10/2026.

Phạm vi: 32 use case theo bảng phân công. Những điều dưới đây ghi “sửa/bổ sung” là đề xuất chuẩn hóa để cùng use case chạy đúng, không phải nội dung vốn đã có trong hình. Database đã thể hiện quyết định thiết kế này; nhóm cần cập nhật lại sơ đồ/báo cáo tương ứng.

| Thành viên | SD | Số use case |
|---|---|---:|
| Lý Bảo Nghi | 1–6 | 6 |
| Lê Xuân Lộc | 7–12 | 6 |
| Phạm Hiếu Hòa | 13–18 | 6 |
| Trần Như Ý | 19–24 | 6 |
| Trần Thị Như Ý | 25–29 | 5 |
| Lương Nguyễn Thành Vinh | 30–32 | 3 |

## SD-01 — Đặt bàn

- Thành viên: Lý Bảo Nghi. Actor: Khách.
- [Hình gốc](evidence/SD-01.png); DOCX `media/image37.png`.
- Luồng/message quan sát: Lấy danh sách chi nhánh; taoDonDatBan(..., "Chờ xác nhận").
- Bảng vật lý liên quan: `ChiNhanh`, `DatBan`.
- Kết luận và chỉnh sửa cần làm: Giữ đúng luồng tạo yêu cầu: chưa gán bàn, chưa chiếm bàn. Bổ sung giờ kết thúc dự kiến khi xác nhận để kiểm tra lịch.
- Quy tắc áp dụng: R01.

## SD-02 — Theo dõi trạng thái đặt bàn

- Thành viên: Lý Bảo Nghi. Actor: Khách.
- [Hình gốc](evidence/SD-02.png); DOCX `media/image38.png`.
- Luồng/message quan sát: timDonTheoSoDienThoai; layTenChiNhanh; nhánh không có kết quả.
- Bảng vật lý liên quan: `DatBan`, `ChiNhanh`.
- Kết luận và chỉnh sửa cần làm: Chỉ đọc. Chỉ trả thông tin đặt bàn cần thiết, không trả hồ sơ nhân viên/tài khoản. Tra cứu bằng số điện thoại hiện bám sequence; tăng bảo vệ tra cứu là việc cần thống nhất nếu triển khai công khai.
- Quy tắc áp dụng: R01.

## SD-03 — Xác nhận đặt bàn

- Thành viên: Lý Bảo Nghi. Actor: Phục vụ.
- [Hình gốc](evidence/SD-03.png); DOCX `media/image39.png`.
- Luồng/message quan sát: timDonTheoChiNhanh; timDonTheoMa; timBanTrongTheoChiNhanhVaSucChua; layTrangThaiBan; ganBanVaCapNhatTrangThaiDon; capNhatTrangThaiBan.
- Bảng vật lý liên quan: `DatBan`, `Ban`.
- Kết luận và chỉnh sửa cần làm: Bổ sung tham số khoảng thời gian và khóa bàn. Trạng thái Đã đặt theo lịch được suy ra từ DatBan, không khóa cứng bàn cho mọi ngày. Gán bàn và xác nhận trong một transaction.
- Quy tắc áp dụng: R01 R02.

## SD-04 — Tạo đặt bàn tại quầy

- Thành viên: Lý Bảo Nghi. Actor: Phục vụ.
- [Hình gốc](evidence/SD-04.png); DOCX `media/image40.png`.
- Luồng/message quan sát: Tìm bàn trống; taoDonTaiQuay(..., "Đang phục vụ"); cập nhật trạng thái bàn.
- Bảng vật lý liên quan: `DatBan`, `Ban`.
- Kết luận và chỉnh sửa cần làm: Kiểm tra lại sức chứa, chi nhánh, bàn đang phục vụ và lịch trùng trong transaction; không dùng kết quả danh sách cũ làm căn cứ cuối cùng.
- Quy tắc áp dụng: R01 R02.

## SD-05 — Tra cứu đặt bàn

- Thành viên: Lý Bảo Nghi. Actor: Phục vụ.
- [Hình gốc](evidence/SD-05.png); DOCX `media/image41.png`.
- Luồng/message quan sát: Tra cứu theo chi nhánh/tên/số điện thoại; lấy thông tin bàn khi đã gán.
- Bảng vật lý liên quan: `DatBan`, `Ban`.
- Kết luận và chỉnh sửa cần làm: LEFT JOIN bàn vì yêu cầu chờ xác nhận chưa có maBan. Bắt buộc lọc chi nhánh từ phiên đăng nhập.
- Quy tắc áp dụng: R01.

## SD-06 — Hủy đặt bàn

- Thành viên: Lý Bảo Nghi. Actor: Phục vụ.
- [Hình gốc](evidence/SD-06.png); DOCX `media/image42.png`.
- Luồng/message quan sát: Cập nhật hủy; lấy maBan; nếu có bàn thì cập nhật Trống.
- Bảng vật lý liên quan: `DatBan`, `Ban`.
- Kết luận và chỉnh sửa cần làm: Sửa cập nhật Trống vô điều kiện: hủy lịch chỉ giải phóng lịch đó; không làm trống bàn đang phục vụ đơn/khách khác. Khóa và kiểm tra trạng thái trước khi hủy; lưu lý do.
- Quy tắc áp dụng: R02.

## SD-07 — Chỉnh sửa thông tin nhân viên

- Thành viên: Lê Xuân Lộc. Actor: Quản lý chi nhánh.
- [Hình gốc](evidence/SD-07.png); DOCX `media/image43.png`.
- Luồng/message quan sát: DB_NHANVIEN cập nhật thông tin nhân viên.
- Bảng vật lý liên quan: `NhanVien`, `TaiKhoan`, `NhatKyNhanSu`.
- Kết luận và chỉnh sửa cần làm: Đồng bộ đặc tả lớp: lưu nhật ký. Nếu thay đổi vai trò/trạng thái có ảnh hưởng đăng nhập thì tăng phienBan; không cho quản lý thao tác nhân viên chi nhánh khác.
- Quy tắc áp dụng: R03.

## SD-08 — Thêm thông tin nhân viên

- Thành viên: Lê Xuân Lộc. Actor: Quản lý chi nhánh.
- [Hình gốc](evidence/SD-08.png); DOCX `media/image44.png`.
- Luồng/message quan sát: DB_NHANVIEN/OBJ_NHANVIEN: tạo đối tượng và INSERT.
- Bảng vật lý liên quan: `NhanVien`, `TaiKhoan`, `NhatKyNhanSu`, `VaiTro`, `ChiNhanh`, `CaLamViec`.
- Kết luận và chỉnh sửa cần làm: Thêm kiểm tra CCCD duy nhất, vai trò, chi nhánh; tạo tài khoản mặc định và nhật ký theo đặc tả lớp trong cùng transaction. CaLamViec chỉ là danh mục hồ sơ, không phải chấm công.
- Quy tắc áp dụng: R03.

## SD-09 — Tiếp nhận đơn tại bếp

- Thành viên: Lê Xuân Lộc. Actor: Bếp (cần sửa actor trong hình).
- [Hình gốc](evidence/SD-09.png); DOCX `media/image45.png`.
- Luồng/message quan sát: Hình ghi actor Phục vụ, nhấn Gửi bếp; GhiNhanThoiGianGui; cập nhật trạng thái đơn.
- Bảng vật lý liên quan: `DonGoiMon`, `ChiTietDonMon`.
- Kết luận và chỉnh sửa cần làm: Tên use case là Tiếp nhận đơn tại bếp: đổi actor thành Bếp, lấy các dòng CHO_CHE_BIEN; cập nhật dòng theo maCTDon, người nhận và giờ nhận. Thao tác gửi bếp đã nằm ở SD-25/26. Không bổ sung tự động trừ kho.
- Quy tắc áp dụng: R04.

## SD-10 — Xem thông tin nhân viên

- Thành viên: Lê Xuân Lộc. Actor: Quản lý chi nhánh.
- [Hình gốc](evidence/SD-10.png); DOCX `media/image46.png`.
- Luồng/message quan sát: getDS nhân viên, lọc danh sách, xem thông tin.
- Bảng vật lý liên quan: `NhanVien`, `VaiTro`, `ChiNhanh`, `CaLamViec`.
- Kết luận và chỉnh sửa cần làm: Chỉ đọc; lọc theo chi nhánh ở server. Tách lọc Boundary khỏi điều kiện truy vấn để tránh lộ dữ liệu chi nhánh khác.
- Quy tắc áp dụng: R03.

## SD-11 — Xóa thông tin nhân viên

- Thành viên: Lê Xuân Lộc. Actor: Quản lý chi nhánh.
- [Hình gốc](evidence/SD-11.png); DOCX `media/image47.png`.
- Luồng/message quan sát: DB_NHANVIEN thể hiện DELETE.
- Bảng vật lý liên quan: `NhanVien`, `TaiKhoan`, `NhatKyNhanSu`.
- Kết luận và chỉnh sửa cần làm: Thay DELETE bằng NGHI_VIEC, khóa tài khoản, tăng phienBan, ghi nhật ký trong một transaction để giữ lịch sử đơn và yêu cầu. Đây là sửa cách xử lý cùng use case, không thêm use case.
- Quy tắc áp dụng: R03.

## SD-12 — Định mức nguyên vật liệu

- Thành viên: Lê Xuân Lộc. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-12.png); DOCX `media/image48.png`.
- Luồng/message quan sát: Hình dùng DB_NVL, chọn nguyên liệu rồi UPDATE NVL.
- Bảng vật lý liên quan: `MonAn`, `NguyenVatLieu`, `DinhMucNVL`, `LichSuDinhMuc`.
- Kết luận và chỉnh sửa cần làm: Thay bằng lưu công thức theo (maMon,maNVL), lượng dương theo đơn vị cơ sở; ghi lịch sử trước/sau; cập nhật trạng thái định mức. Không tạo lại nguyên liệu khi lưu công thức.
- Quy tắc áp dụng: R05.

## SD-13 — Thêm chi nhánh

- Thành viên: Phạm Hiếu Hòa. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-13.png); DOCX `media/image49.png`.
- Luồng/message quan sát: Kiểm tra trùng và thêm chi nhánh qua entity E_ThemChiNhanh.
- Bảng vật lý liên quan: `ChiNhanh`, `ChuoiNhaHang`, `NhanVien`, `Ban`, `ThucDonChiNhanh`, `MonAn`.
- Kết luận và chỉnh sửa cần làm: Chuẩn hóa entity thành ChiNhanh. Các cấu hình bàn, quản lý và thực đơn là dữ liệu phụ thuộc theo đặc tả; không thêm module quản lý riêng. Kiểm tra quản lý thuộc đúng chi nhánh.
- Quy tắc áp dụng: R06.

## SD-14 — Xem chi nhánh

- Thành viên: Phạm Hiếu Hòa. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-14.png); DOCX `media/image50.png`.
- Luồng/message quan sát: E_ChiNhanh: lấy danh sách/chi tiết.
- Bảng vật lý liên quan: `ChiNhanh`, `NhanVien`, `Ban`, `ThucDonChiNhanh`, `MonAn`.
- Kết luận và chỉnh sửa cần làm: Chỉ đọc; tổng số bàn tính từ Ban, thực đơn lấy từ bảng nối, không lưu thêm bản sao danh sách trong ChiNhanh.
- Quy tắc áp dụng: R06.

## SD-15 — Cập nhật chi nhánh

- Thành viên: Phạm Hiếu Hòa. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-15.png); DOCX `media/image51.png`.
- Luồng/message quan sát: Lấy dữ liệu; kiểm tra trùng; cập nhật E_ChiNhanh.
- Bảng vật lý liên quan: `ChiNhanh`, `NhanVien`, `Ban`, `ThucDonChiNhanh`, `MonAn`.
- Kết luận và chỉnh sửa cần làm: Kiểm tra trùng phải loại trừ chính chi nhánh đang sửa; khóa bản ghi, cập nhật cấu hình liên quan trong cùng transaction nếu có.
- Quy tắc áp dụng: R06.

## SD-16 — Xóa chi nhánh

- Thành viên: Phạm Hiếu Hòa. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-16.png); DOCX `media/image52.png`.
- Luồng/message quan sát: kiemTraDieuKienXoa; đổi trạng thái ngừng.
- Bảng vật lý liên quan: `ChiNhanh`, `NhanVien`, `DonGoiMon`, `DatBan`.
- Kết luận và chỉnh sửa cần làm: Giữ xóa mềm NGUNG; kiểm tra nhân viên còn làm và đơn đang mở; bổ sung lịch đặt đang hiệu lực để tránh bỏ sót dữ liệu phụ thuộc. Không xóa dây chuyền lịch sử.
- Quy tắc áp dụng: R06.

## SD-17 — Xử lý yêu cầu điều chuyển nguyên liệu giữa các chi nhánh

- Thành viên: Phạm Hiếu Hòa. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-17.jpeg); DOCX `media/image53.jpeg`.
- Luồng/message quan sát: E_YeuCauBoSung, E_TonKhoTheoLo, E_YeuCauDieuChuyen; layTonKhoChiNhanhKhac; layTonKhoConHan; tạo Chờ chi nhánh xác nhận.
- Bảng vật lý liên quan: `YeuCauNhapKho`, `PhieuDeXuat`, `ChiTietDeXuat`, `ChiNhanh`, `NguyenVatLieu`, `TonKhoTheoLo`, `DieuChuyenKho`, `ChiTietDieuChuyenKho`.
- Kết luận và chỉnh sửa cần làm: YeuCauBoSung ánh xạ YeuCauNhapKho; YeuCauDieuChuyen trong hình này là DieuChuyenKho, không phải yêu cầu nhân viên. Tính còn hạn, gợi ý khoảng cách khi có tọa độ. Tạo yêu cầu không giảm tồn.
- Quy tắc áp dụng: R07.

## SD-18 — Xử lý yêu cầu nhập nguyên liệu từ nhà cung cấp

- Thành viên: Phạm Hiếu Hòa. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-18.jpeg); DOCX `media/image54.jpeg`.
- Luồng/message quan sát: layNhaCungCapPhuHop(maNguyenLieu); taoYeuCauNhap(...,maNCC,soLuong), Chờ nhập hàng.
- Bảng vật lý liên quan: `YeuCauNhapKho`, `PhieuDeXuat`, `ChiTietDeXuat`, `NguyenVatLieu`, `NhaCungCap`, `NhaCungCapNguyenLieu`, `DonNhapNCC`, `ChiTietDonNhapNCC`.
- Kết luận và chỉnh sửa cần làm: Bổ sung bảng nối NCC–NVL cho truy vấn phù hợp. E_YeuCauNhapNCC ánh xạ DonNhapNCC. Sửa nhãn giao diện sao chép từ điều chuyển. Dừng ở yêu cầu, không làm nhận hàng/hóa đơn NCC.
- Quy tắc áp dụng: R07.

## SD-19 — Thêm món ăn

- Thành viên: Trần Như Ý. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-19.png); DOCX `media/image55.png`.
- Luồng/message quan sát: LoaiMonAn/MónĂn: lấy nhóm, kiểm tra dữ liệu/trùng, lưu món.
- Bảng vật lý liên quan: `NhomMon`, `MonAn`.
- Kết luận và chỉnh sửa cần làm: LoaiMonAn ánh xạ NhomMon; một tên vật lý dùng thống nhất. Giá không âm, nhóm hoạt động; kiểm tra tên trùng theo nhóm ở ứng dụng.
- Quy tắc áp dụng: R08.

## SD-20 — Xem danh sách món ăn

- Thành viên: Trần Như Ý. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-20.png); DOCX `media/image56.png`.
- Luồng/message quan sát: Lấy danh sách món và loại món; hình có entity gọi entity.
- Bảng vật lý liên quan: `MonAn`, `NhomMon`.
- Kết luận và chỉnh sửa cần làm: Controller/repository thực hiện JOIN; tránh để entity trực tiếp điều phối entity trong BCE. Chỉ đọc.
- Quy tắc áp dụng: R08.

## SD-21 — Cập nhật món ăn

- Thành viên: Trần Như Ý. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-21.png); DOCX `media/image57.png`.
- Luồng/message quan sát: Lấy thông tin món, kiểm tra và cập nhật.
- Bảng vật lý liên quan: `MonAn`, `NhomMon`.
- Kết luận và chỉnh sửa cần làm: Hoàn chỉnh nhánh lỗi/nhập lại và lần lưu thành công; kiểm tra tồn tại nhóm, giá, tên trùng có loại trừ mã hiện tại.
- Quy tắc áp dụng: R08.

## SD-22 — Xóa món ăn

- Thành viên: Trần Như Ý. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-22.png); DOCX `media/image58.png`.
- Luồng/message quan sát: Xóa món ăn.
- Bảng vật lý liên quan: `MonAn`, `ThucDonChiNhanh`, `DinhMucNVL`, `ChiTietDonMon`.
- Kết luận và chỉnh sửa cần làm: Sửa thành NGUNG khi món đã có tham chiếu; ngừng bán ở thực đơn chi nhánh. Không xóa chi tiết đơn, giá hay lịch sử định mức.
- Quy tắc áp dụng: R08.

## SD-23 — Tìm kiếm món ăn

- Thành viên: Trần Như Ý. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-23.png); DOCX `media/image59.png`.
- Luồng/message quan sát: Tìm theo từ khóa; nhánh có/không có kết quả.
- Bảng vật lý liên quan: `MonAn`, `NhomMon`.
- Kết luận và chỉnh sửa cần làm: Truy vấn tham số PDO, không ghép trực tiếp từ khóa vào SQL; không thêm chức năng tìm kiếm khác ngoài món.
- Quy tắc áp dụng: R08.

## SD-24 — Xem chi tiết món ăn

- Thành viên: Trần Như Ý. Actor: Chủ chuỗi.
- [Hình gốc](evidence/SD-24.png); DOCX `media/image60.png`.
- Luồng/message quan sát: Lấy thông tin món theo mã; tồn tại/không tồn tại.
- Bảng vật lý liên quan: `MonAn`, `NhomMon`, `DinhMucNVL`, `NguyenVatLieu`.
- Kết luận và chỉnh sửa cần làm: Thông tin món/nhóm là bắt buộc; công thức là dữ liệu đọc hỗ trợ nếu chi tiết hiển thị, không mở module mới.
- Quy tắc áp dụng: R08.

## SD-25 — Tạo đơn gọi món tại chỗ

- Thành viên: Trần Thị Như Ý. Actor: Phục vụ.
- [Hình gốc](evidence/SD-25.png); DOCX `media/image61.png`.
- Luồng/message quan sát: layDSBanDangPhucVu; getThongTinBan; layThongTinBanVaThucDon; taoDonGoiMon(maBan,dsMonChon); gửi bếp.
- Bảng vật lý liên quan: `Ban`, `DonGoiMon`, `ChiTietDonMon`, `MonAn`, `NhomMon`, `ThucDonChiNhanh`.
- Kết luận và chỉnh sửa cần làm: Bổ sung lifeline MonAn và ChiTietDonMon. Lấy giá ở server, lưu giá/tên tại thời điểm gọi, khóa bàn và kiểm tra đơn mở. Không thêm mang về/thanh toán/trừ kho.
- Quy tắc áp dụng: R04.

## SD-26 — Thêm món vào đơn

- Thành viên: Trần Thị Như Ý. Actor: Phục vụ.
- [Hình gốc](evidence/SD-26.png); DOCX `media/image62.png`.
- Luồng/message quan sát: taoChiTietDonMon(maDon,maMon,soLuong,ghiChu); capNhatTongTienDon; gửi bếp.
- Bảng vật lý liên quan: `DonGoiMon`, `ChiTietDonMon`, `MonAn`, `NhomMon`, `ThucDonChiNhanh`.
- Kết luận và chỉnh sửa cần làm: Khóa đơn; thêm dòng mới, không gộp vào dòng đang chế biến. Tính lại tổng các dòng chưa hủy trong cùng transaction.
- Quy tắc áp dụng: R04.

## SD-27 — Hủy món trong đơn

- Thành viên: Trần Thị Như Ý. Actor: Phục vụ.
- [Hình gốc](evidence/SD-27.png); DOCX `media/image63.png`.
- Luồng/message quan sát: kiemTraTrangThaiMon(maMon); cập nhật hủy/lý do/tổng tiền.
- Bảng vật lý liên quan: `DonGoiMon`, `ChiTietDonMon`.
- Kết luận và chỉnh sửa cần làm: Dùng maCTDon thay maMon để chọn đúng lượt gọi. Sửa nhánh alt bị đặt hành động thành công trong nhánh không hợp lệ. Khóa dòng; chỉ hủy CHO_CHE_BIEN theo quy tắc đề xuất; bếp nhận trước thì từ chối hủy.
- Quy tắc áp dụng: R04.

## SD-28 — Lập yêu cầu nhập nguyên vật liệu từ phiếu đề xuất

- Thành viên: Trần Thị Như Ý. Actor: Kho.
- [Hình gốc](evidence/SD-28.png); DOCX `media/image64.png`.
- Luồng/message quan sát: Xem phiếu chờ; tạo yêu cầu Chờ xử lý; cập nhật phiếu Đã tạo yêu cầu; gửi quản lý chi nhánh.
- Bảng vật lý liên quan: `PhieuDeXuat`, `ChiTietDeXuat`, `YeuCauNhapKho`, `NguyenVatLieu`.
- Kết luận và chỉnh sửa cần làm: Điểm đến đang lệch SD-17/18 và mô tả Chủ chuỗi: thống nhất chuyển Chủ chuỗi xử lý. UNIQUE(maPhieu) tránh tạo trùng. Phiếu là đầu vào; không tự thêm use case lập phiếu.
- Quy tắc áp dụng: R07.

## SD-29 — Tạo yêu cầu điều chuyển nhân viên

- Thành viên: Trần Thị Như Ý. Actor: Quản lý chi nhánh.
- [Hình gốc](evidence/SD-29.png); DOCX `media/image65.png`.
- Luồng/message quan sát: Chọn vị trí, số lượng, thời gian, lý do; tạo Chờ duyệt; gửi Chủ chuỗi.
- Bảng vật lý liên quan: `ChiNhanh`, `YeuCauDieuChuyen`, `NhanVien`.
- Kết luận và chỉnh sửa cần làm: Chỉ lưu yêu cầu. Không triển khai duyệt yêu cầu nhân viên, danh sách điều động, chấm công hay đổi chi nhánh nhân viên. Sửa đặc tả lớp cũ nếu đang mô tả bước Hoàn tất tự chuyển nhân viên.
- Quy tắc áp dụng: R09.

## SD-30 — Quản lý kho

- Thành viên: Lương Nguyễn Thành Vinh. Actor: Kho.
- [Hình gốc](evidence/SD-30.png); DOCX `media/image66.png`.
- Luồng/message quan sát: Danh sách, thêm, lọc, cập nhật và xóa có kiểm tra sử dụng.
- Bảng vật lý liên quan: `NguyenVatLieu`, `TonKhoTheoLo`, `GiaoDichKho`, `ChiTietGiaoDichKho`, `ChiNhanh`.
- Kết luận và chỉnh sửa cần làm: Phân biệt danh mục nguyên liệu với lô/số dư chi nhánh. Sửa lượng tồn phải ghi biến động cùng transaction; xóa mềm khi có tham chiếu, không xóa lịch sử. Nhãn entity bị watermark che một phần, không tự đoán tên gốc.
- Quy tắc áp dụng: R10.

## SD-31 — Duyệt yêu cầu điều chuyển nguyên vật liệu

- Thành viên: Lương Nguyễn Thành Vinh. Actor: Quản lý chi nhánh nguồn (người lập trình: Vinh).
- [Hình gốc](evidence/SD-31.png); DOCX `media/image67.png`.
- Luồng/message quan sát: Hình actor Quản lý chi nhánh; xem yêu cầu/chi tiết/tồn; duyệt hoặc từ chối, lưu lý do.
- Bảng vật lý liên quan: `DieuChuyenKho`, `ChiTietDieuChuyenKho`, `TonKhoTheoLo`, `NguyenVatLieu`.
- Kết luận và chỉnh sửa cần làm: Thống nhất đây là xác nhận nguồn cho SD-17, không phải duyệt điều chuyển nhân viên SD-29. Cần xác nhận sửa nhãn với nhóm. Kiểm tra nguồn trong phiên và trạng thái chờ; duyệt không giảm tồn/không tạo lệnh vận chuyển.
- Quy tắc áp dụng: R07.

## SD-32 — Xem thống kê báo cáo

- Thành viên: Lương Nguyễn Thành Vinh. Actor: Kho.
- [Hình gốc](evidence/SD-32.png); DOCX `media/image68.png`.
- Luồng/message quan sát: Chọn khoảng thời gian; báo cáo nhập/xuất/tồn; không có dữ liệu; xuất Excel/PDF.
- Bảng vật lý liên quan: `TonKhoTheoLo`, `GiaoDichKho`, `ChiTietGiaoDichKho`, `NguyenVatLieu`, `ChiNhanh`.
- Kết luận và chỉnh sửa cần làm: Chỉ báo cáo kho, không doanh thu/lợi nhuận/lương. Không cần bảng BaoCao lưu số liệu tính lại. Nhãn entity bị watermark che một phần. Xuất file thuộc cùng use case, chưa được lập trình bởi việc chốt DB.
- Quy tắc áp dụng: R10 R11.
