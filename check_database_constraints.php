<?php
// Check Database Constraints
require_once 'include/connect.php';

echo "=== KIỂM TRA DATABASE CONSTRAINTS ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Kiểm tra cấu trúc bảng file_dinh_kem
echo "1. Cấu trúc bảng file_dinh_kem:\n";
$result = $conn->query("DESCRIBE file_dinh_kem");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['Field'] . " (" . $row['Type'] . ") " . 
             ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . 
             ($row['Key'] ? ' [' . $row['Key'] . ']' : '') . "\n";
    }
}

// 2. Kiểm tra foreign keys
echo "\n2. Foreign Key Constraints:\n";
$result = $conn->query("
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'file_dinh_kem' 
    AND TABLE_SCHEMA = 'ql_nckh'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['CONSTRAINT_NAME'] . ": " . 
             $row['COLUMN_NAME'] . " → " . 
             $row['REFERENCED_TABLE_NAME'] . "." . 
             $row['REFERENCED_COLUMN_NAME'] . "\n";
    }
} else {
    echo "   Không có foreign key constraints\n";
}

// 3. Kiểm tra bảng bien_ban
echo "\n3. Kiểm tra bảng bien_ban:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM bien_ban");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   - Tổng số biên bản: " . $row['count'] . "\n";
}

// Lấy một vài BB_SOBB mẫu
$result = $conn->query("SELECT BB_SOBB FROM bien_ban LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "   - Một vài BB_SOBB mẫu:\n";
    while ($row = $result->fetch_assoc()) {
        echo "     * " . $row['BB_SOBB'] . "\n";
    }
} else {
    echo "   - Không có dữ liệu biên bản\n";
}

// 4. Giải pháp
echo "\n4. GIẢI PHÁP:\n";
echo "   Option 1: Sử dụng BB_SOBB có sẵn\n";
echo "   Option 2: Cho phép BB_SOBB = NULL\n";
echo "   Option 3: Tạo biên bản dummy\n";
echo "   Option 4: Sửa constraint\n";

// 5. Kiểm tra có thể set NULL không
echo "\n5. Kiểm tra cột BB_SOBB có cho phép NULL không:\n";
$result = $conn->query("
    SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'file_dinh_kem' 
    AND COLUMN_NAME = 'BB_SOBB'
    AND TABLE_SCHEMA = 'ql_nckh'
");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "   - IS_NULLABLE: " . $row['IS_NULLABLE'] . "\n";
    echo "   - DEFAULT: " . ($row['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 KHUYẾN NGHỊ:\n";
echo "Dựa trên thông tin trên, chọn giải pháp phù hợp để sửa lỗi foreign key constraint.\n";
?>
