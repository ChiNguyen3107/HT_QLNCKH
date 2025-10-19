<?php
/**
 * CSRF Token Refresh API
 * 
 * API endpoint để refresh CSRF token cho AJAX requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: X-CSRF-Token, Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../core/CSRF.php';

// Chỉ cho phép POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'message' => 'Chỉ cho phép POST requests'
    ]);
    exit;
}

try {
    // Tạo CSRF token mới
    $token = CSRF::generateToken('ajax');
    
    // Trả về response thành công
    echo json_encode([
        'success' => true,
        'token' => $token,
        'token_name' => CSRF::getTokenName(),
        'message' => 'CSRF token đã được refresh thành công'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => 'Lỗi server khi tạo CSRF token: ' . $e->getMessage()
    ]);
}
