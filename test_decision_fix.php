<?php
// Test script to verify decision table structure and query syntax
include 'include/connect.php';

echo "<h2>Testing Decision Update Fixes</h2>";

// Test 1: Check decision table structure
echo "<h3>1. Decision Table (quyet_dinh_nghiem_thu) Structure:</h3>";
$result = $conn->query("DESCRIBE quyet_dinh_nghiem_thu");
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

// Test 2: Check bien_ban table structure
echo "<h3>2. Report Table (bien_ban) Structure:</h3>";
$result = $conn->query("DESCRIBE bien_ban");
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

// Test 3: Test UPDATE query syntax for bien_ban
echo "<h3>3. Testing UPDATE Query for bien_ban:</h3>";
$test_update_sql = "UPDATE bien_ban SET 
                   BB_NGAYNGHIEMTHU = ?, 
                   BB_XEPLOAI = ? 
                   WHERE QD_SO = ?";

$stmt = $conn->prepare($test_update_sql);
if ($stmt) {
    echo "✅ UPDATE query syntax for bien_ban is correct<br>";
    echo "Query: " . htmlspecialchars($test_update_sql) . "<br>";
} else {
    echo "❌ UPDATE query syntax error: " . $conn->error . "<br>";
    echo "Query: " . htmlspecialchars($test_update_sql) . "<br>";
}

// Test 4: Test UPDATE query syntax for quyet_dinh_nghiem_thu
echo "<h3>4. Testing UPDATE Query for quyet_dinh_nghiem_thu:</h3>";
$test_update_decision_sql = "UPDATE quyet_dinh_nghiem_thu SET 
                            QD_NGAY = ?, 
                            QD_FILE = ? 
                            WHERE QD_SO = ?";

$stmt = $conn->prepare($test_update_decision_sql);
if ($stmt) {
    echo "✅ UPDATE query syntax for quyet_dinh_nghiem_thu is correct<br>";
    echo "Query: " . htmlspecialchars($test_update_decision_sql) . "<br>";
} else {
    echo "❌ UPDATE query syntax error: " . $conn->error . "<br>";
    echo "Query: " . htmlspecialchars($test_update_decision_sql) . "<br>";
}

// Test 5: Test INSERT query syntax for bien_ban
echo "<h3>5. Testing INSERT Query for bien_ban:</h3>";
$test_insert_report_sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) 
                          VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($test_insert_report_sql);
if ($stmt) {
    echo "✅ INSERT query syntax for bien_ban is correct<br>";
    echo "Query: " . htmlspecialchars($test_insert_report_sql) . "<br>";
} else {
    echo "❌ INSERT query syntax error: " . $conn->error . "<br>";
    echo "Query: " . htmlspecialchars($test_insert_report_sql) . "<br>";
}

// Test 6: Test INSERT query syntax for quyet_dinh_nghiem_thu
echo "<h3>6. Testing INSERT Query for quyet_dinh_nghiem_thu:</h3>";
$test_insert_decision_sql = "INSERT INTO quyet_dinh_nghiem_thu (QD_SO, BB_SOBB, QD_NGAY, QD_FILE) 
                            VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($test_insert_decision_sql);
if ($stmt) {
    echo "✅ INSERT query syntax for quyet_dinh_nghiem_thu is correct<br>";
    echo "Query: " . htmlspecialchars($test_insert_decision_sql) . "<br>";
} else {
    echo "❌ INSERT query syntax error: " . $conn->error . "<br>";
    echo "Query: " . htmlspecialchars($test_insert_decision_sql) . "<br>";
}

// Test 7: Check existing data samples
echo "<h3>7. Sample Decision Data:</h3>";
$result = $conn->query("SELECT * FROM quyet_dinh_nghiem_thu LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>QD_SO</th><th>BB_SOBB</th><th>QD_NGAY</th><th>QD_FILE</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No decision data found or error: " . $conn->error;
}

echo "<h3>8. Sample Report Data:</h3>";
$result = $conn->query("SELECT * FROM bien_ban LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>BB_SOBB</th><th>QD_SO</th><th>BB_NGAYNGHIEMTHU</th><th>BB_XEPLOAI</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No report data found or error: " . $conn->error;
}

$conn->close();
?>
