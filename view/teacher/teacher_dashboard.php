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
        .card-counter {
            box-shadow: 2px 2px 10px #dadada;
            padding: 20px 10px;
            background-color: #fff;
            height: 100px;
            border-radius: 5px;
            transition: all 0.3s ease-in-out;
        }
        
        .card-counter:hover {
            transform: scale(1.02);
        }
        
        .card-counter i {
            font-size: 5em;
            opacity: 0.2;
        }
        
        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 20px;
            font-size: 28px;
            display: block;
        }
        
        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 65px;
            text-transform: capitalize;
            opacity: 0.8;
            display: block;
            font-size: 14px;
        }
        
        .card-counter.primary {
            background-color: #4e73df;
            color: #FFF;
        }
        
        .card-counter.danger {
            background-color: #e74a3b;
            color: #FFF;
        }
        
        .card-counter.success {
            background-color: #1cc88a;
            color: #FFF;
        }
        
        .card-counter.info {
            background-color: #36b9cc;
            color: #FFF;
        }
        
        .quick-access-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .quick-access-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .project-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #f6c23e;
            color: white;
        }
        
        .status-progress {
            background-color: #4e73df;
            color: white;
        }
        
        .status-completed {
            background-color: #1cc88a;
            color: white;
        }
        
        .status-rejected {
            background-color: #e74a3b;
            color: white;
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .activity-timeline:before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        
        .activity-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .activity-item:before {
            content: '';
            position: absolute;
            left: -22px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #4e73df;
        }
        
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
                        <h1 class="h3 mb-0 text-gray-800">Bảng điều khiển</h1>
                        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Tạo báo cáo
                        </a>
                    </div>

                    <!-- Content Row - Statistics Cards -->
                    <div class="row">
                        <!-- Tổng số đề tài -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter primary">
                                <i class="fa fa-folder-open"></i>
                                <span class="count-numbers"><?= $total_projects ?></span>
                                <span class="count-name">Tổng số đề tài</span>
                            </div>
                        </div>

                        <!-- Đề tài đang tiến hành -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter info">
                                <i class="fa fa-spinner"></i>
                                <span class="count-numbers"><?= $in_progress ?></span>
                                <span class="count-name">Đang tiến hành</span>
                            </div>
                        </div>

                        <!-- Đề tài hoàn thành -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter success">
                                <i class="fa fa-check-circle"></i>
                                <span class="count-numbers"><?= $completed ?></span>
                                <span class="count-name">Đã hoàn thành</span>
                            </div>
                        </div>

                        <!-- Đề tài chờ phê duyệt -->
                        <div class="col-md-3 mb-4">
                            <div class="card card-counter danger">
                                <i class="fa fa-clock"></i>
                                <span class="count-numbers"><?= $pending ?></span>
                                <span class="count-name">Chờ phê duyệt</span>
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
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Đề tài gần đây</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Mã đề tài</th>
                                                    <th>Tên đề tài</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày tạo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($recent_projects && $recent_projects->num_rows > 0): ?>
                                                    <?php while ($project = $recent_projects->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?= $project['DT_MADT'] ?></td>
                                                            <td><?= $project['DT_TENDT'] ?></td>
                                                            <td>
                                                                <?php 
                                                                $status_class = '';
                                                                switch($project['DT_TRANGTHAI']) {
                                                                    case 'Chờ phê duyệt':
                                                                        $status_class = 'status-pending';
                                                                        break;
                                                                    case 'Đang tiến hành':
                                                                        $status_class = 'status-progress';
                                                                        break;
                                                                    case 'Đã hoàn thành':
                                                                        $status_class = 'status-completed';
                                                                        break;
                                                                    case 'Đã từ chối':
                                                                        $status_class = 'status-rejected';
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="project-status <?= $status_class ?>"><?= $project['DT_TRANGTHAI'] ?></span>
                                                            </td>
                                                            <td><?= isset($project['DT_NGAYTAO']) ? date('d/m/Y', strtotime($project['DT_NGAYTAO'])) : 'N/A' ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Không có đề tài nào</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="/NLNganh/view/teacher/manage_projects.php" class="btn btn-sm btn-primary">Xem tất cả đề tài</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Access and Timeline -->
                        <div class="col-lg-6 mb-4">
                            <!-- Quick Access -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Thêm đề tài mới</div>
                                                    <div class="small text-gray-600">Tạo đề tài nghiên cứu mới</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-plus-circle fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/create_project.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Quản lý hồ sơ</div>
                                                    <div class="small text-gray-600">Cập nhật thông tin cá nhân</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-edit fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/manage_profile.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Báo cáo</div>
                                                    <div class="small text-gray-600">Xem báo cáo thống kê</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/reports.php" class="stretched-link"></a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow h-100 py-2 quick-access-card">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Sinh viên tham gia</div>
                                                    <div class="small text-gray-600">Quản lý sinh viên: <?= $member_count ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="/NLNganh/view/teacher/manage_students.php" class="stretched-link"></a>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity Timeline -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Hoạt động gần đây</h6>
                                </div>
                                <div class="card-body">
                                    <div class="activity-timeline">
                                        <?php
                                        // Mảng hoạt động demo - trong thực tế nên lấy từ cơ sở dữ liệu
                                        $activities = [
                                            ['time' => '2 giờ trước', 'text' => 'Đề tài "Nghiên cứu ứng dụng AI" được cập nhật trạng thái.'],
                                            ['time' => '1 ngày trước', 'text' => 'Sinh viên Nguyễn Văn A đã nộp báo cáo đề tài.'],
                                            ['time' => '3 ngày trước', 'text' => 'Bạn đã tạo đề tài nghiên cứu mới.'],
                                            ['time' => '1 tuần trước', 'text' => 'Đề tài "Phát triển ứng dụng web" đã được duyệt.']
                                        ];
                                        
                                        foreach ($activities as $activity) {
                                            echo '<div class="activity-item">';
                                            echo '<p class="mb-1 small text-gray-600">' . $activity['time'] . '</p>';
                                            echo '<p class="mb-0">' . $activity['text'] . '</p>';
                                            echo '</div>';
                                        }
                                        ?>
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
                    },
                    legend: {
                        display: false
                    },
                    cutoutPercentage: 80,
                },
            });
            
            // Biểu đồ tổng quan đề tài
            var ctx2 = document.getElementById("projectStatusChart");
            var myLineChart = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6"],
                    datasets: [{
                        label: "Đề tài",
                        lineTension: 0.3,
                        backgroundColor: "rgba(78, 115, 223, 0.05)",
                        borderColor: "rgba(78, 115, 223, 1)",
                        pointRadius: 3,
                        pointBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointBorderColor: "rgba(78, 115, 223, 1)",
                        pointHoverRadius: 3,
                        pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                        pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
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
                                maxTicksLimit: 7
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                // Include a dollar sign in the ticks
                                callback: function(value, index, values) {
                                    return value;
                                }
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
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
                        callbacks: {
                            label: function(tooltipItem, chart) {
                                var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                                return datasetLabel + ': ' + tooltipItem.yLabel;
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>