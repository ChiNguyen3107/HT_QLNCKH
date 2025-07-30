<?php
// Bắt đầu output buffering để tránh lỗi header
ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

// Debug - kiểm tra session
error_log("Update report info - User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Update report info - Role: " . ($_SESSION['role'] ?? 'NOT SET'));

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
$report_id = trim($_POST['report_id'] ?? '');
$acceptance_date = trim($_POST['acceptance_date'] ?? '');
$evaluation_grade = trim($_POST['evaluation_grade'] ?? '');
$total_score = trim($_POST['total_score'] ?? '');
$council_members = trim($_POST['council_members'] ?? '');
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
    $_SESSION['error_message'] = "Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin biên bản nghiệm thu.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Debug POST data
error_log("Update report info - Project ID: " . $project_id);
error_log("Update report info - Decision ID: " . $decision_id);
error_log("Update report info - Report ID: " . $report_id);
error_log("Update report info - Update reason: " . $update_reason);

// Kiểm tra dữ liệu đầu vào
if (empty($project_id) || empty($decision_id) || empty($acceptance_date) || 
    empty($evaluation_grade) || empty($update_reason)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin bắt buộc.";
    header("Location: view_project.php?id=" . urlencode($project_id));
    exit();
}

// Validate total score nếu có
if (!empty($total_score)) {
    $total_score = (float) $total_score;
    if ($total_score < 0 || $total_score > 10) {
        $_SESSION['error_message'] = "Tổng điểm đánh giá phải từ 0 đến 10.";
        header("Location: view_project.php?id=" . urlencode($project_id));
        exit();
    }
} else {
    $total_score = null;
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
    $progress_content .= "Lý do: " . $update_reason . "\n\n";
    $progress_content .= "Chi tiết biên bản:\n";
    $progress_content .= "- Ngày nghiệm thu: " . date('d/m/Y', strtotime($acceptance_date)) . "\n";
    $progress_content .= "- Xếp loại: " . $evaluation_grade . "\n";
    if ($total_score !== null) {
        $progress_content .= "- Tổng điểm: " . number_format($total_score, 2) . "/10\n";
    }
    if ($council_members) {
        $progress_content .= "- Hội đồng nghiệm thu: " . $council_members . "\n";
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

    // Cập nhật trạng thái đề tài thành "Đã hoàn thành" nếu có kết quả nghiệm thu đạt
    if (in_array($evaluation_grade, ['Xuất sắc', 'Tốt', 'Khá', 'Đạt'])) {
        $update_status_sql = "UPDATE de_tai_nghien_cuu SET DT_TRANGTHAI = 'Đã hoàn thành' WHERE DT_MADT = ?";
        $stmt = $conn->prepare($update_status_sql);
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        error_log("Project status updated to completed");
    }

    // Commit transaction
    $conn->commit();
    error_log("Report transaction committed successfully");

    $_SESSION['success_message'] = "Cập nhật thông tin biên bản nghiệm thu thành công!";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update report info error: " . $e->getMessage());
    error_log("User ID: " . $user_id);
    error_log("Project ID: " . $project_id);
    
    $_SESSION['error_message'] = "Lỗi: " . $e->getMessage();
} catch (Error $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update report info fatal error: " . $e->getMessage());
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
