<?php
include 'include/connect.php';

// Thông tin giảng viên mới
$gv_magv = 'GV000001'; // Mã giảng viên
$dv_madv = 'KH011'; // Mã đơn vị (khoa)
$gv_hogv = 'Nguyen'; // Họ giảng viên
$gv_tengv = 'Van A'; // Tên giảng viên
$gv_email = 'nguyenvana@example.com'; // Email giảng viên
$gv_matkhau = password_hash('11111', PASSWORD_DEFAULT); // Mật khẩu giảng viên (đã mã hóa)

// Thêm giảng viên vào bảng giang_vien
$sql = "INSERT INTO giang_vien (GV_MAGV, DV_MADV, GV_HOGV, GV_TENGV, GV_EMAIL, GV_MATKHAU) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("ssssss", $gv_magv, $dv_madv, $gv_hogv, $gv_tengv, $gv_email, $gv_matkhau);
$stmt->execute();

// Thêm tài khoản vào bảng user
$username = $gv_email;
$password = $gv_matkhau;
$role = 'giang_vien';
$sql = "INSERT INTO user (USERNAME, PASSWORD, ROLE) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("sss", $username, $password, $role);
$stmt->execute();

echo "Tài khoản giảng viên đã được thêm thành công.";

$stmt->close();
$conn->close();
?>