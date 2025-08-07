<?php
include 'include/connect.php';

echo "=== TEST HTML OUTPUT AFTER FIX ===\n\n";

// Lấy dữ liệu test
$result = $conn->query("
    SELECT dt.DT_MADT, q.QD_SO, q.HD_THANHVIEN
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE q.HD_THANHVIEN IS NOT NULL 
    AND q.HD_THANHVIEN != ''
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $test_data = $result->fetch_assoc();
    $decision = $test_data;
    
    echo "Test data:\n";
    echo "Project: {$test_data['DT_MADT']}\n";
    echo "QD_SO: {$test_data['QD_SO']}\n";
    echo "Raw HD_THANHVIEN: " . substr($test_data['HD_THANHVIEN'], 0, 100) . "...\n\n";
    
    // Test HTML output cũ (sẽ bị lỗi)
    echo "1. HTML output CŨ (có vấn đề):\n";
    $old_html = htmlspecialchars($decision['HD_THANHVIEN'] ?? '');
    echo "Raw: " . substr($old_html, 0, 100) . "...\n";
    echo "Contains newlines: " . (strpos($old_html, "\n") !== false ? "YES" : "NO") . "\n";
    
    // Test HTML output mới (đã fix)
    echo "\n2. HTML output MỚI (đã fix):\n";
    $new_html = htmlspecialchars(str_replace(array("\r", "\n"), ' ', $decision['HD_THANHVIEN'] ?? ''));
    echo "Processed: " . substr($new_html, 0, 100) . "...\n";
    echo "Contains newlines: " . (strpos($new_html, "\n") !== false ? "YES" : "NO") . "\n";
    
    // Test tạo HTML input
    echo "\n3. Test HTML input element:\n";
    
    echo "OLD (problematic):\n";
    $old_input = '<input type="hidden" value="' . htmlspecialchars($decision['HD_THANHVIEN'] ?? '') . '">';
    echo $old_input . "\n";
    
    echo "\nNEW (fixed):\n";
    $new_input = '<input type="hidden" value="' . htmlspecialchars(str_replace(array("\r", "\n"), ' ', $decision['HD_THANHVIEN'] ?? '')) . '">';
    echo $new_input . "\n";
    
    // Validate HTML
    echo "\n4. HTML Validation:\n";
    
    // Check if old version breaks HTML
    $old_lines = explode("\n", $old_input);
    echo "Old input spans " . count($old_lines) . " lines\n";
    
    $new_lines = explode("\n", $new_input);
    echo "New input spans " . count($new_lines) . " lines\n";
    
    if (count($old_lines) > 1) {
        echo "❌ OLD: Multi-line input breaks HTML structure\n";
    } else {
        echo "✅ OLD: Single line (OK)\n";
    }
    
    if (count($new_lines) > 1) {
        echo "❌ NEW: Multi-line input breaks HTML structure\n";
    } else {
        echo "✅ NEW: Single line (OK)\n";
    }
    
} else {
    echo "No test data found\n";
}

echo "\n=== KẾT THÚC TEST ===\n";
?>
