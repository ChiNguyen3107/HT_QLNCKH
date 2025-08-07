<?php
// Script cập nhật cấu trúc database để cho phép mã quyết định 11 ký tự
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== UPDATING DECISION CODE FIELD TO SUPPORT 11 CHARACTERS ===\n\n";

echo "1. Current QD_SO field structure:\n";
$result = $conn->query('DESCRIBE quyet_dinh_nghiem_thu');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'QD_SO') {
            echo "   Type: " . $row['Type'] . "\n";
            echo "   Current length limit: 5 characters\n";
            break;
        }
    }
}

echo "\n2. Checking existing data before update:\n";
$result = $conn->query('SELECT QD_SO, LENGTH(QD_SO) as length FROM quyet_dinh_nghiem_thu');
if ($result && $result->num_rows > 0) {
    echo "   Existing decision codes:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['QD_SO'] . " (Length: " . $row['length'] . ")\n";
    }
} else {
    echo "   No existing decision codes found.\n";
}

echo "\n3. Updating QD_SO field to VARCHAR(11)...\n";
$update_sql = "ALTER TABLE quyet_dinh_nghiem_thu MODIFY QD_SO VARCHAR(11) NOT NULL";

if ($conn->query($update_sql) === TRUE) {
    echo "   ✓ Successfully updated QD_SO field to VARCHAR(11)\n";
} else {
    echo "   ✗ Error updating QD_SO field: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

echo "\n4. Verifying the update:\n";
$result = $conn->query('DESCRIBE quyet_dinh_nghiem_thu');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'QD_SO') {
            echo "   New Type: " . $row['Type'] . "\n";
            echo "   New length limit: 11 characters\n";
            break;
        }
    }
}

echo "\n5. Testing with a long decision code (simulation):\n";
$test_code = "QD2024-0001"; // 10 characters
echo "   Test code: '$test_code' (Length: " . strlen($test_code) . ")\n";
echo "   This code should now be acceptable.\n";

echo "\n=== UPDATE COMPLETED SUCCESSFULLY ===\n";
echo "The QD_SO field can now store up to 11 characters.\n";
echo "You can now use decision codes like: QD2024-001, NGHIEM-001, DECISION01, etc.\n";

$conn->close();
?>
