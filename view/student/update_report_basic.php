<?php
/**
 * File: update_report_basic.php
 * Mục đích: Xử lý cập nhật thông tin biên bản nghiệm thu với tính toán điểm chính xác
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
        $report_id = $_POST['report_id'] ?? '';
        $acceptance_date = $_POST['acceptance_date'] ?? '';
        $evaluation_grade = $_POST['evaluation_grade'] ?? '';
        $total_score = $_POST['total_score'] ?? '';

        // Validate dữ liệu đầu vào
        $errors = [];
        
        if (empty($project_id)) $errors[] = "Mã đề tài không hợp lệ";
        if (empty($decision_id)) $errors[] = "Mã quyết định không hợp lệ";
        if (empty($acceptance_date)) $errors[] = "Ngày nghiệm thu không được để trống";
        if (empty($evaluation_grade)) $errors[] = "Xếp loại không được để trống";
        
        // Validate điểm số
        if (!empty($total_score)) {
            $total_score = floatval($total_score);
            if ($total_score < 0 || $total_score > 100) {
                $errors[] = "Tổng điểm phải từ 0 đến 100";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
            exit();
        }

        $conn->autocommit(FALSE); // Bắt đầu transaction

        // 1. Tính toán điểm thực tế từ thành viên hội đồng
        $sql_calculate_score = "
            SELECT 
                COUNT(*) as total_members,
                COUNT(tv.TV_DIEM) as scored_members,
                AVG(tv.TV_DIEM) as average_score,
                MIN(tv.TV_DIEM) as min_score,
                MAX(tv.TV_DIEM) as max_score,
                STDDEV(tv.TV_DIEM) as score_stddev
            FROM thanh_vien_hoi_dong tv
            WHERE tv.QD_SO = ? 
              AND tv.TV_DIEM IS NOT NULL 
              AND tv.TV_DIEM >= 0 
              AND tv.TV_DIEM <= 100
        ";
        
        $stmt_calc = $conn->prepare($sql_calculate_score);
        if (!$stmt_calc) {
            throw new Exception("Lỗi prepare statement: " . $conn->error);
        }
        
        $stmt_calc->bind_param("s", $decision_id);
        $stmt_calc->execute();
        $result_calc = $stmt_calc->get_result();
        $score_stats = $result_calc->fetch_assoc();
        $stmt_calc->close();

        // 2. Áp dụng thuật toán lọc điểm bất thường (nếu có nhiều hơn 2 thành viên)
        $final_score = null;
        $score_method = 'manual'; // manual, average, filtered_average
        
        if ($score_stats['scored_members'] >= 2) {
            if ($score_stats['scored_members'] >= 3 && $score_stats['score_stddev'] > 15) {
                // Lọc bỏ điểm chênh lệch quá lớn
                $sql_filtered = "
                    SELECT AVG(tv.TV_DIEM) as filtered_average
                    FROM thanh_vien_hoi_dong tv
                    WHERE tv.QD_SO = ? 
                      AND tv.TV_DIEM IS NOT NULL 
                      AND ABS(tv.TV_DIEM - ?) <= 15
                ";
                $stmt_filtered = $conn->prepare($sql_filtered);
                if (!$stmt_filtered) {
                    throw new Exception("Lỗi prepare filtered statement: " . $conn->error);
                }
                
                $stmt_filtered->bind_param("sd", $decision_id, $score_stats['average_score']);
                $stmt_filtered->execute();
                $result_filtered = $stmt_filtered->get_result();
                $filtered_result = $result_filtered->fetch_assoc();
                $stmt_filtered->close();
                
                if ($filtered_result['filtered_average']) {
                    $final_score = round($filtered_result['filtered_average'], 2);
                    $score_method = 'filtered_average';
                }
            } else {
                // Sử dụng điểm trung bình thông thường
                $final_score = round($score_stats['average_score'], 2);
                $score_method = 'average';
            }
        }

        // 3. Ưu tiên điểm được nhập thủ công nếu có
        if (!empty($total_score)) {
            $final_score = $total_score;
            $score_method = 'manual';
        }

        // 4. Cập nhật hoặc tạo mới biên bản
        if (!empty($report_id)) {
            // Cập nhật biên bản hiện có
            $sql_update = "
                UPDATE bien_ban 
                SET BB_NGAYNGHIEMTHU = ?,
                    BB_XEPLOAI = ?,
                    BB_TONGDIEM = ?
                WHERE BB_SOBB = ? AND QD_SO = ?
            ";
            
            $stmt_update = $conn->prepare($sql_update);
            if (!$stmt_update) {
                throw new Exception("Lỗi prepare update statement: " . $conn->error);
            }
            
            $stmt_update->bind_param("ssdss", $acceptance_date, $evaluation_grade, $final_score, $report_id, $decision_id);
            $result = $stmt_update->execute();
            $stmt_update->close();
            
            $action = 'cập nhật';
        } else {
            // Tạo mới biên bản
            // Tạo mã biên bản mới
            $sql_max_id = "SELECT MAX(CAST(SUBSTRING(BB_SOBB, 3) AS UNSIGNED)) as max_id FROM bien_ban";
            $result_max = $conn->query($sql_max_id);
            $max_result = $result_max->fetch_assoc();
            $next_id = ($max_result['max_id'] ?? 0) + 1;
            $new_report_id = 'BB' . str_pad($next_id, 8, '0', STR_PAD_LEFT);
            
            $sql_insert = "
                INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM)
                VALUES (?, ?, ?, ?, ?)
            ";
            
            $stmt_insert = $conn->prepare($sql_insert);
            if (!$stmt_insert) {
                throw new Exception("Lỗi prepare insert statement: " . $conn->error);
            }
            
            $stmt_insert->bind_param("ssssd", $new_report_id, $decision_id, $acceptance_date, $evaluation_grade, $final_score);
            $result = $stmt_insert->execute();
            $stmt_insert->close();
            
            $action = 'tạo mới';
            $report_id = $new_report_id;
        }

        if (!$result) {
            throw new Exception("Lỗi khi {$action} biên bản nghiệm thu");
        }

        // 5. Ghi log hoạt động vào tiến độ đề tài
        $log_content = "Đã {$action} thông tin biên bản nghiệm thu:\n";
        $log_content .= "- Số biên bản: {$report_id}\n";
        $log_content .= "- Ngày nghiệm thu: " . date('d/m/Y', strtotime($acceptance_date)) . "\n";
        $log_content .= "- Xếp loại: {$evaluation_grade}\n";
        
        if ($final_score !== null) {
            $log_content .= "- Tổng điểm: {$final_score}/100\n";
            $log_content .= "- Phương pháp tính điểm: ";
            switch ($score_method) {
                case 'manual':
                    $log_content .= "Nhập thủ công\n";
                    break;
                case 'average':
                    $log_content .= "Trung bình từ {$score_stats['scored_members']} thành viên hội đồng\n";
                    break;
                case 'filtered_average':
                    $log_content .= "Trung bình đã lọc (loại bỏ điểm bất thường)\n";
                    break;
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
            SELECT ?, ?, SV_MASV, ?, ?, 
                   CASE WHEN ? IN ('Xuất sắc', 'Tốt', 'Khá', 'Đạt') THEN 100 ELSE 90 END,
                   NOW()
            FROM chi_tiet_tham_gia 
            WHERE DT_MADT = ? AND CTTG_VAITRO = 'Chủ nhiệm'
            LIMIT 1
        ";
        
        $stmt_progress = $conn->prepare($sql_progress);
        if (!$stmt_progress) {
            throw new Exception("Lỗi prepare progress statement: " . $conn->error);
        }
        
        $progress_title = "Cập nhật biên bản nghiệm thu";
        $stmt_progress->bind_param("ssssss", $progress_id, $project_id, $progress_title, $log_content, $evaluation_grade, $project_id);
        $stmt_progress->execute();
        $stmt_progress->close();

        // 6. Cập nhật trạng thái đề tài nếu đã hoàn thành
        if (in_array($evaluation_grade, ['Xuất sắc', 'Tốt', 'Khá', 'Đạt'])) {
            $sql_update_project = "
                UPDATE de_tai_nghien_cuu 
                SET DT_TRANGTHAI = 'Đã hoàn thành',
                    DT_NGUOICAPNHAT = ?,
                    DT_NGAYCAPNHAT = NOW()
                WHERE DT_MADT = ?
            ";
            
            $stmt_update_project = $conn->prepare($sql_update_project);
            if (!$stmt_update_project) {
                throw new Exception("Lỗi prepare project update statement: " . $conn->error);
            }
            
            $stmt_update_project->bind_param("ss", $_SESSION['user_id'], $project_id);
            $stmt_update_project->execute();
            $stmt_update_project->close();
        }

        $conn->commit(); // Commit transaction

        // Thông báo thành công
        $_SESSION['success'] = "Đã {$action} thông tin biên bản nghiệm thu thành công!";
        
        // Thêm thông tin chi tiết về điểm
        if ($final_score !== null) {
            $_SESSION['success'] .= "<br><strong>Tổng điểm:</strong> {$final_score}/100";
            if ($score_stats['scored_members'] > 0) {
                $_SESSION['success'] .= " (từ {$score_stats['scored_members']} thành viên hội đồng)";
            }
        }
        
        header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
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
