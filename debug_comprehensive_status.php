<?php
require_once 'config/connection.php';

$project_id = $_GET['project_id'] ?? 'DT001';

echo "<h2>🔍 KIỂM TRA TOÀN DIỆN TRẠNG THÁI TÀI LIỆU</h2>";
echo "<p><strong>Project ID:</strong> " . htmlspecialchars($project_id) . "</p>";
echo "<hr>";

// 1. Kiểm tra thông tin cơ bản của project
echo "<h3>📋 1. THÔNG TIN CƠ BẢN CỦA ĐỀ TÀI</h3>";
$project_sql = "SELECT DT_MADT, DT_TENDT, DT_FILEBTM, DT_TRANGTHAI, QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($project_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$project_result = $stmt->get_result();

if ($project_row = $project_result->fetch_assoc()) {
    echo "<div class='info-box'>";
    echo "<table>";
    echo "<tr><td><strong>Mã đề tài:</strong></td><td>" . htmlspecialchars($project_row['DT_MADT']) . "</td></tr>";
    echo "<tr><td><strong>Tên đề tài:</strong></td><td>" . htmlspecialchars($project_row['DT_TENDT']) . "</td></tr>";
    echo "<tr><td><strong>File thuyết minh:</strong></td><td>" . htmlspecialchars($project_row['DT_FILEBTM'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Trạng thái:</strong></td><td>" . htmlspecialchars($project_row['DT_TRANGTHAI']) . "</td></tr>";
    echo "<tr><td><strong>Số quyết định:</strong></td><td>" . htmlspecialchars($project_row['QD_SO'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    echo "</div>";
} else {
    echo "<div class='error-box'>❌ Không tìm thấy project với ID: " . htmlspecialchars($project_id) . "</div>";
    exit;
}

// 2. Kiểm tra chi tiết file thuyết minh
echo "<h3>📄 2. KIỂM TRA FILE THUYẾT MINH</h3>";
$proposal_sql = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND DT_FILEBTM IS NOT NULL AND DT_FILEBTM != ''";
$stmt = $conn->prepare($proposal_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$proposal_exists = ($result->num_rows > 0);

echo "<div class='check-box " . ($proposal_exists ? 'success' : 'warning') . "'>";
echo "<p><strong>SQL Query:</strong> <code>" . $proposal_sql . "</code></p>";
echo "<p><strong>Kết quả:</strong> " . ($proposal_exists ? "✅ CÓ FILE" : "❌ CHƯA CÓ FILE") . "</p>";
if ($proposal_exists && $row = $result->fetch_assoc()) {
    echo "<p><strong>Tên file:</strong> " . htmlspecialchars($row['DT_FILEBTM']) . "</p>";
}
echo "</div>";

// 3. Kiểm tra chi tiết file hợp đồng
echo "<h3>📝 3. KIỂM TRA FILE HỢP ĐỒNG</h3>";
$contract_sql = "SELECT HD_MAHD, HD_FILEHD, HD_FILE, HD_NGAYTHD FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$contract_result = $stmt->get_result();

$contract_exists = false;
if ($contract_row = $contract_result->fetch_assoc()) {
    $has_filehd = isset($contract_row['HD_FILEHD']) && !empty(trim($contract_row['HD_FILEHD'])) && $contract_row['HD_FILEHD'] != '';
    $has_file = isset($contract_row['HD_FILE']) && !empty(trim($contract_row['HD_FILE'])) && $contract_row['HD_FILE'] != '';
    $contract_exists = ($has_filehd || $has_file);
    
    echo "<div class='check-box " . ($contract_exists ? 'success' : 'warning') . "'>";
    echo "<table>";
    echo "<tr><td><strong>Mã hợp đồng:</strong></td><td>" . htmlspecialchars($contract_row['HD_MAHD']) . "</td></tr>";
    echo "<tr><td><strong>HD_FILEHD:</strong></td><td>" . htmlspecialchars($contract_row['HD_FILEHD'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>HD_FILE:</strong></td><td>" . htmlspecialchars($contract_row['HD_FILE'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Ngày ký:</strong></td><td>" . htmlspecialchars($contract_row['HD_NGAYTHD'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    echo "<p><strong>Có HD_FILEHD:</strong> " . ($has_filehd ? "✅ CÓ" : "❌ KHÔNG") . "</p>";
    echo "<p><strong>Có HD_FILE:</strong> " . ($has_file ? "✅ CÓ" : "❌ KHÔNG") . "</p>";
    echo "<p><strong>Kết luận hợp đồng:</strong> " . ($contract_exists ? "✅ HOÀN THÀNH" : "❌ CHƯA HOÀN THÀNH") . "</p>";
    echo "</div>";
} else {
    echo "<div class='error-box'>❌ Không tìm thấy bản ghi hợp đồng cho project này</div>";
}

// 4. Kiểm tra chi tiết quyết định và biên bản
echo "<h3>⚖️ 4. KIỂM TRA QUYẾT ĐỊNH VÀ BIÊN BẢN</h3>";
$decision_sql = "SELECT dt.QD_SO, qd.QD_FILE, qd.QD_NGAYQD, bb.BB_SOBB, bb.BB_NGAYLAP 
                FROM de_tai_nghien_cuu dt
                LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($decision_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$decision_result = $stmt->get_result();

$decision_exists = false;
if ($decision_row = $decision_result->fetch_assoc()) {
    $has_decision_file = !empty($decision_row['QD_FILE']) && $decision_row['QD_FILE'] != '';
    $has_bien_ban = !empty($decision_row['BB_SOBB']);
    $decision_exists = ($has_decision_file && $has_bien_ban);
    
    echo "<div class='check-box " . ($decision_exists ? 'success' : 'warning') . "'>";
    echo "<table>";
    echo "<tr><td><strong>Số quyết định:</strong></td><td>" . htmlspecialchars($decision_row['QD_SO'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>File quyết định:</strong></td><td>" . htmlspecialchars($decision_row['QD_FILE'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Ngày quyết định:</strong></td><td>" . htmlspecialchars($decision_row['QD_NGAYQD'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Số biên bản:</strong></td><td>" . htmlspecialchars($decision_row['BB_SOBB'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td><strong>Ngày lập BB:</strong></td><td>" . htmlspecialchars($decision_row['BB_NGAYLAP'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    echo "<p><strong>Có file quyết định:</strong> " . ($has_decision_file ? "✅ CÓ" : "❌ KHÔNG") . "</p>";
    echo "<p><strong>Có biên bản:</strong> " . ($has_bien_ban ? "✅ CÓ" : "❌ KHÔNG") . "</p>";
    echo "<p><strong>Kết luận quyết định:</strong> " . ($decision_exists ? "✅ HOÀN THÀNH" : "❌ CHƯA HOÀN THÀNH") . "</p>";
    echo "</div>";
} else {
    echo "<div class='error-box'>❌ Không tìm thấy thông tin quyết định cho project này</div>";
}

// 5. Kiểm tra chi tiết file đánh giá
echo "<h3>⭐ 5. KIỂM TRA FILE ĐÁNH GIÁ</h3>";

// Kiểm tra theo cách của function hiện tại
$evaluation_exists = false;
if ($decision_exists) {
    echo "<div class='info-box'>🔄 Kiểm tra file đánh giá thông qua biên bản...</div>";
    $eval_sql = "SELECT fg.FDG_FILE, fg.FDG_LOAI, fg.FDG_DIEM, bb.BB_SOBB
                FROM file_danh_gia fg
                INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB
                INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
                INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
                WHERE dt.DT_MADT = ? AND fg.FDG_FILE IS NOT NULL AND fg.FDG_FILE != ''";
    $stmt = $conn->prepare($eval_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $eval_result = $stmt->get_result();
    
    $eval_files = [];
    while ($eval_row = $eval_result->fetch_assoc()) {
        $eval_files[] = $eval_row;
    }
    $evaluation_exists = (count($eval_files) > 0);
} else {
    echo "<div class='info-box'>🔄 Quyết định chưa hoàn thành, kiểm tra file đánh giá trực tiếp...</div>";
    $eval_direct_sql = "SELECT FDG_FILE, FDG_LOAI, FDG_DIEM FROM file_danh_gia WHERE DT_MADT = ? AND FDG_FILE IS NOT NULL AND FDG_FILE != ''";
    $stmt = $conn->prepare($eval_direct_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $eval_result = $stmt->get_result();
    
    $eval_files = [];
    while ($eval_row = $eval_result->fetch_assoc()) {
        $eval_files[] = $eval_row;
    }
    $evaluation_exists = (count($eval_files) > 0);
}

echo "<div class='check-box " . ($evaluation_exists ? 'success' : 'warning') . "'>";
if (!empty($eval_files)) {
    echo "<p><strong>Số file đánh giá:</strong> " . count($eval_files) . "</p>";
    echo "<table>";
    echo "<tr><th>File đánh giá</th><th>Loại</th><th>Điểm</th></tr>";
    foreach ($eval_files as $file) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($file['FDG_FILE']) . "</td>";
        echo "<td>" . htmlspecialchars($file['FDG_LOAI']) . "</td>";
        echo "<td>" . htmlspecialchars($file['FDG_DIEM'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Kết luận đánh giá:</strong> ✅ HOÀN THÀNH</p>";
} else {
    echo "<p><strong>Số file đánh giá:</strong> 0</p>";
    echo "<p><strong>Kết luận đánh giá:</strong> ❌ CHƯA HOÀN THÀNH</p>";
}
echo "</div>";

// 6. Kiểm tra function checkProjectCompleteness
echo "<h3>🔧 6. KẾT QUẢ FUNCTION checkProjectCompleteness</h3>";

// Include the actual function
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

$completeness = checkProjectCompleteness($project_id, $conn);

echo "<div class='result-box'>";
echo "<h4>📊 Kết quả tổng hợp:</h4>";
echo "<table class='result-table'>";
echo "<tr><th>Loại tài liệu</th><th>Trạng thái function</th><th>Trạng thái thực tế</th><th>Khớp?</th></tr>";

$type_names = [
    'proposal' => 'Thuyết minh',
    'contract' => 'Hợp đồng',
    'decision' => 'Quyết định',
    'evaluation' => 'Đánh giá'
];

$actual_status = [
    'proposal' => $proposal_exists,
    'contract' => $contract_exists,
    'decision' => $decision_exists,
    'evaluation' => $evaluation_exists
];

foreach ($completeness as $type => $function_status) {
    $actual = $actual_status[$type];
    $match = ($function_status === $actual);
    
    echo "<tr class='" . ($match ? 'match' : 'mismatch') . "'>";
    echo "<td>" . $type_names[$type] . "</td>";
    echo "<td>" . ($function_status ? "✅ Hoàn thành" : "❌ Chưa hoàn thành") . "</td>";
    echo "<td>" . ($actual ? "✅ Hoàn thành" : "❌ Chưa hoàn thành") . "</td>";
    echo "<td>" . ($match ? "✅ Khớp" : "❌ Không khớp") . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 7. Hiển thị như trong completion-indicators
echo "<h3>🎨 7. HIỂN THỊ TRẠNG THÁI TÀI LIỆU THEO UI</h3>";
echo "<div class='completion-preview'>";
echo "<div class='completion-title'>";
echo "<i class='fas fa-tasks'></i>";
echo "<span>Trạng thái tài liệu</span>";
echo "</div>";
echo "<div class='file-indicators'>";

foreach ($completeness as $type => $status) {
    $icon = $status ? 'check-circle' : 'circle';
    $class = $status ? 'completed' : 'pending';
    
    echo "<div class='file-indicator {$class}'>";
    echo "<i class='fas fa-{$icon}'></i>";
    echo "<span>{$type_names[$type]}</span>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

$conn->close();
?>

<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f5f5f5; }
    h2, h3 { color: #333; margin-top: 30px; }
    h2 { border-bottom: 3px solid #007bff; padding-bottom: 10px; }
    h3 { border-left: 5px solid #17a2b8; padding-left: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px; }
    
    .info-box, .check-box, .error-box, .result-box { 
        padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 5px solid; 
    }
    .info-box { background: #e7f3ff; border-color: #007bff; }
    .check-box.success { background: #d4edda; border-color: #28a745; }
    .check-box.warning { background: #fff3cd; border-color: #ffc107; }
    .error-box { background: #f8d7da; border-color: #dc3545; }
    .result-box { background: #e2e3e5; border-color: #6c757d; }
    
    table { width: 100%; border-collapse: collapse; margin: 10px 0; background: white; }
    th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: 600; }
    
    .result-table tr.match { background-color: #d4edda; }
    .result-table tr.mismatch { background-color: #f8d7da; }
    
    code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    
    /* Completion indicators preview */
    .completion-preview { 
        background: white; border: 2px solid #dee2e6; border-radius: 15px; padding: 20px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .completion-title { 
        font-size: 1.1em; font-weight: 600; margin-bottom: 15px; 
        display: flex; align-items: center; color: #495057;
    }
    .completion-title i { margin-right: 10px; color: #007bff; }
    .file-indicators { display: flex; flex-wrap: wrap; gap: 10px; }
    .file-indicator { 
        display: flex; align-items: center; padding: 8px 15px; border-radius: 20px; 
        font-weight: 500; transition: all 0.3s ease;
    }
    .file-indicator i { margin-right: 8px; }
    .file-indicator.completed { 
        background: #d4edda; color: #155724; border: 1px solid #c3e6cb; 
    }
    .file-indicator.pending { 
        background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; 
    }
    .file-indicator.completed i { color: #28a745; }
    .file-indicator.pending i { color: #6c757d; }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
