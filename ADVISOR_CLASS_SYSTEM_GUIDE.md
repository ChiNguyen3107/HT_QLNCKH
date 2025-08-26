# HƯỚNG DẪN SỬ DỤNG TÍNH NĂNG "QUẢN LÝ LỚP HỌC" CHO CỐ VẤN HỌC TẬP

## 📋 Tổng quan

Tính năng "Quản lý lớp học" cho phép giảng viên làm cố vấn học tập (CVHT) theo dõi và quản lý các lớp được giao phụ trách, bao gồm:

- Xem danh sách lớp đang cố vấn
- Thống kê sinh viên và đề tài nghiên cứu
- Theo dõi tiến độ tham gia NCKH của sinh viên
- Xuất báo cáo chi tiết

## 🚀 Cài đặt và khởi tạo

### 1. Chạy Script Migration Database

```sql
-- Chạy file SQL để tạo cấu trúc database
source create_advisor_class_system.sql;
```

Hoặc copy nội dung từ file `create_advisor_class_system.sql` và chạy trong phpMyAdmin.

### 2. Cấu trúc Database được tạo

- **Bảng `advisor_class`**: Lưu thông tin gán CVHT cho lớp
- **View `v_student_project_summary`**: Thống kê sinh viên và đề tài
- **View `v_class_overview`**: Tổng quan lớp học
- **Bảng `advisor_class_audit_log`**: Log audit cho thao tác gán/huỷ CVHT

## 👨‍🏫 Hướng dẫn sử dụng cho Giảng viên

### 1. Truy cập tính năng

1. Đăng nhập vào hệ thống với tài khoản giảng viên
2. Trong sidebar, click vào **"Quản lý lớp học"**
3. Chọn **"Danh sách lớp"** để xem các lớp đang cố vấn

### 2. Xem danh sách lớp

- **Thống kê tổng quan**: Hiển thị số liệu tổng hợp
- **Bộ lọc**: Tìm kiếm theo tên lớp, khoa, niên khóa
- **Card lớp**: Mỗi lớp hiển thị:
  - Tên lớp và khoa
  - Số lượng sinh viên
  - Tỷ lệ tham gia NCKH
  - Trạng thái đề tài

### 3. Xem chi tiết lớp

1. Click **"Xem chi tiết"** trên card lớp
2. Trang chi tiết hiển thị:
   - **Thông tin lớp**: Tên, khoa, niên khóa, CVHT
   - **Thống kê theo trạng thái**: Chưa tham gia, Đang tham gia, Đã hoàn thành, Bị từ chối
   - **Danh sách sinh viên**: Chi tiết từng sinh viên và đề tài

### 4. Bộ lọc và tìm kiếm

- **Tìm kiếm**: Theo MSSV, tên sinh viên, tên đề tài
- **Lọc trạng thái**: Chưa tham gia, Đang tham gia, Đã hoàn thành
- **Sắp xếp**: Theo tên, MSSV, trạng thái, tiến độ

### 5. Xuất báo cáo

- Click **"Xuất Excel"** để tải file báo cáo
- File bao gồm thông tin chi tiết sinh viên và thống kê

## 👨‍💼 Hướng dẫn sử dụng cho Admin

### 1. Quản lý gán CVHT

1. Đăng nhập với tài khoản admin
2. Truy cập **"Quản lý Cố vấn học tập"**
3. Xem danh sách CVHT hiện tại

### 2. Gán CVHT mới

1. Click **"Gán CVHT mới"**
2. Chọn giảng viên và lớp
3. Nhập ngày bắt đầu và ghi chú
4. Click **"Gán CVHT"**

**Lưu ý**: Nếu lớp đã có CVHT, hệ thống sẽ tự động huỷ hiệu lực CVHT cũ.

### 3. Huỷ gán CVHT

1. Tìm CVHT cần huỷ trong danh sách
2. Click **"Huỷ"** trên card tương ứng
3. Xác nhận huỷ gán

## 📊 Các trạng thái đề tài

### Phân loại trạng thái

1. **Chưa tham gia**: Sinh viên chưa có đề tài nào
2. **Đang tham gia**: Đề tài đang thực hiện
3. **Đã hoàn thành**: Đề tài đã nghiệm thu/xong
4. **Bị từ chối/Tạm dừng**: Đề tài bị hủy hoặc tạm dừng

### Tiến độ tự động

- **0%**: Chưa tham gia
- **10%**: Chờ duyệt
- **25%**: Đang xử lý
- **50%**: Đang thực hiện
- **100%**: Đã hoàn thành

## 🔧 Cấu hình và tùy chỉnh

### 1. Thêm dữ liệu mẫu

```sql
-- Gán CVHT mẫu
INSERT INTO advisor_class (GV_MAGV, LOP_MA, AC_NGAYBATDAU, AC_COHIEULUC, AC_GHICHU) VALUES
('GV001', 'CNTT01', '2024-09-01', 1, 'Cố vấn lớp CNTT01 khóa 2024'),
('GV002', 'CNTT02', '2024-09-01', 1, 'Cố vấn lớp CNTT02 khóa 2024');
```

### 2. Tùy chỉnh giao diện

- Chỉnh sửa CSS trong file `class_management.php` và `class_detail.php`
- Thay đổi màu sắc badge trạng thái
- Điều chỉnh layout responsive

### 3. Thêm tính năng mới

- Tích hợp thông báo cho sinh viên
- Thêm biểu đồ thống kê nâng cao
- Tạo báo cáo PDF

## 🛠️ Troubleshooting

### Lỗi thường gặp

1. **"Chưa có lớp nào được gán"**
   - Kiểm tra quyền giảng viên
   - Admin cần gán CVHT cho lớp

2. **"Không có quyền truy cập"**
   - Kiểm tra session và role
   - Đảm bảo đăng nhập đúng tài khoản

3. **Lỗi database**
   - Kiểm tra kết nối database
   - Chạy lại script migration
   - Kiểm tra quyền truy cập database

### Kiểm tra hệ thống

```php
// Kiểm tra view có hoạt động không
SELECT * FROM v_class_overview LIMIT 5;

// Kiểm tra quyền CVHT
SELECT COUNT(*) FROM advisor_class WHERE GV_MAGV = 'GV001' AND AC_COHIEULUC = 1;
```

## 📈 Hiệu năng và tối ưu

### Index Database

```sql
-- Thêm index cho truy vấn nhanh
CREATE INDEX idx_advisor_class_gv_lop ON advisor_class(GV_MAGV, LOP_MA);
CREATE INDEX idx_student_project_lop ON v_student_project_summary(LOP_MA);
```

### Cache

- Sử dụng Redis/Memcached cho thống kê
- Cache view database cho hiệu suất tốt hơn

## 🔒 Bảo mật

### Phân quyền

- Giảng viên chỉ xem được lớp mình cố vấn
- Admin có quyền gán/huỷ CVHT
- Audit log cho tất cả thao tác

### Validation

- Kiểm tra input data
- Sanitize output HTML
- Prevent SQL injection

## 📝 Changelog

### Version 1.0.0 (2025-01-XX)
- ✅ Tính năng cơ bản quản lý lớp học
- ✅ Giao diện responsive
- ✅ Xuất báo cáo CSV/Excel
- ✅ Phân quyền và bảo mật
- ✅ Audit log

### Version 1.1.0 (Planned)
- 🔄 Biểu đồ thống kê nâng cao
- 🔄 Thông báo tự động
- 🔄 API integration
- 🔄 Mobile app support

## 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng:

1. Kiểm tra log lỗi trong `/logs/`
2. Xem troubleshooting section
3. Liên hệ admin hệ thống
4. Tạo issue trên Git repository

---

**Ngày cập nhật**: 2025-01-XX  
**Phiên bản**: 1.0.0  
**Tác giả**: Development Team
