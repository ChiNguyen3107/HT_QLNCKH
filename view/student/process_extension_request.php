<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\process_extension_request.php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không được hỗ trợ');
    }
    
    $student_id = $_SESSION['user_id'];
    $project_id = trim($_POST['project_id'] ?? '');
    $current_deadline = trim($_POST['current_deadline'] ?? '');
    $extension_months = intval($_POST['extension_months'] ?? 0);
    $new_deadline = trim($_POST['new_deadline'] ?? '');
    $extension_reason = trim($_POST['extension_reason'] ?? '');
    
    // Validation
    if (empty($project_id) || empty($current_deadline) || $extension_months <= 0 || empty($extension_reason)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin bắt buộc');
    }
    
    if (strlen($extension_reason) < 20) {
        throw new Exception('Lý do gia hạn phải có ít nhất 20 ký tự');
    }
    
    if ($extension_months > 6) {
        throw new Exception('Không thể gia hạn quá 6 tháng');
    }
    
    // Kiểm tra sinh viên có quyền yêu cầu gia hạn đề tài này không
    $check_sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, dt.DT_SO_LAN_GIA_HAN,
                         hd.HD_NGAYKT,
                         COUNT(gh.GH_ID) as SO_YEU_CAU_CHO_DUYET
                  FROM de_tai_nghien_cuu dt
                  INNER JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                  LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                  LEFT JOIN de_tai_gia_han gh ON dt.DT_MADT = gh.DT_MADT AND gh.GH_TRANGTHAI = 'Chờ duyệt'
                  WHERE cttg.SV_MASV = ? AND dt.DT_MADT = ?
                  GROUP BY dt.DT_MADT";
    
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $student_id, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();
    
    if (!$project) {
        throw new Exception('Không tìm thấy đề tài hoặc bạn không có quyền gia hạn đề tài này');
    }
    
    // Kiểm tra trạng thái đề tài
    if (!in_array($project['DT_TRANGTHAI'], ['Đang thực hiện', 'Chờ duyệt'])) {
        throw new Exception('Chỉ có thể gia hạn đề tài đang thực hiện hoặc chờ duyệt');
    }
    
    // Kiểm tra đã có yêu cầu gia hạn chờ duyệt chưa
    if ($project['SO_YEU_CAU_CHO_DUYET'] > 0) {
        throw new Exception('Đã có yêu cầu gia hạn đang chờ duyệt cho đề tài này');
    }
    
    // Kiểm tra số lần gia hạn
    if ($project['DT_SO_LAN_GIA_HAN'] >= 3) {
        throw new Exception('Đề tài đã gia hạn tối đa 3 lần');
    }
    
    // Xử lý file đính kèm (nếu có)
    $attachment_file = null;
    if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/extensions/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['attachment_file']['name']);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        
        if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
            throw new Exception('File đính kèm phải là PDF, Word hoặc hình ảnh');
        }
        
        if ($_FILES['attachment_file']['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('File đính kèm không được vượt quá 5MB');
        }
        
        $new_filename = $project_id . '_' . $student_id . '_' . time() . '.' . $file_info['extension'];
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], $upload_path)) {
            $attachment_file = 'uploads/extensions/' . $new_filename;
        } else {
            throw new Exception('Không thể upload file đính kèm');
        }
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Thêm yêu cầu gia hạn
        $insert_sql = "INSERT INTO de_tai_gia_han (
                          DT_MADT, SV_MASV, GH_LYDOYEUCAU, GH_NGAYHETHAN_CU, 
                          GH_NGAYHETHAN_MOI, GH_SOTHANGGIAHAN, GH_FILE_DINKEM, GH_NGUOITAO
                       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssssisr", 
            $project_id, $student_id, $extension_reason, $current_deadline,
            $new_deadline, $extension_months, $attachment_file, $student_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể tạo yêu cầu gia hạn: ' . $stmt->error);
        }
        
        $extension_id = $conn->insert_id;
        $stmt->close();
        
        // Tạo thông báo cho quản lý NCKH
        $notification_sql = "INSERT INTO thong_bao (
                               TB_NOIDUNG, TB_LOAI, DT_MADT, SV_MASV, 
                               NGUOI_NHAN, TB_LINK, TB_TRANGTHAI
                             ) VALUES (?, 'Yêu cầu gia hạn', ?, ?, 'RESEARCH_MANAGER', ?, 'Chưa đọc')";
        
        $notification_content = "Sinh viên {$student_id} yêu cầu gia hạn {$extension_months} tháng cho đề tài \"{$project['DT_TENDT']}\"";
        $notification_link = "/view/research/manage_extensions.php?gh_id={$extension_id}";
        
        $stmt = $conn->prepare($notification_sql);
        $stmt->bind_param("ssss", $notification_content, $project_id, $student_id, $notification_link);
        $stmt->execute();
        $stmt->close();
        
        // Gửi email thông báo (tùy chọn - cần cấu hình email)
        // sendExtensionNotificationEmail($project_id, $student_id, $extension_months, $extension_reason);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Yêu cầu gia hạn đã được gửi thành công',
            'extension_id' => $extension_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
