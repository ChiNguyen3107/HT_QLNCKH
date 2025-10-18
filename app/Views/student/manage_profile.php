<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\manage_profile.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thông tin sinh viên từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$sql = "SELECT SV_MASV, SV_HOSV, SV_TENSV, SV_EMAIL, SV_SDT, LOP_MA, SV_NGAYSINH, SV_GIOITINH, SV_DIACHI, SV_AVATAR FROM sinh_vien WHERE SV_MASV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Lấy thông tin lớp, khoa, khóa học và niên khóa từ các bảng liên quan
$lop_ma = $student['LOP_MA'];
$sql = "SELECT lop.LOP_MA, lop.LOP_TEN, khoa.DV_TENDV, khoa_hoc.KH_NAM, lop.LOP_LOAICTDT 
        FROM lop 
        JOIN khoa ON lop.DV_MADV = khoa.DV_MADV 
        JOIN khoa_hoc ON lop.KH_NAM = khoa_hoc.KH_NAM 
        WHERE lop.LOP_MA = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $lop_ma);
$stmt->execute();
$result = $stmt->get_result();
$class_info = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hồ sơ sinh viên</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/main.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/student/manage_profile.css" rel="stylesheet">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
        }
        
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #adb5bd;
        }
        
        .avatar-upload {
            position: absolute;
            right: 5px;
            bottom: 5px;
            background: #2ecc71;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .avatar-upload:hover {
            background: #27ae60;
            transform: scale(1.05);
        }
        
        .tab-content {
            padding-top: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: 0;
            position: relative;
        }
        
        .nav-tabs .nav-link.active {
            color: #2ecc71;
            background-color: transparent;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #2ecc71;
            border-radius: 3px;
        }
        
        .form-group label {
            font-weight: 500;
            color: #495057;
        }
        
        .form-control:focus {
            border-color: #2ecc71;
            box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25);
        }
        
        .card {
            overflow: hidden;
            transition: transform 0.3s ease-in-out;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(to right, #3498db, #2ecc71);
            color: white;
            font-weight: 600;
        }
        
        .change-password-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .save-btn {
            background-color: #2ecc71;
            border-color: #2ecc71;
            padding: 0.5rem 2rem;
            transition: all 0.3s ease;
        }
        
        .save-btn:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .tooltip-inner {
            background-color: #2ecc71;
        }
        
        .tooltip.bs-tooltip-auto[x-placement^=top] .arrow::before, 
        .tooltip.bs-tooltip-top .arrow::before {
            border-top-color: #2ecc71;
        }
        
        .field-icon {
            color: #2ecc71;
            margin-right: 5px;
        }
        
        /* Tab content visibility fixes */
        .tab-content {
            display: block !important;
        }
        
        .tab-pane {
            opacity: 1 !important;
        }
        
        .tab-pane.fade {
            transition: opacity 0.15s linear;
        }
        
        .tab-pane.fade.show {
            opacity: 1;
        }
        
        @media (max-width: 767.98px) {
            .profile-header {
                text-align: center;
            }
            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="container-fluid content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/NLNganh/view/student/student_dashboard.php">
                    <i class="fas fa-home mr-1"></i> Trang chủ
                </a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý hồ sơ</li>
            </ol>
        </nav>

        <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header animate__animated animate__fadeIn">
            <div class="row align-items-center">
                <div class="col-lg-2 col-md-3 text-center">                    <div class="avatar-container">
                        <div class="avatar" id="profileAvatar">
                            <?php if (isset($student['SV_AVATAR']) && !empty($student['SV_AVATAR'])): ?>
                                <img src="/NLNganh/<?php echo htmlspecialchars($student['SV_AVATAR']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($student['SV_TENSV'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-upload" id="uploadAvatarBtn" title="Cập nhật ảnh đại diện">
                            <i class="fas fa-camera"></i>
                        </div>
                        <input type="file" id="avatarUpload" style="display:none" accept="image/*">
                    </div>
                </div>
                <div class="col-lg-10 col-md-9">
                    <h1 class="mb-1"><?php echo htmlspecialchars($student['SV_HOSV'] . ' ' . $student['SV_TENSV']); ?></h1>
                    <p class="lead mb-1"><i class="fas fa-id-card mr-2"></i><?php echo htmlspecialchars($student['SV_MASV']); ?></p>
                    <p class="mb-1"><i class="fas fa-graduation-cap mr-2"></i><?php echo htmlspecialchars($class_info['LOP_TEN']); ?> - <?php echo htmlspecialchars($class_info['DV_TENDV']); ?></p>
                    <p class="mb-0"><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($student['SV_EMAIL']); ?></p>
                </div>
            </div>
        </div>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab" aria-controls="personal" aria-selected="true">
                    <i class="fas fa-user mr-2"></i>Thông tin cá nhân
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="academic-tab" data-toggle="tab" href="#academic" role="tab" aria-controls="academic" aria-selected="false">
                    <i class="fas fa-university mr-2"></i>Thông tin học vấn
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="security-tab" data-toggle="tab" href="#security" role="tab" aria-controls="security" aria-selected="false">
                    <i class="fas fa-lock mr-2"></i>Bảo mật tài khoản
                </a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content" id="profileTabContent">
            <!-- Thông tin cá nhân -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                <form action="update_profile.php" method="post" id="personalInfoForm">
                    <input type="hidden" name="form_type" value="personal_info">
                    <input type="hidden" name="SV_MASV" value="<?php echo htmlspecialchars($student['SV_MASV']); ?>">
                    
                    <div class="card animate__animated animate__fadeIn">
                        <div class="card-header">
                            <i class="fas fa-id-card mr-2"></i>Thông tin cá nhân
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_HOSV"><i class="fas fa-user field-icon"></i>Họ và tên đệm</label>
                                        <input type="text" class="form-control" id="SV_HOSV" name="SV_HOSV"
                                            value="<?php echo htmlspecialchars($student['SV_HOSV']); ?>" required>
                                        <div class="invalid-feedback">Vui lòng nhập họ và tên đệm</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_TENSV"><i class="fas fa-signature field-icon"></i>Tên</label>
                                        <input type="text" class="form-control" id="SV_TENSV" name="SV_TENSV"
                                            value="<?php echo htmlspecialchars($student['SV_TENSV']); ?>" required>
                                        <div class="invalid-feedback">Vui lòng nhập tên</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_NGAYSINH"><i class="fas fa-birthday-cake field-icon"></i>Ngày sinh</label>
                                        <input type="text" class="form-control datepicker" id="SV_NGAYSINH" name="SV_NGAYSINH"
                                            value="<?php echo isset($student['SV_NGAYSINH']) ? htmlspecialchars($student['SV_NGAYSINH']) : ''; ?>"
                                            placeholder="Chọn ngày sinh">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_GIOITINH"><i class="fas fa-venus-mars field-icon"></i>Giới tính</label>
                                        <select class="form-control" id="SV_GIOITINH" name="SV_GIOITINH">
                                            <option value="0" <?php echo (isset($student['SV_GIOITINH']) && $student['SV_GIOITINH'] == 0) ? 'selected' : ''; ?>>Nam</option>
                                            <option value="1" <?php echo (isset($student['SV_GIOITINH']) && $student['SV_GIOITINH'] == 1) ? 'selected' : ''; ?>>Nữ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_EMAIL"><i class="fas fa-envelope field-icon"></i>Email</label>
                                        <input type="email" class="form-control" id="SV_EMAIL" name="SV_EMAIL"
                                            value="<?php echo htmlspecialchars($student['SV_EMAIL']); ?>" required>
                                        <div class="invalid-feedback">Vui lòng nhập email hợp lệ</div>
                                        <small class="form-text text-muted">Email sẽ được sử dụng để liên lạc và thông báo.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_SDT"><i class="fas fa-phone field-icon"></i>Số điện thoại</label>
                                        <input type="text" class="form-control" id="SV_SDT" name="SV_SDT"
                                            value="<?php echo htmlspecialchars($student['SV_SDT']); ?>" required
                                            pattern="[0-9]{10}" title="Số điện thoại gồm 10 chữ số">
                                        <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ (10 số)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="SV_DIACHI"><i class="fas fa-map-marker-alt field-icon"></i>Địa chỉ</label>
                                <textarea class="form-control" id="SV_DIACHI" name="SV_DIACHI" rows="3"
                                    placeholder="Nhập địa chỉ liên lạc"><?php echo isset($student['SV_DIACHI']) ? htmlspecialchars($student['SV_DIACHI']) : ''; ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary save-btn">
                                <i class="fas fa-save mr-2"></i>Lưu thông tin cá nhân
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Thông tin học vấn -->
            <div class="tab-pane fade" id="academic" role="tabpanel" aria-labelledby="academic-tab">
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-header">
                        <i class="fas fa-graduation-cap mr-2"></i>Thông tin học vấn
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>Thông tin học vấn được quản lý bởi nhà trường và không thể thay đổi trực tiếp. Vui lòng liên hệ phòng đào tạo nếu có thông tin không chính xác.
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-3"><i class="fas fa-school mr-2"></i>Thông tin lớp học</h5>
                                        <p><strong><i class="fas fa-id-badge mr-2"></i>Mã lớp:</strong> <?php echo htmlspecialchars($class_info['LOP_MA']); ?></p>
                                        <p><strong><i class="fas fa-users mr-2"></i>Tên lớp:</strong> <?php echo htmlspecialchars($class_info['LOP_TEN']); ?></p>
                                        <p><strong><i class="fas fa-university mr-2"></i>Khoa:</strong> <?php echo htmlspecialchars($class_info['DV_TENDV']); ?></p>
                                        <p><strong><i class="fas fa-calendar-alt mr-2"></i>Khóa học:</strong> <?php echo htmlspecialchars($class_info['KH_NAM']); ?></p>
                                        <p><strong><i class="fas fa-book mr-2"></i>Chương trình đào tạo:</strong> <?php echo htmlspecialchars($class_info['LOP_LOAICTDT']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-3"><i class="fas fa-tasks mr-2"></i>Thống kê học tập</h5>
                                        <p><strong><i class="fas fa-project-diagram mr-2"></i>Số đề tài đã tham gia:</strong> <span class="badge badge-info">Đang tải...</span></p>
                                        <p><strong><i class="fas fa-check-circle mr-2"></i>Số đề tài đã hoàn thành:</strong> <span class="badge badge-success">Đang tải...</span></p>
                                        <p><strong><i class="fas fa-clock mr-2"></i>Số đề tài đang thực hiện:</strong> <span class="badge badge-warning">Đang tải...</span></p>
                                        <p><strong><i class="fas fa-medal mr-2"></i>Thành tích:</strong> <span class="badge badge-secondary">Chưa có dữ liệu</span></p>
                                        <p><strong><i class="fas fa-certificate mr-2"></i>Chứng chỉ:</strong> <span class="badge badge-secondary">Chưa có dữ liệu</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bảo mật -->
            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-header">
                        <i class="fas fa-shield-alt mr-2"></i>Bảo mật tài khoản
                    </div>
                    <div class="card-body">
                        <form action="update_profile.php" method="post" id="changePasswordForm">
                            <input type="hidden" name="form_type" value="change_password">
                            <input type="hidden" name="SV_MASV" value="<?php echo htmlspecialchars($student['SV_MASV']); ?>">
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
                            </div>
                            
                            <div class="form-group">
                                <label for="current_password"><i class="fas fa-key field-icon"></i>Mật khẩu hiện tại</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text password-toggle" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="invalid-feedback">Vui lòng nhập mật khẩu hiện tại</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password"><i class="fas fa-lock field-icon"></i>Mật khẩu mới</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required
                                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                           title="Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt">
                                    <div class="input-group-append">
                                        <span class="input-group-text password-toggle" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="invalid-feedback">Mật khẩu không đáp ứng yêu cầu bảo mật</div>
                                <div class="password-strength mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div id="password-strength-bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small id="password-strength-text" class="form-text text-muted">Độ mạnh: Yếu</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password"><i class="fas fa-lock field-icon"></i>Xác nhận mật khẩu mới</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text password-toggle" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="invalid-feedback">Xác nhận mật khẩu không khớp</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary save-btn">
                                <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <h5><i class="fas fa-shield-alt mr-2"></i>Bảo mật tài khoản nâng cao</h5>
                        <p>Tính năng bảo mật nâng cao sẽ được cập nhật trong thời gian tới.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>    <!-- Modal thông tin lớp -->
    <div class="modal fade" id="classInfoModal" tabindex="-1" role="dialog" aria-labelledby="classInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="classInfoModalLabel">
                        <i class="fas fa-info-circle mr-2"></i>Thông tin chi tiết lớp học
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fas fa-school mr-1"></i> Thông tin cơ bản
                                </div>
                                <div class="card-body">
                                    <p><strong><i class="fas fa-id-card-alt mr-2"></i>Mã lớp:</strong> <?php echo htmlspecialchars($class_info['LOP_MA']); ?></p>
                                    <p><strong><i class="fas fa-users mr-2"></i>Tên lớp:</strong> <?php echo htmlspecialchars($class_info['LOP_TEN']); ?></p>
                                    <p><strong><i class="fas fa-university mr-2"></i>Khoa:</strong> <?php echo htmlspecialchars($class_info['DV_TENDV']); ?></p>
                                    <p><strong><i class="fas fa-calendar-alt mr-2"></i>Khóa học:</strong> <?php echo htmlspecialchars($class_info['KH_NAM']); ?></p>
                                    <p class="mb-0"><strong><i class="fas fa-book-reader mr-2"></i>Chương trình đào tạo:</strong> <?php echo htmlspecialchars($class_info['LOP_LOAICTDT']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fas fa-info-circle mr-1"></i> Thông tin khác
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>Để xem thêm thông tin chi tiết về lớp học, thời khóa biểu, và các thông báo liên quan, vui lòng truy cập vào hệ thống quản lý đào tạo.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận cập nhật ảnh đại diện -->
    <div class="modal fade" id="avatarConfirmModal" tabindex="-1" role="dialog" aria-labelledby="avatarConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="avatarConfirmModalLabel">
                        <i class="fas fa-image mr-2"></i>Xác nhận ảnh đại diện
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="avatarPreview" src="" alt="Ảnh đại diện" class="img-fluid mb-3" style="max-height: 300px; border-radius: 10px;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>Bạn có muốn sử dụng ảnh này làm ảnh đại diện không?
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </button>
                    <button type="button" class="btn btn-primary" id="saveAvatarBtn">
                        <i class="fas fa-check mr-1"></i> Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
      <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/main.js"></script>
    <script src="/NLNganh/assets/js/student/manage_profile.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Bootstrap tabs manually
            $('#profileTabs a').tab();
            
            // Handle tab clicks
            $('#profileTabs a').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
                
                // Add animation class to the target tab's content
                const target = $(this).attr('href');
                $(target).find('.card').removeClass('animate__fadeIn').addClass('animate__fadeIn');
            });
            
            // Show initial tab on page load
            var firstTab = $('#profileTabs a:first');
            firstTab.tab('show');
            
            // Add animation class to initial tab's content
            var firstTabContent = $(firstTab.attr('href'));
            firstTabContent.find('.card').addClass('animate__fadeIn');
            
            // Khởi tạo tooltip Bootstrap
            $('[data-toggle="tooltip"]').tooltip();
            
            // Khởi tạo datepicker
            $(".datepicker").flatpickr({
                locale: "vn",
                dateFormat: "Y-m-d",
                maxDate: "today",
                allowInput: true,
                altInput: true,
                altFormat: "d/m/Y",
                defaultDate: "<?php echo isset($student['SV_NGAYSINH']) ? $student['SV_NGAYSINH'] : ''; ?>",
                disableMobile: "true",
                parseDate: (datestr, format) => {
                    return moment(datestr, "YYYY-MM-DD").toDate();
                },
                formatDate: (date, format, locale) => {
                    return moment(date).format("YYYY-MM-DD");
                }
            });

            // Trigger animation on page load for active tab
            $('.tab-pane.active .card').addClass('animate__fadeIn');

            // Clear animation when it ends
            $('.card').on('animationend', function() {
                $(this).removeClass('animate__fadeIn');
            });
            
            // Hiển thị/ẩn mật khẩu
            $('.password-toggle').on('click', function() {
                const target = $(this).data('target');
                const input = $('#' + target);
                const icon = $(this).find('i');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Kiểm tra độ mạnh của mật khẩu
            $('#new_password').on('input', function() {
                const password = $(this).val();
                let strength = 0;
                let strengthText = 'Yếu';
                let barClass = 'bg-danger';
                
                // Tăng điểm độ mạnh dựa trên tiêu chí
                if (password.length >= 8) strength += 20; // Độ dài
                if (/[a-z]/.test(password)) strength += 20; // Chữ thường
                if (/[A-Z]/.test(password)) strength += 20; // Chữ hoa
                if (/\d/.test(password)) strength += 20; // Số
                if (/[^a-zA-Z0-9]/.test(password)) strength += 20; // Ký tự đặc biệt
                
                // Phân loại độ mạnh
                if (strength >= 80) {
                    strengthText = 'Rất mạnh';
                    barClass = 'bg-success';
                } else if (strength >= 60) {
                    strengthText = 'Mạnh';
                    barClass = 'bg-info';
                } else if (strength >= 40) {
                    strengthText = 'Trung bình';
                    barClass = 'bg-warning';
                } else {
                    strengthText = 'Yếu';
                    barClass = 'bg-danger';
                }
                
                // Cập nhật thanh tiến trình
                $('#password-strength-bar')
                    .removeClass('bg-danger bg-warning bg-info bg-success')
                    .addClass(barClass)
                    .css('width', strength + '%')
                    .attr('aria-valuenow', strength);
                
                $('#password-strength-text').text('Độ mạnh: ' + strengthText);
            });
            
            // Kiểm tra khớp xác nhận mật khẩu
            $('#confirm_password').on('input', function() {
                if ($(this).val() !== $('#new_password').val()) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            // Xác thực form thông tin cá nhân
            $('#personalInfoForm').on('submit', function(e) {
                let isValid = true;
                
                // Kiểm tra các trường bắt buộc
                $(this).find('[required]').each(function() {
                    if ($(this).val() === '') {
                        $(this).addClass('is-invalid');
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                // Kiểm tra định dạng email
                const email = $('#SV_EMAIL').val();
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    $('#SV_EMAIL').addClass('is-invalid');
                    isValid = false;
                }
                
                // Kiểm tra định dạng số điện thoại
                const phone = $('#SV_SDT').val();
                if (phone && !/^[0-9]{10}$/.test(phone)) {
                    $('#SV_SDT').addClass('is-invalid');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Vui lòng kiểm tra lại thông tin nhập.');
                }
            });
            
            // Xác thực form đổi mật khẩu
            $('#changePasswordForm').on('submit', function(e) {
                let isValid = true;
                
                // Kiểm tra các trường bắt buộc
                $(this).find('[required]').each(function() {
                    if ($(this).val() === '') {
                        $(this).addClass('is-invalid');
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                // Kiểm tra mật khẩu mới đáp ứng yêu cầu
                const newPassword = $('#new_password').val();
                const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                
                if (newPassword && !passwordPattern.test(newPassword)) {
                    $('#new_password').addClass('is-invalid');
                    isValid = false;
                }
                
                // Kiểm tra xác nhận mật khẩu
                if ($('#confirm_password').val() !== newPassword) {
                    $('#confirm_password').addClass('is-invalid');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Vui lòng kiểm tra lại thông tin mật khẩu.');
                }
            });
              // Xử lý tải lên ảnh đại diện đã chuyển sang file manage_profile.js
            
            // Tải thông tin học tập (mô phỏng)
            setTimeout(function() {
                $('.badge:contains("Đang tải...")').first().html('3').removeClass('badge-info').addClass('badge-primary');
                $('.badge:contains("Đang tải...")').first().html('2').removeClass('badge-info').addClass('badge-success');
                $('.badge:contains("Đang tải...")').first().html('1').removeClass('badge-info').addClass('badge-warning');
            }, 1500);
            
            // Khởi tạo DataTables cho bảng danh sách đề tài
            $('#projectListTable').DataTable({
                responsive: true,
                language: {
                    search: "Tìm kiếm:",
                    lengthMenu: "Hiển thị _MENU_ dòng",
                    info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                    infoEmpty: "Không có dữ liệu",
                    infoFiltered: "(lọc từ _MAX_ dòng)",
                    paginate: {
                        first: "Đầu",
                        last: "Cuối",
                        next: "Sau",
                        previous: "Trước"
                    },
                    emptyTable: "Không có dữ liệu",
                    zeroRecords: "Không tìm thấy kết quả"
                },
                columnDefs: [
                    { orderable: false, targets: [-1] } // Cột cuối cùng (thao tác) không sắp xếp được
                ],
                order: [[0, 'asc']] // Sắp xếp mặc định theo cột đầu tiên, tăng dần
            });
            
            // Khởi tạo tooltip Bootstrap
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>

</html>