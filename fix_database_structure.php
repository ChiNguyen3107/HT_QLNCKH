<?php
require_once 'include/database.php';

try {
    $conn = connectDB();
    
    echo "=== Sửa cấu trúc bảng de_tai_nghien_cuu ===" . PHP_EOL;
    
    // Tạm thời disable foreign key checks để có thể sửa cấu trúc
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Sửa cột QD_SO để cho phép NULL
    $alter_sql = "ALTER TABLE de_tai_nghien_cuu MODIFY COLUMN QD_SO char(5) NULL";
    
    if ($conn->query($alter_sql)) {
        echo "✅ Đã sửa cột QD_SO để cho phép NULL" . PHP_EOL;
    } else {
        echo "❌ Lỗi khi sửa cột QD_SO: " . $conn->error . PHP_EOL;
    }
    
    // Bật lại foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Kiểm tra lại cấu trúc
    echo PHP_EOL . "=== Kiểm tra lại cấu trúc ===" . PHP_EOL;
    $result = $conn->query("SHOW COLUMNS FROM de_tai_nghien_cuu WHERE Field = 'QD_SO'");
    if ($result && $result->num_rows > 0) {
        $col = $result->fetch_assoc();
        echo "QD_SO column after modification:" . PHP_EOL;
        echo "- Type: {$col['Type']}" . PHP_EOL;
        echo "- Null: {$col['Null']}" . PHP_EOL;
        echo "- Key: {$col['Key']}" . PHP_EOL;
    }
    
    // Cập nhật các đề tài hiện có không có quyết định hợp lệ
    echo PHP_EOL . "=== Cập nhật dữ liệu hiện có ===" . PHP_EOL;
    $update_sql = "UPDATE de_tai_nghien_cuu 
                   SET QD_SO = NULL 
                   WHERE DT_TRANGTHAI IN ('Chờ duyệt', 'Đang thực hiện') 
                   AND QD_SO NOT IN (SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE QD_SO IS NOT NULL)";
    
    if ($conn->query($update_sql)) {
        $affected_rows = $conn->affected_rows;
        echo "✅ Đã cập nhật {$affected_rows} đề tài để có QD_SO = NULL" . PHP_EOL;
    } else {
        echo "❌ Lỗi khi cập nhật dữ liệu: " . $conn->error . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Hoàn thành ===" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
