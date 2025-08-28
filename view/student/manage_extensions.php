<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\manage_extensions.php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';

// Lấy thông tin sinh viên
$student_id = $_SESSION['user_id'];

// Lấy danh sách đề tài của sinh viên có thể gia hạn (đang thực hiện)
$projects_sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, dt.DT_TRE_TIENDO, dt.DT_SO_LAN_GIA_HAN,
                        hd.HD_NGAYKT as NGAY_KET_THUC_HIENTAI,
                        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                        cttg.CTTG_VAITRO,
                        DATEDIFF(hd.HD_NGAYKT, CURDATE()) as SO_NGAY_CON_LAI
                 FROM de_tai_nghien_cuu dt
                 INNER JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                 INNER JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                 LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                 WHERE cttg.SV_MASV = ? 
                 AND dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Chờ duyệt')
                 ORDER BY hd.HD_NGAYKT ASC";

$stmt = $conn->prepare($projects_sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$projects_result = $stmt->get_result();
$projects = [];
while ($row = $projects_result->fetch_assoc()) {
    $projects[] = $row;
}
$stmt->close();

// Lấy danh sách yêu cầu gia hạn của sinh viên
$extensions_sql = "SELECT gh.*, dt.DT_TENDT, dt.DT_TRANGTHAI,
                          CONCAT(ql.QL_HO, ' ', ql.QL_TEN) as NGUOI_DUYET_HOTEN,
                          DATEDIFF(NOW(), gh.GH_NGAYYEUCAU) as SO_NGAY_CHO
                   FROM de_tai_gia_han gh
                   INNER JOIN de_tai_nghien_cuu dt ON gh.DT_MADT = dt.DT_MADT
                   LEFT JOIN quan_ly_nghien_cuu ql ON gh.GH_NGUOIDUYET = ql.QL_MA
                   WHERE gh.SV_MASV = ?
                   ORDER BY gh.GH_NGAYYEUCAU DESC";

$stmt = $conn->prepare($extensions_sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$extensions_result = $stmt->get_result();
$extensions = [];
while ($row = $extensions_result->fetch_assoc()) {
    $extensions[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý gia hạn đề tài - Hệ thống QLNCKH</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 1rem 0.75rem;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .deadline-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .deadline-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .btn-extension {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .btn-extension:hover {
            transform: scale(1.05);
            color: white;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .extension-history {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .timeline-item {
            border-left: 3px solid #667eea;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #667eea;
        }
        
        .project-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s;
        }
        
        .project-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }
        
        .project-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-in-progress {
            background-color: #cce5ff;
            color: #0056b3;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../../include/student_sidebar.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">
                                <i class="fas fa-clock me-2"></i>Quản lý gia hạn đề tài
                            </h1>
                            <p class="mb-0 opacity-75">Yêu cầu gia hạn thời gian thực hiện đề tài nghiên cứu</p>
                        </div>
                        <div class="text-end">
                            <small>Sinh viên: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></small>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-primary me-3">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1"><?php echo count($projects); ?></h5>
                                    <p class="card-text text-muted mb-0">Đề tài có thể gia hạn</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-warning me-3">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php echo count(array_filter($extensions, function($ext) { return $ext['GH_TRANGTHAI'] == 'Chờ duyệt'; })); ?>
                                    </h5>
                                    <p class="card-text text-muted mb-0">Yêu cầu chờ duyệt</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-success me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php echo count(array_filter($extensions, function($ext) { return $ext['GH_TRANGTHAI'] == 'Đã duyệt'; })); ?>
                                    </h5>
                                    <p class="card-text text-muted mb-0">Đã được duyệt</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-danger me-3">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php echo count(array_filter($extensions, function($ext) { return $ext['GH_TRANGTHAI'] == 'Từ chối'; })); ?>
                                    </h5>
                                    <p class="card-text text-muted mb-0">Bị từ chối</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đề tài có thể gia hạn -->
                <div class="card table-card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-alt me-2"></i>Đề tài có thể yêu cầu gia hạn
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($projects) > 0): ?>
                            <div class="row">
                                <?php foreach ($projects as $project): ?>
                                    <div class="col-lg-6 mb-3">
                                        <div class="project-card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($project['DT_MADT']); ?></h6>
                                                <span class="project-status <?php 
                                                    echo $project['DT_TRANGTHAI'] == 'Đang thực hiện' ? 'status-in-progress' : 'status-pending'; 
                                                ?>">
                                                    <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                                </span>
                                            </div>
                                            <h6 class="mb-2"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h6>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-user-tie me-1"></i>
                                                <?php echo htmlspecialchars($project['GV_HOTEN']); ?>
                                            </p>
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <small class="text-muted">Vai trò:</small><br>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($project['CTTG_VAITRO']); ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Hết hạn:</small><br>
                                                    <strong class="<?php echo $project['SO_NGAY_CON_LAI'] < 30 ? 'text-danger' : 'text-success'; ?>">
                                                        <?php echo date('d/m/Y', strtotime($project['NGAY_KET_THUC_HIENTAI'])); ?>
                                                    </strong>
                                                </div>
                                            </div>
                                            
                                            <?php if ($project['SO_NGAY_CON_LAI'] < 30): ?>
                                                <div class="deadline-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    <strong>Cảnh báo:</strong> Còn <?php echo $project['SO_NGAY_CON_LAI']; ?> ngày đến hạn!
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($project['DT_TRE_TIENDO']): ?>
                                                <div class="mb-2">
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Trễ tiến độ (Đã gia hạn <?php echo $project['DT_SO_LAN_GIA_HAN']; ?> lần)
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    Còn lại: <strong><?php echo $project['SO_NGAY_CON_LAI']; ?> ngày</strong>
                                                </small>
                                                <button class="btn btn-extension btn-sm" 
                                                        onclick="requestExtension('<?php echo $project['DT_MADT']; ?>', '<?php echo htmlspecialchars($project['DT_TENDT']); ?>', '<?php echo $project['NGAY_KET_THUC_HIENTAI']; ?>')">
                                                    <i class="fas fa-plus me-1"></i>Yêu cầu gia hạn
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Không có đề tài nào có thể gia hạn</h5>
                                <p class="text-muted">Chỉ có thể gia hạn các đề tài đang thực hiện hoặc chờ duyệt</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lịch sử yêu cầu gia hạn -->
                <div class="card table-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Lịch sử yêu cầu gia hạn
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($extensions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Đề tài</th>
                                            <th>Từ ngày</th>
                                            <th>Đến ngày</th>
                                            <th>Số tháng</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày yêu cầu</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($extensions as $extension): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($extension['DT_MADT']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($extension['DT_TENDT']); ?></small>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_CU'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_MOI'])); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $extension['GH_SOTHANGGIAHAN']; ?> tháng</span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($extension['GH_TRANGTHAI']) {
                                                        case 'Chờ duyệt':
                                                            $status_class = 'bg-warning';
                                                            break;
                                                        case 'Đã duyệt':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'Từ chối':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                        case 'Hủy':
                                                            $status_class = 'bg-secondary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($extension['GH_TRANGTHAI']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y H:i', strtotime($extension['GH_NGAYYEUCAU'])); ?><br>
                                                    <small class="text-muted"><?php echo $extension['SO_NGAY_CHO']; ?> ngày trước</small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewExtensionDetail(<?php echo $extension['GH_ID']; ?>)">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </button>
                                                    <?php if ($extension['GH_TRANGTHAI'] == 'Chờ duyệt'): ?>
                                                        <button class="btn btn-sm btn-outline-danger ms-1" 
                                                                onclick="cancelExtension(<?php echo $extension['GH_ID']; ?>)">
                                                            <i class="fas fa-times"></i> Hủy
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có yêu cầu gia hạn nào</h5>
                                <p class="text-muted">Lịch sử các yêu cầu gia hạn sẽ hiển thị ở đây</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal yêu cầu gia hạn -->
    <div class="modal fade" id="extensionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clock me-2"></i>Yêu cầu gia hạn đề tài
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="extensionForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Mã đề tài</label>
                                    <input type="text" class="form-control" id="projectCode" readonly>
                                    <input type="hidden" id="projectId" name="project_id">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ngày hết hạn hiện tại</label>
                                    <input type="text" class="form-control" id="currentDeadline" readonly>
                                    <input type="hidden" id="currentDeadlineValue" name="current_deadline">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tên đề tài</label>
                            <input type="text" class="form-control" id="projectName" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Số tháng gia hạn <span class="text-danger">*</span></label>
                                    <select class="form-control" id="extensionMonths" name="extension_months" required>
                                        <option value="">Chọn số tháng</option>
                                        <option value="1">1 tháng</option>
                                        <option value="2">2 tháng</option>
                                        <option value="3">3 tháng</option>
                                        <option value="4">4 tháng</option>
                                        <option value="6">6 tháng</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ngày hết hạn mới</label>
                                    <input type="text" class="form-control" id="newDeadline" readonly>
                                    <input type="hidden" id="newDeadlineValue" name="new_deadline">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Lý do yêu cầu gia hạn <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="extensionReason" name="extension_reason" rows="4" 
                                      placeholder="Vui lòng mô tả chi tiết lý do cần gia hạn..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">File đính kèm (tùy chọn)</label>
                            <input type="file" class="form-control" id="attachmentFile" name="attachment_file" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">
                                Chấp nhận: PDF, Word, hình ảnh. Tối đa 5MB.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Gửi yêu cầu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal chi tiết gia hạn -->
    <div class="modal fade" id="extensionDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Chi tiết yêu cầu gia hạn
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="extensionDetailContent">
                    <!-- Nội dung sẽ được load bằng AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function requestExtension(projectId, projectName, currentDeadline) {
            $('#projectId').val(projectId);
            $('#projectCode').val(projectId);
            $('#projectName').val(projectName);
            $('#currentDeadline').val(formatDate(currentDeadline));
            $('#currentDeadlineValue').val(currentDeadline);
            $('#extensionModal').modal('show');
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }
        
        function addMonths(date, months) {
            const result = new Date(date);
            result.setMonth(result.getMonth() + parseInt(months));
            return result;
        }
        
        $('#extensionMonths').change(function() {
            const months = $(this).val();
            if (months) {
                const currentDate = new Date($('#currentDeadlineValue').val());
                const newDate = addMonths(currentDate, months);
                $('#newDeadline').val(formatDate(newDate));
                $('#newDeadlineValue').val(newDate.toISOString().split('T')[0]);
            } else {
                $('#newDeadline').val('');
                $('#newDeadlineValue').val('');
            }
        });
        
        $('#extensionForm').submit(function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: 'process_extension_request.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Yêu cầu gia hạn đã được gửi thành công!');
                        $('#extensionModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Có lỗi xảy ra: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    alert('Có lỗi xảy ra khi gửi yêu cầu!\nChi tiết: ' + xhr.responseText + '\nStatus: ' + status);
                }
            });
        });
        
        function viewExtensionDetail(extensionId) {
            $.ajax({
                url: 'get_extension_detail.php',
                type: 'GET',
                data: { id: extensionId },
                success: function(response) {
                    $('#extensionDetailContent').html(response);
                    $('#extensionDetailModal').modal('show');
                },
                error: function() {
                    alert('Không thể tải chi tiết yêu cầu!');
                }
            });
        }
        
        function cancelExtension(extensionId) {
            if (confirm('Bạn có chắc chắn muốn hủy yêu cầu gia hạn này?')) {
                $.ajax({
                    url: 'cancel_extension.php',
                    type: 'POST',
                    data: { id: extensionId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Đã hủy yêu cầu gia hạn!');
                            location.reload();
                        } else {
                            alert('Có lỗi xảy ra: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra khi hủy yêu cầu!');
                    }
                });
            }
        }
        
        // Auto-refresh để cập nhật trạng thái
        setInterval(function() {
            // Refresh page every 5 minutes if there are pending requests
            const pendingCount = <?php echo count(array_filter($extensions, function($ext) { return $ext['GH_TRANGTHAI'] == 'Chờ duyệt'; })); ?>;
            if (pendingCount > 0) {
                location.reload();
            }
        }, 300000); // 5 minutes
    </script>
</body>
</html>
