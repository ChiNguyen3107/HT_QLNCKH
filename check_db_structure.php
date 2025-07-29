<?php
include 'include/connect.php';

echo "=== KIỂM TRA CẤU TRÚC DATABASE ===\n";

// 1. Kiểm tra cấu trúc bảng bien_ban
echo "\n1. Cấu trúc bảng bien_ban:\n";
$result = $conn->query("DESCRIBE bien_ban");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']} | Default: {$row['Default']}\n";
}

// 2. Kiểm tra cấu trúc bảng quyet_dinh_nghiem_thu
echo "\n2. Cấu trúc bảng quyet_dinh_nghiem_thu:\n";
$result = $conn->query("DESCRIBE quyet_dinh_nghiem_thu");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']} | Default: {$row['Default']}\n";
}

// 3. Kiểm tra foreign key constraints
echo "\n3. Foreign key constraints:\n";
$result = $conn->query("SELECT 
    CONSTRAINT_NAME, 
    TABLE_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE CONSTRAINT_SCHEMA = 'ql_nckh' 
AND REFERENCED_TABLE_NAME IS NOT NULL 
AND (TABLE_NAME = 'bien_ban' OR TABLE_NAME = 'quyet_dinh_nghiem_thu')");

while ($row = $result->fetch_assoc()) {
    echo "- {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
}

// 4. Kiểm tra dữ liệu hiện tại
echo "\n4. Dữ liệu hiện tại:\n";
$result = $conn->query('SELECT COUNT(*) as count FROM quyet_dinh_nghiem_thu');
$row = $result->fetch_assoc();
echo "- Số quyết định nghiệm thu: {$row['count']}\n";

$result = $conn->query('SELECT COUNT(*) as count FROM bien_ban');
$row = $result->fetch_assoc();
echo "- Số biên bản: {$row['count']}\n";

// 5. Kiểm tra mối quan hệ dữ liệu
echo "\n5. Mối quan hệ dữ liệu (3 record đầu):\n";
$result = $conn->query('SELECT qd.QD_SO, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI 
    FROM quyet_dinh_nghiem_thu qd 
    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO 
    LIMIT 3');
while ($row = $result->fetch_assoc()) {
    echo "- QD: {$row['QD_SO']} -> BB: " . ($row['BB_SOBB'] ?? 'NULL') . 
         " | Ngày: " . ($row['BB_NGAYNGHIEMTHU'] ?? 'NULL') . 
         " | Xếp loại: " . ($row['BB_XEPLOAI'] ?? 'NULL') . "\n";
}

$conn->close();
?>
