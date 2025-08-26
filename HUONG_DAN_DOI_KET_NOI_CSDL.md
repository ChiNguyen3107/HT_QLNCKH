# HƯỚNG DẪN THAY ĐỔI KẾT NỐI CƠ SỞ DỮ LIỆU

## Tổng quan
Hướng dẫn này sẽ giúp bạn thay đổi kết nối cơ sở dữ liệu từ `ql_nckh` sang `ql_nckh_test` một cách an toàn và có thể rollback.

## Các file đã được tạo

### 1. Script thay đổi kết nối
- **File**: `change_database_connection.php`
- **Chức năng**: Tự động thay đổi tất cả các file có kết nối đến cơ sở dữ liệu

### 2. Script rollback
- **File**: `rollback_database_connection.php`
- **Chức năng**: Quay lại kết nối cơ sở dữ liệu cũ nếu cần

### 3. File SQL mới
- **File**: `ql_nckh_test.sql`
- **Chức năng**: Cấu trúc cơ sở dữ liệu cho môi trường test

### 4. Script kiểm tra
- **File**: `test_database_connection.php`
- **Chức năng**: Kiểm tra kết nối và cấu trúc cơ sở dữ liệu

## Các bước thực hiện

### Bước 1: Sao lưu dữ liệu hiện tại
```bash
# Sao lưu cơ sở dữ liệu hiện tại
mysqldump -u root -p ql_nckh > backup_ql_nckh_$(date +%Y%m%d_%H%M%S).sql
```

### Bước 2: Tạo cơ sở dữ liệu test
```sql
-- Trong MySQL hoặc phpMyAdmin
CREATE DATABASE ql_nckh_test CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### Bước 3: Import cấu trúc cơ sở dữ liệu test
```bash
# Import file SQL mới
mysql -u root -p ql_nckh_test < ql_nckh_test.sql
```

### Bước 4: Chạy script thay đổi kết nối
```bash
# Chạy script thay đổi kết nối
php change_database_connection.php
```

### Bước 5: Kiểm tra kết nối
```bash
# Kiểm tra kết nối mới
php test_database_connection.php
```

## Các file sẽ được thay đổi

Script sẽ tự động cập nhật các file sau:

### File cấu hình chính
- `include/config.php` - File cấu hình tập trung
- `include/connect.php` - File kết nối cơ sở dữ liệu

### File test và debug
- `check_contract_structure.php`
- `check_decision_structure.php`
- `debug_bien_ban_structure.php`
- `debug_bien_ban_creation.php`
- `test_contract_codes.php`
- `test_length_constraint.php`
- `update_decision_field.php`
- `update_decision_field_safe.php`
- `update_contract_field.php`

## Rollback (nếu cần)

Nếu muốn quay lại cơ sở dữ liệu cũ:

```bash
# Chạy script rollback
php rollback_database_connection.php
```

## Kiểm tra sau khi thay đổi

### 1. Kiểm tra kết nối
```bash
php test_database_connection.php
```

### 2. Kiểm tra các chức năng chính
- Đăng nhập hệ thống
- Xem danh sách đề tài
- Thêm/sửa/xóa dữ liệu
- Upload file

### 3. Kiểm tra log lỗi
- Kiểm tra file log của web server
- Kiểm tra console browser

## Lưu ý quan trọng

### 1. Backup dữ liệu
- Luôn sao lưu dữ liệu trước khi thay đổi
- Kiểm tra backup có thể restore được

### 2. Môi trường test
- Cơ sở dữ liệu test nên có dữ liệu mẫu
- Không sử dụng dữ liệu production trong test

### 3. Quyền truy cập
- Đảm bảo user MySQL có đủ quyền
- Kiểm tra quyền SELECT, INSERT, UPDATE, DELETE

### 4. Cấu hình web server
- Kiểm tra cấu hình PHP
- Kiểm tra extension mysqli

## Xử lý lỗi thường gặp

### Lỗi kết nối
```
Kết nối thất bại: Access denied for user 'root'@'localhost'
```
**Giải pháp**: Kiểm tra mật khẩu MySQL trong file config

### Lỗi database không tồn tại
```
Unknown database 'ql_nckh_test'
```
**Giải pháp**: Tạo cơ sở dữ liệu trước khi chạy script

### Lỗi quyền truy cập
```
Access denied for table 'table_name'
```
**Giải pháp**: Cấp quyền cho user MySQL

## Liên hệ hỗ trợ

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra log lỗi
2. Chạy script test_database_connection.php
3. Cung cấp thông tin lỗi chi tiết

---

**Lưu ý**: Đây là hướng dẫn cho môi trường development. Trong production, cần thêm các biện pháp bảo mật và backup.

