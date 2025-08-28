<?php
/**
 * Fix Notification Errors
 * Sửa lỗi hệ thống thông báo
 */

include 'include/connect.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Fix Notification Errors</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1>🔧 Sửa lỗi Hệ thống Thông báo</h1>
    <hr>
";

try {
    echo "<div class='alert alert-info'>📝 Đang sửa lỗi...</div>";
    
    // 1. Tạo bảng thông báo cơ bản
    echo "<h4>1. Tạo bảng thông báo</h4>";
    
    $create_table_sql = "CREATE TABLE IF NOT EXISTS thong_bao (
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
        INDEX idx_danhdoc (TB_DANHDOC),
        INDEX idx_muctieu (TB_MUCTIEU),
        INDEX idx_ngaytao (TB_NGAYTAO)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($create_table_sql)) {
        echo "<div class='alert alert-success'>✅ Tạo bảng thong_bao thành công</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Bảng thong_bao đã tồn tại hoặc lỗi: " . $conn->error . "</div>";
    }
    
    // 2. Tạo function đơn giản
    echo "<h4>2. Tạo function đếm thông báo</h4>";
    
    $function_sql = "
    DROP FUNCTION IF EXISTS fn_dem_thong_bao_chua_doc;
    
    DELIMITER //
    CREATE FUNCTION fn_dem_thong_bao_chua_doc(
        p_user_id VARCHAR(8),
        p_user_role VARCHAR(20)
    ) RETURNS INT
    READS SQL DATA
    DETERMINISTIC
    BEGIN
        DECLARE v_count INT DEFAULT 0;
        
        SELECT COUNT(*) INTO v_count
        FROM thong_bao tb
        WHERE tb.TB_DANHDOC = 0
        AND (
            tb.TB_MUCTIEU = 'all'
            OR (tb.TB_MUCTIEU = p_user_role)
        );
        
        RETURN v_count;
    END //
    DELIMITER ;
    ";
    
    // Thực thi từng câu lệnh
    $statements = [
        "DROP FUNCTION IF EXISTS fn_dem_thong_bao_chua_doc",
        "CREATE FUNCTION fn_dem_thong_bao_chua_doc(
            p_user_id VARCHAR(8),
            p_user_role VARCHAR(20)
        ) RETURNS INT
        READS SQL DATA
        DETERMINISTIC
        BEGIN
            DECLARE v_count INT DEFAULT 0;
            
            SELECT COUNT(*) INTO v_count
            FROM thong_bao tb
            WHERE tb.TB_DANHDOC = 0
            AND (
                tb.TB_MUCTIEU = 'all'
                OR (tb.TB_MUCTIEU = p_user_role)
            );
            
            RETURN v_count;
        END"
    ];
    
    foreach ($statements as $sql) {
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Thực thi thành công: " . substr($sql, 0, 50) . "...</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Lỗi: " . $conn->error . "</div>";
        }
    }
    
    // 3. Tạo trigger đơn giản cho gia hạn
    echo "<h4>3. Tạo trigger cho gia hạn</h4>";
    
    $trigger_sql = "
    DROP TRIGGER IF EXISTS tr_gia_han_notification;
    
    CREATE TRIGGER tr_gia_han_notification
    AFTER INSERT ON de_tai_gia_han
    FOR EACH ROW
    BEGIN
        DECLARE v_noidung TEXT;
        
        SET v_noidung = CONCAT(
            'Yêu cầu gia hạn đề tài mới: ', 
            NEW.DT_MADT,
            ' - Thời gian: ',
            NEW.GH_SOTHANGGIAHAN,
            ' tháng'
        );
        
        INSERT INTO thong_bao (
            TB_NOIDUNG, 
            TB_LOAI, 
            TB_MUCTIEU, 
            TB_MUCDO,
            DT_MADT,
            SV_MASV
        ) VALUES (
            v_noidung,
            'gia_han_yeu_cau',
            'research_manager',
            'cao',
            NEW.DT_MADT,
            NEW.SV_MASV
        );
    END
    ";
    
    $trigger_statements = [
        "DROP TRIGGER IF EXISTS tr_gia_han_notification",
        "CREATE TRIGGER tr_gia_han_notification
        AFTER INSERT ON de_tai_gia_han
        FOR EACH ROW
        BEGIN
            DECLARE v_noidung TEXT;
            
            SET v_noidung = CONCAT(
                'Yêu cầu gia hạn đề tài mới: ', 
                NEW.DT_MADT,
                ' - Thời gian: ',
                NEW.GH_SOTHANGGIAHAN,
                ' tháng'
            );
            
            INSERT INTO thong_bao (
                TB_NOIDUNG, 
                TB_LOAI, 
                TB_MUCTIEU, 
                TB_MUCDO,
                DT_MADT,
                SV_MASV
            ) VALUES (
                v_noidung,
                'gia_han_yeu_cau',
                'research_manager',
                'cao',
                NEW.DT_MADT,
                NEW.SV_MASV
            );
        END"
    ];
    
    foreach ($trigger_statements as $sql) {
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Trigger thành công: " . substr($sql, 0, 30) . "...</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Lỗi trigger: " . $conn->error . "</div>";
        }
    }
    
    // 4. Tạo thông báo test
    echo "<h4>4. Tạo thông báo test</h4>";
    
    $test_notifications = [
        "🔔 Hệ thống thông báo đã được kích hoạt thành công!",
        "📝 Test thông báo cho Research Manager",
        "⚡ Thông báo real-time đang hoạt động"
    ];
    
    foreach ($test_notifications as $content) {
        $stmt = $conn->prepare("INSERT INTO thong_bao (TB_NOIDUNG, TB_LOAI, TB_MUCTIEU, TB_MUCDO) VALUES (?, 'test', 'research_manager', 'trung_binh')");
        $stmt->bind_param('s', $content);
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ Tạo thông báo: " . htmlspecialchars($content) . "</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Lỗi tạo thông báo: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    
    // 5. Test function
    echo "<h4>5. Test function</h4>";
    
    try {
        $result = $conn->query("SELECT fn_dem_thong_bao_chua_doc('test', 'research_manager') as count");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<div class='alert alert-success'>✅ Function hoạt động! Số thông báo chưa đọc: $count</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Function lỗi: " . $conn->error . "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Exception: " . $e->getMessage() . "</div>";
    }
    
    // 6. Hiển thị thông báo hiện tại
    echo "<h4>6. Thông báo hiện tại</h4>";
    
    $result = $conn->query("SELECT * FROM thong_bao ORDER BY TB_NGAYTAO DESC LIMIT 10");
    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-sm'>";
        echo "<thead><tr><th>ID</th><th>Nội dung</th><th>Loại</th><th>Mục tiêu</th><th>Ngày tạo</th><th>Đã đọc</th></tr></thead><tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['TB_MA'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['TB_NOIDUNG'], 0, 50)) . "...</td>";
            echo "<td><span class='badge bg-info'>" . $row['TB_LOAI'] . "</span></td>";
            echo "<td><span class='badge bg-primary'>" . $row['TB_MUCTIEU'] . "</span></td>";
            echo "<td><small>" . $row['TB_NGAYTAO'] . "</small></td>";
            echo "<td>" . ($row['TB_DANHDOC'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Không có thông báo nào</div>";
    }
    
    echo "<hr><div class='alert alert-success'>";
    echo "<h5>🎉 Hoàn tất sửa lỗi!</h5>";
    echo "<p>Hệ thống thông báo đã được sửa và sẵn sàng hoạt động.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Lỗi nghiêm trọng: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "
    <div class='d-flex gap-2 mb-4'>
        <a href='api/simple_notification_count.php' class='btn btn-primary' target='_blank'>
            Test Simple API
        </a>
        <a href='debug_notification_api.php' class='btn btn-info'>
            Debug Page
        </a>
        <a href='test_complete_notification.php' class='btn btn-success'>
            Complete Test
        </a>
    </div>
</div>
</body>
</html>";

$conn->close();
?>

