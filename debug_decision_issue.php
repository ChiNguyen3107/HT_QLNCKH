<?php
include 'include/connect.php';

echo "=== KIỂM TRA VẤN ĐỀ QUYẾT ĐỊNH VÀ BIÊN BẢN ===\n\n";

// 1. Kiểm tra cấu trúc bảng bien_ban
echo "1. Cấu trúc bảng bien_ban:\n";
$result = $conn->query("DESCRIBE bien_ban");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']} | Default: {$row['Default']}\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n2. Cấu trúc bảng quyet_dinh_nghiem_thu:\n";
$result = $conn->query("DESCRIBE quyet_dinh_nghiem_thu");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']} | Default: {$row['Default']}\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n3. Kiểm tra ràng buộc khóa ngoại:\n";
$result = $conn->query("
    SELECT 
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'bien_ban' 
    AND TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']} (Constraint: {$row['CONSTRAINT_NAME']})\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n4. Test tạo biên bản (DRY RUN):\n";
// Thử tạo biên bản với dữ liệu test
$test_report_code = "BB999TEST";
$test_decision_number = "QD999TEST";
$test_date = date('Y-m-d');
$test_grade = "Chưa nghiệm thu";

echo "Thử tạo với dữ liệu:\n";
echo "- BB_SOBB: $test_report_code\n";
echo "- QD_SO: $test_decision_number\n";
echo "- BB_NGAYNGHIEMTHU: $test_date\n";
echo "- BB_XEPLOAI: $test_grade\n";

// Kiểm tra SQL statement
$sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, ?, ?, ?)";
echo "\nSQL Statement: $sql\n";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Lỗi prepare statement: " . $conn->error . "\n";
} else {
    echo "Prepare statement thành công\n";
    
    // Bind parameters
    $stmt->bind_param("ssss", $test_report_code, $test_decision_number, $test_date, $test_grade);
    echo "Bind parameters thành công\n";
    
    // Không thực thi thực sự, chỉ kiểm tra
    echo "Test statement sẵn sàng thực thi (không thực thi thật)\n";
}

echo "\n5. Kiểm tra dữ liệu hiện tại:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM bien_ban");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Số biên bản hiện tại: " . $row['count'] . "\n";
}

$result = $conn->query("SELECT COUNT(*) as count FROM quyet_dinh_nghiem_thu");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Số quyết định hiện tại: " . $row['count'] . "\n";
}

echo "\n6. Kiểm tra mối quan hệ:\n";
$result = $conn->query("
    SELECT 
        q.QD_SO, 
        b.BB_SOBB,
        q.QD_NGAY,
        b.BB_NGAYNGHIEMTHU,
        b.BB_XEPLOAI
    FROM quyet_dinh_nghiem_thu q
    LEFT JOIN bien_ban b ON q.QD_SO = b.QD_SO
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "QD: {$row['QD_SO']} -> BB: {$row['BB_SOBB']} | QD_Date: {$row['QD_NGAY']} | BB_Date: {$row['BB_NGAYNGHIEMTHU']} | Grade: {$row['BB_XEPLOAI']}\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n=== KẾT THÚC KIỂM TRA ===\n";
?>
