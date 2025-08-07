<?php
/**
 * Kiểm tra và cập nhật trạng thái hoàn thành của đề tài
 * Đề tài chỉ được hoàn thành khi:
 * 1. Có quyết định nghiệm thu
 * 2. Tất cả thành viên hội đồng đã được đánh giá (có điểm)
 * 3. Có ít nhất 1 file đánh giá được tải lên
 * 4. Có biên bản nghiệm thu với xếp loại
 */

require_once 'include/database.php';

function checkProjectCompletionRequirements($project_id, $conn) {
    $requirements = [
        'has_decision' => false,
        'all_members_evaluated' => false,
        'has_evaluation_files' => false,
        'has_acceptance_report' => false,
        'details' => []
    ];
    
    try {
        // 1. Kiểm tra có quyết định nghiệm thu
        $decision_sql = "SELECT QD_SO, QD_NGAY FROM quyet_dinh_nghiem_thu WHERE DT_MADT = ?";
        $stmt = $conn->prepare($decision_sql);
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $decision = $stmt->get_result()->fetch_assoc();
        
        if ($decision) {
            $requirements['has_decision'] = true;
            $requirements['details']['decision'] = $decision['QD_SO'];
            
            // 2. Kiểm tra tất cả thành viên hội đồng đã được đánh giá
            $members_sql = "SELECT 
                                COUNT(*) as total_members,
                                COUNT(CASE WHEN TV_DIEM IS NOT NULL AND TV_DIEM >= 0 THEN 1 END) as evaluated_members,
                                AVG(CASE WHEN TV_DIEM IS NOT NULL AND TV_DIEM >= 0 THEN TV_DIEM END) as avg_score
                            FROM thanh_vien_hoi_dong 
                            WHERE QD_SO = ?";
            $stmt = $conn->prepare($members_sql);
            $stmt->bind_param("s", $decision['QD_SO']);
            $stmt->execute();
            $members_result = $stmt->get_result()->fetch_assoc();
            
            $requirements['details']['total_members'] = $members_result['total_members'];
            $requirements['details']['evaluated_members'] = $members_result['evaluated_members'];
            $requirements['details']['avg_score'] = $members_result['avg_score'];
            
            if ($members_result['total_members'] > 0 && 
                $members_result['evaluated_members'] == $members_result['total_members']) {
                $requirements['all_members_evaluated'] = true;
            }
            
            // 3. Kiểm tra có file đánh giá
            $files_sql = "SELECT COUNT(*) as file_count FROM file_dinh_kem 
                         WHERE QD_SO = ? AND FDG_LOAI = 'evaluation'";
            $stmt = $conn->prepare($files_sql);
            $stmt->bind_param("s", $decision['QD_SO']);
            $stmt->execute();
            $files_result = $stmt->get_result()->fetch_assoc();
            
            $requirements['details']['evaluation_files'] = $files_result['file_count'];
            if ($files_result['file_count'] > 0) {
                $requirements['has_evaluation_files'] = true;
            }
            
            // 4. Kiểm tra có biên bản nghiệm thu với xếp loại
            $report_sql = "SELECT BB_SOBB, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM 
                          FROM bien_ban_nghiem_thu 
                          WHERE QD_SO = ? AND BB_XEPLOAI IS NOT NULL";
            $stmt = $conn->prepare($report_sql);
            $stmt->bind_param("s", $decision['QD_SO']);
            $stmt->execute();
            $report_result = $stmt->get_result()->fetch_assoc();
            
            if ($report_result) {
                $requirements['has_acceptance_report'] = true;
                $requirements['details']['report'] = [
                    'number' => $report_result['BB_SOBB'],
                    'date' => $report_result['BB_NGAYNGHIEMTHU'],
                    'grade' => $report_result['BB_XEPLOAI'],
                    'score' => $report_result['BB_TONGDIEM']
                ];
            }
        }
        
    } catch (Exception $e) {
        error_log("Error checking project completion: " . $e->getMessage());
        $requirements['error'] = $e->getMessage();
    }
    
    // Tính tổng điểm hoàn thành
    $completion_score = 0;
    if ($requirements['has_decision']) $completion_score += 25;
    if ($requirements['all_members_evaluated']) $completion_score += 35;
    if ($requirements['has_evaluation_files']) $completion_score += 20;
    if ($requirements['has_acceptance_report']) $completion_score += 20;
    
    $requirements['completion_score'] = $completion_score;
    $requirements['is_ready_for_completion'] = $completion_score >= 100;
    
    return $requirements;
}

function updateProjectStatus($project_id, $new_status, $conn, $reason = '') {
    try {
        // Cập nhật trạng thái đề tài
        $update_sql = "UPDATE de_tai SET DT_TRANGTHAI = ? WHERE DT_MADT = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ss", $new_status, $project_id);
        $success = $stmt->execute();
        
        if ($success) {
            // Ghi log thay đổi trạng thái
            $log_sql = "INSERT INTO project_status_log (project_id, old_status, new_status, change_reason, change_date) 
                       SELECT ?, DT_TRANGTHAI, ?, ?, NOW() FROM de_tai WHERE DT_MADT = ?";
            
            // Tạo bảng log nếu chưa có
            $create_log_table = "CREATE TABLE IF NOT EXISTS project_status_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id VARCHAR(50) NOT NULL,
                old_status VARCHAR(50),
                new_status VARCHAR(50) NOT NULL,
                change_reason TEXT,
                change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_project_id (project_id),
                INDEX idx_change_date (change_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $conn->query($create_log_table);
            
            $stmt = $conn->prepare($log_sql);
            $stmt->bind_param("ssss", $project_id, $new_status, $reason, $project_id);
            $stmt->execute();
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error updating project status: " . $e->getMessage());
        return false;
    }
}

function autoCheckProjectCompletion($project_id, $conn) {
    // Lấy trạng thái hiện tại
    $current_status_sql = "SELECT DT_TRANGTHAI FROM de_tai WHERE DT_MADT = ?";
    $stmt = $conn->prepare($current_status_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $current_status = $stmt->get_result()->fetch_assoc()['DT_TRANGTHAI'];
    
    // Chỉ kiểm tra nếu đề tài đang ở trạng thái "Đang thực hiện"
    if ($current_status !== 'Đang thực hiện') {
        return [
            'changed' => false,
            'reason' => 'Đề tài không ở trạng thái "Đang thực hiện"',
            'current_status' => $current_status
        ];
    }
    
    $requirements = checkProjectCompletionRequirements($project_id, $conn);
    
    if ($requirements['is_ready_for_completion']) {
        $reason = "Tự động hoàn thành: Đã đáp ứng đầy đủ các yêu cầu - " .
                 "Quyết định nghiệm thu: " . $requirements['details']['decision'] . ", " .
                 "Đánh giá: " . $requirements['details']['evaluated_members'] . "/" . $requirements['details']['total_members'] . " thành viên, " .
                 "File đánh giá: " . $requirements['details']['evaluation_files'] . " file, " .
                 "Biên bản: " . ($requirements['details']['report']['grade'] ?? 'Có');
        
        $success = updateProjectStatus($project_id, 'Đã hoàn thành', $conn, $reason);
        
        return [
            'changed' => $success,
            'new_status' => 'Đã hoàn thành',
            'reason' => $reason,
            'requirements' => $requirements
        ];
    }
    
    return [
        'changed' => false,
        'reason' => 'Chưa đáp ứng đầy đủ yêu cầu hoàn thành',
        'completion_score' => $requirements['completion_score'],
        'requirements' => $requirements
    ];
}

// API endpoint để kiểm tra trạng thái hoàn thành
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_completion'])) {
    header('Content-Type: application/json');
    
    if (!isset($_GET['project_id'])) {
        echo json_encode(['error' => 'Missing project_id']);
        exit;
    }
    
    $project_id = $_GET['project_id'];
    $requirements = checkProjectCompletionRequirements($project_id, $conn);
    
    echo json_encode([
        'success' => true,
        'project_id' => $project_id,
        'requirements' => $requirements,
        'ready_for_completion' => $requirements['is_ready_for_completion']
    ]);
    exit;
}

// API endpoint để tự động cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_complete'])) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['project_id'])) {
        echo json_encode(['error' => 'Missing project_id']);
        exit;
    }
    
    $project_id = $_POST['project_id'];
    $result = autoCheckProjectCompletion($project_id, $conn);
    
    echo json_encode([
        'success' => true,
        'project_id' => $project_id,
        'result' => $result
    ]);
    exit;
}
?>
