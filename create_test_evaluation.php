<?php
include 'include/connect.php';

// Kiểm tra và tạo dữ liệu test
echo "<h2>Tạo dữ liệu test file đánh giá</h2>";

// 1. Lấy một đề tài đã hoàn thành
$project_sql = "SELECT DT_MADT, DT_TENDT FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hoàn thành' LIMIT 1";
$result = $conn->query($project_sql);

if ($result && $result->num_rows > 0) {
    $project = $result->fetch_assoc();
    $project_id = $project['DT_MADT'];
    echo "<p>Đề tài test: " . htmlspecialchars($project['DT_TENDT']) . " (ID: " . htmlspecialchars($project_id) . ")</p>";
    
    // 2. Tìm biên bản của đề tài này
    $bb_sql = "SELECT BB_SOBB FROM bien_ban WHERE DT_MADT = ? LIMIT 1";
    $stmt = $conn->prepare($bb_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $bb_result = $stmt->get_result();
    
    if ($bb_result->num_rows > 0) {
        $bb = $bb_result->fetch_assoc();
        $bb_id = $bb['BB_SOBB'];
        echo "<p>Biên bản: " . htmlspecialchars($bb_id) . "</p>";
        
        // 3. Kiểm tra xem đã có file đánh giá chưa
        $check_sql = "SELECT COUNT(*) as count FROM file_danh_gia WHERE BB_SOBB = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $bb_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        $file_count = $check_result->fetch_assoc()['count'];
        
        echo "<p>Số file đánh giá hiện có: " . $file_count . "</p>";
        
        if ($file_count == 0) {
            // 4. Tạo một file đánh giá test
            $new_file_id = 'FDG' . str_pad(1, 7, '0', STR_PAD_LEFT);
            $file_name = "File đánh giá test cho đề tài " . $project_id;
            $file_path = "test_evaluation_file.pdf";
            
            // Kiểm tra cột FDG_DUONGDAN có tồn tại không
            $check_column_sql = "SHOW COLUMNS FROM file_danh_gia LIKE 'FDG_DUONGDAN'";
            $column_result = $conn->query($check_column_sql);
            
            if ($column_result->num_rows === 0) {
                echo "<p>Thêm cột FDG_DUONGDAN...</p>";
                $add_column_sql = "ALTER TABLE file_danh_gia ADD COLUMN FDG_DUONGDAN VARCHAR(500) NULL AFTER FDG_TEN";
                if ($conn->query($add_column_sql)) {
                    echo "<p style='color: green;'>✓ Đã thêm cột FDG_DUONGDAN</p>";
                } else {
                    echo "<p style='color: red;'>✗ Lỗi thêm cột: " . $conn->error . "</p>";
                }
            } else {
                echo "<p style='color: green;'>✓ Cột FDG_DUONGDAN đã tồn tại</p>";
            }
            
            // Tạo file test
            $insert_sql = "INSERT INTO file_danh_gia (FDG_MA, BB_SOBB, FDG_TEN, FDG_DUONGDAN, FDG_NGAYCAP) VALUES (?, ?, ?, ?, CURDATE())";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssss", $new_file_id, $bb_id, $file_name, $file_path);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Đã tạo file đánh giá test: " . htmlspecialchars($new_file_id) . "</p>";
                
                // Tạo file vật lý test
                $upload_dir = 'uploads/evaluation_files/';
                $test_content = "Đây là file đánh giá test cho đề tài " . $project_id;
                file_put_contents($upload_dir . $file_path, $test_content);
                echo "<p style='color: green;'>✓ Đã tạo file vật lý test</p>";
                
                echo "<p><strong>Bây giờ hãy kiểm tra tab đánh giá trong view_project.php?id=" . urlencode($project_id) . "</strong></p>";
                echo "<p><a href='view/student/view_project.php?id=" . urlencode($project_id) . "' target='_blank'>Xem đề tài</a></p>";
                
            } else {
                echo "<p style='color: red;'>✗ Lỗi tạo file đánh giá: " . $stmt->error . "</p>";
            }
            
        } else {
            echo "<p>Đã có file đánh giá. <a href='view/student/view_project.php?id=" . urlencode($project_id) . "' target='_blank'>Xem đề tài</a></p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Không tìm thấy biên bản cho đề tài này</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Không tìm thấy đề tài đã hoàn thành</p>";
}
?>
