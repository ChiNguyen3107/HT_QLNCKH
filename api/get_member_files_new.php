<?php
// File: api/get_member_files_new.php
// API để lấy danh sách file đánh giá của thành viên hội đồng

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

include '../include/connect.php';

try {
    // Kiểm tra kết nối database
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối cơ sở dữ liệu");
    }
    
    // Lấy tham số
    $member_id = $_GET['member_id'] ?? $_POST['member_id'] ?? '';
    $project_id = $_GET['project_id'] ?? $_POST['project_id'] ?? '';
    
    if (empty($member_id) || empty($project_id)) {
        throw new Exception("Thiếu tham số member_id hoặc project_id");
    }
    
    // Lấy thông tin quyết định nghiệm thu
    $decision_sql = "SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($decision_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $decision_result = $stmt->get_result();
    
    if ($decision_result->num_rows === 0) {
        throw new Exception("Không tìm thấy quyết định nghiệm thu cho đề tài này");
    }
    
    $decision = $decision_result->fetch_assoc();
    $qd_so = $decision['QD_SO'];
    
    // Lấy danh sách file đánh giá của thành viên từ bảng file_dinh_kem
    $files_sql = "SELECT 
                      fdk.FDG_MA,
                      fdk.FDG_TENFILE as original_name,
                      fdk.FDG_DUONGDAN as filename,
                      fdk.FDG_LOAI as file_type,
                      fdk.FDG_MOTA as description,
                      fdk.FDG_NGAYTAO as upload_date,
                      fdk.FDG_KICHTHUC as file_size,
                      gv.GV_HOTEN as uploader_name
                  FROM file_dinh_kem fdk
                  LEFT JOIN giang_vien gv ON fdk.GV_MAGV = gv.GV_MAGV
                  WHERE fdk.FDG_LOAI = 'member_evaluation' 
                    AND fdk.GV_MAGV = ?
                    AND fdk.QD_SO = ?
                  ORDER BY fdk.FDG_NGAYTAO DESC";
    
    $stmt = $conn->prepare($files_sql);
    $stmt->bind_param("ss", $member_id, $qd_so);
    $stmt->execute();
    $files_result = $stmt->get_result();
    
    $files = [];
    while ($row = $files_result->fetch_assoc()) {
        $files[] = [
            'id' => $row['FDG_MA'],
            'original_name' => $row['original_name'],
            'filename' => $row['filename'],
            'file_type' => $row['file_type'],
            'description' => $row['description'],
            'upload_date' => $row['upload_date'],
            'file_size' => $row['file_size'],
            'uploader_name' => $row['uploader_name']
        ];
    }
    
    // Lấy thông tin thành viên để bổ sung context
    $member_sql = "SELECT gv.GV_HOTEN, tv.TV_VAITRO 
                   FROM thanh_vien_hoi_dong tv
                   JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                   WHERE tv.QD_SO = ? AND tv.GV_MAGV = ?";
    
    $stmt = $conn->prepare($member_sql);
    $stmt->bind_param("ss", $qd_so, $member_id);
    $stmt->execute();
    $member_result = $stmt->get_result();
    $member_info = $member_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'files' => $files,
        'member_info' => [
            'id' => $member_id,
            'name' => $member_info['GV_HOTEN'] ?? 'N/A',
            'role' => $member_info['TV_VAITRO'] ?? 'N/A'
        ],
        'total_files' => count($files),
        'message' => count($files) > 0 ? 'Lấy danh sách file thành công' : 'Chưa có file đánh giá nào'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'files' => [],
        'total_files' => 0
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
