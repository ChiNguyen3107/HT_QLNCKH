<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\notifications.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin quản lý nghiên cứu
$manager_id = $_SESSION['user_id'];

// Kiểm tra có bảng thông báo không
$table_check = $conn->query("SHOW TABLES LIKE 'thong_bao'");
if ($table_check && $table_check->num_rows == 0) {
    // Nếu bảng chưa tồn tại, tạo bảng thông báo
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `thong_bao` (
        `TB_MA` INT AUTO_INCREMENT PRIMARY KEY,
        `TB_NOIDUNG` TEXT NOT NULL,
        `TB_NGAYTAO` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `TB_DANHDOC` TINYINT(1) NOT NULL DEFAULT 0,
        `TB_LOAI` VARCHAR(50) DEFAULT 'Thông báo',
        `DT_MADT` CHAR(10) NULL,
        `GV_MAGV` CHAR(8) NULL,
        `SV_MASV` CHAR(8) NULL,
        `QL_MA` CHAR(8) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if (!$conn->query($create_table_sql)) {
        $error_message = "Lỗi khi tạo bảng thông báo: " . $conn->error;
    }
}

// Xử lý đánh dấu đã đọc
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $mark_read_sql = "UPDATE thong_bao SET TB_DANHDOC = 1 WHERE TB_MA = ?";
    $stmt = $conn->prepare($mark_read_sql);
    if ($stmt) {
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Xử lý đánh dấu tất cả đã đọc
if (isset($_GET['mark_all_read'])) {
    $mark_all_sql = "UPDATE thong_bao SET TB_DANHDOC = 1 WHERE QL_MA = ?";
    $stmt = $conn->prepare($mark_all_sql);
    if ($stmt) {
        $stmt->bind_param("s", $manager_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Xử lý xóa thông báo
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM thong_bao WHERE TB_MA = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt) {
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Lấy danh sách thông báo
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$notifications_sql = "SELECT * FROM thong_bao 
                     WHERE QL_MA = ? OR (TB_LOAI = 'Hệ thống' AND QL_MA IS NULL)
                     ORDER BY TB_NGAYTAO DESC 
                     LIMIT ?, ?";
$stmt = $conn->prepare($notifications_sql);
if ($stmt) {
    $stmt->bind_param("sii", $manager_id, $offset, $items_per_page);
    $stmt->execute();
    $notifications = $stmt->get_result();
    $stmt->close();
} else {
    $error_message = "Lỗi khi lấy danh sách thông báo: " . $conn->error;
    $notifications = null;
}

// Đếm tổng số thông báo cho phân trang
$count_sql = "SELECT COUNT(*) as total FROM thong_bao 
             WHERE QL_MA = ? OR (TB_LOAI = 'Hệ thống' AND QL_MA IS NULL)";
$stmt = $conn->prepare($count_sql);
if ($stmt) {
    $stmt->bind_param("s", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_items = $result->fetch_assoc()['total'];
    $total_pages = ceil($total_items / $items_per_page);
    $stmt->close();
} else {
    $error_message = "Lỗi khi đếm thông báo: " . $conn->error;
    $total_pages = 1;
}

// Đếm số thông báo chưa đọc
$unread_count_sql = "SELECT COUNT(*) as unread FROM thong_bao 
                    WHERE TB_DANHDOC = 0 
                    AND (QL_MA = ? OR (TB_LOAI = 'Hệ thống' AND QL_MA IS NULL))";
$stmt = $conn->prepare($unread_count_sql);
if ($stmt) {
    $stmt->bind_param("s", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = $result->fetch_assoc()['unread'];
    $stmt->close();
} else {
    $error_message = "Lỗi khi đếm thông báo chưa đọc: " . $conn->error;
    $unread_count = 0;
}

// Set page title
$page_title = "Thông báo | Quản lý nghiên cứu";

// Define any additional CSS specific to this page
$additional_css = '<style>
    /* Layout positioning - tương tự như dashboard và profile */
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
    
    /* Enhanced notification cards */
    .notifications-container {
        max-width: 100%;
    }
    
    .notification-item {
        padding: 1.5rem;
        border-left: 4px solid #667eea;
        margin-bottom: 1.5rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .notification-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .notification-item.unread {
        border-left-color: #f6c23e;
        background: linear-gradient(135deg, #fff8e6 0%, #fefaf0 100%);
    }
    
    .notification-item.system {
        border-left-color: #1cc88a;
        background: linear-gradient(135deg, #e8f5e8 0%, #f0f9f0 100%);
    }
    
    .notification-item.warning {
        border-left-color: #e74a3b;
        background: linear-gradient(135deg, #fdf2f2 0%, #fef5f5 100%);
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .notification-type {
        font-weight: 600;
        color: #5a5c69;
        font-size: 0.9rem;
    }
    
    .notification-time {
        color: #858796;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .notification-content {
        margin-bottom: 1.5rem;
        color: #5a5c69;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    
    .notification-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    
    .notification-actions .btn {
        padding: 8px 16px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .notification-actions .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .unread-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.8rem;
        margin-left: 0.5rem;
        border-radius: 20px;
        font-weight: 600;
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
        color: white;
        border: none;
    }
    
    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .notifications-header .btn-group {
        white-space: nowrap;
    }
    
    .notifications-header .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85em;
    }
    
    .notifications-header .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .btn-outline-primary {
        border: 2px solid #667eea;
        color: #667eea;
        background: transparent;
    }
    
    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }
    
    .btn-outline-secondary {
        border: 2px solid #6c757d;
        color: #6c757d;
        background: transparent;
    }
    
    .btn-outline-secondary:hover {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        border-color: #6c757d;
    }
    
    .btn-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
    }
    
    .btn-outline-danger {
        border: 2px solid #e74a3b;
        color: #e74a3b;
        background: transparent;
    }
    
    .btn-outline-danger:hover {
        background: linear-gradient(135deg, #e74a3b 0%, #c82333 100%);
        color: white;
        border-color: #e74a3b;
    }
    
    /* Enhanced empty state */
    .empty-notification {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .empty-notification .icon {
        font-size: 5rem;
        color: #d1d3e2;
        margin-bottom: 25px;
        opacity: 0.5;
    }
    
    .empty-notification h5 {
        color: #5a5c69;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .empty-notification p {
        color: #858796;
        margin-bottom: 25px;
        font-size: 1.1em;
    }
    
    /* Card improvements */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        background: white;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 20px;
        font-weight: 600;
    }
    
    .card-body {
        padding: 25px;
    }
    
    /* Pagination improvements */
    .pagination {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .page-link {
        border: none;
        color: #667eea;
        transition: all 0.3s ease;
        padding: 10px 15px;
        font-weight: 500;
    }
    
    .page-link:hover {
        background-color: #667eea;
        color: white;
        transform: translateY(-1px);
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
    }
    
    /* Dropdown improvements */
    .dropdown-menu {
        border-radius: 8px;
        border: none;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .dropdown-item {
        padding: 10px 20px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .dropdown-item:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 20px 15px !important;
        }
        
        .notifications-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .notifications-header .btn-group {
            margin-top: 1rem;
            width: 100%;
            display: flex;
        }
        
        .notifications-header .btn-group .btn {
            flex: 1;
            margin: 0 2px;
        }
        
        .notification-actions {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .notification-actions .btn {
            margin: 2px;
            font-size: 0.75rem;
            padding: 6px 12px;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
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
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-bell me-3"></i>
            Thông báo hệ thống
            <?php if ($unread_count > 0): ?>
                <span class="badge bg-warning unread-badge"><?php echo $unread_count; ?> chưa đọc</span>
            <?php endif; ?>
        </h1>
        <a href="research_dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Về Dashboard
        </a>
    </div>

    <!-- Hiển thị thông báo lỗi nếu có -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Action buttons -->
    <div class="notifications-header">
        <div class="btn-group">
            <a href="?mark_all_read=1" class="btn btn-outline-primary">
                <i class="fas fa-check-double me-1"></i> Đánh dấu tất cả đã đọc
            </a>
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-1"></i> Lọc thông báo
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="notifications.php">
                    <i class="fas fa-list me-1"></i> Tất cả thông báo
                </a>
                <a class="dropdown-item" href="notifications.php?filter=unread">
                    <i class="fas fa-envelope me-1"></i> Chưa đọc
                </a>
                <a class="dropdown-item" href="notifications.php?filter=system">
                    <i class="fas fa-cog me-1"></i> Thông báo hệ thống
                </a>
                <a class="dropdown-item" href="notifications.php?filter=project">
                    <i class="fas fa-folder me-1"></i> Thông báo đề tài
                </a>
            </div>
        </div>
    </div>
    
    <!-- Card chứa nội dung thông báo -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Danh sách thông báo
            </h5>
        </div>
        
        <div class="card-body">
            <div class="notifications-container">
                <?php if ($notifications && $notifications->num_rows > 0): ?>
                    <?php while ($notification = $notifications->fetch_assoc()): ?>
                        <div class="notification-item <?php echo ($notification['TB_DANHDOC'] == 0) ? 'unread' : ''; ?> <?php echo ($notification['TB_LOAI'] == 'Hệ thống') ? 'system' : ''; ?>">
                            <div class="notification-header">
                                <span class="notification-type">
                                    <?php
                                    $icon = 'fa-bell';
                                    switch ($notification['TB_LOAI']) {
                                        case 'Hệ thống':
                                            $icon = 'fa-cog';
                                            break;
                                        case 'Đề tài':
                                            $icon = 'fa-folder';
                                            break;
                                        case 'Đánh giá':
                                            $icon = 'fa-star';
                                            break;
                                        case 'Cảnh báo':
                                            $icon = 'fa-exclamation-triangle';
                                            break;
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?> me-1"></i> 
                                    <?php echo htmlspecialchars($notification['TB_LOAI']); ?>
                                </span>
                                <span class="notification-time">
                                    <?php 
                                    $date = new DateTime($notification['TB_NGAYTAO']);
                                    echo $date->format('d/m/Y H:i:s');
                                    ?>
                                </span>
                            </div>
                            <div class="notification-content">
                                <?php echo htmlspecialchars($notification['TB_NOIDUNG']); ?>
                            </div>
                            <div class="notification-actions">
                                <?php if ($notification['TB_DANHDOC'] == 0): ?>
                                    <a href="notifications.php?mark_read=<?php echo $notification['TB_MA']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-check me-1"></i> Đánh dấu đã đọc
                                    </a>
                                <?php endif; ?>
                                <?php if ($notification['DT_MADT']): ?>
                                    <a href="view_project.php?id=<?php echo htmlspecialchars($notification['DT_MADT']); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye me-1"></i> Xem đề tài
                                    </a>
                                <?php endif; ?>
                                <a href="notifications.php?delete=<?php echo $notification['TB_MA']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa thông báo này?');">
                                    <i class="fas fa-trash-alt me-1"></i> Xóa
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="notifications.php?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Trước
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="notifications.php?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                        <a class="page-link" href="notifications.php?page=' . $i . '">' . $i . '</a>
                                    </li>';
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="notifications.php?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="notifications.php?page=<?php echo $page + 1; ?>">
                                        Tiếp <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-notification">
                        <div class="icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>
                        <h5>Không có thông báo nào</h5>
                        <p class="text-muted">Bạn sẽ nhận được thông báo khi có hoạt động liên quan đến nghiên cứu khoa học.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div> <!-- /.container-fluid -->

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript for notifications functionality -->
<script>
$(document).ready(function() {
    // Auto-dismiss alerts after 5 seconds
    $(".alert").delay(5000).fadeOut(500);
    
    // Hiệu ứng highlight cho thông báo chưa đọc
    $(".notification-item.unread").hover(
        function() {
            $(this).addClass("bg-light");
        },
        function() {
            $(this).removeClass("bg-light");
        }
    );
    
    // Smooth scroll to top when clicking pagination
    $(".pagination a").click(function(e) {
        if (this.href.indexOf('#') === -1) {
            $('html, body').animate({
                scrollTop: $(".container-fluid").offset().top - 20
            }, 500);
        }
    });
    
    // Confirm delete actions
    $("a[href*='delete=']").click(function(e) {
        if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
            e.preventDefault();
        }
    });
    
    // Auto refresh thông báo mỗi 10 phút (tăng từ 5 phút để giảm tải server)
    setTimeout(function() {
        // Thêm thông báo trước khi refresh
        const alert = $('<div class="alert alert-info alert-dismissible fade show" role="alert">' +
            '<i class="fas fa-sync-alt me-2"></i>' +
            'Đang cập nhật thông báo mới...' +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>');
        $(".container-fluid").prepend(alert);
        
        setTimeout(function() {
            location.reload();
        }, 2000);
    }, 600000); // 10 phút = 600000ms
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mark notification as read when clicking "Xem đề tài" link
    $("a[href*='view_project.php']").click(function() {
        const notificationItem = $(this).closest('.notification-item');
        if (notificationItem.hasClass('unread')) {
            // Extract notification ID and mark as read via AJAX
            const deleteLink = notificationItem.find("a[href*='delete=']");
            if (deleteLink.length > 0) {
                const href = deleteLink.attr('href');
                const notificationId = href.match(/delete=(\d+)/);
                if (notificationId) {
                    // Mark as read silently
                    $.get('notifications.php?mark_read=' + notificationId[1]);
                    notificationItem.removeClass('unread');
                }
            }
        }
    });
});

// Function to update notification count in real-time
function updateNotificationCount() {
    $.get('../../api/get_notifications_count.php', function(data) {
        if (data.unread_count !== undefined) {
            const badge = $('.unread-badge');
            if (data.unread_count > 0) {
                badge.text(data.unread_count + ' chưa đọc');
                badge.show();
            } else {
                badge.hide();
            }
        }
    }, 'json').fail(function() {
        console.log('Could not update notification count');
    });
}

// Update count every minute
setInterval(updateNotificationCount, 60000);
</script>

<?php
// Include footer if needed
// include '../../include/research_footer.php';
?>
