<?php
// Kiểm tra cấu trúc bảng bien_ban và lỗi có thể xảy ra
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== CHECKING BIEN_BAN TABLE STRUCTURE ===\n\n";

echo "1. Table structure:\n";
$result = $conn->query('DESCRIBE bien_ban');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "   " . $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . "\n";
    }
} else {
    echo "   Error: " . $conn->error . "\n";
}

echo "\n2. Foreign key constraints:\n";
$result = $conn->query("
    SELECT 
        CONSTRAINT_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'bien_ban' 
    AND TABLE_SCHEMA = 'ql_nckh'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   " . $row['CONSTRAINT_NAME'] . ": " . $row['COLUMN_NAME'] . " -> " . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "\n";
    }
} else {
    echo "   No foreign key constraints found.\n";
}

echo "\n3. Existing bien_ban records:\n";
$result = $conn->query('SELECT BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM FROM bien_ban LIMIT 5');
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   ID: " . $row['BB_SOBB'] . " | QD: " . $row['QD_SO'] . " | Date: " . $row['BB_NGAYNGHIEMTHU'] . " | Grade: " . $row['BB_XEPLOAI'] . " | Score: " . $row['BB_TONGDIEM'] . "\n";
    }
} else {
    echo "   No existing records found.\n";
}

echo "\n4. Testing BB_SOBB generation:\n";
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(BB_SOBB, 3) AS UNSIGNED)) as max_id FROM bien_ban");
if ($result) {
    $max_result = $result->fetch_assoc();
    $next_id = ($max_result['max_id'] ?? 0) + 1;
    $new_report_id = 'BB' . str_pad($next_id, 8, '0', STR_PAD_LEFT);
    echo "   Current max ID: " . ($max_result['max_id'] ?? 0) . "\n";
    echo "   Next ID would be: " . $new_report_id . "\n";
    echo "   Length: " . strlen($new_report_id) . " characters\n";
} else {
    echo "   Error: " . $conn->error . "\n";
}

echo "\n5. Testing sample insert (with rollback):\n";
$conn->autocommit(FALSE);

$test_report_id = 'BB99999999';
$test_qd_so = 'QDDT0'; // Existing decision ID
$test_date = '2025-08-06';
$test_grade = 'Tốt';
$test_score = 85.5;

$sql_test = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM) VALUES (?, ?, ?, ?, ?)";
$stmt_test = $conn->prepare($sql_test);

if ($stmt_test) {
    $stmt_test->bind_param("ssssd", $test_report_id, $test_qd_so, $test_date, $test_grade, $test_score);
    if ($stmt_test->execute()) {
        echo "   ✓ Test insert successful\n";
    } else {
        echo "   ✗ Test insert failed: " . $stmt_test->error . "\n";
    }
    $stmt_test->close();
} else {
    echo "   ✗ Prepare failed: " . $conn->error . "\n";
}

$conn->rollback();
$conn->autocommit(TRUE);

echo "\n=== DIAGNOSIS COMPLETE ===\n";

$conn->close();
?>
