<?php
/**
 * File: fix_database_score.php  
 * Mục đích: Chạy script sửa lỗi cấu trúc database cho hệ thống điểm số
 * Tạo ngày: 05/08/2025
 * 
 * CÁCH SỬ DỤNG:
 * 1. Backup database trước khi chạy
 * 2. Truy cập: http://localhost/NLNganh/fix_database_score.php
 * 3. Kiểm tra kết quả
 */

require_once 'config/database.php';

// Chỉ cho phép chạy trong môi trường development
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('Script này chỉ được chạy trong môi trường development!');
}

echo "<h2>🔧 Script Sửa Lỗi Cấu Trúc Database - Hệ Thống Điểm Số</h2>";
echo "<hr>";

try {
    $pdo->beginTransaction();
    
    echo "<h3>📋 Bước 1: Backup dữ liệu hiện tại</h3>";
    
    // Backup bảng bien_ban
    $backup_bien_ban = "CREATE TABLE IF NOT EXISTS backup_bien_ban_" . date('Ymd_His') . " AS SELECT * FROM bien_ban";
    $pdo->exec($backup_bien_ban);
    echo "✅ Đã backup bảng bien_ban<br>";
    
    // Backup bảng thanh_vien_hoi_dong
    $backup_tv = "CREATE TABLE IF NOT EXISTS backup_thanh_vien_hoi_dong_" . date('Ymd_His') . " AS SELECT * FROM thanh_vien_hoi_dong";
    $pdo->exec($backup_tv);
    echo "✅ Đã backup bảng thanh_vien_hoi_dong<br>";
    
    echo "<h3>🔧 Bước 2: Sửa cấu trúc bảng bien_ban</h3>";
    
    // Sửa cột BB_TONGDIEM
    $alter_bien_ban = "ALTER TABLE bien_ban 
                       MODIFY COLUMN BB_TONGDIEM decimal(5,2) DEFAULT NULL 
                       COMMENT 'Tổng điểm đánh giá (thang điểm 100, VD: 85.50)'";
    $pdo->exec($alter_bien_ban);
    echo "✅ Đã sửa cấu trúc cột BB_TONGDIEM thành decimal(5,2)<br>";
    
    echo "<h3>🔧 Bước 3: Sửa cấu trúc bảng thanh_vien_hoi_dong</h3>";
    
    // Sửa comment cho cột TV_DIEM
    $alter_tv = "ALTER TABLE thanh_vien_hoi_dong 
                 MODIFY COLUMN TV_DIEM decimal(5,2) DEFAULT NULL 
                 COMMENT 'Điểm đánh giá của thành viên (thang điểm 100, VD: 85.50)'";
    $pdo->exec($alter_tv);
    echo "✅ Đã cập nhật comment cho cột TV_DIEM<br>";
    
    echo "<h3>🔄 Bước 4: Chuyển đổi dữ liệu cũ (nếu có)</h3>";
    
    // Kiểm tra và chuyển đổi dữ liệu trong bien_ban
    $convert_bien_ban = "UPDATE bien_ban 
                         SET BB_TONGDIEM = BB_TONGDIEM * 10 
                         WHERE BB_TONGDIEM IS NOT NULL 
                           AND BB_TONGDIEM <= 10 
                           AND BB_TONGDIEM > 0";
    $converted_bb = $pdo->exec($convert_bien_ban);
    echo "✅ Đã chuyển đổi {$converted_bb} bản ghi trong bảng bien_ban<br>";
    
    // Kiểm tra và chuyển đổi dữ liệu trong thanh_vien_hoi_dong
    $convert_tv = "UPDATE thanh_vien_hoi_dong 
                   SET TV_DIEM = TV_DIEM * 10 
                   WHERE TV_DIEM IS NOT NULL 
                     AND TV_DIEM <= 10 
                     AND TV_DIEM > 0";
    $converted_tv = $pdo->exec($convert_tv);
    echo "✅ Đã chuyển đổi {$converted_tv} bản ghi trong bảng thanh_vien_hoi_dong<br>";
    
    echo "<h3>🛡️ Bước 5: Thêm constraints bảo vệ dữ liệu</h3>";
    
    try {
        // Constraint cho bien_ban
        $constraint_bb = "ALTER TABLE bien_ban 
                          ADD CONSTRAINT chk_bien_ban_tongdiem_range 
                          CHECK (BB_TONGDIEM IS NULL OR (BB_TONGDIEM >= 0 AND BB_TONGDIEM <= 100))";
        $pdo->exec($constraint_bb);
        echo "✅ Đã thêm constraint cho bảng bien_ban<br>";
    } catch (Exception $e) {
        echo "⚠️ Constraint bien_ban đã tồn tại hoặc có lỗi: " . $e->getMessage() . "<br>";
    }
    
    try {
        // Constraint cho thanh_vien_hoi_dong
        $constraint_tv = "ALTER TABLE thanh_vien_hoi_dong 
                          ADD CONSTRAINT chk_thanh_vien_diem_range 
                          CHECK (TV_DIEM IS NULL OR (TV_DIEM >= 0 AND TV_DIEM <= 100))";
        $pdo->exec($constraint_tv);
        echo "✅ Đã thêm constraint cho bảng thanh_vien_hoi_dong<br>";
    } catch (Exception $e) {
        echo "⚠️ Constraint thanh_vien_hoi_dong đã tồn tại hoặc có lỗi: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>🔄 Bước 6: Tính lại tất cả điểm từ thành viên hội đồng</h3>";
    
    // Lấy danh sách tất cả quyết định có thành viên hội đồng
    $sql_decisions = "SELECT DISTINCT tv.QD_SO 
                      FROM thanh_vien_hoi_dong tv 
                      WHERE tv.TV_DIEM IS NOT NULL 
                        AND tv.TV_DIEM >= 0 
                        AND tv.TV_DIEM <= 100";
    $stmt_decisions = $pdo->query($sql_decisions);
    $decisions = $stmt_decisions->fetchAll(PDO::FETCH_COLUMN);
    
    $updated_count = 0;
    foreach ($decisions as $qd_so) {
        // Tính điểm trung bình cho mỗi quyết định
        $sql_avg = "SELECT AVG(TV_DIEM) as avg_score, COUNT(*) as count_members
                    FROM thanh_vien_hoi_dong 
                    WHERE QD_SO = :qd_so 
                      AND TV_DIEM IS NOT NULL 
                      AND TV_DIEM >= 0 
                      AND TV_DIEM <= 100";
        $stmt_avg = $pdo->prepare($sql_avg);
        $stmt_avg->execute([':qd_so' => $qd_so]);
        $result = $stmt_avg->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count_members'] > 0) {
            $avg_score = round($result['avg_score'], 2);
            
            // Cập nhật điểm vào bien_ban
            $sql_update = "UPDATE bien_ban 
                           SET BB_TONGDIEM = :avg_score 
                           WHERE QD_SO = :qd_so";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                ':avg_score' => $avg_score,
                ':qd_so' => $qd_so
            ]);
            
            $updated_count++;
            echo "📊 QD_{$qd_so}: {$result['count_members']} thành viên, điểm TB: {$avg_score}<br>";
        }
    }
    
    echo "✅ Đã tính lại điểm cho {$updated_count} biên bản<br>";
    
    echo "<h3>📊 Bước 7: Kiểm tra kết quả</h3>";
    
    // Hiển thị thống kê sau khi sửa
    $sql_check = "SELECT 
                    bb.BB_SOBB,
                    bb.BB_TONGDIEM as 'DB_Score',
                    COUNT(tv.TV_DIEM) as 'Member_Count',
                    AVG(tv.TV_DIEM) as 'Actual_Avg',
                    MIN(tv.TV_DIEM) as 'Min_Score',
                    MAX(tv.TV_DIEM) as 'Max_Score',
                    CASE 
                        WHEN COUNT(tv.TV_DIEM) = 0 THEN 'Chưa có điểm'
                        WHEN ABS(bb.BB_TONGDIEM - AVG(tv.TV_DIEM)) < 0.01 THEN 'ĐÚNG'
                        ELSE 'SAI'
                    END as 'Status'
                  FROM bien_ban bb
                  LEFT JOIN thanh_vien_hoi_dong tv ON bb.QD_SO = tv.QD_SO AND tv.TV_DIEM IS NOT NULL
                  GROUP BY bb.BB_SOBB, bb.BB_TONGDIEM
                  ORDER BY bb.BB_SOBB";
    
    $stmt_check = $pdo->query($sql_check);
    $results = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($results)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Biên bản</th><th>Điểm DB</th><th>Số TV</th><th>Điểm TB</th><th>Min</th><th>Max</th><th>Trạng thái</th>";
        echo "</tr>";
        
        foreach ($results as $row) {
            $status_color = $row['Status'] === 'ĐÚNG' ? 'green' : ($row['Status'] === 'SAI' ? 'red' : 'orange');
            echo "<tr>";
            echo "<td>{$row['BB_SOBB']}</td>";
            echo "<td>" . ($row['DB_Score'] ? number_format($row['DB_Score'], 2) : 'NULL') . "</td>";
            echo "<td>{$row['Member_Count']}</td>";
            echo "<td>" . ($row['Actual_Avg'] ? number_format($row['Actual_Avg'], 2) : 'NULL') . "</td>";
            echo "<td>" . ($row['Min_Score'] ? number_format($row['Min_Score'], 2) : 'NULL') . "</td>";
            echo "<td>" . ($row['Max_Score'] ? number_format($row['Max_Score'], 2) : 'NULL') . "</td>";
            echo "<td style='color: {$status_color}; font-weight: bold;'>{$row['Status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "📝 Chưa có dữ liệu biên bản nào.<br>";
    }
    
    $pdo->commit();
    
    echo "<h3>🎉 Hoàn thành!</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Đã hoàn thành sửa lỗi database:</h4>";
    echo "<ul>";
    echo "<li>Sửa cấu trúc bảng bien_ban: BB_TONGDIEM decimal(5,2)</li>";
    echo "<li>Sửa cấu trúc bảng thanh_vien_hoi_dong: TV_DIEM decimal(5,2)</li>";
    echo "<li>Chuyển đổi dữ liệu cũ từ thang điểm 10 sang 100</li>";
    echo "<li>Thêm constraints bảo vệ dữ liệu (0-100)</li>";
    echo "<li>Tính lại tất cả điểm từ thành viên hội đồng</li>";
    echo "<li>Tạo backup dữ liệu trước khi sửa đổi</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>🔧 Bước tiếp theo:</h4>";
    echo "<ol>";
    echo "<li>Kiểm tra lại trang view_project.php - tab Biên bản</li>";
    echo "<li>Verify điểm số hiển thị chính xác</li>";
    echo "<li>Test tính năng cập nhật điểm mới</li>";
    echo "<li>Xóa file này sau khi hoàn thành</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Lỗi khi sửa database:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Database đã được rollback về trạng thái ban đầu.</p>";
    echo "</div>";
}
?>
