<?php
/**
 * Cấu hình ứng dụng chính
 */

return [
    'name' => 'Hệ thống Quản lý Nghiên cứu Khoa học',
    'version' => '1.0.0',
    'author' => 'Admin NCKH',
    'debug' => true,
    'timezone' => 'Asia/Ho_Chi_Minh',
    'locale' => 'vi',
    'url' => env('APP_URL', 'http://localhost/NLNganh'),
    'asset_url' => env('ASSET_URL', 'http://localhost/NLNganh/public'),
    'session' => [
        'lifetime' => 3600,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
    ],
    'pagination' => [
        'per_page' => 10,
        'max_per_page' => 100,
    ],
    'upload' => [
        'max_file_size' => 10485760, // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'path' => 'uploads/',
    ],
];

