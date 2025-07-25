<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Helper function to format dates consistently throughout the application
function formatDate($date, $default = 'Chưa xác định') {
    if (isset($date) && !empty($date) && $date !== '0000-00-00') {
        try {
            return date('d/m/Y', strtotime($date));
        } catch (Exception $e) {
            return $default;
        }
    }
    return $default;
}

// Lấy ID đề tài từ URL
$project_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($project_id)) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài.";
    header('Location: student_manage_projects.php');
    exit;
}

// Lấy thông tin chi tiết của đề tài
$sql = "SELECT dt.*, 
               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
               gv.GV_EMAIL,
               ldt.LDT_TENLOAI,
               lvnc.LVNC_TEN,
               lvut.LVUT_TEN,
               hd.HD_NGAYTAO,
               hd.HD_NGAYBD,
               hd.HD_NGAYKT,
               hd.HD_TONGKINHPHI
        FROM de_tai_nghien_cuu dt
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
        WHERE dt.DT_MADT = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}

$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài với mã số " . htmlspecialchars($project_id);
    header('Location: student_manage_projects.php');
    exit;
}

$project = $result->fetch_assoc();

// Kiểm tra quyền truy cập: sinh viên chỉ có thể xem đề tài họ tham gia
$check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_access_sql);
$stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
$stmt->execute();
$access_result = $stmt->get_result();
$has_access = ($access_result->num_rows > 0);
$user_role = $has_access ? $access_result->fetch_assoc()['CTTG_VAITRO'] : '';

// Lấy danh sách thành viên tham gia
$member_sql = "SELECT sv.SV_MASV, CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN, 
               l.LOP_TEN, cttg.CTTG_VAITRO, cttg.CTTG_NGAYTHAMGIA
               FROM chi_tiet_tham_gia cttg
               JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
               JOIN lop l ON sv.LOP_MA = l.LOP_MA
               WHERE cttg.DT_MADT = ?";
$stmt = $conn->prepare($member_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = [];
while ($member = $members_result->fetch_assoc()) {
    $members[] = $member;
}

// Lấy thông tin tiến độ đề tài
$progress_sql = "SELECT td.*, CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN
               FROM tien_do_de_tai td
               LEFT JOIN sinh_vien sv ON td.SV_MASV = sv.SV_MASV
               WHERE td.DT_MADT = ? 
               ORDER BY td.TDDT_NGAYCAPNHAT DESC";
$stmt = $conn->prepare($progress_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$progress_result = $stmt->get_result();
$progress_entries = [];
while ($progress = $progress_result->fetch_assoc()) {
    $progress_entries[] = $progress;
}

// Tính phần trăm hoàn thành tổng thể
$overall_completion = 0;
$progress_count = count($progress_entries);
if ($progress_count > 0) {
    $latest_progress = $progress_entries[0];
    $overall_completion = $latest_progress['TDDT_PHANTRAMHOANTHANH'];
}

// Lấy thông tin file hợp đồng nếu có
$contract_sql = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$contract_result = $stmt->get_result();
$contract = $contract_result->num_rows > 0 ? $contract_result->fetch_assoc() : null;

// Lấy thông tin quyết định nghiệm thu và biên bản nếu có
$decision_sql = "SELECT qd.*, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI 
                FROM bien_ban bb
                JOIN quyet_dinh_nghiem_thu qd ON bb.BB_SOBB = qd.BB_SOBB
                WHERE bb.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";

// Thêm kiểm tra lỗi sau khi prepare
$stmt = $conn->prepare($decision_sql);
if ($stmt === false) {
    // Thêm log lỗi nhưng không dừng xử lý trang
    $decision_error = "Lỗi truy vấn quyết định nghiệm thu: " . $conn->error;
    $decision = null;
} else {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $decision_result = $stmt->get_result();
    $decision = $decision_result->num_rows > 0 ? $decision_result->fetch_assoc() : null;
}

// Lấy file đánh giá nếu có biên bản
$evaluation_files = [];
if ($decision) {
    $eval_files_sql = "SELECT * FROM file_danh_gia WHERE BB_SOBB = ?";
    $stmt = $conn->prepare($eval_files_sql);
    if ($stmt === false) {
        $eval_files_error = "Lỗi truy vấn file đánh giá: " . $conn->error;
    } else {
        $stmt->bind_param("s", $decision['BB_SOBB']);
        $stmt->execute();
        $eval_files_result = $stmt->get_result();
        while ($file = $eval_files_result->fetch_assoc()) {
            $evaluation_files[] = $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['DT_TENDT']); ?> | Chi tiết đề tài</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #2c68c9;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .content {
            padding-top: 20px;
            padding-left: 20px;
            padding-right: 20px;
            transition: all 0.3s ease;
        }        .project-header {
            background: linear-gradient(120deg, var(--primary), #5a8aef);
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .project-header p {
            transition: all 0.3s ease;
        }
        
        .project-header p:hover {
            transform: translateX(5px);
        }
        
        .project-header .badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }

        .project-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            background-color: rgba(255, 255, 255, 0.1);
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }

        .project-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            background-color: rgba(255, 255, 255, 0.08);
            width: 80px;
            height: 80px;
            border-radius: 50%;
        }

        .project-title {
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .status-badge {
            font-size: 0.95rem;
            padding: 8px 16px;
            font-weight: 500;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-primary {
            background-color: var(--primary);
        }

        .badge-success {
            background-color: var(--success);
        }

        .badge-info {
            background-color: var(--info);
        }

        .badge-danger {
            background-color: var(--danger);
        }

        .info-card {
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            font-weight: 500;
            background-color: #fff;
            border-bottom: 1px solid #eaedf2;
            padding: 15px 20px;
        }

        .card-body {
            padding: 20px;
        }

        .member-card {
            border-radius: 8px;
            transition: all 0.3s ease;
            padding: 15px !important;
            margin-bottom: 15px;
            border: 1px solid #eaedf2;
        }

        .member-card:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .member-card.current-user {
            background-color: #e8f4fe;
            border-left: 4px solid var(--primary);
        }

        .avatar {
            width: 45px !important;
            height: 45px !important;
            background: linear-gradient(120deg, var(--primary), #5a8aef);
            color: white;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 25px;
            padding-left: 20px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .timeline-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 22px;
            height: calc(100% - 15px);
            width: 2px;
            background-color: #e0e0e0;
        }

        .timeline-item:last-child::before {
            height: 0;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: -38px;
            top: 15px;
            height: 16px;
            width: 16px;
            border-radius: 50%;
            background-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(44, 104, 201, 0.2);
            z-index: 1;
        }

        .timeline-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: inline-block;
            background-color: #f8f9fa;
            padding: 3px 10px;
            border-radius: 15px;
        }

        .timeline-title {
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .progress-badge {
            background-color: #e3f2fd;
            color: var(--primary);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .file-upload-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px dashed #dee2e6;
            transition: all 0.3s ease;
        }

        .file-upload-form:hover {
            border-color: var(--primary);
            background-color: #f0f7ff;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
            background-color: #fff;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .file-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-icon {
            color: var(--primary);
            font-size: 1.2rem;
            margin-right: 10px;
        }

        .nav-tabs .nav-link {
            font-weight: 500;
            color: #495057;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 15px;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: transparent;
            border-bottom: 2px solid var(--primary);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 10px rgba(44, 104, 201, 0.2);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2456a8;
            border-color: #2456a8;
            box-shadow: 0 6px 15px rgba(44, 104, 201, 0.3);
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .custom-file-label {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            padding-right: 90px;
        }

        .custom-file-input:lang(vi)~.custom-file-label::after {
            content: "Chọn file";
        }

        .project-progress {
            height: 8px;
            border-radius: 4px;
            margin: 15px 0;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            background: linear-gradient(to right, var(--primary), #5a8aef);
        }

        .progress-label {
            font-weight: 500;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .feature-icon {
            background-color: #e8f4fe;
            color: var(--primary);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
            box-shadow: 0 3px 10px rgba(44, 104, 201, 0.15);
        }

        .feature-text {
            font-weight: 500;
            color: #495057;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--primary);
        }

        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background-color: #e0f8e9;
            color: #156c2e;
        }

        .alert-danger {
            background-color: #ffe7e7;
            color: #b02a37;
        }

        .alert-info {
            background-color: #e0f7fa;
            color: #0c6a82;
        }

        .alert-warning {
            background-color: #fff9e6;
            color: #997404;
        }

        /* Animation classes */
        .animate-fade-in {
            animation: fadeIn 0.5s ease;
        }

        .animate-slide-up {
            animation: slideUp 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .project-header {
                padding: 20px;
                margin-bottom: 20px;
            }

            .timeline {
                padding-left: 30px;
            }

            .timeline-item::before {
                left: -20px;
            }

            .timeline-item::after {
                left: -28px;
            }
        }

        @media (max-width: 768px) {
            .project-header {
                text-align: center;
            }

            .status-badge {
                margin-top: 15px;
                display: inline-block;
            }

            .col-md-4.text-md-right {
                text-align: center !important;
                margin-top: 15px;
            }

            .timeline {
                padding-left: 25px;
            }
        }        /* Trạng thái đề tài */
        .project-status-container {
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            animation: pulse 2s infinite;
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255,255,255, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255,255,255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255,255,255, 0);
            }
        }
        
        .bg-primary-soft {
            background-color: rgba(44, 104, 201, 0.2);
        }
        
        .bg-success-soft {
            background-color: rgba(40, 167, 69, 0.2);
        }
        
        .bg-warning-soft {
            background-color: rgba(255, 193, 7, 0.2);
        }
        
        .bg-info-soft {
            background-color: rgba(23, 162, 184, 0.2);
        }
        
        .bg-danger-soft {
            background-color: rgba(220, 53, 69, 0.2);
        }
        
        .bg-secondary-soft {
            background-color: rgba(108, 117, 125, 0.2);
        }

        /* Print styles */
        @media print {
            .sidebar, .sidebar-toggle, .no-print {
                display: none !important;
            }

            .content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .project-header {
                background: none !important;
                color: #000 !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin-bottom: 20px !important;
            }

            .card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #eee !important;
            }
        }
    </style>
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>
    <div class="container-fluid content" style="margin-left:250px; transition:all 0.3s;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4 animate-fade-in">
            <ol class="breadcrumb bg-white p-3 shadow-sm rounded">
                <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-tachometer-alt mr-1"></i>Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="student_manage_projects.php"><i class="fas fa-clipboard-list mr-1"></i>Quản lý đề tài</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-project-diagram mr-1"></i>Chi tiết đề tài</li>
            </ol>
        </nav>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate-slide-up" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate-slide-up" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Header đề tài -->
        <div class="project-header animate-fade-in">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-7">                    <h1 class="project-title"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h1>
                    <p class="mb-2 d-flex align-items-center">
                        <i class="fas fa-barcode mr-2"></i>
                        Mã đề tài: <span class="badge badge-light ml-2 p-2"><?php echo htmlspecialchars($project['DT_MADT']); ?></span>
                    </p>                    <p class="mb-2 d-flex align-items-center">
                        <i class="far fa-calendar-alt mr-2"></i>
                        Ngày tạo: <span class="ml-2"><?php echo formatDate($project['HD_NGAYTAO']); ?></span>
                    </p>
                    <p class="mb-2 d-flex align-items-center">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Thời gian thực hiện: <span class="ml-2">
                            <?php echo formatDate($project['HD_NGAYBD']) . ' - ' . formatDate($project['HD_NGAYKT']); ?>
                        </span>
                    </p>
                    <p class="mb-2 d-flex align-items-center">
                        <i class="fas fa-tag mr-2"></i>
                        Loại đề tài: <span class="ml-2"><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không xác định'); ?></span>
                    </p>
                    <p class="mb-2 d-flex align-items-center">
                        <i class="fas fa-microscope mr-2"></i>
                        Lĩnh vực nghiên cứu: <span class="ml-2"><?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Không xác định'); ?></span>
                    </p>
                    
                    <!-- Progress bar -->
                    <div class="mt-4">
                        <div class="progress-label">
                            <span>Tiến độ tổng thể</span>
                            <span><?php echo $overall_completion; ?>%</span>
                        </div>
                        <div class="progress project-progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $overall_completion; ?>%" 
                                aria-valuenow="<?php echo $overall_completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-5 text-md-right">
                    <!-- Trạng thái đề tài -->                    <div class="project-status-container">
                        <?php 
                        // Xác định class cho badge trạng thái
                        $status_class = '';
                        $status_icon = '';
                        switch ($project['DT_TRANGTHAI']) {
                            case 'Chờ duyệt':
                                $status_class = 'warning';
                                $status_icon = 'clock';
                                break;
                            case 'Đang thực hiện':
                                $status_class = 'primary';
                                $status_icon = 'play-circle';
                                break;
                            case 'Đã hoàn thành':
                                $status_class = 'success';
                                $status_icon = 'check-circle';
                                break;
                            case 'Tạm dừng':
                                $status_class = 'info';
                                $status_icon = 'pause-circle';
                                break;
                            case 'Đã hủy':
                                $status_class = 'danger';
                                $status_icon = 'times-circle';
                                break;
                            default:
                                $status_class = 'secondary';
                                $status_icon = 'question-circle';
                        }
                        ?>
                        <div class="status-badge bg-<?php echo $status_class; ?>-soft text-<?php echo $status_class; ?> animate-pulse">
                            <i class="fas fa-<?php echo $status_icon; ?> mr-2"></i>
                            <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                        </div>
                    </div>
                    
                    <?php if ($has_access): ?>
                        <div class="action-buttons mt-3">
                            <?php if ($project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addProgressModal">
                                    <i class="fas fa-tasks mr-1"></i> Cập nhật tiến độ
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-sm btn-outline-primary no-print" id="printProjectBtn">
                                <i class="fas fa-print mr-1"></i> In báo cáo
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Thông tin đề tài -->
            <div class="col-lg-8">
                <div class="card info-card animate-slide-up">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin đề tài</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-bookmark"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Loại đề tài</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-microscope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Lĩnh vực nghiên cứu</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Lĩnh vực ưu tiên</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['LVUT_TEN'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Ngày tạo</div>
                                        <div class="feature-text">
                                            <?php echo formatDate($project['DT_NGAYTAO']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>                                        <div class="text-muted small">Thời gian thực hiện</div>
                                        <div class="feature-text">
                                            <?php echo formatDate($project['HD_NGAYBD']) . ' - ' . formatDate($project['HD_NGAYKT']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Giảng viên hướng dẫn</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Chưa có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Liên hệ GVHD</div>
                                        <div class="feature-text">
                                            <?php if ($project['GV_EMAIL']): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($project['GV_EMAIL']); ?>" class="text-primary">
                                                    <?php echo htmlspecialchars($project['GV_EMAIL'] ?? ''); ?>
                                                </a>
                                            <?php else: ?>
                                                Không có thông tin
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($contract): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Kinh phí</div>
                                        <div class="feature-text"><?php echo number_format($contract['HD_TONGKINHPHI']); ?> VNĐ</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>

                        <div class="mt-3">
                            <h5 class="section-title"><i class="fas fa-align-left"></i> Mô tả đề tài</h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($project['DT_MOTA'] ?? 'Không có mô tả')); ?>
                            </div>
                        </div>

                        <?php if ($project['DT_FILEBTM']): ?>
                            <hr>
                            <div class="mt-3">
                                <h5 class="section-title"><i class="fas fa-file-alt"></i> File thuyết minh</h5>
                                <a href="/NLNganh/uploads/project_files/<?php echo htmlspecialchars($project['DT_FILEBTM']); ?>"
                                    class="btn btn-outline-primary" download>
                                    <i class="fas fa-file-download mr-2"></i> Tải xuống file thuyết minh
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tiến độ đề tài -->
                <div class="card info-card animate-slide-up" style="animation-delay: 0.2s">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tasks mr-2"></i>Tiến độ đề tài</h5>
                        <?php if ($has_access && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                            <button type="button" class="btn btn-sm btn-primary no-print" data-toggle="modal"
                                data-target="#addProgressModal">
                                <i class="fas fa-plus-circle mr-1"></i> Cập nhật tiến độ
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($progress_entries) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($progress_entries as $i => $entry): ?>
                                    <div class="timeline-item" style="animation-delay: <?php echo 0.1 * $i; ?>s">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="timeline-date">
                                                <i class="far fa-calendar-alt mr-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($entry['TDDT_NGAYCAPNHAT'])); ?>
                                            </div>
                                            <?php if ($entry['TDDT_PHANTRAMHOANTHANH'] > 0): ?>
                                                <span class="progress-badge">
                                                    <i class="fas fa-percentage mr-1"></i>
                                                    <?php echo $entry['TDDT_PHANTRAMHOANTHANH']; ?>%
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h6 class="timeline-title">
                                            <?php echo htmlspecialchars($entry['TDDT_TIEUDE']); ?>
                                            <?php if ($entry['SV_MASV'] === $_SESSION['user_id']): ?>
                                                <span class="badge badge-info ml-2">Bạn</span>
                                            <?php else: ?>
                                                <small class="text-muted ml-2">(<?php echo htmlspecialchars($entry['SV_HOTEN']); ?>)</small>
                                            <?php endif; ?>
                                        </h6>
                                        
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($entry['TDDT_NOIDUNG'])); ?></p>
                                        
                                        <?php if ($entry['TDDT_FILE']): ?>
                                            <a href="/NLNganh/uploads/progress_files/<?php echo htmlspecialchars($entry['TDDT_FILE']); ?>"
                                                class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-paperclip mr-1"></i>
                                                Tải file đính kèm
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> Chưa có cập nhật tiến độ nào.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nộp báo cáo -->
                <!-- <div class="card info-card animate-slide-up mb-4" style="animation-delay: 0.2s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-upload mr-2"></i>Nộp báo cáo</h5>
                    </div>
                    <div class="card-body">
                        <form action="submit_report.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                            
                            <div class="form-group">
                                <label for="report_title">
                                    <i class="fas fa-heading mr-1"></i> Tiêu đề báo cáo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="report_title" name="report_title" 
                                    placeholder="Nhập tiêu đề báo cáo" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="report_type">
                                    <i class="fas fa-tag mr-1"></i> Loại báo cáo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="report_type" name="report_type" required>
                                    <option value="">-- Chọn loại báo cáo --</option>
                                    <?php
                                    // Fetch report types from database
                                    $report_types_sql = "SELECT LBC_MALOAI, LBC_TENLOAI FROM loai_bao_cao";
                                    $report_types_result = $conn->query($report_types_sql);
                                    while ($type = $report_types_result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($type['LBC_MALOAI']) . '">' . 
                                            htmlspecialchars($type['LBC_TENLOAI']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="report_description">
                                    <i class="fas fa-align-left mr-1"></i> Mô tả báo cáo
                                </label>
                                <textarea class="form-control" id="report_description" name="report_description" 
                                    rows="3" placeholder="Nhập mô tả ngắn về báo cáo"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="report_file">
                                    <i class="fas fa-file mr-1"></i> File báo cáo <span class="text-danger">*</span>
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="report_file" name="report_file" required>
                                    <label class="custom-file-label" for="report_file">Chọn file...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Các định dạng hỗ trợ: PDF, Word, Excel, PowerPoint, ZIP, RAR. Kích thước tối đa: 20MB.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload mr-1"></i> Nộp báo cáo
                            </button>
                        </form>
                    </div>
                </div> -->
            </div>

            <!-- Sidebar bên phải -->
            <div class="col-lg-4">
                <!-- Thành viên tham gia -->
                <div class="card info-card animate-slide-up" style="animation-delay: 0.1s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Thành viên tham gia</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $member): ?>
                                <div class="member-card <?php echo ($member['SV_MASV'] === $_SESSION['user_id']) ? 'current-user' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar rounded-circle d-flex align-items-center justify-content-center">
                                            <?php echo strtoupper(mb_substr($member['SV_HOTEN'] ?? 'U', 0, 1, 'UTF-8')); ?>
                                        </div>
                                        <div class="ml-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($member['SV_HOTEN'] ?? 'Không rõ'); ?>
                                                <?php if ($member['SV_MASV'] === $_SESSION['user_id']): ?>
                                                    <span class="badge badge-info ml-1">Bạn</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-0 text-muted small">
                                                <span class="badge <?php echo (isset($member['CTTG_VAITRO']) && $member['CTTG_VAITRO'] == 'Chủ nhiệm') ? 'badge-primary' : 'badge-secondary'; ?>">
                                                    <?php echo htmlspecialchars($member['CTTG_VAITRO'] ?? 'Thành viên'); ?>
                                                </span>
                                                <span class="ml-2"><?php echo htmlspecialchars($member['LOP_TEN'] ?? 'Không rõ lớp'); ?></span>
                                            </p>
                                            <p class="mb-0 text-muted small mt-1">
                                                <i class="far fa-calendar-alt mr-1"></i>
                                                Tham gia: <?php echo isset($member['CTTG_NGAYTHAMGIA']) ? date('d/m/Y', strtotime($member['CTTG_NGAYTHAMGIA'])) : 'Chưa xác định'; ?>
                                            </p>
                                            <p class="mb-0 text-muted small mt-1">
                                                <i class="fas fa-clock mr-1"></i>
                                                Ngày tạo: <?php echo isset($member['CTTG_NGAYTAO']) ? date('d/m/Y', strtotime($member['CTTG_NGAYTAO'])) : date('d/m/Y'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i> Không tìm thấy thông tin thành viên.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quản lý file liên quan -->
                <div class="card info-card animate-slide-up" style="animation-delay: 0.2s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Tài liệu liên quan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="documentTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="contract-tab" data-toggle="tab" href="#contract" role="tab">
                                    <i class="fas fa-file-contract mr-1"></i> Hợp đồng
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="decision-tab" data-toggle="tab" href="#decision" role="tab">
                                    <i class="fas fa-file-signature mr-1"></i> Quyết định
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="evaluation-tab" data-toggle="tab" href="#evaluation" role="tab">
                                    <i class="fas fa-clipboard-check mr-1"></i> Đánh giá
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content" id="documentTabsContent">
                            <!-- Tab Hợp đồng -->
                            <div class="tab-pane fade show active" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                                <?php if ($contract): ?>
                                    <div class="mb-3">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-info-circle mr-2"></i>Thông tin hợp đồng</h6>
                                                <p class="mb-2"><strong>Mã hợp đồng:</strong>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($contract['HD_MA']); ?></span>
                                                </p>
                                                <p class="mb-2"><strong>Ngày tạo:</strong>
                                                    <?php echo isset($contract['HD_NGAYTAO']) ? date('d/m/Y', strtotime($contract['HD_NGAYTAO'])) : 'Chưa xác định'; ?>
                                                </p>
                                                <p class="mb-2"><strong>Thời gian thực hiện:</strong><br>
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($contract['HD_NGAYBD']) ? date('d/m/Y', strtotime($contract['HD_NGAYBD'])) : 'Chưa xác định'; ?> - 
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($contract['HD_NGAYKT']) ? date('d/m/Y', strtotime($contract['HD_NGAYKT'])) : 'Chưa xác định'; ?>
                                                </p>
                                                <p class="mb-2"><strong>Tổng kinh phí:</strong>
                                                    <span class="text-success font-weight-bold">
                                                        <?php echo isset($contract['HD_TONGKINHPHI']) ? number_format($contract['HD_TONGKINHPHI']) : '0'; ?> VNĐ
                                                    </span>
                                                </p>

                                                <?php if (isset($contract['HD_FILEHD']) && $contract['HD_FILEHD']): ?>
                                                    <hr>
                                                    <a href="/NLNganh/uploads/contract_files/<?php echo htmlspecialchars($contract['HD_FILEHD']); ?>"
                                                        class="btn btn-info btn-block" download>
                                                        <i class="fas fa-file-download mr-2"></i> Tải xuống hợp đồng
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($user_role === 'Chủ nhiệm'): ?>
                                    <hr>
                                    <h6 class="mb-3"><i class="fas fa-upload mr-2"></i>Cập nhật file hợp đồng</h6>
                                    <form action="upload_project_file.php" method="post" enctype="multipart/form-data"
                                        class="file-upload-form">
                                        <input type="hidden" name="project_id"
                                            value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                        <input type="hidden" name="file_type" value="contract">
                                        <input type="hidden" name="contract_id"
                                            value="<?php echo htmlspecialchars($contract['HD_MA']); ?>">
                                        <div class="custom-file mb-3">
                                            <input type="file" class="custom-file-input" id="contractFile"
                                                name="contract_file" required>
                                            <label class="custom-file-label" for="contractFile">Chọn file...</label>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-upload mr-1"></i> Cập nhật file
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Quyết định & biên bản -->
                            <div class="tab-pane fade" id="decision" role="tabpanel" aria-labelledby="decision-tab">
                                <?php if ($decision): ?>
                                    <div class="mb-3">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-gavel mr-2"></i>Thông tin quyết định nghiệm thu</h6>
                                                <p class="mb-2"><strong>Số quyết định:</strong>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($decision['QD_SO']); ?></span>
                                                </p>
                                                <p class="mb-2"><strong>Ngày quyết định:</strong>
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($decision['QD_NGAY']) ? date('d/m/Y', strtotime($decision['QD_NGAY'])) : 'Chưa xác định'; ?>
                                                </p>
                                                <p class="mb-2"><strong>Ngày nghiệm thu:</strong>
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($decision['BB_NGAYNGHIEMTHU']) ? date('d/m/Y', strtotime($decision['BB_NGAYNGHIEMTHU'])) : 'Chưa xác định'; ?>
                                                </p>
                                                <p class="mb-2"><strong>Xếp loại:</strong>
                                                    <span class="badge <?php 
                                                        $xeploai = isset($decision['BB_XEPLOAI']) ? $decision['BB_XEPLOAI'] : '';
                                                        echo ($xeploai == 'Xuất sắc' || $xeploai == 'Tốt') ? 'badge-success' : 
                                                            (($xeploai == 'Khá' || $xeploai == 'Đạt') ? 'badge-primary' : 'badge-secondary'); 
                                                        ?>">
                                                        <?php echo htmlspecialchars($xeploai ?: 'Chưa xác định'); ?>
                                                    </span>
                                                </p>

                                                <?php if (isset($decision['QD_FILE']) && $decision['QD_FILE']): ?>
                                                    <hr>
                                                    <a href="/NLNganh/uploads/decision_files/<?php echo htmlspecialchars($decision['QD_FILE']); ?>"
                                                        class="btn btn-info btn-block" download>
                                                        <i class="fas fa-file-download mr-2"></i> Tải xuống quyết định
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có quyết định nghiệm thu. Bạn có thể
                                        tải lên thông tin bằng cách điền form bên dưới.
                                    </div>
                                    <form action="upload_decision.php" method="post" enctype="multipart/form-data"
                                        class="file-upload-form">
                                        <input type="hidden" name="project_id"
                                            value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                        <div class="form-group">
                                            <label for="decision_number">
                                                <i class="fas fa-hashtag mr-1"></i> Số quyết định <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="decision_number"
                                                name="decision_number" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="decision_date">
                                                <i class="far fa-calendar-alt mr-1"></i> Ngày quyết định <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control" id="decision_date" name="decision_date" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="report_date">
                                                <i class="far fa-calendar-alt mr-1"></i> Ngày nghiệm thu <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control" id="report_date" name="report_date" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="report_grade">
                                                <i class="fas fa-award mr-1"></i> Xếp loại <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="report_grade" name="report_grade" required>
                                                <option value="">-- Chọn xếp loại --</option>
                                                <option value="Xuất sắc">Xuất sắc</option>
                                                <option value="Tốt">Tốt</option>
                                                <option value="Khá">Khá</option>
                                                <option value="Đạt">Đạt</option>
                                                <option value="Không đạt">Không đạt</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="decision_file">
                                                <i class="fas fa-file mr-1"></i> File quyết định <span class="text-danger">*</span>
                                            </label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="decision_file"
                                                    name="decision_file" required>
                                                <label class="custom-file-label" for="decision_file">Chọn file...</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-block mt-3">
                                            <i class="fas fa-plus-circle mr-1"></i> Tải lên quyết định
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có thông tin quyết định nghiệm thu.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Đánh giá -->
                            <div class="tab-pane fade" id="evaluation" role="tabpanel" aria-labelledby="evaluation-tab">
                                <?php if (count($evaluation_files) > 0): ?>
                                    <h6 class="text-primary mb-3"><i class="fas fa-file-alt mr-2"></i>Các file đánh giá</h6>
                                    <div class="list-group mb-3">
                                        <?php foreach ($evaluation_files as $file): ?>
                                            <div class="list-group-item list-group-item-action file-item">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div>
                                                        <i class="far fa-file-pdf file-icon"></i>
                                                        <span><?php echo htmlspecialchars($file['FDG_TEN'] ?? 'Không có tên'); ?></span>
                                                    </div>
                                                    <div>
                                                        <?php if (isset($file['FDG_DUONGDAN']) && $file['FDG_DUONGDAN']): ?>
                                                        <a href="/NLNganh/uploads/evaluation_files/<?php echo htmlspecialchars($file['FDG_DUONGDAN']); ?>"
                                                            class="btn btn-sm btn-outline-primary" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="far fa-calendar-alt mr-1"></i>
                                                    Ngày tạo: <?php echo isset($file['FDG_NGAYCAP']) ? date('d/m/Y', strtotime($file['FDG_NGAYCAP'])) : 'Chưa xác định'; ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($user_role === 'Chủ nhiệm'): ?>
                                        <hr>
                                        <h6 class="mb-3"><i class="fas fa-upload mr-2"></i>Thêm file đánh giá mới</h6>
                                        <form action="upload_evaluation_file.php" method="post" enctype="multipart/form-data"
                                            class="file-upload-form">
                                            <input type="hidden" name="project_id"
                                                value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                            <input type="hidden" name="report_id"
                                                value="<?php echo htmlspecialchars($decision['BB_SOBB']); ?>">
                                            <div class="form-group">
                                                <label for="evaluation_name">
                                                    <i class="fas fa-file-signature mr-1"></i> Tên file đánh giá <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="evaluation_name"
                                                    name="evaluation_name" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="evaluation_file">
                                                    <i class="fas fa-file mr-1"></i> File đánh giá <span class="text-danger">*</span>
                                                </label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="evaluation_file"
                                                        name="evaluation_file" required>
                                                    <label class="custom-file-label" for="evaluation_file">Chọn file...</label>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-block mt-3">
                                                <i class="fas fa-plus-circle mr-1"></i> Thêm file đánh giá
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                <?php elseif ($decision && $user_role === 'Chủ nhiệm'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có file đánh giá. Bạn có thể tải lên file đánh giá mới.
                                    </div>
                                    <form action="upload_evaluation_file.php" method="post" enctype="multipart/form-data"
                                        class="file-upload-form">
                                        <input type="hidden" name="project_id"
                                            value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                        <input type="hidden" name="report_id"
                                            value="<?php echo htmlspecialchars($decision['BB_SOBB']); ?>">
                                        <div class="form-group">
                                            <label for="evaluation_name">
                                                <i class="fas fa-file-signature mr-1"></i> Tên file đánh giá <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="evaluation_name"
                                                name="evaluation_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="evaluation_file">
                                                <i class="fas fa-file mr-1"></i> File đánh giá <span class="text-danger">*</span>
                                            </label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="evaluation_file"
                                                    name="evaluation_file" required>
                                                <label class="custom-file-label" for="evaluation_file">Chọn file...</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-block mt-3">
                                            <i class="fas fa-plus-circle mr-1"></i> Thêm file đánh giá
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có file đánh giá nào.
                                        <?php if (!$decision): ?>
                                            <span class="d-block mt-2">
                                                <i class="fas fa-exclamation-circle"></i> Cần phải có quyết định nghiệm thu trước khi thêm file đánh giá.
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cập nhật tiến độ -->
    <?php if ($has_access && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
        <div class="modal fade" id="addProgressModal" tabindex="-1" role="dialog" aria-labelledby="addProgressModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addProgressModalLabel">
                            <i class="fas fa-tasks mr-2"></i>Cập nhật tiến độ đề tài
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="update_project_progress.php" method="post" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                            
                            <div class="form-group">
                                <label for="progress_title">
                                    <i class="fas fa-heading mr-1"></i> Tiêu đề <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="progress_title" name="progress_title" 
                                    placeholder="Nhập tiêu đề cập nhật tiến độ" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="progress_content">
                                    <i class="fas fa-align-left mr-1"></i> Nội dung <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="progress_content" name="progress_content" 
                                    rows="5" placeholder="Mô tả chi tiết về tiến độ đề tài" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="progress_completion">
                                    <i class="fas fa-percentage mr-1"></i> Tiến độ hoàn thành (%) <span class="text-danger">*</span>
                                </label>
                                <input type="range" class="custom-range" min="0" max="100" step="5" 
                                    id="progress_completion" name="progress_completion" value="<?php echo $overall_completion; ?>">
                                <div class="d-flex justify-content-between">
                                    <span>0%</span>
                                    <span id="progress_completion_value"><?php echo $overall_completion; ?>%</span>
                                    <span>100%</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="progress_file">
                                    <i class="fas fa-paperclip mr-1"></i> File đính kèm (nếu có)
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="progress_file" name="progress_file">
                                    <label class="custom-file-label" for="progress_file">Chọn file...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Các định dạng hỗ trợ: PDF, Word, Excel, PowerPoint, ZIP, RAR, JPG, JPEG, PNG. Kích thước tối đa: 10MB.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i> Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Lưu cập nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Custom file input display filename
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass('selected').html(fileName);
            });

            // Show progress completion value when slider changes
            $('#progress_completion').on('input', function() {
                $('#progress_completion_value').text($(this).val() + '%');
            });

            // Print functionality
            $('#printProjectBtn').on('click', function() {
                window.print();
            });
        });
    </script>
</body>
</html>
