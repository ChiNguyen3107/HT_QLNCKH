<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\edit_project.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Lấy ID đề tài từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($id)) {
    $_SESSION['error_message'] = "ID đề tài không hợp lệ!";
    header("Location: list_projects.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật thông tin đề tài</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .nav-pills .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .document-card {
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            transition: all 0.3s;
        }
        .document-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .progress-item {
            padding: 15px;
            border-left: 4px solid #007bff;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
        .member-badge {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        .member-badge.leader {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        .file-preview {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 10px;
        }
        .file-icon {
            font-size: 2rem;
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>

    <div class="container-fluid" style="margin-left: 220px;">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Thông tin chi tiết đề tài</h1>
                
                <?php
                // Hiển thị thông báo thành công nếu có
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                    unset($_SESSION['success_message']);
                }
                // Hiển thị thông báo lỗi nếu có
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
                
                // Truy vấn lấy thông tin chính của đề tài
                $main_query = "SELECT 
                                dt.*,
                                ldt.LDT_TENLOAI,
                                CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
                                lvnc.LVNC_TEN,
                                lvut.LVUT_TEN,
                                qd.QD_NGAY,
                                qd.QD_FILE,
                                hd.HD_NGAYTAO,
                                hd.HD_NGAYBD,
                                hd.HD_NGAYKT,
                                hd.HD_TONGKINHPHI,
                                hd.HD_GHICHU,
                                hd.HD_FILEHD,
                                bb.BB_NGAYNGHIEMTHU,
                                bb.BB_XEPLOAI
                            FROM 
                                de_tai_nghien_cuu dt
                            LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                            LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                            LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
                            LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
                            LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                            LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
                            LEFT JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB
                            WHERE dt.DT_MADT = ?";
                            
                $stmt = $conn->prepare($main_query);
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $project = $result->fetch_assoc();
                    ?>
                    <!-- Hiển thị thông tin cơ bản -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Thông tin đề tài: <?php echo htmlspecialchars($project['DT_TENDT']); ?></h5>
                            <span class="badge badge-light"><?php echo $project['DT_TRANGTHAI']; ?></span>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-pills mb-3" id="projectInfoTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="basic-tab" data-toggle="pill" href="#basic" role="tab">Thông tin cơ bản</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="members-tab" data-toggle="pill" href="#members" role="tab">Thành viên</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="progress-tab" data-toggle="pill" href="#progress" role="tab">Tiến độ</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="documents-tab" data-toggle="pill" href="#documents" role="tab">Quyết định & Biên bản</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="contract-tab" data-toggle="pill" href="#contract" role="tab">Hợp đồng & Tài chính</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="report-tab" data-toggle="pill" href="#report" role="tab">Báo cáo</a>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="projectInfoTabsContent">
                                <!-- Tab 1: Thông tin cơ bản -->
                                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                    <form action="update_project_basic.php" method="POST">
                                        <input type="hidden" name="DT_MADT" value="<?php echo $id; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="DT_MADT">Mã đề tài</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label for="DT_TENDT">Tên đề tài <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="DT_TENDT" name="DT_TENDT" value="<?php echo htmlspecialchars($project['DT_TENDT']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="DT_MOTA">Mô tả <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" id="DT_MOTA" name="DT_MOTA" rows="5" required><?php echo htmlspecialchars($project['DT_MOTA']); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="DT_TRANGTHAI">Trạng thái <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="DT_TRANGTHAI" name="DT_TRANGTHAI">
                                                        <option value="Chờ duyệt" <?php if ($project['DT_TRANGTHAI'] == 'Chờ duyệt') echo 'selected'; ?>>Chờ duyệt</option>
                                                        <option value="Đang thực hiện" <?php if ($project['DT_TRANGTHAI'] == 'Đang thực hiện') echo 'selected'; ?>>Đang thực hiện</option>
                                                        <option value="Đã hoàn thành" <?php if ($project['DT_TRANGTHAI'] == 'Đã hoàn thành') echo 'selected'; ?>>Đã hoàn thành</option>
                                                        <option value="Tạm dừng" <?php if ($project['DT_TRANGTHAI'] == 'Tạm dừng') echo 'selected'; ?>>Tạm dừng</option>
                                                        <option value="Đã hủy" <?php if ($project['DT_TRANGTHAI'] == 'Đã hủy') echo 'selected'; ?>>Đã hủy</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="GV_MAGV">Giảng viên hướng dẫn <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="GV_MAGV" name="GV_MAGV" required>
                                                        <?php
                                                        $giang_vien = $conn->query("SELECT GV_MAGV, CONCAT(GV_HOGV, ' ', GV_TENGV) AS GV_HOTEN FROM giang_vien");
                                                        while ($gv = $giang_vien->fetch_assoc()) {
                                                            $selected = ($gv['GV_MAGV'] == $project['GV_MAGV']) ? 'selected' : '';
                                                            echo "<option value='{$gv['GV_MAGV']}' {$selected}>{$gv['GV_HOTEN']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="LDT_MA">Loại đề tài <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="LDT_MA" name="LDT_MA" required>
                                                        <?php
                                                        $loai_de_tai = $conn->query("SELECT LDT_MA, LDT_TENLOAI FROM loai_de_tai");
                                                        while ($ldt = $loai_de_tai->fetch_assoc()) {
                                                            $selected = ($ldt['LDT_MA'] == $project['LDT_MA']) ? 'selected' : '';
                                                            echo "<option value='{$ldt['LDT_MA']}' {$selected}>{$ldt['LDT_TENLOAI']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="LVNC_MA">Lĩnh vực nghiên cứu <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="LVNC_MA" name="LVNC_MA" required>
                                                        <?php
                                                        $linh_vuc_nc = $conn->query("SELECT LVNC_MA, LVNC_TEN FROM linh_vuc_nghien_cuu");
                                                        while ($lvnc = $linh_vuc_nc->fetch_assoc()) {
                                                            $selected = ($lvnc['LVNC_MA'] == $project['LVNC_MA']) ? 'selected' : '';
                                                            echo "<option value='{$lvnc['LVNC_MA']}' {$selected}>{$lvnc['LVNC_TEN']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="LVUT_MA">Lĩnh vực ưu tiên <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="LVUT_MA" name="LVUT_MA" required>
                                                        <?php
                                                        $linh_vuc_ut = $conn->query("SELECT LVUT_MA, LVUT_TEN FROM linh_vuc_uu_tien");
                                                        while ($lvut = $linh_vuc_ut->fetch_assoc()) {
                                                            $selected = ($lvut['LVUT_MA'] == $project['LVUT_MA']) ? 'selected' : '';
                                                            echo "<option value='{$lvut['LVUT_MA']}' {$selected}>{$lvut['LVUT_TEN']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="DT_FILEBTM">File đề cương</label>
                                                    <?php if (!empty($project['DT_FILEBTM'])): ?>
                                                    <div class="file-preview">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-file-pdf file-icon"></i>
                                                            <div>
                                                                <p class="mb-1">File hiện tại: <?php echo basename($project['DT_FILEBTM']); ?></p>
                                                                <a href="<?php echo $project['DT_FILEBTM']; ?>" class="btn btn-sm btn-primary" target="_blank">Xem file</a>
                                                                <a href="download_file.php?path=<?php echo urlencode($project['DT_FILEBTM']); ?>" class="btn btn-sm btn-info">Tải xuống</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php else: ?>
                                                    <p class="text-muted">Chưa có file đề cương</p>
                                                    <?php endif; ?>
                                                    <input type="file" class="form-control-file mt-2" id="new_outline" name="new_outline">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group text-center mt-4">
                                            <button type="submit" class="btn btn-primary">Cập nhật thông tin cơ bản</button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Tab 2: Thành viên -->
                                <div class="tab-pane fade" id="members" role="tabpanel">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">Danh sách thành viên</h5>
                                                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addMemberModal">
                                                    <i class="fas fa-plus"></i> Thêm thành viên
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>MSSV</th>
                                                            <th>Họ tên</th>
                                                            <th>Lớp</th>
                                                            <th>Email</th>
                                                            <th>SĐT</th>
                                                            <th>Vai trò</th>
                                                            <th>Ngày tham gia</th>
                                                            <th>Thao tác</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $member_query = "SELECT 
                                                                        ct.SV_MASV, 
                                                                        ct.CTTG_VAITRO, 
                                                                        ct.CTTG_NGAYTHAMGIA, 
                                                                        CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN,
                                                                        lop.LOP_TEN,
                                                                        sv.SV_EMAIL,
                                                                        sv.SV_SDT
                                                                    FROM 
                                                                        chi_tiet_tham_gia ct
                                                                    JOIN 
                                                                        sinh_vien sv ON ct.SV_MASV = sv.SV_MASV
                                                                    LEFT JOIN 
                                                                        lop ON sv.LOP_MA = lop.LOP_MA
                                                                    WHERE 
                                                                        ct.DT_MADT = ?
                                                                    ORDER BY 
                                                                        FIELD(ct.CTTG_VAITRO, 'Chủ nhiệm', 'Thành viên')";
                                                        
                                                        $member_stmt = $conn->prepare($member_query);
                                                        $member_stmt->bind_param("s", $id);
                                                        $member_stmt->execute();
                                                        $members = $member_stmt->get_result();
                                                        
                                                        if ($members->num_rows > 0) {
                                                            while ($member = $members->fetch_assoc()) {
                                                                $role_badge = ($member['CTTG_VAITRO'] == 'Chủ nhiệm') 
                                                                    ? '<span class="badge badge-success">Chủ nhiệm</span>' 
                                                                    : '<span class="badge badge-secondary">Thành viên</span>';
                                                                
                                                                echo "<tr>
                                                                        <td>{$member['SV_MASV']}</td>
                                                                        <td>{$member['SV_HOTEN']}</td>
                                                                        <td>{$member['LOP_TEN']}</td>
                                                                        <td>{$member['SV_EMAIL']}</td>
                                                                        <td>{$member['SV_SDT']}</td>
                                                                        <td>{$role_badge}</td>
                                                                        <td>" . date('d/m/Y', strtotime($member['CTTG_NGAYTHAMGIA'])) . "</td>
                                                                        <td>";
                                                                if ($member['CTTG_VAITRO'] != 'Chủ nhiệm') {
                                                                    echo "<button class='btn btn-danger btn-sm remove-member' data-student-id='{$member['SV_MASV']}' data-project-id='{$id}'>
                                                                            <i class='fas fa-trash'></i>
                                                                          </button>";
                                                                }
                                                                echo "</td>
                                                                    </tr>";
                                                            }
                                                        } else {
                                                            echo "<tr><td colspan='8' class='text-center'>Không tìm thấy thành viên nào</td></tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 3: Tiến độ -->
                                <div class="tab-pane fade" id="progress" role="tabpanel">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">Tiến độ thực hiện đề tài</h5>
                                                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addProgressModal">
                                                    <i class="fas fa-plus"></i> Cập nhật tiến độ
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="progress mb-4" style="height: 30px;">
                                                <?php
                                                // Lấy tiến độ mới nhất của đề tài
                                                $progress_query = "SELECT TDDT_PHANTRAMHOANTHANH FROM tien_do_de_tai 
                                                                  WHERE DT_MADT = ? ORDER BY TDDT_NGAYCAPNHAT DESC LIMIT 1";
                                                $progress_stmt = $conn->prepare($progress_query);
                                                $progress_stmt->bind_param("s", $id);
                                                $progress_stmt->execute();
                                                $latest_progress = $progress_stmt->get_result()->fetch_assoc();
                                                
                                                $progress_percent = $latest_progress ? $latest_progress['TDDT_PHANTRAMHOANTHANH'] : 0;
                                                ?>
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progress_percent; ?>%;" 
                                                    aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $progress_percent; ?>%
                                                </div>
                                            </div>
                                            <h6>Lịch sử cập nhật tiến độ</h6>
                                            <div class="timeline">
                                                <?php
                                                $timeline_query = "SELECT 
                                                                    td.*, 
                                                                    CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN
                                                                  FROM 
                                                                    tien_do_de_tai td
                                                                  JOIN 
                                                                    sinh_vien sv ON td.SV_MASV = sv.SV_MASV
                                                                  WHERE 
                                                                    td.DT_MADT = ?
                                                                  ORDER BY 
                                                                    td.TDDT_NGAYCAPNHAT DESC";
                                                                    
                                                $timeline_stmt = $conn->prepare($timeline_query);
                                                $timeline_stmt->bind_param("s", $id);
                                                $timeline_stmt->execute();
                                                $timeline_result = $timeline_stmt->get_result();
                                                
                                                if ($timeline_result->num_rows > 0) {
                                                    while ($progress = $timeline_result->fetch_assoc()) {
                                                        $date = date('d/m/Y H:i', strtotime($progress['TDDT_NGAYCAPNHAT']));
                                                        echo "<div class='progress-item'>
                                                                <h6>{$progress['TDDT_TIEUDE']} - <small>{$date} bởi {$progress['SV_HOTEN']}</small></h6>
                                                                <div class='progress mb-2' style='height: 5px;'>
                                                                    <div class='progress-bar' role='progressbar' style='width: {$progress['TDDT_PHANTRAMHOANTHANH']}%;'></div>
                                                                </div>
                                                                <p>{$progress['TDDT_NOIDUNG']}</p>
                                                                <div class='text-right'>
                                                                    <button class='btn btn-sm btn-danger delete-progress' data-id='{$progress['TDDT_MA']}'>
                                                                        <i class='fas fa-trash'></i> Xóa
                                                                    </button>
                                                                </div>
                                                            </div>";
                                                    }
                                                } else {
                                                    echo "<p class='text-muted'>Chưa có cập nhật tiến độ nào.</p>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal cập nhật tiến độ -->
                                <div class="modal fade" id="addProgressModal" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Cập nhật tiến độ đề tài</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="update_project_progress.php" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="DT_MADT" value="<?php echo $id; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label for="TDDT_TIEUDE">Tiêu đề cập nhật <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="TDDT_TIEUDE" name="TDDT_TIEUDE" required>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="TDDT_NOIDUNG">Mô tả chi tiết <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="TDDT_NOIDUNG" name="TDDT_NOIDUNG" rows="4" required></textarea>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="TDDT_PHANTRAMHOANTHANH">Phần trăm hoàn thành <span class="text-danger">*</span></label>
                                                        <input type="range" class="form-control-range" id="TDDT_PHANTRAMHOANTHANH" name="TDDT_PHANTRAMHOANTHANH" min="0" max="100" value="<?php echo $progress_percent; ?>">
                                                        <div class="text-center" id="progress-value"><?php echo $progress_percent; ?>%</div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="SV_MASV">Người cập nhật <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="SV_MASV" name="SV_MASV" required>
                                                            <?php
                                                            // Lấy danh sách sinh viên tham gia đề tài
                                                            $member_query = "SELECT 
                                                                            sv.SV_MASV, 
                                                                            CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN,
                                                                            ct.CTTG_VAITRO
                                                                         FROM 
                                                                            chi_tiet_tham_gia ct
                                                                         JOIN 
                                                                            sinh_vien sv ON ct.SV_MASV = sv.SV_MASV
                                                                         WHERE 
                                                                            ct.DT_MADT = ?
                                                                         ORDER BY 
                                                                            FIELD(ct.CTTG_VAITRO, 'Chủ nhiệm', 'Thành viên')";
                                                                            
                                                            $member_stmt = $conn->prepare($member_query);
                                                            $member_stmt->bind_param("s", $id);
                                                            $member_stmt->execute();
                                                            $members = $member_stmt->get_result();
                                                            
                                                            while ($member = $members->fetch_assoc()) {
                                                                $role_suffix = $member['CTTG_VAITRO'] == 'Chủ nhiệm' ? ' (Chủ nhiệm)' : '';
                                                                echo "<option value='" . $member['SV_MASV'] . "'>" . $member['SV_HOTEN'] . $role_suffix . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                                                    <button type="submit" class="btn btn-primary">Cập nhật tiến độ</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal tạo quyết định nghiệm thu -->
                                <div class="modal fade" id="createDecisionModal" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Tạo quyết định nghiệm thu</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="create_decision.php" method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="DT_MADT" value="<?php echo $id; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label for="QD_SO">Số quyết định <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="QD_SO" name="QD_SO" placeholder="Ví dụ: QD123" required>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="QD_NGAY">Ngày quyết định <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" id="QD_NGAY" name="QD_NGAY" value="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="QD_FILE">File quyết định</label>
                                                        <input type="file" class="form-control-file" id="QD_FILE" name="QD_FILE">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                                                    <button type="submit" class="btn btn-primary">Tạo quyết định</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal tạo biên bản nghiệm thu -->
                                <div class="modal fade" id="createMinutesModal" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Tạo biên bản nghiệm thu</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="create_minutes.php" method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="DT_MADT" value="<?php echo $id; ?>">
                                                    <input type="hidden" name="QD_SO" value="<?php echo $project['QD_SO']; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label for="BB_SOBB">Số biên bản <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="BB_SOBB" name="BB_SOBB" placeholder="Ví dụ: BB123" required>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="BB_NGAYNGHIEMTHU">Ngày nghiệm thu <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" id="BB_NGAYNGHIEMTHU" name="BB_NGAYNGHIEMTHU" value="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="BB_XEPLOAI">Xếp loại <span class="text-danger">*</span></label>
                                                        <select class="form-control" id="BB_XEPLOAI" name="BB_XEPLOAI" required>
                                                            <option value="Xuất sắc">Xuất sắc</option>
                                                            <option value="Tốt">Tốt</option>
                                                            <option value="Khá">Khá</option>
                                                            <option value="Đạt">Đạt</option>
                                                            <option value="Không đạt">Không đạt</option>
                                                            <option value="Chưa nghiệm thu" selected>Chưa nghiệm thu</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="BB_FILE">File biên bản</label>
                                                        <input type="file" class="form-control-file" id="BB_FILE" name="BB_FILE">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                                                    <button type="submit" class="btn btn-primary">Tạo biên bản</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal tạo hợp đồng -->
                                <div class="modal fade" id="createContractModal" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Tạo hợp đồng</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="create_contract.php" method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="DT_MADT" value="<?php echo $id; ?>">
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="HD_MA">Mã hợp đồng <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" id="HD_MA" name="HD_MA" placeholder="Ví dụ: HD123" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="HD_NGAYTAO">Ngày tạo <span class="text-danger">*</span></label>
                                                                <input type="date" class="form-control" id="HD_NGAYTAO" name="HD_NGAYTAO" value="<?php echo date('Y-m-d'); ?>" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="HD_NGAYBD">Ngày bắt đầu <span class="text-danger">*</span></label>
                                                                <input type="date" class="form-control" id="HD_NGAYBD" name="HD_NGAYBD" value="<?php echo date('Y-m-d'); ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="HD_NGAYKT">Ngày kết thúc <span class="text-danger">*</span></label>
                                                                <input type="date" class="form-control" id="HD_NGAYKT" name="HD_NGAYKT" value="<?php echo date('Y-m-d', strtotime('+6 months')); ?>" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="HD_TONGKINHPHI">Tổng kinh phí (VNĐ) <span class="text-danger">*</span></label>
                                                                <input type="number" class="form-control" id="HD_TONGKINHPHI" name="HD_TONGKINHPHI" value="5000000" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="HD_FILEHD">File hợp đồng</label>
                                                                <input type="file" class="form-control-file" id="HD_FILEHD" name="HD_FILEHD">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="HD_GHICHU">Ghi chú</label>
                                                        <textarea class="form-control" id="HD_GHICHU" name="HD_GHICHU" rows="3"><?php echo "Hợp đồng thực hiện đề tài nghiên cứu khoa học: " . htmlspecialchars($project['DT_TENDT']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                                                    <button type="submit" class="btn btn-primary">Tạo hợp đồng</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal thêm thanh toán -->
                                <div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Thêm khoản thanh toán</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form action="add_payment.php" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="DT_MADT" value="<?php echo $id; ?>">
                                                    <input type="hidden" name="HD_MA" value="<?php echo $project['HD_MA']; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label for="TT_NGAY">Ngày thanh toán <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" id="TT_NGAY" name="TT_NGAY" value="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="TT_SOTIEN">Số tiền (VNĐ) <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" id="TT_SOTIEN" name="TT_SOTIEN" required>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="TT_NOIDUNG">Nội dung thanh toán <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="TT_NOIDUNG" name="TT_NOIDUNG" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                                                    <button type="submit" class="btn btn-primary">Thêm thanh toán</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                    <?php 
                    } else {
                        echo '<div class="alert alert-danger">Không tìm thấy thông tin đề tài!</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Cập nhật hiển thị phần trăm khi kéo thanh range
            $('#TDDT_PHANTRAMHOANTHANH').on('input', function() {
                $('#progress-value').text($(this).val() + '%');
            });
            
            // Xử lý xóa thành viên
            $('.remove-member').on('click', function() {
                if (confirm('Bạn có chắc chắn muốn xóa thành viên này khỏi đề tài?')) {
                    const studentId = $(this).data('student-id');
                    const projectId = $(this).data('project-id');
                    
                    window.location.href = `remove_member.php?student_id=${studentId}&project_id=${projectId}`;
                }
            });
            
            // Xử lý xóa báo cáo
            $('.delete-report').on('click', function() {
                if (confirm('Bạn có chắc chắn muốn xóa báo cáo này?')) {
                    const reportId = $(this).data('id');
                    
                    window.location.href = `delete_report.php?id=${reportId}&project_id=<?php echo $id; ?>`;
                }
            });
            
            // Xử lý xóa tiến độ
            $('.delete-progress').on('click', function() {
                if (confirm('Bạn có chắc chắn muốn xóa cập nhật tiến độ này?')) {
                    const progressId = $(this).data('id');
                    
                    window.location.href = `delete_progress.php?id=${progressId}&project_id=<?php echo $id; ?>`;
                }
            });
        });
    </script>
</body>
</html>

