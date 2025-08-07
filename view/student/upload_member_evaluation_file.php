<?php
// File: view/student/upload_member_evaluation_file.php
// Upload file đánh giá cho thành viên hội đồng

ob_start();

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

try {
    // Kiểm tra phương thức POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Phương thức không được phép");
    }
    
    // Lấy dữ liệu từ form
    $project_id = trim($_POST['project_id'] ?? '');
    $decision_id = trim($_POST['decision_id'] ?? '');
    $member_id = trim($_POST['member_id'] ?? '');
    $file_name = trim($_POST['file_name'] ?? '');
    $file_description = trim($_POST['file_description'] ?? '');
    $update_reason = trim($_POST['update_reason'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($project_id) || empty($decision_id) || empty($member_id) || empty($file_name)) {
        throw new Exception("Thiếu thông tin bắt buộc");
    }
    
    if (empty($update_reason)) {
        throw new Exception("Vui lòng nhập lý do upload file");
    }
    
    // Kiểm tra file upload
    if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Vui lòng chọn file để upload");
    }
    
    $uploaded_file = $_FILES['evaluation_file'];
    
    // Kiểm tra quyền chủ nhiệm
    $check_role_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
    $stmt = $conn->prepare($check_role_sql);
    $stmt->bind_param("ss", $project_id, $user_id);
    $stmt->execute();
    $role_result = $stmt->get_result();
    
    if ($role_result->num_rows === 0) {
        throw new Exception("Bạn không có quyền truy cập đề tài này");
    }
    
    $user_role = $role_result->fetch_assoc()['CTTG_VAITRO'];
    if ($user_role !== 'Chủ nhiệm') {
        throw new Exception("Chỉ chủ nhiệm đề tài mới có thể upload file đánh giá");
    }
    
    // Kiểm tra trạng thái đề tài
    $check_status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($check_status_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $status_result = $stmt->get_result();
    
    if ($status_result->num_rows === 0) {
        throw new Exception("Không tìm thấy đề tài");
    }
    
    $project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];
    if ($project_status !== 'Đang thực hiện') {
        throw new Exception("Chỉ có thể upload file khi đề tài đang trong trạng thái 'Đang thực hiện'");
    }
    
    // Kiểm tra thành viên hội đồng có tồn tại
    $check_member_sql = "SELECT tv.GV_MAGV, tv.TV_VAITRO, tv.TV_HOTEN, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN
                        FROM thanh_vien_hoi_dong tv
                        JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                        WHERE tv.QD_SO = ? AND tv.GV_MAGV = ?";
    $stmt = $conn->prepare($check_member_sql);
    $stmt->bind_param("ss", $decision_id, $member_id);
    $stmt->execute();
    $member_result = $stmt->get_result();
    
    if ($member_result->num_rows === 0) {
        throw new Exception("Thành viên hội đồng không tồn tại");
    }
    
    $member_info = $member_result->fetch_assoc();
    
    // Validate file
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    $max_size = 15 * 1024 * 1024; // 15MB
    
    if (!in_array($uploaded_file['type'], $allowed_types)) {
        throw new Exception("Chỉ cho phép upload file PDF, DOC, DOCX, TXT");
    }
    
    if ($uploaded_file['size'] > $max_size) {
        throw new Exception("Kích thước file không được vượt quá 15MB");
    }
    
    // Tạo thư mục upload nếu chưa có
    $upload_dir = '../../uploads/evaluation_files/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Tạo tên file unique
    $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
    $safe_filename = 'eval_' . $member_id . '_' . $decision_id . '_' . date('YmdHis') . '.' . $file_extension;
    $upload_path = $upload_dir . $safe_filename;
    
    // Upload file
    if (!move_uploaded_file($uploaded_file['tmp_name'], $upload_path)) {
        throw new Exception("Không thể lưu file. Vui lòng thử lại");
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    // Lấy số biên bản từ quyết định
    $bb_sql = "SELECT BB_SOBB FROM bien_ban WHERE QD_SO = ?";
    $stmt = $conn->prepare($bb_sql);
    $stmt->bind_param("s", $decision_id);
    $stmt->execute();
    $bb_result = $stmt->get_result();
    
    $bb_sobb = '';
    if ($bb_result->num_rows > 0) {
        $bb_sobb = $bb_result->fetch_assoc()['BB_SOBB'];
    } else {
        // Tạo biên bản mới nếu chưa có
        $bb_sobb = 'BB' . date('ymd') . sprintf('%04d', rand(1000, 9999));
        $create_bb_sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, ?, CURDATE(), 'Chưa xác định')";
        $stmt = $conn->prepare($create_bb_sql);
        $stmt->bind_param("ss", $bb_sobb, $decision_id);
        $stmt->execute();
    }
    
    // Lưu thông tin file vào database
    $file_id = 'FDG' . date('ymd') . sprintf('%06d', rand(100000, 999999));
    
    $insert_file_sql = "INSERT INTO file_dinh_kem (FDG_MA, BB_SOBB, GV_MAGV, FDG_LOAI, FDG_TENFILE, FDG_FILE, FDG_NGAYTAO, FDG_KICHTHUC, FDG_MOTA) 
                       VALUES (?, ?, ?, 'Đánh giá thành viên', ?, ?, NOW(), ?, ?)";
    
    $stmt = $conn->prepare($insert_file_sql);
    $stmt->bind_param("sssssds", $file_id, $bb_sobb, $member_id, $file_name, $safe_filename, $uploaded_file['size'], $file_description);
    
    if (!$stmt->execute()) {
        throw new Exception("Không thể lưu thông tin file vào database");
    }
    
    // Cập nhật file đánh giá cho thành viên
    $update_member_sql = "UPDATE thanh_vien_hoi_dong SET TV_FILEDANHGIA = ? WHERE QD_SO = ? AND GV_MAGV = ?";
    $stmt = $conn->prepare($update_member_sql);
    $stmt->bind_param("sss", $safe_filename, $decision_id, $member_id);
    $stmt->execute();
    
    // Ghi lại tiến độ
    $progress_title = "Upload file đánh giá cho thành viên hội đồng";
    $progress_content = "Đã upload file đánh giá cho thành viên hội đồng.\n\n";
    $progress_content .= "Lý do: " . $update_reason . "\n\n";
    $progress_content .= "Chi tiết:\n";
    $progress_content .= "- Thành viên: " . $member_info['GV_HOTEN'] . " (" . $member_info['TV_VAITRO'] . ")\n";
    $progress_content .= "- Tên file: " . $file_name . "\n";
    $progress_content .= "- Kích thước: " . number_format($uploaded_file['size'] / 1024, 2) . " KB\n";
    if ($file_description) {
        $progress_content .= "- Mô tả: " . $file_description . "\n";
    }
    
    // Tạo mã tiến độ mới
    $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));
    
    $progress_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 100)";
    $stmt = $conn->prepare($progress_sql);
    $stmt->bind_param("sssss", $progress_id, $project_id, $user_id, $progress_title, $progress_content);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Upload file đánh giá cho thành viên " . $member_info['GV_HOTEN'] . " thành công!";
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Xóa file đã upload nếu có lỗi database
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    $_SESSION['error_message'] = "Lỗi: " . $e->getMessage();
    
    // Log lỗi
    error_log("Upload member evaluation file error: " . $e->getMessage());
}

// Clean output buffer và redirect
ob_end_clean();

// Redirect về trang chi tiết đề tài
$redirect_url = isset($project_id) ? "view_project.php?id=" . urlencode($project_id) : "view_project.php";
header("Location: " . $redirect_url);
exit();
?>
