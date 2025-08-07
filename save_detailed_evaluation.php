<?php
session_start();
require_once 'include/database.php';
require_once 'check_project_completion.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

// Lấy dữ liệu từ form
$member_id = trim($_POST['member_id'] ?? '');
$project_id = trim($_POST['project_id'] ?? '');
$scores = $_POST['scores'] ?? [];
$comments = $_POST['comments'] ?? [];
$overall_comment = trim($_POST['overall_comment'] ?? '');

// Validate dữ liệu đầu vào
if (empty($member_id) || empty($project_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin member_id hoặc project_id'
    ]);
    exit;
}

if (empty($scores) || !is_array($scores)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu điểm đánh giá'
    ]);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Lấy thông tin quyết định nghiệm thu
    $decision_sql = "SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($decision_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $decision = $stmt->get_result()->fetch_assoc();
    
    if (!$decision) {
        throw new Exception('Không tìm thấy quyết định nghiệm thu cho đề tài này');
    }
    
    $qd_so = $decision['QD_SO'];
    
    // Lấy danh sách tiêu chí đánh giá để validate
    $criteria_sql = "SELECT TC_MATC, TC_DIEMTOIDA, TC_TRONGSO FROM tieu_chi ORDER BY TC_MATC";
    $criteria_result = $conn->query($criteria_sql);
    $criteria_list = [];
    
    while ($criterion = $criteria_result->fetch_assoc()) {
        $criteria_list[$criterion['TC_MATC']] = [
            'max_score' => $criterion['TC_DIEMTOIDA'],
            'weight' => $criterion['TC_TRONGSO']
        ];
    }
    
    // Validate từng điểm số
    $total_weighted_score = 0;
    $valid_scores = [];
    
    foreach ($scores as $criteria_id => $score) {
        if (!isset($criteria_list[$criteria_id])) {
            throw new Exception("Tiêu chí không hợp lệ: $criteria_id");
        }
        
        $score = floatval($score);
        $max_score = $criteria_list[$criteria_id]['max_score'];
        $weight = $criteria_list[$criteria_id]['weight'];
        
        if ($score < 0 || $score > $max_score) {
            throw new Exception("Điểm cho tiêu chí $criteria_id phải từ 0 đến $max_score");
        }
        
        $valid_scores[$criteria_id] = $score;
        
        // Tính điểm theo trọng số (quy về thang điểm 100)
        $normalized_score = ($score / $max_score) * $weight;
        $total_weighted_score += $normalized_score;
    }
    
    // Xóa điểm chi tiết cũ (nếu có)
    $delete_sql = "DELETE FROM chi_tiet_diem_danh_gia WHERE QD_SO = ? AND GV_MAGV = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ss", $qd_so, $member_id);
    $stmt->execute();
    
    // Lưu điểm chi tiết mới
    $insert_sql = "INSERT INTO chi_tiet_diem_danh_gia (QD_SO, GV_MAGV, TC_MATC, CTDD_DIEM, CTDD_NHANXET) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    
    foreach ($valid_scores as $criteria_id => $score) {
        $comment = trim($comments[$criteria_id] ?? '');
        $stmt->bind_param("sssds", $qd_so, $member_id, $criteria_id, $score, $comment);
        $stmt->execute();
    }
    
    // Cập nhật điểm tổng và trạng thái cho thành viên hội đồng
    $update_member_sql = "UPDATE thanh_vien_hoi_dong 
                         SET TV_DIEM = ?, 
                             TV_DANHGIA = ?,
                             TV_DIEMCHITIET = 'Có',
                             TV_TRANGTHAI = 'Đã hoàn thành',
                             TV_NGAYDANHGIA = NOW()
                         WHERE QD_SO = ? AND GV_MAGV = ?";
    
    $stmt = $conn->prepare($update_member_sql);
    $stmt->bind_param("dsss", $total_weighted_score, $overall_comment, $qd_so, $member_id);
    $stmt->execute();
    
    // Cập nhật điểm trung bình cho biên bản nghiệm thu
    $avg_score_sql = "SELECT AVG(TV_DIEM) as avg_score 
                     FROM thanh_vien_hoi_dong 
                     WHERE QD_SO = ? AND TV_DIEM IS NOT NULL";
    $stmt = $conn->prepare($avg_score_sql);
    $stmt->bind_param("s", $qd_so);
    $stmt->execute();
    $avg_result = $stmt->get_result()->fetch_assoc();
    
    if ($avg_result['avg_score']) {
        $update_report_sql = "UPDATE bien_ban_nghiem_thu 
                             SET BB_TONGDIEM = ? 
                             WHERE QD_SO = ?";
        $stmt = $conn->prepare($update_report_sql);
        $stmt->bind_param("ds", $avg_result['avg_score'], $qd_so);
        $stmt->execute();
    }
    
    $conn->commit();
    
    // Kiểm tra và tự động cập nhật trạng thái đề tài nếu đủ điều kiện
    $completion_check = autoCheckProjectCompletion($project_id, $conn);
    
    $response = [
        'success' => true,
        'message' => 'Đánh giá chi tiết đã được lưu thành công',
        'total_score' => round($total_weighted_score, 1),
        'member_id' => $member_id,
        'criteria_count' => count($valid_scores)
    ];
    
    // Thêm thông tin về việc hoàn thành đề tài nếu có
    if ($completion_check['changed']) {
        $response['project_completed'] = true;
        $response['completion_message'] = 'Đề tài đã được tự động chuyển sang trạng thái "Đã hoàn thành" do đã đáp ứng đầy đủ các yêu cầu.';
        $response['completion_details'] = $completion_check['requirements'];
    } else {
        $response['project_completed'] = false;
        $response['completion_status'] = $completion_check;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lưu đánh giá: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
