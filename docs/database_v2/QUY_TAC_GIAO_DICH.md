# Quy tắc giao dịch và hợp đồng dữ liệu V2

Đây là hợp đồng bắt buộc cho các module PHP/PDO tiếp theo. Schema cung cấp PK/FK/UNIQUE/CHECK; các quy tắc liên bản ghi, phân quyền, tổng tiền và chống trùng khoảng thời gian phải do service thực hiện bằng transaction. Chưa triển khai đầy đủ các service nghiệp vụ trong website. Không tuyên bố chỉ import SQL là tự có những quy tắc này.

## R01 — Phạm vi và lịch đặt bàn (SD-1–5)

Chủ chuỗi xem toàn chuỗi; người dùng chi nhánh chỉ xử lý dữ liệu chi nhánh của mình. Actor lấy từ session, không tin maNguoiTao/maChiNhanh gửi từ trình duyệt. Khách gửi yêu cầu không đăng nhập theo SD-1; không gán bàn ở trạng thái CHO_XAC_NHAN.

Bản đặt bàn ngày 30/10/2026 dùng khoảng giữ chỗ [thoiGianDen, hanGiuCho], với hanGiuCho = thoiGianDen +15 phút, tính cả hai đầu. Hai khoảng giao nhau khi `cu.thoiGianDen <= moi.hanGiuCho AND cu.hanGiuCho >= moi.thoiGianDen`. Lịch 18:00–18:15 không gán cùng bàn với lịch 18:15, nhưng không giao lịch 18:16. Không dùng khoảng giữ chỗ làm thời lượng dùng bữa.

Chỉ DA_XAC_NHAN chưa đến giữ theo khoảng này; DANG_PHUC_VU hoặc DA_XAC_NHAN đã có thoiGianKhachDen chặn gán lượt khác tới khi hoàn tất. DA_HUY/DA_HOAN_TAT không chiếm lịch. Bàn cùng chi nhánh, đủ sức chứa, không NGUNG. Ban.trangThai là trạng thái vật lý TRONG/DANG_PHUC_VU/NGUNG; không lưu thêm bảng GiuBan.

Không nhập giờ kết thúc dự kiến. Khi hiện tại > hanGiuCho và đơn DA_XAC_NHAN chưa có giờ khách đến, chuyển DA_HUY, lý do tự động và maNguoiHuy=NULL. Đặt 18:00 vẫn được nhận lúc 18:15:00; 18:15:01 là quá hạn. MySQL Event kiểm tra mỗi phút; đọc trang đặt bàn đồng bộ ngay. Không hủy lượt đã đến/đang ăn và không làm trống bàn đang phục vụ người khác. CHO_XAC_NHAN chưa giữ chỗ nên không tự hủy bằng quy tắc này; không được xác nhận khi đã quá hạn.

## R02 — Xác nhận, tại quầy và hủy (SD-3/4/6)

Thứ tự khóa chung cho đặt bàn: ChiNhanh FOR SHARE → Ban FOR UPDATE (mã tăng dần nếu nhiều bàn) → DatBan FOR UPDATE. Có thể đọc maBan tạm để biết bàn cần khóa, nhưng phải đọc lại trạng thái/maBan sau khóa; nếu thay đổi thì rollback và thử lại. Xác nhận bắt đầu bằng khóa bàn mục tiêu, rồi khóa đơn và kiểm tra lại toàn bộ điều kiện.

BEGIN → khóa ChiNhanh FOR SHARE → `SELECT ... FROM Ban WHERE maBan=? FOR UPDATE` → khóa DatBan → truy vấn lịch trùng bằng **locking read FOR UPDATE** → kiểm tra trạng thái/sức chứa/chi nhánh → cập nhật DatBan → cập nhật Ban nếu tại quầy đang nhận khách → COMMIT. Mọi đường ghi lịch dùng cùng khóa bàn; SELECT kiểm tra sau khi chờ khóa phải đọc dữ liệu mới nhất, không dùng snapshot cũ. CHECK không thể tự ngăn chồng lịch.

Hủy chỉ cho CHO_XAC_NHAN/DA_XAC_NHAN theo quy tắc đề xuất; đang phục vụ thì từ chối vì SD-6 không mô tả kết thúc phục vụ. Ghi lý do và người hủy, giữ maBan làm lịch sử; không đặt Ban=TRONG vô điều kiện. Hủy lại cùng yêu cầu không sinh hiệu ứng lần hai.

Ghi nhận khách đến đúng hạn: nếu bàn TRONG thì cập nhật đơn/bàn DANG_PHUC_VU, lưu giờ đến và giờ nhận bàn trong một transaction. Bàn bận: lưu giờ đến đầu tiên, giữ DA_XAC_NHAN và chờ; khi trống, thao tác tiếp nhận ghi giờ nhận bàn mới. Khách đã có mặt không bị tự hủy. Tại quầy nhận ngay, bắt buộc bàn TRONG, không có lượt đang phục vụ/chờ và không có khoảng giữ chỗ giao [hiện tại, hiện tại +15 phút]; tên/SĐT tùy chọn. Không thuộc phạm vi hoàn tất phục vụ của module cá nhân.

## R03 — Nhân sự và tài khoản (SD-7/8/10/11)

Tạo nhân viên, tạo tài khoản và ghi NhatKyNhanSu trong một transaction. Kiểm tra CCCD 12 số duy nhất; hồ sơ cũ chưa bổ sung CCCD được NULL. Không cho Quản lý tạo/sửa Chủ chuỗi hay vượt chi nhánh. VaiTro chỉ có 5 mã đã chốt. CaLamViec phục vụ thông tin hồ sơ, không thêm xếp lịch/chấm công.

Khóa NhanVien → TaiKhoan; sửa hồ sơ và ghi nhật ký. Khi đổi vai trò/phạm vi hoặc khóa tài khoản: tăng TaiKhoan.phienBan để phiên cũ mất hiệu lực. Xóa nhân viên = NGHI_VIEC + KHOA tài khoản + tăng phienBan + nhật ký, không DELETE. Không ghi hash/mật khẩu vào JSON nhật ký. Mật khẩu dùng password_hash/password_verify; 123456 là mật khẩu 5 tài khoản demo theo yêu cầu, không ghi dạng rõ vào SQL.

## R04 — Đơn món và bếp (SD-9/25–27)

Tạo đơn: khóa Ban → kiểm tra bàn đang phục vụ và chưa có đơn mở → tạo DonGoiMon + các ChiTietDonMon → tổng tiền = SUM(soLuong*donGia của dòng chưa DA_HUY) → ghi giờ gửi bếp → commit. Một bàn tối đa một đơn DANG_PHUC_VU, quy tắc thực thi bằng khóa Ban và truy vấn, không chỉ UI. Thêm/hủy/tiếp nhận: khóa DonGoiMon → ChiTietDonMon theo mã tăng dần. Không khóa theo thứ tự ngược lại.

Giá lấy từ ThucDonChiNhanh.giaBan nếu có, nếu không dùng MonAn.donGia; kiểm tra món/nhóm/chi nhánh đang hoạt động, món đang bán. Tên và giá lưu snapshot vào chi tiết. Tổng tiền không nhận từ client. Thêm món là dòng mới để không thay đổi dòng bếp đã nhận.

Tiếp nhận: CHO_CHE_BIEN → DANG_CHE_BIEN, lưu maNhanVienBep/gioTiepNhan, UPDATE có điều kiện trạng thái cũ; số dòng cập nhật phải là 1. Hủy chỉ từ CHO_CHE_BIEN, dùng maCTDon, bắt buộc lý do, cập nhật tổng cùng transaction. Hai thao tác tranh chấp thì người giữ khóa trước quyết định, người sau đọc lại trạng thái. Các trạng thái HOAN_THANH/DA_PHUC_VU/HOAN_TAT là vòng đời dữ liệu dự phòng; không tự tạo màn hình hoàn thành/thanh toán chưa phân công. Không tự động trừ/hoàn kho khi gửi bếp/hủy món trong phạm vi này.

## R05 — Định mức (SD-12)

Khóa MonAn, lấy công thức cũ, kiểm tra NVL hợp lệ và mỗi NVL xuất hiện một lần; lượng >0, dùng đơn vị cơ sở tại NguyenVatLieu. Thay toàn bộ các dòng DinhMucNVL và thêm LichSuDinhMuc (JSON trước/sau, người sửa, thời gian) cùng transaction. Không tự trừ kho; không biến nguyên liệu thành món. MonAn.trangThaiDinhMuc là dữ liệu dẫn xuất được cập nhật cùng công thức.

## R06 — Chi nhánh (SD-13–16)

Tên chi nhánh kiểm tra trùng trong chuỗi ở service; khi sửa loại trừ mã hiện tại. maQuanLy nếu có phải là nhân viên QUAN_LY đang làm tại chính chi nhánh đó (FK ghép ngăn sai chi nhánh; kiểm tra vai trò/trạng thái ở service). Kinh/vĩ độ có thể NULL; SD-17 không tính khoảng cách giả khi thiếu tọa độ. Bàn và thực đơn liên quan được cập nhật cùng transaction khi thao tác có các phần này.

Ngừng chi nhánh phải kiểm tra nhân viên đang làm, đơn đang mở, đặt bàn còn hiệu lực; lưu NGUNG, giữ lịch sử. Tất cả service tạo dữ liệu mới đều kiểm tra chi nhánh còn hoạt động để tránh race với ngừng hoạt động. Ngoại lệ đã được nhóm xác nhận: Chủ chuỗi được xóa vĩnh viễn dữ liệu riêng của chi nhánh đã NGUNG qua thao tác xác nhận riêng. Phải kiểm tra dấu vân tay dữ liệu, chặn tham chiếu từ ngoài chi nhánh và dữ liệu điều chuyển dùng chung, giữ kiểm tra khóa ngoại và rollback toàn bộ nếu có lỗi; không tự cascade-delete khi ngừng hoạt động.

## R07 — Chuỗi yêu cầu bổ sung NVL (SD-17/18/28/31)

SD-28: khóa PhieuDeXuat → kiểm tra CHO_XU_LY và đúng chi nhánh → tạo YeuCauNhapKho → đổi phiếu DA_TAO_YEU_CAU → commit. UNIQUE(maPhieu) bảo vệ nhấn gửi hai lần. Dữ liệu phiếu là đầu vào của use case; việc giữ bảng không đồng nghĩa giao thêm người làm màn hình lập phiếu.

SD-17/18: khóa YeuCauNhapKho → đọc dòng ChiTietDeXuat → tính phần cần phân bổ → tạo phương án + chi tiết → đổi trạng thái DA_LAP_PHUONG_AN nếu đã phân bổ đủ. Cùng yêu cầu có thể tách cho nhiều chi nhánh/NCC. Chống phân bổ quá lượng: trong cùng khóa yêu cầu, tổng lượng chi tiết DonNhapNCC + lượng DieuChuyenKho CHO_XAC_NHAN_NGUON/DA_DUYET cho từng NVL không vượt ChiTietDeXuat.soLuong; TU_CHOI không tính để có thể lập lại. Mọi dòng phương án phải thuộc NVL của phiếu. Đây là quy tắc service, FK không chứng minh đủ lượng.

NCC phải HOAT_DONG, có ánh xạ NhaCungCapNguyenLieu hoạt động. Lượng tồn còn hạn tính từ các lô hoạt động HSD >= ngày hiện tại, trừ các yêu cầu nguồn đã DA_DUYET nếu nhóm coi đó là lượng đã cam kết. Yêu cầu chờ chỉ là đề nghị; không hứa giữ hàng khi chưa duyệt.

Theo quyết định nhóm đã xác nhận khi tích hợp, Chủ chuỗi duyệt toàn bộ điều chuyển, thay bước chờ Quản lý chi nhánh nguồn xác nhận SD-31. Khi tạo điều chuyển mới, kiểm tra tồn còn hạn sau cam kết trong khóa giao dịch rồi lưu DA_DUYET, người duyệt và thời gian duyệt. Phiếu cũ CHO_XAC_NHAN_NGUON cũng do Chủ chuỗi duyệt. Thứ tự khóa: YeuCauNhapKho → các lô theo (maChiNhanh,maLo) tăng dần → DieuChuyenKho; không duyệt vượt tồn. Không sửa số dư vật lý, không ghi GiaoDichKho. Đơn NCC dừng ở CHO_NHAP_HANG. Khi hủy phiếu, giữ lịch sử và mở lại phần thiếu của yêu cầu trong cùng transaction. Hiện chưa có chứng từ xuất/nhập gắn với phiếu: bước hủy yêu cầu người dùng xác nhận chưa thực hiện; khi nối luồng xuất/nhập thực tế phải bổ sung kiểm tra chứng từ tự động.

Đầu nhận yêu cầu SD-28 và người duyệt điều chuyển nguyên liệu là Chủ chuỗi. Không gộp với YeuCauDieuChuyen nhân viên.

## R08 — Món ăn (SD-19–24)

NhomMon là tên bảng duy nhất cho LoaiMonAn trong hình. Giá DECIMAL(18,2), số lượng công thức DECIMAL(18,3), không dùng float tính tiền. Kiểm tra tên trùng theo nhóm bằng so sánh chuẩn hóa ở server; không coi index tìm kiếm là ràng buộc duy nhất. Xóa = NGUNG, ngừng thực đơn tương ứng; giữ chi tiết đơn/công thức/lịch sử. Tìm kiếm dùng prepared statements.

## R09 — Yêu cầu điều chuyển nhân viên (SD-29)

YeuCauDieuChuyen có thông tin vị trí, lượng >0, thời gian kết thúc > bắt đầu, lý do, người gửi và chi nhánh. Quản lý tạo yêu cầu CHO_DUYET; Chủ chuỗi duyệt DA_DUYET hoặc TU_CHOI, ghi người/giờ xử lý và bắt buộc lý do khi từ chối theo migration 004. Duyệt không tự chuyển hồ sơ nhân viên và không chỉ định cá nhân. DieuChuyenKho là khái niệm khác.

## R10 — Kho và sổ biến động (SD-30)

Danh mục NVL không chứa tồn gộp. TonKhoTheoLo là nguồn số dư vật lý theo (maChiNhanh,maLo); mỗi lô có một maNVL cố định, HSD và đơn vị của danh mục. Không cho sửa maNVL của lô đã có biến động. Lô hết hạn vẫn nằm trong tồn vật lý nhưng không được tính là khả dụng.

Mỗi thay đổi lượng: BEGIN → khóa lô → kiểm tra số dư mới >=0 và trạng thái → tạo GiaoDichKho + ChiTietGiaoDichKho → cập nhật số dư → COMMIT; lỗi bất kỳ rollback toàn bộ. Số lượng dòng luôn >0; TON_DAU/NHAP/DIEU_CHINH_TANG là tăng; XUAT/DIEU_CHINH_GIAM là giảm. `khoaChongLap` duy nhất từ một thao tác nghiệp vụ để retry không ghi hai lần. Sổ đã ghi không sửa/xóa; sửa sai bằng biến động bù có lý do. Không cấp thêm màn hình nhập NCC/xuất chuyển hàng chỉ vì sổ có NHAP/XUAT. Seed không tạo tồn giả; dữ liệu nhập/xuất thực tế là đầu vào của quản lý kho/báo cáo.

Xóa NVL/lô chỉ ngừng sử dụng khi không còn lượng tồn và không làm mất tham chiếu lịch sử; service kiểm tra lượng cam kết. Các thao tác giảm lượng phải xét phần đã cam kết DA_DUYET để không làm thiếu yêu cầu nguồn. FK giữ lịch sử. V_DoiSoatKho.chenhLech phải bằng 0 sau mọi nghiệp vụ. CHECK số dư không âm không thay thế transaction ghi sổ.

## R11 — Báo cáo kho (SD-32)

Theo chi nhánh, NVL, khoảng [tuNgay 00:00, denNgay+1 ngày 00:00). Tồn đầu = SUM biến động trước đầu kỳ; phát sinh tăng/giảm trong kỳ lấy V_BienDongKho; tồn cuối = tồn đầu + tăng - giảm. TON_DAU ghi tại ngày bắt đầu quản lý số liệu; không lấy số dư hiện tại làm tồn đầu của kỳ quá khứ. Có thể tách NHAP/XUAT thuần và điều chỉnh tăng/giảm thành cột riêng; tổng phát sinh phải bao gồm điều chỉnh. Không cộng khác đơn vị thành một số tổng vật lý. Đọc các phần báo cáo trong một consistent snapshot; xuất Excel/PDF dùng cùng bộ lọc/quyền, không gọi lại dữ liệu khác kỳ.

## Hợp đồng lỗi dùng chung

Service trả kết quả nhất quán: `ok`, `code`, `message`, `data`; code gợi ý NOT_FOUND, FORBIDDEN, VALIDATION_ERROR, CONFLICT, INSUFFICIENT_STOCK. HTTP tương ứng 404/403/422/409. Không trả thông báo lỗi PDO hay thông tin kết nối cho trình duyệt. Mã chứng từ VARCHAR(20) phải sinh không trùng, không dùng COUNT(*)+1. Dùng server time Asia/Ho_Chi_Minh và kết nối +07:00; DATETIME trong schema lưu giờ Việt Nam theo quy ước này. ID FK không UPDATE tùy tiện.

Mỗi transaction bắt Throwable, rollback nếu còn transaction, chỉ commit khi mọi bước thành công. Retry deadlock có giới hạn cho toàn transaction; dùng khóa chống lặp nếu thao tác được gửi lại. Đây là quy tắc triển khai, không phải các service đã viết sẵn.
