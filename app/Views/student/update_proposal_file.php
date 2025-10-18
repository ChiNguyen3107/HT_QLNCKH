<?php
// Bắt đầu output buffering để tránh lỗi header
ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Debug - kiểm tra session
error_log("Update proposal file - User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Update proposal file - Role: " . ($_SESSION['role'] ?? 'NOT SET'));

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    $_SESSION['error_message'] = "Lỗi kết nối cơ sở dữ liệu.";
    header("Location: view_project.php");
    exit();
}

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Phương thức không được phép.";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Lấy dữ liệu từ form
$project_id = trim($_POST['project_id'] ?? '');
$update_reason = trim($_POST['update_reason'] ?? '');
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền chủ nhiệm trước khi xử lý
$check_role_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_role_sql);
$stmt->bind_param("ss", $project_id, $user_id);
$stmt->execute();
$role_result = $stmt->get_result();

if ($role_result->num_rows === 0) {
    $_SESSION['error_message'] = "Bạn không có quyền truy cập đề tài này.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

$user_role = $role_result->fetch_assoc()['CTTG_VAITRO'];
if ($user_role !== 'Chủ nhiệm') {
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể cập nhật file thuyết minh.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Kiểm tra trạng thái đề tài
$check_status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($check_status_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$status_result = $stmt->get_result();

if ($status_result->num_rows === 0) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

$project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];
if ($project_status !== 'Đang thực hiện') {
    $_SESSION['error_message'] = "Chỉ có thể cập nhật file khi đề tài đang trong trạng thái 'Đang thực hiện'. Trạng thái hiện tại: " . $project_status;
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Debug POST data
error_log("Update proposal file - Project ID: " . $project_id);
error_log("Update proposal file - Update reason: " . $update_reason);
error_log("Update proposal file - Files: " . print_r($_FILES, true));

// Kiểm tra dữ liệu đầu vào
if (empty($project_id) || empty($update_reason)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Kiểm tra file upload
if (!isset($_FILES['proposal_file']) || $_FILES['proposal_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = "Vui lòng chọn file thuyết minh.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

$file = $_FILES['proposal_file'];
$allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$max_size = 10 * 1024 * 1024; // 10MB

// Kiểm tra loại file
if (!in_array($file['type'], $allowed_types)) {
    $_SESSION['error_message'] = "Chỉ chấp nhận file PDF hoặc Word.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Kiểm tra kích thước file
if ($file['size'] > $max_size) {
    $_SESSION['error_message'] = "File không được vượt quá 10MB.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

try {
    // Bắt đầu transaction
    $conn->begin_transaction();

    // Kiểm tra quyền truy cập đề tài
    $check_access_sql = "SELECT dt.DT_MADT, dt.DT_FILEBTM, ct.CTTG_VAITRO 
                        FROM de_tai_nghien_cuu dt 
                        LEFT JOIN chi_tiet_tham_gia ct ON dt.DT_MADT = ct.DT_MADT 
                        WHERE dt.DT_MADT = ? AND ct.SV_MASV = ?";
    $stmt = $conn->prepare($check_access_sql);
    $stmt->bind_param("ss", $project_id, $user_id);
    $stmt->execute();
    $access_result = $stmt->get_result();

    if ($access_result->num_rows === 0) {
        throw new Exception("Bạn không có quyền truy cập đề tài này.");
    }

    $project_info = $access_result->fetch_assoc();
    $old_file = $project_info['DT_FILEBTM'];

    // Tạo tên file mới
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = "proposal_" . $project_id . "_" . time() . "." . $file_extension;
    $upload_dir = "../../uploads/project_files/";
    
    // Debug paths
    error_log("Upload directory: " . $upload_dir);
    error_log("New filename: " . $new_filename);
    error_log("Full upload path: " . $upload_dir . $new_filename);
    
    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        error_log("Created directory: " . $upload_dir);
    }

    $upload_path = $upload_dir . $new_filename;

    // Upload file mới
    error_log("Attempting to upload file from: " . $file['tmp_name'] . " to: " . $upload_path);
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        error_log("File upload failed - move_uploaded_file returned false");
        throw new Exception("Không thể upload file. Vui lòng thử lại.");
    }
    error_log("File uploaded successfully to: " . $upload_path);

    // Lưu vết file cũ vào lịch sử trước khi cập nhật
    if ($old_file) {
        // Lấy thông tin file cũ
        $old_file_path = $upload_dir . $old_file;
        $old_file_size = file_exists($old_file_path) ? filesize($old_file_path) : 0;
        $old_file_type = pathinfo($old_file, PATHINFO_EXTENSION);
        
        // Thêm vào bảng lịch sử thuyết minh
        $history_sql = "INSERT INTO lich_su_thuyet_minh (DT_MADT, FILE_TEN, FILE_KICHTHUOC, FILE_LOAI, LY_DO, NGUOI_TAI, NGAY_TAI, LA_HIEN_TAI) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
        $stmt = $conn->prepare($history_sql);
        if ($stmt) {
            $stmt->bind_param("ssisss", $project_id, $old_file, $old_file_size, $old_file_type, $update_reason, $student_info['SV_HOTEN']);
            $stmt->execute();
            error_log("Old file saved to history: " . $old_file);
        }
    }
    
    // Cập nhật đường dẫn file trong database
    $update_sql = "UPDATE de_tai_nghien_cuu SET DT_FILEBTM = ? WHERE DT_MADT = ?";
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        error_log("Failed to prepare update statement: " . $conn->error);
        throw new Exception("Lỗi chuẩn bị truy vấn cập nhật.");
    }
    
    $stmt->bind_param("ss", $new_filename, $project_id);
    
    if (!$stmt->execute()) {
        error_log("Failed to execute update statement: " . $stmt->error);
        throw new Exception("Không thể cập nhật thông tin đề tài.");
    }
    
    error_log("Database updated successfully - affected rows: " . $stmt->affected_rows);

    // Lấy thông tin sinh viên
    $student_sql = "SELECT CONCAT(SV_HOSV, ' ', SV_TENSV) AS SV_HOTEN FROM sinh_vien WHERE SV_MASV = ?";
    $stmt = $conn->prepare($student_sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    $student_info = $student_result->fetch_assoc();

    // Lưu file mới vào lịch sử và đánh dấu là file hiện tại
    $new_file_size = filesize($upload_path);
    $new_file_type = pathinfo($new_filename, PATHINFO_EXTENSION);
    
    $new_history_sql = "INSERT INTO lich_su_thuyet_minh (DT_MADT, FILE_TEN, FILE_KICHTHUOC, FILE_LOAI, LY_DO, NGUOI_TAI, NGAY_TAI, LA_HIEN_TAI) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)";
    $stmt = $conn->prepare($new_history_sql);
    if ($stmt) {
        $stmt->bind_param("ssisss", $project_id, $new_filename, $new_file_size, $new_file_type, $update_reason, $student_info['SV_HOTEN']);
        $stmt->execute();
        error_log("New file saved to history as current: " . $new_filename);
    }

    // Thêm vào tiến độ đề tài
    $progress_title = "Cập nhật file thuyết minh";
    $progress_content = "File thuyết minh đã được cập nhật.\n\nLý do cập nhật: " . $update_reason;
    if ($old_file) {
        $progress_content .= "\n\nFile cũ: " . $old_file;
    }
    $progress_content .= "\nFile mới: " . $new_filename;

    // Tạo mã tiến độ mới (format: TD + YYMMDD + XX where XX is random 2 digits)
    $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));

    $progress_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 0)";
    $stmt = $conn->prepare($progress_sql);
    if (!$stmt) {
        error_log("Failed to prepare progress statement: " . $conn->error);
        throw new Exception("Lỗi chuẩn bị truy vấn tiến độ.");
    }
    
    $stmt->bind_param("sssss", $progress_id, $project_id, $user_id, $progress_title, $progress_content);
    
    if (!$stmt->execute()) {
        error_log("Failed to execute progress statement: " . $stmt->error);
        throw new Exception("Không thể ghi lại tiến độ cập nhật.");
    }
    
    error_log("Progress inserted successfully - insert id: " . $conn->insert_id);

    // Không xóa file cũ ngay lập tức để giữ lịch sử
    // File cũ sẽ được giữ lại trong thư mục uploads để có thể tải về từ lịch sử
    if ($old_file) {
        error_log("Old file preserved for history: " . $old_file);
    }

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully");

    $_SESSION['success_message'] = "Cập nhật file thuyết minh thành công!";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Xóa file đã upload nếu có lỗi
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    // Ghi log lỗi
    error_log("Update proposal file error: " . $e->getMessage());
    error_log("User ID: " . $user_id);
    error_log("Project ID: " . $project_id);
    
    $_SESSION['error_message'] = "Lỗi: " . $e->getMessage();
} catch (Error $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Xóa file đã upload nếu có lỗi
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    // Ghi log lỗi
    error_log("Update proposal file fatal error: " . $e->getMessage());
    error_log("User ID: " . $user_id);
    error_log("Project ID: " . $project_id);
    
    $_SESSION['error_message'] = "Có lỗi hệ thống xảy ra. Vui lòng thử lại.";
}

// Clean output buffer và redirect
ob_end_clean();

// Redirect về trang chi tiết đề tài
header("Location: view_project.php?id=" . urlencode($project_id));
exit();
?>
