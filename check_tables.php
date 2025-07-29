<?php
include 'include/connect.php';

echo "Danh sách tất cả các bảng trong database:\n";

$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tableName = array_values($row)[0];
        echo "- " . $tableName . "\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\nKiểm tra bảng có chứa từ 'don_vi' hoặc tương tự:\n";
$sql = "SHOW TABLES LIKE '%vi%'";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tableName = array_values($row)[0];
        echo "- " . $tableName . "\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

$conn->close();
?>
