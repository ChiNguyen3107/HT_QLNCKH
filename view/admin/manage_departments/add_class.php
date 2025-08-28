<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\add_class.php

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
$required_fields = ['departmentId', 'classCode', 'className', 'course'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(["error" => "Vui lòng điền đầy đủ thông tin bắt buộc."]);
        exit();
    }
}

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
    // Kiểm tra khoa có tồn tại không
    $deptCheckSql = "SELECT DV_MADV FROM khoa WHERE DV_MADV = ?";
    $deptCheckStmt = $conn->prepare($deptCheckSql);
    
    if ($deptCheckStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra khoa: " . $conn->error);
    }
    
    $deptCheckStmt->bind_param("s", $departmentId);
    $deptCheckStmt->execute();
    $deptResult = $deptCheckStmt->get_result();
    
    if ($deptResult->num_rows === 0) {
        echo json_encode(["error" => "Khoa không tồn tại."]);
        $deptCheckStmt->close();
        exit();
    }
    $deptCheckStmt->close();
    
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
    
    // Kiểm tra mã lớp đã tồn tại chưa
    $classCheckSql = "SELECT LOP_MA FROM lop WHERE LOP_MA = ?";
    $classCheckStmt = $conn->prepare($classCheckSql);
    
    if ($classCheckStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra lớp: " . $conn->error);
    }
    
    $classCheckStmt->bind_param("s", $classCode);
    $classCheckStmt->execute();
    $classResult = $classCheckStmt->get_result();
    
    if ($classResult->num_rows > 0) {
        echo json_encode(["error" => "Mã lớp '$classCode' đã tồn tại."]);
        $classCheckStmt->close();
        exit();
    }
    $classCheckStmt->close();
    
    // Thêm lớp học mới
    $insertSql = "INSERT INTO lop (LOP_MA, DV_MADV, KH_NAM, LOP_TEN, LOP_LOAICTDT) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    
    if ($insertStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh thêm: " . $conn->error);
    }
    
    $insertStmt->bind_param("sssss", $classCode, $departmentId, $course, $className, $classType);
    
    if ($insertStmt->execute()) {
        echo json_encode(["success" => "Thêm lớp học '$className' thành công."]);
    } else {
        throw new Exception("Lỗi thực thi câu lệnh: " . $insertStmt->error);
    }
    
    $insertStmt->close();
    
} catch (Exception $e) {
    error_log("Error in add_class.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi khi thêm lớp học: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
