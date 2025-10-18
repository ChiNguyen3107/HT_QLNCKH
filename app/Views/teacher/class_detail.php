<?php
// Bao gồm file session để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên và mã lớp
$teacher_id = $_SESSION['user_id'];
$lop_ma = $_GET['lop_ma'] ?? '';

if (empty($lop_ma)) {
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

// Lấy thông tin lớp từ các bảng gốc
$class_info_sql = "SELECT 
                        l.LOP_MA,
                        l.LOP_TEN,
                        l.KH_NAM,
                        k.DV_TENDV,
                        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as CVHT_HOTEN,
                        -- Thống kê sinh viên
                        COUNT(DISTINCT sv.SV_MASV) as TONG_SV,
                        COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL THEN sv.SV_MASV END) as SV_CO_DETAI,
                        COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NULL THEN sv.SV_MASV END) as SV_CHUA_CO_DETAI,
                        -- Thống kê sinh viên theo trạng thái đề tài
                        COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NULL THEN sv.SV_MASV END) as SV_CHUA_THAM_GIA,
                        COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL AND dt.DT_TRANGTHAI LIKE '%thuc%' THEN sv.SV_MASV END) as SV_DANG_THAM_GIA,
                        COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL AND dt.DT_TRANGTHAI LIKE '%hoan%' THEN sv.SV_MASV END) as SV_DA_HOAN_THANH,
                        COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL AND (dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%') THEN sv.SV_MASV END) as SV_BI_TU_CHOI,
                        -- Thống kê đề tài (để tham khảo)
                        COUNT(DISTINCT CASE WHEN dt.DT_MADT IS NOT NULL THEN dt.DT_MADT END) as TONG_DETAI,
                        COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN dt.DT_MADT END) as DETAI_HOAN_THANH,
                        COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN dt.DT_MADT END) as DETAI_DANG_THUCHIEN,
                        COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN dt.DT_MADT END) as DETAI_TAM_DUNG
                    FROM advisor_class ac
                    JOIN lop l ON ac.LOP_MA = l.LOP_MA
                    JOIN khoa k ON l.DV_MADV = k.DV_MADV
                    JOIN giang_vien gv ON ac.GV_MAGV = gv.GV_MAGV
                    LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
                    LEFT JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV
                    LEFT JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                    WHERE ac.GV_MAGV = ? AND ac.LOP_MA = ? AND ac.AC_COHIEULUC = 1
                    GROUP BY l.LOP_MA, l.LOP_TEN, l.KH_NAM, k.DV_TENDV, gv.GV_HOGV, gv.GV_TENGV";

$stmt = $conn->prepare($class_info_sql);
$stmt->bind_param("ss", $teacher_id, $lop_ma);
$stmt->execute();
$class_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class_info) {
    header("Location: class_management.php?error=not_found");
    exit;
}

// Xử lý tham số tìm kiếm và phân trang
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
// Tăng limit để hiển thị nhiều sinh viên hơn hoặc bỏ limit nếu không cần phân trang
$limit = min(200, max(1, intval($_GET['limit'] ?? 200)));

// Truy vấn danh sách sinh viên với thông tin chi tiết (không lặp lại)
$sql = "SELECT 
            sv.SV_MASV,
            sv.SV_HOSV,
            sv.SV_TENSV,
            sv.SV_EMAIL,
            sv.SV_SDT,
            l.LOP_MA,
            l.LOP_TEN,
            l.KH_NAM,
            k.DV_TENDV,
            -- Thông tin đề tài chính (lấy đề tài mới nhất hoặc quan trọng nhất)
            dt.DT_MADT,
            dt.DT_TENDT,
            dt.DT_TRANGTHAI,
            dt.DT_NGAYTAO,
            dt.DT_NGAYCAPNHAT,
            -- Thông tin tham gia
            cttg.CTTG_VAITRO,
            cttg.CTTG_NGAYTHAMGIA,
            -- Thông tin giảng viên hướng dẫn
            gv.GV_MAGV,
            CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
            gv.GV_EMAIL,
            -- Phân loại trạng thái
            CASE 
                WHEN dt.DT_MADT IS NULL THEN 'Chưa tham gia'
                WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN 'Đã hoàn thành'
                WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN 'Bị từ chối/Tạm dừng'
                WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN 'Đang tham gia'
                ELSE 'Đang tham gia'
            END as TRANGTHAI_PHANLOAI,
            -- Tiến độ (giả định dựa trên trạng thái)
            CASE 
                WHEN dt.DT_MADT IS NULL THEN 0
                WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN 100
                WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN 0
                WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN 50
                ELSE 25
            END as TIENDO_PHANTRAM,
            -- Số lượng đề tài tham gia
            (
                SELECT COUNT(DISTINCT cttg2.DT_MADT) 
                FROM chi_tiet_tham_gia cttg2 
                WHERE cttg2.SV_MASV = sv.SV_MASV AND cttg2.DT_MADT IS NOT NULL
            ) as TONG_SO_DETAI_THAM_GIA
        FROM advisor_class ac
        JOIN lop l ON ac.LOP_MA = l.LOP_MA
        JOIN khoa k ON l.DV_MADV = k.DV_MADV
        LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
        LEFT JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV
        LEFT JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        WHERE ac.GV_MAGV = ? AND ac.LOP_MA = ? AND ac.AC_COHIEULUC = 1";

$params = [$teacher_id, $lop_ma];
if ($status) {
    $sql .= " AND CASE 
                WHEN dt.DT_MADT IS NULL THEN 'Chưa tham gia'
                WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN 'Đã hoàn thành'
                WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN 'Bị từ chối/Tạm dừng'
                WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN 'Đang tham gia'
                ELSE 'Đang tham gia'
            END = ?";
    $params[] = $status;
}
if ($search) {
    $sql .= " AND (sv.SV_MASV LIKE ? OR CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) LIKE ? OR dt.DT_TENDT LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY sv.SV_MASV ORDER BY CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = ($page - 1) * $limit;

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

// Lấy danh sách sinh viên
$students_list = [];
while ($student = $students->fetch_assoc()) {
    $students_list[] = $student;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết lớp <?= htmlspecialchars($class_info['LOP_TEN']) ?> - NCKH</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Responsive design cho sidebar */
        @media (max-width: 992px) {
            .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding: 10px;
            }
        }
        
        .class-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border-radius: 15px; 
            padding: 30px; 
            margin-bottom: 30px; 
        }
        
        .stats-card { 
            border: 1px solid #e0e0e0; 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 20px; 
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .filter-section { 
            background-color: #f8f9fa; 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 20px; 
        }
        
        .status-badge { 
            font-size: 0.8em; 
            padding: 4px 8px; 
            border-radius: 12px; 
        }
        
        .student-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .progress {
            border-radius: 10px;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-radius: 4px 0 0 4px;
        }
        
        .btn-group .btn:last-child {
            border-radius: 0 4px 4px 0;
        }
        
        .card-header .btn {
            font-size: 0.875rem;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .modal-lg .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../../include/teacher_sidebar.php'; ?>
    
    <!-- Begin Page Content -->
    <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="class_management.php" class="btn btn-secondary me-3">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-graduation-cap text-primary"></i>
                        Chi tiết lớp <?= htmlspecialchars($class_info['LOP_TEN']) ?>
                    </h1>
                </div>
                <div>
                    <button class="btn btn-success me-2" onclick="exportData()">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </button>
                </div>
            </div>
            
            <!-- Thông tin lớp -->
            <div class="class-header">
                <div class="row">
                    <div class="col-md-8">
                        <h2><?= htmlspecialchars($class_info['LOP_TEN']) ?></h2>
                        <p class="mb-2"><i class="fas fa-university"></i> <?= htmlspecialchars($class_info['DV_TENDV']) ?></p>
                        <p class="mb-2"><i class="fas fa-calendar"></i> Niên khóa: <?= htmlspecialchars($class_info['KH_NAM']) ?></p>
                        <p class="mb-0"><i class="fas fa-user-tie"></i> CVHT: <?= htmlspecialchars($class_info['CVHT_HOTEN']) ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="display-4"><?= $class_info['TONG_SV'] ?? 0 ?></div>
                        <div>sinh viên</div>
                        <div class="mt-2">
                            <?php 
                            $ty_le_tham_gia = $class_info['TONG_SV'] > 0 ? 
                                round(($class_info['SV_CO_DETAI'] / $class_info['TONG_SV']) * 100, 1) : 0;
                            ?>
                            <span class="badge bg-light text-dark">
                                <?= $ty_le_tham_gia ?>% tham gia
                            </span>
                        </div>
                        <div class="mt-1">
                            <small class="text-muted">
                                <?= $class_info['SV_CO_DETAI'] ?? 0 ?> có đề tài
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Thống kê -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card bg-danger bg-opacity-10 border-danger">
                        <div class="text-center">
                            <i class="fas fa-user-times fa-2x text-danger mb-2"></i>
                            <h4 class="text-danger"><?= $class_info['SV_CHUA_THAM_GIA'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Chưa tham gia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-warning bg-opacity-10 border-warning">
                        <div class="text-center">
                            <i class="fas fa-user-clock fa-2x text-warning mb-2"></i>
                            <h4 class="text-warning"><?= $class_info['SV_DANG_THAM_GIA'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Đang tham gia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-success bg-opacity-10 border-success">
                        <div class="text-center">
                            <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                            <h4 class="text-success"><?= $class_info['SV_DA_HOAN_THANH'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Đã hoàn thành</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-secondary bg-opacity-10 border-secondary">
                        <div class="text-center">
                            <i class="fas fa-user-slash fa-2x text-secondary mb-2"></i>
                            <h4 class="text-secondary"><?= $class_info['SV_BI_TU_CHOI'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Bị từ chối</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bộ lọc -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="lop_ma" value="<?= htmlspecialchars($lop_ma) ?>">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="MSSV, tên SV, tên đề tài...">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tất cả</option>
                            <option value="Chưa tham gia" <?= $status === 'Chưa tham gia' ? 'selected' : '' ?>>Chưa tham gia</option>
                            <option value="Đang tham gia" <?= $status === 'Đang tham gia' ? 'selected' : '' ?>>Đang tham gia</option>
                            <option value="Đã hoàn thành" <?= $status === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                            <option value="Bị từ chối/Tạm dừng" <?= $status === 'Bị từ chối/Tạm dừng' ? 'selected' : '' ?>>Bị từ chối/Tạm dừng</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Danh sách sinh viên -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users text-primary"></i>
                        Danh sách sinh viên
                        <span class="badge bg-primary ms-2"><?= $class_info['TONG_SV'] ?? 0 ?> sinh viên</span>
                        <?php if ($class_info['TONG_SV'] > $limit): ?>
                            <span class="badge bg-info ms-1">Hiển thị <?= count($students_list) ?>/<?= $class_info['TONG_SV'] ?></span>
                        <?php endif; ?>
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleView()">
                            <i class="fas fa-th-large"></i> Card view
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="exportStudentList()">
                            <i class="fas fa-download"></i> Xuất danh sách
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($students_list)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>Không tìm thấy sinh viên</h5>
                            <p class="text-muted">Thử thay đổi bộ lọc tìm kiếm</p>
                        </div>
                    <?php else: ?>
                        <!-- Table View -->
                        <div id="tableView" class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">MSSV</th>
                                        <th width="15%">Họ tên</th>
                                        <th width="12%">Trạng thái</th>
                                        <th width="25%">Đề tài</th>
                                        <th width="15%">GV hướng dẫn</th>
                                        <th width="10%">Tiến độ</th>
                                        <th width="13%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_list as $student): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-primary"><?= htmlspecialchars($student['SV_MASV']) ?></strong>
                                                <?php if ($student['SV_EMAIL']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($student['SV_EMAIL']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($student['SV_HOSV'] . ' ' . $student['SV_TENSV']) ?></div>
                                                <?php if ($student['SV_SDT']): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($student['SV_SDT']) ?></small>
                                                <?php endif; ?>
                                                <?php if ($student['TONG_SO_DETAI_THAM_GIA'] > 0): ?>
                                                    <br><small class="badge bg-info"><?= $student['TONG_SO_DETAI_THAM_GIA'] ?> đề tài</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= getStatusBadgeClass($student['TRANGTHAI_PHANLOAI']) ?>">
                                                    <i class="fas <?= getStatusIcon($student['TRANGTHAI_PHANLOAI']) ?> me-1"></i>
                                                    <?= htmlspecialchars($student['TRANGTHAI_PHANLOAI']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($student['DT_TENDT']): ?>
                                                    <div class="fw-bold text-success"><?= htmlspecialchars(substr($student['DT_TENDT'], 0, 50)) ?>
                                                        <?= strlen($student['DT_TENDT']) > 50 ? '...' : '' ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= $student['DT_NGAYTAO'] ? date('d/m/Y', strtotime($student['DT_NGAYTAO'])) : 'N/A' ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">
                                                        <i class="fas fa-times-circle me-1"></i>Chưa có đề tài
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['GV_HOTEN']): ?>
                                                    <div class="fw-bold"><?= htmlspecialchars($student['GV_HOTEN']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($student['GV_EMAIL'] ?? '') ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['TIENDO_PHANTRAM'] > 0): ?>
                                                    <div class="progress mb-1" style="height: 6px;">
                                                        <div class="progress-bar bg-<?= getProgressColor($student['TIENDO_PHANTRAM']) ?>" 
                                                             style="width: <?= $student['TIENDO_PHANTRAM'] ?>%"></div>
                                                    </div>
                                                    <small class="fw-bold text-<?= getProgressColor($student['TIENDO_PHANTRAM']) ?>">
                                                        <?= $student['TIENDO_PHANTRAM'] ?>%
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($student['TONG_SO_DETAI_THAM_GIA'] > 1): ?>
                                                        <!-- Sinh viên có nhiều đề tài - xem tất cả -->
                                                        <a href="student_projects_detail.php?lop_ma=<?= urlencode($lop_ma) ?>&sv_masv=<?= urlencode($student['SV_MASV']) ?>" 
                                                           class="btn btn-sm btn-outline-success" title="Xem tất cả đề tài (<?= $student['TONG_SO_DETAI_THAM_GIA'] ?>)">
                                                            <i class="fas fa-list"></i>
                                                        </a>
                                                    <?php elseif ($student['DT_MADT']): ?>
                                                        <!-- Sinh viên có 1 đề tài - xem trực tiếp -->
                                                        <a href="../project/view_project.php?dt_madt=<?= urlencode($student['DT_MADT']) ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Xem đề tài">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="showStudentDetail('<?= htmlspecialchars(json_encode($student)) ?>')" 
                                                            title="Chi tiết sinh viên">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Card View (ẩn mặc định) -->
                        <div id="cardView" class="row p-3" style="display: none;">
                            <?php foreach ($students_list as $student): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card student-card h-100">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-primary"><?= htmlspecialchars($student['SV_MASV']) ?></strong>
                                                <span class="status-badge <?= getStatusBadgeClass($student['TRANGTHAI_PHANLOAI']) ?>">
                                                    <?= htmlspecialchars($student['TRANGTHAI_PHANLOAI']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($student['SV_HOSV'] . ' ' . $student['SV_TENSV']) ?></h6>
                                            <?php if ($student['SV_EMAIL']): ?>
                                                <p class="card-text small text-muted">
                                                    <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($student['SV_EMAIL']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($student['TONG_SO_DETAI_THAM_GIA'] > 0): ?>
                                                <p class="card-text small">
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-project-diagram me-1"></i><?= $student['TONG_SO_DETAI_THAM_GIA'] ?> đề tài tham gia
                                                    </span>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if ($student['DT_TENDT']): ?>
                                                <div class="mb-2">
                                                    <strong class="text-success">Đề tài:</strong><br>
                                                    <small><?= htmlspecialchars(substr($student['DT_TENDT'], 0, 60)) ?>
                                                        <?= strlen($student['DT_TENDT']) > 60 ? '...' : '' ?>
                                                    </small>
                                                </div>
                                                <?php if ($student['GV_HOTEN']): ?>
                                                    <div class="mb-2">
                                                        <strong>GV hướng dẫn:</strong><br>
                                                        <small><?= htmlspecialchars($student['GV_HOTEN']) ?></small>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($student['TIENDO_PHANTRAM'] > 0): ?>
                                                    <div class="mb-2">
                                                        <strong>Tiến độ:</strong>
                                                        <div class="progress mt-1" style="height: 8px;">
                                                            <div class="progress-bar bg-<?= getProgressColor($student['TIENDO_PHANTRAM']) ?>" 
                                                                 style="width: <?= $student['TIENDO_PHANTRAM'] ?>%"></div>
                                                        </div>
                                                        <small class="text-<?= getProgressColor($student['TIENDO_PHANTRAM']) ?>">
                                                            <?= $student['TIENDO_PHANTRAM'] ?>%
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p class="text-muted fst-italic">
                                                    <i class="fas fa-times-circle me-1"></i>Chưa có đề tài
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group w-100" role="group">
                                                <?php if ($student['TONG_SO_DETAI_THAM_GIA'] > 1): ?>
                                                    <!-- Sinh viên có nhiều đề tài -->
                                                    <a href="student_projects_detail.php?lop_ma=<?= urlencode($lop_ma) ?>&sv_masv=<?= urlencode($student['SV_MASV']) ?>" 
                                                       class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-list me-1"></i>Tất cả đề tài (<?= $student['TONG_SO_DETAI_THAM_GIA'] ?>)
                                                    </a>
                                                <?php elseif ($student['DT_MADT']): ?>
                                                    <!-- Sinh viên có 1 đề tài -->
                                                    <a href="../project/view_project.php?dt_madt=<?= urlencode($student['DT_MADT']) ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>Xem đề tài
                                                    </a>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="showStudentDetail('<?= htmlspecialchars(json_encode($student)) ?>')">
                                                    <i class="fas fa-info-circle me-1"></i>Chi tiết
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Phân trang -->
                    <?php if ($class_info['TONG_SV'] > $limit): ?>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    Hiển thị <?= (($page - 1) * $limit) + 1 ?> - <?= min($page * $limit, $class_info['TONG_SV']) ?> 
                                    trong tổng số <?= $class_info['TONG_SV'] ?> sinh viên
                                </div>
                                <nav aria-label="Phân trang">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php
                                        $total_pages = ceil($class_info['TONG_SV'] / $limit);
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        ?>
                                        
                                        <!-- Nút Trước -->
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?lop_ma=<?= urlencode($lop_ma) ?>&page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Các trang -->
                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?lop_ma=<?= urlencode($lop_ma) ?>&page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <!-- Nút Sau -->
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?lop_ma=<?= urlencode($lop_ma) ?>&page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modal Chi tiết Sinh viên -->
    <div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentDetailModalLabel">
                        <i class="fas fa-user-graduate text-primary"></i>
                        Chi tiết sinh viên
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="studentDetailContent">
                    <!-- Nội dung sẽ được load động -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportData() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = 'class_export.php?' + params.toString();
        }
        
        function toggleView() {
            const tableView = document.getElementById('tableView');
            const cardView = document.getElementById('cardView');
            const toggleBtn = event.target;
            
            if (tableView.style.display === 'none') {
                tableView.style.display = 'block';
                cardView.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-th-large"></i> Card view';
            } else {
                tableView.style.display = 'none';
                cardView.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-list"></i> Table view';
            }
        }
        
        function exportStudentList() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'students');
            window.location.href = 'class_export.php?' + params.toString();
        }
        
        function showStudentDetail(studentData) {
            try {
                const student = typeof studentData === 'string' ? JSON.parse(decodeURIComponent(studentData)) : studentData;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Thông tin cá nhân
                            </h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>MSSV:</strong></td>
                                    <td>${student.SV_MASV}</td>
                                </tr>
                                <tr>
                                    <td><strong>Họ tên:</strong></td>
                                    <td>${student.SV_HOSV} ${student.SV_TENSV}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>${student.SV_EMAIL || '<span class="text-muted">Chưa cập nhật</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Số điện thoại:</strong></td>
                                    <td>${student.SV_SDT || '<span class="text-muted">Chưa cập nhật</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Lớp:</strong></td>
                                    <td>${student.LOP_MA} - ${student.LOP_TEN}</td>
                                </tr>
                                <tr>
                                    <td><strong>Khoa:</strong></td>
                                    <td>${student.DV_TENDV}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">
                                <i class="fas fa-project-diagram me-2"></i>Thông tin đề tài
                            </h6>
                            ${student.DT_TENDT ? `
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="40%"><strong>Mã đề tài:</strong></td>
                                        <td>${student.DT_MADT}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tên đề tài:</strong></td>
                                        <td>${student.DT_TENDT}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái:</strong></td>
                                        <td>
                                            <span class="status-badge ${getStatusBadgeClass(student.TRANGTHAI_PHANLOAI)}">
                                                ${student.TRANGTHAI_PHANLOAI}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ngày tạo:</strong></td>
                                        <td>${student.DT_NGAYTAO ? new Date(student.DT_NGAYTAO).toLocaleDateString('vi-VN') : 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Vai trò:</strong></td>
                                        <td>${student.CTTG_VAITRO || '<span class="text-muted">Chưa cập nhật</span>'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ngày tham gia:</strong></td>
                                        <td>${student.CTTG_NGAYTHAMGIA ? new Date(student.CTTG_NGAYTHAMGIA).toLocaleDateString('vi-VN') : 'N/A'}</td>
                                    </tr>
                                    ${student.GV_HOTEN ? `
                                                                            <tr>
                                        <td><strong>GV hướng dẫn:</strong></td>
                                        <td>${student.GV_HOTEN}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email GV:</strong></td>
                                        <td>${student.GV_EMAIL || '<span class="text-muted">Chưa cập nhật</span>'}</td>
                                    </tr>
                                                                        <tr>
                                        <td><strong>Tổng đề tài:</strong></td>
                                        <td><span class="badge bg-info">${student.TONG_SO_DETAI_THAM_GIA || 0} đề tài tham gia</span></td>
                                    </tr>
                                    ` : ''}
                                    <tr>
                                        <td><strong>Tổng đề tài:</strong></td>
                                        <td><span class="badge bg-info">${student.TONG_SO_DETAI_THAM_GIA || 0} đề tài tham gia</span></td>
                                    </tr>
                                </table>
                            ` : `
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Sinh viên chưa tham gia đề tài nào
                                </div>
                            `}
                        </div>
                    </div>
                    ${student.TIENDO_PHANTRAM > 0 ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-info mb-2">
                                    <i class="fas fa-chart-line me-2"></i>Tiến độ thực hiện
                                </h6>
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar bg-${getProgressColor(student.TIENDO_PHANTRAM)}" 
                                         style="width: ${student.TIENDO_PHANTRAM}%"
                                         role="progressbar">
                                        ${student.TIENDO_PHANTRAM}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                `;
                document.getElementById('studentDetailContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('studentDetailModal')).show();
            } catch (error) {
                console.error('Error showing student detail:', error);
                alert('Có lỗi khi hiển thị thông tin chi tiết');
            }
        }
        
        function getStatusBadgeClass(status) {
            switch (status) {
                case 'Chưa tham gia': return 'bg-danger';
                case 'Đang tham gia': return 'bg-warning text-dark';
                case 'Đã hoàn thành': return 'bg-success';
                case 'Bị từ chối/Tạm dừng': return 'bg-secondary';
                default: return 'bg-light text-dark';
            }
        }
        
        function getProgressColor(progress) {
            if (progress >= 80) return 'success';
            if (progress >= 50) return 'warning';
            if (progress >= 25) return 'info';
            return 'danger';
        }
    </script>
</body>
</html>

<?php
// Helper functions
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Chưa tham gia': return 'bg-danger';
        case 'Đang tham gia': return 'bg-warning text-dark';
        case 'Đã hoàn thành': return 'bg-success';
        case 'Bị từ chối/Tạm dừng': return 'bg-secondary';
        default: return 'bg-light text-dark';
    }
}

function getProgressColor($progress) {
    if ($progress >= 80) return 'success';
    if ($progress >= 50) return 'warning';
    if ($progress >= 25) return 'info';
    return 'danger';
}

function getStatusIcon($status) {
    switch ($status) {
        case 'Chưa tham gia': return 'fa-times-circle';
        case 'Đang tham gia': return 'fa-play-circle';
        case 'Đã hoàn thành': return 'fa-check-circle';
        case 'Bị từ chối/Tạm dừng': return 'fa-pause-circle';
        default: return 'fa-question-circle';
    }
}
?>
