<?php

include '../../../include/session.php';
checkAdminRole();

include '../../../include/connect.php';

header('Content-Type: application/json');

$classId = $_GET['classId'];

$sql = "SELECT SV_MASV, CONCAT(SV_HOSV, ' ', SV_TENSV) AS SV_HOTEN, SV_EMAIL, SV_SDT FROM sinh_vien WHERE LOP_MA = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $classId);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    echo json_encode($students);
} else {
    echo json_encode(["error" => "Lỗi truy vấn: " . $conn->error]);
}
?>