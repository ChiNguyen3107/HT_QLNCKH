<?php
// filepath: d:\xampp\htdocs\NLNganh\include\session.php
/**
 * Quản lý phiên đăng nhập và bảo mật cải tiến
 */

// Đảm bảo config.php được nạp
require_once 'config.php';

// Hằng số vai trò người dùng
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher'); 
define('ROLE_STUDENT', 'student');
define('ROLE_RESEARCH_MANAGER', 'research_manager');

// Thời gian timeout phiên lấy từ config.php
// SESSION_TIMEOUT đã được định nghĩa trong config.php

// Hàm khởi tạo session nếu chưa được khởi tạo
function startSessionIfNotStarted() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Hàm kiểm tra đăng nhập và bảo mật phiên
function checkLogin() {
    startSessionIfNotStarted();
    
    // Kiểm tra tồn tại user_id và role
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    
    // Kiểm tra timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Đăng xuất
        logoutUser();
        header("Location: /NLNganh/login.php?timeout=1");
        exit;
    }
    
    // Kiểm tra session hijacking
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        logoutUser();
        header("Location: /NLNganh/login.php?security=1");
        exit;
    }
    
    // Cập nhật thời gian hoạt động
    $_SESSION['last_activity'] = time();
}

// Hàm kiểm tra vai trò admin
function checkAdminRole() {
    startSessionIfNotStarted();
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    
    // Kiểm tra vai trò
    if ($_SESSION['role'] != ROLE_ADMIN) {
        header("Location: /NLNganh/access_denied.php");
        exit;
    }
    
    // Kiểm tra timeout và bảo mật
    checkLogin();
}

// Hàm kiểm tra vai trò giảng viên
function checkTeacherRole() {
    startSessionIfNotStarted();
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    
    // Kiểm tra vai trò (giảng viên hoặc admin)
    if ($_SESSION['role'] != ROLE_TEACHER && $_SESSION['role'] != ROLE_ADMIN) {
        header("Location: /NLNganh/access_denied.php");
        exit;
    }
    
    // Kiểm tra timeout và bảo mật
    checkLogin();
}

// Hàm kiểm tra vai trò sinh viên
function checkStudentRole() {
    startSessionIfNotStarted();
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    if ($_SESSION['role'] != 'student') {
        header("Location: /NLNganh/access_denied.php");
        exit;
    }
}

// Hàm kiểm tra vai trò quản lý nghiên cứu
function checkResearchManagerRole() {
    startSessionIfNotStarted();
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        header("Location: /NLNganh/login.php");
        exit;
    }
    // Kiểm tra vai trò (research_manager hoặc admin)
    if ($_SESSION['role'] != ROLE_RESEARCH_MANAGER && $_SESSION['role'] != ROLE_ADMIN) {
        header("Location: /NLNganh/access_denied.php");
        exit;
    }
    
    // Kiểm tra timeout và bảo mật
    checkLogin();
}

// Hàm kiểm tra nếu người dùng đăng nhập là quản lý nghiên cứu (không redirect)
function isResearchManagerLoggedIn() {
    startSessionIfNotStarted();
    // Kiểm tra đăng nhập và vai trò
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != ROLE_RESEARCH_MANAGER && $_SESSION['role'] != ROLE_ADMIN)) {
        return false;
    }
    return true;
}

// Hàm đăng xuất người dùng
function logoutUser() {
    startSessionIfNotStarted();
    
    // Ghi log nếu cần
    
    // Xóa tất cả biến session
    $_SESSION = array();
    
    // Xóa cookie phiên
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hủy phiên
    session_destroy();
}

// Hàm thiết lập đăng nhập
function setLogin($user_id, $role, $extra_data = []) {
    startSessionIfNotStarted();
    
    // Thiết lập thông tin cơ bản
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Thiết lập thông tin thêm nếu có
    foreach($extra_data as $key => $value) {
        $_SESSION[$key] = $value;
    }
    
    // Tái tạo ID phiên
    session_regenerate_id(true);
}

// Hàm mã hóa mật khẩu
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Hàm xác thực mật khẩu
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Ghi log hoạt động sử dụng hàm từ functions.php
// Hàm logActivity() đã được định nghĩa trong functions.php
?>