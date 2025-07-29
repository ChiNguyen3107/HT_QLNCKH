<?php
header('Content-Type: application/json; charset=utf-8');

// Test API không cần session
$rootPath = dirname(dirname(__FILE__));
include $rootPath . '/include/connect.php';

try {
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
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
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
        'count' => count($teachers),
        'message' => 'API test thành công'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
