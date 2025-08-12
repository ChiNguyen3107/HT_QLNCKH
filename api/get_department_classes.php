<?php
// API để lấy danh sách lớp theo khoa và khóa học
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối database
include '../include/connect.php';

try {
    // Lấy tham số từ request
    $dept_id = isset($_GET['dept_id']) ? $_GET['dept_id'] : '';
    $year = isset($_GET['year']) ? $_GET['year'] : '';
    
    // Kiểm tra tham số bắt buộc
    if (empty($dept_id) || empty($year)) {
        throw new Exception('Thiếu tham số dept_id hoặc year');
    }
    
    // Truy vấn lấy danh sách lớp theo khoa và khóa học
    $sql = "SELECT 
                l.LOP_MA as class_id,
                l.LOP_TEN as class_name,
                l.KH_NAM as school_year,
                l.LOP_LOAICTDT as program_type,
                COUNT(sv.SV_MASV) as student_count
            FROM lop l
            LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
            WHERE l.DV_MADV = ? AND l.KH_NAM = ?
            GROUP BY l.LOP_MA, l.LOP_TEN, l.KH_NAM, l.LOP_LOAICTDT
            ORDER BY l.LOP_TEN ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $dept_id, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = [
            'class_id' => $row['class_id'],
            'class_name' => $row['class_name'],
            'school_year' => $row['school_year'],
            'program_type' => $row['program_type'],
            'student_count' => (int)$row['student_count']
        ];
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'classes' => $classes,
        'total' => count($classes)
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
