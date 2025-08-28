<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\get_class.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Đặt header trả về JSON
header('Content-Type: application/json');

// Kiểm tra và lấy tham số từ GET
if (!isset($_GET['classId']) || empty($_GET['classId'])) {
    echo json_encode(["error" => "Thiếu mã lớp (classId)."]);
    exit();
}

$classId = $_GET['classId'];

try {
    // Chuẩn bị câu lệnh SQL
    $sql = "SELECT LOP_MA, DV_MADV, KH_NAM, LOP_TEN, LOP_LOAICTDT FROM lop WHERE LOP_MA = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
    }
    
    // Gán tham số và thực thi câu lệnh
    $stmt->bind_param("s", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Xử lý kết quả
    if ($result && $result->num_rows > 0) {
        $class = $result->fetch_assoc();
        echo json_encode($class);
    } else {
        echo json_encode(["error" => "Không tìm thấy lớp học."]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error in get_class.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi khi tải thông tin lớp học: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
