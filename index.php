<?php
// filepath: d:\xampp\htdocs\NLNganh\index.php
// Trang chính - kiểm tra đăng nhập và chuyển hướng thích hợp
session_start();

// Nếu đã đăng nhập, chuyển hướng đến trang tương ứng
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') {
        header("Location: view/admin/admin_dashboard.php");
    } elseif($_SESSION['role'] == 'student') {
        header("Location: view/student/student_dashboard.php");
    } elseif($_SESSION['role'] == 'teacher') {
        header("Location: view/teacher/teacher_dashboard.php");
    } elseif($_SESSION['role'] == 'research_manager') {
        header("Location: view/research/research_dashboard.php");
    } else {
        header("Location: login.php?error=role");
    }
    exit;
}

// Kết nối CSDL để lấy một số thống kê chung (nếu muốn hiển thị)
include 'include/connect.php';

// Thống kê tổng quan (đơn giản)
$stats = [
    'projects' => 0,
    'students' => 0,
    'teachers' => 0,
    'departments' => 0
];

// Truy vấn số lượng đề tài
$query = "SELECT COUNT(*) as count FROM de_tai_nghien_cuu";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['projects'] = $row['count'];
}

// Truy vấn số lượng sinh viên
$query = "SELECT COUNT(*) as count FROM sinh_vien";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['students'] = $row['count'];
}

// Truy vấn số lượng giảng viên
$query = "SELECT COUNT(*) as count FROM giang_vien";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['teachers'] = $row['count'];
}

// Truy vấn số lượng khoa
$query = "SELECT COUNT(*) as count FROM khoa";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['departments'] = $row['count'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Nghiên cứu Khoa học - Trường Đại học Cần Thơ</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary: #1a365d;
            --primary-light: #2d4a73;
            --primary-dark: #0f1b2e;
            --secondary: #2563eb;
            --accent: #f59e0b;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            background-color: var(--background);
            overflow-x: hidden;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Inter', 'Montserrat', sans-serif;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text-primary);
        }
        
        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        /* HEADER SECTION */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid var(--border-color);
        }
        
        .header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 800;
            color: var(--primary);
            font-size: 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover {
            color: var(--secondary);
            text-decoration: none;
            transform: scale(1.02);
        }
        
        .navbar-brand-svg {
            height: 50px;
            width: 50px;
            margin-right: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .navbar-brand-svg:hover {
            transform: rotate(5deg) scale(1.05);
            box-shadow: var(--shadow-lg);
        }
        
        .navbar-nav .nav-link {
            font-weight: 600;
            color: var(--text-secondary);
            padding: 0.75rem 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border-radius: 12px;
            margin: 0 4px;
        }
        
        .navbar-nav .nav-link:before {
            content: '';
            position: absolute;
            bottom: 6px;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
            border-radius: 1px;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--primary);
            background: rgba(26, 54, 93, 0.05);
        }
        
        .navbar-nav .nav-link:hover:before {
            width: 70%;
        }
        
        .navbar-nav .nav-link.active {
            color: var(--primary);
            background: rgba(26, 54, 93, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            font-weight: 700;
            padding: 0.65rem 1.8rem;
            border-radius: 50px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            color: white;
            text-decoration: none;
        }
        
        .btn-login:hover:before {
            left: 100%;
        }
        
        /* HERO SECTION */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, 
                rgba(26, 54, 93, 0.95) 0%, 
                rgba(37, 99, 235, 0.9) 50%, 
                rgba(245, 158, 11, 0.85) 100%
            ), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="300" cy="700" r="120" fill="url(%23a)"/><circle cx="700" cy="800" r="80" fill="url(%23a)"/></svg>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            margin-top: 80px;
        }
        
        .hero:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float-particle 20s infinite linear;
        }
        
        .particle:nth-child(1) { width: 4px; height: 4px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 6px; height: 6px; left: 20%; animation-delay: 2s; }
        .particle:nth-child(3) { width: 3px; height: 3px; left: 30%; animation-delay: 4s; }
        .particle:nth-child(4) { width: 5px; height: 5px; left: 40%; animation-delay: 6s; }
        .particle:nth-child(5) { width: 4px; height: 4px; left: 50%; animation-delay: 8s; }
        .particle:nth-child(6) { width: 7px; height: 7px; left: 60%; animation-delay: 10s; }
        .particle:nth-child(7) { width: 3px; height: 3px; left: 70%; animation-delay: 12s; }
        .particle:nth-child(8) { width: 5px; height: 5px; left: 80%; animation-delay: 14s; }
        .particle:nth-child(9) { width: 4px; height: 4px; left: 90%; animation-delay: 16s; }
        
        .hero-content {
            color: white;
            position: relative;
            z-index: 2;
            max-width: 600px;
        }
        
        .hero h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            animation: fadeInUp 1s ease;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .hero h1 .highlight {
            background: linear-gradient(135deg, #fbbf24, #f59e0b, #d97706);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            line-height: 1.7;
            animation: fadeInUp 1s ease 0.2s forwards;
            opacity: 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .hero-buttons {
            animation: fadeInUp 1s ease 0.4s forwards;
            opacity: 0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-hero {
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            font-weight: 700;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }
        
        .btn-hero:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s;
        }
        
        .btn-hero:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            color: var(--primary);
            text-decoration: none;
        }
        
        .btn-hero:hover:before {
            left: 100%;
        }
        
        .btn-hero-outline {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-weight: 700;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .btn-hero-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.6);
            color: white;
            text-decoration: none;
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .hero-image {
            position: relative;
            animation: float 6s ease-in-out infinite;
            z-index: 2;
        }
        
        .hero-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            height: 450px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hero-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.25) 0%, 
                rgba(255, 255, 255, 0.1) 50%, 
                rgba(255, 255, 255, 0.05) 100%
            );
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .hero-image-placeholder::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(255,255,255,0.1), transparent, rgba(255,255,255,0.1), transparent);
            animation: rotate 20s linear infinite;
        }
        
        .hero-image-icon {
            font-size: 100px;
            color: rgba(255,255,255,0.9);
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
            animation: pulse 3s ease-in-out infinite;
        }
        
        /* ABOUT SECTION */
        .section {
            padding: 120px 0;
            position: relative;
        }
        
        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 20px;
            color: var(--text-primary);
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            width: 120px;
            height: 5px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            bottom: 0;
            left: 0;
            border-radius: 3px;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 600px;
            line-height: 1.7;
        }
        
        .about-content p {
            margin-bottom: 1.8rem;
            font-size: 1.15rem;
            line-height: 1.8;
            color: var(--text-secondary);
        }
        
        .about-features {
            margin-top: 2rem;
        }
        
        .about-feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(26, 54, 93, 0.03);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .about-feature-item:hover {
            background: rgba(26, 54, 93, 0.08);
            transform: translateX(8px);
        }
        
        .about-feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            font-size: 18px;
        }
        
        .about-image {
            position: relative;
        }
        
        .about-image-container {
            position: relative;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            height: 400px;
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
        }
        
        .about-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .about-image-placeholder .pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background-image: 
                radial-gradient(circle at 25% 25%, #ffffff 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, #ffffff 2px, transparent 2px);
            background-size: 50px 50px;
            background-position: 0 0, 25px 25px;
        }
        
        .about-image-icon {
            font-size: 80px;
            color: rgba(255,255,255,0.9);
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.2));
        }
        
        /* FEATURES SECTION */
        .features {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
        }
        
        .features:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(26,54,93,0.05)"/><circle cx="80" cy="20" r="2" fill="rgba(37,99,235,0.05)"/><circle cx="20" cy="80" r="2" fill="rgba(245,158,11,0.05)"/><circle cx="80" cy="80" r="2" fill="rgba(16,185,129,0.05)"/></svg>');
            background-size: 100px 100px;
        }
        
        .feature-box {
            padding: 3rem 2rem;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-lg);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            height: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .feature-box:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .feature-box:hover {
            transform: translateY(-12px);
            box-shadow: var(--shadow-xl);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .feature-box:hover:before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-icon:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .feature-box:hover .feature-icon {
            transform: scale(1.05) rotate(5deg);
        }
        
        .feature-box:hover .feature-icon:before {
            left: 100%;
        }
        
        .feature-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .feature-text {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 1.05rem;
        }
        
        /* STATS SECTION */
        .stats {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .stats:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><polygon points="0,0 100,0 50,100" fill="rgba(255,255,255,0.03)"/><polygon points="0,100 100,100 50,0" fill="rgba(255,255,255,0.02)"/></svg>');
            background-size: 200px 200px;
        }
        
        .stat-item {
            text-align: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .stat-number {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            font-family: 'Inter', 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-number span {
            font-size: 2rem;
            margin-left: 5px;
            opacity: 0.8;
        }
        
        .stat-title {
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.9;
            font-weight: 600;
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.7;
        }
        
        /* TESTIMONIALS */
        .testimonial-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            position: relative;
        }
        
        .testimonial-box:after {
            content: '\f10e';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 30px;
            top: 20px;
            font-size: 2rem;
            color: rgba(78,115,223,0.1);
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            color: #555;
            line-height: 1.7;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .testimonial-avatar-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-right: 15px;
        }
        
        .testimonial-info h5 {
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .testimonial-info p {
            color: #777;
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* CTA SECTION */
        .cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cta:before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }
        
        .cta:after {
            content: "";
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .cta-text {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
            opacity: 0.9;
        }
        
        .btn-cta {
            background-color: white;
            color: var(--primary);
            font-weight: 600;
            padding: 0.75rem 2.5rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            border: 2px solid white;
            font-size: 1.1rem;
        }
        
        .btn-cta:hover {
            background-color: transparent;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* FOOTER */
        .footer {
            background-color: #1c2331;
            color: white;
            padding: 70px 0 0;
        }
        
        .nav-logo-placeholder {
            height: 50px;
            width: 150px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .footer-about {
            margin-bottom: 30px;
        }
        
        .footer-about p {
            color: rgba(255,255,255,0.7);
            line-height: 1.7;
        }
        
        .footer-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-title:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 2px;
            background: var(--primary);
            bottom: 0;
            left: 0;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.7);
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            padding: 5px 0;
        }
        
        .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .footer-links a i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .footer-contact p {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: rgba(255,255,255,0.7);
        }
        
        .footer-contact i {
            margin-right: 15px;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .footer-social {
            margin-top: 30px;
        }
        
        .footer-social a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .footer-social a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            background-color: #151c27;
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        .footer-bottom p {
            margin: 0;
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
        }
        
        /* ANIMATIONS */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        @keyframes float-particle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-10vh) translateX(100px);
                opacity: 0;
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        @keyframes shimmer {
            0% {
                background-position: -200px 0;
            }
            100% {
                background-position: calc(200px + 100%) 0;
            }
        }
        
        /* RESPONSIVE */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .about-image:before {
                display: none;
            }
            
            .section {
                padding: 80px 0;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .feature-box {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                height: auto;
                padding: 100px 0;
            }
            
            .hero-image {
                margin-top: 50px;
            }
            
            .btn-hero, .btn-hero-outline {
                display: block;
                width: 100%;
                margin-bottom: 15px;
                margin-right: 0;
            }
            
            .section {
                padding: 60px 0;
            }
            
            .about-image {
                margin-bottom: 30px;
            }
            
            .stat-item {
                margin-bottom: 30px;
            }
            
            .footer-section {
                margin-bottom: 30px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-brand img {
                height: 35px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .cta-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <div class="navbar-brand-svg">
                        <i class="fas fa-flask"></i>
                    </div>
                    <span>NCKH System</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item active">
                            <a class="nav-link" href="#home">Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#about">Giới thiệu</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#features">Tính năng</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#testimonials">Chia sẻ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">Liên hệ</a>
                        </li>
                        <li class="nav-item ml-lg-3">
                            <a href="login.php" class="btn btn-login">
                                <i class="fas fa-sign-in-alt"></i>
                                Đăng nhập
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6 hero-content">
                    <h1>
                        Quản lý <span class="highlight">Nghiên cứu</span><br>
                        Khoa học Hiện đại
                    </h1>
                    <p>Hệ thống quản lý toàn diện và thông minh cho các hoạt động nghiên cứu khoa học của sinh viên và giảng viên tại Trường Đại học Cần Thơ. Nâng tầm nghiên cứu với công nghệ tiên tiến.</p>
                    <div class="hero-buttons">
                        <a href="login.php" class="btn btn-hero">
                            <i class="fas fa-rocket"></i>
                            Bắt đầu ngay
                        </a>
                        <a href="#features" class="btn btn-hero-outline">
                            <i class="fas fa-play-circle"></i>
                            Khám phá tính năng
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image">
                    <div class="hero-image-container">
                        <div class="hero-image-placeholder">
                            <div class="hero-image-icon">
                                <i class="fas fa-atom"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="section about" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-image">
                        <div class="about-image-container">
                            <div class="about-image-placeholder">
                                <div class="pattern"></div>
                                <div class="about-image-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0">
                    <div class="about-content">
                        <h2 class="section-title">Về Hệ thống NCKH</h2>
                        <p class="section-subtitle">Công nghệ tiên tiến phục vụ nghiên cứu khoa học</p>
                        <p>Hệ thống Quản lý Nghiên cứu Khoa học (NCKH) là nền tảng số hóa toàn diện được phát triển nhằm hỗ trợ và đẩy mạnh các hoạt động nghiên cứu khoa học tại Trường Đại học Cần Thơ.</p>
                        <p>Với giao diện thân thiện và tính năng đa dạng, hệ thống giúp quản lý hiệu quả quá trình đăng ký, thực hiện, theo dõi và đánh giá các đề tài nghiên cứu của sinh viên và giảng viên.</p>
                        
                        <div class="about-features">
                            <div class="about-feature-item">
                                <div class="about-feature-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <span><strong>Bảo mật cao:</strong> Đảm bảo an toàn dữ liệu nghiên cứu</span>
                            </div>
                            <div class="about-feature-item">
                                <div class="about-feature-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <span><strong>Đồng bộ thời gian thực:</strong> Cập nhật tức thì mọi thay đổi</span>
                            </div>
                            <div class="about-feature-item">
                                <div class="about-feature-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <span><strong>Phân tích thông minh:</strong> Báo cáo chi tiết và trực quan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section features" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title mx-auto" style="max-width: 500px;">Tính năng đẳng cấp</h2>
                <p class="section-subtitle mx-auto">Hệ thống cung cấp các công cụ mạnh mẽ và hiện đại cho việc quản lý nghiên cứu khoa học</p>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="feature-title">Quản lý đề tài</h3>
                        <p class="feature-text">Đăng ký, theo dõi và quản lý các đề tài nghiên cứu một cách chi tiết. Hỗ trợ quy trình phê duyệt và đánh giá tiến độ thực hiện.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Quản lý nhóm nghiên cứu</h3>
                        <p class="feature-text">Phân chia nhóm nghiên cứu, phân công nhiệm vụ và theo dõi đóng góp của từng thành viên trong quá trình thực hiện đề tài.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="feature-title">Quản lý tiến độ</h3>
                        <p class="feature-text">Thiết lập mốc thời gian, theo dõi tiến độ và nhận thông báo khi đến hạn các công việc trong quy trình nghiên cứu.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="feature-title">Quản lý tài liệu</h3>
                        <p class="feature-text">Lưu trữ, chia sẻ và quản lý tài liệu nghiên cứu một cách có hệ thống, hỗ trợ nhiều định dạng tài liệu khác nhau.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Thống kê & Báo cáo</h3>
                        <p class="feature-text">Tạo biểu đồ thống kê, báo cáo chi tiết về các hoạt động nghiên cứu, hỗ trợ việc đánh giá và ra quyết định.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Thông báo & Nhắc nhở</h3>
                        <p class="feature-text">Hệ thống thông báo thông minh giúp không bỏ lỡ các mốc thời gian quan trọng và cập nhật tình hình đề tài kịp thời.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div class="stat-number" data-count="<?php echo $stats['projects']; ?>"><?php echo $stats['projects']; ?><span>+</span></div>
                        <div class="stat-title">Đề tài nghiên cứu</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-number" data-count="<?php echo $stats['students']; ?>"><?php echo $stats['students']; ?><span>+</span></div>
                        <div class="stat-title">Sinh viên tham gia</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-number" data-count="<?php echo $stats['teachers']; ?>"><?php echo $stats['teachers']; ?><span>+</span></div>
                        <div class="stat-title">Giảng viên hướng dẫn</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="stat-number" data-count="<?php echo $stats['departments']; ?>"><?php echo $stats['departments']; ?><span>+</span></div>
                        <div class="stat-title">Khoa tham gia</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section testimonials" id="testimonials">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title mx-auto" style="max-width: 500px;">Phản hồi từ cộng đồng</h2>
                <p class="section-subtitle mx-auto">Những đánh giá chân thực từ giảng viên và sinh viên đã trải nghiệm hệ thống</p>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-box">
                        <p class="testimonial-text">"Hệ thống NCKH đã giúp tôi quản lý các đề tài nghiên cứu của sinh viên một cách hiệu quả. Giao diện thân thiện, dễ sử dụng và có đầy đủ các tính năng cần thiết."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="testimonial-info">
                                <h5>TS. Nguyễn Văn A</h5>
                                <p>Giảng viên Khoa Công nghệ</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-box">
                        <p class="testimonial-text">"Việc đăng ký đề tài và theo dõi tiến độ trở nên đơn giản hơn bao giờ hết. Tôi có thể dễ dàng cập nhật tiến độ công việc và trao đổi với giáo viên hướng dẫn."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar-icon" style="background-color: var(--info);">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="testimonial-info">
                                <h5>Trần Thị B</h5>
                                <p>Sinh viên Khoa CNTT</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-box">
                        <p class="testimonial-text">"Hệ thống thống kê và báo cáo giúp tôi có cái nhìn tổng quan về hoạt động nghiên cứu của khoa. Đây là công cụ không thể thiếu cho công tác quản lý khoa học."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar-icon" style="background-color: var(--success);">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="testimonial-info">
                                <h5>PGS.TS. Lê Văn C</h5>
                                <p>Trưởng khoa</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2 class="cta-title">Sẵn sàng khởi đầu hành trình nghiên cứu?</h2>
            <p class="cta-text">Hãy tham gia cùng chúng tôi để nâng cao hiệu quả quản lý và thúc đẩy các hoạt động nghiên cứu khoa học tại Trường Đại học Cần Thơ. Trải nghiệm ngay hôm nay!</p>
            <a href="login.php" class="btn btn-cta">
                <i class="fas fa-rocket mr-2"></i>
                Đăng nhập ngay
            </a>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <div class="nav-logo-placeholder">
                        <i class="fas fa-flask mr-2"></i> NCKH System
                    </div>
                    <div class="footer-about">
                        <p>Hệ thống Quản lý Nghiên cứu Khoa học - Công cụ hỗ trợ đắc lực cho các hoạt động nghiên cứu khoa học tại Trường Đại học Cần Thơ.</p>
                    </div>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-5 mb-md-0">
                    <h4 class="footer-title">Liên kết</h4>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-angle-right"></i> Trang chủ</a></li>
                        <li><a href="#about"><i class="fas fa-angle-right"></i> Giới thiệu</a></li>
                        <li><a href="#features"><i class="fas fa-angle-right"></i> Tính năng</a></li>
                        <li><a href="#testimonials"><i class="fas fa-angle-right"></i> Đánh giá</a></li>
                        <li><a href="#contact"><i class="fas fa-angle-right"></i> Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4 mb-5 mb-md-0">
                    <h4 class="footer-title">Hỗ trợ</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-question-circle"></i> Câu hỏi thường gặp</a></li>
                        <li><a href="#"><i class="fas fa-book"></i> Hướng dẫn sử dụng</a></li>
                        <li><a href="#"><i class="fas fa-headset"></i> Hỗ trợ kỹ thuật</a></li>
                        <li><a href="#"><i class="fas fa-shield-alt"></i> Chính sách bảo mật</a></li>
                        <li><a href="#"><i class="fas fa-file-contract"></i> Điều khoản sử dụng</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4">
                    <h4 class="footer-title">Liên hệ</h4>
                    <div class="footer-contact">
                        <p><i class="fas fa-map-marker-alt"></i> Khu II, Đường 3/2, Phường Xuân Khánh, Quận Ninh Kiều, TP. Cần Thơ</p>
                        <p><i class="fas fa-phone-alt"></i> (0292) 3832663</p>
                        <p><i class="fas fa-envelope"></i> dhct@ctu.edu.vn</p>
                        <p><i class="fas fa-globe"></i> www.ctu.edu.vn</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> Hệ thống Quản lý Nghiên cứu Khoa học - Trường Đại học Cần Thơ. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Header scroll effect with improved performance
            let scrollTimer = null;
            $(window).scroll(function() {
                if (scrollTimer) clearTimeout(scrollTimer);
                scrollTimer = setTimeout(function() {
                    if ($(window).scrollTop() > 50) {
                        $('.header').addClass('scrolled');
                    } else {
                        $('.header').removeClass('scrolled');
                    }
                }, 10);
            });
            
            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                var target = $(this.hash);
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 1000, 'easeInOutCubic');
                }
            });
            
            // Enhanced stats counting animation
            function animateCounter($element, start, end, duration) {
                $({counter: start}).animate({counter: end}, {
                    duration: duration,
                    easing: 'easeOutCubic',
                    step: function() {
                        $element.text(Math.ceil(this.counter));
                    },
                    complete: function() {
                        $element.text(end + '+');
                    }
                });
            }
            
            // Intersection Observer for stats animation
            if ('IntersectionObserver' in window) {
                const statsObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const $statNumber = $(entry.target).find('.stat-number');
                            const targetCount = $statNumber.data('count');
                            animateCounter($statNumber, 0, targetCount, 2500);
                            statsObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });
                
                $('.stat-item').each(function() {
                    statsObserver.observe(this);
                });
            }
            
            // Feature boxes hover effect
            $('.feature-box').hover(
                function() {
                    $(this).addClass('shadow-lg').removeClass('shadow-md');
                },
                function() {
                    $(this).removeClass('shadow-lg').addClass('shadow-md');
                }
            );
            
            // Parallax effect for hero section
            $(window).scroll(function() {
                const scrolled = $(this).scrollTop();
                const parallax = $('.hero');
                const speed = scrolled * 0.5;
                parallax.css('background-position', `center ${speed}px`);
            });
            
            // Add loading animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe elements for animation
            $('.feature-box, .testimonial-box, .about-content, .about-image').each(function() {
                this.style.opacity = '0';
                this.style.transform = 'translateY(30px)';
                this.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(this);
            });
        });
        
        // Add easing functions
        $.easing.easeInOutCubic = function (x, t, b, c, d) {
            if ((t/=d/2) < 1) return c/2*t*t*t + b;
            return c/2*((t-=2)*t*t + 2) + b;
        };
        
        $.easing.easeOutCubic = function (x, t, b, c, d) {
            return c*((t=t/d-1)*t*t + 1) + b;
        };
    </script>
</body>
</html>