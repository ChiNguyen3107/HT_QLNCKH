<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\delete_class.php

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
if (!isset($_POST['classCode']) || empty(trim($_POST['classCode']))) {
    echo json_encode(["error" => "Thiếu thông tin mã lớp cần xóa."]);
    exit();
}

$classCode = trim($_POST['classCode']);

try {
    // Kiểm tra lớp học có tồn tại không
    $checkSql = "SELECT LOP_MA, LOP_TEN FROM lop WHERE LOP_MA = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if ($checkStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra: " . $conn->error);
    }
    
    $checkStmt->bind_param("s", $classCode);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(["error" => "Lớp học không tồn tại."]);
        $checkStmt->close();
        exit();
    }
    
    $classInfo = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    // Kiểm tra có sinh viên nào trong lớp không
    $studentCheckSql = "SELECT COUNT(*) as count FROM sinh_vien WHERE LOP_MA = ?";
    $studentCheckStmt = $conn->prepare($studentCheckSql);
    
    if ($studentCheckStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra sinh viên: " . $conn->error);
    }
    
    $studentCheckStmt->bind_param("s", $classCode);
    $studentCheckStmt->execute();
    $studentResult = $studentCheckStmt->get_result();
    $studentCount = $studentResult->fetch_assoc()['count'];
    $studentCheckStmt->close();
    
    if ($studentCount > 0) {
        echo json_encode(["error" => "Không thể xóa lớp học '{$classInfo['LOP_TEN']}' vì đang có $studentCount sinh viên trong lớp."]);
        exit();
    }
    
    // Xóa lớp học
    $deleteSql = "DELETE FROM lop WHERE LOP_MA = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    
    if ($deleteStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh xóa: " . $conn->error);
    }
    
    $deleteStmt->bind_param("s", $classCode);
    
    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            echo json_encode(["success" => "Xóa lớp học '{$classInfo['LOP_TEN']}' thành công."]);
        } else {
            echo json_encode(["error" => "Không có lớp học nào được xóa."]);
        }
    } else {
        throw new Exception("Lỗi thực thi câu lệnh: " . $deleteStmt->error);
    }
    
    $deleteStmt->close();
    
} catch (Exception $e) {
    error_log("Error in delete_class.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi khi xóa lớp học: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
