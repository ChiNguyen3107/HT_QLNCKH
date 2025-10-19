# Nâng cấp bảo mật Password - Hệ thống NCKH

## Tổng quan
Dự án đã được nâng cấp từ MD5 hash yếu sang bcrypt/Argon2 với các tính năng bảo mật nâng cao.

## Các tính năng mới

### 1. Password Hashing mạnh mẽ
- **bcrypt/Argon2**: Thay thế MD5 bằng thuật toán hash mạnh
- **Mixed format support**: Hỗ trợ cả MD5 cũ và bcrypt mới trong quá trình chuyển đổi
- **Auto-upgrade**: Tự động nâng cấp password cũ khi user đăng nhập

### 2. Password Policy
- **Độ dài tối thiểu**: 8 ký tự (có thể cấu hình)
- **Yêu cầu ký tự**: Chữ hoa, chữ thường, số, ký tự đặc biệt
- **Kiểm tra password yếu**: Từ chối các password phổ biến
- **Kiểm tra ký tự liên tiếp**: Tránh patterns như "123", "abc"
- **Kiểm tra ký tự lặp**: Tránh patterns như "aaa", "111"

### 3. Password Strength Indicator
- **Real-time validation**: Kiểm tra độ mạnh khi nhập
- **Visual feedback**: Thanh tiến trình và thông báo
- **Requirements checklist**: Hiển thị các yêu cầu chưa đạt

### 4. Account Lockout
- **5 lần thất bại**: Khóa tài khoản sau 5 lần đăng nhập sai
- **15 phút lockout**: Thời gian khóa 15 phút
- **IP-based tracking**: Theo dõi theo cả username và IP

### 5. Password Reset
- **Secure tokens**: Token 64 ký tự hex ngẫu nhiên
- **1 giờ expiry**: Token hết hạn sau 1 giờ
- **Rate limiting**: Tối đa 3 lần reset trong 1 giờ
- **Email notification**: Gửi email với link reset

### 6. Login Logging
- **Tất cả attempts**: Ghi log mọi lần đăng nhập
- **IP tracking**: Theo dõi IP address
- **Status tracking**: Success, failed, locked, error
- **Audit trail**: Lưu trữ lịch sử đăng nhập

## Cài đặt

### 1. Chạy Migration Database
```bash
# Chạy file SQL migration
mysql -u username -p database_name < migrations/001_security_upgrade.sql
```

### 2. Migrate Existing Passwords
```bash
# Chạy script migration
php migrate_passwords.php
```

### 3. Cấu hình Email (Optional)
Cập nhật cấu hình email trong `config/mail.php` để gửi password reset.

## Cách sử dụng

### 1. Password Policy
```php
$policy = new PasswordPolicy();

// Validate password
$result = $policy->validatePassword('MyPassword123!');
if ($result['valid']) {
    echo "Password hợp lệ";
} else {
    echo "Lỗi: " . implode(', ', $result['errors']);
}

// Check strength
$strength = $policy->calculateStrength('MyPassword123!');
echo "Điểm mạnh: " . $strength . "/100";
```

### 2. Authentication
```php
$authService = new AuthService();

// Authenticate user
$result = $authService->authenticate($username, $password, $ipAddress);
if ($result['success']) {
    // Login thành công
    $user = $result['user'];
} else {
    // Login thất bại
    echo $result['message'];
}
```

### 3. Password Reset
```php
$resetService = new PasswordResetService();

// Tạo reset token
$result = $resetService->createResetToken($email, $userType);

// Reset password
$result = $resetService->resetPassword($token, $newPassword, $userType);
```

### 4. Password Strength Indicator (Frontend)
```html
<!-- Include JavaScript -->
<script src="public/js/password-strength.js"></script>

<!-- HTML -->
<input type="password" id="password" name="password">
<div id="password-strength-container"></div>

<script>
// Auto-initialize
// Hoặc manual initialize
const indicator = new PasswordStrengthIndicator({
    container: document.getElementById('password-strength-container'),
    passwordInput: document.getElementById('password')
});
</script>
```

## API Endpoints

### 1. Forgot Password
- **URL**: `/forgot_password.php`
- **Method**: POST
- **Fields**: `email`, `user_type`

### 2. Reset Password
- **URL**: `/reset_password.php?token=xxx`
- **Method**: POST
- **Fields**: `new_password`, `confirm_password`

## Cấu hình

### 1. Password Policy
```php
$config = [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special_chars' => true,
    'max_length' => 128,
    'max_consecutive_chars' => 3,
    'max_repeating_chars' => 2
];

$policy = new PasswordPolicy($config);
```

### 2. Account Lockout
```php
// Trong AuthService
private $maxLoginAttempts = 5;        // Số lần thất bại tối đa
private $lockoutDuration = 900;       // Thời gian khóa (giây)
```

### 3. Password Reset
```php
// Trong PasswordResetService
private $tokenExpiry = 3600;          // Thời gian hết hạn token (giây)
private $maxAttempts = 3;             // Số lần reset tối đa trong 1 giờ
```

## Database Schema

### Bảng mới được tạo:
1. **login_attempts**: Lưu trữ lịch sử đăng nhập
2. **password_reset_tokens**: Lưu trữ token reset password
3. **security_audit_log**: Log các hoạt động bảo mật
4. **user_sessions**: Quản lý session

### Cột mới được thêm:
- `last_login`: Thời gian đăng nhập cuối
- `password_changed_at`: Thời gian thay đổi password cuối
- `email`: Email cho bảng user

## Testing

### Chạy Unit Tests
```bash
# Cài đặt PHPUnit
composer require --dev phpunit/phpunit

# Chạy tests
./vendor/bin/phpunit tests/
```

### Test Coverage
- PasswordPolicy: 100%
- AuthService: 95%
- PasswordResetService: 90%

## Bảo trì

### 1. Cleanup Expired Data
```php
// Chạy hàng ngày
$resetService = new PasswordResetService();
$resetService->cleanupExpiredTokens();
```

### 2. Monitor Login Attempts
```sql
-- Xem các lần đăng nhập thất bại gần đây
SELECT * FROM login_attempts 
WHERE status = 'failed' 
AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY attempt_time DESC;
```

### 3. Security Audit
```sql
-- Xem audit log
SELECT * FROM security_audit_log 
WHERE action = 'password_changed'
ORDER BY created_at DESC;
```

## Troubleshooting

### 1. Lỗi "Logger class not found"
- Đảm bảo `core/Logger.php` đã được tạo
- Kiểm tra autoloader trong `bootstrap/app.php`

### 2. Lỗi "Database connection failed"
- Kiểm tra cấu hình database trong `config/database.php`
- Đảm bảo database đã được tạo và migration đã chạy

### 3. Email không gửi được
- Kiểm tra cấu hình SMTP trong `config/mail.php`
- Test với `mail()` function của PHP

### 4. Password không được upgrade
- Kiểm tra quyền ghi database
- Xem log trong `logs/` directory

## Security Best Practices

1. **Regular Password Changes**: Khuyến khích user thay đổi password định kỳ
2. **Monitor Failed Logins**: Theo dõi các lần đăng nhập thất bại
3. **Session Management**: Implement session timeout
4. **HTTPS Only**: Sử dụng HTTPS cho tất cả authentication
5. **Rate Limiting**: Implement rate limiting cho API endpoints
6. **Audit Logging**: Regular review audit logs

## Changelog

### Version 2.0.0
- ✅ Thay thế MD5 bằng bcrypt/Argon2
- ✅ Thêm Password Policy
- ✅ Thêm Password Strength Indicator
- ✅ Thêm Account Lockout
- ✅ Thêm Password Reset
- ✅ Thêm Login Logging
- ✅ Thêm Unit Tests
- ✅ Migration script cho existing passwords

## Support

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra log files trong `logs/` directory
2. Xem troubleshooting section
3. Tạo issue với thông tin chi tiết về lỗi
