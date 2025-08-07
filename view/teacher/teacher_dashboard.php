<?php
// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên
$teacher_id = $_SESSION['user_id'];
$total_projects = $in_progress = $completed = $pending = $member_count = 0;
$recent_projects = null; // Initialize as null
$notifications = null; // Initialize as null

// Thêm kiểm tra lỗi cho mỗi truy vấn
// Đếm tổng số đề tài của giảng viên
$stmt = $conn->prepare("SELECT COUNT(*) as total_projects FROM de_tai_nghien_cuu WHERE GV_MAGV = ?");
if ($stmt) {
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_projects = $result->fetch_assoc()['total_projects'];
    $stmt->close();
} else {
    error_log("Prepare failed: " . $conn->error);
}

// Đếm số đề tài đang tiến hành
$stmt = $conn->prepare("SELECT COUNT(*) as in_progress FROM de_tai_nghien_cuu WHERE GV_MAGV = ? AND DT_TRANGTHAI = 'Đang tiến hành'");
if ($stmt) {
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $in_progress = $result->fetch_assoc()['in_progress'];
    $stmt->close();
} else {
    error_log("Prepare failed: " . $conn->error);
}

// Đếm số đề tài đã hoàn thành
$stmt = $conn->prepare("SELECT COUNT(*) as completed FROM de_tai_nghien_cuu WHERE GV_MAGV = ? AND DT_TRANGTHAI = 'Đã hoàn thành'");
if ($stmt) {
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $completed = $result->fetch_assoc()['completed'];
    $stmt->close();
} else {
    error_log("Prepare failed: " . $conn->error);
}

// Đếm số đề tài chờ phê duyệt
$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM de_tai_nghien_cuu WHERE GV_MAGV = ? AND DT_TRANGTHAI = 'Chờ phê duyệt'");
if ($stmt) {
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending = $result->fetch_assoc()['pending'];
    $stmt->close();
} else {
    error_log("Prepare failed: " . $conn->error);
}

// Lấy 5 đề tài gần nhất
$stmt = $conn->prepare("SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI, DT_NGAYTAO 
                      FROM de_tai_nghien_cuu 
                      WHERE GV_MAGV = ? 
                      ORDER BY DT_NGAYTAO DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("s", $teacher_id);
    if ($stmt->execute()) { // Check if execute is successful
        $recent_projects = $stmt->get_result();
    } else {
        error_log("Execute failed in recent_projects query: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Prepare failed: " . $conn->error);
}

// Lấy danh sách các thành viên tham gia đề tài
$query = "SELECT COUNT(DISTINCT sv.SV_MASV) as member_count 
          FROM sinh_vien sv 
          JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV 
          JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT 
          WHERE dt.GV_MAGV = ?";
          
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $member_count = $result->fetch_assoc()['member_count'];
    $stmt->close();
} else {
    error_log("Prepare failed: " . $conn->error);
}

// Kiểm tra có bảng thông báo không
$table_check = $conn->query("SHOW TABLES LIKE 'thong_bao'");
if ($table_check && $table_check->num_rows > 0) {
    // Lấy các thông báo gần nhất
    $stmt = $conn->prepare("SELECT * FROM thong_bao WHERE GV_MAGV = ? ORDER BY TB_NGAYTAO DESC LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("s", $teacher_id);
        if ($stmt->execute()) { // Check if execute is successful
            $notifications = $stmt->get_result();
        } else {
            error_log("Execute failed in notifications query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Prepare failed: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển | Giảng viên</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    
    <!-- Custom fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap CSS từ CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS từ CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS từ CDN -->
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.css">
    
    <style>
        /* Enhanced Dashboard Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Nunito', sans-serif;
        }
        
        #wrapper {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes bounce {
            0%, 20%, 60%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            80% { transform: translateY(-5px); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes glow {
            0% { box-shadow: 0 0 5px rgba(78, 115, 223, 0.3); }
            50% { box-shadow: 0 0 20px rgba(78, 115, 223, 0.6); }
            100% { box-shadow: 0 0 5px rgba(78, 115, 223, 0.3); }
        }
        
        .card-counter {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 20px 10px;
            background: linear-gradient(135deg, #fff 0%, #f8f9fc 100%);
            height: 120px;
            border-radius: 15px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out;
        }
        
        .card-counter::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }
        
        .card-counter:hover::before {
            left: 100%;
        }
        
        .card-counter:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.25);
            animation: glow 2s infinite;
        }
        
        .card-counter i {
            font-size: 4.5em;
            opacity: 0.15;
            transition: all 0.3s ease;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .card-counter:hover i {
            opacity: 0.25;
            transform: translateY(-50%) scale(1.1);
            animation: bounce 1s ease-in-out;
        }
        
        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 15px;
            font-size: 32px;
            font-weight: 700;
            display: block;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            animation: slideInRight 0.8s ease-out;
        }
        
        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 65px;
            text-transform: capitalize;
            opacity: 0.9;
            display: block;
            font-size: 14px;
            font-weight: 600;
            animation: slideInRight 1s ease-out;
        }
        
        .card-counter.primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: #FFF;
            animation-delay: 0.1s;
        }
        
        .card-counter.danger {
            background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);
            color: #FFF;
            animation-delay: 0.2s;
        }
        
        .card-counter.success {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: #FFF;
            animation-delay: 0.3s;
        }
        
        .card-counter.info {
            background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
            color: #FFF;
            animation-delay: 0.4s;
        }
        
        .quick-access-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            border-radius: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            animation: slideInLeft 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        .quick-access-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(78, 115, 223, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .quick-access-card:hover::before {
            left: 100%;
        }
        
        .quick-access-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.2);
            border-color: #4e73df;
        }
        
        .card.shadow {
            border-radius: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            animation: slideInUp 0.6s ease-out;
        }
        
        .card.shadow:hover {
            transform: translateY(-2px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        .project-status {
            padding: 6px 12px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .project-status::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .project-status:hover::before {
            left: 100%;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(246, 194, 62, 0.3);
        }
        
        .status-progress {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(78, 115, 223, 0.3);
        }
        
        .status-completed {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(28, 200, 138, 0.3);
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(231, 74, 59, 0.3);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
            transform: scale(1.01);
            transition: all 0.3s ease;
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
            animation: slideInLeft 0.8s ease-out;
        }
        
        .activity-timeline:before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #4e73df, #1cc88a);
            border-radius: 2px;
        }
        
        .activity-item {
            position: relative;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            padding: 10px;
            border-radius: 8px;
        }
        
        .activity-item:hover {
            background: rgba(78, 115, 223, 0.05);
            transform: translateX(5px);
        }
        
        .activity-item:before {
            content: '';
            position: absolute;
            left: -25px;
            top: 8px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            border: 3px solid white;
            box-shadow: 0 0 10px rgba(78, 115, 223, 0.3);
            animation: pulse 2s infinite;
        }
        
        .topbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .navbar-search .form-control {
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .navbar-search .form-control:focus {
            transform: scale(1.02);
            box-shadow: 0 0 15px rgba(78, 115, 223, 0.3);
        }
        
        .btn {
            border-radius: 25px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            border: none;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            border: none;
        }
        
        .dropdown-menu {
            border-radius: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideInUp 0.3s ease-out;
        }
        
        .dropdown-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 8px;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            transform: translateX(5px);
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4e73df;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Enhanced Scroll Bar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #224abe, #13855c);
        }
        
        /* Chart Container Enhancement */
        .chart-area, .chart-pie {
            position: relative;
            animation: slideInUp 0.8s ease-out;
        }
        
        /* Notification Badge */
        .badge-counter {
            animation: pulse 2s infinite;
        }
        
        /* Enhanced Footer */
        .sticky-footer {
            background: linear-gradient(135deg, #f8f9fc 0%, #e2e6ea 100%) !important;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        /* Page Title Animation */
        .page-title {
            animation: slideInLeft 0.6s ease-out;
        }
        
        /* Stats Animation Counter */
        .counter-animation {
            animation: fadeIn 1s ease-out;
        }
        
        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .card-counter {
                height: 100px;
                margin-bottom: 20px;
            }
            
            .card-counter .count-numbers {
                font-size: 24px;
                top: 10px;
            }
            
            .card-counter .count-name {
                top: 50px;
                font-size: 12px;
            }
            
            .card-counter i {
                font-size: 3em;
            }
        }
        
        /* Advanced Hover Effects */
        .card-hover-effect {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .card-hover-effect:hover {
            transform: perspective(1000px) rotateX(5deg) rotateY(5deg);
        }
        
        /* Staggered Animation */
        .stagger-animation {
            animation: slideInUp 0.6s ease-out;
        }
        
        .stagger-animation:nth-child(1) { animation-delay: 0.1s; }
        .stagger-animation:nth-child(2) { animation-delay: 0.2s; }
        .stagger-animation:nth-child(3) { animation-delay: 0.3s; }
        .stagger-animation:nth-child(4) { animation-delay: 0.4s; }
        
        .calendar-card {
            height: 350px;
            overflow-y: auto;
        }
        
        .event-date {
            width: 60px;
            height: 60px;
            background: #4e73df;
            color: white;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .event-date .day {
            font-size: 20px;
            font-weight: bold;
        }
        
        .event-date .month {
            font-size: 12px;
        }
        
        /* Additional Enhancement Styles */
        .progress-indicator {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0 0 15px 15px;
            overflow: hidden;
        }
        
        .progress-indicator .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.6), rgba(255, 255, 255, 0.9));
            border-radius: 0;
            transition: width 2s ease-in-out;
        }
        
        .success-indicator {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 14px;
            opacity: 0.7;
        }
        
        .notification-pulse {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .pulse-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #ff4757;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        
        .icon-wrapper {
            transition: all 0.3s ease;
        }
        
        .quick-access-card:hover .icon-wrapper {
            transform: scale(1.2) rotate(5deg);
        }
        
        .card-icon-overlay {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 12px;
            opacity: 0.3;
        }
        
        .project-title {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .project-title:hover {
            color: #4e73df;
            font-weight: 600;
        }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
        }
        
        .empty-state i {
            opacity: 0.3;
            margin-bottom: 20px;
        }
        
        .activity-icon {
            width: 30px;
            text-align: center;
        }
        
        .activity-content {
            border-left: 2px solid #f8f9fc;
            padding-left: 15px;
            margin-left: 15px;
        }
        
        .table-hover-highlight {
            background: linear-gradient(90deg, rgba(78, 115, 223, 0.05), rgba(78, 115, 223, 0.1)) !important;
            border-left: 4px solid #4e73df;
        }
        
        .context-menu {
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 5px 0;
            min-width: 150px;
        }
        
        .context-item {
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .context-item:hover {
            background: #4e73df;
            color: white;
        }
        
        .notification-alert {
            animation: slideInRight 0.5s ease-out;
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include '../../include/teacher_sidebar.php'; ?>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Tìm kiếm..." aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header">
                                    Thông báo
                                </h6>
                                <?php if ($notifications && $notifications->num_rows > 0): ?>
                                    <?php while($notification = $notifications->fetch_assoc()): ?>
                                        <a class="dropdown-item d-flex align-items-center" href="#">
                                            <div class="mr-3">
                                                <div class="icon-circle bg-primary">
                                                    <i class="fas fa-file-alt text-white"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="small text-gray-500"><?= date('d/m/Y', strtotime($notification['TB_NGAYTAO'])) ?></div>
                                                <span class="font-weight-bold"><?= $notification['TB_NOIDUNG'] ?></span>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <div>
                                            <span class="font-weight-bold">Không có thông báo mới</span>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Xem tất cả thông báo</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= $_SESSION['user_name'] ?></span>
                                <img class="img-profile rounded-circle" src="/NLNganh/assets/img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="/NLNganh/view/teacher/manage_profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Hồ sơ cá nhân
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Đăng xuất
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 page-title">
                            <i class="fas fa-tachometer-alt mr-2"></i>Bảng điều khiển
                        </h1>
                        <div class="d-flex align-items-center">
                            <div class="dropdown mr-3">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="timeRangeDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-calendar-alt mr-1"></i>Tháng này
                                </button>
                                <div class="dropdown-menu" aria-labelledby="timeRangeDropdown">
                                    <a class="dropdown-item" href="#"><i class="fas fa-calendar-day mr-2"></i>Hôm nay</a>
                                    <a class="dropdown-item" href="#"><i class="fas fa-calendar-week mr-2"></i>Tuần này</a>
                                    <a class="dropdown-item active" href="#"><i class="fas fa-calendar-alt mr-2"></i>Tháng này</a>
                                    <a class="dropdown-item" href="#"><i class="fas fa-calendar mr-2"></i>Năm này</a>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary shadow-sm" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt fa-sm text-white-50 mr-1"></i>Làm mới
                            </button>
                            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm ml-2">
                                <i class="fas fa-download fa-sm text-white-50 mr-1"></i>Tạo báo cáo
                            </a>
                        </div>
                    </div>

                    <!-- Content Row - Statistics Cards -->
                    <div class="row">
                        <!-- Tổng số đề tài -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter primary stagger-animation counter-animation" data-count="<?= $total_projects ?>">
                                <i class="fa fa-folder-open"></i>
                                <span class="count-numbers counter-value">0</span>
                                <span class="count-name">Tổng số đề tài</span>
                                <div class="card-icon-overlay">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Đề tài đang tiến hành -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter info stagger-animation counter-animation" data-count="<?= $in_progress ?>">
                                <i class="fa fa-spinner"></i>
                                <span class="count-numbers counter-value">0</span>
                                <span class="count-name">Đang tiến hành</span>
                                <div class="progress-indicator">
                                    <div class="progress-bar" style="width: <?= $total_projects > 0 ? ($in_progress / $total_projects) * 100 : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Đề tài hoàn thành -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter success stagger-animation counter-animation" data-count="<?= $completed ?>">
                                <i class="fa fa-check-circle"></i>
                                <span class="count-numbers counter-value">0</span>
                                <span class="count-name">Đã hoàn thành</span>
                                <div class="success-indicator">
                                    <i class="fas fa-trophy text-warning"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Đề tài chờ phê duyệt -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter danger stagger-animation counter-animation" data-count="<?= $pending ?>">
                                <i class="fa fa-clock"></i>
                                <span class="count-numbers counter-value">0</span>
                                <span class="count-name">Chờ phê duyệt</span>
                                <?php if ($pending > 0): ?>
                                <div class="notification-pulse">
                                    <span class="pulse-dot"></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row - Charts -->
                    <div class="row">
                        <!-- Area Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <!-- Card Header -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Tổng quan đề tài</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="projectStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pie Chart -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <!-- Card Header -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Phân bổ đề tài</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="projectDistributionChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Đang tiến hành
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> Đã hoàn thành
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-warning"></i> Chờ phê duyệt
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row - Recent Projects and Quick Access -->
                    <div class="row">
                        <!-- Recent Projects -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow mb-4 card-hover-effect">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-project-diagram mr-2"></i>Đề tài gần đây
                                    </h6>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fas fa-filter mr-1"></i>Lọc
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#"><i class="fas fa-clock mr-2"></i>Mới nhất</a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-check mr-2"></i>Đã hoàn thành</a>
                                            <a class="dropdown-item" href="#"><i class="fas fa-spinner mr-2"></i>Đang tiến hành</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" width="100%" cellspacing="0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th><i class="fas fa-hashtag mr-1"></i>Mã đề tài</th>
                                                    <th><i class="fas fa-file-alt mr-1"></i>Tên đề tài</th>
                                                    <th><i class="fas fa-info-circle mr-1"></i>Trạng thái</th>
                                                    <th><i class="fas fa-calendar mr-1"></i>Ngày tạo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($recent_projects && $recent_projects->num_rows > 0): ?>
                                                    <?php $index = 0; ?>
                                                    <?php while ($project = $recent_projects->fetch_assoc()): ?>
                                                        <tr class="project-row" style="animation-delay: <?= $index * 0.1 ?>s">
                                                            <td class="font-weight-bold text-primary"><?= $project['DT_MADT'] ?></td>
                                                            <td>
                                                                <div class="project-title" title="<?= $project['DT_TENDT'] ?>">
                                                                    <?= strlen($project['DT_TENDT']) > 30 ? substr($project['DT_TENDT'], 0, 30) . '...' : $project['DT_TENDT'] ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                $status_class = $status_icon = '';
                                                                switch($project['DT_TRANGTHAI']) {
                                                                    case 'Chờ phê duyệt':
                                                                        $status_class = 'status-pending';
                                                                        $status_icon = 'fas fa-clock';
                                                                        break;
                                                                    case 'Đang tiến hành':
                                                                        $status_class = 'status-progress';
                                                                        $status_icon = 'fas fa-spinner fa-spin';
                                                                        break;
                                                                    case 'Đã hoàn thành':
                                                                        $status_class = 'status-completed';
                                                                        $status_icon = 'fas fa-check-circle';
                                                                        break;
                                                                    case 'Đã từ chối':
                                                                        $status_class = 'status-rejected';
                                                                        $status_icon = 'fas fa-times-circle';
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="project-status <?= $status_class ?>">
                                                                    <i class="<?= $status_icon ?> mr-1"></i><?= $project['DT_TRANGTHAI'] ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted">
                                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                                    <?= isset($project['DT_NGAYTAO']) ? date('d/m/Y', strtotime($project['DT_NGAYTAO'])) : 'N/A' ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <?php $index++; ?>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4">
                                                            <div class="empty-state">
                                                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                                                <p class="text-muted">Không có đề tài nào</p>
                                                                <a href="/NLNganh/view/teacher/create_project.php" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-plus mr-1"></i>Tạo đề tài mới
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="/NLNganh/view/teacher/manage_projects.php" class="btn btn-primary">
                                            <i class="fas fa-eye mr-2"></i>Xem tất cả đề tài
                                            <span class="badge badge-light ml-2"><?= $total_projects ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access and Timeline -->
                        <div class="col-lg-6 mb-4">
                            <!-- Quick Access -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        <i class="fas fa-plus-circle mr-1"></i>Thêm đề tài mới
                                                    </div>
                                                    <div class="small text-gray-600">Tạo đề tài nghiên cứu mới</div>
                                                    <div class="mt-2">
                                                        <span class="badge badge-primary">Nhanh chóng</span>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="icon-wrapper">
                                                        <i class="fas fa-plus-circle fa-2x text-primary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/create_project.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        <i class="fas fa-user-edit mr-1"></i>Quản lý hồ sơ
                                                    </div>
                                                    <div class="small text-gray-600">Cập nhật thông tin cá nhân</div>
                                                    <div class="mt-2">
                                                        <span class="badge badge-success">Quan trọng</span>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="icon-wrapper">
                                                        <i class="fas fa-user-edit fa-2x text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/manage_profile.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        <i class="fas fa-chart-bar mr-1"></i>Báo cáo
                                                    </div>
                                                    <div class="small text-gray-600">Xem báo cáo thống kê</div>
                                                    <div class="mt-2">
                                                        <span class="badge badge-info">Phân tích</span>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="icon-wrapper">
                                                        <i class="fas fa-chart-bar fa-2x text-info"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/reports.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                        <i class="fas fa-users mr-1"></i>Sinh viên tham gia
                                                    </div>
                                                    <div class="small text-gray-600">Quản lý sinh viên: <?= $member_count ?></div>
                                                    <div class="mt-2">
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-users mr-1"></i><?= $member_count ?> thành viên
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="icon-wrapper">
                                                        <i class="fas fa-users fa-2x text-warning"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/manage_students.php" class="stretched-link"></a>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity Timeline -->
                            <div class="card shadow mb-4 card-hover-effect">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-history mr-2"></i>Hoạt động gần đây
                                    </h6>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshActivity()">
                                        <i class="fas fa-sync-alt mr-1"></i>Làm mới
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="activity-timeline">
                                        <?php
                                        // Mảng hoạt động demo với thêm thông tin
                                        $activities = [
                                            [
                                                'time' => '2 giờ trước', 
                                                'text' => 'Đề tài "Nghiên cứu ứng dụng AI" được cập nhật trạng thái.',
                                                'type' => 'update',
                                                'icon' => 'fas fa-edit',
                                                'color' => 'primary'
                                            ],
                                            [
                                                'time' => '1 ngày trước', 
                                                'text' => 'Sinh viên Nguyễn Văn A đã nộp báo cáo đề tài.',
                                                'type' => 'submission',
                                                'icon' => 'fas fa-file-upload',
                                                'color' => 'success'
                                            ],
                                            [
                                                'time' => '3 ngày trước', 
                                                'text' => 'Bạn đã tạo đề tài nghiên cứu mới.',
                                                'type' => 'create',
                                                'icon' => 'fas fa-plus-circle',
                                                'color' => 'info'
                                            ],
                                            [
                                                'time' => '1 tuần trước', 
                                                'text' => 'Đề tài "Phát triển ứng dụng web" đã được duyệt.',
                                                'type' => 'approved',
                                                'icon' => 'fas fa-check-circle',
                                                'color' => 'success'
                                            ],
                                            [
                                                'time' => '2 tuần trước', 
                                                'text' => 'Hội đồng đánh giá đã được thành lập.',
                                                'type' => 'council',
                                                'icon' => 'fas fa-users',
                                                'color' => 'warning'
                                            ]
                                        ];
                                        
                                        foreach ($activities as $index => $activity) {
                                            echo '<div class="activity-item" style="animation-delay: ' . ($index * 0.1) . 's">';
                                            echo '<div class="d-flex align-items-start">';
                                            echo '<div class="activity-icon mr-3">';
                                            echo '<i class="' . $activity['icon'] . ' text-' . $activity['color'] . '"></i>';
                                            echo '</div>';
                                            echo '<div class="activity-content flex-grow-1">';
                                            echo '<p class="mb-1 small text-gray-600">';
                                            echo '<i class="fas fa-clock mr-1"></i>' . $activity['time'];
                                            echo '</p>';
                                            echo '<p class="mb-0 font-weight-medium">' . $activity['text'] . '</p>';
                                            if ($activity['type'] === 'submission') {
                                                echo '<div class="mt-2">';
                                                echo '<span class="badge badge-success">Đã nộp</span>';
                                                echo '</div>';
                                            } elseif ($activity['type'] === 'approved') {
                                                echo '<div class="mt-2">';
                                                echo '<span class="badge badge-success">Đã duyệt</span>';
                                                echo '</div>';
                                            }
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <button class="btn btn-sm btn-outline-primary" onclick="loadMoreActivities()">
                                            <i class="fas fa-chevron-down mr-1"></i>Xem thêm hoạt động
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Hệ thống quản lý nghiên cứu khoa học &copy; 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Đăng xuất</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy</button>
                    <a class="btn btn-primary" href="/NLNganh/logout.php">Đăng xuất</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript và các thư viện JS khác từ CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
        $(document).ready(function() {
            // Initialize dashboard
            initializeDashboard();
            
            // Counter animation
            animateCounters();
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Add loading states
            setupLoadingStates();
            
            // Biểu đồ phân bổ đề tài
            var ctx = document.getElementById("projectDistributionChart");
            var myPieChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ["Đang tiến hành", "Đã hoàn thành", "Chờ phê duyệt"],
                    datasets: [{
                        data: [<?= $in_progress ?>, <?= $completed ?>, <?= $pending ?>],
                        backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e'],
                        hoverBackgroundColor: ['#2e59d9', '#17a673', '#f4b619'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                        borderWidth: 2,
                        hoverBorderWidth: 3
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                        titleFontSize: 14,
                        bodyFontSize: 13,
                        cornerRadius: 8
                    },
                    legend: {
                        display: false
                    },
                    cutoutPercentage: 75,
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 2000,
                        easing: 'easeOutQuart'
                    }
                },
            });
            
            // Biểu đồ tổng quan đề tài với hiệu ứng
            var ctx2 = document.getElementById("projectStatusChart");
            var myLineChart = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6"],
                    datasets: [{
                        label: "Đề tài",
                        lineTension: 0.4,
                        backgroundColor: "rgba(78, 115, 223, 0.1)",
                        borderColor: "rgba(78, 115, 223, 1)",
                        pointRadius: 5,
                        pointBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointBorderColor: "rgba(255, 255, 255, 1)",
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointHoverBorderColor: "rgba(255, 255, 255, 1)",
                        pointHitRadius: 15,
                        pointBorderWidth: 3,
                        data: [0, 1, 2, 3, <?= $total_projects - 1 ?>, <?= $total_projects ?>],
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        xAxes: [{
                            time: {
                                unit: 'date'
                            },
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 7,
                                fontColor: '#858796'
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                fontColor: '#858796',
                                callback: function(value, index, values) {
                                    return value;
                                }
                            },
                            gridLines: {
                                color: "rgba(234, 236, 244, 0.8)",
                                zeroLineColor: "rgba(234, 236, 244, 0.8)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }],
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        titleMarginBottom: 10,
                        titleFontColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(tooltipItem, chart) {
                                var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                                return datasetLabel + ': ' + tooltipItem.yLabel + ' đề tài';
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    hover: {
                        animationDuration: 300
                    }
                }
            });
            
            // Auto refresh data every 5 minutes
            setInterval(function() {
                checkForUpdates();
            }, 300000);
            
            // Add real-time clock
            updateClock();
            setInterval(updateClock, 1000);
        });
        
        // Counter animation function
        function animateCounters() {
            $('.counter-value').each(function() {
                var $this = $(this);
                var countTo = $this.parent().data('count') || 0;
                
                $({ count: 0 }).animate({ count: countTo }, {
                    duration: 2000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.count));
                    },
                    complete: function() {
                        $this.text(countTo);
                        // Add pulse effect after animation
                        $this.parent().addClass('animated pulse');
                        setTimeout(function() {
                            $this.parent().removeClass('animated pulse');
                        }, 1000);
                    }
                });
            });
        }
        
        // Dashboard initialization
        function initializeDashboard() {
            // Add fade-in effect for cards
            $('.card').each(function(index) {
                $(this).css('opacity', '0').delay(index * 100).animate({ opacity: 1 }, 600);
            });
            
            // Initialize progress bars
            $('.progress-bar').each(function() {
                var width = $(this).css('width');
                $(this).css('width', '0%').animate({ width: width }, 1500);
            });
            
            // Add hover effects
            $('.quick-access-card').hover(function() {
                $(this).find('i').addClass('animated bounce');
            }, function() {
                $(this).find('i').removeClass('animated bounce');
            });
        }
        
        // Loading states
        function setupLoadingStates() {
            $('.btn').click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                
                $btn.html('<span class="loading-spinner mr-2"></span>Đang tải...');
                $btn.prop('disabled', true);
                
                // Re-enable after 2 seconds (simulated)
                setTimeout(function() {
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }, 2000);
            });
        }
        
        // Refresh dashboard function
        function refreshDashboard() {
            showNotification('Đang làm mới dữ liệu...', 'info');
            
            // Simulate data refresh
            setTimeout(function() {
                animateCounters();
                showNotification('Dữ liệu đã được cập nhật!', 'success');
            }, 1500);
        }
        
        // Refresh activity function
        function refreshActivity() {
            var $timeline = $('.activity-timeline');
            $timeline.fadeOut(300, function() {
                // Simulate loading new activities
                setTimeout(function() {
                    $timeline.fadeIn(300);
                    showNotification('Hoạt động đã được cập nhật!', 'success');
                }, 1000);
            });
        }
        
        // Load more activities
        function loadMoreActivities() {
            var $btn = $(event.target);
            $btn.html('<span class="loading-spinner mr-2"></span>Đang tải...');
            
            setTimeout(function() {
                $btn.html('<i class="fas fa-chevron-up mr-1"></i>Thu gọn');
                showNotification('Đã tải thêm hoạt động!', 'info');
            }, 1500);
        }
        
        // Check for updates
        function checkForUpdates() {
            // Simulate checking for updates
            var randomUpdate = Math.random() > 0.7;
            if (randomUpdate) {
                showNotification('Có cập nhật mới!', 'warning');
                $('.badge-counter').addClass('animated pulse');
            }
        }
        
        // Real-time clock
        function updateClock() {
            var now = new Date();
            var time = now.toLocaleTimeString('vi-VN');
            var date = now.toLocaleDateString('vi-VN');
            
            if ($('#current-time').length === 0) {
                $('.page-title').append('<small class="ml-3 text-muted" id="current-time">' + time + ' - ' + date + '</small>');
            } else {
                $('#current-time').text(time + ' - ' + date);
            }
        }
        
        // Notification system
        function showNotification(message, type) {
            var alertClass = 'alert-' + type;
            var iconClass = type === 'success' ? 'check-circle' : 
                          type === 'warning' ? 'exclamation-triangle' : 
                          type === 'error' ? 'times-circle' : 'info-circle';
            
            var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show notification-alert" role="alert">' +
                '<i class="fas fa-' + iconClass + ' mr-2"></i>' + message +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>');
            
            $('body').append(notification);
            
            // Position notification
            notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999,
                minWidth: '300px',
                maxWidth: '400px'
            });
            
            // Auto dismiss after 4 seconds
            setTimeout(function() {
                notification.alert('close');
            }, 4000);
        }
        
        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl + R to refresh
            if (e.ctrlKey && e.keyCode === 82) {
                e.preventDefault();
                refreshDashboard();
            }
            
            // Ctrl + N to create new project
            if (e.ctrlKey && e.keyCode === 78) {
                e.preventDefault();
                window.location.href = '/NLNganh/view/teacher/create_project.php';
            }
        });
        
        // Add smooth scrolling
        $('a[href^="#"]').click(function() {
            var target = $(this.hash);
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
                return false;
            }
        });
        
        // Add search functionality
        $('#searchInput').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            $('.project-row').each(function() {
                var projectName = $(this).find('.project-title').text().toLowerCase();
                if (projectName.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Enhanced table interactions
        $('.table-hover tbody tr').hover(function() {
            $(this).addClass('table-hover-highlight');
        }, function() {
            $(this).removeClass('table-hover-highlight');
        });
        
        // Add copy to clipboard functionality
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('Đã sao chép vào clipboard!', 'success');
            });
        }
        
        // Context menu for project rows
        $('.project-row').contextmenu(function(e) {
            e.preventDefault();
            var projectId = $(this).find('td:first').text();
            
            var contextMenu = $('<div class="context-menu">' +
                '<div class="context-item" onclick="copyToClipboard(\'' + projectId + '\')">Sao chép mã đề tài</div>' +
                '<div class="context-item">Xem chi tiết</div>' +
                '<div class="context-item">Chỉnh sửa</div>' +
                '</div>');
            
            $('body').append(contextMenu);
            contextMenu.css({
                position: 'absolute',
                top: e.pageY + 'px',
                left: e.pageX + 'px',
                zIndex: 9999
            });
            
            // Remove context menu on click outside
            $(document).one('click', function() {
                contextMenu.remove();
            });
        });
    </script>
</body>
</html>