<?php
require_once 'config/connection.php';

// Get project ID from URL parameter
$project_id = $_GET['project_id'] ?? '';

if (empty($project_id)) {
    die("Vui lòng cung cấp project_id trong URL. Ví dụ: debug_file_status_updated.php?project_id=DT001");
}

// Include the checkProjectCompleteness function
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

echo "<h2>DEBUG: Trạng thái file cho project ID: " . htmlspecialchars($project_id) . "</h2>";

// 1. Kiểm tra thông tin project
echo "<h3>1. Thông tin đề tài:</h3>";
$project_sql = "SELECT DT_MADT, DT_TENDT, DT_FILEBTM, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($project_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$project_result = $stmt->get_result();

if ($project_row = $project_result->fetch_assoc()) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><td><strong>Mã đề tài:</strong></td><td>" . htmlspecialchars($project_row['DT_MADT']) . "</td></tr>";
    echo "<tr><td><strong>Tên đề tài:</strong></td><td>" . htmlspecialchars($project_row['DT_TENDT']) . "</td></tr>";
    echo "<tr><td><strong>File thuyết minh:</strong></td><td>" . htmlspecialchars($project_row['DT_FILEBTM'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Trạng thái:</strong></td><td>" . htmlspecialchars($project_row['DT_TRANGTHAI']) . "</td></tr>";
    echo "</table>";
} else {
    echo "<p style='color: red;'>Không tìm thấy project với ID: " . htmlspecialchars($project_id) . "</p>";
    exit;
}

// 2. Kiểm tra thông tin hợp đồng
echo "<h3>2. Thông tin hợp đồng:</h3>";
$contract_sql = "SELECT HD_MAHD, HD_FILEHD, HD_FILE, HD_NGAYTHD FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$contract_result = $stmt->get_result();

if ($contract_row = $contract_result->fetch_assoc()) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><td><strong>Mã hợp đồng:</strong></td><td>" . htmlspecialchars($contract_row['HD_MAHD']) . "</td></tr>";
    echo "<tr><td><strong>HD_FILEHD:</strong></td><td>" . htmlspecialchars($contract_row['HD_FILEHD'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>HD_FILE:</strong></td><td>" . htmlspecialchars($contract_row['HD_FILE'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Ngày ký:</strong></td><td>" . htmlspecialchars($contract_row['HD_NGAYTHD'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    
    // Check if files exist
    $has_filehd = !empty($contract_row['HD_FILEHD']) && $contract_row['HD_FILEHD'] != '';
    $has_file = !empty($contract_row['HD_FILE']) && $contract_row['HD_FILE'] != '';
    echo "<p><strong>Có file HD_FILEHD:</strong> " . ($has_filehd ? "✅ Có" : "❌ Không") . "</p>";
    echo "<p><strong>Có file HD_FILE:</strong> " . ($has_file ? "✅ Có" : "❌ Không") . "</p>";
    echo "<p><strong>Hợp đồng hoàn thành:</strong> " . (($has_filehd || $has_file) ? "✅ Có" : "❌ Không") . "</p>";
} else {
    echo "<p style='color: red;'>Không tìm thấy hợp đồng cho project này</p>";
}

// 3. Kiểm tra quyết định và biên bản
echo "<h3>3. Thông tin quyết định và biên bản:</h3>";
$decision_sql = "SELECT dt.QD_SO, qd.QD_FILE, bb.BB_SOBB, bb.BB_NGAYLAP 
                FROM de_tai_nghien_cuu dt
                LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($decision_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$decision_result = $stmt->get_result();

if ($decision_row = $decision_result->fetch_assoc()) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><td><strong>Số quyết định:</strong></td><td>" . htmlspecialchars($decision_row['QD_SO'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>File quyết định:</strong></td><td>" . htmlspecialchars($decision_row['QD_FILE'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Số biên bản:</strong></td><td>" . htmlspecialchars($decision_row['BB_SOBB'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Ngày lập BB:</strong></td><td>" . htmlspecialchars($decision_row['BB_NGAYLAP'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    
    // Check decision completeness
    $has_decision = !empty($decision_row['QD_FILE']) && $decision_row['QD_FILE'] != '';
    $has_bien_ban = !empty($decision_row['BB_SOBB']);
    echo "<p><strong>Có file quyết định:</strong> " . ($has_decision ? "✅ Có" : "❌ Không") . "</p>";
    echo "<p><strong>Có biên bản:</strong> " . ($has_bien_ban ? "✅ Có" : "❌ Không") . "</p>";
    echo "<p><strong>Quyết định hoàn thành:</strong> " . (($has_decision && $has_bien_ban) ? "✅ Có" : "❌ Không") . "</p>";
} else {
    echo "<p style='color: red;'>Không tìm thấy quyết định cho project này</p>";
}

// 4. Kiểm tra file đánh giá
echo "<h3>4. Thông tin file đánh giá:</h3>";
$eval_sql = "SELECT fg.FDG_FILE, fg.FDG_LOAI, bb.BB_SOBB
            FROM file_danh_gia fg
            INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB
            INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
            INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
            WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($eval_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$eval_result = $stmt->get_result();

$eval_files = [];
while ($eval_row = $eval_result->fetch_assoc()) {
    $eval_files[] = $eval_row;
}

if (!empty($eval_files)) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>File đánh giá</th><th>Loại</th><th>Biên bản</th></tr>";
    foreach ($eval_files as $file) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($file['FDG_FILE']) . "</td>";
        echo "<td>" . htmlspecialchars($file['FDG_LOAI']) . "</td>";
        echo "<td>" . htmlspecialchars($file['BB_SOBB']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Số file đánh giá:</strong> " . count($eval_files) . "</p>";
    echo "<p><strong>Đánh giá hoàn thành:</strong> " . (count($eval_files) > 0 ? "✅ Có" : "❌ Không") . "</p>";
} else {
    echo "<p style='color: red;'>Không tìm thấy file đánh giá nào</p>";
}

// 5. Kết quả function checkProjectCompleteness
echo "<h3>5. Kết quả function checkProjectCompleteness:</h3>";
$completeness = checkProjectCompleteness($project_id, $conn);

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Loại file</th><th>Trạng thái</th><th>Icon</th></tr>";
foreach ($completeness as $type => $status) {
    $status_text = $status ? "✅ Hoàn thành" : "❌ Chưa hoàn thành";
    $icon_class = $status ? "fas fa-check-circle" : "fas fa-circle";
    $type_name = [
        'proposal' => 'Thuyết minh',
        'contract' => 'Hợp đồng', 
        'decision' => 'Quyết định',
        'evaluation' => 'Đánh giá'
    ][$type];
    
    echo "<tr>";
    echo "<td>{$type_name}</td>";
    echo "<td>{$status_text}</td>";
    echo "<td><i class='{$icon_class}'></i></td>";
    echo "</tr>";
}
echo "</table>";

// 6. Visual representation như trong completion-indicators
echo "<h3>6. Hiển thị như trong completion-indicators:</h3>";
echo "<div style='padding: 20px; background: #f8f9fa; border-radius: 10px;'>";
echo "<h4><i class='fas fa-tasks'></i> Trạng thái tài liệu</h4>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px;'>";

foreach ($completeness as $type => $status) {
    $type_names = [
        'proposal' => 'Thuyết minh',
        'contract' => 'Hợp đồng', 
        'decision' => 'Quyết định',
        'evaluation' => 'Đánh giá'
    ];
    
    $icon = $status ? 'check-circle' : 'circle';
    $color = $status ? '#28a745' : '#6c757d';
    $bg_color = $status ? '#d4edda' : '#f8f9fa';
    
    echo "<div style='display: flex; align-items: center; padding: 8px 12px; background: {$bg_color}; border-radius: 20px; border: 1px solid " . ($status ? '#c3e6cb' : '#dee2e6') . ";'>";
    echo "<i class='fas fa-{$icon}' style='color: {$color}; margin-right: 8px;'></i>";
    echo "<span style='color: {$color}; font-weight: 500;'>{$type_names[$type]}</span>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { width: 100%; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
</style>
