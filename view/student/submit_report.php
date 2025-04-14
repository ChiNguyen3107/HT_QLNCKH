<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Create upload directory if it doesn't exist
$upload_dir = '../../uploads/reports/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

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
$report_title = isset($_POST['report_title']) ? trim($_POST['report_title']) : '';
$report_description = isset($_POST['report_description']) ? trim($_POST['report_description']) : '';
$report_type = isset($_POST['report_type']) ? trim($_POST['report_type']) : '';
$student_id = $_SESSION['user_id'];

// Kiểm tra dữ liệu đầu vào
if (empty($project_id) || empty($report_title) || empty($report_type)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin cần thiết.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra quyền truy cập (sinh viên phải là thành viên của đề tài)
$check_access_sql = "SELECT 1 FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
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
    $_SESSION['error_message'] = "Bạn không có quyền nộp báo cáo cho đề tài này.";
    header('Location: student_manage_projects.php');
    exit;
}

// Kiểm tra trạng thái đề tài
$check_status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($check_status_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$status_result = $stmt->get_result();
$project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];

if ($project_status !== 'Đang thực hiện' && $project_status !== 'Đã hoàn thành') {
    $_SESSION['error_message'] = "Chỉ có thể nộp báo cáo cho đề tài đang thực hiện hoặc đã hoàn thành.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Xử lý file upload
$uploaded_file_path = null;
if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
    
    // Lấy thông tin file
    $file_name = $_FILES['report_file']['name'];
    $file_tmp = $_FILES['report_file']['tmp_name'];
    $file_size = $_FILES['report_file']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Kiểm tra phần mở rộng file
    $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar');
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['error_message'] = "Định dạng file không được hỗ trợ. Vui lòng sử dụng: " . implode(', ', $allowed_extensions);
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra kích thước file (tối đa 20MB)
    if ($file_size > 20 * 1024 * 1024) {
        $_SESSION['error_message'] = "Kích thước file không được vượt quá 20MB.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Tạo tên file mới để tránh trùng lặp
    $new_file_name = uniqid('report_') . '_' . time() . '.' . $file_ext;
    $file_destination = $upload_dir . $new_file_name;
    
    // Upload file
    if (move_uploaded_file($file_tmp, $file_destination)) {
        $uploaded_file_path = $new_file_name;
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi tải file lên. Vui lòng thử lại.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
} else {
    $_SESSION['error_message'] = "Vui lòng chọn file báo cáo để tải lên.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Tạo mã báo cáo mới (format: BC + YYMMDD + random)
$report_id = 'BC' . date('ymd') . rand(100, 999);

// Thêm thông tin báo cáo vào cơ sở dữ liệu
$insert_sql = "INSERT INTO bao_cao (
                   BC_MABC,
                   BC_TENBC,
                   BC_DUONGDAN,
                   BC_MOTA,
                   BC_NGAYNOP,
                   BC_TRANGTHAI,
                   BC_GHICHU,
                   BC_DIEMSO,
                   DT_MADT,
                   SV_MASV,
                   LBC_MALOAI
               ) VALUES (?, ?, ?, ?, NOW(), 'Chờ duyệt', NULL, NULL, ?, ?, ?)";

$stmt = $conn->prepare($insert_sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error;
    // Xóa file đã upload nếu có lỗi
    if ($uploaded_file_path && file_exists($upload_dir . $uploaded_file_path)) {
        unlink($upload_dir . $uploaded_file_path);
    }
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

$stmt->bind_param("ssssss", 
    $report_id,
    $report_title,
    $uploaded_file_path,
    $report_description,
    $project_id,
    $student_id,
    $report_type
);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Nộp báo cáo thành công! Báo cáo của bạn đang chờ được duyệt.";
} else {
    $_SESSION['error_message'] = "Lỗi khi nộp báo cáo: " . $stmt->error;
    // Xóa file đã upload nếu có lỗi
    if ($uploaded_file_path && file_exists($upload_dir . $uploaded_file_path)) {
        unlink($upload_dir . $uploaded_file_path);
    }
}

// Đóng kết nối
$stmt->close();
$conn->close();

// Chuyển hướng về trang chi tiết đề tài
header('Location: view_project.php?id=' . urlencode($project_id));
exit;
?>