<?php
include 'include/connect.php';

echo "<h2>Kiểm tra và sửa lỗi hiển thị file đánh giá</h2>";

// 1. Kiểm tra cấu trúc bảng file_danh_gia
echo "<h3>1. Kiểm tra bảng file_danh_gia</h3>";
$check_table = "SHOW TABLES LIKE 'file_danh_gia'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    echo "<p style='color: red;'>Bảng file_danh_gia không tồn tại! Tạo bảng...</p>";
    $create_table = "
    CREATE TABLE file_danh_gia (
        FDG_MA VARCHAR(10) PRIMARY KEY,
        BB_SOBB VARCHAR(10) NOT NULL,
        FDG_TEN VARCHAR(255) NOT NULL,
        FDG_DUONGDAN VARCHAR(500) NULL,
        FDG_NGAYCAP DATE NULL,
        FOREIGN KEY (BB_SOBB) REFERENCES bien_ban(BB_SOBB)
    )";
    
    if ($conn->query($create_table)) {
        echo "<p style='color: green;'>✓ Đã tạo bảng file_danh_gia</p>";
    } else {
        echo "<p style='color: red;'>✗ Lỗi tạo bảng: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Bảng file_danh_gia đã tồn tại</p>";
    
    // Kiểm tra cột FDG_DUONGDAN
    $check_column = "SHOW COLUMNS FROM file_danh_gia LIKE 'FDG_DUONGDAN'";
    $col_result = $conn->query($check_column);
    
    if ($col_result->num_rows == 0) {
        echo "<p style='color: orange;'>Thêm cột FDG_DUONGDAN...</p>";
        $add_column = "ALTER TABLE file_danh_gia ADD COLUMN FDG_DUONGDAN VARCHAR(500) NULL AFTER FDG_TEN";
        if ($conn->query($add_column)) {
            echo "<p style='color: green;'>✓ Đã thêm cột FDG_DUONGDAN</p>";
        } else {
            echo "<p style='color: red;'>✗ Lỗi thêm cột: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Cột FDG_DUONGDAN đã tồn tại</p>";
    }
}

// 2. Lấy một đề tài đã hoàn thành để test
echo "<h3>2. Tìm đề tài để test</h3>";
$project_sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, bb.BB_SOBB 
                FROM de_tai_nghien_cuu dt 
                LEFT JOIN bien_ban bb ON dt.DT_MADT = bb.DT_MADT 
                WHERE dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Đã hoàn thành') 
                LIMIT 3";
$result = $conn->query($project_sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><th>Mã đề tài</th><th>Tên đề tài</th><th>Trạng thái</th><th>Biên bản</th><th>Số file đánh giá</th><th>Hành động</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        // Đếm file đánh giá cho từng đề tài
        $file_count = 0;
        if ($row['BB_SOBB']) {
            $count_sql = "SELECT COUNT(*) as count FROM file_danh_gia WHERE BB_SOBB = ?";
            $stmt = $conn->prepare($count_sql);
            $stmt->bind_param("s", $row['BB_SOBB']);
            $stmt->execute();
            $count_result = $stmt->get_result();
            $file_count = $count_result->fetch_assoc()['count'];
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TENDT']) . "</td>";
        echo "<td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_SOBB'] ?? 'Chưa có') . "</td>";
        echo "<td>" . $file_count . "</td>";
        echo "<td>";
        echo "<a href='view/student/view_project.php?id=" . urlencode($row['DT_MADT']) . "' target='_blank'>Xem đề tài</a>";
        
        if ($row['BB_SOBB'] && $file_count == 0) {
            echo " | <a href='?create_test_file=" . urlencode($row['BB_SOBB']) . "&project=" . urlencode($row['DT_MADT']) . "'>Tạo file test</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Không tìm thấy đề tài phù hợp</p>";
}

// 3. Tạo file test nếu được yêu cầu
if (isset($_GET['create_test_file']) && isset($_GET['project'])) {
    $bb_id = $_GET['create_test_file'];
    $project_id = $_GET['project'];
    
    echo "<h3>3. Tạo file đánh giá test</h3>";
    
    // Tạo ID mới
    $max_id_sql = "SELECT MAX(CAST(SUBSTRING(FDG_MA, 4) AS UNSIGNED)) as max_id FROM file_danh_gia WHERE FDG_MA LIKE 'FDG%'";
    $max_result = $conn->query($max_id_sql);
    $max_id = 0;
    if ($max_result && $max_result->num_rows > 0) {
        $max_id = $max_result->fetch_assoc()['max_id'] ?? 0;
    }
    $new_id = 'FDG' . str_pad($max_id + 1, 7, '0', STR_PAD_LEFT);
    
    $file_name = "File đánh giá test - " . date('d/m/Y H:i:s');
    $file_path = "test_evaluation_" . time() . ".txt";
    
    // Tạo file vật lý
    $upload_dir = 'uploads/evaluation_files/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $test_content = "Đây là file đánh giá test cho đề tài " . $project_id . "\n";
    $test_content .= "Biên bản: " . $bb_id . "\n";
    $test_content .= "Ngày tạo: " . date('d/m/Y H:i:s') . "\n";
    $test_content .= "Điểm: 85/100 - Tốt\n";
    
    if (file_put_contents($upload_dir . $file_path, $test_content)) {
        // Thêm vào database
        $insert_sql = "INSERT INTO file_danh_gia (FDG_MA, BB_SOBB, FDG_TEN, FDG_DUONGDAN, FDG_NGAYCAP) VALUES (?, ?, ?, ?, CURDATE())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssss", $new_id, $bb_id, $file_name, $file_path);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Đã tạo file đánh giá test: " . htmlspecialchars($new_id) . "</p>";
            echo "<p><strong><a href='view/student/view_project.php?id=" . urlencode($project_id) . "' target='_blank'>Kiểm tra đề tài ngay</a></strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Lỗi thêm vào database: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Lỗi tạo file vật lý</p>";
    }
}

echo "<hr>";
echo "<h3>Hướng dẫn khắc phục</h3>";
echo "<ol>";
echo "<li><strong>Nếu không thấy tab đánh giá:</strong> Đảm bảo đề tài có biên bản nghiệm thu</li>";
echo "<li><strong>Nếu hiển thị 'Chưa có file đánh giá':</strong> Tạo file test bằng link ở trên</li>";
echo "<li><strong>Nếu file không hiển thị:</strong> Kiểm tra cột FDG_DUONGDAN và file vật lý trong uploads/evaluation_files/</li>";
echo "<li><strong>Quyền tải file:</strong> Chỉ chủ nhiệm đề tài có thể tải file đánh giá</li>";
echo "<li><strong>Trạng thái đề tài:</strong> Chỉ cho phép tải file khi đề tài 'Đang thực hiện' hoặc 'Đã hoàn thành'</li>";
echo "</ol>";
?>
