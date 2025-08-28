<?php
/**
 * Debug Notifications Table
 * Kiểm tra bảng thông báo trước khi sử dụng
 */

include 'include/session.php';
include 'include/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Debug Notifications Table</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1>🔍 Debug Notifications Table</h1>
    <div class='alert alert-info'>
        <strong>User:</strong> $user_id ($user_role)
    </div>
    <hr>
";

// Test 1: Kiểm tra bảng tồn tại
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>1. Kiểm tra bảng thong_bao tồn tại</h5></div>";
echo "<div class='card-body'>";

try {
    $table_check = $conn->query("SHOW TABLES LIKE 'thong_bao'");
    if ($table_check && $table_check->num_rows > 0) {
        echo "<div class='alert alert-success'>✅ Bảng thong_bao tồn tại</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Bảng thong_bao KHÔNG tồn tại</div>";
        echo "<a href='fix_table_structure.php' class='btn btn-warning'>Tạo bảng</a>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Lỗi kiểm tra bảng: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></div>";

// Test 2: Kiểm tra cấu trúc bảng
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>2. Cấu trúc bảng</h5></div>";
echo "<div class='card-body'>";

try {
    $describe_result = $conn->query("DESCRIBE thong_bao");
    if ($describe_result) {
        echo "<div class='alert alert-success'>✅ Có thể truy vấn cấu trúc bảng</div>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm table-striped'>";
        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead><tbody>";
        
        $has_tb_muctieu = false;
        while ($row = $describe_result->fetch_assoc()) {
            if ($row['Field'] === 'TB_MUCTIEU') {
                $has_tb_muctieu = true;
            }
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        
        if ($has_tb_muctieu) {
            echo "<div class='alert alert-success'>✅ Cột TB_MUCTIEU tồn tại</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Cột TB_MUCTIEU KHÔNG tồn tại</div>";
            echo "<a href='fix_table_structure.php' class='btn btn-warning'>Sửa cấu trúc bảng</a>";
        }
    } else {
        echo "<div class='alert alert-danger'>❌ Không thể truy vấn cấu trúc: " . htmlspecialchars($conn->error) . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></div>";

// Test 3: Test query đơn giản
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>3. Test query cơ bản</h5></div>";
echo "<div class='card-body'>";

try {
    $simple_query = "SELECT COUNT(*) as total FROM thong_bao";
    $result = $conn->query($simple_query);
    if ($result) {
        $total = $result->fetch_assoc()['total'];
        echo "<div class='alert alert-success'>✅ Query cơ bản thành công: $total bản ghi</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Query cơ bản thất bại: " . htmlspecialchars($conn->error) . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></div>";

// Test 4: Test query với TB_MUCTIEU
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>4. Test query với TB_MUCTIEU</h5></div>";
echo "<div class='card-body'>";

try {
    $muctieu_query = "SELECT COUNT(*) as total FROM thong_bao WHERE TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?";
    $stmt = $conn->prepare($muctieu_query);
    if ($stmt) {
        $stmt->bind_param('s', $user_role);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();
        echo "<div class='alert alert-success'>✅ Query với TB_MUCTIEU thành công: $total bản ghi cho role '$user_role'</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Prepare thất bại: " . htmlspecialchars($conn->error) . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></div>";

// Test 5: Test stats query
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>5. Test stats query (có thể gây lỗi)</h5></div>";
echo "<div class='card-body'>";

try {
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN TB_DANHDOC = 0 THEN 1 END) as unread,
                    COUNT(CASE WHEN TB_DANHDOC = 1 THEN 1 END) as read_count
                  FROM thong_bao 
                  WHERE TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?";
    $stats_stmt = $conn->prepare($stats_sql);
    if ($stats_stmt) {
        $stats_stmt->bind_param('s', $user_role);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result()->fetch_assoc();
        $stats_stmt->close();
        
        echo "<div class='alert alert-success'>✅ Stats query thành công</div>";
        echo "<div class='row'>";
        echo "<div class='col-md-4'><div class='card text-center'><div class='card-body'><h4>" . $stats_result['total'] . "</h4><small>Tổng</small></div></div></div>";
        echo "<div class='col-md-4'><div class='card text-center'><div class='card-body'><h4>" . $stats_result['unread'] . "</h4><small>Chưa đọc</small></div></div></div>";
        echo "<div class='col-md-4'><div class='card text-center'><div class='card-body'><h4>" . $stats_result['read_count'] . "</h4><small>Đã đọc</small></div></div></div>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Prepare stats query thất bại: " . htmlspecialchars($conn->error) . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Exception trong stats query: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></div>";

// Test 6: Hiển thị dữ liệu mẫu
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>6. Dữ liệu mẫu</h5></div>";
echo "<div class='card-body'>";

try {
    $sample_query = "SELECT * FROM thong_bao ORDER BY TB_NGAYTAO DESC LIMIT 5";
    $result = $conn->query($sample_query);
    if ($result && $result->num_rows > 0) {
        echo "<div class='alert alert-success'>✅ Có " . $result->num_rows . " bản ghi</div>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>ID</th><th>Nội dung</th><th>Loại</th><th>Mục tiêu</th><th>Mức độ</th><th>Đã đọc</th><th>Ngày tạo</th></tr></thead><tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['TB_MA']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['TB_NOIDUNG'], 0, 30)) . "...</td>";
            echo "<td>" . htmlspecialchars($row['TB_LOAI'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['TB_MUCTIEU'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['TB_MUCDO'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['TB_DANHDOC'] ? '✅' : '❌') . "</td>";
            echo "<td>" . htmlspecialchars($row['TB_NGAYTAO']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Không có dữ liệu</div>";
        echo "<a href='simple_notification_test.php' class='btn btn-primary'>Tạo dữ liệu test</a>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></div>";

// Kết luận và hướng dẫn
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'><h5>📋 Kết luận</h5></div>";
echo "<div class='card-body'>";

$can_access_notifications = true;
$issues = [];

// Kiểm tra các điều kiện cần thiết
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'thong_bao'");
    if (!$table_check || $table_check->num_rows == 0) {
        $can_access_notifications = false;
        $issues[] = "Bảng thong_bao không tồn tại";
    }
    
    $describe_result = $conn->query("DESCRIBE thong_bao");
    if ($describe_result) {
        $has_tb_muctieu = false;
        while ($row = $describe_result->fetch_assoc()) {
            if ($row['Field'] === 'TB_MUCTIEU') {
                $has_tb_muctieu = true;
                break;
            }
        }
        if (!$has_tb_muctieu) {
            $can_access_notifications = false;
            $issues[] = "Cột TB_MUCTIEU không tồn tại";
        }
    }
} catch (Exception $e) {
    $can_access_notifications = false;
    $issues[] = "Lỗi truy vấn: " . $e->getMessage();
}

if ($can_access_notifications) {
    echo "<div class='alert alert-success'>";
    echo "<h6>✅ Hệ thống thông báo sẵn sàng!</h6>";
    echo "<p>Bạn có thể truy cập trang thông báo an toàn.</p>";
    echo "<a href='view/research/notifications.php' class='btn btn-success me-2'>Xem thông báo</a>";
    echo "<a href='simple_notification_test.php' class='btn btn-info'>Test thông báo</a>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "<h6>❌ Cần sửa lỗi trước khi sử dụng:</h6>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ul>";
    echo "<a href='fix_table_structure.php' class='btn btn-warning me-2'>Sửa cấu trúc bảng</a>";
    echo "<a href='setup_notifications.php' class='btn btn-primary'>Setup hệ thống</a>";
    echo "</div>";
}

echo "</div></div>";

echo "</div></body></html>";

$conn->close();
?>
