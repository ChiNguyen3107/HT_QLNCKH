<?php
// filepath: d:\xampp\htdocs\NLNganh\api\get_project_count.php
/**
 * API để lấy số lượng đề tài cho sidebar
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include database connection
include_once '../include/connect.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Basic session check - more permissive
if (!isset($_SESSION['user_id'])) {
    // For development/testing, allow some basic access
    // In production, you might want to be more strict
    error_log('No user session found in get_project_count.php');
}

try {
    // Đếm tổng số đề tài
    $query = "SELECT COUNT(*) as total_count FROM de_tai_nghien_cuu";
    $result = $conn->query($query);
    
    if ($result) {
        $data = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'count' => (int)$data['total_count']
        ]);
    } else {
        throw new Exception('Database query failed');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get project count',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
