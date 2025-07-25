<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\approve_project.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../../include/session.php';
checkAdminRole();

// Bao gồm file kết nối cơ sở dữ liệu
include '../../../include/connect.php';

// Kiểm tra id đề tài
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $project_id = $_GET['id'];
    
    // Kiểm tra trạng thái hiện tại của đề tài
    $check_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt) {
        $check_stmt->bind_param("s", $project_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $project = $check_result->fetch_assoc();
            
            // Chỉ duyệt các đề tài đang ở trạng thái "Chờ duyệt"
            if ($project['DT_TRANGTHAI'] === 'Chờ duyệt') {
                // Cập nhật trạng thái đề tài
                $update_sql = "UPDATE de_tai_nghien_cuu SET DT_TRANGTHAI = 'Đang thực hiện' WHERE DT_MADT = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                if ($update_stmt) {
                    $update_stmt->bind_param("s", $project_id);
                    
                    if ($update_stmt->execute()) {
                        // Lấy thông tin sinh viên chủ nhiệm đề tài
                        $student_query = "SELECT SV_MASV FROM chi_tiet_tham_gia 
                                         WHERE DT_MADT = ? AND CTTG_VAITRO = 'Chủ nhiệm' 
                                         LIMIT 1";
                        $student_stmt = $conn->prepare($student_query);
                        $student_stmt->bind_param("s", $project_id);
                        $student_stmt->execute();
                        $student_result = $student_stmt->get_result();
                        
                        $student_id = null;
                        if ($student_result && $student_result->num_rows > 0) {
                            $student_data = $student_result->fetch_assoc();
                            $student_id = $student_data['SV_MASV'];
                        }
                        
                        // Thêm ghi chú vào tiến độ đề tài
                        $progress_id = 'TD' . date('ymd') . rand(100, 999);
                        $title = "Duyệt đề tài";
                        $content = "Đề tài đã được duyệt và chuyển sang trạng thái đang thực hiện.";
                        $completion = 0;
                        
                        // Câu lệnh SQL đúng với cấu trúc bảng
                        $progress_sql = "INSERT INTO tien_do_de_tai 
                                        (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, 
                                        TDDT_PHANTRAMHOANTHANH, TDDT_NGAYCAPNHAT) 
                                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                        
                        $progress_stmt = $conn->prepare($progress_sql);
                        if ($progress_stmt === false) {
                            $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error;
                        } else {
                            $progress_stmt->bind_param("ssssis", $progress_id, $project_id, $student_id, $title, $content, $completion);
                            $progress_stmt->execute();
                        }

                        $_SESSION['success_message'] = "Đề tài đã được duyệt thành công!";
                    } else {
                        $_SESSION['error_message'] = "Không thể duyệt đề tài: " . $update_stmt->error;
                    }
                    
                    $update_stmt->close();
                } else {
                    $_SESSION['error_message'] = "Lỗi hệ thống: " . $conn->error;
                }
            } else {
                $_SESSION['error_message'] = "Chỉ có thể duyệt đề tài đang ở trạng thái 'Chờ duyệt'!";
            }
        } else {
            $_SESSION['error_message'] = "Không tìm thấy đề tài với mã số này!";
        }
        
        $check_stmt->close();
    } else {
        $_SESSION['error_message'] = "Lỗi hệ thống: " . $conn->error;
    }
} else {
    $_SESSION['error_message'] = "Không tìm thấy mã đề tài!";
}

// Chuyển hướng về trang quản lý đề tài
header("Location: manage_projects.php");
exit();
?>