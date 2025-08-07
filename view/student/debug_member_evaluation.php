<?php
session_start();
require_once '../../include/connect.php';

// Giả lập session cho test
$_SESSION['student_id'] = 'B2110051';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}

$project_id = 'DT0000001';
$member_id = 'GV000002';

echo "=== TEST QUERY MEMBER SCORES ===\n";

// Test query để lấy điểm
$find_qd = $conn->prepare("
    SELECT qd.QD_SO 
    FROM quyet_dinh_nghiem_thu qd
    JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB  
    JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
    WHERE dt.DT_MADT = ?
");
$find_qd->bind_param("s", $project_id);
$find_qd->execute();
$qd_result = $find_qd->get_result();

if ($qd_result->num_rows === 0) {
    echo "Không tìm thấy quyết định nghiệm thu cho dự án $project_id\n";
    
    // Debug: Xem thông tin dự án
    $debug_query = $conn->query("SELECT DT_MADT, QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = '$project_id'");
    if ($debug_query && $debug_query->num_rows > 0) {
        $debug_data = $debug_query->fetch_assoc();
        echo "Dự án có QD_SO: " . ($debug_data['QD_SO'] ?? 'NULL') . "\n";
    } else {
        echo "Không tìm thấy dự án với ID: $project_id\n";
    }
} else {
    $qd_info = $qd_result->fetch_assoc();
    echo "Tìm thấy QD_SO: {$qd_info['QD_SO']}\n";
    
    // Test query lấy thông tin thành viên
    $get_member = $conn->prepare("
        SELECT 
            TV_DIEM,
            TV_DANHGIA,
            TV_DIEMCHITIET,
            TV_TRANGTHAI,
            TV_VAITRO
        FROM thanh_vien_hoi_dong 
        WHERE QD_SO = ? AND GV_MAGV = ?
    ");
    $get_member->bind_param("ss", $qd_info['QD_SO'], $member_id);
    $get_member->execute();
    $member_result = $get_member->get_result();
    
    if ($member_result->num_rows === 0) {
        echo "Không tìm thấy thành viên $member_id trong QD_SO {$qd_info['QD_SO']}\n";
        
        // Debug: Xem tất cả thành viên trong QD_SO này
        $debug_members = $conn->query("SELECT GV_MAGV, TV_VAITRO FROM thanh_vien_hoi_dong WHERE QD_SO = '{$qd_info['QD_SO']}'");
        echo "Danh sách thành viên trong QD_SO {$qd_info['QD_SO']}:\n";
        while ($debug_member = $debug_members->fetch_assoc()) {
            echo "- GV_MAGV: {$debug_member['GV_MAGV']}, Vai trò: {$debug_member['TV_VAITRO']}\n";
        }
    } else {
        $member_data = $member_result->fetch_assoc();
        echo "Tìm thấy thành viên: {$member_data['TV_VAITRO']}\n";
        echo "Điểm hiện tại: " . ($member_data['TV_DIEM'] ?? 'NULL') . "\n";
        echo "Trạng thái: " . ($member_data['TV_TRANGTHAI'] ?? 'NULL') . "\n";
        echo "Chi tiết điểm: " . ($member_data['TV_DIEMCHITIET'] ?? 'NULL') . "\n";
    }
}

echo "\n=== TEST CRITERIA QUERY ===\n";
$criteria_query = $conn->query("SELECT TC_MATC, TC_NDDANHGIA, TC_DIEMTOIDA FROM tieu_chi WHERE TC_TRANGTHAI = 'Hoạt động' ORDER BY TC_THUTU");
if ($criteria_query) {
    echo "Tìm thấy " . $criteria_query->num_rows . " tiêu chí:\n";
    while ($criteria = $criteria_query->fetch_assoc()) {
        echo "- {$criteria['TC_MATC']}: {$criteria['TC_NDDANHGIA']} (Max: {$criteria['TC_DIEMTOIDA']})\n";
    }
} else {
    echo "Lỗi truy vấn tiêu chí: " . $conn->error . "\n";
}

$conn->close();
?>
