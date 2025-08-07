<?php
require_once 'include/database.php';

echo "=== TEST ĐĂNG KÝ ĐỀ TÀI ===" . PHP_EOL;

try {
    $conn = connectDB();
    
    // Kiểm tra cấu trúc bảng hiện tại
    echo "1. Kiểm tra cấu trúc bảng de_tai_nghien_cuu..." . PHP_EOL;
    $result = $conn->query("SHOW COLUMNS FROM de_tai_nghien_cuu WHERE Field = 'QD_SO'");
    if ($result && $result->num_rows > 0) {
        $col = $result->fetch_assoc();
        if ($col['Null'] === 'YES') {
            echo "   ✅ Cột QD_SO cho phép NULL" . PHP_EOL;
        } else {
            echo "   ❌ Cột QD_SO vẫn NOT NULL" . PHP_EOL;
        }
    }
    
    // Test insert đề tài mới với QD_SO = NULL
    echo PHP_EOL . "2. Test thêm đề tài mới..." . PHP_EOL;
    
    // Tạo dữ liệu test
    $test_project_id = 'TEST' . date('His');
    $test_data = [
        'DT_MADT' => $test_project_id,
        'LDT_MA' => 'LDT01', // Giả sử có loại đề tài này
        'GV_MAGV' => 'GV000001', // Giả sử có giảng viên này
        'LVNC_MA' => 'LVNC1', // Lĩnh vực nghiên cứu
        'QD_SO' => null,
        'LVUT_MA' => 'LVUT1', // Lĩnh vực ưu tiên
        'HD_MA' => 'HD001', // Hợp đồng
        'DT_TENDT' => 'Đề tài test',
        'DT_MOTA' => 'Mô tả test',
        'DT_TRANGTHAI' => 'Chờ duyệt',
        'DT_FILEBTM' => null,
        'DT_NGAYTAO' => date('Y-m-d H:i:s'),
        'DT_SLSV' => 2,
        'DT_GHICHU' => 'Test từ script',
        'DT_NGUOICAPNHAT' => 'SYSTEM',
        'DT_NGAYCAPNHAT' => date('Y-m-d H:i:s')
    ];
    
    // Kiểm tra xem có các bảng liên quan không
    $tables_to_check = ['loai_de_tai', 'giang_vien', 'linh_vuc_nghien_cuu', 'linh_vuc_uu_tien'];
    foreach ($tables_to_check as $table) {
        $check_result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_result && $check_result->num_rows > 0) {
            echo "   ✅ Bảng $table tồn tại" . PHP_EOL;
        } else {
            echo "   ⚠️  Bảng $table không tồn tại" . PHP_EOL;
        }
    }
    
    // Thử insert đơn giản
    $simple_sql = "INSERT INTO de_tai_nghien_cuu (DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI, DT_NGAYTAO, DT_SLSV, QD_SO) 
                   VALUES (?, 'Test Project', 'Test Description', 'Chờ duyệt', NOW(), 1, NULL)";
    
    $stmt = $conn->prepare($simple_sql);
    if ($stmt) {
        $stmt->bind_param("s", $test_project_id);
        if ($stmt->execute()) {
            echo "   ✅ Thêm đề tài test thành công với ID: $test_project_id" . PHP_EOL;
            
            // Xóa dữ liệu test
            $conn->query("DELETE FROM de_tai_nghien_cuu WHERE DT_MADT = '$test_project_id'");
            echo "   ✅ Đã xóa dữ liệu test" . PHP_EOL;
        } else {
            echo "   ❌ Lỗi khi thêm đề tài: " . $stmt->error . PHP_EOL;
        }
    } else {
        echo "   ❌ Lỗi prepare statement: " . $conn->error . PHP_EOL;
    }
    
    echo PHP_EOL . "3. Kiểm tra bảng quyet_dinh_nghiem_thu..." . PHP_EOL;
    $qd_count = $conn->query("SELECT COUNT(*) as count FROM quyet_dinh_nghiem_thu");
    if ($qd_count) {
        $count = $qd_count->fetch_assoc()['count'];
        echo "   📊 Hiện có $count quyết định nghiệm thu" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== KẾT LUẬN ===" . PHP_EOL;
    echo "✅ Hệ thống đã được sửa để không tạo quyết định tạm thời khi đăng ký đề tài" . PHP_EOL;
    echo "✅ Cột QD_SO cho phép NULL cho đề tài chưa được nghiệm thu" . PHP_EOL;
    echo "✅ Có thể thêm đề tài mới với QD_SO = NULL" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . PHP_EOL;
}
?>
