<?php
// Test version - không cần đăng nhập
require_once 'include/database.php';

header('Content-Type: application/json');

// Kiểm tra phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Lấy MSSV từ parameter
$student_id = trim($_GET['student_id'] ?? '');

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã số sinh viên']);
    exit;
}

// Validate MSSV (8 ký tự)
if (!preg_match('/^[A-Za-z0-9]{8}$/', $student_id)) {
    echo json_encode(['success' => false, 'message' => 'MSSV phải có đúng 8 ký tự']);
    exit;
}

try {
    $conn = connectDB();
    
    // Truy vấn thông tin sinh viên từ database
    $sql = "SELECT 
                sv.SV_MASV,
                sv.SV_HOSV,
                sv.SV_TENSV,
                CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as fullname,
                sv.SV_NGAYSINH,
                sv.SV_SDT,
                sv.SV_EMAIL,
                lop.LOP_TEN,
                lop.KH_NAM as KHOA
            FROM sinh_vien sv
            LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
            WHERE sv.SV_MASV = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu truy vấn: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Không tìm thấy sinh viên với MSSV này'
        ]);
        exit;
    }
    
    $student = $result->fetch_assoc();
    
    // Kiểm tra các trường bắt buộc
    if (empty($student['fullname'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Thông tin sinh viên không đầy đủ'
        ]);
        exit;
    }
    
    // Trả về thông tin sinh viên
    echo json_encode([
        'success' => true,
        'message' => 'Tìm thấy thông tin sinh viên',
        'data' => [
            'SV_MASV' => $student['SV_MASV'],
            'fullname' => $student['fullname'],
            'SV_NGAYSINH' => $student['SV_NGAYSINH'],
            'SV_SDT' => $student['SV_SDT'] ?? '',
            'SV_EMAIL' => $student['SV_EMAIL'] ?? '',
            'LOP_TEN' => $student['LOP_TEN'] ?? '',
            'KHOA' => $student['KHOA'] ?? ''
        ]
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log('Lỗi get_student_info_test.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
