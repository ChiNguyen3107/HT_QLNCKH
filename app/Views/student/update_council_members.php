<?php
// Bắt đầu output buffering để tránh lỗi header
ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Debug - kiểm tra session
error_log("Update council members - User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Update council members - Role: " . ($_SESSION['role'] ?? 'NOT SET'));

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    $_SESSION['error_message'] = "Lỗi kết nối cơ sở dữ liệu.";
    header("Location: student_manage_projects.php");
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
$council_members_json = trim($_POST['council_members_json'] ?? '');
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
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể cập nhật thành viên hội đồng nghiệm thu.";
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
// Cho phép cập nhật cả khi đề tài đã hoàn thành (theo yêu cầu mới)
if ($project_status !== 'Đang thực hiện' && $project_status !== 'Đã hoàn thành') {
    $_SESSION['error_message'] = "Chỉ có thể cập nhật thông tin khi đề tài đang thực hiện hoặc đã hoàn thành. Trạng thái hiện tại: " . $project_status;
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Debug POST data
error_log("Update council members - Project ID: " . $project_id);
error_log("Update council members - Decision ID: " . $decision_id);
error_log("Update council members - Council Members JSON: " . $council_members_json);

// Kiểm tra dữ liệu đầu vào
if (empty($project_id)) {
    $_SESSION['error_message'] = "Thiếu mã đề tài.";
    header("Location: student_manage_projects.php");
    exit();
}

if (empty($decision_id)) {
    $_SESSION['error_message'] = "Thiếu số quyết định nghiệm thu.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

if (empty($council_members_json)) {
    $_SESSION['error_message'] = "Vui lòng chọn ít nhất một thành viên hội đồng.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

try {
    // Bắt đầu transaction
    $conn->begin_transaction();

    // Kiểm tra sự tồn tại của quyết định
    $check_decision_sql = "SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
    $stmt = $conn->prepare($check_decision_sql);
    $stmt->bind_param("s", $decision_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Quyết định nghiệm thu không tồn tại.");
    }

    // Xử lý thành viên hội đồng
    error_log("Processing council members JSON: " . $council_members_json);
    
    // Parse council members JSON
    $members_data = json_decode($council_members_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        throw new Exception("Lỗi định dạng dữ liệu thành viên hội đồng.");
    }
    
    if (!$members_data || !is_array($members_data) || count($members_data) === 0) {
        throw new Exception("Vui lòng chọn ít nhất một thành viên hội đồng.");
    }

    error_log("Found " . count($members_data) . " council members to process");
    
    // Lấy thông tin giảng viên hướng dẫn của đề tài
    $supervisor_sql = "SELECT dt.GV_MAGV FROM de_tai_nghien_cuu dt WHERE dt.DT_MADT = ?";
    $stmt = $conn->prepare($supervisor_sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn lấy giảng viên hướng dẫn: " . $conn->error);
    }
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $supervisor_result = $stmt->get_result();
    
    if ($supervisor_result->num_rows === 0) {
        throw new Exception('Không tìm thấy thông tin đề tài');
    }
    
    $supervisor = $supervisor_result->fetch_assoc();
    $supervisor_id = $supervisor['GV_MAGV'];
    error_log("Project supervisor ID: " . $supervisor_id);

    // Kiểm tra xem có giảng viên hướng dẫn trong danh sách thành viên không
    foreach ($members_data as $member) {
        $gv_magv = $member['id'] ?? '';
        if ($gv_magv === $supervisor_id) {
            throw new Exception('Không thể thêm giảng viên hướng dẫn vào thành viên hội đồng. Giảng viên hướng dẫn không được phép tham gia hội đồng nghiệm thu của đề tài mình hướng dẫn.');
        }
    }
    
    // Xóa thành viên hội đồng cũ
    $delete_members_sql = "DELETE FROM thanh_vien_hoi_dong WHERE QD_SO = ?";
    $stmt = $conn->prepare($delete_members_sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn xóa thành viên hội đồng cũ: " . $conn->error);
    }
    $stmt->bind_param("s", $decision_id);
    if (!$stmt->execute()) {
        throw new Exception("Không thể xóa thành viên hội đồng cũ: " . $stmt->error);
    }
    error_log("Deleted old council members for decision: " . $decision_id);
    
    // Thêm thành viên mới
    $insert_member_sql = "INSERT INTO thanh_vien_hoi_dong (QD_SO, GV_MAGV, TC_MATC, TV_VAITRO, TV_DIEM, TV_DANHGIA) VALUES (?, ?, ?, ?, 0, 'Chưa đánh giá')";
    $stmt = $conn->prepare($insert_member_sql);
    
    if (!$stmt) {
        error_log("Failed to prepare council member insert statement: " . $conn->error);
        throw new Exception("Lỗi chuẩn bị truy vấn thêm thành viên hội đồng.");
    }
    
    $inserted_count = 0;
    foreach ($members_data as $member) {
        $gv_magv = $member['id'] ?? '';
        $vaitro = $member['role'] ?? '';
        $hoten = $member['name'] ?? '';
        $tc_matc = 'TC001'; // Default value
        
        if (!empty($gv_magv) && !empty($vaitro)) {
            $stmt->bind_param("ssss", $decision_id, $gv_magv, $tc_matc, $vaitro);
            if (!$stmt->execute()) {
                error_log("Failed to execute council member insert: " . $stmt->error);
                error_log("Member data: QD_SO=$decision_id, GV_MAGV=$gv_magv, TC_MATC=$tc_matc, TV_VAITRO=$vaitro");
                throw new Exception("Không thể thêm thành viên hội đồng: " . $stmt->error);
            }
            $inserted_count++;
            error_log("Council member inserted: $hoten ($vaitro) - ID: $gv_magv");
        } else {
            error_log("Skipping member with missing data: " . json_encode($member));
        }
    }
    
    if ($inserted_count === 0) {
        throw new Exception("Không có thành viên nào được thêm vào hội đồng.");
    }
    
    error_log("Successfully inserted $inserted_count council members");
    
    // Cập nhật trường HD_THANHVIEN trong bảng quyet_dinh_nghiem_thu  
    $member_text = '';
    foreach ($members_data as $member) {
        $hoten = $member['name'] ?? '';
        $vaitro = $member['role'] ?? '';
        if (!empty($hoten) && !empty($vaitro)) {
            $member_text .= $hoten . ' (' . $vaitro . ')' . "\n";
        }
    }
    $member_text = rtrim($member_text, "\n");
    
    $update_decision_sql = "UPDATE quyet_dinh_nghiem_thu SET HD_THANHVIEN = ? WHERE QD_SO = ?";
    $stmt = $conn->prepare($update_decision_sql);
    if ($stmt) {
        $stmt->bind_param("ss", $member_text, $decision_id);
        if (!$stmt->execute()) {
            error_log("Failed to update HD_THANHVIEN: " . $stmt->error);
        } else {
            error_log("Updated HD_THANHVIEN field with: " . $member_text);
        }
    }

    // Thêm vào tiến độ đề tài
    $progress_title = "Cập nhật thành viên hội đồng nghiệm thu";
    $progress_content = "Thành viên hội đồng nghiệm thu đã được cập nhật.\n\n";
    $progress_content .= "Danh sách thành viên:\n";
    foreach ($members_data as $member) {
        $hoten = $member['name'] ?? '';
        $vaitro = $member['role'] ?? '';
        if (!empty($hoten) && !empty($vaitro)) {
            $progress_content .= "- " . $hoten . " (" . $vaitro . ")\n";
        }
    }

    // Tạo mã tiến độ mới (đảm bảo không quá 10 ký tự và unique)
    $progress_id = null;
    $attempts = 0;
    $max_attempts = 10;
    
    do {
        $timestamp = date('ymd');
        $random = rand(10, 99);
        $progress_id = 'TD' . $timestamp . $random;
        
        // Đảm bảo mã không quá 10 ký tự
        if (strlen($progress_id) > 10) {
            $progress_id = substr($progress_id, 0, 10);
        }
        
        // Kiểm tra xem mã đã tồn tại chưa
        $check_sql = "SELECT 1 FROM tien_do_de_tai WHERE TDDT_MA = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param("s", $progress_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->num_rows > 0;
            $check_stmt->close();
            
            if (!$exists) {
                break; // Mã unique, thoát khỏi vòng lặp
            }
        }
        
        $attempts++;
    } while ($attempts < $max_attempts);
    
    // Nếu không tạo được mã unique, sử dụng timestamp
    if ($attempts >= $max_attempts) {
        $progress_id = 'TD' . time();
        if (strlen($progress_id) > 10) {
            $progress_id = substr($progress_id, 0, 10);
        }
    }

    $progress_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 100)";
    $stmt = $conn->prepare($progress_sql);
    if (!$stmt) {
        error_log("Failed to prepare progress statement: " . $conn->error);
        throw new Exception("Lỗi chuẩn bị truy vấn tiến độ: " . $conn->error);
    }
    
    $stmt->bind_param("sssss", $progress_id, $project_id, $user_id, $progress_title, $progress_content);
    
    if (!$stmt->execute()) {
        error_log("Failed to execute progress statement: " . $stmt->error);
        error_log("Progress data - ID: $progress_id, Project: $project_id, User: $user_id");
        error_log("Progress title length: " . strlen($progress_title));
        error_log("Progress content length: " . strlen($progress_content));
        // Không throw exception cho progress vì không critical
    } else {
        error_log("Progress inserted successfully - ID: $progress_id");
        error_log("Progress details - Title: $progress_title");
        error_log("Progress details - Content length: " . strlen($progress_content));
        error_log("Progress details - Project: $project_id, User: $user_id");
    }

    // Commit transaction
    $conn->commit();
    error_log("Council members transaction committed successfully");

    $_SESSION['success_message'] = "Cập nhật thành viên hội đồng nghiệm thu thành công! Đã thêm $inserted_count thành viên. Thông tin này đã được ghi lại trong tiến độ đề tài.";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update council members error: " . $e->getMessage());
    error_log("User ID: " . $user_id);
    error_log("Project ID: " . $project_id);
    error_log("Decision ID: " . $decision_id);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error_message'] = "Lỗi cập nhật thành viên hội đồng: " . $e->getMessage();
} catch (Error $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update council members fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error_message'] = "Có lỗi hệ thống nghiêm trọng xảy ra khi cập nhật thành viên hội đồng.";
}

// Clean output buffer và redirect
ob_end_clean();

// Redirect về trang chi tiết đề tài (tiến độ sẽ được hiển thị ở phần chính)
$redirect_url = "view_project.php?id=" . urlencode($project_id);
header("Location: " . $redirect_url);
exit();
?>
