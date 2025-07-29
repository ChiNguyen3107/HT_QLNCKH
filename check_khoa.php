<?php
include 'include/connect.php';

echo "Cấu trúc bảng giang_vien:\n";
$sql = "DESCRIBE giang_vien";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nCấu trúc bảng khoa:\n";
$sql = "DESCRIBE khoa";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nDữ liệu mẫu bảng khoa:\n";
$sql = "SELECT * FROM khoa LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$conn->close();
?>
