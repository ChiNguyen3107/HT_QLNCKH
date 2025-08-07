<?php
include 'include/connect.php';

echo "=== CẤU TRÚC BẢNG THANH_VIEN_HOI_DONG ===\n";

$result = $conn->query("DESCRIBE thanh_vien_hoi_dong");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | Key: {$row['Key']} | Default: " . ($row['Default'] ?? 'NULL') . "\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n=== DỮ LIỆU MẪU (3 RECORD ĐẦU) ===\n";
$result = $conn->query("SELECT * FROM thanh_vien_hoi_dong LIMIT 3");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "QD_SO: {$row['QD_SO']} | GV_MAGV: {$row['GV_MAGV']} | TV_VAITRO: {$row['TV_VAITRO']} | TV_DIEM: " . ($row['TV_DIEM'] ?? 'NULL') . " | TC_MATC: {$row['TC_MATC']}\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

$conn->close();
?>
