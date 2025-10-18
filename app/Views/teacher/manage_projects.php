<?php
// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên hiện tại
$teacher_id = $_SESSION['user_id'];

// Khởi tạo biến lọc
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$scope_filter = isset($_GET['scope']) ? $_GET['scope'] : 'my'; // Mặc định chỉ hiển thị đề tài của giảng viên

// Xây dựng truy vấn SQL với điều kiện lọc
// Sửa gv.GV_HOTEN thành CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) để ghép họ và tên
$sql = "SELECT dt.*, ldt.LDT_TENLOAI, lvnc.LVNC_TEN, lvut.LVUT_TEN, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as gv_ten 
        FROM de_tai_nghien_cuu dt 
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV";

// Thêm điều kiện lọc theo phạm vi
if ($scope_filter == 'my') {
    $sql .= " WHERE dt.GV_MAGV = ?";
} else {
    $sql .= " WHERE 1=1"; // 1=1 luôn đúng, để dễ dàng thêm các điều kiện AND sau này
}

// Thêm điều kiện lọc theo trạng thái nếu có
if (!empty($status_filter)) {
    $sql .= " AND dt.DT_TRANGTHAI = ?";
}

// Thêm điều kiện lọc theo loại đề tài nếu có
if (!empty($type_filter)) {
    $sql .= " AND dt.LDT_MA = ?";
}

// Thêm điều kiện tìm kiếm nếu có
if (!empty($search_term)) {
    $sql .= " AND (dt.DT_TENDT LIKE ? OR dt.DT_MADT LIKE ?)";
}

$sql .= " ORDER BY dt.DT_MADT DESC";

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}

// Gán giá trị cho các tham số
$param_types = ""; // Chuỗi các loại tham số
$param_values = array();

// Chỉ thêm điều kiện giảng viên nếu phạm vi là 'my'
if ($scope_filter == 'my') {
    $param_types .= "s";
    $param_values[] = $teacher_id;
}

if (!empty($status_filter)) {
    $param_types .= "s";
    $param_values[] = $status_filter;
}

if (!empty($type_filter)) {
    $param_types .= "s";
    $param_values[] = $type_filter;
}

if (!empty($search_term)) {
    $param_types .= "ss";
    $search_param = "%{$search_term}%";
    $param_values[] = $search_param;
    $param_values[] = $search_param;
}

// Sử dụng call_user_func_array để truyền các tham số động cho bind_param
if (!empty($param_values)) {
    $refs = array();
    foreach ($param_values as $key => $value) {
        $refs[$key] = &$param_values[$key];
    }
    array_unshift($refs, $param_types);
    call_user_func_array(array($stmt, 'bind_param'), $refs);
}

$stmt->execute();
$result = $stmt->get_result();

// Lấy danh sách loại đề tài để hiển thị trong bộ lọc
$types_query = "SELECT * FROM loai_de_tai ORDER BY LDT_TENLOAI ASC";
$types_result = $conn->query($types_query);

// Hàm lấy số lượng sinh viên tham gia đề tài
function getStudentCount($conn, $project_id) {
    $sql = "SELECT COUNT(*) as count FROM chi_tiet_tham_gia WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Lỗi SQL (chi_tiet_tham_gia): " . $conn->error);
        return 0;
    }
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Hàm lấy số lượng báo cáo của đề tài
function getReportCount($conn, $project_id) {
    $sql = "SELECT COUNT(*) as count FROM bao_cao WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Bảng bao_cao không tồn tại, trả về 0
        error_log("Lỗi SQL (bao_cao): " . $conn->error);
        return 0;
    }
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Hàm lấy thông tin hợp đồng của đề tài
function getContractInfo($conn, $project_id) {
    $sql = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Lỗi SQL (hop_dong): " . $conn->error);
        return null;
    }
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Hàm lấy tiến độ mới nhất của đề tài
function getLatestProgress($conn, $project_id) {
    $sql = "SELECT * FROM tien_do_de_tai WHERE DT_MADT = ? ORDER BY TDDT_NGAYCAPNHAT DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Lỗi SQL (tien_do_de_tai): " . $conn->error);
        return null;
    }
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Xử lý thông báo thành công hoặc lỗi
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đề tài | Giảng viên</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    
    <!-- Custom fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap CSS từ CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS từ CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS từ CDN -->
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
        /* Enhanced Project Cards */
        .project-card {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: none;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fc 100%);
        }
        
        .project-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(78, 115, 223, 0.15);
        }
        
        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4e73df, #224abe);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .project-card:hover::before {
            opacity: 1;
        }
        
        /* Enhanced Status Badges */
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .status-badge:hover::before {
            left: 100%;
        }
        
        /* Enhanced Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #f8f9fc 0%, #e8f4fe 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(78, 115, 223, 0.1);
        }
        
        .filter-section .form-control {
            border-radius: 10px;
            border: 2px solid #e3e6f0;
            transition: all 0.3s ease;
            padding: 8px 12px;
        }
        
        .filter-section .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            transform: translateY(-1px);
        }
        
        .filter-section label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        /* Enhanced Project Meta */
        .project-meta {
            font-size: 0.85rem;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            background: rgba(108, 117, 125, 0.1);
            border-radius: 15px;
            margin-right: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .project-meta:hover {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            transform: scale(1.05);
        }
        
        .project-meta i {
            margin-right: 5px;
        }
        
        /* Enhanced Progress Bar */
        .progress {
            height: 12px;
            border-radius: 10px;
            background: rgba(0,0,0,0.05);
            overflow: visible;
            position: relative;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            border-radius: 10px;
            position: relative;
            background: linear-gradient(90deg, #28a745, #20c997);
            animation: progressShine 2s ease-in-out infinite;
        }
        
        @keyframes progressShine {
            0%, 100% { 
                box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
            }
            50% { 
                box-shadow: 0 0 20px rgba(40, 167, 69, 0.6);
            }
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, .2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, .2) 50%,
                rgba(255, 255, 255, .2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
            animation: progressSlide 1s linear infinite;
        }
        
        @keyframes progressSlide {
            0% { background-position-x: 1rem; }
            100% { background-position-x: 0; }
        }
        
        /* Enhanced Project Advisor */
        .project-advisor {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: linear-gradient(135deg, #e8f4fe 0%, #d1ecf1 100%);
            border-radius: 20px;
            font-size: 0.8rem;
            color: #4e73df;
            margin-top: 8px;
            border: 1px solid rgba(78, 115, 223, 0.2);
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .project-advisor:hover {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            transform: scale(1.05);
        }
        
        .project-advisor i {
            margin-right: 5px;
        }
        
        /* Enhanced My Project Highlight */
        .my-project {
            border-left: 5px solid #4e73df;
            position: relative;
        }
        
        .my-project::after {
            content: 'CỦA BẠN';
            position: absolute;
            top: 2px;
            right: 10px;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: bold;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* Enhanced Card Headers */
        .card-header {
            background: linear-gradient(135deg, transparent 0%, rgba(78, 115, 223, 0.05) 100%) !important;
            border-bottom: 2px solid rgba(78, 115, 223, 0.1) !important;
            padding: 15px 20px;
        }
        
        /* Enhanced Card Body */
        .card-body {
            padding: 20px;
        }
        
        .card-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .card-title a {
            transition: all 0.3s ease;
            text-decoration: none !important;
        }
        
        .card-title a:hover {
            color: #4e73df !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Enhanced Buttons */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        /* Statistics Card Enhancement */
        .stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4e73df, #224abe);
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Enhanced Alert Messages */
        .alert {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
        }
        
        /* Page Header Enhancement */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            color: white !important;
            margin-bottom: 5px;
        }
        
        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .project-card {
                margin-bottom: 20px;
            }
            
            .filter-section {
                padding: 15px;
            }
            
            .project-card:hover {
                transform: translateY(-4px) scale(1.01);
            }
            
            .my-project::after {
                font-size: 0.6rem;
                padding: 2px 6px;
            }
            
            .btn-group .btn {
                padding: 4px 8px;
                font-size: 0.8rem;
            }
        }
        
        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #e3e6f0;
            border-top: 5px solid #4e73df;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include '../../include/teacher_sidebar.php'; ?>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid mt-4">
                    <!-- Page Heading -->
                    <div class="page-header">
                        <div class="d-sm-flex align-items-center justify-content-between">
                            <div>
                                <h1 class="h3 mb-0">
                                    <i class="fas fa-project-diagram mr-2"></i>Quản lý đề tài nghiên cứu khoa học
                                </h1>
                                <p class="mt-1 mb-0">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Quản lý và theo dõi tiến độ các đề tài nghiên cứu một cách hiệu quả
                                </p>
                            </div>
                            <div class="btn-group">
                                <a href="create_project.php" class="btn btn-light shadow-sm">
                                    <i class="fas fa-plus mr-1"></i>Tạo đề tài mới
                                </a>
                                <button type="button" class="btn btn-outline-light dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="import_projects.php">
                                        <i class="fas fa-upload mr-2 text-primary"></i>Nhập từ Excel
                                    </a>
                                    <a class="dropdown-item" href="export_projects.php">
                                        <i class="fas fa-download mr-2 text-success"></i>Xuất báo cáo
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="project_templates.php">
                                        <i class="fas fa-file-alt mr-2 text-info"></i>Mẫu đề tài
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hiển thị thông báo nếu có -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filter Section -->
                    <div class="filter-section mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-filter mr-2"></i>Bộ lọc nâng cao
                            </h5>
                            <div class="d-flex">
                                <span class="badge badge-info mr-2">
                                    <i class="fas fa-keyboard mr-1"></i>Ctrl+F để tìm kiếm nhanh
                                </span>
                                <button class="btn btn-sm btn-outline-secondary" onclick="resetAllFilters()">
                                    <i class="fas fa-undo mr-1"></i>Đặt lại tất cả
                                </button>
                            </div>
                        </div>
                        
                        <form method="get" action="" id="filterForm">
                            <div class="row">
                                <!-- Phạm vi đề tài -->
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <label for="scope">
                                        <i class="fas fa-eye mr-1 text-primary"></i>Phạm vi đề tài:
                                    </label>
                                    <select name="scope" id="scope" class="form-control" onchange="this.form.submit()">
                                        <option value="my" <?php echo ($scope_filter == 'my') ? 'selected' : ''; ?>>
                                            🔒 Đề tài của tôi
                                        </option>
                                        <option value="all" <?php echo ($scope_filter == 'all') ? 'selected' : ''; ?>>
                                            🌐 Tất cả đề tài
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Trạng thái -->
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <label for="status">
                                        <i class="fas fa-tasks mr-1 text-success"></i>Trạng thái:
                                    </label>
                                    <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                                        <option value="">📋 Tất cả trạng thái</option>
                                        <option value="Chờ duyệt" <?php echo ($status_filter == 'Chờ duyệt') ? 'selected' : ''; ?>>
                                            ⏳ Chờ duyệt
                                        </option>
                                        <option value="Đang thực hiện" <?php echo ($status_filter == 'Đang thực hiện') ? 'selected' : ''; ?>>
                                            🔄 Đang thực hiện
                                        </option>
                                        <option value="Đã hoàn thành" <?php echo ($status_filter == 'Đã hoàn thành') ? 'selected' : ''; ?>>
                                            ✅ Đã hoàn thành
                                        </option>
                                        <option value="Tạm dừng" <?php echo ($status_filter == 'Tạm dừng') ? 'selected' : ''; ?>>
                                            ⏸️ Tạm dừng
                                        </option>
                                        <option value="Đã hủy" <?php echo ($status_filter == 'Đã hủy') ? 'selected' : ''; ?>>
                                            ❌ Đã hủy
                                        </option>
                                        <option value="Đang xử lý" <?php echo ($status_filter == 'Đang xử lý') ? 'selected' : ''; ?>>
                                            🔄 Đang xử lý
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Loại đề tài -->
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                    <label for="type">
                                        <i class="fas fa-tag mr-1 text-info"></i>Loại đề tài:
                                    </label>
                                    <select name="type" id="type" class="form-control" onchange="this.form.submit()">
                                        <option value="">🏷️ Tất cả loại đề tài</option>
                                        <?php if ($types_result && $types_result->num_rows > 0): ?>
                                            <?php while ($type = $types_result->fetch_assoc()): ?>
                                                <option value="<?php echo $type['LDT_MA']; ?>" <?php echo ($type_filter == $type['LDT_MA']) ? 'selected' : ''; ?>>
                                                    <?php echo $type['LDT_TENLOAI']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <!-- Tìm kiếm -->
                                <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                                    <label for="search">
                                        <i class="fas fa-search mr-1 text-warning"></i>Tìm kiếm:
                                    </label>
                                    <div class="input-group">
                                        <input type="text" name="search" id="search" class="form-control" 
                                               placeholder="🔍 Tên đề tài hoặc mã đề tài..." 
                                               value="<?php echo htmlspecialchars($search_term); ?>">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Nút đặt lại -->
                                <div class="col-lg-2 col-md-6 col-sm-6 mb-3 d-flex align-items-end">
                                    <a href="manage_projects.php" class="btn btn-outline-secondary btn-block">
                                        <i class="fas fa-sync-alt mr-1"></i>Đặt lại
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Thống kê nhanh và View Options -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="card stats-card shadow-sm">
                                <div class="card-body py-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <p class="mb-0 text-primary font-weight-bold">
                                                <i class="fas fa-chart-bar mr-2"></i>
                                                Đang hiển thị: <span class="badge badge-primary badge-pill"><?php echo $result->num_rows; ?></span> đề tài
                                                <?php if ($scope_filter == 'all'): ?>
                                                <span class="text-muted small">(tất cả giảng viên)</span>
                                                <?php else: ?>
                                                <span class="text-muted small">(của bạn)</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-end">
                                                <div class="project-meta mr-2">
                                                    <i class="fas fa-clock"></i>
                                                    Cập nhật: <?php echo date('d/m/Y H:i'); ?>
                                                </div>
                                                <div class="project-meta">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Giảng viên'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card stats-card shadow-sm">
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted font-weight-bold">
                                            <i class="fas fa-eye mr-1"></i>Tùy chọn hiển thị:
                                        </span>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary active" id="gridViewBtn" onclick="switchView('grid')">
                                                <i class="fas fa-th"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" id="listViewBtn" onclick="switchView('list')">
                                                <i class="fas fa-list"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" onclick="toggleSort()">
                                                <i class="fas fa-sort" id="sortIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Projects List -->
                    <div id="projectsContainer">
                        <div class="row" id="projectsGrid">
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($project = $result->fetch_assoc()) {
                                    // Lấy thông tin bổ sung
                                    $student_count = getStudentCount($conn, $project['DT_MADT']);
                                    $report_count = getReportCount($conn, $project['DT_MADT']);
                                    $contract_info = getContractInfo($conn, $project['DT_MADT']);
                                    $latest_progress = getLatestProgress($conn, $project['DT_MADT']);
                                    $progress_percent = $latest_progress ? $latest_progress['TDDT_PHANTRAMHOANTHANH'] : 0;
                                    
                                    // Kiểm tra có phải đề tài của giảng viên đang đăng nhập không
                                    $is_my_project = ($project['GV_MAGV'] == $teacher_id);
                                    
                                    // Xác định màu cho badge trạng thái và icon
                                    $status_class = '';
                                    $status_icon = '';
                                    switch ($project['DT_TRANGTHAI']) {
                                        case 'Chờ duyệt':
                                            $status_class = 'badge-warning';
                                            $status_icon = 'fas fa-hourglass-half';
                                            break;
                                        case 'Đang thực hiện':
                                            $status_class = 'badge-primary';
                                            $status_icon = 'fas fa-cog fa-spin';
                                            break;
                                        case 'Đã hoàn thành':
                                            $status_class = 'badge-success';
                                            $status_icon = 'fas fa-check-circle';
                                            break;
                                        case 'Tạm dừng':
                                            $status_class = 'badge-info';
                                            $status_icon = 'fas fa-pause-circle';
                                            break;
                                        case 'Đã hủy':
                                            $status_class = 'badge-danger';
                                            $status_icon = 'fas fa-times-circle';
                                            break;
                                        case 'Đang xử lý':
                                            $status_class = 'badge-secondary';
                                            $status_icon = 'fas fa-sync-alt fa-spin';
                                            break;
                                        default:
                                            $status_class = 'badge-dark';
                                            $status_icon = 'fas fa-question-circle';
                                    }
                                    ?>
                                    <div class="col-xl-4 col-lg-6 col-md-12 mb-4" data-project-id="<?php echo $project['DT_MADT']; ?>">
                                        <div class="card project-card h-100 shadow-sm <?php echo $is_my_project ? 'my-project' : ''; ?>">
                                            <div class="card-header bg-transparent">
                                                <!-- Top Row: Status Badge & Actions -->
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <i class="<?php echo $status_icon; ?> mr-1"></i>
                                                        <?php echo $project['DT_TRANGTHAI']; ?>
                                                    </span>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-light border-0" type="button" data-toggle="dropdown" title="Tùy chọn">
                                                            <i class="fas fa-ellipsis-h text-muted"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right shadow-lg border-0" style="border-radius: 10px;">
                                                            <div class="dropdown-header bg-light">
                                                                <i class="fas fa-cog mr-1"></i>Thao tác
                                                            </div>
                                                            <a class="dropdown-item" href="view_project.php?id=<?php echo $project['DT_MADT']; ?>">
                                                                <i class="fas fa-eye mr-2 text-primary"></i>Xem chi tiết
                                                            </a>
                                                            <?php if ($is_my_project): ?>
                                                            <a class="dropdown-item" href="edit_project.php?id=<?php echo $project['DT_MADT']; ?>">
                                                                <i class="fas fa-edit mr-2 text-warning"></i>Chỉnh sửa
                                                            </a>
                                                            <a class="dropdown-item" href="progress_project.php?id=<?php echo $project['DT_MADT']; ?>">
                                                                <i class="fas fa-chart-line mr-2 text-info"></i>Cập nhật tiến độ
                                                            </a>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item" href="duplicate_project.php?id=<?php echo $project['DT_MADT']; ?>">
                                                                <i class="fas fa-copy mr-2 text-info"></i>Nhân bản
                                                            </a>
                                                            <a class="dropdown-item" href="export_project.php?id=<?php echo $project['DT_MADT']; ?>">
                                                                <i class="fas fa-download mr-2 text-success"></i>Xuất file
                                                            </a>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item text-danger" href="delete_project.php?id=<?php echo $project['DT_MADT']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa đề tài này?')">
                                                                <i class="fas fa-trash mr-2"></i>Xóa đề tài
                                                            </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Middle Row: Project Type & Priority -->
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge badge-light border" style="font-size: 0.75rem; padding: 4px 8px;">
                                                        <i class="fas fa-tag mr-1 text-primary"></i>
                                                        <?php echo $project['LDT_TENLOAI']; ?>
                                                    </span>
                                                    
                                                    <?php if ($project['LVUT_TEN']): ?>
                                                    <span class="badge badge-warning" style="font-size: 0.7rem;">
                                                        <i class="fas fa-star mr-1"></i>
                                                        Ưu tiên
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Bottom Row: Quick Stats -->
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex">
                                                        <?php if (isset($project['DT_NGAYBATDAU']) && $project['DT_NGAYBATDAU']): ?>
                                                        <span class="badge badge-outline-primary mr-1" style="font-size: 0.7rem; background: rgba(78, 115, 223, 0.1); color: #4e73df; border: 1px solid rgba(78, 115, 223, 0.2);">
                                                            <i class="fas fa-calendar-alt mr-1"></i>
                                                            <?php echo date('d/m/Y', strtotime($project['DT_NGAYBATDAU'])); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                        <?php if (isset($project['DT_NGAYKETTHUC']) && $project['DT_NGAYKETTHUC']): ?>
                                                        <span class="badge badge-outline-danger" style="font-size: 0.7rem; background: rgba(231, 74, 59, 0.1); color: #e74a3b; border: 1px solid rgba(231, 74, 59, 0.2);">
                                                            <i class="fas fa-flag-checkered mr-1"></i>
                                                            <?php echo date('d/m/Y', strtotime($project['DT_NGAYKETTHUC'])); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="text-muted" style="font-size: 0.7rem;">
                                                        ID: <?php echo substr($project['DT_MADT'], -6); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>" class="text-decoration-none text-primary">
                                                        <?php echo htmlspecialchars($project['DT_TENDT']); ?>
                                                    </a>
                                                </h5>
                                                <p class="card-text text-muted small mb-3">
                                                    <i class="fas fa-barcode mr-1"></i>Mã: <?php echo $project['DT_MADT']; ?>
                                                </p>
                                                
                                                <?php if (!$is_my_project && $scope_filter == 'all'): ?>
                                                <div class="project-advisor mb-3">
                                                    <i class="fas fa-user-tie mr-1"></i> <?php echo htmlspecialchars($project['gv_ten']); ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="mb-3">
                                                    <div class="project-meta">
                                                        <i class="fas fa-users"></i>
                                                        <?php echo $student_count; ?> sinh viên
                                                    </div>
                                                    <div class="project-meta">
                                                        <i class="fas fa-file-alt"></i>
                                                        <?php echo $report_count; ?> báo cáo
                                                    </div>
                                                    <?php if ($contract_info): ?>
                                                    <div class="project-meta">
                                                        <i class="fas fa-file-contract"></i>
                                                        Có hợp đồng
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="small font-weight-bold">
                                                            <i class="fas fa-chart-line mr-1 text-primary"></i>Tiến độ:
                                                        </span>
                                                        <span class="small font-weight-bold text-primary"><?php echo $progress_percent; ?>%</span>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress_percent; ?>%" 
                                                            aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="project-details">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <p class="card-text mb-1 small text-muted">
                                                                <i class="fas fa-flask mr-1"></i>Lĩnh vực:
                                                            </p>
                                                            <span class="badge badge-light"><?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Chưa xác định'); ?></span>
                                                        </div>
                                                        <div class="col-6">
                                                            <p class="card-text mb-1 small text-muted">
                                                                <i class="fas fa-star mr-1"></i>Ưu tiên:
                                                            </p>
                                                            <span class="badge badge-light"><?php echo htmlspecialchars($project['LVUT_TEN'] ?? 'Thường'); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-flex justify-content-between">
                                                    <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-info-circle mr-1"></i>Chi tiết
                                                    </a>
                                                    <?php if ($is_my_project): ?>
                                                    <div class="btn-group">
                                                        <a href="edit_project.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-outline-secondary" title="Chỉnh sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="manage_students.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-outline-info" title="Quản lý sinh viên">
                                                            <i class="fas fa-user-graduate"></i>
                                                        </a>
                                                        <a href="project_reports.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-outline-success" title="Báo cáo">
                                                            <i class="fas fa-file-alt"></i>
                                                        </a>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="btn-group">
                                                        <span class="badge badge-light">
                                                            <i class="fas fa-eye"></i> Chỉ xem
                                                        </span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                            } else {
                                echo '<div class="col-12">';
                                echo '<div class="card shadow-sm" style="border-radius: 15px;">';
                                echo '<div class="card-body text-center py-5">';
                                echo '<i class="fas fa-search fa-3x text-muted mb-3"></i>';
                                echo '<h5 class="text-muted">Không tìm thấy đề tài nào</h5>';
                                echo '<p class="text-muted">Không có đề tài nào phù hợp với điều kiện lọc hiện tại.</p>';
                                echo '<div class="mt-3">';
                                echo '<a href="manage_projects.php" class="btn btn-outline-primary mr-2">Xóa bộ lọc</a>';
                                echo '<a href="create_project.php" class="btn btn-primary">Tạo đề tài mới</a>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Hệ thống quản lý nghiên cứu khoa học &copy; <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    
    <!-- SB Admin 2 JS từ CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>
    
    <!-- DataTables JS từ CDN -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        // Enhanced Project Management JavaScript
        let currentView = 'grid';
        let sortOrder = 'desc';
        
        $(document).ready(function() {
            // Initialize enhanced features
            initializeEnhancedFeatures();
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert-dismissible').alert('close');
            }, 5000);
            
            // Initialize tooltips
            $('[title]').tooltip();
            
            // Load saved preferences
            loadSavedPreferences();
            
            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 100
                    }, 1000);
                }
            });
        });
        
        function initializeEnhancedFeatures() {
            // Enhanced search with debounce
            let searchTimeout;
            $('#search').on('input', function() {
                clearTimeout(searchTimeout);
                const searchValue = $(this).val();
                
                searchTimeout = setTimeout(function() {
                    if (searchValue.length >= 2 || searchValue.length === 0) {
                        showLoading();
                        $('#filterForm').submit();
                    }
                }, 500);
                
                // Real-time search highlight (optional)
                if (searchValue.length > 0) {
                    highlightSearchTerms(searchValue);
                }
            });
            
            // Enhanced form submission with loading
            $('#filterForm').on('submit', function() {
                showLoading();
                // Save current filter state
                saveFilterPreferences();
            });
            
            // Keyboard shortcuts
            $(document).keydown(function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.keyCode) {
                        case 70: // Ctrl+F - Focus search
                            e.preventDefault();
                            $('#search').focus().select();
                            break;
                        case 78: // Ctrl+N - New project
                            e.preventDefault();
                            window.location.href = 'create_project.php';
                            break;
                        case 82: // Ctrl+R - Reset filters
                            e.preventDefault();
                            resetAllFilters();
                            break;
                    }
                }
                
                // ESC key to clear search
                if (e.keyCode === 27 && $('#search').is(':focus')) {
                    $('#search').val('').trigger('input');
                }
            });
            
            // Enhanced hover effects for project cards
            $(document).on('mouseenter', '.project-card', function() {
                $(this).find('.status-badge').addClass('animate__animated animate__pulse');
            });
            
            $(document).on('mouseleave', '.project-card', function() {
                $(this).find('.status-badge').removeClass('animate__animated animate__pulse');
            });
        }
        
        function switchView(viewType) {
            showLoading();
            
            // Update active button
            $('.btn-group button').removeClass('active');
            $(`#${viewType}ViewBtn`).addClass('active');
            
            currentView = viewType;
            
            setTimeout(function() {
                if (viewType === 'list') {
                    // Switch to list view
                    $('.col-xl-4').removeClass('col-xl-4 col-lg-6').addClass('col-12');
                    $('.project-card').addClass('mb-2').removeClass('h-100');
                    
                    // Modify card structure for list view
                    $('.project-card .card-body').each(function() {
                        const $cardBody = $(this);
                        const $card = $cardBody.closest('.project-card');
                        
                        // Restructure for horizontal layout
                        $card.find('.card-body, .card-footer').addClass('d-flex align-items-center');
                    });
                    
                } else {
                    // Switch back to grid view
                    $('.col-12').removeClass('col-12').addClass('col-xl-4 col-lg-6');
                    $('.project-card').removeClass('mb-2').addClass('h-100');
                    
                    // Restore card structure
                    $('.project-card .card-body, .project-card .card-footer').removeClass('d-flex align-items-center');
                }
                
                hideLoading();
                
                // Save preference
                localStorage.setItem('projectViewPreference', viewType);
            }, 300);
        }
        
        function toggleSort() {
            sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
            
            const $icon = $('#sortIcon');
            $icon.removeClass('fa-sort fa-sort-up fa-sort-down');
            
            if (sortOrder === 'asc') {
                $icon.addClass('fa-sort-up');
            } else {
                $icon.addClass('fa-sort-down');
            }
            
            // Sort projects
            sortProjects(sortOrder);
            
            // Save preference
            localStorage.setItem('projectSortOrder', sortOrder);
        }
        
        function sortProjects(order) {
            const $container = $('#projectsGrid');
            const $projects = $container.children().detach();
            
            $projects.sort(function(a, b) {
                const titleA = $(a).find('.card-title a').text().trim().toLowerCase();
                const titleB = $(b).find('.card-title a').text().trim().toLowerCase();
                
                if (order === 'asc') {
                    return titleA.localeCompare(titleB);
                } else {
                    return titleB.localeCompare(titleA);
                }
            });
            
            $container.append($projects);
            
            // Add animation
            $projects.each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's')
                       .addClass('animate__animated animate__fadeInUp');
            });
        }
        
        function resetAllFilters() {
            if (confirm('Bạn có chắc chắn muốn đặt lại tất cả bộ lọc?')) {
                showLoading();
                window.location.href = 'manage_projects.php';
            }
        }
        
        function highlightSearchTerms(searchTerm) {
            $('.card-title, .card-text').each(function() {
                const $element = $(this);
                let text = $element.text();
                
                // Remove previous highlights
                text = text.replace(/<mark[^>]*>([^<]+)<\/mark>/gi, '$1');
                
                if (searchTerm.length > 0) {
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    text = text.replace(regex, '<mark style="background: yellow; padding: 2px;">$1</mark>');
                    $element.html(text);
                }
            });
        }
        
        function saveFilterPreferences() {
            const filters = {
                scope: $('#scope').val(),
                status: $('#status').val(),
                type: $('#type').val(),
                search: $('#search').val()
            };
            localStorage.setItem('projectFilters', JSON.stringify(filters));
        }
        
        function loadSavedPreferences() {
            // Load view preference
            const savedView = localStorage.getItem('projectViewPreference');
            if (savedView && savedView !== 'grid') {
                switchView(savedView);
            }
            
            // Load sort preference
            const savedSort = localStorage.getItem('projectSortOrder');
            if (savedSort) {
                sortOrder = savedSort;
                const $icon = $('#sortIcon');
                $icon.removeClass('fa-sort fa-sort-up fa-sort-down');
                if (sortOrder === 'asc') {
                    $icon.addClass('fa-sort-up');
                } else {
                    $icon.addClass('fa-sort-down');
                }
            }
        }
        
        function showLoading() {
            $('#loadingOverlay').css('display', 'flex');
        }
        
        function hideLoading() {
            $('#loadingOverlay').hide();
        }
        
        // Enhanced notifications
        function showNotification(message, type = 'success') {
            const notification = $(`
                <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 1050; min-width: 300px; border-radius: 15px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-2"></i>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(function() {
                notification.alert('close');
            }, 4000);
        }
        
        // Auto-refresh functionality (optional)
        function enableAutoRefresh() {
            setInterval(function() {
                const lastUpdate = localStorage.getItem('lastProjectUpdate');
                const now = new Date().getTime();
                
                // Check if data needs refresh (every 5 minutes)
                if (!lastUpdate || (now - lastUpdate) > 300000) {
                    location.reload();
                }
            }, 60000); // Check every minute
        }
        
        // Initialize auto-refresh if enabled
        // enableAutoRefresh();
    </script>
</body>
</html>