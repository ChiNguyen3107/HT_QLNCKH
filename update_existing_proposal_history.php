<?php
// Script cập nhật lịch sử file thuyết minh cho các đề tài hiện có
include 'include/connect.php';

echo "Bắt đầu cập nhật lịch sử file thuyết minh...\n";

// Lấy tất cả đề tài có file thuyết minh
$sql = "SELECT DT_MADT, DT_FILEBTM, DT_NGAYTAO, DT_GHICHU FROM de_tai_nghien_cuu WHERE DT_FILEBTM IS NOT NULL AND TRIM(DT_FILEBTM) != ''";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "Tìm thấy " . $result->num_rows . " đề tài có file thuyết minh.\n";
    
    while ($project = $result->fetch_assoc()) {
        $project_id = $project['DT_MADT'];
        $file_name = $project['DT_FILEBTM'];
        $created_date = $project['DT_NGAYTAO'];
        
        echo "Đang xử lý đề tài: $project_id\n";
        
        // Kiểm tra xem đã có lịch sử chưa
        $check_sql = "SELECT COUNT(*) as count FROM lich_su_thuyet_minh WHERE DT_MADT = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $project_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $count = $check_result->fetch_assoc()['count'];
        
        if ($count == 0) {
            // Chưa có lịch sử, thêm bản ghi đầu tiên
            $file_path = "uploads/project_files/" . $file_name;
            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
            $file_type = pathinfo($file_name, PATHINFO_EXTENSION);
            
            $insert_sql = "INSERT INTO lich_su_thuyet_minh (DT_MADT, FILE_TEN, FILE_KICHTHUOC, FILE_LOAI, LY_DO, NGUOI_TAI, NGAY_TAI, LA_HIEN_TAI) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            $reason = 'File thuyết minh khi đăng ký đề tài';
            $uploader = 'Hệ thống';
            
            $insert_stmt->bind_param("ssissss", $project_id, $file_name, $file_size, $file_type, $reason, $uploader, $created_date);
            
            if ($insert_stmt->execute()) {
                echo "  ✓ Đã thêm lịch sử cho file: $file_name\n";
            } else {
                echo "  ✗ Lỗi khi thêm lịch sử: " . $insert_stmt->error . "\n";
            }
        } else {
            echo "  - Đã có lịch sử, bỏ qua\n";
        }
    }
} else {
    echo "Không tìm thấy đề tài nào có file thuyết minh.\n";
}

echo "Hoàn thành cập nhật lịch sử file thuyết minh.\n";
?>





















