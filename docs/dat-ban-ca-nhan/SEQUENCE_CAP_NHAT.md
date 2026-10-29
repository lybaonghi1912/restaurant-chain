# Sequence và quy tắc đặt bàn thống nhất

Bản ngày 30/10/2026 thay thế mô tả nhập giờ kết thúc dự kiến. Sáu use case giữ nguyên phân công của Lý Bảo Nghi. Ghi nhận khách đến là nhánh bổ sung trong Xác nhận đặt bàn; tác vụ hết hạn là xử lý nội bộ hỗ trợ Theo dõi trạng thái. Không thêm module thanh toán hoặc hoàn tất phục vụ vào bài cá nhân.

## Quy tắc dùng chung
- BR-01: Web bắt buộc chi nhánh hoạt động, tên 1–100 ký tự, SĐT 10 số bắt đầu 0, số khách nguyên 1–32767, giờ đến tương lai; ghi chú tối đa 500 ký tự. Tại quầy tên/SĐT được bỏ trống.
- BR-02: Gửi web chỉ tạo CHO_XAC_NHAN, chưa gán và chưa giữ bàn.
- BR-03: PHUC_VU ghi tại chi nhánh của mình. QUAN_LY chỉ xem chi nhánh mình; CHU_CHUOI xem toàn chuỗi; BEP/KHO không truy cập đặt bàn.
- BR-04: Bàn phải cùng chi nhánh, đủ sức chứa và không NGUNG. Không yêu cầu nhập giờ khách về.
- BR-05: hanGiuCho = thoiGianDen + 15 phút, do MySQL tính. Hai khoảng giữ chỗ [giờ đặt, hạn giữ] tính cả hai đầu không được giao nhau. Ví dụ 18:00–18:15 xung đột với 18:15, không giao với 18:16. Đây là khoảng giữ chỗ, không phải thời lượng dùng bữa.
- BR-06: Tại quầy nhận ngay khi bàn TRONG, không có lượt đang phục vụ/đã đến chờ bàn và không có khoảng giữ chỗ giao với [hiện tại, hiện tại +15 phút]. Tạo DatBan và cập nhật Ban=DANG_PHUC_VU trong cùng giao dịch.
- BR-07: Hủy thủ công chỉ CHO_XAC_NHAN hoặc DA_XAC_NHAN; lý do 1–255 ký tự; giữ maBan và lịch sử. Hủy lặp giữ lý do đầu. Không ép Ban=TRONG vì có thể đang phục vụ người khác.
- BR-08: Tra cứu công khai theo SĐT, tối đa 20 lần/phút/phiên, trả tối đa 100 yêu cầu gần nhất; không lộ tên, SĐT, ghi chú, người xử lý. Nội bộ tìm tên/SĐT/mã, lọc ngày/trạng thái; tối đa 200 kết quả.
- BR-09: HTTP kiểm tra CSRF; request_key chống tạo lặp; ghi thành công chuyển hướng 303; escape dữ liệu hiển thị.
- BR-10: Khóa ChiNhanh FOR SHARE → Ban FOR UPDATE → DatBan FOR UPDATE; đọc lại trước ghi; lỗi rollback. Hai request tranh bàn chỉ một request thắng. Kiểm tra danh sách bàn trước đó chưa giữ bàn.
- BR-11: Chỉ tự hủy DA_XAC_NHAN có thoiGianKhachDen=NULL và hiện tại > hanGiuCho. Đặt 18:00 còn được nhận lúc 18:15:00, quá hạn lúc 18:15:01. Lưu DA_HUY, lý do tự động, maNguoiHuy=NULL; không đổi trạng thái vật lý bàn. CHO_XAC_NHAN không giữ bàn nên không tự hủy theo tác vụ này; quá hạn thì không thể xác nhận.
- BR-12: PHUC_VU bấm Khách đã đến: nếu đúng hạn và bàn TRONG, ghi giờ khách đến và giờ nhận bàn, chuyển đơn/bàn DANG_PHUC_VU. Nếu bàn bận, ghi giờ đến, giữ DA_XAC_NHAN và chờ bàn; không tự hủy nữa. Khi bàn trống, bấm Tiếp nhận vào bàn; giữ giờ khách đến đầu tiên và lưu giờ nhận bàn thực tế mới. Bấm nhận lại lượt đã phục vụ giữ nguyên giờ.
- BR-13: DANG_PHUC_VU hoặc DA_XAC_NHAN đã có giờ đến đều chặn việc gán lượt khác cùng bàn. Không tự làm trống bàn khi đang ăn. Việc kết thúc phục vụ thuộc luồng khác; chưa triển khai trong sáu chức năng cá nhân. Bàn tương lai không phải cam kết rằng khách trước chắc chắn đã về; khi đến có thể phải chờ bố trí.

## Thông báo và lỗi
MSG là mã tài liệu để đối chiếu, không giả định PHP gửi chuỗi mã MSG lên giao diện.
| Mã | Ánh xạ thực tế |
|---|---|
| MSG-01 | Yêu cầu đã được gửi, vui lòng chờ xác nhận; kèm mã và thông tin chưa giữ bàn. |
| MSG-02 | BookingError VALIDATION_ERROR, HTTP 422; câu lỗi cụ thể theo trường. |
| MSG-03 | Không tìm thấy đơn đặt bàn theo số điện thoại đã nhập. |
| MSG-04 | FORBIDDEN HTTP 403; chưa đăng nhập trang nội bộ chuyển về login. |
| MSG-05 | NOT_FOUND HTTP 404. |
| MSG-06 | CONFLICT HTTP 409; trạng thái/bàn/hạn giữ chỗ không hợp lệ. |
| MSG-07 | Bootstrap trả HTTP 503 chung khi PDO lỗi; không lộ kết nối. |
| MSG-08 | Xác nhận đặt bàn thành công. |
| MSG-09 | Tạo đặt bàn tại quầy thành công; kèm mã. |
| MSG-10 | Đã hủy yêu cầu đặt bàn; lịch được giải phóng. |
| MSG-11 | Danh sách hoặc chi tiết nội bộ; kết quả tìm kiếm có thể rỗng. |
| MSG-12 | Danh sách trạng thái công khai mới nhất. |
| MSG-13 | Hiện không có bàn phù hợp. Vui lòng liên hệ khách hàng để thông báo. |
| MSG-14 | Đã ghi nhận khách đến và tiếp nhận vào bàn. |
| MSG-15 | Đã ghi nhận khách đến đúng hạn. Bàn đang bận; chờ bố trí bàn. |
| MSG-16 | RATE_LIMITED HTTP 429: Bạn tra cứu quá nhanh. Vui lòng thử lại sau một phút. |

## Sơ đồ đối chiếu với code
| Sequence | Boundary và Control | Bộ test nghiệp vụ |
|---|---|---|
| SD-01 Đặt bàn | dat-ban.php + web.php → taoDonDatBan(data) | DB01–DB08 |
| SD-02 Theo dõi trạng thái | theo-doi.php → theoDoiTrangThai(sdt), tuHuyQuaHan() | TD01–TD07, SYS02 |
| SD-03 Xác nhận đặt bàn | index.php + web.php → kiemTraBanPhuHop(actor,id), xacNhanDatBan(actor,id,ban), ghiNhanKhachDen(actor,id) | XN01–XN15 |
| SD-04 Tạo tại quầy | index.php + web.php → taoDonTaiQuay(actor,data) | TQ01–TQ08 |
| SD-05 Tra cứu nội bộ | index.php → traCuuDatBan(actor,...), chiTiet(actor,id) | TC01–TC06 |
| SD-06 Hủy đặt bàn | index.php + web.php → huyDatBan(actor,id,lyDo) | HY01–HY07 |

Tên bảng ChiNhanh/Ban/DatBan giữ đúng schema chung. DatBanControl điều phối các Entity dùng cùng PDO để giao dịch nguyên tử; không tách thành các giao dịch riêng chỉ để đổi hình vẽ. Boundary gồm view và adapter web.php; kiểm tra CSRF/quyền và đồng bộ hết hạn diễn ra tại adapter trước thao tác. Sáu file .puml mô tả hàm thực tế; SD-03 bổ sung khối ghi nhận khách đến, SD-02 bổ sung bộ hẹn giờ nội bộ. Hình trong báo cáo giữa kỳ gốc chưa tự thay đổi; dùng các .puml này để cập nhật lại hình trong Visual Paradigm.

## Database và vận hành
Migration 003 bổ sung thoiGianKhachDen và cột tính hanGiuCho, thay ràng buộc yêu cầu giờ kết thúc bằng ràng buộc gán bàn và giờ nhận khách. Cột thoiGianKetThucDuKien cũ được giữ để tương thích nhưng chức năng mới không nhập và không dùng làm điều kiện khả dụng. Dữ liệu cũ đang phục vụ được bổ sung giờ đến từ giờ nhận bàn.

Chạy `php database/migrate.php` bằng tài khoản quản trị MySQL. Script cài ev_reservation_no_show, chạy mỗi phút; đọc trang đặt bàn cũng đồng bộ ngay đơn quá hạn. Tính quá hạn bắt đầu sau đúng 15 phút, tác vụ nền cập nhật trong chu kỳ một phút tiếp theo khi MySQL đang chạy; giao diện đang mở cần tải lại/tra cứu lại để thấy trạng thái mới, chưa có push realtime. event_scheduler phải ON; sau khi khởi động lại MySQL, chạy lại `php database/install_reservation_timer.php` nếu chưa cấu hình event_scheduler=ON trong my.ini. Các lệnh cài đặt cần quyền quản trị tạo Event/bật scheduler; ứng dụng web vẫn dùng tài khoản PDO cấu hình riêng.
