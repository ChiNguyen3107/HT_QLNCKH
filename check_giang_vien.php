<?php
require_once 'include/connect.php';
$conn = new mysqli($servername, $username, $password, $dbname);

echo "giang_vien table structure:\n";
$result = $conn->query('DESCRIBE giang_vien');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

echo "\nSample data:\n";
$result = $conn->query('SELECT * FROM giang_vien LIMIT 2');
while($row = $result->fetch_assoc()) {
    echo "GV_MAGV: " . $row['GV_MAGV'] . "\n";
    echo "Available fields: " . implode(", ", array_keys($row)) . "\n\n";
}

$conn->close();
?>
