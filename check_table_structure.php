<?php
require_once 'include/database.php';

try {
    $conn = connectDB();
    $result = $conn->query('DESCRIBE de_tai_nghien_cuu');
    
    echo "=== Structure of de_tai_nghien_cuu table ===" . PHP_EOL;
    while ($row = $result->fetch_assoc()) {
        $null_status = ($row['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
        echo "- {$row['Field']} ({$row['Type']}) {$null_status}";
        if (!empty($row['Key'])) {
            echo " [{$row['Key']}]";
        }
        echo PHP_EOL;
    }
    
    // Kiểm tra cụ thể cột QD_SO
    echo PHP_EOL . "=== Checking QD_SO column specifically ===" . PHP_EOL;
    $qd_result = $conn->query("SHOW COLUMNS FROM de_tai_nghien_cuu WHERE Field = 'QD_SO'");
    if ($qd_result && $qd_result->num_rows > 0) {
        $qd_col = $qd_result->fetch_assoc();
        echo "QD_SO column: " . PHP_EOL;
        print_r($qd_col);
    } else {
        echo "QD_SO column not found!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
