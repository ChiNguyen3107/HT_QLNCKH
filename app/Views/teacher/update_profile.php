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
$gv_magv = $_POST['GV_MAGV'];
$gv_hogv = $_POST['GV_HOGV'];
$gv_tengv = $_POST['GV_TENGV'];
$gv_email = $_POST['GV_EMAIL'];
$gv_sdt = $_POST['GV_SDT'] ?? null;
$gv_diachi = $_POST['GV_DIACHI'] ?? null;
$gv_ngaysinh = !empty($_POST['GV_NGAYSINH']) ? $_POST['GV_NGAYSINH'] : null;
$gv_gioitinh = $_POST['GV_GIOITINH'] ?? null;

// Xác thực dữ liệu cơ bản
$errors = [];

// Kiểm tra email
if (empty($gv_email) || !filter_var($gv_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email không hợp lệ";
}

// Kiểm tra số điện thoại
if (!empty($gv_sdt) && !preg_match('/^[0-9]{10,11}$/', $gv_sdt)) {
    $errors[] = "Số điện thoại phải có 10-11 chữ số";
}

// Kiểm tra họ tên
if (empty($gv_hogv) || empty($gv_tengv)) {
    $errors[] = "Họ và tên không được để trống";
}

// Nếu có lỗi, quay lại trang hồ sơ và hiển thị thông báo
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header('Location: manage_profile.php');
    exit;
}

// Cập nhật thông tin giảng viên
$sql = "UPDATE giang_vien SET 
        GV_HOGV = ?, 
        GV_TENGV = ?, 
        GV_EMAIL = ?, 
        GV_SDT = ?, 
        GV_DIACHI = ?, 
        GV_NGAYSINH = ?,
        GV_GIOITINH = ?
        WHERE GV_MAGV = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi SQL: " . $conn->error;
    header('Location: manage_profile.php');
    exit;
}

$stmt->bind_param("ssssssis", 
    $gv_hogv, 
    $gv_tengv, 
    $gv_email, 
    $gv_sdt, 
    $gv_diachi, 
    $gv_ngaysinh, 
    $gv_gioitinh,
    $gv_magv
);
$success = $stmt->execute();

if ($success) {
    $_SESSION['success_message'] = "Cập nhật thông tin thành công!";
    
    // Cập nhật tên hiển thị trong session
    $_SESSION['user_name'] = $gv_hogv . ' ' . $gv_tengv;
} else {
    $_SESSION['error_message'] = "Không thể cập nhật thông tin: " . $stmt->error;
}

// Chuyển hướng về trang hồ sơ
header('Location: manage_profile.php');
exit;
?>