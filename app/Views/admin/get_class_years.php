<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\get_class_years.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập
include '../../include/session.php';
checkAdminRole();
// Kết nối database
include '../../include/connect.php';

header('Content-Type: application/json');

// Kiểm tra tham số
if (!isset($_GET['dept_id']) || empty($_GET['dept_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu tham số khoa',
        'years' => []
    ]);
    exit;
}

$dept_id = $conn->real_escape_string($_GET['dept_id']);

// Lấy danh sách khóa học theo khoa từ bảng lớp
$years_query = "SELECT DISTINCT l.KH_NAM AS class_year
               FROM lop l
               WHERE l.DV_MADV = '$dept_id'
               ORDER BY l.KH_NAM DESC";

$result = $conn->query($years_query);
$years = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['class_year'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách khóa học thành công',
        'years' => $years
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy khóa học nào cho khoa đã chọn',
        'years' => []
    ]);
}
?>