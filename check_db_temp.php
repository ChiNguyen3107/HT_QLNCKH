<?php
include 'include/connect.php';

if ($conn) {
    echo "Database connection: OK\n";
    
    // Kiểm tra bảng quan_ly_nghien_cuu
    $result = $conn->query("SHOW TABLES LIKE 'quan_ly_nghien_cuu'");
    if ($result && $result->num_rows > 0) {
        echo "Table quan_ly_nghien_cuu: EXISTS\n";
        
        // Hiển thị cấu trúc bảng
        $result2 = $conn->query("DESCRIBE quan_ly_nghien_cuu");
        if ($result2) {
            echo "Table structure:\n";
            while ($row = $result2->fetch_assoc()) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        }
    } else {
        echo "Table quan_ly_nghien_cuu: NOT EXISTS\n";
        echo "Available tables:\n";
        $result3 = $conn->query("SHOW TABLES");
        if ($result3) {
            while ($row = $result3->fetch_row()) {
                echo "- " . $row[0] . "\n";
            }
        }
    }
} else {
    echo "Database connection: FAILED\n";
}
?>
