<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\get_extension_detail_admin.php
include '../../include/session.php';
checkResearchManagerRole();
include '../../include/connect.php';

$extension_id = intval($_GET['id'] ?? 0);

if ($extension_id <= 0) {
    echo '<div class="alert alert-danger">ID yêu cầu không hợp lệ</div>';
    exit;
}

// Lấy thông tin chi tiết yêu cầu gia hạn
$sql = "SELECT gh.*, dt.DT_TENDT, dt.DT_TRANGTHAI as DT_TRANGTHAI_HIENTAI,
               dt.DT_TRE_TIENDO, dt.DT_SO_LAN_GIA_HAN, dt.DT_MOTA,
               CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
               sv.SV_EMAIL, sv.SV_SDT, sv.SV_MASV,
               lop.LOP_TEN, lop.KH_NAM,
               dv.DV_TENDV as KHOA_TEN,
               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
               gv.GV_EMAIL as GV_EMAIL, gv.GV_SDT as GV_SDT,
               CONCAT(ql.QL_HO, ' ', ql.QL_TEN) as NGUOI_DUYET_HOTEN,
               ql.QL_EMAIL as NGUOI_DUYET_EMAIL,
               hd.HD_NGAYBD as HD_NGAY_BATDAU,
               hd.HD_NGAYKT as HD_NGAY_KETTHUC_HIENTAI,
               hd.HD_TONGKINHPHI,
               DATEDIFF(NOW(), gh.GH_NGAYYEUCAU) as SO_NGAY_CHO,
               DATEDIFF(gh.GH_NGAYHETHAN_MOI, gh.GH_NGAYHETHAN_CU) as SO_NGAY_GIA_HAN
        FROM de_tai_gia_han gh
        INNER JOIN de_tai_nghien_cuu dt ON gh.DT_MADT = dt.DT_MADT
        INNER JOIN sinh_vien sv ON gh.SV_MASV = sv.SV_MASV
        INNER JOIN lop ON sv.LOP_MA = lop.LOP_MA
        INNER JOIN khoa dv ON lop.DV_MADV = dv.DV_MADV
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
        LEFT JOIN quan_ly_nghien_cuu ql ON gh.GH_NGUOIDUYET = ql.QL_MA
        WHERE gh.GH_ID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $extension_id);
$stmt->execute();
$result = $stmt->get_result();
$extension = $result->fetch_assoc();
$stmt->close();

if (!$extension) {
    echo '<div class="alert alert-danger">Không tìm thấy yêu cầu gia hạn</div>';
    exit;
}

// Lấy lịch sử thay đổi
$history_sql = "SELECT lsg.*, 
                       CASE 
                           WHEN lsg.LSG_NGUOITHUCHIEN = ? THEN CONCAT('Sinh viên (', lsg.LSG_NGUOITHUCHIEN, ')')
                           ELSE CONCAT('Quản lý NCKH (', lsg.LSG_NGUOITHUCHIEN, ')')
                       END as NGUOI_THUCHIEN_DISPLAY
                FROM lich_su_gia_han lsg
                WHERE lsg.GH_ID = ?
                ORDER BY lsg.LSG_NGAYTHUCHIEN ASC";

$stmt = $conn->prepare($history_sql);
$stmt->bind_param("si", $extension['SV_MASV'], $extension_id);
$stmt->execute();
$history_result = $stmt->get_result();
$history = [];
while ($row = $history_result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

// Lấy thống kê đề tài của sinh viên này
$student_stats_sql = "SELECT 
                        COUNT(*) as TONG_DETAI,
                        COUNT(CASE WHEN dt.DT_TRANGTHAI = 'Đang thực hiện' THEN 1 END) as DANG_THUCHIEN,
                        COUNT(CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN 1 END) as HOAN_THANH,
                        COUNT(CASE WHEN dt.DT_TRE_TIENDO = 1 THEN 1 END) as TRE_TIENDO
                      FROM de_tai_nghien_cuu dt
                      INNER JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                      WHERE cttg.SV_MASV = ?";

$stmt = $conn->prepare($student_stats_sql);
$stmt->bind_param("s", $extension['SV_MASV']);
$stmt->execute();
$student_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="row">
    <div class="col-md-8">
        <!-- Thông tin đề tài -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-project-diagram me-2"></i>Thông tin đề tài
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Mã đề tài:</strong><br>
                        <span class="text-primary fs-5"><?php echo htmlspecialchars($extension['DT_MADT']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Trạng thái hiện tại:</strong><br>
                        <span class="badge bg-info fs-6"><?php echo htmlspecialchars($extension['DT_TRANGTHAI_HIENTAI']); ?></span>
                        <?php if ($extension['DT_TRE_TIENDO']): ?>
                            <span class="badge bg-warning ms-1">Trễ tiến độ</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Tên đề tài:</strong><br>
                    <div class="border rounded p-2 bg-light">
                        <?php echo htmlspecialchars($extension['DT_TENDT']); ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Mô tả đề tài:</strong><br>
                    <div class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                        <?php echo nl2br(htmlspecialchars($extension['DT_MOTA'])); ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Giảng viên hướng dẫn:</strong><br>
                        <?php echo htmlspecialchars($extension['GV_HOTEN'] ?? 'Chưa có'); ?>
                        <?php if ($extension['GV_EMAIL']): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($extension['GV_EMAIL']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Thông tin hợp đồng:</strong><br>
                        <?php if ($extension['HD_NGAY_BATDAU']): ?>
                            Từ <?php echo date('d/m/Y', strtotime($extension['HD_NGAY_BATDAU'])); ?> 
                            đến <?php echo date('d/m/Y', strtotime($extension['HD_NGAY_KETTHUC_HIENTAI'])); ?><br>
                            <small class="text-muted">Kinh phí: <?php echo number_format($extension['HD_TONGKINHPHI']); ?> VNĐ</small>
                        <?php else: ?>
                            <span class="text-muted">Chưa có hợp đồng</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin yêu cầu gia hạn -->
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Chi tiết yêu cầu gia hạn
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Từ ngày:</strong><br>
                        <span class="text-info fs-6"><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_CU'])); ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Đến ngày:</strong><br>
                        <span class="text-success fs-6"><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_MOI'])); ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Thời gian gia hạn:</strong><br>
                        <span class="badge bg-info fs-6"><?php echo $extension['GH_SOTHANGGIAHAN']; ?> tháng</span>
                        <small class="text-muted d-block">(<?php echo $extension['SO_NGAY_GIA_HAN']; ?> ngày)</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Lý do yêu cầu gia hạn:</strong>
                    <div class="border rounded p-3 bg-light mt-2">
                        <?php echo nl2br(htmlspecialchars($extension['GH_LYDOYEUCAU'])); ?>
                    </div>
                </div>
                
                <?php if ($extension['GH_FILE_DINKEM']): ?>
                <div class="mb-3">
                    <strong>File đính kèm:</strong><br>
                    <a href="../../<?php echo htmlspecialchars($extension['GH_FILE_DINKEM']); ?>" 
                       target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-download me-1"></i>Tải xuống file hỗ trợ
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Ngày gửi yêu cầu:</strong><br>
                        <?php echo date('d/m/Y H:i:s', strtotime($extension['GH_NGAYYEUCAU'])); ?>
                        <br><small class="text-muted"><?php echo $extension['SO_NGAY_CHO']; ?> ngày trước</small>
                    </div>
                    <div class="col-md-6">
                        <strong>Trạng thái yêu cầu:</strong><br>
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
                        <span class="badge <?php echo $status_class; ?> fs-6">
                            <?php echo htmlspecialchars($extension['GH_TRANGTHAI']); ?>
                        </span>
                        <?php if ($extension['GH_TRANGTHAI'] === 'Chờ duyệt' && $extension['SO_NGAY_CHO'] > 7): ?>
                            <span class="badge bg-danger ms-1">Quá hạn xử lý</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Phản hồi từ quản lý (nếu có) -->
        <?php if ($extension['GH_TRANGTHAI'] != 'Chờ duyệt' && $extension['GH_NGAYDUYET']): ?>
        <div class="card mb-3">
            <div class="card-header <?php echo $extension['GH_TRANGTHAI'] === 'Đã duyệt' ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                <h6 class="card-title mb-0">
                    <i class="fas fa-<?php echo $extension['GH_TRANGTHAI'] === 'Đã duyệt' ? 'check' : 'times'; ?>-circle me-2"></i>
                    Kết quả xử lý
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6">
                        <strong>Người xử lý:</strong><br>
                        <?php echo htmlspecialchars($extension['NGUOI_DUYET_HOTEN'] ?? 'N/A'); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Ngày xử lý:</strong><br>
                        <?php echo date('d/m/Y H:i:s', strtotime($extension['GH_NGAYDUYET'])); ?>
                    </div>
                </div>
                
                <?php if ($extension['GH_LYDOTUCHO']): ?>
                <div class="alert alert-danger">
                    <strong>Lý do từ chối:</strong><br>
                    <?php echo nl2br(htmlspecialchars($extension['GH_LYDOTUCHO'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($extension['GH_GHICHU']): ?>
                <div class="alert alert-info">
                    <strong>Ghi chú:</strong><br>
                    <?php echo nl2br(htmlspecialchars($extension['GH_GHICHU'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Thông tin sinh viên -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-user-graduate me-2"></i>Thông tin sinh viên
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-circle fa-4x text-muted"></i>
                </div>
                
                <div class="mb-2">
                    <strong>Họ tên:</strong><br>
                    <?php echo htmlspecialchars($extension['SV_HOTEN']); ?>
                </div>
                
                <div class="mb-2">
                    <strong>Mã sinh viên:</strong><br>
                    <span class="text-primary"><?php echo htmlspecialchars($extension['SV_MASV']); ?></span>
                </div>
                
                <div class="mb-2">
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo htmlspecialchars($extension['SV_EMAIL']); ?>">
                        <?php echo htmlspecialchars($extension['SV_EMAIL']); ?>
                    </a>
                </div>
                
                <?php if ($extension['SV_SDT']): ?>
                <div class="mb-2">
                    <strong>Số điện thoại:</strong><br>
                    <a href="tel:<?php echo htmlspecialchars($extension['SV_SDT']); ?>">
                        <?php echo htmlspecialchars($extension['SV_SDT']); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="mb-2">
                    <strong>Lớp:</strong><br>
                    <?php echo htmlspecialchars($extension['LOP_TEN']); ?>
                </div>
                
                <div class="mb-2">
                    <strong>Khóa:</strong><br>
                    <?php echo htmlspecialchars($extension['KH_NAM']); ?>
                </div>
                
                <div class="mb-2">
                    <strong>Khoa:</strong><br>
                    <?php echo htmlspecialchars($extension['KHOA_TEN']); ?>
                </div>
            </div>
        </div>
        
        <!-- Thống kê sinh viên -->
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Thống kê đề tài
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-2">
                        <div class="border rounded p-2">
                            <div class="fs-4 text-primary"><?php echo $student_stats['TONG_DETAI']; ?></div>
                            <small>Tổng đề tài</small>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="border rounded p-2">
                            <div class="fs-4 text-success"><?php echo $student_stats['HOAN_THANH']; ?></div>
                            <small>Hoàn thành</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-4 text-warning"><?php echo $student_stats['DANG_THUCHIEN']; ?></div>
                            <small>Đang thực hiện</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-4 text-danger"><?php echo $student_stats['TRE_TIENDO']; ?></div>
                            <small>Trễ tiến độ</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <strong>Đánh giá:</strong>
                    <?php
                    $completion_rate = $student_stats['TONG_DETAI'] > 0 ? 
                        round(($student_stats['HOAN_THANH'] / $student_stats['TONG_DETAI']) * 100) : 0;
                    
                    if ($completion_rate >= 80) {
                        $rating_class = 'success';
                        $rating_text = 'Xuất sắc';
                    } elseif ($completion_rate >= 60) {
                        $rating_class = 'info';
                        $rating_text = 'Tốt';
                    } elseif ($completion_rate >= 40) {
                        $rating_class = 'warning';
                        $rating_text = 'Trung bình';
                    } else {
                        $rating_class = 'danger';
                        $rating_text = 'Cần cải thiện';
                    }
                    ?>
                    <span class="badge bg-<?php echo $rating_class; ?>"><?php echo $rating_text; ?></span>
                    <small class="text-muted d-block">Tỷ lệ hoàn thành: <?php echo $completion_rate; ?>%</small>
                </div>
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
                <div class="timeline" style="max-height: 300px; overflow-y: auto;">
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
</style>
