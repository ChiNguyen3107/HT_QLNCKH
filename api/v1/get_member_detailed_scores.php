<?php
// File: api/get_member_detailed_scores.php
// API để lấy điểm đánh giá chi tiết của thành viên hội đồng

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

include '../include/connect.php';

try {
    // Kiểm tra kết nối database
    if ($conn->connect_error) {
        throw new Exception("Lỗi kết nối cơ sở dữ liệu");
    }
    
    // Lấy tham số từ GET hoặc POST
    $member_id = $_GET['member_id'] ?? $_POST['member_id'] ?? '';
    $project_id = $_GET['project_id'] ?? $_POST['project_id'] ?? '';
    
    if (empty($member_id) || empty($project_id)) {
        throw new Exception("Thiếu tham số member_id hoặc project_id");
    }
    
    // Lấy thông tin quyết định nghiệm thu từ project_id
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
    
    // Lấy thông tin thành viên hội đồng
    $member_sql = "SELECT tv.*, gv.GV_HOTEN, gv.GV_EMAIL, gv.GV_SODIENTHOAI, k.DV_TENDV
                   FROM thanh_vien_hoi_dong tv
                   JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                   LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
                   WHERE tv.QD_SO = ? AND tv.GV_MAGV = ?";
    
    $stmt = $conn->prepare($member_sql);
    $stmt->bind_param("ss", $qd_so, $member_id);
    $stmt->execute();
    $member_result = $stmt->get_result();
    
    if ($member_result->num_rows === 0) {
        throw new Exception("Không tìm thấy thành viên hội đồng");
    }
    
    $member_info = $member_result->fetch_assoc();
    
    // Lấy chi tiết điểm đánh giá đã có
    $scores_sql = "SELECT 
                       ctddg.TC_MATC, 
                       ctddg.CTDD_DIEM, 
                       ctddg.CTDD_NHANXET,
                       ctddg.CTDD_NGAYTAO,
                       ctddg.CTDD_NGAYCAPNHAT,
                       tc.TC_TEN,
                       tc.TC_NDDANHGIA,
                       tc.TC_MOTA,
                       tc.TC_DIEMTOIDA,
                       tc.TC_TRONGSO
                   FROM chi_tiet_diem_danh_gia ctddg
                   JOIN tieu_chi tc ON ctddg.TC_MATC = tc.TC_MATC
                   WHERE ctddg.QD_SO = ? AND ctddg.GV_MAGV = ?
                   ORDER BY COALESCE(tc.TC_THUTU, 1), tc.TC_MATC";
    
    $stmt = $conn->prepare($scores_sql);
    $stmt->bind_param("ss", $qd_so, $member_id);
    $stmt->execute();
    $scores_result = $stmt->get_result();
    
    $existing_scores = [];
    while ($row = $scores_result->fetch_assoc()) {
        $existing_scores[$row['TC_MATC']] = $row;
    }
    
    // Lấy tất cả tiêu chí để đảm bảo có đầy đủ
    $all_criteria_sql = "SELECT 
                             TC_MATC,
                             COALESCE(TC_TEN, TC_NDDANHGIA) as TC_TEN,
                             TC_NDDANHGIA,
                             COALESCE(TC_MOTA, TC_NDDANHGIA) as TC_MOTA,
                             TC_DIEMTOIDA,
                             COALESCE(TC_TRONGSO, 20.00) as TC_TRONGSO,
                             COALESCE(TC_THUTU, 1) as TC_THUTU
                         FROM tieu_chi 
                         WHERE COALESCE(TC_TRANGTHAI, 'Hoạt động') = 'Hoạt động'
                         ORDER BY COALESCE(TC_THUTU, 1), TC_MATC";
    
    $all_criteria_result = $conn->query($all_criteria_sql);
    
    $scores = [];
    $overall_comment = '';
    
    while ($criterion = $all_criteria_result->fetch_assoc()) {
        $tc_matc = $criterion['TC_MATC'];
        
        if (isset($existing_scores[$tc_matc])) {
            // Đã có điểm
            $existing = $existing_scores[$tc_matc];
            $scores[] = [
                'TC_MATC' => $tc_matc,
                'CTDD_DIEM' => (float)$existing['CTDD_DIEM'],
                'CTDD_NHANXET' => $existing['CTDD_NHANXET'],
                'TC_TEN' => $existing['TC_TEN'] ?: $criterion['TC_TEN'],
                'TC_MOTA' => $existing['TC_MOTA'] ?: $criterion['TC_MOTA'],
                'TC_DIEMTOIDA' => (float)$existing['TC_DIEMTOIDA'],
                'TC_TRONGSO' => (float)$existing['TC_TRONGSO']
            ];
        } else {
            // Chưa có điểm
            $scores[] = [
                'TC_MATC' => $tc_matc,
                'CTDD_DIEM' => 0,
                'CTDD_NHANXET' => '',
                'TC_TEN' => $criterion['TC_TEN'],
                'TC_MOTA' => $criterion['TC_MOTA'],
                'TC_DIEMTOIDA' => (float)$criterion['TC_DIEMTOIDA'],
                'TC_TRONGSO' => (float)$criterion['TC_TRONGSO']
            ];
        }
    }
    
    // Lấy nhận xét tổng quan từ bảng thanh_vien_hoi_dong
    if (!empty($member_info['TV_DANHGIA'])) {
        $overall_comment = $member_info['TV_DANHGIA'];
    }
    
    echo json_encode([
        'success' => true,
        'scores' => $scores,
        'overall_comment' => $overall_comment,
        'member_info' => [
            'id' => $member_info['GV_MAGV'],
            'name' => $member_info['GV_HOTEN'],
            'role' => $member_info['TV_VAITRO'],
            'email' => $member_info['GV_EMAIL'],
            'phone' => $member_info['GV_SODIENTHOAI'],
            'department' => $member_info['DV_TENDV'],
            'current_score' => (float)($member_info['TV_DIEM'] ?? 0),
            'status' => $member_info['TV_TRANGTHAI'] ?? 'Chưa đánh giá'
        ],
        'total_criteria' => count($scores),
        'evaluated_criteria' => count($existing_scores)
    ], JSON_UNESCAPED_UNICODE);
    
    $evaluation_files = [];
    while ($row = $files_result->fetch_assoc()) {
        $evaluation_files[] = [
            'id' => $row['FDG_MA'],
            'name' => $row['FDG_TENFILE'],
            'filename' => $row['FDG_FILE'],
            'createdAt' => $row['FDG_NGAYTAO'],
            'size' => $row['FDG_KICHTHUC'],
            'description' => $row['FDG_MOTA']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'memberInfo' => [
                'id' => $member_info['GV_MAGV'],
                'name' => $member_info['GV_HOTEN'],
                'displayName' => $member_info['TV_HOTEN'],
                'role' => $member_info['TV_VAITRO'],
                'status' => $member_info['TV_TRANGTHAI'],
                'lastEvaluated' => $member_info['TV_NGAYDANHGIA'],
                'evaluationFile' => $member_info['TV_FILEDANHGIA'],
                'totalScore' => (float)$member_info['TV_DIEM']
            ],
            'detailedScores' => $final_scores,
            'evaluationFiles' => $evaluation_files,
            'totalScore' => $total_score,
            'maxTotalScore' => array_sum(array_column($final_scores, 'maxScore'))
        ],
        'message' => 'Lấy thông tin đánh giá chi tiết thành công'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
