<?php
session_start();

// Simulate student session for testing
if (!isset($_SESSION['user_id']) && isset($_SERVER['HTTP_X_STUDENT_ID'])) {
    $_SESSION['user_id'] = $_SERVER['HTTP_X_STUDENT_ID'];
    $_SESSION['role'] = 'student';
}

require_once '../../include/connect.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$debug_log = [];

try {
    $debug_log[] = "Starting evaluation update process";
    
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
        throw new Exception('Vui lòng đăng nhập. Session: ' . json_encode($_SESSION));
    }
    
    $debug_log[] = "Session check passed";

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không hợp lệ');
    }
    
    $debug_log[] = "Method check passed";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối database: " . $conn->connect_error);
    }
    
    $debug_log[] = "Database connection established";

    $student_id = $_SESSION['user_id'];
    $member_id = $_POST['member_id'] ?? '';
    $project_id = $_POST['project_id'] ?? '';
    $criteria_ids = $_POST['criteria_id'] ?? [];
    $scores = $_POST['score'] ?? [];
    $comments = $_POST['criteria_comments'] ?? [];
    $overall_comment = $_POST['overall_comment'] ?? '';
    $is_completed = ($_POST['is_completed'] ?? '0') === '1';

    $debug_log[] = "Parameters extracted: student_id=$student_id, member_id=$member_id, project_id=$project_id";
    $debug_log[] = "Criteria count: " . count($criteria_ids) . ", Scores count: " . count($scores);

    // Validate input
    if (empty($member_id) || empty($project_id)) {
        throw new Exception('Thiếu thông tin thành viên hoặc dự án');
    }

    if (empty($criteria_ids) || empty($scores)) {
        throw new Exception('Thiếu thông tin tiêu chí đánh giá');
    }
    
    $debug_log[] = "Input validation passed";

    // Kiểm tra quyền của sinh viên
    $check_permission = $conn->prepare("
        SELECT cttg.DT_MADT 
        FROM chi_tiet_tham_gia cttg 
        WHERE cttg.DT_MADT = ? AND cttg.SV_MASV = ?
    ");
    
    if (!$check_permission) {
        throw new Exception('Lỗi prepare permission query: ' . $conn->error);
    }
    
    $check_permission->bind_param("ss", $project_id, $student_id);
    $check_permission->execute();
    $permission_result = $check_permission->get_result();
    
    $debug_log[] = "Permission query executed, result count: " . $permission_result->num_rows;
    
    if ($permission_result->num_rows === 0) {
        throw new Exception('Bạn không có quyền đánh giá dự án này');
    }
    
    $debug_log[] = "Permission check passed";

    // Bắt đầu transaction
    $conn->autocommit(false);
    $debug_log[] = "Transaction started";

    // Tính tổng điểm và chuẩn bị JSON
    $total_score = 0;
    $criteria_scores_json = [];
    
    for ($i = 0; $i < count($criteria_ids); $i++) {
        $criteria_id = $criteria_ids[$i];
        $score = floatval($scores[$i] ?? 0);
        $comment = $comments[$i] ?? '';
        
        if ($score > 0) {
            $total_score += $score;
            $criteria_scores_json[$criteria_id] = [
                'score' => $score,
                'comment' => $comment
            ];
        }
    }
    
    $debug_log[] = "Score calculation completed. Total: $total_score";

    // Cập nhật bảng thanh_vien_hoi_dong với cấu trúc hiện có
    $criteria_scores_json_str = json_encode($criteria_scores_json, JSON_UNESCAPED_UNICODE);
    
    // Tìm thành viên hội đồng dựa trên member_id và project_id
    $find_member = $conn->prepare("
        SELECT thd.QD_SO, thd.GV_MAGV, thd.TC_MATC 
        FROM thanh_vien_hoi_dong thd
        JOIN quyet_dinh_nghiem_thu qd ON thd.QD_SO = qd.QD_SO
        JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB
        JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
        WHERE thd.GV_MAGV = ? AND dt.DT_MADT = ?
        LIMIT 1
    ");
    
    if (!$find_member) {
        throw new Exception('Lỗi prepare member query: ' . $conn->error);
    }
    
    $find_member->bind_param("ss", $member_id, $project_id);
    $find_member->execute();
    $member_result = $find_member->get_result();
    
    $debug_log[] = "Member search executed, result count: " . $member_result->num_rows;
    
    if ($member_result->num_rows === 0) {
        throw new Exception('Không tìm thấy thông tin thành viên hội đồng');
    }
    
    $member_info = $member_result->fetch_assoc();
    $debug_log[] = "Member found: QD_SO=" . $member_info['QD_SO'] . ", GV_MAGV=" . $member_info['GV_MAGV'];
    
    $update_member = $conn->prepare("
        UPDATE thanh_vien_hoi_dong 
        SET 
            TV_DIEM = ?,
            TV_DANHGIA = ?,
            TV_DIEMCHITIET = ?,
            TV_TRANGTHAI = ?,
            TV_NGAYDANHGIA = NOW()
        WHERE QD_SO = ? AND GV_MAGV = ? AND TC_MATC = ?
    ");
    
    if (!$update_member) {
        throw new Exception('Lỗi prepare update query: ' . $conn->error);
    }
    
    $status = $is_completed ? 'Đã hoàn thành' : 'Đang đánh giá';
    $bind_result = $update_member->bind_param("dssssss", 
        $total_score, 
        $overall_comment, 
        $criteria_scores_json_str, 
        $status,
        $member_info['QD_SO'], 
        $member_info['GV_MAGV'], 
        $member_info['TC_MATC']
    );
    
    if (!$bind_result) {
        throw new Exception('Lỗi bind parameters: ' . $update_member->error);
    }
    
    $debug_log[] = "Parameters bound successfully";
    
    $execute_result = $update_member->execute();
    
    if (!$execute_result) {
        throw new Exception('Lỗi execute update: ' . $update_member->error);
    }
    
    $affected_rows = $update_member->affected_rows;
    $debug_log[] = "Update executed successfully, affected rows: $affected_rows";

    // Commit transaction
    $conn->commit();
    $debug_log[] = "Transaction committed";
    
    echo json_encode([
        'success' => true, 
        'message' => $is_completed ? 'Hoàn tất đánh giá thành công!' : 'Lưu nháp thành công!',
        'total_score' => $total_score,
        'is_completed' => $is_completed,
        'affected_rows' => $affected_rows,
        'debug_log' => $debug_log
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $debug_log[] = "Transaction rolled back";
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug_log' => $debug_log,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Log lỗi
    error_log("Lỗi cập nhật điểm tiêu chí: " . $e->getMessage());
}

if (isset($conn)) {
    $conn->close();
}
?>
