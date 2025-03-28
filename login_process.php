<?php
session_start();
include 'include/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kiểm tra trong bảng `user`
    $sql = "SELECT * FROM user WHERE USERNAME = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['PASSWORD'])) {
            $_SESSION['user_id'] = $user['USER_ID'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['role'] = $user['ROLE'];

            // Chuyển hướng theo vai trò
            if ($user['ROLE'] == 'admin') {
                header("Location: view/admin/admin_dashboard.php");
            } elseif ($user['ROLE'] == 'teacher') {
                header("Location: view/teacher/teacher_dashboard.php");
            } elseif ($user['ROLE'] == 'student') {
                header("Location: view/student/student_dashboard.php");
            }
            exit();
        }
    }

    // Kiểm tra trong bảng `sinh_vien`
    $sql = "SELECT * FROM sinh_vien WHERE SV_EMAIL = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['SV_MATKHAU'])) {
            $_SESSION['user_id'] = $user['SV_MASV'];
            $_SESSION['username'] = $user['SV_EMAIL'];
            $_SESSION['role'] = 'student';
            header("Location: view/student/student_dashboard.php");
            exit();
        }
    }

    // Kiểm tra trong bảng `giang_vien`
    $sql = "SELECT * FROM giang_vien WHERE GV_EMAIL = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['GV_MATKHAU'])) {
            $_SESSION['user_id'] = $user['GV_MAGV'];
            $_SESSION['username'] = $user['GV_EMAIL'];
            $_SESSION['role'] = 'teacher';
            header("Location: view/teacher/teacher_dashboard.php");
            exit();
        }
    }

    // Đăng nhập thất bại
    header("Location: login.php?error=invalid_credentials");
    exit();
}
?>