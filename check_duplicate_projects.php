<?php
// Script kiểm tra đề tài trùng lặp
include 'include/connect.php';

echo "<h2>🔍 KIỂM TRA ĐỀ TÀI TRÙNG LẶP</h2>";

// 1. Kiểm tra đề tài có tên trùng lặp
echo "<h3>1. Đề tài có tên trùng lặp:</h3>";
$duplicate_title_query = "
    SELECT DT_TENDT, COUNT(*) as count, GROUP_CONCAT(DT_MADT) as project_ids
    FROM de_tai_nghien_cuu 
    GROUP BY DT_TENDT 
    HAVING COUNT(*) > 1
    ORDER BY count DESC
";

$duplicate_result = $conn->query($duplicate_title_query);

if ($duplicate_result && $duplicate_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Tên đề tài</th>";
    echo "<th>Số lượng</th>";
    echo "<th>Mã đề tài</th>";
    echo "<th>Hành động</th>";
    echo "</tr>";
    
    while ($row = $duplicate_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['DT_TENDT']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $row['project_ids'] . "</td>";
        echo "<td>";
        echo "<a href='view_duplicate_projects.php?title=" . urlencode($row['DT_TENDT']) . "' target='_blank'>Xem chi tiết</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ Không có đề tài nào trùng tên</p>";
}

// 2. Kiểm tra đề tài có mô tả trùng lặp
echo "<h3>2. Đề tài có mô tả trùng lặp:</h3>";
$duplicate_desc_query = "
    SELECT DT_MOTA, COUNT(*) as count, GROUP_CONCAT(DT_MADT) as project_ids
    FROM de_tai_nghien_cuu 
    WHERE LENGTH(DT_MOTA) > 50  -- Chỉ kiểm tra mô tả có độ dài > 50 ký tự
    GROUP BY DT_MOTA 
    HAVING COUNT(*) > 1
    ORDER BY count DESC
    LIMIT 10
";

$duplicate_desc_result = $conn->query($duplicate_desc_query);

if ($duplicate_desc_result && $duplicate_desc_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Mô tả đề tài</th>";
    echo "<th>Số lượng</th>";
    echo "<th>Mã đề tài</th>";
    echo "</tr>";
    
    while ($row = $duplicate_desc_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars(substr($row['DT_MOTA'], 0, 100)) . "...</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $row['project_ids'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ Không có đề tài nào trùng mô tả</p>";
}

// 3. Kiểm tra sinh viên đăng ký nhiều đề tài (Thông tin tham khảo)
echo "<h3>3. Sinh viên tham gia nhiều đề tài (Thông tin tham khảo):</h3>";
echo "<p style='color: #6c757d; font-style: italic;'>Lưu ý: Sinh viên có thể tham gia nhiều đề tài khác nhau, đây là điều bình thường.</p>";

$multiple_projects_query = "
    SELECT sv.SV_MASV, CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as student_name,
           COUNT(ct.DT_MADT) as project_count,
           GROUP_CONCAT(ct.DT_MADT) as project_ids
    FROM sinh_vien sv
    JOIN chi_tiet_tham_gia ct ON sv.SV_MASV = ct.SV_MASV
    GROUP BY sv.SV_MASV
    HAVING COUNT(ct.DT_MADT) > 1
    ORDER BY project_count DESC
    LIMIT 10
";

$multiple_result = $conn->query($multiple_projects_query);

if ($multiple_result && $multiple_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>MSSV</th>";
    echo "<th>Họ tên</th>";
    echo "<th>Số đề tài</th>";
    echo "<th>Mã đề tài</th>";
    echo "<th>Ghi chú</th>";
    echo "</tr>";
    
    while ($row = $multiple_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['SV_MASV'] . "</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
        echo "<td>" . $row['project_count'] . "</td>";
        echo "<td>" . $row['project_ids'] . "</td>";
        echo "<td style='color: #28a745;'>✅ Bình thường</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: #28a745;'>✅ Có " . $multiple_result->num_rows . " sinh viên tham gia nhiều đề tài - Điều này hoàn toàn bình thường</p>";
} else {
    echo "<p style='color: green;'>✅ Không có sinh viên nào tham gia nhiều đề tài</p>";
}

// 4. Đề xuất giải pháp
echo "<h3>4. Đề xuất giải pháp xử lý trùng lặp:</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>🔧 Giải pháp kỹ thuật:</h4>";
echo "<ul>";
echo "<li><strong>Thêm ràng buộc UNIQUE:</strong> Thêm UNIQUE KEY cho DT_TENDT để ngăn trùng tên</li>";
echo "<li><strong>Kiểm tra trước khi insert:</strong> Thêm logic kiểm tra trong register_project_process.php</li>";
echo "<li><strong>So sánh nội dung:</strong> Sử dụng thuật toán so sánh văn bản để phát hiện trùng lặp</li>";
echo "<li><strong>Gợi ý tên đề tài:</strong> Đề xuất tên đề tài tương tự nếu đã tồn tại</li>";
echo "</ul>";

echo "<h4>📋 Các bước thực hiện:</h4>";
echo "<ol>";
echo "<li>Thêm validation trong form đăng ký</li>";
echo "<li>Cập nhật logic xử lý trong register_project_process.php</li>";
echo "<li>Thêm thông báo cảnh báo cho người dùng</li>";
echo "<li>Tạo trang quản lý đề tài trùng lặp</li>";
echo "</ol>";
echo "</div>";

// 5. Tạo script sửa lỗi
echo "<h3>5. Script sửa lỗi:</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>📝 Code cần thêm vào register_project_process.php:</h4>";
echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "// Kiểm tra đề tài trùng lặp
function checkDuplicateProject(\$conn, \$project_title, \$project_description) {
    // Kiểm tra tên đề tài trùng lặp
    \$title_query = \"SELECT DT_MADT, DT_TENDT FROM de_tai_nghien_cuu WHERE DT_TENDT = ?\";
    \$title_stmt = \$conn->prepare(\$title_query);
    \$title_stmt->bind_param(\"s\", \$project_title);
    \$title_stmt->execute();
    \$title_result = \$title_stmt->get_result();
    
    if (\$title_result->num_rows > 0) {
        return [
            'duplicate' => true,
            'type' => 'title',
            'message' => 'Đã tồn tại đề tài với tên này. Vui lòng đặt tên khác hoặc kiểm tra lại.'
        ];
    }
    
    // Kiểm tra mô tả trùng lặp (nếu mô tả dài hơn 100 ký tự)
    if (strlen(\$project_description) > 100) {
        \$desc_query = \"SELECT DT_MADT, DT_TENDT FROM de_tai_nghien_cuu WHERE DT_MOTA = ?\";
        \$desc_stmt = \$conn->prepare(\$desc_query);
        \$desc_stmt->bind_param(\"s\", \$project_description);
        \$desc_stmt->execute();
        \$desc_result = \$desc_stmt->get_result();
        
        if (\$desc_result->num_rows > 0) {
            return [
                'duplicate' => true,
                'type' => 'description',
                'message' => 'Đã tồn tại đề tài với mô tả tương tự. Vui lòng kiểm tra lại.'
            ];
        }
    }
    
    return ['duplicate' => false];
}

// Sử dụng trong quá trình xử lý
\$duplicate_check = checkDuplicateProject(\$conn, \$project_title, \$project_description);
if (\$duplicate_check['duplicate']) {
    throw new Exception(\$duplicate_check['message']);
}
";
echo "</pre>";
echo "</div>";

echo "<h3>6. Thêm ràng buộc database:</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>🗄️ SQL để thêm ràng buộc:</h4>";
echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "-- Thêm UNIQUE constraint cho tên đề tài
ALTER TABLE de_tai_nghien_cuu ADD UNIQUE KEY unique_project_title (DT_TENDT);

-- Thêm index cho tìm kiếm nhanh
CREATE INDEX idx_project_title ON de_tai_nghien_cuu(DT_TENDT);
CREATE INDEX idx_project_description ON de_tai_nghien_cuu(DT_MOTA(100));
";
echo "</pre>";
echo "</div>";

$conn->close();
?>
