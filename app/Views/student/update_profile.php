<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\update_profile.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Nếu không phải là phương thức POST, chuyển hướng về trang quản lý hồ sơ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_profile.php');
    exit;
}

// Kiểm tra loại form
$form_type = $_POST['form_type'] ?? '';
$sv_masv = $_POST['SV_MASV'] ?? '';

// Kiểm tra SV_MASV có khớp với user_id trong session
if ($sv_masv !== $_SESSION['user_id']) {
    $_SESSION['error_message'] = "Không có quyền cập nhật thông tin này";
    header('Location: manage_profile.php');
    exit;
}

// Xử lý form thông tin cá nhân
if ($form_type === 'personal_info') {
    // Lấy và kiểm tra dữ liệu từ form
    $sv_hosv = trim($_POST['SV_HOSV'] ?? '');
    $sv_tensv = trim($_POST['SV_TENSV'] ?? '');
    $sv_email = trim($_POST['SV_EMAIL'] ?? '');
    $sv_sdt = trim($_POST['SV_SDT'] ?? '');
    $sv_ngaysinh = !empty($_POST['SV_NGAYSINH']) ? date('Y-m-d', strtotime($_POST['SV_NGAYSINH'])) : null;
    $sv_gioitinh = isset($_POST['SV_GIOITINH']) ? (int)$_POST['SV_GIOITINH'] : null;
    $sv_diachi = trim($_POST['SV_DIACHI'] ?? '');

    // Xác thực dữ liệu
    $errors = [];

    if (empty($sv_hosv) || empty($sv_tensv)) {
        $errors[] = "Họ và tên không được để trống";
    }

    if (empty($sv_email) || !filter_var($sv_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }

    if (!empty($sv_sdt) && !preg_match('/^[0-9]{10}$/', $sv_sdt)) {
        $errors[] = "Số điện thoại phải có 10 chữ số";
    }

    // Kiểm tra email đã tồn tại chưa (trừ email hiện tại của sinh viên)
    $stmt = $conn->prepare("SELECT SV_MASV FROM sinh_vien WHERE SV_EMAIL = ? AND SV_MASV != ?");
    $stmt->bind_param("ss", $sv_email, $sv_masv);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email đã được sử dụng bởi tài khoản khác";
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode(", ", $errors);
        header('Location: manage_profile.php');
        exit;
    }

    // Cập nhật thông tin sinh viên
    $sql = "UPDATE sinh_vien SET 
            SV_HOSV = ?, 
            SV_TENSV = ?, 
            SV_EMAIL = ?, 
            SV_SDT = ?, 
            SV_NGAYSINH = ?, 
            SV_GIOITINH = ?, 
            SV_DIACHI = ? 
            WHERE SV_MASV = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error;
        header('Location: manage_profile.php');
        exit;
    }

    $stmt->bind_param("sssssiss", $sv_hosv, $sv_tensv, $sv_email, $sv_sdt, $sv_ngaysinh, $sv_gioitinh, $sv_diachi, $sv_masv);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Cập nhật thông tin cá nhân thành công!";
    } else {
        $_SESSION['error_message'] = "Không thể cập nhật thông tin: " . $stmt->error;
    }
    
    header('Location: manage_profile.php');
    exit;
}
// Xử lý form đổi mật khẩu
else if ($form_type === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];

    if (empty($current_password)) {
        $errors[] = "Vui lòng nhập mật khẩu hiện tại";
    }

    if (empty($new_password)) {
        $errors[] = "Vui lòng nhập mật khẩu mới";
    }

    if ($new_password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp";
    }

    if (!empty($new_password) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $new_password)) {
        $errors[] = "Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt";
    }

    if (empty($errors)) {
        // Kiểm tra mật khẩu hiện tại
        $stmt = $conn->prepare("SELECT SV_MATKHAU FROM sinh_vien WHERE SV_MASV = ?");
        $stmt->bind_param("s", $sv_masv);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            $_SESSION['error_message'] = "Không tìm thấy thông tin sinh viên";
            header('Location: manage_profile.php#security');
            exit;
        }

        $password_verified = password_verify($current_password, $user['SV_MATKHAU']);
        
        if (!$password_verified) {
            // Thử với mật khẩu không được hash (cho các mật khẩu cũ)
            if ($current_password !== $user['SV_MATKHAU']) {
                $_SESSION['error_message'] = "Mật khẩu hiện tại không đúng";
                header('Location: manage_profile.php#security');
                exit;
            }
        }

        // Hash và cập nhật mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE sinh_vien SET SV_MATKHAU = ? WHERE SV_MASV = ?");
        $stmt->bind_param("ss", $hashed_password, $sv_masv);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Đổi mật khẩu thành công!";
        } else {
            $_SESSION['error_message'] = "Không thể đổi mật khẩu: " . $stmt->error;
        }
    } else {
        $_SESSION['error_message'] = implode(", ", $errors);
    }
    
    header('Location: manage_profile.php#security');
    exit;
}

// Form type không hợp lệ
$_SESSION['error_message'] = "Yêu cầu không hợp lệ";
header('Location: manage_profile.php');
exit;
?>