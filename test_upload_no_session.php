<?php
// Upload Test - No Session Required
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not POST');
    }
    
    // Log input data
    $log = [];
    $log[] = "=== UPLOAD TEST NO SESSION ===";
    $log[] = "Time: " . date('Y-m-d H:i:s');
    $log[] = "POST data: " . json_encode($_POST);
    $log[] = "FILES data: " . json_encode(array_map(function($file) {
        return [
            'name' => $file['name'],
            'size' => $file['size'],
            'error' => $file['error'],
            'type' => $file['type']
        ];
    }, $_FILES));
    
    // Get and validate POST data
    $project_id = $_POST['project_id'] ?? '';
    $member_id = $_POST['member_id'] ?? '';
    $file_name = $_POST['evaluation_file_name'] ?? '';
    $file_description = $_POST['file_description'] ?? '';
    
    if (empty($project_id) || empty($member_id) || empty($file_name)) {
        throw new Exception('Missing required fields');
    }
    $log[] = "Validation: OK";
    
    // Check file upload
    if (!isset($_FILES['evaluation_file'])) {
        throw new Exception('No file in request');
    }
    
    $file = $_FILES['evaluation_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error code: ' . $file['error']);
    }
    $log[] = "File upload: OK (" . $file['name'] . ", " . $file['size'] . " bytes)";
    
    // Check file extension
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('File extension not allowed: ' . $file_extension);
    }
    $log[] = "File extension: OK (.$file_extension)";
    
    // Check file size (10MB)
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('File too large: ' . round($file['size']/1024/1024, 2) . 'MB');
    }
    $log[] = "File size: OK (" . round($file['size']/1024, 2) . " KB)";
    
    // Create upload directory
    $upload_dir = 'uploads/member_evaluations/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Cannot create upload directory');
        }
    }
    $log[] = "Upload directory: OK";
    
    // Generate unique filename
    $unique_filename = 'eval_' . $member_id . '_' . $project_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Cannot move uploaded file');
    }
    $log[] = "File moved: OK (" . $upload_path . ")";
    
    // Test database connection
    require_once 'include/connect.php';
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    $log[] = "Database: Connected";
    
    // Get a valid BB_SOBB or create one for member evaluation files
    $bb_sobb_result = $conn->query("SELECT BB_SOBB FROM bien_ban LIMIT 1");
    if ($bb_sobb_result && $bb_sobb_result->num_rows > 0) {
        $bb_row = $bb_sobb_result->fetch_assoc();
        $bb_sobb = $bb_row['BB_SOBB'];
        $log[] = "Using existing BB_SOBB: " . $bb_sobb;
    } else {
        // Create a dummy bien_ban for member evaluation files
        $bb_sobb = 'BBEVAL' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $create_bb = $conn->prepare("INSERT INTO bien_ban (BB_SOBB, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, NOW(), 'Đánh giá')");
        $create_bb->bind_param("s", $bb_sobb);
        if (!$create_bb->execute()) {
            throw new Exception('Cannot create bien_ban: ' . $create_bb->error);
        }
        $log[] = "Created new BB_SOBB: " . $bb_sobb;
    }
    
    // Insert to database
    $stmt = $conn->prepare("
        INSERT INTO file_dinh_kem (
            FDG_MA, BB_SOBB, GV_MAGV, FDG_LOAI, 
            FDG_TENFILE, FDG_FILE, FDG_NGAYTAO, 
            FDG_KICHTHUC, FDG_MOTA
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Cannot prepare statement: ' . $conn->error);
    }
    
    $file_id = 'FDG' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    $file_type = 'member_evaluation';
    
    $stmt->bind_param("ssssssis", 
        $file_id, $bb_sobb, $member_id, $file_type, 
        $file_name, $unique_filename, $file['size'], $file_description
    );
    
    if (!$stmt->execute()) {
        unlink($upload_path); // Delete file if database insert fails
        throw new Exception('Database insert failed: ' . $stmt->error);
    }
    $log[] = "Database: Inserted (ID: $file_id)";
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload successful!',
        'file_id' => $file_id,
        'file_name' => $file_name,
        'file_path' => $unique_filename,
        'file_size' => $file['size'],
        'log' => implode("\n", $log)
    ]);
    
} catch (Exception $e) {
    $log[] = "ERROR: " . $e->getMessage();
    $log[] = "Line: " . $e->getLine();
    $log[] = "File: " . basename($e->getFile());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile()),
        'log' => implode("\n", $log)
    ]);
}
?>
