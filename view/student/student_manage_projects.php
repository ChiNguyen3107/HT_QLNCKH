<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\student_manage_projects.php

// Thêm code để hiển thị lỗi khi phát triển
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Include session và kiểm tra vai trò
include '../../include/session.php';
// Thêm dòng debug nếu cần
// echo '<pre>DEBUG SESSION: '; print_r($_SESSION); echo '</pre>';

// Kiểm tra quyền sinh viên
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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="student_dashboard.php">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý đề tài</li>
                </ol>
            </nav>
            
            <h1 class="page-header mb-4">Quản lý đề tài nghiên cứu</h1>
            
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
                        <table class="table table-hover table-bordered">
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
                                            <td><?php echo $project['GV_HOTEN']; ?></td>
                                            <td><?php echo $project['CTTG_VAITRO']; ?></td>
                                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $project['DT_TRANGTHAI']; ?></span></td>
                                            <td>
                                                <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                                <?php if ($project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                                <a href="submit_report.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-file-upload"></i> Nộp báo cáo
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Bạn chưa đăng ký đề tài nghiên cứu nào</td>
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
                    <div>
                        <input type="text" id="searchProject" class="form-control form-control-sm d-inline-block mr-2" style="width: 200px;" placeholder="Tìm kiếm đề tài...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
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
                                <!-- Sẽ được điền bằng AJAX -->
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Hàm tải danh sách đề tài gợi ý
        function loadSuggestedProjects(search = '') {
            $.ajax({
                url: 'get_suggested_projects.php',
                type: 'GET',
                data: { search: search },
                dataType: 'json',
                success: function(data) {
                    var html = '';
                    if (data.length > 0) {
                        $.each(data, function(index, project) {
                            var statusClass = '';
                            switch (project.DT_TRANGTHAI) {
                                case 'Chờ duyệt': statusClass = 'badge-warning'; break;
                                case 'Đang thực hiện': statusClass = 'badge-primary'; break;
                                case 'Đã hoàn thành': statusClass = 'badge-success'; break;
                                case 'Tạm dừng': statusClass = 'badge-info'; break;
                                case 'Đã hủy': statusClass = 'badge-danger'; break;
                                default: statusClass = 'badge-secondary';
                            }
                            
                            html += '<tr>' +
                                '<td>' + project.DT_MADT + '</td>' +
                                '<td>' + project.DT_TENDT + '</td>' +
                                '<td>' + project.GV_HOTEN + '</td>' +
                                '<td>' + project.LDT_TENLOAI + '</td>' +
                                '<td><span class="badge ' + statusClass + '">' + project.DT_TRANGTHAI + '</span></td>' +
                                '<td>' +
                                    '<a href="view_project.php?id=' + project.DT_MADT + '" class="btn btn-sm btn-info">' +
                                    '<i class="fas fa-eye"></i> Xem</a> ' +
                                    '<a href="register_project.php?id=' + project.DT_MADT + '" class="btn btn-sm btn-success">' +
                                    '<i class="fas fa-check"></i> Đăng ký</a>' +
                                '</td>' +
                            '</tr>';
                        });
                    } else {
                        html = '<tr><td colspan="6" class="text-center">Không có đề tài nào phù hợp</td></tr>';
                    }
                    $('#suggestedProjects').html(html);
                },
                error: function() {
                    $('#suggestedProjects').html('<tr><td colspan="6" class="text-center text-danger">Có lỗi xảy ra khi tải dữ liệu</td></tr>');
                }
            });
        }
        
        // Tải dữ liệu ban đầu
        loadSuggestedProjects();
        
        // Xử lý tìm kiếm
        var searchTimeout;
        $('#searchProject').on('input', function() {
            clearTimeout(searchTimeout);
            var searchValue = $(this).val();
            searchTimeout = setTimeout(function() {
                loadSuggestedProjects(searchValue);
            }, 500);
        });
    });
    </script>
</body>
</html>