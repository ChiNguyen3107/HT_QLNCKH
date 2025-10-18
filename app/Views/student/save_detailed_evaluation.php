<?php
// File: view/student/save_detailed_evaluation.php
// Lưu điểm đánh giá chi tiết cho thành viên hội đồng

ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

header('Content-Type: application/json');

try {
    // Kiểm tra phương thức POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Phương thức không được phép");
    }
    
    // Lấy dữ liệu JSON từ request body
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (!$data) {
        throw new Exception("Dữ liệu không hợp lệ");
    }
    
    $project_id = $data['project_id'] ?? '';
    $decision_id = $data['decision_id'] ?? '';
    $member_id = $data['member_id'] ?? '';
    $scores = $data['scores'] ?? [];
    $update_reason = $data['update_reason'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($project_id) || empty($decision_id) || empty($member_id) || empty($scores)) {
        throw new Exception("Thiếu thông tin bắt buộc");
    }
    
    if (empty($update_reason)) {
        throw new Exception("Vui lòng nhập lý do cập nhật");
    }
    
    // Kiểm tra quyền chủ nhiệm
    $check_role_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
    $stmt = $conn->prepare($check_role_sql);
    $stmt->bind_param("ss", $project_id, $user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    
    if ($role_result->num_rows === 0) {
        throw new Exception("Bạn không có quyền truy cập đề tài này");
    }
    
    $user_role = $role_result->fetch_assoc()['CTTG_VAITRO'];
    if ($user_role !== 'Chủ nhiệm') {
        throw new Exception("Chỉ chủ nhiệm đề tài mới có thể cập nhật điểm đánh giá");
    }
    
    // Kiểm tra trạng thái đề tài
    $check_status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($check_status_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $status_result = $stmt->get_result();
    
    if ($status_result->num_rows === 0) {
        throw new Exception("Không tìm thấy đề tài");
    }
    
    $project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];
    if ($project_status !== 'Đang thực hiện') {
        throw new Exception("Chỉ có thể cập nhật điểm khi đề tài đang trong trạng thái 'Đang thực hiện'");
    }
    
    // Kiểm tra thành viên hội đồng có tồn tại
    $check_member_sql = "SELECT GV_MAGV FROM thanh_vien_hoi_dong WHERE QD_SO = ? AND GV_MAGV = ?";
    $stmt = $conn->prepare($check_member_sql);
    $stmt->bind_param("ss", $decision_id, $member_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Thành viên hội đồng không tồn tại");
    }
    
    // Validate điểm số
    foreach ($scores as $score_data) {
        $criteria_id = $score_data['criteriaId'] ?? '';
        $score = $score_data['score'] ?? 0;
        
        if (empty($criteria_id)) {
            throw new Exception("Thiếu mã tiêu chí");
        }
        
        if ($score < 0) {
            throw new Exception("Điểm số không được âm");
        }
        
        // Kiểm tra điểm không vượt quá điểm tối đa
        $check_max_sql = "SELECT TC_DIEMTOIDA FROM tieu_chi WHERE TC_MATC = ?";
        $stmt = $conn->prepare($check_max_sql);
        $stmt->bind_param("s", $criteria_id);
        $stmt->execute();
        $max_result = $stmt->get_result();
        
        if ($max_result->num_rows === 0) {
            throw new Exception("Tiêu chí không tồn tại: " . $criteria_id);
        }
        
        $max_score = $max_result->fetch_assoc()['TC_DIEMTOIDA'];
        if ($score > $max_score) {
            throw new Exception("Điểm không được vượt quá điểm tối đa ($max_score) cho tiêu chí $criteria_id");
        }
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    // Xóa điểm cũ của thành viên này
    $delete_old_sql = "DELETE FROM chi_tiet_diem_danh_gia WHERE QD_SO = ? AND GV_MAGV = ?";
    $stmt = $conn->prepare($delete_old_sql);
    $stmt->bind_param("ss", $decision_id, $member_id);
    $stmt->execute();
    
    // Thêm điểm mới
    $insert_score_sql = "INSERT INTO chi_tiet_diem_danh_gia (CTDDG_MA, QD_SO, GV_MAGV, TC_MATC, CTDDG_DIEM, CTDDG_GHICHU) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_score_sql);
    
    $total_score = 0;
    foreach ($scores as $score_data) {
        $criteria_id = $score_data['criteriaId'];
        $score = (float)$score_data['score'];
        $note = $score_data['note'] ?? '';
        
        // Tạo mã chi tiết đánh giá
        $detail_id = 'DG' . date('ymd') . sprintf('%04d', rand(1000, 9999));
        
        $stmt->bind_param("ssssds", $detail_id, $decision_id, $member_id, $criteria_id, $score, $note);
        $stmt->execute();
        
        $total_score += $score;
    }
    
    // Cập nhật trạng thái và tổng điểm cho thành viên
    $update_member_sql = "UPDATE thanh_vien_hoi_dong 
                         SET TV_DIEM = ?, 
                             TV_TRANGTHAI = 'Đã hoàn thành',
                             TV_NGAYDANHGIA = NOW()
                         WHERE QD_SO = ? AND GV_MAGV = ?";
    $stmt = $conn->prepare($update_member_sql);
    $stmt->bind_param("dss", $total_score, $decision_id, $member_id);
    $stmt->execute();
    
    // Ghi lại tiến độ
    $progress_title = "Cập nhật điểm đánh giá chi tiết cho thành viên hội đồng";
    $progress_content = "Đã cập nhật điểm đánh giá chi tiết cho thành viên hội đồng.\n\n";
    $progress_content .= "Lý do: " . $update_reason . "\n\n";
    $progress_content .= "Chi tiết điểm:\n";
    
    foreach ($scores as $score_data) {
        $progress_content .= "- " . $score_data['criteriaId'] . ": " . $score_data['score'] . " điểm\n";
    }
    
    $progress_content .= "\nTổng điểm: " . number_format($total_score, 2) . " điểm";
    
    // Tạo mã tiến độ mới
    $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));
    
    $progress_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 100)";
    $stmt = $conn->prepare($progress_sql);
    $stmt->bind_param("sssss", $progress_id, $project_id, $user_id, $progress_title, $progress_content);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật điểm đánh giá chi tiết thành công',
        'data' => [
            'totalScore' => $total_score,
            'scoresCount' => count($scores)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
    // Log lỗi
    error_log("Save detailed evaluation error: " . $e->getMessage());
}

ob_end_clean();
$conn->close();
?>
