<?php
// Test data for manage_projects.php
include '../../include/database.php';

echo "<h3>Kiểm tra dữ liệu đề tài nghiên cứu</h3>";

// Test thống kê tổng quan
$stats_sql = "SELECT 
    COUNT(*) as total_projects,
    SUM(CASE WHEN DT_TRANGTHAI = 'Chờ phê duyệt' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN DT_TRANGTHAI = 'Đang tiến hành' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN DT_TRANGTHAI = 'Đã hoàn thành' THEN 1 ELSE 0 END) as completed
FROM de_tai_nghien_cuu";

$result = $conn->query($stats_sql);
if ($result) {
    $stats = $result->fetch_assoc();
    echo "<h4>Thống kê đề tài:</h4>";
    echo "<ul>";
    echo "<li>Tổng số đề tài: " . $stats['total_projects'] . "</li>";
    echo "<li>Chờ phê duyệt: " . $stats['pending'] . "</li>";
    echo "<li>Đang tiến hành: " . $stats['in_progress'] . "</li>";
    echo "<li>Đã hoàn thành: " . $stats['completed'] . "</li>";
    echo "</ul>";
} else {
    echo "Lỗi truy vấn thống kê: " . $conn->error;
}

// Test cấu trúc bảng
echo "<h4>Cấu trúc bảng de_tai_nghien_cuu:</h4>";
$structure = $conn->query("DESCRIBE de_tai_nghien_cuu");
if ($structure) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
}

// Test dữ liệu mẫu
echo "<h4>Dữ liệu mẫu (5 đề tài đầu tiên):</h4>";
$sample = $conn->query("SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI, DT_NGAYTAO FROM de_tai_nghien_cuu LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    echo "<table border='1'><tr><th>Mã</th><th>Tên đề tài</th><th>Trạng thái</th><th>Ngày tạo</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TENDT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_NGAYTAO']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Không có dữ liệu đề tài nào trong database!</p>";
    
    // Kiểm tra xem bảng có tồn tại không
    $table_check = $conn->query("SHOW TABLES LIKE 'de_tai_nghien_cuu'");
    if ($table_check && $table_check->num_rows > 0) {
        echo "<p>Bảng de_tai_nghien_cuu tồn tại nhưng không có dữ liệu.</p>";
    } else {
        echo "<p style='color: red;'>Bảng de_tai_nghien_cuu không tồn tại!</p>";
        
        // Hiển thị tất cả bảng có sẵn
        echo "<h4>Các bảng có sẵn trong database:</h4>";
        $tables = $conn->query("SHOW TABLES");
        if ($tables) {
            while ($table = $tables->fetch_row()) {
                echo "- " . $table[0] . "<br>";
            }
        }
    }
}
?>
