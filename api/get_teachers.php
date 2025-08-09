<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__DIR__) . '/include/connect.php';

try {
    // Lấy danh sách giảng viên từ database
    $sql = "SELECT gv.GV_MAGV as id, 
                   CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as name,
                   gv.DV_MADV as department,
                   k.DV_TENDV as department_name,
                   gv.GV_EMAIL as email,
                   gv.GV_SDT as phone
            FROM giang_vien gv
            LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
            ORDER BY gv.GV_HOGV, gv.GV_TENGV";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Lỗi truy vấn database: " . $conn->error);
    }
    
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'id' => $row['id'],
            'name' => $row['name'] ?: 'Chưa có tên',
            'department' => $row['department'] ?: 'Chưa xác định', // Mã khoa
            'department_name' => $row['department_name'] ?: 'Chưa xác định', // Tên khoa
            'email' => $row['email'] ?: '',
            'phone' => $row['phone'] ?: ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'teachers' => $teachers,
        'total' => count($teachers)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'teachers' => []
    ]);
}

$conn->close();
?>