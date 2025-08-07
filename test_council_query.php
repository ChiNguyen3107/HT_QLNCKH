<?php
require_once 'include/connect.php';

echo "Testing council member query after fix:\n";

$conn = new mysqli($servername, $username, $password, $dbname);

$qd_so = 'QDDT0'; // From our debug data

$council_sql = "SELECT tv.*, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
                       gv.GV_EMAIL, gv.GV_SDT
                FROM thanh_vien_hoi_dong tv
                JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                WHERE tv.QD_SO = ?
                ORDER BY 
                    CASE tv.TV_VAITRO 
                        WHEN 'Chủ tịch hội đồng' THEN 1
                        WHEN 'Phó chủ tịch' THEN 2
                        WHEN 'Thành viên' THEN 3
                        WHEN 'Thư ký' THEN 4
                        ELSE 5
                    END, 
                    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) ASC";

echo "Query: $council_sql\n";
echo "QD_SO: $qd_so\n\n";

$stmt = $conn->prepare($council_sql);
if ($stmt === false) {
    echo "Error preparing statement: " . $conn->error . "\n";
} else {
    $stmt->bind_param("s", $qd_so);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "Found " . $result->num_rows . " council members:\n";
    
    while ($member = $result->fetch_assoc()) {
        echo "- GV_MAGV: " . $member['GV_MAGV'] . "\n";
        echo "  GV_HOTEN: " . $member['GV_HOTEN'] . "\n";
        echo "  TV_VAITRO: " . $member['TV_VAITRO'] . "\n";
        echo "  TC_MATC: " . $member['TC_MATC'] . "\n";
        echo "  TV_DIEM: " . ($member['TV_DIEM'] ?? 'NULL') . "\n";
        echo "\n";
    }
}

$conn->close();
?>
