<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\student_reports.php
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
        echo '<div class="alert alert-danger">Lỗi chuẩn bị truy vấn: ' . htmlspecialchars($conn->error) . '</div>';
        return null;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        echo '<div class="alert alert-danger">Lỗi thực thi truy vấn: ' . htmlspecialchars($stmt->error) . '</div>';
        return null;
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

// Lấy thông tin sinh viên
$student_id = $_SESSION['user_id'];

// Lấy các đề tài mà sinh viên tham gia
$project_query = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, cttg.CTTG_VAITRO,
                 CONCAT(IFNULL(gv.GV_HOGV, ''), ' ', IFNULL(gv.GV_TENGV, '')) AS GV_HOTEN
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

// Lấy các báo cáo tiến độ của sinh viên
$report_query = "SELECT bc.BC_MABC, bc.BC_TENBC, bc.BC_DUONGDAN, bc.BC_MOTA, 
                bc.BC_NGAYNOP, bc.BC_TRANGTHAI, bc.BC_GHICHU, bc.BC_DIEMSO,
                dt.DT_MADT, dt.DT_TENDT, IFNULL(lbc.LBC_TENLOAI, 'Khác') AS LBC_TENLOAI
                FROM bao_cao bc
                JOIN de_tai_nghien_cuu dt ON bc.DT_MADT = dt.DT_MADT
                LEFT JOIN loai_bao_cao lbc ON bc.LBC_MALOAI = lbc.LBC_MALOAI
                WHERE bc.SV_MASV = ?
                ORDER BY bc.BC_NGAYNOP DESC";

$reports_result = execPreparedQuery($conn, $report_query, "s", [$student_id]);
$reports = [];

if ($reports_result) {
    while ($row = $reports_result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Lấy thống kê báo cáo theo từng đề tài
$stats_query = "SELECT dt.DT_MADT, dt.DT_TENDT,
               COUNT(bc.BC_MABC) AS total_reports,
               SUM(CASE WHEN bc.BC_TRANGTHAI = 'Đã duyệt' THEN 1 ELSE 0 END) AS approved_reports,
               SUM(CASE WHEN bc.BC_TRANGTHAI = 'Chờ duyệt' THEN 1 ELSE 0 END) AS pending_reports,
               SUM(CASE WHEN bc.BC_TRANGTHAI = 'Cần chỉnh sửa' THEN 1 ELSE 0 END) AS revision_reports,
               AVG(CASE WHEN bc.BC_DIEMSO IS NOT NULL THEN bc.BC_DIEMSO ELSE NULL END) AS avg_score
               FROM de_tai_nghien_cuu dt
               JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
               LEFT JOIN bao_cao bc ON dt.DT_MADT = bc.DT_MADT AND bc.SV_MASV = cttg.SV_MASV
               WHERE cttg.SV_MASV = ?
               GROUP BY dt.DT_MADT";

$stats_result = execPreparedQuery($conn, $stats_query, "s", [$student_id]);
$stats = [];

if ($stats_result) {
    while ($row = $stats_result->fetch_assoc()) {
        $stats[] = $row;
    }
}

// Lấy báo cáo gần đây nhất
$recent_report = null;
if (count($reports) > 0) {
    $recent_report = $reports[0];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo và thống kê | Sinh viên</title>

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/student/reports.css" rel="stylesheet">
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-home mr-1"></i> Trang
                            chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Báo cáo và thống kê</li>
                </ol>
            </nav>

            <h1 class="page-header mb-4">
                <i class="fas fa-chart-line mr-2"></i>Báo cáo và thống kê
            </h1>

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

            <!-- Tổng quan báo cáo -->
            <div class="row">
                <!-- Thống kê tổng quan -->
                <div class="col-lg-8">
                    <!-- Thẻ thống kê -->
                    <div class="row">
                        <div class="col-sm-4 mb-4">
                            <div class="card stats-card bg-primary text-white">
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
                            <div class="card stats-card bg-success text-white">
                                <div class="card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <h5 class="card-title">Báo cáo đã duyệt</h5>
                                    <h3 class="stats-number">
                                        <?php
                                        $approved = array_filter($reports, function ($r) {
                                            return $r['BC_TRANGTHAI'] == 'Đã duyệt';
                                        });
                                        echo count($approved);
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4 mb-4">
                            <div class="card stats-card bg-warning text-white">
                                <div class="card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <h5 class="card-title">Đang chờ duyệt</h5>
                                    <h3 class="stats-number">
                                        <?php
                                        $pending = array_filter($reports, function ($r) {
                                            return $r['BC_TRANGTHAI'] == 'Chờ duyệt';
                                        });
                                        echo count($pending);
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ tiến độ -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie mr-2"></i>Thống kê báo cáo theo đề tài
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($stats) > 0): ?>
                                <canvas id="reportsByProjectChart" height="250"></canvas>
                            <?php else: ?>
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-chart-bar mb-3"></i>
                                    <p>Chưa có dữ liệu thống kê báo cáo</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Báo cáo gần đây -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history mr-2"></i>Báo cáo gần đây
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_report): ?>
                                <h5 class="recent-report-title"><?php echo $recent_report['BC_TENBC']; ?></h5>
                                <p class="text-muted">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Nộp lúc: <?php echo date('d/m/Y H:i', strtotime($recent_report['BC_NGAYNOP'])); ?>
                                </p>

                                <div class="report-status mb-3">
                                    <?php
                                    $status_class = '';
                                    switch ($recent_report['BC_TRANGTHAI']) {
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
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo $recent_report['BC_TRANGTHAI']; ?>
                                    </span>
                                </div>

                                <?php if (!empty($recent_report['BC_GHICHU'])): ?>
                                    <div class="report-feedback">
                                        <h6><i class="fas fa-comment-dots mr-1"></i> Nhận xét:</h6>
                                        <p><?php echo $recent_report['BC_GHICHU']; ?></p>
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

                    <!-- Thông tin điểm số -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-star mr-2"></i>Điểm số báo cáo
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($reports) > 0): ?>
                                <canvas id="scoreChart" height="200"></canvas>
                                <?php
                                $scores = array_filter(array_map(function ($r) {
                                    return $r['BC_DIEMSO'];
                                }, $reports));

                                if (count($scores) > 0) {
                                    $avg_score = array_sum($scores) / count($scores);
                                } else {
                                    $avg_score = 0;
                                }
                                ?>
                                <div class="text-center mt-3">
                                    <h5>Điểm trung bình: <span
                                            class="text-primary"><?php echo number_format($avg_score, 1); ?></span>/10</h5>
                                </div>
                            <?php else: ?>
                                <div class="empty-state text-center py-4">
                                    <i class="fas fa-chart-line mb-3"></i>
                                    <p>Chưa có dữ liệu điểm số</p>
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
                        <a href="submit_report.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Nộp báo cáo mới
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (count($reports) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="thead-light">
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
                                            <td><?php echo $report['BC_TENBC']; ?></td>
                                            <td><?php echo $report['DT_TENDT']; ?></td>
                                            <td><?php echo $report['LBC_TENLOAI']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($report['BC_NGAYNOP'])); ?></td>
                                            <td>
                                                <span
                                                    class="badge <?php echo $status_class; ?>"><?php echo $report['BC_TRANGTHAI']; ?></span>
                                            </td>
                                            <td><?php echo $report['BC_DIEMSO'] ? $report['BC_DIEMSO'] : '-'; ?></td>
                                            <td>
                                                <div class="btn-group-sm">
                                                    <a href="view_report.php?id=<?php echo $report['BC_MABC']; ?>"
                                                        class="btn btn-sm btn-info mb-1" data-toggle="tooltip"
                                                        title="Xem chi tiết báo cáo">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($report['BC_TRANGTHAI'] !== 'Đã duyệt' && !empty($report['BC_DUONGDAN'])): ?>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/student/reports.js"></script>

    <!-- Khởi tạo biểu đồ -->
    <script>
        // Dữ liệu cho biểu đồ theo đề tài
        <?php if (count($stats) > 0): ?>
            var projectLabels = <?php echo json_encode(array_map(function ($s) {
                return $s['DT_TENDT']; }, $stats)); ?>;
            var approvedData = <?php echo json_encode(array_map(function ($s) {
                return $s['approved_reports']; }, $stats)); ?>;
            var pendingData = <?php echo json_encode(array_map(function ($s) {
                return $s['pending_reports']; }, $stats)); ?>;
            var revisionData = <?php echo json_encode(array_map(function ($s) {
                return $s['revision_reports']; }, $stats)); ?>;
        <?php endif; ?>

        // Dữ liệu điểm số
        <?php if (count($reports) > 0): ?>
            var scoreLabels = <?php echo json_encode(array_map(function ($r) {
                return date('d/m', strtotime($r['BC_NGAYNOP']));
            }, array_slice($reports, 0, 10))); ?>;
            var scoreData = <?php echo json_encode(array_map(function ($r) {
                return $r['BC_DIEMSO'] ? $r['BC_DIEMSO'] : 0;
            }, array_slice($reports, 0, 10))); ?>;
        <?php endif; ?>
    </script>
</body>

</html>