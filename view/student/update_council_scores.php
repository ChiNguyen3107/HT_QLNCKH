<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không được phép');
    }

    $project_id = $_POST['project_id'] ?? '';
    $council_scores = $_POST['council_scores'] ?? [];

    if (empty($project_id)) {
        throw new Exception('Thiếu mã đề tài');
    }

    // Kiểm tra quyền truy cập
    $check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
    $stmt = $conn->prepare($check_access_sql);
    $stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
    $stmt->execute();
    $access_result = $stmt->get_result();
    $has_access = ($access_result->num_rows > 0);
    $user_role = $has_access ? $access_result->fetch_assoc()['CTTG_VAITRO'] : '';

    if (!$has_access || $user_role !== 'Chủ nhiệm') {
        throw new Exception('Bạn không có quyền cập nhật điểm hội đồng');
    }

    // Lấy thông tin quyết định
    $decision_sql = "SELECT qd.QD_SO FROM quyet_dinh_nghiem_thu qd 
                     WHERE qd.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";
    $stmt = $conn->prepare($decision_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $decision_result = $stmt->get_result();
    
    if ($decision_result->num_rows === 0) {
        throw new Exception('Không tìm thấy quyết định nghiệm thu cho đề tài này');
    }
    
    $decision = $decision_result->fetch_assoc();

    $conn->autocommit(false);

    $updated_count = 0;
    $total_score = 0;
    $total_members = 0;

    foreach ($council_scores as $member_id => $score) {
        if (!is_numeric($score) || $score < 0 || $score > 10) {
            continue; // Bỏ qua điểm không hợp lệ
        }

        // Cập nhật điểm cho thành viên hội đồng
        $update_sql = "UPDATE thanh_vien_hoi_dong 
                       SET TV_DIEM = ?, TV_NGAYCAPNHAT = NOW() 
                       WHERE TV_MA = ? AND QD_SO = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("dis", $score, $member_id, $decision['QD_SO']);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $updated_count++;
            $total_score += $score;
            $total_members++;
        }
    }

    if ($updated_count > 0) {
        // Tính điểm trung bình và xếp loại
        $average_score = $total_members > 0 ? round($total_score / $total_members, 2) : 0;
        
        $xep_loai = '';
        if ($average_score >= 9.0) {
            $xep_loai = 'Xuất sắc';
        } elseif ($average_score >= 8.0) {
            $xep_loai = 'Tốt';
        } elseif ($average_score >= 7.0) {
            $xep_loai = 'Khá';
        } elseif ($average_score >= 5.0) {
            $xep_loai = 'Trung bình';
        } else {
            $xep_loai = 'Yếu';
        }

        // Cập nhật biên bản với tổng điểm và xếp loại
        $update_bb_sql = "UPDATE bien_ban 
                          SET BB_TONGDIEM = ?, BB_XEPLOAI = ?, BB_NGAYCAPNHAT = NOW() 
                          WHERE QD_SO = ?";
        $stmt = $conn->prepare($update_bb_sql);
        $stmt->bind_param("dss", $average_score, $xep_loai, $decision['QD_SO']);
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Đã cập nhật điểm cho $updated_count thành viên hội đồng",
        'updated_count' => $updated_count,
        'average_score' => $average_score ?? 0,
        'xep_loai' => $xep_loai ?? ''
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
