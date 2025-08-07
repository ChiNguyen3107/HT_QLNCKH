<?php
include 'include/connect.php';

// Kiểm tra dữ liệu trong bảng file_danh_gia
echo "<h2>Debug file đánh giá</h2>";

// 1. Kiểm tra cấu trúc bảng file_danh_gia
echo "<h3>1. Cấu trúc bảng file_danh_gia:</h3>";
$structure_sql = "DESCRIBE file_danh_gia";
$result = $conn->query($structure_sql);
if ($result) {
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
} else {
    echo "Lỗi: " . $conn->error;
}

// 2. Kiểm tra dữ liệu trong bảng file_danh_gia
echo "<h3>2. Dữ liệu trong bảng file_danh_gia:</h3>";
$data_sql = "SELECT * FROM file_danh_gia ORDER BY FDG_NGAYCAP DESC LIMIT 10";
$result = $conn->query($data_sql);
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>FDG_MA</th><th>BB_SOBB</th><th>FDG_TEN</th><th>FDG_DUONGDAN</th><th>FDG_NGAYCAP</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['FDG_MA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['BB_SOBB']) . "</td>";
            echo "<td>" . htmlspecialchars($row['FDG_TEN'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['FDG_DUONGDAN'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['FDG_NGAYCAP'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Không có dữ liệu trong bảng file_danh_gia.";
    }
} else {
    echo "Lỗi: " . $conn->error;
}

// 3. Kiểm tra dữ liệu biên bản
echo "<h3>3. Dữ liệu biên bản nghiệm thu:</h3>";
$bb_sql = "SELECT BB_SOBB, DT_MADT, BB_NGAYLAP FROM bien_ban ORDER BY BB_NGAYLAP DESC LIMIT 10";
$result = $conn->query($bb_sql);
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>BB_SOBB</th><th>DT_MADT</th><th>BB_NGAYLAP</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['BB_SOBB']) . "</td>";
            echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
            echo "<td>" . htmlspecialchars($row['BB_NGAYLAP']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Không có dữ liệu biên bản.";
    }
} else {
    echo "Lỗi: " . $conn->error;
}

// 4. Kiểm tra đề tài với trạng thái "Đã hoàn thành"
echo "<h3>4. Đề tài với trạng thái 'Đã hoàn thành':</h3>";
$project_sql = "SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hoàn thành' LIMIT 5";
$result = $conn->query($project_sql);
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>DT_MADT</th><th>DT_TENDT</th><th>DT_TRANGTHAI</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
            echo "<td>" . htmlspecialchars($row['DT_TENDT']) . "</td>";
            echo "<td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Không có đề tài 'Đã hoàn thành'.";
    }
} else {
    echo "Lỗi: " . $conn->error;
}
?>
