<?php
// filepath: d:\xampp\htdocs\NLNganh\include\modern_research_sidebar.php
// Modern Research Manager Sidebar with enhanced design

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
        // Log error nhưng vẫn tiếp tục
        error_log("Database error in modern_research_sidebar.php: " . $e->getMessage());
        $manager_info = null;
    }
}

// Lấy URL hiện tại để highlight menu active
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<style>
    :root {
        --sidebar-bg: linear-gradient(135deg, #1a365d 0%, #2563eb 100%);
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 60px;
        --sidebar-text: #ffffff;
        --sidebar-text-secondary: rgba(255, 255, 255, 0.8);
        --sidebar-accent: #f59e0b;
        --sidebar-hover: rgba(255, 255, 255, 0.1);
        --sidebar-active: rgba(245, 158, 11, 0.2);
        --sidebar-border: rgba(255, 255, 255, 0.1);
        --sidebar-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Sidebar Container */
    .modern-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--sidebar-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-right: 1px solid var(--sidebar-border);
        box-shadow: var(--sidebar-shadow);
        transition: var(--transition-smooth);
        z-index: 1000;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .modern-sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    /* Header Section */
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--sidebar-border);
        position: relative;
        overflow: hidden;
    }

    .sidebar-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
        pointer-events: none;
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--sidebar-text);
        text-decoration: none;
        transition: var(--transition-smooth);
    }

    .sidebar-logo:hover {
        color: var(--sidebar-accent);
        text-decoration: none;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--sidebar-accent) 0%, #fbbf24 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        transition: var(--transition-smooth);
        flex-shrink: 0;
    }

    .logo-icon:hover {
        transform: rotate(5deg) scale(1.05);
    }

    .logo-text {
        font-size: 1.2rem;
        font-weight: 700;
        white-space: nowrap;
        transition: var(--transition-smooth);
    }

    .collapsed .logo-text {
        opacity: 0;
        transform: translateX(-10px);
    }

    /* User Profile Section */
    .sidebar-user {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--sidebar-border);
        display: flex;
        align-items: center;
        gap: 12px;
        transition: var(--transition-smooth);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        font-weight: 600;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .user-info {
        flex: 1;
        min-width: 0;
        transition: var(--transition-smooth);
    }

    .user-name {
        color: var(--sidebar-text);
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-role {
        color: var(--sidebar-text-secondary);
        font-size: 0.75rem;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .collapsed .user-info {
        opacity: 0;
        transform: translateX(-10px);
    }

    /* Navigation Menu */
    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 1rem 0;
    }

    .sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-nav::-webkit-scrollbar-thumb {
        background: var(--sidebar-border);
        border-radius: 2px;
    }

    .nav-section {
        margin-bottom: 1.5rem;
    }

    .nav-section-title {
        color: var(--sidebar-text-secondary);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0 1.5rem 0.5rem;
        transition: var(--transition-smooth);
    }

    .collapsed .nav-section-title {
        opacity: 0;
        height: 0;
        padding: 0;
        margin: 0;
    }

    .nav-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-item {
        margin: 0 0.75rem 0.25rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0.75rem 0.75rem;
        color: var(--sidebar-text-secondary);
        text-decoration: none;
        border-radius: 12px;
        transition: var(--transition-smooth);
        position: relative;
        overflow: hidden;
    }

    .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.6s;
    }

    .nav-link:hover::before {
        left: 100%;
    }

    .nav-link:hover {
        color: var(--sidebar-text);
        background: var(--sidebar-hover);
        text-decoration: none;
        transform: translateX(4px);
    }

    .nav-link.active {
        color: var(--sidebar-text);
        background: var(--sidebar-active);
        border-left: 3px solid var(--sidebar-accent);
    }

    .nav-icon {
        width: 20px;
        height: 20px;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: var(--transition-smooth);
    }

    .nav-text {
        font-size: 0.9rem;
        font-weight: 500;
        white-space: nowrap;
        transition: var(--transition-smooth);
    }

    .collapsed .nav-text {
        opacity: 0;
        transform: translateX(-10px);
    }

    .nav-badge {
        background: var(--sidebar-accent);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 8px;
        margin-left: auto;
        transition: var(--transition-smooth);
    }

    .collapsed .nav-badge {
        opacity: 0;
        transform: scale(0.8);
    }

    /* Dropdown Menu */
    .nav-dropdown {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .nav-dropdown.show {
        max-height: 300px;
    }

    .nav-dropdown .nav-link {
        padding-left: 3rem;
        font-size: 0.85rem;
    }

    .dropdown-toggle::after {
        content: '\f107';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        margin-left: auto;
        transition: transform 0.3s ease;
    }

    .dropdown-toggle.collapsed::after {
        transform: rotate(-90deg);
    }

    /* Toggle Button */
    .sidebar-toggle {
        position: absolute;
        top: 20px;
        right: -15px;
        width: 30px;
        height: 30px;
        background: var(--sidebar-bg);
        border: 2px solid var(--sidebar-border);
        border-radius: 50%;
        color: var(--sidebar-text);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition-smooth);
        z-index: 1001;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .sidebar-toggle:hover {
        background: var(--sidebar-accent);
        transform: scale(1.1);
    }

    /* Footer */
    .sidebar-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--sidebar-border);
        background: rgba(0, 0, 0, 0.1);
    }

    .footer-text {
        color: var(--sidebar-text-secondary);
        font-size: 0.75rem;
        text-align: center;
        margin: 0;
        transition: var(--transition-smooth);
    }

    .collapsed .footer-text {
        opacity: 0;
    }

    /* Content Adjustment */
    .content-wrapper {
        margin-left: var(--sidebar-width);
        transition: var(--transition-smooth);
    }

    .sidebar-collapsed .content-wrapper {
        margin-left: var(--sidebar-collapsed-width);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .modern-sidebar {
            transform: translateX(-100%);
        }

        .modern-sidebar.show {
            transform: translateX(0);
        }

        .content-wrapper {
            margin-left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-smooth);
        }

        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }
    }

    /* Loading Animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .loading {
        animation: pulse 2s infinite;
    }

    /* Smooth Scrollbar */
    .sidebar-nav {
        scrollbar-width: thin;
        scrollbar-color: var(--sidebar-border) transparent;
    }
</style>

<!-- Mobile Overlay -->
<div class="sidebar-overlay d-md-none" onclick="toggleMobileSidebar()"></div>

<!-- Modern Sidebar -->
<aside class="modern-sidebar" id="modernSidebar">
    <!-- Toggle Button -->
    <button class="sidebar-toggle d-none d-md-flex" onclick="toggleSidebar()" title="Thu gọn sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>

    <!-- Header Section -->
    <div class="sidebar-header">
        <a href="/NLNganh/view/research/research_dashboard.php" class="sidebar-logo">
            <div class="logo-icon">
                <i class="fas fa-atom"></i>
            </div>
            <div class="logo-text">NCKH System</div>
        </a>
    </div>

    <!-- User Profile Section -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <?php 
            if ($manager_info) {
                echo strtoupper(substr($manager_info['QL_HO'], 0, 1) . substr($manager_info['QL_TEN'], 0, 1));
            } else {
                echo 'QL';
            }
            ?>
        </div>
        <div class="user-info">
            <p class="user-name">
                <?php 
                if ($manager_info) {
                    echo htmlspecialchars($manager_info['QL_HO'] . ' ' . $manager_info['QL_TEN']);
                } else {
                    echo 'Quản lý nghiên cứu';
                }
                ?>
            </p>
            <p class="user-role">Research Manager</p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <!-- Dashboard Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">Dashboard</h6>
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

        <!-- Project Management Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">Quản lý đề tài</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/manage_projects.php" 
                       class="nav-link <?php echo ($current_page == 'manage_projects.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-project-diagram"></i>
                        <span class="nav-text">Danh sách đề tài</span>
                        <span class="nav-badge">16</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/NLNganh/view/research/review_projects.php" 
                       class="nav-link <?php echo ($current_page == 'review_projects.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-clipboard-check"></i>
                        <span class="nav-text">Duyệt đề tài</span>
                        <span class="nav-badge">3</span>
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a href="/NLNganh/view/research/batch_approve.php" 
                       class="nav-link <?php echo ($current_page == 'batch_approve.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tasks"></i>
                        <span class="nav-text">Duyệt hàng loạt</span>
                    </a>
                </li> -->
            </ul>
        </div>

        <!-- Research Management Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">Quản lý nghiên cứu</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/manage_researchers.php" 
                       class="nav-link <?php echo ($current_page == 'manage_researchers.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <span class="nav-text">Quản lý nghiên cứu viên</span>
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a href="/NLNganh/view/research/researcher_projects.php" 
                       class="nav-link <?php echo ($current_page == 'researcher_projects.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-microscope"></i>
                        <span class="nav-text">Đề tài nghiên cứu viên</span>
                    </a>
                </li> -->
                <li class="nav-item">
                    <a href="/NLNganh/view/research/publications.php" 
                       class="nav-link <?php echo ($current_page == 'publications.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-book-open"></i>
                        <span class="nav-text">Xuất bản</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Reports & Analytics Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">Báo cáo & Thống kê</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/research_reports.php" 
                       class="nav-link <?php echo ($current_page == 'research_reports.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <span class="nav-text">Báo cáo nghiên cứu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="collapse" data-target="#exportSubmenu">
                        <i class="nav-icon fas fa-download"></i>
                        <span class="nav-text">Xuất dữ liệu</span>
                    </a>
                    <div class="nav-dropdown collapse" id="exportSubmenu">
                        <a href="/NLNganh/view/research/export_researchers.php" class="nav-link">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <span class="nav-text">Xuất nghiên cứu viên</span>
                        </a>
                        <a href="/NLNganh/view/research/export_students.php" class="nav-link">
                            <i class="nav-icon fas fa-graduation-cap"></i>
                            <span class="nav-text">Xuất sinh viên</span>
                        </a>
                        <a href="/NLNganh/view/research/export_report.php" class="nav-link">
                            <i class="nav-icon fas fa-file-excel"></i>
                            <span class="nav-text">Xuất báo cáo</span>
                        </a>
                    </div>
                </li>
            </ul>
        </div>

        <!-- System Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">Hệ thống</h6>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/NLNganh/view/research/notifications.php" 
                       class="nav-link <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-bell"></i>
                        <span class="nav-text">Thông báo</span>
                        <span class="nav-badge">5</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/NLNganh/view/research/settings.php" 
                       class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <span class="nav-text">Cài đặt</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/NLNganh/view/research/manage_profile.php" 
                       class="nav-link <?php echo ($current_page == 'manage_profile.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-circle"></i>
                        <span class="nav-text">Hồ sơ cá nhân</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <p class="footer-text">© 2025 NCKH System</p>
    </div>
</aside>

<script>
    // Sidebar functionality
    function toggleSidebar() {
        const sidebar = document.getElementById('modernSidebar');
        const body = document.body;
        
        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        
        // Update toggle icon
        const toggleIcon = document.querySelector('.sidebar-toggle i');
        if (isCollapsed) {
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        } else {
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');
        }
    }
    
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('modernSidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
    
    // Initialize sidebar state from localStorage
    document.addEventListener('DOMContentLoaded', function() {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        const sidebar = document.getElementById('modernSidebar');
        const body = document.body;
        
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
            
            const toggleIcon = document.querySelector('.sidebar-toggle i');
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        }
        
        // Handle dropdown menus
        document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                const target = this.getAttribute('data-target');
                const dropdown = document.querySelector(target);
                
                if (dropdown) {
                    dropdown.classList.toggle('show');
                    this.classList.toggle('collapsed');
                }
            });
        });
        
        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('modernSidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                if (!sidebar.contains(e.target) && !e.target.closest('.navbar-toggler')) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('modernSidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
        
        // Add smooth scroll animation to nav links
        document.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                // Add loading effect
                this.classList.add('loading');
                
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 1000);
            });
        });
    });
    
    // Smooth hover effects
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
            });
            
            link.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(0)';
                }
            });
        });
    });
</script>
