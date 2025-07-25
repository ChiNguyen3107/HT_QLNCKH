<?php
// Test data for review_projects.php
include '../../include/database.php';

echo "<h3>Kiểm tra dữ liệu cho trang review_projects.php</h3>";

// Kiểm tra các trạng thái có sẵn
echo "<h4>Các trạng thái đề tài hiện có:</h4>";
$status_query = "SELECT DISTINCT DT_TRANGTHAI, COUNT(*) as count FROM de_tai_nghien_cuu GROUP BY DT_TRANGTHAI ORDER BY count DESC";
$status_result = $conn->query($status_query);
if ($status_result) {
    echo "<table border='1'><tr><th>Trạng thái</th><th>Số lượng</th></tr>";
    while ($row = $status_result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "Lỗi: " . $conn->error;
}

// Kiểm tra đề tài cần phê duyệt
echo "<h4>Đề tài có thể cần phê duyệt:</h4>";
$possible_statuses = ['Chờ duyệt', 'Chờ phê duyệt', 'Đang xử lý'];
foreach ($possible_statuses as $status) {
    $count_query = "SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = '$status'";
    $count_result = $conn->query($count_query);
    if ($count_result) {
        $count = $count_result->fetch_assoc()['count'];
        echo "- $status: $count đề tài<br>";
    }
}

// Hiển thị mẫu dữ liệu đề tài có thể phê duyệt
echo "<h4>Mẫu dữ liệu đề tài:</h4>";
$sample_query = "SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI, DT_NGAYTAO FROM de_tai_nghien_cuu LIMIT 10";
$sample_result = $conn->query($sample_query);
if ($sample_result && $sample_result->num_rows > 0) {
    echo "<table border='1'><tr><th>Mã</th><th>Tên đề tài</th><th>Trạng thái</th><th>Ngày tạo</th></tr>";
    while ($row = $sample_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TENDT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_NGAYTAO']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
