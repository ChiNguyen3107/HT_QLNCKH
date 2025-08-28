<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\add_course.php

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
    echo json_encode(["error" => "Vui lòng nhập tên khóa học."]);
    exit();
}

$courseYear = trim($_POST['courseYear']);

// Validate độ dài
if (strlen($courseYear) > 9) {
    echo json_encode(["error" => "Tên khóa học không được vượt quá 9 ký tự."]);
    exit();
}

try {
    // Kiểm tra khóa học đã tồn tại chưa
    $checkSql = "SELECT KH_NAM FROM khoa_hoc WHERE KH_NAM = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if ($checkStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra: " . $conn->error);
    }
    
    $checkStmt->bind_param("s", $courseYear);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(["error" => "Khóa học '$courseYear' đã tồn tại."]);
        $checkStmt->close();
        exit();
    }
    
    $checkStmt->close();
    
    // Thêm khóa học mới
    $insertSql = "INSERT INTO khoa_hoc (KH_NAM) VALUES (?)";
    $insertStmt = $conn->prepare($insertSql);
    
    if ($insertStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh thêm: " . $conn->error);
    }
    
    $insertStmt->bind_param("s", $courseYear);
    
    if ($insertStmt->execute()) {
        echo json_encode(["success" => "Thêm khóa học '$courseYear' thành công."]);
    } else {
        throw new Exception("Lỗi thực thi câu lệnh: " . $insertStmt->error);
    }
    
    $insertStmt->close();
    
} catch (Exception $e) {
    error_log("Error in add_course.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi khi thêm khóa học: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
