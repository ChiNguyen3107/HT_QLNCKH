<?php
/**
 * Các hàm kiểm tra điều kiện hoàn thành đề tài
 */

/**
 * Kiểm tra xem tất cả thành viên hội đồng đã có điểm chưa
 */
function checkAllCouncilMembersHaveScores($project_id, $conn) {
    try {
        // Lấy quyết định nghiệm thu của đề tài
        $decision_sql = "SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND QD_SO IS NOT NULL";
        $stmt = $conn->prepare($decision_sql);
        if (!$stmt) {
            error_log("Failed to prepare decision_sql: " . $conn->error);
            return false;
        }
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $decision_result = $stmt->get_result();

        if ($decision_result->num_rows === 0) {
            return false; // Chưa có quyết định nghiệm thu
        }

        $decision_row = $decision_result->fetch_assoc();
        $decision_id = $decision_row['QD_SO'];

        // Đếm tổng số thành viên hội đồng
        $total_members_sql = "SELECT COUNT(*) as total FROM thanh_vien_hoi_dong WHERE QD_SO = ?";
        $stmt = $conn->prepare($total_members_sql);
        if (!$stmt) {
            error_log("Failed to prepare total_members_sql: " . $conn->error);
            return false;
        }
        $stmt->bind_param("s", $decision_id);
        $stmt->execute();
        $total_result = $stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $total_members = $total_row['total'];

        if ($total_members === 0) {
            return false; // Không có thành viên hội đồng nào
        }

        // Đếm số thành viên đã có điểm (điểm >= 0 và có nhận xét)
        $scored_members_sql = "SELECT COUNT(*) as scored 
                              FROM thanh_vien_hoi_dong 
                              WHERE QD_SO = ? 
                              AND TV_DIEM IS NOT NULL 
                              AND TV_DIEM >= 0 
                              AND TV_NHANXET IS NOT NULL 
                              AND TV_NHANXET != ''";
        $stmt = $conn->prepare($scored_members_sql);
        if (!$stmt) {
            error_log("Failed to prepare scored_members_sql: " . $conn->error);
            return false;
        }
        $stmt->bind_param("s", $decision_id);
        $stmt->execute();
        $scored_result = $stmt->get_result();
        $scored_row = $scored_result->fetch_assoc();
        $scored_members = $scored_row['scored'];

        // Trả về true nếu tất cả thành viên đã có điểm
        return ($scored_members >= $total_members && $total_members > 0);

    } catch (Exception $e) {
        error_log("Error checking council members scores: " . $e->getMessage());
        return false;
    }
}

/**
 * Kiểm tra điều kiện đầy đủ để hoàn thành đề tài
 */
function checkProjectCompletionConditions($project_id, $conn) {
    try {
        // 1. Kiểm tra có biên bản nghiệm thu với kết quả đạt
        $report_sql = "SELECT bb.BB_XEPLOAI 
                      FROM bien_ban bb
                      INNER JOIN de_tai_nghien_cuu dt ON bb.QD_SO = dt.QD_SO
                      WHERE dt.DT_MADT = ? 
                      AND bb.BB_XEPLOAI IN ('Xuất sắc', 'Tốt', 'Khá', 'Đạt')";
        
        $stmt = $conn->prepare($report_sql);
        if (!$stmt) {
            error_log("Failed to prepare report_sql: " . $conn->error);
            return [
                'can_complete' => false,
                'reason' => 'Lỗi hệ thống khi kiểm tra biên bản nghiệm thu'
            ];
        }
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $report_result = $stmt->get_result();

        if ($report_result->num_rows === 0) {
            return [
                'can_complete' => false,
                'reason' => 'Chưa có biên bản nghiệm thu với kết quả đạt'
            ];
        }

        // 2. Kiểm tra tất cả thành viên hội đồng đã có điểm
        if (!checkAllCouncilMembersHaveScores($project_id, $conn)) {
            // Lấy thông tin chi tiết về thành viên chưa có điểm
            $decision_sql = "SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
            $stmt = $conn->prepare($decision_sql);
            if (!$stmt) {
                error_log("Failed to prepare decision_sql in completion check: " . $conn->error);
                return [
                    'can_complete' => false,
                    'reason' => 'Lỗi hệ thống khi kiểm tra quyết định nghiệm thu'
                ];
            }
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $decision_result = $stmt->get_result();
            
            if ($decision_result->num_rows > 0) {
                $decision_row = $decision_result->fetch_assoc();
                $decision_id = $decision_row['QD_SO'];
                
                // Lấy danh sách thành viên chưa có điểm
                $missing_scores_sql = "SELECT tv.GV_MAGV, gv.GV_HOTEN, tv.TV_VAITRO
                                      FROM thanh_vien_hoi_dong tv
                                      INNER JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                                      WHERE tv.QD_SO = ? 
                                      AND (tv.TV_DIEM IS NULL OR tv.TV_NHANXET IS NULL OR tv.TV_NHANXET = '')";
                
                $stmt = $conn->prepare($missing_scores_sql);
                $stmt->bind_param("s", $decision_id);
                $stmt->execute();
                $missing_result = $stmt->get_result();
                
                $missing_members = [];
                while ($row = $missing_result->fetch_assoc()) {
                    $missing_members[] = $row['GV_HOTEN'] . ' (' . $row['TV_VAITRO'] . ')';
                }
                
                return [
                    'can_complete' => false,
                    'reason' => 'Chưa đủ điểm đánh giá từ tất cả thành viên hội đồng',
                    'missing_members' => $missing_members
                ];
            }
            
            return [
                'can_complete' => false,
                'reason' => 'Chưa đủ điểm đánh giá từ tất cả thành viên hội đồng'
            ];
        }

        // Tất cả điều kiện đã đạt
        return [
            'can_complete' => true,
            'reason' => 'Đã đủ điều kiện hoàn thành đề tài'
        ];

    } catch (Exception $e) {
        error_log("Error checking project completion conditions: " . $e->getMessage());
        return [
            'can_complete' => false,
            'reason' => 'Lỗi hệ thống khi kiểm tra điều kiện hoàn thành'
        ];
    }
}

/**
 * Cập nhật trạng thái đề tài thành "Đã hoàn thành" nếu đủ điều kiện
 */
function updateProjectStatusIfComplete($project_id, $conn) {
    $conditions = checkProjectCompletionConditions($project_id, $conn);
    
    if ($conditions['can_complete']) {
        $update_sql = "UPDATE de_tai_nghien_cuu SET DT_TRANGTHAI = 'Đã hoàn thành' WHERE DT_MADT = ?";
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            error_log("Failed to prepare update_sql in updateProjectStatusIfComplete: " . $conn->error);
            return false;
        }
        $stmt->bind_param("s", $project_id);
        
        if ($stmt->execute()) {
            error_log("Project $project_id automatically completed - all conditions met");
            return true;
        } else {
            error_log("Failed to update project status: " . $stmt->error);
            return false;
        }
    }
    
    return false;
}

/**
 * Lấy thông tin chi tiết về tiến độ hoàn thành đề tài
 */
function getProjectCompletionDetails($project_id, $conn) {
    $details = [
        'has_passing_report' => false,
        'all_members_scored' => false,
        'total_members' => 0,
        'scored_members' => 0,
        'missing_members' => [],
        'can_complete' => false
    ];

    try {
        // Kiểm tra biên bản nghiệm thu
        $report_sql = "SELECT bb.BB_XEPLOAI, bb.BB_NGAYNGHIEMTHU
                      FROM bien_ban bb
                      INNER JOIN de_tai_nghien_cuu dt ON bb.QD_SO = dt.QD_SO
                      WHERE dt.DT_MADT = ?";
        
        $stmt = $conn->prepare($report_sql);
        if (!$stmt) {
            error_log("Failed to prepare report_sql in getCompletionDetails: " . $conn->error);
            return $details;
        }
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $report_result = $stmt->get_result();

        if ($report_result->num_rows > 0) {
            $report_row = $report_result->fetch_assoc();
            $details['has_passing_report'] = in_array($report_row['BB_XEPLOAI'], ['Xuất sắc', 'Tốt', 'Khá', 'Đạt']);
            $details['report_grade'] = $report_row['BB_XEPLOAI'];
            $details['report_date'] = $report_row['BB_NGAYNGHIEMTHU'];
        }

        // Kiểm tra thành viên hội đồng
        $decision_sql = "SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
        $stmt = $conn->prepare($decision_sql);
        if (!$stmt) {
            error_log("Failed to prepare decision_sql in getCompletionDetails: " . $conn->error);
            return $details;
        }
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $decision_result = $stmt->get_result();

        if ($decision_result->num_rows > 0) {
            $decision_row = $decision_result->fetch_assoc();
            $decision_id = $decision_row['QD_SO'];

            // Đếm tổng số thành viên
            $total_sql = "SELECT COUNT(*) as total FROM thanh_vien_hoi_dong WHERE QD_SO = ?";
            $stmt = $conn->prepare($total_sql);
            if (!$stmt) {
                error_log("Failed to prepare total_sql in getCompletionDetails: " . $conn->error);
                return $details;
            }
            $stmt->bind_param("s", $decision_id);
            $stmt->execute();
            $total_result = $stmt->get_result();
            $total_row = $total_result->fetch_assoc();
            $details['total_members'] = $total_row['total'];

            // Đếm số thành viên đã có điểm
            $scored_sql = "SELECT COUNT(*) as scored 
                          FROM thanh_vien_hoi_dong 
                          WHERE QD_SO = ? 
                          AND TV_DIEM IS NOT NULL 
                          AND TV_DIEM >= 0 
                          AND TV_NHANXET IS NOT NULL 
                          AND TV_NHANXET != ''";
            $stmt = $conn->prepare($scored_sql);
            if (!$stmt) {
                error_log("Failed to prepare scored_sql in getCompletionDetails: " . $conn->error);
                return $details;
            }
            $stmt->bind_param("s", $decision_id);
            $stmt->execute();
            $scored_result = $stmt->get_result();
            $scored_row = $scored_result->fetch_assoc();
            $details['scored_members'] = $scored_row['scored'];

            // Lấy danh sách thành viên chưa có điểm
            $missing_sql = "SELECT tv.GV_MAGV, gv.GV_HOTEN, tv.TV_VAITRO
                           FROM thanh_vien_hoi_dong tv
                           INNER JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                           WHERE tv.QD_SO = ? 
                           AND (tv.TV_DIEM IS NULL OR tv.TV_NHANXET IS NULL OR tv.TV_NHANXET = '')";
            
            $stmt = $conn->prepare($missing_sql);
            if (!$stmt) {
                error_log("Failed to prepare missing_sql in getCompletionDetails: " . $conn->error);
                return $details;
            }
            $stmt->bind_param("s", $decision_id);
            $stmt->execute();
            $missing_result = $stmt->get_result();
            
            while ($row = $missing_result->fetch_assoc()) {
                $details['missing_members'][] = [
                    'id' => $row['GV_MAGV'],
                    'name' => $row['GV_HOTEN'],
                    'role' => $row['TV_VAITRO']
                ];
            }

            $details['all_members_scored'] = ($details['scored_members'] >= $details['total_members'] && $details['total_members'] > 0);
        }

        $details['can_complete'] = $details['has_passing_report'] && $details['all_members_scored'];

    } catch (Exception $e) {
        error_log("Error getting project completion details: " . $e->getMessage());
    }

    return $details;
}
?>
