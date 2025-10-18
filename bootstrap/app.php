<?php
/**
 * Bootstrap file - Khởi tạo ứng dụng
 */

// Định nghĩa các hằng số cơ bản
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Autoloader đơn giản
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load core classes
require_once CORE_PATH . '/Config.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/Helper.php';
require_once CORE_PATH . '/Validator.php';
require_once CORE_PATH . '/Logger.php';

// Load configuration
Config::load('app');
Config::load('database');
Config::load('session');
Config::load('mail');
Config::load('cache');

// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set(Config::get('app.timezone', 'Asia/Ho_Chi_Minh'));

// Error reporting
if (Config::get('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Helper functions
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        return Config::get($key, $default);
    }
}

if (!function_exists('db')) {
    function db() {
        return Database::getInstance();
    }
}

if (!function_exists('view')) {
    function view($template, $data = []) {
        extract($data);
        $templatePath = APP_PATH . '/Views/' . $template . '.php';
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            throw new Exception("View not found: {$template}");
        }
    }
}

if (!function_exists('redirect')) {
    function redirect($url, $statusCode = 302) {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $baseUrl = config('app.url', 'http://localhost/NLNganh');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        $assetUrl = config('app.asset_url', 'http://localhost/NLNganh/public');
        return rtrim($assetUrl, '/') . '/' . ltrim($path, '/');
    }
}

