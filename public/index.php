<?php
/**
 * Entry point của ứng dụng
 */

// Load bootstrap
require_once dirname(__DIR__) . '/bootstrap/app.php';

// Khởi tạo Router
$router = new Router();

// Định nghĩa routes
require_once dirname(__DIR__) . '/routes/web.php';

// Dispatch request
$router->dispatch();

