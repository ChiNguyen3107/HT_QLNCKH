<?php
// Kiểm tra chi tiết cấu trúc bảng bien_ban và các ràng buộc
include 'include/connect.php';

echo "<h2>Kiểm tra cấu trúc bảng BIEN_BAN</h2>";

// 1. Kiểm tra cấu trúc bảng bien_ban
echo "<h3>1. Cấu trúc bảng bien_ban:</h3>";
$sql = "DESCRIBE bien_ban";
$result = $conn->query($sql);

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Kiểm tra foreign keys
echo "<h3>2. Foreign Keys của bảng bien_ban:</h3>";
$sql_fk = "SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'ql_nckh' 
AND TABLE_NAME = 'bien_ban' 
AND REFERENCED_TABLE_NAME IS NOT NULL";

$result_fk = $conn->query($sql_fk);
if ($result_fk) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table</th><th>Column</th><th>Constraint</th><th>References Table</th><th>References Column</th></tr>";
    while ($row = $result_fk->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['TABLE_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['CONSTRAINT_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['REFERENCED_TABLE_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['REFERENCED_COLUMN_NAME']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Kiểm tra dữ liệu hiện có
echo "<h3>3. Dữ liệu hiện có trong bien_ban:</h3>";
$sql_data = "SELECT BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM FROM bien_ban LIMIT 10";
$result_data = $conn->query($sql_data);
if ($result_data) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>BB_SOBB</th><th>QD_SO</th><th>BB_NGAYNGHIEMTHU</th><th>BB_XEPLOAI</th><th>BB_TONGDIEM</th></tr>";
    while ($row = $result_data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['BB_SOBB']) . "</td>";
        echo "<td>" . htmlspecialchars($row['QD_SO']) . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_NGAYNGHIEMTHU'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_XEPLOAI'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_TONGDIEM'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Kiểm tra bảng quyet_dinh_nghiem_thu
echo "<h3>4. Cấu trúc bảng quyet_dinh_nghiem_thu:</h3>";
$sql_qd = "DESCRIBE quyet_dinh_nghiem_thu";
$result_qd = $conn->query($sql_qd);

if ($result_qd) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result_qd->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Kiểm tra dữ liệu quyết định nghiệm thu
echo "<h3>5. Dữ liệu quyết định nghiệm thu:</h3>";
$sql_qd_data = "SELECT QD_SO, QD_NGAY, BB_SOBB FROM quyet_dinh_nghiem_thu LIMIT 10";
$result_qd_data = $conn->query($sql_qd_data);
if ($result_qd_data) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>QD_SO</th><th>QD_NGAY</th><th>BB_SOBB</th></tr>";
    while ($row = $result_qd_data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['QD_SO']) . "</td>";
        echo "<td>" . htmlspecialchars($row['QD_NGAY'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_SOBB']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 6. Test thêm dữ liệu mới
echo "<h3>6. Test thêm dữ liệu mới:</h3>";
try {
    // Tìm QD_SO có sẵn
    $test_sql = "SELECT QD_SO FROM quyet_dinh_nghiem_thu LIMIT 1";
    $test_result = $conn->query($test_sql);
    if ($test_result && $test_result->num_rows > 0) {
        $test_row = $test_result->fetch_assoc();
        $test_qd = $test_row['QD_SO'];
        
        // Kiểm tra xem đã có biên bản chưa
        $check_sql = "SELECT BB_SOBB FROM bien_ban WHERE QD_SO = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $test_qd);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo "<p style='color:orange;'>✓ Quyết định $test_qd đã có biên bản</p>";
        } else {
            echo "<p style='color:blue;'>Quyết định $test_qd chưa có biên bản - có thể test insert</p>";
            
            // Test insert
            $new_id = 'BB' . str_pad(999, 8, '0', STR_PAD_LEFT);
            $test_insert = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, ?, '2024-01-15', 'Đạt')";
            $test_stmt = $conn->prepare($test_insert);
            if ($test_stmt) {
                $test_stmt->bind_param("ss", $new_id, $test_qd);
                if ($test_stmt->execute()) {
                    echo "<p style='color:green;'>✓ Test insert thành công với ID: $new_id</p>";
                    
                    // Xóa test data
                    $delete_sql = "DELETE FROM bien_ban WHERE BB_SOBB = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("s", $new_id);
                    $delete_stmt->execute();
                    echo "<p style='color:green;'>✓ Đã xóa test data</p>";
                } else {
                    echo "<p style='color:red;'>✗ Test insert failed: " . $test_stmt->error . "</p>";
                }
            } else {
                echo "<p style='color:red;'>✗ Prepare failed: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p style='color:red;'>Không tìm thấy quyết định nghiệm thu để test</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Test error: " . $e->getMessage() . "</p>";
}

echo "<h3>7. Check MySQL version và settings:</h3>";
$version_sql = "SELECT VERSION() as version";
$version_result = $conn->query($version_sql);
if ($version_result) {
    $version_row = $version_result->fetch_assoc();
    echo "<p>MySQL Version: " . $version_row['version'] . "</p>";
}

$mode_sql = "SELECT @@sql_mode as sql_mode";
$mode_result = $conn->query($mode_sql);
if ($mode_result) {
    $mode_row = $mode_result->fetch_assoc();
    echo "<p>SQL Mode: " . $mode_row['sql_mode'] . "</p>";
}

$conn->close();
?>
