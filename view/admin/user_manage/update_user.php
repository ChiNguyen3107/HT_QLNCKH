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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['editId'];
    $firstName = $_POST['editFirstName'];
    $lastName = $_POST['editLastName'];
    $email = $_POST['editEmail'];
    $phone = $_POST['editPhone'];
    $address = $_POST['editAddress'];
    $birthDate = $_POST['editBirthDate'];
    $gender = $_POST['editGender']; // Giá trị là "Nam" hoặc "Nữ"
    $class = $_POST['editClass'];

    // Chuyển đổi giới tính từ "Nam"/"Nữ" thành 0/1
    $genderValue = ($gender == 'Nam') ? 0 : 1;

    // Kiểm tra xem id là sinh viên hay giảng viên
    $sql = "SELECT * FROM sinh_vien WHERE SV_MASV = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Cập nhật thông tin sinh viên
        $sql = "UPDATE sinh_vien SET SV_HOSV = ?, SV_TENSV = ?, SV_EMAIL = ?, SV_SDT = ?, SV_DIACHI = ?, SV_NGAYSINH = ?, SV_GIOITINH = ?, LOP_MA = ? WHERE SV_MASV = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
            exit();
        }
        $stmt->bind_param("ssssssiss", $firstName, $lastName, $email, $phone, $address, $birthDate, $genderValue, $class, $id);
        $stmt->execute();
        echo json_encode(["success" => "Cập nhật thông tin sinh viên thành công"]);
    } else {
        // Nếu không tìm thấy trong bảng sinh_vien, kiểm tra bảng giang_vien
        $sql = "SELECT * FROM giang_vien WHERE GV_MAGV = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
            exit();
        }
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Cập nhật thông tin giảng viên
            $sql = "UPDATE giang_vien SET GV_HOGV = ?, GV_TENGV = ?, GV_EMAIL = ?, GV_SDT = ?, GV_DIACHI = ?, GV_NGAYSINH = ?, GV_GIOITINH = ? WHERE GV_MAGV = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                echo json_encode(["error" => "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error]);
                exit();
            }
            $stmt->bind_param("ssssssis", $firstName, $lastName, $email, $phone, $address, $birthDate, $genderValue, $id);
            $stmt->execute();
            echo json_encode(["success" => "Cập nhật thông tin giảng viên thành công"]);
        } else {
            echo json_encode(["error" => "Không tìm thấy người dùng"]);
        }
    }
} else {
    echo json_encode(["error" => "Yêu cầu không hợp lệ"]);
}
?>