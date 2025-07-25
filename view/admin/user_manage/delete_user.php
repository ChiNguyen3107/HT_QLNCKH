<?php
include '../../../include/session.php';
checkAdminRole();
include '../../../include/connect.php';

// Thiết lập header
header('Content-Type: application/json');

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$userType = $_POST['userType'] ?? '';
$userId = $_POST['userId'] ?? '';

if (empty($userType) || empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin người dùng']);
    exit;
}

// Bắt đầu transaction để đảm bảo tính nhất quán
$conn->begin_transaction();

try {
    // Xóa khỏi bảng người dùng
    $deleteUserQuery = "DELETE FROM nguoi_dung WHERE ND_MA = ?";
    $deleteUserStmt = $conn->prepare($deleteUserQuery);
    $deleteUserStmt->bind_param("s", $userId);
    $deleteUserStmt->execute();
    
    if ($userType === 'student') {
        // Xóa sinh viên
        $deleteQuery = "DELETE FROM sinh_vien WHERE SV_MASV = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("s", $userId);
        $deleteStmt->execute();
        
    } elseif ($userType === 'teacher') {
        // Xóa giảng viên
        $deleteQuery = "DELETE FROM giang_vien WHERE GV_MAGV = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("s", $userId);
        $deleteStmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công']);
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa người dùng: ' . $e->getMessage()]);
}
?>