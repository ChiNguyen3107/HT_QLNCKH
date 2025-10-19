# CSRF Protection Implementation

## Tổng quan

Hệ thống CSRF Protection đã được implement toàn diện cho dự án PHP để bảo vệ khỏi các cuộc tấn công Cross-Site Request Forgery.

## Các thành phần đã implement

### 1. Core CSRF Class (`core/CSRF.php`)
- **Chức năng chính:**
  - Tạo và validate CSRF tokens
  - Quản lý token lifecycle (expiration, single-use)
  - Cleanup tokens cũ tự động
  - Hỗ trợ multiple forms

- **Các methods quan trọng:**
  - `generateToken($formName)` - Tạo token mới
  - `validateToken($token, $formName)` - Validate token
  - `getTokenField($formName)` - Tạo HTML input field
  - `checkRequest($formName)` - Kiểm tra request có token hợp lệ
  - `validateHeaderToken($formName)` - Validate token từ header

### 2. CSRF Middleware (`app/Middleware/CSRFMiddleware.php`)
- **Chức năng:**
  - Tự động validate CSRF token cho POST/PUT/DELETE requests
  - Hỗ trợ excluded routes và methods
  - Log CSRF violations
  - Xử lý lỗi CSRF cho AJAX và form requests

- **Cấu hình:**
  - Excluded routes: `/login`, `/logout`, `/forgot-password`, etc.
  - Excluded methods: `GET`, `HEAD`, `OPTIONS`
  - Token expiration: 24 giờ

### 3. Helper Functions (`core/Helper.php`)
- **Chức năng:**
  - Cung cấp helper functions cho views
  - Tạo forms với CSRF protection
  - Hiển thị thông báo lỗi CSRF
  - Hỗ trợ AJAX requests

- **Các methods:**
  - `csrfField($formName)` - Tạo CSRF input field
  - `csrfToken($formName)` - Lấy CSRF token
  - `formOpen($action, $method, $attributes, $formName)` - Tạo form với CSRF
  - `csrfAjaxToken($formName)` - Token cho AJAX
  - `showCSRFError($class)` - Hiển thị lỗi CSRF

### 4. JavaScript Protection (`public/js/csrf-protection.js`)
- **Chức năng:**
  - Tự động thêm CSRF token vào AJAX requests
  - Validate forms trước khi submit
  - Xử lý lỗi CSRF từ server
  - Hỗ trợ jQuery, Fetch API, XMLHttpRequest

- **Features:**
  - Auto-injection cho tất cả AJAX requests
  - Form validation
  - Error handling với UI notifications
  - Token refresh functionality

### 5. API Endpoints
- **`api/v1/csrf/refresh.php`** - Refresh CSRF token cho AJAX
- Các API endpoints khác đã được cập nhật để validate CSRF token

### 6. Unit Tests (`tests/Unit/Security/CSRFTest.php`)
- **Coverage:**
  - Token generation và validation
  - Middleware functionality
  - Helper functions
  - Error handling
  - Edge cases

## Cách sử dụng

### 1. Trong PHP Views

```php
<?php require_once 'core/Helper.php'; ?>

<!-- Thêm CSRF token vào form -->
<form method="POST" action="process.php">
    <?php echo Helper::csrfField('my_form'); ?>
    <!-- Các fields khác -->
    <button type="submit">Submit</button>
</form>

<!-- Hoặc sử dụng helper form -->
<?php echo Helper::formOpen('/process', 'POST', ['class' => 'my-form'], 'my_form'); ?>
    <!-- Form content -->
<?php echo Helper::formClose(); ?>
```

### 2. Trong JavaScript

```javascript
// CSRF protection tự động hoạt động cho tất cả AJAX requests
$.ajax({
    url: '/api/endpoint',
    method: 'POST',
    data: { key: 'value' },
    // CSRF token sẽ được tự động thêm vào header
    success: function(response) {
        console.log(response);
    }
});

// Sử dụng Fetch API
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ key: 'value' })
    // CSRF token sẽ được tự động thêm
});
```

### 3. Trong Controllers

```php
<?php
require_once 'core/CSRF.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::checkRequest('form_name')) {
        // Xử lý lỗi CSRF
        die('CSRF token không hợp lệ');
    }
    
    // Xử lý form data
}
?>
```

### 4. Sử dụng Middleware

```php
<?php
require_once 'app/Middleware/CSRFMiddleware.php';

$middleware = new CSRFMiddleware();
$middleware->handle($request, function($req) {
    // Xử lý request
    return $response;
});
?>
```

## Cấu hình

### 1. Excluded Routes
Các routes không cần CSRF protection:
- `/login`
- `/logout`
- `/forgot-password`
- `/reset-password`
- `/api/v1/auth/*`

### 2. Token Settings
- **Expiration:** 24 giờ
- **Max tokens per session:** 10
- **Token length:** 32 bytes (64 hex characters)
- **Single use:** Có (token chỉ dùng được 1 lần)

### 3. Security Features
- **Timing attack protection:** Sử dụng `hash_equals()`
- **Token rotation:** Tự động tạo token mới
- **Session binding:** Token gắn với session
- **Logging:** Log tất cả CSRF violations

## Testing

Chạy unit tests:

```bash
# Chạy tất cả tests
./vendor/bin/phpunit

# Chạy chỉ CSRF tests
./vendor/bin/phpunit tests/Unit/Security/CSRFTest.php

# Chạy với coverage
./vendor/bin/phpunit --coverage-html coverage/
```

## Troubleshooting

### 1. CSRF token không hợp lệ
- Kiểm tra session có hoạt động không
- Kiểm tra token có được gửi đúng không
- Kiểm tra form name có khớp không

### 2. AJAX requests bị lỗi CSRF
- Đảm bảo đã include `csrf-protection.js`
- Kiểm tra token có được gửi trong header không
- Kiểm tra API endpoint có validate CSRF không

### 3. Forms không hoạt động
- Kiểm tra CSRF field có được thêm vào form không
- Kiểm tra form name có đúng không
- Kiểm tra token có được validate không

## Security Notes

1. **Token Storage:** Tokens được lưu trong session, không trong database
2. **Token Rotation:** Mỗi request tạo token mới
3. **Expiration:** Tokens tự động hết hạn sau 24 giờ
4. **Single Use:** Mỗi token chỉ dùng được 1 lần
5. **Logging:** Tất cả CSRF violations được log để monitoring

## Maintenance

1. **Regular cleanup:** Tokens cũ được tự động dọn dẹp
2. **Monitoring:** Kiểm tra logs để phát hiện attacks
3. **Updates:** Cập nhật excluded routes khi cần
4. **Testing:** Chạy tests định kỳ để đảm bảo hoạt động

## Files đã tạo/cập nhật

### Files mới:
- `core/CSRF.php`
- `app/Middleware/CSRFMiddleware.php`
- `public/js/csrf-protection.js`
- `api/v1/csrf/refresh.php`
- `tests/Unit/Security/CSRFTest.php`
- `CSRF_PROTECTION_README.md`

### Files đã cập nhật:
- `core/Helper.php`
- `login.php`
- `forgot_password.php`
- `reset_password.php`
- `app/Views/student/register_project_form.php`
- `api/v1/notification_manager.php`
- `tests/phpunit.xml`

## Kết luận

CSRF Protection đã được implement toàn diện với:
- ✅ Token generation và validation
- ✅ Middleware tự động
- ✅ Helper functions cho views
- ✅ JavaScript protection cho AJAX
- ✅ API endpoints protection
- ✅ Unit tests đầy đủ
- ✅ Error handling và logging
- ✅ Documentation chi tiết

Hệ thống hiện tại đã sẵn sàng để bảo vệ khỏi các cuộc tấn công CSRF.
