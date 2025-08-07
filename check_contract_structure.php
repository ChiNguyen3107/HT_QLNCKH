<?php
// Kiểm tra cấu trúc bảng hợp đồng
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Checking contract table structure...\n";
$result = $conn->query('DESCRIBE hop_dong');
if ($result) {
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'HD_MA') {
            echo "HD_MA field details:\n";
            echo "  Type: " . $row['Type'] . "\n";
            echo "  Null: " . $row['Null'] . "\n";
            echo "  Key: " . $row['Key'] . "\n";
            echo "  Default: " . $row['Default'] . "\n";
            echo "  Extra: " . $row['Extra'] . "\n";
        }
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nChecking existing contract codes length...\n";
$result = $conn->query('SELECT HD_MA, LENGTH(HD_MA) as length FROM hop_dong ORDER BY LENGTH(HD_MA) DESC LIMIT 10');
if ($result) {
    if ($result->num_rows > 0) {
        echo "Existing contract codes:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  Code: " . $row['HD_MA'] . " (Length: " . $row['length'] . ")\n";
        }
    } else {
        echo "No contract codes found in database.\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nChecking if any contract codes might be truncated...\n";
$result = $conn->query('SELECT COUNT(*) as count FROM hop_dong WHERE LENGTH(HD_MA) >= 10');
if ($result) {
    $row = $result->fetch_assoc();
    echo "Number of contract codes with 10+ characters: " . $row['count'] . "\n";
}

$conn->close();
?>
