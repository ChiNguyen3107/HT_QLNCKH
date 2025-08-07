<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\update_project_progress.php
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
    header('Location: student_manage_projects.php');
    exit;
}

// Lấy dữ liệu từ form
$project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';
$progress_title = isset($_POST['progress_title']) ? trim($_POST['progress_title']) : '';
$progress_content = isset($_POST['progress_content']) ? trim($_POST['progress_content']) : '';
$student_id = $_SESSION['user_id'];

// Kiểm tra dữ liệu đầu vào
if (empty($project_id) || empty($progress_title) || empty($progress_content)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin cần thiết.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra quyền truy cập (sinh viên phải là chủ nhiệm của đề tài)
$check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_access_sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error;
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

$stmt->bind_param("ss", $project_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Bạn không có quyền cập nhật tiến độ cho đề tài này.";
    header('Location: student_manage_projects.php');
    exit;
}

$user_role = $result->fetch_assoc()['CTTG_VAITRO'];
if ($user_role !== 'Chủ nhiệm') {
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể cập nhật tiến độ.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra trạng thái đề tài
$check_status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($check_status_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$status_result = $stmt->get_result();
$project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];

if ($project_status !== 'Đang thực hiện') {
    $_SESSION['error_message'] = "Chỉ có thể cập nhật tiến độ khi đề tài đang trong trạng thái 'Đang thực hiện'. Trạng thái hiện tại: " . $project_status;
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Xử lý file upload nếu có
$uploaded_file = '';
if (isset($_FILES['progress_file']) && $_FILES['progress_file']['error'] == 0) {
    $upload_dir = '../../uploads/progress_files/';
    
    // Đảm bảo thư mục tồn tại
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Lấy thông tin file
    $file_name = $_FILES['progress_file']['name'];
    $file_tmp = $_FILES['progress_file']['tmp_name'];
    $file_size = $_FILES['progress_file']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Kiểm tra phần mở rộng file
    $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'jpg', 'jpeg', 'png');
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['error_message'] = "Định dạng file không được hỗ trợ. Vui lòng sử dụng: " . implode(', ', $allowed_extensions);
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra kích thước file (tối đa 10MB)
    if ($file_size > 10 * 1024 * 1024) {
        $_SESSION['error_message'] = "Kích thước file không được vượt quá 10MB.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Tạo tên file mới để tránh trùng lặp
    $new_file_name = uniqid('progress_') . '_' . time() . '.' . $file_ext;
    $file_destination = $upload_dir . $new_file_name;
    
    // Upload file
    if (move_uploaded_file($file_tmp, $file_destination)) {
        $uploaded_file = $new_file_name;
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi tải file lên. Vui lòng thử lại.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
}

// Tạo mã tiến độ mới (format: TD + YYMMDD + random)
$progress_id = 'TD' . date('ymd') . rand(100, 999);

// Thêm thông tin tiến độ vào cơ sở dữ liệu
$insert_sql = "INSERT INTO tien_do_de_tai (
                   TDDT_MA,
                   DT_MADT,
                   SV_MASV,
                   TDDT_TIEUDE,
                   TDDT_NOIDUNG,
                   TDDT_FILE,
                   TDDT_NGAYCAPNHAT
               ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($insert_sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error;
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

$stmt->bind_param("ssssss", 
    $progress_id,
    $project_id,
    $student_id,
    $progress_title,
    $progress_content,
    $uploaded_file
);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Cập nhật tiến độ thành công!";
} else {
    $_SESSION['error_message'] = "Lỗi khi cập nhật tiến độ: " . $stmt->error;
}

// Đóng kết nối
$stmt->close();
$conn->close();

// Chuyển hướng về trang chi tiết đề tài
header('Location: view_project.php?id=' . urlencode($project_id));
exit;
?>