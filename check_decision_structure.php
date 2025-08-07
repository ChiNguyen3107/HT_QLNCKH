<?php
// Kiểm tra cấu trúc bảng quyết định
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Checking decision table structure...\n";
$result = $conn->query('DESCRIBE quyet_dinh_nghiem_thu');
if ($result) {
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'QD_SO') {
            echo "QD_SO field details:\n";
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

echo "\nChecking existing decision codes length...\n";
$result = $conn->query('SELECT QD_SO, LENGTH(QD_SO) as length FROM quyet_dinh_nghiem_thu ORDER BY LENGTH(QD_SO) DESC LIMIT 10');
if ($result) {
    if ($result->num_rows > 0) {
        echo "Existing decision codes:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  Code: " . $row['QD_SO'] . " (Length: " . $row['length'] . ")\n";
        }
    } else {
        echo "No decision codes found in database.\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nChecking if any decision codes might be truncated...\n";
$result = $conn->query('SELECT COUNT(*) as count FROM quyet_dinh_nghiem_thu WHERE LENGTH(QD_SO) >= 10');
if ($result) {
    $row = $result->fetch_assoc();
    echo "Number of decision codes with 10+ characters: " . $row['count'] . "\n";
}

$conn->close();
?>
