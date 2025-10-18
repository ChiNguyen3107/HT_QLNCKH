<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\get_notification_count.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin quản lý nghiên cứu
$manager_id = $_SESSION['user_id'];

// Kiểm tra nếu bảng thông báo tồn tại
$table_check = $conn->query("SHOW TABLES LIKE 'thong_bao'");
if ($table_check->num_rows == 0) {
    // Trả về 0 nếu bảng không tồn tại
    header('Content-Type: application/json');
    echo json_encode(['count' => 0, 'error' => 'Table not found']);
    exit;
}

// Đếm số thông báo chưa đọc
$unread_count = 0;
$unread_count_sql = "SELECT COUNT(*) as unread FROM thong_bao 
                    WHERE TB_DANHDOC = 0 
                    AND (QL_MA = ? OR (TB_LOAI = 'Hệ thống' AND QL_MA IS NULL))";
$stmt = $conn->prepare($unread_count_sql);
if ($stmt) {
    $stmt->bind_param("s", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = $result->fetch_assoc()['unread'];
    $stmt->close();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['count' => $unread_count]);
?>
