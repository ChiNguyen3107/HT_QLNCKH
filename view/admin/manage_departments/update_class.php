<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\update_class.php

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
$required_fields = ['originalCode', 'departmentId', 'classCode', 'className', 'course'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(["error" => "Vui lòng điền đầy đủ thông tin bắt buộc."]);
        exit();
    }
}

$originalCode = trim($_POST['originalCode']);
$departmentId = trim($_POST['departmentId']);
$classCode = trim($_POST['classCode']);
$className = trim($_POST['className']);
$course = trim($_POST['course']);
$classType = isset($_POST['classType']) ? trim($_POST['classType']) : null;

// Validate độ dài
if (strlen($classCode) > 8) {
    echo json_encode(["error" => "Mã lớp không được vượt quá 8 ký tự."]);
    exit();
}

if (strlen($className) > 50) {
    echo json_encode(["error" => "Tên lớp không được vượt quá 50 ký tự."]);
    exit();
}

try {
    // Kiểm tra lớp học có tồn tại không
    $checkSql = "SELECT LOP_MA FROM lop WHERE LOP_MA = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if ($checkStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra: " . $conn->error);
    }
    
    $checkStmt->bind_param("s", $originalCode);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(["error" => "Lớp học không tồn tại."]);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();
    
    // Nếu mã lớp thay đổi, kiểm tra mã mới có bị trùng không
    if ($originalCode !== $classCode) {
        $duplicateCheckSql = "SELECT LOP_MA FROM lop WHERE LOP_MA = ?";
        $duplicateCheckStmt = $conn->prepare($duplicateCheckSql);
        
        if ($duplicateCheckStmt === false) {
            throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra trùng lặp: " . $conn->error);
        }
        
        $duplicateCheckStmt->bind_param("s", $classCode);
        $duplicateCheckStmt->execute();
        $duplicateResult = $duplicateCheckStmt->get_result();
        
        if ($duplicateResult->num_rows > 0) {
            echo json_encode(["error" => "Mã lớp '$classCode' đã tồn tại."]);
            $duplicateCheckStmt->close();
            exit();
        }
        $duplicateCheckStmt->close();
    }
    
    // Kiểm tra khóa học có tồn tại không
    $courseCheckSql = "SELECT KH_NAM FROM khoa_hoc WHERE KH_NAM = ?";
    $courseCheckStmt = $conn->prepare($courseCheckSql);
    
    if ($courseCheckStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra khóa học: " . $conn->error);
    }
    
    $courseCheckStmt->bind_param("s", $course);
    $courseCheckStmt->execute();
    $courseResult = $courseCheckStmt->get_result();
    
    if ($courseResult->num_rows === 0) {
        echo json_encode(["error" => "Khóa học không tồn tại."]);
        $courseCheckStmt->close();
        exit();
    }
    $courseCheckStmt->close();
    
    // Cập nhật lớp học
    $updateSql = "UPDATE lop SET LOP_MA = ?, DV_MADV = ?, KH_NAM = ?, LOP_TEN = ?, LOP_LOAICTDT = ? WHERE LOP_MA = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    if ($updateStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh cập nhật: " . $conn->error);
    }
    
    $updateStmt->bind_param("ssssss", $classCode, $departmentId, $course, $className, $classType, $originalCode);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode(["success" => "Cập nhật thông tin lớp học thành công."]);
        } else {
            echo json_encode(["success" => "Không có thay đổi nào được thực hiện."]);
        }
    } else {
        throw new Exception("Lỗi thực thi câu lệnh: " . $updateStmt->error);
    }
    
    $updateStmt->close();
    
} catch (Exception $e) {
    error_log("Error in update_class.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi khi cập nhật lớp học: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
