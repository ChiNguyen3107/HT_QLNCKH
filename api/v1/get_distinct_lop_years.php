<?php
// API để lấy danh sách khóa học từ bảng lop
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối database
include '../include/connect.php';

try {
    // Truy vấn lấy danh sách khóa học từ bảng lop
    $sql = "SELECT DISTINCT KH_NAM as school_year 
            FROM lop 
            WHERE KH_NAM IS NOT NULL AND KH_NAM != '' 
            ORDER BY KH_NAM ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Lỗi truy vấn database: ' . $conn->error);
    }
    
    $years = [];
    while ($row = $result->fetch_assoc()) {
        $years[] = [
            'school_year' => $row['school_year']
        ];
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'years' => $years,
        'total' => count($years)
    ]);
    
} catch (Exception $e) {
    // Trả về lỗi
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
