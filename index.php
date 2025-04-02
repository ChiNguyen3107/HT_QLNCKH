<?php
// filepath: d:\xampp\htdocs\NLNganh\index.php
// Trang chính - kiểm tra đăng nhập và chuyển hướng thích hợp
session_start();

// Kiểm tra người dùng đã đăng nhập chưa
if(isset($_SESSION['user_id'])) {
    // Nếu đã đăng nhập, kiểm tra vai trò và chuyển hướng đến trang tương ứng
    if($_SESSION['role'] == 'admin') {
        header("Location: view/admin/admin_dashboard.php"); // Sửa tên file nếu cần
    } elseif($_SESSION['role'] == 'student') {
        header("Location: view/student/student_dashboard.php"); // Sửa tên file nếu cần
    } elseif($_SESSION['role'] == 'teacher') {
        header("Location: view/teacher/teacher_dashboard.php"); // Sửa tên file nếu cần
    } else {
        header("Location: login.php?error=role");
    }
} else {
    // Nếu chưa đăng nhập, chuyển hướng đến trang đăng nhập
    header("Location: login.php");
}
exit;
?>