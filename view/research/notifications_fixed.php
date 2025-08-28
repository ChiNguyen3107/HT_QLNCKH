<?php
/**
 * Notifications Page - Fixed Version
 * Trang thông báo đã sửa lỗi
 */

include '../../include/session.php';
checkResearchManagerRole();
include '../../include/connect.php';

$manager_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Xử lý các actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read']) && is_numeric($_POST['mark_read'])) {
        $notification_id = intval($_POST['mark_read']);
        $stmt = $conn->prepare("UPDATE thong_bao SET TB_DANHDOC = 1 WHERE TB_MA = ?");
        if ($stmt) {
            $stmt->bind_param("i", $notification_id);
            $stmt->execute();
            $stmt->close();
            $success_message = "Đã đánh dấu thông báo là đã đọc";
        }
    }
    
    if (isset($_POST['mark_all_read'])) {
        $stmt = $conn->prepare("UPDATE thong_bao SET TB_DANHDOC = 1 WHERE TB_DANHDOC = 0 AND (TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?)");
        if ($stmt) {
            $stmt->bind_param("s", $user_role);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            $success_message = "Đã đánh dấu $affected_rows thông báo là đã đọc";
        }
    }
    
    if (isset($_POST['delete']) && is_numeric($_POST['delete'])) {
        $notification_id = intval($_POST['delete']);
        $stmt = $conn->prepare("DELETE FROM thong_bao WHERE TB_MA = ?");
        if ($stmt) {
            $stmt->bind_param("i", $notification_id);
            $stmt->execute();
            $stmt->close();
            $success_message = "Đã xóa thông báo";
        }
    }
}

// Lấy tham số phân trang và lọc
$page = max(1, intval($_GET['page'] ?? 1));
$status = $_GET['status'] ?? 'all'; // all, unread, read
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng điều kiện WHERE
$where_conditions = ["(TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?)"];
$params = [$user_role];
$param_types = 's';

if ($status === 'unread') {
    $where_conditions[] = "TB_DANHDOC = 0";
} elseif ($status === 'read') {
    $where_conditions[] = "TB_DANHDOC = 1";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Đếm tổng số thông báo
$count_sql = "SELECT COUNT(*) as total FROM thong_bao $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($param_types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_records / $limit);

// Lấy danh sách thông báo
$notifications_sql = "SELECT * FROM thong_bao 
                     $where_clause
                     ORDER BY TB_DANHDOC ASC, TB_NGAYTAO DESC 
                     LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($notifications_sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$notifications_result = $stmt->get_result();

$notifications = [];
while ($row = $notifications_result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Thống kê
$stats_sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN TB_DANHDOC = 0 THEN 1 END) as unread,
                COUNT(CASE WHEN TB_DANHDOC = 1 THEN 1 END) as read
              FROM thong_bao 
              WHERE TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param('s', $user_role);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo - Research Manager</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        .notification-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s;
        }
        
        .notification-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .notification-item.unread {
            border-left: 4px solid #007bff;
            background-color: #f8f9ff;
        }
        
        .notification-item.read {
            border-left: 4px solid #6c757d;
            opacity: 0.8;
        }
        
        .priority-urgent { border-left-color: #dc3545 !important; }
        .priority-high { border-left-color: #fd7e14 !important; }
        .priority-medium { border-left-color: #ffc107 !important; }
        .priority-low { border-left-color: #28a745 !important; }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include '../../include/simple_research_sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Mobile Toggle Button -->
        <div class="d-md-none mb-3">
            <button class="btn btn-primary" onclick="toggleMobileSidebar()">
                <i class="fas fa-bars me-1"></i>Menu
            </button>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">
                        <i class="fas fa-bell me-2"></i>Thông báo
                    </h1>
                    <p class="mb-0 opacity-75">Quản lý và theo dõi các thông báo hệ thống</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <?= $stats['unread'] ?> chưa đọc
                    </span>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-list-alt fa-2x text-primary mb-2"></i>
                        <h4><?= $stats['total'] ?></h4>
                        <small class="text-muted">Tổng thông báo</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-eye-slash fa-2x text-warning mb-2"></i>
                        <h4><?= $stats['unread'] ?></h4>
                        <small class="text-muted">Chưa đọc</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-eye fa-2x text-success mb-2"></i>
                        <h4><?= $stats['read'] ?></h4>
                        <small class="text-muted">Đã đọc</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <a href="?status=all" class="btn btn-<?= $status === 'all' ? 'primary' : 'outline-primary' ?>">
                                Tất cả (<?= $stats['total'] ?>)
                            </a>
                            <a href="?status=unread" class="btn btn-<?= $status === 'unread' ? 'warning' : 'outline-warning' ?>">
                                Chưa đọc (<?= $stats['unread'] ?>)
                            </a>
                            <a href="?status=read" class="btn btn-<?= $status === 'read' ? 'success' : 'outline-success' ?>">
                                Đã đọc (<?= $stats['read'] ?>)
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if ($stats['unread'] > 0): ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="mark_all_read" class="btn btn-outline-secondary" 
                                    onclick="return confirm('Đánh dấu tất cả thông báo là đã đọc?')">
                                <i class="fas fa-check-double me-1"></i>Đánh dấu tất cả đã đọc
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Danh sách thông báo
                    <small class="text-muted">(Trang <?= $page ?>/<?= $total_pages ?>)</small>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($notifications) > 0): ?>
                    <div class="p-3">
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                            $is_unread = !$notification['TB_DANHDOC'];
                            $priority_class = '';
                            
                            switch ($notification['TB_MUCDO'] ?? 'trung_binh') {
                                case 'khan_cap': $priority_class = 'priority-urgent'; break;
                                case 'cao': $priority_class = 'priority-high'; break;
                                case 'trung_binh': $priority_class = 'priority-medium'; break;
                                case 'thap': $priority_class = 'priority-low'; break;
                            }
                            ?>
                            
                            <div class="notification-item <?= $is_unread ? 'unread' : 'read' ?> <?= $priority_class ?>">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-<?= $notification['TB_LOAI'] === 'test' ? 'flask' : ($notification['TB_LOAI'] === 'gia_han_yeu_cau' ? 'clock' : 'bell') ?> me-2 text-primary"></i>
                                            <h6 class="mb-0 <?= $is_unread ? 'fw-bold' : '' ?>">
                                                <?= htmlspecialchars($notification['TB_LOAI'] ?? 'Thông báo') ?>
                                            </h6>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($notification['TB_NGAYTAO'])) ?>
                                            </small>
                                            <?php if (isset($notification['TB_MUCDO'])): ?>
                                            <span class="badge bg-<?= $notification['TB_MUCDO'] === 'khan_cap' ? 'danger' : ($notification['TB_MUCDO'] === 'cao' ? 'warning' : 'info') ?>">
                                                <?= ucfirst($notification['TB_MUCDO']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-3 <?= $is_unread ? 'fw-semibold' : 'text-muted' ?>">
                                        <?= nl2br(htmlspecialchars($notification['TB_NOIDUNG'])) ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($notification['DT_MADT']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-file-alt me-1"></i>Đề tài: <?= htmlspecialchars($notification['DT_MADT']) ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($is_unread): ?>
                                            <form method="POST" class="d-inline">
                                                <button type="submit" name="mark_read" value="<?= $notification['TB_MA'] ?>" 
                                                        class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline">
                                                <button type="submit" name="delete" value="<?= $notification['TB_MA'] ?>" 
                                                        class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('Xóa thông báo này?')">
                                                    <i class="fas fa-trash me-1"></i>Xóa
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="p-3 border-top">
                        <nav aria-label="Pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= urlencode($status) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= urlencode($status) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có thông báo</h5>
                        <p class="text-muted">
                            <?php if ($status === 'unread'): ?>
                                Bạn không có thông báo chưa đọc nào
                            <?php elseif ($status === 'read'): ?>
                                Bạn không có thông báo đã đọc nào
                            <?php else: ?>
                                Bạn chưa có thông báo nào
                            <?php endif; ?>
                        </p>
                        <a href="/NLNganh/simple_notification_test.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Tạo thông báo test
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

