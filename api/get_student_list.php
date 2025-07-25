<?php
// filepath: d:\xampp\htdocs\NLNganh\api\get_student_list.php
/**
 * API để lấy danh sách sinh viên theo các tiêu chí lọc
 * Hỗ trợ lọc theo khoa, khóa học, lớp và trạng thái tham gia nghiên cứu
 */

// Bao gồm file kết nối cơ sở dữ liệu
require_once '../include/connect.php';
require_once '../include/session.php';

// Kiểm tra đăng nhập và quyền truy cập
if (!isResearchManagerLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

// Thiết lập header
header('Content-Type: application/json');

// Lấy các tham số lọc
$department = isset($_GET['department']) ? $_GET['department'] : '';
$school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$class = isset($_GET['class']) ? $_GET['class'] : '';
$research_status = isset($_GET['research_status']) ? $_GET['research_status'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

// Tính offset cho phân trang
$offset = ($page - 1) * $limit;

// Xây dựng câu truy vấn cơ bản
$query = "SELECT 
            sv.SV_MASV, 
            CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN, 
            l.LOP_TEN, 
            k.DV_TENDV,
            (SELECT COUNT(*) FROM chi_tiet_tham_gia ct WHERE ct.SV_MASV = sv.SV_MASV) AS project_count
          FROM 
            sinh_vien sv
          LEFT JOIN 
            lop l ON sv.LOP_MA = l.LOP_MA
          LEFT JOIN 
            khoa k ON l.DV_MADV = k.DV_MADV";

// Xây dựng WHERE clause
$where_clauses = [];
$params = [];
$types = "";

// Lọc theo khoa
if (!empty($department)) {
    $where_clauses[] = "l.DV_MADV = ?";
    $params[] = $department;
    $types .= "s";
}

// Lọc theo khóa học
if (!empty($school_year)) {
    $where_clauses[] = "l.LOP_NIENKHOA LIKE ?";
    $params[] = $school_year . "%";
    $types .= "s";
}

// Lọc theo lớp
if (!empty($class)) {
    $where_clauses[] = "l.LOP_MA = ?";
    $params[] = $class;
    $types .= "s";
}

// Thêm WHERE clause vào truy vấn
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Truy vấn phụ để lọc theo trạng thái nghiên cứu
$having_clause = "";
if (!empty($research_status)) {
    switch ($research_status) {
        case 'active':
            $having_clause = " HAVING project_count > 0 AND EXISTS (
                SELECT 1 
                FROM chi_tiet_tham_gia ct 
                JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT 
                WHERE ct.SV_MASV = sv.SV_MASV AND dt.DT_TRANGTHAI = 'Đang tiến hành'
            )";
            break;
        case 'completed':
            $having_clause = " HAVING project_count > 0 AND EXISTS (
                SELECT 1 
                FROM chi_tiet_tham_gia ct 
                JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT 
                WHERE ct.SV_MASV = sv.SV_MASV AND dt.DT_TRANGTHAI = 'Đã hoàn thành'
            )";
            break;
        case 'none':
            $having_clause = " HAVING project_count = 0";
            break;
    }
    $query .= $having_clause;
}

// Thêm ORDER BY và LIMIT
$query .= " ORDER BY sv.SV_MASV ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

// Truy vấn đếm tổng số sinh viên (không có LIMIT)
$count_query = str_replace("SELECT 
            sv.SV_MASV, 
            CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN, 
            l.LOP_TEN, 
            k.DV_TENDV,
            (SELECT COUNT(*) FROM chi_tiet_tham_gia ct WHERE ct.SV_MASV = sv.SV_MASV) AS project_count", "SELECT COUNT(*) as total", $query);
$count_query = preg_replace('/LIMIT \?, \?$/', '', $count_query);

try {
    // Thực hiện truy vấn đếm
    $count_stmt = $conn->prepare($count_query);
    if (!$count_stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn đếm: " . $conn->error);
    }
    
    if (!empty($params)) {
        // Loại bỏ 2 tham số cuối (offset và limit)
        $count_params = array_slice($params, 0, count($params) - 2);
        $count_types = substr($types, 0, -2);
        
        if (!empty($count_params)) {
            $count_stmt->bind_param($count_types, ...$count_params);
        }
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
    
    // Thực hiện truy vấn chính
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    $index = $offset + 1;
    
    while ($row = $result->fetch_assoc()) {
        // Xác định trạng thái nghiên cứu
        $status = "Chưa tham gia";
        $status_class = "text-secondary";
        
        if ($row['project_count'] > 0) {
            // Kiểm tra trạng thái đề tài
            $project_status_query = "SELECT dt.DT_TRANGTHAI 
                                     FROM chi_tiet_tham_gia ct 
                                     JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT 
                                     WHERE ct.SV_MASV = ? 
                                     ORDER BY dt.DT_NGAYTAO DESC 
                                     LIMIT 1";
            $project_stmt = $conn->prepare($project_status_query);
            $project_stmt->bind_param("s", $row['SV_MASV']);
            $project_stmt->execute();
            $project_result = $project_stmt->get_result();
            
            if ($project_row = $project_result->fetch_assoc()) {
                if ($project_row['DT_TRANGTHAI'] == 'Đã hoàn thành') {
                    $status = "Đã hoàn thành";
                    $status_class = "text-success";
                } else {
                    $status = "Đang tham gia";
                    $status_class = "text-primary";
                }
            }
            $project_stmt->close();
        }
        
        // Định dạng dữ liệu sinh viên
        $students[] = [
            'index' => $index++,
            'id' => $row['SV_MASV'],
            'name' => $row['SV_HOTEN'],
            'class' => $row['LOP_TEN'] ?? 'Chưa có thông tin',
            'department' => $row['DV_TENDV'] ?? 'Chưa có thông tin',
            'status' => $status,
            'status_class' => $status_class,
            'project_count' => $row['project_count']
        ];
    }
    
    $total_pages = ceil($total / $limit);
    
    // Trả về dữ liệu
    echo json_encode([
        'success' => true,
        'data' => $students,
        'pagination' => [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_student_list.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Lỗi khi lấy danh sách sinh viên: " . $e->getMessage()
    ]);
}
?>
