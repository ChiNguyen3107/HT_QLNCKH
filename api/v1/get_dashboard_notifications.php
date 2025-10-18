<?php
// API để lấy thông báo cho dashboard
session_start();
require_once '../include/database.php';
require_once '../include/session.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        throw new Exception('Chưa đăng nhập');
    }

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

    // Kiểm tra bảng thông báo có tồn tại không
    $check_table = $conn->query("SHOW TABLES LIKE 'thong_bao'");
    if (!$check_table || $check_table->num_rows == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Bảng thông báo chưa được tạo',
            'data' => [
                'count' => 0,
                'notifications' => []
            ]
        ]);
        exit;
    }

    // Kiểm tra cột TB_MUCTIEU có tồn tại không
    $check_column = $conn->query("SHOW COLUMNS FROM thong_bao LIKE 'TB_MUCTIEU'");
    if (!$check_column || $check_column->num_rows == 0) {
        // Nếu không có cột TB_MUCTIEU, lấy tất cả thông báo
        $sql = "SELECT TB_ID, TB_TIEUDE, TB_NOIDUNG, TB_NGAYTAO, TB_DANHDOC 
                FROM thong_bao 
                ORDER BY TB_NGAYTAO DESC 
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
    } else {
        // Có cột TB_MUCTIEU, lọc theo target
        $sql = "SELECT TB_ID, TB_TIEUDE, TB_NOIDUNG, TB_NGAYTAO, TB_DANHDOC 
                FROM thong_bao 
                WHERE TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?
                ORDER BY TB_NGAYTAO DESC 
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $user_role, $limit);
    }

    if (!$stmt) {
        throw new Exception('Lỗi prepare query: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();

    // Đếm tổng số thông báo chưa đọc
    if (!$check_column || $check_column->num_rows == 0) {
        $count_sql = "SELECT COUNT(*) as unread_count FROM thong_bao WHERE TB_DANHDOC = 0";
        $count_stmt = $conn->prepare($count_sql);
    } else {
        $count_sql = "SELECT COUNT(*) as unread_count FROM thong_bao 
                      WHERE TB_DANHDOC = 0 AND (TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?)";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param('s', $user_role);
    }

    if (!$count_stmt) {
        throw new Exception('Lỗi prepare count query: ' . $conn->error);
    }

    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $unread_count = $count_result->fetch_assoc()['unread_count'] ?? 0;
    $count_stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Lấy thông báo thành công',
        'data' => [
            'count' => (int)$unread_count,
            'notifications' => $notifications
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => [
            'count' => 0,
            'notifications' => []
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>





