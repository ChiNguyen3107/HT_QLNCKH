<?php
// Fixed Upload Handler - Version 2
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    session_start();
    require_once 'include/connect.php';
    
    $log = [];
    $log[] = "=== UPLOAD FIXED V2 ===";
    $log[] = "Time: " . date('Y-m-d H:i:s');
    
    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Not POST method');
    }
    $log[] = "Method: POST ✓";
    
    // Get POST data
    $project_id = $_POST['project_id'] ?? '';
    $member_id = $_POST['member_id'] ?? '';
    $file_name = $_POST['evaluation_file_name'] ?? '';
    $file_description = $_POST['file_description'] ?? '';
    
    if (empty($project_id) || empty($member_id) || empty($file_name)) {
        throw new Exception('Missing required fields: project_id, member_id, file_name');
    }
    $log[] = "POST data validated ✓";
    
    // Check file upload
    if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['evaluation_file']['error'] ?? 'No file';
        throw new Exception('File upload error: ' . $error_code);
    }
    
    $file = $_FILES['evaluation_file'];
    $log[] = "File: " . $file['name'] . " (" . $file['size'] . " bytes)";
    
    // Validate file
    $allowed_ext = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_ext)) {
        throw new Exception('Invalid file extension: ' . $file_ext);
    }
    
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('File too large: ' . round($file['size']/1024/1024, 2) . 'MB');
    }
    $log[] = "File validation ✓";
    
    // Create upload directory
    $upload_dir = 'uploads/member_evaluations/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $unique_filename = 'eval_' . $member_id . '_' . $project_id . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $unique_filename;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Cannot move uploaded file');
    }
    $log[] = "File moved to: " . $upload_path;
    
    // Get valid BB_SOBB
    $bb_result = $conn->query("SELECT BB_SOBB FROM bien_ban ORDER BY BB_SOBB LIMIT 1");
    if (!$bb_result || $bb_result->num_rows === 0) {
        // Create a dummy bien_ban if none exists
        $bb_sobb = 'BBEVAL' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $create_bb = $conn->prepare("INSERT INTO bien_ban (BB_SOBB, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, NOW(), 'Đánh giá')");
        $create_bb->bind_param("s", $bb_sobb);
        if (!$create_bb->execute()) {
            throw new Exception('Cannot create bien_ban: ' . $create_bb->error);
        }
        $log[] = "Created new BB_SOBB: " . $bb_sobb;
    } else {
        $bb_row = $bb_result->fetch_assoc();
        $bb_sobb = $bb_row['BB_SOBB'];
        $log[] = "Using existing BB_SOBB: " . $bb_sobb;
    }
    
    // Check if GV_MAGV exists
    $gv_magv = null;
    $gv_check = $conn->prepare("SELECT GV_MAGV FROM giang_vien WHERE GV_MAGV = ?");
    $gv_check->bind_param("s", $member_id);
    $gv_check->execute();
    $gv_result = $gv_check->get_result();
    
    if ($gv_result->num_rows > 0) {
        $gv_magv = $member_id;
        $log[] = "Valid GV_MAGV: " . $gv_magv;
    } else {
        $log[] = "GV_MAGV not found, setting to NULL";
    }
    
    // Insert to database
    $file_id = 'FDG' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("
        INSERT INTO file_dinh_kem (
            FDG_MA, BB_SOBB, GV_MAGV, FDG_LOAI, 
            FDG_TENFILE, FDG_FILE, FDG_NGAYTAO, 
            FDG_KICHTHUC, FDG_MOTA
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $file_type = 'member_evaluation';
    
    // Bind parameters correctly
    $stmt->bind_param("ssssssis", 
        $file_id, $bb_sobb, $gv_magv, $file_type, 
        $file_name, $unique_filename, $file['size'], $file_description
    );
    
    if (!$stmt->execute()) {
        // Delete uploaded file if database insert fails
        unlink($upload_path);
        throw new Exception('Database insert failed: ' . $stmt->error);
    }
    
    $log[] = "Database insert successful! ID: " . $file_id;
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Upload thành công!',
        'file_id' => $file_id,
        'file_name' => $file_name,
        'file_path' => $unique_filename,
        'file_size' => $file['size'],
        'bb_sobb' => $bb_sobb,
        'gv_magv' => $gv_magv,
        'log' => implode("\n", $log)
    ]);
    
} catch (Exception $e) {
    $log[] = "ERROR: " . $e->getMessage();
    $log[] = "Line: " . $e->getLine();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile()),
        'log' => implode("\n", $log)
    ]);
}
?>
