<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra dữ liệu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Phương thức không hợp lệ.";
    header('Location: student_manage_projects.php');
    exit;
}

$project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';
$decision_id = isset($_POST['decision_id']) ? trim($_POST['decision_id']) : '';
$member_id = isset($_POST['member_id']) ? trim($_POST['member_id']) : '';
$member_score = isset($_POST['member_score']) ? floatval($_POST['member_score']) : 0;
$member_evaluation = isset($_POST['member_evaluation']) ? trim($_POST['member_evaluation']) : '';
$score_note = isset($_POST['score_note']) ? trim($_POST['score_note']) : '';

// Validate dữ liệu
if (empty($project_id) || empty($decision_id) || empty($member_id)) {
    $_SESSION['error_message'] = "Thiếu thông tin cần thiết.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

if ($member_score < 0 || $member_score > 100) {
    $_SESSION['error_message'] = "Điểm đánh giá phải từ 0 đến 100.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

if (empty($member_evaluation)) {
    $_SESSION['error_message'] = "Vui lòng chọn nhận xét đánh giá.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

try {
    $conn->begin_transaction();

    // Kiểm tra quyền truy cập: chỉ chủ nhiệm đề tài mới có thể cập nhật điểm
    $check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
    $stmt = $conn->prepare($check_access_sql);
    $stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
    $stmt->execute();
    $access_result = $stmt->get_result();
    
    if ($access_result->num_rows === 0) {
        throw new Exception("Bạn không có quyền truy cập đề tài này.");
    }
    
    $user_role = $access_result->fetch_assoc()['CTTG_VAITRO'];
    if ($user_role !== 'Chủ nhiệm') {
        throw new Exception("Chỉ chủ nhiệm đề tài mới có thể cập nhật điểm đánh giá.");
    }

    // Kiểm tra trạng thái đề tài
    $check_status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($check_status_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $status_result = $stmt->get_result();
    
    if ($status_result->num_rows === 0) {
        throw new Exception("Không tìm thấy đề tài.");
    }
    
    $project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];
    if ($project_status !== 'Đang thực hiện' && $project_status !== 'Đã hoàn thành') {
        throw new Exception("Chỉ có thể cập nhật điểm khi đề tài đang trong trạng thái 'Đang thực hiện' hoặc 'Đã hoàn thành'. Trạng thái hiện tại: " . $project_status);
    }

    // Kiểm tra xem thành viên hội đồng có tồn tại không
    $check_member_sql = "SELECT tv.*, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN 
                        FROM thanh_vien_hoi_dong tv
                        JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                        WHERE tv.QD_SO = ? AND tv.GV_MAGV = ?";
    $stmt = $conn->prepare($check_member_sql);
    $stmt->bind_param("ss", $decision_id, $member_id);
    $stmt->execute();
    $member_result = $stmt->get_result();
    
    if ($member_result->num_rows === 0) {
        throw new Exception("Không tìm thấy thành viên hội đồng này.");
    }
    
    $member_info = $member_result->fetch_assoc();

    // Cập nhật điểm cho thành viên hội đồng
    $update_score_sql = "UPDATE thanh_vien_hoi_dong 
                        SET TV_DIEM = ?, TV_DANHGIA = ?
                        WHERE QD_SO = ? AND GV_MAGV = ?";
    $stmt = $conn->prepare($update_score_sql);
    $stmt->bind_param("isss", $member_score, $member_evaluation, $decision_id, $member_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi cập nhật điểm đánh giá: " . $stmt->error);
    }

    // Ghi lại lịch sử cập nhật điểm trong tiến độ đề tài
    $progress_title = "Cập nhật điểm đánh giá thành viên hội đồng";
    $progress_content = "Đã cập nhật điểm đánh giá cho thành viên hội đồng:\n";
    $progress_content .= "- Họ tên: " . $member_info['GV_HOTEN'] . "\n";
    $progress_content .= "- Vai trò: " . $member_info['TV_VAITRO'] . "\n";
    $progress_content .= "- Điểm đánh giá: " . $member_score . "/100\n";
    $progress_content .= "- Nhận xét: " . $member_evaluation . "\n";
    if (!empty($score_note)) {
        $progress_content .= "- Ghi chú: " . $score_note . "\n";
    }
    $progress_content .= "- Thời gian cập nhật: " . date('d/m/Y H:i:s');

    $insert_progress_sql = "INSERT INTO tien_do_de_tai (DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_PHANTRAMHOANTHANH, TDDT_NGAYCAPNHAT) 
                           VALUES (?, ?, ?, ?, 0, NOW())";
    $stmt = $conn->prepare($insert_progress_sql);
    $stmt->bind_param("ssss", $project_id, $_SESSION['user_id'], $progress_title, $progress_content);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi ghi lại lịch sử: " . $stmt->error);
    }

    // Kiểm tra xem tất cả thành viên hội đồng đã có điểm chưa và cập nhật trạng thái đề tài nếu cần
    include_once '../../include/project_completion_functions.php';
    $completion_updated = updateProjectStatusIfComplete($project_id, $conn);
    
    if ($completion_updated) {
        error_log("Project $project_id automatically completed after member score update");
    }

    $conn->commit();
    
    $_SESSION['success_message'] = "Đã cập nhật điểm đánh giá cho thành viên hội đồng " . $member_info['GV_HOTEN'] . " thành công!";
    $_SESSION['success_message'] .= " (Điểm: " . $member_score . "/100 - " . $member_evaluation . ")";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

// Chuyển hướng về trang chi tiết đề tài
header('Location: view_project.php?id=' . urlencode($project_id) . '#evaluation');
exit;
?>
