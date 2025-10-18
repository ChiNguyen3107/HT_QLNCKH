<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\export_student_list.php
/**
 * Script to export student list to Excel based on filters
 */

// Include necessary files
require_once '../../include/session.php';
require_once '../../include/connect.php';
require_once '../../include/functions.php';

// Check if user is logged in as research manager
checkResearchManagerRole();

// Load PHPSpreadsheet library if not included in your project
// You might need to install it via composer: composer require phpoffice/phpspreadsheet
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Get filter parameters
$department = isset($_GET['department']) ? $_GET['department'] : '';
$school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$class = isset($_GET['class']) ? $_GET['class'] : '';
$research_status = isset($_GET['research_status']) ? $_GET['research_status'] : '';

// Build query to get students
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

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = "";

// Filter by department
if (!empty($department)) {
    $where_clauses[] = "l.DV_MADV = ?";
    $params[] = $department;
    $types .= "s";
}

// Filter by school year
if (!empty($school_year)) {
    $where_clauses[] = "l.LOP_NIENKHOA LIKE ?";
    $params[] = $school_year . "%";
    $types .= "s";
}

// Filter by class
if (!empty($class)) {
    $where_clauses[] = "l.LOP_MA = ?";
    $params[] = $class;
    $types .= "s";
}

// Add WHERE clause to query
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Add having clause for research status
if (!empty($research_status)) {
    switch ($research_status) {
        case 'active':
            $query .= " HAVING project_count > 0 AND EXISTS (
                SELECT 1 
                FROM chi_tiet_tham_gia ct 
                JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT 
                WHERE ct.SV_MASV = sv.SV_MASV AND dt.DT_TRANGTHAI = 'Đang tiến hành'
            )";
            break;
        case 'completed':
            $query .= " HAVING project_count > 0 AND EXISTS (
                SELECT 1 
                FROM chi_tiet_tham_gia ct 
                JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT 
                WHERE ct.SV_MASV = sv.SV_MASV AND dt.DT_TRANGTHAI = 'Đã hoàn thành'
            )";
            break;
        case 'none':
            $query .= " HAVING project_count = 0";
            break;
    }
}

$query .= " ORDER BY sv.SV_MASV ASC";

try {
    // Execute query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(30);
    $sheet->getColumnDimension('F')->setWidth(25);
    $sheet->getColumnDimension('G')->setWidth(15);
    
    // Set title, header fields and meta data
    $sheet->setTitle('Danh sách sinh viên');
    
    // Create header
    $sheet->setCellValue('A1', 'DANH SÁCH SINH VIÊN THEO LỚP');
    $sheet->mergeCells('A1:G1');
    
    // Apply styles to header
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Second row - Filter information
    $filter_text = 'Bộ lọc: ';
    
    // Get filter info details
    if (!empty($department)) {
        $dept_query = "SELECT DV_TENDV FROM khoa WHERE DV_MADV = ?";
        $dept_stmt = $conn->prepare($dept_query);
        $dept_stmt->bind_param("s", $department);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        if ($dept_row = $dept_result->fetch_assoc()) {
            $filter_text .= 'Khoa: ' . $dept_row['DV_TENDV'] . '; ';
        }
    }
    
    if (!empty($school_year)) {
        $filter_text .= 'Khóa: ' . $school_year . '; ';
    }
    
    if (!empty($class)) {
        $class_query = "SELECT LOP_TEN FROM lop WHERE LOP_MA = ?";
        $class_stmt = $conn->prepare($class_query);
        $class_stmt->bind_param("s", $class);
        $class_stmt->execute();
        $class_result = $class_stmt->get_result();
        if ($class_row = $class_result->fetch_assoc()) {
            $filter_text .= 'Lớp: ' . $class_row['LOP_TEN'] . '; ';
        }
    }
    
    if (!empty($research_status)) {
        $status_text = '';
        switch ($research_status) {
            case 'active': $status_text = 'Đang làm nghiên cứu'; break;
            case 'completed': $status_text = 'Đã hoàn thành nghiên cứu'; break;
            case 'none': $status_text = 'Chưa tham gia nghiên cứu'; break;
        }
        $filter_text .= 'Trạng thái nghiên cứu: ' . $status_text . '; ';
    }
    
    $sheet->setCellValue('A2', $filter_text);
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getFont()->setItalic(true);
    
    // Add export date
    $sheet->setCellValue('A3', 'Ngày xuất: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:G3');
    
    // Add table headers at row 5
    $headers = ['STT', 'Mã SV', 'Họ tên', 'Lớp', 'Khoa', 'Trạng thái nghiên cứu', 'Số đề tài'];
    
    foreach (range('A', 'G') as $idx => $col) {
        $sheet->setCellValue($col . '5', $headers[$idx]);
        $sheet->getStyle($col . '5')->getFont()->setBold(true);
    }
    
    // Style the header row
    $sheet->getStyle('A5:G5')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('D9EAD3');
    
    $sheet->getStyle('A5:G5')->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);
    
    $sheet->getStyle('A5:G5')->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);
    
    // Add data rows
    $row = 6;
    $count = 1;
    
    while ($data = $result->fetch_assoc()) {
        // Determine research status
        $status = "Chưa tham gia";
        
        if ($data['project_count'] > 0) {
            // Check project status
            $project_status_query = "SELECT dt.DT_TRANGTHAI 
                                 FROM chi_tiet_tham_gia ct 
                                 JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT 
                                 WHERE ct.SV_MASV = ? 
                                 ORDER BY dt.DT_NGAYTAO DESC 
                                 LIMIT 1";
            $project_stmt = $conn->prepare($project_status_query);
            $project_stmt->bind_param("s", $data['SV_MASV']);
            $project_stmt->execute();
            $project_result = $project_stmt->get_result();
            
            if ($project_row = $project_result->fetch_assoc()) {
                if ($project_row['DT_TRANGTHAI'] == 'Đã hoàn thành') {
                    $status = "Đã hoàn thành";
                } else {
                    $status = "Đang tham gia";
                }
            }
        }
        
        // Add row data
        $sheet->setCellValue('A' . $row, $count);
        $sheet->setCellValue('B' . $row, $data['SV_MASV']);
        $sheet->setCellValue('C' . $row, $data['SV_HOTEN']);
        $sheet->setCellValue('D' . $row, $data['LOP_TEN'] ?? 'Chưa có thông tin');
        $sheet->setCellValue('E' . $row, $data['DV_TENDV'] ?? 'Chưa có thông tin');
        $sheet->setCellValue('F' . $row, $status);
        $sheet->setCellValue('G' . $row, $data['project_count']);
        
        // Add borders
        $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        
        $count++;
        $row++;
    }
    
    // Center some columns
    $sheet->getStyle('A6:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B6:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D6:D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F6:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Create file name
    $filename = 'Danh_sach_sinh_vien_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Export to Excel file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    // Log error
    error_log("Error exporting student list to Excel: " . $e->getMessage());
    
    // Display error message
    echo "Có lỗi xảy ra khi xuất danh sách sinh viên: " . $e->getMessage();
}
?>
