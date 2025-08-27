<?php
// Script để kiểm tra và sửa các vấn đề trong cơ sở dữ liệu
include_once 'include/connect.php';

echo "<h2>Kiểm tra và sửa lỗi cơ sở dữ liệu</h2>";

// 1. Kiểm tra cấu trúc bảng bien_ban
echo "<h3>1. Kiểm tra bảng bien_ban</h3>";
$result = $conn->query("DESCRIBE bien_ban");
if ($result) {
    echo "<p style='color: green;'>✓ Bảng bien_ban tồn tại</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ Lỗi khi kiểm tra bảng bien_ban: " . $conn->error . "</p>";
}

// 2. Kiểm tra bảng de_tai_nghien_cuu
echo "<h3>2. Kiểm tra bảng de_tai_nghien_cuu</h3>";
$result = $conn->query("DESCRIBE de_tai_nghien_cuu");
if ($result) {
    echo "<p style='color: green;'>✓ Bảng de_tai_nghien_cuu tồn tại</p>";
    $fields = [];
    while ($row = $result->fetch_assoc()) {
        $fields[] = $row['Field'];
    }
    
    // Kiểm tra các trường cần thiết
    $required_fields = ['DT_MADT', 'DT_TENDT', 'DT_MOTA', 'DT_TRANGTHAI', 'DT_NGAYTAO', 'DT_SLSV'];
    foreach ($required_fields as $field) {
        if (in_array($field, $fields)) {
            echo "<p style='color: green;'>✓ Trường $field tồn tại</p>";
        } else {
            echo "<p style='color: red;'>✗ Trường $field không tồn tại</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ Lỗi khi kiểm tra bảng de_tai_nghien_cuu: " . $conn->error . "</p>";
}

// 3. Kiểm tra bảng file_dinh_kem
echo "<h3>3. Kiểm tra bảng file_dinh_kem</h3>";
$result = $conn->query("DESCRIBE file_dinh_kem");
if ($result) {
    echo "<p style='color: green;'>✓ Bảng file_dinh_kem tồn tại</p>";
    $fields = [];
    while ($row = $result->fetch_assoc()) {
        $fields[] = $row['Field'];
    }
    
    // Kiểm tra các trường cần thiết
    $required_fields = ['FDG_MA', 'BB_SOBB', 'FDG_LOAI', 'FDG_TENFILE', 'FDG_FILE', 'FDG_NGAYTAO'];
    foreach ($required_fields as $field) {
        if (in_array($field, $fields)) {
            echo "<p style='color: green;'>✓ Trường $field tồn tại</p>";
        } else {
            echo "<p style='color: red;'>✗ Trường $field không tồn tại</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ Lỗi khi kiểm tra bảng file_dinh_kem: " . $conn->error . "</p>";
}

// 4. Kiểm tra dữ liệu mẫu
echo "<h3>4. Kiểm tra dữ liệu mẫu</h3>";

// Kiểm tra đề tài
$result = $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "<p>✓ Có $count đề tài trong cơ sở dữ liệu</p>";
} else {
    echo "<p style='color: red;'>✗ Lỗi khi đếm đề tài: " . $conn->error . "</p>";
}

// Kiểm tra biên bản
$result = $conn->query("SELECT COUNT(*) as count FROM bien_ban");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "<p>✓ Có $count biên bản trong cơ sở dữ liệu</p>";
} else {
    echo "<p style='color: red;'>✗ Lỗi khi đếm biên bản: " . $conn->error . "</p>";
}

// Kiểm tra file đính kèm
$result = $conn->query("SELECT COUNT(*) as count FROM file_dinh_kem");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "<p>✓ Có $count file đính kèm trong cơ sở dữ liệu</p>";
} else {
    echo "<p style='color: red;'>✗ Lỗi khi đếm file đính kèm: " . $conn->error . "</p>";
}

// 5. Kiểm tra foreign key constraints
echo "<h3>5. Kiểm tra foreign key constraints</h3>";
$result = $conn->query("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'ql_nckh' 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME, CONSTRAINT_NAME
");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Constraint</th><th>Table</th><th>Column</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
        echo "<td>" . $row['TABLE_NAME'] . "</td>";
        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠ Không tìm thấy foreign key constraints</p>";
}

// 6. Kiểm tra encoding
echo "<h3>6. Kiểm tra encoding</h3>";
$result = $conn->query("SHOW VARIABLES LIKE 'character_set%'");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Variable_name'] . "</td>";
        echo "<td>" . $row['Value'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Kết luận</h3>";
echo "<p>Script kiểm tra đã hoàn thành. Nếu có lỗi nào được báo cáo ở trên, vui lòng sửa chúng trước khi sử dụng hệ thống.</p>";
?>











