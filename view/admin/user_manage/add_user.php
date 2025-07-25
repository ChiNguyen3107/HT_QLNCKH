<?php
include '../../../include/session.php';
checkAdminRole();
include '../../../include/connect.php';

// Tắt hiển thị lỗi PHP trực tiếp, thay vào đó trả về lỗi dạng JSON
error_reporting(0);
ini_set('display_errors', 0);

// Thiết lập header
header('Content-Type: application/json');

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Lấy dữ liệu từ POST request
$userType = $_POST['userType'] ?? '';
$addId = $_POST['addId'] ?? '';
$addEmail = $_POST['addEmail'] ?? '';
$addFirstName = $_POST['addFirstName'] ?? '';
$addLastName = $_POST['addLastName'] ?? '';
$addPassword = $_POST['addPassword'] ?? '';
$addPhone = $_POST['addPhone'] ?? '';
$addGender = $_POST['addGender'] ?? '';

// Kiểm tra dữ liệu đầu vào
if (empty($userType) || empty($addId) || empty($addEmail) || empty($addFirstName) || 
    empty($addLastName) || empty($addPassword)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
    exit;
}

// Mã hóa mật khẩu
$hashedPassword = password_hash($addPassword, PASSWORD_DEFAULT);

// Kiểm tra loại người dùng và thực hiện thêm tương ứng
if ($userType === 'student') {
    // Lấy thông tin bổ sung cho sinh viên
    $addClass = $_POST['addClass'] ?? '';
    
    // Kiểm tra xem sinh viên đã tồn tại chưa
    $checkQuery = "SELECT SV_MASV FROM sinh_vien WHERE SV_MASV = ?";
    $checkStmt = $conn->prepare($checkQuery);
    
    if ($checkStmt === false) {
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
        exit;
    }
    
    $checkStmt->bind_param("s", $addId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Mã sinh viên đã tồn tại trong hệ thống']);
        exit;
    }
    
    // Kiểm tra email đã tồn tại chưa
    $checkEmailQuery = "SELECT SV_EMAIL FROM sinh_vien WHERE SV_EMAIL = ?";
    $checkEmailStmt = $conn->prepare($checkEmailQuery);
    
    if ($checkEmailStmt === false) {
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
        exit;
    }
    
    $checkEmailStmt->bind_param("s", $addEmail);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    
    if ($checkEmailResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
        exit;
    }
    
    // Thêm sinh viên mới
    $insertQuery = "INSERT INTO sinh_vien (SV_MASV, SV_HOSV, SV_TENSV, SV_MATKHAU, SV_EMAIL, SV_SDT, SV_GIOITINH, LOP_MA) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    // Kiểm tra lỗi prepare
    if ($insertStmt === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error,
            'query' => $insertQuery
        ]);
        exit;
    }
    
    $insertStmt->bind_param("ssssssss", $addId, $addFirstName, $addLastName, $hashedPassword, $addEmail, $addPhone, $addGender, $addClass);
    
    if ($insertStmt->execute()) {
        // Thêm vào bảng người dùng để phân quyền
        $addUserQuery = "INSERT INTO nguoi_dung (ND_MA, ND_MATKHAU, ND_VAITRO) VALUES (?, ?, 'Sinh viên')";
        $addUserStmt = $conn->prepare($addUserQuery);
        
        if ($addUserStmt === false) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm quyền người dùng: ' . $conn->error]);
            exit;
        }
        
        $addUserStmt->bind_param("ss", $addId, $hashedPassword);
        $addUserStmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Thêm sinh viên mới thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm sinh viên: ' . $insertStmt->error]);
    }
    
} elseif ($userType === 'teacher') {
    // Lấy thông tin bổ sung cho giảng viên
    $addDepartment = $_POST['addDepartment'] ?? '';
    
    // Kiểm tra xem giảng viên đã tồn tại chưa
    $checkQuery = "SELECT GV_MAGV FROM giang_vien WHERE GV_MAGV = ?";
    $checkStmt = $conn->prepare($checkQuery);
    
    if ($checkStmt === false) {
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
        exit;
    }
    
    $checkStmt->bind_param("s", $addId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Mã giảng viên đã tồn tại trong hệ thống']);
        exit;
    }
    
    // Kiểm tra email đã tồn tại chưa
    $checkEmailQuery = "SELECT GV_EMAIL FROM giang_vien WHERE GV_EMAIL = ?";
    $checkEmailStmt = $conn->prepare($checkEmailQuery);
    
    if ($checkEmailStmt === false) {
        echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
        exit;
    }
    
    $checkEmailStmt->bind_param("s", $addEmail);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    
    if ($checkEmailResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
        exit;
    }
    
    // Thêm giảng viên mới
    $insertQuery = "INSERT INTO giang_vien (GV_MAGV, GV_HOGV, GV_TENGV, GV_MATKHAU, GV_EMAIL, GV_SDT, GV_GIOITINH, DV_MADV) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    // Kiểm tra lỗi prepare
    if ($insertStmt === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error,
            'query' => $insertQuery
        ]);
        exit;
    }
    
    $insertStmt->bind_param("ssssssss", $addId, $addFirstName, $addLastName, $hashedPassword, $addEmail, $addPhone, $addGender, $addDepartment);
    
    if ($insertStmt->execute()) {
        // Thêm vào bảng người dùng để phân quyền
        $addUserQuery = "INSERT INTO nguoi_dung (ND_MA, ND_MATKHAU, ND_VAITRO) VALUES (?, ?, 'Giảng viên')";
        $addUserStmt = $conn->prepare($addUserQuery);
        
        if ($addUserStmt === false) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm quyền người dùng: ' . $conn->error]);
            exit;
        }
        
        $addUserStmt->bind_param("ss", $addId, $hashedPassword);
        $addUserStmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Thêm giảng viên mới thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm giảng viên: ' . $insertStmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Loại người dùng không hợp lệ']);
}
?>