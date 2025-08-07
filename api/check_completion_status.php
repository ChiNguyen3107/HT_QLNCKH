<?php
session_start();
require_once '../include/config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['MANGUOIDUNG'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $project_id = $_POST['project_id'] ?? '';
        
        if (empty($project_id)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu mã đề tài']);
            exit;
        }
        
        // Include file xử lý completion
        require_once '../check_project_completion.php';
        
        // Kiểm tra yêu cầu hoàn thành
        $completion_data = checkProjectCompletionRequirements($conn, $project_id);
        
        echo json_encode([
            'success' => true,
            'data' => $completion_data
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
}
?>
