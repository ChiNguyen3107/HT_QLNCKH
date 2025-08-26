<?php
// API thống kê đơn giản và ổn định
header('Content-Type: application/json');

// Kết nối database
include '../../include/connect.php';

// Kiểm tra tham số
$lop_ma = $_GET['lop_ma'] ?? 'DI2195A2';

try {
    // 1. Tổng số sinh viên trong lớp
    $total_result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = '$lop_ma'");
    $total_students = $total_result->fetch_assoc()['total'];
    
    // 2. Sinh viên có đề tài
    $with_projects_result = $conn->query("
        SELECT COUNT(DISTINCT sv.SV_MSSV) as total 
        FROM sinh_vien sv 
        JOIN sinh_vien_de_tai svdt ON sv.SV_MSSV = svdt.SV_MSSV 
        WHERE sv.LOP_MA = '$lop_ma'
    ");
    $students_with_projects = $with_projects_result->fetch_assoc()['total'];
    
    // 3. Đề tài hoàn thành
    $completed_result = $conn->query("
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai dt 
        JOIN sinh_vien_de_tai svdt ON dt.DT_MADT = svdt.DT_MADT 
        JOIN sinh_vien sv ON svdt.SV_MSSV = sv.SV_MSSV 
        WHERE sv.LOP_MA = '$lop_ma' AND dt.DT_TRANGTHAI = 'Hoàn thành'
    ");
    $completed_projects = $completed_result->fetch_assoc()['total'];
    
    // 4. Đề tài đang thực hiện
    $ongoing_result = $conn->query("
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai dt 
        JOIN sinh_vien_de_tai svdt ON dt.DT_MADT = svdt.DT_MADT 
        JOIN sinh_vien sv ON svdt.SV_MSSV = sv.SV_MSSV 
        WHERE sv.LOP_MA = '$lop_ma' AND dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Đã đăng ký', 'Đã phê duyệt')
    ");
    $ongoing_projects = $ongoing_result->fetch_assoc()['total'];
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'statistics' => [
            'total_students' => (int)$total_students,
            'students_with_projects' => (int)$students_with_projects,
            'completed_projects' => (int)$completed_projects,
            'ongoing_projects' => (int)$ongoing_projects
        ],
        'debug' => [
            'lop_ma' => $lop_ma,
            'total_students_raw' => $total_students,
            'students_with_projects_raw' => $students_with_projects,
            'completed_projects_raw' => $completed_projects,
            'ongoing_projects_raw' => $ongoing_projects
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage(),
        'debug' => [
            'lop_ma' => $lop_ma,
            'error' => $e->getMessage()
        ]
    ]);
}
?>
