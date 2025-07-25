# Hướng dẫn sử dụng hệ thống quản lý nghiên cứu

## Giới thiệu
Hệ thống quản lý nghiên cứu được tích hợp vào hệ thống quản lý nghiên cứu khoa học hiện có, cho phép quản lý các hoạt động nghiên cứu, theo dõi tiến độ và phê duyệt các đề xuất nghiên cứu mới.

## Vai trò quản lý nghiên cứu
Vai trò quản lý nghiên cứu có những quyền hạn sau:
- Quản lý và phê duyệt đề tài nghiên cứu
- Theo dõi tiến độ các dự án nghiên cứu
- Quản lý nhà nghiên cứu (giảng viên và sinh viên)
- Xem báo cáo thống kê về hoạt động nghiên cứu
- Quản lý các ấn phẩm khoa học
- Nhận thông báo về các hoạt động nghiên cứu

## Hướng dẫn cài đặt
1. Để thiết lập hệ thống quản lý nghiên cứu, truy cập URL:
   ```
   http://localhost/NLNganh/setup_research_manager.php
   ```
   Trang này sẽ thực thi script SQL để tạo cơ sở dữ liệu cần thiết và tài khoản quản lý.

2. Tạo dữ liệu thông báo mẫu (tùy chọn):
   ```
   http://localhost/NLNganh/view/research/generate_test_notifications.php
   ```

## Hướng dẫn sử dụng
1. Đăng nhập vào hệ thống với tài khoản:
   - Tên đăng nhập: `research_admin`
   - Mật khẩu: `Research@123`

2. Sau khi đăng nhập, bạn sẽ được chuyển đến trang dashboard quản lý nghiên cứu.

3. Từ dashboard, bạn có thể truy cập các chức năng chính:
   - **Dashboard**: Tổng quan về hoạt động nghiên cứu
   - **Quản lý hồ sơ**: Quản lý thông tin cá nhân
   - **Quản lý đề tài**: Xem và quản lý các đề tài nghiên cứu
   - **Duyệt đề tài**: Phê duyệt các đề xuất đề tài mới
   - **Báo cáo nghiên cứu**: Xem báo cáo thống kê
   - **Quản lý nghiên cứu viên**: Quản lý giảng viên và sinh viên tham gia nghiên cứu
   - **Ấn phẩm khoa học**: Quản lý các công bố khoa học
   - **Thông báo**: Xem và quản lý các thông báo

## Quản lý thông báo
Hệ thống thông báo cho phép:
- Xem danh sách thông báo
- Đánh dấu thông báo đã đọc
- Đánh dấu tất cả thông báo đã đọc
- Xóa thông báo
- Lọc thông báo theo loại hoặc trạng thái

Thông báo sẽ được cập nhật tự động khi có các sự kiện mới trong hệ thống.

## Kiểm tra hệ thống
Để kiểm tra các chức năng của hệ thống quản lý nghiên cứu:
```
http://localhost/NLNganh/tests/test_research_manager.php
```

## Hỗ trợ
Nếu bạn gặp vấn đề khi sử dụng hệ thống, vui lòng liên hệ với quản trị viên.
