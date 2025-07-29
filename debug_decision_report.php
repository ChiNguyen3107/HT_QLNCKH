<?php
include 'include/connect.php';

echo "=== KIỂM TRA VÀ SỬA LỖI BIÊN BẢN NGHIỆM THU ===\n";

// 1. Kiểm tra cấu trúc các bảng liên quan
echo "\n1. Cấu trúc bảng de_tai_nghien_cuu:\n";
$result = $conn->query("DESCRIBE de_tai_nghien_cuu");
while ($row = $result->fetch_assoc()) {
    if (in_array($row['Field'], ['DT_MADT', 'QD_SO'])) {
        echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']}\n";
    }
}

echo "\n2. Cấu trúc bảng quyet_dinh_nghiem_thu:\n";
$result = $conn->query("DESCRIBE quyet_dinh_nghiem_thu");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']}\n";
}

echo "\n3. Cấu trúc bảng bien_ban:\n";
$result = $conn->query("DESCRIBE bien_ban");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} | NULL: {$row['Null']} | KEY: {$row['Key']}\n";
}

// 2. Kiểm tra mối quan hệ thực tế
echo "\n4. Kiểm tra mối quan hệ giữa các bảng:\n";
$result = $conn->query("SELECT 
    dt.DT_MADT, 
    dt.QD_SO as dt_qd_so,
    qd.QD_SO as qd_real_so, 
    qd.BB_SOBB as qd_bb_ref,
    bb.BB_SOBB as bb_real_so,
    bb.QD_SO as bb_qd_ref
FROM de_tai_nghien_cuu dt 
LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
WHERE dt.DT_MADT IS NOT NULL 
LIMIT 5");

while ($row = $result->fetch_assoc()) {
    echo "Project: {$row['DT_MADT']} -> ";
    echo "DT.QD_SO: " . ($row['dt_qd_so'] ?? 'NULL') . " -> ";
    echo "QD.QD_SO: " . ($row['qd_real_so'] ?? 'NULL') . " -> ";
    echo "QD.BB_SOBB: " . ($row['qd_bb_ref'] ?? 'NULL') . " -> ";
    echo "BB.BB_SOBB: " . ($row['bb_real_so'] ?? 'NULL') . " -> ";
    echo "BB.QD_SO: " . ($row['bb_qd_ref'] ?? 'NULL') . "\n";
}

// 3. Kiểm tra query hiện tại trong view_project.php
echo "\n5. Test query hiện tại:\n";
$test_project_id = 'DT0000001'; // Thay bằng project ID thực tế

// Query hiện tại (có vấn đề)
$current_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM 
                FROM bien_ban bb
                JOIN quyet_dinh_nghiem_thu qd ON bb.BB_SOBB = qd.BB_SOBB
                WHERE bb.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";

echo "Query hiện tại (có vấn đề): \n$current_sql\n";

// Query đúng
$correct_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM 
                FROM quyet_dinh_nghiem_thu qd
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE qd.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";

echo "\nQuery đúng (sửa lỗi): \n$correct_sql\n";

// Test cả 2 query
echo "\n6. Test kết quả:\n";

// Lấy một project có dữ liệu
$result = $conn->query("SELECT DT_MADT FROM de_tai_nghien_cuu WHERE QD_SO IS NOT NULL LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $test_project_id = $row['DT_MADT'];
    echo "Test với project: $test_project_id\n";
    
    // Test query cũ
    $stmt = $conn->prepare($current_sql);
    if ($stmt) {
        $stmt->bind_param("s", $test_project_id);
        $stmt->execute();
        $result1 = $stmt->get_result();
        echo "Query cũ - Kết quả: " . $result1->num_rows . " rows\n";
    } else {
        echo "Query cũ - Lỗi: " . $conn->error . "\n";
    }
    
    // Test query mới
    $stmt = $conn->prepare($correct_sql);
    if ($stmt) {
        $stmt->bind_param("s", $test_project_id);
        $stmt->execute();
        $result2 = $stmt->get_result();
        echo "Query mới - Kết quả: " . $result2->num_rows . " rows\n";
        
        if ($result2->num_rows > 0) {
            $data = $result2->fetch_assoc();
            echo "Dữ liệu mẫu:\n";
            echo "- QD_SO: " . ($data['QD_SO'] ?? 'NULL') . "\n";
            echo "- BB_SOBB: " . ($data['BB_SOBB'] ?? 'NULL') . "\n";
            echo "- BB_XEPLOAI: " . ($data['BB_XEPLOAI'] ?? 'NULL') . "\n";
        }
    } else {
        echo "Query mới - Lỗi: " . $conn->error . "\n";
    }
} else {
    echo "Không tìm thấy project có quyết định để test\n";
}

$conn->close();
?>
