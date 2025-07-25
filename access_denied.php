<?php
// filepath: d:\xampp\htdocs\NLNganh\access_denied.php
http_response_code(403); // Trả về mã lỗi HTTP 403
session_start();

// Xác định đường dẫn về trang chủ dựa trên vai trò người dùng
$home_url = "index.php";
$role_text = "trang chủ";

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        $home_url = "view/admin/admin_dashboard.php";
        $role_text = "bảng điều khiển quản trị viên";
    } elseif ($_SESSION['role'] == 'teacher') {
        $home_url = "view/teacher/teacher_dashboard.php";
        $role_text = "bảng điều khiển giảng viên";
    } elseif ($_SESSION['role'] == 'student') {
        $home_url = "view/student/student_dashboard.php";
        $role_text = "bảng điều khiển sinh viên";
    } elseif ($_SESSION['role'] == 'research_manager') {
        $home_url = "view/research/research_dashboard.php";
        $role_text = "bảng điều khiển quản lý nghiên cứu";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy cập bị từ chối | Hệ thống NCKH</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e74a3b;
            --primary-dark: #d13428;
            --secondary-color: #4e73df;
            --secondary-dark: #2e59d9;
            --dark-text: #3a3b45;
            --light-text: #858796;
            --bg-color: #f8f9fc;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--dark-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            display: block;
            pointer-events: none;
            background-color: var(--primary-color);
            opacity: 0.2;
            border-radius: 50%;
        }

        .access-denied-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .access-denied-card {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: fadeInUp 1s ease-out forwards;
        }

        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .card-body {
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .status-code {
            font-size: 64px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary-color);
            position: relative;
            display: inline-block;
        }

        .status-code::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--primary-color);
            opacity: 0.5;
            border-radius: 3px;
        }

        .lock-icon {
            font-size: 60px;
            color: var(--white);
            margin-bottom: 20px;
            animation: pulse 2s infinite ease-in-out;
        }

        .shield-icon {
            font-size: 100px;
            color: var(--primary-color);
            margin-bottom: 25px;
            animation: pulse 2s infinite ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .card-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark-text);
        }

        .card-text {
            font-size: 16px;
            color: var(--light-text);
            margin-bottom: 25px;
            line-height: 1.7;
            max-width: 500px;
        }

        .user-info {
            padding: 15px;
            background-color: rgba(78, 115, 223, 0.1);
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid var(--secondary-color);
            text-align: left;
            width: 100%;
            max-width: 400px;
        }

        .user-info i {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .btn {
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            margin: 10px;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .btn i {
            margin-right: 8px;
            font-size: 16px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }

        .btn-secondary:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--dark-text);
            border: 2px solid #e3e6f0;
        }

        .btn-outline:hover {
            background-color: #e3e6f0;
            transform: translateY(-3px);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 10px;
        }

        .mac-buttons {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
        }

        .mac-button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .mac-button.red {
            background-color: #ff5f57;
        }

        .mac-button.yellow {
            background-color: #ffbd2e;
        }

        .mac-button.green {
            background-color: #28c941;
        }

        .road {
            width: 100%;
            height: 25px;
            background-color: #3a3b45;
            position: relative;
            margin-top: 50px;
            display: flex;
            justify-content: center;
        }

        .road::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-50%);
        }

        .road::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: repeating-linear-gradient(90deg, #3a3b45, #3a3b45 20px, #4e4f5a 20px, #4e4f5a 40px);
        }

        .error-badge {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 40px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 25px;
            }

            .status-code {
                font-size: 50px;
            }

            .shield-icon {
                font-size: 80px;
            }

            .card-title {
                font-size: 24px;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                margin: 8px 0;
                width: 100%;
                justify-content: center;
            }
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            color: var(--light-text);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="particles-container">
        <?php 
        // Tạo các hạt nền ngẫu nhiên
        for ($i = 0; $i < 20; $i++) {
            $size = rand(5, 20);
            $left = rand(0, 100);
            $top = rand(0, 100);
            
            echo '<div class="particle" style="width:' . $size . 'px; height:' . $size . 'px; 
                left:' . $left . '%; top:' . $top . '%; 
                animation: pulse ' . (rand(3, 8)) . 's infinite ease-in-out;"></div>';
        }
        ?>
    </div>

    <div class="access-denied-container">
        <div class="access-denied-card">
            <div class="card-header">
                <div class="mac-buttons">
                    <div class="mac-button red"></div>
                    <div class="mac-button yellow"></div>
                    <div class="mac-button green"></div>
                </div>
                <i class="fas fa-lock lock-icon"></i>
                <h1>Truy cập bị từ chối</h1>
            </div>
            
            <div class="card-body">
                <div class="error-badge">Lỗi 403</div>
                <i class="fas fa-shield-alt shield-icon"></i>
                
                <h2 class="card-title">Bạn không có quyền truy cập!</h2>
                
                <p class="card-text">
                    Có vẻ như bạn đang cố gắng truy cập vào một trang mà bạn không có quyền xem. 
                    Vui lòng kiểm tra lại quyền truy cập của mình hoặc liên hệ với quản trị viên hệ thống.
                </p>
                
                <?php if (isset($_SESSION['username'])): ?>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span>Đăng nhập với tài khoản: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <?php if (isset($_SESSION['role'])): ?>
                    <br>
                    <i class="fas fa-user-tag mt-2"></i>
                    <span>Vai trò hiện tại: <strong><?php 
                        if ($_SESSION['role'] == 'admin') echo 'Quản trị viên';
                        elseif ($_SESSION['role'] == 'teacher') echo 'Giảng viên';
                        elseif ($_SESSION['role'] == 'student') echo 'Sinh viên';
                        else echo htmlspecialchars($_SESSION['role']);
                    ?></strong></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="actions">
                    <a href="<?php echo $home_url; ?>" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Quay lại <?php echo $role_text; ?>
                    </a>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập lại
                    </a>
                    <a href="#" onclick="history.back(); return false;" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Trang trước
                    </a>
                </div>
            </div>
            
            <div class="road"></div>
        </div>
        
        <div class="footer">
            Hệ thống quản lý nghiên cứu khoa học &copy; <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        // Thêm hiệu ứng chuyển động cho các hạt
        document.addEventListener('DOMContentLoaded', function() {
            // Tạo hiệu ứng chuyển động cho các phần tử
            const particles = document.querySelectorAll('.particle');
            particles.forEach(particle => {
                const size = Math.random() * 15 + 5;
                const duration = Math.random() * 5 + 3;
                
                particle.animate([
                    { transform: 'translateY(0) rotate(0)', opacity: 0.2 },
                    { transform: `translateY(-${Math.random() * 100}px) rotate(${Math.random() * 360}deg)`, opacity: 0.7 },
                    { transform: 'translateY(0) rotate(0)', opacity: 0.2 }
                ], {
                    duration: duration * 1000,
                    iterations: Infinity
                });
            });
        });
    </script>
</body>
</html>