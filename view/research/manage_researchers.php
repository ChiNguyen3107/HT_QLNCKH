<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\manage_researchers.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Khởi tạo biến lọc và phân trang
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Tính statistics cho dashboard
$total_teachers_result = $conn->query("SELECT COUNT(*) as total FROM giang_vien WHERE 1=1");
$total_teachers = $total_teachers_result->fetch_assoc()['total'];

$total_students_result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE 1=1");
$total_students = $total_students_result->fetch_assoc()['total'];

$total_projects_result = $conn->query("SELECT COUNT(*) as total FROM de_tai_nghien_cuu WHERE 1=1");
$total_projects = $total_projects_result->fetch_assoc()['total'];

$total_faculties_result = $conn->query("SELECT COUNT(*) as total FROM khoa WHERE 1=1");
$total_faculties = $total_faculties_result->fetch_assoc()['total'];

// Xây dựng truy vấn SQL cho giảng viên
$teacher_sql = "SELECT gv.*, k.DV_TENDV, 
                COUNT(dt.DT_MADT) AS project_count 
                FROM giang_vien gv 
                LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
                LEFT JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                WHERE 1=1";

// Truy vấn SQL cho sinh viên
$student_sql = "SELECT sv.*, l.LOP_TEN, k.DV_TENDV, 
                COUNT(ct.DT_MADT) AS project_count 
                FROM sinh_vien sv 
                LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA 
                LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV 
                LEFT JOIN chi_tiet_tham_gia ct ON sv.SV_MASV = ct.SV_MASV
                WHERE 1=1";

// Điều kiện lọc khoa
if (!empty($faculty_filter)) {
    $teacher_sql .= " AND gv.DV_MADV = ?";
    $student_sql .= " AND l.DV_MADV = ?";
}

// Điều kiện tìm kiếm
if (!empty($search_term)) {
    $teacher_sql .= " AND (gv.GV_MAGV LIKE ? OR CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) LIKE ? OR gv.GV_EMAIL LIKE ?)";
    $student_sql .= " AND (sv.SV_MASV LIKE ? OR CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) LIKE ? OR sv.SV_EMAIL LIKE ?)";
}

// Thêm Group By và sắp xếp
$teacher_sql .= " GROUP BY gv.GV_MAGV ORDER BY project_count DESC, gv.GV_TENGV ASC";
$student_sql .= " GROUP BY sv.SV_MASV ORDER BY project_count DESC, sv.SV_TENSV ASC";

// Lấy tổng số bản ghi để phân trang
$teacher_count_sql = str_replace("SELECT gv.*, k.DV_TENDV, COUNT(dt.DT_MADT) AS project_count", 
                               "SELECT COUNT(DISTINCT gv.GV_MAGV) AS total", 
                               $teacher_sql);
$teacher_count_sql = preg_replace('/ORDER BY.*$/', '', $teacher_count_sql); // Loại bỏ ORDER BY

$student_count_sql = str_replace("SELECT sv.*, l.LOP_TEN, k.DV_TENDV, COUNT(ct.DT_MADT) AS project_count", 
                               "SELECT COUNT(DISTINCT sv.SV_MASV) AS total", 
                               $student_sql);
$student_count_sql = preg_replace('/ORDER BY.*$/', '', $student_count_sql); // Loại bỏ ORDER BY

// Chọn truy vấn dựa trên bộ lọc vai trò
if ($role_filter === 'student') {
    $main_sql = $student_sql . " LIMIT ?, ?";
    $count_sql = $student_count_sql;
} else {
    // Mặc định là giảng viên
    $main_sql = $teacher_sql . " LIMIT ?, ?";
    $count_sql = $teacher_count_sql;
    $role_filter = 'teacher'; // Đảm bảo role_filter có giá trị
}

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($main_sql);

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
if (!empty($params)) {
    $ref_params = array();
    $ref_params[] = &$types;
    foreach ($params as $key => $value) {
        $ref_params[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $ref_params);
}

$stmt->execute();
$result = $stmt->get_result();
$researchers = array();

while ($row = $result->fetch_assoc()) {
    $researchers[] = $row;
}

// Đếm tổng số bản ghi để phân trang
$stmt_count = $conn->prepare($count_sql);

if (!empty($params)) {
    $params_count = array_slice($params, 0, -2); // Bỏ 2 tham số offset và limit
    $types_count = substr($types, 0, -2); // Bỏ 2 kiểu ii của offset và limit
    
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
$row = $result_count->fetch_assoc();
$total_items = ($row && isset($row['total'])) ? $row['total'] : 0;
$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách các khoa
$faculty_sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV ASC";
$faculty_result = $conn->query($faculty_sql);
$faculties = array();
while ($row = $faculty_result->fetch_assoc()) {
    $faculties[] = $row;
}

?>

<?php
// Set page title
$page_title = "Quản lý nhà nghiên cứu | Hệ thống quản lý nghiên cứu";

// Additional CSS
$additional_css = '
<style>
    .filter-form {
        background-color: #f8f9fc;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .filter-form label {
        font-weight: 600;
        color: #4e73df;
    }
    
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    
    .researcher-card {
        transition: all 0.3s;
        border-radius: 0.5rem;
        margin-bottom: 20px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .researcher-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .researcher-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: #f8f9fc;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto;
        border: 5px solid #eaecf4;
        box-shadow: 0 0.15rem 0.25rem rgba(0, 0, 0, 0.1);
    }
    
    .researcher-avatar i {
        font-size: 40px;
        color: #4e73df;
    }
    
    .researcher-info {
        padding: 15px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .researcher-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #4e73df;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .researcher-meta {
        color: #858796;
        font-size: 0.85rem;
        margin-bottom: 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .researcher-stats {
        background-color: #f8f9fc;
        border-top: 1px solid #eaecf4;
        padding: 10px;
        margin-top: auto;
        border-radius: 0 0 0.5rem 0.5rem;
    }
    
    .role-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .role-teacher {
        background-color: #4e73df;
        color: white;
    }
    
    .role-student {
        background-color: #1cc88a;
        color: white;
    }
    
    .role-tabs {
        margin-bottom: 20px;
    }
    
    .role-tab {
        padding: 10px 20px;
        border-radius: 0.35rem;
        font-weight: 600;
        cursor: pointer;
        margin-right: 10px;
        border: 1px solid #eaecf4;
    }
    
    .role-tab.active {
        background-color: #4e73df;
        color: white;
        border-color: #4e73df;
    }
    
    /* Layout positioning - tương tự như dashboard và các file khác */
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
    
    /* Enhanced statistics cards */
    .card-counter {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        padding: 25px 15px;
        background-color: #fff;
        height: 120px;
        border-radius: 12px;
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
        border: none;
    }
    
    .card-counter:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .card-counter i {
        font-size: 4em;
        opacity: 0.2;
        position: absolute;
        right: 15px;
        bottom: -5px;
        transition: all 0.3s ease;
    }
    
    .card-counter:hover i {
        opacity: 0.3;
        transform: scale(1.1);
    }
    
    .card-counter .count-numbers {
        position: absolute;
        right: 35px;
        top: 20px;
        font-size: 2rem;
        font-weight: 700;
        display: block;
        color: white;
    }
    
    .card-counter .count-name {
        position: absolute;
        right: 35px;
        top: 65px;
        text-transform: uppercase;
        opacity: 0.9;
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        color: white;
    }
    
    .card-counter.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #FFF;
    }
    
    .card-counter.success {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
        color: #FFF;
    }
    
    .card-counter.info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
        color: #FFF;
    }
    
    .card-counter.warning {
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
        color: #FFF;
    }
    
    /* Enhanced cards */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        background: white;
        margin-bottom: 25px;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 20px;
        font-weight: 600;
    }
    
    .card-body {
        padding: 25px;
    }
    
    /* Enhanced tabs */
    .nav-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 20px;
    }
    
    .nav-tabs .nav-link {
        border: none;
        border-radius: 8px 8px 0 0;
        color: #5a5c69;
        font-weight: 600;
        padding: 12px 20px;
        margin-right: 5px;
        transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        background: #f8f9fc;
        color: #667eea;
    }
    
    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
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
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }
    
    /* Enhanced form controls */
    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 15px;
        transition: all 0.3s ease;
        font-size: 0.95em;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }
    
    /* Enhanced tables */
    .table {
        border-radius: 12px;
        overflow: hidden;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
        padding: 15px;
    }
    
    .table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table tbody tr:hover {
        background: #f8f9fc;
        transform: scale(1.01);
        transition: all 0.3s ease;
    }
    
    /* Enhanced badges */
    .badge {
        padding: 8px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .badge-success {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
    }
    
    .badge-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
    }
    
    .badge-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
    }
    
    /* Search and filter section */
    .search-filter-section {
        background: #f8f9fc;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
    }
    
    /* Pagination */
    .pagination {
        justify-content: center;
        margin-top: 25px;
    }
    
    .page-link {
        border: none;
        padding: 10px 15px;
        margin: 0 2px;
        border-radius: 8px;
        color: #667eea;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .page-link:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: transparent;
    }
    
    /* Animation classes */
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    }
    
    .animate-on-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    .fadeInUp {
        animation-name: fadeInUp;
        animation-duration: 0.5s;
        animation-fill-mode: both;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 20px 15px !important;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .card-counter {
            height: 110px;
            margin-bottom: 15px;
        }
        
        .card-counter .count-numbers {
            font-size: 1.5rem;
            right: 20px;
        }
        
        .card-counter .count-name {
            font-size: 0.75rem;
            right: 20px;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .table-responsive {
            border-radius: 12px;
        }
    }
    
    /* Researcher cards styles */
    .researcher-card {
        transition: all 0.3s ease;
        height: 100%;
        border: none;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .researcher-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .researcher-avatar {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        margin-bottom: 15px;
    }
    
    .researcher-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }
    
    .researcher-meta {
        font-size: 0.9rem;
        color: #718096;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }
    
    .role-teacher {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .role-student {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
        color: white;
    }
    
    .project-count {
        background: #f8f9fc;
        padding: 10px;
        border-radius: 8px;
        margin-top: 10px;
        border: 1px solid #e9ecef;
    }
    
    .project-count-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
    }
    
    .project-count-label {
        font-size: 0.85rem;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>
';

// Set page title for the header
$page_title = "Quản lý nhà nghiên cứu | Quản lý nghiên cứu";

// Include the research header (uses unified sidebar)
include '../../include/research_header.php';
?>

<!-- Main Content -->
<div id="content-wrapper" class="d-flex flex-column">
    <div class="container-fluid">
        <!-- Header với breadcrumb -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4 animate-on-scroll">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Quản lý nhà nghiên cứu</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quản lý nhà nghiên cứu</li>
                    </ol>
                </nav>
            </div>
            <a href="manage_projects.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                <i class="fas fa-folder fa-sm text-white-50"></i> Quản lý đề tài
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 animate-on-scroll">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-counter primary">
                    <div class="count-numbers"><?php echo $total_teachers; ?></div>
                    <div class="count-name">Giảng viên</div>
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-counter success">
                    <div class="count-numbers"><?php echo $total_students; ?></div>
                    <div class="count-name">Sinh viên</div>
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-counter info">
                    <div class="count-numbers"><?php echo $total_projects; ?></div>
                    <div class="count-name">Đề tài</div>
                    <i class="fas fa-folder"></i>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-counter warning">
                    <div class="count-numbers"><?php echo $total_faculties; ?></div>
                    <div class="count-name">Khoa</div>
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>

        <!-- Modern Navigation Tabs -->
        <div class="card animate-on-scroll">
            <div class="card-body">
                <ul class="nav nav-tabs" id="roleTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $role_filter === 'teacher' || empty($role_filter) ? 'active' : ''; ?>" 
                           href="?role=teacher<?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"
                           data-role="teacher">
                            <i class="fas fa-chalkboard-teacher mr-2"></i> Giảng viên
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $role_filter === 'student' ? 'active' : ''; ?>" 
                           href="?role=student<?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"
                           data-role="student">
                            <i class="fas fa-user-graduate mr-2"></i> Sinh viên
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Enhanced Search and Filter Section -->
        <div class="card shadow-sm mb-4 animate-on-scroll">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm và bộ lọc
                </h6>
            </div>
            <div class="card-body search-filter-section">
                <form method="GET" action="" class="filter-form">
                    <input type="hidden" name="role" value="<?php echo $role_filter; ?>">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="faculty" class="form-label">
                                <i class="fas fa-building mr-1"></i>Khoa/Đơn vị
                            </label>
                            <select class="form-select form-control" id="faculty" name="faculty">
                                <option value="">Tất cả khoa</option>
                                <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty['DV_MADV']); ?>" 
                                        <?php echo $faculty_filter == $faculty['DV_MADV'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['DV_TENDV']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="search" class="form-label">
                                <i class="fas fa-search mr-1"></i>Tìm kiếm
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Nhập mã, tên, email để tìm kiếm..." 
                                       value="<?php echo htmlspecialchars($search_term); ?>">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter mr-1"></i>Lọc
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quick actions -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group" role="group">
                                    <a href="?<?php echo http_build_query(array_filter(['role' => $role_filter])); ?>" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-refresh mr-1"></i>Làm mới
                                    </a>
                                    <a href="export_researchers.php?role=<?php echo $role_filter; ?><?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-file-excel mr-1"></i>Xuất Excel
                                    </a>
                                </div>
                                
                                <!-- Results info -->
                                <small class="text-muted">
                                    <?php if ($role_filter === 'teacher'): ?>
                                        Hiển thị <?php echo count($researchers); ?> / <?php echo $total_teachers; ?> giảng viên
                                    <?php elseif ($role_filter === 'student'): ?>
                                        Hiển thị <?php echo count($researchers); ?> / <?php echo $total_students; ?> sinh viên  
                                    <?php else: ?>
                                        Hiển thị <?php echo count($researchers); ?> nhà nghiên cứu
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Display researchers -->
                    <div class="row">
                        <?php if (count($researchers) > 0): ?>
                            <?php foreach ($researchers as $researcher): ?>
                                <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                                    <div class="card researcher-card">
                                        <div class="card-body text-center">
                                            <div class="researcher-avatar">
                                                <?php if ($role_filter === 'teacher'): ?>
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user-graduate"></i>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="researcher-name">
                                                <?php 
                                                if ($role_filter === 'teacher') {
                                                    echo htmlspecialchars($researcher['GV_HOGV'] . ' ' . $researcher['GV_TENGV']);
                                                } else {
                                                    echo htmlspecialchars($researcher['SV_HOSV'] . ' ' . $researcher['SV_TENSV']);
                                                }
                                                ?>
                                            </div>
                                            
                                            <span class="role-badge <?php echo $role_filter === 'teacher' ? 'role-teacher' : 'role-student'; ?>">
                                                <?php echo $role_filter === 'teacher' ? 'Giảng viên' : 'Sinh viên'; ?>
                                            </span>
                                            
                                            <div class="researcher-meta">
                                                <i class="fas fa-id-card mr-1"></i>
                                                <span><?php echo htmlspecialchars($role_filter === 'teacher' ? $researcher['GV_MAGV'] : $researcher['SV_MASV']); ?></span>
                                            </div>
                                            
                                            <div class="researcher-meta">
                                                <i class="fas fa-envelope mr-1"></i>
                                                <span><?php echo htmlspecialchars($role_filter === 'teacher' ? $researcher['GV_EMAIL'] : $researcher['SV_EMAIL']); ?></span>
                                            </div>
                                            
                                            <div class="researcher-meta">
                                                <i class="fas fa-university mr-1"></i>
                                                <span><?php echo htmlspecialchars($researcher['DV_TENDV']); ?></span>
                                                <?php if ($role_filter === 'student'): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($researcher['LOP_TEN']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="project-count">
                                                <div class="project-count-number">
                                                    <?php echo $researcher['project_count']; ?>
                                                </div>
                                                <div class="project-count-label">
                                                    Đề tài tham gia
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-center mt-3">
                                                <a href="researcher_details.php?role=<?php echo $role_filter; ?>&id=<?php echo htmlspecialchars($role_filter === 'teacher' ? $researcher['GV_MAGV'] : $researcher['SV_MASV']); ?>" 
                                                   class="btn btn-primary btn-sm mx-1">
                                                    <i class="fas fa-info-circle mr-1"></i>Chi tiết
                                                </a>
                                                <a href="researcher_projects.php?role=<?php echo $role_filter; ?>&id=<?php echo htmlspecialchars($role_filter === 'teacher' ? $researcher['GV_MAGV'] : $researcher['SV_MASV']); ?>" 
                                                   class="btn btn-info btn-sm mx-1">
                                                    <i class="fas fa-folder mr-1"></i>Đề tài
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="card text-center animate-on-scroll">
                                    <div class="card-body py-5">
                                        <div class="mb-4">
                                            <i class="fas fa-search fa-3x text-muted"></i>
                                        </div>
                                        <h4 class="text-muted">Không tìm thấy nhà nghiên cứu</h4>
                                        <p class="text-muted mb-4">
                                            Không có nhà nghiên cứu nào phù hợp với tiêu chí tìm kiếm của bạn.
                                        </p>
                                        <div class="btn-group" role="group">
                                            <a href="?role=<?php echo $role_filter; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-refresh mr-1"></i>Làm mới bộ lọc
                                            </a>
                                            <a href="?" class="btn btn-primary">
                                                <i class="fas fa-list mr-1"></i>Xem tất cả
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Phân trang -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?role=<?php echo $role_filter; ?>&page=<?php echo $page - 1; ?><?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                        Trước
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?role=' . $role_filter . '&page=1' . 
                                    (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                                    (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                                    '">1</a></li>';
                                
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                    <a class="page-link" href="?role=' . $role_filter . '&page=' . $i . 
                                    (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                                    (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                                    '">' . $i . '</a>
                                </li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                                
                                echo '<li class="page-item"><a class="page-link" href="?role=' . $role_filter . '&page=' . $total_pages . 
                                    (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                                    (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                                    '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?role=<?php echo $role_filter; ?>&page=<?php echo $page + 1; ?><?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                        Tiếp
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Animation on scroll
    function animateOnScroll() {
        $('.animate-on-scroll').each(function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('visible');
            }
        });
    }
    
    // Run animation on scroll
    $(window).on('scroll resize', animateOnScroll);
    animateOnScroll(); // Run on page load
    
    // Enhanced hover effects
    $('.card').hover(
        function() {
            $(this).addClass('shadow-lg');
        },
        function() {
            $(this).removeClass('shadow-lg');
        }
    );
    
    // Smooth scrolling for internal links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 1000);
        }
    });
    
    // Enhanced form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        
        $(this).find('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
                
                // Add error message if not exists
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Trường này là bắt buộc.</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Vui lòng điền đầy đủ thông tin bắt buộc.',
                confirmButtonColor: '#667eea'
            });
        }
    });
    
    // Clear validation on input
    $('input, select').on('input change', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Role tab switching
    $('.role-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update active state
        $('.role-tab').removeClass('active');
        $(this).addClass('active');
        
        // Update URL and reload
        const role = $(this).data('role');
        const url = new URL(window.location);
        
        if (role === 'all') {
            url.searchParams.delete('role');
        } else {
            url.searchParams.set('role', role);
        }
        url.searchParams.delete('page'); // Reset to first page
        
        window.location.href = url.toString();
    });
    
    // Search functionality with debouncing
    let searchTimeout;
    $('input[name="search"]').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            const url = new URL(window.location);
            
            if (searchTerm.trim()) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page'); // Reset to first page
            
            window.location.href = url.toString();
        }, 500);
    });
    
    // Faculty filter
    $('select[name="faculty"]').on('change', function() {
        const faculty = $(this).val();
        const url = new URL(window.location);
        
        if (faculty) {
            url.searchParams.set('faculty', faculty);
        } else {
            url.searchParams.delete('faculty');
        }
        url.searchParams.delete('page'); // Reset to first page
        
        window.location.href = url.toString();
    });
    
    // Add fadeIn animation to newly loaded content
    $('.card, .table, .btn').addClass('fadeInUp');
    
    // Counter animation
    $('.count-numbers').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.text().replace(/,/g, ''));
        
        $({ countNum: 0 }).animate(
            { countNum: countTo },
            {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    $this.text(Math.floor(this.countNum).toLocaleString());
                },
                complete: function() {
                    $this.text(countTo.toLocaleString());
                }
            }
        );
    });
});
</script>

<?php include '../../include/research_footer.php'; ?>
