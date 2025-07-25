<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\update_project_basic.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Phương thức không được hỗ trợ!";
    header("Location: list_projects.php");
    exit();
}

try {
    // Lấy dữ liệu từ form
    $project_id = $_POST['DT_MADT'];
    $project_title = trim($_POST['DT_TENDT']);
    $project_description = trim($_POST['DT_MOTA']);
    $project_status = $_POST['DT_TRANGTHAI'];
    $advisor_id = $_POST['GV_MAGV'];
    $category_id = $_POST['LDT_MA'];
    $research_field_id = $_POST['LVNC_MA'];
    $priority_field_id = $_POST['LVUT_MA'];
    
    // Validate dữ liệu cơ bản
    if (empty($project_id) || empty($project_title)) {
        throw new Exception("Vui lòng nhập đầy đủ thông tin bắt buộc!");
    }
    
    // Lấy thông tin hiện tại của đề tài
    $current_query = "SELECT QD_SO, HD_MA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $current_stmt = $conn->prepare($current_query);
    $current_stmt->bind_param("s", $project_id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    
    if ($current_result->num_rows == 0) {
        throw new Exception("Không tìm thấy đề tài!");
    }
    
    $current_data = $current_result->fetch_assoc();
    $current_decision_id = $current_data['QD_SO'];
    $current_contract_id = $current_data['HD_MA'];
    $current_status = $current_data['DT_TRANGTHAI'];
    
    // Lấy hoặc giữ nguyên QD_SO
    $decision_id = !empty($_POST['QD_SO']) ? $_POST['QD_SO'] : $current_decision_id;
    
    // Lấy hoặc giữ nguyên HD_MA
    $contract_id = !empty($_POST['HD_MA']) ? $_POST['HD_MA'] : $current_contract_id;
    
    // Nếu đang cập nhật từ "Chờ duyệt" sang "Đã duyệt", cần kiểm tra các trường bắt buộc
    if ($current_status == 'Chờ duyệt' && $project_status == 'Đã duyệt') {
        // Chỉ cần kiểm tra nếu không có giá trị sẵn
        if (empty($advisor_id)) {
            throw new Exception("Vui lòng chọn giảng viên hướng dẫn!");
        }
    }

    // Bắt đầu transaction
    $conn->begin_transaction();
    
    // Tạo câu truy vấn UPDATE dựa trên các trường có sẵn
    $update_fields = [
        "DT_TENDT = ?",
        "DT_MOTA = ?",
        "DT_TRANGTHAI = ?",
        "GV_MAGV = ?",
        "LDT_MA = ?",
        "LVNC_MA = ?",
        "LVUT_MA = ?"
    ];
    
    $params = [
        $project_title,
        $project_description,
        $project_status,
        $advisor_id,
        $category_id,
        $research_field_id,
        $priority_field_id
    ];
    $types = "sssssss";
    
    // Thêm QD_SO nếu có
    if (!empty($decision_id)) {
        $update_fields[] = "QD_SO = ?";
        $params[] = $decision_id;
        $types .= "s";
    }
    
    // Thêm HD_MA nếu có
    if (!empty($contract_id)) {
        $update_fields[] = "HD_MA = ?";
        $params[] = $contract_id;
        $types .= "s";
    }
    
    // Thêm project_id vào cuối mảng params
    $params[] = $project_id;
    $types .= "s";
    
    $update_query = "UPDATE de_tai_nghien_cuu SET " . implode(", ", $update_fields) . " WHERE DT_MADT = ?";
    $stmt = $conn->prepare($update_query);
    
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị câu truy vấn: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi cập nhật thông tin đề tài: " . $stmt->error);
    }
    
    // Xử lý upload file nếu có
    if (isset($_FILES['DT_FILEBTM']) && $_FILES['DT_FILEBTM']['error'] === UPLOAD_ERR_OK) {
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        $file_extension = pathinfo($_FILES['DT_FILEBTM']['name'], PATHINFO_EXTENSION);
        
        // Kiểm tra định dạng file
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            throw new Exception("Chỉ chấp nhận file PDF, DOC, DOCX!");
        }
        
        // Tạo thư mục lưu trữ nếu chưa có
        $upload_dir = '../../../uploads/project_outlines/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Tạo tên file duy nhất
        $new_file_name = uniqid('outline_') . '_' . $_FILES['DT_FILEBTM']['name'];
        $file_path = $upload_dir . $new_file_name;
        
        // Di chuyển file tải lên vào thư mục lưu trữ
        if (!move_uploaded_file($_FILES['DT_FILEBTM']['tmp_name'], $file_path)) {
            throw new Exception("Không thể lưu file đính kèm!");
        }
        
        // Cập nhật đường dẫn file trong CSDL
        $update_file_query = "UPDATE de_tai_nghien_cuu SET DT_FILEBTM = ? WHERE DT_MADT = ?";
        $file_stmt = $conn->prepare($update_file_query);
        
        if ($file_stmt === false) {
            throw new Exception("Lỗi chuẩn bị câu truy vấn cập nhật file: " . $conn->error);
        }
        
        $file_stmt->bind_param("ss", $file_path, $project_id);
        
        if (!$file_stmt->execute()) {
            throw new Exception("Lỗi khi cập nhật đường dẫn file: " . $file_stmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Thông báo thành công và chuyển hướng
    $_SESSION['success_message'] = "Cập nhật thông tin đề tài thành công!";
    header("Location: edit_project.php?id=$project_id");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    if ($conn->connect_errno == 0) {
        $conn->rollback();
    }
    
    // Thông báo lỗi và chuyển hướng
    $_SESSION['error_message'] = "Lỗi: " . $e->getMessage();
    
    if (isset($_POST['DT_MADT'])) {
        header("Location: edit_project.php?id=" . $_POST['DT_MADT']);
    } else {
        header("Location: list_projects.php");
    }
    exit();
}
?>