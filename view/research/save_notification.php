<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\save_notification.php

// File functions dành cho việc lưu thông báo cho người dùng
// Được gọi thông qua AJAX từ review_projects.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Kiểm tra request method và dữ liệu gửi lên
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['content']) && isset($_POST['type'])) {
    // Lấy dữ liệu từ request
    $user_id = $_POST['user_id'];
    $content = $_POST['content'];
    $type = $_POST['type']; // 'success', 'warning', 'danger', 'info'
    $link = isset($_POST['link']) ? $_POST['link'] : '';
    
    // Tạo câu truy vấn SQL để thêm thông báo mới
    $sql = "INSERT INTO thong_bao (NGUOI_NHAN, TB_NOIDUNG, TB_LOAI, TB_LINK, TB_TRANGTHAI, TB_NGAYTAO) 
            VALUES (?, ?, ?, ?, 'chưa đọc', NOW())";
    
    // Chuẩn bị và thực thi câu truy vấn
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $user_id, $content, $type, $link);
        
        if ($stmt->execute()) {
            // Thông báo đã được lưu thành công
            $response = [
                'success' => true,
                'message' => 'Thông báo đã được gửi thành công'
            ];
        } else {
            // Có lỗi khi lưu thông báo
            $response = [
                'success' => false,
                'message' => 'Có lỗi khi lưu thông báo: ' . $stmt->error
            ];
        }
        
        $stmt->close();
    } else {
        // Có lỗi khi chuẩn bị câu truy vấn
        $response = [
            'success' => false,
            'message' => 'Có lỗi khi chuẩn bị câu truy vấn: ' . $conn->error
        ];
    }
} else {
    // Không đủ dữ liệu cần thiết
    $response = [
        'success' => false,
        'message' => 'Thiếu dữ liệu cần thiết'
    ];
}

// Trả về response dạng JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
