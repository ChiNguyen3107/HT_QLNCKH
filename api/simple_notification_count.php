<?php
/**
 * Simple Notification Count API
 * API đơn giản để lấy số thông báo chưa đọc
 */

// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Include database
    require_once '../include/connect.php';
    
    // Kiểm tra session đơn giản
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Session không tồn tại',
            'debug' => [
                'session_status' => session_status(),
                'session_id' => session_id(),
                'has_session_data' => !empty($_SESSION)
            ]
        ]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'unknown';
    
    // Query đơn giản để đếm thông báo
    $sql = "SELECT COUNT(*) as count FROM thong_bao 
            WHERE TB_DANHDOC = 0 
            AND (TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $user_role);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'count' => (int)$count
        ],
        'debug' => [
            'user_id' => $user_id,
            'user_role' => $user_role,
            'query_executed' => true
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
}
?>

