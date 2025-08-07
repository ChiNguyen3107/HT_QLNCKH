<?php
include 'include/connect.php';

// Test với một đề tài thực tế
$project_id = 'DT0000014'; // Đề tài có trạng thái "Đã hoàn thành"

echo "<h2>Test SQL Query Fixed - Đề tài: $project_id</h2>";

// Test SQL query mới
$decision_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
                FROM de_tai_nghien_cuu dt
                INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE dt.DT_MADT = ?";

$stmt = $conn->prepare($decision_sql);
if ($stmt === false) {
    echo "❌ Lỗi prepare SQL: " . $conn->error;
} else {
    $stmt->bind_param("s", $project_id);
    if (!$stmt->execute()) {
        echo "❌ Lỗi execute SQL: " . $stmt->error;
    } else {
        $decision_result = $stmt->get_result();
        echo "✅ SQL thành công. Tìm thấy " . $decision_result->num_rows . " quyết định nghiệm thu<br>";
        
        if ($decision_result->num_rows > 0) {
            $decision = $decision_result->fetch_assoc();
            
            echo "<h3>Thông tin quyết định:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Trường</th><th>Giá trị</th></tr>";
            foreach ($decision as $key => $value) {
                echo "<tr><td>$key</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
            
            // Test file đánh giá nếu có biên bản
            if (!empty($decision['BB_SOBB'])) {
                echo "<h3>Kiểm tra file đánh giá:</h3>";
                $eval_files_sql = "SELECT * FROM file_danh_gia WHERE BB_SOBB = ?";
                $stmt2 = $conn->prepare($eval_files_sql);
                if ($stmt2) {
                    $stmt2->bind_param("s", $decision['BB_SOBB']);
                    $stmt2->execute();
                    $eval_files_result = $stmt2->get_result();
                    echo "Tìm thấy " . $eval_files_result->num_rows . " file đánh giá<br>";
                    
                    if ($eval_files_result->num_rows > 0) {
                        echo "<table border='1' style='border-collapse: collapse;'>";
                        echo "<tr><th>ID</th><th>Tên file</th><th>Loại đánh giá</th></tr>";
                        while ($file = $eval_files_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($file['FDG_MA'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($file['FDG_TENFILE'] ?? '') . "</td>";
                            echo "<td>" . htmlspecialchars($file['FDG_LOAIDANHGIA'] ?? '') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
            }
        } else {
            echo "ℹ️ Chưa có quyết định nghiệm thu cho đề tài này";
        }
    }
}

// Test thêm với đề tài khác
echo "<hr><h2>Test với đề tài khác:</h2>";
$test_projects = ['DT0000011', 'DT0000012', 'DT0000015'];

foreach ($test_projects as $test_id) {
    echo "<h4>Đề tài: $test_id</h4>";
    $stmt = $conn->prepare($decision_sql);
    if ($stmt) {
        $stmt->bind_param("s", $test_id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo "Số quyết định tìm thấy: " . $result->num_rows . "<br>";
    }
}
?>
