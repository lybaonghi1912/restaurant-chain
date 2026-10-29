# Kiểm thử module đặt bàn

Tại thư mục dự án, mở Laragon Terminal và chạy `php tests/dat-ban/run.php`.

41 test service/PDO sẽ tạo database tạm fbn_reservation_test_*, seed danh mục và nhân viên giả rồi tự xóa database đó. Cần tài khoản quản trị tạo/xóa database và trigger trong môi trường cục bộ; website vẫn dùng tài khoản PDO giới hạn quyền. Thiết lập RESTAURANT_SETUP_HOST, RESTAURANT_SETUP_PORT, RESTAURANT_SETUP_USER, RESTAURANT_SETUP_PASSWORD theo máy nếu không dùng mặc định root localhost không mật khẩu.

Kết quả mặc định ghi tests/dat-ban/results.json (được bỏ khỏi Git). Có thể đổi bằng RESERVATION_TEST_RESULTS. Hai test đồng thời dùng proc_open; cần cho phép hàm đó trong CLI. Không dùng script reset dữ liệu trên database thật.

Toàn bộ 63 test case (41 service, 18 HTTP, 4 GUI) và kết quả ghi trong docs/dat-ban-ca-nhan/. 18 test HTTP đã được chạy trên bản sao source/database tạm, 4 GUI đã được thao tác trong trình duyệt; không gộp chúng vào con số test tự động của run.php.
