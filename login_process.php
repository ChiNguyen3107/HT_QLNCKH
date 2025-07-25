<?php
session_start();
include 'include/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Debug log
    error_log("Đăng nhập: $username");

    // Kiểm tra trong bảng `user`
    $sql = "SELECT * FROM user WHERE USERNAME = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();        if (password_verify($password, $user['PASSWORD'])) {
            $_SESSION['user_id'] = $user['USER_ID'] ?? $user['USERNAME'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['role'] = $user['ROLE'];
            $_SESSION['user_name'] = $user['NAME'] ?? $user['USERNAME'];            
            
            // Log successful login
            error_log("Đăng nhập thành công: {$user['USERNAME']} với vai trò {$user['ROLE']}");

            // Chuyển hướng theo vai trò - sử dụng đường dẫn tuyệt đối
            if ($user['ROLE'] == 'admin') {
                header("Location: /NLNganh/view/admin/admin_dashboard.php");
            } elseif ($user['ROLE'] == 'teacher') {
                header("Location: /NLNganh/view/teacher/teacher_dashboard.php");
            } elseif ($user['ROLE'] == 'student') {
                header("Location: /NLNganh/view/student/student_dashboard.php");            } elseif ($user['ROLE'] == 'research_manager') {
                // Đảm bảo luôn có giá trị manager_id để xác thực API
                if (!empty($user['USER_ID'])) {
                    // Nếu có USER_ID, sử dụng nó
                    $_SESSION['manager_id'] = $user['USER_ID'];
                } else {
                    // Mặc định gán manager_id giống với user_id
                    $_SESSION['manager_id'] = $user['USERNAME'];
                    
                    // Hoặc kiểm tra xem có quản lý nghiên cứu có mã QLR001 không
                    $check_sql = "SELECT QL_MA FROM quan_ly_nghien_cuu WHERE QL_MA = 'QLR001'";
                    $check_result = $conn->query($check_sql);
                    if ($check_result && $check_result->num_rows > 0) {
                        $_SESSION['manager_id'] = 'QLR001';
                    }
                }
                
                // Ghi log
                error_log("Research manager login successful. manager_id: {$_SESSION['manager_id']}");
                header("Location: /NLNganh/view/research/research_dashboard.php");
            }
            exit();
        }
    }

    // Kiểm tra trong bảng `sinh_vien`
    $sql = "SELECT * FROM sinh_vien WHERE SV_MASV = ? OR SV_EMAIL = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['SV_MATKHAU'])) {
            $_SESSION['user_id'] = $user['SV_MASV'];
            $_SESSION['username'] = $user['SV_EMAIL'];
            $_SESSION['role'] = 'student';
            $_SESSION['user_name'] = $user['SV_HOSV'] . ' ' . $user['SV_TENSV'];
            header("Location: /NLNganh/view/student/student_dashboard.php");
            exit();
        }
    }

    // Kiểm tra trong bảng `giang_vien`
    // Cho phép đăng nhập bằng cả mã giảng viên hoặc email
    $sql = "SELECT * FROM giang_vien WHERE GV_MAGV = ? OR GV_EMAIL = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Kiểm tra mật khẩu với nhiều phương thức (password_verify và so sánh trực tiếp)
        $password_correct = false;

        if (password_verify($password, $user['GV_MATKHAU'])) {
            $password_correct = true;
        } elseif ($password == $user['GV_MATKHAU']) {
            // Cho phép so sánh trực tiếp nếu mật khẩu không được hash
            $password_correct = true;
            error_log("Đăng nhập giảng viên với mật khẩu plain text");
        }

        if ($password_correct) {
            // Thiết lập session
            $_SESSION['user_id'] = $user['GV_MAGV']; 
            $_SESSION['username'] = $user['GV_EMAIL'];
            $_SESSION['role'] = 'teacher';
            $_SESSION['user_name'] = $user['GV_HOGV'] . ' ' . $user['GV_TENGV'];
            
            error_log("Đăng nhập giảng viên thành công: {$user['GV_MAGV']} - {$user['GV_HOGV']} {$user['GV_TENGV']}");
            
            // Sử dụng đường dẫn tuyệt đối giống như các trường hợp khác
            header("Location: /NLNganh/view/teacher/teacher_dashboard.php");
            exit();
        }
    }

    // Đăng nhập thất bại
    error_log("Đăng nhập thất bại: $username");
    header("Location: login.php?error=invalid_credentials");
    exit();
}
?>