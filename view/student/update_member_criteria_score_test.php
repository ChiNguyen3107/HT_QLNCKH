<?php
// Test with simulated session
session_start();

// Simulate student session for testing
if (!isset($_SESSION['user_id']) && isset($_SERVER['HTTP_X_STUDENT_ID'])) {
    $_SESSION['user_id'] = $_SERVER['HTTP_X_STUDENT_ID'];
    $_SESSION['role'] = 'student';
}

require_once '../../include/connect.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập - Updated to match login_process.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập', 'session' => $_SESSION]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit();
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối database: " . $conn->connect_error);
    }

    $student_id = $_SESSION['user_id']; // Updated to use user_id from login
    $member_id = $_POST['member_id'] ?? '';
    $project_id = $_POST['project_id'] ?? '';
    $criteria_ids = $_POST['criteria_id'] ?? [];
    $scores = $_POST['score'] ?? [];
    $comments = $_POST['criteria_comments'] ?? [];
    $overall_comment = $_POST['overall_comment'] ?? '';
    $is_completed = ($_POST['is_completed'] ?? '0') === '1';

    // Validate input
    if (empty($member_id) || empty($project_id)) {
        throw new Exception('Thiếu thông tin thành viên hoặc dự án');
    }

    if (empty($criteria_ids) || empty($scores)) {
        throw new Exception('Thiếu thông tin tiêu chí đánh giá');
    }

    // Kiểm tra quyền của sinh viên
    $check_permission = $conn->prepare("
        SELECT cttg.DT_MADT 
        FROM chi_tiet_tham_gia cttg 
        WHERE cttg.DT_MADT = ? AND cttg.SV_MASV = ?
    ");
    $check_permission->bind_param("ss", $project_id, $student_id);
    $check_permission->execute();
    $permission_result = $check_permission->get_result();
    
    if ($permission_result->num_rows === 0) {
        throw new Exception('Bạn không có quyền đánh giá dự án này');
    }

    // Bắt đầu transaction
    $conn->autocommit(false);

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
    $find_member->bind_param("ss", $member_id, $project_id);
    $find_member->execute();
    $member_result = $find_member->get_result();
    
    if ($member_result->num_rows === 0) {
        throw new Exception('Không tìm thấy thông tin thành viên hội đồng');
    }
    
    $member_info = $member_result->fetch_assoc();
    
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
    
    $status = $is_completed ? 'Đã hoàn thành' : 'Đang đánh giá';
    $update_member->bind_param("dsssss", 
        $total_score, 
        $overall_comment, 
        $criteria_scores_json_str, 
        $status,
        $member_info['QD_SO'], 
        $member_info['GV_MAGV'], 
        $member_info['TC_MATC']
    );
    
    if (!$update_member->execute()) {
        throw new Exception('Lỗi cập nhật điểm thành viên: ' . $update_member->error);
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $is_completed ? 'Hoàn tất đánh giá thành công!' : 'Lưu nháp thành công!',
        'total_score' => $total_score,
        'is_completed' => $is_completed,
        'debug' => [
            'student_id' => $student_id,
            'member_id' => $member_id,
            'project_id' => $project_id,
            'qd_so' => $member_info['QD_SO'],
            'criteria_count' => count($criteria_scores_json)
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => [
            'student_id' => $_SESSION['user_id'] ?? 'not set',
            'member_id' => $_POST['member_id'] ?? 'not set',
            'project_id' => $_POST['project_id'] ?? 'not set',
            'file' => __FILE__,
            'line' => $e->getLine()
        ]
    ]);
    
    // Log lỗi
    error_log("Lỗi cập nhật điểm tiêu chí: " . $e->getMessage());
}

if (isset($conn)) {
    $conn->close();
}
?>
