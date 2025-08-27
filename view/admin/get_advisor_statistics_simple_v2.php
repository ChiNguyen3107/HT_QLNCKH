<?php
// API thống kê đơn giản và ổn định - Version 2
header('Content-Type: application/json; charset=utf-8');

// Kết nối database
include '../../include/connect.php';

// Kiểm tra tham số
$lop_ma = $_GET['lop_ma'] ?? 'DI2195A2';

try {
    // 1. Tổng số sinh viên trong lớp
    $total_result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = '$lop_ma'");
    if (!$total_result) {
        throw new Exception("Lỗi query tổng sinh viên: " . $conn->error);
    }
    $total_students = $total_result->fetch_assoc()['total'];
    
    // 2. Sinh viên có đề tài
    $with_projects_result = $conn->query("
        SELECT COUNT(DISTINCT sv.SV_MASV) as total 
        FROM sinh_vien sv 
        JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV 
        WHERE sv.LOP_MA = '$lop_ma'
    ");
    if (!$with_projects_result) {
        throw new Exception("Lỗi query sinh viên có đề tài: " . $conn->error);
    }
    $students_with_projects = $with_projects_result->fetch_assoc()['total'];
    
    // 3. Đề tài hoàn thành
    $completed_result = $conn->query("
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai_nghien_cuu dt 
        JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
        JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV 
        WHERE sv.LOP_MA = '$lop_ma' AND dt.DT_TRANGTHAI = 'Đã hoàn thành'
    ");
    if (!$completed_result) {
        throw new Exception("Lỗi query đề tài hoàn thành: " . $conn->error);
    }
    $completed_projects = $completed_result->fetch_assoc()['total'];
    
    // 4. Đề tài đang thực hiện
    $ongoing_result = $conn->query("
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai_nghien_cuu dt 
        JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
        JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV 
        WHERE sv.LOP_MA = '$lop_ma' AND dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Chờ duyệt', 'Đang xử lý')
    ");
    if (!$ongoing_result) {
        throw new Exception("Lỗi query đề tài đang thực hiện: " . $conn->error);
    }
    $ongoing_projects = $ongoing_result->fetch_assoc()['total'];
    
    // Trả về kết quả
    $response = [
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
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage(),
        'debug' => [
            'lop_ma' => $lop_ma,
            'error' => $e->getMessage()
        ]
    ];
    
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
}
?>
