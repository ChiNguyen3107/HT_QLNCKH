<?php
require_once 'include/connect.php';
$conn = new mysqli($servername, $username, $password, $dbname);

echo "Checking student-project relationships:\n";

$result = $conn->query("SELECT SV_MASV, DT_MADT, CTTG_VAITRO FROM chi_tiet_tham_gia LIMIT 10");
echo "Found relationships:\n";
while($row = $result->fetch_assoc()) {
    echo "Student: ".$row['SV_MASV']." | Project: ".$row['DT_MADT']." | Role: ".$row['CTTG_VAITRO']."\n";
}

echo "\nChecking available students:\n";
$result = $conn->query("SELECT SV_MASV, SV_TENSV FROM sinh_vien LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "Student: ".$row['SV_MASV']." | Name: ".$row['SV_TENSV']."\n";
}

$conn->close();
?>
