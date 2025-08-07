<?php
// Kiểm tra chi tiết mối quan hệ giữa quyết định và biên bản
include 'include/connect.php';

echo "<h2>Phân tích mối quan hệ giữa quyết định và biên bản</h2>";

// 1. Kiểm tra số lượng bản ghi
echo "<h3>1. Số lượng bản ghi:</h3>";
$count_qd = $conn->query("SELECT COUNT(*) as count FROM quyet_dinh_nghiem_thu")->fetch_assoc()['count'];
$count_bb = $conn->query("SELECT COUNT(*) as count FROM bien_ban")->fetch_assoc()['count'];

echo "<p>Số quyết định nghiệm thu: $count_qd</p>";
echo "<p>Số biên bản: $count_bb</p>";

// 2. Kiểm tra dữ liệu trong quyet_dinh_nghiem_thu
echo "<h3>2. Chi tiết quyết định nghiệm thu:</h3>";
$sql_detail = "SELECT QD_SO, BB_SOBB, QD_NGAY, QD_NOIDUNG FROM quyet_dinh_nghiem_thu";
$result_detail = $conn->query($sql_detail);
if ($result_detail) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>QD_SO</th><th>BB_SOBB (trong QD)</th><th>QD_NGAY</th><th>QD_NOIDUNG</th></tr>";
    while ($row = $result_detail->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['QD_SO']) . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_SOBB']) . "</td>";
        echo "<td>" . htmlspecialchars($row['QD_NGAY']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['QD_NOIDUNG'] ?? '', 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. So sánh BB_SOBB giữa 2 bảng
echo "<h3>3. So sánh BB_SOBB giữa quyết định và biên bản:</h3>";
$sql_compare = "
SELECT 
    qd.QD_SO, 
    qd.BB_SOBB as 'BB_trong_QD', 
    bb.BB_SOBB as 'BB_trong_BienBan',
    bb.BB_NGAYNGHIEMTHU,
    bb.BB_XEPLOAI
FROM quyet_dinh_nghiem_thu qd 
LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
";
$result_compare = $conn->query($sql_compare);
if ($result_compare) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>QD_SO</th><th>BB trong QD</th><th>BB trong BiênBan</th><th>Ngày nghiệm thu</th><th>Xếp loại</th></tr>";
    while ($row = $result_compare->fetch_assoc()) {
        $match = ($row['BB_trong_QD'] == $row['BB_trong_BienBan']) ? "✓" : "✗";
        $style = ($row['BB_trong_QD'] == $row['BB_trong_BienBan']) ? "background:lightgreen;" : "background:lightcoral;";
        echo "<tr style='$style'>";
        echo "<td>" . htmlspecialchars($row['QD_SO']) . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_trong_QD']) . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_trong_BienBan'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_NGAYNGHIEMTHU'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_XEPLOAI'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Tìm biên bản không có trong quyết định
echo "<h3>4. Biên bản không khớp với quyết định:</h3>";
$sql_orphan = "
SELECT bb.BB_SOBB, bb.QD_SO, bb.BB_XEPLOAI 
FROM bien_ban bb 
LEFT JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO AND bb.BB_SOBB = qd.BB_SOBB
WHERE qd.QD_SO IS NULL
";
$result_orphan = $conn->query($sql_orphan);
if ($result_orphan && $result_orphan->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>BB_SOBB</th><th>QD_SO</th><th>BB_XEPLOAI</th></tr>";
    while ($row = $result_orphan->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['BB_SOBB']) . "</td>";
        echo "<td>" . htmlspecialchars($row['QD_SO']) . "</td>";
        echo "<td>" . htmlspecialchars($row['BB_XEPLOAI']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tất cả biên bản đều khớp với quyết định</p>";
}

// 5. Test thử update một bản ghi cụ thể
echo "<h3>5. Test cập nhật cụ thể:</h3>";
$test_qd = '123ab';  // Sử dụng QD_SO có sẵn
echo "<p>Test với QD_SO: $test_qd</p>";

// Lấy thông tin hiện tại
$current_sql = "SELECT bb.*, qd.BB_SOBB as qd_bb_sobb FROM bien_ban bb JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO WHERE bb.QD_SO = ?";
$current_stmt = $conn->prepare($current_sql);
$current_stmt->bind_param("s", $test_qd);
$current_stmt->execute();
$current_result = $current_stmt->get_result();

if ($current_result && $current_result->num_rows > 0) {
    $current_data = $current_result->fetch_assoc();
    echo "<p>Dữ liệu hiện tại:</p>";
    echo "<ul>";
    echo "<li>BB_SOBB trong biên bản: " . $current_data['BB_SOBB'] . "</li>";
    echo "<li>BB_SOBB trong quyết định: " . $current_data['qd_bb_sobb'] . "</li>";
    echo "<li>QD_SO: " . $current_data['QD_SO'] . "</li>";
    echo "<li>Ngày nghiệm thu hiện tại: " . $current_data['BB_NGAYNGHIEMTHU'] . "</li>";
    echo "<li>Xếp loại hiện tại: " . $current_data['BB_XEPLOAI'] . "</li>";
    echo "</ul>";
    
    // Test update
    try {
        $new_date = '2024-12-15';
        $new_grade = 'Khá';
        $new_score = 85.5;
        
        $update_sql = "UPDATE bien_ban SET BB_NGAYNGHIEMTHU = ?, BB_XEPLOAI = ?, BB_TONGDIEM = ? WHERE QD_SO = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssds", $new_date, $new_grade, $new_score, $test_qd);
        
        if ($update_stmt->execute()) {
            echo "<p style='color:green;'>✓ Test update thành công!</p>";
            echo "<p>Đã cập nhật: Ngày = $new_date, Xếp loại = $new_grade, Điểm = $new_score</p>";
            
            // Rollback để không thay đổi dữ liệu thật
            $rollback_sql = "UPDATE bien_ban SET BB_NGAYNGHIEMTHU = ?, BB_XEPLOAI = ?, BB_TONGDIEM = ? WHERE QD_SO = ?";
            $rollback_stmt = $conn->prepare($rollback_sql);
            $rollback_stmt->bind_param("ssds", $current_data['BB_NGAYNGHIEMTHU'], $current_data['BB_XEPLOAI'], $current_data['BB_TONGDIEM'], $test_qd);
            $rollback_stmt->execute();
            echo "<p style='color:blue;'>Đã rollback về dữ liệu ban đầu</p>";
        } else {
            echo "<p style='color:red;'>✗ Test update failed: " . $update_stmt->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Test error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>Không tìm thấy dữ liệu cho QD_SO: $test_qd</p>";
}

// 6. Kiểm tra xem có constraint nào khác không
echo "<h3>6. Tất cả constraints trong database:</h3>";
$constraint_sql = "
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'ql_nckh' 
AND (TABLE_NAME = 'bien_ban' OR REFERENCED_TABLE_NAME = 'bien_ban')
AND REFERENCED_TABLE_NAME IS NOT NULL
";
$constraint_result = $conn->query($constraint_sql);
if ($constraint_result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Constraint</th><th>Table</th><th>Column</th><th>References Table</th><th>References Column</th></tr>";
    while ($row = $constraint_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['CONSTRAINT_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['TABLE_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['REFERENCED_TABLE_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($row['REFERENCED_COLUMN_NAME']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
