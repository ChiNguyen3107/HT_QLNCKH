<?php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';

header('Content-Type: application/json');

$query = "SELECT gv.GV_MAGV, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
          gv.GV_CHUYENMON, gv.DV_MADV, k.DV_TENDV 
          FROM giang_vien gv 
          LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
          ORDER BY gv.GV_TENGV";

$result = $conn->query($query);

if ($result) {
    $lecturers = [];
    while ($row = $result->fetch_assoc()) {
        $lecturers[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $lecturers
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn dữ liệu: ' . $conn->error
    ]);
}
?>