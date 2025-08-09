<?php
require_once 'config/connection.php';

// Use the updated function
function checkProjectCompleteness($project_id, $conn) {
    $required_files = [
        'proposal' => false,    // File thuyết minh
        'contract' => false,    // File hợp đồng
        'decision' => false,    // File quyết định
        'evaluation' => false   // File đánh giá
    ];
    
    // Kiểm tra file thuyết minh
    $proposal_sql = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND DT_FILEBTM IS NOT NULL AND DT_FILEBTM != ''";
    $stmt = $conn->prepare($proposal_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $required_files['proposal'] = ($result->num_rows > 0);
        $stmt->close();
    }
    
    // Kiểm tra file hợp đồng - kiểm tra cả HD_FILE và HD_FILEHD
    $contract_sql = "SELECT HD_FILEHD, HD_FILE FROM hop_dong WHERE DT_MADT = ? AND (HD_FILEHD IS NOT NULL OR HD_FILE IS NOT NULL)";
    $stmt = $conn->prepare($contract_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Kiểm tra cả hai trường file một cách chặt chẽ hơn
            $has_filehd = isset($row['HD_FILEHD']) && !empty(trim($row['HD_FILEHD'])) && $row['HD_FILEHD'] != '';
            $has_file = isset($row['HD_FILE']) && !empty(trim($row['HD_FILE'])) && $row['HD_FILE'] != '';
            $required_files['contract'] = ($has_filehd || $has_file);
        }
        $stmt->close();
    }
    
    // Kiểm tra file quyết định và biên bản
    $decision_sql = "SELECT qd.QD_FILE, bb.BB_SOBB 
                    FROM de_tai_nghien_cuu dt
                    INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                    WHERE dt.DT_MADT = ?
                    AND qd.QD_FILE IS NOT NULL AND qd.QD_FILE != ''
                    AND bb.BB_SOBB IS NOT NULL";
    $stmt = $conn->prepare($decision_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $required_files['decision'] = ($result->num_rows > 0);
        $stmt->close();
    }
    
    // Kiểm tra file đánh giá
    if ($required_files['decision']) {
        $eval_sql = "SELECT COUNT(*) as file_count FROM file_danh_gia fg
                    INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB
                    INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
                    INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
                    WHERE dt.DT_MADT = ? AND fg.FDG_FILE IS NOT NULL AND fg.FDG_FILE != ''";
        $stmt = $conn->prepare($eval_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $required_files['evaluation'] = ($row['file_count'] > 0);
            $stmt->close();
        }
    } else {
        // Nếu chưa có quyết định, kiểm tra file đánh giá theo cách khác
        // Có thể có file đánh giá được upload trực tiếp mà không thông qua biên bản
        $eval_direct_sql = "SELECT COUNT(*) as file_count FROM file_danh_gia fg 
                           WHERE fg.DT_MADT = ? AND fg.FDG_FILE IS NOT NULL AND fg.FDG_FILE != ''";
        $stmt = $conn->prepare($eval_direct_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $required_files['evaluation'] = ($row['file_count'] > 0);
            $stmt->close();
        }
    }
    
    return $required_files;
}

$project_id = $_GET['project_id'] ?? 'DT001';

echo "<h2>Test Function Updated checkProjectCompleteness</h2>";
echo "<p><strong>Project ID:</strong> " . htmlspecialchars($project_id) . "</p>";

$file_completeness = checkProjectCompleteness($project_id, $conn);

echo "<h3>Kết quả:</h3>";
echo "<div style='padding: 20px; background: #f8f9fa; border-radius: 10px; margin: 20px 0;'>";
echo "<h4><i class='fas fa-tasks' style='color: #007bff;'></i> Trạng thái tài liệu</h4>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;'>";

$type_names = [
    'proposal' => 'Thuyết minh',
    'contract' => 'Hợp đồng', 
    'decision' => 'Quyết định',
    'evaluation' => 'Đánh giá'
];

foreach ($file_completeness as $type => $status) {
    $icon = $status ? 'check-circle' : 'circle';
    $color = $status ? '#28a745' : '#6c757d';
    $bg_color = $status ? '#d4edda' : '#f8f9fa';
    $border_color = $status ? '#c3e6cb' : '#dee2e6';
    
    echo "<div style='display: flex; align-items: center; padding: 12px 16px; background: {$bg_color}; border-radius: 25px; border: 2px solid {$border_color}; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>";
    echo "<i class='fas fa-{$icon}' style='color: {$color}; margin-right: 10px; font-size: 1.2em;'></i>";
    echo "<span style='color: {$color}; font-weight: 600;'>{$type_names[$type]}</span>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Show detailed breakdown
echo "<h3>Chi tiết kiểm tra:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f2f2f2;'><th>Loại file</th><th>Trạng thái</th><th>Mô tả</th></tr>";

foreach ($file_completeness as $type => $status) {
    $status_text = $status ? "✅ Hoàn thành" : "❌ Chưa hoàn thành";
    $description = [
        'proposal' => 'File thuyết minh trong bảng de_tai_nghien_cuu',
        'contract' => 'File hợp đồng trong bảng hop_dong (HD_FILEHD hoặc HD_FILE)',
        'decision' => 'File quyết định và biên bản nghiệm thu',
        'evaluation' => 'File đánh giá từ hội đồng'
    ][$type];
    
    echo "<tr>";
    echo "<td style='padding: 8px; font-weight: 500;'>{$type_names[$type]}</td>";
    echo "<td style='padding: 8px;'>{$status_text}</td>";
    echo "<td style='padding: 8px; font-size: 0.9em;'>{$description}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p style='margin-top: 30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff; border-radius: 5px;'>";
echo "<strong>Lưu ý:</strong> Hệ thống đã được cập nhật để kiểm tra chính xác trạng thái các file. ";
echo "Nếu bạn đã upload file nhưng vẫn hiển thị chưa hoàn thành, vui lòng kiểm tra lại file đã được lưu đúng vào database chưa.";
echo "</p>";

$conn->close();
?>

<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #fafafa; }
    h2, h3 { color: #333; }
    table { background: white; }
    th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: 600; }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
