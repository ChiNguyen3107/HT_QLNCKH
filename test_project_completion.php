<?php
// File test để kiểm tra logic tự động cập nhật trạng thái đề tài
include 'include/connect.php';

// Helper function để kiểm tra tính đầy đủ của các file yêu cầu
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
    
    // Kiểm tra file hợp đồng
    $contract_sql = "SELECT HD_FILE FROM hop_dong WHERE DT_MADT = ? AND HD_FILE IS NOT NULL AND HD_FILE != ''";
    $stmt = $conn->prepare($contract_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $required_files['contract'] = ($result->num_rows > 0);
    }
    
    // Kiểm tra file quyết định và biên bản
    $decision_sql = "SELECT qd.QD_FILE, bb.BB_SOBB 
                    FROM quyet_dinh_nghiem_thu qd
                    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                    WHERE qd.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)
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
                    INNER JOIN de_tai_nghien_cuu dt ON qd.QD_SO = dt.QD_SO
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

// Function tự động cập nhật trạng thái đề tài
function updateProjectStatusIfComplete($project_id, $conn) {
    $completeness = checkProjectCompleteness($project_id, $conn);
    
    // Nếu tất cả file đã đầy đủ, cập nhật trạng thái thành "Đã hoàn thành"
    if ($completeness['proposal'] && $completeness['contract'] && 
        $completeness['decision'] && $completeness['evaluation']) {
        
        $update_sql = "UPDATE de_tai_nghien_cuu SET DT_TRANGTHAI = 'Đã hoàn thành' WHERE DT_MADT = ? AND DT_TRANGTHAI != 'Đã hoàn thành'";
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            return $stmt->affected_rows > 0; // Trả về true nếu có cập nhật
        }
    }
    
    return false;
}

// Test với một mã đề tài cụ thể
$test_project_id = "DT001"; // Thay đổi theo mã đề tài thực tế trong database

echo "<h2>Test tính năng tự động cập nhật trạng thái đề tài</h2>";
echo "<p>Đang kiểm tra đề tài: <strong>$test_project_id</strong></p>";

// Kiểm tra tính đầy đủ của file
$completeness = checkProjectCompleteness($test_project_id, $conn);

echo "<h3>Tình trạng file:</h3>";
echo "<ul>";
echo "<li>File thuyết minh: " . ($completeness['proposal'] ? "✅ Có" : "❌ Chưa có") . "</li>";
echo "<li>File hợp đồng: " . ($completeness['contract'] ? "✅ Có" : "❌ Chưa có") . "</li>";
echo "<li>File quyết định: " . ($completeness['decision'] ? "✅ Có" : "❌ Chưa có") . "</li>";
echo "<li>File đánh giá: " . ($completeness['evaluation'] ? "✅ Có" : "❌ Chưa có") . "</li>";
echo "</ul>";

// Kiểm tra trạng thái hiện tại
$status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("s", $test_project_id);
$stmt->execute();
$result = $stmt->get_result();
$current_status = "";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $current_status = $row['DT_TRANGTHAI'];
    echo "<p>Trạng thái hiện tại: <strong>$current_status</strong></p>";
} else {
    echo "<p>❌ Không tìm thấy đề tài này trong database</p>";
    exit;
}

// Thử cập nhật trạng thái
$updated = updateProjectStatusIfComplete($test_project_id, $conn);

if ($updated) {
    echo "<p>✅ <strong>Đã cập nhật trạng thái thành 'Đã hoàn thành'</strong></p>";
} else {
    if ($completeness['proposal'] && $completeness['contract'] && 
        $completeness['decision'] && $completeness['evaluation']) {
        echo "<p>ℹ️ Đề tài đã ở trạng thái 'Đã hoàn thành' từ trước</p>";
    } else {
        echo "<p>⏳ Chưa đủ điều kiện để cập nhật thành 'Đã hoàn thành'</p>";
        echo "<p>Cần có đầy đủ: file thuyết minh, hợp đồng, quyết định và file đánh giá</p>";
    }
}

echo "<hr>";
echo "<h3>Hướng dẫn sử dụng:</h3>";
echo "<ol>";
echo "<li>Đề tài sẽ tự động chuyển sang trạng thái 'Đã hoàn thành' khi nộp đủ 4 loại file: thuyết minh, hợp đồng, quyết định, đánh giá</li>";
echo "<li>Khi ở trạng thái 'Đã hoàn thành', không thể chỉnh sửa hay upload file mới</li>";
echo "<li>Chỉ hiển thị các file đã nộp để xem và tải xuống</li>";
echo "<li>Chỉ chủ nhiệm đề tài mới có quyền upload file khi đề tài đang 'Đang thực hiện'</li>";
echo "</ol>";
?>
