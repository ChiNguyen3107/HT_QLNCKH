<?php
// Kiểm tra cấu trúc database
include '../../include/connect.php';

echo "<h2>Kiểm tra cấu trúc Database</h2>";

// 1. Kiểm tra cấu trúc bảng sinh_vien
echo "<h3>1. Cấu trúc bảng sinh_vien:</h3>";
$result = $conn->query("DESCRIBE sinh_vien");
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
    echo "<p>Lỗi: " . $conn->error . "</p>";
}

// 2. Kiểm tra cấu trúc bảng chi_tiet_tham_gia
echo "<h3>2. Cấu trúc bảng chi_tiet_tham_gia:</h3>";
$result = $conn->query("DESCRIBE chi_tiet_tham_gia");
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
    echo "<p>Lỗi: " . $conn->error . "</p>";
}

// 3. Kiểm tra cấu trúc bảng de_tai_nghien_cuu
echo "<h3>3. Cấu trúc bảng de_tai_nghien_cuu:</h3>";
$result = $conn->query("DESCRIBE de_tai_nghien_cuu");
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
    echo "<p>Lỗi: " . $conn->error . "</p>";
}

// 4. Kiểm tra dữ liệu mẫu
echo "<h3>4. Dữ liệu mẫu từ bảng sinh_vien (LOP_MA = 'DI2195A2'):</h3>";
$result = $conn->query("SELECT * FROM sinh_vien WHERE LOP_MA = 'DI2195A2' LIMIT 5");
if ($result) {
    echo "<table border='1'>";
    if ($result->num_rows > 0) {
        $first = true;
        while ($row = $result->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<th>" . $key . "</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='10'>Không có dữ liệu</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Lỗi: " . $conn->error . "</p>";
}

// 5. Kiểm tra trạng thái đề tài có sẵn
echo "<h3>5. Trạng thái đề tài có sẵn:</h3>";
$result = $conn->query("SELECT DISTINCT DT_TRANGTHAI FROM de_tai_nghien_cuu");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['DT_TRANGTHAI'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Lỗi: " . $conn->error . "</p>";
}
?>
