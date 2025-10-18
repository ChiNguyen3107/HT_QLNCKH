<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\get_extension_detail.php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';

$student_id = $_SESSION['user_id'];
$extension_id = intval($_GET['id'] ?? 0);

if ($extension_id <= 0) {
    echo '<div class="alert alert-danger">ID yêu cầu không hợp lệ</div>';
    exit;
}

// Lấy thông tin chi tiết yêu cầu gia hạn
$sql = "SELECT gh.*, dt.DT_TENDT, dt.DT_TRANGTHAI as DT_TRANGTHAI_HIENTAI,
               CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
               CONCAT(ql.QL_HO, ' ', ql.QL_TEN) as NGUOI_DUYET_HOTEN,
               ql.QL_EMAIL as NGUOI_DUYET_EMAIL,
               DATEDIFF(NOW(), gh.GH_NGAYYEUCAU) as SO_NGAY_CHO,
               DATEDIFF(gh.GH_NGAYHETHAN_MOI, gh.GH_NGAYHETHAN_CU) as SO_NGAY_GIA_HAN
        FROM de_tai_gia_han gh
        INNER JOIN de_tai_nghien_cuu dt ON gh.DT_MADT = dt.DT_MADT
        INNER JOIN sinh_vien sv ON gh.SV_MASV = sv.SV_MASV
        LEFT JOIN quan_ly_nghien_cuu ql ON gh.GH_NGUOIDUYET = ql.QL_MA
        WHERE gh.GH_ID = ? AND gh.SV_MASV = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $extension_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$extension = $result->fetch_assoc();
$stmt->close();

if (!$extension) {
    echo '<div class="alert alert-danger">Không tìm thấy yêu cầu gia hạn hoặc bạn không có quyền xem</div>';
    exit;
}

// Lấy lịch sử thay đổi
$history_sql = "SELECT lsg.*, 
                       CASE 
                           WHEN lsg.LSG_NGUOITHUCHIEN = ? THEN 'Bạn'
                           ELSE CONCAT('Quản lý NCKH (', lsg.LSG_NGUOITHUCHIEN, ')')
                       END as NGUOI_THUCHIEN_DISPLAY
                FROM lich_su_gia_han lsg
                WHERE lsg.GH_ID = ?
                ORDER BY lsg.LSG_NGAYTHUCHIEN ASC";

$stmt = $conn->prepare($history_sql);
$stmt->bind_param("si", $student_id, $extension_id);
$stmt->execute();
$history_result = $stmt->get_result();
$history = [];
while ($row = $history_result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();
?>

<div class="row">
    <div class="col-md-8">
        <!-- Thông tin yêu cầu -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Thông tin yêu cầu gia hạn
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Mã đề tài:</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($extension['DT_MADT']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Trạng thái:</strong><br>
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
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Tên đề tài:</strong><br>
                    <?php echo htmlspecialchars($extension['DT_TENDT']); ?>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Từ ngày:</strong><br>
                        <span class="text-info"><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_CU'])); ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Đến ngày:</strong><br>
                        <span class="text-success"><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_MOI'])); ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Thời gian gia hạn:</strong><br>
                        <span class="badge bg-info"><?php echo $extension['GH_SOTHANGGIAHAN']; ?> tháng (<?php echo $extension['SO_NGAY_GIA_HAN']; ?> ngày)</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Lý do yêu cầu:</strong><br>
                    <div class="border rounded p-2 bg-light">
                        <?php echo nl2br(htmlspecialchars($extension['GH_LYDOYEUCAU'])); ?>
                    </div>
                </div>
                
                <?php if ($extension['GH_FILE_DINKEM']): ?>
                <div class="mb-3">
                    <strong>File đính kèm:</strong><br>
                    <a href="../../<?php echo htmlspecialchars($extension['GH_FILE_DINKEM']); ?>" 
                       target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download me-1"></i>Tải xuống
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Ngày gửi yêu cầu:</strong><br>
                        <?php echo date('d/m/Y H:i:s', strtotime($extension['GH_NGAYYEUCAU'])); ?>
                        <small class="text-muted">(<?php echo $extension['SO_NGAY_CHO']; ?> ngày trước)</small>
                    </div>
                    <?php if ($extension['GH_NGAYDUYET']): ?>
                    <div class="col-md-6">
                        <strong>Ngày xử lý:</strong><br>
                        <?php echo date('d/m/Y H:i:s', strtotime($extension['GH_NGAYDUYET'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Phản hồi từ quản lý (nếu có) -->
        <?php if ($extension['GH_TRANGTHAI'] == 'Từ chối' && $extension['GH_LYDOTUCHO']): ?>
        <div class="card mb-3">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-times-circle me-2"></i>Lý do từ chối
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-danger mb-2">
                    <?php echo nl2br(htmlspecialchars($extension['GH_LYDOTUCHO'])); ?>
                </div>
                <?php if ($extension['NGUOI_DUYET_HOTEN']): ?>
                <small class="text-muted">
                    Từ chối bởi: <strong><?php echo htmlspecialchars($extension['NGUOI_DUYET_HOTEN']); ?></strong>
                    vào <?php echo date('d/m/Y H:i', strtotime($extension['GH_NGAYDUYET'])); ?>
                </small>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($extension['GH_TRANGTHAI'] == 'Đã duyệt' && $extension['GH_GHICHU']): ?>
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-check-circle me-2"></i>Ghi chú từ quản lý
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-success mb-2">
                    <?php echo nl2br(htmlspecialchars($extension['GH_GHICHU'])); ?>
                </div>
                <?php if ($extension['NGUOI_DUYET_HOTEN']): ?>
                <small class="text-muted">
                    Duyệt bởi: <strong><?php echo htmlspecialchars($extension['NGUOI_DUYET_HOTEN']); ?></strong>
                    vào <?php echo date('d/m/Y H:i', strtotime($extension['GH_NGAYDUYET'])); ?>
                </small>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Thống kê nhanh -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Thông tin nhanh
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Trạng thái đề tài:</span>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($extension['DT_TRANGTHAI_HIENTAI']); ?></span>
                </div>
                
                <?php if ($extension['GH_TRANGTHAI'] == 'Chờ duyệt'): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Thời gian chờ:</span>
                    <span class="text-warning"><strong><?php echo $extension['SO_NGAY_CHO']; ?> ngày</strong></span>
                </div>
                
                <?php if ($extension['SO_NGAY_CHO'] > 7): ?>
                <div class="alert alert-warning alert-sm">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <small>Yêu cầu đã chờ quá 7 ngày</small>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($extension['NGUOI_DUYET_HOTEN']): ?>
                <div class="mb-2">
                    <strong>Người xử lý:</strong><br>
                    <small><?php echo htmlspecialchars($extension['NGUOI_DUYET_HOTEN']); ?></small>
                    <?php if ($extension['NGUOI_DUYET_EMAIL']): ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars($extension['NGUOI_DUYET_EMAIL']); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lịch sử thay đổi -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Lịch sử thay đổi
                </h6>
            </div>
            <div class="card-body">
                <?php if (count($history) > 0): ?>
                <div class="timeline">
                    <?php foreach ($history as $item): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($item['LSG_HANHDONG']); ?></strong>
                                <?php if ($item['LSG_TRANGTHAI_CU'] && $item['LSG_TRANGTHAI_MOI']): ?>
                                <br><small class="text-muted">
                                    <?php echo $item['LSG_TRANGTHAI_CU']; ?> → <?php echo $item['LSG_TRANGTHAI_MOI']; ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo date('d/m H:i', strtotime($item['LSG_NGAYTHUCHIEN'])); ?></small>
                        </div>
                        <?php if ($item['LSG_NOIDUNG']): ?>
                        <div class="mt-1">
                            <small><?php echo htmlspecialchars($item['LSG_NOIDUNG']); ?></small>
                        </div>
                        <?php endif; ?>
                        <div class="mt-1">
                            <small class="text-muted">bởi <?php echo htmlspecialchars($item['NGUOI_THUCHIEN_DISPLAY']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">Chưa có lịch sử thay đổi</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-item {
    border-left: 3px solid #667eea;
    padding-left: 1rem;
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

.alert-sm {
    padding: 0.5rem;
    font-size: 0.875rem;
}
</style>
