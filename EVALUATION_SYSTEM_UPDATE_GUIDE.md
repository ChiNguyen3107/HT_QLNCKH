# HƯỚNG DẪN CẬP NHẬT HỆ THỐNG ĐÁNH GIÁ CHI TIẾT

## Tổng Quan
Hệ thống đánh giá chi tiết cho phép:
1. Cập nhật thông tin file thuyết minh ✅
2. Cập nhật hợp đồng ✅  
3. Cập nhật quyết định nghiệm thu ✅
4. Cập nhật biên bản với thông tin thành viên hội đồng ✅
5. Tab đánh giá hiển thị danh sách thành viên, cho phép nhập điểm theo tiêu chí và upload file đánh giá ✅
6. Tự động hoàn thành đề tài khi đủ điều kiện ✅

## BƯỚC 1: Cập nhật cơ sở dữ liệu

### 1.1. Chạy file SQL cập nhật
```sql
-- Chạy file: update_evaluation_criteria.sql
-- File này sẽ:
-- - Thêm các cột cần thiết vào bảng tieu_chi
-- - Tạo dữ liệu mẫu cho tiêu chí đánh giá
-- - Tạo bảng chi_tiet_diem_danh_gia
-- - Cập nhật bảng thanh_vien_hoi_dong
-- - Tạo view và stored procedure hỗ trợ
```

### 1.2. Kiểm tra cấu trúc bảng sau khi cập nhật
```sql
-- Kiểm tra bảng tieu_chi
DESCRIBE tieu_chi;

-- Kiểm tra dữ liệu tiêu chí
SELECT * FROM tieu_chi ORDER BY TC_THUTU;

-- Kiểm tra bảng chi_tiet_diem_danh_gia
DESCRIBE chi_tiet_diem_danh_gia;

-- Kiểm tra bảng thanh_vien_hoi_dong
DESCRIBE thanh_vien_hoi_dong;
```

## BƯỚC 2: Cập nhật thư mục uploads

### 2.1. Tạo thư mục cho file đánh giá thành viên
```bash
mkdir -p uploads/member_evaluation_files
chmod 755 uploads/member_evaluation_files
```

### 2.2. Cập nhật .htaccess nếu cần
```apache
# Trong uploads/.htaccess
<Files "*.php">
    Deny from all
</Files>

# Cho phép truy cập file đánh giá
<FilesMatch "\.(pdf|doc|docx|txt|xls|xlsx)$">
    Allow from all
</FilesMatch>
```

## BƯỚC 3: Test các API mới

### 3.1. Test API tiêu chí đánh giá
```javascript
// Test trong browser console
fetch('/NLNganh/api/get_evaluation_criteria.php')
    .then(response => response.json())
    .then(data => console.log(data));
```

### 3.2. Test API điểm chi tiết thành viên
```javascript
// Test với member_id và project_id thực tế
fetch('/NLNganh/api/get_member_detailed_scores.php?member_id=GV001&project_id=DT001')
    .then(response => response.json())
    .then(data => console.log(data));
```

### 3.3. Test API file thành viên
```javascript
// Test với member_id và project_id thực tế
fetch('/NLNganh/api/get_member_files_new.php?member_id=GV001&project_id=DT001')
    .then(response => response.json())
    .then(data => console.log(data));
```

## BƯỚC 4: Test chức năng đánh giá

### 4.1. Kiểm tra hiển thị thành viên hội đồng
- Truy cập trang chi tiết đề tài có biên bản nghiệm thu
- Vào tab "Đánh giá"
- Kiểm tra danh sách thành viên hội đồng hiển thị

### 4.2. Test đánh giá chi tiết
- Nhấn nút "Đánh giá" của một thành viên
- Modal đánh giá chi tiết sẽ mở
- Nhập điểm cho từng tiêu chí
- Nhập nhận xét tổng quan
- Lưu đánh giá

### 4.3. Test upload file đánh giá
- Nhấn nút "Upload file" của một thành viên
- Chọn file và nhập mô tả
- Upload file
- Kiểm tra file hiển thị trong danh sách

### 4.4. Test hoàn thành tự động
- Đánh giá tất cả thành viên hội đồng
- Upload file đánh giá cho tất cả thành viên
- Hệ thống sẽ tự động chuyển đề tài sang "Đã hoàn thành"

## BƯỚC 5: Xử lý lỗi có thể gặp

### 5.1. Lỗi "Bảng không tồn tại"
```sql
-- Kiểm tra bảng tieu_chi
SHOW TABLES LIKE 'tieu_chi';

-- Nếu không có, tạo bảng:
CREATE TABLE `tieu_chi` (
  `TC_MATC` char(5) NOT NULL PRIMARY KEY,
  `TC_TEN` VARCHAR(255) NULL,
  `TC_NDDANHGIA` text NOT NULL,
  `TC_MOTA` TEXT NULL,
  `TC_DIEMTOIDA` decimal(3,0) NOT NULL,
  `TC_TRONGSO` DECIMAL(5,2) DEFAULT 20.00,
  `TC_THUTU` INT DEFAULT 1,
  `TC_TRANGTHAI` ENUM('Hoạt động', 'Tạm dừng') DEFAULT 'Hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 5.2. Lỗi "Không thể tải tiêu chí"
```php
// Kiểm tra kết nối database trong api/get_evaluation_criteria.php
// Kiểm tra quyền truy cập file
// Kiểm tra log lỗi PHP
```

### 5.3. Lỗi upload file
```bash
# Kiểm tra quyền thư mục
ls -la uploads/
chmod 755 uploads/member_evaluation_files/

# Kiểm tra kích thước file upload trong php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

## BƯỚC 6: Tối ưu hóa

### 6.1. Index database
```sql
-- Thêm index cho hiệu suất
ALTER TABLE chi_tiet_diem_danh_gia ADD INDEX idx_member_eval (GV_MAGV, QD_SO);
ALTER TABLE file_dinh_kem ADD INDEX idx_member_files (GV_MAGV, FDG_LOAI);
```

### 6.2. Caching
```php
// Thêm cache cho API tiêu chí đánh giá
// Cache danh sách thành viên hội đồng
```

## BƯỚC 7: Backup dữ liệu

### 7.1. Backup trước khi cập nhật
```bash
mysqldump -u username -p database_name > backup_before_evaluation_update.sql
```

### 7.2. Backup sau khi cập nhật thành công
```bash
mysqldump -u username -p database_name > backup_after_evaluation_update.sql
```

## KẾT LUẬN

Sau khi hoàn thành các bước trên, hệ thống sẽ có đầy đủ tính năng:

✅ **Cập nhật thông tin đề tài** - Đã có sẵn
✅ **Quản lý hợp đồng** - Đã có sẵn  
✅ **Quản lý quyết định** - Đã có sẵn
✅ **Quản lý biên bản và thành viên hội đồng** - Đã có sẵn
✅ **Đánh giá chi tiết theo tiêu chí** - Mới thêm
✅ **Upload file đánh giá thành viên** - Mới thêm
✅ **Tự động hoàn thành đề tài** - Đã có logic

**Lưu ý quan trọng:**
- Hệ thống sẽ tự động hoàn thành đề tài khi có đủ:
  1. Biên bản nghiệm thu với xếp loại
  2. Tất cả thành viên hội đồng đã được đánh giá
  3. Có file đánh giá cho các thành viên
  4. Các thông tin cần thiết khác đã đầy đủ

**Hỗ trợ kỹ thuật:**
- Kiểm tra log lỗi PHP: `/var/log/apache2/error.log`
- Kiểm tra log MySQL: `SHOW ENGINE INNODB STATUS;`
- Debug JavaScript: Mở Developer Tools trong browser
