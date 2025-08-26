<?php
// filepath: d:\xampp\htdocs\NLNganh\include\admin_sidebar.php
?>

<!-- Sidebar Admin -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2>QUẢN TRỊ HỆ THỐNG</h2>
    </div>

    <div class="user-info">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-details">
            <h3><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin'; ?></h3>
            <p>Quản trị viên</p>
        </div>
    </div>

    <div class="separator"></div>

    <nav>
        <ul>
            <li>
                <a href="/NLNganh/view/admin/admin_dashboard.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_dashboard.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Bảng điều khiển</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/admin/user_manage/manage_users.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'user_manage') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Quản lý người dùng</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/admin/manage_projects/manage_projects.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'manage_projects') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-project-diagram"></i>
                    <span>Quản lý đề tài</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/admin/manage_advisor.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'manage_advisor') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Quản lý CVHT</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/admin/manage_departments/manage_departments.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'manage_departments') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Quản lý các khoa</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/admin/reports.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'reports.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Báo cáo</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/admin/settings.php"
                    class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'settings.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Cài đặt</span>
                </a>
            </li>
            <li class="separator"></li>
            <li>
                <a href="/NLNganh/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> - NCKH System</p>
    </div>
</div>

<!-- Thêm Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
    body,
    html {
        overflow-x: hidden;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Sidebar styles */
    .sidebar {
        width: 250px;
        height: 100vh;
        background: linear-gradient(135deg, #1a2035 0%, #242939 100%);
        color: #fff;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    /* Sidebar Header */
    .sidebar-header {
        padding: 20px 15px;
        text-align: center;
        background-color: rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-header h2 {
        font-size: 16px;
        margin: 10px 0 0;
        color: #fff;
        font-weight: 600;
        letter-spacing: 1px;
    }

    .logo {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }

    /* User info section */
    .user-info {
        display: flex;
        align-items: center;
        padding: 15px;
        background-color: rgba(0, 0, 0, 0.05);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #3498db;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .user-avatar i {
        font-size: 20px;
        color: #fff;
    }

    .user-details h3 {
        font-size: 14px;
        margin: 0;
        color: #fff;
        font-weight: 600;
    }

    .user-details p {
        font-size: 12px;
        margin: 5px 0 0;
        color: rgba(255, 255, 255, 0.7);
    }

    /* Separator */
    .separator {
        height: 1px;
        background-color: rgba(255, 255, 255, 0.1);
        margin: 10px 0;
    }

    /* Navigation */
    .sidebar nav ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    .sidebar nav ul li {
        margin: 5px 0;
    }

    .sidebar nav ul li a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .sidebar nav ul li a:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #fff;
        border-left-color: #3498db;
    }

    .sidebar nav ul li a.active {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-left-color: #3498db;
    }

    .sidebar nav ul li a i {
        margin-right: 10px;
        font-size: 16px;
        width: 20px;
        text-align: center;
    }

    .sidebar nav ul li a span {
        font-size: 14px;
    }

    /* Sidebar Footer */
    .sidebar-footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        text-align: center;
        padding: 10px 0;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.5);
        background-color: rgba(0, 0, 0, 0.1);
    }

    /* Điều chỉnh khoảng cách cho nội dung chính */
    .main-content,
    .container-fluid,
    div[class^="container"],
    body>div:not(.sidebar):not(.sidebar-footer) {
        padding: 30px !important;
        /* Thêm padding cho nội dung */
        max-width: calc(100vw - 280px) !important;
        transition: margin-left 0.3s ease;
    }

    /* Card styles */
    .card-deck .card {
        min-width: 250px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-deck .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    /* Header and content wrappers */
    .content-wrapper {
        padding-top: 20px;
        padding-bottom: 20px;
    }

    .page-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e9ecef;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
        }

        .sidebar:hover {
            width: 250px;
        }

        .sidebar .sidebar-header h2,
        .sidebar .user-details,
        .sidebar nav ul li a span {
            display: none;
        }

        .sidebar:hover .sidebar-header h2,
        .sidebar:hover .user-details,
        .sidebar:hover nav ul li a span {
            display: block;
        }

        .sidebar-header {
            padding: 10px;
        }

        .logo {
            width: 40px;
            height: 40px;
        }

        /* Điều chỉnh khoảng cách cho màn hình nhỏ */
        .main-content,
        .container-fluid,
        div[class^="container"],
        body>div:not(.sidebar):not(.sidebar-footer) {
            margin-left: 85px !important;
            /* Tăng lên từ 70px */
            padding: 15px !important;
            max-width: calc(100vw - 85px) !important;
        }
    }
</style>

<!-- Add this script to ensure the main content has proper margin -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tìm tất cả container chính và thêm khoảng cách
        var containers = document.querySelectorAll('.container-fluid, .container');
        containers.forEach(function (container) {
            if (!container.closest('.sidebar')) {
                container.style.marginLeft = '280px';
                container.style.maxWidth = 'calc(100vw - 280px)';
                container.style.padding = '30px';
            }
        });

        // Nếu không có container nào, thêm class vào body hoặc div đầu tiên sau sidebar
        if (containers.length === 0) {
            var mainContent = document.querySelector('body > div:not(.sidebar)');
            if (mainContent) {
                mainContent.classList.add('main-content');
            }
        }
    });
</script>