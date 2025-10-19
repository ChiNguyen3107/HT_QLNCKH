<?php
// filepath: d:\xampp\htdocs\NLNganh\login.php
require_once 'core/Helper.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Nghiên cứu Khoa học</title>
    <?php echo Helper::csrfMetaTag('login'); ?>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('assets/images/rlc.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        
        /* Overlay trên ảnh nền để tăng độ tương phản */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 0;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 15px;
            position: relative;
            z-index: 1;
        }
        
        .card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            animation: fadeIn 1s ease;
            background-color: rgba(255, 255, 255, 0.95);
        }
        
        .card-header {
            background: #2b5797;
            color: white;
            text-align: center;
            padding: 25px 0;
            border-bottom: none;
        }
        
        .system-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            font-weight: 500;
            color: #495057;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .form-control {
            height: 50px;
            border-radius: 25px;
            padding: 10px 20px;
            font-size: 16px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #2b5797;
            box-shadow: 0 0 0 0.2rem rgba(43, 87, 151, 0.25);
        }
        
        .input-icon {
            position: absolute;
            top: 43px;
            right: 20px;
            color: #6c757d;
            cursor: pointer;
        }
        
        .btn-login {
            height: 50px;
            border-radius: 25px;
            font-size: 16px;
            background: #2b5797;
            border: none;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-login:hover, .btn-login:focus {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.3);
            background-color: #1e3f6f;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: #2b5797;
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .system-name {
            margin-top: 15px;
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .system-name h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
        }
        
        .system-name p {
            font-size: 14px;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .card-body {
                padding: 25px;
            }
            
            .system-name h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="system-name mb-4">
            <h2>HỆ THỐNG QUẢN LÝ NGHIÊN CỨU KHOA HỌC</h2>
            <p>Trường Đại học Cần Thơ</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="system-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h4>ĐĂNG NHẬP HỆ THỐNG</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        if ($_GET['error'] == 'invalid_credentials') {
                            echo '<i class="fas fa-exclamation-triangle mr-2"></i> Tên đăng nhập hoặc mật khẩu không đúng!';
                        } elseif ($_GET['error'] == 'role') {
                            echo '<i class="fas fa-exclamation-triangle mr-2"></i> Bạn không có quyền truy cập!';
                        }
                        ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <form action="login_process.php" method="POST">
                    <?php echo Helper::csrfField('login'); ?>
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               placeholder="Nhập tên đăng nhập">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="Nhập mật khẩu">
                        <span class="input-icon toggle-password"><i class="fas fa-lock"></i></span>
                    </div>
                    
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary btn-block btn-login">
                            <i class="fas fa-sign-in-alt mr-2"></i> Đăng Nhập
                        </button>
                    </div>
                </form>
                
                <div class="forgot-password">
                    <a href="#" data-toggle="modal" data-target="#forgotPasswordModal">Quên mật khẩu?</a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p style="color: white; font-size: 14px; position: relative; z-index: 1; text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">
                &copy; <?php echo date('Y'); ?> - Hệ thống Quản lý Nghiên cứu Khoa học
            </p>
        </div>
    </div>
    
    <!-- Modal Quên mật khẩu -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header">
                    <h5 class="modal-title">Khôi phục mật khẩu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Vui lòng nhập email đã đăng ký để nhận hướng dẫn khôi phục mật khẩu.</p>
                    <div class="form-group">
                        <input type="email" class="form-control" placeholder="Email đã đăng ký">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary">Gửi yêu cầu</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="public/js/csrf-protection.js"></script>
    <script>
        // Hiệu ứng tự động đóng thông báo lỗi sau 5 giây
        $(document).ready(function(){
            setTimeout(function() {
                $(".alert").alert('close');
            }, 5000);
            
            // Hiệu ứng hiện/ẩn mật khẩu
            $(".toggle-password").click(function(){
                var input = $(this).parent().prev();
                var icon = $(this).find("i");
                
                if(input.attr("type") === "password") {
                    input.attr("type", "text");
                    icon.removeClass("fa-lock").addClass("fa-eye");
                } else {
                    input.attr("type", "password");
                    icon.removeClass("fa-eye").addClass("fa-lock");
                }
            });
        });
    </script>
</body>
</html>