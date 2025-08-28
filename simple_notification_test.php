<?php
/**
 * Simple Notification Test
 * Test đơn giản hệ thống thông báo
 */

include 'include/session.php';
include 'include/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Xử lý tạo thông báo test
$message = '';
if ($_POST['action'] ?? '' === 'create_test') {
    try {
        $content = "🧪 Test thông báo tạo lúc " . date('d/m/Y H:i:s') . " bởi " . $user_id;
        $stmt = $conn->prepare("INSERT INTO thong_bao (TB_NOIDUNG, TB_LOAI, TB_MUCTIEU, TB_MUCDO, TB_NGUOITAO) VALUES (?, 'test', 'research_manager', 'trung_binh', ?)");
        $stmt->bind_param('ss', $content, $user_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Tạo thông báo test thành công!</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Lỗi: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>❌ Exception: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Notification Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .notification-counter {
            position: relative;
            display: inline-block;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 12px;
            min-width: 20px;
            text-align: center;
        }
        
        .notification-badge.zero {
            display: none;
        }
        
        .api-result {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <!-- Header -->
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">
                                    <i class="fas fa-bell me-2"></i>Simple Notification Test
                                </h1>
                                <p class="mb-0">Test đơn giản hệ thống thông báo</p>
                            </div>
                            <div class="text-end">
                                <div class="notification-counter">
                                    <i class="fas fa-bell fa-2x"></i>
                                    <span id="notification-count" class="notification-badge zero">0</span>
                                </div>
                                <br>
                                <small>User: <?= htmlspecialchars($user_id) ?> (<?= htmlspecialchars($user_role) ?>)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <?php echo $message; ?>

                <!-- Controls -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs me-2"></i>Test Controls</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="action" value="create_test">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-plus me-1"></i>Tạo thông báo test
                                    </button>
                                </form>
                                
                                <button id="btn-test-simple-api" class="btn btn-info w-100 mb-2">
                                    <i class="fas fa-code me-1"></i>Test Simple API
                                </button>
                                
                                <button id="btn-test-complex-api" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-cogs me-1"></i>Test Complex API
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button id="btn-refresh-count" class="btn btn-warning w-100 mb-2">
                                    <i class="fas fa-sync me-1"></i>Refresh Count
                                </button>
                                
                                <button id="btn-start-auto" class="btn btn-secondary w-100 mb-2">
                                    <i class="fas fa-play me-1"></i>Start Auto Update
                                </button>
                                
                                <button id="btn-stop-auto" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-stop me-1"></i>Stop Auto Update
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-terminal me-2"></i>API Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="api-results">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Nhấn các nút trên để test API
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Notifications -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Thông báo hiện tại</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $result = $conn->query("SELECT * FROM thong_bao ORDER BY TB_NGAYTAO DESC LIMIT 5");
                            if ($result && $result->num_rows > 0) {
                                echo "<div class='table-responsive'>";
                                echo "<table class='table table-sm'>";
                                echo "<thead><tr><th>ID</th><th>Nội dung</th><th>Loại</th><th>Mục tiêu</th><th>Ngày tạo</th><th>Đã đọc</th></tr></thead><tbody>";
                                
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['TB_MA'] . "</td>";
                                    echo "<td>" . htmlspecialchars(substr($row['TB_NOIDUNG'], 0, 50)) . "...</td>";
                                    echo "<td><span class='badge bg-info'>" . $row['TB_LOAI'] . "</span></td>";
                                    echo "<td><span class='badge bg-primary'>" . $row['TB_MUCTIEU'] . "</span></td>";
                                    echo "<td><small>" . $row['TB_NGAYTAO'] . "</small></td>";
                                    echo "<td>" . ($row['TB_DANHDOC'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>') . "</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody></table></div>";
                            } else {
                                echo "<div class='alert alert-info'>Không có thông báo nào</div>";
                            }
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let autoUpdateTimer = null;
        
        // Update notification count display
        function updateCountDisplay(count) {
            const badge = document.getElementById('notification-count');
            badge.textContent = count;
            
            if (count > 0) {
                badge.classList.remove('zero');
            } else {
                badge.classList.add('zero');
            }
        }
        
        // Add result to display
        function addResult(title, content, type = 'info') {
            const container = document.getElementById('api-results');
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'error' ? 'alert-danger' : 'alert-info';
            
            const resultHTML = `
                <div class="${alertClass} alert-dismissible fade show" role="alert">
                    <h6>${title}</h6>
                    <div class="api-result">${content}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            container.insertAdjacentHTML('afterbegin', resultHTML);
        }
        
        // Test Simple API
        document.getElementById('btn-test-simple-api').addEventListener('click', async () => {
            try {
                const response = await fetch('/NLNganh/api/simple_notification_count.php');
                const data = await response.json();
                
                addResult('Simple API Result', 
                    JSON.stringify(data, null, 2), 
                    data.success ? 'success' : 'error'
                );
                
                if (data.success) {
                    updateCountDisplay(data.data.count);
                }
            } catch (error) {
                addResult('Simple API Error', error.message, 'error');
            }
        });
        
        // Test Complex API
        document.getElementById('btn-test-complex-api').addEventListener('click', async () => {
            try {
                const response = await fetch('/NLNganh/api/notification_manager.php?action=get_count');
                const data = await response.json();
                
                addResult('Complex API Result', 
                    JSON.stringify(data, null, 2), 
                    data.success ? 'success' : 'error'
                );
                
                if (data.success) {
                    updateCountDisplay(data.data.count);
                }
            } catch (error) {
                addResult('Complex API Error', error.message, 'error');
            }
        });
        
        // Refresh count
        document.getElementById('btn-refresh-count').addEventListener('click', () => {
            document.getElementById('btn-test-simple-api').click();
        });
        
        // Start auto update
        document.getElementById('btn-start-auto').addEventListener('click', () => {
            if (autoUpdateTimer) {
                clearInterval(autoUpdateTimer);
            }
            
            autoUpdateTimer = setInterval(() => {
                fetch('/NLNganh/api/simple_notification_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateCountDisplay(data.data.count);
                        }
                    })
                    .catch(error => console.error('Auto update error:', error));
            }, 10000); // 10 seconds
            
            addResult('Auto Update Started', 'Cập nhật mỗi 10 giây', 'success');
        });
        
        // Stop auto update
        document.getElementById('btn-stop-auto').addEventListener('click', () => {
            if (autoUpdateTimer) {
                clearInterval(autoUpdateTimer);
                autoUpdateTimer = null;
                addResult('Auto Update Stopped', 'Đã dừng cập nhật tự động', 'info');
            }
        });
        
        // Initial load
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btn-test-simple-api').click();
        });
    </script>
</body>
</html>

