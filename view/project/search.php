<?php
session_start();
require_once '../../include/config.php';

// Lấy tham số tìm kiếm
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$department = $_GET['department'] ?? '';
$year = $_GET['year'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where_conditions[] = "(dt.DT_TENDT LIKE ? OR dt.DT_MOTA LIKE ? OR CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sss';
    }
    
    if (!empty($status)) {
        $where_conditions[] = "dt.DT_TRANGTHAI = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($department)) {
        $where_conditions[] = "k.DV_MADV = ?";
        $params[] = $department;
        $types .= 's';
    }
    
    if (!empty($year)) {
        $where_conditions[] = "YEAR(dt.DT_NGAYTAO) = ?";
        $params[] = $year;
        $types .= 'i';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total records
    $count_sql = "SELECT COUNT(*) as total 
                  FROM de_tai_nghien_cuu dt
                  LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                  LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
                  $where_clause";
    
    $stmt_count = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_records = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Get projects
    $sql = "SELECT 
                dt.DT_MADT,
                dt.DT_TENDT,
                dt.DT_MOTA,
                dt.DT_TRANGTHAI,
                dt.DT_NGAYTAO,
                dt.DT_SLSV,
                ldt.LDT_TENLOAI,
                lvnc.LVNC_TEN,
                CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                k.DV_TENDV,
                COUNT(cttg.SV_MASV) as SO_SV_THAMGIA
            FROM de_tai_nghien_cuu dt
            LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
            LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
            LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
            LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
            LEFT JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
            $where_clause
            GROUP BY dt.DT_MADT
            ORDER BY dt.DT_NGAYTAO DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get filter options
    $departments_sql = "SELECT DISTINCT k.DV_MADV, k.DV_TENDV 
                       FROM khoa k 
                       JOIN giang_vien gv ON k.DV_MADV = gv.DV_MADV
                       JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                       ORDER BY k.DV_TENDV";
    $departments = $conn->query($departments_sql)->fetch_all(MYSQLI_ASSOC);
    
    $years_sql = "SELECT DISTINCT YEAR(DT_NGAYTAO) as year 
                  FROM de_tai_nghien_cuu 
                  ORDER BY year DESC";
    $years = $conn->query($years_sql)->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in project search: " . $e->getMessage());
    $projects = [];
    $total_pages = 0;
    $departments = [];
    $years = [];
}

function getStatusBadge($status) {
    $badges = [
        'Chờ duyệt' => 'bg-warning',
        'Đang thực hiện' => 'bg-info',
        'Đã hoàn thành' => 'bg-success',
        'Tạm dừng' => 'bg-secondary',
        'Đã hủy' => 'bg-danger',
        'Đang xử lý' => 'bg-primary'
    ];
    
    $class = $badges[$status] ?? 'bg-secondary';
    return "<span class='badge {$class}'>{$status}</span>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm đề tài nghiên cứu - Hệ thống quản lý NCKH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/project/project-view.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .search-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .project-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .project-card .card-header {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #dee2e6;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .project-title {
            color: #495057;
            text-decoration: none;
            font-weight: 600;
        }
        
        .project-title:hover {
            color: #007bff;
        }
        
        .project-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .pagination-custom .page-link {
            border-radius: 10px;
            margin: 0 2px;
            border: none;
            color: #495057;
        }
        
        .pagination-custom .page-link:hover {
            background-color: #e9ecef;
        }
        
        .pagination-custom .page-item.active .page-link {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">
                        <i class="fas fa-search me-3"></i>
                        Tìm kiếm đề tài nghiên cứu
                    </h1>
                    <p class="lead mb-0">Khám phá các đề tài nghiên cứu khoa học tại trường</p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-card">
                        <h3 class="mb-1"><?= number_format($total_records) ?></h3>
                        <p class="mb-0">Đề tài được tìm thấy</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Search Form -->
        <div class="search-card mb-4">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-search me-1"></i>Tìm kiếm
                        </label>
                        <input type="text" class="form-control" name="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Tên đề tài, mô tả, giảng viên...">
                    </div>
                    
                    <div class="col-lg-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-flag me-1"></i>Trạng thái
                        </label>
                        <select class="form-select" name="status">
                            <option value="">Tất cả</option>
                            <option value="Chờ duyệt" <?= $status === 'Chờ duyệt' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="Đang thực hiện" <?= $status === 'Đang thực hiện' ? 'selected' : '' ?>>Đang thực hiện</option>
                            <option value="Đã hoàn thành" <?= $status === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                            <option value="Tạm dừng" <?= $status === 'Tạm dừng' ? 'selected' : '' ?>>Tạm dừng</option>
                            <option value="Đã hủy" <?= $status === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-building me-1"></i>Khoa
                        </label>
                        <select class="form-select" name="department">
                            <option value="">Tất cả khoa</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['DV_MADV'] ?>" <?= $department === $dept['DV_MADV'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['DV_TENDV']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-calendar me-1"></i>Năm
                        </label>
                        <select class="form-select" name="year">
                            <option value="">Tất cả</option>
                            <?php foreach ($years as $y): ?>
                            <option value="<?= $y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
                                <?= $y['year'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results -->
        <?php if (empty($projects)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">Không tìm thấy đề tài nào</h4>
            <p class="text-muted">Hãy thử thay đổi từ khóa tìm kiếm hoặc bộ lọc</p>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($projects as $project): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card project-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-0 text-muted"><?= htmlspecialchars($project['DT_MADT']) ?></h6>
                            <?= getStatusBadge($project['DT_TRANGTHAI']) ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="view_project.php?dt_madt=<?= urlencode($project['DT_MADT']) ?>" 
                               class="project-title">
                                <?= htmlspecialchars($project['DT_TENDT']) ?>
                            </a>
                        </h5>
                        
                        <p class="card-text text-muted mb-3" style="height: 60px; overflow: hidden;">
                            <?= htmlspecialchars(substr($project['DT_MOTA'], 0, 150)) ?>
                            <?= strlen($project['DT_MOTA']) > 150 ? '...' : '' ?>
                        </p>
                        
                        <div class="project-meta">
                            <div class="row g-2">
                                <div class="col-6">
                                    <i class="fas fa-user me-1"></i>
                                    <small><?= htmlspecialchars($project['GV_HOTEN']) ?></small>
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-building me-1"></i>
                                    <small><?= htmlspecialchars($project['DV_TENDV'] ?? 'N/A') ?></small>
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-users me-1"></i>
                                    <small><?= $project['SO_SV_THAMGIA'] ?>/<?= $project['DT_SLSV'] ?> SV</small>
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-calendar me-1"></i>
                                    <small><?= date('d/m/Y', strtotime($project['DT_NGAYTAO'])) ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($project['LDT_TENLOAI']): ?>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($project['LDT_TENLOAI']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($project['LVNC_TEN']): ?>
                        <div class="mt-1">
                            <span class="badge bg-info">
                                <?= htmlspecialchars($project['LVNC_TEN']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="view_project.php?dt_madt=<?= urlencode($project['DT_MADT']) ?>" 
                           class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-eye me-1"></i>Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Phân trang" class="mt-4">
            <ul class="pagination pagination-custom justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">© 2025 Hệ thống quản lý đề tài nghiên cứu khoa học</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select[name="status"], select[name="department"], select[name="year"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
        
        // Clear search
        function clearSearch() {
            window.location.href = 'search.php';
        }
    </script>
</body>
</html>
