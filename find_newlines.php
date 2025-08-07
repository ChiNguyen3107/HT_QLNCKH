<?php
include 'include/connect.php';

echo "=== TÌM DỮ LIỆU CÓ NEWLINES ===\n\n";

$result = $conn->query("
    SELECT QD_SO, HD_THANHVIEN, LENGTH(HD_THANHVIEN) as length
    FROM quyet_dinh_nghiem_thu 
    WHERE HD_THANHVIEN IS NOT NULL 
    AND HD_THANHVIEN != ''
    ORDER BY LENGTH(HD_THANHVIEN) DESC
    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "QD: {$row['QD_SO']} | Length: {$row['length']}\n";
        echo "Content: " . str_replace("\n", "[NL]", substr($row['HD_THANHVIEN'], 0, 150)) . "\n";
        echo "Has newlines: " . (strpos($row['HD_THANHVIEN'], "\n") !== false ? "YES" : "NO") . "\n";
        echo "---\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n=== KẾT THÚC ===\n";
?>
