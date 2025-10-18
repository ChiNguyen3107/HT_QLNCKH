<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Helper function để thực hiện prepared statement an toàn
function execPreparedQuery($conn, $query, $types, $params)
{
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Lỗi chuẩn bị truy vấn: " . $conn->error);
        return null;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Lỗi thực thi truy vấn: " . $stmt->error);
        return null;
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

// Lấy thông tin sinh viên
$student_id = $_SESSION['user_id'];
$student_name = '';

// Lấy thông tin chi tiết sinh viên
$studentInfoQuery = "SELECT CONCAT(SV_HOSV, ' ', SV_TENSV) as fullname FROM sinh_vien WHERE SV_MASV = ?";
$studentResult = execPreparedQuery($conn, $studentInfoQuery, "s", [$student_id]);
if ($studentResult && $studentResult->num_rows > 0) {
    $student_name = $studentResult->fetch_assoc()['fullname'];
}

// Lấy học kỳ hiện tại
$current_semester = "";
$semesterQuery = "SELECT HK_MA, HK_TEN FROM hoc_ki WHERE CURDATE() BETWEEN HK_NGAYBD AND HK_NGAYKT";
$semesterResult = $conn->query($semesterQuery);
if ($semesterResult && $semesterResult->num_rows > 0) {
    $semester = $semesterResult->fetch_assoc();
    $current_semester = $semester['HK_TEN'];
} else {
    // Nếu không tìm thấy học kỳ hiện tại, lấy học kỳ gần nhất
    $semesterQuery = "SELECT HK_MA, HK_TEN FROM hoc_ki ORDER BY HK_NGAYBD DESC LIMIT 1";
    $semesterResult = $conn->query($semesterQuery);
    if ($semesterResult && $semesterResult->num_rows > 0) {
        $semester = $semesterResult->fetch_assoc();
        $current_semester = $semester['HK_TEN'];
    }
}

// Lấy các đề tài mà sinh viên tham gia
$project_query = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, cttg.CTTG_VAITRO,
                 CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN
                 FROM de_tai_nghien_cuu dt
                 JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                 LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                 WHERE cttg.SV_MASV = ?";

$projects_result = execPreparedQuery($conn, $project_query, "s", [$student_id]);
$projects = [];

if ($projects_result) {
    while ($row = $projects_result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Lấy filter từ request
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_project = isset($_GET['project']) ? $_GET['project'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Lấy các báo cáo tiến độ của sinh viên với filter
$report_query = "SELECT bc.BC_MABC, bc.BC_TENBC, bc.BC_DUONGDAN, bc.BC_MOTA, 
                bc.BC_NGAYNOP, bc.BC_TRANGTHAI, bc.BC_GHICHU, bc.BC_DIEMSO,
                dt.DT_MADT, dt.DT_TENDT, IFNULL(lbc.LBC_TENLOAI, 'Khác') AS LBC_TENLOAI
                FROM bao_cao bc
                JOIN de_tai_nghien_cuu dt ON bc.DT_MADT = dt.DT_MADT
                LEFT JOIN loai_bao_cao lbc ON bc.LBC_MALOAI = lbc.LBC_MALOAI
                WHERE bc.SV_MASV = ?";

$params = [$student_id];
$types = "s";

// Thêm điều kiện filter
if (!empty($filter_status)) {
    $report_query .= " AND bc.BC_TRANGTHAI = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if (!empty($filter_project)) {
    $report_query .= " AND bc.DT_MADT = ?";
    $params[] = $filter_project;
    $types .= "s";
}
if (!empty($filter_type)) {
    $report_query .= " AND bc.LBC_MALOAI = ?";
    $params[] = $filter_type;
    $types .= "s";
}

$report_query .= " ORDER BY bc.BC_NGAYNOP DESC";

$reports_result = execPreparedQuery($conn, $report_query, $types, $params);
$reports = [];

if ($reports_result) {
    while ($row = $reports_result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Lấy danh sách loại báo cáo cho filter
$reportTypesQuery = "SELECT * FROM loai_bao_cao ORDER BY LBC_TENLOAI";
$reportTypes = $conn->query($reportTypesQuery);

// Lấy báo cáo gần đây nhất
$recent_report = null;
if (count($reports) > 0) {
    $recent_report = $reports[0];
}

// Tính thống kê trạng thái báo cáo
$approved_count = $pending_count = $revision_count = 0;

foreach ($reports as $report) {
    if ($report['BC_TRANGTHAI'] == 'Đã duyệt') $approved_count++;
    elseif ($report['BC_TRANGTHAI'] == 'Chờ duyệt') $pending_count++;
    elseif ($report['BC_TRANGTHAI'] == 'Cần chỉnh sửa') $revision_count++;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo và thống kê | Sinh viên</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4e73df;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --secondary: #858796;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }

        .content-wrapper {
            /* margin-left: rem; Giảm margin-left để thu hẹp khoảng cách */
            padding: 20px 25px;
            transition: all 0.3s;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
            }
        }

        .page-header {
            font-weight: 700;
            color: #4e73df;
        }

        .stats-card {
            border: none;
            border-radius: 0.5rem;
            transition: transform 0.3s;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .empty-state {
            color: #b7b9cc;
        }

        .empty-state i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 15px;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-radius: 0.5rem;
            margin-bottom: 25px;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
        }

        .card-title {
            margin-bottom: 0;
            font-weight: 600;
        }

        .report-status .badge {
            padding: 0.5em 0.75em;
            font-size: 0.85rem;
        }

        .recent-report-title {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .report-feedback {
            background-color: rgba(78, 115, 223, 0.05);
            border-left: 4px solid #4e73df;
            padding: 10px 15px;
            border-radius: 4px;
        }

        .filter-box {
            background-color: #fff;
            padding: 15px;
            border-radius: 0.5rem;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem 0 rgba(58, 59, 69, 0.2);
        }

        .project-progress {
            height: 8px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table th {
            background-color: #f8f9fc;
            font-weight: 600;
        }

        table.dataTable {
            border-collapse: collapse !important;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .score-badge {
            font-size: 1.2rem;
            font-weight: 600;
            padding: 0.5rem;
            border-radius: 0.5rem;
            margin-right: 1rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info {
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem 0 rgba(58, 59, 69, 0.2);
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #4e73df;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        .user-details h5 {
            margin-bottom: 5px;
            font-weight: 600;
        }

        .user-details p {
            margin-bottom: 0;
            color: #858796;
        }

        .semester-badge {
            background-color: #4e73df;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 30px;
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- User Info Section -->
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="user-details">
                    <h5><?php echo htmlspecialchars($student_name); ?> <span class="text-muted">(<?php echo htmlspecialchars($student_id); ?>)</span></h5>
                    <p>Sinh viên<?php if (!empty($current_semester)): ?> <span class="semester-badge"><?php echo htmlspecialchars($current_semester); ?></span><?php endif; ?></p>
                </div>
            </div>
            
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-home mr-1"></i> Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Báo cáo và thống kê</li>
                </ol>
            </nav>

            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="page-header">
                    <i class="fas fa-file-alt mr-2"></i>Báo cáo của tôi
                </h1>
                <?php if (count($projects) > 0): ?>
                    <a href="submit_report.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                        <i class="fas fa-plus fa-sm mr-1"></i> Nộp báo cáo mới
                    </a>
                <?php endif; ?>
            </div>

            <!-- Thông báo -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="filter-box mb-4">
                <form method="get" action="" class="row">
                    <div class="col-md-3 mb-2">
                        <label for="project"><small>Đề tài:</small></label>
                        <select name="project" id="project" class="form-control form-control-sm">
                            <option value="">Tất cả đề tài</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['DT_MADT']; ?>" <?php echo $filter_project == $project['DT_MADT'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['DT_TENDT']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="status"><small>Trạng thái:</small></label>
                        <select name="status" id="status" class="form-control form-control-sm">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Đã duyệt" <?php echo $filter_status == 'Đã duyệt' ? 'selected' : ''; ?>>Đã duyệt</option>
                            <option value="Chờ duyệt" <?php echo $filter_status == 'Chờ duyệt' ? 'selected' : ''; ?>>Chờ duyệt</option>
                            <option value="Cần chỉnh sửa" <?php echo $filter_status == 'Cần chỉnh sửa' ? 'selected' : ''; ?>>Cần chỉnh sửa</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="type"><small>Loại báo cáo:</small></label>
                        <select name="type" id="type" class="form-control form-control-sm">
                            <option value="">Tất cả loại</option>
                            <?php if ($reportTypes && $reportTypes->num_rows > 0): ?>
                                <?php while ($type = $reportTypes->fetch_assoc()): ?>
                                    <option value="<?php echo $type['LBC_MALOAI']; ?>" <?php echo $filter_type == $type['LBC_MALOAI'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['LBC_TENLOAI']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end mb-2">
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-filter mr-1"></i> Lọc
                        </button>
                        <a href="student_reports.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-sync-alt mr-1"></i> Đặt lại
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tổng quan báo cáo -->
            <div class="row">
                <div class="col-sm-4 mb-4">
                    <div class="card stats-card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h5 class="card-title">Tổng số báo cáo</h5>
                            <h3 class="stats-number"><?php echo count($reports); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 mb-4">
                    <div class="card stats-card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="card-title">Báo cáo đã duyệt</h5>
                            <h3 class="stats-number"><?php echo $approved_count; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 mb-4">
                    <div class="card stats-card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="stats-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <h5 class="card-title">Chờ duyệt</h5>
                            <h3 class="stats-number"><?php echo $pending_count; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Báo cáo gần đây -->
                <div class="col-lg-5 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history mr-2"></i>Báo cáo gần đây
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_report): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch ($recent_report['BC_TRANGTHAI']) {
                                        case 'Đã duyệt':
                                            $status_class = 'bg-success';
                                            $status_icon = 'check-circle';
                                            break;
                                        case 'Chờ duyệt':
                                            $status_class = 'bg-warning';
                                            $status_icon = 'clock';
                                            break;
                                        case 'Cần chỉnh sửa':
                                            $status_class = 'bg-danger';
                                            $status_icon = 'exclamation-circle';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                            $status_icon = 'question-circle';
                                    }
                                    ?>
                                    <div class="score-badge <?php echo $status_class; ?> text-white">
                                        <i class="fas fa-<?php echo $status_icon; ?> fa-lg"></i>
                                    </div>
                                    <div>
                                        <h5 class="recent-report-title"><?php echo htmlspecialchars($recent_report['BC_TENBC']); ?></h5>
                                        <p class="text-muted mb-0">
                                            <small>
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($recent_report['BC_NGAYNOP'])); ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="report-status mb-3">
                                    <span class="badge <?php echo $status_class; ?> mr-2">
                                        <?php echo $recent_report['BC_TRANGTHAI']; ?>
                                    </span>
                                    <span class="badge badge-secondary">
                                        <?php echo $recent_report['LBC_TENLOAI']; ?>
                                    </span>
                                </div>

                                <?php if (!empty($recent_report['BC_DIEMSO'])): ?>
                                    <div class="mb-3">
                                        <h6>Điểm số: 
                                            <span class="badge <?php echo $recent_report['BC_DIEMSO'] >= 8 ? 'badge-success' : ($recent_report['BC_DIEMSO'] >= 5 ? 'badge-warning' : 'badge-danger'); ?>">
                                                <?php echo $recent_report['BC_DIEMSO']; ?>/10
                                            </span>
                                        </h6>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($recent_report['BC_GHICHU'])): ?>
                                    <div class="report-feedback mb-3">
                                        <h6><i class="fas fa-comment-dots mr-1"></i> Nhận xét:</h6>
                                        <p class="mb-0"><?php echo htmlspecialchars($recent_report['BC_GHICHU']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="text-center mt-3">
                                    <a href="view_report.php?id=<?php echo $recent_report['BC_MABC']; ?>"
                                        class="btn btn-info btn-sm">
                                        <i class="fas fa-eye mr-1"></i> Xem chi tiết
                                    </a>

                                    <?php if (!empty($recent_report['BC_DUONGDAN'])): ?>
                                        <a href="<?php echo $recent_report['BC_DUONGDAN']; ?>" class="btn btn-primary btn-sm"
                                            target="_blank">
                                            <i class="fas fa-download mr-1"></i> Tải xuống
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state text-center py-4">
                                    <i class="fas fa-file-alt mb-3"></i>
                                    <p>Bạn chưa nộp báo cáo nào</p>
                                    <?php if (count($projects) > 0): ?>
                                        <a href="submit_report.php?id=<?php echo $projects[0]['DT_MADT']; ?>"
                                            class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus mr-1"></i> Nộp báo cáo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Các đề tài đang tham gia -->
                <div class="col-lg-7 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-project-diagram mr-2"></i>Đề tài đang tham gia
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($projects) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mã đề tài</th>
                                                <th>Tên đề tài</th>
                                                <th>Vai trò</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($projects as $project): ?>
                                                <?php
                                                $status_class = '';
                                                switch ($project['DT_TRANGTHAI']) {
                                                    case 'Đã duyệt':
                                                    case 'Đang thực hiện':
                                                        $status_class = 'badge-primary';
                                                        break;
                                                    case 'Đã hoàn thành':
                                                        $status_class = 'badge-success';
                                                        break;
                                                    case 'Chờ duyệt':
                                                        $status_class = 'badge-warning';
                                                        break;
                                                    case 'Tạm dừng':
                                                        $status_class = 'badge-secondary';
                                                        break;
                                                    default:
                                                        $status_class = 'badge-info';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo $project['DT_MADT']; ?></td>
                                                    <td><?php echo htmlspecialchars($project['DT_TENDT']); ?></td>
                                                    <td><?php echo htmlspecialchars($project['CTTG_VAITRO'] ?: 'Thành viên'); ?></td>
                                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $project['DT_TRANGTHAI']; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state text-center py-4">
                                    <i class="fas fa-users mb-3"></i>
                                    <p>Bạn chưa tham gia đề tài nào</p>
                                    <a href="available_projects.php" class="btn btn-primary btn-sm mt-2">
                                        <i class="fas fa-search mr-1"></i> Tìm đề tài
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách báo cáo -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list mr-2"></i>Tất cả báo cáo
                    </h5>
                    <?php if (count($projects) > 0): ?>
                        <a href="submit_report.php" class="btn btn-primary btn-sm d-md-none">
                            <i class="fas fa-plus mr-1"></i> Nộp báo cáo mới
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (count($reports) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="reportsTable">
                                <thead>
                                    <tr>
                                        <th>Mã BC</th>
                                        <th>Tên báo cáo</th>
                                        <th>Đề tài</th>
                                        <th>Loại báo cáo</th>
                                        <th>Ngày nộp</th>
                                        <th>Trạng thái</th>
                                        <th>Điểm</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <?php
                                        $status_class = '';
                                        switch ($report['BC_TRANGTHAI']) {
                                            case 'Đã duyệt':
                                                $status_class = 'badge-success';
                                                break;
                                            case 'Chờ duyệt':
                                                $status_class = 'badge-warning';
                                                break;
                                            case 'Cần chỉnh sửa':
                                                $status_class = 'badge-danger';
                                                break;
                                            default:
                                                $status_class = 'badge-secondary';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $report['BC_MABC']; ?></td>
                                            <td><?php echo htmlspecialchars($report['BC_TENBC']); ?></td>
                                            <td><?php echo htmlspecialchars($report['DT_TENDT']); ?></td>
                                            <td><?php echo htmlspecialchars($report['LBC_TENLOAI']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($report['BC_NGAYNOP'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $report['BC_TRANGTHAI']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($report['BC_DIEMSO']): ?>
                                                    <span class="badge <?php echo $report['BC_DIEMSO'] >= 8 ? 'badge-success' : ($report['BC_DIEMSO'] >= 5 ? 'badge-warning' : 'badge-danger'); ?>">
                                                        <?php echo $report['BC_DIEMSO']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span>-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group-sm">
                                                    <a href="view_report.php?id=<?php echo $report['BC_MABC']; ?>"
                                                        class="btn btn-sm btn-info mb-1" data-toggle="tooltip"
                                                        title="Xem chi tiết báo cáo">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($report['BC_TRANGTHAI'] !== 'Đã duyệt'): ?>
                                                        <a href="edit_report.php?id=<?php echo $report['BC_MABC']; ?>"
                                                            class="btn btn-sm btn-warning mb-1" data-toggle="tooltip"
                                                            title="Chỉnh sửa báo cáo">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($report['BC_DUONGDAN'])): ?>
                                                        <a href="<?php echo $report['BC_DUONGDAN']; ?>"
                                                            class="btn btn-sm btn-primary mb-1" target="_blank"
                                                            data-toggle="tooltip" title="Tải xuống báo cáo">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state text-center py-5">
                            <i class="fas fa-clipboard-list mb-3"></i>
                            <p>Bạn chưa nộp báo cáo nào</p>
                            <?php if (count($projects) > 0): ?>
                                <a href="submit_report.php?id=<?php echo $projects[0]['DT_MADT']; ?>"
                                    class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus mr-1"></i> Nộp báo cáo ngay
                                </a>
                            <?php else: ?>
                                <p class="text-muted mt-2">Bạn cần tham gia một đề tài trước khi nộp báo cáo</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#reportsTable').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.22/i18n/Vietnamese.json"
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                ordering: true,
                responsive: true
            });
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>

</html>