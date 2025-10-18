<?php
require_once '../include/connect.php';
require_once '../include/session.php';

header('Content-Type: application/json');

if (!isResearchManagerLoggedIn()) {
    error_log('Unauthorized access: User is not logged in as research manager'); // Ghi log chi tiết
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

// Sử dụng user_id khi manager_id không có sẵn
$manager_id = isset($_SESSION['manager_id']) ? $_SESSION['manager_id'] : $_SESSION['user_id'];

try {
    $sql = "SELECT COUNT(*) as unread FROM thong_bao 
            WHERE TB_DANHDOC = 0 
            AND (QL_MA = ? OR (TB_LOAI = 'Hệ thống' AND QL_MA IS NULL))";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    $stmt->bind_param("s", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = $result->fetch_assoc()['unread'] ?? 0;

    echo json_encode(['unread_count' => $unread_count]);
} catch (Exception $e) {
    error_log($e->getMessage()); // Log lỗi để kiểm tra
    echo json_encode(['error' => 'Failed to fetch notifications']);
    http_response_code(500);
}
