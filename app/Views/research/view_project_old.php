<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\view_project.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/database.php';

// Kiểm tra tham số
$project_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($project_id)) {
    // Redirect về trang danh sách dự án
    header('Location: manage_projects.php');
    exit;
}

// Lấy thông tin dự án chi tiết
$sql = "SELECT dt.*, 
        ldt.LDT_TENLOAI, 
        lvnc.LVNC_TEN as LVNC_TEN,
        lvut.LVUT_TEN as LVUT_TEN,
        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN, 
        gv.GV_MAGV,
        gv.GV_EMAIL,
        gv.GV_SDT,
        gv.GV_CHUYENMON,
        k.DV_TENDV
        FROM de_tai_nghien_cuu dt
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
        WHERE dt.DT_MADT = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Không tìm thấy dự án
    header('Location: manage_projects.php');
    exit;
}

$project = $result->fetch_assoc();

// Lấy danh sách sinh viên tham gia
$student_sql = "SELECT sv.*, 
                CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
                l.LOP_TEN,
                k.DV_TENDV,
                cttg.CTTG_VAITRO,
                cttg.CTTG_NGAYTHAMGIA,
                hk.HK_TEN
                FROM chi_tiet_tham_gia cttg
                JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
                LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
                LEFT JOIN hoc_ki hk ON cttg.HK_MA = hk.HK_MA
                WHERE cttg.DT_MADT = ?
                ORDER BY cttg.CTTG_VAITRO DESC, sv.SV_TENSV";

$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $project_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$students = [];

while ($student = $student_result->fetch_assoc()) {
    $students[] = $student;
}

// Lấy thông tin hợp đồng
$contract_sql = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
$contract_stmt = $conn->prepare($contract_sql);
$contract_stmt->bind_param("s", $project_id);
$contract_stmt->execute();
$contract_result = $contract_stmt->get_result();
$contracts = [];

while ($contract = $contract_result->fetch_assoc()) {
    $contracts[] = $contract;
}

// Lấy thông tin quyết định nghiệm thu
$decision_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
                 FROM quyet_dinh_nghiem_thu qd
                 LEFT JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB
                 WHERE qd.QD_SO = ?";
$decision_stmt = $conn->prepare($decision_sql);
$decision_stmt->bind_param("s", $project['QD_SO']);
$decision_stmt->execute();
$decision_result = $decision_stmt->get_result();
$decision = $decision_result->fetch_assoc();

// Lấy thông tin thành viên hội đồng
$council_sql = "SELECT tvhd.*, 
                CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                tc.TC_TEN, tc.TC_DIEMTOIDA
                FROM thanh_vien_hoi_dong tvhd
                LEFT JOIN giang_vien gv ON tvhd.GV_MAGV = gv.GV_MAGV
                LEFT JOIN tieu_chi tc ON tvhd.TC_MATC = tc.TC_MATC
                WHERE tvhd.QD_SO = ?
                ORDER BY tvhd.TV_VAITRO, gv.GV_TENGV";
$council_stmt = $conn->prepare($council_sql);
$council_members = [];

if ($project['QD_SO']) {
    $council_stmt->bind_param("s", $project['QD_SO']);
    $council_stmt->execute();
    $council_result = $council_stmt->get_result();
    
    while ($member = $council_result->fetch_assoc()) {
        $council_members[] = $member;
    }
}

// Lấy thông tin tiến độ
$progress_sql = "SELECT * FROM tien_do_de_tai WHERE DT_MADT = ? ORDER BY TDDT_NGAYCAPNHAT DESC";
$progress_stmt = $conn->prepare($progress_sql);
$progress_stmt->bind_param("s", $project_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$progress_list = [];

while ($progress = $progress_result->fetch_assoc()) {
    $progress_list[] = $progress;
}

// Lấy thông tin báo cáo
$report_sql = "SELECT bc.*, lbc.LBC_TENLOAI
               FROM bao_cao bc
               LEFT JOIN loai_bao_cao lbc ON bc.LBC_MALOAI = lbc.LBC_MALOAI
               WHERE bc.DT_MADT = ?
               ORDER BY bc.BC_NGAYNOP DESC";
$report_stmt = $conn->prepare($report_sql);
$report_stmt->bind_param("s", $project_id);
$report_stmt->execute();
$report_result = $report_stmt->get_result();
$reports = [];

while ($report = $report_result->fetch_assoc()) {
    $reports[] = $report;
}

// Lấy thông tin file đính kèm
$file_sql = "SELECT * FROM file_dinh_kem WHERE BB_SOBB = ?";
$file_stmt = $conn->prepare($file_sql);
$files = [];

if ($decision && $decision['BB_SOBB']) {
    $file_stmt->bind_param("s", $decision['BB_SOBB']);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();
    
    while ($file = $file_result->fetch_assoc()) {
        $files[] = $file;
    }
}

// Lấy lịch sử thay đổi
$log_sql = "SELECT lhd.*, 
           CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN
           FROM log_hoat_dong lhd
           LEFT JOIN giang_vien gv ON lhd.LHD_NGUOITHAOTAC = gv.GV_MAGV
           WHERE lhd.LHD_DOITUONG = 'de_tai' AND lhd.LHD_DOITUONG_ID = ?
           ORDER BY lhd.LHD_THOIGIAN DESC";

$log_stmt = $conn->prepare($log_sql);
$logs = [];

if ($log_stmt === false) {
    // Ghi log lỗi để debug
    error_log("Lỗi SQL (log_hoat_dong): " . $conn->error);
    // Không làm gì, giữ mảng logs trống
} else {
    $log_stmt->bind_param("s", $project_id);
    $log_stmt->execute();
    $log_result = $log_stmt->get_result();

    while ($log = $log_result->fetch_assoc()) {
        $logs[] = $log;
    }
}

// Set page title
$page_title = "Chi tiết đề tài: " . $project['DT_TENDT'];

// Define additional CSS for this page
$additional_css = '<style>
    /* Layout positioning - Fixed to match 250px sidebar */
    body {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    #wrapper {
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
    }
    
    #content-wrapper {
        margin-left: 250px !important;
        width: calc(100% - 250px) !important;
        padding: 0 !important;
        flex: 1 !important;
    }
    
    #content {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .container-fluid {
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
        margin: 0 !important;
    }
    
    /* Override any conflicting styles */
    .modern-research-sidebar,
    .simple-sidebar {
        width: 250px !important;
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
    
    /* Timeline styling */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e3e6f0;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 25px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -35px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #4e73df;
        border: 2px solid #fff;
    }
    
    .timeline-content {
        padding-bottom: 10px;
        border-bottom: 1px solid #f2f2f2;
    }
    
    .timeline-title {
        font-size: 1rem;
        margin-bottom: 10px;
    }
    
    /* Card enhancements */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }
    
    /* Main project overview card header */
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        border-bottom: none;
        padding: 20px;
    }
    
    /* Detailed information card header */
    .card .card-header:has(+ .card-body .nav-tabs) {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    /* Team tab - Lecturer card header */
    .border-left-primary .card-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    /* Team tab - Student card header */
    .border-left-success .card-header {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    
    /* Evaluation tab - Decision card header */
    .border-left-info .card-header {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    
    /* Evaluation tab - Minutes card header */
    .border-left-warning .card-header {
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        color: #333;
    }
    
    /* Council members card header */
    .card:has(.table thead th) .card-header {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        color: #333;
    }
    
    /* Files tab - Project files card header */
    .card:has(.file-attachment) .card-header:first-of-type {
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        color: #333;
    }
    
    /* Files tab - Evaluation files card header */
    .card:has(.file-attachment) .card-header:last-of-type {
        background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
        color: #333;
    }
    
    /* Progress and History timeline cards */
    .card:has(.timeline) .card-header {
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        color: #333;
    }
    
    /* Reports table card header */
    .card:has(.table-responsive) .card-header {
        background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
        color: #333;
    }
    
    /* Contract table card header */
    .card:has(.table-bordered) .card-header {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        color: #333;
    }
    
    /* Empty state cards (no data) */
    .card:has(.text-center .text-muted) .card-header {
        background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
        color: #333;
    }
    
    /* Action buttons area */
    .d-sm-flex.align-items-center.justify-content-between .btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }
    
    .d-sm-flex.align-items-center.justify-content-between .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .card-header h6 {
        margin: 0;
        font-weight: 600;
    }
    
    .icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }
    
    /* Button improvements */
    .btn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
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
    
    .btn-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #e74a3b 100%);
    }
    
    /* Badge styling */
    .badge {
        padding: 8px 12px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* List group styling */
    .list-group-item {
        border: 1px solid #e9ecef;
        border-radius: 8px !important;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fc;
        transform: translateX(5px);
    }
    
    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }
    
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
    
    .border-left-warning {
        border-left: 4px solid #f6c23e !important;
    }
    
    .border-left-info {
        border-left: 4px solid #36b9cc !important;
    }
    
    /* Progress bar styling */
    .progress {
        height: 10px;
        border-radius: 5px;
        background-color: #e9ecef;
    }
    
    .progress-bar {
        border-radius: 5px;
        transition: width 0.6s ease;
    }
    
    /* Table styling */
    .table {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fc;
    }
    
    /* Status indicators */
    .status-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .status-completed { background-color: #1cc88a; }
    .status-in-progress { background-color: #36b9cc; }
    .status-pending { background-color: #f6c23e; }
    .status-cancelled { background-color: #e74a3b; }
    
    /* File attachment styling */
    .file-attachment {
        display: flex;
        align-items: center;
        padding: 10px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .file-attachment:hover {
        background-color: #f8f9fc;
        border-color: #4e73df;
    }
    
    /* File icon with new colors */
    .file-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 15px;
    }
    
    /* Tab styling */
    .nav-tabs {
        border-bottom: 2px solid #e3e6f0;
    }
    
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #858796;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.3s ease;
        background-color: #f8f9fc;
        margin-right: 5px;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #4e73df;
        background-color: #eaecf4;
    }
    
    .nav-tabs .nav-link.active {
        border-bottom-color: #4e73df;
        color: #4e73df;
        background-color: #fff;
        border-top: 1px solid #e3e6f0;
        border-left: 1px solid #e3e6f0;
        border-right: 1px solid #e3e6f0;
    }
    
    /* Info boxes */
    .info-box {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #4e73df;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .info-box h5 {
        color: #4e73df;
        margin-bottom: 10px;
        font-weight: 600;
    }
    
    .info-box p {
        margin-bottom: 5px;
        color: #6c757d;
    }
    
    .info-box strong {
        color: #333;
    }
    
    /* Print styles */
    @media print {
        .sidebar, .topbar, .footer, .btn {
            display: none !important;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .container-fluid {
            padding: 0 !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        .card-header {
            background-color: #f8f9fc !important;
            color: #333 !important;
        }
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .container-fluid {
            padding: 10px !important;
        }
        
        .card-body {
            padding: 15px !important;
        }
    }
</style>';

// Include the research header
include '../../include/research_header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Action buttons -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-eye me-3"></i>
            Chi tiết đề tài nghiên cứu
        </h1>
        <div>
            <a href="manage_projects.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Quay lại danh sách
            </a>
            <?php if ($project['DT_TRANGTHAI'] === 'Chờ duyệt'): ?>
            <a href="review_projects.php" class="btn btn-sm btn-primary ms-2">
                <i class="fas fa-check-circle fa-sm text-white-50 me-1"></i> Đến trang phê duyệt
            </a>
            <?php endif; ?>
            <a href="#" class="btn btn-sm btn-info ms-2" onclick="window.print()">
                <i class="fas fa-print fa-sm text-white-50 me-1"></i> In
            </a>
        </div>
    </div>

    <!-- Project Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-project-diagram me-2"></i>
                        Tổng quan đề tài
                    </h6>
                    <span class="badge badge-<?php 
                        if ($project['DT_TRANGTHAI'] == 'Đã hoàn thành') echo 'success';
                        elseif ($project['DT_TRANGTHAI'] == 'Đang thực hiện') echo 'primary';
                        elseif ($project['DT_TRANGTHAI'] == 'Chờ duyệt') echo 'warning';
                        else echo 'danger';
                    ?>">
                        <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="font-weight-bold text-primary mb-3"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <h5><i class="fas fa-info-circle me-2"></i>Thông tin cơ bản</h5>
                                        <p><strong>Mã đề tài:</strong> <?php echo htmlspecialchars($project['DT_MADT']); ?></p>
                                        <p><strong>Loại đề tài:</strong> <?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không xác định'); ?></p>
                                        <p><strong>Lĩnh vực nghiên cứu:</strong> <?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Không xác định'); ?></p>
                                        <p><strong>Lĩnh vực ưu tiên:</strong> <?php echo htmlspecialchars($project['LVUT_TEN'] ?? 'Không xác định'); ?></p>
                                        <p><strong>Số lượng SV:</strong> <?php echo htmlspecialchars($project['DT_SLSV']); ?> sinh viên</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <h5><i class="fas fa-calendar-alt me-2"></i>Thời gian</h5>
                                        <p><strong>Ngày tạo:</strong> <?php echo !empty($project['DT_NGAYTAO']) ? date('d/m/Y H:i', strtotime($project['DT_NGAYTAO'])) : 'N/A'; ?></p>
                                        <p><strong>Ngày cập nhật:</strong> <?php echo !empty($project['DT_NGAYCAPNHAT']) ? date('d/m/Y H:i', strtotime($project['DT_NGAYCAPNHAT'])) : 'N/A'; ?></p>
                                        <p><strong>Người cập nhật:</strong> <?php echo htmlspecialchars($project['DT_NGUOICAPNHAT'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="icon-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-flask fa-3x"></i>
                                </div>
                                <h5 class="text-primary">Đề tài nghiên cứu</h5>
                                <p class="text-muted">Hệ thống quản lý nghiên cứu khoa học</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="info-box">
                                <h5><i class="fas fa-file-alt me-2"></i>Mô tả đề tài</h5>
                                <p><?php echo nl2br(htmlspecialchars($project['DT_MOTA'] ?? 'Không có mô tả')); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($project['DT_GHICHU'])): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="info-box">
                                <h5><i class="fas fa-sticky-note me-2"></i>Ghi chú</h5>
                                <p><?php echo nl2br(htmlspecialchars($project['DT_GHICHU'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Information Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list-alt me-2"></i>
                        Thông tin chi tiết
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Navigation tabs -->
                    <ul class="nav nav-tabs" id="projectTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="team-tab" data-toggle="tab" data-target="#team" type="button" role="tab">
                                <i class="fas fa-users me-2"></i>Nhóm nghiên cứu
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="proposal-tab" data-toggle="tab" data-target="#proposal" type="button" role="tab">
                                <i class="fas fa-file-alt me-2"></i>Thuyết minh
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contract-tab" data-toggle="tab" data-target="#contract" type="button" role="tab">
                                <i class="fas fa-file-contract me-2"></i>Hợp đồng
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="decision-tab" data-toggle="tab" data-target="#decision" type="button" role="tab">
                                <i class="fas fa-gavel me-2"></i>Quyết định
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="evaluation-tab" data-toggle="tab" data-target="#evaluation" type="button" role="tab">
                                <i class="fas fa-star me-2"></i>Đánh giá nghiệm thu
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="overview-tab" data-toggle="tab" data-target="#overview" type="button" role="tab">
                                <i class="fas fa-chart-pie me-2"></i>Tổng quan kết quả
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content mt-4" id="projectTabsContent">
                        <!-- Team Tab -->
                        <div class="tab-pane fade show active" id="team" role="tabpanel">
                            <div class="row">
                                <!-- Lecturer Information -->
                                <div class="col-md-6">
                                    <div class="card border-left-primary">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-chalkboard-teacher me-2"></i>
                                                Giảng viên hướng dẫn
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($project['GV_MAGV'])): ?>
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="icon-circle bg-primary text-white d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                                <div>
                                                    <h6 class="font-weight-bold mb-1"><?php echo htmlspecialchars($project['GV_HOTEN']); ?></h6>
                                                    <p class="text-muted mb-0">Giảng viên hướng dẫn</p>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-12">
                                                    <p><strong>Mã giảng viên:</strong> <?php echo htmlspecialchars($project['GV_MAGV']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($project['GV_EMAIL'] ?? 'N/A'); ?></p>
                                                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($project['GV_SDT'] ?? 'N/A'); ?></p>
                                                    <p><strong>Đơn vị:</strong> <?php echo htmlspecialchars($project['DV_TENDV'] ?? 'N/A'); ?></p>
                                                    <?php if (!empty($project['GV_CHUYENMON'])): ?>
                                                    <p><strong>Chuyên môn:</strong> <?php echo htmlspecialchars($project['GV_CHUYENMON']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <a href="researcher_details.php?role=teacher&id=<?php echo htmlspecialchars($project['GV_MAGV']); ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-user mr-1"></i> Xem hồ sơ giảng viên
                                            </a>
                                            <?php else: ?>
                                            <p class="text-center text-muted">Không có thông tin giảng viên hướng dẫn.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Student Information -->
                                <div class="col-md-6">
                                    <div class="card border-left-success">
                                        <div class="card-header d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-user-graduate me-2"></i>
                                                Sinh viên tham gia
                                            </h6>
                                            <span class="badge badge-info"><?php echo count($students); ?> sinh viên</span>
                                        </div>
                                        <div class="card-body">
                                            <?php if (count($students) > 0): ?>
                                            <div class="list-group">
                                                <?php foreach ($students as $student): ?>
                                                <div class="list-group-item border-left-success">
                                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1 font-weight-bold">
                                                                <?php echo htmlspecialchars($student['SV_HOTEN']); ?>
                                                                <span class="badge badge-<?php echo $student['CTTG_VAITRO'] == 'Chủ nhiệm' ? 'primary' : 'secondary'; ?> ms-2">
                                                                    <?php echo htmlspecialchars($student['CTTG_VAITRO']); ?>
                                                                </span>
                                                            </h6>
                                                            <p class="mb-1">
                                                                <small>
                                                                    <strong>MSSV:</strong> <?php echo htmlspecialchars($student['SV_MASV']); ?> |
                                                                    <strong>Lớp:</strong> <?php echo htmlspecialchars($student['LOP_TEN'] ?? 'N/A'); ?> |
                                                                    <strong>Khoa:</strong> <?php echo htmlspecialchars($student['DV_TENDV'] ?? 'N/A'); ?>
                                                                </small>
                                                            </p>
                                                            <p class="mb-1">
                                                                <small>
                                                                    <strong>Ngày tham gia:</strong> <?php echo date('d/m/Y', strtotime($student['CTTG_NGAYTHAMGIA'])); ?> |
                                                                    <strong>Học kỳ:</strong> <?php echo htmlspecialchars($student['HK_TEN'] ?? 'N/A'); ?>
                                                                </small>
                                                            </p>
                                                        </div>
                                                        <a href="researcher_details.php?role=student&id=<?php echo htmlspecialchars($student['SV_MASV']); ?>" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-info-circle"></i> Chi tiết
                                                        </a>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php else: ?>
                                            <p class="text-center text-muted">Không có sinh viên tham gia đề tài này.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Proposal Tab -->
                        <div class="tab-pane fade" id="proposal" role="tabpanel">
                            <div class="row">
                                <!-- Project Proposal File -->
                                <div class="col-md-6">
                                    <div class="card border-left-primary">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-file-alt me-2"></i>
                                                File thuyết minh đề tài
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($project['DT_FILEBTM'])): ?>
                                            <div class="file-attachment">
                                                <div class="file-icon">
                                                    <i class="fas fa-file-word"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">File thuyết minh</h6>
                                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($project['DT_FILEBTM']); ?></p>
                                                </div>
                                                <a href="../../uploads/project_files/<?php echo htmlspecialchars($project['DT_FILEBTM']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                            <?php else: ?>
                                            <p class="text-center text-muted">Chưa có file thuyết minh.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- File Tracking -->
                                <div class="col-md-6">
                                    <div class="card border-left-info">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-paperclip me-2"></i>
                                                Lưu vết các file
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (count($files) > 0): ?>
                                            <?php foreach ($files as $file): ?>
                                            <div class="file-attachment">
                                                <div class="file-icon">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($file['FDG_TENFILE'] ?? $file['FDG_LOAI']); ?></h6>
                                                    <p class="mb-0 text-muted">
                                                        <?php echo htmlspecialchars($file['FDG_LOAI']); ?> | 
                                                        <?php echo date('d/m/Y', strtotime($file['FDG_NGAYTAO'])); ?>
                                                    </p>
                                                </div>
                                                <a href="../../uploads/member_evaluation_files/<?php echo htmlspecialchars($file['FDG_FILE']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <p class="text-center text-muted">Chưa có file nào được đính kèm.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Tab -->
                        <div class="tab-pane fade" id="contract" role="tabpanel">
                            <?php if (count($contracts) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Mã hợp đồng</th>
                                            <th>Ngày tạo</th>
                                            <th>Thời gian thực hiện</th>
                                            <th>Tổng kinh phí</th>
                                            <th>File hợp đồng</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contracts as $contract): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($contract['HD_MA']); ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($contract['HD_NGAYTAO'])); ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($contract['HD_NGAYBD'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($contract['HD_NGAYKT'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo number_format($contract['HD_TONGKINHPHI'], 0, ',', '.'); ?> VNĐ
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($contract['HD_FILEHD'])): ?>
                                                <a href="../../uploads/contract_files/<?php echo htmlspecialchars($contract['HD_FILEHD']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i> Tải xuống
                                                </a>
                                                <?php else: ?>
                                                <span class="text-muted">Không có file</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewContractDetails('<?php echo htmlspecialchars($contract['HD_MA']); ?>')">
                                                    <i class="fas fa-eye"></i> Xem chi tiết
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có hợp đồng nào</h5>
                                <p class="text-muted">Hợp đồng nghiên cứu sẽ được hiển thị tại đây khi được tạo.</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Decision Tab -->
                        <div class="tab-pane fade" id="decision" role="tabpanel">
                            <?php if ($decision): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-left-info">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-gavel me-2"></i>
                                                Quyết định nghiệm thu
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Số quyết định:</strong> <?php echo htmlspecialchars($decision['QD_SO']); ?></p>
                                            <p><strong>Ngày ra quyết định:</strong> <?php echo date('d/m/Y', strtotime($decision['QD_NGAY'])); ?></p>
                                            <?php if (!empty($decision['QD_FILE'])): ?>
                                            <p><strong>File quyết định:</strong> 
                                                <a href="../../uploads/decision_files/<?php echo htmlspecialchars($decision['QD_FILE']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i> Tải xuống
                                                </a>
                                            </p>
                                            <?php endif; ?>
                                            <?php if (!empty($decision['QD_NOIDUNG'])): ?>
                                            <p><strong>Nội dung:</strong> <?php echo nl2br(htmlspecialchars($decision['QD_NOIDUNG'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-left-warning">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-clipboard-check me-2"></i>
                                                Biên bản nghiệm thu
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Số biên bản:</strong> <?php echo htmlspecialchars($decision['BB_SOBB']); ?></p>
                                            <p><strong>Ngày nghiệm thu:</strong> <?php echo date('d/m/Y', strtotime($decision['BB_NGAYNGHIEMTHU'])); ?></p>
                                            <p><strong>Xếp loại:</strong> 
                                                <span class="badge badge-success"><?php echo htmlspecialchars($decision['BB_XEPLOAI']); ?></span>
                                            </p>
                                            <p><strong>Tổng điểm:</strong> 
                                                <span class="badge badge-info"><?php echo number_format($decision['BB_TONGDIEM'], 2); ?>/100</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-gavel fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có quyết định nghiệm thu</h5>
                                <p class="text-muted">Thông tin quyết định sẽ được hiển thị tại đây khi có quyết định.</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Evaluation Tab -->
                        <div class="tab-pane fade" id="evaluation" role="tabpanel">
                            <?php if ($decision && count($council_members) > 0): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-users-cog me-2"></i>
                                        Thành viên hội đồng nghiệm thu
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Họ tên</th>
                                                    <th>Vai trò</th>
                                                    <th>Tiêu chí đánh giá</th>
                                                    <th>Điểm</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày đánh giá</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($council_members as $member): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($member['GV_HOTEN'] ?? $member['TV_HOTEN']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            if (strpos($member['TV_VAITRO'], 'Chủ tịch') !== false) echo 'primary';
                                                            elseif (strpos($member['TV_VAITRO'], 'Phó chủ tịch') !== false) echo 'info';
                                                            elseif (strpos($member['TV_VAITRO'], 'Thư ký') !== false) echo 'success';
                                                            else echo 'secondary';
                                                        ?>">
                                                            <?php echo htmlspecialchars($member['TV_VAITRO']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($member['TC_TEN'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($member['TV_DIEM']): ?>
                                                        <span class="badge badge-success"><?php echo number_format($member['TV_DIEM'], 2); ?>/100</span>
                                                        <?php else: ?>
                                                        <span class="text-muted">Chưa đánh giá</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            if ($member['TV_TRANGTHAI'] == 'Đã hoàn thành') echo 'success';
                                                            elseif ($member['TV_TRANGTHAI'] == 'Đang đánh giá') echo 'warning';
                                                            else echo 'secondary';
                                                        ?>">
                                                            <?php echo htmlspecialchars($member['TV_TRANGTHAI']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo $member['TV_NGAYDANHGIA'] ? date('d/m/Y H:i', strtotime($member['TV_NGAYDANHGIA'])) : 'N/A'; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có đánh giá nghiệm thu</h5>
                                <p class="text-muted">Thông tin đánh giá nghiệm thu sẽ được hiển thị tại đây.</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Overview Tab -->
                        <div class="tab-pane fade" id="overview" role="tabpanel">
                            <div class="row">
                                <!-- Project Summary -->
                                <div class="col-md-6">
                                    <div class="card border-left-primary">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-chart-pie me-2"></i>
                                                Tổng quan đề tài
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <div class="info-box">
                                                        <h4 class="text-primary"><?php echo count($students); ?></h4>
                                                        <p class="text-muted">Sinh viên tham gia</p>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="info-box">
                                                        <h4 class="text-success"><?php echo count($contracts); ?></h4>
                                                        <p class="text-muted">Hợp đồng</p>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="info-box">
                                                        <h4 class="text-info"><?php echo count($progress_list); ?></h4>
                                                        <p class="text-muted">Cập nhật tiến độ</p>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="info-box">
                                                        <h4 class="text-warning"><?php echo count($reports); ?></h4>
                                                        <p class="text-muted">Báo cáo</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            
                                            <h6 class="font-weight-bold">Thông tin cơ bản</h6>
                                            <p><strong>Trạng thái:</strong> 
                                                <span class="badge badge-<?php 
                                                    if ($project['DT_TRANGTHAI'] == 'Hoàn thành') echo 'success';
                                                    elseif ($project['DT_TRANGTHAI'] == 'Đang thực hiện') echo 'info';
                                                    elseif ($project['DT_TRANGTHAI'] == 'Tạm dừng') echo 'warning';
                                                    else echo 'secondary';
                                                ?>">
                                                    <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                                </span>
                                            </p>
                                            <p><strong>Loại đề tài:</strong> <?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'N/A'); ?></p>
                                            <p><strong>Lĩnh vực nghiên cứu:</strong> <?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'N/A'); ?></p>
                                            <p><strong>Lĩnh vực ưu tiên:</strong> <?php echo htmlspecialchars($project['LVUT_TEN'] ?? 'N/A'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Results Summary -->
                                <div class="col-md-6">
                                    <div class="card border-left-success">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-trophy me-2"></i>
                                                Kết quả đạt được
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($decision): ?>
                                            <div class="text-center mb-4">
                                                <h3 class="text-success"><?php echo number_format($decision['BB_TONGDIEM'], 2); ?>/100</h3>
                                                <p class="text-muted">Điểm tổng kết</p>
                                                <span class="badge badge-success badge-lg"><?php echo htmlspecialchars($decision['BB_XEPLOAI']); ?></span>
                                            </div>
                                            
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo $decision['BB_TONGDIEM']; ?>%" 
                                                     aria-valuenow="<?php echo $decision['BB_TONGDIEM']; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            
                                            <p><strong>Ngày nghiệm thu:</strong> <?php echo date('d/m/Y', strtotime($decision['BB_NGAYNGHIEMTHU'])); ?></p>
                                            <p><strong>Số quyết định:</strong> <?php echo htmlspecialchars($decision['QD_SO']); ?></p>
                                            <p><strong>Số biên bản:</strong> <?php echo htmlspecialchars($decision['BB_SOBB']); ?></p>
                                            <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Chưa có kết quả</h5>
                                                <p class="text-muted">Kết quả đề tài sẽ được hiển thị sau khi nghiệm thu.</p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Files Tab -->
                        <div class="tab-pane fade" id="files" role="tabpanel">
                            <div class="row">
                                <!-- Project Files -->
                                <div class="col-md-6">
                                    <div class="card border-left-primary">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-file me-2"></i>
                                                File đề tài
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($project['DT_FILEBTM'])): ?>
                                            <div class="file-attachment">
                                                <div class="file-icon">
                                                    <i class="fas fa-file-word"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">File thuyết minh</h6>
                                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($project['DT_FILEBTM']); ?></p>
                                                </div>
                                                <a href="../../uploads/project_files/<?php echo htmlspecialchars($project['DT_FILEBTM']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                            <?php else: ?>
                                            <p class="text-center text-muted">Chưa có file thuyết minh.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Evaluation Files -->
                                <div class="col-md-6">
                                    <div class="card border-left-info">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-file-check me-2"></i>
                                                File đánh giá
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (count($files) > 0): ?>
                                            <?php foreach ($files as $file): ?>
                                            <div class="file-attachment">
                                                <div class="file-icon">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($file['FDG_TENFILE'] ?? $file['FDG_LOAI']); ?></h6>
                                                    <p class="mb-0 text-muted">
                                                        <?php echo htmlspecialchars($file['FDG_LOAI']); ?> | 
                                                        <?php echo date('d/m/Y', strtotime($file['FDG_NGAYTAO'])); ?>
                                                    </p>
                                                </div>
                                                <a href="../../uploads/member_evaluation_files/<?php echo htmlspecialchars($file['FDG_FILE']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <p class="text-center text-muted">Chưa có file đánh giá nào.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- History Tab -->
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <?php if (count($logs) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($logs as $log): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="timeline-title mb-0"><?php echo htmlspecialchars($log['LHD_HANHDONG']); ?></h5>
                                            <span class="badge badge-info">
                                                <?php echo date('d/m/Y H:i', strtotime($log['LHD_THOIGIAN'])); ?>
                                            </span>
                                        </div>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($log['LHD_NOIDUNG'])); ?></p>
                                        <p class="text-muted mb-0">
                                            <small>
                                                Thực hiện bởi: 
                                                <?php echo !empty($log['GV_HOTEN']) ? htmlspecialchars($log['GV_HOTEN']) : 'Hệ thống'; ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có hoạt động nào</h5>
                                <p class="text-muted">Lịch sử hoạt động của đề tài sẽ được hiển thị tại đây.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include '../../include/research_footer.php'; ?>

<!-- Include required JavaScript -->
<script>
// Print functionality
function printProject() {
    window.print();
}

// View contract details
function viewContractDetails(contractId) {
    // Có thể mở modal hoặc chuyển hướng đến trang chi tiết hợp đồng
    alert('Xem chi tiết hợp đồng: ' + contractId);
}

// Bootstrap tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize Bootstrap tabs
    $('#projectTabs button[data-toggle="tab"]').on('click', function (event) {
        event.preventDefault();
        $(this).tab('show');
    });
    
    // Auto-refresh progress data
    function refreshProgress() {
        // Có thể thêm AJAX call để cập nhật dữ liệu tiến độ
        console.log('Refreshing progress data...');
    }
    
    // Refresh every 5 minutes
    setInterval(refreshProgress, 300000);
    
    // File download tracking
    $('.file-attachment a').on('click', function() {
        var fileName = $(this).closest('.file-attachment').find('h6').text();
        console.log('Downloading file: ' + fileName);
        // Có thể thêm tracking analytics ở đây
    });
    
    // Status indicator animation
    $('.status-indicator').each(function() {
        $(this).addClass('pulse-animation');
    });
    
    // Progress bar animation
    $('.progress-bar').each(function() {
        var percentage = $(this).css('width');
        $(this).css('width', '0%').animate({
            width: percentage
        }, 1000);
    });
    
    // Card hover effects
    $('.card').hover(
        function() {
            $(this).addClass('shadow-lg');
        },
        function() {
            $(this).removeClass('shadow-lg');
        }
    );
    
    // Timeline animation
    $('.timeline-item').each(function(index) {
        $(this).css('opacity', '0').css('transform', 'translateX(-20px)');
        setTimeout(function() {
            $('.timeline-item').eq(index).animate({
                opacity: 1,
                transform: 'translateX(0)'
            }, 500);
        }, index * 200);
    });
    
    // Export functionality
    window.exportProjectData = function(format) {
        var projectData = {
            id: '<?php echo htmlspecialchars($project_id); ?>',
            name: '<?php echo htmlspecialchars($project['DT_TENDT']); ?>',
            status: '<?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>',
            students: <?php echo count($students); ?>,
            contracts: <?php echo count($contracts); ?>,
            progress: <?php echo count($progress_list); ?>
        };
        
        if (format === 'json') {
            var dataStr = JSON.stringify(projectData, null, 2);
            var dataBlob = new Blob([dataStr], {type: 'application/json'});
            var url = URL.createObjectURL(dataBlob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'project_<?php echo htmlspecialchars($project_id); ?>.json';
            link.click();
        } else if (format === 'csv') {
            // Implement CSV export
            console.log('CSV export not implemented yet');
        }
    };
    
    // Search functionality within tabs
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.timeline-item, .list-group-item, .table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // Real-time updates (if needed)
    function checkForUpdates() {
        // Có thể thêm WebSocket hoặc AJAX polling để cập nhật real-time
        $.ajax({
            url: 'check_project_updates.php',
            method: 'POST',
            data: { project_id: '<?php echo htmlspecialchars($project_id); ?>' },
            success: function(response) {
                if (response.hasUpdates) {
                    // Show notification
                    showNotification('Có cập nhật mới cho đề tài này', 'info');
                }
            }
        });
    }
    
    // Notification system
    function showNotification(message, type) {
        var alertClass = 'alert-' + type;
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                       message +
                       '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                       '</div>';
        
        $('.container-fluid').prepend(alertHtml);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
    
    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+P for print
        if (e.ctrlKey && e.keyCode === 80) {
            e.preventDefault();
            window.print();
        }
        
        // Ctrl+E for export
        if (e.ctrlKey && e.keyCode === 69) {
            e.preventDefault();
            exportProjectData('json');
        }
        
        // Tab navigation with arrow keys
        if (e.keyCode === 37) { // Left arrow
            var currentTab = $('.nav-tabs .nav-link.active');
            var prevTab = currentTab.parent().prev().find('.nav-link');
            if (prevTab.length) {
                prevTab.tab('show');
            }
        }
        
        if (e.keyCode === 39) { // Right arrow
            var currentTab = $('.nav-tabs .nav-link.active');
            var nextTab = currentTab.parent().next().find('.nav-link');
            if (nextTab.length) {
                nextTab.tab('show');
            }
        }
    });
    
    // Mobile responsive handling
    function handleMobileView() {
        if ($(window).width() < 768) {
            $('.nav-tabs').addClass('nav-pills');
            $('.card-body').addClass('p-2');
        } else {
            $('.nav-tabs').removeClass('nav-pills');
            $('.card-body').removeClass('p-2');
        }
    }
    
    $(window).resize(handleMobileView);
    handleMobileView();
    
    // Performance optimization
    $('.tab-pane').on('shown.bs.tab', function() {
        // Lazy load content for better performance
        var tabId = $(this).attr('id');
        console.log('Tab activated: ' + tabId);
    });
    
    // Accessibility improvements
    $('.card').attr('tabindex', '0');
    $('.btn').attr('tabindex', '0');
    
    // Add ARIA labels
    $('.nav-link').each(function() {
        var text = $(this).text();
        $(this).attr('aria-label', 'Tab: ' + text);
    });
    
    // Focus management
    $('.nav-link').on('shown.bs.tab', function() {
        var targetId = $(this).attr('data-target');
        $(targetId).find('.card').first().focus();
    });
});

// Additional utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('vi-VN');
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('vi-VN');
}

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    // Có thể gửi lỗi về server để logging
});

// Performance monitoring
window.addEventListener('load', function() {
    var loadTime = performance.now();
    console.log('Page loaded in: ' + loadTime + 'ms');
});
</script>
