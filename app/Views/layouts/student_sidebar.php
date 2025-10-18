<?php
// filepath: d:\xampp\htdocs\NLNganh\include\student_sidebar.php

// Lấy thông tin sinh viên từ CSDL để hiển thị đầy đủ
if (isset($_SESSION['user_id'])) {
    // Tạo kết nối database nếu chưa được include
    if (!isset($conn)) {
        require_once 'connect.php';
    }
      // Lấy thông tin chi tiết của sinh viên
    $student_id = $_SESSION['user_id'];
    $student_query = "SELECT 
                        sv.SV_MASV, 
                        CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN,
                        sv.SV_EMAIL,
                        l.LOP_TEN,
                        dv.DV_TENDV,
                        sv.SV_AVATAR
                     FROM sinh_vien sv
                     LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
                     LEFT JOIN don_vi dv ON l.DV_MADV = dv.DV_MADV
                     WHERE sv.SV_MASV = ?";
    
    $stmt = $conn->prepare($student_query);
    if ($stmt) {
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student_info = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Xác định trang hiện tại để đánh dấu menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Sinh viên -->
<div class="student-sidebar">
    <div class="sidebar-header">
        <h2>NGHIÊN CỨU KHOA HỌC</h2>
    </div>
      <div class="user-info">
        <div class="user-avatar">
            <?php if (isset($student_info['SV_AVATAR']) && !empty($student_info['SV_AVATAR'])): ?>
                <img src="/NLNganh/<?php echo htmlspecialchars($student_info['SV_AVATAR']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
            <?php else: ?>
                <i class="fas fa-user-graduate"></i>
            <?php endif; ?>
        </div>        <div class="user-details">
            <?php if (isset($student_info)): ?>
                <h3><?php 
                    if (isset($student_info['SV_HOTEN'])) {
                        echo htmlspecialchars($student_info['SV_HOTEN']);
                    } elseif (isset($student_info['SV_HOSV']) && isset($student_info['SV_TENSV'])) {
                        echo htmlspecialchars($student_info['SV_HOSV'] . ' ' . $student_info['SV_TENSV']);
                    } else {
                        echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Sinh viên';
                    }
                ?></h3>
                <p><?php echo htmlspecialchars($student_info['SV_MASV']); ?></p>
                <small class="text-light"><?php echo htmlspecialchars($student_info['LOP_TEN'] ?? 'Không có dữ liệu'); ?></small>
            <?php else: ?>
                <h3><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Sinh viên'; ?></h3>
                <p>Sinh viên</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="separator"></div>
    
    <nav>
        <ul>
            <li>
                <a href="/NLNganh/view/student/student_dashboard.php" class="<?php echo ($current_page === 'student_dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Bảng điều khiển</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/student/manage_profile.php" class="<?php echo ($current_page === 'manage_profile.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-edit"></i>
                    <span>Quản lý hồ sơ</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/student/browse_projects.php" class="<?php echo ($current_page === 'browse_projects.php') ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i>
                    <span>Tìm kiếm đề tài</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/student/register_project_form.php" class="<?php echo ($current_page === 'register_project_form.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Đăng ký đề tài</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/student/student_manage_projects.php" class="<?php echo (in_array($current_page, ['student_manage_projects.php', 'view_project.php'])) ? 'active' : ''; ?>">
                    <i class="fas fa-book-reader"></i>
                    <span>Quản lý đề tài</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/student/manage_extensions.php" class="<?php echo (in_array($current_page, ['manage_extensions.php', 'process_extension_request.php', 'get_extension_detail.php'])) ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span>Gia hạn đề tài</span>
                </a>
            </li>
            <li>
                <a href="/NLNganh/view/student/student_reports.php" class="<?php echo (in_array($current_page, ['student_reports.php', 'view_report.php', 'submit_report.php'])) ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Báo cáo</span>
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
    
    <?php if (isset($student_info)): ?>
    <div class="user-info-extended">
        <div class="info-item">
            <i class="fas fa-envelope"></i>
            <span class="info-text" title="<?php echo htmlspecialchars($student_info['SV_EMAIL'] ?? ''); ?>"><?php echo htmlspecialchars($student_info['SV_EMAIL'] ?? 'Chưa cập nhật'); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-university"></i>
            <span class="info-text" title="<?php echo htmlspecialchars($student_info['DV_TENDV'] ?? ''); ?>"><?php echo htmlspecialchars($student_info['DV_TENDV'] ?? 'Chưa cập nhật'); ?></span>
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
    .student-sidebar {
        width: 250px;
        height: 100vh;
        background: linear-gradient(135deg, #1f5ca9 0%, #0c3b76 100%);
        color: #fff;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    /* Thanh cuộn tùy chỉnh cho sidebar */
    .student-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    .student-sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .student-sidebar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }

    /* Sidebar Header */
    .student-sidebar .sidebar-header {
        padding: 20px 15px;
        text-align: center;
        background-color: rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .student-sidebar .sidebar-header h2 {
        font-size: 16px;
        margin: 0;
        color: #fff;
        font-weight: 600;
        letter-spacing: 1px;
    }

    /* User info section */
    .student-sidebar .user-info {
        display: flex;
        align-items: center;
        padding: 15px;
        background-color: rgba(0, 0, 0, 0.05);
    }

    .student-sidebar .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #219653;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .student-sidebar .user-avatar i {
        font-size: 20px;
        color: #fff;
    }

    .student-sidebar .user-details h3 {
        font-size: 14px;
        margin: 0;
        color: #fff;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 180px;
    }

    .student-sidebar .user-details p {
        font-size: 12px;
        margin: 5px 0 0;
        color: rgba(255, 255, 255, 0.7);
    }

    .student-sidebar .user-details small {
        display: block;
        font-size: 11px;
        margin-top: 3px;
        color: rgba(255, 255, 255, 0.6);
    }

    /* Separator */
    .student-sidebar .separator {
        height: 1px;
        background-color: rgba(255, 255, 255, 0.1);
        margin: 10px 0;
    }

    /* Navigation */
    .student-sidebar nav {
        flex: 1;
    }
    
    .student-sidebar nav ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    .student-sidebar nav ul li {
        margin: 2px 0;
    }

    .student-sidebar nav ul li a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }

    .student-sidebar nav ul li a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-left-color: #f1c40f;
    }

    .student-sidebar nav ul li a.active {
        background-color: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-left-color: #f1c40f;
        font-weight: 600;
    }

    .student-sidebar nav ul li a i {
        margin-right: 10px;
        font-size: 16px;
        width: 20px;
        text-align: center;
    }

    .student-sidebar nav ul li a span {
        font-size: 14px;
    }

    /* Thông tin mở rộng */
    .student-sidebar .user-info-extended {
        padding: 15px;
        background-color: rgba(0, 0, 0, 0.05);
        font-size: 12px;
    }
    
    .student-sidebar .user-info-extended .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        color: rgba(255, 255, 255, 0.8);
    }
    
    .student-sidebar .user-info-extended .info-item i {
        margin-right: 8px;
        width: 15px;
    }
    
    .student-sidebar .user-info-extended .info-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    /* Sidebar Footer */
    .student-sidebar .sidebar-footer {
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
        .student-sidebar {
            width: 70px;
        }
        
        .student-sidebar .sidebar-header h2,
        .student-sidebar .user-details,
        .student-sidebar nav ul li a span,
        .student-sidebar .user-info-extended {
            display: none;
        }
        
        .student-sidebar:hover {
            width: 250px;
        }
        
        .student-sidebar:hover .sidebar-header h2,
        .student-sidebar:hover .user-details,
        .student-sidebar:hover nav ul li a span,
        .student-sidebar:hover .user-info-extended {
            display: block;
        }
        
        .content,
        .container-fluid:not(.sidebar-content) {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
    }
    
    @media (max-width: 576px) {
        .student-sidebar {
            width: 0;
            opacity: 0;
        }
        
        .student-sidebar.show {
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
            background-color: #1f5ca9;
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

<!-- JS để xử lý chức năng toggle sidebar trên mobile -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Thêm nút toggle cho mobile nếu chưa có
    if (!document.querySelector('.mobile-toggle-btn') && window.innerWidth <= 576) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.student-sidebar');
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
                const sidebar = document.querySelector('.student-sidebar');
                sidebar.classList.toggle('show');
            });
        } else if (window.innerWidth > 576 && document.querySelector('.mobile-toggle-btn')) {
            document.querySelector('.mobile-toggle-btn').remove();
            document.querySelector('.student-sidebar').classList.remove('show');
        }
    });
});
</script>