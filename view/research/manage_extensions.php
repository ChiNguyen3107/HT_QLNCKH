<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\manage_extensions.php
include '../../include/session.php';
checkResearchManagerRole();
include '../../include/connect.php';

// Xử lý các action (duyệt/từ chối)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $extension_id = intval($_POST['extension_id'] ?? 0);
    
    if ($action === 'approve' && $extension_id > 0) {
        $ghi_chu = trim($_POST['ghi_chu'] ?? '');
        try {
            $conn->query("CALL sp_approve_extension($extension_id, '{$_SESSION['user_id']}', '$ghi_chu')");
            $success_message = "Đã duyệt yêu cầu gia hạn thành công!";
        } catch (Exception $e) {
            $error_message = "Lỗi khi duyệt: " . $e->getMessage();
        }
    } elseif ($action === 'reject' && $extension_id > 0) {
        $ly_do_tu_choi = trim($_POST['ly_do_tu_choi'] ?? '');
        if (empty($ly_do_tu_choi)) {
            $error_message = "Vui lòng nhập lý do từ chối";
        } else {
            try {
                $conn->query("CALL sp_reject_extension($extension_id, '{$_SESSION['user_id']}', '$ly_do_tu_choi')");
                $success_message = "Đã từ chối yêu cầu gia hạn!";
            } catch (Exception $e) {
                $error_message = "Lỗi khi từ chối: " . $e->getMessage();
            }
        }
    }
}

// Lấy bộ lọc
$filter_status = $_GET['status'] ?? '';
$filter_khoa = $_GET['khoa'] ?? '';
$filter_search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Xây dựng điều kiện WHERE
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($filter_status)) {
    $where_conditions[] = "gh.GH_TRANGTHAI = ?";
    $params[] = $filter_status;
    $param_types .= 's';
}

if (!empty($filter_khoa)) {
    $where_conditions[] = "dv.DV_MADV = ?";
    $params[] = $filter_khoa;
    $param_types .= 's';
}

if (!empty($filter_search)) {
    $where_conditions[] = "(gh.DT_MADT LIKE ? OR dt.DT_TENDT LIKE ? OR CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) LIKE ?)";
    $search_param = "%$filter_search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total
              FROM de_tai_gia_han gh
              INNER JOIN de_tai_nghien_cuu dt ON gh.DT_MADT = dt.DT_MADT
              INNER JOIN sinh_vien sv ON gh.SV_MASV = sv.SV_MASV
              INNER JOIN lop ON sv.LOP_MA = lop.LOP_MA
              INNER JOIN khoa dv ON lop.DV_MADV = dv.DV_MADV
              $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Lấy danh sách yêu cầu gia hạn
$extensions_sql = "SELECT gh.*, dt.DT_TENDT, dt.DT_TRANGTHAI as DT_TRANGTHAI_HIENTAI,
                          dt.DT_TRE_TIENDO, dt.DT_SO_LAN_GIA_HAN,
                          CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
                          sv.SV_EMAIL, sv.SV_SDT,
                          lop.LOP_TEN, lop.KH_NAM,
                          dv.DV_TENDV as KHOA_TEN,
                          CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                          CONCAT(ql.QL_HO, ' ', ql.QL_TEN) as NGUOI_DUYET_HOTEN,
                          DATEDIFF(NOW(), gh.GH_NGAYYEUCAU) as SO_NGAY_CHO,
                          DATEDIFF(gh.GH_NGAYHETHAN_MOI, gh.GH_NGAYHETHAN_CU) as SO_NGAY_GIA_HAN
                   FROM de_tai_gia_han gh
                   INNER JOIN de_tai_nghien_cuu dt ON gh.DT_MADT = dt.DT_MADT
                   INNER JOIN sinh_vien sv ON gh.SV_MASV = sv.SV_MASV
                   INNER JOIN lop ON sv.LOP_MA = lop.LOP_MA
                   INNER JOIN khoa dv ON lop.DV_MADV = dv.DV_MADV
                   LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                   LEFT JOIN quan_ly_nghien_cuu ql ON gh.GH_NGUOIDUYET = ql.QL_MA
                   $where_clause
                   ORDER BY 
                       CASE WHEN gh.GH_TRANGTHAI = 'Chờ duyệt' THEN 1 ELSE 2 END,
                       gh.GH_NGAYYEUCAU DESC
                   LIMIT $limit OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($extensions_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $extensions_result = $stmt->get_result();
} else {
    $extensions_result = $conn->query($extensions_sql);
}

$extensions = [];
while ($row = $extensions_result->fetch_assoc()) {
    $extensions[] = $row;
}
if (isset($stmt)) $stmt->close();

// Thống kê tổng quan
$stats_sql = "SELECT 
                COUNT(*) as TONG_YEU_CAU,
                COUNT(CASE WHEN GH_TRANGTHAI = 'Chờ duyệt' THEN 1 END) as CHO_DUYET,
                COUNT(CASE WHEN GH_TRANGTHAI = 'Đã duyệt' THEN 1 END) as DA_DUYET,
                COUNT(CASE WHEN GH_TRANGTHAI = 'Từ chối' THEN 1 END) as TU_CHOI,
                COUNT(CASE WHEN GH_TRANGTHAI = 'Chờ duyệt' AND DATEDIFF(NOW(), GH_NGAYYEUCAU) > 7 THEN 1 END) as QUA_HAN
              FROM de_tai_gia_han";
$stats = $conn->query($stats_sql)->fetch_assoc();

// Lấy danh sách khoa để filter
$khoa_sql = "SELECT DISTINCT dv.DV_MADV, dv.DV_TENDV 
             FROM khoa dv 
             INNER JOIN lop ON dv.DV_MADV = lop.DV_MADV
             INNER JOIN sinh_vien sv ON lop.LOP_MA = sv.LOP_MA
             INNER JOIN de_tai_gia_han gh ON sv.SV_MASV = gh.SV_MASV
             ORDER BY dv.DV_TENDV";
$khoa_result = $conn->query($khoa_sql);
$khoa_list = [];
while ($row = $khoa_result->fetch_assoc()) {
    $khoa_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý gia hạn đề tài - Research Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
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
        
        .extension-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s;
        }
        
        .extension-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }
        
        .extension-card.pending {
            border-left: 4px solid #ffc107;
        }
        
        .extension-card.approved {
            border-left: 4px solid #28a745;
        }
        
        .extension-card.rejected {
            border-left: 4px solid #dc3545;
        }
        
        .extension-card.overdue {
            border-left: 4px solid #ff6b6b;
            background-color: #fff5f5;
        }
        
        .btn-action {
            border-radius: 20px;
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
            margin: 0.125rem;
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        
        .priority-high {
            border-top: 3px solid #dc3545;
        }
        
        .priority-medium {
            border-top: 3px solid #ffc107;
        }
        
        .priority-low {
            border-top: 3px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include sidebar -->
            <?php include '../../include/research_header.php'; ?>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">
                                <i class="fas fa-clock me-2"></i>Quản lý gia hạn đề tài
                            </h1>
                            <p class="mb-0 opacity-75">Duyệt và quản lý các yêu cầu gia hạn từ sinh viên</p>
                        </div>
                        <div class="text-end">
                            <small>Quản lý: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></small>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-primary me-3">
                                    <i class="fas fa-list-alt"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1"><?php echo $stats['TONG_YEU_CAU']; ?></h5>
                                    <p class="card-text text-muted mb-0">Tổng yêu cầu</p>
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
                                    <h5 class="card-title mb-1"><?php echo $stats['CHO_DUYET']; ?></h5>
                                    <p class="card-text text-muted mb-0">Chờ duyệt</p>
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
                                    <h5 class="card-title mb-1"><?php echo $stats['DA_DUYET']; ?></h5>
                                    <p class="card-text text-muted mb-0">Đã duyệt</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="card-icon bg-danger me-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1"><?php echo $stats['QUA_HAN']; ?></h5>
                                    <p class="card-text text-muted mb-0">Quá hạn xử lý</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status">
                                <option value="">Tất cả</option>
                                <option value="Chờ duyệt" <?php echo $filter_status === 'Chờ duyệt' ? 'selected' : ''; ?>>Chờ duyệt</option>
                                <option value="Đã duyệt" <?php echo $filter_status === 'Đã duyệt' ? 'selected' : ''; ?>>Đã duyệt</option>
                                <option value="Từ chối" <?php echo $filter_status === 'Từ chối' ? 'selected' : ''; ?>>Từ chối</option>
                                <option value="Hủy" <?php echo $filter_status === 'Hủy' ? 'selected' : ''; ?>>Hủy</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Khoa</label>
                            <select class="form-select" name="khoa">
                                <option value="">Tất cả khoa</option>
                                <?php foreach ($khoa_list as $khoa): ?>
                                <option value="<?php echo $khoa['DV_MADV']; ?>" 
                                        <?php echo $filter_khoa === $khoa['DV_MADV'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($khoa['DV_TENDV']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($filter_search); ?>"
                                   placeholder="Mã đề tài, tên đề tài, tên sinh viên...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Lọc
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Extensions List -->
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Danh sách yêu cầu gia hạn
                            </h5>
                            <small class="text-muted">
                                Hiển thị <?php echo count($extensions); ?> / <?php echo $total_records; ?> yêu cầu
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($extensions) > 0): ?>
                            <?php foreach ($extensions as $extension): ?>
                                <?php
                                $card_class = 'extension-card';
                                $priority_class = '';
                                
                                if ($extension['GH_TRANGTHAI'] === 'Chờ duyệt') {
                                    $card_class .= ' pending';
                                    if ($extension['SO_NGAY_CHO'] > 7) {
                                        $card_class .= ' overdue';
                                        $priority_class = 'priority-high';
                                    } elseif ($extension['SO_NGAY_CHO'] > 3) {
                                        $priority_class = 'priority-medium';
                                    } else {
                                        $priority_class = 'priority-low';
                                    }
                                } elseif ($extension['GH_TRANGTHAI'] === 'Đã duyệt') {
                                    $card_class .= ' approved';
                                } elseif ($extension['GH_TRANGTHAI'] === 'Từ chối') {
                                    $card_class .= ' rejected';
                                }
                                ?>
                                
                                <div class="<?php echo $card_class . ' ' . $priority_class; ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-1 text-primary">
                                                        <?php echo htmlspecialchars($extension['DT_MADT']); ?>
                                                        <?php if ($extension['DT_TRE_TIENDO']): ?>
                                                            <span class="badge bg-warning ms-2">Trễ tiến độ</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <div class="text-end">
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
                                                        <?php if ($extension['GH_TRANGTHAI'] === 'Chờ duyệt' && $extension['SO_NGAY_CHO'] > 7): ?>
                                                            <span class="badge bg-danger ms-1">Quá hạn</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <h6 class="mb-2"><?php echo htmlspecialchars($extension['DT_TENDT']); ?></h6>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-6">
                                                        <small class="text-muted">Sinh viên:</small><br>
                                                        <strong><?php echo htmlspecialchars($extension['SV_HOTEN']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($extension['KHOA_TEN']); ?> - <?php echo htmlspecialchars($extension['LOP_TEN']); ?></small>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted">GVHD:</small><br>
                                                        <?php echo htmlspecialchars($extension['GV_HOTEN'] ?? 'Chưa có'); ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-4">
                                                        <small class="text-muted">Từ ngày:</small><br>
                                                        <strong><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_CU'])); ?></strong>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Đến ngày:</small><br>
                                                        <strong class="text-success"><?php echo date('d/m/Y', strtotime($extension['GH_NGAYHETHAN_MOI'])); ?></strong>
                                                    </div>
                                                    <div class="col-4">
                                                        <small class="text-muted">Gia hạn:</small><br>
                                                        <span class="badge bg-info"><?php echo $extension['GH_SOTHANGGIAHAN']; ?> tháng</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">Lý do:</small><br>
                                                    <div class="text-truncate" style="max-width: 500px;" 
                                                         title="<?php echo htmlspecialchars($extension['GH_LYDOYEUCAU']); ?>">
                                                        <?php echo htmlspecialchars(substr($extension['GH_LYDOYEUCAU'], 0, 100)); ?>
                                                        <?php if (strlen($extension['GH_LYDOYEUCAU']) > 100): ?>...<?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="text-end">
                                                    <div class="mb-2">
                                                        <small class="text-muted">Ngày yêu cầu:</small><br>
                                                        <?php echo date('d/m/Y H:i', strtotime($extension['GH_NGAYYEUCAU'])); ?><br>
                                                        <small class="text-muted"><?php echo $extension['SO_NGAY_CHO']; ?> ngày trước</small>
                                                    </div>
                                                    
                                                    <?php if ($extension['GH_NGUOIDUYET_HOTEN']): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted">Người xử lý:</small><br>
                                                        <small><?php echo htmlspecialchars($extension['GH_NGUOIDUYET_HOTEN']); ?></small>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="btn-group-vertical d-grid gap-1">
                                                        <button class="btn btn-sm btn-outline-primary btn-action" 
                                                                onclick="viewExtensionDetail(<?php echo $extension['GH_ID']; ?>)">
                                                            <i class="fas fa-eye me-1"></i>Chi tiết
                                                        </button>
                                                        
                                                        <?php if ($extension['GH_TRANGTHAI'] === 'Chờ duyệt'): ?>
                                                            <button class="btn btn-sm btn-success btn-action" 
                                                                    onclick="approveExtension(<?php echo $extension['GH_ID']; ?>, '<?php echo htmlspecialchars($extension['DT_TENDT']); ?>')">
                                                                <i class="fas fa-check me-1"></i>Duyệt
                                                            </button>
                                                            <button class="btn btn-sm btn-danger btn-action" 
                                                                    onclick="rejectExtension(<?php echo $extension['GH_ID']; ?>, '<?php echo htmlspecialchars($extension['DT_TENDT']); ?>')">
                                                                <i class="fas fa-times me-1"></i>Từ chối
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page-1); ?>&status=<?php echo urlencode($filter_status); ?>&khoa=<?php echo urlencode($filter_khoa); ?>&search=<?php echo urlencode($filter_search); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&khoa=<?php echo urlencode($filter_khoa); ?>&search=<?php echo urlencode($filter_search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page+1); ?>&status=<?php echo urlencode($filter_status); ?>&khoa=<?php echo urlencode($filter_khoa); ?>&search=<?php echo urlencode($filter_search); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Không có yêu cầu gia hạn nào</h5>
                                <p class="text-muted">Các yêu cầu gia hạn từ sinh viên sẽ hiển thị ở đây</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal duyệt gia hạn -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Duyệt yêu cầu gia hạn
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="extension_id" id="approveExtensionId">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Bạn có chắc chắn muốn duyệt yêu cầu gia hạn cho đề tài: 
                            <strong id="approveProjectName"></strong>?
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ghi chú (tùy chọn)</label>
                            <textarea class="form-control" name="ghi_chu" rows="3" 
                                      placeholder="Ghi chú thêm về quyết định duyệt..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Duyệt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal từ chối gia hạn -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Từ chối yêu cầu gia hạn
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="extension_id" id="rejectExtensionId">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Bạn có chắc chắn muốn từ chối yêu cầu gia hạn cho đề tài: 
                            <strong id="rejectProjectName"></strong>?
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Lý do từ chối <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="ly_do_tu_choi" rows="4" required
                                      placeholder="Vui lòng nêu rõ lý do từ chối để sinh viên hiểu và cải thiện..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Từ chối
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal chi tiết gia hạn -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>Chi tiết yêu cầu gia hạn
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded via AJAX -->
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
        function approveExtension(extensionId, projectName) {
            $('#approveExtensionId').val(extensionId);
            $('#approveProjectName').text(projectName);
            $('#approveModal').modal('show');
        }
        
        function rejectExtension(extensionId, projectName) {
            $('#rejectExtensionId').val(extensionId);
            $('#rejectProjectName').text(projectName);
            $('#rejectModal').modal('show');
        }
        
        function viewExtensionDetail(extensionId) {
            $.ajax({
                url: 'get_extension_detail_admin.php',
                type: 'GET',
                data: { id: extensionId },
                success: function(response) {
                    $('#detailContent').html(response);
                    $('#detailModal').modal('show');
                },
                error: function() {
                    alert('Không thể tải chi tiết yêu cầu!');
                }
            });
        }
        
        // Auto-refresh để cập nhật trạng thái mới
        setInterval(function() {
            // Chỉ refresh nếu có yêu cầu chờ duyệt
            const pendingCount = <?php echo $stats['CHO_DUYET']; ?>;
            if (pendingCount > 0) {
                // Soft refresh - chỉ reload nếu có thay đổi
                checkForUpdates();
            }
        }, 30000); // 30 seconds
        
        function checkForUpdates() {
            $.ajax({
                url: 'check_extension_updates.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.has_updates) {
                        // Hiển thị thông báo có cập nhật mới
                        showUpdateNotification();
                    }
                }
            });
        }
        
        function showUpdateNotification() {
            if (!$('#updateNotification').length) {
                $('body').append(`
                    <div id="updateNotification" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
                        <div class="toast show" role="alert">
                            <div class="toast-header bg-info text-white">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong class="me-auto">Cập nhật mới</strong>
                                <button type="button" class="btn-close btn-close-white" onclick="$('#updateNotification').remove()"></button>
                            </div>
                            <div class="toast-body">
                                Có yêu cầu gia hạn mới. <a href="#" onclick="location.reload()">Tải lại trang</a>
                            </div>
                        </div>
                    </div>
                `);
            }
        }
    </script>
</body>
</html>
