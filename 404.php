<?php
// Thiết lập mã trạng thái HTTP 404
http_response_code(404);
session_start();

// Xác định đường dẫn về trang chính dựa trên vai trò người dùng
$home_url = "index.php";
$role_text = "trang chủ";

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        $home_url = "/NLNganh/view/admin/admin_dashboard.php";
        $role_text = "bảng điều khiển quản trị viên";
    } elseif ($_SESSION['role'] == 'teacher') {
        $home_url = "/NLNganh/view/teacher/teacher_dashboard.php";
        $role_text = "bảng điều khiển giảng viên";
    } elseif ($_SESSION['role'] == 'student') {
        $home_url = "/NLNganh/view/student/student_dashboard.php";
        $role_text = "bảng điều khiển sinh viên";
    }
}

// Lấy URL hiện tại để hiển thị trong thông báo
$current_url = htmlspecialchars($_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy trang | Hệ thống NCKH</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #2e59d9;
            --secondary-color: #6c757d;
            --white: #ffffff;
            --light-gray: #f8f9fc;
            --dark-gray: #5a5c69;
            --red: #e74a3b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .error-container {
            max-width: 800px;
            width: 100%;
            text-align: center;
            background-color: var(--white);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
            animation: fadeInUp 0.8s ease-out;
        }

        .error-code {
            font-size: 160px;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
            text-shadow: 4px 4px 0px rgba(78, 115, 223, 0.1);
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .error-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-gray);
        }

        .error-message {
            font-size: 18px;
            color: var(--secondary-color);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-url {
            display: inline-block;
            padding: 8px 16px;
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            font-family: monospace;
            margin-top: 10px;
            word-break: break-all;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 16px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            outline: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.4);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.6);
        }

        .btn-secondary {
            background-color: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background-color: rgba(78, 115, 223, 0.1);
            transform: translateY(-3px);
        }

        .btn i {
            margin-right: 10px;
            font-size: 18px;
        }

        .illustration {
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            position: relative;
            z-index: 2;
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            display: block;
            border-radius: 50%;
            background-color: var(--primary-color);
            opacity: 0.3;
            animation: floatParticles 5s infinite ease-in-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes floatParticles {
            0%, 100% {
                transform: translateY(0) rotate(0);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .error-details {
            margin-top: 30px;
            font-size: 15px;
            color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .error-container {
                padding: 30px;
            }

            .error-code {
                font-size: 120px;
            }

            .error-title {
                font-size: 26px;
            }

            .error-message {
                font-size: 16px;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }
        }

        .illustration svg {
            width: 100%;
            height: auto;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.1));
        }

        .svg-animate {
            animation: float 3s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: var(--white);
            border-radius: 50px;
            padding: 8px 16px;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .back-btn:hover {
            background-color: var(--primary-color);
            color: var(--white);
            transform: translateX(-5px);
        }

        .back-btn i {
            margin-right: 5px;
        }

        .footer {
            margin-top: 50px;
            font-size: 14px;
            color: var(--secondary-color);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="particles">
        <?php 
        // Tạo các hạt nền ngẫu nhiên
        for ($i = 0; $i < 20; $i++) {
            $size = rand(5, 20);
            $left = rand(0, 100);
            $top = rand(0, 100);
            $delay = rand(0, 5);
            $duration = rand(5, 10);
            
            echo '<div class="particle" style="width:' . $size . 'px; height:' . $size . 'px; 
                left:' . $left . '%; top:' . $top . '%; 
                animation-delay:' . $delay . 's; 
                animation-duration:' . $duration . 's;"></div>';
        }
        ?>
    </div>

    <a href="<?php echo $home_url; ?>" class="back-btn">
        <i class="fas fa-chevron-left"></i> Quay lại
    </a>

    <div class="error-container">
        <div class="error-code">404</div>
        
        <div class="illustration">
            <svg class="svg-animate" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500">
                <g id="freepik--background-complete--inject-2">
                    <rect y="382.4" width="500" height="0.25" style="fill:#ebebeb"></rect>
                    <rect x="416.78" y="398.49" width="33.12" height="0.25" style="fill:#ebebeb"></rect>
                    <rect x="322.53" y="401.21" width="8.69" height="0.25" style="fill:#ebebeb"></rect>
                    <rect x="396.59" y="389.21" width="19.19" height="0.25" style="fill:#ebebeb"></rect>
                    <rect x="52.46" y="390.89" width="43.19" height="0.25" style="fill:#ebebeb"></rect>
                    <rect x="104.56" y="390.89" width="6.33" height="0.25" style="fill:#ebebeb"></rect>
                    <rect x="131.47" y="395.11" width="93.68" height="0.25" style="fill:#ebebeb"></rect>
                    <path d="M237,337.8H43.91a5.71,5.71,0,0,1-5.7-5.71V60.66A5.71,5.71,0,0,1,43.91,55H237a5.71,5.71,0,0,1,5.71,5.71V332.09A5.71,5.71,0,0,1,237,337.8ZM43.91,55.2a5.46,5.46,0,0,0-5.45,5.46V332.09a5.46,5.46,0,0,0,5.45,5.46H237a5.47,5.47,0,0,0,5.46-5.46V60.66A5.47,5.47,0,0,0,237,55.2Z" style="fill:#ebebeb"></path>
                    <path d="M453.31,337.8H260.21a5.72,5.72,0,0,1-5.71-5.71V60.66A5.72,5.72,0,0,1,260.21,55h193.1A5.71,5.71,0,0,1,459,60.66V332.09A5.71,5.71,0,0,1,453.31,337.8ZM260.21,55.2a5.47,5.47,0,0,0-5.46,5.46V332.09a5.47,5.47,0,0,0,5.46,5.46h193.1a5.47,5.47,0,0,0,5.46-5.46V60.66a5.47,5.47,0,0,0-5.46-5.46Z" style="fill:#ebebeb"></path>
                </g>
                <g id="freepik--Shadow--inject-2">
                    <ellipse id="freepik--path--inject-2" cx="250" cy="416.24" rx="193.89" ry="11.32" style="fill:#f5f5f5"></ellipse>
                </g>
                <g id="freepik--Character--inject-2">
                    <path d="M398.38,406.63c-.51-3.06-1.08-6.11-1.78-9.14s-1.53-6-2.46-9-1.94-5.95-3.11-8.85-2.48-5.74-3.92-8.53-3.07-5.49-4.87-8.08c-3.59-5.21-8-9.88-12.91-14a85.64,85.64,0,0,0-15.29-10.53c-1.37-.75-2.77-1.38-4.21-2l-2.15-.86-2.19-.75a47.3,47.3,0,0,0-17.82-2.08,52.71,52.71,0,0,0-8.76,1.5c-1.44.37-2.86.81-4.28,1.31a64.58,64.58,0,0,0-8.28,3.45,58.58,58.58,0,0,0-15.2,10.18,61.12,61.12,0,0,0-6.45,6.95,65,65,0,0,0-5.33,7.64,78.39,78.39,0,0,0-7.45,16.38c-.48,1.42-1,2.83-1.41,4.27s-.79,2.87-1.15,4.32-.67,2.9-1,4.36-.56,2.93-.8,4.4-.47,2.95-.66,4.43c-.08.74-.18,1.48-.25,2.22s-.15,1.49-.18,2.23c-.08,1.49-.16,3-.19,4.47,0,.74,0,1.49,0,2.23s.07,1.49.11,2.24c.08,1.49.17,3,.31,4.46s.31,3,.52,4.44.45,2.95.73,4.42.59,2.92,1,4.37c.17.73.39,1.45.59,2.17l.63,2.16c.45,1.43.93,2.85,1.45,4.26s1.09,2.8,1.68,4.18l1.8,4.14c.65,1.36,1.3,2.71,2,4.05.34.67.72,1.32,1.08,2l1.11,2c.76,1.31,1.53,2.6,2.33,3.88s1.68,2.5,2.55,3.73l2.7,3.63.11.14c.22.25.1.14.17.23l.41.5c.63.76,1.28,1.5,1.94,2.23,1.3,1.47,2.6,3,4,4.31s2.83,2.71,4.38,3.91,3.11,2.41,4.77,3.45a58.8,58.8,0,0,0,5.14,3,79.35,79.35,0,0,0,11,4.75l2.86,1,1.44.45,1.46.39a84.53,84.53,0,0,0,11.79,2.3q1.49.19,3,.31c1,.08,2,.15,3,.19a78.47,78.47,0,0,0,12.06-.6,34.56,34.56,0,0,0,14.7-5.6,25.59,25.59,0,0,0,5.05-4.49,27.79,27.79,0,0,0,3.8-5.57,34.75,34.75,0,0,0,2.7-6.2c.37-1.06.7-2.13,1-3.22.14-.55.3-1.09.43-1.64s.21-1.13.32-1.69c.21-1.13.39-2.27.56-3.41a73.5,73.5,0,0,0,.82-7.62c.07-1.2.09-2.4.09-3.59s0-2.39-.05-3.58c0-.6,0-1.19-.06-1.79s-.07-1.19-.1-1.78c-.08-1.19-.17-2.38-.28-3.56s-.26-2.37-.43-3.55-.33-2.36-.54-3.54-.48-2.33-.75-3.49c-.14-.58-.28-1.16-.42-1.74l-.44-1.74c-.31-1.15-.63-2.3-1-3.44a78,78,0,0,0-9.37-21.12c-.47-.76-1-1.51-1.45-2.26l-.74-1.14-.78-1.1c-.52-.73-1-1.46-1.59-2.17a40.81,40.81,0,0,0-3.45-4c-.62-.63-1.26-1.22-1.91-1.82a40.9,40.9,0,0,0-4.09-3.29,38,38,0,0,0-4.45-2.73q-1.15-.59-2.34-1.12c-.79-.35-1.58-.7-2.39-1a24.82,24.82,0,0,0-5-1.31,13.83,13.83,0,0,0-5.1.13,16.4,16.4,0,0,0-4.8,1.94,30.46,30.46,0,0,0-4.22,3,38.82,38.82,0,0,0-7.24,8.2,53.79,53.79,0,0,0-5.2,10.07c-.69,1.74-1.29,3.53-1.87,5.31s-1.12,3.61-1.57,5.45-.48,1.91-.7,2.88-.45,1.93-.65,2.9c-.42,1.94-.79,3.89-1.13,5.85-.16,1-.32,2-.44,3l-.38,3c-.11,1-.21,2-.31,3s-.17,2-.24,3c-.13,2-.25,4-.31,6s-.07,4,0,6c0,1,.05,2,.08,3l.15,3c.1,2,.27,4,.43,5.94.09,1,.18,2,.28,3l.34,3c.14,1,.24,2,.37,3s.29,2,.45,2.94.31,2,.49,2.94.32,2,.52,2.93c.9,3.89,2.06,7.71,3.33,11.49a86.85,86.85,0,0,0,4.35,10.86,85.7,85.7,0,0,0,5.62,10.12c1.05,1.61,2.14,3.23,3.26,4.8s2.32,3.13,3.52,4.63c2.4,3,5,5.86,7.56,8.53l.86-.87a197.92,197.92,0,0,1-14.62-17.22,82.82,82.82,0,0,1-5.7-9c-1.74-3.11-3.36-6.33-4.76-9.66s-2.64-6.8-3.73-10.32c-.53-1.76-1.07-3.52-1.51-5.31s-.87-3.59-1.25-5.39c-.74-3.61-1.31-7.25-1.73-10.91-.09-.91-.21-1.82-.28-2.74s-.12-1.84-.16-2.76c-.08-1.84-.14-3.69-.15-5.53s0-3.69.09-5.53c.06-1.84.13-3.68.27-5.52s.32-3.67.55-5.49c.11-.92.25-1.83.38-2.74s.3-1.83.47-2.73c.33-1.82.7-3.63,1.11-5.42s.88-3.57,1.41-5.33c.29-.88.52-1.76.83-2.63l.93-2.58a63.16,63.16,0,0,1,9.26-17.26,45.85,45.85,0,0,1,13.33-11.86,42,42,0,0,1,8-3.55c1.39-.43,2.81-.84,4.23-1.17a59.43,59.43,0,0,1,8.59-1.31,49.36,49.36,0,0,1,8.64.09c1.44.13,2.87.29,4.3.52a47.07,47.07,0,0,1,8.43,1.94c1.39.44,2.75,1,4.09,1.51s2.68,1.19,4,1.84a58.6,58.6,0,0,1,14.94,10.38A67.53,67.53,0,0,1,376,355.42a75.74,75.74,0,0,1,7.18,18,74.44,74.44,0,0,1,1.89,9.36c.43,3.16.74,6.32,1,9.49.13,1.59.19,3.19.27,4.78a38.89,38.89,0,0,1-.12,4.5,25.44,25.44,0,0,1-3.25,10.2,30.47,30.47,0,0,1-7.19,8.33c-.7.57-1.41,1.12-2.16,1.62s-1.52,1-2.32,1.44a51,51,0,0,1-4.94,2.38c-3.41,1.49-7,2.65-10.56,3.79l-5.4,1.72-5.43,1.63-10.88,3.2c-7.26,2.09-14.54,4.08-21.9,5.77q-5.51,1.27-11.06,2.32c-3.7.71-7.42,1.3-11.14,1.87l1.09,1.42c3.75-.18,7.5-.43,11.24-.82s7.48-.84,11.21-1.39q5.6-.84,11.16-1.95c3.72-.74,7.42-1.57,11.11-2.46s7.36-1.85,11-2.89l5.47-1.53,5.45-1.61c1.81-.53,3.62-1.06,5.4-1.68s3.56-1.24,5.3-2c.87-.35,1.73-.72,2.58-1.13s1.67-.86,2.5-1.33a27.33,27.33,0,0,0,8.17-7.21,23.63,23.63,0,0,0,4.45-9.34,30.1,30.1,0,0,0,.52-5.1c0-.85,0-1.7,0-2.54S398.45,407.47,398.38,406.63Z" style="fill:#e0e0e0"></path>
                    <path d="M329.51,167.42a31.78,31.78,0,0,0-31.74,31.75V311a31.78,31.78,0,0,0,31.74,31.75H392a31.78,31.78,0,0,0,31.74-31.75V199.17A31.78,31.78,0,0,0,392,167.42Z" style="fill:#e6e6e6"></path>
                    <path d="M327.43,198.59v111.8a12.3,12.3,0,0,0,12.3,12.3h63.46a12.3,12.3,0,0,0,12.3-12.3V198.59Z" style="fill:#407BFF"></path>
                    <path d="M379.89,157.78H350.25a3.11,3.11,0,0,0-1,.16,3.59,3.59,0,0,0-1.8,1.41,3.88,3.88,0,0,0-.61,2.09l32.17,21.77L391,183.21V170.08A12.3,12.3,0,0,0,379.89,157.78Z" style="fill:#407BFF"></path>
                    <path d="M350.25,157.78H331a12.3,12.3,0,0,0-12.3,12.3v16a12.3,12.3,0,0,0,12.3,12.3h28.15Z" style="fill:#407BFF"></path>
                    <polygon points="378.97 183.21 347.4 161.44 378.97 161.44 378.97 183.21" style="fill:#263238"></polygon>
                    <path d="M124.31,112.25A15,15,0,0,0,109.29,127v90.47a15,15,0,0,0,15,15H188a15,15,0,0,0,15-15V127a15,15,0,0,0-15-15Z" style="fill:#e0e0e0"></path>
                    <path d="M121.27,126.7v87.65A12.15,12.15,0,0,0,133.42,226H187.1a12.16,12.16,0,0,0,12.16-12.16V126.7Z" style="fill:#e0e0e0"></path>
                    <path d="M121.27,126.7v87.65A12.15,12.15,0,0,0,133.42,226H187.1a12.16,12.16,0,0,0,12.16-12.16V126.7Z" style="fill:#263238;opacity:0.2"></path>
                    <path d="M173.12,102.72H143.86a3.15,3.15,0,0,0-1,.17,3.52,3.52,0,0,0-1.77,1.38,3.81,3.81,0,0,0-.61,2.07l31.77,21.51L184,127.85V114.88a12.16,12.16,0,0,0-10.88-12.16Z" style="fill:#407BFF"></path>
                    <path d="M143.86,102.72H124.77A12.15,12.15,0,0,0,112.61,114v0h0v13.29a12.16,12.16,0,0,0,12.16,12.16h27.8Z" style="fill:#407BFF"></path>
                    <polygon points="172.23 127.85 141.05 106.34 172.23 106.34 172.23 127.85" style="fill:#263238"></polygon>
                    <path d="M156.26,245.61V406.17a10.11,10.11,0,0,0,10.09,10.07h167.3a10.11,10.11,0,0,0,10.09-10.07V245.61Z" style="fill:#407BFF"></path>
                    <path d="M156.26,245.61V406.17a10.11,10.11,0,0,0,10.09,10.07h167.3a10.11,10.11,0,0,0,10.09-10.07V245.61Z" style="opacity:0.1"></path>
                    <path d="M258.79,225.45H241.21a2.48,2.48,0,0,0-.77.12,2.85,2.85,0,0,0-1.39,1.09,2.94,2.94,0,0,0-.48,1.62l24.93,16.88,9.91-19.71Z" style="fill:#407BFF"></path>
                    <path d="M258.79,225.45H241.21a2.48,2.48,0,0,0-.77.12,2.85,2.85,0,0,0-1.39,1.09,2.94,2.94,0,0,0-.48,1.62l24.93,16.88,9.91-19.71Z" style="fill:#fff;opacity:0.6"></path>
                    <path d="M241.21,225.45h-82a10.09,10.09,0,0,0-10.09,10.08v10.08a10.09,10.09,0,0,0,10.09,10.08h82Z" style="fill:#407BFF"></path>
                    <path d="M241.21,225.45h-82a10.09,10.09,0,0,0-10.09,10.08v10.08a10.09,10.09,0,0,0,10.09,10.08h82Z" style="fill:#fff;opacity:0.6"></path>
                    <polygon points="258.79 245.16 238.57 228.28 258.79 228.28 258.79 245.16" style="fill:#263238"></polygon>
                    <polygon points="258.79 245.16 238.57 228.28 258.79 228.28 258.79 245.16" style="opacity:0.2"></polygon>
                    <path d="M317.45,245.61H169.69a5,5,0,0,1-4.31-2.42l-9.12-15.87a5,5,0,0,1,4.31-7.54H308.34a5,5,0,0,1,4.31,2.42l9.12,15.87A5,5,0,0,1,317.45,245.61Z" style="fill:#407BFF"></path>
                    <path d="M268.7,225.45H309a0,0,0,0,1,0,0v20.16a0,0,0,0,1,0,0H278.61a9.91,9.91,0,0,1-9.91-9.91v-10.25a0,0,0,0,1,0,0Z" style="opacity:0.2"></path>
                    <rect x="211.06" y="257.82" width="58.72" height="37.65" style="fill:#fff"></rect>
                    <rect x="211.06" y="307.61" width="58.72" height="37.65" style="fill:#fff"></rect>
                    <rect x="211.06" y="357.41" width="58.72" height="37.65" style="fill:#fff"></rect>
                    <rect x="220.18" y="265.53" width="40.48" height="4.89" style="fill:#407BFF"></rect>
                    <rect x="220.18" y="265.53" width="40.48" height="4.89" style="opacity:0.1"></rect>
                    <rect x="220.18" y="276.5" width="40.48" height="4.89" style="fill:#407BFF;opacity:0.30000000000000004"></rect>
                    <rect x="220.18" y="315.33" width="40.48" height="4.89" style="fill:#407BFF;opacity:0.30000000000000004"></rect>
                    <rect x="220.18" y="365.12" width="40.48" height="4.89" style="fill:#407BFF;opacity:0.30000000000000004"></rect>
                    <rect x="220.18" y="376.09" width="25.99" height="4.89" style="fill:#407BFF;opacity:0.30000000000000004"></rect>
                    <rect x="220.18" y="326.3" width="25.99" height="4.89" style="fill:#407BFF;opacity:0.30000000000000004"></rect>
                    <path d="M327.43,194.56H415.5a.35.35,0,0,1,.31.19.34.34,0,0,1,0,.36L407,209.24a.34.34,0,0,1-.31.18H326.47a.34.34,0,0,1-.31-.54l.93-1.62Z" style="fill:#407BFF"></path>
                    <path d="M327.43,194.56H415.5a.35.35,0,0,1,.31.19.34.34,0,0,1,0,.36L407,209.24a.34.34,0,0,1-.31.18H326.47a.34.34,0,0,1-.31-.54l.93-1.62Z" style="fill:#fff;opacity:0.8"></path>
                    <path d="M156.26,245.61h99.32a0,0,0,0,1,0,0V391.67a0,0,0,0,1,0,0h-89.4a9.91,9.91,0,0,1-9.91-9.91V245.61A0,0,0,0,1,156.26,245.61Z" style="opacity:0.2"></path>
                    <rect x="180.87" y="266.15" width="146.66" height="115.85" style="fill:#fff"></rect>
                    <rect x="265.6" y="289.04" width="49" height="70.06" style="fill:#407BFF;opacity:0.30000000000000004"></rect>
                    <rect x="194.8" y="289.04" width="49" height="70.06" style="fill:#407BFF"></rect>
                    <rect x="194.8" y="289.04" width="49" height="70.06" style="opacity:0.1"></rect>
                    <path d="M225.62,318.79a8.09,8.09,0,1,0-8.09-8.09A8.09,8.09,0,0,0,225.62,318.79Zm0,4.05c-5.39,0-16.18,2.7-16.18,8.09v4.05h32.36v-4.05C241.8,325.54,231,322.84,225.62,322.84Z" style="fill:#fff"></path>
                    <path d="M278.53,318.79a8.09,8.09,0,1,0-8.09-8.09A8.09,8.09,0,0,0,278.53,318.79Zm0,4.05c-5.4,0-16.18,2.7-16.18,8.09v4.05h32.36v-4.05C294.71,325.54,283.92,322.84,278.53,322.84Z" style="fill:#407BFF;opacity:0.30000000000000004"></path>
                </g>
            </svg>
        </div>

        <h1 class="error-title">Ồ không! Trang không tồn tại</h1>
        <p class="error-message">
            Có vẻ như trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.
            <br>Vui lòng kiểm tra lại URL hoặc quay lại trang chủ.
        </p>

        <div class="error-url"><?php echo $current_url; ?></div>

        <div class="actions">
            <a href="<?php echo $home_url; ?>" class="btn btn-primary">
                <i class="fas fa-home"></i> Về <?php echo $role_text; ?>
            </a>
            <a href="#" onclick="history.back(); return false;" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại trang trước
            </a>
        </div>

        <div class="error-details">
            Mã lỗi: 404 | Không tìm thấy trang
        </div>
    </div>
    
    <div class="footer">
        Hệ thống quản lý nghiên cứu khoa học &copy; <?php echo date('Y'); ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tạo hiệu ứng chuyển động cho các phần tử
            const particles = document.querySelectorAll('.particle');
            particles.forEach(particle => {
                const size = Math.random() * 15 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                particle.style.animationDuration = `${Math.random() * 5 + 5}s`;
            });
        });
    </script>
</body>
</html>