<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\import_students.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Đặt header trả về JSON
header('Content-Type: application/json');

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Phương thức không được hỗ trợ."]);
    exit();
}

// Kiểm tra có file được upload không
if (!isset($_FILES['studentFile']) || $_FILES['studentFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["error" => "Vui lòng chọn file để upload."]);
    exit();
}

// Kiểm tra class ID
if (!isset($_POST['classId']) || empty(trim($_POST['classId']))) {
    echo json_encode(["error" => "Thiếu thông tin mã lớp."]);
    exit();
}

$classId = trim($_POST['classId']);
$file = $_FILES['studentFile'];

// Kiểm tra định dạng file
$allowedExtensions = ['xlsx', 'xls', 'csv'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(["error" => "Chỉ chấp nhận file Excel (.xlsx, .xls) hoặc CSV (.csv)."]);
    exit();
}

// Kiểm tra kích thước file (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(["error" => "File không được vượt quá 5MB."]);
    exit();
}

try {
    // Kiểm tra lớp học có tồn tại không
    $classCheckSql = "SELECT LOP_MA, LOP_TEN FROM lop WHERE LOP_MA = ?";
    $classCheckStmt = $conn->prepare($classCheckSql);
    
    if ($classCheckStmt === false) {
        throw new Exception("Lỗi chuẩn bị câu lệnh kiểm tra lớp: " . $conn->error);
    }
    
    $classCheckStmt->bind_param("s", $classId);
    $classCheckStmt->execute();
    $classResult = $classCheckStmt->get_result();
    
    if ($classResult->num_rows === 0) {
        echo json_encode(["error" => "Lớp học không tồn tại."]);
        $classCheckStmt->close();
        exit();
    }
    
    $classInfo = $classResult->fetch_assoc();
    $classCheckStmt->close();
    
    // Tạo thư mục uploads nếu chưa có
    $uploadDir = '../../../uploads/temp/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Di chuyển file upload
    $uploadPath = $uploadDir . uniqid('import_') . '.' . $fileExtension;
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception("Không thể lưu file upload.");
    }
    
    // Xử lý file dựa trên định dạng
    $students = [];
    
    if ($fileExtension === 'csv') {
        $students = processCSVFile($uploadPath);
    } else {
        // Xử lý file Excel (.xlsx, .xls)
        $students = processExcelFile($uploadPath);
    }
    
    // Xóa file tạm
    unlink($uploadPath);
    
    if (empty($students)) {
        echo json_encode(["error" => "Không tìm thấy dữ liệu sinh viên trong file."]);
        exit();
    }
    
    // Validate dữ liệu và import
    $importResult = importStudents($students, $classId, $conn);
    
    echo json_encode($importResult);
    
} catch (Exception $e) {
    // Xóa file tạm nếu có lỗi
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    error_log("Error in import_students.php: " . $e->getMessage());
    echo json_encode(["error" => "Đã xảy ra lỗi: " . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}

/**
 * Xử lý file CSV
 */
function processCSVFile($filePath) {
    $students = [];
    
    // Đọc file và tự động phát hiện dấu phân cách
    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new Exception("Không thể đọc file CSV");
    }
    
    // Xử lý BOM nếu có
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    
    // Phát hiện dấu phân cách (comma hoặc semicolon)
    $delimiter = ',';
    if (substr_count($content, ';') > substr_count($content, ',')) {
        $delimiter = ';';
    }
    
    // Tách thành các dòng
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        
        // Bỏ qua dòng trống
        if (empty($line)) {
            continue;
        }
        
        // Tách dữ liệu theo dấu phân cách
        $data = str_getcsv($line, $delimiter);
        
        // Làm sạch dữ liệu (trim và loại bỏ ký tự đặc biệt)
        $data = array_map(function($item) {
            return trim($item, " \t\n\r\0\x0B\"'");
        }, $data);
        
        // Debug: Log dữ liệu để kiểm tra
        error_log("CSV Line " . ($lineNumber + 1) . ": " . print_r($data, true));
        
        if (count($data) >= 6) {
            // Xử lý số điện thoại: thêm số 0 đầu nếu thiếu
            $sdt = $data[5];
            if (preg_match('/^[1-9][0-9]{8,9}$/', $sdt)) {
                // Nếu là số 9-10 chữ số bắt đầu bằng 1-9, thêm số 0 đầu
                $sdt = '0' . $sdt;
            }
            
            $students[] = [
                'ma_sv' => $data[0],
                'ho_sv' => $data[1],
                'ten_sv' => $data[2],
                'ngay_sinh' => $data[3],
                'email' => $data[4],
                'sdt' => $sdt
            ];
        } else {
            error_log("CSV Line " . ($lineNumber + 1) . " has only " . count($data) . " columns: " . implode('|', $data));
        }
    }
    
    return $students;
}

/**
 * Xử lý file Excel
 * Cần cài đặt thư viện PhpSpreadsheet hoặc SimpleXLSX
 */
function processExcelFile($filePath) {
    // Kiểm tra xem có thư viện PhpSpreadsheet không
    if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        return processExcelWithPhpSpreadsheet($filePath);
    } else {
        // Fallback: Yêu cầu người dùng chuyển đổi sang CSV
        throw new Exception("Vui lòng chuyển đổi file Excel sang định dạng CSV để import. Hệ thống chưa cài đặt thư viện xử lý Excel. Xem hướng dẫn tại EXCEL_TO_CSV_GUIDE.md");
    }
}

/**
 * Xử lý Excel với PhpSpreadsheet (nếu có)
 */
function processExcelWithPhpSpreadsheet($filePath) {
    $students = [];
    
    try {
        // Sử dụng fully qualified class name để tránh lỗi lint
        $ioFactory = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
        $spreadsheet = $ioFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        foreach ($rows as $index => $row) {
            // Bỏ qua dòng trống
            if (empty(trim($row[0]))) {
                continue;
            }
            
            if (count($row) >= 6) {
                // Xử lý số điện thoại: thêm số 0 đầu nếu thiếu
                $sdt = trim($row[5]);
                if (preg_match('/^[1-9][0-9]{8,9}$/', $sdt)) {
                    // Nếu là số 9-10 chữ số bắt đầu bằng 1-9, thêm số 0 đầu
                    $sdt = '0' . $sdt;
                }
                
                $students[] = [
                    'ma_sv' => trim($row[0]),
                    'ho_sv' => trim($row[1]),
                    'ten_sv' => trim($row[2]),
                    'ngay_sinh' => trim($row[3]),
                    'email' => trim($row[4]),
                    'sdt' => $sdt
                ];
            }
        }
    } catch (Exception $e) {
        throw new Exception("Lỗi đọc file Excel: " . $e->getMessage());
    }
    
    return $students;
}

/**
 * Import sinh viên vào database
 */
function importStudents($students, $classId, $conn) {
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $duplicates = [];
    
    // Bắt đầu transaction
    $conn->autocommit(false);
    
    try {
        foreach ($students as $index => $student) {
            $rowNumber = $index + 1;
            
            // Validate dữ liệu
            $validation = validateStudentData($student, $rowNumber);
            if (!$validation['valid']) {
                $errors[] = $validation['error'];
                $errorCount++;
                continue;
            }
            
            // Kiểm tra trùng lặp mã sinh viên
            $checkSql = "SELECT SV_MASV FROM sinh_vien WHERE SV_MASV = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $student['ma_sv']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $duplicates[] = "Dòng $rowNumber: Mã sinh viên '{$student['ma_sv']}' đã tồn tại";
                $errorCount++;
                $checkStmt->close();
                continue;
            }
            $checkStmt->close();
            
            // Chuyển đổi ngày sinh
            $ngaySinh = convertDateFormat($student['ngay_sinh']);
            if (!$ngaySinh) {
                error_log("Date conversion failed for row $rowNumber: '{$student['ngay_sinh']}'");
                $errors[] = "Dòng $rowNumber: Định dạng ngày sinh không hợp lệ '{$student['ngay_sinh']}'. Hỗ trợ: d/m/yyyy, dd/mm/yyyy, yyyy-mm-dd";
                $errorCount++;
                continue;
            } else {
                error_log("Date conversion success for row $rowNumber: '{$student['ngay_sinh']}' → '$ngaySinh'");
            }
            
            // Tạo mật khẩu mặc định (mã sinh viên)
            $defaultPassword = password_hash($student['ma_sv'], PASSWORD_DEFAULT);
            
            // Xác định giới tính (mặc định là 1 - Nam)
            $gioiTinh = 1; // Có thể cải tiến sau
            
            // Insert sinh viên
            $insertSql = "INSERT INTO sinh_vien (SV_MASV, LOP_MA, SV_HOSV, SV_TENSV, SV_GIOITINH, SV_SDT, SV_EMAIL, SV_MATKHAU, SV_NGAYSINH) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            
            if ($insertStmt === false) {
                throw new Exception("Lỗi chuẩn bị câu lệnh insert: " . $conn->error);
            }
            
            $insertStmt->bind_param("ssssissss", 
                $student['ma_sv'], 
                $classId, 
                $student['ho_sv'], 
                $student['ten_sv'], 
                $gioiTinh, 
                $student['sdt'], 
                $student['email'], 
                $defaultPassword, 
                $ngaySinh
            );
            
            if ($insertStmt->execute()) {
                $successCount++;
            } else {
                $errors[] = "Dòng $rowNumber: Lỗi insert - " . $insertStmt->error;
                $errorCount++;
            }
            
            $insertStmt->close();
        }
        
        // Commit transaction nếu có ít nhất 1 sinh viên thành công
        if ($successCount > 0) {
            $conn->commit();
        } else {
            $conn->rollback();
        }
        
        $conn->autocommit(true);
        
        return [
            'success' => true,
            'message' => "Import hoàn tất: $successCount thành công, $errorCount lỗi",
            'details' => [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => array_merge($errors, $duplicates)
            ]
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        throw $e;
    }
}

/**
 * Validate dữ liệu sinh viên
 */
function validateStudentData($student, $rowNumber) {
    // Debug: Log dữ liệu để kiểm tra
    error_log("Validating row $rowNumber: " . print_r($student, true));
    
    // Kiểm tra mã sinh viên
    if (empty($student['ma_sv'])) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Mã sinh viên trống (giá trị: '" . $student['ma_sv'] . "')"];
    }
    if (strlen($student['ma_sv']) > 8) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Mã sinh viên quá dài (" . strlen($student['ma_sv']) . " ký tự): '" . $student['ma_sv'] . "'"];
    }
    
    // Kiểm tra họ
    if (empty($student['ho_sv'])) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Họ sinh viên trống"];
    }
    if (strlen($student['ho_sv']) > 50) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Họ sinh viên quá dài: '" . $student['ho_sv'] . "'"];
    }
    
    // Kiểm tra tên
    if (empty($student['ten_sv'])) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Tên sinh viên trống"];
    }
    if (strlen($student['ten_sv']) > 50) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Tên sinh viên quá dài: '" . $student['ten_sv'] . "'"];
    }
    
    // Kiểm tra email
    if (empty($student['email'])) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Email trống"];
    }
    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Email không hợp lệ: '" . $student['email'] . "'"];
    }
    if (strlen($student['email']) > 35) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Email quá dài: '" . $student['email'] . "'"];
    }
    
    // Kiểm tra số điện thoại
    if (empty($student['sdt'])) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Số điện thoại trống"];
    }
    if (!preg_match('/^[0-9]{10,11}$/', $student['sdt'])) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Số điện thoại không hợp lệ (phải 10-11 chữ số): '" . $student['sdt'] . "'"];
    }
    
    // Kiểm tra ngày sinh
    if (empty($student['ngay_sinh'])) {
        return ['valid' => false, 'error' => "Dòng $rowNumber: Ngày sinh trống"];
    }
    
    return ['valid' => true];
}

/**
 * Chuyển đổi định dạng ngày
 */
function convertDateFormat($dateString) {
    $dateString = trim($dateString);
    
    // Xử lý định dạng ngày thiếu số 0: d/m/yyyy → dd/mm/yyyy
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        $dateString = "$day/$month/$year";
    }
    
    // Xử lý định dạng ngày thiếu số 0: d-m-yyyy → dd-mm-yyyy
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateString, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        $dateString = "$day-$month-$year";
    }
    
    // Các định dạng có thể: dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd
    $formats = [
        'd/m/Y',    // 06/02/2004
        'd-m-Y',    // 06-02-2004
        'Y-m-d',    // 2004-02-06
        'd/m/y',    // 06/02/04
        'd-m-y',    // 06-02-04
        'j/n/Y',    // 6/2/2004 (single digit)
        'j-n-Y',    // 6-2-2004 (single digit)
    ];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateString);
        if ($date && $date->format($format) === $dateString) {
            return $date->format('Y-m-d');
        }
    }
    
    // Thử parse với strtotime
    $timestamp = strtotime($dateString);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return false;
}
?>
