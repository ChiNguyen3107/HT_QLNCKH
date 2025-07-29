<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\view_project.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Lấy ID đề tài từ URL
$project_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($project_id)) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài.";
    header('Location: manage_projects.php');
    exit;
}

// Lấy thông tin chi tiết của đề tài
$sql = "SELECT dt.*, 
               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
               gv.GV_EMAIL,
               ldt.LDT_TENLOAI,
               lvnc.LVNC_TEN,
               lvut.LVUT_TEN,
               hd.HD_MA,
               hd.HD_NGAYTAO,
               hd.HD_NGAYBD,
               hd.HD_NGAYKT,
               hd.HD_TONGKINHPHI,
               hd.HD_FILEHD,
               hd.HD_GHICHU,
               qd.QD_SO,
               qd.QD_NGAY,
               qd.QD_FILE,
               bb.BB_SOBB,
               bb.BB_NGAYNGHIEMTHU,
               bb.BB_XEPLOAI
        FROM de_tai_nghien_cuu dt
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
        LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
        LEFT JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB
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
    header('Location: manage_projects.php');
    exit;
}

$project = $result->fetch_assoc();

// Lấy danh sách thành viên tham gia
$member_sql = "SELECT sv.SV_MASV, CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN, 
               l.LOP_TEN, cttg.CTTG_VAITRO, cttg.CTTG_NGAYTHAMGIA, sv.SV_EMAIL, sv.SV_SDT
               FROM chi_tiet_tham_gia cttg
               JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
               JOIN lop l ON sv.LOP_MA = l.LOP_MA
               WHERE cttg.DT_MADT = ?
               ORDER BY FIELD(cttg.CTTG_VAITRO, 'Chủ nhiệm', 'Thành viên')";
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
while ($entry = $progress_result->fetch_assoc()) {
    $progress_entries[] = $entry;
}

// Lấy tiến độ tổng thể
$overall_completion = 0;
if (count($progress_entries) > 0) {
    $overall_completion = $progress_entries[0]['TDDT_PHANTRAMHOANTHANH'];
}

// Lấy thông tin hợp đồng
$contract = [];
if (!empty($project['HD_MA'])) {
    $contract = [
        'HD_MA' => $project['HD_MA'],
        'HD_NGAYTAO' => $project['HD_NGAYTAO'],
        'HD_NGAYBD' => $project['HD_NGAYBD'],
        'HD_NGAYKT' => $project['HD_NGAYKT'],
        'HD_TONGKINHPHI' => $project['HD_TONGKINHPHI'],
        'HD_FILEHD' => $project['HD_FILEHD'],
        'HD_GHICHU' => $project['HD_GHICHU']
    ];
}

// Lấy thông tin quyết định và biên bản
$decision = [];
if (!empty($project['QD_SO'])) {
    $decision = [
        'QD_SO' => $project['QD_SO'],
        'QD_NGAY' => $project['QD_NGAY'],
        'QD_FILE' => $project['QD_FILE'],
        'BB_SOBB' => $project['BB_SOBB'],
        'BB_NGAYNGHIEMTHU' => $project['BB_NGAYNGHIEMTHU'],
        'BB_XEPLOAI' => $project['BB_XEPLOAI']
    ];
}

// Lấy báo cáo của đề tài
$reports_sql = "SELECT bc.*, lbc.LBC_TENLOAI, CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN
                FROM bao_cao bc
                LEFT JOIN loai_bao_cao lbc ON bc.LBC_MALOAI = lbc.LBC_MALOAI
                LEFT JOIN sinh_vien sv ON bc.SV_MASV = sv.SV_MASV
                WHERE bc.DT_MADT = ?
                ORDER BY bc.BC_NGAYNOP DESC";
$stmt = $conn->prepare($reports_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$reports_result = $stmt->get_result();
$reports = [];
while ($report = $reports_result->fetch_assoc()) {
    $reports[] = $report;
}

// Lấy file đánh giá
$evaluation_sql = "SELECT fdg.*
                  FROM file_danh_gia fdg
                  JOIN bien_ban bb ON fdg.BB_SOBB = bb.BB_SOBB
                  JOIN quyet_dinh_nghiem_thu qd ON bb.BB_SOBB = qd.BB_SOBB
                  JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
                  WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($evaluation_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$eval_result = $stmt->get_result();
$evaluation_files = [];
while ($file = $eval_result->fetch_assoc()) {
    $evaluation_files[] = $file;
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

    <style>
        :root {
            --primary: #4e73df;
            --secondary: #6c757d;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 500;
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
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.2);
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
            background-color: #e8f4fe;
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .member-card {
            border-radius: 8px;
            transition: all 0.3s ease;
            padding: 15px !important;
            margin-bottom: 15px;
            border: 1px solid #e3e6f0;
        }

        .avatar {
            width: 45px !important;
            height: 45px !important;
            background: linear-gradient(120deg, var(--primary), #5a8aef);
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
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
        }
        
        .file-item {
            transition: all 0.3s ease;
        }
        
        .file-item:hover {
            background-color: #f8f9fa;
        }
        
        .file-icon {
            color: var(--primary);
            margin-right: 10px;
        }
        
        .status-badge {
            font-size: 0.95rem;
            padding: 8px 16px;
            font-weight: 500;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .progress-item {
            padding: 15px;
            border-left: 4px solid #4e73df;
            margin-bottom: 10px;
            background-color: #f8f9fa;
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
        
        @media print {
            .btn, .sidebar, .nav-link, .sidebar-toggler {
                display: none !important;
            }
            
            .content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .card {
                break-inside: avoid;
                box-shadow: none !important;
            }
        }
    </style>
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>

    <div class="container-fluid" style="margin-left: 250px; padding: 20px;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-white p-3 shadow-sm">
                <li class="breadcrumb-item"><a href="../admin_dashboard.php"><i class="fas fa-tachometer-alt mr-1"></i>Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="manage_projects.php"><i class="fas fa-clipboard-list mr-1"></i>Quản lý đề tài</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-project-diagram mr-1"></i>Chi tiết đề tài</li>
            </ol>
        </nav>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Header đề tài -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="m-0 font-weight-bold"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <p class="mb-2 d-flex align-items-center">
                            <i class="fas fa-barcode mr-2"></i>
                            Mã đề tài: <span class="badge badge-light ml-2 p-2"><?php echo htmlspecialchars($project['DT_MADT']); ?></span>
                        </p>
                        
                        <!-- Tiến độ tổng thể -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Tiến độ tổng thể</span>
                                <span><?php echo $overall_completion; ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $overall_completion; ?>%" 
                                    aria-valuenow="<?php echo $overall_completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-right">
                        <?php
                        // Xác định class cho badge trạng thái
                        $status_class = '';
                        switch ($project['DT_TRANGTHAI']) {
                            case 'Chờ duyệt':
                                $status_class = 'badge-warning';
                                break;
                            case 'Đã duyệt':
                                $status_class = 'badge-info';
                                break;
                            case 'Đang thực hiện':
                                $status_class = 'badge-primary';
                                break;
                            case 'Đã hoàn thành':
                                $status_class = 'badge-success';
                                break;
                            case 'Tạm dừng':
                                $status_class = 'badge-secondary';
                                break;
                            case 'Đã hủy':
                                $status_class = 'badge-danger';
                                break;
                            default:
                                $status_class = 'badge-secondary';
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <i class="fas fa-circle mr-1 small"></i> <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                        </span>
                        
                        <div class="mt-3">
                            <a href="edit_project.php?id=<?php echo htmlspecialchars($project_id); ?>" class="btn btn-primary">
                                <i class="fas fa-edit mr-1"></i> Cập nhật thông tin
                            </a>
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print mr-1"></i> In báo cáo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Thông tin đề tài -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle mr-2"></i>Thông tin đề tài</h5>
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
                                        <div class="font-weight-medium"><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-microscope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Lĩnh vực nghiên cứu</div>
                                        <div class="font-weight-medium"><?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Lĩnh vực ưu tiên</div>
                                        <div class="font-weight-medium"><?php echo htmlspecialchars($project['LVUT_TEN'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Ngày tạo</div>
                                        <div class="font-weight-medium">
                                            <?php echo isset($project['DT_NGAYTAO']) && $project['DT_NGAYTAO'] ? date('d/m/Y', strtotime($project['DT_NGAYTAO'])) : 'Chưa cập nhật'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Thời gian thực hiện</div>
                                        <div class="font-weight-medium">
                                            <?php 
                                            if (!empty($project['HD_NGAYBD']) && !empty($project['HD_NGAYKT'])) {
                                                echo date('d/m/Y', strtotime($project['HD_NGAYBD'])) . ' - ' . 
                                                     date('d/m/Y', strtotime($project['HD_NGAYKT']));
                                            } else {
                                                echo 'Chưa cập nhật';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Giảng viên hướng dẫn</div>
                                        <div class="font-weight-medium"><?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Chưa có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Liên hệ GVHD</div>
                                        <div class="font-weight-medium">
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
                                        <div class="font-weight-medium"><?php echo number_format($contract['HD_TONGKINHPHI']); ?> VNĐ</div>
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
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-tasks mr-2"></i>Tiến độ đề tài</h5>
                        <a href="add_progress.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus-circle mr-1"></i> Thêm tiến độ
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($progress_entries) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($progress_entries as $entry): ?>
                                    <div class="timeline-item">
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
                                            <small class="text-muted ml-2">(<?php echo htmlspecialchars($entry['SV_HOTEN']); ?>)</small>
                                        </h6>
                                        
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($entry['TDDT_NOIDUNG'])); ?></p>
                                        
                                        <?php if ($entry['TDDT_FILE']): ?>
                                            <a href="/NLNganh/uploads/progress_files/<?php echo htmlspecialchars($entry['TDDT_FILE']); ?>"
                                                class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-paperclip mr-1"></i>
                                                Tải file đính kèm
                                            </a>
                                        <?php endif; ?>
                                        
                                        <div class="mt-2">
                                            <a href="edit_progress.php?id=<?php echo $entry['TDDT_MA']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_progress.php?id=<?php echo $entry['TDDT_MA']; ?>&project_id=<?php echo $project_id; ?>" 
                                               class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
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

                <!-- Báo cáo -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-upload mr-2"></i>Báo cáo đề tài</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($reports) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tiêu đề</th>
                                            <th>Loại báo cáo</th>
                                            <th>Sinh viên nộp</th>
                                            <th>Ngày nộp</th>
                                            <th>Tải xuống</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($report['BC_TENBC'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($report['LBC_TENLOAI'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($report['SV_HOTEN'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($report['BC_NGAYNOP'])); ?></td>
                                                <td>
                                                    <?php if ($report['BC_DUONGDAN']): ?>
                                                        <a href="/NLNganh/<?php echo $report['BC_DUONGDAN']; ?>" 
                                                           class="btn btn-sm btn-primary" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Không có file</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> Chưa có báo cáo nào được nộp.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Thành viên tham gia -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-users mr-2"></i>Thành viên tham gia</h5>
                        <a href="manage_members.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-edit mr-1"></i> Quản lý
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $member): ?>
                                <div class="member-card">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar rounded-circle">
                                            <?php echo strtoupper(mb_substr($member['SV_HOTEN'], 0, 1, 'UTF-8')); ?>
                                        </div>
                                        <div class="ml-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($member['SV_HOTEN']); ?></h6>
                                            <p class="mb-0">
                                                <span class="badge <?php echo ($member['CTTG_VAITRO'] == 'Chủ nhiệm') ? 'badge-primary' : 'badge-secondary'; ?>">
                                                    <?php echo htmlspecialchars($member['CTTG_VAITRO']); ?>
                                                </span>
                                                <span class="ml-2"><?php echo htmlspecialchars($member['LOP_TEN']); ?></span>
                                            </p>
                                            <p class="mb-0 text-muted small mt-1">
                                                <i class="far fa-calendar-alt mr-1"></i>
                                                Tham gia: <?php echo date('d/m/Y', strtotime($member['CTTG_NGAYTHAMGIA'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <?php if (!empty($member['SV_EMAIL'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($member['SV_EMAIL']); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-envelope mr-1"></i> Email
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($member['SV_SDT'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($member['SV_SDT']); ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-phone mr-1"></i> Gọi điện
                                            </a>
                                        <?php endif; ?>
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

                <!-- Quản lý tài liệu -->
                <div class="card-header">
                        <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-folder mr-2"></i>Quản lý tài liệu</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="documentTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="contract-tab" data-toggle="tab" href="#contract" role="tab" aria-controls="contract" aria-selected="true">
                                    <i class="fas fa-file-contract mr-1"></i> Hợp đồng
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="decision-tab" data-toggle="tab" href="#decision" role="tab" aria-controls="decision" aria-selected="false">
                                    <i class="fas fa-gavel mr-1"></i> QĐ & biên bản
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="evaluation-tab" data-toggle="tab" href="#evaluation" role="tab" aria-controls="evaluation" aria-selected="false">
                                    <i class="fas fa-clipboard-check mr-1"></i> Đánh giá
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content pt-3" id="documentTabContent">
                            <!-- Tab Hợp đồng -->
                            <div class="tab-pane fade show active" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                                <?php if ($contract): ?>
                                    <div class="mb-3">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-file-contract mr-2"></i>Thông tin hợp đồng</h6>
                                                <p class="mb-2"><strong>Mã hợp đồng:</strong>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($contract['HD_MA']); ?></span>
                                                </p>
                                                <p class="mb-2"><strong>Ngày tạo:</strong>
                                                    <?php echo date('d/m/Y', strtotime($contract['HD_NGAYTAO'])); ?>
                                                </p>
                                                <p class="mb-2"><strong>Thời gian thực hiện:</strong>
                                                    <?php echo date('d/m/Y', strtotime($contract['HD_NGAYBD'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($contract['HD_NGAYKT'])); ?>
                                                </p>
                                                <p class="mb-2"><strong>Tổng kinh phí:</strong>
                                                    <?php echo number_format($contract['HD_TONGKINHPHI'], 0, ',', '.'); ?> VNĐ
                                                </p>
                                                <?php if (!empty($contract['HD_GHICHU'])): ?>
                                                    <p class="mb-0"><strong>Ghi chú:</strong>
                                                        <?php echo nl2br(htmlspecialchars($contract['HD_GHICHU'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($contract['HD_FILEHD']): ?>
                                        <div class="mb-3 file-item p-2 rounded">
                                            <a href="/NLNganh/uploads/contracts/<?php echo htmlspecialchars($contract['HD_FILEHD']); ?>" 
                                               class="d-flex align-items-center text-decoration-none" download>
                                                <i class="fas fa-file-pdf fa-2x file-icon"></i>
                                                <div>
                                                    <div>Hợp đồng nghiên cứu</div>
                                                    <small class="text-muted">Tải xuống file hợp đồng</small>
                                                </div>
                                                <i class="fas fa-download ml-auto"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có thông tin hợp đồng.
                                    </div>
                                <?php endif; ?>

                                <!-- Thêm hợp đồng -->
                                <div class="mt-3 text-center">
                                    <a href="add_contract.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle mr-1"></i> Thêm hợp đồng
                                    </a>
                                </div>
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
                                                    <?php echo date('d/m/Y', strtotime($decision['QD_NGAY'])); ?>
                                                </p>
                                                <?php if ($decision['BB_SOBB']): ?>
                                                <hr>
                                                <h6 class="text-primary mb-3"><i class="fas fa-file-alt mr-2"></i>Thông tin biên bản nghiệm thu</h6>
                                                <p class="mb-2"><strong>Số biên bản:</strong>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($decision['BB_SOBB']); ?></span>
                                                </p>
                                                <p class="mb-2"><strong>Ngày nghiệm thu:</strong>
                                                    <?php echo date('d/m/Y', strtotime($decision['BB_NGAYNGHIEMTHU'])); ?>
                                                </p>
                                                <p class="mb-2"><strong>Xếp loại:</strong>
                                                    <span class="badge badge-success"><?php echo htmlspecialchars($decision['BB_XEPLOAI']); ?></span>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($decision['QD_FILE']): ?>
                                        <div class="mb-3 file-item p-2 rounded">
                                            <a href="/NLNganh/uploads/decisions/<?php echo htmlspecialchars($decision['QD_FILE']); ?>" 
                                               class="d-flex align-items-center text-decoration-none" download>
                                                <i class="fas fa-file-pdf fa-2x file-icon"></i>
                                                <div>
                                                    <div>Quyết định nghiệm thu</div>
                                                    <small class="text-muted">Tải xuống file quyết định</small>
                                                </div>
                                                <i class="fas fa-download ml-auto"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có quyết định nghiệm thu.
                                    </div>
                                <?php endif; ?>

                                <!-- Thêm quyết định nghiệm thu -->
                                <div class="mt-3 text-center">
                                    <a href="add_decision.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle mr-1"></i> Thêm quyết định nghiệm thu
                                    </a>
                                </div>
                            </div>

                            <!-- Tab Đánh giá -->
                            <div class="tab-pane fade" id="evaluation" role="tabpanel" aria-labelledby="evaluation-tab">
                                <?php if (count($evaluation_files) > 0): ?>
                                    <?php foreach($evaluation_files as $file): ?>
                                        <div class="mb-3 file-item p-2 rounded">
                                            <a href="/NLNganh/uploads/evaluations/<?php echo htmlspecialchars($file['FDG_DUONGDAN']); ?>" 
                                              class="d-flex align-items-center text-decoration-none" download>
                                                <i class="fas fa-file-alt fa-2x file-icon"></i>
                                                <div>
                                                    <div><?php echo htmlspecialchars($file['FDG_TENFILE']); ?></div>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($file['FDG_LOAI']); ?> - 
                                                        Tải lên: <?php echo date('d/m/Y', strtotime($file['FDG_NGAYTAILEN'])); ?>
                                                    </small>
                                                </div>
                                                <i class="fas fa-download ml-auto"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> 
                                        <?php echo (!empty($decision)) ? "Chưa có file đánh giá." : "Chưa có quyết định nghiệm thu và đánh giá."; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($decision['BB_SOBB'])): ?>
                                    <!-- Thêm file đánh giá -->
                                    <div class="mt-3 text-center">
                                        <a href="add_evaluation.php?project_id=<?php echo $project_id; ?>&bb_sobb=<?php echo $decision['BB_SOBB']; ?>" class="btn btn-primary">
                                            <i class="fas fa-plus-circle mr-1"></i> Thêm file đánh giá
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kết quả đề tài -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0 font-weight-bold text-primary"><i class="fas fa-trophy mr-2"></i>Kết quả đề tài</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($project['DT_KETQUA'])): ?>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($project['DT_KETQUA'])); ?>
                            </div>
                            
                            <!-- Nếu đề tài đã hoàn thành và có biên bản nghiệm thu -->
                            <?php if ($project['DT_TRANGTHAI'] == 'Đã hoàn thành' && !empty($decision['BB_XEPLOAI'])): ?>
                                <div class="alert alert-success mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <i class="fas fa-award fa-3x"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-1">Xếp loại: <?php echo htmlspecialchars($decision['BB_XEPLOAI']); ?></h6>
                                            <p class="mb-0 small">Ngày nghiệm thu: <?php echo date('d/m/Y', strtotime($decision['BB_NGAYNGHIEMTHU'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> Chưa có kết quả đề tài.
                            </div>
                            
                            <!-- Thêm kết quả đề tài -->
                            <div class="mt-3 text-center">
                                <a href="add_result.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-plus-circle mr-1"></i> Thêm kết quả đề tài
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            // Đổi màu nền cho các dòng trong bảng khi hover
            $('tbody tr').hover(
                function() {
                    $(this).addClass('bg-light');
                },
                function() {
                    $(this).removeClass('bg-light');
                }
            );
            
            // Hiệu ứng cho các card
            $('.card').hover(
                function() {
                    $(this).css('transform', 'translateY(-5px)');
                    $(this).css('transition', 'transform 0.3s ease');
                    $(this).css('box-shadow', '0 0.5rem 1rem rgba(0, 0, 0, 0.15)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                    $(this).css('box-shadow', '0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15)');
                }
            );
            
            // Tự động ẩn thông báo sau 5 giây
            setTimeout(function() {
                $('.alert-dismissible').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>
