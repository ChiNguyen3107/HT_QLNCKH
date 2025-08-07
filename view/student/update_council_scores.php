<?php
/**
 * File: update_council_scores.php
 * Mục đích: Cập nhật điểm đánh giá cho tất cả thành viên hội đồng
 * Tạo ngày: 05/08/2025
 */

// Bắt đầu output buffering để tránh lỗi header
ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $project_id = $_POST['project_id'] ?? '';
        $decision_id = $_POST['decision_id'] ?? '';
        $member_scores = $_POST['member_scores'] ?? [];
        
        // Validate dữ liệu đầu vào
        $errors = [];
        
        if (empty($project_id)) $errors[] = "Mã đề tài không hợp lệ";
        if (empty($decision_id)) $errors[] = "Mã quyết định không hợp lệ";
        if (empty($member_scores)) $errors[] = "Chưa có điểm nào được nhập";
        
        // Kiểm tra quyền truy cập
        $sql_check_access = "
            SELECT CTTG_VAITRO 
            FROM chi_tiet_tham_gia 
            WHERE DT_MADT = ? AND SV_MASV = ?
        ";
        $stmt_check = $conn->prepare($sql_check_access);
        if (!$stmt_check) {
            throw new Exception("Lỗi prepare access check statement: " . $conn->error);
        }
        
        $stmt_check->bind_param("ss", $project_id, $_SESSION['user_id']);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $access_info = $result_check->fetch_assoc();
        $stmt_check->close();
        
        if (!$access_info || $access_info['CTTG_VAITRO'] !== 'Chủ nhiệm') {
            $errors[] = "Bạn không có quyền cập nhật điểm thành viên hội đồng";
        }
        
        // Validate từng điểm số
        $valid_scores = [];
        foreach ($member_scores as $member_id => $score) {
            if (!empty($score)) {
                $score_value = floatval($score);
                if ($score_value < 0 || $score_value > 100) {
                    $errors[] = "Điểm của thành viên {$member_id} phải từ 0 đến 100";
                } else {
                    $valid_scores[$member_id] = $score_value;
                }
            }
        }
        
        if (empty($valid_scores)) {
            $errors[] = "Không có điểm hợp lệ nào được nhập";
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
            exit();
        }

        $conn->autocommit(FALSE); // Bắt đầu transaction

        $updated_count = 0;
        $score_details = [];

        // Cập nhật điểm cho từng thành viên
        foreach ($valid_scores as $member_id => $score_value) {
            // Lấy thông tin thành viên
            $sql_member_info = "
                SELECT tv.GV_MAGV, tv.TV_HOTEN, tv.TV_VAITRO, tv.TV_DIEM as old_score,
                       CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN_FULL
                FROM thanh_vien_hoi_dong tv
                LEFT JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                WHERE tv.QD_SO = ? AND tv.GV_MAGV = ?
                LIMIT 1
            ";
            
            $stmt_info = $conn->prepare($sql_member_info);
            if (!$stmt_info) {
                throw new Exception("Lỗi prepare member info statement: " . $conn->error);
            }
            
            $stmt_info->bind_param("ss", $decision_id, $member_id);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result();
            $member_info = $result_info->fetch_assoc();
            $stmt_info->close();
            
            if (!$member_info) {
                continue; // Bỏ qua nếu không tìm thấy thành viên
            }
            
            // Cập nhật điểm cho thành viên
            $sql_update_score = "
                UPDATE thanh_vien_hoi_dong 
                SET TV_DIEM = ?,
                    TV_NGAYDANHGIA = NOW()
                WHERE QD_SO = ? AND GV_MAGV = ?
            ";
            
            $stmt_update = $conn->prepare($sql_update_score);
            if (!$stmt_update) {
                throw new Exception("Lỗi prepare update score statement: " . $conn->error);
            }
            
            $stmt_update->bind_param("dss", $score_value, $decision_id, $member_id);
            $result = $stmt_update->execute();
            $stmt_update->close();
            
            if ($result) {
                $updated_count++;
                $member_name = $member_info['TV_HOTEN'] ?: $member_info['GV_HOTEN_FULL'];
                $old_score_text = $member_info['old_score'] !== null ? number_format($member_info['old_score'], 1) : 'N/A';
                $score_details[] = "- {$member_name} ({$member_info['TV_VAITRO']}): {$old_score_text} → {$score_value}/100";
            }
        }

        if ($updated_count == 0) {
            throw new Exception("Không có điểm nào được cập nhật");
        }

        // Tính lại điểm trung bình từ tất cả thành viên có điểm
        $sql_avg_score = "
            SELECT 
                AVG(TV_DIEM) as average_score, 
                COUNT(*) as scored_count,
                MIN(TV_DIEM) as min_score,
                MAX(TV_DIEM) as max_score,
                STDDEV(TV_DIEM) as score_stddev
            FROM thanh_vien_hoi_dong 
            WHERE QD_SO = ? AND TV_DIEM IS NOT NULL AND TV_DIEM >= 0 AND TV_DIEM <= 100
        ";
        
        $stmt_avg = $conn->prepare($sql_avg_score);
        if (!$stmt_avg) {
            throw new Exception("Lỗi prepare average statement: " . $conn->error);
        }
        
        $stmt_avg->bind_param("s", $decision_id);
        $stmt_avg->execute();
        $result_avg = $stmt_avg->get_result();
        $avg_data = $result_avg->fetch_assoc();
        $stmt_avg->close();

        $average_score = null;
        $score_calculation_method = 'none';
        
        if ($avg_data['scored_count'] > 0) {
            $average_score = round($avg_data['average_score'], 2);
            $score_calculation_method = 'average';
            
            // Cập nhật điểm vào bảng bien_ban (nếu đã tồn tại)
            $sql_update_bb = "
                UPDATE bien_ban 
                SET BB_TONGDIEM = ? 
                WHERE QD_SO = ?
            ";
            
            $stmt_update_bb = $conn->prepare($sql_update_bb);
            if ($stmt_update_bb) {
                $stmt_update_bb->bind_param("ds", $average_score, $decision_id);
                $stmt_update_bb->execute();
                $stmt_update_bb->close();
            }
        }

        // Ghi log hoạt động vào tiến độ đề tài
        $log_content = "Đã cập nhật điểm đánh giá thành viên hội đồng:\n\n";
        $log_content .= implode("\n", $score_details) . "\n\n";
        $log_content .= "📊 THỐNG KÊ:\n";
        $log_content .= "- Số thành viên được cập nhật: {$updated_count}\n";
        $log_content .= "- Tổng số thành viên có điểm: {$avg_data['scored_count']}\n";
        
        if ($average_score !== null) {
            $log_content .= "- Điểm trung bình: {$average_score}/100\n";
            if ($avg_data['min_score'] !== null) {
                $log_content .= "- Điểm thấp nhất: " . number_format($avg_data['min_score'], 1) . "/100\n";
            }
            if ($avg_data['max_score'] !== null) {
                $log_content .= "- Điểm cao nhất: " . number_format($avg_data['max_score'], 1) . "/100\n";
            }
        }

        // Lấy ID tiến độ mới
        $sql_max_progress = "SELECT MAX(CAST(SUBSTRING(TDDT_MA, 5) AS UNSIGNED)) as max_id FROM tien_do_de_tai";
        $result_max_progress = $conn->query($sql_max_progress);
        $max_progress = $result_max_progress->fetch_assoc();
        $next_progress_id = ($max_progress['max_id'] ?? 0) + 1;
        $progress_id = 'TDDT' . str_pad($next_progress_id, 6, '0', STR_PAD_LEFT);

        $sql_progress = "
            INSERT INTO tien_do_de_tai 
            (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_PHANTRAMHOANTHANH, TDDT_NGAYCAPNHAT)
            SELECT ?, ?, SV_MASV, ?, ?, 90, NOW()
            FROM chi_tiet_tham_gia 
            WHERE DT_MADT = ? AND CTTG_VAITRO = 'Chủ nhiệm'
            LIMIT 1
        ";
        
        $stmt_progress = $conn->prepare($sql_progress);
        if (!$stmt_progress) {
            throw new Exception("Lỗi prepare progress statement: " . $conn->error);
        }
        
        $progress_title = "Cập nhật điểm thành viên hội đồng";
        $stmt_progress->bind_param("sssss", $progress_id, $project_id, $progress_title, $log_content, $project_id);
        $stmt_progress->execute();
        $stmt_progress->close();

        $conn->commit(); // Commit transaction

        // Thông báo thành công
        $_SESSION['success'] = "✅ Đã cập nhật điểm cho {$updated_count} thành viên hội đồng thành công!";
        
        if ($average_score !== null) {
            $_SESSION['success'] .= "<br><strong>📊 Điểm trung bình hiện tại:</strong> {$average_score}/100";
            $_SESSION['success'] .= " (từ {$avg_data['scored_count']} thành viên)";
        }
        
        header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction
        $_SESSION['error'] = "❌ Lỗi: " . $e->getMessage();
        header("Location: view_project.php?id=" . urlencode($project_id ?? '') . "&tab=report");
        exit();
    } finally {
        // Khôi phục autocommit
        $conn->autocommit(TRUE);
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
