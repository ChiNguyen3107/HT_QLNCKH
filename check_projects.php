<?php
include 'include/connect.php';

echo "<h3>Kiểm tra các đề tài có sẵn:</h3>";

$sql = "SELECT DT_MADT, QD_SO, DT_TRANGTHAI FROM de_tai_nghien_cuu LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>DT_MADT</th><th>QD_SO</th><th>DT_TRANGTHAI</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['QD_SO']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Không có đề tài nào trong database hoặc có lỗi: " . $conn->error;
}

// Kiểm tra bảng quyết định
echo "<br><h3>Kiểm tra quyết định nghiệm thu:</h3>";
$sql2 = "SELECT QD_SO, BB_SOBB FROM quyet_dinh_nghiem_thu LIMIT 5";
$result2 = $conn->query($sql2);

if ($result2 && $result2->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>QD_SO</th><th>BB_SOBB</th></tr>";
    while($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['QD_SO']) . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_SOBB']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Không có quyết định nào trong database hoặc có lỗi: " . $conn->error;
}
?>
