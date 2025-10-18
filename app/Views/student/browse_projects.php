<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\browse_projects.php

// Include session check and necessary files
include '../../include/session.php';
include '../../include/connect.php';

// Check if user is logged in
checkStudentRole();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : ''; // Removed default status filter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Lấy danh sách khoa
$dept_query = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV";
$dept_result = $conn->query($dept_query);
$departments = array();

if ($dept_result && $dept_result->num_rows > 0) {
    while($row = $dept_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Lấy danh sách loại đề tài với xử lý lỗi
$project_types = array();
$has_project_types = false;

try {
    $type_query = "SELECT LDT_MA, LDT_TENLOAI FROM loai_de_tai ORDER BY LDT_TENLOAI";
    $type_result = $conn->query($type_query);
    
    if ($type_result && $type_result->num_rows > 0) {
        $has_project_types = true;
        while ($row = $type_result->fetch_assoc()) {
            $project_types[] = $row;
        }
    }
} catch (Exception $e) {
    // Log error if needed
    error_log("Lỗi khi lấy loại đề tài: " . $e->getMessage());
}

// Xây dựng câu truy vấn dựa trên các bộ lọc
$query = "SELECT dt.*, 
          CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
          IFNULL(ldt.LDT_TENLOAI, 'Chưa phân loại') as LDT_TENLOAI
          FROM de_tai_nghien_cuu dt
          LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
          LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
          WHERE 1=1";

// Thêm điều kiện tìm kiếm
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (dt.DT_TENDT LIKE '%$search%' OR dt.DT_MOTA LIKE '%$search%')";
}

// Lọc theo khoa
if (!empty($department)) {
    $department = $conn->real_escape_string($department);
    $query .= " AND gv.DV_MADV = '$department'";
}

// Lọc theo loại đề tài
if (!empty($type)) {
    $type = $conn->real_escape_string($type);
    $query .= " AND dt.LDT_MA = '$type'";
}

// Lọc theo trạng thái
if (!empty($status)) {
    $status = $conn->real_escape_string($status);
    $query .= " AND dt.DT_TRANGTHAI = '$status'";
}

// Sắp xếp kết quả
switch ($sort) {
    case 'newest':
        $query .= " ORDER BY dt.DT_MADT DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY dt.DT_MADT ASC";
        break;
    case 'name_asc':
        $query .= " ORDER BY dt.DT_TENDT ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY dt.DT_TENDT DESC";
        break;
    default:
        $query .= " ORDER BY dt.DT_MADT DESC";
}

// Thực hiện truy vấn
$result = $conn->query($query);
$projects = array();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Helper function to get the badge class for different statuses
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Đã hoàn thành':
            return 'badge-success';
        case 'Đang thực hiện':
            return 'badge-info';
        case 'Chờ duyệt':
            return 'badge-warning';
        case 'Đã hủy':
            return 'badge-danger';
        case 'Tạm dừng':
            return 'badge-secondary';
        default:
            return 'badge-secondary';
    }
}

// Helper function to truncate text
function truncateText($text, $length = 100) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm đề tài | Hệ thống quản lý NCKH</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="/NLNganh/assets/css/styles.css">
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .project-card {
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .badge {
            font-size: 85%;
            margin-right: 5px;
            padding: 5px 8px;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .card-subtitle {
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .pagination-container {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="student_dashboard.php">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tìm kiếm đề tài</li>
                </ol>
            </nav>
            
            <!-- Page header -->
            <div class="page-header mb-4">
                <h1 class="page-title">Tìm kiếm đề tài nghiên cứu</h1>
                <p class="text-muted">Tìm kiếm và xem thông tin đề tài nghiên cứu khoa học</p>
            </div>

            <!-- Filter section -->
            <div class="filter-section">
                <form method="get" action="browse_projects.php">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search">Tìm kiếm:</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Nhập từ khóa..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="department">Khoa/Đơn vị:</label>
                                <select class="form-control" id="department" name="department">
                                    <option value="">Tất cả khoa</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['DV_MADV']; ?>" <?php echo ($department == $dept['DV_MADV']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['DV_TENDV']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="projectType">Loại đề tài:</label>
                                <select class="form-control" id="projectType" name="type">
                                    <option value="">Tất cả loại</option>
                                    <?php if ($has_project_types): ?>
                                        <?php foreach ($project_types as $pt): ?>
                                            <option value="<?php echo $pt['LDT_MA']; ?>" <?php echo ($type == $pt['LDT_MA']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pt['LDT_TENLOAI']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Không thể tải danh sách loại đề tài</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">Trạng thái:</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="Đang thực hiện" <?php echo ($status == 'Đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                                    <option value="Đã hoàn thành" <?php echo ($status == 'Đã hoàn thành') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                    <option value="Chờ duyệt" <?php echo ($status == 'Chờ duyệt') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="sort">Sắp xếp theo:</label>
                                <select class="form-control" id="sort" name="sort">
                                    <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                                    <option value="oldest" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Cũ nhất</option>
                                    <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Tên (A-Z)</option>
                                    <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Tên (Z-A)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-filter mr-1"></i> Lọc kết quả
                            </button>
                            <a href="browse_projects.php" class="btn btn-secondary">
                                <i class="fas fa-sync-alt mr-1"></i> Đặt lại
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Kết quả tìm kiếm -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="m-0">Kết quả tìm kiếm</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Tìm thấy <?php echo count($projects); ?> đề tài phù hợp</p>

                    <?php if (count($projects) > 0): ?>
                        <div class="row">
                            <?php foreach ($projects as $project): ?>
                                <div class="col-md-6 col-xl-4 d-flex">
                                    <div class="card project-card w-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                <i class="fas fa-user-tie mr-1"></i>
                                                <?php echo $project['GV_HOTEN'] ?: 'Chưa có GVHD'; ?>
                                            </h6>
                                            <div class="mb-2">
                                                <span class="badge <?php echo getStatusBadgeClass($project['DT_TRANGTHAI']); ?>">
                                                    <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                                </span>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($project['LDT_TENLOAI']); ?>
                                                </span>
                                            </div>
                                            <p class="card-text">
                                                <?php 
                                                $description = !empty($project['DT_MOTA']) ? $project['DT_MOTA'] : 'Không có mô tả';
                                                echo truncateText($description, 150); 
                                                ?>
                                            </p>
                                            <a href="project_details.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-info-circle mr-1"></i> Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> Không tìm thấy đề tài nào phù hợp với điều kiện tìm kiếm.
                            <?php if (!$has_project_types): ?>
                            <br><small>Lưu ý: Hệ thống không thể tải danh sách loại đề tài. Kết quả có thể không đầy đủ.</small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if (count($projects) > 0): ?>
            <div class="pagination-container d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item disabled"><a class="page-link" href="#">Trước</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item disabled"><a class="page-link" href="#">Sau</a></li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>


    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Hiển thị tooltip
            $('[data-toggle="tooltip"]').tooltip();
            
            // Xử lý sự kiện nhấp vào thẻ card để chuyển đến trang chi tiết
            $('.project-card').css('cursor', 'pointer').click(function(e) {
                // Chỉ kích hoạt khi nhấp vào card, không phải vào các nút
                if (!$(e.target).is('a') && !$(e.target).is('button') && !$(e.target).is('i')) {
                    const url = $(this).find('a.btn-primary').attr('href');
                    if (url) {
                        window.location.href = url;
                    }
                }
            });
        });
    </script>
</body>
</html>