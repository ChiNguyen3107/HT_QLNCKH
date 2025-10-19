<?php
require 'include/connect.php';
require_once 'app/Services/AuthService.php';
require_once 'core/Logger.php';
require_once 'core/SessionManager.php';

$conn->set_charset('utf8mb4');

// Initialize services
$authService = new AuthService();
$sessionManager = SessionManager::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit;
    }

    // Authenticate using AuthService
    $result = $authService->authenticate($username, $password, $ipAddress);

    if ($result['success']) {
        $user = $result['user'];
        
        // Tạo secure session với SessionManager
        $userData = [
            'username' => $user['username'],
            'role' => $user['role'],
            'user_name' => $user['name'],
            'login_time' => time()
        ];
        
        // Set manager_id for research_manager
        if ($user['role'] === 'research_manager') {
            $userData['manager_id'] = $user['id'];
        }
        
        // Tạo secure session
        if ($sessionManager->createSecureSession($user['id'], $userData)) {
            // Update last_login timestamp
            updateLastLogin($user['id'], $user['role']);

            // Redirect based on role
            redirectByRole($user['role']);
            exit;
        } else {
            // Session creation failed
            header("Location: login.php?error=session_error");
            exit;
        }
    } else {
        // Handle failed login
        $errorParam = $result['locked'] ? 'account_locked' : 'invalid_credentials';
        header("Location: login.php?error={$errorParam}");
        exit;
    }
}

/**
 * Update last login timestamp
 */
function updateLastLogin($userId, $role) {
    global $conn;
    
    $table = '';
    $idColumn = '';
    
    switch ($role) {
        case 'admin':
        case 'research_manager':
            $table = 'user';
            $idColumn = 'USER_ID';
            break;
        case 'student':
            $table = 'sinh_vien';
            $idColumn = 'SV_MASV';
            break;
        case 'teacher':
            $table = 'giang_vien';
            $idColumn = 'GV_MAGV';
            break;
    }
    
    if ($table && $idColumn) {
        $sql = "UPDATE {$table} SET last_login = NOW() WHERE {$idColumn} = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Redirect user based on role
 */
function redirectByRole($role) {
    $baseUrl = '/NLNganh';
    
    switch ($role) {
        case 'admin':
            header("Location: {$baseUrl}/view/admin/admin_dashboard.php");
            break;
        case 'teacher':
            header("Location: {$baseUrl}/view/teacher/teacher_dashboard.php");
            break;
        case 'student':
            header("Location: {$baseUrl}/view/student/student_dashboard.php");
            break;
        case 'research_manager':
            header("Location: {$baseUrl}/view/research/research_dashboard.php");
            break;
        default:
            header("Location: {$baseUrl}/index.php");
            break;
    }
}
