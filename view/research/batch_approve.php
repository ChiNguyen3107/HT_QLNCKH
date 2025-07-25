<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\batch_approve.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['batch_action']) && isset($_POST['project_ids'])) {
    $batch_action = $_POST['batch_action'];
    $project_ids = $_POST['project_ids'];
    $batch_comment = $_POST['batch_comment'] ?? '';
    
    if (empty($project_ids)) {
        header("Location: review_projects.php?error=1&message=Không có đề tài nào được chọn");
        exit;
    }
    
    if ($batch_action === 'approve') {
        $new_status = 'Đang tiến hành';
        $action_text = 'phê duyệt';
        $notification_type = 'success';
    } else {
        $new_status = 'Đã từ chối';
        $action_text = 'từ chối';
        $notification_type = 'danger';
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Chuẩn bị truy vấn cập nhật trạng thái đề tài
        $update_sql = "UPDATE de_tai_nghien_cuu 
                      SET DT_TRANGTHAI = ?, 
                          DT_GHICHU = CONCAT(IFNULL(DT_GHICHU, ''), '\n', ?),
                          DT_NGUOICAPNHAT = ?,
                          DT_NGAYCAPNHAT = NOW()
                      WHERE DT_MADT = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        // Chuẩn bị truy vấn ghi log
        $log_sql = "INSERT INTO log_hoat_dong 
                  (LHD_DOITUONG, LHD_DOITUONG_ID, LHD_HANHDONG, LHD_NOIDUNG, LHD_NGUOITHAOTAC, LHD_THOIGIAN) 
                  VALUES ('de_tai', ?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        
        // Chuẩn bị truy vấn tạo thông báo
        $notification_sql = "INSERT INTO thong_bao 
                          (NGUOI_NHAN, TB_NOIDUNG, TB_LOAI, TB_LINK, TB_TRANGTHAI, TB_NGAYTAO) 
                          VALUES (?, ?, ?, ?, 'chưa đọc', NOW())";
        $notification_stmt = $conn->prepare($notification_sql);
        
        // Lấy thông tin đề tài và giảng viên để gửi thông báo
        $info_sql = "SELECT dt.DT_MADT, dt.DT_TENDT, gv.GV_MAGV 
                   FROM de_tai_nghien_cuu dt 
                   LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                   WHERE dt.DT_MADT = ?";
        $info_stmt = $conn->prepare($info_sql);
        
        // Thực hiện cập nhật cho từng đề tài
        $success_count = 0;
        $failed_ids = [];
        
        foreach ($project_ids as $project_id) {
            // Lấy thông tin đề tài
            $info_stmt->bind_param("s", $project_id);
            $info_stmt->execute();
            $result = $info_stmt->get_result();
            $project = $result->fetch_assoc();
            
            if (!$project) {
                $failed_ids[] = $project_id;
                continue;
            }
            
            // Cập nhật trạng thái đề tài
            $update_stmt->bind_param("ssss", $new_status, $batch_comment, $_SESSION['user_id'], $project_id);
            if (!$update_stmt->execute()) {
                $failed_ids[] = $project_id;
                continue;
            }
            
            // Ghi log hoạt động
            $log_content = "Đề tài [{$project['DT_MADT']}] {$project['DT_TENDT']} đã được $action_text trong thao tác hàng loạt" . ($batch_comment ? ". Ghi chú: $batch_comment" : "");
            $log_stmt->bind_param("ssss", $project_id, $action_text, $log_content, $_SESSION['user_id']);
            $log_stmt->execute();
            
            // Tạo thông báo cho giảng viên nếu có
            if ($project['GV_MAGV']) {
                $notification_content = "Đề tài của bạn ({$project['DT_TENDT']}) đã được $action_text" . ($batch_comment ? ". Ghi chú: $batch_comment" : "");
                $notification_link = "/NLNganh/view/teacher/view_project.php?id={$project['DT_MADT']}";
                $notification_stmt->bind_param("ssss", $project['GV_MAGV'], $notification_content, $notification_type, $notification_link);
                $notification_stmt->execute();
            }
            
            $success_count++;
        }
        
        // Commit transaction nếu có ít nhất một cập nhật thành công
        $conn->commit();
        
        // Thông báo kết quả
        if ($success_count > 0) {
            $message = "Đã $action_text thành công $success_count đề tài";
            if (!empty($failed_ids)) {
                $message .= ". " . count($failed_ids) . " đề tài không thể cập nhật.";
            }
            header("Location: review_projects.php?success=1&message=" . urlencode($message) . "&action=$batch_action");
        } else {
            header("Location: review_projects.php?error=1&message=Không thể cập nhật bất kỳ đề tài nào");
        }
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $conn->rollback();
        
        // Thông báo lỗi
        header("Location: review_projects.php?error=1&message=" . urlencode($e->getMessage()));
    }
    
    exit;
} else {
    // Không có request hợp lệ
    header("Location: review_projects.php");
    exit;
}
?>
