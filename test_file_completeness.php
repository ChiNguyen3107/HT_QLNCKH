<?php
session_start();
require_once '../config/database.php';

// Lấy project ID từ URL hoặc mặc định
$project_id = isset($_GET['id']) ? $_GET['id'] : 'DT0000001';

// Copy function checkProjectCompleteness từ view_project.php để test
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
    }
    
    // Kiểm tra file hợp đồng - kiểm tra cả HD_FILE và HD_FILEHD
    $contract_sql = "SELECT HD_FILEHD, HD_FILE FROM hop_dong WHERE DT_MADT = ?";
    $stmt = $conn->prepare($contract_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Kiểm tra cả hai trường file
            $has_filehd = !empty($row['HD_FILEHD']) && $row['HD_FILEHD'] != '';
            $has_file = !empty($row['HD_FILE']) && $row['HD_FILE'] != '';
            $required_files['contract'] = ($has_filehd || $has_file);
            
            // Debug info
            echo "<!-- Debug: HD_FILEHD = " . ($row['HD_FILEHD'] ?: 'NULL') . ", HD_FILE = " . ($row['HD_FILE'] ?: 'NULL') . " -->";
        }
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
    }
    
    // Kiểm tra file đánh giá
    if ($required_files['decision']) {
        $eval_sql = "SELECT COUNT(*) as file_count FROM file_danh_gia fg
                    INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB
                    INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
                    INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
                    WHERE dt.DT_MADT = ?";
        $stmt = $conn->prepare($eval_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $required_files['evaluation'] = ($row['file_count'] > 0);
        }
    }
    
    return $required_files;
}

echo "<h2>Test Function checkProjectCompleteness - Đề tài: $project_id</h2>";

$file_completeness = checkProjectCompleteness($project_id, $conn);

echo "<h3>Kết quả kiểm tra:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>";

foreach ($file_completeness as $type => $status) {
    $icon = $status ? '✅' : '❌';
    $color = $status ? 'green' : 'red';
    $text = $status ? 'Hoàn thành' : 'Chưa hoàn thành';
    
    echo "<p style='margin: 5px 0; font-weight: bold;'>";
    echo "<span style='color: $color;'>$icon</span> ";
    
    switch ($type) {
        case 'proposal':
            echo "File thuyết minh: $text";
            break;
        case 'contract':
            echo "File hợp đồng: $text";
            break;
        case 'decision':
            echo "File quyết định: $text";
            break;
        case 'evaluation':
            echo "File đánh giá: $text";
            break;
    }
    echo "</p>";
}

$all_complete = $file_completeness['proposal'] && $file_completeness['contract'] && 
                $file_completeness['decision'] && $file_completeness['evaluation'];

echo "<hr>";
echo "<h4 style='color: " . ($all_complete ? 'green' : 'orange') . ";'>";
echo ($all_complete ? '✅' : '⚠️') . " Tổng trạng thái: ";
echo ($all_complete ? "Đầy đủ - Có thể hoàn thành đề tài" : "Chưa đầy đủ - Cần bổ sung file");
echo "</h4>";

echo "</div>";

// Hiển thị mẫu header indicator như trong view_project.php
echo "<h3>Mẫu hiển thị trong header:</h3>";
echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px;'>";
echo "<h5>Trạng thái tài liệu</h5>";
echo "<div style='display: flex; flex-direction: column; gap: 10px;'>";

$labels = [
    'proposal' => 'Thuyết minh',
    'contract' => 'Hợp đồng', 
    'decision' => 'Quyết định',
    'evaluation' => 'Đánh giá'
];

foreach ($file_completeness as $type => $status) {
    $icon = $status ? 'fas fa-check-circle' : 'fas fa-circle';
    $color = $status ? '#2ecc71' : 'rgba(255, 255, 255, 0.6)';
    
    echo "<div style='display: flex; align-items: center; gap: 12px; color: $color;'>";
    echo "<i class='$icon'></i>";
    echo "<span>{$labels[$type]}</span>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/js/all.min.js"></script>
