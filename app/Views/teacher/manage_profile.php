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
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: none;
            transition: all 0.3s ease;
        }
        
        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }
        
        .profile-card .card-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            font-weight: 600;
            border-radius: 15px 15px 0 0;
            border: none;
            padding: 1.25rem 1.5rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
            box-shadow: 0 8px 25px rgba(78, 115, 223, 0.3);
        }
        
        .info-section {
            margin-bottom: 2rem;
            background: #f8f9fc;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #4e73df;
        }
        
        .info-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-section h4 i {
            color: #4e73df;
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.15);
            background-color: #fff;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #36b9cc 0%, #2a96a5 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            border: none;
            color: #fff;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: #fff;
        }
        
        .form-control[readonly] {
            background: linear-gradient(135deg, #f8f9fc 0%, #eef0f5 100%);
            border-color: #e3e6f0;
            color: #6c757d;
        }
        
        .sidebar-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .sidebar-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 1.25rem;
        }
        
        .sidebar-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4e73df;
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            border-radius: 15px 15px 0 0;
            border: none;
            padding: 1.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #f8f9fc 0%, #eef0f5 100%);
            border: none;
            color: #5a5c69;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
            transform: scale(1.01);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);
            color: white;
        }
        
        .page-heading {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        .breadcrumb-item {
            color: #6c757d;
        }
        
        .breadcrumb-item.active {
            color: #4e73df;
            font-weight: 600;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            
            .page-heading {
                font-size: 1.5rem;
            }
            
            .info-section {
                margin-bottom: 1rem;
                padding: 1rem;
            }
            
            .sidebar-card .card-body {
                padding: 1rem;
            }
        }
        
        /* Animation keyframes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .pulse-on-hover:hover {
            animation: pulse 0.3s ease-in-out;
        }
        
        /* Loading states */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        /* Enhanced form styling */
        .form-control:valid {
            border-color: #1cc88a;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%231cc88a' d='m2.3 6.73.5-.55 1.5 1.45 2.5-2.4.5.55-3 2.95z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .form-control:invalid {
            border-color: #e74a3b;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23e74a3b' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 5.8 2.4 2.4m0-2.4-2.4 2.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        /* List group enhancements */
        .list-group-item-action:hover {
            background-color: rgba(78, 115, 223, 0.1);
            border-left: 3px solid #4e73df;
            padding-left: 1.2rem;
            transition: all 0.3s ease;
        }
        
        /* Card border animations */
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        
        /* Progress bar enhancements */
        .progress-sm {
            height: 0.5rem;
        }
        
        .progress-bar {
            transition: width 0.6s ease;
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
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-transparent p-0 mb-2">
                                    <li class="breadcrumb-item"><a href="../teacher/">Trang chủ</a></li>
                                    <li class="breadcrumb-item active">Quản lý hồ sơ</li>
                                </ol>
                            </nav>
                            <h1 class="h2 mb-0 page-heading">
                                <i class="fas fa-user-edit mr-3"></i>Quản lý hồ sơ cá nhân
                            </h1>
                            <p class="text-muted mt-2">Cập nhật và quản lý thông tin cá nhân của bạn</p>
                        </div>
                        <div class="text-right">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thống kê tổng quan -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Đề tài hướng dẫn
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($projects); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Hội đồng tham gia
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($councils); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Năm kinh nghiệm
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $experience_years = 0;
                                                if (!empty($teacher['GV_NGAYSINH'])) {
                                                    $birth_year = date('Y', strtotime($teacher['GV_NGAYSINH']));
                                                    $experience_years = max(0, date('Y') - $birth_year - 22); // Giả sử tốt nghiệp ở tuổi 22
                                                }
                                                echo $experience_years;
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Tính hoàn thiện
                                            </div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                        <?php 
                                                        $completion = 0;
                                                        $fields = ['GV_HOGV', 'GV_TENGV', 'GV_EMAIL', 'GV_SDT', 'GV_NGAYSINH', 'GV_DIACHI'];
                                                        $filled_fields = 0;
                                                        foreach ($fields as $field) {
                                                            if (!empty($teacher[$field])) {
                                                                $filled_fields++;
                                                            }
                                                        }
                                                        $completion = round(($filled_fields / count($fields)) * 100);
                                                        echo $completion . '%';
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-warning" role="progressbar"
                                                            style="width: <?php echo $completion; ?>%" aria-valuenow="<?php echo $completion; ?>"
                                                            aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                                        <label for="GV_EMAIL"><i class="fas fa-envelope mr-1"></i> Email</label>
                                                        <input type="email" class="form-control" id="GV_EMAIL" name="GV_EMAIL"
                                                            value="<?php echo htmlspecialchars($teacher['GV_EMAIL']); ?>" required>
                                                        <div class="invalid-feedback">
                                                            Vui lòng nhập email hợp lệ.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="info-section">
                                                    <h4><i class="fas fa-user-graduate"></i> Thông tin khác</h4>
                                                    
                                                    <div class="form-group">
                                                        <label for="GV_SDT"><i class="fas fa-phone mr-1"></i> Số điện thoại</label>
                                                        <input type="tel" class="form-control" id="GV_SDT" name="GV_SDT"
                                                            value="<?php echo htmlspecialchars($teacher['GV_SDT'] ?? ''); ?>"
                                                            pattern="[0-9]{10,11}" placeholder="0123456789">
                                                        <div class="invalid-feedback">
                                                            Số điện thoại phải có 10-11 chữ số.
                                                        </div>
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
                                        
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save mr-2"></i> Cập nhật thông tin cá nhân
                                        </button>
                                        <a href="manage_projects.php" class="btn btn-outline-secondary btn-lg ml-3">
                                            <i class="fas fa-arrow-left mr-2"></i> Quay lại
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Thông tin khoa -->
                            <div class="card sidebar-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-university mr-2"></i>Thông tin đơn vị</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-building fa-3x text-primary mb-3"></i>
                                    </div>
                                    <h5 class="font-weight-bold text-primary"><?php echo htmlspecialchars($department['DV_TENDV'] ?? 'Chưa cập nhật'); ?></h5>
                                    <p class="text-muted mb-0">Mã đơn vị: <span class="font-weight-bold"><?php echo htmlspecialchars($teacher['DV_MADV'] ?? 'Chưa cập nhật'); ?></span></p>
                                </div>
                            </div>
                            
                            <!-- Thông tin đề tài đang hướng dẫn -->
                            <div class="card sidebar-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-project-diagram mr-2"></i>Đề tài hướng dẫn</h6>
                                </div>
                                <div class="card-body text-center">
                                    <?php if (count($projects) > 0): ?>
                                        <div class="mb-3">
                                            <i class="fas fa-tasks fa-3x text-success mb-3"></i>
                                        </div>
                                        <span class="stat-number"><?php echo count($projects); ?></span>
                                        <span class="stat-label">Đề tài đang hướng dẫn</span>
                                        <div class="mt-3">
                                            <a href="#" class="btn btn-info btn-sm btn-block" data-toggle="modal" data-target="#projectsModal">
                                                <i class="fas fa-list mr-1"></i> Xem danh sách chi tiết
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                        </div>
                                        <p class="text-muted mb-0">Hiện tại bạn chưa hướng dẫn đề tài nào.</p>
                                        <div class="mt-3">
                                            <a href="create_project.php" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus mr-1"></i> Tạo đề tài mới
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Thông tin hội đồng tham gia -->
                            <div class="card sidebar-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-users mr-2"></i>Hội đồng tham gia</h6>
                                </div>
                                <div class="card-body text-center">
                                    <?php if (count($councils) > 0): ?>
                                        <div class="mb-3">
                                            <i class="fas fa-user-friends fa-3x text-info mb-3"></i>
                                        </div>
                                        <span class="stat-number"><?php echo count($councils); ?></span>
                                        <span class="stat-label">Hội đồng tham gia</span>
                                        <div class="mt-3">
                                            <a href="#" class="btn btn-info btn-sm btn-block" data-toggle="modal" data-target="#councilsModal">
                                                <i class="fas fa-list mr-1"></i> Xem danh sách chi tiết
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                        </div>
                                        <p class="text-muted mb-0">Hiện tại bạn chưa tham gia hội đồng nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="card sidebar-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-bolt mr-2"></i>Thao tác nhanh</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <a href="create_project.php" class="list-group-item list-group-item-action border-0 py-2">
                                            <i class="fas fa-plus text-primary mr-2"></i>
                                            Tạo đề tài mới
                                        </a>
                                        <a href="manage_projects.php" class="list-group-item list-group-item-action border-0 py-2">
                                            <i class="fas fa-tasks text-success mr-2"></i>
                                            Quản lý đề tài
                                        </a>
                                        <a href="../teacher/" class="list-group-item list-group-item-action border-0 py-2">
                                            <i class="fas fa-home text-info mr-2"></i>
                                            Về trang chủ
                                        </a>
                                        <a href="../../logout.php" class="list-group-item list-group-item-action border-0 py-2 text-danger">
                                            <i class="fas fa-sign-out-alt mr-2"></i>
                                            Đăng xuất
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Thông tin tài khoản -->
                            <div class="card sidebar-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold"><i class="fas fa-shield-alt mr-2"></i>Bảo mật tài khoản</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-key fa-3x text-warning mb-3"></i>
                                    </div>
                                    <p class="text-muted mb-3">Đảm bảo tài khoản của bạn được bảo mật</p>
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
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-shield-alt mr-2"></i>Đổi mật khẩu bảo mật
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="change_password.php" method="post">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Mật khẩu mới phải có ít nhất 6 ký tự và bao gồm chữ hoa, chữ thường và số.
                        </div>
                        <div class="form-group">
                            <label for="current_password"><i class="fas fa-lock mr-1"></i> Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required placeholder="Nhập mật khẩu hiện tại">
                        </div>
                        <div class="form-group">
                            <label for="new_password"><i class="fas fa-key mr-1"></i> Mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="Nhập mật khẩu mới">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-check-circle mr-1"></i> Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Hủy bỏ
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-shield-alt mr-1"></i> Cập nhật mật khẩu
                        </button>
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
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                    <a href="manage_projects.php" class="btn btn-primary">
                        <i class="fas fa-eye mr-1"></i> Xem tất cả đề tài
                    </a>
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
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
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
            // Thêm class animate cho các element khi load
            $('.card').addClass('animate-fade-in');
            
            // Tự động ẩn thông báo sau 5 giây với hiệu ứng
            setTimeout(function() {
                $('.alert-dismissible').fadeOut('slow');
            }, 5000);
            
            // Form validation real-time
            $('#GV_EMAIL').on('input', function() {
                const email = $(this).val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (email.length > 0) {
                    if (emailRegex.test(email)) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    } else {
                        $(this).removeClass('is-valid').addClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });
            
            $('#GV_SDT').on('input', function() {
                const phone = $(this).val();
                const phoneRegex = /^[0-9]{10,11}$/;
                
                if (phone.length > 0) {
                    if (phoneRegex.test(phone)) {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    } else {
                        $(this).removeClass('is-valid').addClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-valid is-invalid');
                }
            });
            
            // Xác nhận trùng khớp mật khẩu với hiệu ứng thời gian thực
            $('#new_password, #confirm_password').on('input', function() {
                const newPassword = $('#new_password').val();
                const confirmPassword = $('#confirm_password').val();
                const confirmField = $('#confirm_password');
                
                if (confirmPassword.length > 0) {
                    if (newPassword === confirmPassword) {
                        confirmField.removeClass('is-invalid').addClass('is-valid');
                    } else {
                        confirmField.removeClass('is-valid').addClass('is-invalid');
                    }
                }
            });
            
            // Password strength indicator
            $('#new_password').on('input', function() {
                const password = $(this).val();
                let strength = 0;
                
                if (password.length >= 6) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Remove existing strength indicator
                $(this).next('.password-strength').remove();
                
                if (password.length > 0) {
                    let strengthText = '';
                    let strengthClass = '';
                    
                    switch (strength) {
                        case 0:
                        case 1:
                            strengthText = 'Rất yếu';
                            strengthClass = 'text-danger';
                            break;
                        case 2:
                            strengthText = 'Yếu';
                            strengthClass = 'text-warning';
                            break;
                        case 3:
                            strengthText = 'Trung bình';
                            strengthClass = 'text-info';
                            break;
                        case 4:
                        case 5:
                            strengthText = 'Mạnh';
                            strengthClass = 'text-success';
                            break;
                    }
                    
                    $(this).after(`<small class="password-strength ${strengthClass}">Độ mạnh: ${strengthText}</small>`);
                }
            });
            
            // Validate form mật khẩu
            $('#changePasswordModal form').on('submit', function(e) {
                const newPassword = $('#new_password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    showNotification('Mật khẩu mới và xác nhận mật khẩu không khớp!', 'error');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    showNotification('Mật khẩu mới phải có ít nhất 6 ký tự!', 'error');
                    return false;
                }
            });
            
            // Hiệu ứng hover cho các card
            $('.sidebar-card, .profile-card').hover(
                function() {
                    $(this).addClass('shadow-lg').addClass('pulse-on-hover');
                },
                function() {
                    $(this).removeClass('shadow-lg').removeClass('pulse-on-hover');
                }
            );
            
            // Animate số liệu thống kê
            $('.stat-number').each(function() {
                const $this = $(this);
                const countTo = parseInt($this.text());
                
                $({ countNum: 0 }).animate({
                    countNum: countTo
                }, {
                    duration: 2000,
                    easing: 'linear',
                    step: function() {
                        $this.text(Math.floor(this.countNum));
                    },
                    complete: function() {
                        $this.text(this.countNum);
                    }
                });
            });
            
            // Animate progress bars
            $('.progress-bar').each(function() {
                const $this = $(this);
                const width = $this.attr('style').match(/width:\s*(\d+)%/);
                if (width) {
                    $this.css('width', '0%').animate({
                        width: width[1] + '%'
                    }, 1500);
                }
            });
            
            // Tooltip cho các nút
            $('[data-toggle="tooltip"]').tooltip();
            
            // Loading state cho form submit
            $('form').on('submit', function() {
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                submitBtn.addClass('btn-loading')
                        .prop('disabled', true);
                
                // Reset sau 10 giây để tránh treo
                setTimeout(function() {
                    submitBtn.removeClass('btn-loading')
                            .prop('disabled', false);
                }, 10000);
            });
            
            // Auto-save draft functionality
            let saveTimeout;
            $('input, textarea, select').on('input change', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    saveDraft();
                }, 2000);
            });
            
            // Keyboard shortcuts
            $(document).keydown(function(e) {
                // Ctrl + S để save
                if (e.ctrlKey && e.which === 83) {
                    e.preventDefault();
                    $('form').first().submit();
                    return false;
                }
                
                // Escape để đóng modal
                if (e.which === 27) {
                    $('.modal').modal('hide');
                }
            });
            
            // Enhanced notification system
            function showNotification(message, type = 'info') {
                const alertClass = {
                    'success': 'alert-success',
                    'error': 'alert-danger',
                    'warning': 'alert-warning',
                    'info': 'alert-info'
                };
                
                const notification = `
                    <div class="alert ${alertClass[type]} alert-dismissible fade show position-fixed" 
                         style="top: 20px; right: 20px; z-index: 9999; max-width: 300px;">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                        ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `;
                
                $('body').append(notification);
                
                setTimeout(function() {
                    $('.alert').last().fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Draft save function
            function saveDraft() {
                const formData = {};
                $('input, textarea, select').each(function() {
                    if ($(this).attr('name')) {
                        formData[$(this).attr('name')] = $(this).val();
                    }
                });
                
                localStorage.setItem('profile_draft', JSON.stringify(formData));
                console.log('Draft saved');
            }
            
            // Load draft on page load
            function loadDraft() {
                const draft = localStorage.getItem('profile_draft');
                if (draft) {
                    const formData = JSON.parse(draft);
                    Object.keys(formData).forEach(function(name) {
                        $(`[name="${name}"]`).val(formData[name]);
                    });
                }
            }
            
            // Clear draft after successful save
            $('form').on('submit', function() {
                localStorage.removeItem('profile_draft');
            });
            
            // Initialize
            loadDraft();
        });
    </script>
</body>
</html>