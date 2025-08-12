<?php
header('Content-Type: application/json; charset=utf-8');

// Định nghĩa đường dẫn gốc
$rootPath = dirname(dirname(__FILE__));

// Include files với xử lý lỗi
$includeFiles = [
    $rootPath . '/include/connect.php',
    $rootPath . '/include/session.php'
];

foreach ($includeFiles as $file) {
    if (!file_exists($file)) {
        error_log("File not found: " . $file);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Thiếu file hệ thống: ' . basename($file)], JSON_UNESCAPED_UNICODE);
        exit;
    }
    include $file;
}

// Khởi tạo session
startSessionIfNotStarted();

// Kiểm tra session - cho phép tất cả user đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập. Vui lòng đăng nhập để sử dụng tính năng này.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Lấy danh sách giảng viên với cấu trúc database thực tế
    $sql = "SELECT 
                gv.GV_MAGV as id,
                CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as name,
                gv.GV_HOGV as lastName,
                gv.GV_TENGV as firstName,
                gv.GV_EMAIL as email,
                gv.GV_CHUYENMON as specialty,
                k.DV_TENDV as department
            FROM giang_vien gv 
            LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
            ORDER BY gv.GV_HOGV ASC, gv.GV_TENGV ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Lỗi truy vấn cơ sở dữ liệu: " . $conn->error);
    }
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        // Tạo tên đầy đủ
        $fullName = trim($row['name']);
        
        $teachers[] = [
            'id' => $row['id'],
            'name' => $fullName,
            'fullName' => $fullName,
            'lastName' => $row['lastName'] ?? '',
            'firstName' => $row['firstName'] ?? '',
            'email' => $row['email'] ?? '',
            'specialty' => $row['specialty'] ?? 'Không có thông tin',
            'department' => $row['department'] ?? 'Không xác định'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $teachers,
        'count' => count($teachers)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Get teachers API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
