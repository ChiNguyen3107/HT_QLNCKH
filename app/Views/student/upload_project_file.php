<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Phương thức yêu cầu không hợp lệ.";
    header('Location: student_manage_projects.php');
    exit;
}

// Lấy thông tin đầu vào
$project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';
$file_type = isset($_POST['file_type']) ? trim($_POST['file_type']) : '';
$student_id = $_SESSION['user_id'];

if (empty($project_id) || empty($file_type)) {
    $_SESSION['error_message'] = "Thiếu thông tin cần thiết.";
    header('Location: student_manage_projects.php');
    exit;
}

// Kiểm tra quyền truy cập: sinh viên phải tham gia đề tài và là chủ nhiệm
$check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia 
                    WHERE DT_MADT = ? AND SV_MASV = ? AND CTTG_VAITRO = 'Chủ nhiệm'";
$stmt = $conn->prepare($check_access_sql);
$stmt->bind_param("ss", $project_id, $student_id);
$stmt->execute();
$access_result = $stmt->get_result();

if ($access_result->num_rows === 0) {
    $_SESSION['error_message'] = "Bạn không có quyền tải lên file cho đề tài này hoặc không phải là chủ nhiệm đề tài.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Kiểm tra trạng thái đề tài
$status_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$status_result = $stmt->get_result();
$project_status = $status_result->fetch_assoc()['DT_TRANGTHAI'];

if ($project_status !== 'Đang thực hiện' && $project_status !== 'Đã hoàn thành') {
    $_SESSION['error_message'] = "Chỉ có thể tải lên file cho đề tài đang thực hiện hoặc đã hoàn thành.";
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Xử lý file upload dựa vào loại file
switch ($file_type) {
    case 'contract':
        handleContractFile($conn, $project_id);
        break;
    
    case 'decision':
        handleDecisionFile($conn, $project_id);
        break;
        
    case 'evaluation':
        handleEvaluationFile($conn, $project_id);
        break;
        
    default:
        $_SESSION['error_message'] = "Loại file không hợp lệ.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
}

// Hàm xử lý file hợp đồng
function handleContractFile($conn, $project_id) {
    $contract_id = isset($_POST['contract_id']) ? trim($_POST['contract_id']) : '';
    
    if (empty($contract_id)) {
        $_SESSION['error_message'] = "Không tìm thấy mã hợp đồng.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra file đã được tải lên
    if (!isset($_FILES['contract_file']) || $_FILES['contract_file']['error'] != 0) {
        $_SESSION['error_message'] = "Vui lòng chọn file hợp đồng để tải lên.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    $upload_dir = '../../uploads/contract_files/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Xử lý upload file
    $file_name = $_FILES['contract_file']['name'];
    $file_tmp = $_FILES['contract_file']['tmp_name'];
    $file_size = $_FILES['contract_file']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Kiểm tra phần mở rộng file
    $allowed_extensions = array('pdf', 'doc', 'docx');
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['error_message'] = "Định dạng file không hợp lệ. Vui lòng sử dụng: PDF, DOC, DOCX.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra kích thước file (tối đa 10MB)
    if ($file_size > 10 * 1024 * 1024) {
        $_SESSION['error_message'] = "Kích thước file không được vượt quá 10MB.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Tạo tên file mới
    $new_file_name = 'contract_' . $contract_id . '_' . time() . '.' . $file_ext;
    $file_destination = $upload_dir . $new_file_name;
    
    // Lấy tên file cũ (nếu có) để xóa
    $old_file_sql = "SELECT HD_FILEHD FROM hop_dong WHERE HD_MA = ? AND DT_MADT = ?";
    $stmt = $conn->prepare($old_file_sql);
    $stmt->bind_param("ss", $contract_id, $project_id);
    $stmt->execute();
    $old_file_result = $stmt->get_result();
    $old_file = $old_file_result->fetch_assoc()['HD_FILEHD'];
    
    // Upload file mới
    if (move_uploaded_file($file_tmp, $file_destination)) {
        // Cập nhật đường dẫn file trong cơ sở dữ liệu
        $update_sql = "UPDATE hop_dong SET HD_FILEHD = ? WHERE HD_MA = ? AND DT_MADT = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sss", $new_file_name, $contract_id, $project_id);
        
        if ($stmt->execute()) {
            // Xóa file cũ nếu có
            if (!empty($old_file) && file_exists($upload_dir . $old_file)) {
                unlink($upload_dir . $old_file);
            }
            
            $_SESSION['success_message'] = "File hợp đồng đã được cập nhật thành công.";
        } else {
            $_SESSION['error_message'] = "Không thể cập nhật file hợp đồng trong cơ sở dữ liệu.";
            // Xóa file mới nếu không thể cập nhật cơ sở dữ liệu
            if (file_exists($file_destination)) {
                unlink($file_destination);
            }
        }
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi tải file hợp đồng lên.";
    }
    
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Hàm xử lý file quyết định
function handleDecisionFile($conn, $project_id) {
    $decision_id = isset($_POST['decision_id']) ? trim($_POST['decision_id']) : '';
    
    if (empty($decision_id)) {
        $_SESSION['error_message'] = "Không tìm thấy số quyết định.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra file đã được tải lên
    if (!isset($_FILES['decision_file']) || $_FILES['decision_file']['error'] != 0) {
        $_SESSION['error_message'] = "Vui lòng chọn file quyết định để tải lên.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    $upload_dir = '../../uploads/decision_files/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Xử lý upload file
    $file_name = $_FILES['decision_file']['name'];
    $file_tmp = $_FILES['decision_file']['tmp_name'];
    $file_size = $_FILES['decision_file']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Kiểm tra phần mở rộng file
    $allowed_extensions = array('pdf', 'doc', 'docx');
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['error_message'] = "Định dạng file không hợp lệ. Vui lòng sử dụng: PDF, DOC, DOCX.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra kích thước file (tối đa 10MB)
    if ($file_size > 10 * 1024 * 1024) {
        $_SESSION['error_message'] = "Kích thước file không được vượt quá 10MB.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Tạo tên file mới
    $new_file_name = 'decision_' . $decision_id . '_' . time() . '.' . $file_ext;
    $file_destination = $upload_dir . $new_file_name;
    
    // Lấy tên file cũ (nếu có) để xóa
    $old_file_sql = "SELECT QD_FILE FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
    $stmt = $conn->prepare($old_file_sql);
    $stmt->bind_param("s", $decision_id);
    $stmt->execute();
    $old_file_result = $stmt->get_result();
    $old_file = $old_file_result->fetch_assoc()['QD_FILE'];
    
    // Upload file mới
    if (move_uploaded_file($file_tmp, $file_destination)) {
        // Cập nhật đường dẫn file trong cơ sở dữ liệu
        $update_sql = "UPDATE quyet_dinh_nghiem_thu SET QD_FILE = ? WHERE QD_SO = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ss", $new_file_name, $decision_id);
        
        if ($stmt->execute()) {
            // Xóa file cũ nếu có
            if (!empty($old_file) && file_exists($upload_dir . $old_file)) {
                unlink($upload_dir . $old_file);
            }
            
            $_SESSION['success_message'] = "File quyết định đã được cập nhật thành công.";
        } else {
            $_SESSION['error_message'] = "Không thể cập nhật file quyết định trong cơ sở dữ liệu.";
            // Xóa file mới nếu không thể cập nhật cơ sở dữ liệu
            if (file_exists($file_destination)) {
                unlink($file_destination);
            }
        }
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi tải file quyết định lên.";
    }
    
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

// Hàm xử lý file đánh giá
function handleEvaluationFile($conn, $project_id) {
    $report_id = isset($_POST['report_id']) ? trim($_POST['report_id']) : '';
    $evaluation_name = isset($_POST['evaluation_name']) ? trim($_POST['evaluation_name']) : '';
    
    if (empty($report_id) || empty($evaluation_name)) {
        $_SESSION['error_message'] = "Thiếu thông tin cần thiết cho file đánh giá.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra file đã được tải lên
    if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] != 0) {
        $_SESSION['error_message'] = "Vui lòng chọn file đánh giá để tải lên.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    $upload_dir = '../../uploads/evaluation_files/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Xử lý upload file
    $file_name = $_FILES['evaluation_file']['name'];
    $file_tmp = $_FILES['evaluation_file']['tmp_name'];
    $file_size = $_FILES['evaluation_file']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Kiểm tra phần mở rộng file
    $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx');
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['error_message'] = "Định dạng file không hợp lệ. Vui lòng sử dụng: PDF, DOC, DOCX, XLS, XLSX.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Kiểm tra kích thước file (tối đa 10MB)
    if ($file_size > 10 * 1024 * 1024) {
        $_SESSION['error_message'] = "Kích thước file không được vượt quá 10MB.";
        header('Location: view_project.php?id=' . urlencode($project_id));
        exit;
    }
    
    // Tạo mã file đánh giá
    $evaluation_id = 'FDG' . date('ymd') . rand(100, 999);
    
    // Tạo tên file mới
    $new_file_name = 'evaluation_' . $evaluation_id . '_' . time() . '.' . $file_ext;
    $file_destination = $upload_dir . $new_file_name;
    
    // Upload file mới
    if (move_uploaded_file($file_tmp, $file_destination)) {
        // Thêm thông tin file đánh giá vào cơ sở dữ liệu
        $insert_sql = "INSERT INTO file_danh_gia (FDG_MA, BB_SOBB, FDG_TEN, FDG_DUONGDAN, FDG_NGAYCAP) 
                      VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssss", $evaluation_id, $report_id, $evaluation_name, $new_file_name);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "File đánh giá đã được tải lên thành công.";
        } else {
            $_SESSION['error_message'] = "Không thể lưu thông tin file đánh giá: " . $stmt->error;
            // Xóa file mới nếu không thể cập nhật cơ sở dữ liệu
            if (file_exists($file_destination)) {
                unlink($file_destination);
            }
        }
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi tải file đánh giá lên.";
    }
    
    header('Location: view_project.php?id=' . urlencode($project_id));
    exit;
}

$conn->close();
?>