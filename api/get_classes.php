<?php
// filepath: d:\xampp\htdocs\NLNganh\api\get_classes.php
/**
 * API để lấy danh sách lớp theo khoa và khóa học
 */

// Bao gồm file kết nối cơ sở dữ liệu
require_once '../include/connect.php';
require_once '../include/session.php';

// Kiểm tra đăng nhập và quyền truy cập
if (!isResearchManagerLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

// Thiết lập header
header('Content-Type: application/json');

// Lấy các tham số
$department = isset($_GET['department']) ? $_GET['department'] : '';
$school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';

// Xây dựng câu truy vấn
$query = "SELECT LOP_MA, LOP_TEN FROM lop WHERE 1=1";
$params = [];
$types = "";

// Lọc theo khoa
if (!empty($department)) {
    $query .= " AND DV_MADV = ?";
    $params[] = $department;
    $types .= "s";
}

// Lọc theo khóa học
if (!empty($school_year)) {
    $query .= " AND LOP_NIENKHOA LIKE ?";
    $params[] = $school_year . "%";
    $types .= "s";
}

$query .= " ORDER BY LOP_TEN ASC";

try {
    // Chuẩn bị và thực thi truy vấn
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = [
            'id' => $row['LOP_MA'],
            'name' => $row['LOP_TEN']
        ];
    }
    
    // Trả về dữ liệu
    echo json_encode([
        'success' => true,
        'data' => $classes
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_classes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Lỗi khi lấy danh sách lớp: " . $e->getMessage()
    ]);
}
?>
