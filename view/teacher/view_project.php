<?php
// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Kiểm tra xem có ID đề tài được truyền vào không
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Không tìm thấy mã đề tài.';
    header('Location: manage_projects.php');
    exit;
}

$project_id = $_GET['id'];
$teacher_id = $_SESSION['user_id'];

// Lấy thông tin chi tiết về đề tài
$sql = "SELECT dt.*, ldt.LDT_TENLOAI, lvnc.LVNC_TEN, lvnc.LVNC_MOTA as lvnc_mota, 
               lvut.LVUT_TEN, lvut.LVUT_MOTA as lvut_mota, qd.QD_NGAY, qd.QD_FILE,
               gv.GV_HOGV, gv.GV_TENGV, gv.GV_EMAIL, gv.GV_SDT
        FROM de_tai_nghien_cuu dt 
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        WHERE dt.DT_MADT = ? AND dt.GV_MAGV = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}

$stmt->bind_param("ss", $project_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

// Kiểm tra xem đề tài có tồn tại và thuộc về giảng viên hiện tại không
if (!$project) {
    $_SESSION['error_message'] = 'Bạn không có quyền xem đề tài này hoặc đề tài không tồn tại.';
    header('Location: manage_projects.php');
    exit;
}

// Lấy thông tin hợp đồng
$contract = null;
$sql_contract = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($sql_contract);
if ($stmt) {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $contract = $result->fetch_assoc();
    }
    $stmt->close();
}

// Lấy danh sách sinh viên tham gia
$sql_students = "SELECT sv.*, cttg.CTTG_VAITRO, cttg.CTTG_NGAYTHAMGIA, cttg.HK_MA, 
                      l.LOP_TEN, hk.HK_TEN
                FROM chi_tiet_tham_gia cttg 
                JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                JOIN lop l ON sv.LOP_MA = l.LOP_MA
                JOIN hoc_ki hk ON cttg.HK_MA = hk.HK_MA
                WHERE cttg.DT_MADT = ?
                ORDER BY cttg.CTTG_VAITRO = 'Chủ nhiệm' DESC, cttg.CTTG_NGAYTHAMGIA ASC";

$stmt = $conn->prepare($sql_students);
$students = [];
if ($stmt) {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

// Lấy danh sách tiến độ đề tài
$sql_progress = "SELECT * FROM tien_do_de_tai WHERE DT_MADT = ? ORDER BY TDDT_NGAYCAPNHAT DESC";
$stmt = $conn->prepare($sql_progress);
$progress = [];
if ($stmt) {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $progress[] = $row;
    }
    $stmt->close();
}

// Lấy danh sách báo cáo
$sql_reports = "SELECT bc.*, lbc.LBC_TENLOAI, sv.SV_HOSV, sv.SV_TENSV 
                FROM bao_cao bc
                LEFT JOIN loai_bao_cao lbc ON bc.LBC_MALOAI = lbc.LBC_MALOAI
                LEFT JOIN sinh_vien sv ON bc.SV_MASV = sv.SV_MASV
                WHERE bc.DT_MADT = ?
                ORDER BY bc.BC_NGAYNOP DESC";
$stmt = $conn->prepare($sql_reports);
$reports = [];
if ($stmt) {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    $stmt->close();
}

// Định dạng các mức trạng thái và màu sắc tương ứng
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Chờ duyệt':
            return 'badge-warning';
        case 'Đang thực hiện':
            return 'badge-primary';
        case 'Đã hoàn thành':
            return 'badge-success';
        case 'Tạm dừng':
            return 'badge-info';
        case 'Đã hủy':
            return 'badge-danger';
        case 'Đang xử lý':
            return 'badge-secondary';
        default:
            return 'badge-dark';
    }
}

// Định dạng các mức trạng thái báo cáo và màu sắc tương ứng
function getReportStatusBadgeClass($status) {
    switch ($status) {
        case 'Chờ duyệt':
            return 'badge-warning';
        case 'Đã duyệt':
            return 'badge-success';
        case 'Yêu cầu sửa':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Hàm hiển thị thời gian đã trôi qua
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "Vừa xong";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " phút trước";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " giờ trước";
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . " ngày trước";
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . " tháng trước";
    } else {
        return floor($diff / 31536000) . " năm trước";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đề tài | <?php echo $project['DT_TENDT']; ?></title>
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
    
    <style>
        .project-header {
            background: linear-gradient(to right, #4e73df, #224abe);
            color: white;
            padding: 2rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .project-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 0.25rem;
        }
        
        .tab-pane {
            padding: 1.5rem;
        }
        
        .progress {
            height: 10px;
        }
        
        .timeline {
            position: relative;
            padding-left: 1.5rem;
            list-style: none;
        }
        
        .timeline:before {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0.25rem;
            width: 2px;
            content: "";
            background-color: #e3e6f0;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item:before {
            position: absolute;
            top: 0;
            left: -0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            content: "";
            background-color: #4e73df;
        }
        
        .attachment-icon {
            font-size: 4rem;
            color: #4e73df;
        }
        
        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f1f3f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #4e73df;
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
                    <!-- Back Button -->
                    <a href="manage_projects.php" class="btn btn-sm btn-secondary mb-3">
                        <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách đề tài
                    </a>
                    
                    <!-- Project Header -->
                    <div class="project-header">
                        <div class="row">
                            <div class="col-md-8">
                                <h1 class="project-title"><?php echo $project['DT_TENDT']; ?></h1>
                                <p class="text-light mb-2">Mã đề tài: <?php echo $project['DT_MADT']; ?></p>
                                <div class="d-flex align-items-center mt-3">
                                    <span class="badge <?php echo getStatusBadgeClass($project['DT_TRANGTHAI']); ?> mr-2 py-1 px-2"><?php echo $project['DT_TRANGTHAI']; ?></span>
                                    <span class="badge badge-light mr-2 py-1 px-2"><?php echo $project['LDT_TENLOAI']; ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-light dropdown-toggle" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog mr-1"></i>Thao tác
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="actionDropdown">
                                        <a class="dropdown-item" href="edit_project.php?id=<?php echo $project_id; ?>">
                                            <i class="fas fa-edit mr-1"></i>Chỉnh sửa đề tài
                                        </a>
                                        <a class="dropdown-item" href="manage_students.php?id=<?php echo $project_id; ?>">
                                            <i class="fas fa-user-graduate mr-1"></i>Quản lý sinh viên
                                        </a>
                                        <a class="dropdown-item" href="project_reports.php?id=<?php echo $project_id; ?>">
                                            <i class="fas fa-file-alt mr-1"></i>Quản lý báo cáo
                                        </a>
                                        <a class="dropdown-item" href="project_progress.php?id=<?php echo $project_id; ?>">
                                            <i class="fas fa-tasks mr-1"></i>Cập nhật tiến độ
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#deleteProjectModal">
                                            <i class="fas fa-trash mr-1"></i>Xóa đề tài
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Project Content -->
                    <div class="row">
                        <!-- Left Column - Project Details -->
                        <div class="col-lg-8">
                            <!-- Nav tabs -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <ul class="nav nav-tabs card-header-tabs" id="projectTabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">
                                                <i class="fas fa-info-circle mr-1"></i>Tổng quan
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="progress-tab" data-toggle="tab" href="#progress" role="tab" aria-controls="progress" aria-selected="false">
                                                <i class="fas fa-tasks mr-1"></i>Tiến độ
                                                <span class="badge badge-primary ml-1"><?php echo count($progress); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="reports-tab" data-toggle="tab" href="#reports" role="tab" aria-controls="reports" aria-selected="false">
                                                <i class="fas fa-file-alt mr-1"></i>Báo cáo
                                                <span class="badge badge-primary ml-1"><?php echo count($reports); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="contract-tab" data-toggle="tab" href="#contract" role="tab" aria-controls="contract" aria-selected="false">
                                                <i class="fas fa-file-contract mr-1"></i>Hợp đồng
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content" id="projectTabContent">
                                        <!-- Tab: Overview -->
                                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                                            <div class="info-item">
                                                <div class="info-label">Mô tả đề tài</div>
                                                <p><?php echo nl2br($project['DT_MOTA']); ?></p>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Lĩnh vực nghiên cứu</div>
                                                        <p><?php echo $project['LVNC_TEN']; ?></p>
                                                        <small class="text-muted"><?php echo $project['lvnc_mota']; ?></small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Lĩnh vực ưu tiên</div>
                                                        <p><?php echo $project['LVUT_TEN']; ?></p>
                                                        <small class="text-muted"><?php echo $project['lvut_mota']; ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Quyết định nghiệm thu</div>
                                                        <p>Số: <?php echo $project['QD_SO']; ?></p>
                                                        <p>Ngày: <?php echo $project['QD_NGAY'] ? date('d/m/Y', strtotime($project['QD_NGAY'])) : 'Chưa có'; ?></p>
                                                        <?php if ($project['QD_FILE']): ?>
                                                            <a href="../../uploads/decisions/<?php echo $project['QD_FILE']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-file-pdf mr-1"></i>Xem quyết định
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">Thuyết minh đề tài</div>
                                                        <?php if ($project['DT_FILEBTM']): ?>
                                                            <div class="mb-2">
                                                                <a href="../../uploads/proposals/<?php echo $project['DT_FILEBTM']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-file-pdf mr-1"></i>Xem thuyết minh
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted">Chưa có file thuyết minh</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="info-item mt-4">
                                                <div class="info-label">Trạng thái đề tài</div>
                                                <div class="alert alert-<?php echo str_replace('badge-', 'alert-', getStatusBadgeClass($project['DT_TRANGTHAI'])); ?>" role="alert">
                                                    <i class="<?php 
                                                        $iconClass = 'fas fa-info-circle';
                                                        switch ($project['DT_TRANGTHAI']) {
                                                            case 'Chờ duyệt': $iconClass = 'fas fa-clock'; break;
                                                            case 'Đang thực hiện': $iconClass = 'fas fa-spinner fa-spin'; break;
                                                            case 'Đã hoàn thành': $iconClass = 'fas fa-check-circle'; break;
                                                            case 'Tạm dừng': $iconClass = 'fas fa-pause-circle'; break;
                                                            case 'Đã hủy': $iconClass = 'fas fa-times-circle'; break;
                                                        }
                                                        echo $iconClass;
                                                    ?> mr-2"></i>
                                                    Đề tài hiện đang ở trạng thái: <strong><?php echo $project['DT_TRANGTHAI']; ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab: Progress -->
                                        <div class="tab-pane fade" id="progress" role="tabpanel" aria-labelledby="progress-tab">
                                            <?php if (empty($progress)): ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Chưa có cập nhật tiến độ nào cho đề tài này.
                                                </div>
                                                <div class="text-center my-4">
                                                    <a href="project_progress.php?id=<?php echo $project_id; ?>" class="btn btn-primary">
                                                        <i class="fas fa-plus mr-1"></i>Thêm cập nhật tiến độ
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-4">
                                                    <a href="project_progress.php?id=<?php echo $project_id; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-plus mr-1"></i>Thêm cập nhật tiến độ
                                                    </a>
                                                </div>
                                                
                                                <ul class="timeline">
                                                    <?php foreach ($progress as $p): ?>
                                                        <?php 
                                                        // Lấy thông tin sinh viên cập nhật
                                                        $student_name = "Không xác định";
                                                        foreach ($students as $student) {
                                                            if ($student['SV_MASV'] == $p['SV_MASV']) {
                                                                $student_name = $student['SV_HOSV'] . ' ' . $student['SV_TENSV'];
                                                                break;
                                                            }
                                                        }
                                                        ?>
                                                        
                                                        <li class="timeline-item">
                                                            <div class="card mb-3">
                                                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0 font-weight-bold"><?php echo $p['TDDT_TIEUDE']; ?></h6>
                                                                    <span class="text-muted small"><?php echo timeAgo($p['TDDT_NGAYCAPNHAT']); ?></span>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="mb-3">
                                                                        <?php echo nl2br($p['TDDT_NOIDUNG']); ?>
                                                                    </div>
                                                                    
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <span class="text-muted small">Cập nhật bởi: <?php echo $student_name; ?></span>
                                                                        <span class="font-weight-bold"><?php echo $p['TDDT_PHANTRAMHOANTHANH']; ?>% hoàn thành</span>
                                                                    </div>
                                                                    
                                                                    <div class="progress mb-3">
                                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $p['TDDT_PHANTRAMHOANTHANH']; ?>%" 
                                                                            aria-valuenow="<?php echo $p['TDDT_PHANTRAMHOANTHANH']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                    
                                                                    <?php if ($p['TDDT_FILE']): ?>
                                                                        <a href="../../uploads/progress/<?php echo $p['TDDT_FILE']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-file mr-1"></i>Xem file đính kèm
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="card-footer bg-light text-muted small">
                                                                    Ngày cập nhật: <?php echo date('d/m/Y H:i', strtotime($p['TDDT_NGAYCAPNHAT'])); ?>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Tab: Reports -->
                                        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
                                            <?php if (empty($reports)): ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Chưa có báo cáo nào cho đề tài này.
                                                </div>
                                                <div class="text-center my-4">
                                                    <a href="project_reports.php?id=<?php echo $project_id; ?>" class="btn btn-primary">
                                                        <i class="fas fa-plus mr-1"></i>Thêm báo cáo
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-4">
                                                    <a href="project_reports.php?id=<?php echo $project_id; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-plus mr-1"></i>Thêm báo cáo
                                                    </a>
                                                </div>
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th style="width: 40%">Tên báo cáo</th>
                                                                <th>Loại báo cáo</th>
                                                                <th>Sinh viên nộp</th>
                                                                <th>Ngày nộp</th>
                                                                <th>Trạng thái</th>
                                                                <th>Thao tác</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($reports as $report): ?>
                                                                <tr>
                                                                    <td><?php echo $report['BC_TENBC']; ?></td>
                                                                    <td><?php echo $report['LBC_TENLOAI']; ?></td>
                                                                    <td><?php echo $report['SV_HOSV'] . ' ' . $report['SV_TENSV']; ?></td>
                                                                    <td><?php echo date('d/m/Y', strtotime($report['BC_NGAYNOP'])); ?></td>
                                                                    <td>
                                                                        <span class="badge <?php echo getReportStatusBadgeClass($report['BC_TRANGTHAI']); ?>">
                                                                            <?php echo $report['BC_TRANGTHAI']; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($report['BC_DUONGDAN']): ?>
                                                                            <a href="../../<?php echo $report['BC_DUONGDAN']; ?>" target="_blank" class="btn btn-sm btn-outline-primary mr-1">
                                                                                <i class="fas fa-eye"></i>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                        <a href="review_report.php?id=<?php echo $report['BC_MABC']; ?>" class="btn btn-sm btn-outline-info">
                                                                            <i class="fas fa-edit"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Tab: Contract -->
                                        <div class="tab-pane fade" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                                            <?php if (!$contract): ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Chưa có hợp đồng cho đề tài này.
                                                </div>
                                                <div class="text-center my-4">
                                                    <a href="create_contract.php?id=<?php echo $project_id; ?>" class="btn btn-primary">
                                                        <i class="fas fa-file-contract mr-1"></i>Tạo hợp đồng
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="card mb-4">
                                                    <div class="card-header bg-light">
                                                        <h6 class="mb-0 font-weight-bold">Thông tin hợp đồng</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="info-item">
                                                                    <div class="info-label">Mã hợp đồng</div>
                                                                    <p><?php echo $contract['HD_MA']; ?></p>
                                                                </div>
                                                                <div class="info-item">
                                                                    <div class="info-label">Ngày tạo</div>
                                                                    <p><?php echo date('d/m/Y', strtotime($contract['HD_NGAYTAO'])); ?></p>
                                                                </div>
                                                                <div class="info-item">
                                                                    <div class="info-label">Thời gian thực hiện</div>
                                                                    <p>Từ <?php echo date('d/m/Y', strtotime($contract['HD_NGAYBD'])); ?> đến <?php echo date('d/m/Y', strtotime($contract['HD_NGAYKT'])); ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="info-item">
                                                                    <div class="info-label">Tổng kinh phí</div>
                                                                    <p class="text-primary font-weight-bold"><?php echo number_format($contract['HD_TONGKINHPHI'], 0, ',', '.'); ?> VNĐ</p>
                                                                </div>
                                                                <div class="info-item">
                                                                    <div class="info-label">Ghi chú</div>
                                                                    <p><?php echo $contract['HD_GHICHU'] ? nl2br($contract['HD_GHICHU']) : 'Không có'; ?></p>
                                                                </div>
                                                                <?php if ($contract['HD_FILEHD']): ?>
                                                                    <div class="info-item">
                                                                        <div class="info-label">File hợp đồng</div>
                                                                        <a href="../../uploads/contracts/<?php echo $contract['HD_FILEHD']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                            <i class="fas fa-file-pdf mr-1"></i>Xem hợp đồng
                                                                        </a>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="text-right mt-3">
                                                            <a href="edit_contract.php?id=<?php echo $contract['HD_MA']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit mr-1"></i>Chỉnh sửa hợp đồng
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Nguồn kinh phí nếu có -->
                                                <?php
                                                $sql_funding = "SELECT * FROM nguon_kinh_phi WHERE HD_MA = ?";
                                                $stmt = $conn->prepare($sql_funding);
                                                $funding_sources = [];
                                                if ($stmt) {
                                                    $stmt->bind_param("s", $contract['HD_MA']);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    while ($row = $result->fetch_assoc()) {
                                                        $funding_sources[] = $row;
                                                    }
                                                }
                                                ?>
                                                
                                                <?php if (!empty($funding_sources)): ?>
                                                    <div class="card">
                                                        <div class="card-header bg-light">
                                                            <h6 class="mb-0 font-weight-bold">Nguồn kinh phí</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                                                                    <thead class="thead-light">
                                                                        <tr>
                                                                            <th>Mã nguồn kinh phí</th>
                                                                            <th>Tên nguồn</th>
                                                                            <th>Số tiền</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($funding_sources as $source): ?>
                                                                            <tr>
                                                                                <td><?php echo $source['NKP_MA']; ?></td>
                                                                                <td><?php echo $source['NKP_TENNGUON']; ?></td>
                                                                                <td><?php echo number_format($source['NKP_SOTIEN'], 0, ',', '.'); ?> VNĐ</td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Sidebar Information -->
                        <div class="col-lg-4">
                            <!-- Thông tin giảng viên hướng dẫn -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chalkboard-teacher mr-1"></i>Giảng viên hướng dẫn
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="font-weight-bold mb-3"><?php echo $project['GV_HOGV'] . ' ' . $project['GV_TENGV']; ?></h5>
                                    <p><i class="fas fa-envelope mr-2 text-primary"></i><?php echo $project['GV_EMAIL']; ?></p>
                                    <p><i class="fas fa-phone mr-2 text-primary"></i><?php echo $project['GV_SDT'] ?: 'Chưa cập nhật'; ?></p>
                                </div>
                            </div>
                            
                            <!-- Danh sách sinh viên tham gia -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-user-graduate mr-1"></i>Sinh viên tham gia
                                    </h6>
                                    <a href="manage_students.php?id=<?php echo $project_id; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-user-plus"></i>
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($students)): ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Chưa có sinh viên nào tham gia đề tài này.
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($students as $student): ?>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center">
                                                            <div class="student-avatar mr-3">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><?php echo $student['SV_HOSV'] . ' ' . $student['SV_TENSV']; ?></h6>
                                                                <p class="mb-0 small text-muted"><?php echo $student['SV_MASV']; ?></p>
                                                                <span class="badge badge-info"><?php echo $student['CTTG_VAITRO']; ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <p class="mb-0 small"><?php echo $student['LOP_TEN']; ?></p>
                                                        <p class="mb-0 small">Tham gia từ: <?php echo date('d/m/Y', strtotime($student['CTTG_NGAYTHAMGIA'])); ?></p>
                                                        <p class="mb-0 small">Học kỳ: <?php echo $student['HK_TEN']; ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Tiến độ đề tài -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-chart-line mr-1"></i>Tiến độ tổng quan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Tính phần trăm hoàn thành của đề tài từ cập nhật gần nhất
                                    $latest_progress = !empty($progress) ? $progress[0]['TDDT_PHANTRAMHOANTHANH'] : 0;
                                    
                                    // Tính màu cho progress bar
                                    $progress_color = 'bg-danger';
                                    if ($latest_progress > 30) $progress_color = 'bg-warning';
                                    if ($latest_progress > 60) $progress_color = 'bg-info';
                                    if ($latest_progress > 80) $progress_color = 'bg-success';
                                    
                                    // Tính trạng thái thời gian
                                    $time_status = '';
                                    $time_class = '';
                                    if ($contract) {
                                        $start_date = new DateTime($contract['HD_NGAYBD']);
                                        $end_date = new DateTime($contract['HD_NGAYKT']);
                                        $now = new DateTime();
                                        
                                        $total_days = $start_date->diff($end_date)->days;
                                        $days_passed = $start_date->diff($now)->days;
                                        
                                        if ($now < $start_date) {
                                            $time_status = 'Chưa bắt đầu';
                                            $time_class = 'text-info';
                                        } elseif ($now > $end_date) {
                                            $time_status = 'Đã hết thời gian';
                                            $time_class = 'text-danger';
                                        } else {
                                            $time_percent = ($days_passed / $total_days) * 100;
                                            $time_status = number_format($time_percent, 1) . '% thời gian đã trôi qua';
                                            
                                            if ($time_percent > $latest_progress + 20) {
                                                $time_class = 'text-danger';
                                            } elseif ($time_percent > $latest_progress + 10) {
                                                $time_class = 'text-warning';
                                            } else {
                                                $time_class = 'text-success';
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <h1 class="display-4 text-center mb-4"><?php echo $latest_progress; ?>%</h1>
                                    
                                    <div class="progress mb-4" style="height: 20px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated <?php echo $progress_color; ?>" role="progressbar" 
                                             style="width: <?php echo $latest_progress; ?>%" aria-valuenow="<?php echo $latest_progress; ?>" 
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    
                                    <?php if ($time_status): ?>
                                        <p class="mb-0 <?php echo $time_class; ?>">
                                            <i class="fas fa-clock mr-1"></i> <?php echo $time_status; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($contract): ?>
                                        <div class="d-flex justify-content-between mt-3">
                                            <small class="text-muted">Bắt đầu: <?php echo date('d/m/Y', strtotime($contract['HD_NGAYBD'])); ?></small>
                                            <small class="text-muted">Kết thúc: <?php echo date('d/m/Y', strtotime($contract['HD_NGAYKT'])); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Files đính kèm -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-paperclip mr-1"></i>Tài liệu đính kèm
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <?php if ($project['DT_FILEBTM']): ?>
                                            <a href="../../uploads/proposals/<?php echo $project['DT_FILEBTM']; ?>" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                                                <i class="fas fa-file-pdf text-danger mr-3 fa-2x"></i>
                                                <div>
                                                    <h6 class="mb-0">Thuyết minh đề tài</h6>
                                                    <small class="text-muted">Bản thuyết minh chi tiết</small>
                                                </div>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($project['QD_FILE']): ?>
                                            <a href="../../uploads/decisions/<?php echo $project['QD_FILE']; ?>" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                                                <i class="fas fa-file-signature text-primary mr-3 fa-2x"></i>
                                                <div>
                                                    <h6 class="mb-0">Quyết định nghiệm thu</h6>
                                                    <small class="text-muted"><?php echo $project['QD_SO']; ?></small>
                                                </div>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($contract) && $contract['HD_FILEHD']): ?>
                                            <a href="../../uploads/contracts/<?php echo $contract['HD_FILEHD']; ?>" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                                                <i class="fas fa-file-contract text-success mr-3 fa-2x"></i>
                                                <div>
                                                    <h6 class="mb-0">Hợp đồng thực hiện</h6>
                                                    <small class="text-muted"><?php echo $contract['HD_MA']; ?></small>
                                                </div>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (empty($project['DT_FILEBTM']) && empty($project['QD_FILE']) && (empty($contract) || empty($contract['HD_FILEHD']))): ?>
                                            <div class="text-muted text-center py-3">Không có tài liệu đính kèm</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <a href="upload_documents.php?id=<?php echo $project_id; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-upload mr-1"></i>Tải lên tài liệu
                                        </a>
                                    </div>
                                </div>
                            </div>
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

    <!-- Delete Project Modal -->
    <div class="modal fade" id="deleteProjectModal" tabindex="-1" role="dialog" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProjectModalLabel">Xác nhận xóa đề tài</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa đề tài "<strong><?php echo $project['DT_TENDT']; ?></strong>" không?</p>
                    <p class="text-danger">Lưu ý: Thao tác này không thể hoàn tác và sẽ xóa tất cả dữ liệu liên quan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <form action="delete_project.php" method="post">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        <button type="submit" class="btn btn-danger">Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    
    <!-- SB Admin 2 JS từ CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Lưu tab đang active vào localStorage khi chuyển tab
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                localStorage.setItem('activeProjectTab', $(e.target).attr('href'));
            });
            
            // Kiểm tra xem có tab nào được lưu trong localStorage không
            var activeTab = localStorage.getItem('activeProjectTab');
            if (activeTab) {
                $('a[href="' + activeTab + '"]').tab('show');
            }
        });
    </script>
</body>
</html>