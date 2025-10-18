<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\cancel_extension.php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không được hỗ trợ');
    }
    
    $student_id = $_SESSION['user_id'];
    $extension_id = intval($_POST['id'] ?? 0);
    
    if ($extension_id <= 0) {
        throw new Exception('ID yêu cầu không hợp lệ');
    }
    
    // Kiểm tra quyền hủy yêu cầu
    $check_sql = "SELECT gh.GH_ID, gh.DT_MADT, gh.GH_TRANGTHAI, dt.DT_TENDT
                  FROM de_tai_gia_han gh
                  INNER JOIN de_tai_nghien_cuu dt ON gh.DT_MADT = dt.DT_MADT
                  WHERE gh.GH_ID = ? AND gh.SV_MASV = ?";
    
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("is", $extension_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $extension = $result->fetch_assoc();
    $stmt->close();
    
    if (!$extension) {
        throw new Exception('Không tìm thấy yêu cầu gia hạn hoặc bạn không có quyền hủy');
    }
    
    // Chỉ có thể hủy yêu cầu đang chờ duyệt
    if ($extension['GH_TRANGTHAI'] !== 'Chờ duyệt') {
        throw new Exception('Chỉ có thể hủy yêu cầu đang chờ duyệt');
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Cập nhật trạng thái thành 'Hủy'
        $update_sql = "UPDATE de_tai_gia_han 
                       SET GH_TRANGTHAI = 'Hủy', 
                           GH_NGAYCAPNHAT = NOW()
                       WHERE GH_ID = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $extension_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Không thể hủy yêu cầu gia hạn');
        }
        $stmt->close();
        
        // Xóa thông báo liên quan (nếu chưa đọc)
        $delete_notification_sql = "DELETE FROM thong_bao 
                                   WHERE TB_LOAI = 'Yêu cầu gia hạn' 
                                   AND DT_MADT = ? 
                                   AND SV_MASV = ? 
                                   AND TB_TRANGTHAI = 'Chưa đọc'
                                   AND TB_NGAYTAO >= (
                                       SELECT GH_NGAYYEUCAU 
                                       FROM de_tai_gia_han 
                                       WHERE GH_ID = ?
                                   )";
        
        $stmt = $conn->prepare($delete_notification_sql);
        $stmt->bind_param("ssi", $extension['DT_MADT'], $student_id, $extension_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã hủy yêu cầu gia hạn thành công'
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
