<?php
/**
 * Session Extend API
 * Gia hạn phiên làm việc
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
    
    // Gia hạn session
    $extended = $sessionManager->extendSession();
    
    if ($extended) {
        // Lấy thông tin session mới
        $sessionInfo = $sessionManager->getSessionInfo();
        
        echo json_encode([
            'success' => true,
            'message' => 'Phiên làm việc đã được gia hạn thành công',
            'time_remaining' => $sessionInfo['time_remaining'],
            'last_activity' => $sessionInfo['last_activity']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể gia hạn phiên làm việc'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}
