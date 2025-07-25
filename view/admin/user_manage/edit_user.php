<?php
// Tắt hiển thị lỗi PHP trong output
error_reporting(0);
ini_set('display_errors', 0);

include '../../../include/session.php';
checkAdminRole();
include '../../../include/connect.php';

// Thiết lập header
header('Content-Type: application/json');

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý cập nhật người dùng
    // ... (giữ nguyên code xử lý POST)
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Lấy thông tin người dùng để hiển thị trong form chỉnh sửa
    $userType = $_GET['userType'] ?? '';
    $userId = $_GET['userId'] ?? '';

    if (empty($userType) || empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin người dùng']);
        exit;
    }

    if ($userType === 'student') {
        $query = "SELECT SV_MASV, SV_HOSV, SV_TENSV, SV_EMAIL, SV_SDT, SV_GIOITINH, SV_NGAYSINH, SV_DIACHI, LOP_MA 
                  FROM sinh_vien WHERE SV_MASV = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $userData = [
                'id' => $row['SV_MASV'],
                'firstName' => $row['SV_HOSV'],
                'lastName' => $row['SV_TENSV'],
                'email' => $row['SV_EMAIL'],
                'phone' => $row['SV_SDT'],
                'gender' => $row['SV_GIOITINH'],
                'birthDate' => $row['SV_NGAYSINH'],
                'address' => $row['SV_DIACHI'],
                'class' => $row['LOP_MA']
            ];

            echo json_encode(['success' => true, 'data' => $userData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin sinh viên']);
        }

    } elseif ($userType === 'teacher') {
        $query = "SELECT GV_MAGV, GV_HOGV, GV_TENGV, GV_EMAIL, GV_SDT, GV_GIOITINH, GV_NGAYSINH, GV_DIACHI, DV_MADV 
                  FROM giang_vien WHERE GV_MAGV = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $userData = [
                'id' => $row['GV_MAGV'],
                'firstName' => $row['GV_HOGV'],
                'lastName' => $row['GV_TENGV'],
                'email' => $row['GV_EMAIL'],
                'phone' => $row['GV_SDT'],
                'gender' => $row['GV_GIOITINH'],
                'birthDate' => $row['GV_NGAYSINH'],
                'address' => $row['GV_DIACHI'],
                'department' => $row['DV_MADV']
            ];

            echo json_encode(['success' => true, 'data' => $userData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin giảng viên']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Loại người dùng không hợp lệ']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
}
?>