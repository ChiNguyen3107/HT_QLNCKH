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

// Lấy thông tin dự án
$sql = "SELECT dt.*, 
        ldt.LDT_TENLOAI, 
        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN, 
        gv.GV_MAGV,
        gv.GV_EMAIL,
        k.DV_TENDV
        FROM de_tai_nghien_cuu dt
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
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
                k.DV_TENDV
                FROM chi_tiet_tham_gia ct
                JOIN sinh_vien sv ON ct.SV_MASV = sv.SV_MASV
                LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
                LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
                WHERE ct.DT_MADT = ?
                ORDER BY sv.SV_TENSV";

$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $project_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$students = [];

while ($student = $student_result->fetch_assoc()) {
    $students[] = $student;
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
    /* Layout positioning */
    #content-wrapper {
        margin-left: 280px !important;
        width: calc(100% - 280px) !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    .container-fluid {
        padding-left: 15px !important;
        padding-right: 15px !important;
        max-width: none !important;
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
    }
</style>';

// Include the research header
include '../../include/research_header.php';
?>

<!-- Sidebar đã được include trong header -->

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Action buttons -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-eye me-3"></i>
            Chi tiết đề tài
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

                <div class="row">
                    <!-- Project Information -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Thông tin đề tài</h6>
                                <span class="badge badge-<?php 
                                    if ($project['DT_TRANGTHAI'] == 'Đã hoàn thành') echo 'success';
                                    elseif ($project['DT_TRANGTHAI'] == 'Đang tiến hành') echo 'primary';
                                    elseif ($project['DT_TRANGTHAI'] == 'Chờ phê duyệt') echo 'warning';
                                    else echo 'danger';
                                ?>">
                                    <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h4 class="font-weight-bold text-primary"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h4>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Mã đề tài:</strong>
                                            <p><?php echo htmlspecialchars($project['DT_MADT']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Loại đề tài:</strong>
                                            <p><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không xác định'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Ngày tạo:</strong>
                                            <p>
                                                <?php 
                                                    echo !empty($project['DT_NGAYTAO']) ? date('d/m/Y', strtotime($project['DT_NGAYTAO'])) : 'N/A'; 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Ngày cập nhật:</strong>
                                            <p>
                                                <?php 
                                                    echo !empty($project['DT_NGAYCAPNHAT']) ? date('d/m/Y', strtotime($project['DT_NGAYCAPNHAT'])) : 'N/A'; 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-4">
                                            <strong class="text-gray-800">Mô tả:</strong>
                                            <p class="mt-2">
                                                <?php 
                                                    echo nl2br(htmlspecialchars($project['DT_MOTA'] ?? 'Không có mô tả')); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Mục tiêu:</strong>
                                            <p class="mt-2">
                                                <?php 
                                                    echo nl2br(htmlspecialchars($project['DT_MUCTIEU'] ?? 'Không có mục tiêu')); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Yêu cầu:</strong>
                                            <p class="mt-2">
                                                <?php 
                                                    echo nl2br(htmlspecialchars($project['DT_YEUCAU'] ?? 'Không có yêu cầu')); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($project['DT_KETQUA'])): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Kết quả:</strong>
                                            <p class="mt-2">
                                                <?php 
                                                    echo nl2br(htmlspecialchars($project['DT_KETQUA'])); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($project['DT_GHICHU'])): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <strong class="text-gray-800">Ghi chú:</strong>
                                            <p class="mt-2">
                                                <?php 
                                                    echo nl2br(htmlspecialchars($project['DT_GHICHU'])); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Project history -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Lịch sử hoạt động</h6>
                            </div>
                            <div class="card-body">
                                <?php if (count($logs) > 0): ?>
                                <div class="timeline">
                                    <?php foreach ($logs as $log): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h5 class="timeline-title">
                                                <?php echo htmlspecialchars($log['LHD_HANHDONG']); ?>
                                                <span class="badge badge-info">
                                                    <?php echo date('d/m/Y H:i', strtotime($log['LHD_THOIGIAN'])); ?>
                                                </span>
                                            </h5>
                                            <p><?php echo nl2br(htmlspecialchars($log['LHD_NOIDUNG'])); ?></p>
                                            <p class="text-muted">
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
                                <p class="text-center">Chưa có hoạt động nào được ghi nhận.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-lg-5">
                        <!-- Lecturer Information -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Thông tin giảng viên</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($project['GV_MAGV'])): ?>
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 50%;">
                                            <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold"><?php echo htmlspecialchars($project['GV_HOTEN']); ?></div>
                                        <div class="text-gray-600">Giảng viên</div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <strong class="text-gray-800">Mã giảng viên:</strong>
                                    <p><?php echo htmlspecialchars($project['GV_MAGV']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <strong class="text-gray-800">Email:</strong>
                                    <p><?php echo htmlspecialchars($project['GV_EMAIL'] ?? 'N/A'); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <strong class="text-gray-800">Đơn vị:</strong>
                                    <p><?php echo htmlspecialchars($project['DV_TENDV'] ?? 'N/A'); ?></p>
                                </div>
                                
                                <a href="researcher_details.php?role=teacher&id=<?php echo htmlspecialchars($project['GV_MAGV']); ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-user mr-1"></i> Xem hồ sơ giảng viên
                                </a>
                                <?php else: ?>
                                <p class="text-center">Không có thông tin giảng viên.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Student Information -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Sinh viên tham gia</h6>
                                <span class="badge badge-info"><?php echo count($students); ?> sinh viên</span>
                            </div>
                            <div class="card-body">
                                <?php if (count($students) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($students as $student): ?>
                                    <div class="list-group-item border-left-success">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 font-weight-bold">
                                                <?php echo htmlspecialchars($student['SV_HOTEN']); ?>
                                            </h6>
                                        </div>
                                        <p class="mb-1">
                                            <small>
                                                <strong>MSSV:</strong> <?php echo htmlspecialchars($student['SV_MASV']); ?>
                                            </small>
                                        </p>
                                        <p class="mb-1">
                                            <small>
                                                <strong>Lớp:</strong> <?php echo htmlspecialchars($student['LOP_TEN'] ?? 'N/A'); ?>
                                            </small>
                                        </p>
                                        <p class="mb-0">
                                            <a href="researcher_details.php?role=student&id=<?php echo htmlspecialchars($student['SV_MASV']); ?>" class="btn btn-sm btn-outline-success mt-2">
                                                <i class="fas fa-info-circle"></i> Chi tiết
                                            </a>
                                        </p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-center">Không có sinh viên tham gia đề tài này.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<style>
    /* Timeline styling */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
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
    
    @media print {
        #wrapper .sidebar {
            display: none !important;
        }
        
        .topbar, .footer {
            display: none !important;
        }
        
        .container-fluid {
            padding: 0 !important;
        }
        
        a {
            text-decoration: none !important;
        }
        
        .btn {
            display: none !important;
        }
        
        .card {
            border: none !important;
        }
        
        .card-header {
            background-color: #f8f9fc !important;
        }
    }
</style>

<?php
include '../../include/research_footer.php';
?>
