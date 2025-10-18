<?php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';
include '../../include/database.php';
include '../../include/models/BaseModel.php';
include '../../include/models/ProjectModel.php';
include '../../include/models/StudentModel.php';

// Khởi tạo các model
$projectModel = new ProjectModel();
$studentModel = new StudentModel();

// Lấy thông tin sinh viên
$student_id = $_SESSION['user_id'];
$student = $studentModel->getStudent($student_id);

// Lấy thông tin đề tài đang tham gia
$projects = $projectModel->getStudentProjects($student_id);

// Đếm số đề tài theo trạng thái
$project_stats = [
    'total' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'waiting' => 0
];

// Lấy gần nhất deadline (nếu có)
$nearest_deadline = null;
$nearest_project = null;

// Tính toán thống kê
if ($projects && is_array($projects)) {
    $project_stats['total'] = count($projects);
    
    foreach ($projects as $project) {
        if ($project['DT_TRANGTHAI'] == 'Đang thực hiện') {
            $project_stats['in_progress']++;
        } elseif ($project['DT_TRANGTHAI'] == 'Đã hoàn thành') {
            $project_stats['completed']++;
        } elseif ($project['DT_TRANGTHAI'] == 'Chờ duyệt') {
            $project_stats['waiting']++;
        }
        
        // Tìm deadline gần nhất
        if (!empty($project['HD_NGAYKT'])) {
            $deadline = strtotime($project['HD_NGAYKT']);
            if (($nearest_deadline === null || $deadline < $nearest_deadline) && $deadline > time()) {
                $nearest_deadline = $deadline;
                $nearest_project = $project;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển sinh viên</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/student/dashboard.css" rel="stylesheet">
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="container-fluid content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-home"></i> Bảng điều khiển
                </li>
            </ol>
        </nav>

        <h1 class="page-title">
            <i class="fas fa-tachometer-alt mr-2"></i>Bảng điều khiển sinh viên
        </h1>

        <!-- Thông tin chào mừng -->
        <div class="alert alert-info mb-4">
            <h5><i class="fas fa-user-circle mr-2"></i>Xin chào,
                <?php echo $student['SV_HOSV'] . ' ' . $student['SV_TENSV']; ?>!</h5>
            <p class="mb-0">Chào mừng bạn đến với hệ thống quản lý nghiên cứu khoa học.</p>
        </div>

        <!-- Phần tổng quan -->
        <div class="row overview-row mb-4">
            <!-- Thông báo -->
            <div class="col-md-8 mb-4">
                <div class="overview-section">
                    <h5 class="mb-3"><i class="fas fa-bullhorn mr-2"></i>Thông báo</h5>

                    <?php
                    // Giả lập thông báo - trong thực tế sẽ lấy từ CSDL
                    $announcements = [
                        [
                            'title' => 'Thông báo đăng ký đề tài học kỳ mới',
                            'text' => 'Thời gian đăng ký đề tài nghiên cứu học kỳ 2 năm học 2024-2025 đã được mở. Hạn chót đăng ký là 30/05/2025.',
                            'date' => '2025-04-01'
                        ],
                        [
                            'title' => 'Hội nghị KHCN sinh viên sắp diễn ra',
                            'text' => 'Hội nghị khoa học công nghệ sinh viên năm 2025 sẽ diễn ra từ ngày 15/06/2025 đến 20/06/2025.',
                            'date' => '2025-03-28'
                        ]
                    ];

                    foreach ($announcements as $announcement) {
                        echo '<div class="announcement-card" style="opacity: 0">';
                        echo '<div class="card-body">';
                        echo '<h6 class="announcement-title">' . $announcement['title'] . ' <small class="text-muted">(' . date('d/m/Y', strtotime($announcement['date'])) . ')</small></h6>';
                        echo '<p class="announcement-text">' . $announcement['text'] . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Đếm ngược thời gian -->
            <div class="col-md-4 mb-4">
                <div class="countdown-card">
                    <div class="card-body">
                        <h5 class="countdown-title"><i class="fas fa-clock mr-2"></i>Thời gian đăng ký đề tài</h5>
                        <div id="countdown" class="countdown-timer" data-target="2025-05-30">
                            <div class="countdown-item">
                                <span id="days" class="countdown-value">00</span>
                                <span class="countdown-label">Ngày</span>
                            </div>
                            <div class="countdown-item">
                                <span id="hours" class="countdown-value">00</span>
                                <span class="countdown-label">Giờ</span>
                            </div>
                            <div class="countdown-item">
                                <span id="minutes" class="countdown-value">00</span>
                                <span class="countdown-label">Phút</span>
                            </div>
                            <div class="countdown-item">
                                <span id="seconds" class="countdown-value">00</span>
                                <span class="countdown-label">Giây</span>
                            </div>
                        </div>
                        <a href="register_project_form.php" class="btn btn-primary">
                            <i class="fas fa-user-plus mr-1"></i> Đăng ký đề tài
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Các thẻ thống kê -->
        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card stats-card" style="opacity: 0">
                    <div class="card-body">
                        <div class="icon-circle info-icon">
                            <i class="fas fa-user card-icon"></i>
                        </div>
                        <h5 class="card-title">Thông tin cá nhân</h5>
                        <p class="card-text">
                            <span class="font-weight-bold">MSSV:</span> <?php echo $student['SV_MASV']; ?><br>
                            <span class="font-weight-bold">Lớp:</span> <?php echo $student['LOP_TEN']; ?><br>
                            <span class="font-weight-bold">Khoa:</span> <?php echo $student['DV_TENDV']; ?>
                        </p>
                        <a href="../student/manage_profile.php" class="btn btn-primary btn-block">
                            <i class="fas fa-user-edit mr-1"></i> Quản lý thông tin
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card stats-card" style="opacity: 0">
                    <div class="card-body">
                        <div class="icon-circle project-icon">
                            <i class="fas fa-file-alt card-icon"></i>
                        </div>
                        <h5 class="card-title">Đề tài nghiên cứu</h5>
                        <p class="card-text">
                            <span class="font-weight-bold">Tổng số đề tài:</span>
                            <?php echo $project_stats['total']; ?><br>
                            <span class="font-weight-bold">Đang thực hiện:</span>
                            <?php echo $project_stats['in_progress']; ?><br>
                            <span class="font-weight-bold">Hoàn thành:</span> <?php echo $project_stats['completed']; ?>
                        </p>
                        <a href="../student/student_manage_projects.php" class="btn btn-success btn-block">
                            <i class="fas fa-folder mr-1"></i> Quản lý đề tài
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card stats-card" style="opacity: 0">
                    <div class="card-body">
                        <div class="icon-circle report-icon">
                            <i class="fas fa-chart-line card-icon"></i>
                        </div>
                        <h5 class="card-title">Thống kê & Báo cáo</h5>
                        <?php if ($nearest_deadline && $nearest_project): ?>
                            <p class="card-text">
                                <span class="font-weight-bold">Deadline gần nhất:</span><br>
                                <?php echo date('d/m/Y', $nearest_deadline); ?><br>
                                <span class="font-weight-bold">Đề tài:</span> <?php echo $nearest_project['DT_TENDT']; ?>
                            </p>
                        <?php else: ?>
                            <p class="card-text">
                                Chưa có deadline đề tài sắp tới.<br>
                                Xem báo cáo và thống kê chi tiết của các đề tài.
                            </p>
                        <?php endif; ?>
                        <a href="../student/student_reports.php" class="btn btn-warning text-white btn-block">
                            <i class="fas fa-chart-pie mr-1"></i> Xem báo cáo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách đề tài -->
        <div class="card projects-card">
            <div class="card-header">
                <h5><i class="fas fa-list mr-2"></i>Danh sách đề tài tham gia</h5>
                <a href="../student/student_manage_projects.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-search mr-1"></i> Xem tất cả
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 10%">Mã đề tài</th>
                                <th style="width: 25%">Tên đề tài</th>
                                <th style="width: 20%">Mô tả</th>
                                <th style="width: 15%">Giảng viên HD</th>
                                <th style="width: 15%">Deadline</th>
                                <th style="width: 10%">Trạng thái</th>
                                <th style="width: 15%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($projects && count($projects) > 0) {
                                // Hiển thị danh sách dự án
                                foreach ($projects as $index => $project) {
                                    // Giới hạn hiển thị 3 đề tài đầu tiên
                                    if ($index >= 3) break;
                                    
                                    // Xác định badge class dựa trên trạng thái
                                    $status_class = '';
                                    switch ($project['DT_TRANGTHAI']) {
                                        case 'Chờ duyệt':
                                            $status_class = 'badge-warning';
                                            break;
                                        case 'Đang thực hiện':
                                            $status_class = 'badge-info';
                                            break;
                                        case 'Đã hoàn thành':
                                            $status_class = 'badge-success';
                                            break;
                                        case 'Đã hủy':
                                            $status_class = 'badge-danger';
                                            break;
                                        default:
                                            $status_class = 'badge-secondary';
                                    }

                                    // Chuẩn bị mô tả ngắn gọn nếu quá dài
                                    $full_desc = $project['DT_MOTA'];
                                    $short_desc = (strlen($full_desc) > 60) ? substr($full_desc, 0, 60) . '...' : $full_desc;
                                    
                                    // Định dạng deadline
                                    $deadline = !empty($project['HD_NGAYKT']) ? date('d/m/Y', strtotime($project['HD_NGAYKT'])) : 'Chưa xác định';
                                    
                                    // Tính ngày còn lại đến deadline
                                    $days_left = '';
                                    if (!empty($project['HD_NGAYKT'])) {
                                        $deadline_date = new DateTime($project['HD_NGAYKT']);
                                        $today = new DateTime();
                                        $interval = $today->diff($deadline_date);
                                        
                                        if ($deadline_date < $today) {
                                            $days_left = '<span class="text-danger">(Quá hạn)</span>';
                                        } else {
                                            $days_left = '<span class="text-muted">(' . $interval->days . ' ngày)</span>';
                                        }
                                    }

                                    echo "<tr>";
                                    echo "<td>{$project['DT_MADT']}</td>";
                                    echo "<td><strong>{$project['DT_TENDT']}</strong></td>";

                                    // Cột mô tả với nút "Xem thêm" nếu cần
                                    if (strlen($full_desc) > 60) {
                                        echo "<td class='truncate-text' data-full-text='" . htmlspecialchars($full_desc) . "' data-short-text='" . htmlspecialchars($short_desc) . "'>" . htmlspecialchars($short_desc) . " <a href='#' class='show-more'>Xem thêm</a></td>";
                                    } else {
                                        echo "<td>" . htmlspecialchars($full_desc) . "</td>";
                                    }

                                    echo "<td>{$project['GV_HOGV']} {$project['GV_TENGV']}</td>";
                                    echo "<td>{$deadline} {$days_left}</td>";
                                    echo "<td><span class='badge {$status_class}'>{$project['DT_TRANGTHAI']}</span></td>";
                                    echo "<td>
                                            <div class='btn-group btn-group-sm'>
                                                <a href='view_project.php?id={$project['DT_MADT']}' class='btn btn-info' title='Xem chi tiết'>
                                                    <i class='fas fa-eye'></i>
                                                </a>
                                                <a href='update_project_progress.php?id={$project['DT_MADT']}' class='btn btn-success' title='Cập nhật tiến độ'>
                                                    <i class='fas fa-tasks'></i>
                                                </a>
                                                <a href='submit_report.php?id={$project['DT_MADT']}' class='btn btn-warning' title='Quản lý báo cáo'>
                                                    <i class='fas fa-file-alt'></i>
                                                </a>
                                            </div>
                                          </td>";
                                    echo "</tr>";
                                }
                                
                                // Nếu không có đề tài nào
                                if (count($projects) == 0) {
                                    echo "<tr><td colspan='7' class='text-center'>Bạn chưa tham gia đề tài nào</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>Bạn chưa tham gia đề tài nào</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php if ($projects && count($projects) > 3): ?>
                        <div class="text-center mt-3">
                            <a href="../student/student_manage_projects.php" class="btn btn-outline-primary">
                                <i class="fas fa-list mr-1"></i> Xem tất cả đề tài (<?php echo count($projects); ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($project_stats['total'] > 0): ?>
            <!-- Biểu đồ tiến độ -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie mr-2"></i>Tiến độ đề tài</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="projectProgressChart"
                                    data-completed="<?php echo $project_stats['completed']; ?>"
                                    data-in-progress="<?php echo $project_stats['in_progress']; ?>"
                                    data-not-started="<?php echo $project_stats['waiting']; ?>">
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-tasks mr-2"></i>Công việc cần làm</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php if ($nearest_deadline && $nearest_project): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-clock text-warning mr-2"></i> Hoàn thành đề tài:
                                            <?php echo $nearest_project['DT_TENDT']; ?></span>
                                        <span class="badge badge-warning"><?php echo date('d/m/Y', $nearest_deadline); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($project_stats['in_progress'] > 0): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-file-alt text-info mr-2"></i> Cập nhật tiến độ đề tài đang thực
                                            hiện</span>
                                        <span class="badge badge-info"><?php echo $project_stats['in_progress']; ?> đề
                                            tài</span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($project_stats['waiting'] > 0): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-hourglass-half text-primary mr-2"></i> Chờ duyệt đề tài</span>
                                        <span class="badge badge-primary"><?php echo $project_stats['waiting']; ?> đề tài</span>
                                    </li>
                                <?php endif; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-book text-success mr-2"></i> Cập nhật thông tin cá nhân</span>
                                    <a href="../student/manage_profile.php" class="btn btn-sm btn-outline-success">Cập
                                        nhật</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/student/dashboard.js"></script>
</body>

</html>