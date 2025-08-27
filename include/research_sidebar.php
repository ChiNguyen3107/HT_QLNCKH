<?php
// filepath: d:\xampp\htdocs\NLNganh\include\research_sidebar.php

// Kiểm tra biến session nếu đăng nhập với vai trò research_manager
include_once 'session.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != ROLE_RESEARCH_MANAGER && $_SESSION['role'] != ROLE_ADMIN)) {
    header("Location: /NLNganh/login.php");
    exit;
}

// Lấy thông tin quản lý nghiên cứu
$manager_id = $_SESSION['user_id'];
$manager_info = null;

// Kết nối cơ sở dữ liệu nếu chưa được kết nối
if (!isset($conn)) {
    include_once 'connect.php';
}

// Kiểm tra trong bảng quan_ly_nghien_cuu
$manager_query = "SELECT ql.*, k.DV_TENDV 
                 FROM quan_ly_nghien_cuu ql 
                 LEFT JOIN khoa k ON ql.DV_MADV = k.DV_MADV 
                 WHERE ql.QL_MA = ?";
$stmt = $conn->prepare($manager_query);
if ($stmt) {
    $stmt->bind_param("s", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $manager_info = $result->fetch_assoc();
    }
    $stmt->close();
}

// Nếu không tìm thấy thông tin trong bảng quan_ly_nghien_cuu, sử dụng thông tin từ bảng user
if (!$manager_info) {
    $user_query = "SELECT * FROM user WHERE USERNAME = ? AND ROLE = 'research_manager'";
    $stmt = $conn->prepare($user_query);
    if ($stmt) {
        $username = $_SESSION['username'] ?? $manager_id;
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_info = $result->fetch_assoc();
            $manager_info = [
                'QL_MA' => $user_info['USER_ID'] ?? $manager_id,
                'QL_HO' => 'Quản lý',
                'QL_TEN' => 'Nghiên cứu',
                'DV_TENDV' => 'Ban Nghiên cứu Khoa học'
            ];
        }
        $stmt->close();
    }
}

// Xác định trang hiện tại để đánh dấu menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Modern Sidebar for Research Management -->
<div class="modern-sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <h2>QUẢN LÝ NGHIÊN CỨU</h2>
    </div>
    
    <!-- User Profile Section -->
    <div class="user-profile">
        <div class="user-avatar">
            <i class="fas fa-microscope"></i>
        </div>
        <div class="user-info">
            <?php if ($manager_info): ?>
                <h3><?php echo htmlspecialchars($manager_info['QL_HO'] . ' ' . $manager_info['QL_TEN']); ?></h3>
                <p><?php echo htmlspecialchars($manager_info['DV_TENDV'] ?? 'Quản lý nghiên cứu'); ?></p>
            <?php else: ?>
                <h3><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Quản lý nghiên cứu'; ?></h3>
                <p>Quản lý nghiên cứu</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="separator"></div>
    
    <!-- Main Navigation -->
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="/NLNganh/view/research/research_dashboard.php" class="<?php echo ($current_page === 'research_dashboard.php') ? 'active' : ''; ?>" data-title="Bảng điều khiển">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Bảng điều khiển</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/research/manage_profile.php" class="<?php echo ($current_page === 'manage_profile.php') ? 'active' : ''; ?>" data-title="Hồ sơ cá nhân">
                    <i class="fas fa-user-edit"></i>
                    <span>Hồ sơ cá nhân</span>
                </a>
            </li>
            <li class="has-submenu <?php echo (strpos($current_page, 'project') !== false || strpos($current_page, 'review_projects') !== false) ? 'open' : ''; ?>">
                <a href="#">
                    <i class="fas fa-folder"></i>
                    <span>Quản lý đề tài</span>
                </a>
                <ul class="submenu <?php echo (strpos($current_page, 'project') !== false || strpos($current_page, 'review_projects') !== false) ? 'active' : ''; ?>">
                    <li>
                        <a href="/NLNganh/view/research/manage_projects.php" class="<?php echo ($current_page === 'manage_projects.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            <span>Danh sách đề tài</span>
                        </a>
                    </li>
                    <!-- <li>
                        <a href="/NLNganh/view/research/create_project.php" class="<?php echo ($current_page === 'create_project.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus"></i>
                            <span>Thêm đề tài mới</span>
                        </a>
                    </li> -->
                    <li>
                        <a href="/NLNganh/view/research/review_projects.php" class="<?php echo ($current_page === 'review_projects.php') ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i>
                            <span>Phê duyệt đề tài</span>
                        </a>
                    </li>
                </ul>
            </li>            <li class="has-submenu <?php echo ($current_page === 'research_reports.php') ? 'open' : ''; ?>">
                <a href="#">
                    <i class="fas fa-chart-area"></i>
                    <span>Báo cáo thống kê</span>
                </a>
                <ul class="submenu <?php echo ($current_page === 'research_reports.php') ? 'active' : ''; ?>">
                    <li>
                        <a href="/NLNganh/view/research/research_reports.php" class="<?php echo ($current_page === 'research_reports.php' && !isset($_GET['tab'])) ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie"></i>
                            <span>Tổng quan</span>
                        </a>
                    </li>
                    <li>
                        <a href="/NLNganh/view/research/research_reports.php?tab=teachers" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'teachers') ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Thống kê giảng viên</span>
                        </a>
                    </li>
                    <li>
                        <a href="/NLNganh/view/research/research_reports.php?tab=students" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'students') ? 'active' : ''; ?>">
                            <i class="fas fa-user-graduate"></i>
                            <span>Thống kê sinh viên</span>
                        </a>
                    </li>
                    <li>
                        <a href="/NLNganh/view/research/research_reports.php?tab=classes" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'classes') ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Thống kê theo lớp</span>
                        </a>
                    </li>
                    <li>
                        <a href="/NLNganh/view/research/research_reports.php?tab=monthly" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'monthly') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Thống kê theo tháng</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="/NLNganh/view/research/manage_researchers.php" class="<?php echo ($current_page === 'manage_researchers.php') ? 'active' : ''; ?>" data-title="Quản lý nhà nghiên cứu">
                    <i class="fas fa-users-cog"></i>
                    <span>Quản lý nhà nghiên cứu</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/research/manage_advisor.php" class="<?php echo ($current_page === 'manage_advisor.php') ? 'active' : ''; ?>" data-title="Quản lý CVHT">
                    <i class="fas fa-user-tie"></i>
                    <span>Quản lý CVHT</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/research/publications.php" class="<?php echo ($current_page === 'publications.php') ? 'active' : ''; ?>" data-title="Ấn phẩm">
                    <i class="fas fa-book"></i>
                    <span>Ấn phẩm</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/research/notifications.php" class="<?php echo ($current_page === 'notifications.php') ? 'active' : ''; ?>" data-title="Thông báo">
                    <i class="fas fa-bell"></i>
                    <span>Thông báo</span>
                    <span class="notification-badge" id="notification-count">0</span>
                </a>
            </li>
            <li class="separator"></li>
            <li>
                <a href="/NLNganh/logout.php" data-title="Đăng xuất">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Extended User Information -->
    <?php if (isset($manager_info)): ?>
    <div class="user-extended-info">
        <div class="info-item">
            <i class="fas fa-envelope"></i>
            <span title="<?php echo htmlspecialchars($manager_info['QL_EMAIL'] ?? ''); ?>"><?php echo htmlspecialchars($manager_info['QL_EMAIL'] ?? 'Chưa cập nhật'); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-phone"></i>
            <span title="<?php echo htmlspecialchars($manager_info['QL_SDT'] ?? ''); ?>"><?php echo htmlspecialchars($manager_info['QL_SDT'] ?? 'Chưa cập nhật'); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-university"></i>
            <span title="<?php echo htmlspecialchars($manager_info['DV_TENDV'] ?? ''); ?>"><?php echo htmlspecialchars($manager_info['DV_TENDV'] ?? 'Chưa cập nhật'); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> - Hệ thống NCKH</p>
    </div>
</div>

<!-- Đã loại bỏ các nút toggle để sidebar luôn cố định -->
<!-- CSS và JavaScript sẽ đảm bảo sidebar không thể thu gọn -->

<?php
// Thêm CSS inline để đảm bảo sidebar cố định
echo '<style>
/* Đảm bảo sidebar luôn cố định */
.modern-sidebar {
    position: fixed !important;
    width: 260px !important;
    height: 100vh !important;
    left: 0 !important;
    top: 0 !important;
    transform: none !important;
    display: block !important;
}

/* Đảm bảo content có margin phù hợp */
#content-wrapper {
    margin-left: 260px !important;
    width: calc(100% - 260px) !important;
}

/* Ẩn hoàn toàn các nút toggle */
.sidebar-collapse-toggle,
.toggle-sidebar-btn {
    display: none !important;
}

/* Vô hiệu hóa trạng thái toggled */
body.sidebar-toggled .modern-sidebar {
    width: 260px !important;
    transform: none !important;
}

body.sidebar-toggled #content-wrapper {
    margin-left: 260px !important;
    width: calc(100% - 260px) !important;
}
</style>';
?>
