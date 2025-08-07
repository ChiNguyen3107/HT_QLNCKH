<?php
require_once 'include/connect.php';

echo "Debug: Council Member Lookup Issue\n";

$conn = new mysqli($servername, $username, $password, $dbname);

// Test parameters
$member_id = 'GV000002';
$project_id = 'DT0000001';

echo "Testing with Member ID: $member_id, Project ID: $project_id\n\n";

// Step 1: Check if member exists in thanh_vien_hoi_dong
echo "1. Checking if member exists in council table:\n";
$query1 = "SELECT QD_SO, GV_MAGV, TC_MATC FROM thanh_vien_hoi_dong WHERE GV_MAGV = '$member_id'";
$result1 = $conn->query($query1);
echo "Query: $query1\n";
echo "Results: " . $result1->num_rows . " rows\n";
while($row = $result1->fetch_assoc()) {
    echo "  - QD_SO: " . $row['QD_SO'] . " | GV_MAGV: " . $row['GV_MAGV'] . " | TC_MATC: " . $row['TC_MATC'] . "\n";
}

// Step 2: Check project exists
echo "\n2. Checking if project exists:\n";
$query2 = "SELECT DT_MADT, DT_TENDT, QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = '$project_id'";
$result2 = $conn->query($query2);
echo "Query: $query2\n";
echo "Results: " . $result2->num_rows . " rows\n";
while($row = $result2->fetch_assoc()) {
    echo "  - DT_MADT: " . $row['DT_MADT'] . " | QD_SO: " . ($row['QD_SO'] ?? 'NULL') . " | Title: " . $row['DT_TENDT'] . "\n";
}

// Step 3: Check the full join query that's failing
echo "\n3. Testing the full join query (from update_member_criteria_score.php):\n";
$query3 = "
    SELECT thd.QD_SO, thd.GV_MAGV, thd.TC_MATC 
    FROM thanh_vien_hoi_dong thd
    JOIN quyet_dinh_nghiem_thu qd ON thd.QD_SO = qd.QD_SO
    JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB
    JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
    WHERE thd.GV_MAGV = '$member_id' AND dt.DT_MADT = '$project_id'
    LIMIT 1
";
echo "Query: $query3\n";
$result3 = $conn->query($query3);
if ($result3) {
    echo "Results: " . $result3->num_rows . " rows\n";
    while($row = $result3->fetch_assoc()) {
        echo "  - QD_SO: " . $row['QD_SO'] . " | GV_MAGV: " . $row['GV_MAGV'] . " | TC_MATC: " . $row['TC_MATC'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

// Step 4: Check all tables involved
echo "\n4. Checking decision table:\n";
$query4 = "SELECT QD_SO, BB_SOBB FROM quyet_dinh_nghiem_thu LIMIT 5";
$result4 = $conn->query($query4);
while($row = $result4->fetch_assoc()) {
    echo "  - QD_SO: " . $row['QD_SO'] . " | BB_SOBB: " . ($row['BB_SOBB'] ?? 'NULL') . "\n";
}

echo "\n5. Checking bien_ban table:\n";
$query5 = "SELECT BB_SOBB FROM bien_ban LIMIT 5";
$result5 = $conn->query($query5);
while($row = $result5->fetch_assoc()) {
    echo "  - BB_SOBB: " . $row['BB_SOBB'] . "\n";
}

// Step 6: Try a simpler approach
echo "\n6. Alternative approach - finding by QD_SO directly:\n";
$query6 = "
    SELECT DISTINCT dt.QD_SO 
    FROM de_tai_nghien_cuu dt 
    WHERE dt.DT_MADT = '$project_id' AND dt.QD_SO IS NOT NULL
";
$result6 = $conn->query($query6);
if ($result6 && $result6->num_rows > 0) {
    $project_qd = $result6->fetch_assoc()['QD_SO'];
    echo "Project QD_SO: $project_qd\n";
    
    // Now check if member exists for this QD_SO
    $query7 = "SELECT * FROM thanh_vien_hoi_dong WHERE QD_SO = '$project_qd' AND GV_MAGV = '$member_id'";
    $result7 = $conn->query($query7);
    echo "Member check: " . $result7->num_rows . " rows\n";
    if ($result7->num_rows > 0) {
        $member_data = $result7->fetch_assoc();
        echo "Found member: QD_SO=" . $member_data['QD_SO'] . ", GV_MAGV=" . $member_data['GV_MAGV'] . ", TC_MATC=" . $member_data['TC_MATC'] . "\n";
    }
} else {
    echo "No QD_SO found for project $project_id\n";
}

$conn->close();
?>
