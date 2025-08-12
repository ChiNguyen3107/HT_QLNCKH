<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\manage_projects.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/database.php';

// Lấy thông tin quản lý nghiên cứu
$manager_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM quan_ly_nghien_cuu WHERE QL_MA = ?");
$stmt->bind_param("s", $manager_id);
$stmt->execute();
$result = $stmt->get_result();
$manager_info = $result->fetch_assoc();
$stmt->close();

// Khởi tạo biến lọc và phân trang
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';

// Advanced filters
$date_from = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '';
$date_to = isset($_GET['dateTo']) ? $_GET['dateTo'] : '';
$sort_by = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'DT_NGAYTAO';
$sort_order = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'DESC';

// Validate sort parameters
$allowed_sort_fields = ['DT_NGAYTAO', 'DT_TENDT', 'DT_MADT', 'DT_TRANGTHAI'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'DT_NGAYTAO';
}

$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Xây dựng truy vấn SQL với điều kiện lọc
$sql = "SELECT dt.*, ldt.LDT_TENLOAI, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN, k.DV_TENDV 
        FROM de_tai_nghien_cuu dt 
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
        WHERE 1=1";

// Điều kiện lọc trạng thái
if (!empty($status_filter)) {
    $sql .= " AND dt.DT_TRANGTHAI = ?";
}

// Điều kiện lọc loại đề tài
if (!empty($type_filter)) {
    $sql .= " AND dt.LDT_MA = ?";
}

// Điều kiện lọc khoa
if (!empty($faculty_filter)) {
    $sql .= " AND gv.DV_MADV = ?";
}

// Điều kiện tìm kiếm
if (!empty($search_term)) {
    $sql .= " AND (dt.DT_TENDT LIKE ? OR dt.DT_MADT LIKE ? OR CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) LIKE ?)";
}

// Điều kiện lọc theo ngày
if (!empty($date_from)) {
    $sql .= " AND DATE(dt.DT_NGAYTAO) >= ?";
}
if (!empty($date_to)) {
    $sql .= " AND DATE(dt.DT_NGAYTAO) <= ?";
}

// Thêm sắp xếp và giới hạn kết quả
$sql_count = $sql;
$sql .= " ORDER BY {$sort_by} {$sort_order} LIMIT ?, ?";

// Xây dựng mảng tham số và kiểu dữ liệu
$types = '';
$params = array();

// Thêm các tham số lọc
if (!empty($status_filter)) {
    $types .= 's';
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $types .= 's';
    $params[] = $type_filter;
}

if (!empty($faculty_filter)) {
    $types .= 's';
    $params[] = $faculty_filter;
}

if (!empty($search_term)) {
    $types .= 'sss';
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Thêm tham số lọc ngày
if (!empty($date_from)) {
    $types .= 's';
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $types .= 's';
    $params[] = $date_to;
}

// Thêm tham số phân trang
$types .= 'ii';
$params[] = $offset;
$params[] = $items_per_page;

// Chuẩn bị và thực thi truy vấn
$projects = array();
if ($stmt = $conn->prepare($sql)) {
    // Gán tham số cho prepared statement
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
    } else {
        die('Lỗi thực thi truy vấn: ' . $stmt->error);
    }
    $stmt->close();
} else {
    die('Lỗi chuẩn bị truy vấn: ' . $conn->error);
}

// Đếm tổng số đề tài để phân trang
$total_items = 0;
$sql_count = str_replace(" ORDER BY dt.DT_NGAYTAO DESC LIMIT ?, ?", "", $sql_count);

if ($stmt_count = $conn->prepare($sql_count)) {
    // Bỏ 2 tham số cuối (limit và offset)
    if (!empty($params)) {
        $params_count = array_slice($params, 0, -2);
        $types_count = substr($types, 0, -2);
        
        if (!empty($params_count)) {
            $stmt_count->bind_param($types_count, ...$params_count);
        }
    }
    
    if ($stmt_count->execute()) {
        $result_count = $stmt_count->get_result();
        $total_items = $result_count->num_rows;
    }
    $stmt_count->close();
}

$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách các khoa
$faculty_sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV ASC";
$faculty_result = $conn->query($faculty_sql);
$faculties = array();
while ($row = $faculty_result->fetch_assoc()) {
    $faculties[] = $row;
}

// Lấy danh sách loại đề tài
$type_sql = "SELECT LDT_MA, LDT_TENLOAI FROM loai_de_tai ORDER BY LDT_TENLOAI ASC";
$type_result = $conn->query($type_sql);
$types_list = array();
while ($row = $type_result->fetch_assoc()) {
    $types_list[] = $row;
}

// Lấy thống kê cho các thẻ số liệu (tất cả đề tài trong hệ thống)
$stats_sql = "SELECT 
    COUNT(*) as total_projects,
    SUM(CASE WHEN DT_TRANGTHAI = 'Chờ duyệt' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN DT_TRANGTHAI = 'Đang thực hiện' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN DT_TRANGTHAI = 'Đã hoàn thành' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN DT_TRANGTHAI = 'Tạm dừng' THEN 1 ELSE 0 END) as paused,
    SUM(CASE WHEN DT_TRANGTHAI = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled
FROM de_tai_nghien_cuu";

$stats_result = $conn->query($stats_sql);
if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
    $total_projects = $stats['total_projects'];
    $pending = $stats['pending'];
    $in_progress = $stats['in_progress'];
    $completed = $stats['completed'];
    $paused = $stats['paused'];
    $cancelled = $stats['cancelled'];
} else {
    // Fallback values if query fails
    $total_projects = 0;
    $pending = 0;
    $in_progress = 0;
    $completed = 0;
    $paused = 0;
    $cancelled = 0;
}

?>

<?php 
// Set page title for the header
$page_title = "Quản lý đề tài | Quản lý nghiên cứu";

// Define any additional CSS specific to this page
$additional_css = '<link href="/NLNganh/assets/css/research/manage-projects-enhanced.css" rel="stylesheet">
<style>
/* Enhanced Filter Styles */
.bg-gradient-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.form-select-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}

.input-group-sm .form-control {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}

.alert-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.btn-sm {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}

/* Filter collapse animation */
.collapse {
    transition: all 0.3s ease;
}

/* Enhanced form controls */
.form-control:focus, .form-select:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

/* Active filter badges */
.badge.bg-primary { background-color: #4e73df !important; }
.badge.bg-success { background-color: #1cc88a !important; }
.badge.bg-warning { background-color: #f6c23e !important; color: #2c3e50 !important; }
.badge.bg-info { background-color: #36b9cc !important; }
.badge.bg-secondary { background-color: #858796 !important; }
.badge.bg-danger { background-color: #e74a3b !important; }

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-lg-2 {
        margin-bottom: 1rem;
    }
    .d-flex.gap-2 {
        flex-direction: column;
    }
    .d-flex.gap-2 .btn {
        margin-bottom: 0.5rem;
    }
}

/* Filter section styling */
.card-header.bg-gradient-primary {
    border-bottom: none;
}

.btn-link {
    color: #4e73df;
}

.btn-link:hover {
    color: #224abe;
    text-decoration: none;
}

/* Enhanced table styling */
.table-responsive {
    border-radius: 0.35rem;
    overflow: hidden;
}

.table thead th {
    background-color: #4e73df;
    color: #4e73df;
    border: none;
    font-weight: 600;
    padding: 0.75rem 0.5rem;
}

.table tbody tr:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
}

/* Project status styling */
.project-status {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending { background-color: #fff3cd; color: #856404; }
.status-progress { background-color: #d1ecf1; color: #0c5460; }
.status-completed { background-color: #d4edda; color: #155724; }
.status-warning { background-color: #f8d7da; color: #721c24; }
.status-rejected { background-color: #f8d7da; color: #721c24; }
.status-info { background-color: #d1ecf1; color: #0c5460; }

/* Button group styling */
.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Filter group styling */
.filter-group {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.filter-group .form-label {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
}

.filter-group .form-control,
.filter-group .form-select {
    height: 38px;
    font-size: 0.9rem;
}

/* Compact layout */
.form-label {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
}

.card-body {
    padding: 1.5rem;
}

/* Enhanced button styling */
.btn {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #224abe;
    border-color: #224abe;
}

/* Enhanced alert styling */
.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.alert-info .fas {
    color: #0c5460;
}

/* Enhanced badge styling */
.badge {
    font-size: 0.8rem;
    font-weight: 500;
    border-radius: 0.375rem;
}

/* Remove unnecessary animations */
.badge:hover {
    transform: none;
}

/* Empty state styling */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
}

.empty-state i {
    display: block;
    margin-bottom: 1rem;
}

.empty-state h5 {
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.empty-state p {
    margin-bottom: 1.5rem;
    color: #6c757d;
}

/* DataTables styling improvements */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 1rem;
}

.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    margin-top: 1rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .filter-group {
        margin-bottom: 1rem;
    }
    
    .d-grid.gap-2 {
        gap: 0.5rem !important;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        text-align: left;
        margin-bottom: 0.5rem;
    }
}
</style>';

// Include the research header
include '../../include/research_header.php';
?>

<!-- Sidebar đã được include trong header -->

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-folder-open me-3"></i>
            Quản lý đề tài nghiên cứu
        </h1>
        <a href="create_project.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Thêm đề tài mới
        </a>
    </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
        <!-- Tổng số đề tài -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter primary animate-on-scroll">
                <i class="fas fa-folder-open"></i>
                <span class="count-numbers"><?= $total_projects ?></span>
                <span class="count-name">Tổng số đề tài</span>
            </div>
        </div>

        <!-- Đề tài đang chờ duyệt -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter warning animate-on-scroll">
                <i class="fas fa-clock"></i>
                <span class="count-numbers"><?= $pending ?></span>
                <span class="count-name">Chờ duyệt</span>
            </div>
        </div>

        <!-- Đề tài đang thực hiện -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter info animate-on-scroll">
                <i class="fas fa-spinner"></i>
                <span class="count-numbers"><?= $in_progress ?></span>
                <span class="count-name">Đang thực hiện</span>
            </div>
        </div>

        <!-- Đề tài đã hoàn thành -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter success animate-on-scroll">
                <i class="fas fa-check-circle"></i>
                <span class="count-numbers"><?= $completed ?></span>
                <span class="count-name">Đã hoàn thành</span>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Filters and Search Section -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Bộ lọc và tìm kiếm
                </h5>
                <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body bg-light p-4">
                <form method="GET" action="" class="needs-validation" novalidate id="filterForm">
                    <!-- Main Filter Row -->
                    <div class="row g-4 mb-4">
                        <!-- Status Filter -->
                        <div class="col-lg-2 col-md-6">
                            <div class="filter-group">
                                <label for="status" class="form-label fw-bold text-primary mb-2">Trạng thái</label>
                                <select class="form-select border-primary" id="status" name="status">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="Chờ duyệt" <?php echo $status_filter == 'Chờ duyệt' ? 'selected' : ''; ?>>Chờ duyệt</option>
                                    <option value="Đang thực hiện" <?php echo $status_filter == 'Đang thực hiện' ? 'selected' : ''; ?>>Đang thực hiện</option>
                                    <option value="Đã hoàn thành" <?php echo $status_filter == 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                    <option value="Tạm dừng" <?php echo $status_filter == 'Tạm dừng' ? 'selected' : ''; ?>>Tạm dừng</option>
                                    <option value="Đã hủy" <?php echo $status_filter == 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                                    <option value="Đang xử lý" <?php echo $status_filter == 'Đang xử lý' ? 'selected' : ''; ?>>Đang xử lý</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Project Type Filter -->
                        <div class="col-lg-2 col-md-6">
                            <div class="filter-group">
                                <label for="type" class="form-label fw-bold text-primary mb-2">Loại đề tài</label>
                                <select class="form-select border-primary" id="type" name="type">
                                    <option value="">Tất cả loại</option>
                                    <?php foreach ($types_list as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['LDT_MA']); ?>" <?php echo $type_filter == $type['LDT_MA'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['LDT_TENLOAI']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Faculty Filter -->
                        <div class="col-lg-2 col-md-6">
                            <div class="filter-group">
                                <label for="faculty" class="form-label fw-bold text-primary mb-2">Khoa/Đơn vị</label>
                                <select class="form-select border-primary" id="faculty" name="faculty">
                                    <option value="">Tất cả khoa</option>
                                    <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?php echo htmlspecialchars($faculty['DV_MADV']); ?>" <?php echo $faculty_filter == $faculty['DV_MADV'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($faculty['DV_TENDV']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Search Input -->
                        <div class="col-lg-4 col-md-6">
                            <div class="filter-group">
                                <label for="search" class="form-label fw-bold text-primary mb-2">Tìm kiếm</label>
                                <div class="input-group">
                                    <input type="text" class="form-control border-primary" id="search" name="search" 
                                           placeholder="Tìm theo tên đề tài, mã đề tài, giảng viên..." 
                                           value="<?php echo htmlspecialchars($search_term); ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Xóa tìm kiếm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="col-lg-2 col-md-12">
                            <div class="filter-group d-flex flex-column h-100 justify-content-end">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Tìm kiếm
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="resetFilters" title="Làm mới">
                                        <i class="fas fa-sync-alt me-2"></i>Làm mới
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Filters Row (Collapsible) -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <button class="btn btn-link text-decoration-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="false">
                                <i class="fas fa-cog me-2"></i>Bộ lọc nâng cao <i class="fas fa-chevron-down ms-1"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="collapse" id="advancedFilters">
                        <div class="row g-4 pt-3 border-top">
                            <!-- Date Range Filter -->
                            <div class="col-lg-3 col-md-6">
                                <div class="filter-group">
                                    <label for="dateFrom" class="form-label fw-bold text-secondary mb-2">Từ ngày</label>
                                    <input type="date" class="form-control" id="dateFrom" name="dateFrom" 
                                           value="<?php echo isset($_GET['dateFrom']) ? htmlspecialchars($_GET['dateFrom']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-6">
                                <div class="filter-group">
                                    <label for="dateTo" class="form-label fw-bold text-secondary mb-2">Đến ngày</label>
                                    <input type="date" class="form-control" id="dateTo" name="dateTo" 
                                           value="<?php echo isset($_GET['dateTo']) ? htmlspecialchars($_GET['dateTo']) : ''; ?>">
                                </div>
                            </div>
                            
                            <!-- Sort Order -->
                            <div class="col-lg-3 col-md-6">
                                <div class="filter-group">
                                    <label for="sortBy" class="form-label fw-bold text-secondary mb-2">Sắp xếp theo</label>
                                    <select class="form-select" id="sortBy" name="sortBy">
                                        <option value="DT_NGAYTAO" <?php echo (isset($_GET['sortBy']) && $_GET['sortBy'] == 'DT_NGAYTAO') ? 'selected' : ''; ?>>Ngày tạo</option>
                                        <option value="DT_TENDT" <?php echo (isset($_GET['sortBy']) && $_GET['sortBy'] == 'DT_TENDT') ? 'selected' : ''; ?>>Tên đề tài</option>
                                        <option value="DT_MADT" <?php echo (isset($_GET['sortBy']) && $_GET['sortBy'] == 'DT_MADT') ? 'selected' : ''; ?>>Mã đề tài</option>
                                        <option value="DT_TRANGTHAI" <?php echo (isset($_GET['sortBy']) && $_GET['sortBy'] == 'DT_TRANGTHAI') ? 'selected' : ''; ?>>Trạng thái</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Sort Direction -->
                            <div class="col-lg-3 col-md-6">
                                <div class="filter-group">
                                    <label for="sortOrder" class="form-label fw-bold text-secondary mb-2">Thứ tự</label>
                                    <select class="form-select" id="sortOrder" name="sortOrder">
                                        <option value="DESC" <?php echo (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC') ? 'selected' : ''; ?>>Giảm dần</option>
                                        <option value="ASC" <?php echo (isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'ASC') ? 'selected' : ''; ?>>Tăng dần</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Filters Display -->
                    <?php if (!empty($status_filter) || !empty($type_filter) || !empty($faculty_filter) || !empty($search_term) || !empty($date_from) || !empty($date_to)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-info py-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-filter me-2"></i>
                                    <strong>Bộ lọc đang áp dụng:</strong>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!empty($status_filter)): ?>
                                        <span class="badge bg-primary px-3 py-2">Trạng thái: <?php echo htmlspecialchars($status_filter); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($type_filter)): ?>
                                        <span class="badge bg-success px-3 py-2">Loại: <?php echo htmlspecialchars($type_filter); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($faculty_filter)): ?>
                                        <span class="badge bg-warning px-3 py-2">Khoa: <?php echo htmlspecialchars($faculty_filter); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($search_term)): ?>
                                        <span class="badge bg-info px-3 py-2">Tìm kiếm: "<?php echo htmlspecialchars($search_term); ?>"</span>
                                    <?php endif; ?>
                                    <?php if (!empty($date_from) || !empty($date_to)): ?>
                                        <span class="badge bg-secondary px-3 py-2">
                                            Khoảng thời gian: 
                                            <?php echo !empty($date_from) ? date('d/m/Y', strtotime($date_from)) : 'Từ đầu'; ?> 
                                            - 
                                            <?php echo !empty($date_to) ? date('d/m/Y', strtotime($date_to)) : 'Đến nay'; ?>
                                        </span>
                                    <?php endif; ?>
                                    <a href="manage_projects.php" class="badge bg-danger text-decoration-none px-3 py-2">
                                        <i class="fas fa-times me-1"></i>Xóa tất cả
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Projects list section -->
    <div class="card mb-4 animate-on-scroll">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Danh sách đề tài nghiên cứu
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_items; ?> đề tài được lọc</span>
                </h5>
                <div class="btn-group" role="group">
                    <button class="btn btn-success btn-sm" id="exportBtn" title="Xuất Excel">
                        <i class="fas fa-file-excel me-1"></i>Xuất Excel
                    </button>
                    <button class="btn btn-info btn-sm" id="printBtn" title="In danh sách">
                        <i class="fas fa-print me-1"></i>In danh sách
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover datatable" id="projectsTable" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th>Mã đề tài</th>
                            <th>Tên đề tài</th>
                            <th>Giảng viên hướng dẫn</th>
                            <th>Khoa/Đơn vị</th>
                            <th>Loại đề tài</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th class="no-sort">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($projects) > 0): ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <span class="font-weight-bold text-primary"><?php echo htmlspecialchars($project['DT_MADT']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($project['DT_TENDT']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Chưa phân công'); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($project['DV_TENDV'] ?? 'Không xác định'); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Chưa phân loại'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($project['DT_TRANGTHAI']) {
                                            case 'Chờ duyệt':
                                                $status_class = 'status-pending';
                                                break;
                                            case 'Đang thực hiện':
                                                $status_class = 'status-progress';
                                                break;
                                            case 'Đã hoàn thành':
                                                $status_class = 'status-completed';
                                                break;
                                            case 'Tạm dừng':
                                                $status_class = 'status-warning';
                                                break;
                                            case 'Đã hủy':
                                                $status_class = 'status-rejected';
                                                break;
                                            case 'Đang xử lý':
                                                $status_class = 'status-info';
                                                break;
                                            default:
                                                $status_class = 'status-pending';
                                                break;
                                        }
                                        ?>
                                        <span class="project-status <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo isset($project['DT_NGAYTAO']) ? date('d/m/Y', strtotime($project['DT_NGAYTAO'])) : 'N/A'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" 
                                               class="btn btn-warning btn-sm" 
                                               title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($project['DT_TRANGTHAI'] == 'Chờ duyệt' || $project['DT_TRANGTHAI'] == 'Đang xử lý'): ?>
                                                <a href="approve_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" 
                                                   class="btn btn-success btn-sm" 
                                                   title="Phê duyệt">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-danger btn-sm" 
                                                    title="Xóa"
                                                    onclick="confirmDelete('<?php echo htmlspecialchars($project['DT_MADT']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    </div> <!-- /.container-fluid -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    <!-- Custom JavaScript for enhanced functionality -->
    <script>
        $(document).ready(function() {
            // Initialize Bootstrap tooltips (only for elements that need them)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize DataTables
            $('#projectsTable').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json",
                    search: "Tìm kiếm:",
                    lengthMenu: "Hiển thị _MENU_ mục",
                    info: "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
                    infoEmpty: "Hiển thị 0 đến 0 của 0 mục",
                    infoFiltered: "(lọc từ _MAX_ mục)",
                    paginate: {
                        first: "Đầu tiên",
                        last: "Cuối cùng",
                        next: "Tiếp",
                        previous: "Trước"
                    },
                    emptyTable: "Không có dữ liệu để hiển thị"
                },
                responsive: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                autoWidth: false,
                processing: true,
                columnDefs: [
                    { targets: 7, orderable: false, searchable: false } // Cột thao tác
                ],
                order: [[6, 'desc']],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function(settings) {
                    // Xử lý trường hợp không có dữ liệu
                    if (settings.json && settings.json.data && settings.json.data.length === 0) {
                        $(this).find('tbody').html(
                            '<tr><td colspan="8" class="text-center py-4">' +
                            '<div class="empty-state">' +
                            '<i class="fas fa-folder-open fa-3x text-muted mb-3"></i>' +
                            '<h5 class="text-muted">Không tìm thấy đề tài nào</h5>' +
                            '<p class="text-muted">Thử thay đổi điều kiện lọc hoặc thêm đề tài mới</p>' +
                            '<a href="create_project.php" class="btn btn-primary">' +
                            '<i class="fas fa-plus me-2"></i>Thêm đề tài mới</a>' +
                            '</div></td></tr>'
                        );
                    }
                }
            });
            
            // Animation on scroll
            function animateOnScroll() {
                $('.animate-on-scroll').each(function() {
                    const elementTop = $(this).offset().top;
                    const elementHeight = $(this).outerHeight();
                    const windowHeight = $(window).height();
                    const scrollY = window.scrollY;
                    
                    const delay = parseInt($(this).data('delay')) || 0;
                    
                    if (elementTop < (scrollY + windowHeight - elementHeight / 2)) {
                        setTimeout(() => {
                            $(this).addClass('visible');
                        }, delay);
                    }
                });
            }
            
            // Execute animation on initial load
            setTimeout(function() {
                animateOnScroll();
            }, 100);
            
            // Execute animation on scroll
            $(window).on('scroll', function() {
                animateOnScroll();
            });
            
            // Counter animation
            $('.count-numbers').each(function () {
                const $this = $(this);
                const countTo = parseInt($this.text());
                
                $({ countNum: 0 }).animate({
                    countNum: countTo
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum));
                    },
                    complete: function() {
                        $this.text(this.countNum);
                    }
                });
            });
            
            // Export to Excel
            $('#exportBtn').on('click', function() {
                exportToExcel();
            });
            
            // Print functionality
            $('#printBtn').on('click', function() {
                printTable();
            });
            
            // Enhanced Filter Functionality
            // Clear search button
            $('#clearSearch').on('click', function() {
                $('#search').val('').focus();
            });
            
            // Reset all filters
            $('#resetFilters').on('click', function() {
                window.location.href = 'manage_projects.php';
            });
            
            // Auto-submit form when filters change (optional)
            $('#status, #type, #faculty').on('change', function() {
                // Uncomment the line below if you want auto-submit on filter change
                // $('#filterForm').submit();
            });
            
            // Collapse/Expand filter section
            $('[data-bs-toggle="collapse"]').on('click', function() {
                const icon = $(this).find('i.fas');
                if ($(this).attr('aria-expanded') === 'true') {
                    icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                } else {
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                }
            });
            
            // Advanced filters collapse
            $('#advancedFilters').on('show.bs.collapse', function() {
                $('[data-bs-target="#advancedFilters"] i.fas.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }).on('hide.bs.collapse', function() {
                $('[data-bs-target="#advancedFilters"] i.fas.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });
            
            // Date validation
            $('#dateFrom, #dateTo').on('change', function() {
                const dateFrom = $('#dateFrom').val();
                const dateTo = $('#dateTo').val();
                
                if (dateFrom && dateTo && dateFrom > dateTo) {
                    alert('Ngày bắt đầu không thể lớn hơn ngày kết thúc!');
                    $(this).val('');
                }
            });
            
            // Real-time search with debounce
            let searchTimeout;
            $('#search').on('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = $(this).val();
                
                searchTimeout = setTimeout(function() {
                    if (searchTerm.length >= 2 || searchTerm.length === 0) {
                        // Uncomment the line below if you want real-time search
                        // $('#filterForm').submit();
                    }
                }, 500);
            });
            
            // Initialize tooltips (only for elements that need them)
            $('[data-toggle="tooltip"]').tooltip();
            
            // Helper functions
            function exportToExcel() {
                const rows = [];
                const headers = [];
                
                // Get headers
                $('#projectsTable thead th').each(function(index) {
                    if (index < 7) { // Exclude action column
                        headers.push($(this).text().trim().replace(/\s+/g, ' '));
                    }
                });
                rows.push(headers);
                
                // Get data rows
                $('#projectsTable tbody tr').each(function() {
                    if (!$(this).find('td').hasClass('text-center')) { // Skip empty state row
                        const row = [];
                        $(this).find('td').each(function(index) {
                            if (index < 7) { // Exclude action column
                                row.push($(this).text().trim().replace(/\s+/g, ' '));
                            }
                        });
                        if (row.length > 0) {
                            rows.push(row);
                        }
                    }
                });
                
                // Create CSV content
                let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
                rows.forEach(rowArray => {
                    const row = rowArray.map(field => '"' + (field || '').replace(/"/g, '""') + '"').join(",");
                    csvContent += row + "\r\n";
                });
                
                // Download file
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "danh_sach_de_tai_" + new Date().toISOString().slice(0,10) + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Show success message
                alert('Xuất file Excel thành công!');
            }
            
            function printTable() {
                const printContent = $('#projectsTable').clone();
                printContent.find('.no-sort').remove(); // Remove action column
                printContent.find('tbody tr').each(function() {
                    $(this).find('td:last').remove(); // Remove action column data
                });
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Danh sách đề tài nghiên cứu</title>
                            <style>
                                body { font-family: Arial, sans-serif; margin: 20px; }
                                h1 { text-align: center; color: #333; }
                                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                th { background-color: #f2f2f2; font-weight: bold; }
                                .project-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                                .status-pending { background-color: #fff3cd; color: #856404; }
                                .status-progress { background-color: #d1ecf1; color: #0c5460; }
                                .status-completed { background-color: #d4edda; color: #155724; }
                                .status-rejected { background-color: #f8d7da; color: #721c24; }
                            </style>
                        </head>
                        <body>
                            <h1>Danh sách đề tài nghiên cứu</h1>
                            <p>Ngày in: ${new Date().toLocaleDateString('vi-VN')}</p>
                            ${printContent[0].outerHTML}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        });
        
        // Global functions
        function confirmDelete(projectId) {
            if (confirm('Bạn có chắc chắn muốn xóa đề tài này không?')) {
                // Implementation for delete would go here
                console.log('Deleting project:', projectId);
                alert('Chức năng xóa đang được phát triển');
            }
        }
    </script>

<?php
// Include footer if needed
// include '../../include/research_footer.php';
?>
