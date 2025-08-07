<?php
include 'include/connect.php';

echo "=== TEST PROBLEM CASE: QDDT0000003 ===\n\n";

$result = $conn->query("SELECT HD_THANHVIEN FROM quyet_dinh_nghiem_thu WHERE QD_SO = 'QDDT0000003'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $decision = $row;
    
    echo "Raw data:\n";
    echo $decision['HD_THANHVIEN'] . "\n\n";
    
    echo "1. OLD HTML (problematic):\n";
    $old_html = '<input type="hidden" value="' . htmlspecialchars($decision['HD_THANHVIEN'] ?? '') . '">';
    echo $old_html . "\n\n";
    
    echo "2. NEW HTML (fixed):\n";
    $new_html = '<input type="hidden" value="' . htmlspecialchars(str_replace(array("\r", "\n"), ' ', $decision['HD_THANHVIEN'] ?? '')) . '">';
    echo $new_html . "\n\n";
    
    echo "3. Problem Analysis:\n";
    $lines_old = explode("\n", $old_html);
    $lines_new = explode("\n", $new_html);
    
    echo "Old HTML spans " . count($lines_old) . " lines\n";
    echo "New HTML spans " . count($lines_new) . " lines\n";
    
    if (count($lines_old) > 1) {
        echo "❌ OLD: Breaks HTML structure - this will break tabs!\n";
        echo "Browser will see this as multiple HTML elements!\n";
    }
    
    if (count($lines_new) == 1) {
        echo "✅ NEW: Proper single-line HTML\n";
    }
    
} else {
    echo "Data not found\n";
}

echo "\n=== KẾT THÚC ===\n";
?>
