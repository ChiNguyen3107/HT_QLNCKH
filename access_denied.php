<?php
// filepath: d:\xampp\htdocs\NLNganh\access_denied.php
http_response_code(403); // Trả về mã lỗi HTTP 403
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy cập bị từ chối</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> <!-- Thêm FontAwesome -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .access-denied-container {
            margin-top: 100px;
        }
        .access-denied-icon {
            font-size: 80px;
            color: #dc3545;
        }
        .access-denied-message {
            font-size: 24px;
            font-weight: bold;
            color: #343a40;
        }
        .access-denied-description {
            font-size: 16px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container access-denied-container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle access-denied-icon"></i> <!-- Biểu tượng cảnh báo -->
                </div>
                <div class="access-denied-message">Truy cập bị từ chối</div>
                <p class="access-denied-description">
                    Bạn không có quyền truy cập vào trang này.  
                    <?php 
                    if (isset($_SESSION['username'])) {
                        echo "<br><strong>Tài khoản:</strong> " . htmlspecialchars($_SESSION['username']);
                    }
                    ?>
                    <br>Vui lòng kiểm tra lại quyền truy cập hoặc liên hệ với quản trị viên.
                </p>
                <?php
                // Điều hướng quay lại trang chủ theo vai trò
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] == 'admin') {
                        echo '<a href="view/admin/admin_dashboard.php" class="btn btn-secondary mt-3">Quay lại trang chủ</a>';
                    } elseif ($_SESSION['role'] == 'teacher') {
                        echo '<a href="view/teacher/teacher_dashboard.php" class="btn btn-secondary mt-3">Quay lại trang chủ</a>';
                    } elseif ($_SESSION['role'] == 'student') {
                        echo '<a href="view/student/student_dashboard.php" class="btn btn-secondary mt-3">Quay lại trang chủ</a>';
                    }
                } else {
                    echo '<a href="index.php" class="btn btn-secondary mt-3">Quay lại trang chủ</a>';
                }
                ?>
                <a href="login.php" class="btn btn-primary mt-3">Đăng nhập lại</a>
            </div>
        </div>
    </div>
</body>
</html>
