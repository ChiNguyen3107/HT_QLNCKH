# Hướng dẫn sửa lỗi Gia hạn đề tài

## Vấn đề
Sinh viên gặp lỗi khi cố gắng gia hạn đề tài tại địa chỉ:
`http://localhost/NLNganh/view/student/manage_extensions.php`

## Nguyên nhân
1. **Lỗi bind parameter**: Số lượng kiểu dữ liệu trong chuỗi bind_param không khớp với số tham số
2. **Thiếu bảng cơ sở dữ liệu**: Bảng `de_tai_gia_han` và `thong_bao` chưa được tạo
3. **Cấu trúc bảng không đồng bộ**: Thiếu các cột cần thiết trong bảng thông báo
4. **Output buffering issues**: Headers và JSON response bị conflict

## Các bước khắc phục

### Bước 1: Chạy script tạo bảng
1. Truy cập: `http://localhost/NLNganh/create_extension_table_if_not_exists.php`
2. Script sẽ tự động:
   - Kiểm tra và tạo bảng `de_tai_gia_han` nếu chưa tồn tại
   - Thêm cột `DT_TRE_TIENDO` và `DT_SO_LAN_GIA_HAN` vào bảng `de_tai_nghien_cuu`
   - Tạo bảng `thong_bao` nếu chưa tồn tại

### Bước 2: Kiểm tra debug cơ bản
1. Truy cập: `http://localhost/NLNganh/view/student/quick_debug.php`
2. Kiểm tra:
   - PHP version và cấu hình
   - File tồn tại
   - Session hoạt động
   - Database kết nối
   - Bảng extension tồn tại

### Bước 3: Debug chi tiết (nếu vẫn lỗi)
1. Truy cập: `http://localhost/NLNganh/view/student/debug_extension.php`
2. Kiểm tra:
   - Kết nối database
   - Cấu trúc bảng
   - Đề tài có thể gia hạn
   - Lịch sử gia hạn

### Bước 4: Test endpoint
1. Truy cập: `http://localhost/NLNganh/test_extension_endpoint.php`
2. Test:
   - Endpoint accessibility
   - POST request handling
   - JSON response format

### Bước 5: Test form đầy đủ
1. Truy cập: `http://localhost/NLNganh/view/student/test_extension_request.php`
2. Thử:
   - Form submission trực tiếp
   - AJAX request
   - Debug response

### Bước 6: Sử dụng chức năng gia hạn
1. Truy cập: `http://localhost/NLNganh/view/student/manage_extensions.php`
2. Mở Developer Tools (F12) để xem lỗi console
3. Chọn đề tài cần gia hạn
4. Điền thông tin và gửi yêu cầu

## Các lỗi đã sửa

### 1. Lỗi bind parameter trong `process_extension_request.php`
**Lỗi**: Số lượng kiểu dữ liệu (7) không khớp với số tham số (8)

**Trước:**
```php
$stmt->bind_param("sssssisr",  // 8 ký tự nhưng có 'r' không hợp lệ
    $project_id, $student_id, $extension_reason, $current_deadline,
    $new_deadline, $extension_months, $attachment_file, $student_id
);
```

**Sau:**
```php
$stmt->bind_param("ssssisss",  // 8 ký tự đúng: s-s-s-s-i-s-s-s
    $project_id, $student_id, $extension_reason, $current_deadline,
    $new_deadline, $extension_months, $attachment_file, $student_id
);
```

**Giải thích**: 
- 8 columns cần bind: DT_MADT(s), SV_MASV(s), GH_LYDOYEUCAU(s), GH_NGAYHETHAN_CU(s), GH_NGAYHETHAN_MOI(s), GH_SOTHANGGIAHAN(i), GH_FILE_DINKEM(s), GH_NGUOITAO(s)
- Chuỗi bind đúng: "ssssisss" (8 ký tự)

### 2. Lỗi cấu trúc thông báo
**Trước:**
```php
$notification_sql = "INSERT INTO thong_bao (
                       TB_NOIDUNG, TB_LOAI, DT_MADT, SV_MASV, 
                       NGUOI_NHAN, TB_LINK, TB_TRANGTHAI
                     ) VALUES (?, 'Yêu cầu gia hạn', ?, ?, 'RESEARCH_MANAGER', ?, 'Chưa đọc')";
```

**Sau:**
```php
$notification_sql = "INSERT INTO thong_bao (
                       TB_NOIDUNG, TB_LOAI, DT_MADT, SV_MASV, TB_NGAYTAO
                     ) VALUES (?, 'Yêu cầu gia hạn', ?, ?, NOW())";
```

## Cấu trúc bảng được tạo

### Bảng `de_tai_gia_han`
- `GH_ID`: ID gia hạn (AUTO_INCREMENT)
- `DT_MADT`: Mã đề tài
- `SV_MASV`: Mã sinh viên
- `GH_LYDOYEUCAU`: Lý do yêu cầu gia hạn
- `GH_NGAYHETHAN_CU`: Ngày hết hạn cũ
- `GH_NGAYHETHAN_MOI`: Ngày hết hạn mới
- `GH_SOTHANGGIAHAN`: Số tháng gia hạn
- `GH_TRANGTHAI`: Trạng thái (Chờ duyệt, Đã duyệt, Từ chối, Hủy)
- Các cột khác cho metadata

### Bảng `thong_bao`
- `TB_MA`: ID thông báo
- `TB_NOIDUNG`: Nội dung thông báo
- `TB_LOAI`: Loại thông báo
- `DT_MADT`: Mã đề tài liên quan
- `SV_MASV`: Mã sinh viên
- Các cột khác cho quản lý thông báo

## Kiểm tra sau khi sửa
1. Đăng nhập với tài khoản sinh viên
2. Truy cập trang gia hạn đề tài
3. Thực hiện yêu cầu gia hạn
4. Kiểm tra thông báo được tạo

## File liên quan

### File chính
- `view/student/manage_extensions.php`: Giao diện quản lý gia hạn
- `view/student/process_extension_request.php`: Xử lý yêu cầu gia hạn
- `view/student/get_extension_detail.php`: Xem chi tiết gia hạn
- `view/student/cancel_extension.php`: Hủy yêu cầu gia hạn

### File debug và test
- `view/student/quick_debug.php`: Debug cơ bản và kiểm tra hệ thống
- `view/student/debug_extension.php`: Debug chi tiết extension system
- `view/student/test_extension_request.php`: Test form gia hạn đầy đủ
- `view/student/debug_process_extension.php`: Debug step-by-step process
- `test_extension_endpoint.php`: Test endpoint accessibility
- `test_bind_fix.php`: Test bind parameters fix
- `create_extension_table_if_not_exists.php`: Script tạo bảng

### File cấu hình
- `include/session.php`: Quản lý session và authentication
- `include/connect.php`: Kết nối database
- `create_extension_system.sql`: SQL tạo bảng extension system

## Lưu ý
- Đảm bảo sinh viên đã đăng ký và tham gia đề tài trước khi gia hạn
- Chỉ có thể gia hạn đề tài đang ở trạng thái "Đang thực hiện" hoặc "Chờ duyệt"
- Tối đa 3 lần gia hạn cho mỗi đề tài
- Tối đa 6 tháng cho mỗi lần gia hạn
