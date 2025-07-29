<?php
// Test script to verify progress entry creation and ID generation
include 'include/connect.php';

echo "<h2>Testing Progress Entry Creation</h2>";

// Test 1: Check current progress entries
echo "<h3>1. Current Progress Entries Sample:</h3>";
$result = $conn->query("SELECT TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NGAYCAPNHAT FROM tien_do_de_tai ORDER BY TDDT_NGAYCAPNHAT DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Project</th><th>Student</th><th>Title</th><th>Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['TDDT_MA']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SV_MASV']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['TDDT_TIEUDE'], 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['TDDT_NGAYCAPNHAT']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No progress entries found or error: " . $conn->error;
}

// Test 2: Test ID generation algorithm
echo "<h3>2. Testing ID Generation Algorithm:</h3>";
for ($i = 0; $i < 10; $i++) {
    $progress_id = 'TD' . date('ymdHi') . rand(100, 999);
    echo "Generated ID $i: <strong>$progress_id</strong><br>";
}

// Test 3: Test INSERT query syntax
echo "<h3>3. Testing INSERT Query Syntax:</h3>";
$test_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
             VALUES (?, ?, ?, ?, ?, NOW(), 0)";

$stmt = $conn->prepare($test_sql);
if ($stmt) {
    echo "✅ INSERT query syntax is correct<br>";
    echo "Query: " . htmlspecialchars($test_sql) . "<br>";
} else {
    echo "❌ INSERT query syntax error: " . $conn->error . "<br>";
    echo "Query: " . htmlspecialchars($test_sql) . "<br>";
}

// Test 4: Check for potential ID conflicts
echo "<h3>4. Checking for ID Pattern Conflicts:</h3>";
$current_pattern = 'TD' . date('ymdHi') . '%';
$result = $conn->query("SELECT COUNT(*) as count FROM tien_do_de_tai WHERE TDDT_MA LIKE '$current_pattern'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Existing entries with current pattern (TD" . date('ymdHi') . "*): " . $row['count'] . "<br>";
} else {
    echo "Error checking patterns: " . $conn->error;
}

// Test 5: Check table structure
echo "<h3>5. Progress Table Structure:</h3>";
$result = $conn->query("DESCRIBE tien_do_de_tai");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
