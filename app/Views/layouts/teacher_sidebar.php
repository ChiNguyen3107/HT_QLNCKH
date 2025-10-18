<?php
// Lấy thông tin giảng viên từ CSDL để hiển thị đầy đủ
if (isset($_SESSION['user_id'])) {
    // Tạo kết nối database nếu chưa được include
    if (!isset($conn)) {
        require_once 'connect.php';
    }
    
    // Lấy thông tin chi tiết của giảng viên
    $teacher_id = $_SESSION['user_id'];
    $teacher_query = "SELECT 
                        gv.GV_MAGV, 
                        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
                        gv.GV_EMAIL,
                        gv.GV_SDT,
                        dv.DV_TENDV
                     FROM giang_vien gv
                     LEFT JOIN don_vi dv ON gv.DV_MADV = dv.DV_MADV
                     WHERE gv.GV_MAGV = ?";
    
    $stmt = $conn->prepare($teacher_query);
    if ($stmt) {
        $stmt->bind_param("s", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $teacher_info = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Xác định trang hiện tại để đánh dấu menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Giảng viên -->
<div class="teacher-sidebar">
    <div class="sidebar-header">
        <h2>NGHIÊN CỨU KHOA HỌC</h2>
    </div>
    
    <div class="user-info">
        <div class="user-avatar">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="user-details">
            <h3><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Giảng viên'; ?></h3>
            <p>Giảng viên</p>
        </div>
    </div>
    
    <div class="separator"></div>
    
    <nav>
        <ul>
            <li>
                <a href="/NLNganh/view/teacher/teacher_dashboard.php" class="<?php echo ($current_page === 'teacher_dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Bảng điều khiển</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/teacher/manage_profile.php" class="<?php echo ($current_page === 'manage_profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-edit"></i>
                    <span>Hồ sơ cá nhân</span>
                </a>
            </li>
            <li class="menu-with-dropdown">
                <a href="#" class="dropdown-toggle <?php echo (strpos($current_page, 'project') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-folder"></i>
                    <span>Đề tài nghiên cứu</span>
                    <i class="fas fa-angle-right dropdown-icon"></i>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="/NLNganh/view/teacher/manage_projects.php" class="<?php echo ($current_page === 'manage_projects.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            <span>Danh sách đề tài</span>
                        </a>
                    </li>
                    <li>
                        <a href="/NLNganh/view/teacher/create_project.php" class="<?php echo ($current_page === 'create_project.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus"></i>
                            <span>Thêm đề tài mới</span>
                        </a>
                    </li>
                    <li>
                        <a href="/NLNganh/view/teacher/project_review.php" class="<?php echo ($current_page === 'project_review.php') ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i>
                            <span>Duyệt đề tài</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="/NLNganh/view/teacher/reports.php" class="<?php echo ($current_page === 'reports.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-area"></i>
                    <span>Báo cáo thống kê</span>
                </a>
            </li>
            <li class="menu-with-dropdown">
                <a href="#" class="dropdown-toggle <?php echo (strpos($current_page, 'class_management') !== false || strpos($current_page, 'manage_class') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Quản lý lớp học</span>
                    <i class="fas fa-angle-right dropdown-icon"></i>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="/NLNganh/view/teacher/class_management.php" class="<?php echo ($current_page === 'class_management.php') ? 'active' : ''; ?>">
                            <i class="fas fa-list-alt"></i>
                            <span>Danh sách lớp</span>
                        </a>
                    </li>
                    <li>
                        <a href="/NLNganh/view/teacher/class_statistics.php" class="<?php echo ($current_page === 'class_statistics.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie"></i>
                            <span>Thống kê lớp</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="/NLNganh/view/teacher/schedule.php" class="<?php echo ($current_page === 'schedule.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar"></i>
                    <span>Lịch công việc</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/teacher/notifications.php" class="<?php echo ($current_page === 'notifications.php') ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Thông báo</span>
                    <span class="badge badge-warning notification-badge" id="notification-count">0</span>
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
    
    <?php if (isset($teacher_info)): ?>
    <div class="user-info-extended">
        <div class="info-item">
            <i class="fas fa-envelope"></i>
            <span class="info-text" title="<?php echo htmlspecialchars($teacher_info['GV_EMAIL'] ?? ''); ?>"><?php echo htmlspecialchars($teacher_info['GV_EMAIL'] ?? 'Chưa cập nhật'); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-phone"></i>
            <span class="info-text" title="<?php echo htmlspecialchars($teacher_info['GV_SDT'] ?? ''); ?>"><?php echo htmlspecialchars($teacher_info['GV_SDT'] ?? 'Chưa cập nhật'); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-university"></i>
            <span class="info-text" title="<?php echo htmlspecialchars($teacher_info['DV_TENDV'] ?? ''); ?>"><?php echo htmlspecialchars($teacher_info['DV_TENDV'] ?? 'Chưa cập nhật'); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> - Hệ thống NCKH</p>
    </div>
</div>

<style>
    body, html {
        overflow-x: hidden;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Sidebar styles */
    .teacher-sidebar {
        width: 250px;
        height: 100vh;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: #fff;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    /* Thanh cuộn tùy chỉnh cho sidebar */
    .teacher-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .teacher-sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .teacher-sidebar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }

    /* Sidebar Header */
    .teacher-sidebar .sidebar-header {
        padding: 20px 15px;
        text-align: center;
        background-color: rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .teacher-sidebar .sidebar-header h2 {
        font-size: 16px;
        margin: 0;
        color: #fff;
        font-weight: 600;
        letter-spacing: 1px;
    }

    /* User info section */
    .teacher-sidebar .user-info {
        display: flex;
        align-items: center;
        padding: 15px;
        background-color: rgba(0, 0, 0, 0.05);
    }

    .teacher-sidebar .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #2e86de;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .teacher-sidebar .user-avatar i {
        font-size: 20px;
        color: #fff;
    }

    .teacher-sidebar .user-details h3 {
        font-size: 14px;
        margin: 0;
        color: #fff;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 180px;
    }

    .teacher-sidebar .user-details p {
        font-size: 12px;
        margin: 5px 0 0;
        color: rgba(255, 255, 255, 0.7);
    }

    /* Separator */
    .teacher-sidebar .separator {
        height: 1px;
        background-color: rgba(255, 255, 255, 0.1);
        margin: 10px 0;
    }

    /* Navigation */
    .teacher-sidebar nav {
        flex: 1;
    }
    
    .teacher-sidebar nav ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    .teacher-sidebar nav ul li {
        margin: 2px 0;
    }

    .teacher-sidebar nav ul li a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }

    .teacher-sidebar nav ul li a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-left-color: #f1c40f;
    }

    .teacher-sidebar nav ul li a.active {
        background-color: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-left-color: #f1c40f;
        font-weight: 600;
    }

    .teacher-sidebar nav ul li a i {
        margin-right: 10px;
        font-size: 16px;
        width: 20px;
        text-align: center;
    }

    .teacher-sidebar nav ul li a span {
        font-size: 14px;
        flex: 1;
    }

    /* Dropdown menu */
    .teacher-sidebar .dropdown-toggle .dropdown-icon {
        transition: transform 0.3s ease;
    }
    
    .teacher-sidebar .dropdown-toggle.active .dropdown-icon {
        transform: rotate(90deg);
    }
    
    .teacher-sidebar .submenu {
        display: none;
        list-style: none;
        padding-left: 20px;
        background-color: rgba(0, 0, 0, 0.1);
    }
    
    .teacher-sidebar .submenu.show {
        display: block;
    }
    
    .teacher-sidebar .submenu li a {
        padding: 10px 15px;
        font-size: 13px;
    }
    
    /* Notification badge */
    .notification-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }

    /* Thông tin mở rộng */
    .teacher-sidebar .user-info-extended {
        padding: 15px;
        background-color: rgba(0, 0, 0, 0.05);
        font-size: 12px;
    }
    
    .teacher-sidebar .user-info-extended .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        color: rgba(255, 255, 255, 0.8);
    }
    
    .teacher-sidebar .user-info-extended .info-item i {
        margin-right: 8px;
        width: 15px;
    }
    
    .teacher-sidebar .user-info-extended .info-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    /* Sidebar Footer */
    .teacher-sidebar .sidebar-footer {
        text-align: center;
        padding: 10px 0;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.6);
        background-color: rgba(0, 0, 0, 0.1);
        width: 100%;
    }

    /* Content margin adjustment */
    .content,
    .container-fluid:not(.sidebar-content) {
        margin-left: 250px;
        width: calc(100% - 250px);
        transition: all 0.3s ease;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .teacher-sidebar {
            width: 70px;
        }
        
        .teacher-sidebar .sidebar-header h2,
        .teacher-sidebar .user-details,
        .teacher-sidebar nav ul li a span,
        .teacher-sidebar .user-info-extended,
        .teacher-sidebar nav ul li a .dropdown-icon {
            display: none;
        }
        
        .teacher-sidebar:hover {
            width: 250px;
        }
        
        .teacher-sidebar:hover .sidebar-header h2,
        .teacher-sidebar:hover .user-details,
        .teacher-sidebar:hover nav ul li a span,
        .teacher-sidebar:hover .user-info-extended,
        .teacher-sidebar:hover nav ul li a .dropdown-icon {
            display: block;
        }
        
        .content,
        .container-fluid:not(.sidebar-content) {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
        
        .teacher-sidebar .submenu {
            position: absolute;
            left: 70px;
            top: 0;
            background: #1e3c72;
            width: 180px;
            z-index: 1001;
            box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        .teacher-sidebar:hover .submenu {
            position: relative;
            left: 0;
            width: 100%;
            box-shadow: none;
        }
    }
    
    @media (max-width: 576px) {
        .teacher-sidebar {
            width: 0;
            opacity: 0;
        }
        
        .teacher-sidebar.show {
            width: 250px;
            opacity: 1;
        }
        
        .content,
        .container-fluid:not(.sidebar-content) {
            margin-left: 0;
            width: 100%;
        }
        
        .mobile-toggle-btn {
            display: block;
            position: fixed;
            top: 10px;
            left: 10px;
            background-color: #1e3c72;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            z-index: 1010;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    }
</style>

<!-- JS để xử lý chức năng toggle sidebar và dropdown menu -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý dropdown menu
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('active');
            const submenu = this.nextElementSibling;
            
            if (submenu.classList.contains('show')) {
                submenu.classList.remove('show');
                submenu.style.maxHeight = '0px';
            } else {
                submenu.classList.add('show');
                submenu.style.maxHeight = submenu.scrollHeight + "px";
            }
        });
    });
    
    // Mở dropdown nếu trang con đang active
    const activeSubmenuItems = document.querySelectorAll('.submenu .active');
    activeSubmenuItems.forEach(item => {
        const parentDropdown = item.closest('.submenu');
        const parentToggle = parentDropdown.previousElementSibling;
        
        if (parentDropdown && parentToggle) {
            parentDropdown.classList.add('show');
            parentDropdown.style.maxHeight = parentDropdown.scrollHeight + "px";
            parentToggle.classList.add('active');
        }
    });
    
    // Thêm nút toggle cho mobile nếu chưa có
    if (!document.querySelector('.mobile-toggle-btn') && window.innerWidth <= 576) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.teacher-sidebar');
            sidebar.classList.toggle('show');
        });
    }
    
    // Bắt sự kiện resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 576 && !document.querySelector('.mobile-toggle-btn')) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'mobile-toggle-btn';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                const sidebar = document.querySelector('.teacher-sidebar');
                sidebar.classList.toggle('show');
            });
        } else if (window.innerWidth > 576 && document.querySelector('.mobile-toggle-btn')) {
            document.querySelector('.mobile-toggle-btn').remove();
            document.querySelector('.teacher-sidebar').classList.remove('show');
        }
    });
});
</script>