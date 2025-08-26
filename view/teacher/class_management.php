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
$count_sql = "SELECT COUNT(*) as total 
              FROM v_class_overview co 
              WHERE co.CVHT_MAGV = ?";
if ($search) {
    $count_sql .= " AND co.LOP_TEN LIKE ?";
}
if ($khoa) {
    $count_sql .= " AND co.DV_TENDV LIKE ?";
}
if ($nien_khoa) {
    $count_sql .= " AND co.KH_NAM = ?";
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

// Truy vấn danh sách lớp
$sql = "SELECT 
            co.LOP_MA,
            co.LOP_TEN,
            co.KH_NAM,
            co.DV_TENDV,
            co.TONG_SV,
            co.SV_CO_DETAI,
            co.SV_CHUA_CO_DETAI,
            co.TY_LE_THAM_GIA_PHANTRAM,
            co.CVHT_HOTEN,
            co.DETAI_CHO_DUYET,
            co.DETAI_DANG_THUCHIEN,
            co.DETAI_HOAN_THANH,
            co.DETAI_TAM_DUNG
        FROM v_class_overview co
        WHERE co.CVHT_MAGV = ?";

$params = [$teacher_id];
if ($search) {
    $sql .= " AND co.LOP_TEN LIKE ?";
    $params[] = "%$search%";
}
if ($khoa) {
    $sql .= " AND co.DV_TENDV LIKE ?";
    $params[] = "%$khoa%";
}
if ($nien_khoa) {
    $sql .= " AND co.KH_NAM = ?";
    $params[] = $nien_khoa;
}

$sql .= " ORDER BY co.LOP_TEN LIMIT ? OFFSET ?";
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

// Tính tổng thống kê
$total_students = 0;
$total_projects = 0;
$total_participation_rate = 0;
$class_count = 0;
$classes_list = [];
while ($class = $classes->fetch_assoc()) {
    $classes_list[] = $class;
    $total_students += $class['TONG_SV'];
    $total_projects += $class['SV_CO_DETAI'];
    $total_participation_rate += $class['TY_LE_THAM_GIA_PHANTRAM'];
    $class_count++;
}
$avg_participation_rate = $class_count > 0 ? round($total_participation_rate / $class_count, 2) : 0;
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
                            <h4 class="card-title"><?= $total_students ?></h4>
                            <p class="card-text">Tổng sinh viên</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success">
                        <div class="card-body text-center">
                            <i class="fas fa-folder-open fa-2x mb-2"></i>
                            <h4 class="card-title"><?= $total_projects ?></h4>
                            <p class="card-text">Đề tài đang thực hiện</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-info">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-2x mb-2"></i>
                            <h4 class="card-title"><?= $avg_participation_rate ?>%</h4>
                            <p class="card-text">Tỷ lệ tham gia TB</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                            <h4 class="card-title"><?= $class_count ?></h4>
                            <p class="card-text">Lớp đang cố vấn</p>
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
