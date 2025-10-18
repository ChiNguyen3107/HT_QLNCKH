<?php
// Bao gồm file session để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkAdminRole();

// Kết nối database
include '../../include/connect.php';

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'assign_advisor':
            assignAdvisor($conn);
            break;
        case 'remove_advisor':
            removeAdvisor($conn);
            break;
    }
}

function assignAdvisor($conn) {
    $gv_magv = $_POST['gv_magv'] ?? '';
    $lop_ma = $_POST['lop_ma'] ?? '';
    $ngay_batdau = $_POST['ngay_batdau'] ?? '';
    $ghi_chu = $_POST['ghi_chu'] ?? '';
    
    if (empty($gv_magv) || empty($lop_ma)) {
        $_SESSION['error'] = 'Vui lòng chọn giảng viên và lớp học';
        return;
    }
    
    // Kiểm tra xem lớp đã có CVHT hiệu lực chưa
    $check_sql = "SELECT COUNT(*) as count FROM advisor_class WHERE LOP_MA = ? AND AC_COHIEULUC = 1";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $lop_ma);
    $stmt->execute();
    $has_advisor = $stmt->get_result()->fetch_assoc()['count'] > 0;
    $stmt->close();
    
    if ($has_advisor) {
        // Huỷ hiệu lực CVHT cũ
        $update_sql = "UPDATE advisor_class SET AC_COHIEULUC = 0, AC_NGAYKETTHUC = CURDATE() WHERE LOP_MA = ? AND AC_COHIEULUC = 1";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("s", $lop_ma);
        $stmt->execute();
        $stmt->close();
    }
    
    // Thêm CVHT mới
    $insert_sql = "INSERT INTO advisor_class (GV_MAGV, LOP_MA, AC_NGAYBATDAU, AC_COHIEULUC, AC_GHICHU, AC_NGUOICAPNHAT) VALUES (?, ?, ?, 1, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $admin_user = $_SESSION['user_id'];
    $stmt->bind_param("sssss", $gv_magv, $lop_ma, $ngay_batdau, $ghi_chu, $admin_user);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Gán cố vấn học tập thành công!';
    } else {
        $_SESSION['error'] = 'Có lỗi xảy ra khi gán cố vấn học tập: ' . $stmt->error;
    }
    $stmt->close();
}

function removeAdvisor($conn) {
    $ac_id = $_POST['ac_id'] ?? '';
    
    if (empty($ac_id)) {
        $_SESSION['error'] = 'ID không hợp lệ';
        return;
    }
    
    // Huỷ hiệu lực CVHT
    $update_sql = "UPDATE advisor_class SET AC_COHIEULUC = 0, AC_NGAYKETTHUC = CURDATE(), AC_NGUOICAPNHAT = ? WHERE AC_ID = ?";
    $stmt = $conn->prepare($update_sql);
    $admin_user = $_SESSION['user_id'];
    $stmt->bind_param("si", $admin_user, $ac_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Huỷ gán cố vấn học tập thành công!';
    } else {
        $_SESSION['error'] = 'Có lỗi xảy ra khi huỷ gán: ' . $stmt->error;
    }
    $stmt->close();
}

// Lấy tham số lọc từ URL
$search = $_GET['search'] ?? '';
$khoa = $_GET['khoa'] ?? '';
$status = $_GET['status'] ?? '';
$giang_vien = $_GET['giang_vien'] ?? '';

// Lấy danh sách CVHT hiện tại với bộ lọc
$current_advisors_sql = "SELECT 
    ac.AC_ID,
    ac.GV_MAGV,
    gv.GV_HOGV,
    gv.GV_TENGV,
    ac.LOP_MA,
    l.LOP_TEN,
    l.KH_NAM,
    ac.AC_NGAYBATDAU,
    ac.AC_NGAYKETTHUC,
    ac.AC_COHIEULUC,
    ac.AC_GHICHU,
    ac.AC_NGUOICAPNHAT,
    dv.DV_TENDV
FROM advisor_class ac
JOIN giang_vien gv ON ac.GV_MAGV = gv.GV_MAGV
JOIN lop l ON ac.LOP_MA = l.LOP_MA
JOIN khoa dv ON l.DV_MADV = dv.DV_MADV
WHERE 1=1";

$params = [];
$param_types = '';

if ($search) {
    $current_advisors_sql .= " AND (l.LOP_TEN LIKE ? OR gv.GV_HOGV LIKE ? OR gv.GV_TENGV LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($khoa) {
    $current_advisors_sql .= " AND dv.DV_TENDV = ?";
    $params[] = $khoa;
    $param_types .= 's';
}

if ($status !== '') {
    $current_advisors_sql .= " AND ac.AC_COHIEULUC = ?";
    $params[] = $status;
    $param_types .= 'i';
}

if ($giang_vien) {
    $current_advisors_sql .= " AND ac.GV_MAGV = ?";
    $params[] = $giang_vien;
    $param_types .= 's';
}

$current_advisors_sql .= " ORDER BY ac.AC_COHIEULUC DESC, l.LOP_TEN, gv.GV_TENGV";

$stmt = $conn->prepare($current_advisors_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$current_advisors = $stmt->get_result();
$stmt->close();

// Lấy danh sách giảng viên với thông tin khoa
$teachers_sql = "SELECT gv.GV_MAGV, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN, gv.DV_MADV, dv.DV_TENDV 
                 FROM giang_vien gv 
                 LEFT JOIN khoa dv ON gv.DV_MADV = dv.DV_MADV 
                 ORDER BY gv.GV_TENGV, gv.GV_HOGV";
$teachers = $conn->query($teachers_sql);

// Lấy danh sách lớp với thông tin khoa và khóa
$classes_sql = "SELECT l.LOP_MA, l.LOP_TEN, l.KH_NAM, dv.DV_TENDV, dv.DV_MADV 
                FROM lop l 
                JOIN khoa dv ON l.DV_MADV = dv.DV_MADV 
                ORDER BY l.LOP_TEN";
$classes = $conn->query($classes_sql);

// Lấy danh sách khoa cho filter
$departments = $conn->query("SELECT DISTINCT DV_TENDV FROM khoa ORDER BY DV_TENDV");
$departments_list = [];
if ($departments) {
    while ($row = $departments->fetch_assoc()) {
        $departments_list[] = $row['DV_TENDV'];
    }
}

// Lấy danh sách giảng viên cho filter
$teachers_filter = $conn->query("SELECT GV_MAGV, CONCAT(GV_HOGV, ' ', GV_TENGV) as GV_HOTEN FROM giang_vien ORDER BY GV_TENGV, GV_HOGV");
$teachers_list = [];
if ($teachers_filter) {
    while ($row = $teachers_filter->fetch_assoc()) {
        $teachers_list[] = $row;
    }
}

// Lấy danh sách khóa cho filter
$academic_years = $conn->query("SELECT DISTINCT KH_NAM FROM lop ORDER BY KH_NAM DESC");
$years_list = [];
if ($academic_years) {
    while ($row = $academic_years->fetch_assoc()) {
        $years_list[] = $row['KH_NAM'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Cố vấn học tập - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .advisor-card { border: 1px solid #e0e0e0; border-radius: 10px; margin-bottom: 15px; }
        .active-advisor { border-left: 4px solid #28a745; }
        .inactive-advisor { border-left: 4px solid #dc3545; opacity: 0.7; }
        
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9em;
        }
        
        .modal-filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
        }
        
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
    </style>
</head>
<body>
    <?php include '../../include/admin_sidebar.php'; ?>
    
    <!-- Begin Page Content -->
    <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user-tie text-primary"></i>
                        Quản lý Cố vấn học tập
                    </h1>
                    <p class="text-muted">Gán và quản lý cố vấn học tập cho các lớp</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                        <i class="fas fa-plus"></i> Gán CVHT mới
                    </button>
                </div>
            </div>
            
            <!-- Thông báo -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Thống kê nhanh -->
            <?php
            $total_advisors = $current_advisors->num_rows;
            $active_advisors = 0;
            $inactive_advisors = 0;
            
            // Reset pointer để đếm lại
            $current_advisors->data_seek(0);
            while ($advisor = $current_advisors->fetch_assoc()) {
                if ($advisor['AC_COHIEULUC']) {
                    $active_advisors++;
                } else {
                    $inactive_advisors++;
                }
            }
            // Reset pointer lại để hiển thị
            $current_advisors->data_seek(0);
            ?>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4><?= $total_advisors ?></h4>
                            <p class="mb-0">Tổng CVHT</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h4><?= $active_advisors ?></h4>
                            <p class="mb-0">Đang hiệu lực</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-times-circle fa-2x mb-2"></i>
                            <h4><?= $inactive_advisors ?></h4>
                            <p class="mb-0">Đã huỷ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage fa-2x mb-2"></i>
                            <h4><?= $total_advisors > 0 ? round(($active_advisors / $total_advisors) * 100, 1) : 0 ?>%</h4>
                            <p class="mb-0">Tỷ lệ hiệu lực</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bộ lọc -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Tên lớp, giảng viên...">
                    </div>
                    <div class="col-md-2">
                        <label for="khoa" class="form-label">Khoa</label>
                        <select class="form-select" id="khoa" name="khoa">
                            <option value="">Tất cả khoa</option>
                            <?php foreach ($departments_list as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" 
                                        <?= $khoa === $dept ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tất cả</option>
                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Đang hiệu lực</option>
                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Đã huỷ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="giang_vien" class="form-label">Giảng viên</label>
                        <select class="form-select" id="giang_vien" name="giang_vien">
                            <option value="">Tất cả giảng viên</option>
                            <?php foreach ($teachers_list as $teacher): ?>
                                <option value="<?= htmlspecialchars($teacher['GV_MAGV']) ?>" 
                                        <?= $giang_vien === $teacher['GV_MAGV'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['GV_HOTEN']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                            <a href="manage_advisor.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Danh sách CVHT hiện tại -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list"></i>
                            Danh sách Cố vấn học tập
                        </h5>
                        <?php if ($search || $khoa || $status !== '' || $giang_vien): ?>
                            <span class="stats-badge">
                                <i class="fas fa-filter"></i>
                                <?= $current_advisors->num_rows ?> kết quả tìm kiếm
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($current_advisors->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($advisor = $current_advisors->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card advisor-card <?= $advisor['AC_COHIEULUC'] ? 'active-advisor' : 'inactive-advisor' ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-graduation-cap text-primary"></i>
                                                    <?= htmlspecialchars($advisor['LOP_TEN']) ?>
                                                </h6>
                                                <span class="badge <?= $advisor['AC_COHIEULUC'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $advisor['AC_COHIEULUC'] ? 'Đang hiệu lực' : 'Đã huỷ' ?>
                                                </span>
                                            </div>
                                            
                                            <p class="card-text mb-1">
                                                <strong>CVHT:</strong> <?= htmlspecialchars($advisor['GV_HOGV'] . ' ' . $advisor['GV_TENGV']) ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <strong>Khoa:</strong> <?= htmlspecialchars($advisor['DV_TENDV']) ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <strong>Ngày bắt đầu:</strong> <?= date('d/m/Y', strtotime($advisor['AC_NGAYBATDAU'])) ?>
                                            </p>
                                            
                                            <?php if ($advisor['AC_GHICHU']): ?>
                                                <p class="card-text mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-comment"></i> <?= htmlspecialchars($advisor['AC_GHICHU']) ?>
                                                    </small>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="btn-group w-100">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="showAdvisorDetail(<?= htmlspecialchars(json_encode($advisor)) ?>)">
                                                    <i class="fas fa-info-circle"></i> Chi tiết
                                                </button>
                                                <a href="../teacher/class_detail.php?lop_ma=<?= urlencode($advisor['LOP_MA']) ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Xem lớp
                                                </a>
                                                <?php if ($advisor['AC_COHIEULUC']): ?>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmRemove(<?= $advisor['AC_ID'] ?>, '<?= htmlspecialchars($advisor['LOP_TEN']) ?>')">
                                                        <i class="fas fa-times"></i> Huỷ
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <h5>Chưa có cố vấn học tập nào</h5>
                            <p class="text-muted">Nhấn "Gán CVHT mới" để bắt đầu</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    
    <!-- Modal Gán CVHT -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i>
                        Gán Cố vấn học tập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_advisor">
                        
                        <!-- Bộ lọc trong modal -->
                        <div class="modal-filter-section">
                            <h6 class="mb-3 text-primary">
                                <i class="fas fa-filter"></i> Bộ lọc tìm kiếm
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="filter_teacher_dept" class="form-label">
                                            <i class="fas fa-university"></i> Khoa giảng viên
                                        </label>
                                        <select class="form-select" id="filter_teacher_dept">
                                            <option value="">Tất cả khoa</option>
                                            <?php foreach ($departments_list as $dept): ?>
                                                <option value="<?= htmlspecialchars($dept) ?>">
                                                    <?= htmlspecialchars($dept) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="filter_class_dept" class="form-label">
                                            <i class="fas fa-university"></i> Khoa lớp
                                        </label>
                                        <select class="form-select" id="filter_class_dept">
                                            <option value="">Tất cả khoa</option>
                                            <?php foreach ($departments_list as $dept): ?>
                                                <option value="<?= htmlspecialchars($dept) ?>">
                                                    <?= htmlspecialchars($dept) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="filter_class_year" class="form-label">
                                            <i class="fas fa-calendar"></i> Khóa lớp
                                        </label>
                                        <select class="form-select" id="filter_class_year">
                                            <option value="">Tất cả khóa</option>
                                            <?php foreach ($years_list as $year): ?>
                                                <option value="<?= htmlspecialchars($year) ?>">
                                                    <?= htmlspecialchars($year) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetModalFilters">
                                                <i class="fas fa-undo"></i> Xóa bộ lọc
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gv_magv" class="form-label">Giảng viên <span class="text-danger">*</span></label>
                                    <select class="form-select" id="gv_magv" name="gv_magv" required>
                                        <option value="">Chọn giảng viên</option>
                                        <?php 
                                        // Reset pointer
                                        $teachers->data_seek(0);
                                        while ($teacher = $teachers->fetch_assoc()): 
                                        ?>
                                            <option value="<?= htmlspecialchars($teacher['GV_MAGV']) ?>" 
                                                    data-dept="<?= htmlspecialchars($teacher['DV_TENDV'] ?? '') ?>">
                                                <?= htmlspecialchars($teacher['GV_HOTEN']) ?> (<?= htmlspecialchars($teacher['GV_MAGV']) ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lop_ma" class="form-label">Lớp <span class="text-danger">*</span></label>
                                    <select class="form-select" id="lop_ma" name="lop_ma" required>
                                        <option value="">Chọn lớp</option>
                                        <?php 
                                        // Reset pointer
                                        $classes->data_seek(0);
                                        while ($class = $classes->fetch_assoc()): 
                                        ?>
                                            <option value="<?= htmlspecialchars($class['LOP_MA']) ?>"
                                                    data-dept="<?= htmlspecialchars($class['DV_TENDV']) ?>"
                                                    data-year="<?= htmlspecialchars($class['KH_NAM']) ?>">
                                                <?= htmlspecialchars($class['LOP_TEN']) ?> - <?= htmlspecialchars($class['KH_NAM']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ngay_batdau" class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="ngay_batdau" name="ngay_batdau" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ghi_chu" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="ghi_chu" name="ghi_chu" rows="3" 
                                      placeholder="Ghi chú bổ sung (không bắt buộc)"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Nếu lớp đã có cố vấn học tập hiệu lực, CVHT cũ sẽ được tự động huỷ hiệu lực.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Gán CVHT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Chi tiết CVHT -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle"></i>
                        Chi tiết Cố vấn học tập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-user"></i> Thông tin Giảng viên
                            </h6>
                            <div class="mb-3">
                                <strong>Mã giảng viên:</strong>
                                <span id="detail_gv_magv"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Họ và tên:</strong>
                                <span id="detail_gv_hoten"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Khoa:</strong>
                                <span id="detail_gv_khoa"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-graduation-cap"></i> Thông tin Lớp
                            </h6>
                            <div class="mb-3">
                                <strong>Mã lớp:</strong>
                                <span id="detail_lop_ma"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Tên lớp:</strong>
                                <span id="detail_lop_ten"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Khóa:</strong>
                                <span id="detail_lop_khoa"></span>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar"></i> Thông tin Gán CVHT
                            </h6>
                            <div class="mb-3">
                                <strong>Ngày bắt đầu:</strong>
                                <span id="detail_ngay_batdau"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Ngày kết thúc:</strong>
                                <span id="detail_ngay_ketthuc"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Trạng thái:</strong>
                                <span id="detail_trang_thai"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-clock"></i> Thông tin Cập nhật
                            </h6>
                            <div class="mb-3">
                                <strong>Người cập nhật:</strong>
                                <span id="detail_nguoi_capnhat"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Ghi chú:</strong>
                                <span id="detail_ghi_chu"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thống kê nhanh -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-chart-bar"></i> Thống kê nhanh
                            </h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white text-center">
                                        <div class="card-body">
                                            <h5 id="detail_tong_sv">0</h5>
                                            <small>Tổng sinh viên</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white text-center">
                                        <div class="card-body">
                                            <h5 id="detail_sv_co_dt">0</h5>
                                            <small>Sinh viên có đề tài</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white text-center">
                                        <div class="card-body">
                                            <h5 id="detail_dt_da_hoanthanh">0</h5>
                                            <small>Đề tài hoàn thành</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white text-center">
                                        <div class="card-body">
                                            <h5 id="detail_dt_dang_thuchien">0</h5>
                                            <small>Đang thực hiện</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="detail_view_class_btn" class="btn btn-primary">
                        <i class="fas fa-eye"></i> Xem chi tiết lớp
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form ẩn để huỷ CVHT -->
    <form id="removeForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="remove_advisor">
        <input type="hidden" name="ac_id" id="remove_ac_id">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmRemove(acId, className) {
            if (confirm(`Bạn có chắc chắn muốn huỷ gán cố vấn học tập cho lớp "${className}"?`)) {
                document.getElementById('remove_ac_id').value = acId;
                document.getElementById('removeForm').submit();
            }
        }
        
        function showAdvisorDetail(advisorData) {
            // Hiển thị thông tin cơ bản
            document.getElementById('detail_gv_magv').textContent = advisorData.GV_MAGV;
            document.getElementById('detail_gv_hoten').textContent = advisorData.GV_HOGV + ' ' + advisorData.GV_TENGV;
            document.getElementById('detail_gv_khoa').textContent = advisorData.DV_TENDV || 'N/A';
            
            document.getElementById('detail_lop_ma').textContent = advisorData.LOP_MA;
            document.getElementById('detail_lop_ten').textContent = advisorData.LOP_TEN;
            document.getElementById('detail_lop_khoa').textContent = advisorData.KH_NAM || 'N/A';
            
            document.getElementById('detail_ngay_batdau').textContent = advisorData.AC_NGAYBATDAU || 'N/A';
            document.getElementById('detail_ngay_ketthuc').textContent = advisorData.AC_NGAYKETTHUC || 'Chưa kết thúc';
            
            const statusText = advisorData.AC_COHIEULUC ? 
                '<span class="badge bg-success">Đang hiệu lực</span>' : 
                '<span class="badge bg-secondary">Đã hết hiệu lực</span>';
            document.getElementById('detail_trang_thai').innerHTML = statusText;
            
            document.getElementById('detail_nguoi_capnhat').textContent = advisorData.AC_NGUOICAPNHAT || 'N/A';
            document.getElementById('detail_ghi_chu').textContent = advisorData.AC_GHICHU || 'Không có ghi chú';
            
            // Cập nhật link xem chi tiết lớp
            document.getElementById('detail_view_class_btn').href = `../teacher/class_detail.php?lop_ma=${encodeURIComponent(advisorData.LOP_MA)}`;
            
            // Reset thống kê
            document.getElementById('detail_tong_sv').textContent = '0';
            document.getElementById('detail_sv_co_dt').textContent = '0';
            document.getElementById('detail_dt_da_hoanthanh').textContent = '0';
            document.getElementById('detail_dt_dang_thuchien').textContent = '0';
            
            // Lấy thống kê từ server
            fetchAdvisorStatistics(advisorData.LOP_MA);
            
            // Hiển thị modal
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        }
        
        function fetchAdvisorStatistics(lopMa) {
            console.log('Đang lấy thống kê cho lớp:', lopMa);
            
            fetch(`get_advisor_statistics_simple_v2.php?lop_ma=${encodeURIComponent(lopMa)}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Thống kê nhận được:', data);
                    if (data.success) {
                        document.getElementById('detail_tong_sv').textContent = data.statistics.total_students;
                        document.getElementById('detail_sv_co_dt').textContent = data.statistics.students_with_projects;
                        document.getElementById('detail_dt_da_hoanthanh').textContent = data.statistics.completed_projects;
                        document.getElementById('detail_dt_dang_thuchien').textContent = data.statistics.ongoing_projects;
                    } else {
                        console.error('Lỗi khi lấy thống kê:', data.message);
                        // Hiển thị thông báo lỗi trong modal
                        document.getElementById('detail_tong_sv').textContent = 'Lỗi';
                        document.getElementById('detail_sv_co_dt').textContent = 'Lỗi';
                        document.getElementById('detail_dt_da_hoanthanh').textContent = 'Lỗi';
                        document.getElementById('detail_dt_dang_thuchien').textContent = 'Lỗi';
                    }
                })
                .catch(error => {
                    console.error('Lỗi kết nối:', error);
                    // Hiển thị thông báo lỗi trong modal
                    document.getElementById('detail_tong_sv').textContent = 'Lỗi';
                    document.getElementById('detail_sv_co_dt').textContent = 'Lỗi';
                    document.getElementById('detail_dt_da_hoanthanh').textContent = 'Lỗi';
                    document.getElementById('detail_dt_dang_thuchien').textContent = 'Lỗi';
                });
        }
        
        // Auto-submit form khi thay đổi select
        document.addEventListener('DOMContentLoaded', function() {
            const khoaSelect = document.getElementById('khoa');
            const statusSelect = document.getElementById('status');
            const giangVienSelect = document.getElementById('giang_vien');
            
            if (khoaSelect) {
                khoaSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }
            
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }
            
            if (giangVienSelect) {
                giangVienSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }
            
            // Bộ lọc trong modal
            initModalFilters();
        });
        
        // Khởi tạo bộ lọc trong modal
        function initModalFilters() {
            const filterTeacherDept = document.getElementById('filter_teacher_dept');
            const filterClassDept = document.getElementById('filter_class_dept');
            const filterClassYear = document.getElementById('filter_class_year');
            const teacherSelect = document.getElementById('gv_magv');
            const classSelect = document.getElementById('lop_ma');
            const resetBtn = document.getElementById('resetModalFilters');
            
            if (filterTeacherDept) {
                filterTeacherDept.addEventListener('change', function() {
                    filterTeacherOptions(this.value);
                });
            }
            
            if (filterClassDept) {
                filterClassDept.addEventListener('change', function() {
                    filterClassOptions(filterClassDept.value, filterClassYear.value);
                });
            }
            
            if (filterClassYear) {
                filterClassYear.addEventListener('change', function() {
                    filterClassOptions(filterClassDept.value, this.value);
                });
            }
            
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    // Reset tất cả bộ lọc
                    if (filterTeacherDept) filterTeacherDept.value = '';
                    if (filterClassDept) filterClassDept.value = '';
                    if (filterClassYear) filterClassYear.value = '';
                    
                    // Hiển thị tất cả options
                    showAllOptions(teacherSelect);
                    showAllOptions(classSelect);
                });
            }
        }
        
        // Hàm lọc options giảng viên
        function filterTeacherOptions(deptFilter) {
            const teacherSelect = document.getElementById('gv_magv');
            const options = teacherSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    // Giữ option "Chọn giảng viên"
                    option.style.display = '';
                    return;
                }
                
                const optionDept = option.getAttribute('data-dept');
                
                if (!deptFilter || optionDept === deptFilter) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        }
        
        // Hàm lọc options lớp
        function filterClassOptions(deptFilter, yearFilter) {
            const classSelect = document.getElementById('lop_ma');
            const options = classSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    // Giữ option "Chọn lớp"
                    option.style.display = '';
                    return;
                }
                
                const optionDept = option.getAttribute('data-dept');
                const optionYear = option.getAttribute('data-year');
                
                let shouldShow = true;
                
                if (deptFilter && optionDept !== deptFilter) {
                    shouldShow = false;
                }
                
                if (yearFilter && optionYear !== yearFilter) {
                    shouldShow = false;
                }
                
                if (shouldShow) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        }
        
        // Hàm hiển thị tất cả options
        function showAllOptions(select) {
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                option.style.display = '';
            });
        }
    </script>
</body>
</html>
