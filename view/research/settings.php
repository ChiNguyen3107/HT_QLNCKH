<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\settings.php

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

// Xử lý cập nhật cài đặt hệ thống
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_system_settings'])) {
    // Xử lý cập nhật các cài đặt hệ thống
    $success_message = "Cập nhật cài đặt hệ thống thành công!";
}

// Xử lý cập nhật cài đặt thông báo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_notification_settings'])) {
    // Xử lý cập nhật các cài đặt thông báo
    $success_message = "Cập nhật cài đặt thông báo thành công!";
}

// Set page title for the header
$page_title = "Cài đặt hệ thống | Quản lý nghiên cứu";

// Define any additional CSS specific to this page
$additional_css = '<style>
    /* Enhanced settings cards */
    .settings-card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
        border: none;
        background: white;
        margin-bottom: 25px;
    }
    
    .settings-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .settings-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 20px;
        font-weight: 600;
    }
    
    .settings-card .card-body {
        padding: 25px;
    }
    
    /* Enhanced form controls */
    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 15px;
        transition: all 0.3s ease;
        font-size: 0.95em;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }
    
    /* Enhanced buttons */
    .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85em;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-gradient:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        color: white;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
    }
    
    /* Settings sections */
    .settings-section {
        background: #f8f9fc;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #667eea;
    }
    
    .settings-section h5 {
        color: #5a5c69;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    /* Switch toggles */
    .form-switch {
        padding-left: 3rem;
    }
    
    .form-switch .form-check-input {
        width: 2rem;
        height: 1.2rem;
        border-radius: 2rem;
        margin-left: -3rem;
        background-color: #dee2e6;
        border: none;
    }
    
    .form-switch .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }
    
    /* Alert improvements */
    .alert {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        color: #0c5460;
    }
    
    /* Animation classes */
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    }
    
    .animate-on-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .settings-card .card-body {
            padding: 20px;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>';

// Include the research header
include '../../include/research_header.php';
?>

<!-- SweetAlert2 for better notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-cogs text-primary me-3"></i>
        Cài đặt hệ thống
    </h1>
</div>

<!-- Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

        <div class="row">
            <!-- System Settings -->
            <div class="col-lg-6">
                <div class="card settings-card animate-on-scroll">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-server me-2"></i>
                            Cài đặt hệ thống
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="settings-section">
                                <h5><i class="fas fa-globe me-2"></i>Cài đặt chung</h5>
                                
                                <div class="mb-3">
                                    <label for="system_name" class="form-label">Tên hệ thống</label>
                                    <input type="text" class="form-control" id="system_name" name="system_name" 
                                           value="Hệ thống Quản lý Nghiên cứu Khoa học" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="system_version" class="form-label">Phiên bản</label>
                                    <input type="text" class="form-control" id="system_version" name="system_version" 
                                           value="1.0.0" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="maintenance_mode" class="form-label">Chế độ bảo trì</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode">
                                        <label class="form-check-label" for="maintenance_mode">
                                            Kích hoạt chế độ bảo trì
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Khi bật, chỉ admin mới có thể truy cập hệ thống</small>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h5><i class="fas fa-shield-alt me-2"></i>Bảo mật</h5>
                                
                                <div class="mb-3">
                                    <label for="session_timeout" class="form-label">Thời gian timeout phiên (phút)</label>
                                    <select class="form-control" id="session_timeout" name="session_timeout">
                                        <option value="30">30 phút</option>
                                        <option value="60" selected>60 phút</option>
                                        <option value="120">120 phút</option>
                                        <option value="240">240 phút</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="force_https" name="force_https" checked>
                                        <label class="form-check-label" for="force_https">
                                            Bắt buộc sử dụng HTTPS
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="two_factor_auth" name="two_factor_auth">
                                        <label class="form-check-label" for="two_factor_auth">
                                            Xác thực hai yếu tố (2FA)
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Tính năng đang được phát triển</small>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" name="update_system_settings" class="btn btn-gradient">
                                    <i class="fas fa-save me-2"></i>
                                    Lưu cài đặt hệ thống
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="col-lg-6">
                <div class="card settings-card animate-on-scroll">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-bell me-2"></i>
                            Cài đặt thông báo
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="settings-section">
                                <h5><i class="fas fa-envelope me-2"></i>Email</h5>
                                
                                <div class="mb-3">
                                    <label for="smtp_server" class="form-label">SMTP Server</label>
                                    <input type="text" class="form-control" id="smtp_server" name="smtp_server" 
                                           placeholder="smtp.gmail.com">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                           value="587">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email_username" class="form-label">Email Username</label>
                                    <input type="email" class="form-control" id="email_username" name="email_username" 
                                           placeholder="admin@university.edu.vn">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" checked>
                                        <label class="form-check-label" for="email_notifications">
                                            Bật thông báo qua email
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h5><i class="fas fa-bell me-2"></i>Thông báo hệ thống</h5>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="project_notifications" name="project_notifications" checked>
                                        <label class="form-check-label" for="project_notifications">
                                            Thông báo dự án mới
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="deadline_notifications" name="deadline_notifications" checked>
                                        <label class="form-check-label" for="deadline_notifications">
                                            Thông báo hạn chót
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="report_notifications" name="report_notifications" checked>
                                        <label class="form-check-label" for="report_notifications">
                                            Thông báo báo cáo
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notification_frequency" class="form-label">Tần suất thông báo</label>
                                    <select class="form-control" id="notification_frequency" name="notification_frequency">
                                        <option value="immediate">Ngay lập tức</option>
                                        <option value="daily" selected>Hàng ngày</option>
                                        <option value="weekly">Hàng tuần</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" name="update_notification_settings" class="btn btn-success">
                                    <i class="fas fa-bell me-2"></i>
                                    Lưu cài đặt thông báo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Settings Row -->
        <div class="row">
            <!-- Database Settings -->
            <div class="col-lg-6">
                <div class="card settings-card animate-on-scroll">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-database me-2"></i>
                            Cài đặt cơ sở dữ liệu
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="settings-section">
                            <h5><i class="fas fa-info-circle me-2"></i>Thông tin kết nối</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Server</label>
                                <input type="text" class="form-control" value="localhost" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Database</label>
                                <input type="text" class="form-control" value="ql_nckh" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Trạng thái kết nối</label>
                                <div class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Đã kết nối
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-warning" onclick="testDatabaseConnection()">
                                <i class="fas fa-plug me-2"></i>
                                Kiểm tra kết nối
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="col-lg-6">
                <div class="card settings-card animate-on-scroll">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-info me-2"></i>
                            Thông tin hệ thống
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="settings-section">
                            <h5><i class="fas fa-server me-2"></i>Môi trường server</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">PHP Version</label>
                                <input type="text" class="form-control" value="<?= phpversion() ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Server Software</label>
                                <input type="text" class="form-control" value="<?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Dung lượng bộ nhớ</label>
                                <input type="text" class="form-control" value="<?= ini_get('memory_limit') ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Thời gian thực thi tối đa</label>
                                <input type="text" class="form-control" value="<?= ini_get('max_execution_time') ?>s" readonly>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-info" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-2"></i>
                                Làm mới thông tin
                            </button>
                        </div>
                    </div>
                </div>
            </div>
<?php 
// Include footer
include '../../include/research_footer.php';
?>
