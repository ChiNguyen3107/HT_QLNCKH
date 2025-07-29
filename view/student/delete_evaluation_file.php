<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra dữ liệu GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $_SESSION['error_message'] = "Phương thức truy cập không hợp lệ.";
    header('Location: student_manage_projects.php');
    exit;
}

$project_id = trim($_GET['project_id'] ?? '');
$file_id = trim($_GET['file_id'] ?? '');

// Validate dữ liệu đầu vào
if (empty($project_id) || empty($file_id)) {
    $_SESSION['error_message'] = "Thông tin không hợp lệ.";
    header('Location: student_manage_projects.php');
    exit;
}

// Kiểm tra quyền truy cập
$check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_access_sql);
$stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
$stmt->execute();
$access_result = $stmt->get_result();

if ($access_result->num_rows === 0) {
    $_SESSION['error_message'] = "Bạn không có quyền truy cập đề tài này.";
    header('Location: student_manage_projects.php');
    exit;
}

$user_role = $access_result->fetch_assoc()['CTTG_VAITRO'];

// Chỉ chủ nhiệm mới có thể xóa file đánh giá
if ($user_role !== 'Chủ nhiệm') {
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể xóa file đánh giá.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Lấy thông tin file cần xóa
$get_file_sql = "SELECT FDG_DUONGDAN FROM file_danh_gia WHERE FDG_MA = ?";
$stmt = $conn->prepare($get_file_sql);
$stmt->bind_param("s", $file_id);
$stmt->execute();
$file_result = $stmt->get_result();

if ($file_result->num_rows === 0) {
    $_SESSION['error_message'] = "File đánh giá không tồn tại.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

$file_data = $file_result->fetch_assoc();
$file_path = '../../uploads/evaluation_files/' . $file_data['FDG_DUONGDAN'];

try {
    // Bắt đầu transaction
    $conn->begin_transaction();

    // Xóa record từ database
    $delete_sql = "DELETE FROM file_danh_gia WHERE FDG_MA = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("s", $file_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi xóa file từ database: " . $stmt->error);
    }

    // Xóa file vật lý nếu tồn tại
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            // Log warning nhưng không throw exception vì đã xóa khỏi DB
            error_log("Warning: Không thể xóa file vật lý: " . $file_path);
        }
    }

    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Xóa file đánh giá thành công!";

} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    
    $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
}

// Redirect về trang chi tiết đề tài
header('Location: view_project.php?id=' . urlencode($project_id));
exit;
?>
