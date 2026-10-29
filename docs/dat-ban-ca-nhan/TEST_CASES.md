# Test case sáu chức năng đặt bàn
Ngày chạy: 29/10/2026. PHP 8.3.30, MySQL 8.4.3, PDO. 63/63 PASS: 41 service, 18 HTTP và 4 thao tác trình duyệt trên dữ liệu tạm.
## Tiền điều kiện chung
Schema/seed V2, ba chi nhánh và 36 bàn; nhân viên kiểm thử PHUC_VU ở CN01/CN02. Trước mỗi test service, xóa riêng DatBan trong database tạm, đặt Ban=TRONG và ChiNhanh=HOAT_DONG. Không chạy reset trên database thật. Các tình huống liên tiếp trong một test cùng dùng fixture của test đó.
## Ánh xạ sequence và code
| SD | Chức năng | Boundary | Control | Test |
|---|---|---|---|---|
| SD-1 | Đặt bàn | modules/dat-ban/dat-ban.php | taoDonDatBan | DB01–DB08, WEB01–04/17, GUI01 |
| SD-4 | Tạo đặt bàn tại quầy | modules/dat-ban/index.php | taoDonTaiQuay | TQ01–TQ08, WEB15–16 |
| SD-6 | Hủy đặt bàn | modules/dat-ban/index.php | huyDatBan | HY01–HY06, WEB13–14, GUI03–04 |
| SD-5 | Tra cứu đặt bàn | modules/dat-ban/index.php | traCuuDatBan/chiTiet | TC01–TC06, WEB07–09 |
| SD-3 | Xác nhận đặt bàn | modules/dat-ban/index.php | kiemTraBanPhuHop/xacNhanDatBan | XN01–XN08, WEB10–11, GUI02 |
| SD-2 | Theo dõi trạng thái | modules/dat-ban/theo-doi.php | theoDoiTrangThai | TD01–TD05, WEB05–06/12/18 |

## Đặt bàn — 14 test case

### DB01 — Gửi hợp lệ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: CN01; 4 khách; thời gian tương lai.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Lưu CHO_XAC_NHAN, maBan NULL; không đổi trạng thái bàn.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### DB02 — Thiếu họ tên
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: tenKhach rỗng.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR; không lưu.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### DB03 — Sai số điện thoại
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: SĐT abc.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### DB04 — Số khách không nguyên dương
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: 0; -1; 1.5; mảng.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Tất cả bị từ chối.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### DB05 — Ngày giờ quá khứ hoặc không tồn tại
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Ngày hôm qua; 2026-02-30.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### DB06 — Chi nhánh ngừng hoạt động
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: CN01 NGUNG.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR; không lưu.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### DB07 — Giới hạn độ dài và kiểu dữ liệu
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Tên 101 ký tự; chi nhánh dạng mảng.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### DB08 — Chuỗi SQL được lưu như dữ liệu
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Tên O'Neil; DROP TABLE DatBan; --.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Tên lưu nguyên văn; bảng DatBan còn nguyên.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### WEB01 — Trang khách hiển thị chi nhánh MySQL và form thật
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 200; không tải app.js mẫu.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB02 — Từ chối POST thiếu CSRF
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 403; không ghi dữ liệu.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB03 — Gửi form hợp lệ và chuyển hướng sau POST
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 303; lưu đúng một CHO_XAC_NHAN.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB04 — Refresh và gửi lại cùng request_key
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: GET không tạo thêm; POST cũ HTTP 409.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB17 — HTML escape tên khách
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: Tên script hiển thị dạng text, không thành thẻ script.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### GUI01 — Gửi yêu cầu bằng trình duyệt
- Phương pháp: Thao tác trình duyệt.
- Dữ liệu/điều kiện: CN01; 2 khách; 15/11/2026 18:00; SĐT kiểm thử.
- Bước thực hiện: CN01; 2 khách; 15/11/2026 18:00; SĐT kiểm thử
- Mong đợi: Hiện thông báo thành công và mã DB, yêu cầu chưa giữ bàn.
- Thực tế: Đã thao tác trên trình duyệt; hiển thị mã mới và thông báo chờ xác nhận.
- Kết quả: **PASS**.

## Tạo đặt bàn tại quầy — 10 test case

### TQ01 — Tiếp nhận hợp lệ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Bàn trống 4 ghế; 4 khách.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Đơn và bàn DANG_PHUC_VU; lưu giờ nhận.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TQ02 — Bàn đang phục vụ hoặc không đủ chỗ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Tạo tại quầy hai lần cùng bàn; bàn 2 ghế.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: CONFLICT; không có thêm đơn.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TQ03 — Xung đột với lịch xác nhận
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Lịch giữ trong 30–90 phút tới; khách quầy phục vụ 2 giờ.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: CONFLICT; không đổi bàn.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TQ04 — Tên/SĐT tùy chọn nhưng nhập phải hợp lệ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Bỏ trống; SĐT abc.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Bỏ trống được lưu NULL; SĐT sai bị từ chối.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TQ05 — Giả chi nhánh và vai trò
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Bàn CN02; owner/manager/Bếp gửi thao tác.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Bàn sai bị từ chối; vai trò sai FORBIDDEN.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TQ06 — Kết thúc đã qua
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Giờ kết thúc hôm qua.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TQ07 — Rollback khi lỗi cập nhật bàn
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Trigger kiểm thử gây lỗi UPDATE Ban.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Không còn đơn mới; bàn vẫn TRONG.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TQ08 — Hai process tiếp nhận cùng bàn
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Hai request quầy đồng thời.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Đúng 1 thành công và 1 CONFLICT.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### WEB15 — Nhận khách qua form
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: POST 303; đơn và bàn DANG_PHUC_VU.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB16 — Gửi lại form quầy cũ
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 409; không tạo thêm.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

## Hủy đặt bàn — 10 test case

### HY01 — Hủy yêu cầu chờ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Đơn chưa gán bàn; lý do hợp lệ.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: DA_HUY; giữ lịch sử và người hủy.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### HY02 — Giải phóng lịch đã xác nhận
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Hủy lịch 18–20 rồi xác nhận yêu cầu khác cùng khoảng.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Đơn cũ giữ maBan; lịch được dùng lại.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### HY03 — Không làm trống bàn đang phục vụ khách khác
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Bàn có lịch tương lai và đang phục vụ tại quầy.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Hủy lịch tương lai; bàn vẫn DANG_PHUC_VU.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### HY04 — Bắt buộc lý do
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Rỗng hoặc quá 255 ký tự.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### HY05 — Không hủy lượt đang phục vụ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Đặt bàn tại quầy DANG_PHUC_VU.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: CONFLICT.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### HY06 — Sai chi nhánh, không tồn tại và hủy lặp
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: CN02 hủy CN01; mã sai; gửi hủy hai lần.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: FORBIDDEN; NOT_FOUND; hủy lặp giữ nguyên lịch sử.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### WEB13 — Thiếu lý do không được hủy
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 422; đơn còn DA_XAC_NHAN.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB14 — Hủy hợp lệ qua HTTP
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: POST 303; DB DA_HUY; khách thấy Đã hủy.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### GUI03 — Chọn Không trong hộp thoại hủy
- Phương pháp: Thao tác trình duyệt.
- Dữ liệu/điều kiện: Yêu cầu đã xác nhận; mở Hủy rồi chọn Không.
- Bước thực hiện: Yêu cầu đã xác nhận; mở Hủy rồi chọn Không
- Mong đợi: Đóng hộp thoại; yêu cầu vẫn Đã xác nhận.
- Thực tế: Đã bấm Không; hộp thoại đóng, trạng thái vẫn Đã xác nhận.
- Kết quả: **PASS**.

### GUI04 — Xác nhận hủy bằng trình duyệt
- Phương pháp: Thao tác trình duyệt.
- Dữ liệu/điều kiện: Lịch tương lai trên A02; A02 đang phục vụ khách khác hôm nay.
- Bước thực hiện: Lịch tương lai trên A02; A02 đang phục vụ khách khác hôm nay
- Mong đợi: Yêu cầu DA_HUY; bàn vật lý vẫn DANG_PHUC_VU.
- Thực tế: Đã nhập lý do và hủy; màn hình hiển thị Đã hủy, bàn A02 vẫn Đang phục vụ.
- Kết quả: **PASS**.

## Tra cứu đặt bàn — 9 test case

### TC01 — Tìm theo tên và số điện thoại
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Khách; 0912.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Trả yêu cầu đúng CN01, maBan có thể NULL.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TC02 — Giới hạn dữ liệu chi nhánh
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: CN01 và CN02 có yêu cầu cùng số điện thoại.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Phục vụ CN01 chỉ thấy CN01.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TC03 — Xem chi tiết khác chi nhánh
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Phục vụ CN02 mở mã CN01.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: FORBIDDEN.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TC04 — Khách/Bếp không có quyền
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: actor NULL hoặc BEP.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: FORBIDDEN.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TC05 — Lọc ngày và trạng thái
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Ngày đúng/sai; trạng thái hợp lệ/không hợp lệ.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Lọc đúng; bộ lọc không hợp lệ bị từ chối.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TC06 — Ký tự LIKE và SQL không mở rộng truy vấn
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: %; _; chuỗi SQL.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Chỉ tìm chuỗi literal; không trả toàn bộ.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### WEB07 — Khách vào trang nhân viên
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 303 về login.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB08 — Phục vụ lọc tên và xem chi tiết
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTML có mã đúng.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB09 — Phục vụ CN02 xem mã CN01
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 403.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

## Xác nhận đặt bàn — 11 test case

### XN01 — Xác nhận bàn phù hợp
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: CN01, bàn 4 ghế; khung giờ hợp lệ.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Gán bàn, DA_XAC_NHAN, người xác nhận; trạng thái vật lý vẫn TRONG.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### XN02 — Bàn thiếu sức chứa hoặc ngừng
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: 4 khách; bàn 2 ghế / NGUNG.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: CONFLICT; đơn vẫn chờ.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### XN03 — Bàn sai chi nhánh và actor sai phạm vi
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Bàn CN02 hoặc phục vụ CN02.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR/FORBIDDEN.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### XN04 — Khung giờ trùng
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Hai yêu cầu cùng bàn và khoảng.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Yêu cầu sau CONFLICT, còn chờ.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### XN05 — Hai khoảng sát giờ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: 18–20 và 20–22 cùng bàn.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Cả hai được xác nhận theo khoảng [đầu, cuối).
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### XN06 — Kết thúc sai hoặc xác nhận lặp
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Kết thúc <= đến; đơn đã xác nhận.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR rồi CONFLICT.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### XN07 — Không có bàn phù hợp
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: 32767 khách.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Danh sách bàn rỗng.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### XN08 — Hai nhân viên tranh cùng bàn
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Hai process, hai yêu cầu, cùng bàn/khung giờ.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Đúng 1 thành công, 1 CONFLICT; không đặt trùng.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### WEB10 — Chủ chuỗi chỉ xem, không giả POST xác nhận
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 403; đơn vẫn chờ.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB11 — Kiểm tra bàn phù hợp và xác nhận bằng form
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: GET có C1-A01; POST 303; DB DA_XAC_NHAN.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### GUI02 — Kiểm tra bàn rồi xác nhận bằng trình duyệt
- Phương pháp: Thao tác trình duyệt.
- Dữ liệu/điều kiện: Yêu cầu GUI01; kết thúc 20:00; bàn A02.
- Bước thực hiện: Yêu cầu GUI01; kết thúc 20:00; bàn A02
- Mong đợi: Danh sách bàn phù hợp; xác nhận chuyển DA_XAC_NHAN.
- Thực tế: Đã chọn giờ, kiểm tra bàn và xác nhận; màn hình hiển thị Đã xác nhận và bàn A02.
- Kết quả: **PASS**.

## Theo dõi trạng thái — 9 test case

### TD01 — Tra cứu số điện thoại đã đặt
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: 0912345678.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Trả đúng yêu cầu và chi nhánh.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TD02 — Số không có yêu cầu
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: 0999999999.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Kết quả rỗng.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TD03 — Số không hợp lệ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: chuỗi SQL hoặc rỗng.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: VALIDATION_ERROR.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TD04 — Cập nhật sau xác nhận và hủy
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: CHO_XAC_NHAN → DA_XAC_NHAN → DA_HUY.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Tra cứu mỗi lần phản ánh trạng thái mới nhất.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### TD05 — Không lộ ghi chú và hồ sơ nội bộ
- Phương pháp: Service/PDO.
- Dữ liệu/điều kiện: Tra cứu yêu cầu có ghi chú.
- Bước thực hiện: Khởi tạo dữ liệu theo tiền điều kiện; gọi method của chức năng; đối chiếu lỗi/kết quả và SELECT dữ liệu bằng assertion.
- Mong đợi: Không có ghiChu, maNguoiTao, SĐT, tên khách trong kết quả công khai.
- Thực tế: Đạt các assertion về kết quả và dữ liệu lưu trong MySQL.
- Kết quả: **PASS**.

### WEB05 — SĐT sai trên form công khai
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTTP 422 và thông báo lỗi.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB06 — Tra cứu sau khi đặt
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTML có mã và Chờ xác nhận.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB12 — Khách thấy trạng thái mới sau xác nhận
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTML Đã xác nhận.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

### WEB18 — Không có kết quả
- Phương pháp: HTTP tự động.
- Dữ liệu/điều kiện: Thực thi biểu mẫu qua HTTP trên bản sao source và database tạm..
- Bước thực hiện: GET form để lấy CSRF/nonce; gửi GET/POST như mô tả; kiểm tra HTTP, HTML và database tạm.
- Mong đợi: HTML thông báo Không tìm thấy.
- Thực tế: HTTP status, nội dung HTML và/hoặc dữ liệu MySQL khớp assertion.
- Kết quả: **PASS**.

## Ghi chú minh chứng
Assertion chi tiết nằm trong tests/dat-ban/run.php. Case HTTP có kết quả HTTP/HTML/database trong results.json và mã thử trong gói bài cá nhân. Các ca GUI được thao tác trực tiếp bằng trình duyệt; không gán chúng là test tự động. Ngày/giờ kiểm thử dùng tương đối hiện tại; khi demo tự chọn giờ tương lai mới. Đã có kiểm thử tranh chấp và rollback, chưa kiểm thử tải lớn/đa trình duyệt hoặc MariaDB.