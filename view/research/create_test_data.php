<?php
// Thêm dữ liệu test cho review_projects.php
include '../../include/database.php';

echo "<h3>Tạo dữ liệu test cho review_projects</h3>";

// Cập nhật một số đề tài thành trạng thái "Chờ duyệt" để test
$update_sql = "UPDATE de_tai_nghien_cuu SET DT_TRANGTHAI = 'Chờ duyệt' WHERE DT_MADT IN ('DT0000011', 'DT0000012', 'DT0000013') LIMIT 3";

if ($conn->query($update_sql)) {
    echo "✅ Đã cập nhật 3 đề tài thành trạng thái 'Chờ duyệt'<br>";
} else {
    echo "❌ Lỗi cập nhật: " . $conn->error . "<br>";
}

// Kiểm tra kết quả
$check_sql = "SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Chờ duyệt'";
$result = $conn->query($check_sql);

if ($result && $result->num_rows > 0) {
    echo "<h4>Đề tài chờ duyệt hiện tại:</h4>";
    echo "<table border='1'><tr><th>Mã đề tài</th><th>Tên đề tài</th><th>Trạng thái</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TENDT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Không có đề tài nào chờ duyệt.";
}

// Thống kê tổng
echo "<h4>Thống kê sau khi cập nhật:</h4>";
$stats_sql = "SELECT DT_TRANGTHAI, COUNT(*) as count FROM de_tai_nghien_cuu GROUP BY DT_TRANGTHAI ORDER BY count DESC";
$stats_result = $conn->query($stats_sql);

if ($stats_result) {
    echo "<table border='1'><tr><th>Trạng thái</th><th>Số lượng</th></tr>";
    while ($row = $stats_result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table>";
}

echo "<br><a href='review_projects.php'>👉 Truy cập trang Review Projects</a>";
?>
