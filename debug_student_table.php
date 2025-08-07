<?php
require_once 'include/database.php';

try {
    $conn = connectDB();
    
    echo "=== Kiểm tra bảng sinh viên ===" . PHP_EOL;
    $result = $conn->query("SHOW TABLES LIKE '%sinh_vien%'");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array()) {
            echo "Table: " . $row[0] . PHP_EOL;
            
            // Hiển thị cấu trúc bảng
            $desc = $conn->query("DESCRIBE " . $row[0]);
            while ($col = $desc->fetch_assoc()) {
                echo "  - " . $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL;
            }
        }
    } else {
        echo "Không tìm thấy bảng sinh_vien" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Kiểm tra bảng lớp ===" . PHP_EOL;
    $result = $conn->query("SHOW TABLES LIKE '%lop%'");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array()) {
            echo "Table: " . $row[0] . PHP_EOL;
            
            // Hiển thị cấu trúc bảng
            $desc = $conn->query("DESCRIBE " . $row[0]);
            while ($col = $desc->fetch_assoc()) {
                echo "  - " . $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL;
            }
        }
    } else {
        echo "Không tìm thấy bảng lop" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Test truy vấn sinh viên ===" . PHP_EOL;
    $test_query = "SELECT * FROM sinh_vien LIMIT 1";
    $result = $conn->query($test_query);
    if ($result) {
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            echo "Sample student data:" . PHP_EOL;
            foreach ($student as $key => $value) {
                echo "  $key: $value" . PHP_EOL;
            }
        } else {
            echo "Bảng sinh_vien không có dữ liệu" . PHP_EOL;
        }
    } else {
        echo "Lỗi truy vấn: " . $conn->error . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
