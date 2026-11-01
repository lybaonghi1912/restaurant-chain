# Sequence đặt bàn

Bản dùng cập nhật báo cáo là sáu ảnh JPG và file Visual Paradigm trong `sequence/`, sao chép từ bộ sơ đồ người dùng đã chỉnh. Không dùng các bản PlantUML cũ.

| Chức năng | Phương thức controller | Model chính |
|---|---|---|
| Đặt bàn | taoDonDatBan(data) | ChiNhanh, DatBan |
| Tạo tại quầy | taoDonTaiQuay(actor, data) | Ban, DatBan |
| Hủy | huyDatBan(actor, maDatBan, lyDoHuy) | DatBan |
| Tra cứu | traCuuDatBan(actor, tuKhoa, ngay, trangThai, maChiNhanh) | DatBan |
| Xác nhận | kiemTraBanPhuHop(actor, maDatBan), xacNhanDatBan(actor, maDatBan, maBan) | DatBan, Ban |
| Theo dõi | theoDoiTrangThai(soDienThoai) | DatBan |

Controller nằm tại `modules/dat-ban/controllers/DatBanController.php`; các model nằm trong `modules/dat-ban/models/`. Tên tham số trên bảng giải thích dữ liệu truyền, không phải thay đổi chữ ký PHP.

Sơ đồ trình bày luồng theo đặc tả. Kiểm tra CSRF, chống gửi lặp, quyền truy cập, khóa giao dịch và đồng bộ quá hạn vẫn được thực hiện trong code. Không bổ sung chúng thành use case mới.
