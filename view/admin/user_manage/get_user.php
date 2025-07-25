<?php
// Tắt hiển thị lỗi để tránh ảnh hưởng tới dữ liệu JSON trả về
error_reporting(0);
ini_set('display_errors', 0);

// Include session
include '../../../include/session.php';
checkAdminRole();

// Kết nối CSDL
include '../../../include/connect.php';

// Đảm bảo phản hồi là JSON
header('Content-Type: application/json');

// Lấy thông tin từ request
$userType = isset($_GET['userType']) ? $_GET['userType'] : '';
$userId = isset($_GET['userId']) ? $_GET['userId'] : '';

if (empty($userType) || empty($userId)) {
    echo json_encode(['error' => true, 'message' => 'Thiếu thông tin người dùng']);
    exit;
}

// Lấy thông tin người dùng theo loại
if ($userType === 'student') {
    $query = "SELECT * FROM sinh_vien WHERE SV_MASV = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo json_encode(['error' => true, 'message' => 'Không tìm thấy sinh viên']);
    }
} elseif ($userType === 'teacher') {
    // Đảm bảo truy vấn lấy cả thông tin khoa
    $query = "SELECT * FROM giang_vien WHERE GV_MAGV = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Ghi log để debug
        error_log("Teacher data: " . print_r($row, true));
        echo json_encode($row);
    } else {
        echo json_encode(['error' => true, 'message' => 'Không tìm thấy giảng viên']);
    }
} else {
    echo json_encode(['error' => true, 'message' => 'Loại người dùng không hợp lệ']);
}
?>