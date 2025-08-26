
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

// Lấy danh sách biên bản
$minutes = [];
$sql_minutes = "SELECT bb.*, qd.QD_NGAY, qd.QD_FILE
                FROM bien_ban bb
                LEFT JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
                WHERE qd.QD_SO = (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)
                ORDER BY bb.BB_NGAYNGHIEMTHU DESC";
$stmt = $conn->prepare($sql_minutes);
if ($stmt === false) {
    error_log("Lỗi SQL (bien_ban): " . $conn->error);
} else {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $minutes[] = $row;
    }
    $stmt->close();
}

// Lấy danh sách đánh giá từ thành viên hội đồng
$evaluations = [];
$sql_evaluations = "SELECT tvhd.*, gv.GV_HOGV, gv.GV_TENGV, tc.TC_TEN, tc.TC_DIEMTOIDA
                    FROM thanh_vien_hoi_dong tvhd
                    LEFT JOIN giang_vien gv ON tvhd.GV_MAGV = gv.GV_MAGV
                    LEFT JOIN tieu_chi tc ON tvhd.TC_MATC = tc.TC_MATC
                    WHERE tvhd.QD_SO = (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)
                    ORDER BY tvhd.TV_NGAYDANHGIA DESC";
$stmt = $conn->prepare($sql_evaluations);
if ($stmt === false) {
    error_log("Lỗi SQL (thanh_vien_hoi_dong): " . $conn->error);
} else {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
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
        /* Enhanced Project View Styles */
        :root {
            --primary-color: #4e73df;
            --secondary-color: #224abe;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.12);
            --shadow-heavy: 0 8px 30px rgba(0, 0, 0, 0.15);
            --border-radius: 15px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }

        .project-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-heavy);
            position: relative;
            overflow: hidden;
        }

        .project-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.5;
        }

        .project-header .content {
            position: relative;
            z-index: 2;
        }
        
        .project-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .project-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        .status-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .enhanced-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        .enhanced-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Cards */
        .enhanced-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            background: white;
            overflow: hidden;
        }

        .enhanced-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .enhanced-card .card-header {
            background: linear-gradient(135deg, #f8f9fc 0%, #e8f4fe 100%);
            border-bottom: 2px solid rgba(78, 115, 223, 0.1);
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .enhanced-card .card-body {
            padding: 1.5rem;
        }

        /* Enhanced Tabs */
        .nav-tabs {
            border-bottom: 2px solid #e3e6f0;
            margin-bottom: 0;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 10px 10px 0 0;
            color: var(--dark-color);
            font-weight: 600;
            padding: 1rem 1.5rem;
            margin-right: 0.25rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .nav-tabs .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-secondary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            background: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }

        .nav-tabs .nav-link.active {
            background: white;
            color: var(--primary-color);
            border-color: #e3e6f0 #e3e6f0 white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .nav-tabs .nav-link.active::before {
            transform: scaleX(1);
        }

        /* Enhanced Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
            list-style: none;
        }

        .timeline::before {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0.75rem;
            width: 3px;
            content: "";
            background: linear-gradient(to bottom, var(--primary-color), var(--info-color));
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
        }

        .timeline-item::before {
            position: absolute;
            top: 0.5rem;
            left: -1.25rem;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            content: "";
            background: white;
            border: 3px solid var(--primary-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .timeline-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            margin-left: 1rem;
        }

        .timeline-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-medium);
        }

        /* Enhanced Progress Bar */
        .enhanced-progress {
            height: 15px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.05);
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .enhanced-progress-bar {
            border-radius: 10px;
            background: linear-gradient(90deg, var(--success-color), #20c997);
            position: relative;
            transition: width 1s ease;
            overflow: hidden;
        }

        .enhanced-progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.3) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0.3) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
            animation: progressSlide 2s linear infinite;
        }

        @keyframes progressSlide {
            0% { background-position-x: 1rem; }
            100% { background-position-x: 0; }
        }

        /* Enhanced Info Items */
        .info-item {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(78, 115, 223, 0.03);
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .info-item:hover {
            background: rgba(78, 115, 223, 0.08);
            transform: translateX(5px);
        }
        
        .info-label {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-content {
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Enhanced Buttons */
        .btn-enhanced {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .btn-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-enhanced:hover::before {
            left: 100%;
        }

        .btn-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        /* Enhanced Dropdown */
        .enhanced-dropdown .dropdown-menu {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-medium);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }

        .enhanced-dropdown .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .enhanced-dropdown .dropdown-item:hover {
            background: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        /* Enhanced Alerts */
        .enhanced-alert {
            border: none;
            border-radius: var(--border-radius);
            padding: 1.25rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .enhanced-alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
        }

        /* Student Avatar Enhancement */
        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--gradient-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            font-weight: 700;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .student-avatar:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-medium);
        }

        .student-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }

        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        /* File Cards */
        .file-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border: 2px dashed #e3e6f0;
        }

        .file-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .file-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: var(--gradient-secondary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .project-header {
                padding: 2rem 1.5rem;
            }
            
            .project-title {
                font-size: 1.8rem;
            }
            
            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
            }
            
            .enhanced-card:hover,
            .timeline-card:hover {
                transform: none;
            }
        }

        /* Loading Animation */
        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }

        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200px 100%;
            animation: shimmer 1.5s infinite;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient-secondary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
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
                        <div class="content">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <h1 class="project-title"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h1>
                                    <p class="project-subtitle">
                                        <i class="fas fa-barcode mr-2"></i>
                                        Mã đề tài: <strong><?php echo $project['DT_MADT']; ?></strong>
                                    </p>
                                    <div class="status-badges">
                                        <span class="enhanced-badge <?php echo str_replace('badge-', '', getStatusBadgeClass($project['DT_TRANGTHAI'])); ?>">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <?php echo $project['DT_TRANGTHAI']; ?>
                                        </span>
                                        <span class="enhanced-badge badge-light text-dark">
                                            <i class="fas fa-tag mr-2"></i>
                                            <?php echo $project['LDT_TENLOAI']; ?>
                                        </span>
                                        <?php if ($project['LVUT_TEN']): ?>
                                        <span class="enhanced-badge badge-warning">
                                            <i class="fas fa-star mr-2"></i>
                                            Ưu tiên
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                                    <div class="enhanced-dropdown dropdown d-inline-block">
                                        <button class="btn btn-light btn-enhanced dropdown-toggle" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-cog mr-2"></i>Thao tác
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="actionDropdown">
                                            <div class="dropdown-header">
                                                <i class="fas fa-tools mr-2"></i>Quản lý đề tài
                                            </div>
                                            <a class="dropdown-item" href="edit_project.php?id=<?php echo $project_id; ?>">
                                                <i class="fas fa-edit mr-2 text-warning"></i>Chỉnh sửa đề tài
                                            </a>
                                            <a class="dropdown-item" href="manage_students.php?id=<?php echo $project_id; ?>">
                                                <i class="fas fa-user-graduate mr-2 text-primary"></i>Quản lý sinh viên
                                            </a>
                                            <a class="dropdown-item" href="project_progress.php?id=<?php echo $project_id; ?>">
                                                <i class="fas fa-tasks mr-2 text-info"></i>Cập nhật tiến độ
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <div class="dropdown-header">
                                                <i class="fas fa-file mr-2"></i>Tài liệu
                                            </div>
                                            <a class="dropdown-item" href="upload_documents.php?id=<?php echo $project_id; ?>">
                                                <i class="fas fa-upload mr-2 text-success"></i>Tải lên tài liệu
                                            </a>
                                            <a class="dropdown-item" href="export_project.php?id=<?php echo $project_id; ?>">
                                                <i class="fas fa-download mr-2 text-success"></i>Xuất báo cáo
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#deleteProjectModal">
                                                <i class="fas fa-trash mr-2"></i>Xóa đề tài
                                            </a>
                                        </div>
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
                            <div class="enhanced-card shadow mb-4">
                                <div class="card-header py-3">
                                    <ul class="nav nav-tabs card-header-tabs" id="projectTabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">
                                                <i class="fas fa-info-circle mr-2"></i>Tổng quan
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="progress-tab" data-toggle="tab" href="#progress" role="tab" aria-controls="progress" aria-selected="false">
                                                <i class="fas fa-tasks mr-2"></i>Tiến độ
                                                <span class="badge badge-primary ml-1"><?php echo count($progress); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="proposal-tab" data-toggle="tab" href="#proposal" role="tab" aria-controls="proposal" aria-selected="false">
                                                <i class="fas fa-file-pdf mr-2"></i>Thuyết minh
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="decision-tab" data-toggle="tab" href="#decision" role="tab" aria-controls="decision" aria-selected="false">
                                                <i class="fas fa-file-signature mr-2"></i>Quyết định
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="minutes-tab" data-toggle="tab" href="#minutes" role="tab" aria-controls="minutes" aria-selected="false">
                                                <i class="fas fa-file-alt mr-2"></i>Biên bản
                                                <span class="badge badge-info ml-1"><?php echo count($minutes); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="evaluation-tab" data-toggle="tab" href="#evaluation" role="tab" aria-controls="evaluation" aria-selected="false">
                                                <i class="fas fa-star mr-2"></i>Đánh giá
                                                <span class="badge badge-warning ml-1"><?php echo count($evaluations); ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="contract-tab" data-toggle="tab" href="#contract" role="tab" aria-controls="contract" aria-selected="false">
                                                <i class="fas fa-file-contract mr-2"></i>Hợp đồng
                                                <?php if ($contract): ?>
                                                <span class="badge badge-success ml-1">✓</span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content" id="projectTabContent">
                                        <!-- Tab: Overview -->
                                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                                            <div class="info-item">
                                                <div class="info-label">
                                                    <i class="fas fa-align-left mr-2"></i>Mô tả đề tài
                                                </div>
                                                <div class="info-content">
                                                    <?php echo nl2br(htmlspecialchars($project['DT_MOTA'])); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">
                                                            <i class="fas fa-flask mr-2"></i>Lĩnh vực nghiên cứu
                                                        </div>
                                                        <div class="info-content">
                                                            <h6 class="mb-2"><?php echo htmlspecialchars($project['LVNC_TEN']); ?></h6>
                                                            <small class="text-muted"><?php echo htmlspecialchars($project['lvnc_mota']); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">
                                                            <i class="fas fa-star mr-2"></i>Lĩnh vực ưu tiên
                                                        </div>
                                                        <div class="info-content">
                                                            <h6 class="mb-2"><?php echo htmlspecialchars($project['LVUT_TEN'] ?? 'Không có'); ?></h6>
                                                            <small class="text-muted"><?php echo htmlspecialchars($project['lvut_mota'] ?? 'Đề tài không thuộc lĩnh vực ưu tiên'); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">
                                                            <i class="fas fa-file-signature mr-2"></i>Quyết định nghiệm thu
                                                        </div>
                                                        <div class="info-content">
                                                            <p class="mb-1"><strong>Số:</strong> <?php echo htmlspecialchars($project['QD_SO'] ?? 'Chưa có'); ?></p>
                                                            <p class="mb-2"><strong>Ngày:</strong> <?php echo $project['QD_NGAY'] ? date('d/m/Y', strtotime($project['QD_NGAY'])) : 'Chưa có'; ?></p>
                                                            <?php if ($project['QD_FILE']): ?>
                                                                <a href="../../uploads/decisions/<?php echo $project['QD_FILE']; ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-enhanced">
                                                                    <i class="fas fa-file-pdf mr-2"></i>Xem quyết định
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <div class="info-label">
                                                            <i class="fas fa-file-alt mr-2"></i>Thuyết minh đề tài
                                                        </div>
                                                        <div class="info-content">
                                                            <?php if ($project['DT_FILEBTM']): ?>
                                                                <div class="mb-2">
                                                                    <a href="../../uploads/proposals/<?php echo $project['DT_FILEBTM']; ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-enhanced">
                                                                        <i class="fas fa-file-pdf mr-2"></i>Xem thuyết minh
                                                                    </a>
                                                                </div>
                                                            <?php else: ?>
                                                                <p class="text-muted mb-2">Chưa có file thuyết minh</p>
                                                                <a href="upload_documents.php?id=<?php echo $project_id; ?>&type=proposal" class="btn btn-sm btn-primary btn-enhanced">
                                                                    <i class="fas fa-upload mr-2"></i>Tải lên
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="info-item mt-4">
                                                <div class="info-label">
                                                    <i class="fas fa-chart-pie mr-2"></i>Trạng thái đề tài
                                                </div>
                                                <div class="enhanced-alert alert-<?php echo str_replace('badge-', '', getStatusBadgeClass($project['DT_TRANGTHAI'])); ?>" role="alert">
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
                                                    ?> mr-3"></i>
                                                    Đề tài hiện đang ở trạng thái: <strong><?php echo $project['DT_TRANGTHAI']; ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab: Progress -->
                                        <div class="tab-pane fade" id="progress" role="tabpanel" aria-labelledby="progress-tab">
                                            <?php if (empty($progress)): ?>
                                                <div class="enhanced-alert alert-info">
                                                    <i class="fas fa-info-circle mr-3"></i>
                                                    Chưa có cập nhật tiến độ nào cho đề tài này.
                                                </div>
                                                <div class="text-center my-5">
                                                    <div class="mb-3">
                                                        <i class="fas fa-chart-line" style="font-size: 4rem; color: var(--primary-color); opacity: 0.3;"></i>
                                                    </div>
                                                    <h5 class="text-muted mb-3">Bắt đầu theo dõi tiến độ</h5>
                                                    <p class="text-muted mb-4">Thêm cập nhật tiến độ đầu tiên để theo dõi sự phát triển của đề tài</p>
                                                    <a href="project_progress.php?id=<?php echo $project_id; ?>" class="btn btn-primary btn-enhanced">
                                                        <i class="fas fa-plus mr-2"></i>Thêm cập nhật tiến độ
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-4 d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">Lịch sử cập nhật tiến độ</h5>
                                                        <p class="text-muted mb-0"><?php echo count($progress); ?> cập nhật</p>
                                                    </div>
                                                    <a href="project_progress.php?id=<?php echo $project_id; ?>" class="btn btn-primary btn-enhanced">
                                                        <i class="fas fa-plus mr-2"></i>Thêm cập nhật
                                                    </a>
                                                </div>
                                                
                                                <ul class="timeline">
                                                    <?php foreach ($progress as $index => $p): ?>
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
                                                            <div class="timeline-card">
                                                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0 font-weight-bold text-primary">
                                                                        <i class="fas fa-flag mr-2"></i>
                                                                        <?php echo htmlspecialchars($p['TDDT_TIEUDE']); ?>
                                                                    </h6>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="badge badge-primary mr-2"><?php echo $p['TDDT_PHANTRAMHOANTHANH']; ?>%</span>
                                                                        <span class="text-muted small"><?php echo timeAgo($p['TDDT_NGAYCAPNHAT']); ?></span>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="mb-3">
                                                                        <?php echo nl2br(htmlspecialchars($p['TDDT_NOIDUNG'])); ?>
                                                                    </div>
                                                                    
                                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                                        <span class="text-muted small">
                                                                            <i class="fas fa-user mr-1"></i>
                                                                            Cập nhật bởi: <strong><?php echo htmlspecialchars($student_name); ?></strong>
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    <div class="enhanced-progress mb-3">
                                                                        <div class="enhanced-progress-bar" style="width: <?php echo $p['TDDT_PHANTRAMHOANTHANH']; ?>%" 
                                                                            aria-valuenow="<?php echo $p['TDDT_PHANTRAMHOANTHANH']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                    
                                                                    <?php if ($p['TDDT_FILE']): ?>
                                                                        <a href="../../uploads/progress/<?php echo $p['TDDT_FILE']; ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-enhanced">
                                                                            <i class="fas fa-paperclip mr-2"></i>Xem file đính kèm
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="card-footer bg-light text-muted small d-flex justify-content-between">
                                                                    <span>
                                                                        <i class="fas fa-calendar mr-1"></i>
                                                                        <?php echo date('d/m/Y H:i', strtotime($p['TDDT_NGAYCAPNHAT'])); ?>
                                                                    </span>
                                                                    <?php if ($index === 0): ?>
                                                                        <span class="badge badge-success">Mới nhất</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Tab: Proposal -->
                                        <div class="tab-pane fade" id="proposal" role="tabpanel" aria-labelledby="proposal-tab">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h5 class="mb-4">Bản thuyết minh đề tài</h5>
                                                    
                                                    <?php if ($project['DT_FILEBTM']): ?>
                                                        <div class="card">
                                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0 font-weight-bold">
                                                                    <i class="fas fa-file-pdf text-danger mr-2"></i>
                                                                    Thuyết minh đề tài
                                                                </h6>
                                                                <span class="badge badge-success">Có file</span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="mb-3">
                                                                    <strong>Tên file:</strong> <?php echo $project['DT_FILEBTM']; ?>
                                                                </div>
                                                                <div class="text-center">
                                                                    <a href="../../uploads/proposals/<?php echo $project['DT_FILEBTM']; ?>" target="_blank" class="btn btn-primary">
                                                                        <i class="fas fa-eye mr-1"></i>Xem thuyết minh
                                                                    </a>
                                                                    <a href="../../uploads/proposals/<?php echo $project['DT_FILEBTM']; ?>" download class="btn btn-outline-primary ml-2">
                                                                        <i class="fas fa-download mr-1"></i>Tải xuống
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                                            Chưa có file thuyết minh cho đề tài này.
                                                        </div>
                                                        <div class="text-center">
                                                            <a href="upload_documents.php?id=<?php echo $project_id; ?>&type=proposal" class="btn btn-primary">
                                                                <i class="fas fa-upload mr-1"></i>Tải lên thuyết minh
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab: Decision -->
                                        <div class="tab-pane fade" id="decision" role="tabpanel" aria-labelledby="decision-tab">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h5 class="mb-4">Quyết định nghiệm thu</h5>
                                                    
                                                    <?php if ($project['QD_FILE']): ?>
                                                        <div class="card">
                                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0 font-weight-bold">
                                                                    <i class="fas fa-file-signature text-primary mr-2"></i>
                                                                    Quyết định nghiệm thu
                                                                </h6>
                                                                <span class="badge badge-success">Có file</span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <strong>Số quyết định:</strong> <?php echo $project['QD_SO']; ?>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <strong>Ngày ban hành:</strong> <?php echo $project['QD_NGAY'] ? date('d/m/Y', strtotime($project['QD_NGAY'])) : 'Chưa cập nhật'; ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <strong>Tên file:</strong> <?php echo $project['QD_FILE']; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="text-center">
                                                                    <a href="../../uploads/decisions/<?php echo $project['QD_FILE']; ?>" target="_blank" class="btn btn-primary">
                                                                        <i class="fas fa-eye mr-1"></i>Xem quyết định
                                                                    </a>
                                                                    <a href="../../uploads/decisions/<?php echo $project['QD_FILE']; ?>" download class="btn btn-outline-primary ml-2">
                                                                        <i class="fas fa-download mr-1"></i>Tải xuống
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                                            Chưa có quyết định nghiệm thu cho đề tài này.
                                                        </div>
                                                        <div class="text-center">
                                                            <a href="upload_documents.php?id=<?php echo $project_id; ?>&type=decision" class="btn btn-primary">
                                                                <i class="fas fa-upload mr-1"></i>Tải lên quyết định
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab: Minutes -->
                                        <div class="tab-pane fade" id="minutes" role="tabpanel" aria-labelledby="minutes-tab">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h5 class="mb-4">Biên bản hội đồng</h5>
                                                    
                                                    <?php if (!empty($minutes)): ?>
                                                        <div class="mb-3">
                                                            <span class="badge badge-info">Tổng cộng: <?php echo count($minutes); ?> biên bản</span>
                                                        </div>
                                                        
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-hover">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th>Số biên bản</th>
                                                                        <th>Ngày nghiệm thu</th>
                                                                        <th>Xếp loại</th>
                                                                        <th>Tổng điểm</th>
                                                                        <th>Thao tác</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($minutes as $minute): ?>
                                                                        <tr>
                                                                            <td><strong><?php echo $minute['BB_SOBB']; ?></strong></td>
                                                                            <td><?php echo $minute['BB_NGAYNGHIEMTHU'] ? date('d/m/Y', strtotime($minute['BB_NGAYNGHIEMTHU'])) : '-'; ?></td>
                                                                            <td>
                                                                                <span class="badge badge-<?php 
                                                                                    switch($minute['BB_XEPLOAI']) {
                                                                                        case 'Xuất sắc': echo 'success'; break;
                                                                                        case 'Tốt': echo 'primary'; break;
                                                                                        case 'Khá': echo 'info'; break;
                                                                                        case 'Trung bình': echo 'warning'; break;
                                                                                        case 'Yếu': echo 'danger'; break;
                                                                                        default: echo 'secondary';
                                                                                    }
                                                                                ?>">
                                                                                    <?php echo $minute['BB_XEPLOAI']; ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($minute['BB_TONGDIEM']): ?>
                                                                                    <strong><?php echo $minute['BB_TONGDIEM']; ?>/100</strong>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">Chưa có</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <a href="view_minute.php?id=<?php echo $minute['BB_SOBB']; ?>" class="btn btn-sm btn-outline-info">
                                                                                    <i class="fas fa-eye"></i> Xem chi tiết
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle mr-2"></i>
                                                            Chưa có biên bản nào cho đề tài này.
                                                        </div>
                                                        <div class="text-center">
                                                            <p class="text-muted mb-3">Biên bản nghiệm thu sẽ được tạo tự động khi có quyết định nghiệm thu</p>
                                                            <?php if ($project['QD_SO']): ?>
                                                                <a href="create_minutes.php?id=<?php echo $project_id; ?>" class="btn btn-primary">
                                                                    <i class="fas fa-plus mr-1"></i>Tạo biên bản nghiệm thu
                                                                </a>
                                                            <?php else: ?>
                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-info-circle mr-2"></i>
                                                                    Cần có quyết định nghiệm thu trước khi tạo biên bản
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab: Evaluation -->
                                        <div class="tab-pane fade" id="evaluation" role="tabpanel" aria-labelledby="evaluation-tab">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h5 class="mb-4">Đánh giá đề tài</h5>
                                                    
                                                    <?php if (!empty($evaluations)): ?>
                                                        <div class="mb-3">
                                                            <span class="badge badge-info">Tổng cộng: <?php echo count($evaluations); ?> đánh giá</span>
                                                        </div>
                                                        
                                                        <?php foreach ($evaluations as $evaluation): ?>
                                                            <div class="card mb-3">
                                                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0 font-weight-bold">
                                                                        <i class="fas fa-star text-warning mr-2"></i>
                                                                        Đánh giá của <?php echo $evaluation['GV_HOGV'] . ' ' . $evaluation['GV_TENGV']; ?>
                                                                        <span class="badge badge-info ml-2"><?php echo $evaluation['TV_VAITRO']; ?></span>
                                                                    </h6>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="badge badge-primary mr-2">
                                                                            <?php echo $evaluation['TV_DIEM'] ? $evaluation['TV_DIEM'] . '/100' : 'Chưa đánh giá'; ?>
                                                                        </span>
                                                                        <span class="badge badge-<?php 
                                                                            switch($evaluation['TV_TRANGTHAI']) {
                                                                                case 'Đã hoàn thành': echo 'success'; break;
                                                                                case 'Đang đánh giá': echo 'warning'; break;
                                                                                default: echo 'secondary';
                                                                            }
                                                                        ?>">
                                                                            <?php echo $evaluation['TV_TRANGTHAI']; ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-8">
                                                                            <div class="mb-3">
                                                                                <strong>Tiêu chí đánh giá:</strong>
                                                                                <p class="mt-2"><?php echo htmlspecialchars($evaluation['TC_TEN']); ?></p>
                                                                                <small class="text-muted">Điểm tối đa: <?php echo $evaluation['TC_DIEMTOIDA']; ?> điểm</small>
                                                                            </div>
                                                                            <?php if ($evaluation['TV_DANHGIA']): ?>
                                                                                <div class="mb-3">
                                                                                    <strong>Nhận xét:</strong>
                                                                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($evaluation['TV_DANHGIA'])); ?></p>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="mb-2">
                                                                                <strong>Ngày đánh giá:</strong>
                                                                                <br><?php echo $evaluation['TV_NGAYDANHGIA'] ? date('d/m/Y H:i', strtotime($evaluation['TV_NGAYDANHGIA'])) : 'Chưa đánh giá'; ?>
                                                                            </div>
                                                                            <?php if ($evaluation['TV_FILEDANHGIA']): ?>
                                                                                <div class="mt-3">
                                                                                    <a href="../../uploads/evaluations/<?php echo $evaluation['TV_FILEDANHGIA']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                                        <i class="fas fa-file mr-1"></i>Xem file đánh giá
                                                                                    </a>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle mr-2"></i>
                                                            Chưa có đánh giá nào cho đề tài này.
                                                        </div>
                                                        <div class="text-center">
                                                            <p class="text-muted mb-3">Đánh giá được thực hiện bởi thành viên hội đồng nghiệm thu</p>
                                                            <?php if ($project['QD_SO']): ?>
                                                                <a href="manage_evaluations.php?id=<?php echo $project_id; ?>" class="btn btn-primary">
                                                                    <i class="fas fa-star mr-1"></i>Quản lý đánh giá
                                                                </a>
                                                            <?php else: ?>
                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-info-circle mr-2"></i>
                                                                    Cần có quyết định nghiệm thu trước khi thực hiện đánh giá
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <div class="student-avatar mx-auto mb-3" style="background: var(--gradient-secondary);">
                                            <?php echo strtoupper(substr($project['GV_TENGV'], 0, 1)); ?>
                                        </div>
                                        <h5 class="font-weight-bold mb-2"><?php echo htmlspecialchars($project['GV_HOGV'] . ' ' . $project['GV_TENGV']); ?></h5>
                                    </div>
                                    <div class="text-left">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-envelope mr-3 text-primary"></i>
                                            <span><?php echo htmlspecialchars($project['GV_EMAIL']); ?></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-phone mr-3 text-primary"></i>
                                            <span><?php echo htmlspecialchars($project['GV_SDT'] ?: 'Chưa cập nhật'); ?></span>
                                        </div>
                                    </div>
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

    <!-- Bootstrap core JavaScript - Optimized loading -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" defer></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" defer></script>
    
    <!-- Core plugin JavaScript - Load only when needed-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js" defer></script>
    
    <!-- SB Admin 2 JS - Deferred load -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js" defer></script>
    
    <script>
        // Optimized initialization to avoid performance violations
        window.addEventListener('load', function() {
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