<?php
include 'include/connect.php';

echo "=== KIỂM TRA DỮ LIỆU TRÙNG LẶP ===\n\n";

echo "1. Kiểm tra quyết định trùng lặp:\n";
$result = $conn->query("
    SELECT QD_SO, COUNT(*) as count, GROUP_CONCAT(BB_SOBB) as bb_codes
    FROM quyet_dinh_nghiem_thu 
    GROUP BY QD_SO 
    HAVING count > 1
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- QD_SO: {$row['QD_SO']} xuất hiện {$row['count']} lần với BB: {$row['bb_codes']}\n";
    }
} else {
    echo "Không có quyết định trùng lặp\n";
}

echo "\n2. Kiểm tra biên bản trùng lặp:\n";
$result = $conn->query("
    SELECT BB_SOBB, COUNT(*) as count, GROUP_CONCAT(QD_SO) as qd_codes
    FROM bien_ban 
    GROUP BY BB_SOBB 
    HAVING count > 1
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- BB_SOBB: {$row['BB_SOBB']} xuất hiện {$row['count']} lần với QD: {$row['qd_codes']}\n";
    }
} else {
    echo "Không có biên bản trùng lặp\n";
}

echo "\n3. Kiểm tra quyết định có nhiều biên bản:\n";
$result = $conn->query("
    SELECT QD_SO, COUNT(*) as count, GROUP_CONCAT(BB_SOBB) as bb_codes
    FROM bien_ban 
    WHERE QD_SO IS NOT NULL 
    GROUP BY QD_SO 
    HAVING count > 1
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- QD_SO: {$row['QD_SO']} có {$row['count']} biên bản: {$row['bb_codes']}\n";
    }
} else {
    echo "Mỗi quyết định chỉ có 1 biên bản\n";
}

echo "\n4. Kiểm tra ràng buộc khóa ngoại:\n";
$result = $conn->query("
    SELECT b.BB_SOBB, b.QD_SO, q.QD_SO as q_exists
    FROM bien_ban b
    LEFT JOIN quyet_dinh_nghiem_thu q ON b.QD_SO = q.QD_SO
    WHERE q.QD_SO IS NULL AND b.QD_SO IS NOT NULL
");
if ($result && $result->num_rows > 0) {
    echo "Biên bản có QD_SO không tồn tại:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- BB: {$row['BB_SOBB']} references QD: {$row['QD_SO']} (not exists)\n";
    }
} else {
    echo "Tất cả biên bản đều có quyết định hợp lệ\n";
}

echo "\n5. Kiểm tra độ dài mã biên bản:\n";
$result = $conn->query("
    SELECT BB_SOBB, LENGTH(BB_SOBB) as length
    FROM bien_ban 
    WHERE LENGTH(BB_SOBB) > 10
");
if ($result && $result->num_rows > 0) {
    echo "Biên bản có mã quá dài (>10 ký tự):\n";
    while ($row = $result->fetch_assoc()) {
        echo "- BB: {$row['BB_SOBB']} (length: {$row['length']})\n";
    }
} else {
    echo "Tất cả mã biên bản đều hợp lệ (≤10 ký tự)\n";
}

echo "\n6. Kiểm tra độ dài mã quyết định:\n";
$result = $conn->query("
    SELECT QD_SO, LENGTH(QD_SO) as length
    FROM quyet_dinh_nghiem_thu 
    WHERE LENGTH(QD_SO) > 11
");
if ($result && $result->num_rows > 0) {
    echo "Quyết định có mã quá dài (>11 ký tự):\n";
    while ($row = $result->fetch_assoc()) {
        echo "- QD: {$row['QD_SO']} (length: {$row['length']})\n";
    }
} else {
    echo "Tất cả mã quyết định đều hợp lệ (≤11 ký tự)\n";
}

echo "\n7. Kiểm tra dữ liệu NULL:\n";
$result = $conn->query("
    SELECT BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI
    FROM bien_ban 
    WHERE BB_NGAYNGHIEMTHU IS NULL 
       OR BB_XEPLOAI IS NULL 
       OR BB_XEPLOAI = ''
       OR BB_SOBB IS NULL
       OR BB_SOBB = ''
");
if ($result && $result->num_rows > 0) {
    echo "Biên bản có dữ liệu NULL/rỗng:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- BB: {$row['BB_SOBB']} | QD: {$row['QD_SO']} | Date: {$row['BB_NGAYNGHIEMTHU']} | Grade: '{$row['BB_XEPLOAI']}'\n";
    }
} else {
    echo "Tất cả biên bản đều có dữ liệu đầy đủ\n";
}

echo "\n=== KẾT THÚC KIỂM TRA ===\n";
?>
