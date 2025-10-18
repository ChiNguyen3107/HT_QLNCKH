<?php
// Bao gồm file session để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên
$teacher_id = $_SESSION['user_id'];

// Xử lý tham số tìm kiếm và phân trang
$search = $_GET['search'] ?? '';
$khoa = $_GET['khoa'] ?? '';
$nien_khoa = $_GET['nien_khoa'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(20, max(1, intval($_GET['limit'] ?? 20)));

// Truy vấn tổng số lớp
$count_sql = "SELECT COUNT(DISTINCT l.LOP_MA) as total 
              FROM advisor_class ac
              JOIN lop l ON ac.LOP_MA = l.LOP_MA
              WHERE ac.GV_MAGV = ? AND ac.AC_COHIEULUC = 1";
if ($search) {
    $count_sql .= " AND l.LOP_TEN LIKE ?";
}
if ($khoa) {
    $count_sql .= " AND k.DV_TENDV LIKE ?";
    $count_sql = str_replace("FROM advisor_class ac", "FROM advisor_class ac JOIN khoa k ON l.DV_MADV = k.DV_MADV", $count_sql);
}
if ($nien_khoa) {
    $count_sql .= " AND l.KH_NAM = ?";
}

$stmt = $conn->prepare($count_sql);
$params = [$teacher_id];
if ($search) $params[] = "%$search%";
if ($khoa) $params[] = "%$khoa%";
if ($nien_khoa) $params[] = $nien_khoa;

$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$total_classes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_pages = ceil($total_classes / $limit);
$offset = ($page - 1) * $limit;

// Truy vấn danh sách lớp với thống kê chi tiết
$sql = "SELECT 
            l.LOP_MA,
            l.LOP_TEN,
            l.KH_NAM,
            k.DV_TENDV,
            -- Thống kê sinh viên
            COUNT(DISTINCT sv.SV_MASV) as TONG_SV,
            COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL THEN sv.SV_MASV END) as SV_CO_DETAI,
            COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NULL THEN sv.SV_MASV END) as SV_CHUA_CO_DETAI,
            -- Tỷ lệ tham gia
            CASE 
                WHEN COUNT(DISTINCT sv.SV_MASV) > 0 
                THEN ROUND(COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL THEN sv.SV_MASV END) * 100.0 / COUNT(DISTINCT sv.SV_MASV), 2)
                ELSE 0 
            END as TY_LE_THAM_GIA_PHANTRAM,
            -- Thông tin CVHT
            CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as CVHT_HOTEN,
            -- Thống kê đề tài theo trạng thái (xử lý encoding và tránh duplicate)
            COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%duyet%' OR dt.DT_TRANGTHAI LIKE '%cho%' THEN dt.DT_MADT END) as DETAI_CHO_DUYET,
            COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN dt.DT_MADT END) as DETAI_DANG_THUCHIEN,
            COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN dt.DT_MADT END) as DETAI_HOAN_THANH,
            COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN dt.DT_MADT END) as DETAI_TAM_DUNG
        FROM advisor_class ac
        JOIN lop l ON ac.LOP_MA = l.LOP_MA
        JOIN khoa k ON l.DV_MADV = k.DV_MADV
        LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
        LEFT JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV
        LEFT JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
        LEFT JOIN giang_vien gv ON ac.GV_MAGV = gv.GV_MAGV
        WHERE ac.GV_MAGV = ? AND ac.AC_COHIEULUC = 1";

$params = [$teacher_id];
if ($search) {
    $sql .= " AND l.LOP_TEN LIKE ?";
    $params[] = "%$search%";
}
if ($khoa) {
    $sql .= " AND k.DV_TENDV LIKE ?";
    $params[] = "%$khoa%";
}
if ($nien_khoa) {
    $sql .= " AND l.KH_NAM = ?";
    $params[] = $nien_khoa;
}

$sql .= " GROUP BY l.LOP_MA, l.LOP_TEN, l.KH_NAM, k.DV_TENDV, gv.GV_HOGV, gv.GV_TENGV";
$sql .= " ORDER BY l.LOP_TEN LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$classes = $stmt->get_result();
$stmt->close();

// Lấy danh sách khoa cho filter
$departments = $conn->query("SELECT DISTINCT DV_TENDV FROM khoa ORDER BY DV_TENDV");
$departments_list = [];
if ($departments) {
    while ($row = $departments->fetch_assoc()) {
        $departments_list[] = $row['DV_TENDV'];
    }
}

// Lấy danh sách niên khóa cho filter
$academic_years = $conn->query("SELECT DISTINCT KH_NAM FROM lop ORDER BY KH_NAM DESC");
$years_list = [];
if ($academic_years) {
    while ($row = $academic_years->fetch_assoc()) {
        $years_list[] = $row['KH_NAM'];
    }
}

// Tính tổng thống kê từ các bảng gốc cho CVHT hiện tại
$stats_sql = "SELECT 
    -- Thống kê sinh viên
    COUNT(DISTINCT sv.SV_MASV) as total_students,
    COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL THEN sv.SV_MASV END) as total_students_with_projects,
    COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NULL THEN sv.SV_MASV END) as total_students_without_projects,
    
    -- Thống kê đề tài theo trạng thái (xử lý encoding và tránh duplicate)
    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%duyet%' OR dt.DT_TRANGTHAI LIKE '%cho%' THEN dt.DT_MADT END) as total_projects_pending,
    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN dt.DT_MADT END) as total_projects_ongoing,
    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN dt.DT_MADT END) as total_projects_completed,
    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN dt.DT_MADT END) as total_projects_suspended,
    
    -- Thống kê lớp
    COUNT(DISTINCT l.LOP_MA) as total_classes,
    
    -- Tỷ lệ tham gia
    CASE 
        WHEN COUNT(DISTINCT sv.SV_MASV) > 0 
        THEN ROUND(COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL THEN sv.SV_MASV END) * 100.0 / COUNT(DISTINCT sv.SV_MASV), 2)
        ELSE 0 
    END as avg_participation_rate
    
FROM advisor_class ac
JOIN lop l ON ac.LOP_MA = l.LOP_MA
LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
LEFT JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV
LEFT JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
WHERE ac.GV_MAGV = ? AND ac.AC_COHIEULUC = 1";

$params = [$teacher_id];
if ($search) {
    $stats_sql .= " AND l.LOP_TEN LIKE ?";
    $params[] = "%$search%";
}
if ($khoa) {
    $stats_sql .= " AND k.DV_TENDV LIKE ?";
    $params[] = "%$khoa%";
    $stats_sql = str_replace("FROM advisor_class ac", "FROM advisor_class ac JOIN khoa k ON l.DV_MADV = k.DV_MADV", $stats_sql);
}
if ($nien_khoa) {
    $stats_sql .= " AND l.KH_NAM = ?";
    $params[] = $nien_khoa;
}

$stmt = $conn->prepare($stats_sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$stats_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Lấy dữ liệu thống kê
$total_students = $stats_result['total_students'] ?? 0;
$total_students_with_projects = $stats_result['total_students_with_projects'] ?? 0;
$total_students_without_projects = $stats_result['total_students_without_projects'] ?? 0;
$total_projects_pending = $stats_result['total_projects_pending'] ?? 0;
$total_projects_ongoing = $stats_result['total_projects_ongoing'] ?? 0;
$total_projects_completed = $stats_result['total_projects_completed'] ?? 0;
$total_projects_suspended = $stats_result['total_projects_suspended'] ?? 0;
$total_classes = $stats_result['total_classes'] ?? 0;
$avg_participation_rate = $stats_result['avg_participation_rate'] ?? 0;

// Tính tổng số đề tài
$total_projects = $total_projects_ongoing + $total_projects_completed + $total_projects_pending + $total_projects_suspended;

// Lấy danh sách lớp cho hiển thị
$classes_list = [];
while ($class = $classes->fetch_assoc()) {
    $classes_list[] = $class;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lớp học - Hệ thống NCKH</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Responsive design cho sidebar */
        @media (max-width: 992px) {
            .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding: 10px;
            }
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .class-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .participation-badge {
            font-size: 0.9em;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .participation-high {
            background-color: #d4edda;
            color: #155724;
        }
        
        .participation-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .participation-low {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
        }
        
        .btn-view-details {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            color: white;
            border-radius: 20px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        
        .btn-view-details:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
            transform: translateY(-1px);
            color: white;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        

    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include '../../include/teacher_sidebar.php'; ?>
    

    
    <!-- Begin Page Content -->
    <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-graduation-cap text-primary"></i>
                        Quản lý lớp học
                    </h1>
                    <p class="text-muted">Quản lý và theo dõi các lớp bạn đang làm cố vấn học tập</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary" onclick="exportData()">
                        <i class="fas fa-download"></i> Xuất báo cáo
                    </button>
                </div>
            </div>
            
            <!-- Thống kê tổng quan -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card stats-card bg-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($total_students) ?></h4>
                            <p class="card-text">Tổng sinh viên các lớp</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success">
                        <div class="card-body text-center">
                            <i class="fas fa-user-check fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($total_students_with_projects) ?></h4>
                            <p class="card-text">Sinh viên có đề tài NCKH</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-info">
                        <div class="card-body text-center">
                            <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                            <h4 class="card-title"><?= $total_classes ?></h4>
                            <p class="card-text">Lớp đang cố vấn học tập</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($avg_participation_rate, 1) ?>%</h4>
                            <p class="card-text">Tỷ lệ tham gia NCKH TB</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Thống kê trạng thái đề tài -->
            <div class="row mt-3">
                <div class="col-md-2">
                    <div class="card stats-card bg-success">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($total_projects_ongoing) ?></h4>
                            <p class="card-text">Đề tài đang thực hiện</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-info">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($total_projects_completed) ?></h4>
                            <p class="card-text">Đề tài đã hoàn thành</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-secondary">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($total_projects_pending) ?></h4>
                            <p class="card-text">Đề tài chờ phê duyệt</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stats-card bg-danger">
                        <div class="card-body text-center">
                            <i class="fas fa-pause fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($total_projects_suspended) ?></h4>
                            <p class="card-text">Đề tài tạm dừng/hủy</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card bg-dark">
                        <div class="card-body text-center">
                            <i class="fas fa-project-diagram fa-2x mb-2"></i>
                            <h4 class="card-title"><?= number_format($total_projects) ?></h4>
                            <p class="card-text">Tổng số đề tài NCKH</p>
                        </div>
                    </div>
                </div>
            </div>
            
          
            
            <!-- Bộ lọc -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Tìm kiếm lớp</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Nhập tên lớp...">
                    </div>
                    <div class="col-md-3">
                        <label for="khoa" class="form-label">Khoa</label>
                        <select class="form-select" id="khoa" name="khoa">
                            <option value="">Tất cả khoa</option>
                            <?php foreach ($departments_list as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" 
                                        <?= $khoa === $dept ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="nien_khoa" class="form-label">Niên khóa</label>
                        <select class="form-select" id="nien_khoa" name="nien_khoa">
                            <option value="">Tất cả niên khóa</option>
                            <?php foreach ($years_list as $year): ?>
                                <option value="<?= htmlspecialchars($year) ?>" 
                                        <?= $nien_khoa === $year ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Danh sách lớp -->
            <?php if (empty($classes_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Chưa có lớp nào được gán</h3>
                    <p>Bạn chưa được gán làm cố vấn học tập cho lớp nào.</p>
                    <p>Vui lòng liên hệ quản trị viên để được gán lớp.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($classes_list as $class): ?>
                        <div class="col-lg-6 col-xl-4">
                            <div class="card class-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-graduation-cap text-primary"></i>
                                            <?= htmlspecialchars($class['LOP_TEN']) ?>
                                        </h5>
                                        <span class="badge <?= getParticipationBadgeClass($class['TY_LE_THAM_GIA_PHANTRAM']) ?>">
                                            <?= $class['TY_LE_THAM_GIA_PHANTRAM'] ?>%
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Khoa:</small><br>
                                            <strong><?= htmlspecialchars($class['DV_TENDV']) ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Niên khóa:</small><br>
                                            <strong><?= htmlspecialchars($class['KH_NAM']) ?></strong>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Tỷ lệ tham gia:</small>
                                            <small class="text-muted"><?= $class['TY_LE_THAM_GIA_PHANTRAM'] ?>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?= getProgressBarColor($class['TY_LE_THAM_GIA_PHANTRAM']) ?>" 
                                                 style="width: <?= $class['TY_LE_THAM_GIA_PHANTRAM'] ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="text-primary fw-bold"><?= $class['TONG_SV'] ?></div>
                                                <small class="text-muted">Tổng SV</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="text-success fw-bold"><?= $class['SV_CO_DETAI'] ?></div>
                                                <small class="text-muted">Có đề tài</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-2">
                                                <div class="text-warning fw-bold"><?= $class['SV_CHUA_CO_DETAI'] ?></div>
                                                <small class="text-muted">Chưa có</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="class_detail.php?lop_ma=<?= urlencode($class['LOP_MA']) ?>" 
                                           class="btn btn-view-details">
                                            <i class="fas fa-eye"></i> Xem chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Phân trang">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <!-- /.container-fluid -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function exportData() {
            // Tạo URL export với các tham số hiện tại
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            
            window.location.href = 'class_export.php?' + params.toString();
        }
        
        // Auto-submit form khi thay đổi select
        document.getElementById('khoa').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('nien_khoa').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>

<?php
// Helper functions
function getParticipationBadgeClass($rate) {
    if ($rate >= 80) return 'participation-high';
    if ($rate >= 50) return 'participation-medium';
    return 'participation-low';
}

function getProgressBarColor($rate) {
    if ($rate >= 80) return 'success';
    if ($rate >= 50) return 'warning';
    return 'danger';
}

function buildQueryString($newParams = []) {
    $currentParams = $_GET;
    $currentParams = array_merge($currentParams, $newParams);
    
    // Loại bỏ các tham số rỗng
    $currentParams = array_filter($currentParams, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return http_build_query($currentParams);
}
?>
