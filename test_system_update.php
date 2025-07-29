<?php
// Test script để kiểm tra các thay đổi mới
echo "=== TEST SYSTEM UPDATE ===\n";

// Test 1: Kiểm tra database schema
echo "\n1. Kiểm tra cấu trúc database:\n";
include 'include/connect.php';

// Kiểm tra bảng bien_ban
echo "- Cấu trúc bảng bien_ban:\n";
$result = $conn->query("DESCRIBE bien_ban");
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . "\n";
}

// Kiểm tra bảng quyet_dinh_nghiem_thu
echo "\n- Cấu trúc bảng quyet_dinh_nghiem_thu:\n";
$result = $conn->query("DESCRIBE quyet_dinh_nghiem_thu");
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . "\n";
}

// Test 2: Kiểm tra file tồn tại
echo "\n2. Kiểm tra files:\n";
$files_to_check = [
    'view/student/update_decision_info.php',
    'view/student/update_report_info.php',
    'view/student/view_project.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✓ $file - EXISTS\n";
    } else {
        echo "✗ $file - MISSING\n";
    }
}

// Test 3: Kiểm tra thư mục uploads
echo "\n3. Kiểm tra thư mục uploads:\n";
$upload_dirs = [
    'uploads/decision_files',
    'uploads/contract_files',
    'uploads/project_files'
];

foreach ($upload_dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "✓ $dir - EXISTS (permissions: $perms)\n";
    } else {
        echo "✗ $dir - MISSING\n";
    }
}

// Test 4: Test SQL queries
echo "\n4. Test SQL queries:\n";

// Test query để lấy thông tin quyết định và biên bản
$test_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM 
             FROM bien_ban bb
             JOIN quyet_dinh_nghiem_thu qd ON bb.BB_SOBB = qd.BB_SOBB
             LIMIT 1";

$stmt = $conn->prepare($test_sql);
if ($stmt) {
    echo "✓ Decision-Report join query - SYNTAX OK\n";
    $stmt->execute();
    $result = $stmt->get_result();
    echo "  Records found: " . $result->num_rows . "\n";
} else {
    echo "✗ Decision-Report join query - SYNTAX ERROR: " . $conn->error . "\n";
}

// Test INSERT query cho biên bản
$test_insert_bb = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM) 
                   VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($test_insert_bb);
if ($stmt) {
    echo "✓ Report INSERT query - SYNTAX OK\n";
} else {
    echo "✗ Report INSERT query - SYNTAX ERROR: " . $conn->error . "\n";
}

// Test UPDATE query cho biên bản
$test_update_bb = "UPDATE bien_ban SET 
                   BB_NGAYNGHIEMTHU = ?, 
                   BB_XEPLOAI = ?,
                   BB_TONGDIEM = ?
                   WHERE QD_SO = ?";
$stmt = $conn->prepare($test_update_bb);
if ($stmt) {
    echo "✓ Report UPDATE query - SYNTAX OK\n";
} else {
    echo "✗ Report UPDATE query - SYNTAX ERROR: " . $conn->error . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
