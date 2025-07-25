# Hệ thống Quản lý Nghiên cứu Khoa học (NCKH)

## Sử dụng Favicon

Để đảm bảo tất cả các trang đều hiển thị favicon đúng cách:

1. Mọi trang HTML/PHP mới nên sử dụng mẫu từ `templates/page_template.php`

2. Nếu tạo trang mới từ đầu, hãy chèn đoạn mã sau vào thẻ `<head>` của trang:
   ```html
   <!-- Favicon -->
   <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
   <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
   ```

3. Nếu bạn muốn thêm favicon vào tất cả các trang một lần nữa, chạy script:
   ```
   php -f add_favicon.php
   ```

4. Cài đặt .htaccess đã được cấu hình để xử lý yêu cầu favicon từ root domain.

5. Một hàm PHP `addFaviconTags()` được cung cấp trong `include/functions.php` để thêm favicon vào các trang một cách động.

## Cấu trúc dự án

...
