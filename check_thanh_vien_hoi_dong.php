<?php
include 'include/connect.php';

echo "=== Kiểm tra cấu trúc bảng thanh_vien_hoi_dong ===\n";

$result = $conn->query('DESCRIBE thanh_vien_hoi_dong');

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL');
        if ($row['Key']) {
            echo " [" . $row['Key'] . "]";
        }
        echo "\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n=== Kiểm tra dữ liệu mẫu ===\n";

$sample_result = $conn->query('SELECT * FROM thanh_vien_hoi_dong LIMIT 3');
if ($sample_result && $sample_result->num_rows > 0) {
    while ($row = $sample_result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            echo "$key: " . ($value !== null ? $value : 'NULL') . "\n";
        }
        echo "---\n";
    }
} else {
    echo "Không có dữ liệu mẫu hoặc lỗi: " . $conn->error . "\n";
}
?>
