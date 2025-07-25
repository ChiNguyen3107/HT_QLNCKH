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

// Thêm sắp xếp và giới hạn kết quả
$sql_count = $sql;
$sql .= " ORDER BY dt.DT_NGAYTAO DESC LIMIT ?, ?";

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
$additional_css = '<style>
    /* Layout positioning - tương tự như dashboard và profile */
    #content-wrapper {
        margin-left: 260px !important;
        width: calc(100% - 260px) !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    .container-fluid {
        padding-left: 15px !important;
        padding-right: 15px !important;
        max-width: none !important;
    }
    
    /* Đảm bảo body layout đúng */
    body {
        margin-left: 0 !important;
    }
    
    /* Enhanced card counter styling */
    .card-counter {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 25px 15px;
        background-color: #fff;
        height: 120px;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow: hidden;
        position: relative;
        border: none;
    }
    
    .card-counter:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .card-counter i {
        font-size: 4.5em;
        opacity: 0.15;
        position: absolute;
        right: 15px;
        bottom: -5px;
        transition: all 0.3s ease;
    }
    
    .card-counter:hover i {
        opacity: 0.25;
        transform: scale(1.1);
    }
    
    .card-counter .count-numbers {
        position: absolute;
        right: 25px;
        top: 20px;
        font-size: 32px;
        font-weight: 700;
        display: block;
        line-height: 1;
    }
    
    .card-counter .count-name {
        position: absolute;
        right: 25px;
        top: 65px;
        text-transform: uppercase;
        opacity: 0.9;
        display: block;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }
    
    .card-counter.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #FFF;
    }
    
    .card-counter.info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
        color: #FFF;
    }
    
    .card-counter.success {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
        color: #FFF;
    }
    
    .card-counter.warning {
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
        color: #FFF;
    }
    
    /* Enhanced card styling */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        border-bottom: none;
        padding: 20px;
    }
    
    .card-body {
        padding: 25px;
    }
    
    /* Project status badges */
    .project-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .status-pending {
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
        color: white;
    }
    
    .status-progress {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .status-completed {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
        color: white;
    }
    
    .status-rejected {
        background: linear-gradient(135deg, #e74a3b 0%, #c82333 100%);
        color: white;
    }
    
    .status-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #e0a800 100%);
        color: white;
    }
    
    .status-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
    }
    
    /* Form controls */
    .form-control {
        border-radius: 8px;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        font-size: 0.95em;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }
    
    /* Button improvements */
    .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85em;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
    }
    
    .btn-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #e74a3b 0%, #c82333 100%);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 0.8em;
    }
    
    /* Table improvements */
    .table-responsive {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .table thead th {
        background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
        border-bottom: 2px solid #e3e6f0;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        color: #5a5c69;
        padding: 15px;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fc;
        transform: scale(1.01);
    }
    
    .table td {
        padding: 15px;
        vertical-align: middle;
    }
    
    /* Badge improvements */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
    }
    
    .badge-light {
        background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
        color: #5a5c69;
    }
    
    /* Empty state styling */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 5rem;
        color: #d1d3e2;
        margin-bottom: 25px;
        opacity: 0.5;
    }
    
    .empty-state h5 {
        color: #5a5c69;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .empty-state p {
        color: #858796;
        margin-bottom: 25px;
        font-size: 1.1em;
    }
    
    /* Animation classes */
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    .animate-on-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* DataTable customization */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-color: #667eea !important;
        color: white !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b4c93 100%) !important;
        border-color: #5a67d8 !important;
        color: white !important;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 8px 12px;
    }
    
    .dataTables_wrapper .dataTables_length select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 8px 12px;
    }
    
    /* Responsive improvements */
    @media (max-width: 992px) {
        .card-counter {
            height: 100px;
            margin-bottom: 20px;
        }
        
        .card-counter .count-numbers {
            font-size: 24px;
            top: 15px;
        }
        
        .card-counter .count-name {
            font-size: 11px;
            top: 50px;
        }
        
        .card-counter i {
            font-size: 3.5em;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
    
    @media (max-width: 768px) {
        .container-fluid {
            padding: 20px 15px !important;
        }
        
        .btn-group .btn {
            margin-bottom: 5px;
        }
        
        .table-responsive {
            font-size: 0.9em;
        }
        
        .card-body {
            padding: 20px;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
    
    @media (max-width: 576px) {
        .card-counter {
            height: 90px;
        }
        
        .card-counter .count-numbers {
            font-size: 20px;
            top: 12px;
        }
        
        .card-counter .count-name {
            font-size: 10px;
            top: 42px;
        }
        
        .btn {
            font-size: 0.8em;
            padding: 8px 16px;
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
    
    <!-- Filters and search section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Bộ lọc và tìm kiếm
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label fw-bold">
                            <i class="fas fa-tasks me-1"></i>Trạng thái đề tài
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Chờ duyệt" <?php echo $status_filter == 'Chờ duyệt' ? 'selected' : ''; ?>>Chờ duyệt</option>
                            <option value="Đang thực hiện" <?php echo $status_filter == 'Đang thực hiện' ? 'selected' : ''; ?>>Đang thực hiện</option>
                            <option value="Đã hoàn thành" <?php echo $status_filter == 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            <option value="Tạm dừng" <?php echo $status_filter == 'Tạm dừng' ? 'selected' : ''; ?>>Tạm dừng</option>
                            <option value="Đã hủy" <?php echo $status_filter == 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                            <option value="Đang xử lý" <?php echo $status_filter == 'Đang xử lý' ? 'selected' : ''; ?>>Đang xử lý</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label fw-bold">
                            <i class="fas fa-layer-group me-1"></i>Loại đề tài
                        </label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Tất cả loại</option>
                            <?php foreach ($types_list as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['LDT_MA']); ?>" <?php echo $type_filter == $type['LDT_MA'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['LDT_TENLOAI']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="faculty" class="form-label fw-bold">
                            <i class="fas fa-university me-1"></i>Khoa/Đơn vị
                        </label>
                        <select class="form-select" id="faculty" name="faculty">
                            <option value="">Tất cả khoa</option>
                            <?php foreach ($faculties as $faculty): ?>
                            <option value="<?php echo htmlspecialchars($faculty['DV_MADV']); ?>" <?php echo $faculty_filter == $faculty['DV_MADV'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($faculty['DV_TENDV']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label fw-bold">
                            <i class="fas fa-search me-1"></i>Tìm kiếm
                        </label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Tên đề tài, mã đề tài, tên GV" value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Tìm kiếm
                    </button>
                    <a href="manage_projects.php" class="btn btn-secondary">
                        <i class="fas fa-sync-alt me-2"></i>Đặt lại
                    </a>
                </div>
            </form>
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
                    <button class="btn btn-success btn-sm" id="exportBtn">
                        <i class="fas fa-file-excel me-1"></i>Xuất Excel
                    </button>
                    <button class="btn btn-info btn-sm" id="printBtn">
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
                            <th><i class="fas fa-hashtag me-1"></i>Mã đề tài</th>
                            <th><i class="fas fa-file-alt me-1"></i>Tên đề tài</th>
                            <th><i class="fas fa-user-tie me-1"></i>Giảng viên hướng dẫn</th>
                            <th><i class="fas fa-building me-1"></i>Khoa/Đơn vị</th>
                            <th><i class="fas fa-tag me-1"></i>Loại đề tài</th>
                            <th><i class="fas fa-info-circle me-1"></i>Trạng thái</th>
                            <th><i class="fas fa-calendar me-1"></i>Ngày tạo</th>
                            <th class="no-sort"><i class="fas fa-cogs me-1"></i>Thao tác</th>
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
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo isset($project['DT_NGAYTAO']) ? date('d/m/Y', strtotime($project['DT_NGAYTAO'])) : 'N/A'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Xem chi tiết"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" 
                                               class="btn btn-warning btn-sm" 
                                               title="Chỉnh sửa"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($project['DT_TRANGTHAI'] == 'Chờ duyệt' || $project['DT_TRANGTHAI'] == 'Đang xử lý'): ?>
                                                <a href="approve_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" 
                                                   class="btn btn-success btn-sm" 
                                                   title="Phê duyệt"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-danger btn-sm" 
                                                    title="Xóa"
                                                    data-bs-toggle="tooltip"
                                                    onclick="confirmDelete('<?php echo htmlspecialchars($project['DT_MADT']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <h5>Không tìm thấy đề tài nào</h5>
                                        <p>Thử thay đổi điều kiện lọc hoặc thêm đề tài mới</p>
                                        <a href="create_project.php" class="btn btn-primary">
                                            <i class="fas fa-plus mr-1"></i>Thêm đề tài mới
                                        </a>
                                    </div>
                                </td>
                            </tr>
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
            // Initialize Bootstrap tooltips
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
                    }
                },
                responsive: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                autoWidth: false,
                columnDefs: [
                    { targets: 'no-sort', orderable: false }
                ],
                order: [[6, 'desc']]
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
            
            // Initialize tooltips
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
