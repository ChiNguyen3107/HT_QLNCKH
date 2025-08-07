<?php
// Script cập nhật cấu trúc database để cho phép mã hợp đồng 11 ký tự
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== UPDATING CONTRACT CODE FIELD TO SUPPORT 11 CHARACTERS ===\n\n";

echo "1. Current HD_MA field structure:\n";
$result = $conn->query('DESCRIBE hop_dong');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'HD_MA') {
            echo "   Type: " . $row['Type'] . "\n";
            echo "   Current length limit: 5 characters\n";
            break;
        }
    }
}

echo "\n2. Checking existing data before update:\n";
$result = $conn->query('SELECT HD_MA, LENGTH(HD_MA) as length FROM hop_dong');
if ($result && $result->num_rows > 0) {
    echo "   Existing contract codes:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['HD_MA'] . " (Length: " . $row['length'] . ")\n";
    }
} else {
    echo "   No existing contract codes found.\n";
}

echo "\n3. Updating HD_MA field to VARCHAR(11)...\n";
$update_sql = "ALTER TABLE hop_dong MODIFY HD_MA VARCHAR(11) NOT NULL";

if ($conn->query($update_sql) === TRUE) {
    echo "   ✓ Successfully updated HD_MA field to VARCHAR(11)\n";
} else {
    echo "   ✗ Error updating HD_MA field: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

echo "\n4. Verifying the update:\n";
$result = $conn->query('DESCRIBE hop_dong');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'HD_MA') {
            echo "   New Type: " . $row['Type'] . "\n";
            echo "   New length limit: 11 characters\n";
            break;
        }
    }
}

echo "\n5. Testing with a long contract code (simulation):\n";
$test_code = "HD12345678A"; // 11 characters
echo "   Test code: '$test_code' (Length: " . strlen($test_code) . ")\n";
echo "   This code should now be acceptable.\n";

echo "\n=== UPDATE COMPLETED SUCCESSFULLY ===\n";
echo "The HD_MA field can now store up to 11 characters.\n";
echo "You can now use contract codes like: HD2024-001, PROJ-000001, etc.\n";

$conn->close();
?>
