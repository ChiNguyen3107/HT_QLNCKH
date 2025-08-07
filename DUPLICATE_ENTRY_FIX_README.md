# Khắc phục lỗi "Duplicate entry 'QD004' for key 'PRIMARY'"

## 🐛 Vấn đề

Khi đăng ký đề tài mới, hệ thống báo lỗi:
```
Lỗi: Lỗi khi thêm quyết định tạm thời: Duplicate entry 'QD004' for key 'PRIMARY'
```

**Nguyên nhân**: Hệ thống cố gắng tạo quyết định nghiệm thu tạm thời cho đề tài chưa được duyệt, dẫn đến xung đột ID.

## ✅ Giải pháp đã thực hiện

### 1. **Sửa cấu trúc database**
- Sửa cột `QD_SO` trong bảng `de_tai_nghien_cuu` từ `NOT NULL` thành `NULL`
- Cho phép đề tài mới có `QD_SO = NULL` khi chưa được nghiệm thu

### 2. **Sửa logic đăng ký đề tài**
- **Xóa bỏ**: Logic tạo quyết định tạm thời không cần thiết
- **Xóa bỏ**: Hàm `generateDecisionID()` gây xung đột
- **Cập nhật**: Câu INSERT để có `QD_SO = NULL` cho đề tài mới

### 3. **Files đã được sửa đổi**

#### `register_project_process.php`
```php
// TRƯỚC (có vấn đề)
if (!$qd_so_nullable) {
    $decision_id = generateDecisionID($conn);
    // Tạo quyết định tạm thời...
}

// SAU (đã sửa)
$decision_id = null; // Luôn để NULL cho đề tài mới
```

#### Database Structure
```sql
-- TRƯỚC
QD_SO char(5) NOT NULL

-- SAU  
QD_SO char(5) NULL
```

### 4. **Logic mới**

#### Quy trình đăng ký đề tài:
1. ✅ Tạo đề tài với `QD_SO = NULL`
2. ✅ Trạng thái = "Chờ duyệt"
3. ✅ Không tạo quyết định nghiệm thu

#### Quy trình nghiệm thu (sau này):
1. Admin/Giảng viên duyệt đề tài
2. Tạo quyết định nghiệm thu khi cần
3. Cập nhật `QD_SO` cho đề tài

## 🔧 Files được tạo

- `fix_database_structure.php` - Script sửa cấu trúc database
- `test_project_registration.php` - Test đăng ký đề tài
- `check_database_details.php` - Kiểm tra cấu trúc chi tiết

## 📊 Kết quả

### ✅ Trước khi sửa:
- ❌ Lỗi "Duplicate entry" khi đăng ký đề tài
- ❌ Tạo quyết định tạm thời không cần thiết
- ❌ Race condition với ID generation

### ✅ Sau khi sửa:
- ✅ Đăng ký đề tài thành công
- ✅ Không tạo quyết định cho đề tài chưa duyệt
- ✅ Logic rõ ràng và hợp lý

## 🎯 Testing

### Test đăng ký đề tài:
```bash
php test_project_registration.php
```

### Kết quả mong đợi:
```
✅ Cột QD_SO cho phép NULL
✅ Có thể thêm đề tài mới với QD_SO = NULL
✅ Hệ thống đã được sửa để không tạo quyết định tạm thời
```

## 📝 Lưu ý quan trọng

### Workflow mới:
1. **Đăng ký đề tài**: `QD_SO = NULL`, trạng thái = "Chờ duyệt"
2. **Duyệt đề tài**: Admin thay đổi trạng thái thành "Đang thực hiện"
3. **Nghiệm thu**: Tạo quyết định nghiệm thu, cập nhật `QD_SO`

### Database Schema:
- `QD_SO` NULL = Đề tài chưa nghiệm thu
- `QD_SO` có giá trị = Đề tài đã có quyết định nghiệm thu

## 🚀 Triển khai

1. ✅ Backup database trước khi sửa
2. ✅ Chạy `fix_database_structure.php`
3. ✅ Test bằng `test_project_registration.php`
4. ✅ Deploy code mới

**Vấn đề đã được khắc phục hoàn toàn!** 🎉
