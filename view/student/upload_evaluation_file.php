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
    $_SESSION['error_message'] = "Phương thức truy cập không hợp lệ.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$project_id = trim($_POST['project_id'] ?? '');
$report_id = trim($_POST['report_id'] ?? '');
$evaluation_name = trim($_POST['evaluation_name'] ?? '');

// Validate dữ liệu đầu vào
if (empty($project_id) || empty($report_id) || empty($evaluation_name)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin.";
    header('Location: view_project.php?id=' . urlencode($project_id));
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

// Chỉ chủ nhiệm mới có thể upload file đánh giá
if ($user_role !== 'Chủ nhiệm') {
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể tải lên file đánh giá.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra trạng thái đề tài
$check_status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($check_status_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$status_result = $stmt->get_result();

if ($status_result->num_rows === 0) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

$project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];
if ($project_status !== 'Đang thực hiện') {
    $_SESSION['error_message'] = "Chỉ có thể tải file khi đề tài đang trong trạng thái 'Đang thực hiện'. Trạng thái hiện tại: " . $project_status;
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra biên bản có tồn tại không
$check_report_sql = "SELECT COUNT(*) as count FROM bien_ban WHERE BB_SOBB = ?";
$stmt = $conn->prepare($check_report_sql);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$report_result = $stmt->get_result();
$report_exists = $report_result->fetch_assoc()['count'] > 0;

if (!$report_exists) {
    $_SESSION['error_message'] = "Biên bản không tồn tại.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Xử lý file upload
if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = "Vui lòng chọn file để tải lên.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

$uploaded_file = $_FILES['evaluation_file'];
$file_name = $uploaded_file['name'];
$file_tmp = $uploaded_file['tmp_name'];
$file_size = $uploaded_file['size'];
$file_type = $uploaded_file['type'];

// Kiểm tra định dạng file
$allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    $_SESSION['error_message'] = "Chỉ cho phép tải lên file PDF, DOC, DOCX, TXT.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra kích thước file (tối đa 10MB)
if ($file_size > 10 * 1024 * 1024) {
    $_SESSION['error_message'] = "Kích thước file không được vượt quá 10MB.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Tạo thư mục lưu file nếu chưa tồn tại
$upload_dir = '../../uploads/evaluation_files/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $_SESSION['error_message'] = "Không thể tạo thư mục lưu file.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
}

// Tạo tên file unique
$file_basename = pathinfo($file_name, PATHINFO_FILENAME);
$unique_filename = $file_basename . '_' . time() . '.' . $file_extension;
$file_path = $upload_dir . $unique_filename;

// Di chuyển file đã upload
if (!move_uploaded_file($file_tmp, $file_path)) {
    $_SESSION['error_message'] = "Không thể lưu file. Vui lòng thử lại.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

try {
    // Bắt đầu transaction
    $conn->begin_transaction();

    // Tạo mã file đánh giá mới
    $get_max_id_sql = "SELECT MAX(CAST(SUBSTRING(FDG_MA, 4) AS UNSIGNED)) as max_id FROM file_danh_gia WHERE FDG_MA LIKE 'FDG%'";
    $max_id_result = $conn->query($get_max_id_sql);
    $max_id = $max_id_result->fetch_assoc()['max_id'] ?? 0;
    $new_id = 'FDG' . str_pad($max_id + 1, 7, '0', STR_PAD_LEFT);

    // Kiểm tra xem bảng có cột FDG_DUONGDAN không, nếu không thì thêm
    $check_column_sql = "SHOW COLUMNS FROM file_danh_gia LIKE 'FDG_DUONGDAN'";
    $column_result = $conn->query($check_column_sql);
    
    if ($column_result->num_rows === 0) {
        // Thêm cột FDG_DUONGDAN nếu chưa có
        $add_column_sql = "ALTER TABLE file_danh_gia ADD COLUMN FDG_DUONGDAN VARCHAR(500) NULL AFTER FDG_TEN";
        $conn->query($add_column_sql);
    }

    // Thêm file đánh giá vào database
    $insert_sql = "INSERT INTO file_danh_gia (FDG_MA, BB_SOBB, FDG_TEN, FDG_DUONGDAN, FDG_NGAYCAP) VALUES (?, ?, ?, ?, CURDATE())";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssss", $new_id, $report_id, $evaluation_name, $unique_filename);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi lưu thông tin file vào database: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Tải lên file đánh giá thành công!";

} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    
    // Xóa file đã upload nếu có lỗi database
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    $_SESSION['error_message'] = "Có lỗi xảy ra: " . $e->getMessage();
}

// Redirect về trang chi tiết đề tài
header('Location: view_project.php?id=' . urlencode($project_id));
exit;
?>
