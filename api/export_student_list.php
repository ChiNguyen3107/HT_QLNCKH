<?php
// filepath: d:\xampp\htdocs\NLNganh\api\export_student_list.php
/**
 * API để xuất danh sách sinh viên ra file CSV
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

// Lấy các tham số lọc
$department = isset($_GET['department']) ? $_GET['department'] : '';
$school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$class = isset($_GET['class']) ? $_GET['class'] : '';
$research_status = isset($_GET['research_status']) ? $_GET['research_status'] : '';

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
    $where_clauses[] = "l.KH_NAM = ?";
    $params[] = $school_year;
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

// Thêm ORDER BY
$query .= " ORDER BY sv.SV_MASV ASC";

// Thực hiện truy vấn
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Lỗi chuẩn bị câu truy vấn: ' . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Thiết lập header để tải file CSV
$filename = 'danh-sach-sinh-vien-' . date('Y-m-d-H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Tạo file CSV
$output = fopen('php://output', 'w');

// Thêm BOM để Excel hiển thị tiếng Việt đúng
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Tiêu đề
fputcsv($output, ['DANH SÁCH SINH VIÊN THEO LỚP']);

// Thông tin bộ lọc
$filterText = "Bộ lọc: ";
if (!empty($department)) {
    $filterText .= "Khoa: " . $department . " | ";
}
if (!empty($school_year)) {
    $filterText .= "Khóa: " . $school_year . " | ";
}
if (!empty($class)) {
    $filterText .= "Lớp: " . $class . " | ";
}
if (!empty($research_status)) {
    $filterText .= "Trạng thái: " . $research_status . " | ";
}
$filterText = rtrim($filterText, " | ");
fputcsv($output, [$filterText]);

// Dòng trống
fputcsv($output, []);

// Tiêu đề cột
fputcsv($output, ['STT', 'Mã SV', 'Họ tên', 'Lớp', 'Khoa', 'Trạng thái nghiên cứu', 'Số đề tài']);

// Dữ liệu sinh viên
$stt = 1;
$totalStudents = 0;
$noResearchCount = 0;
$activeResearchCount = 0;
$multipleResearchCount = 0;

while ($row = $result->fetch_assoc()) {
    // Trạng thái nghiên cứu
    $researchStatus = '';
    if ($row['project_count'] == 0) {
        $researchStatus = 'Chưa tham gia';
        $noResearchCount++;
    } elseif ($row['project_count'] == 1) {
        $researchStatus = 'Đang tham gia';
        $activeResearchCount++;
    } else {
        $researchStatus = 'Tham gia nhiều';
        $multipleResearchCount++;
    }
    
    fputcsv($output, [
        $stt,
        $row['SV_MASV'],
        $row['SV_HOTEN'],
        $row['LOP_TEN'] ?? 'N/A',
        $row['DV_TENDV'] ?? 'N/A',
        $researchStatus,
        $row['project_count']
    ]);
    
    $stt++;
    $totalStudents++;
}

// Dòng trống
fputcsv($output, []);
fputcsv($output, []);

// Thống kê tổng hợp
fputcsv($output, ['THỐNG KÊ TỔNG HỢP']);
fputcsv($output, ['Tổng số sinh viên', $totalStudents]);
fputcsv($output, ['Sinh viên chưa tham gia nghiên cứu', $noResearchCount]);
fputcsv($output, ['Sinh viên đang tham gia nghiên cứu', $activeResearchCount]);
fputcsv($output, ['Sinh viên tham gia nhiều đề tài', $multipleResearchCount]);

fclose($output);
$stmt->close();
$conn->close();
exit;
?>
