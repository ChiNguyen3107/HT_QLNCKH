
<?php
include '../../../include/session.php';
checkAdminRole();
?>

<?php
include '../../../include/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['editId'];
    $name = $_POST['editName'];

    $sql = "UPDATE khoa SET DV_TENDV = ? WHERE DV_MADV = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("ss", $name, $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => "Cập nhật thông tin khoa thành công"]);
    } else {
        echo json_encode(["error" => "Lỗi cập nhật thông tin khoa: " . $stmt->error]);
    }
} else {
    echo json_encode(["error" => "Yêu cầu không hợp lệ"]);
}
?>