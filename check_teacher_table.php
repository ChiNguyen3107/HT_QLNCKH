<?php
include 'include/connect.php';

echo "Kiểm tra cấu trúc bảng giang_vien:\n";

$sql = "DESCRIBE giang_vien";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\nKiểm tra dữ liệu mẫu:\n";
$sql = "SELECT * FROM giang_vien LIMIT 3";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

$conn->close();
?>
