<?php
include 'include/connect.php';

echo "=== KIỂM TRA DEEP DEBUG VỚI DTOO000003 ===\n\n";

// 1. Kiểm tra dữ liệu thực trong database
echo "1. Kiểm tra dữ liệu raw từ database:\n";
$result = $conn->query("
    SELECT 
        dt.DT_MADT,
        dt.DT_TRANGTHAI,
        q.QD_SO,
        q.HD_THANHVIEN,
        LENGTH(q.HD_THANHVIEN) as len,
        q.HD_THANHVIEN LIKE '%\\n%' as has_newlines
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE dt.DT_MADT = 'DT0000003'
");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo "Project: {$data['DT_MADT']}\n";
    echo "Status: {$data['DT_TRANGTHAI']}\n";
    echo "QD_SO: {$data['QD_SO']}\n";
    echo "HD_THANHVIEN length: {$data['len']}\n";
    echo "Has newlines: " . ($data['has_newlines'] ? 'YES' : 'NO') . "\n";
    echo "Raw data: " . str_replace("\n", "\\n", substr($data['HD_THANHVIEN'], 0, 200)) . "...\n\n";
    
    // 2. Test HTML output với fix
    echo "2. Test HTML output (AFTER FIX):\n";
    $fixed_value = htmlspecialchars(str_replace(array("\r", "\n"), ' ', $data['HD_THANHVIEN'] ?? ''));
    echo "Fixed value: " . substr($fixed_value, 0, 200) . "...\n";
    echo "Fixed length: " . strlen($fixed_value) . "\n";
    echo "Has newlines after fix: " . (strpos($fixed_value, "\n") !== false ? 'YES' : 'NO') . "\n\n";
    
    // 3. Test JavaScript-safe output
    echo "3. Test JavaScript safety:\n";
    $js_safe = json_encode(str_replace(array("\r", "\n"), ' ', $data['HD_THANHVIEN'] ?? ''));
    echo "JS-safe JSON: " . substr($js_safe, 0, 200) . "...\n\n";
    
} else {
    echo "❌ Không tìm thấy dữ liệu\n";
}

// 4. Kiểm tra các project khác
echo "4. So sánh với project hoạt động tốt:\n";
$compare_result = $conn->query("
    SELECT 
        dt.DT_MADT,
        dt.DT_TRANGTHAI,
        q.QD_SO,
        LENGTH(q.HD_THANHVIEN) as len,
        q.HD_THANHVIEN LIKE '%\\n%' as has_newlines
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE dt.DT_MADT IN ('DT0000002', 'DT0000001')
    ORDER BY dt.DT_MADT
");

if ($compare_result && $compare_result->num_rows > 0) {
    while ($row = $compare_result->fetch_assoc()) {
        echo "Project: {$row['DT_MADT']} ({$row['DT_TRANGTHAI']})\n";
        echo "  Length: {$row['len']}, Has newlines: " . ($row['has_newlines'] ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "Không tìm thấy dữ liệu so sánh\n";
}

echo "\n=== TỔNG KẾT ===\n";
echo "Nếu fix đã được apply nhưng vẫn lỗi, có thể:\n";
echo "1. Browser cache vẫn load version cũ\n";
echo "2. Có lỗi JavaScript khác không liên quan\n";
echo "3. Bootstrap/jQuery version conflict\n";
echo "4. CSS conflict making tabs invisible\n";
?>
