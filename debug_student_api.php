<?php
echo "=== KIỂM TRA HỆ THỐNG TÌM KIẾM SINH VIÊN ===" . PHP_EOL;

// 1. Kiểm tra file get_student_info.php
echo "1. Kiểm tra file get_student_info.php..." . PHP_EOL;
if (file_exists('get_student_info.php')) {
    echo "   ✓ File tồn tại" . PHP_EOL;
    
    // Kiểm tra syntax
    $output = [];
    $return_var = 0;
    exec('php -l get_student_info.php 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "   ✓ Syntax hợp lệ" . PHP_EOL;
    } else {
        echo "   ✗ Lỗi syntax: " . implode("\n", $output) . PHP_EOL;
    }
} else {
    echo "   ✗ File KHÔNG tồn tại" . PHP_EOL;
}

// 2. Kiểm tra kết nối database
echo "\n2. Kiểm tra kết nối database..." . PHP_EOL;
try {
    require_once 'include/database.php';
    $conn = connectDB();
    echo "   ✓ Kết nối database thành công" . PHP_EOL;
} catch (Exception $e) {
    echo "   ✗ Lỗi kết nối database: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 3. Kiểm tra bảng sinh_vien
echo "\n3. Kiểm tra bảng sinh_vien..." . PHP_EOL;
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM sinh_vien");
    $count = $result->fetch_assoc()['count'];
    echo "   ✓ Bảng sinh_vien có $count bản ghi" . PHP_EOL;
} catch (Exception $e) {
    echo "   ✗ Lỗi truy vấn bảng sinh_vien: " . $e->getMessage() . PHP_EOL;
}

// 4. Test API trực tiếp
echo "\n4. Test API get_student_info.php..." . PHP_EOL;

// Start session để tránh lỗi session
session_start();
$_SESSION['user_id'] = 'TEST_USER'; // Fake session để test

// Simulate GET request
$_GET['student_id'] = 'B2110051';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();
include 'get_student_info.php';
$api_output = ob_get_clean();

echo "   API Response: " . $api_output . PHP_EOL;

// Try to decode JSON
$json_response = json_decode($api_output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "   ✓ JSON hợp lệ" . PHP_EOL;
    if (isset($json_response['success'])) {
        if ($json_response['success']) {
            echo "   ✓ API trả về thành công" . PHP_EOL;
        } else {
            echo "   ✗ API trả về lỗi: " . $json_response['message'] . PHP_EOL;
        }
    }
} else {
    echo "   ✗ JSON không hợp lệ: " . json_last_error_msg() . PHP_EOL;
}

// 5. Kiểm tra quyền file
echo "\n5. Kiểm tra quyền truy cập file..." . PHP_EOL;
if (is_readable('get_student_info.php')) {
    echo "   ✓ File có thể đọc" . PHP_EOL;
} else {
    echo "   ✗ File KHÔNG thể đọc" . PHP_EOL;
}

// 6. Kiểm tra Apache/web server
echo "\n6. Kiểm tra URL..." . PHP_EOL;
$url = 'http://localhost/NLNganh/get_student_info.php?student_id=B2110051';
echo "   Test URL: $url" . PHP_EOL;

// Sử dụng cURL để test
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "   HTTP Code: $http_code" . PHP_EOL;
    if ($error) {
        echo "   ✗ cURL Error: $error" . PHP_EOL;
    } else {
        echo "   ✓ cURL thành công" . PHP_EOL;
        echo "   Response: " . substr($response, 0, 200) . "..." . PHP_EOL;
    }
} else {
    echo "   ⚠ cURL không có sẵn" . PHP_EOL;
}

echo "\n=== KẾT THÚC KIỂM TRA ===" . PHP_EOL;
?>
