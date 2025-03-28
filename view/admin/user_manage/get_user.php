<?php
include '../../../include/session.php';
checkAdminRole();
?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}

include '../../../include/connect.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Kiểm tra xem id là sinh viên hay giảng viên
    $sql = "SELECT * FROM sinh_vien WHERE SV_MASV = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("s", $id);
    if(!$stmt->execute()){
        echo json_encode(["error" => "Lỗi thực thi câu lệnh SQL: " . $stmt->error]);
        exit();
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // Nếu không tìm thấy trong bảng sinh_vien, kiểm tra bảng giang_vien
        $sql = "SELECT * FROM giang_vien WHERE GV_MAGV = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
            exit();
        }
        $stmt->bind_param("s", $id);
        if(!$stmt->execute()){
            echo json_encode(["error" => "Lỗi thực thi câu lệnh SQL: " . $stmt->error]);
            exit();
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }

    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode(["error" => "Không tìm thấy người dùng"]);
    }
} else {
    echo json_encode(["error" => "Thiếu id người dùng"]);
}
?>