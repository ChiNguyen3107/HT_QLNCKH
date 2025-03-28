<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\get_classes.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Đặt header trả về JSON
header('Content-Type: application/json');

// Kiểm tra và lấy tham số từ GET
if (!isset($_GET['departmentId']) || empty($_GET['departmentId'])) {
    echo json_encode(["error" => "Thiếu mã khoa (departmentId)."]);
    exit();
}

$departmentId = $_GET['departmentId'];
$courseId = $_GET['courseId'] ?? null; // Có thể không bắt buộc

// Chuẩn bị câu lệnh SQL
$sql = "SELECT LOP_MA, LOP_TEN, KH_NAM FROM lop WHERE DV_MADV = ?";
$params = [$departmentId];

if ($courseId) {
    $sql .= " AND KH_NAM = ?";
    $params[] = $courseId;
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
    exit();
}

// Gán tham số và thực thi câu lệnh
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Xử lý kết quả
$classes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    echo json_encode($classes);
} else {
    echo json_encode(["error" => "Lỗi truy vấn: " . $conn->error]);
}

// Đóng kết nối
$stmt->close();
$conn->close();
?>