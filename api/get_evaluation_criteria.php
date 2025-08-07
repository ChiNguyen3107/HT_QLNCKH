<?php
// File: api/get_evaluation_criteria.php
// API để lấy danh sách tiêu chí đánh giá

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

include '../include/connect.php';

try {
    // Kiểm tra kết nối database
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối cơ sở dữ liệu");
    }
    
    // Lấy danh sách tiêu chí với đầy đủ thông tin
    $sql = "SELECT 
                TC_MATC, 
                COALESCE(TC_TEN, TC_NDDANHGIA) as TC_TEN,
                TC_NDDANHGIA, 
                COALESCE(TC_MOTA, TC_NDDANHGIA) as TC_MOTA,
                TC_DIEMTOIDA,
                COALESCE(TC_TRONGSO, 20.00) as TC_TRONGSO,
                COALESCE(TC_THUTU, 1) as TC_THUTU,
                COALESCE(TC_TRANGTHAI, 'Hoạt động') as TC_TRANGTHAI
            FROM tieu_chi 
            WHERE COALESCE(TC_TRANGTHAI, 'Hoạt động') = 'Hoạt động'
            ORDER BY COALESCE(TC_THUTU, 1), TC_MATC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }
    
    $criteria = [];
    while ($row = $result->fetch_assoc()) {
        $criteria[] = [
            'TC_MATC' => $row['TC_MATC'],
            'TC_TEN' => $row['TC_TEN'],
            'TC_NDDANHGIA' => $row['TC_NDDANHGIA'],
            'TC_MOTA' => $row['TC_MOTA'],
            'TC_DIEMTOIDA' => (float)$row['TC_DIEMTOIDA'],
            'TC_TRONGSO' => (float)$row['TC_TRONGSO'],
            'TC_THUTU' => (int)$row['TC_THUTU'],
            
            // Compatibility với code cũ
            'id' => $row['TC_MATC'],
            'content' => $row['TC_NDDANHGIA'],
            'maxScore' => (float)$row['TC_DIEMTOIDA'],
            'code' => $row['TC_MATC']
        ];
    }
    
    // Tính tổng trọng số
    $totalWeight = array_sum(array_column($criteria, 'TC_TRONGSO'));
    $totalMaxScore = array_sum(array_column($criteria, 'TC_DIEMTOIDA'));
    
    echo json_encode([
        'success' => true,
        'criteria' => $criteria, // Format mới cho JavaScript
        'data' => $criteria,     // Compatibility với code cũ
        'totalMaxScore' => $totalMaxScore,
        'totalWeight' => $totalWeight,
        'count' => count($criteria),
        'message' => 'Lấy danh sách tiêu chí thành công'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => [],
        'totalMaxScore' => 0,
        'count' => 0
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
