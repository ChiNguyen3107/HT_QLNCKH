<?php
require_once 'include/connect.php';

echo "Testing evaluation query...\n";

$conn = new mysqli($servername, $username, $password, $dbname);

// Test query to find member in council
$member_id = 'GV000002';
$project_id = 'DT0001';

$query = "
    SELECT thd.QD_SO, thd.GV_MAGV, thd.TC_MATC, dt.DT_MADT
    FROM thanh_vien_hoi_dong thd
    JOIN quyet_dinh_nghiem_thu qd ON thd.QD_SO = qd.QD_SO
    JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB
    JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
    WHERE thd.GV_MAGV = '$member_id'
    LIMIT 5
";

echo "Query: $query\n";
$result = $conn->query($query);
if ($result) {
    echo "Found rows: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "QD_SO: " . $row['QD_SO'] . " | Member: " . $row['GV_MAGV'] . " | Project: " . ($row['DT_MADT'] ?? 'N/A') . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test simpler query
echo "\nTesting council members...\n";
$query2 = "SELECT QD_SO, GV_MAGV, TC_MATC FROM thanh_vien_hoi_dong WHERE GV_MAGV = '$member_id' LIMIT 3";
$result2 = $conn->query($query2);
if ($result2) {
    echo "Found council members: " . $result2->num_rows . "\n";
    while($row = $result2->fetch_assoc()) {
        echo "QD_SO: " . $row['QD_SO'] . " | GV_MAGV: " . $row['GV_MAGV'] . " | TC_MATC: " . $row['TC_MATC'] . "\n";
    }
}

// Test existing projects
echo "\nTesting existing projects...\n";
$query3 = "SELECT DT_MADT, DT_TENDT FROM de_tai_nghien_cuu LIMIT 5";
$result3 = $conn->query($query3);
if ($result3) {
    while($row = $result3->fetch_assoc()) {
        echo "Project: " . $row['DT_MADT'] . " - " . $row['DT_TENDT'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
