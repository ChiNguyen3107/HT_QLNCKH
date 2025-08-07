<?php
require_once 'include/connect.php';

echo "=== CHECKING STUDENT-PROJECT RELATIONSHIP ===\n\n";

$tables = ['chi_tiet_tham_gia', 'yeu_cau_dang_ky', 'tien_do_de_tai'];

foreach ($tables as $table) {
    echo "$table structure:\n";
    $result = $conn->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . " (" . $row['Key'] . ")\n";
    }
    
    echo "Sample data:\n";
    $result = $conn->query("SELECT * FROM $table LIMIT 2");
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "\n" . str_repeat("-", 50) . "\n\n";
}
?>
