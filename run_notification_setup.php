<?php
/**
 * Setup Notification System
 * Chạy script thiết lập hệ thống thông báo
 */

include 'include/connect.php';

echo "<h2>Thiết lập Hệ thống Thông báo</h2>";
echo "<pre>";

try {
    // Đọc và thực thi script SQL
    $sql_content = file_get_contents('create_comprehensive_notification_system.sql');
    
    if (!$sql_content) {
        throw new Exception("Không thể đọc file SQL");
    }
    
    // Tách các câu lệnh SQL
    $statements = explode(';', $sql_content);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $result = $conn->query($statement);
            if ($result) {
                $success_count++;
                echo "✓ Thực thi thành công: " . substr($statement, 0, 50) . "...\n";
            } else {
                $error_count++;
                echo "✗ Lỗi: " . $conn->error . "\n";
                echo "   SQL: " . substr($statement, 0, 100) . "...\n";
            }
        } catch (Exception $e) {
            $error_count++;
            echo "✗ Exception: " . $e->getMessage() . "\n";
            echo "   SQL: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n=== KẾT QUẢ ===\n";
    echo "Thành công: $success_count câu lệnh\n";
    echo "Lỗi: $error_count câu lệnh\n";
    
    if ($error_count == 0) {
        echo "\n🎉 Thiết lập hệ thống thông báo hoàn tất!\n";
    } else {
        echo "\n⚠️ Có một số lỗi trong quá trình thiết lập.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi nghiêm trọng: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Test tạo một thông báo
echo "<h3>Test tạo thông báo</h3>";
try {
    $test_metadata = json_encode([
        'DT_MADT' => 'TEST001',
        'DT_TENDT' => 'Test thông báo hệ thống',
        'SV_HOTEN' => 'Sinh viên Test',
        'NGUOI_TAO' => 'system'
    ]);
    
    $stmt = $conn->prepare("CALL sp_tao_thong_bao(?, ?, ?)");
    $sukien = 'de_tai_moi';
    $nguoi_nhan = 'research_manager';
    $stmt->bind_param('sss', $sukien, $nguoi_nhan, $test_metadata);
    $stmt->execute();
    $stmt->close();
    
    echo "<p>✅ Tạo thông báo test thành công!</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Lỗi tạo thông báo test: " . $e->getMessage() . "</p>";
}

// Hiển thị thông báo hiện có
echo "<h3>Thông báo hiện tại</h3>";
try {
    $result = $conn->query("SELECT * FROM thong_bao ORDER BY TB_NGAYTAO DESC LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nội dung</th><th>Loại</th><th>Mục tiêu</th><th>Mức độ</th><th>Ngày tạo</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['TB_MA'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['TB_NOIDUNG'], 0, 50)) . "...</td>";
            echo "<td>" . $row['TB_LOAI'] . "</td>";
            echo "<td>" . $row['TB_MUCTIEU'] . "</td>";
            echo "<td>" . $row['TB_MUCDO'] . "</td>";
            echo "<td>" . $row['TB_NGAYTAO'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có thông báo nào</p>";
    }
} catch (Exception $e) {
    echo "<p>Lỗi hiển thị thông báo: " . $e->getMessage() . "</p>";
}

echo '<br><a href="test_notification_system.php">🔗 Đi đến trang test thông báo</a>';
?>

