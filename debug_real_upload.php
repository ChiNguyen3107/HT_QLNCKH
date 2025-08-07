<?php
// Debug Upload Error - Thực tế
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG UPLOAD ERROR THỰC TẾ ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Kiểm tra bảng file_dinh_kem chi tiết
require_once 'include/connect.php';

echo "1. Kiểm tra cấu trúc bảng file_dinh_kem:\n";
$result = $conn->query("DESCRIBE file_dinh_kem");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $nullable = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
        $default = $row['Default'] ? ' DEFAULT: ' . $row['Default'] : '';
        echo "   - " . $row['Field'] . " (" . $row['Type'] . ") $nullable" . $default . "\n";
    }
}

echo "\n2. Kiểm tra constraints chi tiết:\n";
$result = $conn->query("
    SELECT 
        kcu.CONSTRAINT_NAME,
        kcu.COLUMN_NAME,
        kcu.REFERENCED_TABLE_NAME,
        kcu.REFERENCED_COLUMN_NAME,
        rc.UPDATE_RULE,
        rc.DELETE_RULE
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
    JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
        ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
    WHERE kcu.TABLE_NAME = 'file_dinh_kem' 
    AND kcu.TABLE_SCHEMA = 'ql_nckh'
    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['CONSTRAINT_NAME'] . ":\n";
        echo "     * " . $row['COLUMN_NAME'] . " → " . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "\n";
        echo "     * UPDATE: " . $row['UPDATE_RULE'] . ", DELETE: " . $row['DELETE_RULE'] . "\n";
    }
}

echo "\n3. Kiểm tra dữ liệu tham chiếu:\n";

// Kiểm tra bien_ban
echo "   a) Bảng bien_ban:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM bien_ban");
if ($result) {
    $row = $result->fetch_assoc();
    echo "      - Tổng số: " . $row['count'] . "\n";
}

$result = $conn->query("SELECT BB_SOBB, BB_XEPLOAI FROM bien_ban LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "      - Mẫu:\n";
    while ($row = $result->fetch_assoc()) {
        echo "        * " . $row['BB_SOBB'] . " (" . $row['BB_XEPLOAI'] . ")\n";
    }
}

// Kiểm tra giang_vien
echo "\n   b) Bảng giang_vien:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM giang_vien");
if ($result) {
    $row = $result->fetch_assoc();
    echo "      - Tổng số: " . $row['count'] . "\n";
}

$result = $conn->query("SELECT GV_MAGV, GV_HOTEN FROM giang_vien WHERE GV_MAGV LIKE 'GV%' LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "      - Mẫu:\n";
    while ($row = $result->fetch_assoc()) {
        echo "        * " . $row['GV_MAGV'] . " (" . $row['GV_HOTEN'] . ")\n";
    }
}

echo "\n4. Test insert với dữ liệu thực tế:\n";

try {
    // Lấy BB_SOBB thực tế
    $bb_result = $conn->query("SELECT BB_SOBB FROM bien_ban LIMIT 1");
    $bb_sobb = null;
    if ($bb_result && $bb_result->num_rows > 0) {
        $bb_row = $bb_result->fetch_assoc();
        $bb_sobb = $bb_row['BB_SOBB'];
        echo "   ✅ BB_SOBB: " . $bb_sobb . "\n";
    } else {
        echo "   ❌ Không có BB_SOBB nào\n";
        throw new Exception("Không có bien_ban nào trong database");
    }
    
    // Lấy GV_MAGV thực tế
    $gv_result = $conn->query("SELECT GV_MAGV FROM giang_vien LIMIT 1");
    $gv_magv = null;
    if ($gv_result && $gv_result->num_rows > 0) {
        $gv_row = $gv_result->fetch_assoc();
        $gv_magv = $gv_row['GV_MAGV'];
        echo "   ✅ GV_MAGV: " . $gv_magv . "\n";
    } else {
        echo "   ⚠️ Không có giang_vien nào, sẽ dùng NULL\n";
        $gv_magv = null;
    }
    
    // Test insert
    $test_id = 'FDGREAL' . mt_rand(1000, 9999);
    echo "   🧪 Test insert với ID: " . $test_id . "\n";
    
    $stmt = $conn->prepare("
        INSERT INTO file_dinh_kem (
            FDG_MA, BB_SOBB, GV_MAGV, FDG_LOAI, 
            FDG_TENFILE, FDG_FILE, FDG_NGAYTAO, 
            FDG_KICHTHUC, FDG_MOTA
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $loai = 'member_evaluation';
    $tenfile = 'Test Upload Real';
    $file = 'test_real.txt';
    $kichthuc = 1024;
    $mota = 'Test với dữ liệu thực tế';
    
    // Bind parameters - check if GV_MAGV is null
    if ($gv_magv === null) {
        $stmt->bind_param("sssssis", $test_id, $bb_sobb, $gv_magv, $loai, $tenfile, $file, $kichthuc, $mota);
    } else {
        $stmt->bind_param("ssssssis", $test_id, $bb_sobb, $gv_magv, $loai, $tenfile, $file, $kichthuc, $mota);
    }
    
    if ($stmt->execute()) {
        echo "   ✅ Insert thành công!\n";
        
        // Verify insert
        $verify = $conn->query("SELECT * FROM file_dinh_kem WHERE FDG_MA = '$test_id'");
        if ($verify && $verify->num_rows > 0) {
            $data = $verify->fetch_assoc();
            echo "   ✅ Verify: " . json_encode($data) . "\n";
        }
        
        // Cleanup
        $conn->query("DELETE FROM file_dinh_kem WHERE FDG_MA = '$test_id'");
        echo "   ✅ Cleaned up test record\n";
        
    } else {
        echo "   ❌ Insert failed: " . $stmt->error . "\n";
        echo "   📋 Error info: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 HƯỚNG DẪN SỬA LỖI:\n";
echo "Dựa trên kết quả trên, tôi sẽ tạo upload handler phù hợp.\n";
?>
