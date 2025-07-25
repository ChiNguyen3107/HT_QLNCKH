<?php
// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/database.php';

// Khởi tạo biến lọc và phân trang
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Xây dựng truy vấn SQL với điều kiện lọc - chỉ lấy đề tài đang chờ duyệt
$sql = "SELECT dt.*, ldt.LDT_TENLOAI, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN, k.DV_TENDV, gv.GV_MAGV 
        FROM de_tai_nghien_cuu dt 
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
        WHERE dt.DT_TRANGTHAI = 'Chờ duyệt'";

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
$sql .= " ORDER BY dt.DT_MADT DESC LIMIT ?, ?";

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Xây dựng mảng tham số và kiểu dữ liệu
$types = '';
$params = array();

// Thêm các tham số lọc
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

// Gán tham số cho prepared statement
if ($stmt && !empty($params)) {
    $ref_params = array();
    $ref_params[] = &$types;
    foreach ($params as $key => $value) {
        $ref_params[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $ref_params);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $projects = array();
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
} else {
    $projects = array();
}

// Đếm tổng số đề tài để phân trang
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $params_count = array_slice($params, 0, -2);
    $types_count = substr($types, 0, -2);
    
    if (!empty($params_count)) {
        $ref_params_count = array();
        $ref_params_count[] = &$types_count;
        foreach ($params_count as $key => $value) {
            $ref_params_count[] = &$params_count[$key];
        }
        call_user_func_array(array($stmt_count, 'bind_param'), $ref_params_count);
    }
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_items = $result_count->num_rows;
$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách các khoa
$faculty_sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV ASC";
$faculty_result = $conn->query($faculty_sql);
$faculties = array();
if ($faculty_result) {
    while ($row = $faculty_result->fetch_assoc()) {
        $faculties[] = $row;
    }
}

// Xử lý phê duyệt hoặc từ chối
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_action'])) {
    $project_id = $_POST['project_id'];
    $action = $_POST['action'];
    $comments = $_POST['comments'] ?? '';
    $lecturer_id = $_POST['lecturer_id'] ?? null;
    $project_title = $_POST['project_title'] ?? 'Đề tài nghiên cứu';
    
    if ($action === 'approve') {
        $new_status = 'Đang thực hiện';
        $status_text = 'đã được phê duyệt';
        $notification_type = 'success';
    } else {
        $new_status = 'Đã hủy';
        $status_text = 'đã bị từ chối';
        $notification_type = 'danger';
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Cập nhật trạng thái đề tài
        $update_sql = "UPDATE de_tai_nghien_cuu SET DT_TRANGTHAI = ?, DT_GHICHU = CONCAT(IFNULL(DT_GHICHU, ''), '\n', ?), DT_NGUOICAPNHAT = ?, DT_NGAYCAPNHAT = NOW() WHERE DT_MADT = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssss", $new_status, $comments, $_SESSION['user_id'], $project_id);
        $update_stmt->execute();
        
        // Ghi log hoạt động
        $log_check = $conn->query("SHOW TABLES LIKE 'log_hoat_dong'");
        if ($log_check && $log_check->num_rows > 0) {
            $log_sql = "INSERT INTO log_hoat_dong (LHD_DOITUONG, LHD_DOITUONG_ID, LHD_HANHDONG, LHD_NOIDUNG, LHD_NGUOITHAOTAC, LHD_THOIGIAN) VALUES ('de_tai', ?, ?, ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $action_text = $action === 'approve' ? 'phê duyệt' : 'từ chối';
            $log_content = "Đề tài [$project_id] $project_title đã được $action_text" . ($comments ? ". Ghi chú: $comments" : "");
            $log_stmt->bind_param("ssss", $project_id, $action_text, $log_content, $_SESSION['user_id']);
            $log_stmt->execute();
        }
        
        // Tạo thông báo cho giảng viên
        if ($lecturer_id) {
            $notification_check = $conn->query("SHOW TABLES LIKE 'thong_bao'");
            if ($notification_check && $notification_check->num_rows > 0) {
                $notification_sql = "INSERT INTO thong_bao (NGUOI_NHAN, TB_NOIDUNG, TB_LOAI, TB_LINK, TB_TRANGTHAI, TB_NGAYTAO) VALUES (?, ?, ?, ?, 'chưa đọc', NOW())";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_content = "Đề tài của bạn ($project_title) $status_text" . ($comments ? ". Ghi chú: $comments" : "");
                $notification_link = "/NLNganh/view/teacher/view_project.php?id=$project_id";
                $notification_stmt->bind_param("ssss", $lecturer_id, $notification_content, $notification_type, $notification_link);
                $notification_stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Thông báo thành công và tải lại trang
        header("Location: review_projects.php?success=1&action=$action");
        exit;
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Thông báo lỗi
        header("Location: review_projects.php?error=1&message=" . urlencode($e->getMessage()));
        exit;
    }
}

// Tính toán thống kê
$pending_count = $total_items; // Số đề tài đang chờ duyệt từ query đã lọc

// Thống kê tổng số đề tài
$total_projects = 0;
$stats_result = $conn->query("SELECT COUNT(*) as total FROM de_tai_nghien_cuu");
if ($stats_result) {
    $total_projects = $stats_result->fetch_assoc()['total'];
}

// Thống kê đề tài đã phê duyệt
$approved_count = 0;
$approved_result = $conn->query("SELECT COUNT(*) as approved FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đang thực hiện'");
if ($approved_result) {
    $approved_count = $approved_result->fetch_assoc()['approved'];
}

// Thống kê đề tài đã từ chối
$rejected_count = 0;
$rejected_result = $conn->query("SELECT COUNT(*) as rejected FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hủy'");
if ($rejected_result) {
    $rejected_count = $rejected_result->fetch_assoc()['rejected'];
}

// Set page title
$page_title = "Phê duyệt đề tài | Quản lý nghiên cứu";

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
    
    /* Enhanced project cards */
    .project-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
        background: white;
    }
    
    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .project-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-bottom: none;
    }
    
    .project-card-body {
        padding: 25px;
    }
    
    /* Statistics cards improvements */
    .stats-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
        background: white;
        margin-bottom: 30px;
    }
    
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }
    
    .stats-card.border-left-primary {
        border-left: 4px solid #667eea !important;
    }
    
    .stats-card.border-left-success {
        border-left: 4px solid #1cc88a !important;
    }
    
    .stats-card.border-left-warning {
        border-left: 4px solid #f6c23e !important;
    }
    
    .stats-card.border-left-danger {
        border-left: 4px solid #e74a3b !important;
    }
    
    /* Enhanced buttons */
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
    
    .form-select {
        border-radius: 8px;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        font-size: 0.95em;
    }
    
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }
    
    /* Alert improvements */
    .alert {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
    }
    
    /* Modal improvements */
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
        border-radius: 12px 12px 0 0;
        border-bottom: none;
        padding: 20px;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-footer {
        border-top: none;
        padding: 20px;
    }
    
    /* Empty state */
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
    
    /* Pagination improvements */
    .pagination {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .page-link {
        border: none;
        color: #667eea;
        transition: all 0.3s ease;
    }
    
    .page-link:hover {
        background-color: #667eea;
        color: white;
        transform: translateY(-1px);
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 20px 15px !important;
        }
        
        .btn-group .btn {
            margin-bottom: 5px;
        }
        
        .project-card-body {
            padding: 20px;
        }
        
        .stats-card {
            margin-bottom: 20px;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
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
            <i class="fas fa-clipboard-check me-3"></i>
            Phê duyệt đề tài nghiên cứu
        </h1>
        <a href="research_dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Về Dashboard
        </a>
    </div>

            <!-- Statistics cards -->
            <div class="row mb-4">
                <!-- Total projects card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-primary h-100 py-3">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                        Đang chờ phê duyệt</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= $total_items ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approved projects card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-success h-100 py-3">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                        Đã phê duyệt</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= $approved_count ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejected projects card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-danger h-100 py-3">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">
                                        Đã từ chối</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= $rejected_count ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total all projects card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-left-warning h-100 py-3">
                        <div class="card-body">
                            <div class="row g-0 align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                        Tổng số đề tài</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?= $total_projects ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-folder-open fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert messages -->
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                        $action_message = isset($_GET['action']) && $_GET['action'] == 'approve' ? 'phê duyệt' : 'từ chối';
                        echo "Đề tài đã được $action_message thành công!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Có lỗi xảy ra khi xử lý yêu cầu. Vui lòng thử lại.
                    <?php if (isset($_GET['message'])): ?>
                        <br><small><?= htmlspecialchars(urldecode($_GET['message'])) ?></small>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filter card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Bộ lọc tìm kiếm
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="filter-form">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="faculty" class="form-label fw-bold">
                                    <i class="fas fa-building me-1"></i>Khoa/Đơn vị
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
                            <div class="col-md-5">
                                <label for="search" class="form-label fw-bold">
                                    <i class="fas fa-search me-1"></i>Tìm kiếm
                                </label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Tên đề tài, mã đề tài, tên GV" 
                                       value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="w-100">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>Lọc
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <a href="review_projects.php" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt me-1"></i>Đặt lại bộ lọc
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Projects list -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Danh sách đề tài chờ phê duyệt
                        <span class="badge bg-warning ms-2"><?php echo count($projects); ?> đề tài</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($projects) > 0): ?>
                        <div class="row">
                        <?php foreach ($projects as $project): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card project-card h-100">
                                    <div class="project-card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-bold">
                                                <?php echo htmlspecialchars($project['DT_TENDT']); ?>
                                            </h6>
                                            <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                        </div>
                                    </div>
                                    <div class="project-card-body">
                                        <div class="mb-3">
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <div class="small text-muted">
                                                        <i class="fas fa-code me-1"></i> Mã đề tài: 
                                                        <span class="fw-bold"><?php echo htmlspecialchars($project['DT_MADT']); ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted">
                                                        <i class="fas fa-user me-1"></i> Giảng viên: 
                                                        <span class="fw-bold"><?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Chưa phân công'); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="small text-muted">
                                                        <i class="fas fa-building me-1"></i> Khoa: 
                                                        <span class="fw-bold"><?php echo htmlspecialchars($project['DV_TENDV'] ?? 'Không xác định'); ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted">
                                                        <i class="fas fa-tag me-1"></i> Loại đề tài: 
                                                        <span class="fw-bold"><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Chưa phân loại'); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-3">
                                            <?php 
                                            $description = $project['DT_MOTA'] ?? 'Không có mô tả';
                                            echo htmlspecialchars(substr($description, 0, 150) . (strlen($description) > 150 ? '...' : '')); 
                                            ?>
                                        </p>
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-info btn-sm" onclick="viewProject('<?php echo htmlspecialchars($project['DT_MADT']); ?>')">
                                                <i class="fas fa-eye me-1"></i> Xem chi tiết
                                            </button>
                                            <div>
                                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $project['DT_MADT']; ?>">
                                                    <i class="fas fa-check me-1"></i> Phê duyệt
                                                </button>
                                                <button class="btn btn-danger btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $project['DT_MADT']; ?>">
                                                    <i class="fas fa-times me-1"></i> Từ chối
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal Phê duyệt -->
                                <div class="modal fade" id="approveModal<?php echo $project['DT_MADT']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">Phê duyệt đề tài</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="">
                                                <div class="modal-body">
                                                    <p>Bạn có chắc chắn muốn phê duyệt đề tài: <strong><?php echo htmlspecialchars($project['DT_TENDT']); ?></strong>?</p>
                                                    <div class="mb-3">
                                                        <label for="approveComments<?php echo $project['DT_MADT']; ?>" class="form-label">Nhận xét (không bắt buộc):</label>
                                                        <textarea class="form-control" id="approveComments<?php echo $project['DT_MADT']; ?>" name="comments" rows="3" placeholder="Nhập nhận xét về đề tài..."></textarea>
                                                    </div>
                                                    <input type="hidden" name="project_action" value="1">
                                                    <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="lecturer_id" value="<?php echo htmlspecialchars($project['GV_MAGV'] ?? ''); ?>">
                                                    <input type="hidden" name="project_title" value="<?php echo htmlspecialchars($project['DT_TENDT']); ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-check me-1"></i> Phê duyệt
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Từ chối -->
                                <div class="modal fade" id="rejectModal<?php echo $project['DT_MADT']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Từ chối đề tài</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="">
                                                <div class="modal-body">
                                                    <p>Bạn có chắc chắn muốn từ chối đề tài: <strong><?php echo htmlspecialchars($project['DT_TENDT']); ?></strong>?</p>
                                                    <div class="mb-3">
                                                        <label for="rejectComments<?php echo $project['DT_MADT']; ?>" class="form-label">Lý do từ chối <span class="text-danger">*</span>:</label>
                                                        <textarea class="form-control" id="rejectComments<?php echo $project['DT_MADT']; ?>" name="comments" rows="3" placeholder="Nhập lý do từ chối đề tài..." required></textarea>
                                                    </div>
                                                    <input type="hidden" name="project_action" value="1">
                                                    <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="lecturer_id" value="<?php echo htmlspecialchars($project['GV_MAGV'] ?? ''); ?>">
                                                    <input type="hidden" name="project_title" value="<?php echo htmlspecialchars($project['DT_TENDT']); ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-times me-1"></i> Từ chối
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h5>Không có đề tài nào cần phê duyệt!</h5>
                            <p>Tất cả đề tài đã được xử lý hoặc chưa có đề tài nào trong hệ thống.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Trước
                    </a>
                </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1' . 
                    (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                    (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                    '">1</a></li>';
                
                if ($start_page > 2) {
                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                    <a class="page-link" href="?page=' . $i . 
                    (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                    (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                    '">' . $i . '</a>
                </li>';
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                }
                
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . 
                    (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                    (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                    '">' . $total_pages . '</a></li>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                        Tiếp <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    </div> <!-- /.container-fluid -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript for page functionality -->
    <script>
    $(document).ready(function() {
        // Auto-dismiss alerts after 5 seconds
        $(".alert").delay(5000).fadeOut(500);
        
        // Form validation
        $("form").on("submit", function(e) {
            const form = $(this);
            const requiredFields = form.find("[required]");
            let isValid = true;
            
            requiredFields.each(function() {
                if ($(this).val().trim() === "") {
                    isValid = false;
                    $(this).addClass("is-invalid");
                } else {
                    $(this).removeClass("is-invalid");
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert("Vui lòng điền đầy đủ thông tin bắt buộc!");
            }
        });
        
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    function viewProject(projectId) {
        window.location.href = "view_project.php?id=" + projectId;
    }
    </script>

<?php
// Include footer if needed
// include '../../include/research_footer.php';
?>
