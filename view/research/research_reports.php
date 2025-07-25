<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\research_reports.php

// Enable error reporting for debugging, remove in production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Catch any fatal errors to show a user-friendly message
try {
    // Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
    include '../../include/session.php';
    checkResearchManagerRole();

    // Kết nối database
    include '../../include/connect.php';
    
    // Check database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

// Khởi tạo biến lọc
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';

// Lấy danh sách năm để lọc
$years = [];
$current_year = date('Y');
for ($i = 2018; $i <= $current_year; $i++) {
    $years[] = $i;
}

// Lấy danh sách khoa/đơn vị
$faculty_sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV ASC";
$faculty_result = $conn->query($faculty_sql);
if (!$faculty_result) {
    // Log SQL error for debugging
    error_log("SQL Error in research_reports.php (faculty list query): " . $conn->error);
    // Throw exception to be caught by the main try-catch block
    throw new Exception("Failed to retrieve faculty list");
}
$faculties = [];
while ($faculty = $faculty_result->fetch_assoc()) {
    $faculties[] = $faculty;
}

// Xử lý điều kiện lọc cho các truy vấn
$year_condition = '';
$faculty_condition = '';

if ($year_filter > 0) {
    $year_condition = "AND YEAR(dt.DT_NGAYTAO) = $year_filter";
}

if (!empty($faculty_filter)) {
    $faculty_condition = "AND gv.DV_MADV = '$faculty_filter'";
}

// 1. Thống kê số lượng đề tài theo trạng thái
$status_query = "SELECT dt.DT_TRANGTHAI, COUNT(*) as count
                FROM de_tai_nghien_cuu dt
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                WHERE 1=1 $year_condition $faculty_condition
                GROUP BY dt.DT_TRANGTHAI";
$status_result = $conn->query($status_query);
if (!$status_result) {
    // Log SQL error for debugging
    error_log("SQL Error in research_reports.php (status query): " . $conn->error);
    // Set empty result
    $status_result = null;
}
$status_stats = [];

// Khởi tạo các trạng thái mặc định
$default_statuses = ['Chờ phê duyệt', 'Đang tiến hành', 'Đã hoàn thành', 'Đã từ chối'];
foreach ($default_statuses as $status) {
    $status_stats[$status] = 0;
}

// Check if result exists before trying to fetch
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $status_stats[$row['DT_TRANGTHAI']] = $row['count'];
    }
}

// 2. Thống kê số lượng đề tài theo loại
$type_query = "SELECT ldt.LDT_TENLOAI, COUNT(*) as count
               FROM de_tai_nghien_cuu dt
               JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
               LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
               WHERE 1=1 $year_condition $faculty_condition
               GROUP BY ldt.LDT_MA
               ORDER BY count DESC";
$type_result = $conn->query($type_query);
if (!$type_result) {
    // Log SQL error for debugging
    error_log("SQL Error in research_reports.php (type query): " . $conn->error);
    // Set empty result
    $type_result = null;
}
$type_stats = [];

// Check if result exists before trying to fetch
if ($type_result) {
    while ($row = $type_result->fetch_assoc()) {
        $type_stats[$row['LDT_TENLOAI']] = $row['count'];
    }
}

// 3. Thống kê số lượng đề tài theo khoa
$faculty_stats_query = "SELECT k.DV_TENDV, COUNT(dt.DT_MADT) as count
                      FROM de_tai_nghien_cuu dt
                      JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                      JOIN khoa k ON gv.DV_MADV = k.DV_MADV
                      WHERE 1=1 $year_condition
                      GROUP BY k.DV_MADV
                      ORDER BY count DESC";
$faculty_stats_result = $conn->query($faculty_stats_query);
if (!$faculty_stats_result) {
    // Log SQL error for debugging
    error_log("SQL Error in research_reports.php (faculty stats query): " . $conn->error);
    // Set empty result
    $faculty_stats_result = null;
}
$faculty_stats = [];

// Check if result exists before trying to fetch
if ($faculty_stats_result) {
    while ($row = $faculty_stats_result->fetch_assoc()) {
        $faculty_stats[$row['DV_TENDV']] = $row['count'];
    }
}

// 4. Thống kê giảng viên có nhiều đề tài nhất
$teacher_query = "SELECT 
                   CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
                   k.DV_TENDV,
                   COUNT(dt.DT_MADT) as project_count
                 FROM 
                   giang_vien gv
                 LEFT JOIN 
                   de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                 JOIN 
                   khoa k ON gv.DV_MADV = k.DV_MADV
                 WHERE 
                   1=1 $year_condition $faculty_condition
                 GROUP BY 
                   gv.GV_MAGV
                 ORDER BY 
                   project_count DESC
                 LIMIT 10";
$teacher_result = $conn->query($teacher_query);
if (!$teacher_result) {
    // Log SQL error for debugging
    error_log("SQL Error in research_reports.php (teacher query): " . $conn->error);
    // Set empty result
    $teacher_result = null;
}
$top_teachers = [];

// Check if result exists before trying to fetch
if ($teacher_result) {
    while ($row = $teacher_result->fetch_assoc()) {
        $top_teachers[] = $row;
    }
}

// 5. Thống kê sinh viên tham gia đề tài nhiều nhất
$student_query = "SELECT 
                   CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN,
                   l.LOP_TEN,
                   COUNT(ct.DT_MADT) as project_count
                 FROM 
                   sinh_vien sv
                 JOIN 
                   chi_tiet_tham_gia ct ON sv.SV_MASV = ct.SV_MASV
                 JOIN 
                   de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                 LEFT JOIN 
                   lop l ON sv.LOP_MA = l.LOP_MA
                 LEFT JOIN
                   giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                 WHERE 
                   1=1 $year_condition $faculty_condition
                 GROUP BY 
                   sv.SV_MASV
                 ORDER BY 
                   project_count DESC
                 LIMIT 10";
$student_result = $conn->query($student_query);
if (!$student_result) {
    // Log SQL error for debugging
    error_log("SQL Error in research_reports.php (student query): " . $conn->error);
    // Set empty result
    $student_result = null;
}
$top_students = [];

// Check if result exists before trying to fetch
if ($student_result) {
    while ($row = $student_result->fetch_assoc()) {
        $top_students[] = $row;
    }
}

// 6. Thống kê theo tháng trong năm
$monthly_query = "SELECT 
                   MONTH(dt.DT_NGAYTAO) as month,
                   COUNT(*) as count
                 FROM 
                   de_tai_nghien_cuu dt
                 LEFT JOIN
                   giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                 WHERE 
                   YEAR(dt.DT_NGAYTAO) = $year_filter $faculty_condition
                 GROUP BY 
                   MONTH(dt.DT_NGAYTAO)
                 ORDER BY 
                   month ASC";
$monthly_result = $conn->query($monthly_query);
if (!$monthly_result) {
    // Log SQL error for debugging
    error_log("SQL Error in research_reports.php (monthly query): " . $conn->error);
    // Set empty result
    $monthly_result = null;
}
$monthly_stats = array_fill(1, 12, 0); // Khởi tạo mảng với 12 tháng, giá trị mặc định là 0

// Check if result exists before trying to fetch
if ($monthly_result) {
    while ($row = $monthly_result->fetch_assoc()) {
        $monthly_stats[$row['month']] = $row['count'];
    }
}

// Chuẩn bị dữ liệu cho biểu đồ
$months = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
           'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
$monthly_data = array_values($monthly_stats);
$status_labels = array_keys($status_stats);
$status_data = array_values($status_stats);
$type_labels = array_keys($type_stats);
$type_data = array_values($type_stats);
$faculty_labels = array_keys($faculty_stats);
$faculty_data = array_values($faculty_stats);

} catch (Exception $e) {
    // Log the error
    error_log("Error in research_reports.php: " . $e->getMessage());
    
    // Set empty arrays to avoid undefined variable errors
    $years = [];
    $faculties = [];
    $year_filter = date('Y');
    $faculty_filter = '';
    $status_stats = ['Chờ phê duyệt' => 0, 'Đang tiến hành' => 0, 'Đã hoàn thành' => 0, 'Đã từ chối' => 0];
    $type_stats = [];
    $faculty_stats = [];
    $top_teachers = [];
    $top_students = [];
    $monthly_stats = array_fill(1, 12, 0);
    $months = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
               'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
    $monthly_data = array_values($monthly_stats);
    $status_labels = array_keys($status_stats);
    $status_data = array_values($status_stats);
    $type_labels = [];
    $type_data = [];
    $faculty_labels = [];
    $faculty_data = [];
    
    // Set an error message to display to users
    $error_message = "Có lỗi xảy ra khi tải báo cáo. Vui lòng thử lại sau hoặc liên hệ quản trị viên.";
}
// Set page title
$page_title = "Báo cáo nghiên cứu | Quản lý nghiên cứu";

// Additional CSS for charts and reports
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
<style>
    /* Chart container styles */
    .chart-container {
        position: relative;
        min-height: 300px;
        margin-bottom: 20px;
    }
    
    .chart-actions {
        position: absolute;
        top: 0;
        right: 0;
        z-index: 10;
    }
    
    /* Card styles */
    .report-card {
        transition: all 0.3s;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
    }
    
    /* Table styles */
    .report-table th {
        background-color: #4e73df;
        color: white;
    }
    
    /* Filter controls */
    .filter-controls {
        background-color: #f8f9fc;
        border-left: 4px solid #4e73df;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    /* Additional styles for reports */
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
    
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    .stats-card {
        transition: all 0.3s;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .top-table th {
        background-color: #4e73df;
        color: white;
    }
    
    .rank-number {
        display: inline-block;
        width: 25px;
        height: 25px;
        line-height: 25px;
        border-radius: 50%;
        background-color: #4e73df;
        color: white;
        text-align: center;
        font-weight: bold;
        margin-right: 10px;
    }
    
    .top-three .rank-number {
        background: linear-gradient(135deg, #4a6baf 0%, #243a6f 100%);
        width: 30px;
        height: 30px;
        line-height: 30px;
    }
    
    .report-section-title {
        margin-bottom: 1.5rem;
        font-weight: 700;
        color: #2e59d9;
        border-bottom: 2px solid #e3e6f0;
        padding-bottom: 0.5rem;
    }
    
    .export-btn {
        position: absolute;
        top: 0;
        right: 0;
        z-index: 10;
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
        
        .chart-container {
            height: 250px;
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
            <i class="fas fa-chart-bar me-3"></i>
            Báo cáo nghiên cứu
        </h1>
        <a href="research_dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Về Dashboard
        </a>
    </div>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Bộ lọc -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc báo cáo</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="filter-form">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="year">Năm</label>
                                        <select class="form-control" id="year" name="year">
                                            <?php foreach ($years as $year): ?>
                                                <option value="<?php echo $year; ?>" <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                                    <?php echo $year; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="faculty">Khoa/Đơn vị</label>
                                        <select class="form-control" id="faculty" name="faculty">
                                            <option value="">Tất cả khoa</option>
                                            <?php foreach ($faculties as $faculty): ?>
                                                <option value="<?php echo htmlspecialchars($faculty['DV_MADV']); ?>" <?php echo $faculty_filter == $faculty['DV_MADV'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($faculty['DV_TENDV']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter fa-sm"></i> Lọc báo cáo
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Thống kê chung -->
                    <div class="row">
                        <!-- Thống kê trạng thái đề tài -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Tổng số đề tài
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo array_sum($status_data); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Đang tiến hành
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $status_stats['Đang tiến hành']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-spinner fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Đã hoàn thành
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $status_stats['Đã hoàn thành']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 stats-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Chờ phê duyệt
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $status_stats['Chờ phê duyệt']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Biểu đồ phân bố đề tài theo tháng -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4 position-relative">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Phân bố đề tài theo tháng</h6>
                                    <button class="btn btn-sm btn-outline-primary export-btn" id="exportMonthlyChart">
                                        <i class="fas fa-download fa-sm"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Biểu đồ trạng thái đề tài -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4 position-relative">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Trạng thái đề tài</h6>
                                    <button class="btn btn-sm btn-outline-primary export-btn" id="exportStatusChart">
                                        <i class="fas fa-download fa-sm"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Biểu đồ phân bố đề tài theo loại -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4 position-relative">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Phân bố đề tài theo loại</h6>
                                    <button class="btn btn-sm btn-outline-primary export-btn" id="exportTypeChart">
                                        <i class="fas fa-download fa-sm"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="typeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Biểu đồ phân bố đề tài theo khoa -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4 position-relative">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Phân bố đề tài theo khoa</h6>
                                    <button class="btn btn-sm btn-outline-primary export-btn" id="exportFacultyChart">
                                        <i class="fas fa-download fa-sm"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="facultyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Top giảng viên có nhiều đề tài -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4 position-relative">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Top giảng viên có nhiều đề tài</h6>
                                    <button class="btn btn-sm btn-outline-primary export-btn" id="exportTeacherTable">
                                        <i class="fas fa-file-excel fa-sm"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered top-table" id="teacherTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th width="5%">STT</th>
                                                    <th width="40%">Tên giảng viên</th>
                                                    <th width="35%">Đơn vị</th>
                                                    <th width="20%">Số đề tài</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($top_teachers) > 0): ?>
                                                    <?php $rank = 1; foreach ($top_teachers as $teacher): ?>
                                                        <tr class="<?php echo ($rank <= 3) ? 'top-three' : ''; ?>">
                                                            <td class="text-center">
                                                                <span class="rank-number"><?php echo $rank; ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($teacher['GV_HOTEN']); ?></td>
                                                            <td><?php echo htmlspecialchars($teacher['DV_TENDV']); ?></td>
                                                            <td class="text-center font-weight-bold"><?php echo $teacher['project_count']; ?></td>
                                                        </tr>
                                                    <?php $rank++; endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Không có dữ liệu</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top sinh viên tham gia nhiều đề tài -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4 position-relative">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Top sinh viên tham gia nhiều đề tài</h6>
                                    <button class="btn btn-sm btn-outline-primary export-btn" id="exportStudentTable">
                                        <i class="fas fa-file-excel fa-sm"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered top-table" id="studentTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th width="5%">STT</th>
                                                    <th width="40%">Tên sinh viên</th>
                                                    <th width="35%">Lớp</th>
                                                    <th width="20%">Số đề tài</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($top_students) > 0): ?>
                                                    <?php $rank = 1; foreach ($top_students as $student): ?>
                                                        <tr class="<?php echo ($rank <= 3) ? 'top-three' : ''; ?>">
                                                            <td class="text-center">
                                                                <span class="rank-number"><?php echo $rank; ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($student['SV_HOTEN']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['LOP_TEN']); ?></td>
                                                            <td class="text-center font-weight-bold"><?php echo $student['project_count']; ?></td>
                                                        </tr>
                                                    <?php $rank++; endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Không có dữ liệu</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê sinh viên theo lớp -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Danh sách sinh viên theo lớp</h6>
                            <button class="btn btn-sm btn-outline-primary" id="exportStudentListTable">
                                <i class="fas fa-file-excel fa-sm"></i> Xuất Excel
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Bộ lọc sinh viên -->
                            <div class="filter-form mb-4">
                                <form method="GET" action="" id="studentFilterForm" class="mb-3">
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="department">Khoa</label>
                                            <select class="form-control" id="department" name="department">
                                                <option value="">Tất cả khoa</option>
                                                <?php foreach ($faculties as $faculty): ?>
                                                    <option value="<?php echo htmlspecialchars($faculty['DV_MADV']); ?>">
                                                        <?php echo htmlspecialchars($faculty['DV_TENDV']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="school_year">Khóa</label>
                                            <select class="form-control" id="school_year" name="school_year">
                                                <option value="">Tất cả</option>
                                                <?php for($i = date('Y') - 5; $i <= date('Y'); $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="class">Lớp</label>
                                            <select class="form-control" id="class" name="class">
                                                <option value="">Tất cả lớp</option>
                                                <!-- Các lớp sẽ được tải bằng AJAX dựa trên khoa và khóa -->
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="research_status">Trạng thái nghiên cứu</label>
                                            <select class="form-control" id="research_status" name="research_status">
                                                <option value="">Tất cả</option>
                                                <option value="active">Đang làm nghiên cứu</option>
                                                <option value="completed">Đã hoàn thành nghiên cứu</option>
                                                <option value="none">Chưa tham gia nghiên cứu</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-1 d-flex align-items-end">
                                            <button type="button" id="filterStudentList" class="btn btn-primary w-100">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Bảng danh sách sinh viên -->
                            <div class="table-responsive">
                                <div id="studentListLoading" class="text-center my-3" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Đang tải...</span>
                                    </div>
                                </div>
                                <table class="table table-bordered table-hover" id="studentListTable" width="100%" cellspacing="0">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="5%">STT</th>
                                            <th width="15%">Mã SV</th>
                                            <th width="25%">Họ tên</th>
                                            <th width="15%">Lớp</th>
                                            <th width="15%">Khoa</th>
                                            <th width="15%">Trạng thái nghiên cứu</th>
                                            <th width="10%">Số đề tài</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center">Vui lòng chọn bộ lọc để hiển thị danh sách sinh viên</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div id="studentCount" class="text-muted">
                                        Hiển thị 0 sinh viên
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <nav aria-label="Student pagination" class="float-right">
                                        <ul class="pagination pagination-sm" id="studentPagination">
                                            <!-- Phân trang sẽ được thêm bằng JavaScript -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Xuất báo cáo tổng hợp -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Xuất báo cáo tổng hợp</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title font-weight-bold text-primary mb-3">Báo cáo hoạt động NCKH</h5>
                                            <p class="card-text">Tổng hợp hoạt động nghiên cứu khoa học trong kỳ.</p>
                                            <button class="btn btn-primary w-100" id="exportActivityReport">
                                                <i class="fas fa-file-pdf mr-2"></i> Xuất báo cáo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title font-weight-bold text-primary mb-3">Bảng kê các đề tài</h5>
                                            <p class="card-text">Liệt kê chi tiết các đề tài nghiên cứu.</p>
                                            <button class="btn btn-primary w-100" id="exportProjectsList">
                                                <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title font-weight-bold text-primary mb-3">Thống kê sinh viên tham gia</h5>
                                            <p class="card-text">Thống kê sinh viên theo khoa, lớp.</p>
                                            <button class="btn btn-primary w-100" id="exportStudentStats">
                                                <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Hệ thống quản lý nghiên cứu khoa học <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>    <!-- Bootstrap core JavaScript - Using CDN for missing files -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <script src="/NLNganh/assets/js/sb-admin-2.min.js"></script>
    <script src="/NLNganh/assets/js/research/modern-sidebar.js"></script>
    <script>
        // Basic SB Admin 2 functionality
        $(document).ready(function() {
            // Handle sidebar toggle
            $("#sidebarToggleTop").click(function() {
                $("body").toggleClass("sidebar-toggled");
                $(".sidebar").toggleClass("toggled");
            });
        });
    </script>
    
    <script>
        // Dữ liệu từ PHP
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        const statusLabels = <?php echo json_encode($status_labels); ?>;
        const statusData = <?php echo json_encode($status_data); ?>;
        const typeLabels = <?php echo json_encode($type_labels); ?>;
        const typeData = <?php echo json_encode($type_data); ?>;
        const facultyLabels = <?php echo json_encode($faculty_labels); ?>;
        const facultyData = <?php echo json_encode($faculty_data); ?>;
        
        // Thiết lập màu
        const backgroundColors = [
            'rgba(78, 115, 223, 0.7)',
            'rgba(28, 200, 138, 0.7)',
            'rgba(246, 194, 62, 0.7)',
            'rgba(231, 74, 59, 0.7)',
            'rgba(54, 185, 204, 0.7)',
            'rgba(133, 135, 150, 0.7)',
            'rgba(105, 0, 132, 0.7)',
            'rgba(0, 200, 83, 0.7)',
            'rgba(255, 152, 0, 0.7)',
            'rgba(233, 30, 99, 0.7)',
            'rgba(0, 188, 212, 0.7)',
            'rgba(97, 97, 97, 0.7)'
        ];
        
        const borderColors = [
            'rgb(78, 115, 223)',
            'rgb(28, 200, 138)',
            'rgb(246, 194, 62)',
            'rgb(231, 74, 59)',
            'rgb(54, 185, 204)',
            'rgb(133, 135, 150)',
            'rgb(105, 0, 132)',
            'rgb(0, 200, 83)',
            'rgb(255, 152, 0)',
            'rgb(233, 30, 99)',
            'rgb(0, 188, 212)',
            'rgb(97, 97, 97)'
        ];

        $(document).ready(function() {
            // Biểu đồ phân bố theo tháng
            const monthlyChartCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyChartCtx, {
                type: 'bar',
                data: {
                    labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
                    datasets: [{
                        label: 'Số lượng đề tài',
                        data: monthlyData,
                        backgroundColor: 'rgba(78, 115, 223, 0.7)',
                        borderColor: 'rgb(78, 115, 223)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
            
            // Biểu đồ trạng thái đề tài
            const statusChartCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusChartCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: backgroundColors.slice(0, statusData.length),
                        borderColor: borderColors.slice(0, statusData.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom'
                    }
                }
            });
            
            // Biểu đồ phân bố theo loại
            const typeChartCtx = document.getElementById('typeChart').getContext('2d');
            const typeChart = new Chart(typeChartCtx, {
                type: 'bar',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        label: 'Số lượng đề tài',
                        data: typeData,
                        backgroundColor: backgroundColors.slice(0, typeData.length),
                        borderColor: borderColors.slice(0, typeData.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
            
            // Biểu đồ phân bố theo khoa
            const facultyChartCtx = document.getElementById('facultyChart').getContext('2d');
            const facultyChart = new Chart(facultyChartCtx, {
                type: 'pie',
                data: {
                    labels: facultyLabels,
                    datasets: [{
                        data: facultyData,
                        backgroundColor: backgroundColors.slice(0, facultyData.length),
                        borderColor: borderColors.slice(0, facultyData.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom'
                    }
                }
            });
              // Xử lý sự kiện xuất báo cáo
            $('#exportActivityReport, #exportProjectsList, #exportStudentStats').click(function() {
                const type = $(this).attr('id').replace('export', '').replace('Report', '').replace('List', '').replace('Stats', '');
                exportReport(type);
            });
            
            // Xử lý sự kiện xuất biểu đồ
            $('#exportMonthlyChart, #exportStatusChart, #exportTypeChart, #exportFacultyChart').click(function() {
                const chartId = $(this).attr('id').replace('export', '');
                exportChart(chartId.replace('Chart', '').toLowerCase());
            });
            
            // Xử lý sự kiện xuất bảng
            $('#exportTeacherTable, #exportStudentTable').click(function() {
                const tableType = $(this).attr('id').replace('export', '').replace('Table', '').toLowerCase();
                exportTable(tableType);
            });
            
            // Hàm xuất báo cáo
            function exportReport(type) {
                // Hiển thị loading
                const btn = $('#export' + type + (type === 'Activity' ? 'Report' : (type === 'Projects' ? 'List' : 'Stats')));
                const originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Đang xuất...');
                
                // Tạo form ẩn để gửi request xuất báo cáo
                const form = $('<form>', {
                    'method': 'post',
                    'action': 'export_report.php',
                    'target': '_blank'
                }).appendTo('body');
                
                $('<input>').attr({
                    'type': 'hidden',
                    'name': 'type',
                    'value': type.toLowerCase()
                }).appendTo(form);
                
                $('<input>').attr({
                    'type': 'hidden',
                    'name': 'year',
                    'value': $('#year').val()
                }).appendTo(form);
                
                if ($('#faculty').length) {
                    $('<input>').attr({
                        'type': 'hidden',
                        'name': 'faculty',
                        'value': $('#faculty').val()
                    }).appendTo(form);
                }
                
                form.submit();
                form.remove();
                
                // Khôi phục nút sau 2 giây
                setTimeout(() => {
                    btn.html(originalText);
                }, 2000);
            }
            
            // Hàm xuất biểu đồ
            function exportChart(chartName) {
                const chartElement = document.getElementById(chartName + 'Chart');
                if (!chartElement) return;
                
                // Hiển thị loading
                const btn = $('#export' + chartName.charAt(0).toUpperCase() + chartName.slice(1) + 'Chart');
                const originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Đang xuất...');
                
                // Xuất biểu đồ thành ảnh PNG và tải về
                const image = chartElement.toDataURL('image/png', 1.0);
                const link = document.createElement('a');
                link.download = 'thong-ke-' + chartName + '-' + new Date().toISOString().slice(0, 10) + '.png';
                link.href = image;
                link.click();
                
                // Khôi phục nút sau 1 giây
                setTimeout(() => {
                    btn.html(originalText);
                }, 1000);
            }
            
            // Hàm xuất bảng
            function exportTable(tableType) {
                // Hiển thị loading
                const btn = $('#export' + tableType.charAt(0).toUpperCase() + tableType.slice(1) + 'Table');
                const originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Đang xuất...');
                
                // Tạo URL để tải về file Excel
                let fileUrl = 'export_table.php?type=' + tableType;
                fileUrl += '&year=' + $('#year').val();
                if ($('#faculty').length) {
                    fileUrl += '&faculty=' + $('#faculty').val();
                }
                
                // Mở URL trong tab mới
                window.open(fileUrl, '_blank');
                
                // Khôi phục nút sau 1 giây
                setTimeout(() => {
                    btn.html(originalText);
                }, 1000);
            }
        });

        // Error handler for Chart.js
        Chart.plugins.register({
            afterDraw: function(chart) {
                if (chart.data.datasets.length === 0 || 
                    (chart.data.datasets[0].data.length === 0)) {
                    // No data is present
                    var ctx = chart.chart.ctx;
                    var width = chart.chart.width;
                    var height = chart.chart.height;
                    
                    chart.clear();
                    
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = "16px 'Nunito'";
                    ctx.fillStyle = '#6e707e';
                    ctx.fillText('Không có dữ liệu', width / 2, height / 2);
                    ctx.restore();
                }
            }
        });
    </script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.min.js"></script>
<script>
    // Initialize tooltips and popovers
    $(function () {
        $("[data-toggle=\'tooltip\']").tooltip();
        $("[data-toggle=\'popover\']").popover();
    });    // Add event listener to refresh charts when filters change
    $("#year, #faculty").on("change", function() {
        $("#filterForm").submit();
    });
</script>

</div> <!-- /.container-fluid -->

<?php
// Include footer if needed
// include '../../include/research_footer.php';
?>
