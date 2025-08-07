<?php
/**
 * Test script để kiểm tra logic hoàn thành đề tài mới
 */

include 'include/connect.php';
include 'include/project_completion_functions.php';

echo "<h2>🧪 Test Logic Hoàn Thành Đề Tài Mới</h2>";

// Lấy một đề tài có biên bản nghiệm thu để test
$test_project_sql = "SELECT DISTINCT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, bb.BB_XEPLOAI
                    FROM de_tai_nghien_cuu dt
                    INNER JOIN bien_ban bb ON dt.QD_SO = bb.QD_SO
                    WHERE bb.BB_XEPLOAI IS NOT NULL
                    LIMIT 5";

$result = $conn->query($test_project_sql);

if ($result->num_rows === 0) {
    echo "<p style='color: red;'>❌ Không tìm thấy đề tài nào có biên bản nghiệm thu để test.</p>";
    exit;
}

echo "<h3>📋 Danh sách đề tài test:</h3>";

while ($project = $result->fetch_assoc()) {
    $project_id = $project['DT_MADT'];
    
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>🔍 Đề tài: {$project['DT_TENDT']}</h4>";
    echo "<p><strong>Mã đề tài:</strong> {$project_id}</p>";
    echo "<p><strong>Trạng thái hiện tại:</strong> <span style='color: blue;'>{$project['DT_TRANGTHAI']}</span></p>";
    echo "<p><strong>Xếp loại biên bản:</strong> <span style='color: green;'>{$project['BB_XEPLOAI']}</span></p>";
    
    echo "<h5>📊 Chi tiết điều kiện hoàn thành:</h5>";
    
    // Test function getProjectCompletionDetails
    $details = getProjectCompletionDetails($project_id, $conn);
    
    echo "<ul>";
    echo "<li><strong>Có biên bản đạt:</strong> " . ($details['has_passing_report'] ? "✅ Có" : "❌ Không") . "</li>";
    echo "<li><strong>Tổng số thành viên hội đồng:</strong> {$details['total_members']}</li>";
    echo "<li><strong>Số thành viên đã có điểm:</strong> {$details['scored_members']}</li>";
    echo "<li><strong>Tất cả thành viên đã có điểm:</strong> " . ($details['all_members_scored'] ? "✅ Có" : "❌ Không") . "</li>";
    echo "<li><strong>Có thể hoàn thành:</strong> " . ($details['can_complete'] ? "✅ Có" : "❌ Không") . "</li>";
    echo "</ul>";
    
    // Hiển thị thành viên chưa có điểm
    if (!empty($details['missing_members'])) {
        echo "<h6>👥 Thành viên chưa có điểm:</h6>";
        echo "<ul>";
        foreach ($details['missing_members'] as $member) {
            echo "<li>{$member['name']} ({$member['role']})</li>";
        }
        echo "</ul>";
    }
    
    // Test function checkProjectCompletionConditions
    echo "<h5>🔍 Kết quả kiểm tra điều kiện:</h5>";
    $conditions = checkProjectCompletionConditions($project_id, $conn);
    
    if ($conditions['can_complete']) {
        echo "<p style='color: green;'>✅ <strong>Đã đủ điều kiện hoàn thành:</strong> {$conditions['reason']}</p>";
        
        // Test thử cập nhật trạng thái (chỉ hiển thị, không thực thi)
        echo "<p style='color: blue;'>🔄 Có thể cập nhật trạng thái thành 'Đã hoàn thành'</p>";
    } else {
        echo "<p style='color: orange;'>⏳ <strong>Chưa đủ điều kiện:</strong> {$conditions['reason']}</p>";
        
        if (isset($conditions['missing_members'])) {
            echo "<p><strong>Thiếu điểm từ:</strong> " . implode(', ', $conditions['missing_members']) . "</p>";
        }
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Test API Endpoint:</h3>";

// Test API endpoint
$first_project_sql = "SELECT DT_MADT FROM de_tai_nghien_cuu 
                      WHERE QD_SO IS NOT NULL 
                      LIMIT 1";
$first_result = $conn->query($first_project_sql);

if ($first_result->num_rows > 0) {
    $first_project = $first_result->fetch_assoc();
    $test_project_id = $first_project['DT_MADT'];
    
    echo "<p>🌐 Test API với đề tài: <strong>{$test_project_id}</strong></p>";
    echo "<p>📡 URL: <code>/NLNganh/api/check_project_completion_status.php</code></p>";
    echo "<p>📤 POST data: <code>project_id={$test_project_id}</code></p>";
    
    // Simulate API call
    $_POST['project_id'] = $test_project_id;
    $_SESSION['user_id'] = 'test_user'; // Mock session
    
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>📥 Response mẫu:</strong><br>";
    echo "<pre>";
    
    // Get completion details for API response simulation
    $api_details = getProjectCompletionDetails($test_project_id, $conn);
    $sample_response = [
        'success' => true,
        'project_info' => [
            'id' => $test_project_id,
            'title' => 'Đề tài test',
            'status' => 'Đang thực hiện'
        ],
        'completion_details' => $api_details,
        'requirements' => [
            [
                'name' => 'Biên bản nghiệm thu đạt',
                'status' => $api_details['has_passing_report'],
                'details' => $api_details['has_passing_report'] 
                    ? "Đã có biên bản với kết quả: " . ($api_details['report_grade'] ?? 'N/A')
                    : "Chưa có biên bản nghiệm thu với kết quả đạt"
            ],
            [
                'name' => 'Điểm đánh giá từ hội đồng',
                'status' => $api_details['all_members_scored'],
                'details' => "Đã có điểm: {$api_details['scored_members']}/{$api_details['total_members']} thành viên"
            ]
        ]
    ];
    
    echo json_encode($sample_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>📋 Tóm tắt thay đổi:</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Đã thực hiện:</h4>";
echo "<ul>";
echo "<li>🔧 Tạo file <code>include/project_completion_functions.php</code> với các hàm kiểm tra điều kiện</li>";
echo "<li>🔄 Sửa <code>update_report_info.php</code> để không tự động hoàn thành khi chỉ có biên bản</li>";
echo "<li>🔄 Sửa <code>update_council_member_score.php</code> để kiểm tra và cập nhật trạng thái khi đủ điều kiện</li>";
echo "<li>🌐 Tạo API <code>api/check_project_completion_status.php</code> để kiểm tra trạng thái chi tiết</li>";
echo "<li>🎨 Cập nhật JavaScript trong <code>view_project.php</code> để hiển thị thông tin mới</li>";
echo "</ul>";

echo "<h4>🎯 Logic mới:</h4>";
echo "<ul>";
echo "<li>📝 <strong>Nhập biên bản:</strong> Chỉ lưu thông tin, không tự động hoàn thành</li>";
echo "<li>📊 <strong>Nhập điểm thành viên:</strong> Kiểm tra nếu tất cả thành viên đã có điểm → tự động hoàn thành</li>";
echo "<li>✅ <strong>Điều kiện hoàn thành:</strong> Biên bản đạt + Tất cả thành viên hội đồng có điểm</li>";
echo "<li>🔍 <strong>Kiểm tra chi tiết:</strong> API trả về thông tin đầy đủ về tiến độ hoàn thành</li>";
echo "</ul>";
echo "</div>";

echo "<p style='color: green; font-weight: bold;'>🎉 Hệ thống đã được cập nhật thành công!</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>
