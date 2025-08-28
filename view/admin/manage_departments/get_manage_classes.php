<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\get_manage_classes.php

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

try {
    // Chuẩn bị câu lệnh SQL với subquery để đếm sinh viên
    $sql = "SELECT l.LOP_MA, l.LOP_TEN, l.KH_NAM, l.LOP_LOAICTDT,
                   (SELECT COUNT(*) FROM sinh_vien sv WHERE sv.LOP_MA = l.LOP_MA) as student_count
            FROM lop l 
            WHERE l.DV_MADV = ?";
    $params = [$departmentId];
    
    if ($courseId) {
        $sql .= " AND l.KH_NAM = ?";
        $params[] = $courseId;
    }
    
    $sql .= " ORDER BY l.KH_NAM DESC, l.LOP_MA ASC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
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
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error in get_manage_classes.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi khi tải danh sách lớp học: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
