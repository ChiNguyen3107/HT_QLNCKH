<?php
// filepath: d:\xampp\htdocs\NLNganh\include\simple_research_sidebar.php
// Simple and Clean Research Manager Sidebar

// Include các file cần thiết
include_once __DIR__ . '/config.php';
include_once __DIR__ . '/connect.php';
include_once __DIR__ . '/session.php';

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /NLNganh/login.php");
    exit;
}

// Kiểm tra role (chấp nhận research_manager hoặc admin)
if ($_SESSION['role'] !== 'research_manager' && $_SESSION['role'] !== 'admin') {
    header("Location: /NLNganh/login.php");
    exit;
}

// Lấy thông tin quản lý nghiên cứu
$manager_info = null;
if (isset($_SESSION['user_id']) && isset($conn) && $conn instanceof mysqli) {
    try {
        $sql = "SELECT * FROM quan_ly_nghien_cuu WHERE QL_MA = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $manager_info = $result->fetch_assoc();
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Database error in simple_research_sidebar.php: " . $e->getMessage());
        $manager_info = null;
    }
}

// Lấy URL hiện tại để highlight menu active
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<style>
/* Simple Sidebar Styles */
.simple-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: #2c3e50;
    color: #ecf0f1;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.simple-sidebar::-webkit-scrollbar {
    width: 6px;
}

.simple-sidebar::-webkit-scrollbar-track {
    background: #34495e;
}

.simple-sidebar::-webkit-scrollbar-thumb {
    background: #576574;
    border-radius: 3px;
}

/* Header */
.sidebar-header {
    padding: 20px;
    background: #34495e;
    border-bottom: 1px solid #3d556b;
    text-align: center;
}

.sidebar-logo {
    color: #ecf0f1;
    text-decoration: none;
    font-size: 1.2rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.sidebar-logo:hover {
    color: #3498db;
    text-decoration: none;
}

.logo-icon {
    background: #3498db;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

/* User Info */
.sidebar-user {
    padding: 15px 20px;
    background: #34495e;
    border-bottom: 1px solid #3d556b;
    text-align: center;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 500;
    margin: 0;
    color: #ecf0f1;
}

.user-role {
    font-size: 0.75rem;
    color: #bdc3c7;
    margin: 5px 0 0 0;
}

/* Navigation */
.sidebar-nav {
    padding: 20px 0;
}

.nav-section {
    margin-bottom: 25px;
}

.nav-section-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #95a5a6;
    padding: 0 20px 10px;
    margin: 0;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin: 2px 10px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #bdc3c7;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    gap: 12px;
}

.nav-link:hover {
    background: #34495e;
    color: #ecf0f1;
    text-decoration: none;
    transform: translateX(3px);
}

.nav-link.active {
    background: #3498db;
    color: #fff;
}

.nav-link.active:hover {
    background: #2980b9;
    transform: none;
}

.nav-icon {
    width: 18px;
    text-align: center;
    font-size: 14px;
}

.nav-text {
    font-size: 0.9rem;
    font-weight: 500;
}

/* Badge for counters */
.nav-badge {
    background: #e74c3c;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
}

/* Footer */
.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 15px 20px;
    background: #34495e;
    border-top: 1px solid #3d556b;
    text-align: center;
}

.sidebar-footer .nav-link {
    justify-content: center;
    margin: 0;
    padding: 8px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .simple-sidebar {
        width: 100%;
        transform: translateX(-100%);
    }
    
    .simple-sidebar.show {
        transform: translateX(0);
    }
    
    .content-wrapper {
        margin-left: 0 !important;
    }
}

/* Content wrapper adjustment */
.content-wrapper, #content-wrapper, #wrapper {
    margin-left: 250px;
    transition: margin-left 0.3s ease;
}

/* Remove body padding since we're using flexbox */
body {
    padding-left: 0 !important;
}

/* Reset on mobile */
@media (max-width: 768px) {    
    .content-wrapper, #content-wrapper, #wrapper {
        margin-left: 0 !important;
    }
}

/* Toggle button for mobile */
.sidebar-toggle {
    display: none;
}

@media (max-width: 768px) {
    .sidebar-toggle {
        display: inline-block;
    }
}

/* Additional content spacing fixes */
.container-fluid {
    padding-left: 15px;
    padding-right: 15px;
}

/* Ensure navbar doesn't get affected by sidebar */
.navbar.topbar {
    margin-left: 0;
    padding-left: 15px;
}
</style>

<!-- Simple Sidebar -->
<div class="simple-sidebar" id="simpleSidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <a href="/NLNganh/view/research/research_dashboard.php" class="sidebar-logo">
            <div class="logo-icon">
                <i class="fas fa-microscope"></i>
            </div>
            <span>Quản lý NC</span>
        </a>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="user-name">
            <?php 
            if ($manager_info) {
                echo htmlspecialchars($manager_info['QL_HO'] . ' ' . $manager_info['QL_TEN']);
            } else {
                echo 'Quản lý nghiên cứu';
            }
            ?>
        </div>
        <div class="user-role">Quản lý nghiên cứu</div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <div class="nav-section">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/research_dashboard.php" 
                       class="nav-link <?php echo ($current_page == 'research_dashboard.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <span class="nav-text">Bảng điều khiển</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Quản lý đề tài -->
        <div class="nav-section">
            <h6 class="nav-section-title">Quản lý đề tài</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/manage_projects.php" 
                       class="nav-link <?php echo ($current_page == 'manage_projects.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-folder-open"></i>
                        <span class="nav-text">Danh sách đề tài</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/NLNganh/view/research/review_projects.php" 
                       class="nav-link <?php echo ($current_page == 'review_projects.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-check-circle"></i>
                        <span class="nav-text">Phê duyệt đề tài</span>
                        <?php
                        // Đếm số đề tài chờ duyệt
                        if (isset($conn)) {
                            $pending_count = 0;
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Chờ duyệt'");
                            if ($stmt) {
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $pending_count = $result->fetch_assoc()['count'];
                                $stmt->close();
                                if ($pending_count > 0) {
                                    echo '<span class="nav-badge">' . $pending_count . '</span>';
                                }
                            }
                        }
                        ?>
                    </a>
                </li>
               
            </ul>
        </div>

        <!-- Quản lý người dùng -->
        <div class="nav-section">
            <h6 class="nav-section-title">Quản lý người dùng</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/manage_researchers.php" 
                       class="nav-link <?php echo ($current_page == 'manage_researchers.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <span class="nav-text">Nhà nghiên cứu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/NLNganh/view/research/manage_advisor.php" 
                       class="nav-link <?php echo ($current_page == 'manage_advisor.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <span class="nav-text">Quản lý CVHT</span>
                    </a>
                </li>
                
        </div>

        <!-- Báo cáo & Thống kê -->
        <div class="nav-section">
            <h6 class="nav-section-title">Báo cáo & Thống kê</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/research_reports.php" 
                       class="nav-link <?php echo ($current_page == 'research_reports.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <span class="nav-text">Báo cáo thống kê</span>
                    </a>
                </li>
                
            </ul>
        </div>

        <!-- Cài đặt -->
        <div class="nav-section">
            <h6 class="nav-section-title">Cài đặt</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/manage_profile.php" 
                       class="nav-link <?php echo ($current_page == 'manage_profile.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-cog"></i>
                        <span class="nav-text">Hồ sơ cá nhân</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/NLNganh/view/research/settings.php" 
                       class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <span class="nav-text">Cài đặt hệ thống</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/NLNganh/view/research/notifications.php" 
                       class="nav-link <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-bell"></i>
                        <span class="nav-text">Thông báo</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="/NLNganh/logout.php" class="nav-link" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <span class="nav-text">Đăng xuất</span>
        </a>
    </div>
</div>

<script>
// Simple sidebar toggle for mobile
function toggleMobileSidebar() {
    const sidebar = document.getElementById('simpleSidebar');
    sidebar.classList.toggle('show');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('simpleSidebar');
    const toggleBtn = document.getElementById('sidebarToggleTop');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('simpleSidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.remove('show');
    }
});
</script>
