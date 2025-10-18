<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\manage_profile.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/database.php';

// Lấy thông tin quản lý nghiên cứu
$manager_id = $_SESSION['user_id'];

// Khởi tạo biến
$manager = null;
$error_message = '';
$success_message = '';

// Truy vấn thông tin quản lý nghiên cứu
$stmt = $conn->prepare("SELECT qlnc.*, k.DV_TENDV 
                       FROM quan_ly_nghien_cuu qlnc 
                       LEFT JOIN khoa k ON qlnc.DV_MADV = k.DV_MADV 
                       WHERE qlnc.QL_MA = ?");
if ($stmt) {
    $stmt->bind_param("s", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $manager = $result->fetch_assoc();
    $stmt->close();
}

// Nếu không tìm thấy, thử với ID mặc định
if (!$manager) {
    $default_id = 'QLR001';
    $stmt = $conn->prepare("SELECT qlnc.*, k.DV_TENDV 
                           FROM quan_ly_nghien_cuu qlnc 
                           LEFT JOIN khoa k ON qlnc.DV_MADV = k.DV_MADV 
                           WHERE qlnc.QL_MA = ?");
    if ($stmt) {
        $stmt->bind_param("s", $default_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $manager = $result->fetch_assoc();
        $stmt->close();
        
        if ($manager) {
            $_SESSION['manager_id'] = $default_id;
        }
    }
}

// Nếu vẫn không có dữ liệu, tạo dữ liệu mặc định
if (!$manager) {
    $manager = [
        'QL_MA' => $manager_id,
        'QL_HO' => 'Quản lý',
        'QL_TEN' => 'Nghiên cứu',
        'QL_EMAIL' => '',
        'QL_SDT' => '',
        'QL_GIOITINH' => 1,
        'QL_NGAYSINH' => '',
        'QL_DIACHI' => '',
        'DV_TENDV' => 'Chưa xác định'
    ];
}

// Xử lý cập nhật thông tin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $gender = $_POST['gender'];
    $birthday = $_POST['birthday'];

    $update_sql = "UPDATE quan_ly_nghien_cuu SET 
                   QL_EMAIL = ?, QL_SDT = ?, QL_DIACHI = ?, 
                   QL_GIOITINH = ?, QL_NGAYSINH = ? 
                   WHERE QL_MA = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("sssiss", $email, $phone, $address, $gender, $birthday, $manager['QL_MA']);
        if ($update_stmt->execute()) {
            $success_message = "Cập nhật thông tin thành công!";
            // Cập nhật lại dữ liệu
            $manager['QL_EMAIL'] = $email;
            $manager['QL_SDT'] = $phone;
            $manager['QL_DIACHI'] = $address;
            $manager['QL_GIOITINH'] = $gender;
            $manager['QL_NGAYSINH'] = $birthday;
        } else {
            $error_message = "Lỗi khi cập nhật thông tin: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}

// Xử lý đổi mật khẩu
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        if (strlen($new_password) >= 6) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $password_sql = "UPDATE quan_ly_nghien_cuu SET QL_MATKHAU = ? WHERE QL_MA = ?";
            $password_stmt = $conn->prepare($password_sql);
            
            if ($password_stmt) {
                $password_stmt->bind_param("ss", $hashed_password, $manager['QL_MA']);
                if ($password_stmt->execute()) {
                    $success_message = "Đổi mật khẩu thành công!";
                } else {
                    $error_message = "Lỗi khi đổi mật khẩu: " . $password_stmt->error;
                }
                $password_stmt->close();
            }
        } else {
            $error_message = "Mật khẩu mới phải có ít nhất 6 ký tự!";
        }
    } else {
        $error_message = "Mật khẩu mới và xác nhận không khớp!";
    }
}


// Set page title for the header
$page_title = "Hồ sơ cá nhân | Quản lý nghiên cứu";

// Define any additional CSS specific to this page
$additional_css = '<link href="/NLNganh/assets/css/research/dashboard-sidebar-fix.css" rel="stylesheet">
<link href="/NLNganh/assets/css/research/dashboard-enhanced.css" rel="stylesheet">
<style>
    .card-counter {
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        padding: 25px 15px;
        background-color: #fff;
        height: 110px;
        border-radius: 8px;
        transition: all 0.3s ease-in-out;
        overflow: hidden;
        position: relative;
    }
    
    .card-counter:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }
    
    .card-counter i {
        font-size: 5em;
        opacity: 0.15;
        position: absolute;
        right: 10px;
        bottom: -10px;
    }
    
    .card-counter .count-numbers {
        position: absolute;
        right: 35px;
        top: 20px;
        font-size: 32px;
        font-weight: 700;
        display: block;
    }
    
    .card-counter .count-name {
        position: absolute;
        right: 35px;
        top: 65px;
        text-transform: uppercase;
        opacity: 0.9;
        display: block;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    
    .card-counter.primary {
        background-color: #4e73df;
        color: #FFF;
    }
    
    .card-counter.danger {
        background-color: #e74a3b;
        color: #FFF;
    }
    
    .card-counter.success {
        background-color: #1cc88a;
        color: #FFF;
    }
    
    .card-counter.info {
        background-color: #36b9cc;
        color: #FFF;
    }
    
    .card-counter.warning {
        background-color: #f6c23e;
        color: #FFF;
    }
    
    .profile-card {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .profile-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }
    
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        backdrop-filter: blur(10px);
    }
    
    .profile-avatar i {
        font-size: 3rem;
        color: white;
    }
    
    .info-section {
        padding: 1.5rem;
    }
    
    .info-item {
        background: #f8f9fc;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }
    
    .info-label {
        font-weight: 600;
        color: #5a5c69;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .info-value {
        color: #2c3e50;
        font-size: 1.1rem;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin: 0.25rem;
    }
    
    .status-active {
        background-color: #1cc88a;
        color: white;
    }
    
    .status-verified {
        background-color: #4e73df;
        color: white;
    }
    
    .status-role {
        background-color: #f6c23e;
        color: white;
    }
    
    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        border-radius: 15px 15px 0 0;
    }
    
    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    }
    
    .animate-on-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    .fadeInUp {
        animation-name: fadeInUp;
        animation-duration: 0.5s;
        animation-fill-mode: both;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Fix layout positioning - giữ đồng nhất với dashboard */
    #content-wrapper {
        margin-left: 260px !important;
        width: calc(100% - 260px) !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    .container-fluid {
        padding-left: 15px !important;
        padding-right: 15px !important;
        max-width: none !important;
    }
    
    /* Đảm bảo body layout đúng */
    body {
        margin-left: 0 !important;
    }
    
    /* Đảm bảo sidebar dropdown hoạt động */
    .modern-research-sidebar .nav-item.has-submenu .submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        list-style: none;
        padding: 0;
        margin: 5px 0 0 0;
        opacity: 0;
    }
    
    .modern-research-sidebar .nav-item.has-submenu.open .submenu {
        max-height: 300px !important;
        opacity: 1;
    }
    
    .modern-research-sidebar .nav-arrow {
        transition: transform 0.3s ease;
        float: right;
        margin-top: 2px;
    }
    
    .modern-research-sidebar .nav-item.open .nav-arrow {
        transform: rotate(90deg);
    }
    
    /* Đảm bảo cursor pointer cho dropdown toggles */
    .modern-research-sidebar .submenu-toggle {
        cursor: pointer !important;
    }
    
    .modern-research-sidebar .nav-item.has-submenu > .nav-link {
        cursor: pointer !important;
    }
    
    /* Fix z-index cho sidebar */
    .modern-research-sidebar {
        z-index: 1050 !important;
        position: fixed !important;
    }
</style>';

// Include the research header
include '../../include/research_header.php';
?>

<!-- Sidebar đã được include trong header -->

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Thông tin cá nhân</h1>
        <a href="/NLNganh/view/research/research_dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Về Dashboard
        </a>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="100">
            <div class="card card-counter primary">
                <i class="fa fa-user-shield"></i>
                <span class="count-numbers">1</span>
                <span class="count-name">Tài khoản Active</span>
            </div>
        </div>
        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="200">
            <div class="card card-counter success">
                <i class="fa fa-check-circle"></i>
                <span class="count-numbers">100%</span>
                <span class="count-name">Đã xác thực</span>
            </div>
        </div>
        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="300">
            <div class="card card-counter info">
                <i class="fa fa-user-tie"></i>
                <span class="count-numbers">QLR</span>
                <span class="count-name">Vai trò</span>
            </div>
        </div>
        <div class="col-md-3 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="400">
            <div class="card card-counter warning">
                <i class="fa fa-clock"></i>
                <span class="count-numbers"><?= date('H:i') ?></span>
                <span class="count-name">Giờ hiện tại</span>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4 mb-4">
            <div class="card profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($manager['QL_HO'] . ' ' . $manager['QL_TEN']) ?></h4>
                    <p class="mb-0 opacity-75"><?= htmlspecialchars($manager['DV_TENDV']) ?></p>
                    
                    <div class="mt-3">
                        <span class="status-badge status-active">
                            <i class="fas fa-circle mr-1"></i>Đang hoạt động
                        </span>
                        <span class="status-badge status-verified">
                            <i class="fas fa-shield-alt mr-1"></i>Đã xác thực
                        </span>
                        <span class="status-badge status-role">
                            <i class="fas fa-user-cog mr-1"></i>Quản lý nghiên cứu
                        </span>
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="info-item">
                        <div class="info-label">Mã quản lý</div>
                        <div class="info-value"><?= htmlspecialchars($manager['QL_MA']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Đơn vị</div>
                        <div class="info-value"><?= htmlspecialchars($manager['DV_TENDV']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Trạng thái</div>
                        <div class="info-value">
                            <span class="badge bg-success">Đang hoạt động</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        Thông tin chi tiết
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-envelope text-primary mr-1"></i>
                                    Email
                                </div>
                                <div class="info-value">
                                    <?= !empty($manager['QL_EMAIL']) ? htmlspecialchars($manager['QL_EMAIL']) : '<em class="text-muted">Chưa cập nhật</em>' ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-phone text-success mr-1"></i>
                                    Số điện thoại
                                </div>
                                <div class="info-value">
                                    <?= !empty($manager['QL_SDT']) ? htmlspecialchars($manager['QL_SDT']) : '<em class="text-muted">Chưa cập nhật</em>' ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-venus-mars text-info mr-1"></i>
                                    Giới tính
                                </div>
                                <div class="info-value">
                                    <?= $manager['QL_GIOITINH'] == 1 ? 'Nam' : 'Nữ' ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-birthday-cake text-warning mr-1"></i>
                                    Ngày sinh
                                </div>
                                <div class="info-value">
                                    <?= !empty($manager['QL_NGAYSINH']) ? date('d/m/Y', strtotime($manager['QL_NGAYSINH'])) : '<em class="text-muted">Chưa cập nhật</em>' ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                    Địa chỉ
                                </div>
                                <div class="info-value">
                                    <?= !empty($manager['QL_DIACHI']) ? htmlspecialchars($manager['QL_DIACHI']) : '<em class="text-muted">Chưa cập nhật</em>' ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-calendar text-secondary mr-1"></i>
                                    Ngày tạo tài khoản
                                </div>
                                <div class="info-value">
                                    <?= date('d/m/Y H:i') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-gradient mr-2" data-toggle="modal" data-target="#editProfileModal">
                                <i class="fas fa-edit mr-2"></i>Chỉnh sửa thông tin
                            </button>
                            <button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#changePasswordModal">
                                <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">
                    <i class="fas fa-user-edit mr-2"></i>Chỉnh sửa thông tin cá nhân
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope text-primary mr-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($manager['QL_EMAIL']) ?>"
                                       placeholder="Nhập địa chỉ email">
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone text-success mr-1"></i>Số điện thoại
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($manager['QL_SDT']) ?>"
                                       placeholder="Nhập số điện thoại">
                            </div>
                            
                            <div class="mb-3">
                                <label for="gender" class="form-label">
                                    <i class="fas fa-venus-mars text-info mr-1"></i>Giới tính
                                </label>
                                <select class="form-control" id="gender" name="gender">
                                    <option value="1" <?= $manager['QL_GIOITINH'] == 1 ? 'selected' : '' ?>>Nam</option>
                                    <option value="0" <?= $manager['QL_GIOITINH'] == 0 ? 'selected' : '' ?>>Nữ</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birthday" class="form-label">
                                    <i class="fas fa-birthday-cake text-warning mr-1"></i>Ngày sinh
                                </label>
                                <input type="date" class="form-control" id="birthday" name="birthday" 
                                       value="<?= $manager['QL_NGAYSINH'] ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt text-danger mr-1"></i>Địa chỉ
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="4" 
                                          placeholder="Nhập địa chỉ"><?= htmlspecialchars($manager['QL_DIACHI']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Hủy
                    </button>
                    <button type="submit" name="update_profile" class="btn btn-gradient">
                        <i class="fas fa-save mr-1"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">
                            <i class="fas fa-lock text-secondary mr-1"></i>Mật khẩu hiện tại
                        </label>
                        <input type="password" class="form-control" id="current_password" name="current_password" 
                               placeholder="Nhập mật khẩu hiện tại" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="fas fa-key text-primary mr-1"></i>Mật khẩu mới
                        </label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-check text-success mr-1"></i>Xác nhận mật khẩu mới
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Nhập lại mật khẩu mới" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Mật khẩu mới phải có ít nhất 6 ký tự và bao gồm cả chữ và số để đảm bảo an toàn.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Hủy
                    </button>
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key mr-1"></i>Đổi mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Wait for all scripts to load before initializing
    setTimeout(function() {
        // Check if jQuery is loaded
        if (typeof $ === 'undefined') {
            console.error('jQuery is not loaded!');
            return;
        }
        
        console.log('manage_profile.php JavaScript initializing...');
        
        // Test dropdown functionality immediately
        console.log('Testing dropdown immediately...');
        const testDropdown = function() {
            const submenuToggles = document.querySelectorAll('.submenu-toggle');
            console.log('Current submenu toggles found:', submenuToggles.length);
            
            submenuToggles.forEach((toggle, i) => {
                console.log(`Toggle ${i}:`, toggle, 'href:', toggle.getAttribute('href'));
            });
            
            const navItems = document.querySelectorAll('.nav-item.has-submenu');
            console.log('Nav items with submenu:', navItems.length);
            
            navItems.forEach((item, i) => {
                const isOpen = item.classList.contains('open');
                console.log(`Nav item ${i}:`, item, 'is open:', isOpen);
            });
        };
        
        // Test immediately and after delay
        testDropdown();
        setTimeout(testDropdown, 500);
        setTimeout(testDropdown, 1000);
        
        // Initialize dropdown functionality manually
        initializeDropdowns();
        
    }, 2000); // Increased delay to wait for all scripts to load
});

function initializeDropdowns() {
    console.log('Manually initializing dropdowns...');
    
    // Wrap all jQuery operations in try-catch to identify the problematic code
    try {
        // Animation on scroll
        function animateOnScroll() {
            $('.animate-on-scroll').each(function() {
                const elementTop = $(this).offset().top;
                const elementHeight = $(this).outerHeight();
                const windowHeight = $(window).height();
                const scrollY = window.scrollY;
                
                const delay = parseInt($(this).data('delay')) || 0;
                
                if (elementTop < (scrollY + windowHeight - elementHeight / 2)) {
                    setTimeout(() => {
                        $(this).addClass('visible');
                    }, delay);
                }
            });
        }
    
    // Execute animation on initial load
    setTimeout(function() {
        animateOnScroll();
    }, 100);
    
    // Execute animation on scroll
    $(window).on('scroll', function() {
        animateOnScroll();
    });
    
    // Counter animation
    $('.count-numbers').each(function () {
        const $this = $(this);
        const countTo = $this.text();
        
        if (countTo && !isNaN(countTo)) {
            const countToInt = parseInt(countTo);
            $({ countNum: 0 }).animate({
                countNum: countToInt
            }, {
                duration: 1000,
                easing: 'swing',
                step: function() {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function() {
                    $this.text(this.countNum);
                }
            });
        }
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = $('.alert');
    alerts.each(function() {
        setTimeout(() => {
            $(this).fadeOut('slow');
        }, 5000);
    });
    
    // Password confirmation validation
    const newPassword = $('#new_password');
    const confirmPassword = $('#confirm_password');
    
    function validatePassword() {
        if (newPassword.length && confirmPassword.length) {
            if (newPassword.val() !== confirmPassword.val()) {
                confirmPassword[0].setCustomValidity("Mật khẩu xác nhận không khớp");
            } else {
                confirmPassword[0].setCustomValidity('');
            }
        }
    }
    
    if (newPassword.length && confirmPassword.length) {
        newPassword.on('change', validatePassword);
        confirmPassword.on('keyup', validatePassword);
    }
    
    // Đảm bảo sidebar layout đồng nhất với dashboard
    if (typeof $ !== "undefined") {
        // Xóa mọi class có thể gây thu gọn sidebar
        $("body").removeClass("sidebar-toggled");
        
        // Đảm bảo sidebar có class cố định
        $(".modern-sidebar").addClass("fixed-sidebar");
        
        // Ẩn hoàn toàn các nút toggle
        $(".sidebar-collapse-toggle").hide();
        $(".toggle-sidebar-btn").hide();
        
        // Đảm bảo layout đúng
        $("#content-wrapper").css({
            "margin-left": "260px",
            "width": "calc(100% - 260px)",
            "padding-left": "15px",
            "padding-right": "15px"
        });
        
        // Đảm bảo container-fluid có padding phù hợp
        $(".container-fluid").css({
            "padding-left": "15px",
            "padding-right": "15px",
            "max-width": "none"
        });
        
        // Đảm bảo sidebar không thể bị thay đổi
        setInterval(function() {
            $("body").removeClass("sidebar-toggled");
            $(".modern-sidebar, .modern-research-sidebar").css({
                "width": "260px",
                "transform": "none",
                "display": "block"
            });
        }, 1000);
    }
    
    // Đảm bảo sidebar dropdown hoạt động - Fix trực tiếp
    setTimeout(function() {
        console.log('Initializing dropdown functionality...');
        
        // Tạo lại event handlers cho dropdown
        const submenuToggles = document.querySelectorAll('.submenu-toggle');
        console.log('Found submenu toggles:', submenuToggles.length);
        
        submenuToggles.forEach((toggle, index) => {
            console.log('Setting up toggle', index, toggle);
            
            // Remove existing event listeners
            const newToggle = toggle.cloneNode(true);
            toggle.parentNode.replaceChild(newToggle, toggle);
            
            // Add fresh event listener
            newToggle.addEventListener('click', function(e) {
                console.log('Dropdown clicked!', this);
                e.preventDefault();
                e.stopPropagation();
                
                const navItem = this.closest('.nav-item');
                if (!navItem) {
                    console.error('No nav-item found for toggle');
                    return;
                }
                
                const isOpen = navItem.classList.contains('open');
                console.log('Current state:', isOpen ? 'open' : 'closed');
                
                // Close all other submenus
                document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
                    if (item !== navItem) {
                        item.classList.remove('open');
                    }
                });
                
                // Toggle current submenu
                if (isOpen) {
                    navItem.classList.remove('open');
                    console.log('Closed submenu');
                } else {
                    navItem.classList.add('open');
                    console.log('Opened submenu');
                }
            });
        });
        
        // Also handle direct clicks on nav items
        const navItems = document.querySelectorAll('.nav-item.has-submenu');
        navItems.forEach(item => {
            const link = item.querySelector('.nav-link');
            if (link && link.getAttribute('href') === '#') {
                link.style.cursor = 'pointer';
            }
        });
// Additional dropdown initialization
function initializeDropdownsDirectly() {
    console.log('Direct dropdown initialization...');
    
    // Force dropdown functionality
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach((toggle, index) => {
        console.log(`Setting up toggle ${index}:`, toggle);
        
        // Remove any existing listeners and create fresh ones
        const newToggle = toggle.cloneNode(true);
        toggle.parentNode.replaceChild(newToggle, toggle);
        
        newToggle.addEventListener('click', function(e) {
            console.log('Dropdown clicked!', this);
            e.preventDefault();
            e.stopPropagation();
            
            const navItem = this.closest('.nav-item');
            if (!navItem) {
                console.error('No nav-item found for toggle');
                return;
            }
            
            const isOpen = navItem.classList.contains('open');
            console.log('Current state:', isOpen ? 'open' : 'closed');
            
            // Close all other submenus
            document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
                if (item !== navItem) {
                    item.classList.remove('open');
                }
            });
            
            // Toggle current submenu
            if (isOpen) {
                navItem.classList.remove('open');
                console.log('Closed submenu');
            } else {
                navItem.classList.add('open');
                console.log('Opened submenu');
            }
        });
    });
}

// Call dropdown initialization multiple times to ensure it works
setTimeout(initializeDropdownsDirectly, 1000);
setTimeout(initializeDropdownsDirectly, 3000);
setTimeout(initializeDropdownsDirectly, 5000);
}

// Additional dropdown initialization
function initializeDropdownsDirectly() {
});
</script>

<?php 
// Include footer
include '../../include/research_footer.php';
?>
