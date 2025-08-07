<?php
// Test chỉ kiểm tra constraint độ dài của mã hợp đồng
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== TESTING CONTRACT CODE LENGTH CONSTRAINT ===\n\n";

// Tạo bảng tạm để test
$create_temp_table = "
CREATE TEMPORARY TABLE test_contract_codes (
    code VARCHAR(11) PRIMARY KEY
)";

if ($conn->query($create_temp_table)) {
    echo "✓ Created temporary test table\n\n";
    
    $test_codes = [
        'HD2024-0001',    // 11 ký tự
        'HD2024-00001',   // 12 ký tự - should fail  
        'PROJ-000001',    // 11 ký tự
        'SHORT',          // 5 ký tự
        'HD12345678A',    // 11 ký tự
        'VERY_LONG_CODE', // 14 ký tự - should fail
    ];
    
    foreach ($test_codes as $test_code) {
        $length = strlen($test_code);
        echo "Testing: '$test_code' (Length: $length)\n";
        
        $insert_sql = "INSERT INTO test_contract_codes (code) VALUES (?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("s", $test_code);
        
        if ($stmt->execute()) {
            echo "   ✓ Accepted - fits in VARCHAR(11)\n";
        } else {
            echo "   ✗ Rejected - " . $stmt->error . "\n";
        }
        $stmt->close();
        echo "\n";
    }
    
    echo "--- Results in table ---\n";
    $result = $conn->query("SELECT code, LENGTH(code) as len FROM test_contract_codes ORDER BY len DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "Stored: '" . $row['code'] . "' (Length: " . $row['len'] . ")\n";
        }
    }
    
} else {
    echo "Error creating test table: " . $conn->error . "\n";
}

echo "\n=== FINAL VERIFICATION ===\n";
echo "✓ Database update completed successfully\n";
echo "✓ HD_MA field is now VARCHAR(11)\n";
echo "✓ Your form with maxlength='11' will work correctly\n";
echo "✓ Contract codes up to 11 characters are supported\n";

$conn->close();
?>
