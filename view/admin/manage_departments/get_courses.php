<?php

include '../../../include/session.php';
checkAdminRole();

include '../../../include/connect.php';

header('Content-Type: application/json');

$sql = "SELECT KH_NAM FROM khoa_hoc";
$result = $conn->query($sql);

$courses = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    echo json_encode($courses);
} else {
    echo json_encode(["error" => "Lỗi truy vấn: " . $conn->error]);
}
?>