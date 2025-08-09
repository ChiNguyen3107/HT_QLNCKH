<?php
// Bắt đầu output buffering để tránh lỗi header
ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Debug - kiểm tra session
error_log("Update contract info - User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Update contract info - Role: " . ($_SESSION['role'] ?? 'NOT SET'));

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
$contract_id = trim($_POST['contract_id'] ?? '');
$contract_code = trim($_POST['contract_code'] ?? '');
$contract_date = trim($_POST['contract_date'] ?? '');
$start_date = trim($_POST['start_date'] ?? '');
$end_date = trim($_POST['end_date'] ?? '');
$total_budget = trim($_POST['total_budget'] ?? '');
$contract_description = trim($_POST['contract_description'] ?? '');
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
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin hợp đồng.";
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
    $_SESSION['error_message'] = "Chỉ có thể cập nhật thông tin khi đề tài đang trong trạng thái 'Đang thực hiện'. Trạng thái hiện tại: " . $project_status;
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Debug POST data
error_log("Update contract info - Project ID: " . $project_id);
error_log("Update contract info - Contract Code: " . $contract_code);
error_log("Update contract info - Update reason: " . $update_reason);
error_log("Update contract info - Files: " . print_r($_FILES, true));

// Kiểm tra dữ liệu đầu vào
if (empty($project_id) || empty($contract_code) || empty($contract_date) || 
    empty($start_date) || empty($end_date) || empty($total_budget) || empty($update_reason)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin bắt buộc.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Nếu thiếu end_date, tự tính theo thời hạn đăng ký từ ghi chú đề tài
if (empty($end_date)) {
    $duration_months = 6;
    $proj_stmt = $conn->prepare("SELECT DT_GHICHU FROM de_tai_nghien_cuu WHERE DT_MADT = ?");
    if ($proj_stmt) {
        $proj_stmt->bind_param("s", $project_id);
        if ($proj_stmt->execute()) {
            $proj_res = $proj_stmt->get_result();
            if ($proj_res && $proj_res->num_rows > 0) {
                $ghichu = $proj_res->fetch_assoc()['DT_GHICHU'] ?? '';
                if ($ghichu && preg_match('/duration_months\s*=\s*(\d+)/i', $ghichu, $m)) {
                    $duration_months = max(1, (int)$m[1]);
                }
            }
        }
    }
    if (!empty($start_date)) {
        $end_date = date('Y-m-d', strtotime($start_date . " +$duration_months months"));
    }
}

// Validate dates
if (empty($start_date) || empty($end_date) || strtotime($start_date) >= strtotime($end_date)) {
    $_SESSION['error_message'] = "Ngày kết thúc phải sau ngày bắt đầu.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Validate budget
if (!is_numeric($total_budget) || $total_budget < 0) {
    $_SESSION['error_message'] = "Tổng kinh phí phải là số dương.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Convert budget to proper decimal format
$total_budget = floatval($total_budget);

// Ensure contract_description is properly handled (can be empty for nullable field)
if (empty($contract_description)) {
    $contract_description = null;
}

// Kiểm tra file upload nếu có
$new_filename = null;
$upload_path = null;
if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['contract_file'];
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 15 * 1024 * 1024; // 15MB

    // Kiểm tra loại file
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error_message'] = "Chỉ chấp nhận file PDF hoặc Word.";
        header("Location: view_project.php?id=" . urlencode($project_id));
        exit();
    }

    // Kiểm tra kích thước file
    if ($file['size'] > $max_size) {
        $_SESSION['error_message'] = "File không được vượt quá 15MB.";
        header("Location: view_project.php?id=" . urlencode($project_id));
        exit();
    }

    // Tạo tên file mới
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = "contract_" . $contract_code . "_" . time() . "." . $file_extension;
    $upload_dir = "../../uploads/contract_files/";
    
    // Debug paths
    error_log("Contract upload directory: " . $upload_dir);
    error_log("New contract filename: " . $new_filename);
    
    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        error_log("Created contract directory: " . $upload_dir);
    }

    $upload_path = $upload_dir . $new_filename;
} elseif (empty($contract_id)) {
    // Nếu tạo mới hợp đồng thì bắt buộc phải có file
    $_SESSION['error_message'] = "Vui lòng chọn file hợp đồng.";
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
        error_log("Attempting to upload contract file from: " . $file['tmp_name'] . " to: " . $upload_path);
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            error_log("Contract file upload failed - move_uploaded_file returned false");
            throw new Exception("Không thể upload file hợp đồng. Vui lòng thử lại.");
        }
        error_log("Contract file uploaded successfully to: " . $upload_path);
    }

    if (!empty($contract_id)) {
        // Cập nhật hợp đồng hiện tại
        
        // Lấy thông tin file cũ
        $old_file_sql = "SELECT HD_FILEHD FROM hop_dong WHERE HD_MA = ?";
        $stmt = $conn->prepare($old_file_sql);
        $stmt->bind_param("s", $contract_id);
        $stmt->execute();
        $old_file_result = $stmt->get_result();
        if ($old_file_result->num_rows > 0) {
            $old_file = $old_file_result->fetch_assoc()['HD_FILEHD'];
        }

        $update_sql = "UPDATE hop_dong SET 
                      HD_MA = ?, 
                      HD_NGAYTAO = ?, 
                      HD_NGAYBD = ?, 
                      HD_NGAYKT = ?, 
                      HD_TONGKINHPHI = ?, 
                      HD_GHICHU = ?" . 
                      ($new_filename ? ", HD_FILEHD = ?" : "") . 
                      " WHERE HD_MA = ?";
        
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            error_log("Failed to prepare contract update statement: " . $conn->error);
            throw new Exception("Lỗi chuẩn bị truy vấn cập nhật hợp đồng.");
        }
        
        if ($new_filename) {
            $stmt->bind_param("ssssdsss", $contract_code, $contract_date, $start_date, $end_date, 
                             $total_budget, $contract_description, $new_filename, $contract_id);
        } else {
            $stmt->bind_param("ssssdss", $contract_code, $contract_date, $start_date, $end_date, 
                             $total_budget, $contract_description, $contract_id);
        }
        
        if (!$stmt->execute()) {
            error_log("Failed to execute contract update statement: " . $stmt->error);
            throw new Exception("Không thể cập nhật thông tin hợp đồng.");
        }
        
        // Cập nhật thông tin trong bảng đề tài nếu mã hợp đồng thay đổi
        if ($contract_code !== $contract_id) {
            $update_project_sql = "UPDATE de_tai_nghien_cuu SET HD_MA = ? WHERE HD_MA = ?";
            $stmt = $conn->prepare($update_project_sql);
            $stmt->bind_param("ss", $contract_code, $contract_id);
            $stmt->execute();
        }
        
        error_log("Contract updated successfully - affected rows: " . $stmt->affected_rows);
        
    } else {
        // Tạo hợp đồng mới
        
        // Kiểm tra mã hợp đồng đã tồn tại chưa
        $check_contract_sql = "SELECT HD_MA FROM hop_dong WHERE HD_MA = ?";
        $stmt = $conn->prepare($check_contract_sql);
        $stmt->bind_param("s", $contract_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Mã hợp đồng đã tồn tại. Vui lòng chọn mã khác.");
        }

        $insert_sql = "INSERT INTO hop_dong (HD_MA, DT_MADT, HD_NGAYTAO, HD_NGAYBD, HD_NGAYKT, HD_TONGKINHPHI, HD_GHICHU, HD_FILEHD) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        error_log("Preparing contract insert with values:");
        error_log("contract_code: $contract_code");
        error_log("project_id: $project_id");
        error_log("contract_date: $contract_date");
        error_log("start_date: $start_date");
        error_log("end_date: $end_date");
        error_log("total_budget: $total_budget (" . gettype($total_budget) . ")");
        error_log("contract_description: " . substr($contract_description, 0, 50) . "...");
        error_log("new_filename: $new_filename");
        
        $stmt = $conn->prepare($insert_sql);
        if (!$stmt) {
            error_log("Failed to prepare contract insert statement: " . $conn->error);
            error_log("Insert SQL: " . $insert_sql);
            throw new Exception("Lỗi chuẩn bị truy vấn tạo hợp đồng.");
        }
        
        $stmt->bind_param("sssssdss", $contract_code, $project_id, $contract_date, $start_date, $end_date, 
                         $total_budget, $contract_description, $new_filename);
        
        error_log("Parameter binding completed successfully");
        
        if (!$stmt->execute()) {
            error_log("Failed to execute contract insert statement: " . $stmt->error);
            error_log("MySQL errno: " . $stmt->errno);
            throw new Exception("Không thể tạo hợp đồng mới.");
        }
        
        error_log("Contract insert executed successfully - affected rows: " . $stmt->affected_rows);
        
        // Cập nhật đề tài với mã hợp đồng mới
        $update_project_sql = "UPDATE de_tai_nghien_cuu SET HD_MA = ? WHERE DT_MADT = ?";
        $stmt = $conn->prepare($update_project_sql);
        $stmt->bind_param("ss", $contract_code, $project_id);
        if (!$stmt->execute()) {
            error_log("Failed to update project with contract: " . $stmt->error);
            throw new Exception("Không thể liên kết hợp đồng với đề tài.");
        }
        
        error_log("Contract created successfully - insert id: " . $conn->insert_id);
    }

    // Thêm vào tiến độ đề tài
    $progress_title = empty($contract_id) ? "Tạo hợp đồng mới" : "Cập nhật thông tin hợp đồng";
    $progress_content = "Thông tin hợp đồng đã được " . (empty($contract_id) ? "tạo mới" : "cập nhật") . ".\n\n";
    $progress_content .= "Lý do: " . $update_reason . "\n\n";
    $progress_content .= "Chi tiết hợp đồng:\n";
    $progress_content .= "- Mã hợp đồng: " . $contract_code . "\n";
    $progress_content .= "- Ngày tạo: " . date('d/m/Y', strtotime($contract_date)) . "\n";
    $progress_content .= "- Thời gian thực hiện: " . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date)) . "\n";
    $progress_content .= "- Tổng kinh phí: " . number_format($total_budget) . " VNĐ\n";
    if ($new_filename) {
        $progress_content .= "- File hợp đồng: " . $new_filename . "\n";
    }
    if ($contract_description) {
        $progress_content .= "- Mô tả: " . $contract_description . "\n";
    }

    // Tạo mã tiến độ mới (format: TD + YYMMDD + XX where XX is random 2 digits)
    $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));
    
    // Kiểm tra và tạo ID duy nhất nếu bị trùng
    $check_id_sql = "SELECT TDDT_MA FROM tien_do_de_tai WHERE TDDT_MA = ?";
    $check_stmt = $conn->prepare($check_id_sql);
    $attempts = 0;
    while ($attempts < 10) {
        $check_stmt->bind_param("s", $progress_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows == 0) {
            break; // ID is unique
        }
        $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));
        $attempts++;
    }

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

    // Xóa file cũ nếu có file mới và có file cũ
    if ($new_filename && $old_file && file_exists($upload_dir . $old_file)) {
        if (unlink($upload_dir . $old_file)) {
            error_log("Old contract file deleted successfully: " . $old_file);
        } else {
            error_log("Failed to delete old contract file: " . $old_file);
        }
    }

    // Commit transaction
    $conn->commit();
    error_log("Contract transaction committed successfully");

    $_SESSION['success_message'] = empty($contract_id) ? 
        "Tạo hợp đồng mới thành công!" : 
        "Cập nhật thông tin hợp đồng thành công!";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Xóa file đã upload nếu có lỗi
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    // Ghi log lỗi
    error_log("Update contract info error: " . $e->getMessage());
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
    error_log("Update contract info fatal error: " . $e->getMessage());
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
