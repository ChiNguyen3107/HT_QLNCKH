<?php
include 'include/connect.php';

echo "=== CẤU TRÚC BẢNG THÀNH VIÊN HỘI ĐỒNG ===\n\n";

$result = $conn->query("DESCRIBE thanh_vien_hoi_dong");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']} | Default: {$row['Default']}\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n=== SAMPLE DATA ===\n";
$result = $conn->query("SELECT * FROM thanh_vien_hoi_dong LIMIT 3");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Row: ";
        foreach ($row as $key => $value) {
            echo "$key=$value | ";
        }
        echo "\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n=== KẾT THÚC ===\n";
?>
