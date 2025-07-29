<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\manage_researchers.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Khởi tạo biến lọc và phân trang
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Tính statistics cho dashboard
$total_teachers_result = $conn->query("SELECT COUNT(*) as total FROM giang_vien WHERE 1=1");
$total_teachers = $total_teachers_result->fetch_assoc()['total'];

$total_students_result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE 1=1");
$total_students = $total_students_result->fetch_assoc()['total'];

$total_projects_result = $conn->query("SELECT COUNT(*) as total FROM de_tai_nghien_cuu WHERE 1=1");
$total_projects = $total_projects_result->fetch_assoc()['total'];

$total_faculties_result = $conn->query("SELECT COUNT(*) as total FROM khoa WHERE 1=1");
$total_faculties = $total_faculties_result->fetch_assoc()['total'];

// Xây dựng truy vấn SQL cho giảng viên
$teacher_sql = "SELECT gv.*, k.DV_TENDV, 
                COUNT(dt.DT_MADT) AS project_count 
                FROM giang_vien gv 
                LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
                LEFT JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                WHERE 1=1";

// Truy vấn SQL cho sinh viên
$student_sql = "SELECT sv.*, l.LOP_TEN, k.DV_TENDV, 
                COUNT(ct.DT_MADT) AS project_count 
                FROM sinh_vien sv 
                LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA 
                LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV 
                LEFT JOIN chi_tiet_tham_gia ct ON sv.SV_MASV = ct.SV_MASV
                WHERE 1=1";

// Điều kiện lọc khoa
if (!empty($faculty_filter)) {
    $teacher_sql .= " AND gv.DV_MADV = ?";
    $student_sql .= " AND l.DV_MADV = ?";
}

// Điều kiện tìm kiếm
if (!empty($search_term)) {
    $teacher_sql .= " AND (gv.GV_MAGV LIKE ? OR CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) LIKE ? OR gv.GV_EMAIL LIKE ?)";
    $student_sql .= " AND (sv.SV_MASV LIKE ? OR CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) LIKE ? OR sv.SV_EMAIL LIKE ?)";
}

// Thêm Group By và sắp xếp
$teacher_sql .= " GROUP BY gv.GV_MAGV ORDER BY project_count DESC, gv.GV_TENGV ASC";
$student_sql .= " GROUP BY sv.SV_MASV ORDER BY project_count DESC, sv.SV_TENSV ASC";

// Lấy tổng số bản ghi để phân trang
$teacher_count_sql = str_replace("SELECT gv.*, k.DV_TENDV, COUNT(dt.DT_MADT) AS project_count", 
                               "SELECT COUNT(DISTINCT gv.GV_MAGV) AS total", 
                               $teacher_sql);
$teacher_count_sql = preg_replace('/ORDER BY.*$/', '', $teacher_count_sql); // Loại bỏ ORDER BY

$student_count_sql = str_replace("SELECT sv.*, l.LOP_TEN, k.DV_TENDV, COUNT(ct.DT_MADT) AS project_count", 
                               "SELECT COUNT(DISTINCT sv.SV_MASV) AS total", 
                               $student_sql);
$student_count_sql = preg_replace('/ORDER BY.*$/', '', $student_count_sql); // Loại bỏ ORDER BY

// Chọn truy vấn dựa trên bộ lọc vai trò
if ($role_filter === 'student') {
    $main_sql = $student_sql . " LIMIT ?, ?";
    $count_sql = $student_count_sql;
} else {
    // Mặc định là giảng viên
    $main_sql = $teacher_sql . " LIMIT ?, ?";
    $count_sql = $teacher_count_sql;
    $role_filter = 'teacher'; // Đảm bảo role_filter có giá trị
}

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($main_sql);

// Xây dựng mảng tham số và kiểu dữ liệu
$types = '';
$params = array();

// Thêm các tham số lọc
if (!empty($faculty_filter)) {
    $types .= 's';
    $params[] = $faculty_filter;
}

if (!empty($search_term)) {
    $types .= 'sss';
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Thêm tham số phân trang
$types .= 'ii';
$params[] = $offset;
$params[] = $items_per_page;

// Gán tham số cho prepared statement
if (!empty($params)) {
    $ref_params = array();
    $ref_params[] = &$types;
    foreach ($params as $key => $value) {
        $ref_params[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $ref_params);
}

$stmt->execute();
$result = $stmt->get_result();
$researchers = array();

while ($row = $result->fetch_assoc()) {
    $researchers[] = $row;
}

// Đếm tổng số bản ghi để phân trang
$stmt_count = $conn->prepare($count_sql);

if (!empty($params)) {
    $params_count = array_slice($params, 0, -2); // Bỏ 2 tham số offset và limit
    $types_count = substr($types, 0, -2); // Bỏ 2 kiểu ii của offset và limit
    
    if (!empty($params_count)) {
        $ref_params_count = array();
        $ref_params_count[] = &$types_count;
        foreach ($params_count as $key => $value) {
            $ref_params_count[] = &$params_count[$key];
        }
        call_user_func_array(array($stmt_count, 'bind_param'), $ref_params_count);
    }
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row = $result_count->fetch_assoc();
$total_items = ($row && isset($row['total'])) ? $row['total'] : 0;
$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách các khoa
$faculty_sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV ASC";
$faculty_result = $conn->query($faculty_sql);
$faculties = array();
while ($row = $faculty_result->fetch_assoc()) {
    $faculties[] = $row;
}

?>

<?php
// Set page title
$page_title = "Quản lý nhà nghiên cứu | Hệ thống quản lý nghiên cứu";

// Additional CSS
$additional_css = '<link href="/NLNganh/assets/css/research/manage-researchers-enhanced.css" rel="stylesheet">';

// Include the research header
include '../../include/research_header.php';
?>

<!-- Sidebar is included in the header -->

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users-cog me-3"></i>
            Quản lý nhà nghiên cứu
        </h1>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Thêm nhà nghiên cứu
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter primary">
                <i class="fas fa-chalkboard-teacher"></i>
                <span class="count-numbers"><?= $total_teachers ?></span>
                <span class="count-name">Giảng viên</span>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter success">
                <i class="fas fa-user-graduate"></i>
                <span class="count-numbers"><?= $total_students ?></span>
                <span class="count-name">Sinh viên</span>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter info">
                <i class="fas fa-folder-open"></i>
                <span class="count-numbers"><?= $total_projects ?></span>
                <span class="count-name">Đề tài</span>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card card-counter warning">
                <i class="fas fa-university"></i>
                <span class="count-numbers"><?= $total_faculties ?></span>
                <span class="count-name">Khoa/Đơn vị</span>
            </div>
        </div>
    </div>

    <!-- Filter and Search Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Bộ lọc và tìm kiếm
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="role" class="form-label fw-bold"><i class="fas fa-user-tag me-1"></i>Vai trò</label>
                        <select class="form-select" id="role" name="role" onchange="this.form.submit()">
                            <option value="teacher" <?php echo $role_filter == 'teacher' ? 'selected' : ''; ?>>Giảng viên</option>
                            <option value="student" <?php echo $role_filter == 'student' ? 'selected' : ''; ?>>Sinh viên</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="faculty" class="form-label fw-bold"><i class="fas fa-university me-1"></i>Khoa/Đơn vị</label>
                        <select class="form-select" id="faculty" name="faculty">
                            <option value="">Tất cả khoa</option>
                            <?php foreach ($faculties as $faculty): ?>
                            <option value="<?php echo htmlspecialchars($faculty['DV_MADV']); ?>" <?php echo $faculty_filter == $faculty['DV_MADV'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($faculty['DV_TENDV']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <label for="search" class="form-label fw-bold"><i class="fas fa-search me-1"></i>Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Mã, tên, email..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-lg-2 col-md-12 d-flex">
                        <button type="submit" class="btn btn-primary w-100 me-2"><i class="fas fa-search"></i></button>
                        <a href="manage_researchers.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Role Tabs -->
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?php echo $role_filter == 'teacher' ? 'active' : ''; ?>" href="?role=teacher&faculty=<?php echo urlencode($faculty_filter); ?>&search=<?php echo urlencode($search_term); ?>">
                <i class="fas fa-chalkboard-teacher me-2"></i>Giảng viên
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $role_filter == 'student' ? 'active' : ''; ?>" href="?role=student&faculty=<?php echo urlencode($faculty_filter); ?>&search=<?php echo urlencode($search_term); ?>">
                <i class="fas fa-user-graduate me-2"></i>Sinh viên
            </a>
        </li>
    </ul>

    <!-- Researchers List -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Danh sách <?php echo $role_filter == 'teacher' ? 'giảng viên' : 'sinh viên'; ?>
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_items; ?> kết quả</span>
                </h5>
                <div class="btn-group">
                    <button class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Xuất Excel
                    </button>
                    <button class="btn btn-info btn-sm">
                        <i class="fas fa-print me-1"></i>In danh sách
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (count($researchers) > 0): ?>
                    <?php foreach ($researchers as $researcher): ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="researcher-card">
                                <div class="card-body text-center">
                                    <div class="researcher-avatar mb-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h5 class="researcher-name">
                                        <?php echo htmlspecialchars($role_filter == 'teacher' ? ($researcher['GV_HOGV'] . ' ' . $researcher['GV_TENGV']) : ($researcher['SV_HOSV'] . ' ' . $researcher['SV_TENSV'])); ?>
                                    </h5>
                                    <p class="researcher-meta">
                                        <?php echo htmlspecialchars($role_filter == 'teacher' ? $researcher['GV_MAGV'] : $researcher['SV_MASV']); ?>
                                    </p>
                                    <p class="researcher-meta">
                                        <i class="fas fa-university me-1"></i>
                                        <?php echo htmlspecialchars($researcher['DV_TENDV'] ?? 'Chưa có khoa'); ?>
                                    </p>
                                    <span class="role-badge <?php echo $role_filter == 'teacher' ? 'role-teacher' : 'role-student'; ?>">
                                        <?php echo $role_filter == 'teacher' ? 'Giảng viên' : 'Sinh viên'; ?>
                                    </span>
                                </div>
                                <div class="researcher-stats text-center">
                                    <div class="row">
                                        <div class="col">
                                            <div class="fw-bold"><?php echo $researcher['project_count']; ?></div>
                                            <div class="small text-muted">Đề tài</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-center bg-light">
                                    <a href="#" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="empty-state py-5">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Không tìm thấy kết quả</h5>
                            <p>Vui lòng thử lại với bộ lọc khác.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $role_filter; ?>&faculty=<?php echo urlencode($faculty_filter); ?>&search=<?php echo urlencode($search_term); ?>">
                        <i class="fas fa-chevron-left"></i> Trước
                    </a>
                </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1&role=' . $role_filter . '&faculty=' . urlencode($faculty_filter) . '&search=' . urlencode($search_term) . '">1</a></li>';
                if ($start_page > 2) {
                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                    <a class="page-link" href="?page=' . $i . '&role=' . $role_filter . '&faculty=' . urlencode($faculty_filter) . '&search=' . urlencode($search_term) . '">' . $i . '</a>
                </li>';
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&role=' . $role_filter . '&faculty=' . urlencode($faculty_filter) . '&search=' . urlencode($search_term) . '">' . $total_pages . '</a></li>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $role_filter; ?>&faculty=<?php echo urlencode($faculty_filter); ?>&search=<?php echo urlencode($search_term); ?>">
                        Tiếp <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>
<!-- /.container-fluid -->

<?php
// Include footer
include '../../include/research_footer.php';
?>
