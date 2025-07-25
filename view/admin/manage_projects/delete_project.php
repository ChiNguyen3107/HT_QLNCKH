<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\delete_project.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Lấy ID đề tài từ URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Kiểm tra ID đề tài
if (empty($id)) {
    $_SESSION['error_message'] = "ID đề tài không hợp lệ!";
    header("Location: manage_projects.php");
    exit();
}

// Kiểm tra xem đề tài có tồn tại không
$check_query = "SELECT DT_MADT FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$check_stmt = $conn->prepare($check_query);
if (!$check_stmt) {
    $_SESSION['error_message'] = "Lỗi hệ thống: " . $conn->error;
    header("Location: manage_projects.php");
    exit();
}

$check_stmt->bind_param("s", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài với ID: " . htmlspecialchars($id);
    header("Location: manage_projects.php");
    exit();
}
$check_stmt->close();

try {
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    // 1. Xóa các thành viên tham gia đề tài
    $delete_members = "DELETE FROM chi_tiet_tham_gia WHERE DT_MADT = ?";
    $stmt = $conn->prepare($delete_members);
    if (!$stmt) {
        throw new Exception("Lỗi khi chuẩn bị truy vấn xóa thành viên: " . $conn->error);
    }
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->close();
    
    // 2. Xóa tiến độ đề tài (nếu có)
    // Kiểm tra xem bảng tien_do_de_tai có tồn tại không
    $tableCheck = $conn->query("SHOW TABLES LIKE 'tien_do_de_tai'");
    if ($tableCheck->num_rows > 0) {
        try {
            $delete_progress = "DELETE FROM tien_do_de_tai WHERE DT_MADT = ?";
            $stmt = $conn->prepare($delete_progress);
            if ($stmt) {
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Bỏ qua lỗi và tiếp tục
            error_log("Lỗi khi xóa tiến độ: " . $e->getMessage());
        }
    }
    
    // 3. Xóa báo cáo liên quan (nếu có)
    try {
        $delete_reports = "DELETE FROM bao_cao WHERE DT_MADT = ?";
        $stmt = $conn->prepare($delete_reports);
        if ($stmt) {
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Bỏ qua lỗi và tiếp tục
        error_log("Lỗi khi xóa báo cáo: " . $e->getMessage());
    }
    
    // 4. Xóa thanh toán hợp đồng (nếu có)
    try {
        $delete_payments = "DELETE FROM thanh_toan WHERE HD_MA IN (SELECT HD_MA FROM hop_dong WHERE DT_MADT = ?)";
        $stmt = $conn->prepare($delete_payments);
        if ($stmt) {
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Bỏ qua lỗi và tiếp tục
        error_log("Lỗi khi xóa thanh toán: " . $e->getMessage());
    }
    
    // 5. Xóa nguồn kinh phí (nếu có)
    try {
        $delete_funds = "DELETE FROM nguon_kinh_phi WHERE HD_MA IN (SELECT HD_MA FROM hop_dong WHERE DT_MADT = ?)";
        $stmt = $conn->prepare($delete_funds);
        if ($stmt) {
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Bỏ qua lỗi và tiếp tục
        error_log("Lỗi khi xóa nguồn kinh phí: " . $e->getMessage());
    }
    
    // 6. Xóa hợp đồng
    try {
        $delete_contract = "DELETE FROM hop_dong WHERE DT_MADT = ?";
        $stmt = $conn->prepare($delete_contract);
        if ($stmt) {
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Bỏ qua lỗi và tiếp tục
        error_log("Lỗi khi xóa hợp đồng: " . $e->getMessage());
    }
    
    // 7. Cuối cùng, xóa đề tài
    $delete_project = "DELETE FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($delete_project);
    if (!$stmt) {
        throw new Exception("Lỗi khi chuẩn bị truy vấn xóa đề tài: " . $conn->error);
    }
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->close();
    
    // Commit các thay đổi
    $conn->commit();
    
    // Hiển thị thông báo thành công
    $_SESSION['success_message'] = "Đã xóa đề tài thành công!";
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $_SESSION['error_message'] = "Có lỗi xảy ra khi xóa đề tài: " . $e->getMessage();
}

// Đóng kết nối
$conn->close();

// Chuyển hướng về trang quản lý đề tài
header("Location: manage_projects.php");
exit();
?>