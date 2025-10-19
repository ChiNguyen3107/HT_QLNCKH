<?php
/**
 * Session Status API
 * Trả về thông tin trạng thái session hiện tại
 */

require_once '../../../include/connect.php';
require_once '../../../core/SessionManager.php';
require_once '../../../app/Services/AuthService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $sessionManager = SessionManager::getInstance();
    $authService = new AuthService();
    
    // Kiểm tra session
    if (!$authService->check()) {
        echo json_encode([
            'success' => false,
            'message' => 'Session không hợp lệ',
            'time_remaining' => 0,
            'is_authenticated' => false
        ]);
        exit;
    }
    
    // Lấy thông tin session
    $sessionInfo = $sessionManager->getSessionInfo();
    
    if (!$sessionInfo) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể lấy thông tin session',
            'time_remaining' => 0,
            'is_authenticated' => false
        ]);
        exit;
    }
    
    // Kiểm tra warning
    $shouldShowWarning = $sessionManager->shouldShowWarning();
    
    echo json_encode([
        'success' => true,
        'is_authenticated' => true,
        'time_remaining' => $sessionInfo['time_remaining'],
        'created_at' => $sessionInfo['created_at'],
        'last_activity' => $sessionInfo['last_activity'],
        'is_warning' => $shouldShowWarning,
        'message' => $shouldShowWarning ? 'Phiên làm việc của bạn sắp hết hạn. Vui lòng lưu công việc và đăng nhập lại.' : null,
        'session_id' => $sessionInfo['session_id'],
        'user_id' => $sessionInfo['user_id']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage(),
        'time_remaining' => 0,
        'is_authenticated' => false
    ]);
}
