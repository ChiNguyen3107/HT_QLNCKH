<?php
/**
 * Session Dismiss Warning API
 * Bỏ qua cảnh báo session
 */

require_once '../../../include/connect.php';
require_once '../../../core/SessionManager.php';
require_once '../../../app/Services/AuthService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $sessionManager = SessionManager::getInstance();
    $authService = new AuthService();
    
    // Kiểm tra session
    if (!$authService->check()) {
        echo json_encode([
            'success' => false,
            'message' => 'Session không hợp lệ'
        ]);
        exit;
    }
    
    // Bỏ qua warning
    $sessionManager->dismissWarning();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã bỏ qua cảnh báo phiên làm việc'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}
