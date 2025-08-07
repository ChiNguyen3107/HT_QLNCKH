<?php
include 'include/connect.php';

echo "=== KIỂM TRA VẤN ĐỀ TAB THEO TRẠNG THÁI ĐỀ TÀI ===\n\n";

// 1. Kiểm tra đề tài theo trạng thái
echo "1. Phân tích đề tài theo trạng thái:\n";
$result = $conn->query("
    SELECT 
        DT_TRANGTHAI, 
        COUNT(*) as count,
        GROUP_CONCAT(DT_MADT SEPARATOR ', ') as project_ids
    FROM de_tai_nghien_cuu 
    GROUP BY DT_TRANGTHAI
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- Trạng thái: {$row['DT_TRANGTHAI']} | Số lượng: {$row['count']}\n";
        echo "  Projects: " . substr($row['project_ids'], 0, 100) . "...\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n2. Kiểm tra đề tài 'Đang thực hiện' có vấn đề:\n";
$result = $conn->query("
    SELECT 
        dt.DT_MADT,
        dt.DT_TENDT,
        dt.DT_TRANGTHAI,
        dt.QD_SO,
        q.HD_THANHVIEN,
        LENGTH(q.HD_THANHVIEN) as hd_length,
        CASE WHEN q.HD_THANHVIEN LIKE '%\n%' THEN 'YES' ELSE 'NO' END as has_newlines
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE dt.DT_TRANGTHAI = 'Đang thực hiện'
    ORDER BY dt.DT_MADT
    LIMIT 10
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Project: {$row['DT_MADT']} | Status: {$row['DT_TRANGTHAI']}\n";
        echo "  QD_SO: {$row['QD_SO']}\n";
        echo "  HD_THANHVIEN Length: {$row['hd_length']}\n";
        echo "  Has Newlines: {$row['has_newlines']}\n";
        if ($row['has_newlines'] === 'YES') {
            echo "  ⚠️ POTENTIAL ISSUE: Contains newlines\n";
            echo "  Preview: " . str_replace("\n", "[NL]", substr($row['HD_THANHVIEN'], 0, 100)) . "...\n";
        }
        echo "---\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n3. Kiểm tra đề tài 'Đã hoàn thành' hoạt động tốt:\n";
$result = $conn->query("
    SELECT 
        dt.DT_MADT,
        dt.DT_TENDT,
        dt.DT_TRANGTHAI,
        dt.QD_SO,
        q.HD_THANHVIEN,
        LENGTH(q.HD_THANHVIEN) as hd_length,
        CASE WHEN q.HD_THANHVIEN LIKE '%\n%' THEN 'YES' ELSE 'NO' END as has_newlines
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE dt.DT_TRANGTHAI = 'Đã hoàn thành'
    ORDER BY dt.DT_MADT
    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Project: {$row['DT_MADT']} | Status: {$row['DT_TRANGTHAI']}\n";
        echo "  QD_SO: {$row['QD_SO']}\n";
        echo "  HD_THANHVIEN Length: {$row['hd_length']}\n";
        echo "  Has Newlines: {$row['has_newlines']}\n";
        if ($row['has_newlines'] === 'YES') {
            echo "  ⚠️ Contains newlines but works\n";
        } else {
            echo "  ✅ Clean data\n";
        }
        echo "---\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n4. So sánh HTML output giữa 2 trạng thái:\n";

// Test với đề tài đang thực hiện
$result1 = $conn->query("
    SELECT dt.DT_MADT, q.HD_THANHVIEN
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE dt.DT_TRANGTHAI = 'Đang thực hiện' 
    AND q.HD_THANHVIEN IS NOT NULL
    LIMIT 1
");

// Test với đề tài đã hoàn thành
$result2 = $conn->query("
    SELECT dt.DT_MADT, q.HD_THANHVIEN
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE dt.DT_TRANGTHAI = 'Đã hoàn thành' 
    AND q.HD_THANHVIEN IS NOT NULL
    LIMIT 1
");

if ($result1 && $result1->num_rows > 0) {
    $ongoing = $result1->fetch_assoc();
    echo "ĐANG THỰC HIỆN - Project: {$ongoing['DT_MADT']}\n";
    echo "  Raw data: " . str_replace("\n", "[NL]", substr($ongoing['HD_THANHVIEN'], 0, 100)) . "\n";
    
    // Simulate HTML generation
    $html_old = '<input type="hidden" value="' . htmlspecialchars($ongoing['HD_THANHVIEN']) . '">';
    $html_new = '<input type="hidden" value="' . htmlspecialchars(str_replace(array("\r", "\n"), ' ', $ongoing['HD_THANHVIEN'])) . '">';
    
    $lines_old = count(explode("\n", $html_old));
    $lines_new = count(explode("\n", $html_new));
    
    echo "  HTML lines (old): $lines_old\n";
    echo "  HTML lines (new): $lines_new\n";
    echo "  Status: " . ($lines_old > 1 ? "❌ BREAKS TABS" : "✅ OK") . "\n";
}

if ($result2 && $result2->num_rows > 0) {
    $completed = $result2->fetch_assoc();
    echo "\nĐÃ HOÀN THÀNH - Project: {$completed['DT_MADT']}\n";
    echo "  Raw data: " . str_replace("\n", "[NL]", substr($completed['HD_THANHVIEN'], 0, 100)) . "\n";
    
    // Simulate HTML generation
    $html_old = '<input type="hidden" value="' . htmlspecialchars($completed['HD_THANHVIEN']) . '">';
    $html_new = '<input type="hidden" value="' . htmlspecialchars(str_replace(array("\r", "\n"), ' ', $completed['HD_THANHVIEN'])) . '">';
    
    $lines_old = count(explode("\n", $html_old));
    $lines_new = count(explode("\n", $html_new));
    
    echo "  HTML lines (old): $lines_old\n";
    echo "  HTML lines (new): $lines_new\n";
    echo "  Status: " . ($lines_old > 1 ? "❌ BREAKS TABS" : "✅ OK") . "\n";
}

echo "\n=== KẾT THÚC KIỂM TRA ===\n";
?>
