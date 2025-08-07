<?php
session_start();
require_once 'include/database.php';
require_once 'check_project_completion.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

// Lấy dữ liệu từ form
$member_id = trim($_POST['member_id'] ?? '');
$project_id = trim($_POST['project_id'] ?? '');
$file_name = trim($_POST['file_name'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate dữ liệu đầu vào
if (empty($member_id) || empty($project_id) || empty($file_name)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin bắt buộc'
    ]);
    exit;
}

// Kiểm tra file upload
if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi upload file: ' . ($_FILES['evaluation_file']['error'] ?? 'Không có file')
    ]);
    exit;
}

$uploaded_file = $_FILES['evaluation_file'];

// Validate file type
$allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
if (!in_array($uploaded_file['type'], $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ chấp nhận file PDF, DOC, DOCX, TXT'
    ]);
    exit;
}

// Validate file size (10MB)
$max_size = 10 * 1024 * 1024;
if ($uploaded_file['size'] > $max_size) {
    echo json_encode([
        'success' => false,
        'message' => 'File không được vượt quá 10MB'
    ]);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Lấy thông tin quyết định nghiệm thu
    $decision_sql = "SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($decision_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $decision = $stmt->get_result()->fetch_assoc();
    
    if (!$decision) {
        throw new Exception('Không tìm thấy quyết định nghiệm thu cho đề tài này');
    }
    
    $qd_so = $decision['QD_SO'];
    
    // Tạo thư mục upload nếu chưa có
    $upload_dir = 'uploads/member_evaluation_files/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Không thể tạo thư mục upload');
        }
    }
    
    // Tạo tên file unique
    $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
    $unique_filename = $project_id . '_' . $member_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;
    
    // Upload file
    if (!move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
        throw new Exception('Không thể lưu file');
    }
    
    // Lưu thông tin file vào database
    // Đầu tiên tạo bảng nếu chưa có
    $create_table_sql = "CREATE TABLE IF NOT EXISTS member_evaluation_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        gv_id VARCHAR(20) NOT NULL,
        project_id VARCHAR(50) NOT NULL,
        qd_so VARCHAR(50) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        description TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        uploaded_by VARCHAR(20) NOT NULL,
        status ENUM('Active', 'Deleted') DEFAULT 'Active',
        INDEX idx_gv_project (gv_id, project_id),
        INDEX idx_qd_so (qd_so),
        INDEX idx_upload_date (upload_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($create_table_sql);
    
    // Lưu thông tin file
    $insert_sql = "INSERT INTO member_evaluation_files 
                   (gv_id, project_id, qd_so, original_name, filename, file_size, file_type, description, uploaded_by) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssssssss", 
        $member_id, 
        $project_id, 
        $qd_so, 
        $file_name,
        $unique_filename, 
        $uploaded_file['size'], 
        $uploaded_file['type'], 
        $description, 
        $_SESSION['user_id']
    );
    
    $stmt->execute();
    $file_id = $conn->insert_id;
    
    // Cập nhật trạng thái có file đánh giá cho thành viên
    $update_member_sql = "UPDATE thanh_vien_hoi_dong 
                         SET TV_FILEDANHGIA = 'Có'
                         WHERE QD_SO = ? AND GV_MAGV = ?";
    $stmt = $conn->prepare($update_member_sql);
    $stmt->bind_param("ss", $qd_so, $member_id);
    $stmt->execute();
    
    // Thêm vào bảng file_dinh_kem để tương thích với hệ thống cũ
    $compat_sql = "INSERT INTO file_dinh_kem 
                   (QD_SO, GV_MAGV, FDG_TENFILE, FDG_FILE, FDG_NGAYTAO, FDG_KICHTHUC, FDG_MOTA, FDG_LOAI) 
                   VALUES (?, ?, ?, ?, NOW(), ?, ?, 'member_evaluation')";
    $stmt = $conn->prepare($compat_sql);
    $stmt->bind_param("ssssss", $qd_so, $member_id, $file_name, $unique_filename, $uploaded_file['size'], $description);
    $stmt->execute();
    
    $conn->commit();
    
    // Kiểm tra và tự động cập nhật trạng thái đề tài nếu đủ điều kiện
    $completion_check = autoCheckProjectCompletion($project_id, $conn);
    
    $response = [
        'success' => true,
        'message' => 'File đánh giá đã được tải lên thành công',
        'file_id' => $file_id,
        'filename' => $unique_filename,
        'original_name' => $file_name,
        'member_id' => $member_id
    ];
    
    // Thêm thông tin về việc hoàn thành đề tài nếu có
    if ($completion_check['changed']) {
        $response['project_completed'] = true;
        $response['completion_message'] = 'Đề tài đã được tự động chuyển sang trạng thái "Đã hoàn thành" do đã đáp ứng đầy đủ các yêu cầu.';
        $response['completion_details'] = $completion_check['requirements'];
    } else {
        $response['project_completed'] = false;
        $response['completion_status'] = $completion_check;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $conn->rollback();
    
    // Xóa file nếu đã upload
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi tải file: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
