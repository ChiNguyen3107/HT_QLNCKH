<?php
// Debug Upload Member Evaluation Files
echo "=== DEBUG UPLOAD MEMBER EVALUATION FILES ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Kiểm tra thư mục uploads
echo "1. Kiểm tra cấu trúc thư mục uploads:\n";

$base_upload_dir = 'uploads/';
$member_eval_dir = 'uploads/member_evaluations/';
$member_eval_files_dir = 'uploads/member_evaluation_files/';

echo "   - uploads/: " . (is_dir($base_upload_dir) ? "✅ Tồn tại" : "❌ Không tồn tại") . "\n";
echo "   - uploads/member_evaluations/: " . (is_dir($member_eval_dir) ? "✅ Tồn tại" : "❌ Không tồn tại") . "\n";
echo "   - uploads/member_evaluation_files/: " . (is_dir($member_eval_files_dir) ? "✅ Tồn tại" : "❌ Không tồn tại") . "\n";

// Tạo thư mục nếu không tồn tại
if (!is_dir($member_eval_dir)) {
    echo "   → Tạo thư mục member_evaluations: ";
    if (mkdir($member_eval_dir, 0755, true)) {
        echo "✅ Thành công\n";
    } else {
        echo "❌ Thất bại\n";
    }
}

// 2. Kiểm tra quyền ghi
echo "\n2. Kiểm tra quyền ghi:\n";
echo "   - uploads/: " . (is_writable($base_upload_dir) ? "✅ Có quyền ghi" : "❌ Không có quyền ghi") . "\n";
if (is_dir($member_eval_dir)) {
    echo "   - member_evaluations/: " . (is_writable($member_eval_dir) ? "✅ Có quyền ghi" : "❌ Không có quyền ghi") . "\n";
}

// 3. Kiểm tra cấu hình PHP
echo "\n3. Kiểm tra cấu hình PHP:\n";
echo "   - file_uploads: " . (ini_get('file_uploads') ? "✅ Bật" : "❌ Tắt") . "\n";
echo "   - upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   - post_max_size: " . ini_get('post_max_size') . "\n";
echo "   - max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "   - upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'Mặc định') . "\n";

// 4. Kiểm tra cấu trúc database
echo "\n4. Kiểm tra cấu trúc database:\n";
try {
    require_once 'include/connect.php';
    
    // Kiểm tra bảng file_dinh_kem
    $check_table = "DESCRIBE file_dinh_kem";
    $result = $conn->query($check_table);
    
    if ($result) {
        echo "   ✅ Bảng file_dinh_kem tồn tại với các cột:\n";
        while ($row = $result->fetch_assoc()) {
            echo "      - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "   ❌ Bảng file_dinh_kem không tồn tại hoặc có lỗi\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Lỗi kết nối database: " . $e->getMessage() . "\n";
}

// 5. Kiểm tra file upload handlers
echo "\n5. Kiểm tra file upload handlers:\n";
$upload_files = [
    'view/student/upload_member_evaluation.php',
    'view/student/upload_evaluation_file.php',
    'view/student/upload_member_evaluation_file.php'
];

foreach ($upload_files as $file) {
    echo "   - $file: " . (file_exists($file) ? "✅ Tồn tại" : "❌ Không tồn tại") . "\n";
}

// 6. Kiểm tra cấu hình session
echo "\n6. Kiểm tra session:\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "   - Session status: " . session_status() . " (1=disabled, 2=active)\n";
echo "   - Session ID: " . (session_id() ?: 'Không có') . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔧 KHUYẾN NGHỊ SỬA LỖI:\n\n";

if (!is_dir($member_eval_dir)) {
    echo "❗ Tạo thư mục member_evaluations:\n";
    echo "   mkdir uploads/member_evaluations -p\n";
    echo "   chmod 755 uploads/member_evaluations\n\n";
}

echo "❗ Kiểm tra và sửa path trong upload files:\n";
echo "   - Đảm bảo đường dẫn uploads đúng\n";
echo "   - Kiểm tra include database connection\n";
echo "   - Xác minh cấu trúc bảng file_dinh_kem\n\n";

echo "❗ Debug upload error:\n";
echo "   - Bật error reporting: error_reporting(E_ALL)\n";
echo "   - Kiểm tra log file: /xampp/apache/logs/error.log\n";
echo "   - Test upload với file nhỏ trước\n";

?>
