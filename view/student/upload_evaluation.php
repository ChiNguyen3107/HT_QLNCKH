<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? '';
    $file_type = $_POST['file_type'] ?? '';
    $file_description = $_POST['file_description'] ?? '';

    try {
        if (empty($project_id)) {
            throw new Exception('Thiếu mã đề tài');
        }

        // Kiểm tra quyền truy cập
        $check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
        $stmt = $conn->prepare($check_access_sql);
        $stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
        $stmt->execute();
        $access_result = $stmt->get_result();
        $has_access = ($access_result->num_rows > 0);
        $user_role = $has_access ? $access_result->fetch_assoc()['CTTG_VAITRO'] : '';

        if (!$has_access || $user_role !== 'Chủ nhiệm') {
            throw new Exception('Bạn không có quyền upload file đánh giá');
        }

        // Lấy thông tin biên bản
        $bb_sql = "SELECT bb.BB_SOBB FROM bien_ban bb 
                   JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO 
                   WHERE qd.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";
        $stmt = $conn->prepare($bb_sql);
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $bb_result = $stmt->get_result();
        
        if ($bb_result->num_rows === 0) {
            throw new Exception('Không tìm thấy biên bản nghiệm thu cho đề tài này');
        }
        
        $bb_info = $bb_result->fetch_assoc();

        // Xử lý upload file
        if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Có lỗi khi upload file');
        }

        $file = $_FILES['evaluation_file'];
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception('Chỉ chấp nhận file PDF, Word hoặc Excel');
        }

        // Tạo tên file unique
        $upload_dir = '../../uploads/reports/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = 'evaluation_' . $bb_info['BB_SOBB'] . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception('Không thể lưu file');
        }

        // Lưu thông tin file vào database
        $insert_sql = "INSERT INTO file_dinh_kem (BB_SOBB, FDG_TENFILE, FDG_DUONGDAN, FDG_LOAI, FDG_MOTA, FDG_NGAYTAO) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssss", 
            $bb_info['BB_SOBB'], 
            $file['name'], 
            $file_name, 
            $file_type, 
            $file_description
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Đã upload file đánh giá thành công';
        } else {
            // Xóa file nếu không lưu được database
            unlink($file_path);
            throw new Exception('Không thể lưu thông tin file vào database');
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }

    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Redirect nếu không phải POST
header('Location: student_manage_projects.php');
exit;
?>
