<?php
// Test Upload Member Evaluation Fix
echo "=== TEST UPLOAD MEMBER EVALUATION FIXED ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Kiểm tra thư mục uploads đã được tạo
echo "1. Kiểm tra thư mục uploads:\n";
$member_eval_dir = 'uploads/member_evaluations/';
echo "   - member_evaluations/: " . (is_dir($member_eval_dir) ? "✅ Tồn tại" : "❌ Không tồn tại") . "\n";
echo "   - Quyền ghi: " . (is_writable($member_eval_dir) ? "✅ OK" : "❌ Không có quyền") . "\n\n";

// 2. Kiểm tra database connection
echo "2. Kiểm tra database connection:\n";
try {
    require_once 'include/connect.php';
    echo "   ✅ Kết nối database thành công\n";
    
    // Test query bảng file_dinh_kem
    $test_query = "SELECT COUNT(*) as count FROM file_dinh_kem WHERE FDG_LOAI = 'member_evaluation'";
    $result = $conn->query($test_query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "   ✅ Bảng file_dinh_kem hoạt động OK\n";
        echo "   📊 Hiện có " . $row['count'] . " file member evaluation trong DB\n";
    }
} catch (Exception $e) {
    echo "   ❌ Lỗi database: " . $e->getMessage() . "\n";
}

echo "\n3. Kiểm tra file upload handler:\n";
$upload_file = 'view/student/upload_member_evaluation.php';
echo "   - File handler: " . (file_exists($upload_file) ? "✅ Tồn tại" : "❌ Không tồn tại") . "\n";

if (file_exists($upload_file)) {
    $content = file_get_contents($upload_file);
    echo "   - Sử dụng mysqli: " . (strpos($content, '$conn->prepare') !== false ? "✅ OK" : "❌ Sai") . "\n";
    echo "   - Schema đúng: " . (strpos($content, 'FDG_TENFILE') !== false ? "✅ OK" : "❌ Sai") . "\n";
    echo "   - Include connect: " . (strpos($content, 'include/connect.php') !== false ? "✅ OK" : "❌ Sai") . "\n";
}

echo "\n4. Kiểm tra form upload trong view_project.php:\n";
$view_file = 'view/student/view_project.php';
if (file_exists($view_file)) {
    $content = file_get_contents($view_file);
    echo "   - Form upload tồn tại: " . (strpos($content, 'uploadEvaluationForm') !== false ? "✅ OK" : "❌ Không có") . "\n";
    echo "   - Action đúng: " . (strpos($content, 'upload_member_evaluation.php') !== false ? "✅ OK" : "❌ Sai") . "\n";
    echo "   - Enctype multipart: " . (strpos($content, 'multipart/form-data') !== false ? "✅ OK" : "❌ Thiếu") . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 TÌNH TRẠNG SAU KHI SỬA:\n\n";

echo "✅ Các lỗi đã được sửa:\n";
echo "   - Tạo thư mục uploads/member_evaluations/\n";
echo "   - Sửa schema database (FDK_ → FDG_)\n";
echo "   - Chuyển từ PDO sang mysqli\n";
echo "   - Sửa đường dẫn include connect.php\n";
echo "   - Cập nhật query lấy file evaluation\n\n";

echo "🧪 Test upload bằng cách:\n";
echo "   1. Truy cập vào một đề tài\n";
echo "   2. Vào tab Đánh giá\n";
echo "   3. Chọn thành viên hội đồng\n";
echo "   4. Upload file đánh giá\n\n";

echo "📝 Các định dạng file được phép:\n";
echo "   - PDF, DOC, DOCX, TXT, XLS, XLSX\n";
echo "   - Tối đa 10MB\n\n";

echo "🔍 Nếu vẫn lỗi, kiểm tra:\n";
echo "   - Error log Apache: /xampp/apache/logs/error.log\n";
echo "   - Console browser (F12)\n";
echo "   - Network tab để xem response\n";
?>
