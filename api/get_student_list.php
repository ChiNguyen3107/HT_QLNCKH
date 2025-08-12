<?php
// API để lấy danh sách sinh viên với thông tin lớp và trạng thái nghiên cứu
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối database
include '../include/connect.php';

try {
    // Lấy tham số từ request
    $department = isset($_GET['department']) ? $_GET['department'] : '';
    $school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';
    $class = isset($_GET['class']) ? $_GET['class'] : '';
    $research_status = isset($_GET['research_status']) ? $_GET['research_status'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    // Tính offset cho phân trang
    $offset = ($page - 1) * $limit;
    
    // Xây dựng điều kiện WHERE
    $where_conditions = [];
    $params = [];
    $param_types = '';
    
    // Điều kiện khoa
    if (!empty($department)) {
        $where_conditions[] = "l.DV_MADV = ?";
        $params[] = $department;
        $param_types .= 's';
    }
    
    // Điều kiện khóa học
    if (!empty($school_year)) {
        $where_conditions[] = "l.KH_NAM = ?";
        $params[] = $school_year;
        $param_types .= 's';
    }
    
    // Điều kiện lớp
    if (!empty($class)) {
        $where_conditions[] = "sv.LOP_MA = ?";
        $params[] = $class;
        $param_types .= 's';
    }
    
    // Điều kiện trạng thái nghiên cứu
    if (!empty($research_status)) {
        switch ($research_status) {
            case 'active':
                $where_conditions[] = "COALESCE(project_stats.project_count, 0) > 0";
                break;
            case 'completed':
                $where_conditions[] = "COALESCE(project_stats.completed_project_count, 0) > 0";
                break;
            case 'none':
                $where_conditions[] = "COALESCE(project_stats.project_count, 0) = 0";
                break;
        }
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Truy vấn đếm tổng số sinh viên
    $count_sql = "SELECT COUNT(DISTINCT sv.SV_MASV) as total
                  FROM sinh_vien sv
                  JOIN lop l ON sv.LOP_MA = l.LOP_MA
                  JOIN khoa k ON l.DV_MADV = k.DV_MADV
                  LEFT JOIN (
                      SELECT 
                          cttg.SV_MASV,
                          COUNT(DISTINCT cttg.DT_MADT) as project_count,
                          COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN cttg.DT_MADT END) as completed_project_count
                      FROM chi_tiet_tham_gia cttg
                      JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                      GROUP BY cttg.SV_MASV
                  ) project_stats ON sv.SV_MASV = project_stats.SV_MASV
                  $where_clause";
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_students = $count_result->fetch_assoc()['total'];
    
    // Truy vấn lấy danh sách sinh viên
    $sql = "SELECT 
                sv.SV_MASV,
                CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
                l.LOP_TEN,
                k.DV_TENDV,
                COALESCE(project_stats.project_count, 0) as project_count,
                COALESCE(project_stats.completed_project_count, 0) as completed_project_count
            FROM sinh_vien sv
            JOIN lop l ON sv.LOP_MA = l.LOP_MA
            JOIN khoa k ON l.DV_MADV = k.DV_MADV
            LEFT JOIN (
                SELECT 
                    cttg.SV_MASV,
                    COUNT(DISTINCT cttg.DT_MADT) as project_count,
                    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN cttg.DT_MADT END) as completed_project_count
                FROM chi_tiet_tham_gia cttg
                JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                GROUP BY cttg.SV_MASV
            ) project_stats ON sv.SV_MASV = project_stats.SV_MASV
            $where_clause
            ORDER BY sv.SV_HOSV, sv.SV_TENSV
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    // Thêm tham số cho LIMIT và OFFSET
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';
    
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'SV_MASV' => $row['SV_MASV'],
            'SV_HOTEN' => $row['SV_HOTEN'],
            'LOP_TEN' => $row['LOP_TEN'],
            'DV_TENDV' => $row['DV_TENDV'],
            'project_count' => (int)$row['project_count'],
            'completed_project_count' => (int)$row['completed_project_count']
        ];
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'data' => $students,
        'total' => (int)$total_students,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total_students / $limit)
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
