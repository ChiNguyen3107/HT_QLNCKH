<?php
/**
 * Tệp cấu hình tập trung cho toàn bộ hệ thống
 */

// Cấu hình cơ sở dữ liệu
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ql_nckh');

// Đường dẫn hệ thống
define('BASE_URL', '/NLNganh');
define('UPLOAD_DIR', 'uploads/');
define('REPORT_UPLOAD_DIR', UPLOAD_DIR . 'reports/');
define('CONTRACT_UPLOAD_DIR', UPLOAD_DIR . 'contract_files/');
define('PROJECT_FILES_DIR', UPLOAD_DIR . 'project_files/');

// Cài đặt hệ thống
define('SESSION_TIMEOUT', 3600); // Thời gian timeout của phiên làm việc (giây)
define('DEFAULT_RECORDS_PER_PAGE', 10); // Số bản ghi mặc định trên mỗi trang

// Cài đặt email
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your_email@gmail.com');
define('MAIL_PASSWORD', 'your_email_password');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', 'your_email@gmail.com');
define('MAIL_FROM_NAME', 'Hệ thống NCKH');

// Cài đặt ứng dụng
define('APP_NAME', 'Hệ thống Quản lý Nghiên cứu Khoa học');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Admin NCKH');
define('DEBUG_MODE', true); // Set false in production
?>
