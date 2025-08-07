<?php
include '../include/connect.php';

echo "<h2>Kiểm tra cấu trúc bảng thanh_vien_hoi_dong</h2>";

// Kiểm tra cấu trúc bảng
$result = $conn->query("DESCRIBE thanh_vien_hoi_dong");
if ($result) {
    echo "<h3>Cấu trúc bảng:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Kiểm tra dữ liệu mẫu
echo "<h3>Dữ liệu hiện tại:</h3>";
$result = $conn->query("SELECT * FROM thanh_vien_hoi_dong LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>QD_SO</th><th>GV_MAGV</th><th>TC_MATC</th><th>TV_VAITRO</th><th>TV_DIEM</th><th>TV_DANHGIA</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['QD_SO'] . "</td>";
        echo "<td>" . $row['GV_MAGV'] . "</td>";
        echo "<td>" . $row['TC_MATC'] . "</td>";
        echo "<td>" . $row['TV_VAITRO'] . "</td>";
        echo "<td>" . $row['TV_DIEM'] . "</td>";
        echo "<td>" . $row['TV_DANHGIA'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Không có dữ liệu trong bảng.";
}

// Kiểm tra có field TV_HOTEN không
echo "<h3>Kiểm tra field TV_HOTEN:</h3>";
$result = $conn->query("SHOW COLUMNS FROM thanh_vien_hoi_dong LIKE 'TV_HOTEN'");
if ($result && $result->num_rows > 0) {
    echo "Field TV_HOTEN tồn tại.";
} else {
    echo "Field TV_HOTEN KHÔNG tồn tại.";
}

$conn->close();
?>
