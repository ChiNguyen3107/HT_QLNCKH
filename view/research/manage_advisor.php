<?php
// Kiểm tra session và vai trò research manager
include '../../include/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != ROLE_RESEARCH_MANAGER) {
    header("Location: /NLNganh/login.php");
    exit;
}

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
    $research_manager = $_SESSION['user_id'];
    $stmt->bind_param("sssss", $gv_magv, $lop_ma, $ngay_batdau, $ghi_chu, $research_manager);
    
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
    $research_manager = $_SESSION['user_id'];
    $stmt->bind_param("si", $research_manager, $ac_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Huỷ gán cố vấn học tập thành công!';
    } else {
        $_SESSION['error'] = 'Có lỗi xảy ra khi huỷ gán: ' . $stmt->error;
    }
    $stmt->close();
}

// Lấy danh sách CVHT hiện tại
$current_advisors_sql = "SELECT 
    ac.AC_ID,
    ac.GV_MAGV,
    gv.GV_HOGV,
    gv.GV_TENGV,
    ac.LOP_MA,
    l.LOP_TEN,
    l.KH_NAM,
    k.DV_TENDV,
    ac.AC_NGAYBATDAU,
    ac.AC_NGAYKETTHUC,
    ac.AC_COHIEULUC,
    ac.AC_GHICHU,
    ac.AC_NGUOICAPNHAT
FROM advisor_class ac
LEFT JOIN giang_vien gv ON ac.GV_MAGV = gv.GV_MAGV
LEFT JOIN lop l ON ac.LOP_MA = l.LOP_MA
LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
ORDER BY ac.AC_NGAYBATDAU DESC";

$current_advisors = $conn->query($current_advisors_sql);

// Lấy danh sách giảng viên và lớp
$teachers_result = $conn->query("SELECT GV_MAGV, CONCAT(GV_HOGV, ' ', GV_TENGV) as GV_HOTEN FROM giang_vien ORDER BY GV_HOGV, GV_TENGV");
$classes_result = $conn->query("SELECT LOP_MA, LOP_TEN, KH_NAM FROM lop ORDER BY LOP_TEN");
?>

<?php
$additional_css = '<link href="/NLNganh/assets/css/research/manage-advisor.css" rel="stylesheet">';
include '../../include/research_header.php';
?>

<!-- Sidebar đã được include trong header -->

<!-- Begin Page Content -->
<div class="container-fluid">
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
        
        <!-- Danh sách CVHT -->
        <div class="row">
            <?php 
            if ($current_advisors->num_rows > 0):
                while ($advisor = $current_advisors->fetch_assoc()):
                    $status_class = $advisor['AC_COHIEULUC'] ? 'active-advisor' : 'inactive-advisor';
                    $status_badge = $advisor['AC_COHIEULUC'] ? 
                        '<span class="badge bg-success">Đang hiệu lực</span>' : 
                        '<span class="badge bg-secondary">Đã hết hiệu lực</span>';
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card advisor-card <?= $status_class ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-user-tie text-primary"></i>
                                    <?= htmlspecialchars($advisor['GV_HOGV'] . ' ' . $advisor['GV_TENGV']) ?>
                                </h6>
                                <small class="text-muted"><?= htmlspecialchars($advisor['GV_MAGV']) ?></small>
                            </div>
                            <?= $status_badge ?>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Lớp:</small><br>
                            <strong><?= htmlspecialchars($advisor['LOP_TEN']) ?></strong>
                            <small class="text-muted d-block"><?= htmlspecialchars($advisor['LOP_MA']) ?></small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-info flex-fill" 
                                    onclick="showAdvisorDetail(<?= htmlspecialchars(json_encode($advisor)) ?>)">
                                <i class="fas fa-info-circle"></i> Chi tiết
                            </button>
                            
                            <?php if ($advisor['AC_COHIEULUC']): ?>
                            <form method="POST" class="d-inline flex-fill" onsubmit="return confirm('Bạn có chắc muốn huỷ gán CVHT này?')">
                                <input type="hidden" name="action" value="remove_advisor">
                                <input type="hidden" name="ac_id" value="<?= $advisor['AC_ID'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                    <i class="fas fa-times"></i> Huỷ gán
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            else:
            ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có gán CVHT nào</h5>
                    <p class="text-muted">Hãy bắt đầu bằng cách gán cố vấn học tập cho lớp học</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                        <i class="fas fa-plus"></i> Gán CVHT đầu tiên
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Gán CVHT -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus text-primary"></i>
                        Gán Cố vấn học tập
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_advisor">
                        
                        <div class="mb-3">
                            <label for="gv_magv" class="form-label">Chọn Giảng viên <span class="text-danger">*</span></label>
                            <select class="form-select" id="gv_magv" name="gv_magv" required>
                                <option value="">Chọn giảng viên...</option>
                                <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                                    <option value="<?= $teacher['GV_MAGV'] ?>">
                                        <?= htmlspecialchars($teacher['GV_HOTEN']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="lop_ma" class="form-label">Chọn Lớp <span class="text-danger">*</span></label>
                            <select class="form-select" id="lop_ma" name="lop_ma" required>
                                <option value="">Chọn lớp...</option>
                                <?php while ($class = $classes_result->fetch_assoc()): ?>
                                    <option value="<?= $class['LOP_MA'] ?>">
                                        <?= htmlspecialchars($class['LOP_TEN']) ?> (<?= htmlspecialchars($class['KH_NAM'] ?? 'N/A') ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ngay_batdau" class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="ngay_batdau" name="ngay_batdau" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ghi_chu" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="ghi_chu" name="ghi_chu" rows="3" 
                                      placeholder="Ghi chú bổ sung về việc gán CVHT..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Gán CVHT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAdvisorDetail(advisorData) {
            alert('Chi tiết CVHT:\n' +
                  'Giảng viên: ' + advisorData.GV_HOGV + ' ' + advisorData.GV_TENGV + '\n' +
                  'Lớp: ' + advisorData.LOP_TEN + '\n' +
                  'Trạng thái: ' + (advisorData.AC_COHIEULUC ? 'Đang hiệu lực' : 'Đã hết hiệu lực'));
        }
    </script>
</body>
</html>
