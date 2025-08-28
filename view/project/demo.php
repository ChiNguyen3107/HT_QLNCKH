<?php
session_start();
require_once '../../include/config.php';
require_once '../../include/database.php';

// Lấy một vài đề tài mẫu để demo
try {
    $demo_sql = "SELECT DT_MADT, DT_TENDT, DT_TRANGTHAI 
                 FROM de_tai_nghien_cuu 
                 ORDER BY DT_NGAYTAO DESC 
                 LIMIT 10";
    $demo_result = $conn->query($demo_sql);
    $demo_projects = $demo_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $demo_projects = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Xem đề tài nghiên cứu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-flask me-2"></i>
                            Demo - Hệ thống xem đề tài nghiên cứu
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Các trang đã được tạo:</h5>
                            <ul class="mb-0">
                                <li><strong>Trang tìm kiếm:</strong> <a href="search.php" target="_blank">search.php</a></li>
                                <li><strong>Trang xem chi tiết:</strong> view_project.php?dt_madt=[MÃ_ĐỀ_TÀI]</li>
                                <li><strong>URL mẫu:</strong> <code>http://localhost/NLNganh/view/project/view_project.php?dt_madt=DT0000001</code></li>
                            </ul>
                        </div>

                        <h5 class="mt-4">
                            <i class="fas fa-list me-2"></i>
                            Đề tài mẫu trong hệ thống:
                        </h5>
                        
                        <?php if (empty($demo_projects)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Chưa có đề tài nào trong hệ thống. Vui lòng thêm dữ liệu mẫu.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($demo_projects as $project): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($project['DT_MADT']) ?></code></td>
                                        <td><?= htmlspecialchars(substr($project['DT_TENDT'], 0, 50)) ?>...</td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= htmlspecialchars($project['DT_TRANGTHAI']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_project.php?dt_madt=<?= urlencode($project['DT_MADT']) ?>" 
                                               class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-eye me-1"></i>Xem
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4 text-center">
                            <a href="search.php" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Tìm kiếm đề tài
                            </a>
                            <a href="/NLNganh/" class="btn btn-secondary">
                                <i class="fas fa-home me-1"></i>Trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
