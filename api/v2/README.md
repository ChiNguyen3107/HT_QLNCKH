# NCKH RESTful API v2

RESTful API cho hệ thống quản lý nghiên cứu khoa học (NCKH) với đầy đủ các tính năng chuẩn.

## Tính năng chính

- ✅ RESTful API design chuẩn
- ✅ JWT Authentication
- ✅ Rate Limiting
- ✅ API Versioning
- ✅ Swagger/OpenAPI Documentation
- ✅ Request Validation
- ✅ Response Formatting
- ✅ Error Handling
- ✅ Logging
- ✅ Unit Tests
- ✅ PHP SDK Client

## Cài đặt

### 1. Cài đặt dependencies

```bash
composer install
```

### 2. Cấu hình môi trường

Copy file cấu hình mẫu:
```bash
cp env.api.example .env
```

Chỉnh sửa file `.env`:
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nckh_database
DB_USERNAME=root
DB_PASSWORD=

# JWT Configuration
JWT_SECRET=your-super-secret-jwt-key-here
JWT_ALGORITHM=HS256
JWT_EXPIRY=86400

# API Configuration
API_RATE_LIMIT=100
API_RATE_WINDOW=3600
```

### 3. Khởi chạy API

```bash
# Sử dụng PHP built-in server
php -S localhost:8000 -t api/v2

# Hoặc sử dụng Apache/Nginx
# Truy cập: http://localhost/api/v2
```

## API Endpoints

### Authentication
- `POST /auth/login` - Đăng nhập
- `POST /auth/logout` - Đăng xuất
- `POST /auth/refresh` - Làm mới token
- `GET /auth/me` - Thông tin user hiện tại
- `POST /auth/change-password` - Đổi mật khẩu

### Students
- `GET /students` - Danh sách sinh viên
- `GET /students/{id}` - Chi tiết sinh viên
- `POST /students` - Tạo sinh viên mới
- `PUT /students/{id}` - Cập nhật sinh viên
- `DELETE /students/{id}` - Xóa sinh viên

### Projects
- `GET /projects` - Danh sách đề tài
- `GET /projects/{id}` - Chi tiết đề tài
- `POST /projects` - Tạo đề tài mới
- `PUT /projects/{id}` - Cập nhật đề tài
- `DELETE /projects/{id}` - Xóa đề tài
- `POST /projects/{id}/members` - Thêm thành viên
- `DELETE /projects/{id}/members/{student_id}` - Xóa thành viên
- `POST /projects/{id}/evaluation` - Đánh giá đề tài

### Faculties
- `GET /faculties` - Danh sách khoa
- `GET /faculties/{id}` - Chi tiết khoa
- `POST /faculties` - Tạo khoa mới
- `PUT /faculties/{id}` - Cập nhật khoa
- `DELETE /faculties/{id}` - Xóa khoa
- `GET /faculties/{id}/statistics` - Thống kê khoa

### Utility
- `GET /health` - Health check

## Sử dụng API

### 1. Authentication

```bash
# Đăng nhập
curl -X POST http://localhost/api/v2/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'

# Response
{
  "success": true,
  "status": 200,
  "message": "Đăng nhập thành công",
  "data": {
    "user": {
      "id": "admin",
      "username": "admin",
      "role": "admin",
      "name": "Administrator"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 86400
  },
  "timestamp": "2024-01-01 12:00:00"
}
```

### 2. Sử dụng token

```bash
# Lấy danh sách sinh viên
curl -X GET http://localhost/api/v2/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### 3. Lọc và phân trang

```bash
# Lấy sinh viên với lọc
curl -X GET "http://localhost/api/v2/students?department=CNTT&research_status=active&page=1&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Sử dụng PHP SDK

```php
<?php
require_once 'api/v2/sdk/NckhApiClient.php';

// Khởi tạo client
$api = new NckhApiClient('http://localhost/api/v2');

try {
    // Đăng nhập
    $loginResult = $api->login('admin', 'admin123');
    
    if ($loginResult['success']) {
        echo "Đăng nhập thành công!\n";
        
        // Lấy danh sách sinh viên
        $students = $api->getStudents(['page' => 1, 'limit' => 10]);
        echo "Tổng số sinh viên: " . $students['meta']['pagination']['total'] . "\n";
        
        // Lấy danh sách đề tài
        $projects = $api->getProjects(['status' => 'Đang thực hiện']);
        echo "Số đề tài đang thực hiện: " . count($projects['data']) . "\n";
        
        // Đăng xuất
        $api->logout();
    }
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?>
```

## Response Format

Tất cả API responses đều có format chuẩn:

```json
{
  "success": true,
  "status": 200,
  "message": "Thành công",
  "data": { ... },
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5,
      "has_next": true,
      "has_prev": false
    }
  },
  "timestamp": "2024-01-01 12:00:00"
}
```

## Error Handling

```json
{
  "success": false,
  "status": 400,
  "message": "Dữ liệu không hợp lệ",
  "data": {
    "errors": {
      "username": ["Tên đăng nhập là bắt buộc"],
      "password": ["Mật khẩu là bắt buộc"]
    }
  },
  "timestamp": "2024-01-01 12:00:00"
}
```

## HTTP Status Codes

- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error

## Rate Limiting

API có rate limiting mặc định:
- 100 requests per hour per client
- Có thể cấu hình trong file `.env`

## Testing

Chạy unit tests:

```bash
# Chạy tất cả tests
composer test

# Chạy API tests
composer test:feature

# Chạy với coverage
composer test:coverage
```

## Swagger Documentation

Truy cập Swagger UI:
```
http://localhost/api/v2/swagger.php
```

## Cấu trúc thư mục

```
api/v2/
├── index.php              # Entry point
├── swagger.php            # Swagger documentation
├── sdk/                   # PHP SDK
│   ├── NckhApiClient.php
│   └── example.php
└── README.md

app/Http/
├── Controllers/Api/       # API Controllers
├── Resources/             # Response Resources
├── Requests/              # Request Validation
├── Middleware/            # Middleware
└── Router/                # API Router

tests/Feature/Api/         # API Tests
```

## Bảo mật

- JWT tokens với expiration
- Rate limiting
- Input validation
- SQL injection protection
- XSS protection
- CORS configuration

## Hỗ trợ

Liên hệ: admin@nckh.ctu.edu.vn
