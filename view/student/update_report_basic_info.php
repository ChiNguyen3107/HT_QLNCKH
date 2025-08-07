<?php
// Bắt đầu output buffering để tránh lỗi header
ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Debug - kiểm tra session
error_log("Update report basic info - User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Update report basic info - Role: " . ($_SESSION['role'] ?? 'NOT SET'));

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
$acceptance_date = trim($_POST['acceptance_date'] ?? '');
$evaluation_grade = trim($_POST['evaluation_grade'] ?? '');
$total_score = trim($_POST['total_score'] ?? '');
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền chủ nhiệm trước khi xử lý
$check_role_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_role_sql);
$stmt->bind_param("ss", $project_id, $user_id);
$stmt->execute();
$role_result = $stmt->get_result();

if ($role_result->num_rows === 0) {
    $_SESSION['error_message'] = "Bạn không có quyền truy cập đề tài này.";
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
    exit();
}

$user_role = $role_result->fetch_assoc()['CTTG_VAITRO'];
if ($user_role !== 'Chủ nhiệm') {
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin biên bản nghiệm thu.";
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
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
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
    exit();
}

$project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];
if ($project_status !== 'Đang thực hiện' && $project_status !== 'Đã hoàn thành') {
    $_SESSION['error_message'] = "Chỉ có thể cập nhật thông tin khi đề tài đang thực hiện hoặc đã hoàn thành. Trạng thái hiện tại: " . $project_status;
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
    exit();
}

// Debug POST data
error_log("Update report basic info - Project ID: " . $project_id);
error_log("Update report basic info - Decision ID: " . $decision_id);
error_log("Update report basic info - Acceptance Date: " . $acceptance_date);
error_log("Update report basic info - Evaluation Grade: " . $evaluation_grade);
error_log("Update report basic info - Total Score: " . $total_score);

// Kiểm tra dữ liệu đầu vào
if (empty($project_id)) {
    $_SESSION['error_message'] = "Thiếu mã đề tài.";
    header("Location: student_manage_projects.php");
    exit();
}

if (empty($decision_id)) {
    $_SESSION['error_message'] = "Thiếu số quyết định nghiệm thu.";
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
    exit();
}

if (empty($acceptance_date) || empty($evaluation_grade)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin bắt buộc (Ngày nghiệm thu, Xếp loại đánh giá).";
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
    exit();
}

// Validate total score nếu có
if (!empty($total_score)) {
    $total_score = (float) $total_score;
    if ($total_score < 0 || $total_score > 100) {
        $_SESSION['error_message'] = "Tổng điểm đánh giá phải từ 0 đến 100.";
        header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
        exit();
    }
} else {
    $total_score = null;
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

    // Cập nhật biên bản nghiệm thu
    if (!empty($total_score)) {
        $update_report_sql = "UPDATE bien_ban SET 
                             BB_NGAYNGHIEMTHU = ?, 
                             BB_XEPLOAI = ?,
                             BB_TONGDIEM = ?
                             WHERE QD_SO = ?";
        
        $stmt = $conn->prepare($update_report_sql);
        if (!$stmt) {
            error_log("Failed to prepare report update statement: " . $conn->error);
            throw new Exception("Lỗi chuẩn bị truy vấn cập nhật biên bản.");
        }
        
        $stmt->bind_param("ssds", $acceptance_date, $evaluation_grade, $total_score, $decision_id);
    } else {
        $update_report_sql = "UPDATE bien_ban SET 
                             BB_NGAYNGHIEMTHU = ?, 
                             BB_XEPLOAI = ?
                             WHERE QD_SO = ?";
        
        $stmt = $conn->prepare($update_report_sql);
        if (!$stmt) {
            error_log("Failed to prepare report update statement: " . $conn->error);
            throw new Exception("Lỗi chuẩn bị truy vấn cập nhật biên bản.");
        }
        
        $stmt->bind_param("sss", $acceptance_date, $evaluation_grade, $decision_id);
    }
    
    if (!$stmt->execute()) {
        error_log("Failed to execute report update statement: " . $stmt->error);
        throw new Exception("Không thể cập nhật thông tin biên bản nghiệm thu.");
    }
    
    error_log("Report updated successfully");

    // Thêm vào tiến độ đề tài
    $progress_title = "Cập nhật thông tin biên bản nghiệm thu";
    $progress_content = "Thông tin biên bản nghiệm thu đã được cập nhật.\n\n";
    $progress_content .= "Chi tiết biên bản:\n";
    $progress_content .= "- Ngày nghiệm thu: " . date('d/m/Y', strtotime($acceptance_date)) . "\n";
    $progress_content .= "- Xếp loại: " . $evaluation_grade . "\n";
    if ($total_score !== null) {
        $progress_content .= "- Tổng điểm: " . number_format($total_score, 2) . "/100\n";
    }

    // Tạo mã tiến độ mới
    $progress_id = 'TD' . date('ymdHis') . rand(10, 99);
    if (strlen($progress_id) > 10) {
        $progress_id = substr($progress_id, 0, 10);
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
        // Không throw exception cho progress vì không critical
    } else {
        error_log("Progress inserted successfully");
    }

    // Kiểm tra điều kiện hoàn thành đề tài
    include_once '../../include/project_completion_functions.php';
    
    if (in_array($evaluation_grade, ['Xuất sắc', 'Tốt', 'Khá', 'Đạt'])) {
        // Chỉ cập nhật trạng thái nếu đủ tất cả điều kiện
        $completion_updated = updateProjectStatusIfComplete($project_id, $conn);
        
        if ($completion_updated) {
            error_log("Project status updated to completed - all conditions met");
        } else {
            error_log("Project not completed yet - waiting for all council member scores");
        }
    }

    // Commit transaction
    $conn->commit();
    error_log("Report basic info transaction committed successfully");

    $_SESSION['success_message'] = "Cập nhật thông tin biên bản nghiệm thu thành công!";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update report basic info error: " . $e->getMessage());
    error_log("User ID: " . $user_id);
    error_log("Project ID: " . $project_id);
    error_log("Decision ID: " . $decision_id);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error_message'] = "Lỗi cập nhật thông tin biên bản: " . $e->getMessage();
} catch (Error $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update report basic info fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error_message'] = "Có lỗi hệ thống nghiêm trọng xảy ra khi cập nhật biên bản.";
}

// Clean output buffer và redirect
ob_end_clean();

// Redirect về trang chi tiết đề tài với tab báo cáo
$redirect_url = "view_project.php?id=" . urlencode($project_id) . "&tab=report";
header("Location: " . $redirect_url);
exit();
?>
