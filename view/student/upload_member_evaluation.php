<?php
// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../include/connect.php';

// Ghi log debug
$debug_log = "=== UPLOAD DEBUG LOG ===\n";
$debug_log .= "Time: " . date('Y-m-d H:i:s') . "\n";
$debug_log .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$debug_log .= "Session ID: " . session_id() . "\n";
$debug_log .= "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
$debug_log .= "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";

// Function to write debug log
function writeDebugLog($message) {
    $log_file = '../../upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

writeDebugLog("=== UPLOAD START ===");
writeDebugLog("POST data: " . json_encode($_POST));
writeDebugLog("FILES data: " . json_encode($_FILES));

// Kiểm tra đăng nhập - Tạm thời bỏ role check để debug
if (!isset($_SESSION['user_id'])) {
    $debug_log .= "ERROR: No user_id in session\n";
    writeDebugLog("ERROR: No user_id in session");
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập', 'debug' => $debug_log]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $debug_log .= "ERROR: Not POST method\n";
    writeDebugLog("ERROR: Not POST method");
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ', 'debug' => $debug_log]);
    exit;
}

try {
    writeDebugLog("Starting upload process");
    
    $project_id = $_POST['project_id'] ?? '';
    $member_id = $_POST['member_id'] ?? '';
    $decision_id = $_POST['decision_id'] ?? '';
    $file_name = $_POST['evaluation_file_name'] ?? '';
    $file_description = $_POST['file_description'] ?? '';
    
    writeDebugLog("Parsed POST data - Project: $project_id, Member: $member_id, File: $file_name");
    
    // Validate dữ liệu
    if (empty($project_id) || empty($member_id) || empty($file_name)) {
        writeDebugLog("ERROR: Missing required fields");
        throw new Exception('Thông tin không đầy đủ');
    }
    
    // Kiểm tra file upload với error handling chi tiết
    if (!isset($_FILES['evaluation_file'])) {
        throw new Exception('Không có file trong request');
    }
    
    $upload_error = $_FILES['evaluation_file']['error'];
    if ($upload_error !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File quá lớn (vượt quá upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File quá lớn (vượt quá MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
            UPLOAD_ERR_NO_FILE => 'Không có file nào được upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file lên disk',
            UPLOAD_ERR_EXTENSION => 'Extension PHP đã dừng file upload'
        ];
        $error_msg = $upload_errors[$upload_error] ?? 'Lỗi upload không xác định: ' . $upload_error;
        throw new Exception($error_msg);
    }
    
    $file = $_FILES['evaluation_file'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_original_name = $file['name'];
    
    // Kiểm tra kích thước file (10MB)
    $max_size = 10 * 1024 * 1024;
    if ($file_size > $max_size) {
        throw new Exception('File quá lớn. Kích thước tối đa là 10MB');
    }
    
    // Kiểm tra loại file
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx'];
    $file_extension = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Loại file không được phép. Chỉ chấp nhận: ' . implode(', ', $allowed_extensions));
    }
    
    // Kiểm tra quyền truy cập đề tài
    $stmt = $conn->prepare("
        SELECT dt.*, cttg.SV_MASV 
        FROM de_tai_nghien_cuu dt 
        JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
        WHERE dt.DT_MADT = ? AND cttg.SV_MASV = ?
    ");
    
    if (!$stmt) {
        writeDebugLog("ERROR: Prepare project check failed: " . $conn->error);
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
    $stmt->execute();
    $project_result = $stmt->get_result();
    
    if ($project_result->num_rows === 0) {
        throw new Exception('Không có quyền truy cập đề tài này');
    }
    
    // Tạo thư mục upload nếu chưa tồn tại
    $upload_dir = '../../uploads/member_evaluations/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Tạo tên file unique
    $unique_filename = 'eval_' . $member_id . '_' . $project_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_filename;
    
    // Upload file
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        throw new Exception('Không thể lưu file');
    }
    
    // Lưu thông tin file vào database với schema đúng
    // Tạo ID file unique
    $file_id = 'FDG' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    
    // Get valid BB_SOBB
    $bb_result = $conn->query("SELECT BB_SOBB FROM bien_ban ORDER BY BB_SOBB LIMIT 1");
    if (!$bb_result || $bb_result->num_rows === 0) {
        // Create a dummy bien_ban if none exists
        $bb_sobb = 'BBEVAL' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $create_bb = $conn->prepare("INSERT INTO bien_ban (BB_SOBB, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, NOW(), 'Đánh giá')");
        if (!$create_bb) {
            writeDebugLog("ERROR: Prepare create bien_ban failed: " . $conn->error);
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $create_bb->bind_param("s", $bb_sobb);
        if (!$create_bb->execute()) {
            writeDebugLog("ERROR: Cannot create bien_ban: " . $create_bb->error);
            throw new Exception('Cannot create bien_ban: ' . $create_bb->error);
        }
        $debug_log .= "Created new BB_SOBB: " . $bb_sobb . "\n";
        writeDebugLog("Created new BB_SOBB: " . $bb_sobb);
    } else {
        $bb_row = $bb_result->fetch_assoc();
        $bb_sobb = $bb_row['BB_SOBB'];
        $debug_log .= "Using existing BB_SOBB: " . $bb_sobb . "\n";
        writeDebugLog("Using existing BB_SOBB: " . $bb_sobb);
    }
    
    // Check if GV_MAGV exists
    $gv_magv = null;
    $gv_check = $conn->prepare("SELECT GV_MAGV FROM giang_vien WHERE GV_MAGV = ?");
    if (!$gv_check) {
        writeDebugLog("ERROR: Prepare GV check failed: " . $conn->error);
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $gv_check->bind_param("s", $member_id);
    $gv_check->execute();
    $gv_result = $gv_check->get_result();
    
    if ($gv_result->num_rows > 0) {
        $gv_magv = $member_id;
        $debug_log .= "Valid GV_MAGV: " . $gv_magv . "\n";
    } else {
        $debug_log .= "GV_MAGV not found, setting to NULL\n";
    }
    
    $file_type = 'member_evaluation';
    
    if ($gv_magv !== null) {
        $stmt = $conn->prepare("
            INSERT INTO file_dinh_kem (
                FDG_MA,
                BB_SOBB, 
                GV_MAGV,
                FDG_LOAI, 
                FDG_TENFILE,
                FDG_FILE, 
                FDG_NGAYTAO, 
                FDG_KICHTHUC,
                FDG_MOTA
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
        ");
        if (!$stmt) {
            writeDebugLog("ERROR: Prepare insert with GV_MAGV failed: " . $conn->error);
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $stmt->bind_param("ssssssis", $file_id, $bb_sobb, $gv_magv, $file_type, $file_original_name, $unique_filename, $file_size, $file_description);
    } else {
        // Modify statement for NULL GV_MAGV
        $stmt = $conn->prepare("
            INSERT INTO file_dinh_kem (
                FDG_MA,
                BB_SOBB, 
                GV_MAGV,
                FDG_LOAI, 
                FDG_TENFILE,
                FDG_FILE, 
                FDG_NGAYTAO, 
                FDG_KICHTHUC,
                FDG_MOTA
            ) VALUES (?, ?, NULL, ?, ?, ?, NOW(), ?, ?)
        ");
        if (!$stmt) {
            writeDebugLog("ERROR: Prepare insert without GV_MAGV failed: " . $conn->error);
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        $stmt->bind_param("sssssis", $file_id, $bb_sobb, $file_type, $file_original_name, $unique_filename, $file_size, $file_description);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        // Xóa file nếu không lưu được database
        unlink($upload_path);
        throw new Exception('Không thể lưu thông tin file vào database');
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Upload file thành công',
        'file_name' => $file_original_name,
        'file_path' => $unique_filename
    ]);

} catch (Exception $e) {
    $debug_log .= "EXCEPTION: " . $e->getMessage() . "\n";
    $debug_log .= "Line: " . $e->getLine() . "\n";
    $debug_log .= "File: " . basename($e->getFile()) . "\n";
    
    writeDebugLog("EXCEPTION: " . $e->getMessage() . " at line " . $e->getLine());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => $debug_log,
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
?>
