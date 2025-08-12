# Hệ thống Quản lý Nghiên cứu Khoa học

## Mô tả
Hệ thống quản lý nghiên cứu khoa học cho sinh viên và giảng viên, bao gồm các chức năng:
- Quản lý đề tài nghiên cứu
- Quản lý sinh viên tham gia nghiên cứu
- Báo cáo và thống kê
- Đánh giá và nghiệm thu đề tài

## Tính năng chính

### 1. Quản lý Sinh viên
- Danh sách sinh viên theo lớp
- Lọc theo khoa, khóa học, lớp
- Lọc theo trạng thái nghiên cứu (Chưa tham gia, Đang tham gia, Đã hoàn thành)
- Xuất danh sách ra Excel

### 2. Quản lý Đề tài
- Đăng ký đề tài nghiên cứu
- Theo dõi tiến độ
- Quản lý thành viên tham gia

### 3. Báo cáo và Thống kê
- Thống kê theo khoa
- Thống kê theo trạng thái đề tài
- Biểu đồ trực quan

## Cài đặt

### Yêu cầu hệ thống
- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 trở lên
- MySQL 5.7 trở lên

### Cài đặt
1. Clone repository này về máy
2. Copy toàn bộ file vào thư mục `htdocs` của XAMPP
3. Import file `ql_nckh.sql` vào MySQL
4. Cấu hình kết nối database trong `include/connect.php`
5. Truy cập `http://localhost/NLNganh`

## Cấu trúc thư mục

```
NLNganh/
├── api/                    # API endpoints
│   ├── get_student_list.php
│   ├── get_department_classes.php
│   ├── get_faculties.php
│   └── export_student_list.php
├── view/                   # Giao diện người dùng
│   ├── research/
│   └── admin/
├── include/                # File cấu hình
│   └── connect.php
├── uploads/                # File upload
└── ql_nckh.sql            # Database schema
```

## API Endpoints

### 1. Lấy danh sách sinh viên
```
GET /api/get_student_list.php
Parameters:
- department: Mã khoa
- school_year: Khóa học
- class: Mã lớp
- research_status: Trạng thái nghiên cứu (none/active/completed)
- page: Trang hiện tại
- limit: Số lượng mỗi trang
```

### 2. Lấy danh sách lớp theo khoa
```
GET /api/get_department_classes.php
Parameters:
- dept_id: Mã khoa
- year: Khóa học
```

### 3. Xuất danh sách Excel
```
GET /api/export_student_list.php
Parameters: Tương tự get_student_list.php
```

## Cập nhật gần đây

### Vấn đề đã sửa:
1. **Dropdown lớp không populate**: Đã sửa logic lấy khóa học từ bảng `lop` thay vì `khoa_hoc`
2. **Filter trạng thái nghiên cứu**: Đã sửa logic SQL để lọc chính xác sinh viên chưa tham gia nghiên cứu
3. **UX cải thiện**: Thêm tự động lọc khi thay đổi bộ lọc

### Tính năng mới:
- API lấy danh sách khoa động
- API lấy khóa học từ bảng lớp
- Tự động cập nhật danh sách khi thay đổi bộ lọc
- Hiển thị trạng thái nghiên cứu với badge màu sắc

## Đóng góp
Nếu bạn muốn đóng góp vào dự án, vui lòng:
1. Fork repository
2. Tạo branch mới cho tính năng
3. Commit thay đổi
4. Tạo Pull Request

## License
MIT License

## Liên hệ
Nếu có vấn đề hoặc câu hỏi, vui lòng tạo issue trên GitHub.
