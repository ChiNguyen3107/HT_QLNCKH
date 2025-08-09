<?php
require_once 'config/connection.php';

$project_id = $_GET['project_id'] ?? 'DT001';

// Include the updated function from view_project.php
function checkProjectCompleteness($project_id, $conn) {
    $required_files = [
        'proposal' => false,    // File thuyết minh
        'contract' => false,    // File hợp đồng
        'decision' => false,    // File quyết định
        'evaluation' => false   // File đánh giá
    ];
    
    try {
        // 1. Kiểm tra file thuyết minh
        $proposal_sql = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND DT_FILEBTM IS NOT NULL AND TRIM(DT_FILEBTM) != ''";
        $stmt = $conn->prepare($proposal_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $required_files['proposal'] = ($result->num_rows > 0);
            $stmt->close();
        }
        
        // 2. Kiểm tra file hợp đồng - kiểm tra cả HD_FILE và HD_FILEHD
        $contract_sql = "SELECT HD_FILEHD, HD_FILE FROM hop_dong WHERE DT_MADT = ?";
        $stmt = $conn->prepare($contract_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Kiểm tra cả hai trường file một cách chặt chẽ hơn
                $has_filehd = isset($row['HD_FILEHD']) && !empty(trim($row['HD_FILEHD']));
                $has_file = isset($row['HD_FILE']) && !empty(trim($row['HD_FILE']));
                $required_files['contract'] = ($has_filehd || $has_file);
            }
            $stmt->close();
        }
        
        // 3. Kiểm tra file quyết định và biên bản
        $decision_sql = "SELECT qd.QD_FILE, bb.BB_SOBB 
                        FROM de_tai_nghien_cuu dt
                        INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                        LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                        WHERE dt.DT_MADT = ?
                        AND qd.QD_FILE IS NOT NULL AND TRIM(qd.QD_FILE) != ''
                        AND bb.BB_SOBB IS NOT NULL";
        $stmt = $conn->prepare($decision_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $required_files['decision'] = ($result->num_rows > 0);
            $stmt->close();
        }
        
        // 4. Kiểm tra file đánh giá - ưu tiên kiểm tra trực tiếp trước
        $eval_direct_sql = "SELECT COUNT(*) as file_count FROM file_danh_gia fg 
                           WHERE fg.DT_MADT = ? AND fg.FDG_FILE IS NOT NULL AND TRIM(fg.FDG_FILE) != ''";
        $stmt = $conn->prepare($eval_direct_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $required_files['evaluation'] = ($row['file_count'] > 0);
            $stmt->close();
        }
        
        // Nếu kiểm tra trực tiếp không có kết quả và có quyết định, kiểm tra thông qua biên bản
        if (!$required_files['evaluation'] && $required_files['decision']) {
            $eval_sql = "SELECT COUNT(*) as file_count FROM file_danh_gia fg
                        INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB
                        INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
                        INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
                        WHERE dt.DT_MADT = ? AND fg.FDG_FILE IS NOT NULL AND TRIM(fg.FDG_FILE) != ''";
            $stmt = $conn->prepare($eval_sql);
            if ($stmt) {
                $stmt->bind_param("s", $project_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $required_files['evaluation'] = ($row['file_count'] > 0);
                $stmt->close();
            }
        }
        
    } catch (Exception $e) {
        // Log error nếu cần thiết
        error_log("Error in checkProjectCompleteness: " . $e->getMessage());
    }
    
    return $required_files;
}

$file_completeness = checkProjectCompleteness($project_id, $conn);
$check_time = date('H:i:s');

echo "<h2>✅ KIỂM TRA CUỐI CÙNG - TRẠNG THÁI TÀI LIỆU</h2>";
echo "<p><strong>Project ID:</strong> " . htmlspecialchars($project_id) . "</p>";
echo "<p><strong>Thời gian kiểm tra:</strong> " . $check_time . "</p>";

// Calculate completion stats
$total_files = 4;
$completed_files = array_sum($file_completeness);
$completion_percentage = round(($completed_files / $total_files) * 100);

echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 20px; margin: 20px 0; position: relative; overflow: hidden;'>";

echo "<div style='position: relative; z-index: 2;'>";
echo "<div style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; font-weight: 600; font-size: 1.1rem;'>";
echo "<div style='display: flex; align-items: center; gap: 10px;'>";
echo "<i class='fas fa-tasks' style='color: #ffd700;'></i>";
echo "<span>Trạng thái tài liệu</span>";
echo "</div>";
echo "<small style='background: rgba(255, 255, 255, 0.2); padding: 4px 8px; border-radius: 8px; font-size: 0.8em;'>" . $check_time . "</small>";
echo "</div>";

echo "<div style='display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;'>";

$type_names = [
    'proposal' => 'Thuyết minh',
    'contract' => 'Hợp đồng',
    'decision' => 'Quyết định',
    'evaluation' => 'Đánh giá'
];

$descriptions = [
    'proposal' => 'File thuyết minh: Đã có file',
    'contract' => 'File hợp đồng: Đã có file',
    'decision' => 'File quyết định: Đã có file quyết định và biên bản',
    'evaluation' => 'File đánh giá: Đã có file đánh giá'
];

foreach ($file_completeness as $type => $status) {
    $icon = $status ? 'check-circle' : 'circle';
    $color = $status ? '#2ecc71' : 'rgba(255, 255, 255, 0.6)';
    $hover_bg = $status ? 'rgba(46, 204, 113, 0.1)' : 'rgba(255, 255, 255, 0.1)';
    
    echo "<div style='display: flex; align-items: center; gap: 15px; padding: 10px 15px; border-radius: 10px; transition: all 0.3s ease; cursor: help;' ";
    echo "title='" . ($status ? $descriptions[$type] : 'Chưa có file') . "' ";
    echo "onmouseover='this.style.background=\"{$hover_bg}\"; this.style.transform=\"translateX(5px)\";' ";
    echo "onmouseout='this.style.background=\"transparent\"; this.style.transform=\"translateX(0)\";'>";
    echo "<i class='fas fa-{$icon}' style='color: {$color}; width: 20px; text-align: center; font-size: 1.1em;'></i>";
    echo "<span style='color: {$color}; font-weight: 500;'>{$type_names[$type]}</span>";
    echo "</div>";
}

echo "</div>";

// Progress summary
echo "<div style='padding-top: 15px; border-top: 1px solid rgba(255, 255, 255, 0.2);'>";
echo "<div style='background: rgba(255, 255, 255, 0.1); height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;'>";
echo "<div style='width: {$completion_percentage}%; height: 100%; background: linear-gradient(90deg, #2ecc71, #27ae60); transition: width 0.5s ease;'></div>";
echo "</div>";
echo "<div style='text-align: center; font-size: 0.9em;'>";
echo "<span style='color: rgba(255, 255, 255, 0.9);'>{$completed_files}/{$total_files} hoàn thành ({$completion_percentage}%)</span>";
echo "</div>";
echo "</div>";

echo "</div>";

// Background decoration
echo "<div style='position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%); border-radius: 50%;'></div>";
echo "<div style='position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%); border-radius: 50%;'></div>";

echo "</div>";

// Status summary
echo "<div style='background: white; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; margin: 20px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>";
echo "<h3 style='color: #333; margin-bottom: 20px; display: flex; align-items: center;'>";
echo "<i class='fas fa-chart-pie' style='margin-right: 10px; color: #007bff;'></i>";
echo "Tóm tắt trạng thái";
echo "</h3>";

if ($completion_percentage == 100) {
    echo "<div style='background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; padding: 20px; border-radius: 10px; border-left: 5px solid #28a745;'>";
    echo "<i class='fas fa-check-circle' style='margin-right: 10px; font-size: 1.2em;'></i>";
    echo "<strong>Tuyệt vời!</strong> Tất cả tài liệu đã được nộp đầy đủ. Đề tài có thể chuyển sang trạng thái 'Đã hoàn thành'.";
    echo "</div>";
} else if ($completion_percentage >= 75) {
    echo "<div style='background: linear-gradient(135deg, #fff3cd, #ffeaa7); color: #856404; padding: 20px; border-radius: 10px; border-left: 5px solid #ffc107;'>";
    echo "<i class='fas fa-exclamation-triangle' style='margin-right: 10px; font-size: 1.2em;'></i>";
    echo "<strong>Gần hoàn thành!</strong> Chỉ còn thiếu " . (4 - $completed_files) . " tài liệu nữa.";
    echo "</div>";
} else if ($completion_percentage >= 50) {
    echo "<div style='background: linear-gradient(135deg, #cce7ff, #b3d9ff); color: #004085; padding: 20px; border-radius: 10px; border-left: 5px solid #007bff;'>";
    echo "<i class='fas fa-info-circle' style='margin-right: 10px; font-size: 1.2em;'></i>";
    echo "<strong>Đang tiến triển!</strong> Đã hoàn thành hơn một nửa tài liệu yêu cầu.";
    echo "</div>";
} else {
    echo "<div style='background: linear-gradient(135deg, #f8d7da, #f1b0b7); color: #721c24; padding: 20px; border-radius: 10px; border-left: 5px solid #dc3545;'>";
    echo "<i class='fas fa-clock' style='margin-right: 10px; font-size: 1.2em;'></i>";
    echo "<strong>Cần nộp thêm tài liệu!</strong> Vẫn còn " . (4 - $completed_files) . " tài liệu chưa được nộp.";
    echo "</div>";
}

echo "</div>";

echo "<div style='text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;'>";
echo "<p style='margin: 0; color: #6c757d;'>";
echo "✨ <strong>Hệ thống đã được cập nhật và tối ưu hóa!</strong> ✨<br>";
echo "Trạng thái tài liệu hiện tại sẽ được cập nhật real-time và hiển thị chính xác.";
echo "</p>";
echo "</div>";

$conn->close();
?>

<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
    h2, h3 { color: #333; }
    h2 { text-align: center; margin-bottom: 30px; }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
