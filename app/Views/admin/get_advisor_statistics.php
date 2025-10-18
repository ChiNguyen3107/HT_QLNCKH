<?php
// Bao gồm file session để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';

// Kết nối database
include '../../include/connect.php';

// Đặt header JSON
header('Content-Type: application/json');

// Kiểm tra tham số
$lop_ma = $_GET['lop_ma'] ?? '';

if (empty($lop_ma)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu mã lớp'
    ]);
    exit;
}

try {
    // Lấy thống kê tổng số sinh viên trong lớp
    $total_students_sql = "SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = ?";
    $stmt = $conn->prepare($total_students_sql);
    if ($stmt) {
        $stmt->bind_param("s", $lop_ma);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_students = $result->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $total_students = 0;
    }
    
    // Lấy thống kê sinh viên có đề tài
    $students_with_projects_sql = "
        SELECT COUNT(DISTINCT sv.SV_MSSV) as total 
        FROM sinh_vien sv 
        JOIN sinh_vien_de_tai svdt ON sv.SV_MSSV = svdt.SV_MSSV 
        WHERE sv.LOP_MA = ?
    ";
    $stmt = $conn->prepare($students_with_projects_sql);
    if ($stmt) {
        $stmt->bind_param("s", $lop_ma);
        $stmt->execute();
        $result = $stmt->get_result();
        $students_with_projects = $result->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $students_with_projects = 0;
    }
    
    // Lấy thống kê đề tài hoàn thành
    $completed_projects_sql = "
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai dt 
        JOIN sinh_vien_de_tai svdt ON dt.DT_MADT = svdt.DT_MADT 
        JOIN sinh_vien sv ON svdt.SV_MSSV = sv.SV_MSSV 
        WHERE sv.LOP_MA = ? AND dt.DT_TRANGTHAI = 'Hoàn thành'
    ";
    $stmt = $conn->prepare($completed_projects_sql);
    if ($stmt) {
        $stmt->bind_param("s", $lop_ma);
        $stmt->execute();
        $result = $stmt->get_result();
        $completed_projects = $result->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $completed_projects = 0;
    }
    
    // Lấy thống kê đề tài đang thực hiện
    $ongoing_projects_sql = "
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai dt 
        JOIN sinh_vien_de_tai svdt ON dt.DT_MADT = svdt.DT_MADT 
        JOIN sinh_vien sv ON svdt.SV_MSSV = sv.SV_MSSV 
        WHERE sv.LOP_MA = ? AND dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Đã đăng ký', 'Đã phê duyệt')
    ";
    $stmt = $conn->prepare($ongoing_projects_sql);
    if ($stmt) {
        $stmt->bind_param("s", $lop_ma);
        $stmt->execute();
        $result = $stmt->get_result();
        $ongoing_projects = $result->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $ongoing_projects = 0;
    }
    
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
        'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage(),
        'debug' => [
            'lop_ma' => $lop_ma,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
