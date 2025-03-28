<?php

include '../../../include/session.php';
checkAdminRole();

include '../../../include/connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $sql = "SELECT DV_MADV, DV_TENDV FROM khoa WHERE DV_MADV = '$id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "Không tìm thấy khoa."]);
    }
} else {
    echo json_encode(["error" => "Thiếu ID khoa."]);
}
?>
