<?php
// API để lấy danh sách khoa/đơn vị
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối database
include '../include/connect.php';

try {
    // Truy vấn lấy danh sách khoa/đơn vị
    $sql = "SELECT DV_MADV, DV_TENDV 
            FROM khoa 
            ORDER BY DV_TENDV ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Lỗi truy vấn database: ' . $conn->error);
    }
    
    $faculties = [];
    while ($row = $result->fetch_assoc()) {
        $faculties[] = [
            'DV_MADV' => $row['DV_MADV'],
            'DV_TENDV' => $row['DV_TENDV']
        ];
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'faculties' => $faculties,
        'total' => count($faculties)
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
