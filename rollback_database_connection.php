<?php
/**
 * Script rollback để quay lại kết nối cơ sở dữ liệu ql_nckh
 * Chạy script này nếu muốn quay lại cơ sở dữ liệu cũ
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

// Thay đổi từ ql_nckh_test về ql_nckh
$old_db_name = 'ql_nckh_test';
$new_db_name = 'ql_nckh';

echo "Bắt đầu rollback kết nối cơ sở dữ liệu...\n";
echo "Từ: $old_db_name\n";
echo "Về: $new_db_name\n\n";

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
                echo "✓ Đã rollback: $file\n";
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

echo "\n=== KẾT QUẢ ROLLBACK ===\n";
echo "Thành công: $success_count file\n";
echo "Lỗi: $error_count file\n";

echo "\n=== HƯỚNG DẪN ===\n";
echo "Đã quay lại kết nối cơ sở dữ liệu ql_nckh\n";
echo "Hệ thống sẽ sử dụng cơ sở dữ liệu gốc\n";
?>

