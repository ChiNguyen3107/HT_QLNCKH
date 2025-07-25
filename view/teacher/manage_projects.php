<?php
// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên hiện tại
$teacher_id = $_SESSION['user_id'];

// Khởi tạo biến lọc
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$scope_filter = isset($_GET['scope']) ? $_GET['scope'] : 'my'; // Mặc định chỉ hiển thị đề tài của giảng viên

// Xây dựng truy vấn SQL với điều kiện lọc
// Sửa gv.GV_HOTEN thành CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) để ghép họ và tên
$sql = "SELECT dt.*, ldt.LDT_TENLOAI, lvnc.LVNC_TEN, lvut.LVUT_TEN, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as gv_ten 
        FROM de_tai_nghien_cuu dt 
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV";

// Thêm điều kiện lọc theo phạm vi
if ($scope_filter == 'my') {
    $sql .= " WHERE dt.GV_MAGV = ?";
} else {
    $sql .= " WHERE 1=1"; // 1=1 luôn đúng, để dễ dàng thêm các điều kiện AND sau này
}

// Thêm điều kiện lọc theo trạng thái nếu có
if (!empty($status_filter)) {
    $sql .= " AND dt.DT_TRANGTHAI = ?";
}

// Thêm điều kiện lọc theo loại đề tài nếu có
if (!empty($type_filter)) {
    $sql .= " AND dt.LDT_MA = ?";
}

// Thêm điều kiện tìm kiếm nếu có
if (!empty($search_term)) {
    $sql .= " AND (dt.DT_TENDT LIKE ? OR dt.DT_MADT LIKE ?)";
}

$sql .= " ORDER BY dt.DT_MADT DESC";

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị truy vấn: " . $conn->error);
}

// Gán giá trị cho các tham số
$param_types = ""; // Chuỗi các loại tham số
$param_values = array();

// Chỉ thêm điều kiện giảng viên nếu phạm vi là 'my'
if ($scope_filter == 'my') {
    $param_types .= "s";
    $param_values[] = $teacher_id;
}

if (!empty($status_filter)) {
    $param_types .= "s";
    $param_values[] = $status_filter;
}

if (!empty($type_filter)) {
    $param_types .= "s";
    $param_values[] = $type_filter;
}

if (!empty($search_term)) {
    $param_types .= "ss";
    $search_param = "%{$search_term}%";
    $param_values[] = $search_param;
    $param_values[] = $search_param;
}

// Sử dụng call_user_func_array để truyền các tham số động cho bind_param
if (!empty($param_values)) {
    $refs = array();
    foreach ($param_values as $key => $value) {
        $refs[$key] = &$param_values[$key];
    }
    array_unshift($refs, $param_types);
    call_user_func_array(array($stmt, 'bind_param'), $refs);
}

$stmt->execute();
$result = $stmt->get_result();

// Lấy danh sách loại đề tài để hiển thị trong bộ lọc
$types_query = "SELECT * FROM loai_de_tai ORDER BY LDT_TENLOAI ASC";
$types_result = $conn->query($types_query);

// Hàm lấy số lượng sinh viên tham gia đề tài
function getStudentCount($conn, $project_id) {
    $sql = "SELECT COUNT(*) as count FROM chi_tiet_tham_gia WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Hàm lấy số lượng báo cáo của đề tài
function getReportCount($conn, $project_id) {
    $sql = "SELECT COUNT(*) as count FROM bao_cao WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Hàm lấy thông tin hợp đồng của đề tài
function getContractInfo($conn, $project_id) {
    $sql = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Hàm lấy tiến độ mới nhất của đề tài
function getLatestProgress($conn, $project_id) {
    $sql = "SELECT * FROM tien_do_de_tai WHERE DT_MADT = ? ORDER BY TDDT_NGAYCAPNHAT DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Xử lý thông báo thành công hoặc lỗi
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đề tài | Giảng viên</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    
    <!-- Custom fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap CSS từ CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS từ CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS từ CDN -->
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
        .project-card {
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        
        .filter-section {
            background-color: #f8f9fc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .project-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .progress {
            height: 10px;
        }
        
        /* Thêm style cho giảng viên hướng dẫn */
        .project-advisor {
            display: inline-block;
            padding: 3px 8px;
            background-color: #e8f4fe;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #4e73df;
            margin-top: 5px;
        }
        
        /* Style cho highlight đề tài của bạn */
        .my-project {
            border-left: 4px solid #4e73df;
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include '../../include/teacher_sidebar.php'; ?>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid mt-4">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-project-diagram mr-2"></i>Quản lý đề tài nghiên cứu khoa học
                        </h1>
                        <a href="create_project.php" class="btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i>Tạo đề tài mới
                        </a>
                    </div>
                    
                    <!-- Hiển thị thông báo nếu có -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filter Section -->
                    <div class="filter-section mb-4">
                        <form method="get" action="" id="filterForm" class="row">
                            <!-- Thêm bộ lọc phạm vi -->
                            <div class="col-md-2 mb-2">
                                <label for="scope">Phạm vi đề tài:</label>
                                <select name="scope" id="scope" class="form-control form-control-sm" onchange="this.form.submit()">
                                    <option value="my" <?php echo ($scope_filter == 'my') ? 'selected' : ''; ?>>Đề tài của tôi</option>
                                    <option value="all" <?php echo ($scope_filter == 'all') ? 'selected' : ''; ?>>Tất cả đề tài</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2 mb-2">
                                <label for="status">Trạng thái:</label>
                                <select name="status" id="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="Chờ duyệt" <?php echo ($status_filter == 'Chờ duyệt') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                    <option value="Đang thực hiện" <?php echo ($status_filter == 'Đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                                    <option value="Đã hoàn thành" <?php echo ($status_filter == 'Đã hoàn thành') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                    <option value="Tạm dừng" <?php echo ($status_filter == 'Tạm dừng') ? 'selected' : ''; ?>>Tạm dừng</option>
                                    <option value="Đã hủy" <?php echo ($status_filter == 'Đã hủy') ? 'selected' : ''; ?>>Đã hủy</option>
                                    <option value="Đang xử lý" <?php echo ($status_filter == 'Đang xử lý') ? 'selected' : ''; ?>>Đang xử lý</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-2">
                                <label for="type">Loại đề tài:</label>
                                <select name="type" id="type" class="form-control form-control-sm" onchange="this.form.submit()">
                                    <option value="">Tất cả loại đề tài</option>
                                    <?php if ($types_result && $types_result->num_rows > 0): ?>
                                        <?php while ($type = $types_result->fetch_assoc()): ?>
                                            <option value="<?php echo $type['LDT_MA']; ?>" <?php echo ($type_filter == $type['LDT_MA']) ? 'selected' : ''; ?>>
                                                <?php echo $type['LDT_TENLOAI']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-2">
                                <label for="search">Tìm kiếm:</label>
                                <div class="input-group">
                                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Tên đề tài hoặc mã đề tài" value="<?php echo $search_term; ?>">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-search fa-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <a href="manage_projects.php" class="btn btn-sm btn-secondary btn-block">
                                    <i class="fas fa-sync-alt mr-1"></i>Đặt lại
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Thống kê nhanh số lượng -->
                    <div class="mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body py-2">
                                <p class="mb-0 text-primary">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Đang hiển thị: <strong><?php echo $result->num_rows; ?></strong> đề tài
                                    <?php if ($scope_filter == 'all'): ?>
                                    (trong tổng số đề tài của tất cả giảng viên)
                                    <?php else: ?>
                                    (trong tổng số đề tài của bạn)
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Projects List -->
                    <div class="row">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($project = $result->fetch_assoc()) {
                                // Lấy thông tin bổ sung
                                $student_count = getStudentCount($conn, $project['DT_MADT']);
                                $report_count = getReportCount($conn, $project['DT_MADT']);
                                $contract_info = getContractInfo($conn, $project['DT_MADT']);
                                $latest_progress = getLatestProgress($conn, $project['DT_MADT']);
                                $progress_percent = $latest_progress ? $latest_progress['TDDT_PHANTRAMHOANTHANH'] : 0;
                                
                                // Kiểm tra có phải đề tài của giảng viên đang đăng nhập không
                                $is_my_project = ($project['GV_MAGV'] == $teacher_id);
                                
                                // Xác định màu cho badge trạng thái
                                $status_class = '';
                                switch ($project['DT_TRANGTHAI']) {
                                    case 'Chờ duyệt':
                                        $status_class = 'badge-warning';
                                        break;
                                    case 'Đang thực hiện':
                                        $status_class = 'badge-primary';
                                        break;
                                    case 'Đã hoàn thành':
                                        $status_class = 'badge-success';
                                        break;
                                    case 'Tạm dừng':
                                        $status_class = 'badge-info';
                                        break;
                                    case 'Đã hủy':
                                        $status_class = 'badge-danger';
                                        break;
                                    case 'Đang xử lý':
                                        $status_class = 'badge-secondary';
                                        break;
                                    default:
                                        $status_class = 'badge-dark';
                                }
                                ?>
                                <div class="col-xl-4 col-md-6 mb-4">
                                    <div class="card project-card h-100 shadow-sm <?php echo $is_my_project ? 'my-project' : ''; ?>">
                                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                            <span class="badge <?php echo $status_class; ?> status-badge"><?php echo $project['DT_TRANGTHAI']; ?></span>
                                            <span class="text-muted small"><?php echo $project['LDT_TENLOAI']; ?></span>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>" class="text-decoration-none text-primary">
                                                    <?php echo $project['DT_TENDT']; ?>
                                                </a>
                                            </h5>
                                            <p class="card-text text-muted small mb-2">Mã đề tài: <?php echo $project['DT_MADT']; ?></p>
                                            
                                            <?php if (!$is_my_project && $scope_filter == 'all'): ?>
                                            <div class="project-advisor mb-2">
                                                <i class="fas fa-user-tie mr-1"></i> <?php echo $project['gv_ten']; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-3">
                                                <span class="project-meta mr-3"><i class="fas fa-users mr-1"></i> <?php echo $student_count; ?> sinh viên</span>
                                                <span class="project-meta mr-3"><i class="fas fa-file-alt mr-1"></i> <?php echo $report_count; ?> báo cáo</span>
                                            </div>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small">Tiến độ:</span>
                                                    <span class="small font-weight-bold"><?php echo $progress_percent; ?>%</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percent; ?>%" 
                                                        aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <p class="card-text mb-0 small text-muted">Lĩnh vực: 
                                                            <span class="text-dark"><?php echo $project['LVNC_TEN']; ?></span>
                                                        </p>
                                                        <p class="card-text small text-muted">Ưu tiên: 
                                                            <span class="text-dark"><?php echo $project['LVUT_TEN']; ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between">
                                                <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-info-circle mr-1"></i>Chi tiết
                                                </a>
                                                <?php if ($is_my_project): ?>
                                                <div class="btn-group">
                                                    <a href="edit_project.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage_students.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-user-graduate"></i>
                                                    </a>
                                                    <a href="project_reports.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                </div>
                                                <?php else: ?>
                                                <!-- Đối với đề tài của giảng viên khác, chỉ hiển thị nút chi tiết -->
                                                <div class="btn-group">
                                                    <span class="badge badge-light">
                                                        <i class="fas fa-eye"></i> Chỉ xem
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                        } else {
                            echo '<div class="col-12"><div class="alert alert-info">Không tìm thấy đề tài nào phù hợp với điều kiện lọc. <a href="manage_projects.php">Xóa bộ lọc</a> để hiển thị tất cả đề tài.</div></div>';
                        }
                        ?>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Hệ thống quản lý nghiên cứu khoa học &copy; <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    
    <!-- SB Admin 2 JS từ CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>
    
    <!-- DataTables JS từ CDN -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Tự động ẩn thông báo sau 5 giây
            setTimeout(function() {
                $('.alert-dismissible').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>