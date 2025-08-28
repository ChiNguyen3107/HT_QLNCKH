<?php
/**
 * Setup Notifications - Thiết lập hệ thống thông báo
 */

include 'include/connect.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Thiết lập Hệ thống Thông báo</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .result-success { color: #28a745; }
        .result-error { color: #dc3545; }
        .result-info { color: #17a2b8; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1>🔧 Thiết lập Hệ thống Thông báo</h1>
    <hr>
";

try {
    // Đọc và thực thi script đơn giản
    $sql_content = file_get_contents('create_simple_extension_notification.sql');
    
    if (!$sql_content) {
        throw new Exception("Không thể đọc file SQL");
    }
    
    echo "<div class='alert alert-info'>📝 Đang thực thi script thiết lập...</div>";
    
    // Loại bỏ comments và split statements
    $lines = explode("\n", $sql_content);
    $current_statement = '';
    $statements = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Bỏ qua comment và dòng trống
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $current_statement .= $line . "\n";
        
        // Nếu gặp delimiter hoặc kết thúc statement
        if (strpos($line, 'DELIMITER') === 0) {
            if (!empty(trim($current_statement))) {
                $statements[] = trim($current_statement);
            }
            $current_statement = '';
        } elseif (substr($line, -1) === ';' && strpos($line, 'DELIMITER') === false) {
            $statements[] = trim($current_statement);
            $current_statement = '';
        }
    }
    
    // Thêm statement cuối nếu có
    if (!empty(trim($current_statement))) {
        $statements[] = trim($current_statement);
    }
    
    $success_count = 0;
    $error_count = 0;
    
    echo "<div class='row'><div class='col-md-8'>";
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        echo "<div class='card mb-2'>";
        echo "<div class='card-body p-2'>";
        
        try {
            // Xử lý multi-query cho stored procedures
            if (strpos($statement, 'CREATE TRIGGER') !== false || strpos($statement, 'DROP TRIGGER') !== false) {
                $conn->multi_query($statement);
                
                // Xử lý tất cả kết quả
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->next_result());
                
                if ($conn->error) {
                    throw new Exception($conn->error);
                }
            } else {
                $result = $conn->query($statement);
                if (!$result && $conn->error) {
                    throw new Exception($conn->error);
                }
            }
            
            $success_count++;
            echo "<span class='result-success'>✅ Thành công:</span> ";
            echo "<small>" . htmlspecialchars(substr($statement, 0, 80)) . "...</small>";
            
        } catch (Exception $e) {
            $error_count++;
            echo "<span class='result-error'>❌ Lỗi:</span> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<small class='text-muted'>" . htmlspecialchars(substr($statement, 0, 100)) . "...</small>";
        }
        
        echo "</div></div>";
    }
    
    echo "</div>";
    
    // Sidebar với thống kê
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>📊 Kết quả</h5></div>";
    echo "<div class='card-body'>";
    echo "<div class='result-success'>✅ Thành công: $success_count</div>";
    echo "<div class='result-error'>❌ Lỗi: $error_count</div>";
    
    if ($error_count == 0) {
        echo "<hr><div class='alert alert-success'>🎉 Thiết lập hoàn tất!</div>";
    } else {
        echo "<hr><div class='alert alert-warning'>⚠️ Có $error_count lỗi</div>";
    }
    
    echo "</div></div>";
    echo "</div></div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Lỗi nghiêm trọng: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Test tạo thông báo
echo "<hr><h3>🧪 Test thông báo</h3>";

try {
    // Tạo thông báo test
    $test_content = "🔔 Test thông báo hệ thống - " . date('d/m/Y H:i:s');
    $stmt = $conn->prepare("INSERT INTO thong_bao (TB_NOIDUNG, TB_LOAI, TB_MUCTIEU, TB_MUCDO) VALUES (?, 'test', 'research_manager', 'trung_binh')");
    $stmt->bind_param('s', $test_content);
    $stmt->execute();
    $stmt->close();
    
    echo "<div class='alert alert-success'>✅ Tạo thông báo test thành công!</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Lỗi tạo thông báo test: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Hiển thị thông báo hiện có
echo "<h4>📋 Thông báo hiện tại</h4>";
try {
    $result = $conn->query("SELECT * FROM thong_bao ORDER BY TB_NGAYTAO DESC LIMIT 10");
    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-sm'>";
        echo "<thead><tr><th>ID</th><th>Nội dung</th><th>Loại</th><th>Mục tiêu</th><th>Mức độ</th><th>Ngày tạo</th><th>Đã đọc</th></tr></thead><tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['TB_MA'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['TB_NOIDUNG'], 0, 60)) . "...</td>";
            echo "<td><span class='badge bg-info'>" . $row['TB_LOAI'] . "</span></td>";
            echo "<td><span class='badge bg-primary'>" . $row['TB_MUCTIEU'] . "</span></td>";
            echo "<td><span class='badge bg-warning'>" . $row['TB_MUCDO'] . "</span></td>";
            echo "<td><small>" . $row['TB_NGAYTAO'] . "</small></td>";
            echo "<td>" . ($row['TB_DANHDOC'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ Không có thông báo nào</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Lỗi hiển thị thông báo: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "
    <hr>
    <div class='d-flex gap-2 mb-4'>
        <a href='test_notification_system.php' class='btn btn-primary'>
            <i class='fas fa-test'></i> Trang Test Thông báo
        </a>
        <a href='view/research/manage_extensions.php' class='btn btn-success'>
            <i class='fas fa-clock'></i> Quản lý Gia hạn
        </a>
        <a href='view/research/notifications.php' class='btn btn-info'>
            <i class='fas fa-bell'></i> Xem Thông báo
        </a>
    </div>
    
    <div class='alert alert-info'>
        <h5>📖 Hướng dẫn:</h5>
        <ol>
            <li>Hệ thống thông báo đã được thiết lập</li>
            <li>Khi sinh viên tạo yêu cầu gia hạn → Research Manager sẽ nhận thông báo</li>
            <li>Khi duyệt/từ chối gia hạn → Sinh viên sẽ nhận thông báo</li>
            <li>Thông báo sẽ hiển thị real-time trên sidebar</li>
        </ol>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

$conn->close();
?>

