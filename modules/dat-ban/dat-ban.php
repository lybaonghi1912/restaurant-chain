<?php if (!defined('APP_ENTRY')) { http_response_code(404); exit; } ?>
<?php // Giao diện chuyển từ mẫu F&B Nodes; dữ liệu hiển thị là dữ liệu mẫu. ?>
<div class="booking-layout">
<div class="booking-visual">
<div class="booking-overlay">
</div>
<div class="customer-nav on-image">
<div class="brand">
<span class="brand-mark light">N</span>
<strong>F&amp;B Nodes</strong>
</div>
<span>Trải nghiệm ẩm thực, trọn vẹn từng khoảnh khắc.</span>
</div>
<div class="visual-copy">
<span class="badge badge-accent">BÀN ĐẸP ĐANG CHỜ BẠN</span>
<h1 class="title title-lg">Một bữa tối đáng nhớ bắt đầu từ đây.</h1>
<p>Không gian ấm cúng, nguyên liệu theo mùa và dịch vụ tận tâm tại cả 3 chi nhánh.</p>
<div class="visual-stats">
<div>
<strong>4.9</strong>
<span>Đánh giá</span>
</div>
<div>
<strong>3</strong>
<span>Chi nhánh</span>
</div>
<div>
<strong>15 phút</strong>
<span>Phản hồi</span>
</div>
</div>
</div>
</div>
<div class="booking-form-wrap">
<div class="mobile-brand brand brand-dark">
<span class="brand-mark">N</span>
<strong>F&amp;B Nodes</strong>
</div>
<span class="eyebrow accent">ĐẶT BÀN TRỰC TUYẾN</span>
<h1 class="title title-lg">Chọn bàn cho dịp đặc biệt</h1>
<p class="lead">Điền thông tin bên dưới, đội ngũ của chúng tôi sẽ xác nhận trong vòng 15 phút.</p>
<div class="form-grid">
<label class="field">
<span class="field-label">Chi nhánh</span>
<select class="control">
<option>Chi nhánh 1 · Thảo Điền</option>
<option>Chi nhánh 2 · Quận 1</option>
<option>Chi nhánh 3 · Phú Mỹ Hưng</option>
</select>
</label>
<div class="availability" title="Khả dụng minh họa; yêu cầu chờ xác nhận chưa giữ bàn.">
<span class="pulse">
</span>
<span>
<strong>Chờ xác nhận</strong> · Gửi yêu cầu chưa giữ bàn</span>
</div>
<label class="field">
<span class="field-label">Ngày</span>
<input class="control " type="date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d') ?>"/>
</label>
<label class="field">
<span class="field-label">Thời gian</span>
<select class="control">
<option>18:30</option>
<option>19:00</option>
<option>19:30</option>
</select>
</label>
<label class="field">
<span class="field-label">Số khách</span>
<div class="stepper">
<button type="button" class="btn btn-ghost " data-guests="-1">−</button>
<strong>4 người</strong>
<button type="button" class="btn btn-ghost " data-guests="1">+</button>
</div>
</label>
<label class="field">
<span class="field-label">Họ và tên</span>
<input class="control " type="text" placeholder="Nguyễn Minh Anh"/>
</label>
<label class="field">
<span class="field-label">Số điện thoại</span>
<input class="control " type="text" placeholder="0903 123 456"/>
<span class="field-hint">Dùng để nhận thông tin xác nhận</span>
</label>
<label class="field">
<span class="field-label">Ghi chú đặc biệt</span>
<textarea class="control textarea" placeholder="Dị ứng thực phẩm, ghế trẻ em, kỷ niệm...">
</textarea>
</label>
</div>
<button type="button" class="btn btn-primary full-button" data-submit-booking="true">Gửi yêu cầu đặt bàn <span>→</span>
</button>
<p class="privacy">Bằng việc gửi yêu cầu, bạn đồng ý với chính sách bảo mật của F&amp;B Nodes.</p>
</div>
</div>