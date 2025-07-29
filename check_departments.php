<?php
include 'include/connect.php';

echo "Danh sách các khoa trong database:\n";
$sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo "- " . $row['DV_MADV'] . ": " . $row['DV_TENDV'] . "\n";
}

echo "\nPhân bố giảng viên theo khoa:\n";
$sql = "SELECT k.DV_TENDV, COUNT(*) as count
        FROM giang_vien gv 
        LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
        GROUP BY k.DV_TENDV
        ORDER BY count DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo "- " . ($row['DV_TENDV'] ?? 'Không xác định') . ": " . $row['count'] . " giảng viên\n";
}

$conn->close();
?>
