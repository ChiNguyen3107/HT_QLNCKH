<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Phương thức yêu cầu không hợp lệ.";
    header('Location: browse_projects.php');
    exit;
}

// Lấy dữ liệu từ form
$project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : 'Thành viên'; // Mặc định là thành viên
$student_id = $_SESSION['user_id'];
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Kiểm tra dữ liệu đầu vào
if (empty($project_id)) {
    $_SESSION['error_message'] = "Thiếu thông tin đề tài.";
    header('Location: browse_projects.php');
    exit;
}

// Kiểm tra xem đề tài có tồn tại và đang mở đăng ký không
$project_check_sql = "SELECT DT_MADT, DT_TRANGTHAI, DT_SLSV, GV_MAGV FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($project_check_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$project_result = $stmt->get_result();

if ($project_result->num_rows === 0) {
    $_SESSION['error_message'] = "Đề tài không tồn tại.";
    header('Location: browse_projects.php');
    exit;
}

$project_data = $project_result->fetch_assoc();

// Kiểm tra trạng thái đề tài
if ($project_data['DT_TRANGTHAI'] !== 'Chờ duyệt' && $project_data['DT_TRANGTHAI'] !== 'Đang thực hiện') {
    $_SESSION['error_message'] = "Đề tài này không trong trạng thái mở đăng ký.";
    header('Location: browse_projects.php');
    exit;
}

// Kiểm tra xem sinh viên đã đăng ký đề tài này chưa
$check_registered_sql = "SELECT 1 FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_registered_sql);
$stmt->bind_param("ss", $project_id, $student_id);
$stmt->execute();
$registered_result = $stmt->get_result();

if ($registered_result->num_rows > 0) {
    $_SESSION['error_message'] = "Bạn đã đăng ký đề tài này rồi.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra xem sinh viên đã tham gia vào đề tài khác không
$check_other_projects_sql = "SELECT DT_MADT, CTTG_VAITRO FROM chi_tiet_tham_gia 
                            WHERE SV_MASV = ? AND DT_MADT != ?";
$stmt = $conn->prepare($check_other_projects_sql);
$stmt->bind_param("ss", $student_id, $project_id);
$stmt->execute();
$other_projects_result = $stmt->get_result();

// Kiểm tra xem sinh viên có đang là chủ nhiệm đề tài khác không
$is_leader_elsewhere = false;
while ($row = $other_projects_result->fetch_assoc()) {
    if ($row['CTTG_VAITRO'] === 'Chủ nhiệm' && $role === 'Chủ nhiệm') {
        $is_leader_elsewhere = true;
        break;
    }
}

if ($is_leader_elsewhere) {
    $_SESSION['error_message'] = "Bạn đã là chủ nhiệm của một đề tài khác. Không thể đăng ký làm chủ nhiệm cho đề tài này.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra số lượng sinh viên đã đăng ký
$count_members_sql = "SELECT COUNT(*) as member_count FROM chi_tiet_tham_gia WHERE DT_MADT = ?";
$stmt = $conn->prepare($count_members_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$count_result = $stmt->get_result();
$member_count = $count_result->fetch_assoc()['member_count'];

// Kiểm tra xem có ai đã đăng ký làm chủ nhiệm chưa
$check_leader_sql = "SELECT 1 FROM chi_tiet_tham_gia 
                    WHERE DT_MADT = ? AND CTTG_VAITRO = 'Chủ nhiệm'";
$stmt = $conn->prepare($check_leader_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$leader_result = $stmt->get_result();
$has_leader = ($leader_result->num_rows > 0);

// Nếu sinh viên đăng ký làm chủ nhiệm nhưng đã có chủ nhiệm rồi
if ($role === 'Chủ nhiệm' && $has_leader) {
    $_SESSION['error_message'] = "Đề tài này đã có chủ nhiệm. Bạn có thể đăng ký làm thành viên.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Nếu số lượng đã đạt giới hạn
if ($member_count >= $project_data['DT_SLSV']) {
    $_SESSION['error_message'] = "Số lượng sinh viên đăng ký đã đạt giới hạn.";
    header('Location: browse_projects.php');
    exit;
}

// Thêm vào bảng yêu cầu đăng ký
$registration_sql = "INSERT INTO yeu_cau_dang_ky (YC_THOIGIAN, YC_NOIDUNG, YC_TRANGTHAI, DT_MADT, SV_MASV, YC_VAITRO) 
                    VALUES (NOW(), ?, 'Đang chờ duyệt', ?, ?, ?)";
$stmt = $conn->prepare($registration_sql);
$stmt->bind_param("ssss", $message, $project_id, $student_id, $role);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Yêu cầu đăng ký đề tài đã được gửi. Vui lòng chờ phê duyệt.";
    
    // Thông báo cho giảng viên
    $notification_sql = "INSERT INTO thong_bao (TB_NOIDUNG, TB_THOIGIAN, TB_DANHDOC, TB_LOAI, DT_MADT, GV_MAGV, SV_MASV) 
                        VALUES (?, NOW(), 0, 'Đăng ký', ?, ?, ?)";
    $notification_content = "Sinh viên " . $_SESSION['user_name'] . " đã yêu cầu đăng ký đề tài với vai trò " . $role;
    
    $stmt = $conn->prepare($notification_sql);
    $stmt->bind_param("ssss", $notification_content, $project_id, $project_data['GV_MAGV'], $student_id);
    $stmt->execute();
} else {
    $_SESSION['error_message'] = "Có lỗi xảy ra khi đăng ký: " . $stmt->error;
}

// Đóng kết nối
$stmt->close();
$conn->close();

// Chuyển hướng về trang chi tiết đề tài
header('Location: view_project.php?id=' . urlencode($project_id));
exit;
?>