<?php
/**
 * Script thay đổi kết nối cơ sở dữ liệu từ ql_nckh sang ql_nckh_test
 * Chạy script này để cập nhật tất cả các file có kết nối đến cơ sở dữ liệu
 */

// Danh sách các file cần thay đổi
$files_to_update = [
    'include/config.php',
    'include/connect.php',
    'check_contract_structure.php',
    'check_decision_structure.php',
    'debug_bien_ban_structure.php',
    'debug_bien_ban_creation.php',
    'test_contract_codes.php',
    'test_length_constraint.php',
    'update_decision_field.php',
    'update_decision_field_safe.php',
    'update_contract_field.php'
];

// Thay đổi từ ql_nckh sang ql_nckh_test
$old_db_name = 'ql_nckh';
$new_db_name = 'ql_nckh_test';

echo "Bắt đầu thay đổi kết nối cơ sở dữ liệu...\n";
echo "Từ: $old_db_name\n";
echo "Sang: $new_db_name\n\n";

$success_count = 0;
$error_count = 0;

foreach ($files_to_update as $file) {
    if (file_exists($file)) {
        try {
            // Đọc nội dung file
            $content = file_get_contents($file);
            
            // Thay thế tên cơ sở dữ liệu
            $new_content = str_replace($old_db_name, $new_db_name, $content);
            
            // Ghi lại file
            if (file_put_contents($file, $new_content)) {
                echo "✓ Đã cập nhật: $file\n";
                $success_count++;
            } else {
                echo "✗ Lỗi ghi file: $file\n";
                $error_count++;
            }
        } catch (Exception $e) {
            echo "✗ Lỗi xử lý file $file: " . $e->getMessage() . "\n";
            $error_count++;
        }
    } else {
        echo "⚠ File không tồn tại: $file\n";
    }
}

echo "\n=== KẾT QUẢ ===\n";
echo "Thành công: $success_count file\n";
echo "Lỗi: $error_count file\n";

// Tạo file SQL để tạo cơ sở dữ liệu mới
echo "\nTạo file SQL cho cơ sở dữ liệu mới...\n";
$sql_content = "-- Tạo cơ sở dữ liệu mới ql_nckh_test\n";
$sql_content .= "CREATE DATABASE IF NOT EXISTS `$new_db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;\n";
$sql_content .= "USE `$new_db_name`;\n\n";

// Đọc file SQL gốc và thay đổi tên cơ sở dữ liệu
if (file_exists('ql_nckh.sql')) {
    $original_sql = file_get_contents('ql_nckh.sql');
    $new_sql = str_replace($old_db_name, $new_db_name, $original_sql);
    
    // Ghi file SQL mới
    $new_sql_file = 'ql_nckh_test.sql';
    if (file_put_contents($new_sql_file, $new_sql)) {
        echo "✓ Đã tạo file SQL mới: $new_sql_file\n";
    } else {
        echo "✗ Lỗi tạo file SQL mới\n";
    }
}

echo "\n=== HƯỚNG DẪN TIẾP THEO ===\n";
echo "1. Import file ql_nckh_test.sql vào MySQL để tạo cơ sở dữ liệu mới\n";
echo "2. Kiểm tra kết nối bằng cách chạy một file PHP bất kỳ\n";
echo "3. Nếu cần rollback, chạy script rollback_database_connection.php\n";
?>

