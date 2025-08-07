<?php
require_once 'include/connect.php';

echo "=== TABLE STRUCTURES ===\n\n";

echo "de_tai_nghien_cuu structure:\n";
$result = $conn->query('DESCRIBE de_tai_nghien_cuu');
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . " (" . $row['Key'] . ")\n";
}

echo "\nsinh_vien structure:\n";
$result = $conn->query('DESCRIBE sinh_vien');
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . " (" . $row['Key'] . ")\n";
}

echo "\ngiang_vien structure:\n";
$result = $conn->query('DESCRIBE giang_vien');
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . " (" . $row['Key'] . ")\n";
}

echo "\nfile_dinh_kem structure:\n";
$result = $conn->query('DESCRIBE file_dinh_kem');
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . " (" . $row['Key'] . ")\n";
}

// Check some sample data
echo "\nSample data from de_tai_nghien_cuu:\n";
$result = $conn->query('SELECT * FROM de_tai_nghien_cuu LIMIT 3');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\nSample data from sinh_vien:\n";
$result = $conn->query('SELECT * FROM sinh_vien LIMIT 3');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
