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

// Lấy thông tin lớp
$class_info_sql = "SELECT * FROM v_class_overview WHERE LOP_MA = ? AND CVHT_MAGV = ?";
$stmt = $conn->prepare($class_info_sql);
$stmt->bind_param("ss", $lop_ma, $teacher_id);
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
$limit = min(50, max(1, intval($_GET['limit'] ?? 20)));

// Truy vấn danh sách sinh viên
$sql = "SELECT 
            sps.SV_MASV,
            sps.SV_HOSV,
            sps.SV_TENSV,
            sps.LOP_MA,
            sps.LOP_TEN,
            sps.DT_MADT,
            sps.DT_TENDT,
            sps.DT_TRANGTHAI,
            sps.TRANGTHAI_PHANLOAI,
            sps.TIENDO_PHANTRAM,
            sps.GV_HOTEN,
            sps.CTTG_VAITRO,
            sps.CTTG_NGAYTHAMGIA,
            sps.DT_NGAYTAO
        FROM v_student_project_summary sps
        JOIN advisor_class ac ON sps.LOP_MA = ac.LOP_MA
        WHERE sps.LOP_MA = ? AND ac.GV_MAGV = ? AND ac.AC_COHIEULUC = 1";

$params = [$lop_ma, $teacher_id];
if ($status) {
    $sql .= " AND sps.TRANGTHAI_PHANLOAI = ?";
    $params[] = $status;
}
if ($search) {
    $sql .= " AND (sps.SV_MASV LIKE ? OR CONCAT(sps.SV_HOSV, ' ', sps.SV_TENSV) LIKE ? OR sps.DT_TENDT LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY CONCAT(sps.SV_HOSV, ' ', sps.SV_TENSV) LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = ($page - 1) * $limit;

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

// Tính thống kê
$stats = ['chua_tham_gia' => 0, 'dang_tham_gia' => 0, 'da_hoan_thanh' => 0, 'bi_tu_choi' => 0];
$students_list = [];
while ($student = $students->fetch_assoc()) {
    $students_list[] = $student;
    switch ($student['TRANGTHAI_PHANLOAI']) {
        case 'Chưa tham gia': $stats['chua_tham_gia']++; break;
        case 'Đang tham gia': $stats['dang_tham_gia']++; break;
        case 'Đã hoàn thành': $stats['da_hoan_thanh']++; break;
        case 'Bị từ chối/Tạm dừng': $stats['bi_tu_choi']++; break;
    }
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
        .main-content { margin-left: 250px; padding: 20px; }
        .class-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; }
        .stats-card { border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .filter-section { background-color: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .status-badge { font-size: 0.8em; padding: 4px 8px; border-radius: 12px; }
    </style>
</head>
<body>
    <?php include '../../include/teacher_sidebar.php'; ?>
    
    <div class="main-content">
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
                        <div class="display-4"><?= $class_info['TONG_SV'] ?></div>
                        <div>sinh viên</div>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark">
                                <?= $class_info['TY_LE_THAM_GIA_PHANTRAM'] ?>% tham gia
                            </span>
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
                            <h4 class="text-danger"><?= $stats['chua_tham_gia'] ?></h4>
                            <p class="text-muted mb-0">Chưa tham gia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-warning bg-opacity-10 border-warning">
                        <div class="text-center">
                            <i class="fas fa-user-clock fa-2x text-warning mb-2"></i>
                            <h4 class="text-warning"><?= $stats['dang_tham_gia'] ?></h4>
                            <p class="text-muted mb-0">Đang tham gia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-success bg-opacity-10 border-success">
                        <div class="text-center">
                            <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                            <h4 class="text-success"><?= $stats['da_hoan_thanh'] ?></h4>
                            <p class="text-muted mb-0">Đã hoàn thành</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-secondary bg-opacity-10 border-secondary">
                        <div class="text-center">
                            <i class="fas fa-user-slash fa-2x text-secondary mb-2"></i>
                            <h4 class="text-secondary"><?= $stats['bi_tu_choi'] ?></h4>
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
            
            <!-- Bảng sinh viên -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users"></i>
                        Danh sách sinh viên (<?= count($students_list) ?> sinh viên)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($students_list)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>Không tìm thấy sinh viên</h5>
                            <p class="text-muted">Thử thay đổi bộ lọc tìm kiếm</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>MSSV</th>
                                        <th>Họ tên</th>
                                        <th>Trạng thái</th>
                                        <th>Đề tài</th>
                                        <th>GV hướng dẫn</th>
                                        <th>Tiến độ</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_list as $student): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($student['SV_MASV']) ?></strong></td>
                                            <td><?= htmlspecialchars($student['SV_HOSV'] . ' ' . $student['SV_TENSV']) ?></td>
                                            <td>
                                                <span class="status-badge <?= getStatusBadgeClass($student['TRANGTHAI_PHANLOAI']) ?>">
                                                    <?= htmlspecialchars($student['TRANGTHAI_PHANLOAI']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($student['DT_TENDT']): ?>
                                                    <span title="<?= htmlspecialchars($student['DT_TENDT']) ?>">
                                                        <?= htmlspecialchars(substr($student['DT_TENDT'], 0, 40)) ?>
                                                        <?= strlen($student['DT_TENDT']) > 40 ? '...' : '' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa có đề tài</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $student['GV_HOTEN'] ? htmlspecialchars($student['GV_HOTEN']) : '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td>
                                                <?php if ($student['TIENDO_PHANTRAM'] > 0): ?>
                                                    <div class="progress" style="width: 80px;">
                                                        <div class="progress-bar bg-<?= getProgressColor($student['TIENDO_PHANTRAM']) ?>" 
                                                             style="width: <?= $student['TIENDO_PHANTRAM'] ?>%"></div>
                                                    </div>
                                                    <small><?= $student['TIENDO_PHANTRAM'] ?>%</small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['DT_MADT']): ?>
                                                    <a href="../project/view_project.php?dt_madt=<?= urlencode($student['DT_MADT']) ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function exportData() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = 'class_export.php?' + params.toString();
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
?>
