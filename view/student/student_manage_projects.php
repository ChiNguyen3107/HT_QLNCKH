<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\student_manage_projects.php

// Include session và kiểm tra vai trò
include '../../include/session.php';
checkStudentRole();

// Kết nối database
include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thông tin đề tài nghiên cứu của sinh viên từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_MOTA, dt.DT_TRANGTHAI,
               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
               cttg.CTTG_VAITRO
        FROM de_tai_nghien_cuu dt
        JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        WHERE cttg.SV_MASV = ?";
        
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đề tài nghiên cứu | Sinh viên</title>
    
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/student/manage_projects.css" rel="stylesheet">
</head>
<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-home mr-1"></i> Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý đề tài</li>
                </ol>
            </nav>
            
            <h1 class="page-header mb-4"><i class="fas fa-project-diagram mr-2"></i>Quản lý đề tài nghiên cứu</h1>
            
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
            
            <!-- Danh sách đề tài đã đăng ký -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0"><i class="fas fa-clipboard-list mr-2"></i>Đề tài của tôi</h5>
                    <a href="browse_projects.php" class="btn btn-sm btn-success">
                        <i class="fas fa-plus mr-1"></i> Tìm đề tài mới
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered project-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mã đề tài</th>
                                    <th>Tên đề tài</th>
                                    <th>Giảng viên hướng dẫn</th>
                                    <th>Vai trò</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($projects) > 0): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <?php 
                                        // Xác định class cho badge trạng thái
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
                                            default:
                                                $status_class = 'badge-secondary';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $project['DT_MADT']; ?></td>
                                            <td><?php echo $project['DT_TENDT']; ?></td>
                                            <td><?php echo $project['GV_HOTEN'] ?: 'Chưa có GVHD'; ?></td>
                                            <td><?php echo $project['CTTG_VAITRO']; ?></td>
                                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $project['DT_TRANGTHAI']; ?></span></td>
                                            <td>
                                                <div class="btn-group-sm">
                                                    <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-info mb-1" 
                                                       data-toggle="tooltip" title="Xem chi tiết đề tài">
                                                        <i class="fas fa-eye"></i> Xem
                                                    </a>
                                                    <?php if ($project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                                    <a href="submit_report.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-primary mb-1"
                                                       data-toggle="tooltip" title="Nộp báo cáo tiến độ">
                                                        <i class="fas fa-file-upload"></i> Nộp báo cáo
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard"></i>
                                                <p>Bạn chưa đăng ký đề tài nghiên cứu nào</p>
                                                <a href="browse_projects.php" class="btn btn-primary btn-sm mt-2">
                                                    <i class="fas fa-search mr-1"></i>Tìm đề tài ngay
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Danh sách đề tài có thể đăng ký -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0"><i class="fas fa-search mr-2"></i>Đề tài gợi ý</h5>
                    <div class="position-relative">
                        <input type="text" id="searchProject" class="form-control form-control-sm d-inline-block" 
                               style="width: 200px;" placeholder="Tìm kiếm đề tài...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered project-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mã đề tài</th>
                                    <th>Tên đề tài</th>
                                    <th>Giảng viên hướng dẫn</th>
                                    <th>Loại đề tài</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="suggestedProjects">
                                <!-- Sẽ được điền bằng JavaScript -->
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Đang tải...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal xác nhận đăng ký -->
    <div class="modal fade" id="registerConfirmModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
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
    
    <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/student/student_manage_projects.js"></script>
</body>
</html>