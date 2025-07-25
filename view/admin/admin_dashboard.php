<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\admin_dashboard.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../include/session.php';

// Kiểm tra quyền admin
checkAdminRole();

// Kết nối database
include '../../include/connect.php';

// Truy vấn dữ liệu thống kê
$total_students = $conn->query("SELECT COUNT(*) as count FROM sinh_vien")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM giang_vien")->fetch_assoc()['count'];
$total_projects = $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu")->fetch_assoc()['count'];
$completed_projects = $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hoàn thành'")->fetch_assoc()['count'];
$pending_projects = $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Chờ duyệt'")->fetch_assoc()['count'];

// Thống kê trạng thái đề tài
$project_stats = [
    'Chờ duyệt' => $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Chờ duyệt'")->fetch_assoc()['count'],
    'Đang thực hiện' => $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đang thực hiện'")->fetch_assoc()['count'],
    'Đã hoàn thành' => $completed_projects,
    'Tạm dừng' => $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Tạm dừng'")->fetch_assoc()['count'],
    'Đã hủy' => $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hủy'")->fetch_assoc()['count'],
];

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển quản trị</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/admin/admin_dashboard.css" rel="stylesheet">
    
    <style>
        /* Hiệu ứng animation mở đầu */
        .stat-card-link {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        
        .quick-links .list-group-item {
            opacity: 0;
            transform: translateX(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
    </style>
</head>

<body class="bg-light">
    <?php include '../../include/admin_sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="container-fluid" style="margin-left: 220px; padding: 20px;">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bảng điều khiển</li>
            </ol>
        </nav>

        <h1 class="mb-4"><i class="fas fa-tachometer-alt"></i> Bảng điều khiển</h1>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <a href="/NLNganh/view/admin/user_manage/manage_users.php?type=students" class="stat-card-link">
                    <div class="card border-left-primary dashboard-card h-100 py-2 stat-card bg-primary">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Sinh viên</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo $total_students; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-graduate card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <a href="/NLNganh/view/admin/user_manage/manage_users.php?type=teachers" class="stat-card-link">
                    <div class="card border-left-success dashboard-card h-100 py-2 stat-card bg-success">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Giảng viên</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo $total_teachers; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chalkboard-teacher card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <a href="/NLNganh/view/admin/manage_projects/manage_projects.php" class="stat-card-link">
                    <div class="card border-left-info dashboard-card h-100 py-2 stat-card bg-info">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Tổng số đề tài</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo $total_projects; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <a href="/NLNganh/view/admin/manage_projects/manage_projects.php?status=Chờ+duyệt" class="stat-card-link">
                    <div class="card border-left-warning dashboard-card h-100 py-2 stat-card bg-warning">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Đề tài chờ duyệt</div>
                                    <div class="h5 mb-0 font-weight-bold"><?php echo $pending_projects; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Biểu đồ và Chức năng chính -->
        <div class="row mb-4">
            <!-- Biểu đồ trạng thái đề tài -->
            <div class="col-lg-8 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-pie mr-1"></i> Thống kê trạng thái đề tài</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="projectStatusChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-link mr-1"></i> Truy cập nhanh</h6>
                    </div>
                    <div class="card-body quick-links">
                        <div class="list-group">
                            <a href="/NLNganh/view/admin/user_manage/manage_users.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-users mr-2"></i> Quản lý người dùng
                            </a>
                            <a href="/NLNganh/view/admin/manage_projects/manage_projects.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-project-diagram mr-2"></i> Quản lý đề tài
                            </a>
                            <a href="/NLNganh/view/admin/reports.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-bar mr-2"></i> Báo cáo và thống kê
                            </a>
                            <a href="/NLNganh/view/admin/settings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-cogs mr-2"></i> Cài đặt hệ thống
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đề tài mới nhất -->
        <div class="card dashboard-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-clipboard-list mr-1"></i> Đề tài mới nhất</h6>
                <a href="/NLNganh/view/admin/manage_projects/manage_projects.php" class="btn btn-sm btn-primary">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Mã đề tài</th>
                                <th>Tên đề tài</th>
                                <th>Giảng viên hướng dẫn</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, 
                                   CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN 
                                   FROM de_tai_nghien_cuu dt
                                   LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                                   ORDER BY dt.DT_MADT DESC LIMIT 5";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Xác định class cho badge trạng thái
                                    $status_class = '';
                                    switch ($row['DT_TRANGTHAI']) {
                                        case 'Chờ duyệt':
                                            $status_class = 'badge-warning';
                                            break;
                                        case 'Đang thực hiện':
                                            $status_class = 'badge-primary';
                                            break;
                                        case 'Đã hoàn thành':
                                            $status_class = 'badge-success';
                                            break;
                                        case 'Tạm dừng':
                                            $status_class = 'badge-info';
                                            break;
                                        case 'Đã hủy':
                                            $status_class = 'badge-danger';
                                            break;
                                        default:
                                            $status_class = 'badge-secondary';
                                    }

                                    echo "<tr>
                                            <td>{$row['DT_MADT']}</td>
                                            <td><a href='manage_projects/edit_project.php?id={$row['DT_MADT']}'>{$row['DT_TENDT']}</a></td>
                                            <td>{$row['GV_HOTEN']}</td>
                                            <td><span class='badge {$status_class}'>{$row['DT_TRANGTHAI']}</span></td>
                                            <td>N/A</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Không có đề tài nào</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sinh viên mới đăng ký -->
        <div class="card dashboard-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-plus mr-1"></i> Sinh viên mới đăng ký</h6>
                <a href="/NLNganh/view/admin/user_manage/manage_users.php" class="btn btn-sm btn-primary">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Mã SV</th>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Lớp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT sv.SV_MASV, sv.SV_HOSV, sv.SV_TENSV, sv.SV_EMAIL, l.LOP_TEN 
                                   FROM sinh_vien sv
                                   LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
                                   ORDER BY sv.SV_MASV DESC LIMIT 5";
                            $result = $conn->query($sql);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['SV_MASV']}</td>
                                            <td>{$row['SV_HOSV']} {$row['SV_TENSV']}</td>
                                            <td>{$row['SV_EMAIL']}</td>
                                            <td>{$row['LOP_TEN']}</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Không có sinh viên nào</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/NLNganh/assets/js/admin/admin_dashboard.js"></script>
    
    <script>
        // Chuyển dữ liệu PHP sang JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Khởi tạo biểu đồ với dữ liệu từ PHP
            initDashboardCharts(<?php echo json_encode($project_stats); ?>);
        });
    </script>
</body>

</html>