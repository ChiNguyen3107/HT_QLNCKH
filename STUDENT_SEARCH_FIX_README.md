# Hướng dẫn khắc phục lỗi tìm kiếm thông tin sinh viên

## 🐛 Vấn đề đã được khắc phục

Lỗi khi tìm kiếm thông tin sinh viên bằng MSSV đã được khắc phục với các thay đổi sau:

### 1. **Tạo file API mới**: `get_student_info_test.php`
- ✅ Không yêu cầu đăng nhập (dành cho trang đăng ký)
- ✅ Cải thiện xử lý lỗi
- ✅ Truy vấn đúng cấu trúc database

### 2. **Cập nhật JavaScript**: `register_project_form.php`
- ✅ Thêm timeout 10 giây
- ✅ Hiển thị lỗi chi tiết hơn
- ✅ Debug console log
- ✅ Thông báo trạng thái rõ ràng

### 3. **Khắc phục cấu trúc database**
- ✅ Sử dụng đúng tên cột: `SV_HOSV`, `SV_TENSV` thay vì `SV_HO`, `SV_TEN`
- ✅ Join với bảng `lop` để lấy thông tin lớp và khóa
- ✅ Xử lý giá trị null an toàn

## 🔧 Các file đã được tạo/sửa đổi

1. **`get_student_info.php`** - API chính (yêu cầu đăng nhập)
2. **`get_student_info_test.php`** - API test (không yêu cầu đăng nhập)
3. **`register_project_form.php`** - Cải thiện JavaScript xử lý lỗi
4. **`test_student_api.html`** - Trang test API
5. **`debug_student_api.php`** - Script debug hệ thống

## 🧪 Cách test hệ thống

### Test 1: Sử dụng trang test
```
Mở: http://localhost/NLNganh/test_student_api.html
Nhập MSSV: B2110051
Click "Tìm kiếm"
```

### Test 2: Trực tiếp API
```
URL: http://localhost/NLNganh/get_student_info_test.php?student_id=B2110051
```

### Test 3: Trên trang đăng ký
```
1. Vào trang đăng ký đề tài
2. Thêm thành viên mới
3. Nhập MSSV: B2110051
4. Click nút tìm kiếm (🔍)
```

## 📊 Kết quả mong đợi

Khi tìm kiếm MSSV `B2110051`, hệ thống sẽ trả về:
```json
{
  "success": true,
  "message": "Tìm thấy thông tin sinh viên",
  "data": {
    "SV_MASV": "B2110051",
    "fullname": "Doan Chi Nguyen",
    "SV_NGAYSINH": "2003-07-31",
    "SV_SDT": "0835886837",
    "SV_EMAIL": "nguyenb2110051@student.ctu.edu.vn",
    "LOP_TEN": "Lớp Kỹ thuật phần mềm",
    "KHOA": "Khóa 47"
  }
}
```

## 🚀 Cách sử dụng

1. **Trong form đăng ký đề tài:**
   - Nhập MSSV (8 ký tự) vào ô "MSSV"
   - Click nút 🔍 "Tìm kiếm"
   - Thông tin sẽ tự động điền vào các trường

2. **Nếu gặp lỗi:**
   - Kiểm tra MSSV có đúng 8 ký tự không
   - Đảm bảo sinh viên tồn tại trong database
   - Kiểm tra kết nối mạng
   - Xem console log để debug (F12)

## 🔍 Troubleshooting

### Lỗi "Chưa đăng nhập"
- **Nguyên nhân**: Session hết hạn
- **Giải pháp**: Đăng nhập lại hoặc sử dụng `get_student_info_test.php`

### Lỗi "Không tìm thấy sinh viên"
- **Nguyên nhân**: MSSV không tồn tại hoặc sai
- **Giải pháp**: Kiểm tra MSSV trong database

### Lỗi "Timeout"
- **Nguyên nhân**: Server chậm hoặc mất kết nối
- **Giải pháp**: Thử lại sau vài giây

### Lỗi "500 Internal Server Error"
- **Nguyên nhân**: Lỗi PHP hoặc database
- **Giải pháp**: Kiểm tra log PHP, đảm bảo database chạy

## 📝 Ghi chú cho developer

- File `get_student_info_test.php` chỉ dùng cho development/testing
- Trong production, nên sử dụng `get_student_info.php` với authentication
- Database schema: `sinh_vien` JOIN `lop` để lấy thông tin đầy đủ
- Tất cả input đều được validate và sanitize
- Sử dụng prepared statements để tránh SQL injection

## ✅ Checklist sau khi deploy

- [ ] Test API qua browser
- [ ] Test trên form đăng ký thật
- [ ] Kiểm tra log PHP không có lỗi
- [ ] Đảm bảo database connection pool ổn định
- [ ] Test với nhiều MSSV khác nhau
