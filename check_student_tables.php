<?php
require_once 'include/connect.php';
$conn = new mysqli($servername, $username, $password, $dbname);

echo "Looking for student-related tables:\n";
$result = $conn->query('SHOW TABLES');
while($row = $result->fetch_array()) {
    if (strpos($row[0], 'sinh_vien') !== false || 
        strpos($row[0], 'chi_tiet') !== false ||
        strpos($row[0], 'tham_gia') !== false) {
        echo 'Found table: '.$row[0]."\n";
        
        // Show structure
        $desc_result = $conn->query("DESCRIBE ".$row[0]);
        echo "  Structure:\n";
        while($desc_row = $desc_result->fetch_assoc()) {
            echo "    ".$desc_row['Field']." (".$desc_row['Type'].")\n";
        }
        echo "\n";
    }
}

echo "Also checking sinh_vien table:\n";
$result = $conn->query('DESCRIBE sinh_vien');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'].' | '.$row['Type']."\n";
    }
} else {
    echo "sinh_vien table not found\n";
}

$conn->close();
?>
