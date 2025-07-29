# 🔬 Hệ thống Quản lý Nghiên cứu Khoa học (NCKH)

Hệ thống quản lý nghiên cứu khoa học toàn diện dành cho các trường đại học, hỗ trợ quản lý đề tài, tiến độ nghiên cứu, và báo cáo khoa học.

## ✨ Tính năng chính

### 👨‍🎓 Dành cho Sinh viên
- 📝 Đăng ký và quản lý đề tài nghiên cứu
- 📊 Theo dõi tiến độ thực hiện đề tài
- 📁 Upload và quản lý tài liệu
- 📈 Xem báo cáo tiến độ
- 👤 Quản lý thông tin cá nhân

### 👨‍🏫 Dành cho Giảng viên
- 🔍 Duyệt và phê duyệt đề tài sinh viên
- 📋 Quản lý danh sách đề tài hướng dẫn
- 📊 Theo dõi tiến độ của sinh viên
- 📝 Đánh giá và chấm điểm
- 📈 Xem báo cáo tổng quan

### 👨‍💼 Dành cho Admin
- 🏢 Quản lý khoa, bộ môn
- 👥 Quản lý người dùng (sinh viên, giảng viên)
- 📊 Quản lý toàn bộ đề tài nghiên cứu
- 📈 Thống kê và báo cáo tổng quan
- ⚙️ Cấu hình hệ thống

### 🔬 Dành cho Nhà nghiên cứu
- 📚 Quản lý dự án nghiên cứu
- 📝 Quản lý xuất bản và công trình khoa học
- 👥 Quản lý nhóm nghiên cứu
- 📊 Dashboard thống kê nghiên cứu

## 🚀 Công nghệ sử dụng

- **Backend:** PHP 7.4+
- **Database:** MySQL 8.0+
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 4
- **Libraries:** 
  - jQuery
  - Font Awesome
  - Chart.js
  - SB Admin 2

## 📋 Yêu cầu hệ thống

- PHP 7.4 trở lên
- MySQL 8.0 trở lên
- Apache/Nginx web server
- 512MB RAM (tối thiểu)
- 1GB dung lượng ổ cứng

## ⚡ Cài đặt nhanh

### 1. Clone dự án
```bash
git clone https://github.com/NguyenDC3107/NLNganh.git
cd NLNganh
```

### 2. Cấu hình database
- Import file `ql_nckh.sql` vào MySQL
- Chỉnh sửa file `include/config.php` với thông tin database của bạn

### 3. Cấu hình web server
- Copy dự án vào thư mục web root (htdocs, www, etc.)
- Đảm bảo Apache/Nginx có quyền đọc/ghi với thư mục `uploads/`

### 4. Truy cập hệ thống
- Mở trình duyệt và truy cập: `http://localhost/NLNganh`
- Đăng nhập với tài khoản admin mặc định

## 🗂️ Cấu trúc thư mục

```
NLNganh/
├── 📁 api/                 # API endpoints
├── 📁 assets/             # Tài nguyên tĩnh
│   ├── 📁 css/           # Stylesheets
│   ├── 📁 js/            # JavaScript files
│   ├── 📁 images/        # Hình ảnh
│   └── 📁 vendor/        # Thư viện bên thứ 3
├── 📁 include/            # File include chung
│   ├── 📁 models/        # Data models
│   ├── config.php        # Cấu hình database
│   ├── functions.php     # Hàm tiện ích
│   └── session.php       # Quản lý session
├── 📁 templates/          # Template layouts
├── 📁 uploads/            # File uploads
│   ├── 📁 avatars/       # Avatar người dùng
│   ├── 📁 contract_files/ # File hợp đồng
│   ├── 📁 progress_files/ # File tiến độ
│   └── 📁 reports/       # File báo cáo
├── 📁 view/               # Giao diện người dùng
│   ├── 📁 admin/         # Giao diện admin
│   ├── 📁 student/       # Giao diện sinh viên
│   ├── 📁 teacher/       # Giao diện giảng viên
│   └── 📁 research/      # Giao diện nghiên cứu
├── index.php              # Trang chủ
├── login.php              # Trang đăng nhập
└── README.md              # Tài liệu này
```

## 🔧 Cấu hình

### Database
Chỉnh sửa file `include/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'ql_nckh');
```

### Upload Files
Cấu hình quyền ghi cho thư mục uploads:
```bash
chmod 755 uploads/
chmod 755 uploads/avatars/
chmod 755 uploads/contract_files/
chmod 755 uploads/progress_files/
chmod 755 uploads/reports/
```

## 👥 Tài khoản mặc định

Sau khi import database, bạn có thể sử dụng các tài khoản mặc định:

- **Admin:** admin / admin123
- **Giảng viên:** gv001 / password123
- **Sinh viên:** sv001 / password123

> ⚠️ **Lưu ý:** Thay đổi mật khẩu ngay sau lần đăng nhập đầu tiên!

## 🔒 Bảo mật

- Mã hóa mật khẩu với bcrypt
- Validation và sanitization input
- Protection CSRF
- Session management an toàn
- File upload security

## 📸 Screenshots

[Thêm screenshots của các trang chính ở đây]

## 🤝 Đóng góp

1. Fork dự án
2. Tạo feature branch (`git checkout -b feature/TinhNangMoi`)
3. Commit thay đổi (`git commit -am 'Thêm tính năng mới'`)
4. Push to branch (`git push origin feature/TinhNangMoi`)
5. Tạo Pull Request

## 📄 License

Dự án này được phát hành dưới [MIT License](LICENSE).

## 📞 Liên hệ

- **Developer:** Nguyen DC
- **Email:** [your-email@example.com]
- **GitHub:** [@NguyenDC3107](https://github.com/NguyenDC3107)

## 🙏 Lời cảm ơn

Cảm ơn tất cả những người đã đóng góp cho dự án này!
