<?php
echo "Testing bind_param parameter count...\n";

// Simulate the UPDATE query
$update_query = "
    UPDATE thanh_vien_hoi_dong 
    SET 
        TV_DIEM = ?,
        TV_DANHGIA = ?,
        TV_DIEMCHITIET = ?,
        TV_TRANGTHAI = ?,
        TV_NGAYDANHGIA = NOW()
    WHERE QD_SO = ? AND GV_MAGV = ? AND TC_MATC = ?
";

// Count placeholders
$placeholder_count = substr_count($update_query, '?');
echo "Number of placeholders in query: $placeholder_count\n";

// Our parameters
$params = [
    'total_score' => 'd',
    'overall_comment' => 's', 
    'criteria_scores_json_str' => 's',
    'status' => 's',
    'qd_so' => 's',
    'gv_magv' => 's', 
    'tc_matc' => 's'
];

echo "Number of parameters: " . count($params) . "\n";
echo "Type definition string should be: " . implode('', array_values($params)) . "\n";

if ($placeholder_count === count($params)) {
    echo "✅ Parameter count matches!\n";
} else {
    echo "❌ Parameter count mismatch!\n";
}

echo "\nParameter details:\n";
foreach ($params as $name => $type) {
    echo "- $name ($type)\n";
}
?>
