<?php
// filepath: d:\xampp\htdocs\NLNganh\include\session.php
// File kiểm tra phiên đăng nhập để sử dụng ở đầu mỗi trang

// Hàm khởi tạo session nếu chưa được khởi tạo
function startSessionIfNotStarted() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Hàm kiểm tra đăng nhập - ĐÃ SỬA
function checkLogin() {
    startSessionIfNotStarted();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    // Chỉ kiểm tra người dùng đã đăng nhập, không quan tâm vai trò
}

// Hàm kiểm tra vai trò admin
function checkAdminRole() {
    startSessionIfNotStarted();
    if (!isset($_SESSION['user_id'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    if ($_SESSION['role'] != 'admin') {
        header("Location: /NLNganh/access_denied.php");
        exit;
    }
}

// Hàm kiểm tra vai trò giảng viên
function checkTeacherRole() {
    startSessionIfNotStarted();
    if (!isset($_SESSION['user_id'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    if ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin') {
        header("Location: /NLNganh/access_denied.php");
        exit;
    }
}

// Hàm kiểm tra vai trò sinh viên
function checkStudentRole() {
    startSessionIfNotStarted();
    if (!isset($_SESSION['user_id'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    if ($_SESSION['role'] != 'student') {
        header("Location: /NLNganh/access_denied.php");
        exit;
    }
}
?>