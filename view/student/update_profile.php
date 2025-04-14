<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\update_profile.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Nếu không phải là phương thức POST, chuyển hướng về trang quản lý hồ sơ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_profile.php');
    exit;
}

// Lấy dữ liệu từ form
$sv_masv = $_POST['SV_MASV'];
$sv_hosv = $_POST['SV_HOSV'];
$sv_tensv = $_POST['SV_TENSV'];
$sv_email = $_POST['SV_EMAIL'];
$sv_sdt = $_POST['SV_SDT'];
$sv_ngaysinh = !empty($_POST['SV_NGAYSINH']) ? $_POST['SV_NGAYSINH'] : null;
$sv_gioitinh = isset($_POST['SV_GIOITINH']) ? $_POST['SV_GIOITINH'] : null;
$sv_diachi = !empty($_POST['SV_DIACHI']) ? $_POST['SV_DIACHI'] : null;

// Xác thực dữ liệu
$errors = [];

// Xác thực email
if (empty($sv_email) || !filter_var($sv_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email không hợp lệ";
}

// Xác thực số điện thoại (10-11 số)
if (!empty($sv_sdt) && !preg_match('/^[0-9]{10,11}$/', $sv_sdt)) {
    $errors[] = "Số điện thoại phải có 10-11 chữ số";
}

// Xác thực họ và tên
if (empty($sv_hosv) || empty($sv_tensv)) {
    $errors[] = "Họ và tên không được để trống";
}

// Nếu có lỗi, lưu vào session và quay lại trang quản lý hồ sơ
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    header('Location: manage_profile.php');
    exit;
}

// Cập nhật thông tin sinh viên
$sql = "UPDATE sinh_vien SET 
        SV_HOSV = ?, 
        SV_TENSV = ?, 
        SV_EMAIL = ?, 
        SV_SDT = ?, 
        SV_NGAYSINH = ?, 
        SV_GIOITINH = ?, 
        SV_DIACHI = ? 
        WHERE SV_MASV = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error_messages'] = ["Lỗi SQL: " . $conn->error];
    header('Location: manage_profile.php');
    exit;
}

$stmt->bind_param("sssssiss", $sv_hosv, $sv_tensv, $sv_email, $sv_sdt, $sv_ngaysinh, $sv_gioitinh, $sv_diachi, $sv_masv);
$success = $stmt->execute();

if ($success) {
    $_SESSION['success_message'] = "Cập nhật thông tin thành công!";
} else {
    $_SESSION['error_messages'] = ["Không thể cập nhật thông tin: " . $stmt->error];
}

// Chuyển hướng trở lại trang quản lý hồ sơ
header('Location: manage_profile.php');
exit;
?>