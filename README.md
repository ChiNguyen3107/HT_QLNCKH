# Hệ thống Quản lý Nghiên cứu Khoa học

Hệ thống quản lý toàn diện cho các hoạt động nghiên cứu khoa học của sinh viên và giảng viên tại Trường Đại học Cần Thơ.

## 🚀 Tính năng chính

- **Quản lý đề tài nghiên cứu**: Đăng ký, theo dõi và quản lý các đề tài nghiên cứu
- **Quản lý nhóm nghiên cứu**: Phân chia nhóm, phân công nhiệm vụ
- **Quản lý tiến độ**: Theo dõi tiến độ thực hiện đề tài
- **Quản lý tài liệu**: Lưu trữ và chia sẻ tài liệu nghiên cứu
- **Thống kê & Báo cáo**: Tạo báo cáo chi tiết về hoạt động nghiên cứu
- **Thông báo**: Hệ thống thông báo thông minh

## 📁 Cấu trúc dự án

```
NLNganh/
├── app/                    # Ứng dụng chính
│   ├── Controllers/        # Controllers (MVC)
│   ├── Models/            # Models (MVC)
│   ├── Views/             # Views (MVC)
│   ├── Services/          # Business Logic
│   └── Middleware/        # Middleware
├── config/                # Cấu hình
├── core/                  # Core framework
├── public/                # Web root
├── storage/               # Storage (logs, cache, sessions)
├── api/                   # API endpoints
├── tests/                 # Tests
└── bootstrap/             # Bootstrap files
```

## 🛠️ Cài đặt

### Yêu cầu hệ thống
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx
- Composer (tùy chọn)

### Cài đặt

1. **Clone repository**
```bash
git clone <repository-url>
cd NLNganh
```

2. **Cấu hình môi trường**
```bash
cp env.example .env
# Chỉnh sửa file .env với thông tin cấu hình của bạn
```

3. **Cấu hình database**
- Tạo database `ql_nckh`
- Import file `ql_nckh.sql`

4. **Cấu hình web server**
- Trỏ document root đến thư mục `public/`
- Đảm bảo mod_rewrite được bật

### Cấu hình XAMPP

1. Copy thư mục dự án vào `htdocs`
2. Truy cập `http://localhost/NLNganh`
3. Cấu hình database trong file `.env`

## 🔧 Cấu hình

### Database
Chỉnh sửa file `config/database.php` hoặc `.env`:

```php
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ql_nckh
DB_USERNAME=root
DB_PASSWORD=
```

### Application
Chỉnh sửa file `config/app.php`:

```php
'url' => 'http://localhost/NLNganh',
'debug' => true,
'timezone' => 'Asia/Ho_Chi_Minh'
```

## 📚 Sử dụng

### Truy cập hệ thống
- **URL**: `http://localhost/NLNganh`
- **Admin**: Sử dụng tài khoản admin
- **Sinh viên**: Sử dụng mã sinh viên
- **Giảng viên**: Sử dụng mã giảng viên

### API Endpoints
- `GET /api/v1/projects` - Lấy danh sách dự án
- `GET /api/v1/students` - Lấy danh sách sinh viên
- `GET /api/v1/teachers` - Lấy danh sách giảng viên

## 🧪 Testing

```bash
# Chạy tests
composer test

# Hoặc
phpunit
```

## 📝 Changelog

### v1.0.0
- Cấu trúc MVC chuẩn
- Hệ thống authentication
- Quản lý đề tài nghiên cứu
- API endpoints cơ bản

## 🤝 Đóng góp

1. Fork dự án
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Tạo Pull Request

## 📄 License

Dự án này được phân phối dưới MIT License. Xem file `LICENSE` để biết thêm chi tiết.

## 📞 Liên hệ

- **Email**: dhct@ctu.edu.vn
- **Website**: www.ctu.edu.vn
- **Địa chỉ**: Khu II, Đường 3/2, Phường Xuân Khánh, Quận Ninh Kiều, TP. Cần Thơ

