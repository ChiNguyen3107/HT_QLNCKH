<?php
// Test script to verify contract table structure and query syntax
include 'include/connect.php';

echo "<h2>Testing Contract Table Structure</h2>";

// Test 1: Check table structure
echo "<h3>1. Contract Table Structure:</h3>";
$result = $conn->query("DESCRIBE hop_dong");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Test 2: Test INSERT query syntax
echo "<h3>2. Testing INSERT Query Syntax:</h3>";
$test_sql = "INSERT INTO hop_dong (HD_MA, DT_MADT, HD_NGAYTAO, HD_NGAYBD, HD_NGAYKT, HD_TONGKINHPHI, HD_GHICHU, HD_FILEHD) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($test_sql);
if ($stmt) {
    echo "✅ INSERT query syntax is correct<br>";
    echo "Query: " . htmlspecialchars($test_sql) . "<br>";
} else {
    echo "❌ INSERT query syntax error: " . $conn->error . "<br>";
    echo "Query: " . htmlspecialchars($test_sql) . "<br>";
}

// Test 3: Test UPDATE query syntax
echo "<h3>3. Testing UPDATE Query Syntax:</h3>";
$test_update_sql = "UPDATE hop_dong SET 
                   HD_MA = ?, 
                   HD_NGAYTAO = ?, 
                   HD_NGAYBD = ?, 
                   HD_NGAYKT = ?, 
                   HD_TONGKINHPHI = ?, 
                   HD_GHICHU = ?, 
                   HD_FILEHD = ? 
                   WHERE HD_MA = ?";

$stmt = $conn->prepare($test_update_sql);
if ($stmt) {
    echo "✅ UPDATE query syntax is correct<br>";
    echo "Query: " . htmlspecialchars($test_update_sql) . "<br>";
} else {
    echo "❌ UPDATE query syntax error: " . $conn->error . "<br>";
    echo "Query: " . htmlspecialchars($test_update_sql) . "<br>";
}

// Test 4: Check if there are any existing contracts to understand the data
echo "<h3>4. Sample Contract Data:</h3>";
$result = $conn->query("SELECT * FROM hop_dong LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr>";
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>" . htmlspecialchars($field->name) . "</th>";
    }
    echo "</tr>";
    
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No contract data found or error: " . $conn->error;
}

// Test 5: Check project table structure
echo "<h3>5. Project Table Structure (relevant fields):</h3>";
$result = $conn->query("DESCRIBE de_tai_nghien_cuu");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        // Only show relevant fields
        if (in_array($row['Field'], ['DT_MADT', 'HD_MA', 'DT_TENDT', 'DT_TRANGTHAI'])) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
