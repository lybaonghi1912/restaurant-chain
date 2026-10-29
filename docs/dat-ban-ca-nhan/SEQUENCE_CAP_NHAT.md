# Sequence cập nhật theo code đặt bàn
Đối chiếu SD-1 đến SD-6 của báo cáo gốc. Sáu file PlantUML trong sequence/ là mô tả hành vi đã triển khai, dùng để vẽ lại trong công cụ thiết kế. Tên Entity vật lý giữ PascalCase đúng schema chung, không đổi sang bộ tên bảng khác. Không chỉnh file Word gốc.
## Các điểm cần cập nhật
| Hình | Điều chỉnh theo code và lý do |
|---|---|
| SD-1 | Thêm validate độ dài/chi nhánh hoạt động; POST CSRF, chống gửi lặp; vẫn chưa giữ bàn. |
| SD-2 | Tra cứu POST, chuẩn hóa SĐT, nhánh rỗng/sai dữ liệu; không trả ghi chú hoặc hồ sơ nội bộ. |
| SD-3 | Nhập giờ kết thúc dự kiến; kiểm tra bàn trước khi chọn và kiểm tra lại có khóa; gán DatBan trong transaction. Lịch Đã đặt suy ra từ khoảng, Ban giữ trạng thái vật lý. Thêm nhánh yêu cầu quá giờ. |
| SD-4 | Nhập số khách và giờ kết thúc dự kiến; tên/SĐT tùy chọn; kiểm tra lại bàn và lịch; tạo DatBan + cập nhật Ban trong transaction. |
| SD-5 | Tìm tên/SĐT/mã, lọc ngày/trạng thái là tiện ích trong cùng use case; LEFT JOIN Ban vì chờ xác nhận chưa gán. |
| SD-6 | Hộp thoại Không/Xác nhận hủy giữ đúng đặc tả; thêm lý do hủy; không cập nhật Ban=TRONG vô điều kiện, giữ maBan lịch sử. |
## Quy tắc nghiệp vụ
- BR-01: Dữ liệu web hợp lệ; thời gian đến ở tương lai; số khách nguyên dương; chi nhánh hoạt động.
- BR-02: Gửi web lưu CHO_XAC_NHAN, maBan=NULL và không đổi bàn.
- BR-03: Chỉ phục vụ thao tác ghi trong chi nhánh; truy vấn nội bộ lọc server-side.
- BR-04: Giờ kết thúc sau bắt đầu; bàn cùng chi nhánh, đủ chỗ và không NGUNG.
- BR-05: Khoảng [đầu,cuối), kiểm tra lịch DA_XAC_NHAN/DANG_PHUC_VU; khóa bàn rồi đọc lại trước ghi.
- BR-06: Tại quầy nhận ngay, bàn TRONG; đơn và bàn cùng DANG_PHUC_VU trong transaction.
- BR-07: Chỉ hủy CHO_XAC_NHAN/DA_XAC_NHAN; lý do 1–255 ký tự; giữ lịch sử; không làm trống bàn khác.
- BR-08: Tra cứu công khai đúng SĐT, chỉ trả trường cần thiết; nội bộ tìm tên/SĐT/mã.
- BR-09: CSRF và request_key ở adapter HTTP; redirect sau ghi thành công; escape output.
- BR-10: Lỗi transaction rollback; PDO lỗi được bootstrap trả 503 chung, không lộ chi tiết kết nối.
## Message về Boundary
Mã MSG là nhãn tài liệu; code trả BookingError.errorCode và câu thông báo tiếng Việt. MSG-02/04/06 là nhóm message có câu chi tiết theo tình huống.
- MSG-01: Yêu cầu đã được gửi, vui lòng chờ xác nhận.
- MSG-02: Thông tin không hợp lệ; hiển thị lỗi tương ứng trường nhập.
- MSG-03: Không tìm thấy đơn đặt bàn theo số điện thoại đã nhập.
- MSG-04: Không có quyền thực hiện hoặc sai chi nhánh.
- MSG-05: Không tìm thấy yêu cầu đặt bàn.
- MSG-06: Xung đột trạng thái/bàn không còn khả dụng; tải lại hoặc chọn bàn khác.
- MSG-07: Tạm thời chưa kết nối được hệ thống; thử lại.
- MSG-08: Xác nhận đặt bàn thành công.
- MSG-09: Tạo đặt bàn tại quầy thành công.
- MSG-10: Đã hủy yêu cầu đặt bàn; lịch của yêu cầu được giải phóng.
- MSG-11: Danh sách/chi tiết đặt bàn và thông tin bàn nếu có.
- MSG-12: Danh sách trạng thái mới nhất theo SĐT.
- MSG-13: Hiện không có bàn phù hợp; liên hệ khách hàng để thông báo.
## Phân tầng và giao dịch
Boundary là view cộng adapter web.php. DatBanControl điều phối ba Entity qua cùng PDO, tương ứng Control xử lý đặt bàn trong hình gốc. Các Entity chỉ thực hiện truy vấn của dữ liệu mình. Khóa transaction là chi tiết kỹ thuật của Control, không là use case mới. Lỗi truy vấn/commit rollback trước khi trả MSG-07; ở code bootstrap xử lý lỗi hệ thống. Lỗi validate có thể xảy ra trước hoặc trong transaction tùy thao tác; sơ đồ ghi nhóm validate tại bước quyết định tương ứng.
## Điều kiện đang áp dụng
Thời lượng phục vụ không tự mặc định 2 giờ; phục vụ nhập giờ kết thúc. Giờ mở cửa chưa cấu hình nên chỉ kiểm tra thời gian tương lai; không tự đặt quy tắc giờ kinh doanh. Chưa có chức năng nhận khách từ đặt trước/hoàn tất phục vụ trong phạm vi cá nhân; không tự đổi trạng thái khi qua giờ. Theo dõi được tra cứu lại để cập nhật, chưa có push realtime.
## Ánh xạ tới kiểm thử
- SD-1: DB01–DB08, WEB01–04/17, GUI01.
- SD-4: TQ01–TQ08, WEB15–16.
- SD-6: HY01–HY06, WEB13–14, GUI03–04.
- SD-5: TC01–TC06, WEB07–09.
- SD-3: XN01–XN08, WEB10–11, GUI02.
- SD-2: TD01–TD05, WEB05–06/12/18.