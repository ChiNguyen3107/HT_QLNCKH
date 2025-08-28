<?php
// Bao gồm file session để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên, mã lớp và mã sinh viên
$teacher_id = $_SESSION['user_id'];
$lop_ma = $_GET['lop_ma'] ?? '';
$sv_masv = $_GET['sv_masv'] ?? '';

if (empty($lop_ma) || empty($sv_masv)) {
    header("Location: class_management.php");
    exit;
}

// Kiểm tra quyền truy cập - chỉ CVHT mới được xem
$permission_check = $conn->prepare("SELECT COUNT(*) as count FROM advisor_class WHERE GV_MAGV = ? AND LOP_MA = ? AND AC_COHIEULUC = 1");
$permission_check->bind_param("ss", $teacher_id, $lop_ma);
$permission_check->execute();
$has_permission = $permission_check->get_result()->fetch_assoc()['count'] > 0;
$permission_check->close();

if (!$has_permission) {
    header("Location: class_management.php?error=permission");
    exit;
}

// Lấy thông tin sinh viên
$student_sql = "SELECT 
                    sv.SV_MASV,
                    sv.SV_HOSV,
                    sv.SV_TENSV,
                    sv.SV_EMAIL,
                    sv.SV_SDT,
                    sv.SV_NGAYSINH,
                    sv.SV_DIACHI,
                    l.LOP_MA,
                    l.LOP_TEN,
                    l.KH_NAM,
                    k.DV_TENDV
                FROM sinh_vien sv
                JOIN lop l ON sv.LOP_MA = l.LOP_MA
                JOIN khoa k ON l.DV_MADV = k.DV_MADV
                WHERE sv.SV_MASV = ? AND l.LOP_MA = ?";

$stmt = $conn->prepare($student_sql);
$stmt->bind_param("ss", $sv_masv, $lop_ma);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student_info) {
    header("Location: class_detail.php?lop_ma=" . urlencode($lop_ma) . "&error=student_not_found");
    exit;
}

// Lấy tất cả đề tài mà sinh viên tham gia
$projects_sql = "SELECT 
                    dt.DT_MADT,
                    dt.DT_TENDT,
                    dt.DT_MOTA,
                    dt.DT_TRANGTHAI,
                    dt.DT_NGAYTAO,
                    dt.DT_NGAYCAPNHAT,
                    dt.DT_FILEBTM,
                    ldt.LDT_TENLOAI,
                    lvnc.LVNC_TEN,
                    cttg.CTTG_VAITRO,
                    cttg.CTTG_NGAYTHAMGIA,
                    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                    gv.GV_EMAIL as GV_EMAIL,
                    gv.GV_SDT as GV_SDT,
                    k_gv.DV_TENDV as GV_KHOA,
                    -- Hợp đồng (nếu có)
                    hd.HD_MA,
                    hd.HD_NGAYBD,
                    hd.HD_NGAYKT,
                    hd.HD_TONGKINHPHI,
                    -- Phân loại trạng thái
                    CASE 
                        WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN 'Đã hoàn thành'
                        WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN 'Tạm dừng/Hủy'
                        WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN 'Đang thực hiện'
                        WHEN dt.DT_TRANGTHAI LIKE '%duyet%' OR dt.DT_TRANGTHAI LIKE '%cho%' THEN 'Chờ duyệt'
                        ELSE dt.DT_TRANGTHAI
                    END as TRANGTHAI_PHANLOAI,
                    -- Tiến độ
                    CASE 
                        WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN 100
                        WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN 0
                        WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN 60
                        WHEN dt.DT_TRANGTHAI LIKE '%duyet%' OR dt.DT_TRANGTHAI LIKE '%cho%' THEN 10
                        ELSE 25
                    END as TIENDO_PHANTRAM
                FROM chi_tiet_tham_gia cttg
                JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                LEFT JOIN khoa k_gv ON gv.DV_MADV = k_gv.DV_MADV
                LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                WHERE cttg.SV_MASV = ?
                ORDER BY dt.DT_NGAYTAO DESC, cttg.CTTG_NGAYTHAMGIA DESC";

$stmt = $conn->prepare($projects_sql);
$stmt->bind_param("s", $sv_masv);
$stmt->execute();
$projects_result = $stmt->get_result();
$projects = $projects_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lấy tiến độ chi tiết của sinh viên này
$progress_sql = "SELECT 
                    tddt.TDDT_MA,
                    tddt.DT_MADT,
                    tddt.TDDT_TIEUDE,
                    tddt.TDDT_NOIDUNG,
                    tddt.TDDT_PHANTRAMHOANTHANH,
                    tddt.TDDT_FILE,
                    tddt.TDDT_NGAYCAPNHAT,
                    dt.DT_TENDT
                FROM tien_do_de_tai tddt
                JOIN de_tai_nghien_cuu dt ON tddt.DT_MADT = dt.DT_MADT
                WHERE tddt.SV_MASV = ?
                ORDER BY tddt.TDDT_NGAYCAPNHAT DESC";

$stmt = $conn->prepare($progress_sql);
$stmt->bind_param("s", $sv_masv);
$stmt->execute();
$progress_result = $stmt->get_result();
$progress_list = $progress_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Thống kê
$total_projects = count($projects);
$completed_projects = 0;
$in_progress_projects = 0;
$pending_projects = 0;
$suspended_projects = 0;

foreach ($projects as $project) {
    switch ($project['TRANGTHAI_PHANLOAI']) {
        case 'Đã hoàn thành':
            $completed_projects++;
            break;
        case 'Đang thực hiện':
            $in_progress_projects++;
            break;
        case 'Chờ duyệt':
            $pending_projects++;
            break;
        case 'Tạm dừng/Hủy':
            $suspended_projects++;
            break;
    }
}

// Helper functions
function getStatusBadge($status) {
    $badges = [
        'Chờ duyệt' => 'bg-warning',
        'Đang thực hiện' => 'bg-info',
        'Đã hoàn thành' => 'bg-success',
        'Tạm dừng/Hủy' => 'bg-danger'
    ];
    
    $class = $badges[$status] ?? 'bg-secondary';
    return "<span class='badge {$class}'>{$status}</span>";
}

function getProgressColor($percent) {
    if ($percent >= 80) return 'bg-success';
    if ($percent >= 60) return 'bg-info';
    if ($percent >= 40) return 'bg-warning';
    return 'bg-danger';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đề tài của sinh viên - <?= htmlspecialchars($student_info['SV_HOSV'] . ' ' . $student_info['SV_TENSV']) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .card-header {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .project-card {
            border-left: 4px solid #007bff;
            margin-bottom: 1rem;
        }
        
        .project-card.completed {
            border-left-color: #28a745;
        }
        
        .project-card.in-progress {
            border-left-color: #17a2b8;
        }
        
        .project-card.pending {
            border-left-color: #ffc107;
        }
        
        .project-card.suspended {
            border-left-color: #dc3545;
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .progress-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        
        .back-button {
            background: linear-gradient(45deg, #6c757d, #495057);
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .back-button:hover {
            background: linear-gradient(45deg, #495057, #343a40);
            color: white;
            transform: translateY(-1px);
        }
        
        .info-row {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-user-graduate me-3"></i>
                        <?= htmlspecialchars($student_info['SV_HOSV'] . ' ' . $student_info['SV_TENSV']) ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-id-card me-2"></i>
                        MSSV: <strong><?= htmlspecialchars($student_info['SV_MASV']) ?></strong>
                        <span class="ms-3">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Lớp: <strong><?= htmlspecialchars($student_info['LOP_TEN']) ?></strong>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="class_detail.php?lop_ma=<?= urlencode($lop_ma) ?>" class="back-button">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Cột trái: Thông tin sinh viên và thống kê -->
            <div class="col-lg-4">
                <!-- Thông tin sinh viên -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            Thông tin sinh viên
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">Họ và tên:</div>
                            <div><?= htmlspecialchars($student_info['SV_HOSV'] . ' ' . $student_info['SV_TENSV']) ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Mã số sinh viên:</div>
                            <div><?= htmlspecialchars($student_info['SV_MASV']) ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div>
                                <a href="mailto:<?= htmlspecialchars($student_info['SV_EMAIL']) ?>">
                                    <?= htmlspecialchars($student_info['SV_EMAIL']) ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Số điện thoại:</div>
                            <div><?= htmlspecialchars($student_info['SV_SDT']) ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Lớp:</div>
                            <div><?= htmlspecialchars($student_info['LOP_TEN']) ?> (K<?= htmlspecialchars($student_info['KH_NAM']) ?>)</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Khoa:</div>
                            <div><?= htmlspecialchars($student_info['DV_TENDV']) ?></div>
                        </div>
                        
                        <?php if ($student_info['SV_NGAYSINH']): ?>
                        <div class="info-row">
                            <div class="info-label">Ngày sinh:</div>
                            <div><?= date('d/m/Y', strtotime($student_info['SV_NGAYSINH'])) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($student_info['SV_DIACHI']): ?>
                        <div class="info-row">
                            <div class="info-label">Địa chỉ:</div>
                            <div><?= htmlspecialchars($student_info['SV_DIACHI']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Thống kê đề tài -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Thống kê đề tài
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stats-card bg-primary text-white rounded">
                                    <h3 class="mb-1"><?= $total_projects ?></h3>
                                    <small>Tổng đề tài</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-card bg-success text-white rounded">
                                    <h3 class="mb-1"><?= $completed_projects ?></h3>
                                    <small>Hoàn thành</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-card bg-info text-white rounded">
                                    <h3 class="mb-1"><?= $in_progress_projects ?></h3>
                                    <small>Đang thực hiện</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-card bg-warning text-white rounded">
                                    <h3 class="mb-1"><?= $pending_projects ?></h3>
                                    <small>Chờ duyệt</small>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($suspended_projects > 0): ?>
                        <div class="mt-3">
                            <div class="stats-card bg-danger text-white rounded">
                                <h4 class="mb-1"><?= $suspended_projects ?></h4>
                                <small>Tạm dừng/Hủy</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Danh sách đề tài -->
            <div class="col-lg-8">
                <!-- Danh sách đề tài -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-project-diagram me-2"></i>
                            Danh sách đề tài tham gia (<?= $total_projects ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($projects)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Sinh viên chưa tham gia đề tài nào</h5>
                        </div>
                        <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                        <div class="card project-card <?= strtolower(str_replace(['/', ' '], ['-', '-'], $project['TRANGTHAI_PHANLOAI'])) ?>">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-1">
                                            <a href="/NLNganh/view/project/view_project.php?dt_madt=<?= urlencode($project['DT_MADT']) ?>" 
                                               target="_blank" class="text-decoration-none">
                                                <?= htmlspecialchars($project['DT_TENDT']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted"><?= htmlspecialchars($project['DT_MADT']) ?></small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <?= getStatusBadge($project['TRANGTHAI_PHANLOAI']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <strong>Vai trò:</strong> 
                                            <span class="badge bg-primary"><?= htmlspecialchars($project['CTTG_VAITRO']) ?></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Giảng viên HD:</strong> 
                                            <?= htmlspecialchars($project['GV_HOTEN']) ?>
                                        </div>
                                        <?php if ($project['LDT_TENLOAI']): ?>
                                        <div class="mb-2">
                                            <strong>Loại đề tài:</strong> 
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($project['LDT_TENLOAI']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <strong>Ngày tham gia:</strong> 
                                            <?= date('d/m/Y', strtotime($project['CTTG_NGAYTHAMGIA'])) ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Ngày tạo đề tài:</strong> 
                                            <?= date('d/m/Y', strtotime($project['DT_NGAYTAO'])) ?>
                                        </div>
                                        <?php if ($project['HD_MA']): ?>
                                        <div class="mb-2">
                                            <strong>Hợp đồng:</strong> 
                                            <span class="badge bg-success"><?= htmlspecialchars($project['HD_MA']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Tiến độ -->
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small><strong>Tiến độ:</strong></small>
                                        <small><?= $project['TIENDO_PHANTRAM'] ?>%</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar <?= getProgressColor($project['TIENDO_PHANTRAM']) ?>" 
                                             style="width: <?= $project['TIENDO_PHANTRAM'] ?>%"></div>
                                    </div>
                                </div>
                                
                                <!-- Mô tả -->
                                <?php if ($project['DT_MOTA']): ?>
                                <div class="mt-3">
                                    <strong>Mô tả:</strong>
                                    <div class="text-muted" style="max-height: 100px; overflow-y: auto;">
                                        <?= nl2br(htmlspecialchars(substr($project['DT_MOTA'], 0, 300))) ?>
                                        <?= strlen($project['DT_MOTA']) > 300 ? '...' : '' ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Hành động -->
                                <div class="mt-3 text-end">
                                    <a href="/NLNganh/view/project/view_project.php?dt_madt=<?= urlencode($project['DT_MADT']) ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tiến độ báo cáo -->
                <?php if (!empty($progress_list)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>
                            Tiến độ báo cáo (<?= count($progress_list) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($progress_list as $progress): ?>
                        <div class="progress-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1"><?= htmlspecialchars($progress['TDDT_TIEUDE']) ?></h6>
                                <span class="badge bg-info"><?= htmlspecialchars($progress['DT_TENDT']) ?></span>
                            </div>
                            
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar <?= getProgressColor($progress['TDDT_PHANTRAMHOANTHANH']) ?>" 
                                     style="width: <?= $progress['TDDT_PHANTRAMHOANTHANH'] ?>%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    Hoàn thành: <strong><?= $progress['TDDT_PHANTRAMHOANTHANH'] ?>%</strong>
                                </small>
                                <small class="text-muted">
                                    Cập nhật: <?= date('d/m/Y H:i', strtotime($progress['TDDT_NGAYCAPNHAT'])) ?>
                                </small>
                            </div>
                            
                            <div class="progress-content">
                                <?= nl2br(htmlspecialchars($progress['TDDT_NOIDUNG'])) ?>
                            </div>
                            
                            <?php if ($progress['TDDT_FILE']): ?>
                            <div class="mt-2">
                                <a href="/NLNganh/uploads/progress/<?= htmlspecialchars($progress['TDDT_FILE']) ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-file-download me-1"></i>Tải file
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
