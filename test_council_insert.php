<?php
include '../include/connect.php';

echo "<h2>Test Council Members Insert</h2>";

$decision_id = 'QD001'; // Test decision ID
$test_data = [
    ['id' => 'GV001', 'name' => 'Nguyễn Văn A', 'role' => 'Chủ tịch'],
    ['id' => 'GV002', 'name' => 'Trần Thị B', 'role' => 'Thành viên'],
    ['id' => 'GV003', 'name' => 'Lê Văn C', 'role' => 'Thư ký']
];

try {
    // Test insert
    $insert_member_sql = "INSERT INTO thanh_vien_hoi_dong (QD_SO, GV_MAGV, TC_MATC, TV_VAITRO, TV_DIEM, TV_DANHGIA) VALUES (?, ?, ?, ?, 0, 'Chưa đánh giá')";
    $stmt = $conn->prepare($insert_member_sql);
    
    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
    } else {
        foreach ($test_data as $member) {
            $gv_magv = $member['id'];
            $vaitro = $member['role'];
            $tc_matc = 'TC001';
            
            echo "Inserting: QD_SO=$decision_id, GV_MAGV=$gv_magv, TC_MATC=$tc_matc, TV_VAITRO=$vaitro<br>";
            
            $stmt->bind_param("ssss", $decision_id, $gv_magv, $tc_matc, $vaitro);
            if ($stmt->execute()) {
                echo "Success!<br>";
            } else {
                echo "Error: " . $stmt->error . "<br>";
            }
        }
    }
    
    // Check inserted data
    echo "<h3>Inserted Data:</h3>";
    $result = $conn->query("SELECT * FROM thanh_vien_hoi_dong WHERE QD_SO = '$decision_id'");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>QD_SO</th><th>GV_MAGV</th><th>TC_MATC</th><th>TV_VAITRO</th><th>TV_DIEM</th><th>TV_DANHGIA</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['QD_SO'] . "</td>";
            echo "<td>" . $row['GV_MAGV'] . "</td>";
            echo "<td>" . $row['TC_MATC'] . "</td>";
            echo "<td>" . $row['TV_VAITRO'] . "</td>";
            echo "<td>" . $row['TV_DIEM'] . "</td>";
            echo "<td>" . $row['TV_DANHGIA'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No data found.";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

$conn->close();
?>
