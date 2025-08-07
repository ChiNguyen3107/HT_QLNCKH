<?php
// Debug Upload Error - Chi tiết
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG UPLOAD ERROR CHI TIẾT ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Kiểm tra POST data
echo "1. Kiểm tra dữ liệu POST:\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "   ✅ Method: POST\n";
    echo "   📊 POST data:\n";
    foreach ($_POST as $key => $value) {
        echo "      - $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
    }
    
    echo "   📁 FILES data:\n";
    if (isset($_FILES)) {
        foreach ($_FILES as $key => $file) {
            echo "      - $key:\n";
            echo "        * name: " . ($file['name'] ?? 'N/A') . "\n";
            echo "        * size: " . ($file['size'] ?? 'N/A') . " bytes\n";
            echo "        * error: " . ($file['error'] ?? 'N/A') . "\n";
            echo "        * tmp_name: " . ($file['tmp_name'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "   ❌ Không phải POST request\n";
    echo "   📝 Tạo form test để debug...\n";
    
    // Tạo form test ngay trong file này
    ?>
    
<!DOCTYPE html>
<html>
<head>
    <title>Debug Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <h2>🔧 Debug Upload File Đánh Giá</h2>
    
    <div class="alert alert-info">
        <strong>Hướng dẫn:</strong><br>
        1. Nhập thông tin bên dưới<br>
        2. Chọn file nhỏ (txt, pdf)<br>
        3. Ấn Submit và xem kết quả debug
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Project ID:</label>
            <input type="text" name="project_id" value="DT0000001" required>
        </div>
        
        <div class="form-group">
            <label>Member ID:</label>
            <input type="text" name="member_id" value="GV000002" required>
        </div>
        
        <div class="form-group">
            <label>Decision ID:</label>
            <input type="text" name="decision_id" value="QDDT0">
        </div>
        
        <div class="form-group">
            <label>Tên file:</label>
            <input type="text" name="evaluation_file_name" value="Test Upload Debug" required>
        </div>
        
        <div class="form-group">
            <label>Mô tả:</label>
            <textarea name="file_description" rows="3">File test debug upload error</textarea>
        </div>
        
        <div class="form-group">
            <label>Chọn file:</label>
            <input type="file" name="evaluation_file" required>
        </div>
        
        <button type="submit">🧪 Test Upload</button>
    </form>
    
</body>
</html>

    <?php
    exit;
}

// 2. Test từng bước upload process
echo "\n2. Test upload process từng bước:\n";

try {
    // Step 1: Validate dữ liệu
    $project_id = $_POST['project_id'] ?? '';
    $member_id = $_POST['member_id'] ?? '';
    $decision_id = $_POST['decision_id'] ?? '';
    $file_name = $_POST['evaluation_file_name'] ?? '';
    $file_description = $_POST['file_description'] ?? '';
    
    echo "   ✅ Step 1: Lấy POST data thành công\n";
    
    // Validate dữ liệu
    if (empty($project_id) || empty($member_id) || empty($file_name)) {
        throw new Exception('Thông tin không đầy đủ: project_id, member_id, file_name');
    }
    echo "   ✅ Step 2: Validate dữ liệu thành công\n";
    
    // Step 2: Kiểm tra file upload
    if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File quá lớn (php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'File quá lớn (form)',
            UPLOAD_ERR_PARTIAL => 'Upload không hoàn thành',
            UPLOAD_ERR_NO_FILE => 'Không có file',
            UPLOAD_ERR_NO_TMP_DIR => 'Không có thư mục tmp',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
            UPLOAD_ERR_EXTENSION => 'Extension không cho phép'
        ];
        $error_code = $_FILES['evaluation_file']['error'] ?? 'Unknown';
        $error_msg = $upload_errors[$error_code] ?? 'Lỗi không xác định: ' . $error_code;
        throw new Exception('Lỗi upload file: ' . $error_msg);
    }
    echo "   ✅ Step 3: File upload OK\n";
    
    $file = $_FILES['evaluation_file'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_original_name = $file['name'];
    
    // Step 3: Kiểm tra kích thước
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file_size > $max_size) {
        throw new Exception('File quá lớn: ' . round($file_size/1024/1024, 2) . 'MB > 10MB');
    }
    echo "   ✅ Step 4: Kích thước file OK (" . round($file_size/1024, 2) . " KB)\n";
    
    // Step 4: Kiểm tra loại file
    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx'];
    $file_extension = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Loại file không được phép: .' . $file_extension);
    }
    echo "   ✅ Step 5: Loại file OK (.$file_extension)\n";
    
    // Step 5: Test database connection
    require_once '../../include/connect.php';
    if ($conn->connect_error) {
        throw new Exception('Kết nối database thất bại: ' . $conn->connect_error);
    }
    echo "   ✅ Step 6: Database connection OK\n";
    
    // Step 6: Kiểm tra quyền truy cập đề tài (nếu có session)
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "   ✅ Step 7: Session user_id = " . $_SESSION['user_id'] . "\n";
        
        $stmt = $conn->prepare("
            SELECT dt.*, sv.SV_MASV 
            FROM de_tai dt 
            JOIN sinh_vien sv ON dt.DT_MADT = sv.SV_MADT 
            WHERE dt.DT_MADT = ? AND sv.SV_MASV = ?
        ");
        $stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
        $stmt->execute();
        $project_result = $stmt->get_result();
        
        if ($project_result->num_rows === 0) {
            echo "   ⚠️ Step 8: Không có quyền truy cập đề tài (có thể do test data)\n";
        } else {
            echo "   ✅ Step 8: Có quyền truy cập đề tài\n";
        }
    } else {
        echo "   ⚠️ Step 7: Chưa login (session không có user_id)\n";
    }
    
    // Step 7: Test tạo thư mục
    $upload_dir = '../../uploads/member_evaluations/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Không thể tạo thư mục upload: ' . $upload_dir);
        }
    }
    echo "   ✅ Step 9: Thư mục upload OK: " . realpath($upload_dir) . "\n";
    
    // Step 8: Test tạo tên file
    $unique_filename = 'eval_' . $member_id . '_' . $project_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_filename;
    echo "   ✅ Step 10: Tên file unique: $unique_filename\n";
    
    // Step 9: Test move file
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        throw new Exception('Không thể move file từ ' . $file_tmp . ' đến ' . $upload_path);
    }
    echo "   ✅ Step 11: Move file thành công\n";
    
    // Step 10: Test insert database
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
    
    // Tạo ID file unique
    $file_id = 'FDG' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    $bb_sobb = ''; 
    $file_type = 'member_evaluation';
    
    $stmt->bind_param("ssssssis", $file_id, $bb_sobb, $member_id, $file_type, $file_name, $unique_filename, $file_size, $file_description);
    
    if (!$stmt->execute()) {
        // Xóa file nếu không lưu được database
        unlink($upload_path);
        throw new Exception('Không thể lưu database: ' . $stmt->error);
    }
    
    echo "   ✅ Step 12: Insert database thành công (ID: $file_id)\n";
    
    echo "\n🎉 UPLOAD THÀNH CÔNG!\n";
    echo "   📁 File path: $upload_path\n";
    echo "   🆔 Database ID: $file_id\n";
    echo "   📊 File size: " . round($file_size/1024, 2) . " KB\n";
    
} catch (Exception $e) {
    echo "\n❌ LỖI: " . $e->getMessage() . "\n";
    echo "\n🔍 DEBUG INFO:\n";
    echo "   - Error line: " . $e->getLine() . "\n";
    echo "   - Error file: " . basename($e->getFile()) . "\n";
    
    // Hiển thị thông tin PHP configuration
    echo "\n📋 PHP CONFIG:\n";
    echo "   - upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "   - post_max_size: " . ini_get('post_max_size') . "\n";
    echo "   - file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n";
    echo "   - upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'Default') . "\n";
}
?>
