# Session Security Enhancement

## Tổng quan

Dự án đã được nâng cấp với hệ thống bảo mật session toàn diện, bao gồm:

- **Session Manager**: Quản lý session an toàn với các tính năng bảo mật cao
- **Session Fingerprinting**: Phát hiện session hijacking
- **Concurrent Session Limits**: Giới hạn số phiên đăng nhập đồng thời
- **Session Timeout với Warning**: Cảnh báo trước khi session hết hạn
- **Session Monitoring**: Theo dõi và ghi log tất cả hoạt động session
- **Session Regeneration**: Tự động tạo session ID mới để tránh fixation attacks

## Cấu trúc Files

### Core Files
- `core/SessionManager.php` - Quản lý session chính
- `config/session.php` - Cấu hình session security

### Updated Files
- `app/Middleware/AuthMiddleware.php` - Middleware xác thực với session security
- `app/Services/AuthService.php` - Service xác thực tích hợp SessionManager
- `login_process.php` - Xử lý đăng nhập với secure session

### API Endpoints
- `api/v1/session/status.php` - Kiểm tra trạng thái session
- `api/v1/session/extend.php` - Gia hạn session
- `api/v1/session/dismiss-warning.php` - Bỏ qua cảnh báo session

### Frontend
- `public/js/session-security.js` - JavaScript xử lý session warning

### Database
- `migrations/002_session_security.sql` - Migration tạo bảng session logs

### Tests
- `tests/Unit/Security/SessionSecurityTest.php` - Unit tests cho session security

## Cài đặt

### 1. Chạy Migration
```sql
-- Chạy file migration để tạo các bảng cần thiết
source migrations/002_session_security.sql
```

### 2. Cấu hình Session
Chỉnh sửa `config/session.php` theo nhu cầu:

```php
return [
    'lifetime' => 3600, // Thời gian sống session (giây)
    'warning_time' => 300, // Thời gian cảnh báo trước khi hết hạn
    'max_concurrent_sessions' => 3, // Số phiên đăng nhập tối đa
    'check_ip_address' => false, // Kiểm tra IP (có thể gây vấn đề với mobile)
    'secure' => false, // Set true cho HTTPS
    // ... các cấu hình khác
];
```

### 3. Thêm JavaScript vào Layout
Thêm vào file layout chính:

```html
<script src="/public/js/session-security.js"></script>
```

## Tính năng chính

### 1. Session Security Manager

```php
// Khởi tạo
$sessionManager = SessionManager::getInstance();

// Tạo secure session
$sessionManager->createSecureSession($userId, $userData);

// Validate session
$isValid = $sessionManager->validateSession();

// Gia hạn session
$sessionManager->extendSession();

// Destroy session
$sessionManager->destroySession('logout');
```

### 2. Session Fingerprinting

Hệ thống tự động tạo fingerprint dựa trên:
- User Agent
- IP Address
- Accept Language
- Accept Encoding

### 3. Concurrent Session Limits

Giới hạn số phiên đăng nhập đồng thời của mỗi user:
- Mặc định: 3 phiên
- Tự động xóa phiên cũ nhất khi vượt quá giới hạn

### 4. Session Timeout với Warning

- Cảnh báo 5 phút trước khi session hết hạn
- Modal popup với countdown timer
- Tùy chọn gia hạn hoặc đăng xuất

### 5. Session Monitoring

Tất cả hoạt động session được ghi log vào bảng `session_logs`:
- Session creation
- Session validation
- Session destruction
- Failed validations
- Security events

## API Usage

### Kiểm tra trạng thái session
```javascript
fetch('/api/v1/session/status')
    .then(response => response.json())
    .then(data => {
        console.log('Time remaining:', data.time_remaining);
        console.log('Warning:', data.is_warning);
    });
```

### Gia hạn session
```javascript
fetch('/api/v1/session/extend', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Session extended');
    }
});
```

### Bỏ qua cảnh báo
```javascript
fetch('/api/v1/session/dismiss-warning', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Warning dismissed');
    }
});
```

## Cấu hình bảo mật

### 1. Session Cookie Security
```php
'session' => [
    'secure' => true, // Chỉ gửi qua HTTPS
    'http_only' => true, // Không cho phép JavaScript truy cập
    'same_site' => 'Strict', // CSRF protection
]
```

### 2. Session Regeneration
```php
'regeneration' => [
    'on_login' => true,
    'interval' => 300, // Mỗi 5 phút
    'on_role_change' => true
]
```

### 3. Hijacking Protection
```php
'hijacking_protection' => [
    'enabled' => true,
    'fingerprint_validation' => true,
    'concurrent_session_limit' => true
]
```

## Monitoring và Logging

### 1. Session Logs
Bảng `session_logs` chứa:
- Session ID
- User ID
- Action (created, validated, destroyed, etc.)
- IP Address
- User Agent
- Timestamp
- Status

### 2. Security Events
Bảng `session_security_events` chứa:
- Event type
- Severity level
- Description
- Additional data

### 3. Views có sẵn
- `session_stats` - Thống kê session theo ngày
- `active_sessions` - Danh sách session đang hoạt động

## Testing

Chạy unit tests:

```bash
php tests/Unit/Security/SessionSecurityTest.php
```

Tests bao gồm:
- Session creation và validation
- Timeout handling
- Fingerprinting
- Regeneration
- Warning system
- Extension
- Destruction

## Troubleshooting

### 1. Session không được tạo
- Kiểm tra cấu hình database
- Đảm bảo bảng `session_logs` đã được tạo
- Kiểm tra quyền ghi database

### 2. Warning không hiển thị
- Kiểm tra JavaScript console
- Đảm bảo file `session-security.js` được load
- Kiểm tra API endpoints

### 3. Session bị invalid liên tục
- Kiểm tra fingerprint configuration
- Kiểm tra IP validation settings
- Xem logs trong `session_security_events`

## Bảo mật

### 1. Production Settings
```php
'secure' => true, // HTTPS only
'check_ip_address' => true, // Kiểm tra IP
'max_concurrent_sessions' => 1, // Chỉ 1 phiên
'regeneration' => [
    'interval' => 180 // Mỗi 3 phút
]
```

### 2. Monitoring
- Theo dõi `session_security_events` thường xuyên
- Set up alerts cho suspicious activities
- Regular cleanup expired sessions

### 3. Maintenance
- Chạy cleanup procedure định kỳ
- Monitor session statistics
- Review security logs

## Changelog

### Version 1.0.0
- Initial release
- Session Manager implementation
- Fingerprinting system
- Concurrent session limits
- Timeout warning system
- Monitoring và logging
- Unit tests
- API endpoints
- Frontend integration
