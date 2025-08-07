<?php
session_start();
require_once 'include/database.php';
require_once 'check_project_completion.php';

// Test data
$test_project_id = 'DT001'; // Thay bằng ID đề tài thực tế

echo "<h2>Test Hệ thống Hoàn thành Tự động</h2>";
echo "<hr>";

// 1. Kiểm tra trạng thái hiện tại
echo "<h3>1. Trạng thái hiện tại của đề tài $test_project_id:</h3>";
$current_status = checkProjectCompletionRequirements($conn, $test_project_id);

echo "<pre>";
print_r($current_status);
echo "</pre>";

echo "<hr>";

// 2. Kiểm tra chi tiết từng yêu cầu
echo "<h3>2. Chi tiết từng yêu cầu:</h3>";

// Kiểm tra quyết định
$sql_decision = "SELECT COUNT(*) as count FROM quyet_dinh_nghiem_thu WHERE DT_MADT = ?";
$stmt = $conn->prepare($sql_decision);
$stmt->bind_param("s", $test_project_id);
$stmt->execute();
$decision_count = $stmt->get_result()->fetch_assoc()['count'];
echo "<p><strong>Quyết định nghiệm thu:</strong> " . ($decision_count > 0 ? "✅ Có ($decision_count)" : "❌ Chưa có") . "</p>";

// Kiểm tra điểm thành viên
$sql_scores = "SELECT COUNT(DISTINCT TV_MA) as count FROM thanh_vien_hoi_dong tvhd 
               JOIN quyet_dinh_nghiem_thu qdnt ON tvhd.QD_SO = qdnt.QD_SO 
               WHERE qdnt.DT_MADT = ? AND tvhd.TV_DIEM IS NOT NULL";
$stmt = $conn->prepare($sql_scores);
$stmt->bind_param("s", $test_project_id);
$stmt->execute();
$scored_members = $stmt->get_result()->fetch_assoc()['count'];

$sql_total_members = "SELECT COUNT(DISTINCT TV_MA) as count FROM thanh_vien_hoi_dong tvhd 
                      JOIN quyet_dinh_nghiem_thu qdnt ON tvhd.QD_SO = qdnt.QD_SO 
                      WHERE qdnt.DT_MADT = ?";
$stmt = $conn->prepare($sql_total_members);
$stmt->bind_param("s", $test_project_id);
$stmt->execute();
$total_members = $stmt->get_result()->fetch_assoc()['count'];

echo "<p><strong>Điểm thành viên:</strong> " . ($scored_members == $total_members && $total_members > 0 ? "✅" : "❌") . " $scored_members/$total_members thành viên đã có điểm</p>";

// Kiểm tra file đánh giá
$sql_files = "SELECT COUNT(DISTINCT member_id) as count FROM member_evaluation_files mef 
              JOIN quyet_dinh_nghiem_thu qdnt ON mef.qd_so = qdnt.QD_SO 
              WHERE qdnt.DT_MADT = ?";
$stmt = $conn->prepare($sql_files);
$stmt->bind_param("s", $test_project_id);
$stmt->execute();
$file_members = $stmt->get_result()->fetch_assoc()['count'];

echo "<p><strong>File đánh giá:</strong> " . ($file_members == $total_members && $total_members > 0 ? "✅" : "❌") . " $file_members/$total_members thành viên đã có file</p>";

// Kiểm tra báo cáo tổng kết
$sql_report = "SELECT COUNT(*) as count FROM bao_cao_tong_ket WHERE DT_MADT = ?";
$stmt = $conn->prepare($sql_report);
$stmt->bind_param("s", $test_project_id);
$stmt->execute();
$report_count = $stmt->get_result()->fetch_assoc()['count'];
echo "<p><strong>Báo cáo tổng kết:</strong> " . ($report_count > 0 ? "✅ Có ($report_count)" : "❌ Chưa có") . "</p>";

echo "<hr>";

// 3. Test tự động cập nhật
echo "<h3>3. Test tự động cập nhật trạng thái:</h3>";
$auto_result = autoCheckProjectCompletion($conn, $test_project_id);

echo "<p><strong>Kết quả tự động kiểm tra:</strong></p>";
echo "<pre>";
print_r($auto_result);
echo "</pre>";

echo "<hr>";

// 4. Trạng thái đề tài trong database
echo "<h3>4. Trạng thái đề tài trong database:</h3>";
$sql_project = "SELECT DT_TRANGTHAI FROM de_tai WHERE DT_MADT = ?";
$stmt = $conn->prepare($sql_project);
$stmt->bind_param("s", $test_project_id);
$stmt->execute();
$project_status = $stmt->get_result()->fetch_assoc();

if ($project_status) {
    echo "<p><strong>Trạng thái hiện tại:</strong> " . htmlspecialchars($project_status['DT_TRANGTHAI']) . "</p>";
} else {
    echo "<p><strong>Lỗi:</strong> Không tìm thấy đề tài với ID: $test_project_id</p>";
}

echo "<hr>";

// 5. Danh sách tất cả đề tài để test
echo "<h3>5. Danh sách đề tài có thể test:</h3>";
$sql_all = "SELECT DT_MADT, DT_TENDETAI, DT_TRANGTHAI FROM de_tai ORDER BY DT_MADT LIMIT 10";
$result = $conn->query($sql_all);

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Mã đề tài</th><th>Tên đề tài</th><th>Trạng thái</th><th>Action</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['DT_MADT']) . "</td>";
    echo "<td>" . htmlspecialchars($row['DT_TENDETAI']) . "</td>";
    echo "<td>" . htmlspecialchars($row['DT_TRANGTHAI']) . "</td>";
    echo "<td><a href='?test_id=" . urlencode($row['DT_MADT']) . "'>Test</a></td>";
    echo "</tr>";
}
echo "</table>";

// Nếu có test_id trong URL
if (isset($_GET['test_id'])) {
    $test_id = $_GET['test_id'];
    echo "<script>window.location.href = '?test_project_id=$test_id';</script>";
}

// Nếu có test_project_id trong URL
if (isset($_GET['test_project_id'])) {
    $test_project_id = $_GET['test_project_id'];
    echo "<script>window.location.reload();</script>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; }
th { background-color: #f0f0f0; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
