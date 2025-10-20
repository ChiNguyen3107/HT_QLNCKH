<?php
/**
 * RESTful API v2 Entry Point
 */

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

// Load database connection
require_once __DIR__ . '/../include/connect.php';

// Load API Router
require_once __DIR__ . '/../../app/Http/Router/ApiRouter.php';

try {
    // Create API router
    $router = new App\Http\Router\ApiRouter();
    
    // Route the request
    $router->route();
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 500,
        'message' => 'Lỗi máy chủ: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
