<?php
include 'include/connect.php';

echo "=== KIỂM TRA VẤN ĐỀ TAB SAU KHI CẬP NHẬT THÀNH VIÊN HỘI ĐỒNG ===\n\n";

// 1. Kiểm tra dữ liệu thành viên hội đồng
echo "1. Kiểm tra dữ liệu thành viên hội đồng gần đây:\n";
$result = $conn->query("
    SELECT 
        tv.QD_SO,
        tv.GV_MAGV, 
        tv.TV_HOTEN,
        tv.TV_VAITRO,
        tv.TV_DIEM,
        tv.TV_NGAYDANHGIA,
        q.QD_NGAY,
        b.BB_SOBB
    FROM thanh_vien_hoi_dong tv
    LEFT JOIN quyet_dinh_nghiem_thu q ON tv.QD_SO = q.QD_SO
    LEFT JOIN bien_ban b ON tv.QD_SO = b.QD_SO
    ORDER BY tv.TV_NGAYDANHGIA DESC
    LIMIT 10
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- QD: {$row['QD_SO']} | Member: {$row['GV_MAGV']} | Name: {$row['TV_HOTEN']} | Role: {$row['TV_VAITRO']} | Score: {$row['TV_DIEM']} | Rated: {$row['TV_NGAYDANHGIA']}\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n2. Kiểm tra cấu trúc dữ liệu có vấn đề:\n";

// Kiểm tra JSON data trong HD_THANHVIEN
echo "2a. Kiểm tra HD_THANHVIEN trong quyết định:\n";
$result = $conn->query("
    SELECT 
        QD_SO, 
        LENGTH(HD_THANHVIEN) as json_length,
        SUBSTRING(HD_THANHVIEN, 1, 100) as json_preview
    FROM quyet_dinh_nghiem_thu 
    WHERE HD_THANHVIEN IS NOT NULL 
    AND HD_THANHVIEN != ''
    ORDER BY QD_NGAY DESC
    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- QD: {$row['QD_SO']} | JSON Length: {$row['json_length']} | Preview: {$row['json_preview']}...\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

// Kiểm tra ký tự đặc biệt hoặc encoding
echo "\n2b. Kiểm tra encoding và ký tự đặc biệt:\n";
$result = $conn->query("
    SELECT 
        QD_SO,
        HD_THANHVIEN,
        CHAR_LENGTH(HD_THANHVIEN) as char_length,
        LENGTH(HD_THANHVIEN) as byte_length
    FROM quyet_dinh_nghiem_thu 
    WHERE HD_THANHVIEN IS NOT NULL 
    AND HD_THANHVIEN != ''
    ORDER BY QD_NGAY DESC
    LIMIT 3
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "QD: {$row['QD_SO']}\n";
        echo "  Char Length: {$row['char_length']} | Byte Length: {$row['byte_length']}\n";
        
        // Kiểm tra JSON validity
        $json_data = json_decode($row['HD_THANHVIEN'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "  ✓ Valid JSON\n";
            echo "  Content: " . substr($row['HD_THANHVIEN'], 0, 200) . "...\n";
        } else {
            echo "  ✗ Invalid JSON: " . json_last_error_msg() . "\n";
            echo "  Raw content: " . substr($row['HD_THANHVIEN'], 0, 200) . "...\n";
        }
        echo "\n";
    }
} else {
    echo "Lỗi: " . $conn->error . "\n";
}

echo "\n3. Kiểm tra output HTML có bị corrupt:\n";

// Tạo test case giống như trong view_project.php
$test_project_id = null;
$result = $conn->query("
    SELECT dt.DT_MADT, dt.DT_TRANGTHAI, q.QD_SO, q.HD_THANHVIEN
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE q.HD_THANHVIEN IS NOT NULL 
    AND q.HD_THANHVIEN != ''
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $test_data = $result->fetch_assoc();
    $test_project_id = $test_data['DT_MADT'];
    
    echo "Test với project: {$test_project_id}\n";
    echo "QD_SO: {$test_data['QD_SO']}\n";
    echo "Status: {$test_data['DT_TRANGTHAI']}\n";
    
    // Test HTML output như trong view_project.php
    echo "\nTest HTML output:\n";
    $hd_thanhvien = $test_data['HD_THANHVIEN'];
    
    echo "Raw data length: " . strlen($hd_thanhvien) . "\n";
    echo "First 200 chars: " . substr($hd_thanhvien, 0, 200) . "\n";
    
    // Test htmlspecialchars
    $escaped = htmlspecialchars($hd_thanhvien);
    echo "After htmlspecialchars length: " . strlen($escaped) . "\n";
    
    // Test nl2br
    $with_br = nl2br($escaped);
    echo "After nl2br length: " . strlen($with_br) . "\n";
    
    // Kiểm tra có ký tự lạ
    if (preg_match('/[^\x20-\x7E\x0A\x0D\xC2-\xF4]/', $hd_thanhvien)) {
        echo "⚠️ Contains non-printable or unusual characters\n";
    } else {
        echo "✓ Clean printable characters\n";
    }
}

echo "\n4. Kiểm tra session và output buffering:\n";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✓ Session is active\n";
} else {
    echo "⚠️ Session not active\n";
}

if (ob_get_level() > 0) {
    echo "⚠️ Output buffer level: " . ob_get_level() . "\n";
} else {
    echo "✓ No output buffering\n";
}

echo "\n=== KẾT THÚC KIỂM TRA ===\n";
?>
