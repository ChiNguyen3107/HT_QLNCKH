<?php
require_once 'include/connect.php';
$conn = new mysqli($servername, $username, $password, $dbname);

echo "Looking for tables with trinh_do, chuyen_mon, or tc:\n";
$result = $conn->query('SHOW TABLES');
while($row = $result->fetch_array()) {
    if (strpos($row[0], 'trinh') !== false || 
        strpos($row[0], 'chuyen') !== false || 
        strpos($row[0], 'tc') !== false ||
        strpos($row[0], 'tieu') !== false) {
        echo "Found: " . $row[0] . "\n";
        
        // Show structure
        $desc = $conn->query("DESCRIBE " . $row[0]);
        echo "  Structure:\n";
        while($col = $desc->fetch_assoc()) {
            echo "    " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        echo "\n";
    }
}

$conn->close();
?>
