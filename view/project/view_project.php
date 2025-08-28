<?php
session_start();
require_once '../../include/config.php';
require_once '../../include/database.php';

// Kiểm tra xem có tham số dt_madt không
if (!isset($_GET['dt_madt']) || empty($_GET['dt_madt'])) {
    header("Location: /NLNganh/404.php");
    exit();
}

$dt_madt = $_GET['dt_madt'];

try {
    // Lấy thông tin chi tiết đề tài
    $project_sql = "SELECT 
                        dt.DT_MADT,
                        dt.DT_TENDT,
                        dt.DT_MOTA,
                        dt.DT_TRANGTHAI,
                        dt.DT_FILEBTM,
                        dt.DT_NGAYTAO,
                        dt.DT_SLSV,
                        dt.DT_GHICHU,
                        dt.DT_NGAYCAPNHAT,
                        ldt.LDT_TENLOAI,
                        lvnc.LVNC_TEN,
                        lvut.LVUT_TEN,
                        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                        gv.GV_EMAIL,
                        gv.GV_SDT,
                        gv.GV_CHUYENMON,
                        k.DV_TENDV,
                        hd.HD_MA,
                        hd.HD_NGAYBD,
                        hd.HD_NGAYKT,
                        hd.HD_TONGKINHPHI,
                        hd.HD_FILEHD,
                        hd.HD_NGUOIKY,
                        qd.QD_SO,
                        qd.QD_NGAY,
                        qd.QD_FILE,
                        bb.BB_SOBB,
                        bb.BB_NGAYNGHIEMTHU,
                        bb.BB_XEPLOAI,
                        bb.BB_TONGDIEM
                    FROM de_tai_nghien_cuu dt
                    LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
                    LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
                    LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                    LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
                    LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                    LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                    WHERE dt.DT_MADT = ?";
    
    $stmt = $conn->prepare($project_sql);
    $stmt->bind_param("s", $dt_madt);
    $stmt->execute();
    $project_result = $stmt->get_result();
    
    if ($project_result->num_rows == 0) {
        header("Location: /NLNganh/404.php");
        exit();
    }
    
    $project = $project_result->fetch_assoc();
    
    // Lấy danh sách sinh viên tham gia
    $students_sql = "SELECT 
                        sv.SV_MASV,
                        sv.SV_HOSV,
                        sv.SV_TENSV,
                        sv.SV_EMAIL,
                        sv.SV_SDT,
                        l.LOP_TEN,
                        l.KH_NAM,
                        cttg.CTTG_VAITRO,
                        cttg.CTTG_NGAYTHAMGIA
                    FROM chi_tiet_tham_gia cttg
                    JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                    JOIN lop l ON sv.LOP_MA = l.LOP_MA
                    WHERE cttg.DT_MADT = ?
                    ORDER BY cttg.CTTG_VAITRO, sv.SV_HOSV, sv.SV_TENSV";
    
    $stmt_students = $conn->prepare($students_sql);
    $stmt_students->bind_param("s", $dt_madt);
    $stmt_students->execute();
    $students_result = $stmt_students->get_result();
    $students = $students_result->fetch_all(MYSQLI_ASSOC);
    
    // Lấy tiến độ đề tài
    $progress_sql = "SELECT 
                        tddt.TDDT_MA,
                        tddt.TDDT_TIEUDE,
                        tddt.TDDT_NOIDUNG,
                        tddt.TDDT_PHANTRAMHOANTHANH,
                        tddt.TDDT_FILE,
                        tddt.TDDT_NGAYCAPNHAT,
                        CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN
                    FROM tien_do_de_tai tddt
                    JOIN sinh_vien sv ON tddt.SV_MASV = sv.SV_MASV
                    WHERE tddt.DT_MADT = ?
                    ORDER BY tddt.TDDT_NGAYCAPNHAT DESC";
    
    $stmt_progress = $conn->prepare($progress_sql);
    $stmt_progress->bind_param("s", $dt_madt);
    $stmt_progress->execute();
    $progress_result = $stmt_progress->get_result();
    $progress_list = $progress_result->fetch_all(MYSQLI_ASSOC);
    
    // Lấy thông tin hội đồng đánh giá (nếu có)
    $council_sql = "SELECT 
                        tv.GV_MAGV,
                        tv.TV_HOTEN,
                        tv.TV_VAITRO,
                        tv.TV_DIEM,
                        tv.TV_TRANGTHAI,
                        tv.TV_NGAYDANHGIA,
                        tv.TV_DANHGIA,
                        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN_FULL
                    FROM thanh_vien_hoi_dong tv
                    JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                    WHERE tv.QD_SO = ?
                    ORDER BY tv.TV_VAITRO, gv.GV_HOGV, gv.GV_TENGV";
    
    $council_members = [];
    if (!empty($project['QD_SO'])) {
        $stmt_council = $conn->prepare($council_sql);
        $stmt_council->bind_param("s", $project['QD_SO']);
        $stmt_council->execute();
        $council_result = $stmt_council->get_result();
        $council_members = $council_result->fetch_all(MYSQLI_ASSOC);
    }

} catch (Exception $e) {
    error_log("Error in view_project.php: " . $e->getMessage());
    header("Location: /NLNganh/404.php");
    exit();
}

// Hàm helper để hiển thị trạng thái
function getStatusBadge($status) {
    $badges = [
        'Chờ duyệt' => 'bg-warning',
        'Đang thực hiện' => 'bg-info',
        'Đã hoàn thành' => 'bg-success',
        'Tạm dừng' => 'bg-secondary',
        'Đã hủy' => 'bg-danger',
        'Đang xử lý' => 'bg-primary'
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
    <title>Chi tiết đề tài: <?= htmlspecialchars($project['DT_TENDT']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/project/project-view.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .project-header {
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
        
        .info-row {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .progress-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        
        .student-card {
            background: linear-gradient(45deg, #fff, #f8f9fa);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #28a745;
        }
        
        .council-member {
            background: linear-gradient(45deg, #fff5f5, #fff);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #dc3545;
        }
        
        .file-download {
            background: linear-gradient(45deg, #e3f2fd, #fff);
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 0.75rem;
            margin: 0.5rem 0;
            transition: all 0.2s;
        }
        
        .file-download:hover {
            background: linear-gradient(45deg, #bbdefb, #e3f2fd);
            transform: translateX(5px);
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
        
        .status-timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .status-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .status-item {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .status-item::before {
            content: '';
            position: absolute;
            left: -15px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #007bff;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #007bff;
        }
    </style>
</head>
<body>
    <div class="project-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-project-diagram me-3"></i>
                        <?= htmlspecialchars($project['DT_TENDT']) ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-code me-2"></i>
                        Mã đề tài: <strong><?= htmlspecialchars($project['DT_MADT']) ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="javascript:history.back()" class="back-button">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Cột trái: Thông tin cơ bản -->
            <div class="col-lg-8">
                <!-- Thông tin đề tài -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Thông tin đề tài
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Tên đề tài:</div>
                                <div class="col-sm-9"><?= htmlspecialchars($project['DT_TENDT']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Mô tả:</div>
                                <div class="col-sm-9">
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        <?= nl2br(htmlspecialchars($project['DT_MOTA'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Trạng thái:</div>
                                <div class="col-sm-9"><?= getStatusBadge($project['DT_TRANGTHAI']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Loại đề tài:</div>
                                <div class="col-sm-9"><?= htmlspecialchars($project['LDT_TENLOAI'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Lĩnh vực nghiên cứu:</div>
                                <div class="col-sm-9"><?= htmlspecialchars($project['LVNC_TEN'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Lĩnh vực ưu tiên:</div>
                                <div class="col-sm-9"><?= htmlspecialchars($project['LVUT_TEN'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Số lượng SV:</div>
                                <div class="col-sm-9">
                                    <span class="badge bg-primary"><?= $project['DT_SLSV'] ?> sinh viên</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Ngày tạo:</div>
                                <div class="col-sm-9"><?= date('d/m/Y H:i', strtotime($project['DT_NGAYTAO'])) ?></div>
                            </div>
                        </div>
                        
                        <?php if ($project['DT_NGAYCAPNHAT']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Cập nhật cuối:</div>
                                <div class="col-sm-9"><?= date('d/m/Y H:i', strtotime($project['DT_NGAYCAPNHAT'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($project['DT_GHICHU']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-sm-3 info-label">Ghi chú:</div>
                                <div class="col-sm-9">
                                    <div class="alert alert-info">
                                        <?= nl2br(htmlspecialchars($project['DT_GHICHU'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Giảng viên hướng dẫn -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            Giảng viên hướng dẫn
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label mb-1">Họ và tên:</div>
                                    <div><?= htmlspecialchars($project['GV_HOTEN']) ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label mb-1">Email:</div>
                                    <div>
                                        <a href="mailto:<?= htmlspecialchars($project['GV_EMAIL']) ?>">
                                            <?= htmlspecialchars($project['GV_EMAIL']) ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if ($project['GV_SDT']): ?>
                                <div class="info-row">
                                    <div class="info-label mb-1">Số điện thoại:</div>
                                    <div><?= htmlspecialchars($project['GV_SDT']) ?></div>
                                </div>
                                <?php endif; ?>
                                <div class="info-row">
                                    <div class="info-label mb-1">Khoa:</div>
                                    <div><?= htmlspecialchars($project['DV_TENDV'] ?? 'Chưa xác định') ?></div>
                                </div>
                            </div>
                        </div>
                        <?php if ($project['GV_CHUYENMON']): ?>
                        <div class="info-row mt-3">
                            <div class="info-label mb-2">Chuyên môn:</div>
                            <div class="alert alert-light">
                                <?= nl2br(htmlspecialchars($project['GV_CHUYENMON'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sinh viên tham gia -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Sinh viên tham gia (<?= count($students) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($students as $student): ?>
                        <div class="student-card">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-1">
                                        <?= htmlspecialchars($student['SV_HOSV'] . ' ' . $student['SV_TENSV']) ?>
                                    </h6>
                                    <small class="text-muted"><?= htmlspecialchars($student['SV_MASV']) ?></small>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-label">Vai trò:</div>
                                    <span class="badge bg-success"><?= htmlspecialchars($student['CTTG_VAITRO']) ?></span>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-label">Lớp:</div>
                                    <div><?= htmlspecialchars($student['LOP_TEN']) ?></div>
                                    <small class="text-muted">K<?= htmlspecialchars($student['KH_NAM']) ?></small>
                                </div>
                                <div class="col-md-2">
                                    <div class="info-label">Tham gia:</div>
                                    <small><?= date('d/m/Y', strtotime($student['CTTG_NGAYTHAMGIA'])) ?></small>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <small>
                                        <i class="fas fa-envelope me-1"></i>
                                        <?= htmlspecialchars($student['SV_EMAIL']) ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small>
                                        <i class="fas fa-phone me-1"></i>
                                        <?= htmlspecialchars($student['SV_SDT']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tiến độ thực hiện -->
                <?php if (!empty($progress_list)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>
                            Tiến độ thực hiện (<?= count($progress_list) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($progress_list as $progress): ?>
                        <div class="progress-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1"><?= htmlspecialchars($progress['TDDT_TIEUDE']) ?></h6>
                                <span class="badge bg-info"><?= htmlspecialchars($progress['SV_HOTEN']) ?></span>
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
                            <div class="file-download mt-2">
                                <i class="fas fa-file-download me-2"></i>
                                <a href="/NLNganh/uploads/progress/<?= htmlspecialchars($progress['TDDT_FILE']) ?>" 
                                   target="_blank" class="text-decoration-none">
                                    Tải file đính kèm
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cột phải: Thông tin bổ sung -->
            <div class="col-lg-4">
                <!-- File đính kèm -->
                <?php if ($project['DT_FILEBTM']): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-paperclip me-2"></i>
                            File đề tài
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="file-download">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                <div>
                                    <div class="fw-bold">Thuyết minh đề tài</div>
                                    <small class="text-muted">
                                        <a href="/NLNganh/uploads/proposals/<?= htmlspecialchars($project['DT_FILEBTM']) ?>" 
                                           target="_blank" class="text-decoration-none">
                                            <i class="fas fa-download me-1"></i>Tải xuống
                                        </a>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Hợp đồng -->
                <?php if ($project['HD_MA']): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-handshake me-2"></i>
                            Hợp đồng
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">Mã hợp đồng:</div>
                            <div><?= htmlspecialchars($project['HD_MA']) ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Thời gian thực hiện:</div>
                            <div>
                                <?= date('d/m/Y', strtotime($project['HD_NGAYBD'])) ?> - 
                                <?= date('d/m/Y', strtotime($project['HD_NGAYKT'])) ?>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Tổng kinh phí:</div>
                            <div class="text-success fw-bold">
                                <?= number_format($project['HD_TONGKINHPHI'], 0, ',', '.') ?> VNĐ
                            </div>
                        </div>
                        
                        <?php if ($project['HD_NGUOIKY']): ?>
                        <div class="info-row">
                            <div class="info-label">Người ký:</div>
                            <div><?= htmlspecialchars($project['HD_NGUOIKY']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($project['HD_FILEHD']): ?>
                        <div class="file-download mt-2">
                            <i class="fas fa-file-contract me-2"></i>
                            <a href="/NLNganh/uploads/contracts/<?= htmlspecialchars($project['HD_FILEHD']) ?>" 
                               target="_blank" class="text-decoration-none">
                                Xem hợp đồng
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quyết định nghiệm thu -->
                <?php if ($project['QD_SO']): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-gavel me-2"></i>
                            Quyết định nghiệm thu
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">Số quyết định:</div>
                            <div><?= htmlspecialchars($project['QD_SO']) ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Ngày ban hành:</div>
                            <div><?= date('d/m/Y', strtotime($project['QD_NGAY'])) ?></div>
                        </div>
                        
                        <?php if ($project['QD_FILE']): ?>
                        <div class="file-download mt-2">
                            <i class="fas fa-file-alt me-2"></i>
                            <a href="/NLNganh/uploads/decisions/<?= htmlspecialchars($project['QD_FILE']) ?>" 
                               target="_blank" class="text-decoration-none">
                                Xem quyết định
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Biên bản nghiệm thu -->
                <?php if ($project['BB_SOBB']): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Biên bản nghiệm thu
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">Số biên bản:</div>
                            <div><?= htmlspecialchars($project['BB_SOBB']) ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Ngày nghiệm thu:</div>
                            <div><?= date('d/m/Y', strtotime($project['BB_NGAYNGHIEMTHU'])) ?></div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Xếp loại:</div>
                            <div>
                                <span class="badge bg-success"><?= htmlspecialchars($project['BB_XEPLOAI']) ?></span>
                            </div>
                        </div>
                        
                        <?php if ($project['BB_TONGDIEM']): ?>
                        <div class="info-row">
                            <div class="info-label">Tổng điểm:</div>
                            <div class="text-primary fw-bold">
                                <?= number_format($project['BB_TONGDIEM'], 2) ?>/100
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Hội đồng đánh giá -->
                <?php if (!empty($council_members)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users-cog me-2"></i>
                            Hội đồng đánh giá (<?= count($council_members) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($council_members as $member): ?>
                        <div class="council-member">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($member['TV_HOTEN'] ?: $member['GV_HOTEN_FULL']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($member['GV_MAGV']) ?></small>
                                </div>
                                <span class="badge bg-primary"><?= htmlspecialchars($member['TV_VAITRO']) ?></span>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <small class="info-label">Trạng thái:</small>
                                    <div><?= getStatusBadge($member['TV_TRANGTHAI']) ?></div>
                                </div>
                                <?php if ($member['TV_DIEM']): ?>
                                <div class="col-6">
                                    <small class="info-label">Điểm đánh giá:</small>
                                    <div class="text-primary fw-bold"><?= number_format($member['TV_DIEM'], 2) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($member['TV_NGAYDANHGIA']): ?>
                            <div class="mt-1">
                                <small class="text-muted">
                                    Đánh giá: <?= date('d/m/Y H:i', strtotime($member['TV_NGAYDANHGIA'])) ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($member['TV_DANHGIA']): ?>
                            <div class="mt-2 p-2 bg-light rounded">
                                <small><?= nl2br(htmlspecialchars($member['TV_DANHGIA'])) ?></small>
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
    <script>
        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-info')) {
                    alert.style.opacity = '0.7';
                }
            });
        }, 5000);
    </script>
</body>
</html>
