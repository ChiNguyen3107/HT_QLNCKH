<?php
require_once '../../config/database.php';

try {
    // Kiểm tra các bảng liên quan đến tiêu chí đánh giá
    $tables_to_check = [
        'tieu_chi',
        'tieu_chi_danh_gia', 
        'chi_tiet_danh_gia',
        'bang_diem_danh_gia',
        'danh_gia_tieu_chi'
    ];
    
    echo "<h3>Kiểm tra cấu trúc bảng tiêu chí đánh giá</h3>";
    
    foreach ($tables_to_check as $table) {
        echo "<h4>Bảng: $table</h4>";
        
        // Kiểm tra xem bảng có tồn tại không
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            // Hiển thị cấu trúc bảng
            $stmt = $pdo->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><th>Tên cột</th><th>Kiểu dữ liệu</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "<td>{$col['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Hiển thị dữ liệu mẫu (nếu có)
            $stmt = $pdo->prepare("SELECT * FROM $table LIMIT 5");
            $stmt->execute();
            $sample_data = $stmt->fetchAll();
            
            if (!empty($sample_data)) {
                echo "<h5>Dữ liệu mẫu:</h5>";
                echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
                
                // Header
                echo "<tr>";
                foreach (array_keys($sample_data[0]) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>$key</th>";
                    }
                }
                echo "</tr>";
                
                // Data
                foreach ($sample_data as $row) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        if (!is_numeric($key)) {
                            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                        }
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p><em>Bảng trống</em></p>";
            }
            
        } else {
            echo "<p style='color: red;'>Bảng không tồn tại</p>";
        }
        
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
