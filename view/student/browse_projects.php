<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\browse_projects.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy các tham số tìm kiếm từ URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$lecturer = isset($_GET['lecturer']) ? trim($_GET['lecturer']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Lấy danh sách loại đề tài cho bộ lọc
$category_query = "SELECT LDT_MA, LDT_TENLOAI FROM loai_de_tai ORDER BY LDT_TENLOAI";
$category_result = $conn->query($category_query);
$categories = [];
if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Lấy danh sách giảng viên cho bộ lọc
$lecturer_query = "SELECT GV_MAGV, CONCAT(GV_HOGV, ' ', GV_TENGV) AS GV_HOTEN 
                  FROM giang_vien 
                  ORDER BY GV_HOGV, GV_TENGV";
$lecturer_result = $conn->query($lecturer_query);
$lecturers = [];
if ($lecturer_result) {
    while ($row = $lecturer_result->fetch_assoc()) {
        $lecturers[] = $row;
    }
}

// Lấy ID sinh viên
$student_id = $_SESSION['user_id'];

// Xây dựng câu truy vấn tìm kiếm đề tài
$sql_projects = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_MOTA, dt.DT_TRANGTHAI, 
                hd.HD_NGAYBD, hd.HD_NGAYKT,
                CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
                ldt.LDT_TENLOAI
                FROM de_tai_nghien_cuu dt
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
                WHERE (dt.DT_TRANGTHAI = 'Chờ duyệt' OR dt.DT_TRANGTHAI = 'Đang thực hiện')
                AND NOT EXISTS (
                    SELECT 1 FROM chi_tiet_tham_gia cttg 
                    WHERE cttg.DT_MADT = dt.DT_MADT AND cttg.SV_MASV = ?
                )";

// Thêm các điều kiện tìm kiếm nếu có
$params = [$student_id];
$types = "s";

if (!empty($search)) {
    $sql_projects .= " AND (dt.DT_TENDT LIKE ? OR dt.DT_MOTA LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $sql_projects .= " AND dt.LDT_MA = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($lecturer)) {
    $sql_projects .= " AND dt.GV_MAGV = ?";
    $params[] = $lecturer;
    $types .= "s";
}

if (!empty($status)) {
    $sql_projects .= " AND dt.DT_TRANGTHAI = ?";
    $params[] = $status;
    $types .= "s";
}

// Thêm sắp xếp
// Sắp xếp theo ngày bắt đầu (từ mới nhất đến cũ nhất), đưa NULL xuống cuối
$sql_projects .= " ORDER BY CASE WHEN hd.HD_NGAYBD IS NULL THEN 1 ELSE 0 END, hd.HD_NGAYBD DESC, dt.DT_TENDT";
// Thực thi truy vấn
$stmt = $conn->prepare($sql_projects);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$projects = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm và đăng ký đề tài | Sinh viên</title>

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/student/manage_projects.css" rel="stylesheet">
    <style>
        .project-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
            height: 100%;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.06);
        }

        .project-card .card-title {
            font-weight: 600;
            color: #343a40;
        }

        .project-card .card-subtitle {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .project-card .card-text {
            font-size: 0.95rem;
            color: #6c757d;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .project-card .card-footer {
            background-color: rgba(0, 0, 0, 0.02);
            padding: 0.75rem 1.25rem;
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .search-input {
            border-radius: 50px;
            padding-left: 20px;
        }

        .search-button {
            border-radius: 50px;
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
        }

        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
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
                    <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-home mr-1"></i> Trang
                            chủ</a></li>
                    <li class="breadcrumb-item"><a href="student_manage_projects.php">Quản lý đề tài</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tìm kiếm đề tài</li>
                </ol>
            </nav>

            <h1 class="page-header mb-4"><i class="fas fa-search mr-2"></i>Tìm kiếm và đăng ký đề tài</h1>

            <!-- Thông báo -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Bộ lọc tìm kiếm -->
            <div class="filter-section">
                <form method="get" action="browse_projects.php">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control search-input"
                                    placeholder="Tìm kiếm đề tài..." value="<?php echo htmlspecialchars($search); ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary search-button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <select name="category" class="form-control">
                                        <option value="">-- Loại đề tài --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['LDT_MALOAI']; ?>" <?php echo ($category == $cat['LDT_MALOAI']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['LDT_TENLOAI']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <select name="lecturer" class="form-control">
                                        <option value="">-- Giảng viên --</option>
                                        <?php foreach ($lecturers as $lect): ?>
                                            <option value="<?php echo $lect['GV_MAGV']; ?>" <?php echo ($lecturer == $lect['GV_MAGV']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lect['GV_HOTEN']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <select name="status" class="form-control">
                                        <option value="">-- Trạng thái --</option>
                                        <option value="Chờ duyệt" <?php echo ($status == 'Chờ duyệt') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                        <option value="Đang thực hiện" <?php echo ($status == 'Đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
                            <a href="browse_projects.php" class="btn btn-secondary mr-2">
                                <i class="fas fa-redo-alt mr-1"></i> Đặt lại
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter mr-1"></i> Lọc kết quả
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Kết quả tìm kiếm -->
            <h5 class="mb-3">Tìm thấy <?php echo count($projects); ?> đề tài phù hợp</h5>

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
                                        echo htmlspecialchars(substr($description, 0, 150)) . (strlen($description) > 150 ? '...' : '');
                                        ?>
                                    </p>
                                    <?php if (!empty($project['HD_NGAYBD']) && !empty($project['HD_NGAYKT'])): ?>
                                        <p class="card-text text-muted small">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <?php
                                            echo date('d/m/Y', strtotime($project['HD_NGAYBD'])) . ' - ' .
                                                date('d/m/Y', strtotime($project['HD_NGAYKT']));
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between">
                                        <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>"
                                            class="btn btn-sm btn-info">
                                            <i class="fas fa-eye mr-1"></i> Xem chi tiết
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success btn-register-project"
                                            data-id="<?php echo $project['DT_MADT']; ?>"
                                            data-title="<?php echo htmlspecialchars($project['DT_TENDT']); ?>">
                                            <i class="fas fa-check mr-1"></i> Đăng ký
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>Không tìm thấy đề tài nào phù hợp với điều kiện tìm kiếm</p>
                    <a href="browse_projects.php" class="btn btn-outline-primary">
                        <i class="fas fa-sync-alt mr-1"></i> Xem tất cả đề tài
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal xác nhận đăng ký -->
    <div class="modal fade" id="registerConfirmModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Xác nhận đăng ký đề tài</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn đăng ký đề tài: <strong id="confirmProjectTitle"></strong>?</p>
                    <form id="registerProjectForm" action="process_register.php" method="post">
                        <input type="hidden" id="confirmProjectId" name="project_id" value="">
                        <div class="form-group">
                            <label for="roleSelect">Vai trò trong đề tài:</label>
                            <select class="form-control" id="roleSelect" name="role" required>
                                <option value="Chủ nhiệm">Chủ nhiệm đề tài</option>
                                <option value="Thành viên">Thành viên</option>
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Xác nhận đăng ký</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Helper function để xác định class cho badge trạng thái
        <?php
        function getStatusBadgeClass($status)
        {
            switch ($status) {
                case 'Chờ duyệt':
                    return 'badge-warning';
                case 'Đang thực hiện':
                    return 'badge-primary';
                case 'Đã hoàn thành':
                    return 'badge-success';
                case 'Tạm dừng':
                    return 'badge-info';
                case 'Đã hủy':
                    return 'badge-danger';
                default:
                    return 'badge-secondary';
            }
        }
        ?>

        $(document).ready(function () {
            // Khởi tạo tooltip
            $('[data-toggle="tooltip"]').tooltip();

            // Xử lý nút đăng ký
            $('.btn-register-project').click(function () {
                var projectId = $(this).data('id');
                var projectTitle = $(this).data('title');

                $('#confirmProjectId').val(projectId);
                $('#confirmProjectTitle').text(projectTitle);
                $('#registerConfirmModal').modal('show');
            });

            // Hiệu ứng khi trang tải xong
            $('.project-card').each(function (index) {
                $(this).css('opacity', 0).delay(50 * index).animate({
                    opacity: 1
                }, 300);
            });

            // Xử lý form đăng ký
            $('#registerProjectForm').on('submit', function (e) {
                var submitBtn = $(this).find('button[type="submit"]');
                submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang đăng ký...');
                submitBtn.prop('disabled', true);
            });
        });
    </script>
</body>

</html>