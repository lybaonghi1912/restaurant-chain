# Nội dung cập nhật báo cáo giữa kỳ theo phạm vi đã giao

## Phạm vi thực hiện

Hệ thống thực hiện 32 use case trong bảng phân công: Đặt bàn (6), nhân sự/bếp/định mức của Lộc (6), chi nhánh/xử lý yêu cầu NVL của Hòa (6), món ăn (6), gọi món và tạo yêu cầu của Trần Thị Như Ý (5), kho/duyệt yêu cầu NVL/báo cáo kho của Vinh (3). Người được phân công lập trình không đồng nhất với actor được cấp quyền trong phần mềm.

Đăng nhập/phân quyền 5 vai trò là hạ tầng dùng chung đã thống nhất. Không mở rộng thanh toán/hóa đơn, doanh thu/lợi nhuận, chấm công/tính lương, duyệt và điều động nhân viên thực tế, nhận hàng NCC, vận chuyển/nhận điều chuyển, kiểm kê riêng hoặc báo thiếu từ bếp thành chức năng mới. Những phần giao diện mẫu có các nhãn này chưa là cam kết chức năng.

## CSDL

Bản V2 gồm 30 bảng nghiệp vụ và 2 bảng kỹ thuật. Bản 51 bảng V1 đã mở rộng quá phạm vi nên được thay thế trong phần thiết kế chính, giữ bản lưu để đối chiếu. Xem danh sách thuộc tính/PK/FK/CHECK và bảng→SD trong TU_DIEN_DATABASE.md, schema.sql là nguồn định nghĩa vật lý.

Giữ các bảng báo cáo gốc có liên quan: ChuoiNhaHang, ChiNhanh, NhanVien, TaiKhoan, VaiTro, CaLamViec, NhatKyNhanSu, Ban, DonGoiMon, ChiTietDonMon, NhomMon, MonAn, NguyenVatLieu, DinhMucNVL, LichSuDinhMuc, PhieuDeXuat, ChiTietDeXuat, YeuCauNhapKho, YeuCauDieuChuyen. VaiTro là tên vật lý chuẩn cho khái niệm vai trò/chức vụ; giữ bộ mã phân quyền hiện có.

Bổ sung 11 bảng nghiệp vụ để khớp dữ liệu thực sự được sequence dùng:

| Bảng bổ sung | Căn cứ |
|---|---|
| DatBan | SD-1–6 cần lưu yêu cầu trước khi được gán bàn |
| ThucDonChiNhanh | SD-13–15,25–26 cần biết món nào bán tại chi nhánh |
| TonKhoTheoLo | SD-17/30/31 cần lượng tồn theo chi nhánh và hạn sử dụng |
| GiaoDichKho, ChiTietGiaoDichKho | SD-30/32 cần lịch sử để tính nhập/xuất/tồn từng kỳ |
| NhaCungCap, NhaCungCapNguyenLieu | SD-18 tra cứu nhà cung cấp phù hợp theo nguyên liệu |
| DonNhapNCC, ChiTietDonNhapNCC | SD-18 tạo yêu cầu nhập từ NCC, chưa nhận hàng |
| DieuChuyenKho, ChiTietDieuChuyenKho | SD-17/31 tạo và xác nhận yêu cầu NVL, chưa xuất/nhận |

GioiHanDangNhap và SchemaMigration phục vụ kỹ thuật, không tính thêm use case/domain nghiệp vụ. Không cần thực thể BaoCao: báo cáo là truy vấn từ tồn và sổ biến động. Ba view V_TonKho, V_BienDongKho, V_DoiSoatKho là mô hình đọc, không phải bảng nghiệp vụ mới.

## Domain

Tách Đặt bàn (yêu cầu/lịch) khỏi Bàn (tài nguyên vật lý); 1 yêu cầu thuộc 1 chi nhánh, được gán tối đa 1 bàn. Bàn có nhiều lịch theo thời gian nhưng không chồng các lịch hiệu lực. Tách yêu cầu điều chuyển nhân viên YeuCauDieuChuyen khỏi điều chuyển nguyên liệu DieuChuyenKho. Tách danh mục nguyên liệu, công thức món, lô tồn và biến động kho. Chi tiết yêu cầu NCC/điều chuyển biểu diễn loại NVL và lượng yêu cầu, không có lượng đã xuất/đã nhận.

Phiếu đề xuất, danh mục ca, nhà cung cấp và ánh xạ khả năng cung cấp là dữ liệu đầu vào/hỗ trợ các use case đã giao; việc có bảng không kéo theo thêm màn hình CRUD chưa phân công. CaLamViec là danh mục hồ sơ, không suy ra thực hiện chấm công.

## Class và BCE

Boundary giữ nhập liệu/hiển thị; Control xử lý quyền, transaction, kiểm tra nghiệp vụ; Entity/repository đọc ghi PDO. Thống nhất E_don_dat_ban → DatBan; LoaiMonAn → NhomMon; E_YeuCauBoSung → YeuCauNhapKho; E_YeuCauNhapNCC → DonNhapNCC; E_YeuCauDieuChuyen trong SD-17 → DieuChuyenKho. Không đổi tên entity thành từng hành động như E_ThemChiNhanh; dùng ChiNhanh và phương thức thêm.

Điều chỉnh các đặc tả lớp đang vượt phạm vi: YeuCauDieuChuyen nhân viên chỉ tạo CHO_DUYET, bỏ mô tả tự chuyển chi nhánh nhân viên khi Hoàn tất; không thêm phương thức duyệt/điều động nhân viên. NhanVien.xoa chuyển nghỉ việc và khóa tài khoản; DinhMucNVL lưu bảng nối công thức, không UPDATE danh mục nguyên liệu. DonGoiMon chỉ đơn tại chỗ, giữ tổng tiền món, không suy ra đã có thanh toán.

Các phương thức control là hợp đồng đề xuất để thành viên hiện thực; sơ đồ class trong ERD_DOMAIN_CLASS.md chỉ phản ánh những thao tác đã giao, không phải code đã triển khai.

## Các sequence phải sửa trước khi chốt báo cáo

SD-3/4/6: thêm kiểm tra lịch theo khoảng, khóa giao dịch; hủy không làm trống nhầm bàn. SD-9: sửa actor Bếp và dòng món tiếp nhận. SD-11: bỏ DELETE nhân viên. SD-12: bổ sung MonAn/DinhMucNVL/LichSuDinhMuc. SD-17/18/28/31: thống nhất tên yêu cầu, đầu xử lý Chủ chuỗi, nguồn xác nhận là Quản lý chi nhánh cấp NVL; không thêm vận chuyển. SD-25/26: bổ sung chi tiết đơn và nguồn thực đơn. SD-27: dùng maCTDon và sửa nhánh alt. SD-29: dừng ở tạo yêu cầu nhân viên. SD-32: ghi rõ báo cáo kho.

Chi tiết rà soát từng hình, bao gồm các sơ đồ chỉ cần chuẩn hóa nhẹ: RA_SOAT_32_SEQUENCE.md. Quy tắc transaction và những điều phải kiểm tra tại service: QUY_TAC_GIAO_DICH.md. Bản này là nội dung bổ sung sẵn để cập nhật báo cáo; file Word gốc chưa bị sửa.

## Nội dung sửa theo code đặt bàn ngày 30/10/2026

Trong CSDL/ERD bổ sung DatBan.thoiGianKhachDen và hanGiuCho (cột sinh giờ đặt +15 phút), bỏ ràng buộc bắt buộc giờ kết thúc khi xác nhận. Domain phân biệt khách có mặt và khách được nhận bàn. Class DatBanControl đổi tên/signature theo code ở ERD_DOMAIN_CLASS.md, bỏ denGio, thêm ghiNhanKhachDen và tuHuyQuaHan.

SD-03 thêm nhánh khách đến đúng hạn, chờ bàn và nhận bàn; SD-02 thêm đồng bộ hết hạn và MySQL Event; SD-04 bỏ nhập giờ khách về. Sau 18:15:00 với lịch 18:00, chỉ đơn đã xác nhận mà khách chưa đến mới tự hủy. Sửa đặc tả, hình sequence và test cùng quy tắc; không mặc định thời lượng ăn. Bảng test có dữ liệu cụ thể theo mẫu giảng viên trong ../dat-ban-ca-nhan/. Bản mới đạt 73 ca, không kế thừa đánh dấu PASS 63 ca của bản cũ. Các file .puml là nguồn để vẽ lại, không tự sửa hình trong file Word giữa kỳ gốc.
