<?php
// Bao gồm file session và kiểm tra quyền
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_profile.php');
    exit;
}

// Lấy thông tin từ form
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Kiểm tra mật khẩu mới và xác nhận mật khẩu
if ($new_password !== $confirm_password) {
    $_SESSION['error_message'] = "Mật khẩu mới và xác nhận mật khẩu không khớp!";
    header('Location: manage_profile.php');
    exit;
}

// Kiểm tra độ dài mật khẩu
if (strlen($new_password) < 6) {
    $_SESSION['error_message'] = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    header('Location: manage_profile.php');
    exit;
}

// Lấy mật khẩu hiện tại từ CSDL
$teacher_id = $_SESSION['user_id'];
$sql = "SELECT GV_MATKHAU FROM giang_vien WHERE GV_MAGV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi hệ thống: " . $conn->error;
    header('Location: manage_profile.php');
    exit;
}

$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Kiểm tra mật khẩu hiện tại
$password_verified = false;

// Thử với password_verify (nếu đã hash)
if (password_verify($current_password, $user['GV_MATKHAU'])) {
    $password_verified = true;
} 
// Thử với kiểm tra trực tiếp (nếu lưu ở dạng plain text)
else if ($current_password === $user['GV_MATKHAU']) {
    $password_verified = true;
}

if (!$password_verified) {
    $_SESSION['error_message'] = "Mật khẩu hiện tại không chính xác!";
    header('Location: manage_profile.php');
    exit;
}

// Hash mật khẩu mới và cập nhật vào CSDL
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);
$sql = "UPDATE giang_vien SET GV_MATKHAU = ? WHERE GV_MAGV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi hệ thống: " . $conn->error;
    header('Location: manage_profile.php');
    exit;
}

$stmt->bind_param("ss", $password_hash, $teacher_id);
$success = $stmt->execute();

if ($success) {
    $_SESSION['success_message'] = "Đổi mật khẩu thành công!";
} else {
    $_SESSION['error_message'] = "Không thể cập nhật mật khẩu: " . $stmt->error;
}

// Chuyển hướng về trang hồ sơ
header('Location: manage_profile.php');
exit;
?>