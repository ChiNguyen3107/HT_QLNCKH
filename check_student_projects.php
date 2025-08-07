<?php
require_once 'include/connect.php';
$conn = new mysqli($servername, $username, $password, $dbname);

echo "Checking de_tai_nghien_cuu structure:\n";
$result = $conn->query('DESCRIBE de_tai_nghien_cuu');
while($row = $result->fetch_assoc()) {
    echo $row['Field'].' | '.$row['Type'].' | Null: '.$row['Null']."\n";
}

echo "\nSample data:\n";
$result = $conn->query('SELECT DT_MADT, SV_MASV, DT_TENDT FROM de_tai_nghien_cuu LIMIT 3');
while($row = $result->fetch_assoc()) {
    echo 'Project: '.$row['DT_MADT'].' | Student: '.($row['SV_MASV'] ?? 'NULL').' | Title: '.$row['DT_TENDT']."\n";
}

echo "\nLooking for valid student-project pairs:\n";
$result = $conn->query("SELECT DT_MADT, SV_MASV FROM de_tai_nghien_cuu WHERE SV_MASV IS NOT NULL AND SV_MASV != '' LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo 'Valid pair: Project='.$row['DT_MADT'].' | Student='.$row['SV_MASV']."\n";
}

$conn->close();
?>
