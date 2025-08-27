<?php
// Test cuối cùng hệ thống thống kê CVHT
include '../../include/connect.php';

echo "<h2>Test Cuối Cùng - Hệ thống Thống kê CVHT</h2>";

$lop_ma = 'DI2195A2';

// 1. Kiểm tra dữ liệu cơ bản
echo "<h3>1. Kiểm tra dữ liệu cơ bản:</h3>";

$result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = '$lop_ma'");
$total_sv = $result->fetch_assoc()['total'];
echo "<p>✓ Tổng sinh viên lớp $lop_ma: <strong>$total_sv</strong></p>";

$result = $conn->query("SELECT COUNT(*) as total FROM advisor_class WHERE LOP_MA = '$lop_ma' AND AC_COHIEULUC = 1");
$total_cvht = $result->fetch_assoc()['total'];
echo "<p>✓ CVHT hiệu lực: <strong>$total_cvht</strong></p>";

// 2. Test API trực tiếp
echo "<h3>2. Test API trực tiếp:</h3>";

$api_url = "get_advisor_statistics_simple_v2.php?lop_ma=" . urlencode($lop_ma);
echo "<p>API URL: <code>$api_url</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: <strong>$http_code</strong></p>";
if ($curl_error) {
    echo "<p>✗ CURL Error: $curl_error</p>";
}

echo "<p>Response:</p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($response) . "</pre>";

// 3. Parse và hiển thị kết quả
echo "<h3>3. Kết quả thống kê:</h3>";

$data = json_decode($response, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px;'>";
    echo "<h4>✅ Thống kê thành công:</h4>";
    echo "<ul>";
    echo "<li><strong>Tổng sinh viên:</strong> " . $data['statistics']['total_students'] . "</li>";
    echo "<li><strong>Sinh viên có đề tài:</strong> " . $data['statistics']['students_with_projects'] . "</li>";
    echo "<li><strong>Đề tài hoàn thành:</strong> " . $data['statistics']['completed_projects'] . "</li>";
    echo "<li><strong>Đề tài đang thực hiện:</strong> " . $data['statistics']['ongoing_projects'] . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // Hiển thị debug info
    if (isset($data['debug'])) {
        echo "<h4>Debug Info:</h4>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . json_encode($data['debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px;'>";
    echo "<h4>❌ Lỗi thống kê:</h4>";
    if (isset($data['message'])) {
        echo "<p><strong>Lỗi:</strong> " . $data['message'] . "</p>";
    }
    if (isset($data['debug'])) {
        echo "<p><strong>Debug:</strong> " . json_encode($data['debug']) . "</p>";
    }
    echo "</div>";
}

// 4. Test JavaScript fetch
echo "<h3>4. Test JavaScript Fetch:</h3>";
echo "<div id='js-test-result'>Đang test...</div>";

echo "<script>
async function testJSFetch() {
    try {
        const response = await fetch('get_advisor_statistics_simple_v2.php?lop_ma=DI2195A2');
        const data = await response.json();
        
        const resultDiv = document.getElementById('js-test-result');
        if (data.success) {
            resultDiv.innerHTML = '<div style=\"background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px;\">' +
                '<h4>✅ JavaScript Fetch thành công:</h4>' +
                '<ul>' +
                '<li><strong>Tổng sinh viên:</strong> ' + data.statistics.total_students + '</li>' +
                '<li><strong>Sinh viên có đề tài:</strong> ' + data.statistics.students_with_projects + '</li>' +
                '<li><strong>Đề tài hoàn thành:</strong> ' + data.statistics.completed_projects + '</li>' +
                '<li><strong>Đề tài đang thực hiện:</strong> ' + data.statistics.ongoing_projects + '</li>' +
                '</ul></div>';
        } else {
            resultDiv.innerHTML = '<div style=\"background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px;\">' +
                '<h4>❌ JavaScript Fetch lỗi:</h4>' +
                '<p>' + data.message + '</p></div>';
        }
    } catch (error) {
        document.getElementById('js-test-result').innerHTML = '<div style=\"background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px;\">' +
            '<h4>❌ JavaScript Fetch lỗi:</h4>' +
            '<p>' + error.message + '</p></div>';
    }
}

testJSFetch();
</script>";

// 5. Link test
echo "<h3>5. Link test:</h3>";
echo "<p><a href='manage_advisor.php' class='btn btn-primary'>Quay lại Quản lý CVHT</a></p>";
echo "<p><a href='get_advisor_statistics_simple_v2.php?lop_ma=DI2195A2' target='_blank' class='btn btn-info'>Test API trực tiếp</a></p>";
?>
