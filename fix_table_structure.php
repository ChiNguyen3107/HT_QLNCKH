<?php
/**
 * Fix Table Structure
 * Sửa cấu trúc bảng thong_bao
 */

include 'include/connect.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Fix Table Structure</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1>🔧 Sửa cấu trúc bảng thong_bao</h1>
    <hr>
";

try {
    // 1. Kiểm tra cấu trúc bảng hiện tại
    echo "<h4>1. Kiểm tra cấu trúc bảng hiện tại</h4>";
    
    $result = $conn->query("DESCRIBE thong_bao");
    if ($result) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>";
        
        $existing_columns = [];
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        
        echo "<div class='alert alert-info'>Các cột hiện có: " . implode(', ', $existing_columns) . "</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Không thể kiểm tra cấu trúc bảng: " . $conn->error . "</div>";
    }
    
    // 2. Xóa bảng cũ và tạo mới
    echo "<h4>2. Tạo lại bảng thong_bao</h4>";
    
    // Backup data nếu có
    $backup_data = [];
    $backup_result = $conn->query("SELECT * FROM thong_bao");
    if ($backup_result) {
        while ($row = $backup_result->fetch_assoc()) {
            $backup_data[] = $row;
        }
        echo "<div class='alert alert-info'>📋 Đã backup " . count($backup_data) . " thông báo</div>";
    }
    
    // Drop và tạo lại bảng
    $drop_sql = "DROP TABLE IF EXISTS thong_bao";
    if ($conn->query($drop_sql)) {
        echo "<div class='alert alert-success'>✅ Đã xóa bảng cũ</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Lỗi xóa bảng: " . $conn->error . "</div>";
    }
    
    // Tạo bảng mới với cấu trúc đầy đủ
    $create_sql = "CREATE TABLE thong_bao (
        TB_MA INT AUTO_INCREMENT PRIMARY KEY,
        TB_NOIDUNG TEXT NOT NULL,
        TB_NGAYTAO DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        TB_DANHDOC TINYINT(1) NOT NULL DEFAULT 0,
        TB_LOAI VARCHAR(50) DEFAULT 'Thông báo',
        TB_MUCTIEU ENUM('admin', 'research_manager', 'teacher', 'student', 'all') DEFAULT 'all',
        TB_MUCDO ENUM('thap', 'trung_binh', 'cao', 'khan_cap') DEFAULT 'trung_binh',
        DT_MADT CHAR(10) NULL,
        GV_MAGV CHAR(8) NULL,
        SV_MASV CHAR(8) NULL,
        QL_MA CHAR(8) NULL,
        TB_NGUOITAO CHAR(8) NULL,
        TB_HANHDONG VARCHAR(100) NULL,
        TB_METADATA JSON NULL,
        INDEX idx_danhdoc (TB_DANHDOC),
        INDEX idx_muctieu (TB_MUCTIEU),
        INDEX idx_ngaytao (TB_NGAYTAO)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($create_sql)) {
        echo "<div class='alert alert-success'>✅ Tạo bảng mới thành công</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Lỗi tạo bảng: " . $conn->error . "</div>";
    }
    
    // 3. Restore data nếu có
    if (!empty($backup_data)) {
        echo "<h4>3. Khôi phục dữ liệu</h4>";
        
        foreach ($backup_data as $row) {
            $noidung = $row['TB_NOIDUNG'];
            $ngaytao = $row['TB_NGAYTAO'] ?? date('Y-m-d H:i:s');
            $danhdoc = $row['TB_DANHDOC'] ?? 0;
            $loai = $row['TB_LOAI'] ?? 'Thông báo';
            
            // Sử dụng giá trị mặc định cho các cột mới
            $muctieu = 'all';
            $mucdo = 'trung_binh';
            
            $stmt = $conn->prepare("INSERT INTO thong_bao (TB_NOIDUNG, TB_NGAYTAO, TB_DANHDOC, TB_LOAI, TB_MUCTIEU, TB_MUCDO) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssisss', $noidung, $ngaytao, $danhdoc, $loai, $muctieu, $mucdo);
            
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>✅ Khôi phục: " . substr(htmlspecialchars($noidung), 0, 50) . "...</div>";
            } else {
                echo "<div class='alert alert-warning'>⚠️ Lỗi khôi phục: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
    
    // 4. Tạo dữ liệu test
    echo "<h4>4. Tạo dữ liệu test</h4>";
    
    $test_notifications = [
        ['noidung' => '🔔 Hệ thống thông báo đã được khởi tạo thành công!', 'muctieu' => 'all', 'mucdo' => 'trung_binh'],
        ['noidung' => '📝 Test thông báo cho Research Manager', 'muctieu' => 'research_manager', 'mucdo' => 'cao'],
        ['noidung' => '👨‍🏫 Test thông báo cho Teacher', 'muctieu' => 'teacher', 'mucdo' => 'trung_binh'],
        ['noidung' => '🎓 Test thông báo cho Student', 'muctieu' => 'student', 'mucdo' => 'thap'],
        ['noidung' => '⚡ Test thông báo khẩn cấp', 'muctieu' => 'all', 'mucdo' => 'khan_cap']
    ];
    
    foreach ($test_notifications as $notification) {
        $stmt = $conn->prepare("INSERT INTO thong_bao (TB_NOIDUNG, TB_LOAI, TB_MUCTIEU, TB_MUCDO, TB_NGUOITAO) VALUES (?, 'test', ?, ?, 'system')");
        $stmt->bind_param('sss', $notification['noidung'], $notification['muctieu'], $notification['mucdo']);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ Tạo test: " . htmlspecialchars($notification['noidung']) . "</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Lỗi tạo test: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    
    // 5. Kiểm tra cấu trúc mới
    echo "<h4>5. Cấu trúc bảng mới</h4>";
    
    $result = $conn->query("DESCRIBE thong_bao");
    if ($result) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm table-striped'>";
        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead><tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>" . $row['Field'] . "</strong></td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    }
    
    // 6. Test API
    echo "<h4>6. Test API</h4>";
    
    try {
        // Test count query
        $count_result = $conn->query("SELECT COUNT(*) as count FROM thong_bao WHERE TB_DANHDOC = 0 AND (TB_MUCTIEU = 'all' OR TB_MUCTIEU = 'research_manager')");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['count'];
            echo "<div class='alert alert-success'>✅ Test count query thành công: $count thông báo chưa đọc</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Lỗi test count: " . $conn->error . "</div>";
        }
        
        // Test select query
        $select_result = $conn->query("SELECT TB_MA, TB_NOIDUNG, TB_MUCTIEU, TB_MUCDO, TB_NGAYTAO FROM thong_bao ORDER BY TB_NGAYTAO DESC LIMIT 3");
        if ($select_result) {
            echo "<div class='alert alert-success'>✅ Test select query thành công</div>";
            echo "<div class='table-responsive'>";
            echo "<table class='table table-sm'>";
            echo "<thead><tr><th>ID</th><th>Nội dung</th><th>Mục tiêu</th><th>Mức độ</th><th>Ngày tạo</th></tr></thead><tbody>";
            
            while ($row = $select_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['TB_MA'] . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['TB_NOIDUNG'], 0, 40)) . "...</td>";
                echo "<td><span class='badge bg-primary'>" . $row['TB_MUCTIEU'] . "</span></td>";
                echo "<td><span class='badge bg-warning'>" . $row['TB_MUCDO'] . "</span></td>";
                echo "<td><small>" . $row['TB_NGAYTAO'] . "</small></td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Lỗi test select: " . $conn->error . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Exception: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h5>🎉 Hoàn tất sửa cấu trúc bảng!</h5>";
    echo "<p>Bảng thong_bao đã được tạo lại với cấu trúc đầy đủ và dữ liệu test.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Lỗi nghiêm trọng: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "
    <div class='d-flex gap-2 mb-4'>
        <a href='api/simple_notification_count.php' class='btn btn-primary' target='_blank'>
            Test Simple API
        </a>
        <a href='simple_notification_test.php' class='btn btn-success'>
            Simple Test Page
        </a>
        <a href='test_complete_notification.php' class='btn btn-info'>
            Complete Test
        </a>
    </div>
</div>
</body>
</html>";

$conn->close();
?>

