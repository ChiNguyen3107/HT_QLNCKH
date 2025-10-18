<?php
// API thống kê đơn giản
header('Content-Type: application/json');

// Kết nối database
include '../../include/connect.php';

// Kiểm tra tham số
$lop_ma = $_GET['lop_ma'] ?? 'DI2195A2';

echo json_encode([
    'success' => true,
    'statistics' => [
        'total_students' => 25,
        'students_with_projects' => 15,
        'completed_projects' => 8,
        'ongoing_projects' => 7
    ],
    'debug' => [
        'lop_ma' => $lop_ma,
        'message' => 'Dữ liệu mẫu - API hoạt động'
    ]
]);
?>
