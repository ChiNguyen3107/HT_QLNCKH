<?php

/**
 * Bootstrap file cho PHPUnit testing
 */

// Đặt timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Include autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define env() function trước khi load config
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Load environment variables cho testing
if (file_exists(__DIR__ . '/../.env.testing')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.testing');
    $dotenv->load();
} else {
    // Set default testing environment variables
    $_ENV['APP_ENV'] = 'testing';
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = ':memory:';
    $_ENV['CACHE_DRIVER'] = 'array';
    $_ENV['SESSION_DRIVER'] = 'array';
}

// Load application bootstrap
require_once __DIR__ . '/../bootstrap/app.php';

// Tạo thư mục storage nếu chưa có
$storageDir = __DIR__ . '/../storage';
$directories = ['logs', 'cache', 'sessions', 'coverage'];

foreach ($directories as $dir) {
    $path = $storageDir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Setup error reporting cho testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', $storageDir . '/logs/php_errors.log');

// Set testing environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
