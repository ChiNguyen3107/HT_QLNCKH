<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Kiểm tra đăng nhập - Updated role check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

try {
    $project_id = $_POST['project_id'] ?? '';
    $member_id = $_POST['member_id'] ?? '';
    $decision_id = $_POST['decision_id'] ?? '';
    $member_score = $_POST['member_score'] ?? '';
    $score_comment = $_POST['score_comment'] ?? '';
    
    // Validate dữ liệu
    if (empty($project_id) || empty($member_id) || empty($member_score)) {
        throw new Exception('Thông tin không đầy đủ');
    }
    
    $score = floatval($member_score);
    if ($score < 0 || $score > 100) {
        throw new Exception('Điểm phải nằm trong khoảng từ 0 đến 100');
    }
    
    // Kiểm tra quyền truy cập đề tài
    $stmt = $pdo->prepare("
        SELECT dt.*, sv.SV_MASV 
        FROM de_tai dt 
        JOIN sinh_vien sv ON dt.DT_MADT = sv.SV_MADT 
        WHERE dt.DT_MADT = ? AND sv.SV_MASV = ?
    ");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $project = $stmt->fetch();
    
    if (!$project) {
        throw new Exception('Không có quyền truy cập đề tài này');
    }
    
    // Cập nhật điểm thành viên hội đồng
    $stmt = $pdo->prepare("
        UPDATE thanh_vien_hoi_dong 
        SET TV_DIEM = ?, TV_NHANXET = ?, TV_NGAYCAPDIEM = NOW() 
        WHERE TV_MAGV = ? AND TV_SOBB = (
            SELECT bb.BB_SOBB 
            FROM bien_ban_nghiem_thu bb 
            JOIN quyet_dinh qd ON bb.BB_SOQD = qd.QD_SO 
            WHERE qd.QD_MADT = ?
        )
    ");
    
    $result = $stmt->execute([$score, $score_comment, $member_id, $project_id]);
    
    if (!$result) {
        throw new Exception('Không thể cập nhật điểm');
    }
    
    // Kiểm tra số hàng bị ảnh hưởng
    if ($stmt->rowCount() === 0) {
        throw new Exception('Không tìm thấy thành viên hội đồng để cập nhật');
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cập nhật điểm thành công',
        'score' => $score
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
