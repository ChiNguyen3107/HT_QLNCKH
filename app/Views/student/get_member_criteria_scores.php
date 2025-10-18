<?php
session_start();
require_once '../../include/connect.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập - Updated to match login_process.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit();
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối database: " . $conn->connect_error);
    }

    $student_id = $_SESSION['user_id']; // Updated to use user_id from login
    $member_id = $_GET['member_id'] ?? '';
    $project_id = $_GET['project_id'] ?? '';

    // Validate input
    if (empty($member_id) || empty($project_id)) {
        throw new Exception('Thiếu thông tin thành viên hoặc dự án');
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
        throw new Exception('Bạn không có quyền xem đánh giá dự án này');
    }

    // Lấy thông tin đánh giá từ bảng thanh_vien_hoi_dong
    // Trước tiên tìm QD_SO từ project_id
    $find_qd = $conn->prepare("
        SELECT qd.QD_SO 
        FROM quyet_dinh_nghiem_thu qd
        JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB  
        JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
        WHERE dt.DT_MADT = ?
    ");
    $find_qd->bind_param("s", $project_id);
    $find_qd->execute();
    $qd_result = $find_qd->get_result();
    
    if ($qd_result->num_rows === 0) {
        throw new Exception('Không tìm thấy quyết định nghiệm thu cho dự án này');
    }
    
    $qd_info = $qd_result->fetch_assoc();
    
    // Lấy thông tin đánh giá của thành viên
    $get_member_scores = $conn->prepare("
        SELECT 
            TV_DIEM,
            TV_DANHGIA,
            TV_DIEMCHITIET,
            TV_TRANGTHAI
        FROM thanh_vien_hoi_dong 
        WHERE QD_SO = ? AND GV_MAGV = ?
        LIMIT 1
    ");
    $get_member_scores->bind_param("ss", $qd_info['QD_SO'], $member_id);
    $get_member_scores->execute();
    $member_result = $get_member_scores->get_result();
    
    if ($member_result->num_rows === 0) {
        throw new Exception('Không tìm thấy thông tin thành viên');
    }
    
    $member_data = $member_result->fetch_assoc();
    
    // Parse JSON chi tiết tiêu chí
    $criteria_scores = [];
    if (!empty($member_data['TV_DIEMCHITIET'])) {
        $criteria_scores = json_decode($member_data['TV_DIEMCHITIET'], true) ?: [];
    }
    
    $is_completed = ($member_data['TV_TRANGTHAI'] === 'Đã hoàn thành') ? 1 : 0;
    
    echo json_encode([
        'success' => true,
        'scores' => $criteria_scores,
        'overall_comment' => $member_data['TV_DANHGIA'],
        'total_score' => floatval($member_data['TV_DIEM']),
        'is_completed' => $is_completed
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    
    // Log lỗi
    error_log("Lỗi lấy điểm tiêu chí: " . $e->getMessage());
}

if (isset($conn)) {
    $conn->close();
}
?>
