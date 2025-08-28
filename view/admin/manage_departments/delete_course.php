<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\delete_course.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Đặt header trả về JSON
header('Content-Type: application/json');

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Phương thức không được hỗ trợ."]);
    exit();
}

// Kiểm tra và lấy dữ liệu từ POST
if (!isset($_POST['courseYear']) || empty(trim($_POST['courseYear']))) {
    echo json_encode(["error" => "Thiếu thông tin khóa học cần xóa."]);
    exit();
}

$courseYear = trim($_POST['courseYear']);

try {
    // Kiểm tra khóa học có tồn tại không
    $checkSql = "SELECT KH_NAM FROM khoa_hoc WHERE KH_NAM = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if ($checkStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra: " . $conn->error);
    }
    
    $checkStmt->bind_param("s", $courseYear);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(["error" => "Khóa học '$courseYear' không tồn tại."]);
        $checkStmt->close();
        exit();
    }
    
    $checkStmt->close();
    
    // Kiểm tra có lớp học nào đang sử dụng khóa học này không
    $classCheckSql = "SELECT COUNT(*) as count FROM lop WHERE KH_NAM = ?";
    $classCheckStmt = $conn->prepare($classCheckSql);
    
    if ($classCheckStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra lớp: " . $conn->error);
    }
    
    $classCheckStmt->bind_param("s", $courseYear);
    $classCheckStmt->execute();
    $classResult = $classCheckStmt->get_result();
    $classCount = $classResult->fetch_assoc()['count'];
    $classCheckStmt->close();
    
    if ($classCount > 0) {
        echo json_encode(["error" => "Không thể xóa khóa học '$courseYear' vì đang có $classCount lớp học sử dụng."]);
        exit();
    }
    
    // Xóa khóa học
    $deleteSql = "DELETE FROM khoa_hoc WHERE KH_NAM = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    
    if ($deleteStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh xóa: " . $conn->error);
    }
    
    $deleteStmt->bind_param("s", $courseYear);
    
    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            echo json_encode(["success" => "Xóa khóa học '$courseYear' thành công."]);
        } else {
            echo json_encode(["error" => "Không có khóa học nào được xóa."]);
        }
    } else {
        throw new Exception("Lỗi thực thi câu lệnh: " . $deleteStmt->error);
    }
    
    $deleteStmt->close();
    
} catch (Exception $e) {
    error_log("Error in delete_course.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi khi xóa khóa học: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
