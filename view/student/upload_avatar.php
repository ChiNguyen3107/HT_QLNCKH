<?php
// Đường dẫn: d:\xampp\htdocs\NLNganh\view\student\upload_avatar.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra nếu không phải phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

// Kiểm tra nếu không có file được tải lên
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không có file được tải lên hoặc file lỗi']);
    exit;
}

// Lấy thông tin sinh viên
$user_id = $_SESSION['user_id'];

// Kiểm tra và tạo thư mục lưu trữ nếu chưa tồn tại
$upload_dir = '../../uploads/avatars/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Xử lý file tải lên
$file = $_FILES['avatar'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Kiểm tra định dạng file hợp lệ
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($file_ext, $allowed_extensions)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Chỉ chấp nhận file hình ảnh (jpg, jpeg, png, gif)'
    ]);
    exit;
}

// Tạo tên file mới để tránh trùng lặp
$new_file_name = $user_id . '_' . time() . '.' . $file_ext;
$file_path = $upload_dir . $new_file_name;

// Lưu file vào thư mục
if (move_uploaded_file($file_tmp, $file_path)) {
    // Cập nhật đường dẫn ảnh đại diện trong cơ sở dữ liệu
    
    // Kiểm tra xem có cột SV_AVATAR trong bảng sinh_vien không
    $check_column_sql = "SHOW COLUMNS FROM sinh_vien LIKE 'SV_AVATAR'";
    $check_result = $conn->query($check_column_sql);
    
    if ($check_result->num_rows == 0) {
        // Tạo cột SV_AVATAR nếu chưa tồn tại
        $create_column_sql = "ALTER TABLE sinh_vien ADD COLUMN SV_AVATAR VARCHAR(255) NULL";
        if (!$conn->query($create_column_sql)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Không thể cập nhật cấu trúc cơ sở dữ liệu: ' . $conn->error
            ]);
            exit;
        }
    }
    
    // Cập nhật đường dẫn ảnh đại diện
    $relative_path = 'uploads/avatars/' . $new_file_name;
    $update_sql = "UPDATE sinh_vien SET SV_AVATAR = ? WHERE SV_MASV = ?";
    $stmt = $conn->prepare($update_sql);
    
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Lỗi SQL: ' . $conn->error
        ]);
        exit;
    }
    
    $stmt->bind_param('ss', $relative_path, $user_id);
    $success = $stmt->execute();
    
    if ($success) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Cập nhật ảnh đại diện thành công', 
            'avatarUrl' => '/' . $relative_path
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Không thể cập nhật ảnh đại diện: ' . $stmt->error
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Không thể tải lên file, vui lòng thử lại'
    ]);
}
?>
