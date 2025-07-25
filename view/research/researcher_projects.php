<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\researcher_projects.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Kiểm tra tham số
$role = isset($_GET['role']) ? $_GET['role'] : 'teacher';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    // Redirect về trang danh sách nếu không có ID
    header('Location: manage_researchers.php');
    exit;
}

// Lấy thông tin nhà nghiên cứu
if ($role === 'teacher') {
    $sql = "SELECT gv.*, k.DV_TENDV
            FROM giang_vien gv 
            LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
            WHERE gv.GV_MAGV = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
} else {
    $sql = "SELECT sv.*, l.LOP_TEN, k.DV_TENDV
            FROM sinh_vien sv 
            LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
            LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
            WHERE sv.SV_MASV = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Không tìm thấy thông tin
    header('Location: manage_researchers.php');
    exit;
}

$researcher = $result->fetch_assoc();

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Lọc theo trạng thái
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Lấy danh sách đề tài
if ($role === 'teacher') {
    $projects_sql = "SELECT dt.*, ldt.LDT_TENLOAI
                    FROM de_tai_nghien_cuu dt
                    LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    WHERE dt.GV_MAGV = ?";
    
    // Thêm điều kiện lọc
    if (!empty($status_filter)) {
        $projects_sql .= " AND dt.DT_TRANGTHAI = ?";
    }
    
    $projects_sql .= " ORDER BY dt.DT_NGAYTAO DESC LIMIT ?, ?";
    
    $projects_stmt = $conn->prepare($projects_sql);
    
    if (!empty($status_filter)) {
        $projects_stmt->bind_param("ssii", $id, $status_filter, $offset, $items_per_page);
    } else {
        $projects_stmt->bind_param("sii", $id, $offset, $items_per_page);
    }
} else {
    $projects_sql = "SELECT dt.*, ldt.LDT_TENLOAI, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN
                    FROM chi_tiet_tham_gia ct
                    JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                    LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                    WHERE ct.SV_MASV = ?";
    
    // Thêm điều kiện lọc
    if (!empty($status_filter)) {
        $projects_sql .= " AND dt.DT_TRANGTHAI = ?";
    }
    
    $projects_sql .= " ORDER BY dt.DT_NGAYTAO DESC LIMIT ?, ?";
    
    $projects_stmt = $conn->prepare($projects_sql);
    
    if (!empty($status_filter)) {
        $projects_stmt->bind_param("ssii", $id, $status_filter, $offset, $items_per_page);
    } else {
        $projects_stmt->bind_param("sii", $id, $offset, $items_per_page);
    }
}

$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();
$projects = [];

while ($project = $projects_result->fetch_assoc()) {
    $projects[] = $project;
}

// Đếm tổng số đề tài để phân trang
if ($role === 'teacher') {
    $count_sql = "SELECT COUNT(*) as total FROM de_tai_nghien_cuu WHERE GV_MAGV = ?";
    
    if (!empty($status_filter)) {
        $count_sql .= " AND DT_TRANGTHAI = ?";
    }
    
    $count_stmt = $conn->prepare($count_sql);
    
    if (!empty($status_filter)) {
        $count_stmt->bind_param("ss", $id, $status_filter);
    } else {
        $count_stmt->bind_param("s", $id);
    }
} else {
    $count_sql = "SELECT COUNT(*) as total 
                 FROM chi_tiet_tham_gia ct
                 JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                 WHERE ct.SV_MASV = ?";
    
    if (!empty($status_filter)) {
        $count_sql .= " AND dt.DT_TRANGTHAI = ?";
    }
    
    $count_stmt = $conn->prepare($count_sql);
    
    if (!empty($status_filter)) {
        $count_stmt->bind_param("ss", $id, $status_filter);
    } else {
        $count_stmt->bind_param("s", $id);
    }
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách trạng thái
$status_sql = "SELECT DISTINCT DT_TRANGTHAI FROM de_tai_nghien_cuu ORDER BY DT_TRANGTHAI";
$status_result = $conn->query($status_sql);
$statuses = [];

while ($status = $status_result->fetch_assoc()) {
    $statuses[] = $status['DT_TRANGTHAI'];
}

// Set page title
$page_title = "Đề tài của " . ($role === 'teacher' ? "giảng viên: " : "sinh viên: ") . 
              ($role === 'teacher' ? $researcher['GV_HOGV'] . ' ' . $researcher['GV_TENGV'] : $researcher['SV_HOSV'] . ' ' . $researcher['SV_TENSV']);

// Include header
include '../../include/research_header.php';
?>

<!-- Content Wrapper -->
<div id="wrapper">
    <!-- Sidebar -->
    <?php include '../../include/new_research_sidebar.php'; ?>
    
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>
                <h1 class="h3 mb-0 text-gray-800 ml-2">
                    Đề tài của <?php echo $role === 'teacher' ? 'giảng viên' : 'sinh viên'; ?>
                </h1>
            </nav>
            
            <!-- Begin Page Content -->
            <div class="container-fluid">
                <!-- Researcher info card -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-1 text-center">
                                <div class="img-profile rounded-circle bg-primary text-white" style="width: 60px; height: 60px; font-size: 24px; line-height: 60px; margin: 0 auto;">
                                    <?php 
                                    if ($role === 'teacher') {
                                        echo mb_substr($researcher['GV_TENGV'], 0, 1, 'UTF-8');
                                    } else {
                                        echo mb_substr($researcher['SV_TENSV'], 0, 1, 'UTF-8');
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-lg-11">
                                <h4 class="font-weight-bold">
                                    <?php 
                                    if ($role === 'teacher') {
                                        echo htmlspecialchars($researcher['GV_HOGV'] . ' ' . $researcher['GV_TENGV']);
                                    } else {
                                        echo htmlspecialchars($researcher['SV_HOSV'] . ' ' . $researcher['SV_TENSV']);
                                    }
                                    ?>
                                    <span class="badge badge-<?php echo $role === 'teacher' ? 'primary' : 'success'; ?> ml-2">
                                        <?php echo $role === 'teacher' ? 'Giảng viên' : 'Sinh viên'; ?>
                                    </span>
                                </h4>
                                <p class="mb-0">
                                    <strong>Mã số:</strong> 
                                    <?php echo htmlspecialchars($role === 'teacher' ? $researcher['GV_MAGV'] : $researcher['SV_MASV']); ?>
                                    | <strong>Email:</strong> 
                                    <?php echo htmlspecialchars($role === 'teacher' ? $researcher['GV_EMAIL'] : $researcher['SV_EMAIL']); ?>
                                    | <strong>Đơn vị:</strong>
                                    <?php echo htmlspecialchars($researcher['DV_TENDV']); ?>
                                    <?php if ($role === 'student'): ?>
                                        | <strong>Lớp:</strong>
                                        <?php echo htmlspecialchars($researcher['LOP_TEN']); ?>
                                    <?php endif; ?>
                                </p>
                                <a href="researcher_details.php?role=<?php echo $role; ?>&id=<?php echo htmlspecialchars($id); ?>" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-user mr-1"></i> Xem hồ sơ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status filter -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Bộ lọc đề tài</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="form-inline">
                            <input type="hidden" name="role" value="<?php echo $role; ?>">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                            
                            <div class="form-group mr-2">
                                <label for="status" class="mr-2">Trạng thái:</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Tất cả trạng thái</option>
                                    <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search fa-sm"></i> Lọc
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Projects list -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Danh sách đề tài 
                            <?php if (!empty($status_filter)): ?>
                                - Trạng thái: <?php echo htmlspecialchars($status_filter); ?>
                            <?php endif; ?>
                        </h6>
                        <div class="text-right">
                            <span class="badge badge-primary"><?php echo $total_items; ?> đề tài</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($projects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Loại đề tài</th>
                                        <?php if ($role === 'student'): ?>
                                        <th>Giảng viên</th>
                                        <?php endif; ?>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Tác vụ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['DT_MADT']); ?></td>
                                        <td><?php echo htmlspecialchars($project['DT_TENDT']); ?></td>
                                        <td><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không xác định'); ?></td>
                                        <?php if ($role === 'student'): ?>
                                        <td><?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Không xác định'); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge badge-<?php 
                                                if ($project['DT_TRANGTHAI'] == 'Đã hoàn thành') echo 'success';
                                                elseif ($project['DT_TRANGTHAI'] == 'Đang tiến hành') echo 'primary';
                                                elseif ($project['DT_TRANGTHAI'] == 'Chờ phê duyệt') echo 'warning';
                                                else echo 'danger';
                                            ?>">
                                                <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($project['DT_NGAYTAO'])) {
                                                echo date('d/m/Y', strtotime($project['DT_NGAYTAO']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="view_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> Chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Phân trang -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?role=<?php echo $role; ?>&id=<?php echo htmlspecialchars($id); ?>&page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                        &laquo;
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $start_page + 4);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?role=' . $role . '&id=' . htmlspecialchars($id) . '&page=1' . (!empty($status_filter) ? '&status=' . urlencode($status_filter) : '') . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                        <a class="page-link" href="?role=' . $role . '&id=' . htmlspecialchars($id) . '&page=' . $i . (!empty($status_filter) ? '&status=' . urlencode($status_filter) : '') . '">' . $i . '</a>
                                    </li>';
                                }
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?role=' . $role . '&id=' . htmlspecialchars($id) . '&page=' . $total_pages . (!empty($status_filter) ? '&status=' . urlencode($status_filter) : '') . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?role=<?php echo $role; ?>&id=<?php echo htmlspecialchars($id); ?>&page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">
                                        &raquo;
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-gray-300 mb-3"></i>
                            <h4>Không có đề tài nào</h4>
                            <p>
                                Không tìm thấy đề tài nào
                                <?php if (!empty($status_filter)): ?>
                                với trạng thái "<?php echo htmlspecialchars($status_filter); ?>"
                                <?php endif; ?>
                                cho <?php echo $role === 'teacher' ? 'giảng viên' : 'sinh viên'; ?> này.
                            </p>
                            <?php if (!empty($status_filter)): ?>
                            <a href="?role=<?php echo $role; ?>&id=<?php echo htmlspecialchars($id); ?>" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Xem tất cả đề tài
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<?php
include '../../include/research_footer.php';
?>
