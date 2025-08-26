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
$report_id = trim($_POST['report_id'] ?? '');
$acceptance_date = trim($_POST['acceptance_date'] ?? '');
$evaluation_grade = trim($_POST['evaluation_grade'] ?? '');
$total_score = trim($_POST['total_score'] ?? '');
$council_members = trim($_POST['council_members_json'] ?? '');
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
error_log("Update report info - Project ID: " . $project_id);
error_log("Update report info - Decision ID: " . $decision_id);
error_log("Update report info - Report ID: " . $report_id);
error_log("Update report info - Acceptance Date: " . $acceptance_date);
error_log("Update report info - Evaluation Grade: " . $evaluation_grade);
error_log("Update report info - Total Score: " . $total_score);
error_log("Update report info - Council Members: " . $council_members);

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

    // Xử lý thành viên hội đồng
    if (!empty($council_members)) {
        error_log("Processing council members JSON: " . $council_members);
        
        // Parse council members JSON
        $members_data = json_decode($council_members, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            throw new Exception("Lỗi định dạng dữ liệu thành viên hội đồng.");
        }
        
        if ($members_data && is_array($members_data) && count($members_data) > 0) {
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
                $stmt->execute();
                error_log("Updated HD_THANHVIEN field with: " . $member_text);
            }
        } else {
            error_log("No valid council members data found or empty array");
        }
    } else {
        error_log("No council members data provided");
    }

    // Thêm vào tiến độ đề tài
    $progress_title = "Cập nhật thông tin biên bản nghiệm thu";
    $progress_content = "Thông tin biên bản nghiệm thu đã được cập nhật.\n\n";
    $progress_content .= "Chi tiết biên bản:\n";
    $progress_content .= "- Ngày nghiệm thu: " . date('d/m/Y', strtotime($acceptance_date)) . "\n";
    $progress_content .= "- Xếp loại: " . $evaluation_grade . "\n";
    if ($total_score !== null) {
        $progress_content .= "- Tổng điểm: " . number_format($total_score, 2) . "/100\n";
    }
    if ($council_members) {
        $progress_content .= "- Thành viên hội đồng nghiệm thu đã được cập nhật\n";
    }

    // Validate field lengths to prevent SQL errors
    if (strlen($project_id) > 10) {
        error_log("Project ID too long: " . $project_id . " (length: " . strlen($project_id) . ")");
        throw new Exception("Mã đề tài quá dài để ghi nhận tiến độ.");
    }
    
    if (strlen($user_id) > 8) {
        error_log("User ID too long: " . $user_id . " (length: " . strlen($user_id) . ")");
        throw new Exception("Mã sinh viên quá dài để ghi nhận tiến độ.");
    }
    
    if (strlen($progress_title) > 200) {
        $progress_title = substr($progress_title, 0, 197) . "...";
    }

    // Tạo mã tiến độ mới với kiểm tra duplicate (simplified approach)
    $progress_id = null;
    $base_id = 'TD' . date('ymd');
    
    // Try với timestamp để đảm bảo unique
    $progress_id = $base_id . substr(microtime(true) * 1000, -2);
    
    // Đảm bảo không vượt quá 10 ký tự
    if (strlen($progress_id) > 10) {
        $progress_id = substr($progress_id, 0, 10);
    }
    
    // Nếu vẫn bị trùng, thử với random number
    $check_sql = "SELECT TDDT_MA FROM tien_do_de_tai WHERE TDDT_MA = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param("s", $progress_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // ID bị trùng, thử với random
            $progress_id = $base_id . sprintf('%02d', rand(10, 99));
            if (strlen($progress_id) > 10) {
                $progress_id = substr($progress_id, 0, 10);
            }
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
        throw new Exception("Không thể ghi lại tiến độ cập nhật: " . $stmt->error);
    }
    
    error_log("Progress inserted successfully - insert id: " . $conn->insert_id);

    // Kiểm tra điều kiện hoàn thành đề tài (bao gồm việc tất cả thành viên hội đồng đã có điểm)
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
    error_log("Report transaction committed successfully");

    $_SESSION['success_message'] = "Cập nhật thông tin biên bản nghiệm thu thành công!";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update report info error: " . $e->getMessage());
    error_log("User ID: " . $user_id);
    error_log("Project ID: " . $project_id);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error_message'] = "Có lỗi hệ thống xảy ra: " . $e->getMessage();
} catch (Error $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Ghi log lỗi
    error_log("Update report info fatal error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error_message'] = "Có lỗi hệ thống nghiêm trọng xảy ra. Vui lòng thử lại sau.";
}

// Clean output buffer và redirect
ob_end_clean();

// Redirect về trang chi tiết đề tài
header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
exit();
?>
