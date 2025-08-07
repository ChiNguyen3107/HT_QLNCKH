<?php
require_once 'include/database.php';

try {
    $conn = connectDB();
    
    echo "=== KIỂM TRA CẤU TRÚC DATABASE ===" . PHP_EOL;
    
    // Lấy cấu trúc thực tế của bảng de_tai_nghien_cuu
    echo "1. Cấu trúc bảng de_tai_nghien_cuu:" . PHP_EOL;
    $result = $conn->query("DESCRIBE de_tai_nghien_cuu");
    while ($row = $result->fetch_assoc()) {
        echo "   - {$row['Field']} ({$row['Type']}) " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . PHP_EOL;
    }
    
    // Kiểm tra foreign keys
    echo PHP_EOL . "2. Foreign key constraints:" . PHP_EOL;
    $fk_query = "SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                 FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = 'ql_nckh' 
                   AND TABLE_NAME = 'de_tai_nghien_cuu' 
                   AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $fk_result = $conn->query($fk_query);
    while ($fk = $fk_result->fetch_assoc()) {
        echo "   - {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}" . PHP_EOL;
    }
    
    // Kiểm tra dữ liệu mẫu trong các bảng liên quan
    echo PHP_EOL . "3. Dữ liệu mẫu trong các bảng liên quan:" . PHP_EOL;
    
    $tables = [
        'loai_de_tai' => 'LDT_MA',
        'giang_vien' => 'GV_MAGV', 
        'linh_vuc_nghien_cuu' => 'LVNC_MA',
        'linh_vuc_uu_tien' => 'LVUT_MA'
    ];
    
    foreach ($tables as $table => $id_column) {
        $sample_query = "SELECT {$id_column} FROM {$table} LIMIT 3";
        $sample_result = $conn->query($sample_query);
        
        echo "   $table:" . PHP_EOL;
        if ($sample_result && $sample_result->num_rows > 0) {
            while ($sample = $sample_result->fetch_assoc()) {
                echo "     - {$sample[$id_column]}" . PHP_EOL;
            }
        } else {
            echo "     (không có dữ liệu)" . PHP_EOL;
        }
    }
    
    // Kiểm tra bảng hợp đồng nếu có
    echo PHP_EOL . "4. Kiểm tra bảng hợp đồng:" . PHP_EOL;
    $hd_tables = $conn->query("SHOW TABLES LIKE '%hop_dong%'");
    if ($hd_tables && $hd_tables->num_rows > 0) {
        while ($table = $hd_tables->fetch_array()) {
            echo "   - Bảng: {$table[0]}" . PHP_EOL;
            $hd_sample = $conn->query("SELECT * FROM {$table[0]} LIMIT 1");
            if ($hd_sample && $hd_sample->num_rows > 0) {
                $sample = $hd_sample->fetch_assoc();
                echo "     Cấu trúc: " . implode(', ', array_keys($sample)) . PHP_EOL;
            }
        }
    } else {
        echo "   - Không có bảng hợp đồng" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . PHP_EOL;
}
?>
