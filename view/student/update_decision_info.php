<?php
// Bắt đầu output buffering để tránh lỗi header
ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Debug - kiểm tra session
error_log("Update decision info - User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Update decision info - Role: " . ($_SESSION['role'] ?? 'NOT SET'));

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
$decision_id = trim($_POST['decision_id'] ?? '');
$decision_number = trim($_POST['decision_number'] ?? '');
$decision_date = trim($_POST['decision_date'] ?? '');
$decision_content = trim($_POST['decision_content'] ?? '');
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
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin quyết định nghiệm thu.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Debug POST data
error_log("Update decision info - Project ID: " . $project_id);
error_log("Update decision info - Decision Number: " . $decision_number);
error_log("Update decision info - Update reason: " . $update_reason);
error_log("Update decision info - Files: " . print_r($_FILES, true));

// Kiểm tra dữ liệu đầu vào
if (empty($project_id) || empty($decision_number) || empty($decision_date) || empty($update_reason)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin bắt buộc.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Validate dates - chỉ cần kiểm tra ngày quyết định
if (!strtotime($decision_date)) {
    $_SESSION['error_message'] = "Ngày quyết định không hợp lệ.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Kiểm tra file upload nếu có
$new_filename = null;
$upload_path = null;
if (isset($_FILES['decision_file']) && $_FILES['decision_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['decision_file'];
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

    // Tạo tên file mới
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = "decision_" . $decision_number . "_" . time() . "." . $file_extension;
    $upload_dir = "../../uploads/decision_files/";
    
    // Debug paths
    error_log("Decision upload directory: " . $upload_dir);
    error_log("New decision filename: " . $new_filename);
    
    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        error_log("Created decision directory: " . $upload_dir);
    }

    $upload_path = $upload_dir . $new_filename;
} elseif (empty($decision_id)) {
    // Nếu tạo mới quyết định thì bắt buộc phải có file
    $_SESSION['error_message'] = "Vui lòng chọn file quyết định.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

try {
    // Bắt đầu transaction
    $conn->begin_transaction();

    // Kiểm tra quyền truy cập đề tài
    $check_access_sql = "SELECT dt.DT_MADT, ct.CTTG_VAITRO 
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

    // Upload file mới nếu có
    $old_file = null;
    if ($upload_path) {
        error_log("Attempting to upload decision file from: " . $file['tmp_name'] . " to: " . $upload_path);
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            error_log("Decision file upload failed - move_uploaded_file returned false");
            throw new Exception("Không thể upload file quyết định. Vui lòng thử lại.");
        }
        error_log("Decision file uploaded successfully to: " . $upload_path);
    }

    if (!empty($decision_id)) {
        // Cập nhật quyết định hiện tại
        
        // Lấy thông tin file cũ
        $old_file_sql = "SELECT QD_FILE FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
        $stmt = $conn->prepare($old_file_sql);
        $stmt->bind_param("s", $decision_id);
        $stmt->execute();
        $old_file_result = $stmt->get_result();
        if ($old_file_result->num_rows > 0) {
            $old_file = $old_file_result->fetch_assoc()['QD_FILE'];
        }

        // Cập nhật quyết định
        if ($new_filename) {
            $update_decision_sql = "UPDATE quyet_dinh_nghiem_thu SET 
                                   QD_NGAY = ?, QD_FILE = ? 
                                   WHERE QD_SO = ?";
            $stmt = $conn->prepare($update_decision_sql);
            $stmt->bind_param("sss", $decision_date, $new_filename, $decision_id);
        } else {
            $update_decision_sql = "UPDATE quyet_dinh_nghiem_thu SET 
                                   QD_NGAY = ? 
                                   WHERE QD_SO = ?";
            $stmt = $conn->prepare($update_decision_sql);
            $stmt->bind_param("ss", $decision_date, $decision_id);
        }
        
        if (!$stmt) {
            error_log("Failed to prepare decision update statement: " . $conn->error);
            throw new Exception("Lỗi chuẩn bị truy vấn cập nhật quyết định.");
        }
        
        if (!$stmt->execute()) {
            error_log("Failed to execute decision update statement: " . $stmt->error);
            throw new Exception("Không thể cập nhật thông tin quyết định.");
        }
        
        error_log("Decision updated successfully");
        
    } else {
        // Tạo quyết định mới
        
        // Kiểm tra số quyết định đã tồn tại chưa
        $check_decision_sql = "SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
        $stmt = $conn->prepare($check_decision_sql);
        $stmt->bind_param("s", $decision_number);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Số quyết định đã tồn tại. Vui lòng chọn số khác.");
        }

        // Tạo quyết định mới trước
        $insert_decision_sql = "INSERT INTO quyet_dinh_nghiem_thu (QD_SO, QD_NGAY, QD_FILE, BB_SOBB) 
                               VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_decision_sql);
        if (!$stmt) {
            error_log("Failed to prepare decision insert statement: " . $conn->error);
            throw new Exception("Lỗi chuẩn bị truy vấn tạo quyết định.");
        }
        
        // Tạo mã biên bản tự động
        $report_code = "BB" . substr($decision_number, 2); // BB021 từ QD021
        
        $stmt->bind_param("ssss", $decision_number, $decision_date, $new_filename, $report_code);
        
        if (!$stmt->execute()) {
            error_log("Failed to execute decision insert statement: " . $stmt->error);
            throw new Exception("Không thể tạo quyết định nghiệm thu.");
        }

        // Tạo biên bản nghiệm thu sau (với thông tin mặc định)
        $insert_report_sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) 
                             VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_report_sql);
        if (!$stmt) {
            error_log("Failed to prepare report insert statement: " . $conn->error);
            throw new Exception("Lỗi chuẩn bị truy vấn tạo biên bản.");
        }
        
        // Sử dụng ngày quyết định và xếp loại mặc định
        $default_acceptance_date = $decision_date;
        $default_grade = "Chưa nghiệm thu";
        
        $stmt->bind_param("ssss", $report_code, $decision_number, $default_acceptance_date, $default_grade);
        
        if (!$stmt->execute()) {
            error_log("Failed to execute report insert statement: " . $stmt->error);
            throw new Exception("Không thể tạo biên bản nghiệm thu.");
        }
        
        // Cập nhật đề tài với số quyết định
        $update_project_sql = "UPDATE de_tai_nghien_cuu SET QD_SO = ? WHERE DT_MADT = ?";
        $stmt = $conn->prepare($update_project_sql);
        $stmt->bind_param("ss", $decision_number, $project_id);
        if (!$stmt->execute()) {
            error_log("Failed to update project with decision: " . $stmt->error);
            throw new Exception("Không thể liên kết quyết định với đề tài.");
        }
        
        error_log("Decision and report created successfully");
    }

    // Thêm vào tiến độ đề tài
    $progress_title = empty($decision_id) ? "Tạo quyết định nghiệm thu" : "Cập nhật thông tin quyết định nghiệm thu";
    $progress_content = "Thông tin quyết định nghiệm thu đã được " . (empty($decision_id) ? "tạo mới" : "cập nhật") . ".\n\n";
    $progress_content .= "Lý do: " . $update_reason . "\n\n";
    $progress_content .= "Chi tiết quyết định:\n";
    $progress_content .= "- Số quyết định: " . $decision_number . "\n";
    $progress_content .= "- Ngày ra quyết định: " . date('d/m/Y', strtotime($decision_date)) . "\n";
    if ($new_filename) {
        $progress_content .= "- File quyết định: " . $new_filename . "\n";
    }
    if ($decision_content) {
        $progress_content .= "- Nội dung: " . $decision_content . "\n";
    }

    // Tạo mã tiến độ mới (format: TD + YYMMDD + XX where XX is random 2 digits)
    $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));

    $progress_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 100)";
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

    // Không tự động cập nhật trạng thái đề tài ở đây
    // Trạng thái sẽ được cập nhật khi cập nhật biên bản với kết quả đạt

    // Xóa file cũ nếu có file mới và có file cũ
    if ($new_filename && $old_file && file_exists($upload_dir . $old_file)) {
        if (unlink($upload_dir . $old_file)) {
            error_log("Old decision file deleted successfully: " . $old_file);
        } else {
            error_log("Failed to delete old decision file: " . $old_file);
        }
    }

    // Commit transaction
    $conn->commit();
    error_log("Decision transaction committed successfully");

    $_SESSION['success_message'] = empty($decision_id) ? 
        "Tạo quyết định nghiệm thu thành công!" : 
        "Cập nhật thông tin quyết định nghiệm thu thành công!";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Xóa file đã upload nếu có lỗi
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    // Ghi log lỗi
    error_log("Update decision info error: " . $e->getMessage());
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
    error_log("Update decision info fatal error: " . $e->getMessage());
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
