<?php
// Bao gồm file session để kiểm tra phiên và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên từ cơ sở dữ liệu
$teacher_id = $_SESSION['user_id'];
$sql = "SELECT * FROM giang_vien WHERE GV_MAGV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Lấy thông tin khoa/đơn vị
$sql = "SELECT DV_TENDV FROM khoa WHERE DV_MADV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $teacher['DV_MADV']);
$stmt->execute();
$result = $stmt->get_result();
$department = $result->fetch_assoc();

// Lấy danh sách đề tài hướng dẫn
$sql = "SELECT DT_MADT, DT_TENDT FROM de_tai_nghien_cuu WHERE GV_MAGV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

// Lấy thông tin hội đồng tham gia từ bảng thanh_vien_hoi_dong
try {
    $councils = [];
    $sql = "SELECT qd.QD_SO, qd.QD_NGAY, tv.TV_VAITRO 
            FROM thanh_vien_hoi_dong tv 
            JOIN quyet_dinh_nghiem_thu qd ON tv.QD_SO = qd.QD_SO 
            WHERE tv.GV_MAGV = ?
            GROUP BY qd.QD_SO";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi truy vấn hội đồng: " . $conn->error);
    }
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $councils[] = $row;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    // Chỉ ghi log lỗi mà không dừng trang
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hồ sơ giảng viên | Hệ thống NCKH</title>
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
    
    <style>
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-card .card-header {
            background-color: #4e73df;
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0;
        }
        
        .info-section {
            margin-bottom: 1.5rem;
        }
        
        .info-section h4 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #343a40;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        
        .info-section h4 i {
            color: #4e73df;
            margin-right: 0.5rem;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        
        .btn-info:hover, .btn-info:focus {
            background-color: #2a96a5;
            border-color: #258391;
        }
        
        .form-control[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
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
                            <i class="fas fa-user-edit mr-2"></i>Quản lý hồ sơ cá nhân
                        </h1>
                    </div>
                    
                    <!-- Hiển thị thông báo nếu có -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                    
                    <!-- Content Row -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card profile-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-id-card mr-2"></i>Thông tin cá nhân</h6>
                                </div>
                                <div class="card-body">
                                    <form action="update_profile.php" method="post">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-section">
                                                    <h4><i class="fas fa-info-circle"></i> Thông tin cơ bản</h4>
                                                    
                                                    <div class="form-group">
                                                        <label for="GV_MAGV">Mã giảng viên</label>
                                                        <input type="text" class="form-control" id="GV_MAGV" name="GV_MAGV"
                                                            value="<?php echo htmlspecialchars($teacher['GV_MAGV']); ?>" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="GV_HOGV">Họ</label>
                                                        <input type="text" class="form-control" id="GV_HOGV" name="GV_HOGV"
                                                            value="<?php echo htmlspecialchars($teacher['GV_HOGV']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="GV_TENGV">Tên</label>
                                                        <input type="text" class="form-control" id="GV_TENGV" name="GV_TENGV"
                                                            value="<?php echo htmlspecialchars($teacher['GV_TENGV']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="GV_EMAIL">Email</label>
                                                        <input type="email" class="form-control" id="GV_EMAIL" name="GV_EMAIL"
                                                            value="<?php echo htmlspecialchars($teacher['GV_EMAIL']); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="info-section">
                                                    <h4><i class="fas fa-user-graduate"></i> Thông tin khác</h4>
                                                    
                                                    <div class="form-group">
                                                        <label for="GV_SDT">Số điện thoại</label>
                                                        <input type="text" class="form-control" id="GV_SDT" name="GV_SDT"
                                                            value="<?php echo htmlspecialchars($teacher['GV_SDT'] ?? ''); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="GV_NGAYSINH">Ngày sinh</label>
                                                        <input type="date" class="form-control" id="GV_NGAYSINH" name="GV_NGAYSINH"
                                                            value="<?php echo htmlspecialchars($teacher['GV_NGAYSINH'] ?? ''); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="GV_GIOITINH">Giới tính</label>
                                                        <select class="form-control" id="GV_GIOITINH" name="GV_GIOITINH">
                                                            <option value="1" <?php echo (isset($teacher['GV_GIOITINH']) && $teacher['GV_GIOITINH'] == 1) ? 'selected' : ''; ?>>Nam</option>
                                                            <option value="0" <?php echo (isset($teacher['GV_GIOITINH']) && $teacher['GV_GIOITINH'] == 0) ? 'selected' : ''; ?>>Nữ</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="GV_DIACHI">Địa chỉ</label>
                                                        <input type="text" class="form-control" id="GV_DIACHI" name="GV_DIACHI"
                                                            value="<?php echo htmlspecialchars($teacher['GV_DIACHI'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-1"></i> Cập nhật thông tin
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Thông tin khoa -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-university mr-2"></i>Thông tin đơn vị</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="font-weight-bold"><?php echo htmlspecialchars($department['DV_TENDV'] ?? 'Chưa cập nhật'); ?></h5>
                                    <p class="text-muted mb-0">Mã đơn vị: <?php echo htmlspecialchars($teacher['DV_MADV'] ?? 'Chưa cập nhật'); ?></p>
                                </div>
                            </div>
                            
                            <!-- Thông tin đề tài đang hướng dẫn -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-project-diagram mr-2"></i>Đề tài đang hướng dẫn</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (count($projects) > 0): ?>
                                        <p>Số lượng: <span class="font-weight-bold"><?php echo count($projects); ?> đề tài</span></p>
                                        <a href="#" class="btn btn-info btn-sm btn-block" data-toggle="modal" data-target="#projectsModal">
                                            <i class="fas fa-list mr-1"></i> Xem danh sách đề tài
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">Hiện tại bạn chưa hướng dẫn đề tài nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Thông tin hội đồng tham gia -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users mr-2"></i>Hội đồng tham gia</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (count($councils) > 0): ?>
                                        <p>Số lượng: <span class="font-weight-bold"><?php echo count($councils); ?> hội đồng</span></p>
                                        <a href="#" class="btn btn-info btn-sm btn-block" data-toggle="modal" data-target="#councilsModal">
                                            <i class="fas fa-list mr-1"></i> Xem danh sách hội đồng
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">Hiện tại bạn chưa tham gia hội đồng nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Thông tin tài khoản -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-key mr-2"></i>Bảo mật tài khoản</h6>
                                </div>
                                <div class="card-body">
                                    <a href="#" class="btn btn-warning btn-block" data-toggle="modal" data-target="#changePasswordModal">
                                        <i class="fas fa-lock mr-1"></i> Đổi mật khẩu
                                    </a>
                                </div>
                            </div>
                        </div>
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

    <!-- Modal Đổi mật khẩu -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="changePasswordModalLabel"><i class="fas fa-key mr-2"></i>Đổi mật khẩu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="change_password.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="current_password">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Cập nhật mật khẩu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal danh sách đề tài -->
    <div class="modal fade" id="projectsModal" tabindex="-1" role="dialog" aria-labelledby="projectsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="projectsModalLabel"><i class="fas fa-project-diagram mr-2"></i>Danh sách đề tài hướng dẫn</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mã đề tài</th>
                                    <th>Tên đề tài</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['DT_MADT']); ?></td>
                                    <td><?php echo htmlspecialchars($project['DT_TENDT']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal danh sách hội đồng -->
    <div class="modal fade" id="councilsModal" tabindex="-1" role="dialog" aria-labelledby="councilsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="councilsModalLabel"><i class="fas fa-users mr-2"></i>Danh sách hội đồng tham gia</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Số quyết định</th>
                                    <th>Ngày quyết định</th>
                                    <th>Vai trò</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($councils as $council): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($council['QD_SO']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($council['QD_NGAY']))); ?></td>
                                    <td><?php echo htmlspecialchars($council['TV_VAITRO']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    
    <!-- SB Admin 2 JS từ CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Tự động ẩn thông báo sau 5 giây
            setTimeout(function() {
                $('.alert-dismissible').alert('close');
            }, 5000);
            
            // Xác nhận trùng khớp mật khẩu
            $('#changePasswordModal form').on('submit', function(e) {
                if ($('#new_password').val() != $('#confirm_password').val()) {
                    e.preventDefault();
                    alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
                }
            });
        });
    </script>
</body>
</html>