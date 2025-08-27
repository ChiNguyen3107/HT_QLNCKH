<?php
// Test đơn giản API thống kê
include '../../include/connect.php';

echo "<h2>Test API Thống kê - Đơn giản</h2>";

$lop_ma = 'DI2195A2';

// Test 1: Tổng sinh viên
$result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = '$lop_ma'");
$total = $result->fetch_assoc()['total'];
echo "<p><strong>Tổng sinh viên lớp $lop_ma:</strong> $total</p>";

// Test 2: Sinh viên có đề tài
$result = $conn->query("
    SELECT COUNT(DISTINCT sv.SV_MASV) as total 
    FROM sinh_vien sv 
    JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV 
    WHERE sv.LOP_MA = '$lop_ma'
");
$with_projects = $result->fetch_assoc()['total'];
echo "<p><strong>Sinh viên có đề tài:</strong> $with_projects</p>";

// Test 3: Đề tài hoàn thành
$result = $conn->query("
    SELECT COUNT(DISTINCT dt.DT_MADT) as total 
    FROM de_tai_nghien_cuu dt 
    JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
    JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV 
    WHERE sv.LOP_MA = '$lop_ma' AND dt.DT_TRANGTHAI = 'Đã hoàn thành'
");
$completed = $result->fetch_assoc()['total'];
echo "<p><strong>Đề tài hoàn thành:</strong> $completed</p>";

// Test 4: Đề tài đang thực hiện
$result = $conn->query("
    SELECT COUNT(DISTINCT dt.DT_MADT) as total 
    FROM de_tai_nghien_cuu dt 
    JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
    JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV 
    WHERE sv.LOP_MA = '$lop_ma' AND dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Chờ duyệt', 'Đang xử lý')
");
$ongoing = $result->fetch_assoc()['total'];
echo "<p><strong>Đề tài đang thực hiện:</strong> $ongoing</p>";

// Test API endpoint
echo "<h3>Test API Endpoint:</h3>";
$api_content = file_get_contents("get_advisor_statistics_fixed.php?lop_ma=" . urlencode($lop_ma));
echo "<pre>" . htmlspecialchars($api_content) . "</pre>";

// Parse JSON
$data = json_decode($api_content, true);
if ($data) {
    echo "<h4>Kết quả API:</h4>";
    echo "<ul>";
    foreach ($data['statistics'] as $key => $value) {
        echo "<li><strong>$key:</strong> $value</li>";
    }
    echo "</ul>";
} else {
    echo "<p>✗ Không thể parse JSON</p>";
}
?>
