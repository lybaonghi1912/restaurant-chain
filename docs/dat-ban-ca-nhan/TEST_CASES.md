# Test case đặt bàn theo mẫu giảng viên

Dữ liệu D0/K0 và đồng hồ thử: xem file Word và README_BAI_CA_NHAN.md. Mỗi ca độc lập.

## Đặt bàn

| Chức năng | Tiền điều kiện | Mô tả | Dữ liệu Test | Kết quả mong muốn |
|---|---|---|---|---|
| DB01 Đặt bàn | Khách mở trang Đặt bàn. | Gửi hợp lệ | D0 không thay đổi. | Lưu CHO_XAC_NHAN, maBan NULL; không đổi trạng thái bàn |
| DB02 Đặt bàn | Khách mở trang Đặt bàn. | Thiếu họ tên | D0; tenKhach="". | VALIDATION_ERROR; không lưu |
| DB03 Đặt bàn | Khách mở trang Đặt bàn. | Sai số điện thoại | D0; soDienThoai="abc". | VALIDATION_ERROR |
| DB04 Đặt bàn | Khách mở trang Đặt bàn. | Số khách không nguyên dương | D0; lần lượt soKhach=0, -1, 1.5, mảng rỗng. | Mọi giá trị bị VALIDATION_ERROR; không tạo đơn. |
| DB05 Đặt bàn | Khách mở trang Đặt bàn. | Ngày giờ quá khứ hoặc không tồn tại | D0; lần lượt thoiGianDen=2020-01-01T18:00 và 2030-02-30T18:00. | VALIDATION_ERROR |
| DB06 Đặt bàn | Khách mở trang Đặt bàn. | Chi nhánh ngừng hoạt động | D0; ChiNhanh CN01.trangThai=NGUNG. | VALIDATION_ERROR; không lưu |
| DB07 Đặt bàn | Khách mở trang Đặt bàn. | Giới hạn độ dài và kiểu dữ liệu | D0; lần 1 tên gồm 101 ký tự a; lần 2 maChiNhanh là mảng rỗng. | Cả hai lần VALIDATION_ERROR; không tạo đơn. |
| DB08 Đặt bàn | Khách mở trang Đặt bàn. | Chuỗi SQL được lưu như dữ liệu | D0; tenKhach="O'Neil; DROP TABLE DatBan; --". | Tên lưu nguyên văn; bảng DatBan còn nguyên |

## Theo dõi trạng thái đặt bàn

| Chức năng | Tiền điều kiện | Mô tả | Dữ liệu Test | Kết quả mong muốn |
|---|---|---|---|---|
| TD01 Theo dõi trạng thái đặt bàn | Khách mở trang Theo dõi; không cần đăng nhập. | Tra cứu số điện thoại đã đặt | Tạo D0; tra cứu SĐT 0912345678. | Trả đúng yêu cầu và chi nhánh |
| TD02 Theo dõi trạng thái đặt bàn | Khách mở trang Theo dõi; không cần đăng nhập. | Số không có yêu cầu | Không có đơn; tra cứu 0999999999. | Kết quả rỗng |
| TD03 Theo dõi trạng thái đặt bàn | Khách mở trang Theo dõi; không cần đăng nhập. | Số không hợp lệ | Lần lượt SĐT="" và "' OR 1=1 --". | VALIDATION_ERROR |
| TD04 Theo dõi trạng thái đặt bàn | Khách mở trang Theo dõi; không cần đăng nhập. | Cập nhật sau xác nhận và hủy | Tạo D0 → xác nhận C1-A01 → tra SĐT 0912345678 → hủy với lý do "Khách đổi lịch" → tra lại. | Lần tra đầu DA_XAC_NHAN; lần tra sau DA_HUY, cùng mã đặt bàn. |
| TD05 Theo dõi trạng thái đặt bàn | Khách mở trang Theo dõi; không cần đăng nhập. | Không lộ ghi chú và hồ sơ nội bộ | Tạo D0 có ghiChu="Ghế trẻ em"; tra 0912345678. | Không có ghiChu, maNguoiTao, SĐT, tên khách trong kết quả công khai |
| TD06 Theo dõi trạng thái đặt bàn | Khách mở trang Theo dõi; không cần đăng nhập. | Tự hủy sau hạn 1 giây | D0 xác nhận C1-A01; 18:15:01; chưa đến | DA_HUY; maNguoiHuy NULL; bàn trống dùng lại được |
| TD07 Theo dõi trạng thái đặt bàn | Khách mở trang Theo dõi; không cần đăng nhập. | Hết hạn một lịch không hủy lịch khác cùng bàn | D0 18:00 và D0 18:30 cùng bàn; đồng hồ 18:15:01 | Chỉ lịch 18:00 DA_HUY; lịch 18:30 DA_XAC_NHAN |

## Xác nhận đặt bàn

| Chức năng | Tiền điều kiện | Mô tả | Dữ liệu Test | Kết quả mong muốn |
|---|---|---|---|---|
| XN01 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Xác nhận bàn phù hợp | D0 đang chờ; PV01 kiểm tra và chọn C1-A01; không nhập giờ kết thúc. | DA_XAC_NHAN; maBan=C1-A01; maNguoiXacNhan=TSWAIT1; hanGiuCho=18:15; giờ kết thúc NULL; Ban vẫn TRONG. |
| XN02 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Bàn thiếu sức chứa hoặc ngừng | D0 với 4 khách; lần 1 C1-A02 (2 ghế); lần 2 C1-A01 có trạng thái NGUNG. | CONFLICT; đơn vẫn chờ |
| XN03 Xác nhận đặt bàn | Nhân viên/chi nhánh theo từng trường hợp trong dữ liệu test. | Bàn sai chi nhánh và actor sai phạm vi | D0 tại CN01; lần 1 PV01 chọn C2-A01; lần 2 PV02 xác nhận mã CN01. | Bàn sai chi nhánh: VALIDATION_ERROR; nhân viên khác chi nhánh: FORBIDDEN; đơn giữ nguyên. |
| XN04 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Khung giờ trùng | Hai D0 cùng 18:00; lần lượt xác nhận vào C1-A01. | Lần đầu DA_XAC_NHAN; lần sau CONFLICT, vẫn CHO_XAC_NHAN và chưa gán bàn. |
| XN05 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Hai hạn giữ chỗ không chồng nhau | D0 lúc 18:00 và D0 lúc 18:16; cùng C1-A01 | Cả hai DA_XAC_NHAN; hạn lần lượt 18:15 và 18:31 |
| XN06 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Không nhập giờ kết thúc và không xác nhận lặp | D0; C1-A01; không gửi thoiGianKetThucDuKien | Xác nhận thành công, kết thúc NULL; lần hai CONFLICT |
| XN07 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Không có bàn phù hợp | D0; soKhach=32767; PV01 kiểm tra bàn phù hợp. | Danh sách bàn phù hợp rỗng; đơn vẫn CHO_XAC_NHAN. |
| XN08 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Hai nhân viên tranh cùng bàn | Hai D0 cùng 18:00; hai tiến trình xác nhận đồng thời C1-A01; đồng hồ 17:30. | Đúng 1 thành công, 1 CONFLICT; không đặt trùng |
| XN09 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Hạn giữ chỗ tại 18:15 vẫn còn hiệu lực | D0 đã xác nhận C1-A01; đồng hồ 18:15:00 | Không tự hủy; khách đến được tiếp nhận |
| XN10 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Đến trước hạn 1 giây | D0 xác nhận; C1-A01; 18:14:59 | DANG_PHUC_VU; giờ khách đến và nhận bàn đều 18:14:59 |
| XN11 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Khách đang ăn không bị tự hủy | D0 xác nhận; khách đến 18:10; đồng hồ tới 21:00 | Vẫn DANG_PHUC_VU; Ban vẫn DANG_PHUC_VU |
| XN12 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Khách đến đúng hạn nhưng bàn đang bận | D0 xác nhận; Ban DANG_PHUC_VU; khách đến 18:10; bàn trống 18:30 | Ghi nhận đến 18:10; không tự hủy; nhận bàn lúc 18:30 |
| XN13 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Khách đến sau hạn | D0 xác nhận C1-A01; khách đến 18:15:01 | CONFLICT; đơn DA_HUY; không nhận bàn |
| XN14 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Ghi nhận đến lặp không đổi giờ | D0 xác nhận; đến 18:05; bấm lại 18:10 | Một lượt phục vụ; giờ đến vẫn 18:05 |
| XN15 Xác nhận đặt bàn | PV01 đăng nhập CN01; dữ liệu tạo độc lập theo ca. | Hai khoảng chạm đúng hạn cuối | D0 18:00 và D0 18:15 cùng bàn | Đơn thứ hai CONFLICT vì mốc 18:15 vẫn được giữ |

## Tạo đặt bàn tại quầy

| Chức năng | Tiền điều kiện | Mô tả | Dữ liệu Test | Kết quả mong muốn |
|---|---|---|---|---|
| TQ01 Tạo đặt bàn tại quầy | PV01 đăng nhập CN01; đồng hồ 17:30. | Tiếp nhận hợp lệ | K0 lúc 17:30; C1-A01 TRONG; không nhập tên, SĐT hay giờ kết thúc. | Đơn và bàn DANG_PHUC_VU; lưu giờ nhận |
| TQ02 Tạo đặt bàn tại quầy | PV01 đăng nhập CN01; đồng hồ 17:30. | Bàn đang phục vụ hoặc không đủ chỗ | K0 lần 1 chọn C1-A02 (2 ghế); sau đó tạo K0 với C1-A01 hai lần. | CONFLICT; không có thêm đơn |
| TQ03 Tạo đặt bàn tại quầy | PV01 đăng nhập CN01; đồng hồ 17:30. | Bàn có lịch trong 15 phút tới | D0 đổi giờ đến 17:40; tiếp nhận quầy lúc 17:30 tại C1-A01 | CONFLICT; không thêm lượt phục vụ |
| TQ04 Tạo đặt bàn tại quầy | PV01 đăng nhập CN01; đồng hồ 17:30. | Tên/SĐT tùy chọn nhưng nhập phải hợp lệ | K0 lần 1 nhập soDienThoai="abc"; lần 2 bỏ trống tên và SĐT. | SĐT abc: VALIDATION_ERROR; bỏ trống: thành công, lưu tên và SĐT NULL. |
| TQ05 Tạo đặt bàn tại quầy | Nhân viên/chi nhánh theo từng trường hợp trong dữ liệu test. | Giả chi nhánh và vai trò | K0 với bàn C2-A01; sau đó chủ chuỗi, quản lý, BEP và khách gửi K0 với C1-A01. | Bàn sai bị từ chối; vai trò sai FORBIDDEN |
| TQ06 Tạo đặt bàn tại quầy | PV01 đăng nhập CN01; đồng hồ 17:30. | Số khách tại quầy sai | C1-A01; soKhach=0 | VALIDATION_ERROR; không thêm lượt, không đổi bàn. |
| TQ07 Tạo đặt bàn tại quầy | PV01 đăng nhập CN01; đồng hồ 17:30. | Rollback khi lỗi cập nhật bàn | K0; trigger kiểm thử SIGNAL SQLSTATE 45000 khi UPDATE Ban; bỏ trigger sau test. | Không còn đơn mới; bàn vẫn TRONG |
| TQ08 Tạo đặt bàn tại quầy | PV01 đăng nhập CN01; đồng hồ 17:30. | Hai process tiếp nhận cùng bàn | Hai tiến trình cùng tạo K0 tại C1-A01 lúc 17:30. | Đúng 1 thành công và 1 CONFLICT |

## Tra cứu đặt bàn

| Chức năng | Tiền điều kiện | Mô tả | Dữ liệu Test | Kết quả mong muốn |
|---|---|---|---|---|
| TC01 Tra cứu đặt bàn | Vai trò và dữ liệu được tạo theo từng trường hợp. | Tìm theo tên và số điện thoại | Tạo D0; từ khóa lần lượt "Khách", "0912". | Trả yêu cầu đúng CN01, maBan có thể NULL |
| TC02 Tra cứu đặt bàn | Vai trò và dữ liệu được tạo theo từng trường hợp. | Giới hạn dữ liệu chi nhánh | Tạo D0 tại CN01 và bản sao tại CN02; PV01 tra cứu tất cả. | Phục vụ CN01 chỉ thấy CN01 |
| TC03 Tra cứu đặt bàn | Vai trò và dữ liệu được tạo theo từng trường hợp. | Xem chi tiết khác chi nhánh | Tạo D0 tại CN01; PV02 mở mã vừa tạo. | FORBIDDEN |
| TC04 Tra cứu đặt bàn | Vai trò và dữ liệu được tạo theo từng trường hợp. | Khách/Bếp không có quyền | Khách chưa đăng nhập và nhân viên BEP tra cứu nội bộ. | FORBIDDEN |
| TC05 Tra cứu đặt bàn | Vai trò và dữ liệu được tạo theo từng trường hợp. | Lọc ngày và trạng thái | D0; lọc 2030-11-10/CHO_XAC_NHAN; ngày không có đơn 2020-01-01; ngày sai 2030-02-30; trạng thái BAD. | Bộ lọc đúng trả 1 đơn; ngày không có đơn trả rỗng; ngày sai/trạng thái BAD trả VALIDATION_ERROR. |
| TC06 Tra cứu đặt bàn | Vai trò và dữ liệu được tạo theo từng trường hợp. | Ký tự LIKE và SQL không mở rộng truy vấn | D0; từ khóa lần lượt %, _, và "' OR 1=1 --". | Chỉ tìm chuỗi literal; không trả toàn bộ |

## Hủy đặt bàn

| Chức năng | Tiền điều kiện | Mô tả | Dữ liệu Test | Kết quả mong muốn |
|---|---|---|---|---|
| HY01 Hủy đặt bàn | Có đơn theo dữ liệu test; PV01 đăng nhập CN01. | Hủy yêu cầu chờ | D0 đang chờ; lý do "Khách không tiếp tục". | DA_HUY; giữ lịch sử và người hủy |
| HY02 Hủy đặt bàn | Có đơn theo dữ liệu test; PV01 đăng nhập CN01. | Giải phóng lịch đã xác nhận | D0 xác nhận C1-A01 lúc 18:00; hủy "Khách đổi kế hoạch"; tạo D0 khác và xác nhận lại C1-A01. | Đơn cũ DA_HUY vẫn giữ maBan=C1-A01; đơn mới DA_XAC_NHAN dùng lại khoảng giữ 18:00–18:15. |
| HY03 Hủy đặt bàn | Có đơn theo dữ liệu test; PV01 đăng nhập CN01. | Không làm trống bàn đang phục vụ khách khác | D0 xác nhận C1-A01 lúc 18:00; K0 tiếp nhận cùng bàn lúc 17:30; hủy D0 với lý do "Hủy lịch ngày mai". | Chỉ D0 DA_HUY; lượt K0 và Ban vẫn DANG_PHUC_VU. |
| HY04 Hủy đặt bàn | Có đơn theo dữ liệu test; PV01 đăng nhập CN01. | Bắt buộc lý do | D0; lý do lần lượt "" và 256 ký tự a. | VALIDATION_ERROR |
| HY05 Hủy đặt bàn | Có đơn theo dữ liệu test; PV01 đăng nhập CN01. | Không hủy lượt đang phục vụ | Tạo K0 đã DANG_PHUC_VU; gửi hủy với lý do "Hủy". | CONFLICT |
| HY06 Hủy đặt bàn | Nhân viên/chi nhánh theo từng trường hợp trong dữ liệu test. | Sai chi nhánh, không tồn tại và hủy lặp | D0 CN01; PV02 gửi hủy; PV01 hủy mã KHONGCO; PV01 hủy D0 hai lần với "Lý do đầu" rồi "Lý do gửi lại". | Lần lượt FORBIDDEN, NOT_FOUND; hủy lặp không tạo lịch sử mới, vẫn giữ "Lý do đầu". |
| HY07 Hủy đặt bàn | Có đơn theo dữ liệu test; PV01 đăng nhập CN01. | Tự hủy không giải phóng bàn đang phục vụ người khác | D0 xác nhận; Ban DANG_PHUC_VU; quá hạn 18:15:01 | Đơn DA_HUY; Ban vẫn DANG_PHUC_VU |
