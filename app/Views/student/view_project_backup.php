<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Helper function to format dates consistently throughout the application
function formatDate($date, $default = 'Chưa xác định') {
    if (isset($date) && !empty($date) && $date !== '0000-00-00') {
        try {
            return date('d/m/Y', strtotime($date));
        } catch (Exception $e) {
            return $default;
        }
    }
    return $default;
}

// Helper function để kiểm tra quyền chỉnh sửa
function canEditProject($project_status, $user_role, $for_evaluation = false) {
    if ($user_role !== 'Chủ nhiệm') {
        return false;
    }
    
    // Cho phép chỉnh sửa khi đang thực hiện
    if ($project_status === 'Đang thực hiện') {
        return true;
    }
    
    // Cho phép chỉnh sửa file và thông tin khi đã hoàn thành (thay đổi quan trọng)
    if ($project_status === 'Đã hoàn thành') {
        return true; // Cho phép cập nhật file và thông tin
    }
    
    return false;
}

// Function kiểm tra tính đầy đủ của các file yêu cầu
function checkProjectCompleteness($project_id, $conn) {
    $required_files = [
        'proposal' => false,    // File thuyết minh
        'contract' => false,    // File hợp đồng
        'decision' => false,    // File quyết định
        'evaluation' => false   // File đánh giá
    ];
    
    // Kiểm tra file thuyết minh
    $proposal_sql = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND DT_FILEBTM IS NOT NULL AND DT_FILEBTM != ''";
    $stmt = $conn->prepare($proposal_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $required_files['proposal'] = ($result->num_rows > 0);
    }
    
    // Kiểm tra file hợp đồng
    $contract_sql = "SELECT HD_FILE FROM hop_dong WHERE DT_MADT = ? AND HD_FILE IS NOT NULL AND HD_FILE != ''";
    $stmt = $conn->prepare($contract_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $required_files['contract'] = ($result->num_rows > 0);
    }
    
    // Kiểm tra file quyết định và biên bản
    $decision_sql = "SELECT qd.QD_FILE, bb.BB_SOBB 
                    FROM de_tai_nghien_cuu dt
                    INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                    WHERE dt.DT_MADT = ?
                    AND qd.QD_FILE IS NOT NULL AND qd.QD_FILE != ''
                    AND bb.BB_SOBB IS NOT NULL";
    $stmt = $conn->prepare($decision_sql);
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $required_files['decision'] = ($result->num_rows > 0);
    }
    
    // Kiểm tra file đánh giá
    if ($required_files['decision']) {
        $eval_sql = "SELECT COUNT(*) as file_count FROM file_danh_gia fg
                    INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB
                    INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO
                    INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
                    WHERE dt.DT_MADT = ?";
        $stmt = $conn->prepare($eval_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $required_files['evaluation'] = ($row['file_count'] > 0);
        }
    }
    
    return $required_files;
}

// Function tự động cập nhật trạng thái đề tài
function updateProjectStatusIfComplete($project_id, $conn) {
    $completeness = checkProjectCompleteness($project_id, $conn);
    
    // Nếu tất cả file đã đầy đủ, cập nhật trạng thái thành "Đã hoàn thành"
    if ($completeness['proposal'] && $completeness['contract'] && 
        $completeness['decision'] && $completeness['evaluation']) {
        
        $update_sql = "UPDATE de_tai_nghien_cuu SET DT_TRANGTHAI = 'Đã hoàn thành' WHERE DT_MADT = ? AND DT_TRANGTHAI != 'Đã hoàn thành'";
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            return $stmt->affected_rows > 0; // Trả về true nếu có cập nhật
        }
    }
    
    return false;
}

// Lấy ID đề tài từ URL
$project_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($project_id)) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài.";
    header('Location: student_manage_projects.php');
    exit;
}

// Lấy thông tin chi tiết của đề tài
$sql = "SELECT dt.*, 
               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
               gv.GV_EMAIL,
               ldt.LDT_TENLOAI,
               lvnc.LVNC_TEN,
               lvut.LVUT_TEN,
               hd.HD_NGAYTAO,
               hd.HD_NGAYBD,
               hd.HD_NGAYKT,
               hd.HD_TONGKINHPHI
        FROM de_tai_nghien_cuu dt
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
        WHERE dt.DT_MADT = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}

$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài với mã số " . htmlspecialchars($project_id);
    header('Location: student_manage_projects.php');
    exit;
}

$project = $result->fetch_assoc();

// Tự động kiểm tra và cập nhật trạng thái đề tài nếu đã nộp đủ file
$status_updated = updateProjectStatusIfComplete($project_id, $conn);
if ($status_updated) {
    // Nếu trạng thái được cập nhật, lấy lại thông tin đề tài
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    
    $_SESSION['success_message'] = "Đề tài đã được cập nhật trạng thái thành 'Đã hoàn thành' do đã nộp đủ tất cả file yêu cầu.";
}

// Kiểm tra tính đầy đủ của các file
$file_completeness = checkProjectCompleteness($project_id, $conn);

// Kiểm tra quyền truy cập: sinh viên chỉ có thể xem đề tài họ tham gia
$check_access_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_access_sql);
$stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
$stmt->execute();
$access_result = $stmt->get_result();
$has_access = ($access_result->num_rows > 0);
$user_role = $has_access ? $access_result->fetch_assoc()['CTTG_VAITRO'] : '';

// Lấy danh sách thành viên tham gia
$member_sql = "SELECT sv.SV_MASV, CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN, 
               l.LOP_TEN, cttg.CTTG_VAITRO, cttg.CTTG_NGAYTHAMGIA
               FROM chi_tiet_tham_gia cttg
               JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
               JOIN lop l ON sv.LOP_MA = l.LOP_MA
               WHERE cttg.DT_MADT = ?";
$stmt = $conn->prepare($member_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = [];
while ($member = $members_result->fetch_assoc()) {
    $members[] = $member;
}

// Lấy thông tin tiến độ đề tài
$progress_sql = "SELECT td.*, CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN
               FROM tien_do_de_tai td
               LEFT JOIN sinh_vien sv ON td.SV_MASV = sv.SV_MASV
               WHERE td.DT_MADT = ? 
               ORDER BY td.TDDT_NGAYCAPNHAT DESC";
$stmt = $conn->prepare($progress_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$progress_result = $stmt->get_result();
$progress_entries = [];
while ($progress = $progress_result->fetch_assoc()) {
    $progress_entries[] = $progress;
}

// Đếm số lượng cập nhật tiến độ
$progress_count = count($progress_entries);

// Lấy thông tin file hợp đồng nếu có
$contract_sql = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$contract_result = $stmt->get_result();
$contract = $contract_result->num_rows > 0 ? $contract_result->fetch_assoc() : null;

// Lấy thông tin quyết định nghiệm thu và biên bản nếu có
// Sử dụng mối liên hệ đúng: de_tai_nghien_cuu.QD_SO = quyet_dinh_nghiem_thu.QD_SO
$decision_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
                FROM de_tai_nghien_cuu dt
                INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE dt.DT_MADT = ?";

$decision = null;
$decision_debug = "";

$stmt = $conn->prepare($decision_sql);
if ($stmt === false) {
    $decision_debug = "Lỗi prepare SQL: " . $conn->error;
} else {
    $stmt->bind_param("s", $project_id);
    if (!$stmt->execute()) {
        $decision_debug = "Lỗi execute SQL: " . $stmt->error;
    } else {
        $decision_result = $stmt->get_result();
        $decision_debug = "Tìm thấy " . $decision_result->num_rows . " quyết định nghiệm thu";
        
        if ($decision_result->num_rows > 0) {
            $decision = $decision_result->fetch_assoc();
            $decision_debug .= " - Đã tải thành công";
        } else {
            $decision_debug .= " - Chưa có quyết định nghiệm thu cho đề tài này";
        }
    }
}

// Lấy file đánh giá nếu có biên bản
$evaluation_files = [];
$eval_files_error = null;
$eval_files_debug = "";
if ($decision) {
    $eval_files_debug = "BB_SOBB: " . $decision['BB_SOBB'];
    $eval_files_sql = "SELECT * FROM file_danh_gia WHERE BB_SOBB = ?";
    $stmt = $conn->prepare($eval_files_sql);
    if ($stmt === false) {
        $eval_files_error = "Lỗi truy vấn file đánh giá: " . $conn->error;
    } else {
        $stmt->bind_param("s", $decision['BB_SOBB']);
        $stmt->execute();
        $eval_files_result = $stmt->get_result();
        while ($file = $eval_files_result->fetch_assoc()) {
            $evaluation_files[] = $file;
        }
        $eval_files_debug .= " | Found " . count($evaluation_files) . " files";
    }
} else {
    $eval_files_debug = "Không có decision/biên bản";
}

// Lấy danh sách thành viên hội đồng nghiệm thu nếu có quyết định
$council_members = [];
if ($decision && isset($decision['QD_SO'])) {
    // Thử lấy từ bảng thanh_vien_hoi_dong trước
    $council_sql = "SELECT tv.*, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
                           gv.GV_EMAIL, gv.GV_DIENTHOAI, tc.TC_TEN
                    FROM thanh_vien_hoi_dong tv
                    JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                    LEFT JOIN trinh_do_chuyen_mon tc ON tv.TC_MATC = tc.TC_MATC
                    WHERE tv.QD_SO = ?
                    ORDER BY 
                        CASE tv.TV_VAITRO 
                            WHEN 'Chủ tịch hội đồng' THEN 1
                            WHEN 'Phó chủ tịch' THEN 2
                            WHEN 'Thành viên' THEN 3
                            WHEN 'Thư ký' THEN 4
                            ELSE 5
                        END, 
                        gv.GV_HOTEN ASC";
    $stmt = $conn->prepare($council_sql);
    if ($stmt === false) {
        $council_error = "Lỗi truy vấn thành viên hội đồng: " . $conn->error;
    } else {
        $stmt->bind_param("s", $decision['QD_SO']);
        $stmt->execute();
        $council_result = $stmt->get_result();
        while ($member = $council_result->fetch_assoc()) {
            $council_members[] = $member;
        }
    }
    
    // Nếu không có dữ liệu từ bảng thanh_vien_hoi_dong, thử parse từ trường HD_THANHVIEN
    if (empty($council_members) && !empty($decision['HD_THANHVIEN'])) {
        // Tạo array từ text để hiển thị tạm thời
        $members_text = explode("\n", $decision['HD_THANHVIEN']);
        foreach ($members_text as $member_text) {
            if (trim($member_text)) {
                // Parse format: "Tên (Vai trò)" nếu có thể
                $council_members[] = [
                    'GV_HOTEN' => trim($member_text),
                    'TV_VAITRO' => 'Thành viên',
                    'TV_DIEM' => null,
                    'TV_DANHGIA' => null,
                    'GV_EMAIL' => '',
                    'GV_DIENTHOAI' => '',
                    'TC_TEN' => '',
                    'GV_MAGV' => 'temp_' . md5($member_text) // ID tạm thời
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['DT_TENDT']); ?> | Chi tiết đề tài</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- Prevent caching issues -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #2c68c9;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        
        /* Tab styling */
        .nav-tabs .nav-link {
            transition: all 0.2s ease;
        }
        
        .tab-pane {
            transition: opacity 0.2s ease-in-out;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .content {
            padding-top: 20px;
            padding-left: 20px;
            padding-right: 20px;
            transition: all 0.3s ease;
        }        .project-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 35px 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .project-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
        }
        
        .project-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }
        
        .project-header .row {
            position: relative;
            z-index: 2;
        }
        
        .project-title {
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            line-height: 1.2;
            letter-spacing: -0.5px;
        }
        
        .project-header .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .project-header .info-item:hover {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 10px;
            transform: translateX(5px);
        }
        
        .project-header .info-item i {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .project-header .badge {
            font-size: 0.9rem;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .project-progress {
            height: 12px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .project-progress .progress-bar {
            background: linear-gradient(90deg, #00f5ff, #0099cc);
            border-radius: 25px;
            position: relative;
            overflow: hidden;
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .project-progress .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: slide 2s infinite;
        }
        
        @keyframes slide {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .project-status-container {
            margin-bottom: 25px;
        }
        
        .project-sidebar-container {
            background: linear-gradient(135deg, rgba(30, 144, 255, 0.15) 0%, rgba(138, 43, 226, 0.15) 50%, rgba(220, 20, 60, 0.15) 100%);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 15px 35px rgba(30, 144, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .project-sidebar-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
        }
        
        .project-sidebar-container:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 20px 45px rgba(30, 144, 255, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .status-badge {
            font-size: 1.1rem;
            padding: 15px 25px;
            font-weight: 700;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        /* Status Badge Colors */
        .status-warning {
            background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);
            color: #fff !important;
            border-color: rgba(255, 167, 38, 0.5);
            box-shadow: 0 10px 25px rgba(255, 152, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .status-primary {
            background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%);
            color: #fff !important;
            border-color: rgba(66, 165, 245, 0.5);
            box-shadow: 0 10px 25px rgba(25, 118, 210, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .status-success {
            background: linear-gradient(135deg, #66bb6a 0%, #2e7d32 100%);
            color: #fff !important;
            border-color: rgba(102, 187, 106, 0.5);
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .status-info {
            background: linear-gradient(135deg, #26c6da 0%, #0097a7 100%);
            color: #fff !important;
            border-color: rgba(38, 198, 218, 0.5);
            box-shadow: 0 10px 25px rgba(0, 151, 167, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .status-danger {
            background: linear-gradient(135deg, #ef5350 0%, #c62828 100%);
            color: #fff !important;
            border-color: rgba(239, 83, 80, 0.5);
            box-shadow: 0 10px 25px rgba(198, 40, 40, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .status-secondary {
            background: linear-gradient(135deg, #90a4ae 0%, #546e7a 100%);
            color: #fff !important;
            border-color: rgba(144, 164, 174, 0.5);
            box-shadow: 0 10px 25px rgba(84, 110, 122, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .status-badge:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }
        
        /* Custom Button Styles */
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: 1px solid rgba(102, 126, 234, 0.3) !important;
            color: white !important;
            border-radius: 25px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            backdrop-filter: blur(15px) !important;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 25px rgba(102, 126, 234, 0.4) !important;
        }
        
        .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
            border: 1px solid rgba(108, 117, 125, 0.3) !important;
            color: white !important;
            border-radius: 25px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            backdrop-filter: blur(15px) !important;
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3) !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-outline-light-custom {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid rgba(255, 255, 255, 0.4) !important;
            color: white !important;
            border-radius: 25px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            backdrop-filter: blur(15px) !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-outline-light-custom:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.6) !important;
            color: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.2) !important;
        }
        
        /* Custom Alert Styles */
        .alert-success-custom {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.2) 0%, rgba(56, 142, 60, 0.2) 100%) !important;
            border: 1px solid rgba(76, 175, 80, 0.3) !important;
            border-radius: 15px !important;
            color: #e8f5e8 !important;
            backdrop-filter: blur(10px) !important;
            padding: 15px !important;
        }
        
        /* File Status Badges */
        .badge-file-success {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%) !important;
            color: white !important;
            border-radius: 20px !important;
            padding: 8px 12px !important;
            font-weight: 600 !important;
            border: 1px solid rgba(76, 175, 80, 0.3) !important;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3) !important;
        }
        
        .badge-file-warning {
            background: linear-gradient(135deg, #ff9800 0%, #ef6c00 100%) !important;
            color: white !important;
            border-radius: 20px !important;
            padding: 8px 12px !important;
            font-weight: 600 !important;
            border: 1px solid rgba(255, 152, 0, 0.3) !important;
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3) !important;
        }
        
        /* Text Colors */
        .text-light-custom {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .text-warning-custom {
            color: #ffcc02 !important;
            font-weight: 600 !important;
        }
        
        .file-status-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .action-buttons .btn {
            border-radius: 20px;
            padding: 10px 20px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .action-buttons .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .action-buttons .btn-outline-primary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-color: rgba(255, 255, 255, 0.4);
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-primary {
            background-color: var(--primary);
        }

        .badge-success {
            background-color: var(--success);
        }

        .badge-info {
            background-color: var(--info);
        }

        .badge-danger {
            background-color: var(--danger);
        }

        .info-card {
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            font-weight: 500;
            background-color: #fff;
            border-bottom: 1px solid #eaedf2;
            padding: 15px 20px;
        }

        .card-body {
            padding: 20px;
        }

        .member-card {
            border-radius: 8px;
            transition: all 0.3s ease;
            padding: 15px !important;
            margin-bottom: 15px;
            border: 1px solid #eaedf2;
        }

        .member-card:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .member-card.current-user {
            background-color: #e8f4fe;
            border-left: 4px solid var(--primary);
        }

        .avatar {
            width: 45px !important;
            height: 45px !important;
            background: linear-gradient(120deg, var(--primary), #5a8aef);
            color: white;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 25px;
            padding-left: 20px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .timeline-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 22px;
            height: calc(100% - 15px);
            width: 2px;
            background-color: #e0e0e0;
        }

        .timeline-item:last-child::before {
            height: 0;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: -38px;
            top: 15px;
            height: 16px;
            width: 16px;
            border-radius: 50%;
            background-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(44, 104, 201, 0.2);
            z-index: 1;
        }

        .timeline-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: inline-block;
            background-color: #f8f9fa;
            padding: 3px 10px;
            border-radius: 15px;
        }

        .timeline-title {
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .file-upload-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px dashed #dee2e6;
            transition: all 0.3s ease;
        }

        .file-upload-form:hover {
            border-color: var(--primary);
            background-color: #f0f7ff;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
            background-color: #fff;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .file-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-icon {
            color: var(--primary);
            font-size: 1.2rem;
            margin-right: 10px;
        }

        .nav-tabs .nav-link {
            font-weight: 500;
            color: #495057;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 15px;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: transparent;
            border-bottom: 2px solid var(--primary);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 10px rgba(44, 104, 201, 0.2);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2456a8;
            border-color: #2456a8;
            box-shadow: 0 6px 15px rgba(44, 104, 201, 0.3);
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .custom-file-label {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            padding-right: 90px;
        }

        .custom-file-input:lang(vi)~.custom-file-label::after {
            content: "Chọn file";
        }

        /* File upload form styles */
        .file-upload-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-top: 15px;
        }

        .file-upload-form .form-group label {
            font-weight: 500;
            color: #495057;
        }

        .file-upload-form textarea {
            resize: vertical;
            min-height: 80px;
        }

        .file-upload-form .btn {
            font-weight: 500;
        }

        /* Proposal file section styles */
        .proposal-file-current {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
        }

        .proposal-update-form {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .proposal-update-form:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        /* Contract form styles */
        .contract-update-form {
            background: linear-gradient(135deg, #fff8f0 0%, #ffffff 100%);
            border: 2px dashed #ffc107;
            border-radius: 10px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .contract-update-form:hover {
            border-color: #ff9800;
            background: linear-gradient(135deg, #ffffff 0%, #fff8f0 100%);
        }

        .contract-update-form .form-control:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }

        .contract-update-form .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .contract-update-form .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #1dd1a1 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        /* Decision form styles */
        .decision-update-form {
            background: linear-gradient(135deg, #f0f8ff 0%, #ffffff 100%);
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .decision-update-form:hover {
            border-color: #0056b3;
            background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);
        }

        .decision-update-form .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .decision-update-form .btn-success {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .decision-update-form .btn-success:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .project-progress {
            height: 8px;
            border-radius: 4px;
            margin: 15px 0;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            background: linear-gradient(to right, var(--primary), #5a8aef);
        }

        .progress-label {
            font-weight: 500;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .feature-icon {
            background-color: #e8f4fe;
            color: var(--primary);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
            box-shadow: 0 3px 10px rgba(44, 104, 201, 0.15);
        }

        .feature-text {
            font-weight: 500;
            color: #495057;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--primary);
        }

        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background-color: #e0f8e9;
            color: #156c2e;
        }

        .alert-danger {
            background-color: #ffe7e7;
            color: #b02a37;
        }

        .alert-info {
            background-color: #e0f7fa;
            color: #0c6a82;
        }

        .alert-warning {
            background-color: #fff9e6;
            color: #997404;
        }

        /* Animation classes */
        .animate-fade-in {
            animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-slide-up {
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(30px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(50px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }
        
        /* Enhanced hover effects */
        .project-header .badge:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.3) !important;
        }
        
        /* Smooth transitions for all elements */
        .project-header *,
        .action-buttons *,
        .status-badge,
        .progress-bar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .project-header {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .project-sidebar-container {
                margin-top: 20px;
                padding: 20px;
            }

            .timeline {
                padding-left: 30px;
            }

            .timeline-item::before {
                left: -20px;
            }

            .timeline-item::after {
                left: -28px;
            }
        }

        @media (max-width: 768px) {
            .project-header {
                padding: 25px 20px;
                text-align: center;
            }
            
            .project-title {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            
            .project-header .info-item {
                justify-content: center;
                text-align: center;
                padding: 10px;
                margin-bottom: 15px;
            }
            
            .project-header .info-item:hover {
                transform: none;
                padding-left: 10px;
            }
            
            .project-sidebar-container {
                margin-top: 25px;
                padding: 20px;
                border-radius: 16px;
                text-align: center;
            }

            .status-badge {
                margin-top: 20px;
                display: inline-block;
                font-size: 1rem;
                padding: 12px 20px;
            }

            .col-md-4.text-md-right {
                text-align: center !important;
                margin-top: 20px;
            }
            
            .action-buttons {
                justify-content: center;
                flex-wrap: wrap;
                flex-direction: column;
                align-items: center;
            }
            
            .action-buttons .btn {
                margin-bottom: 10px;
                width: 100%;
                max-width: 250px;
            }
            
            .file-status-section {
                margin-top: 15px;
                text-align: center;
            }
            
            .file-status-indicators {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }
            
            .progress-label {
                text-align: center;
                flex-direction: column;
                gap: 5px;
            }

            .timeline {
                padding-left: 25px;
            }
        }
        
        @media (max-width: 576px) {
            .project-header {
                padding: 20px 15px;
            }
            
            .project-title {
                font-size: 1.5rem;
                line-height: 1.3;
            }
            
            .project-header .info-item {
                font-size: 0.9rem;
                flex-direction: column;
                text-align: center;
            }
            
            .project-header .info-item i {
                margin-bottom: 5px;
                margin-right: 0;
            }
            
            .project-sidebar-container {
                padding: 15px;
                border-radius: 12px;
            }
            
            .status-badge {
                font-size: 0.9rem;
                padding: 10px 16px;
                flex-direction: column;
                text-align: center;
            }
            
            .status-badge i {
                margin-right: 0;
                margin-bottom: 5px;
            }
            
            .action-buttons .btn {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            
            .badge-file-success,
            .badge-file-warning {
                padding: 6px 10px;
                font-size: 0.8rem;
                margin-bottom: 5px;
            }
            
            .file-status-indicators {
                flex-direction: column;
                align-items: center;
            }
            
            .action-buttons .btn {
                padding: 8px 16px;
                font-size: 0.85rem;
                margin-right: 5px;
                margin-bottom: 8px;
            }
        }        /* Trạng thái đề tài */
        .project-status-container {
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            animation: pulse 2s infinite;
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255,255,255, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255,255,255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255,255,255, 0);
            }
        }
        
        .bg-primary-soft {
            background-color: rgba(44, 104, 201, 0.2);
        }
        
        .bg-success-soft {
            background-color: rgba(40, 167, 69, 0.2);
        }
        
        .bg-warning-soft {
            background-color: rgba(255, 193, 7, 0.2);
        }
        
        .bg-info-soft {
            background-color: rgba(23, 162, 184, 0.2);
        }
        
        .bg-danger-soft {
            background-color: rgba(220, 53, 69, 0.2);
        }
        
        .bg-secondary-soft {
            background-color: rgba(108, 117, 125, 0.2);
        }

        /* Print styles */
        @media print {
            .sidebar, .sidebar-toggle, .no-print {
                display: none !important;
            }

            .content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .project-header {
                background: none !important;
                color: #000 !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin-bottom: 20px !important;
            }

            .card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #eee !important;
            }
        }
        
        /* Council Members Styles */
        .council-members-list .card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        
        .council-members-list .card:hover {
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .selected-council-members {
            min-height: 60px;
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .selected-council-members:not(:empty) {
            border-color: #007bff;
            background-color: #f0f8ff;
        }
        
        #councilMemberModal .modal-dialog {
            max-width: 800px;
        }
        
        #teacherSelect {
            font-family: 'Roboto Mono', monospace;
            font-size: 0.9em;
        }
        
        #teacherSelect option {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .badge-primary {
            background-color: #007bff;
            font-size: 0.8em;
            padding: 4px 8px;
        }
        
        #addCouncilMemberBtn {
            transition: all 0.3s ease;
        }
        
        #addCouncilMemberBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
        }
        
        /* Modal filters styling */
        #councilMemberModal .form-group label {
            font-weight: 600;
            color: #495057;
        }
        
        #departmentFilter, #searchTeacher {
            border-radius: 6px;
            transition: border-color 0.3s ease;
        }
        
        #departmentFilter:focus, #searchTeacher:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1);
        }

        /* Validation styling */
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        
        .is-valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }

        .is-valid:focus {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }
        
        .score-feedback {
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            color: white;
            font-size: 2rem;
        }

        /* Performance optimizations */
        .form-control {
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        #teacherCount {
            font-weight: 500;
        }
        
        .input-group-append .btn {
            border-left: none;
        }
        
        .input-group .form-control:focus {
            z-index: 2;
        }
        
        /* File status indicators */
        .file-status-indicators {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .file-status-indicators .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        /* Responsive design for file status */
        @media (max-width: 768px) {
            .file-status-indicators {
                justify-content: center;
                margin-top: 10px;
            }
            
            .file-status-indicators .badge {
                margin-bottom: 5px;
            }
        }
        
        /* Evaluation Tab Styles */
        .evaluation-result-section {
            background: linear-gradient(135deg, #f8fffe 0%, #f0fff0 100%);
            padding: 20px;
            border-radius: 15px;
            border: 2px solid #28a745;
            position: relative;
            overflow: hidden;
        }
        
        .evaluation-result-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(40, 167, 69, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .evaluation-files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .evaluation-file-card .card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .evaluation-file-card .card:hover {
            border-color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
        }
        
        .evaluation-file-card .card-title {
            color: #495057;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .evaluation-upload-form {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border: 2px dashed #007bff;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .evaluation-upload-form::before {
            content: '';
            position: absolute;
            top: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(0, 123, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .evaluation-upload-form:hover {
            border-color: #0056b3;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.1);
        }
        
        .evaluation-upload-form .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .evaluation-upload-form .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .evaluation-upload-form .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #1dd1a1 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .upload-section {
            position: relative;
            z-index: 2;
        }
        
        /* Badge styles for evaluation result */
        .badge.font-size-sm {
            font-size: 1rem;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        /* Card hover effects in evaluation section */
        .evaluation-result-section .card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        
        .evaluation-result-section .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        /* Custom file input styling */
        .custom-file-label {
            border-radius: 8px;
            font-weight: 500;
        }
        
        .custom-file-input:focus ~ .custom-file-label {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Responsive design for evaluation tab */
        @media (max-width: 768px) {
            .evaluation-files-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .evaluation-upload-form {
                padding: 20px 15px;
            }
            
            .evaluation-result-section {
                padding: 15px;
            }
            
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                margin-bottom: 5px;
                border-radius: 6px !important;
            }
        }
        
        /* Completion Status Styles */
        .completion-status-section .requirement-item {
            transition: all 0.3s ease;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .completion-status-section .requirement-item:last-child {
            border-bottom: none;
        }
        
        .completion-status-section .requirement-item:hover {
            transform: translateX(5px);
            background-color: rgba(0, 123, 255, 0.02);
        }
        
        .completion-requirements .progress {
            height: 25px;
            border-radius: 15px;
            overflow: hidden;
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
        }
        
        .completion-requirements .progress-bar {
            font-weight: 600;
            line-height: 25px;
            transition: width 0.6s ease;
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .overall-status {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .completion-status-section .card {
            border: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
        }
        
        .completion-status-section .card-header {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            border: none;
        }
        
        .completion-status-section .card-header h6 {
            color: white;
        }
        
        #checkCompletionBtn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        #checkCompletionBtn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
        }
        
        /* Special CSS for all projects to ensure active tab is visible */
        .tab-pane.fade.active.show {
            display: block !important;
            opacity: 1 !important;
        }
        
        /* Additional CSS for completed projects */
        body.completed-project .tab-pane.fade.active.show {
            display: block !important;
            opacity: 1 !important;
        }
    </style>
</head>

<body<?php if ($project['DT_TRANGTHAI'] === 'Đã hoàn thành') echo ' class="completed-project"'; ?>>
    <?php include '../../include/student_sidebar.php'; ?>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <div style="margin-top: 10px; font-size: 1rem;">Đang xử lý...</div>
        </div>
    </div>
    
    <div class="container-fluid content" style="margin-left:250px; transition:all 0.3s;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4 animate-fade-in">
            <ol class="breadcrumb bg-white p-3 shadow-sm rounded">
                <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-tachometer-alt mr-1"></i>Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="student_manage_projects.php"><i class="fas fa-clipboard-list mr-1"></i>Quản lý đề tài</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-project-diagram mr-1"></i>Chi tiết đề tài</li>
            </ol>
        </nav>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate-slide-up" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate-slide-up" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Header đề tài -->
        <div class="project-header animate-fade-in">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-7">
                    <h1 class="project-title"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h1>
                    
                    <div class="info-item">
                        <i class="fas fa-barcode"></i>
                        <span>Mã đề tài: <span class="badge badge-light ml-2"><?php echo htmlspecialchars($project['DT_MADT']); ?></span></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="far fa-calendar-alt"></i>
                        <span>Ngày tạo: <?php echo formatDate($project['HD_NGAYTAO']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Thời gian thực hiện: <?php echo formatDate($project['HD_NGAYBD']) . ' - ' . formatDate($project['HD_NGAYKT']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-tag"></i>
                        <span>Loại đề tài: <?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không xác định'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-microscope"></i>
                        <span>Lĩnh vực nghiên cứu: <?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Không xác định'); ?></span>
                    </div>
                    
                    <!-- Thông tin tiến độ -->
                    <div class="mt-4">
                        <div class="progress-label">
                            <span>Số cập nhật tiến độ: <?php echo $progress_count; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-5 text-md-right project-sidebar-container">
                    <!-- Trạng thái đề tài -->                    <div class="project-status-container">
                        <?php 
                        // Xác định class cho badge trạng thái
                        $status_class = '';
                        $status_icon = '';
                        switch ($project['DT_TRANGTHAI']) {
                            case 'Chờ duyệt':
                                $status_class = 'warning';
                                $status_icon = 'clock';
                                break;
                            case 'Đang thực hiện':
                                $status_class = 'primary';
                                $status_icon = 'play-circle';
                                break;
                            case 'Đã hoàn thành':
                                $status_class = 'success';
                                $status_icon = 'check-circle';
                                break;
                            case 'Tạm dừng':
                                $status_class = 'info';
                                $status_icon = 'pause-circle';
                                break;
                            case 'Đã hủy':
                                $status_class = 'danger';
                                $status_icon = 'times-circle';
                                break;
                            default:
                                $status_class = 'secondary';
                                $status_icon = 'question-circle';
                        }
                        ?>
                        <div class="status-badge status-<?php echo $status_class; ?> animate-pulse">
                            <i class="fas fa-<?php echo $status_icon; ?> mr-2"></i>
                            <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                        </div>
                    </div>
                    
                    <?php if ($has_access): ?>
                        <div class="action-buttons mt-3">
                            <?php if ($project['DT_TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                <div class="alert alert-success-custom mb-2">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <strong>Đề tài đã hoàn thành!</strong><br>
                                    <small>Tất cả các file yêu cầu đã được nộp đầy đủ. Không thể chỉnh sửa trong trạng thái này.</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary-custom" disabled>
                                    <i class="fas fa-lock mr-1"></i> Không thể cập nhật
                                </button>
                            <?php elseif ($project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                <?php if ($user_role === 'Chủ nhiệm'): ?>
                                    <button type="button" class="btn btn-sm btn-primary-custom" data-toggle="modal" data-target="#addProgressModal">
                                        <i class="fas fa-tasks mr-1"></i> Cập nhật tiến độ
                                    </button>
                                    
                                    <!-- Hiển thị thông tin về file cần nộp -->
                                    <div class="mt-2 file-status-section">
                                        <small class="text-light-custom">
                                            <i class="fas fa-info-circle mr-1"></i>Trạng thái file yêu cầu:
                                        </small>
                                        <div class="file-status-indicators mt-1">
                                            <span class="badge badge-file-<?php echo $file_completeness['proposal'] ? 'success' : 'warning'; ?> mr-1">
                                                <i class="fas fa-<?php echo $file_completeness['proposal'] ? 'check' : 'exclamation-triangle'; ?> mr-1"></i>
                                                Thuyết minh
                                            </span>
                                            <span class="badge badge-file-<?php echo $file_completeness['contract'] ? 'success' : 'warning'; ?> mr-1">
                                                <i class="fas fa-<?php echo $file_completeness['contract'] ? 'check' : 'exclamation-triangle'; ?> mr-1"></i>
                                                Hợp đồng
                                            </span>
                                            <span class="badge badge-file-<?php echo $file_completeness['decision'] ? 'success' : 'warning'; ?> mr-1">
                                                <i class="fas fa-<?php echo $file_completeness['decision'] ? 'check' : 'exclamation-triangle'; ?> mr-1"></i>
                                                Quyết định
                                            </span>
                                            <span class="badge badge-file-<?php echo $file_completeness['evaluation'] ? 'success' : 'warning'; ?>">
                                                <i class="fas fa-<?php echo $file_completeness['evaluation'] ? 'check' : 'exclamation-triangle'; ?> mr-1"></i>
                                                Đánh giá
                                            </span>
                                        </div>
                                        <?php if (!$file_completeness['proposal'] || !$file_completeness['contract'] || 
                                                  !$file_completeness['decision'] || !$file_completeness['evaluation']): ?>
                                            <small class="text-warning-custom d-block mt-1">
                                                <i class="fas fa-lightbulb mr-1"></i>
                                                Khi nộp đủ tất cả file, đề tài sẽ tự động chuyển sang trạng thái "Đã hoàn thành"
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-secondary-custom" disabled title="Chỉ chủ nhiệm đề tài mới có thể cập nhật tiến độ">
                                        <i class="fas fa-lock mr-1"></i> Cập nhật tiến độ
                                    </button>
                                    <small class="text-light-custom d-block mt-1">
                                        <i class="fas fa-info-circle mr-1"></i> Chỉ chủ nhiệm đề tài mới có thể cập nhật tiến độ và tải file
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-secondary-custom" disabled title="Chỉ có thể cập nhật khi đề tài đang thực hiện">
                                    <i class="fas fa-ban mr-1"></i> Cập nhật tiến độ
                                </button>
                                <small class="text-light-custom d-block mt-1">
                                    <i class="fas fa-info-circle mr-1"></i> Chỉ có thể cập nhật khi đề tài đang ở trạng thái "Đang thực hiện"
                                    <br>Trạng thái hiện tại: <strong><?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?></strong>
                                </small>
                            <?php endif; ?>
                            
                            <button class="btn btn-sm btn-outline-light-custom no-print" id="printProjectBtn">
                                <i class="fas fa-print mr-1"></i> In báo cáo
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Thông tin đề tài -->
            <div class="col-lg-8">
                <div class="card info-card animate-slide-up">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Thông tin đề tài</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-bookmark"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Loại đề tài</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-microscope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Lĩnh vực nghiên cứu</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Lĩnh vực ưu tiên</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['LVUT_TEN'] ?? 'Không có'); ?></div>
                                    </div>
                                </div>                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Ngày tạo</div>
                                        <div class="feature-text">
                                            <?php echo formatDate($project['DT_NGAYTAO']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>                                        <div class="text-muted small">Thời gian thực hiện</div>
                                        <div class="feature-text">
                                            <?php echo formatDate($project['HD_NGAYBD']) . ' - ' . formatDate($project['HD_NGAYKT']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Giảng viên hướng dẫn</div>
                                        <div class="feature-text"><?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Chưa có'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Liên hệ GVHD</div>
                                        <div class="feature-text">
                                            <?php if ($project['GV_EMAIL']): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($project['GV_EMAIL']); ?>" class="text-primary">
                                                    <?php echo htmlspecialchars($project['GV_EMAIL'] ?? ''); ?>
                                                </a>
                                            <?php else: ?>
                                                Không có thông tin
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($contract): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="feature-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Kinh phí</div>
                                        <div class="feature-text"><?php echo number_format($contract['HD_TONGKINHPHI']); ?> VNĐ</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>

                        <div class="mt-3">
                            <h5 class="section-title"><i class="fas fa-align-left"></i> Mô tả đề tài</h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($project['DT_MOTA'] ?? 'Không có mô tả')); ?>
                            </div>
                        </div>

                        <?php if ($project['DT_FILEBTM']): ?>
                            <hr>
                            <div class="mt-3">
                                <h5 class="section-title"><i class="fas fa-file-alt"></i> File thuyết minh</h5>
                                <a href="/NLNganh/uploads/project_files/<?php echo htmlspecialchars($project['DT_FILEBTM']); ?>"
                                    class="btn btn-outline-primary" download>
                                    <i class="fas fa-file-download mr-2"></i> Tải xuống file thuyết minh
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tiến độ đề tài -->
                <div class="card info-card animate-slide-up" style="animation-delay: 0.2s">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tasks mr-2"></i>Tiến độ đề tài</h5>
                        <?php if ($has_access && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                            <button type="button" class="btn btn-sm btn-primary no-print" data-toggle="modal"
                                data-target="#addProgressModal">
                                <i class="fas fa-plus-circle mr-1"></i> Cập nhật tiến độ
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (count($progress_entries) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($progress_entries as $i => $entry): ?>
                                    <div class="timeline-item" style="animation-delay: <?php echo 0.1 * $i; ?>s">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="timeline-date">
                                                <i class="far fa-calendar-alt mr-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($entry['TDDT_NGAYCAPNHAT'])); ?>
                                            </div>
                                        </div>
                                        
                                        <h6 class="timeline-title">
                                            <?php echo htmlspecialchars($entry['TDDT_TIEUDE']); ?>
                                            <?php if ($entry['SV_MASV'] === $_SESSION['user_id']): ?>
                                                <span class="badge badge-info ml-2">Bạn</span>
                                            <?php else: ?>
                                                <small class="text-muted ml-2">(<?php echo htmlspecialchars($entry['SV_HOTEN']); ?>)</small>
                                            <?php endif; ?>
                                        </h6>
                                        
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($entry['TDDT_NOIDUNG'])); ?></p>
                                        
                                        <?php if ($entry['TDDT_FILE']): ?>
                                            <a href="/NLNganh/uploads/progress_files/<?php echo htmlspecialchars($entry['TDDT_FILE']); ?>"
                                                class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-paperclip mr-1"></i>
                                                Tải file đính kèm
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> Chưa có cập nhật tiến độ nào.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nộp báo cáo -->
                <!-- <div class="card info-card animate-slide-up mb-4" style="animation-delay: 0.2s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-upload mr-2"></i>Nộp báo cáo</h5>
                    </div>
                    <div class="card-body">
                        <form action="submit_report.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                            
                            <div class="form-group">
                                <label for="report_title">
                                    <i class="fas fa-heading mr-1"></i> Tiêu đề báo cáo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="report_title" name="report_title" 
                                    placeholder="Nhập tiêu đề báo cáo" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="report_type">
                                    <i class="fas fa-tag mr-1"></i> Loại báo cáo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="report_type" name="report_type" required>
                                    <option value="">-- Chọn loại báo cáo --</option>
                                    <?php
                                    // Fetch report types from database
                                    $report_types_sql = "SELECT LBC_MALOAI, LBC_TENLOAI FROM loai_bao_cao";
                                    $report_types_result = $conn->query($report_types_sql);
                                    while ($type = $report_types_result->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($type['LBC_MALOAI']) . '">' . 
                                            htmlspecialchars($type['LBC_TENLOAI']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="report_description">
                                    <i class="fas fa-align-left mr-1"></i> Mô tả báo cáo
                                </label>
                                <textarea class="form-control" id="report_description" name="report_description" 
                                    rows="3" placeholder="Nhập mô tả ngắn về báo cáo"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="report_file">
                                    <i class="fas fa-file mr-1"></i> File báo cáo <span class="text-danger">*</span>
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="report_file" name="report_file" required>
                                    <label class="custom-file-label" for="report_file">Chọn file...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Các định dạng hỗ trợ: PDF, Word, Excel, PowerPoint, ZIP, RAR. Kích thước tối đa: 20MB.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload mr-1"></i> Nộp báo cáo
                            </button>
                        </form>
                    </div>
                </div> -->
            </div>

            <!-- Sidebar bên phải -->
            <div class="col-lg-4">
                <!-- Thành viên tham gia -->
                <div class="card info-card animate-slide-up" style="animation-delay: 0.1s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Thành viên tham gia</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $member): ?>
                                <div class="member-card <?php echo ($member['SV_MASV'] === $_SESSION['user_id']) ? 'current-user' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar rounded-circle d-flex align-items-center justify-content-center">
                                            <?php echo strtoupper(mb_substr($member['SV_HOTEN'] ?? 'U', 0, 1, 'UTF-8')); ?>
                                        </div>
                                        <div class="ml-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($member['SV_HOTEN'] ?? 'Không rõ'); ?>
                                                <?php if ($member['SV_MASV'] === $_SESSION['user_id']): ?>
                                                    <span class="badge badge-info ml-1">Bạn</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-0 text-muted small">
                                                <span class="badge <?php echo (isset($member['CTTG_VAITRO']) && $member['CTTG_VAITRO'] == 'Chủ nhiệm') ? 'badge-primary' : 'badge-secondary'; ?>">
                                                    <?php echo htmlspecialchars($member['CTTG_VAITRO'] ?? 'Thành viên'); ?>
                                                </span>
                                                <span class="ml-2"><?php echo htmlspecialchars($member['LOP_TEN'] ?? 'Không rõ lớp'); ?></span>
                                            </p>
                                            <p class="mb-0 text-muted small mt-1">
                                                <i class="far fa-calendar-alt mr-1"></i>
                                                Tham gia: <?php echo isset($member['CTTG_NGAYTHAMGIA']) ? date('d/m/Y', strtotime($member['CTTG_NGAYTHAMGIA'])) : 'Chưa xác định'; ?>
                                            </p>
                                            <p class="mb-0 text-muted small mt-1">
                                                <i class="fas fa-clock mr-1"></i>
                                                Ngày tạo: <?php echo isset($member['CTTG_NGAYTAO']) ? date('d/m/Y', strtotime($member['CTTG_NGAYTAO'])) : date('d/m/Y'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i> Không tìm thấy thông tin thành viên.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quản lý file liên quan -->
                <div class="card info-card animate-slide-up" style="animation-delay: 0.2s">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Tài liệu liên quan</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="documentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="proposal-tab" data-toggle="tab" href="#proposal" role="tab" aria-controls="proposal" aria-selected="true">
                                    <i class="fas fa-file-alt mr-1"></i> Thuyết minh
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="contract-tab" data-toggle="tab" href="#contract" role="tab" aria-controls="contract" aria-selected="false">
                                    <i class="fas fa-file-contract mr-1"></i> Hợp đồng
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="decision-tab" data-toggle="tab" href="#decision" role="tab" aria-controls="decision" aria-selected="false">
                                    <i class="fas fa-file-signature mr-1"></i> Quyết định
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="report-tab" data-toggle="tab" href="#report" role="tab" aria-controls="report" aria-selected="false">
                                    <i class="fas fa-file-invoice mr-1"></i> Biên bản
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="evaluation-tab" data-toggle="tab" href="#evaluation" role="tab" aria-controls="evaluation" aria-selected="false">
                                    <i class="fas fa-clipboard-check mr-1"></i> Đánh giá
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="documentTabsContent">
                            <!-- Tab Thuyết minh -->
                            <div class="tab-pane fade show active" id="proposal" role="tabpanel" aria-labelledby="proposal-tab">
                                <?php if ($project['DT_FILEBTM']): ?>
                                    <div class="proposal-file-current">
                                        <h6 class="text-primary mb-3"><i class="fas fa-file-alt mr-2"></i>File thuyết minh hiện tại</h6>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="far fa-file-pdf file-icon text-danger mr-2"></i>
                                                <span class="font-weight-medium"><?php echo htmlspecialchars($project['DT_FILEBTM']); ?></span>
                                            </div>
                                            <a href="/NLNganh/uploads/project_files/<?php echo htmlspecialchars($project['DT_FILEBTM']); ?>"
                                                class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download mr-1"></i> Tải xuống
                                            </a>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            Ngày tạo đề tài: <?php echo formatDate($project['DT_NGAYTAO']); ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i> Chưa có file thuyết minh.
                                    </div>
                                <?php endif; ?>

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                    <div class="proposal-update-form">
                                        <h6 class="mb-3 text-center">
                                            <i class="fas fa-upload mr-2"></i>Cập nhật file thuyết minh
                                        </h6>
                                        <form action="/NLNganh/view/student/update_proposal_file.php" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="project_id"
                                                value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                            
                                            <div class="form-group">
                                                <label for="proposal_update_reason">
                                                    <i class="fas fa-edit mr-1"></i> Lý do cập nhật <span class="text-danger">*</span>
                                                </label>
                                                <textarea class="form-control" id="proposal_update_reason" name="update_reason" 
                                                    rows="3" placeholder="Nhập lý do cập nhật file thuyết minh (ví dụ: bổ sung nội dung, sửa lỗi chính tả, cập nhật thông tin...)" required></textarea>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Thông tin này sẽ được ghi lại trong tiến độ đề tài
                                                </small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="proposal_file">
                                                    <i class="fas fa-file mr-1"></i> File thuyết minh mới <span class="text-danger">*</span>
                                                </label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="proposal_file" 
                                                        name="proposal_file" required accept=".pdf,.doc,.docx">
                                                    <label class="custom-file-label" for="proposal_file">Chọn file...</label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-file-pdf mr-1"></i> PDF, 
                                                    <i class="fas fa-file-word mr-1"></i> Word | 
                                                    <i class="fas fa-weight-hanging mr-1"></i> Tối đa: 10MB
                                                </small>
                                            </div>
                                            
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fas fa-upload mr-2"></i> Cập nhật file thuyết minh
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php elseif ($has_access && $project['DT_TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle mr-2"></i> 
                                        <strong>Đề tài đã hoàn thành:</strong> Không thể chỉnh sửa file thuyết minh khi đề tài ở trạng thái "Đã hoàn thành".
                                        <br><small class="text-muted">Tất cả tài liệu đã được nộp đầy đủ và được phê duyệt.</small>
                                    </div>
                                <?php elseif ($has_access && $user_role !== 'Chủ nhiệm'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock mr-2"></i> 
                                        <strong>Quyền hạn bị hạn chế:</strong> Chỉ chủ nhiệm đề tài mới có thể cập nhật file thuyết minh.
                                        <br><small class="text-muted">Vai trò của bạn: <strong><?php echo htmlspecialchars($user_role); ?></strong></small>
                                    </div>
                                <?php elseif ($has_access && $project['DT_TRANGTHAI'] !== 'Đang thực hiện'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> 
                                        <strong>Không thể cập nhật:</strong> Chỉ có thể cập nhật file khi đề tài đang trong trạng thái "Đang thực hiện".
                                        <br><small class="text-muted">Trạng thái hiện tại: <strong><?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?></strong></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Tab Hợp đồng -->
                            <div class="tab-pane fade" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                                <?php if ($contract): ?>
                                    <div class="mb-3">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-info-circle mr-2"></i>Thông tin hợp đồng</h6>
                                                <p class="mb-2"><strong>Mã hợp đồng:</strong>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($contract['HD_MA']); ?></span>
                                                </p>
                                                <p class="mb-2"><strong>Ngày tạo:</strong>
                                                    <?php echo isset($contract['HD_NGAYTAO']) ? date('d/m/Y', strtotime($contract['HD_NGAYTAO'])) : 'Chưa xác định'; ?>
                                                </p>
                                                <p class="mb-2"><strong>Thời gian thực hiện:</strong><br>
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($contract['HD_NGAYBD']) ? date('d/m/Y', strtotime($contract['HD_NGAYBD'])) : 'Chưa xác định'; ?> - 
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($contract['HD_NGAYKT']) ? date('d/m/Y', strtotime($contract['HD_NGAYKT'])) : 'Chưa xác định'; ?>
                                                </p>
                                                <p class="mb-2"><strong>Tổng kinh phí:</strong>
                                                    <span class="text-success font-weight-bold">
                                                        <?php echo isset($contract['HD_TONGKINHPHI']) ? number_format($contract['HD_TONGKINHPHI']) : '0'; ?> VNĐ
                                                    </span>
                                                </p>

                                                <?php if (isset($contract['HD_FILEHD']) && $contract['HD_FILEHD']): ?>
                                                    <hr>
                                                    <a href="/NLNganh/uploads/contract_files/<?php echo htmlspecialchars($contract['HD_FILEHD']); ?>"
                                                        class="btn btn-info btn-block" download>
                                                        <i class="fas fa-file-download mr-2"></i> Tải xuống hợp đồng
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có thông tin hợp đồng.
                                    </div>
                                <?php endif; ?>

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                    <div class="contract-update-form">
                                        <h6 class="mb-3 text-center">
                                            <i class="fas fa-file-signature mr-2"></i>
                                            <?php echo $contract ? 'Cập nhật thông tin hợp đồng' : 'Nhập thông tin hợp đồng'; ?>
                                        </h6>
                                        <form action="/NLNganh/view/student/update_contract_info.php" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="project_id"
                                                value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                            <?php if ($contract): ?>
                                                <input type="hidden" name="contract_id"
                                                    value="<?php echo htmlspecialchars($contract['HD_MA']); ?>">
                                            <?php endif; ?>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="contract_code">
                                                            <i class="fas fa-barcode mr-1"></i> Mã hợp đồng <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" class="form-control" id="contract_code" name="contract_code" 
                                                            value="<?php echo htmlspecialchars($contract['HD_MA'] ?? ''); ?>" 
                                                            placeholder="Nhập mã hợp đồng" required maxlength="11">
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Mã hợp đồng có độ dài tối đa 11 ký tự.
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="contract_date">
                                                            <i class="far fa-calendar-alt mr-1"></i> Ngày tạo hợp đồng <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="date" class="form-control" id="contract_date" name="contract_date" 
                                                            value="<?php echo isset($contract['HD_NGAYTAO']) ? date('Y-m-d', strtotime($contract['HD_NGAYTAO'])) : ''; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="start_date">
                                                            <i class="fas fa-play mr-1"></i> Ngày bắt đầu <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                                            value="<?php echo isset($contract['HD_NGAYBD']) ? date('Y-m-d', strtotime($contract['HD_NGAYBD'])) : ''; ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="end_date">
                                                            <i class="fas fa-stop mr-1"></i> Ngày kết thúc <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                                            value="<?php echo isset($contract['HD_NGAYKT']) ? date('Y-m-d', strtotime($contract['HD_NGAYKT'])) : ''; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="total_budget">
                                                    <i class="fas fa-money-bill-wave mr-1"></i> Tổng kinh phí (VNĐ) <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control" id="total_budget" name="total_budget" 
                                                    value="<?php echo htmlspecialchars($contract['HD_TONGKINHPHI'] ?? ''); ?>" 
                                                    placeholder="Nhập tổng kinh phí" min="0" step="1000" required>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Nhập số tiền bằng VNĐ (ví dụ: 5000000 cho 5 triệu VNĐ)
                                                </small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="contract_description">
                                                    <i class="fas fa-align-left mr-1"></i> Mô tả hợp đồng
                                                </label>
                                                <textarea class="form-control" id="contract_description" name="contract_description" 
                                                    rows="3" placeholder="Nhập mô tả về nội dung hợp đồng, điều khoản đặc biệt..."><?php echo htmlspecialchars($contract['HD_GHICHU'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="contract_update_reason">
                                                    <i class="fas fa-edit mr-1"></i> Lý do cập nhật <span class="text-danger">*</span>
                                                </label>
                                                <textarea class="form-control" id="contract_update_reason" name="update_reason" 
                                                    rows="2" placeholder="Nhập lý do cập nhật thông tin hợp đồng (ví dụ: bổ sung thông tin, sửa đổi ngày tháng...)" required></textarea>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Thông tin này sẽ được ghi lại trong tiến độ đề tài
                                                </small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="contract_file">
                                                    <i class="fas fa-file mr-1"></i> File hợp đồng <?php echo $contract ? '' : '<span class="text-danger">*</span>'; ?>
                                                </label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="contract_file" 
                                                        name="contract_file" <?php echo $contract ? '' : 'required'; ?> accept=".pdf,.doc,.docx">
                                                    <label class="custom-file-label" for="contract_file">Chọn file...</label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-file-pdf mr-1"></i> PDF, 
                                                    <i class="fas fa-file-word mr-1"></i> Word | 
                                                    <i class="fas fa-weight-hanging mr-1"></i> Tối đa: 15MB
                                                    <?php if ($contract): ?>
                                                        <br><i class="fas fa-info-circle mr-1"></i> Bỏ trống nếu không muốn thay đổi file
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fas fa-save mr-2"></i> 
                                                    <?php echo $contract ? 'Cập nhật thông tin hợp đồng' : 'Lưu thông tin hợp đồng'; ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php elseif ($has_access && $project['DT_TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle mr-2"></i> 
                                        <strong>Đề tài đã hoàn thành:</strong> Không thể chỉnh sửa thông tin hợp đồng khi đề tài ở trạng thái "Đã hoàn thành".
                                        <br><small class="text-muted">Tất cả tài liệu đã được nộp đầy đủ và được phê duyệt.</small>
                                    </div>
                                <?php elseif ($has_access && $user_role !== 'Chủ nhiệm'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock mr-2"></i> 
                                        <strong>Quyền hạn bị hạn chế:</strong> Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin hợp đồng.
                                        <br><small class="text-muted">Vai trò của bạn: <strong><?php echo htmlspecialchars($user_role); ?></strong></small>
                                    </div>
                                <?php elseif ($has_access && $project['DT_TRANGTHAI'] !== 'Đang thực hiện'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> 
                                        <strong>Không thể cập nhật:</strong> Chỉ có thể cập nhật hợp đồng khi đề tài đang trong trạng thái "Đang thực hiện".
                                        <br><small class="text-muted">Trạng thái hiện tại: <strong><?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?></strong></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Quyết định -->
                            <div class="tab-pane fade" id="decision" role="tabpanel" aria-labelledby="decision-tab">
                                <?php if ($decision): ?>
                                    <div class="mb-3">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-gavel mr-2"></i>Thông tin quyết định nghiệm thu</h6>
                                                <p class="mb-2"><strong>Số quyết định:</strong>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($decision['QD_SO']); ?></span>
                                                </p>
                                                <p class="mb-2"><strong>Ngày quyết định:</strong>
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($decision['QD_NGAY']) ? date('d/m/Y', strtotime($decision['QD_NGAY'])) : 'Chưa xác định'; ?>
                                                </p>

                                                <?php if (isset($decision['QD_FILE']) && $decision['QD_FILE']): ?>
                                                    <hr>
                                                    <a href="/NLNganh/uploads/decision_files/<?php echo htmlspecialchars($decision['QD_FILE']); ?>"
                                                        class="btn btn-info btn-block" download>
                                                        <i class="fas fa-file-download mr-2"></i> Tải xuống quyết định
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có thông tin quyết định nghiệm thu.
                                    </div>
                                <?php endif; ?>

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                    <div class="decision-update-form">
                                        <h6 class="mb-3 text-center">
                                            <i class="fas fa-gavel mr-2"></i>
                                            <?php echo $decision ? 'Cập nhật thông tin quyết định nghiệm thu' : 'Nhập thông tin quyết định nghiệm thu'; ?>
                                        </h6>
                                        <form action="/NLNganh/view/student/update_decision_info.php" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="project_id"
                                                value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                            <?php if ($decision): ?>
                                                <input type="hidden" name="decision_id"
                                                    value="<?php echo htmlspecialchars($decision['QD_SO']); ?>">
                                            <?php endif; ?>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="decision_number">
                                                            <i class="fas fa-hashtag mr-1"></i> Số quyết định <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" class="form-control" id="decision_number" name="decision_number" 
                                                            value="<?php echo htmlspecialchars($decision['QD_SO'] ?? ''); ?>" 
                                                            placeholder="Nhập số quyết định (ví dụ: QD2024-0001)" required maxlength="11">
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Số quyết định có độ dài tối đa 11 ký tự.
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="decision_date">
                                                            <i class="far fa-calendar-alt mr-1"></i> Ngày ra quyết định <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="date" class="form-control" id="decision_date" name="decision_date" 
                                                            value="<?php echo isset($decision['QD_NGAY']) ? date('Y-m-d', strtotime($decision['QD_NGAY'])) : ''; ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="decision_content">
                                                    <i class="fas fa-align-left mr-1"></i> Nội dung quyết định
                                                </label>
                                                <textarea class="form-control" id="decision_content" name="decision_content" 
                                                    rows="3" placeholder="Nhập nội dung chi tiết của quyết định nghiệm thu..."><?php echo htmlspecialchars($decision['QD_NOIDUNG'] ?? ''); ?></textarea>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Có thể bao gồm: kết quả nghiệm thu, nhận xét của hội đồng, khuyến nghị...
                                                </small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="decision_update_reason">
                                                    <i class="fas fa-edit mr-1"></i> Lý do cập nhật <span class="text-danger">*</span>
                                                </label>
                                                <textarea class="form-control" id="decision_update_reason" name="update_reason" 
                                                    rows="2" placeholder="Nhập lý do cập nhật thông tin quyết định (ví dụ: bổ sung thông tin nghiệm thu, cập nhật kết quả...)" required></textarea>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Thông tin này sẽ được ghi lại trong tiến độ đề tài
                                                </small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="decision_file">
                                                    <i class="fas fa-file mr-1"></i> File quyết định <?php echo $decision ? '' : '<span class="text-danger">*</span>'; ?>
                                                </label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="decision_file" 
                                                        name="decision_file" <?php echo $decision ? '' : 'required'; ?> accept=".pdf,.doc,.docx">
                                                    <label class="custom-file-label" for="decision_file">Chọn file...</label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-file-pdf mr-1"></i> PDF, 
                                                    <i class="fas fa-file-word mr-1"></i> Word | 
                                                    <i class="fas fa-weight-hanging mr-1"></i> Tối đa: 10MB
                                                    <?php if ($decision): ?>
                                                        <br><i class="fas fa-info-circle mr-1"></i> Bỏ trống nếu không muốn thay đổi file
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="fas fa-save mr-2"></i> 
                                                    <?php echo $decision ? 'Cập nhật thông tin quyết định' : 'Lưu thông tin quyết định'; ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php elseif ($has_access && $project['DT_TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle mr-2"></i> 
                                        <strong>Đề tài đã hoàn thành:</strong> Không thể chỉnh sửa thông tin quyết định nghiệm thu khi đề tài ở trạng thái "Đã hoàn thành".
                                        <br><small class="text-muted">Tất cả tài liệu đã được nộp đầy đủ và được phê duyệt.</small>
                                    </div>
                                <?php elseif ($has_access && $user_role !== 'Chủ nhiệm'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock mr-2"></i> 
                                        <strong>Quyền hạn bị hạn chế:</strong> Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin quyết định nghiệm thu.
                                        <br><small class="text-muted">Vai trò của bạn: <strong><?php echo htmlspecialchars($user_role); ?></strong></small>
                                    </div>
                                <?php elseif ($has_access && $project['DT_TRANGTHAI'] !== 'Đang thực hiện'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> 
                                        <strong>Không thể cập nhật:</strong> Chỉ có thể cập nhật quyết định khi đề tài đang trong trạng thái "Đang thực hiện".
                                        <br><small class="text-muted">Trạng thái hiện tại: <strong><?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?></strong></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Biên bản nghiệm thu -->
                            <div class="tab-pane fade" id="report" role="tabpanel" aria-labelledby="report-tab">
                                <?php if ($decision): ?>
                                    <?php
                                    // Tính toán tổng điểm thực tế từ thành viên hội đồng
                                    $actual_total_score = null;
                                    $actual_classification = '';
                                    
                                    if (!empty($council_members)) {
                                        // Lọc những thành viên có điểm hợp lệ (từ 0 đến 100)
                                        $scored_members = array_filter($council_members, function($member) {
                                            return !empty($member['TV_DIEM']) && is_numeric($member['TV_DIEM']) && 
                                                   $member['TV_DIEM'] >= 0 && $member['TV_DIEM'] <= 100;
                                        });
                                        
                                        if (count($scored_members) > 0) {
                                            // Tính điểm trung bình ban đầu
                                            $total_score = array_sum(array_column($scored_members, 'TV_DIEM'));
                                            $initial_average = $total_score / count($scored_members);
                                            
                                            // Lọc điểm hợp lệ (không chênh lệch quá 15 điểm so với trung bình ban đầu)
                                            // và đảm bảo điểm nằm trong khoảng hợp lý
                                            $valid_members = array_filter($scored_members, function($member) use ($initial_average) {
                                                $score = floatval($member['TV_DIEM']);
                                                return $score >= 0 && $score <= 100 && abs($score - $initial_average) <= 15;
                                            });
                                            
                                            // Tính điểm trung bình cuối cùng từ các điểm hợp lệ
                                            if (count($valid_members) > 0) {
                                                $valid_scores = array_column($valid_members, 'TV_DIEM');
                                                $actual_total_score = array_sum($valid_scores) / count($valid_scores);
                                                
                                                // Đảm bảo điểm nằm trong khoảng 0-100
                                                $actual_total_score = max(0, min(100, $actual_total_score));
                                            } else {
                                                $actual_total_score = 0;
                                            }
                                                
                                            // Xếp loại theo điểm trung bình cuối cùng
                                            if ($actual_total_score >= 90) {
                                                $actual_classification = 'Xuất sắc';
                                            } elseif ($actual_total_score >= 80) {
                                                $actual_classification = 'Tốt';
                                            } elseif ($actual_total_score >= 70) {
                                                $actual_classification = 'Khá';
                                            } elseif ($actual_total_score >= 50) {
                                                $actual_classification = 'Đạt';
                                            } else {
                                                $actual_classification = 'Không đạt';
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="mb-3">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-3"><i class="fas fa-file-invoice mr-2"></i>Thông tin biên bản nghiệm thu</h6>
                                                <p class="mb-2"><strong>Số biên bản:</strong>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($decision['BB_SOBB'] ?? 'Chưa xác định'); ?></span>
                                                </p>
                                                <p class="mb-2"><strong>Ngày nghiệm thu:</strong>
                                                    <i class="far fa-calendar-alt mr-1"></i> 
                                                    <?php echo isset($decision['BB_NGAYNGHIEMTHU']) ? date('d/m/Y', strtotime($decision['BB_NGAYNGHIEMTHU'])) : 'Chưa xác định'; ?>
                                                </p>
                                                <p class="mb-2"><strong>Xếp loại:</strong>
                                                    <span class="badge <?php 
                                                        $display_xeploai = '';
                                                        if ($actual_classification) {
                                                            $display_xeploai = $actual_classification;
                                                        } else {
                                                            $display_xeploai = isset($decision['BB_XEPLOAI']) ? $decision['BB_XEPLOAI'] : '';
                                                        }
                                                        echo ($display_xeploai == 'Xuất sắc' || $display_xeploai == 'Tốt') ? 'badge-success' : 
                                                            (($display_xeploai == 'Khá' || $display_xeploai == 'Đạt') ? 'badge-primary' : 'badge-secondary'); 
                                                        ?>">
                                                        <?php echo htmlspecialchars($display_xeploai ?: 'Chưa xác định'); ?>
                                                    </span>
                                                    <?php if ($actual_classification && isset($decision['BB_XEPLOAI']) && $actual_classification !== $decision['BB_XEPLOAI']): ?>
                                                        <small class="text-muted ml-2">
                                                            <i class="fas fa-info-circle" title="Xếp loại được tính toán từ điểm thành viên hội đồng"></i>
                                                            (DB: <?php echo htmlspecialchars($decision['BB_XEPLOAI']); ?>)
                                                        </small>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mb-2"><strong>Tổng điểm:</strong>
                                                    <span class="badge badge-info">
                                                        <?php 
                                                        if ($actual_total_score !== null) {
                                                            echo number_format($actual_total_score, 1) . '/100';
                                                        } elseif (isset($decision['BB_TONGDIEM']) && $decision['BB_TONGDIEM'] > 0) {
                                                            echo number_format($decision['BB_TONGDIEM'], 1) . '/100';
                                                        } else {
                                                            echo 'Chưa xác định';
                                                        }
                                                        ?>
                                                    </span>
                                                    <?php if ($actual_total_score !== null && isset($decision['BB_TONGDIEM']) && $decision['BB_TONGDIEM'] > 0 && abs($actual_total_score - $decision['BB_TONGDIEM']) > 0.1): ?>
                                                        <small class="text-muted ml-2">
                                                            <i class="fas fa-info-circle" title="Điểm được tính toán từ thành viên hội đồng" data-toggle="tooltip"></i>
                                                            (DB: <?php echo number_format($decision['BB_TONGDIEM'], 1); ?>)
                                                        </small>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có thông tin biên bản nghiệm thu. Vui lòng tạo quyết định nghiệm thu trước.
                                    </div>
                                <?php endif; ?>

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện' && $decision): ?>
                                    <div class="report-update-form">
                                        <h6 class="mb-3 text-center">
                                            <i class="fas fa-file-invoice mr-2"></i>
                                            Cập nhật thông tin biên bản nghiệm thu
                                        </h6>
                                        
                                        <!-- Form 1: Cập nhật thông tin cơ bản biên bản -->
                                        <div class="card mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-edit mr-2"></i>Thông tin cơ bản biên bản
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <form action="/NLNganh/view/student/update_report_basic_simple.php" method="post" id="reportBasicForm">
                                                    <input type="hidden" name="project_id"
                                                        value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                                    <input type="hidden" name="decision_id"
                                                        value="<?php echo htmlspecialchars($decision['QD_SO']); ?>">
                                                    <?php if (isset($decision['BB_SOBB']) && !empty($decision['BB_SOBB'])): ?>
                                                        <input type="hidden" name="report_id"
                                                            value="<?php echo htmlspecialchars($decision['BB_SOBB']); ?>">
                                                    <?php endif; ?>>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="acceptance_date">
                                                                    <i class="fas fa-calendar-check mr-1"></i> Ngày nghiệm thu <span class="text-danger">*</span>
                                                                </label>
                                                                <input type="date" class="form-control" id="acceptance_date" name="acceptance_date" 
                                                                    value="<?php echo isset($decision['BB_NGAYNGHIEMTHU']) ? date('Y-m-d', strtotime($decision['BB_NGAYNGHIEMTHU'])) : ''; ?>" required>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="evaluation_grade">
                                                                    <i class="fas fa-award mr-1"></i> Xếp loại nghiệm thu <span class="text-danger">*</span>
                                                                </label>
                                                                <select class="form-control" id="evaluation_grade" name="evaluation_grade" required>
                                                                    <option value="">-- Chọn xếp loại --</option>
                                                                                    <option value="Xuất sắc" <?php 
                                                                        $current_grade = $actual_classification ?: (isset($decision['BB_XEPLOAI']) ? $decision['BB_XEPLOAI'] : '');
                                                                        echo ($current_grade === 'Xuất sắc') ? 'selected' : ''; 
                                                                    ?>>Xuất sắc</option>
                                                                    <option value="Tốt" <?php echo ($current_grade === 'Tốt') ? 'selected' : ''; ?>>Tốt</option>
                                                                    <option value="Khá" <?php echo ($current_grade === 'Khá') ? 'selected' : ''; ?>>Khá</option>
                                                                    <option value="Đạt" <?php echo ($current_grade === 'Đạt') ? 'selected' : ''; ?>>Đạt</option>
                                                                    <option value="Không đạt" <?php echo ($current_grade === 'Không đạt') ? 'selected' : ''; ?>>Không đạt</option>
                                                                </select>
                                                                <?php if ($actual_classification): ?>
                                                                    <small class="form-text text-success">
                                                                        <i class="fas fa-calculator mr-1"></i>
                                                                        Xếp loại được đề xuất: <strong><?php echo $actual_classification; ?></strong> (dựa trên điểm <?php echo number_format($actual_total_score, 1); ?>)
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="total_score">
                                                            <i class="fas fa-star mr-1"></i> Tổng điểm đánh giá (0-100)
                                                        </label>
                                                        <input type="number" class="form-control" id="total_score" name="total_score" 
                                                            min="0" max="100" step="0.1" 
                                                            value="<?php 
                                                                if ($actual_total_score !== null) {
                                                                    echo number_format($actual_total_score, 1);
                                                                } elseif (isset($decision['BB_TONGDIEM'])) {
                                                                    echo $decision['BB_TONGDIEM'];
                                                                } else {
                                                                    echo '';
                                                                }
                                                            ?>" 
                                                            placeholder="Nhập tổng điểm đánh giá">
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Điểm từ 0 đến 100, có thể nhập số thập phân (ví dụ: 85.5)
                                                            <?php if ($actual_total_score !== null): ?>
                                                                <br><i class="fas fa-calculator mr-1 text-success"></i>
                                                                Điểm được tính tự động: <?php echo number_format($actual_total_score, 1); ?>/100 từ thành viên hội đồng
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="text-center">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-save mr-2"></i> 
                                                            Cập nhật thông tin biên bản
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Form 2: Cập nhật thành viên hội đồng -->
                                        <div class="card">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-users mr-2"></i>Thành viên hội đồng nghiệm thu
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <form action="/NLNganh/view/student/update_council_members.php" method="post" id="councilMembersForm">
                                                    <input type="hidden" name="project_id"
                                                        value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                                    <input type="hidden" name="decision_id"
                                                        value="<?php echo htmlspecialchars($decision['QD_SO']); ?>">
                                            
                                                    <div class="form-group">
                                                        <label>
                                                            <i class="fas fa-users mr-1"></i> Thành viên hội đồng nghiệm thu
                                                        </label>
                                                        
                                                        <!-- Danh sách thành viên đã chọn -->
                                                        <div id="selectedCouncilMembers" class="selected-council-members mb-3">
                                                            <?php if (!empty($decision['HD_THANHVIEN'])): ?>
                                                                <div class="alert alert-info">
                                                                    <strong>Thành viên hiện tại:</strong><br>
                                                                    <?php echo nl2br(htmlspecialchars($decision['HD_THANHVIEN'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <!-- Nút thêm thành viên -->
                                                        <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addCouncilMemberBtn">
                                                            <i class="fas fa-plus mr-1"></i> Thêm thành viên hội đồng
                                                        </button>
                                                        
                                                        <!-- Input ẩn để lưu dữ liệu -->
                                                        <input type="hidden" id="council_members" name="council_members" value="<?php echo htmlspecialchars(str_replace(array("\r", "\n"), ' ', $decision['HD_THANHVIEN'] ?? '')); ?>">
                                                        <input type="hidden" id="council_members_json" name="council_members_json" value="">
                                                        
                                                        <small class="form-text text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            Chọn giảng viên từ danh sách và chỉ định vai trò (Chủ tịch, Thành viên, Thư ký)
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="text-center">
                                                        <button type="submit" class="btn btn-success btn-lg">
                                                            <i class="fas fa-save mr-2"></i> 
                                                            Cập nhật thành viên hội đồng
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Form 3: Cập nhật điểm thành viên hội đồng -->
                                        <?php if (!empty($council_members)): ?>
                                        <div class="card mt-3">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-star mr-2"></i>Cập nhật điểm đánh giá thành viên hội đồng
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <form action="/NLNganh/view/student/update_council_scores.php" method="post" id="councilScoresForm">
                                                    <input type="hidden" name="project_id"
                                                        value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                                    <input type="hidden" name="decision_id"
                                                        value="<?php echo htmlspecialchars($decision['QD_SO']); ?>">
                                                    
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle mr-2"></i>
                                                        <strong>Hướng dẫn:</strong> Nhập điểm đánh giá cho từng thành viên hội đồng (0-100 điểm). 
                                                        Hệ thống sẽ tự động tính điểm trung bình và cập nhật vào biên bản.
                                                    </div>
                                                    
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th>Họ tên</th>
                                                                    <th>Vai trò</th>
                                                                    <th>Điểm hiện tại</th>
                                                                    <th>Điểm mới (0-100)</th>
                                                                    <th>Trạng thái</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($council_members as $index => $member): ?>
                                                                <tr>
                                                                    <td>
                                                                        <strong><?php echo htmlspecialchars($member['TV_HOTEN'] ?: $member['GV_HOTEN']); ?></strong>
                                                                        <br><small class="text-muted"><?php echo htmlspecialchars($member['GV_MAGV']); ?></small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge <?php 
                                                                            echo $member['TV_VAITRO'] === 'Chủ tịch hội đồng' ? 'badge-primary' : 
                                                                                ($member['TV_VAITRO'] === 'Thư ký' ? 'badge-info' : 'badge-secondary'); 
                                                                        ?>">
                                                                            <?php echo htmlspecialchars($member['TV_VAITRO']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <?php if ($member['TV_DIEM'] !== null && is_numeric($member['TV_DIEM'])): ?>
                                                                            <span class="badge badge-success font-weight-bold">
                                                                                <?php echo number_format((float)$member['TV_DIEM'], 1); ?>/100
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">Chưa có</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" 
                                                                               class="form-control" 
                                                                               name="member_scores[<?php echo htmlspecialchars($member['GV_MAGV']); ?>]"
                                                                               min="0" 
                                                                               max="100" 
                                                                               step="0.1"
                                                                               value="<?php echo ($member['TV_DIEM'] !== null && is_numeric($member['TV_DIEM'])) ? number_format((float)$member['TV_DIEM'], 1) : ''; ?>"
                                                                               placeholder="Nhập điểm">
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <?php if ($member['TV_DIEM'] !== null): ?>
                                                                            <i class="fas fa-check-circle text-success" title="Đã có điểm"></i>
                                                                        <?php else: ?>
                                                                            <i class="fas fa-clock text-warning" title="Chưa chấm điểm"></i>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    
                                                    <?php 
                                                    // Tính thống kê điểm hiện tại
                                                    $scored_count = 0;
                                                    $total_score = 0;
                                                    foreach ($council_members as $member) {
                                                        if ($member['TV_DIEM'] !== null) {
                                                            $scored_count++;
                                                            $total_score += $member['TV_DIEM'];
                                                        }
                                                    }
                                                    $average_score = $scored_count > 0 ? $total_score / $scored_count : 0;
                                                    ?>
                                                    
                                                    <?php if ($scored_count > 0): ?>
                                                    <div class="alert alert-success">
                                                        <i class="fas fa-calculator mr-2"></i>
                                                        <strong>Thống kê hiện tại:</strong> 
                                                        <?php echo $scored_count; ?>/<?php echo count($council_members); ?> thành viên đã chấm điểm
                                                        <br><strong>Điểm trung bình:</strong> <?php echo number_format($average_score, 2); ?>/100
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="text-center">
                                                        <button type="submit" class="btn btn-warning btn-lg">
                                                            <i class="fas fa-star mr-2"></i> 
                                                            Cập nhật điểm thành viên hội đồng
                                                        </button>
                                                        <button type="button" class="btn btn-secondary ml-2" onclick="resetScores()">
                                                            <i class="fas fa-undo mr-2"></i> 
                                                            Khôi phục điểm cũ
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($has_access && $user_role !== 'Chủ nhiệm' && $decision): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock mr-2"></i> 
                                        <strong>Quyền hạn bị hạn chế:</strong> Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin biên bản nghiệm thu.
                                        <br><small class="text-muted">Vai trò của bạn: <strong><?php echo htmlspecialchars($user_role); ?></strong></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Đánh giá -->
                            <div class="tab-pane fade" id="evaluation" role="tabpanel" aria-labelledby="evaluation-tab">
                                <!-- Thông tin kết quả đánh giá -->
                                <?php if ($decision): ?>
                                    <div class="evaluation-result-section mb-4">
                                        <h6 class="text-success mb-3">
                                            <i class="fas fa-award mr-2"></i>Kết quả đánh giá nghiệm thu
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card border-success">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-success">
                                                            <i class="fas fa-calendar-check mr-2"></i>Ngày nghiệm thu
                                                        </h6>
                                                        <p class="card-text h5">
                                                            <?php echo isset($decision['BB_NGAYNGHIEMTHU']) ? formatDate($decision['BB_NGAYNGHIEMTHU']) : 'Chưa xác định'; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-primary">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">
                                                            <i class="fas fa-star mr-2"></i>Xếp loại
                                                        </h6>
                                                        <p class="card-text h5">
                                                            <?php 
                                                            $xep_loai = $decision['BB_XEPLOAI'] ?? 'Chưa xác định';
                                                            $badge_class = '';
                                                            switch ($xep_loai) {
                                                                case 'Xuất sắc':
                                                                    $badge_class = 'badge-success';
                                                                    break;
                                                                case 'Tốt':
                                                                    $badge_class = 'badge-primary';
                                                                    break;
                                                                case 'Khá':
                                                                    $badge_class = 'badge-info';
                                                                    break;
                                                                case 'Đạt':
                                                                    $badge_class = 'badge-warning';
                                                                    break;
                                                                case 'Không đạt':
                                                                    $badge_class = 'badge-danger';
                                                                    break;
                                                                default:
                                                                    $badge_class = 'badge-secondary';
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $badge_class; ?> p-2 font-size-sm">
                                                                <?php echo htmlspecialchars($xep_loai); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Thông tin quyết định -->
                                        <div class="card mt-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 text-info">
                                                    <i class="fas fa-file-contract mr-2"></i>Thông tin quyết định nghiệm thu
                                                    <button type="button" class="btn btn-sm btn-outline-info float-right" 
                                                        data-toggle="modal" data-target="#evaluationDetailModal">
                                                        <i class="fas fa-info-circle mr-1"></i>Chi tiết đánh giá
                                                    </button>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Số quyết định:</strong> <?php echo htmlspecialchars($decision['QD_SO'] ?? 'Chưa có'); ?></p>
                                                        <p><strong>Ngày ban hành:</strong> <?php echo isset($decision['QD_NGAYBANHANH']) ? formatDate($decision['QD_NGAYBANHANH']) : 'Chưa xác định'; ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Người ký:</strong> <?php echo htmlspecialchars($decision['QD_NGUOIKY'] ?? 'Chưa có'); ?></p>
                                                        <p><strong>Chức vụ:</strong> <?php echo htmlspecialchars($decision['QD_CHUCVU'] ?? 'Chưa có'); ?></p>
                                                    </div>
                                                </div>
                                                <?php if (isset($decision['QD_NOIDUNG']) && !empty($decision['QD_NOIDUNG'])): ?>
                                                    <hr>
                                                    <h6 class="text-primary">Nội dung quyết định:</h6>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($decision['QD_NOIDUNG'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    
                                    <!-- Trạng thái hoàn thành đề tài -->
                                    <div class="completion-status-section mb-4" id="completionStatusSection">
                                        <div class="card border-info">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 text-info">
                                                    <i class="fas fa-tasks mr-2"></i>Trạng thái hoàn thành đề tài
                                                    <button type="button" class="btn btn-sm btn-outline-info float-right" id="checkCompletionBtn">
                                                        <i class="fas fa-sync mr-1"></i>Kiểm tra
                                                    </button>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="completionDetails">
                                                    <div class="text-center py-3">
                                                        <i class="fas fa-spinner fa-spin mr-2"></i>Đang kiểm tra trạng thái...
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- File đánh giá -->
                                <?php if (count($evaluation_files) > 0): ?>
                                    <h6 class="text-primary mb-3"><i class="fas fa-file-alt mr-2"></i>Các file đánh giá</h6>
                                    <div class="evaluation-files-grid">
                                        <?php foreach ($evaluation_files as $index => $file): ?>
                                            <div class="evaluation-file-card animate-slide-up" style="animation-delay: <?php echo 0.1 * $index; ?>s">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="card-title mb-0">
                                                                <i class="far fa-file-pdf text-danger mr-2"></i>
                                                                <?php echo htmlspecialchars($file['FDG_TEN'] ?? 'Không có tên'); ?>
                                                            </h6>
                                                            <?php if (isset($file['FDG_DUONGDAN']) && $file['FDG_DUONGDAN']): ?>
                                                                <div class="btn-group">
                                                                    <a href="/NLNganh/uploads/evaluation_files/<?php echo htmlspecialchars($file['FDG_DUONGDAN']); ?>"
                                                                        class="btn btn-sm btn-outline-primary" target="_blank" title="Xem file">
                                                                        <i class="fas fa-eye"></i> Xem
                                                                    </a>
                                                                    <a href="/NLNganh/uploads/evaluation_files/<?php echo htmlspecialchars($file['FDG_DUONGDAN']); ?>"
                                                                        class="btn btn-sm btn-outline-success" download title="Tải xuống">
                                                                        <i class="fas fa-download"></i> Tải
                                                                    </a>
                                                                    <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-evaluation-file" 
                                                                            data-file-id="<?php echo htmlspecialchars($file['FDG_MA']); ?>"
                                                                            data-file-name="<?php echo htmlspecialchars($file['FDG_TEN']); ?>"
                                                                            title="Xóa file">
                                                                            <i class="fas fa-trash"></i> Xóa
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar-alt mr-1"></i>
                                                            Ngày tạo: <?php echo isset($file['FDG_NGAYCAP']) ? formatDate($file['FDG_NGAYCAP']) : 'Chưa xác định'; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                        <hr>
                                        <div class="upload-section">
                                            <h6 class="mb-3"><i class="fas fa-upload mr-2"></i>Thêm file đánh giá mới</h6>
                                            <form action="upload_evaluation_file.php" method="post" enctype="multipart/form-data"
                                                class="evaluation-upload-form">
                                                <input type="hidden" name="project_id"
                                                    value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                                <input type="hidden" name="report_id"
                                                    value="<?php echo htmlspecialchars($decision['BB_SOBB']); ?>">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="evaluation_name">
                                                                <i class="fas fa-file-signature mr-1"></i> Tên file đánh giá <span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text" class="form-control" id="evaluation_name"
                                                                name="evaluation_name" placeholder="Nhập tên file đánh giá" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="evaluation_file">
                                                                <i class="fas fa-file mr-1"></i> File đánh giá <span class="text-danger">*</span>
                                                            </label>
                                                            <div class="custom-file">
                                                                <input type="file" class="custom-file-input" id="evaluation_file"
                                                                    name="evaluation_file" accept=".pdf,.doc,.docx,.txt" required>
                                                                <label class="custom-file-label" for="evaluation_file">Chọn file...</label>
                                                            </div>
                                                            <small class="form-text text-muted">
                                                                Chỉ chấp nhận file PDF, DOC, DOCX, TXT (tối đa 10MB)
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-plus-circle mr-1"></i> Thêm file đánh giá
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($project['DT_TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle mr-2"></i> 
                                            <strong>Đề tài đã hoàn thành:</strong> Không thể thêm file đánh giá mới khi đề tài ở trạng thái "Đã hoàn thành".
                                            <br><small class="text-muted">Tất cả tài liệu đã được nộp đầy đủ và được phê duyệt.</small>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif ($decision && $has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có file đánh giá. Bạn có thể tải lên file đánh giá mới.
                                    </div>
                                    <div class="upload-section">
                                        <form action="upload_evaluation_file.php" method="post" enctype="multipart/form-data"
                                            class="evaluation-upload-form">
                                            <input type="hidden" name="project_id"
                                                value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                            <input type="hidden" name="report_id"
                                                value="<?php echo htmlspecialchars($decision['BB_SOBB']); ?>">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="evaluation_name_new">
                                                            <i class="fas fa-file-signature mr-1"></i> Tên file đánh giá <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" class="form-control" id="evaluation_name_new"
                                                            name="evaluation_name" placeholder="Nhập tên file đánh giá" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="evaluation_file_new">
                                                            <i class="fas fa-file mr-1"></i> File đánh giá <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="evaluation_file_new"
                                                                name="evaluation_file" accept=".pdf,.doc,.docx,.txt" required>
                                                            <label class="custom-file-label" for="evaluation_file_new">Chọn file...</label>
                                                        </div>
                                                        <small class="form-text text-muted">
                                                            Chỉ chấp nhận file PDF, DOC, DOCX, TXT (tối đa 10MB)
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus-circle mr-1"></i> Thêm file đánh giá
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> 
                                        <?php if (!$decision): ?>
                                            Chưa có quyết định nghiệm thu cho đề tài này.
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-exclamation-circle"></i> Cần phải có quyết định nghiệm thu trước khi có thể thêm file đánh giá.
                                                </small>
                                            </div>
                                        <?php elseif ($project['DT_TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                            <strong>Đề tài đã hoàn thành:</strong> Không thể thêm file đánh giá mới.
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-check-circle"></i> Tất cả tài liệu đã được nộp đầy đủ và được phê duyệt.
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            Chưa có file đánh giá nào.
                                            <?php if (isset($eval_files_debug)): ?>
                                                <div class="mt-2">
                                                    <small class="text-info">
                                                        <i class="fas fa-bug"></i> Debug: <?php echo htmlspecialchars($eval_files_debug); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($eval_files_error)): ?>
                                                <div class="mt-2">
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Lỗi: <?php echo htmlspecialchars($eval_files_error); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($user_role !== 'Chủ nhiệm'): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle"></i> Chỉ chủ nhiệm đề tài mới có thể tải lên file đánh giá.
                                                    </small>
                                                </div>
                                            <?php elseif ($project['DT_TRANGTHAI'] !== 'Đang thực hiện'): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle"></i> Không thể tải file đánh giá với trạng thái hiện tại.
                                                        <br>Trạng thái: <strong><?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?></strong>
                                                        <br>Chỉ có thể tải file khi đề tài "Đang thực hiện".
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <!-- Form upload file đánh giá cho trường hợp có quyền -->
                                                <div class="mt-3">
                                                    <h6 class="mb-3"><i class="fas fa-upload mr-2"></i>Thêm file đánh giá mới</h6>
                                                    <form action="upload_evaluation_file.php" method="post" enctype="multipart/form-data"
                                                        class="evaluation-upload-form">
                                                        <input type="hidden" name="project_id"
                                                            value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                                        <input type="hidden" name="report_id"
                                                            value="<?php echo htmlspecialchars($decision['BB_SOBB']); ?>">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="evaluation_name_empty">
                                                                        <i class="fas fa-file-signature mr-1"></i> Tên file đánh giá <span class="text-danger">*</span>
                                                                    </label>
                                                                    <input type="text" class="form-control" id="evaluation_name_empty"
                                                                        name="evaluation_name" placeholder="Nhập tên file đánh giá" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="evaluation_file_empty">
                                                                        <i class="fas fa-file mr-1"></i> File đánh giá <span class="text-danger">*</span>
                                                                    </label>
                                                                    <div class="custom-file">
                                                                        <input type="file" class="custom-file-input" id="evaluation_file_empty"
                                                                            name="evaluation_file" accept=".pdf,.doc,.docx,.txt" required>
                                                                        <label class="custom-file-label" for="evaluation_file_empty">Chọn file...</label>
                                                                    </div>
                                                                    <small class="form-text text-muted">
                                                                        Chỉ chấp nhận file PDF, DOC, DOCX, TXT (tối đa 10MB)
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-plus-circle mr-1"></i> Thêm file đánh giá
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Danh sách thành viên hội đồng nghiệm thu -->
                                <?php if ($decision && count($council_members) > 0): ?>
                                    <hr>
                                    <div class="council-members-section mb-4">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-users mr-2"></i>Thành viên hội đồng nghiệm thu
                                            <span class="badge badge-primary ml-2"><?php echo count($council_members); ?> thành viên</span>
                                        </h6>
                                        
                                        <div class="row">
                                            <?php foreach ($council_members as $index => $member): ?>
                                                <div class="col-lg-6 col-md-12 mb-3">
                                                    <div class="card border-left-primary animate-slide-up" style="animation-delay: <?php echo 0.1 * $index; ?>s">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="member-info flex-grow-1">
                                                                    <h6 class="card-title mb-1 text-dark">
                                                                        <i class="fas fa-user-tie mr-2 text-primary"></i>
                                                                        <?php echo htmlspecialchars($member['GV_HOTEN']); ?>
                                                                    </h6>
                                                                    <div class="member-details">
                                                                        <p class="mb-1 text-muted">
                                                                            <strong>Vai trò:</strong> 
                                                                            <span class="badge <?php echo ($member['TV_VAITRO'] === 'Chủ tịch') ? 'badge-danger' : (($member['TV_VAITRO'] === 'Thư ký') ? 'badge-info' : 'badge-primary'); ?>">
                                                                                <?php echo htmlspecialchars($member['TV_VAITRO']); ?>
                                                                            </span>
                                                                        </p>
                                                                        <p class="mb-1 text-muted small">
                                                                            <i class="fas fa-id-card mr-1"></i>
                                                                            Mã GV: <?php echo htmlspecialchars($member['GV_MAGV']); ?>
                                                                        </p>
                                                                        
                                                                        <!-- Thông tin đánh giá -->
                                                                        <?php if (isset($member['TV_DIEM']) && $member['TV_DIEM'] > 0): ?>
                                                                            <div class="evaluation-info mt-2">
                                                                                <div class="row">
                                                                                    <div class="col-6">
                                                                                        <small class="text-muted">Điểm đánh giá:</small>
                                                                                        <div class="font-weight-bold text-primary">
                                                                                            <?php echo number_format($member['TV_DIEM'] ?? 0, 1); ?>/100
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <small class="text-muted">Trạng thái:</small>
                                                                                        <div>
                                                                                            <span class="badge badge-success badge-sm">Đã đánh giá</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <?php if (!empty($member['TV_NGAYDANHGIA'])): ?>
                                                                                    <small class="text-muted d-block mt-1">
                                                                                        <i class="fas fa-calendar mr-1"></i>
                                                                                        Đánh giá: <?php echo date('d/m/Y H:i', strtotime($member['TV_NGAYDANHGIA'])); ?>
                                                                                    </small>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="evaluation-info mt-2">
                                                                                <span class="badge badge-warning badge-sm">Chưa đánh giá</span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Nút đánh giá -->
                                                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                                                    <div class="member-actions ml-2">
                                                                        <button type="button" class="btn btn-sm btn-outline-primary evaluate-member-btn" 
                                                                                data-member-id="<?php echo htmlspecialchars($member['GV_MAGV']); ?>"
                                                                                data-member-name="<?php echo htmlspecialchars($member['GV_HOTEN']); ?>"
                                                                                data-member-role="<?php echo htmlspecialchars($member['TV_VAITRO']); ?>"
                                                                                title="Đánh giá thành viên">
                                                                            <i class="fas fa-star"></i>
                                                                        </button>
                                                                        
                                                                        <!-- Hiển thị số file đánh giá nếu có -->
                                                                        <?php 
                                                                        $file_count_sql = "SELECT COUNT(*) as file_count FROM member_evaluation_files 
                                                                                          WHERE QD_SO = ? AND GV_MAGV = ? AND MEF_STATUS = 'Active'";
                                                                        $file_stmt = $conn->prepare($file_count_sql);
                                                                        $file_stmt->bind_param("ss", $decision['QD_SO'], $member['GV_MAGV']);
                                                                        $file_stmt->execute();
                                                                        $file_count = $file_stmt->get_result()->fetch_assoc()['file_count'];
                                                                        ?>
                                                                        <?php if ($file_count > 0): ?>
                                                                            <small class="text-muted d-block mt-1">
                                                                                <i class="fas fa-file mr-1"></i><?php echo $file_count; ?> file
                                                                            </small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <!-- Thống kê điểm -->
                                        <?php 
                                        $scored_members = array_filter($council_members, function($member) {
                                            return !empty($member['TV_DIEM']) && is_numeric($member['TV_DIEM']) && 
                                                   $member['TV_DIEM'] >= 0 && $member['TV_DIEM'] <= 100;
                                        });
                                        
                                        if (count($scored_members) > 0):
                                            // Tính điểm trung bình ban đầu
                                            $total_score = array_sum(array_column($scored_members, 'TV_DIEM'));
                                            $initial_average = $total_score / count($scored_members);
                                            
                                            // Lọc điểm hợp lệ (không chênh lệch quá 15 điểm so với trung bình ban đầu)
                                            // và đảm bảo điểm nằm trong khoảng hợp lý
                                            $valid_members = array_filter($scored_members, function($member) use ($initial_average) {
                                                $score = floatval($member['TV_DIEM']);
                                                return $score >= 0 && $score <= 100 && abs($score - $initial_average) <= 15;
                                            });
                                            
                                            $invalid_members = array_filter($scored_members, function($member) use ($initial_average) {
                                                $score = floatval($member['TV_DIEM']);
                                                return $score < 0 || $score > 100 || abs($score - $initial_average) > 15;
                                            });
                                            
                                            // Tính điểm trung bình cuối cùng từ các điểm hợp lệ
                                            $final_average = count($valid_members) > 0 ? 
                                                array_sum(array_column($valid_members, 'TV_DIEM')) / count($valid_members) : 0;
                                                
                                            // Xếp loại theo điểm trung bình cuối cùng
                                            $classification = '';
                                            $classification_class = 'text-secondary';
                                            if ($final_average >= 90) {
                                                $classification = 'Xuất sắc';
                                                $classification_class = 'text-success';
                                            } elseif ($final_average >= 80) {
                                                $classification = 'Tốt';
                                                $classification_class = 'text-primary';
                                            } elseif ($final_average >= 70) {
                                                $classification = 'Khá';
                                                $classification_class = 'text-info';
                                            } elseif ($final_average >= 50) {
                                                $classification = 'Đạt';
                                                $classification_class = 'text-warning';
                                            } else {
                                                $classification = 'Không đạt';
                                                $classification_class = 'text-danger';
                                            }
                                        ?>
                                            <div class="card bg-light border-0 mt-3">
                                                <div class="card-body">
                                                    <h6 class="text-info mb-3">
                                                        <i class="fas fa-chart-bar mr-2"></i>Thống kê điểm đánh giá
                                                    </h6>
                                                    <div class="row text-center">
                                                        <div class="col-md-2">
                                                            <div class="stat-item">
                                                                <h5 class="text-primary mb-0"><?php echo count($council_members); ?></h5>
                                                                <small class="text-muted">Tổng thành viên</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="stat-item">
                                                                <h5 class="text-success mb-0"><?php echo count($scored_members); ?></h5>
                                                                <small class="text-muted">Đã chấm điểm</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="stat-item">
                                                                <h5 class="text-warning mb-0"><?php echo count($council_members) - count($scored_members); ?></h5>
                                                                <small class="text-muted">Chưa chấm điểm</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="stat-item">
                                                                <h5 class="text-info mb-0"><?php echo count($valid_members); ?></h5>
                                                                <small class="text-muted">Điểm hợp lệ</small>
                                                            </div>
                                                        </div>
                                                        <?php if (count($invalid_members) > 0): ?>
                                                        <div class="col-md-2">
                                                            <div class="stat-item">
                                                                <h5 class="text-danger mb-0"><?php echo count($invalid_members); ?></h5>
                                                                <small class="text-muted">Điểm không hợp lệ</small>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="col-md-2">
                                                            <div class="stat-item">
                                                                <h5 class="<?php echo $classification_class; ?> mb-0"><?php echo number_format($final_average, 1); ?></h5>
                                                                <small class="text-muted">Điểm cuối cùng</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Hiển thị xếp loại -->
                                                    <div class="row mt-3">
                                                        <div class="col-12 text-center">
                                                            <div class="final-classification p-3 rounded" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                                                <h5 class="mb-2">
                                                                    <i class="fas fa-award mr-2"></i>Xếp loại đề tài
                                                                </h5>
                                                                <h3 class="<?php echo $classification_class; ?> mb-0">
                                                                    <?php echo $classification; ?>
                                                                </h3>
                                                                <small class="text-muted">
                                                                    Dựa trên điểm trung bình từ <?php echo count($valid_members); ?> điểm hợp lệ
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Cảnh báo về điểm không hợp lệ -->
                                                    <?php if (count($invalid_members) > 0): ?>
                                                    <div class="alert alert-warning mt-3">
                                                        <h6 class="alert-heading">
                                                            <i class="fas fa-exclamation-triangle mr-2"></i>Lưu ý về điểm không hợp lệ
                                                        </h6>
                                                        <p class="mb-2">
                                                            Có <?php echo count($invalid_members); ?> điểm bị loại do chênh lệch >15 điểm so với điểm trung bình ban đầu (<?php echo number_format($initial_average, 1); ?> điểm):
                                                        </p>
                                                        <ul class="mb-0">
                                                            <?php foreach ($invalid_members as $member): ?>
                                                            <li>
                                                                <strong><?php echo htmlspecialchars($member['GV_HOTEN']); ?></strong>: 
                                                                <?php echo number_format((float)$member['TV_DIEM'], 1); ?> điểm 
                                                                (chênh lệch: <?php echo number_format((float)abs($member['TV_DIEM'] - $initial_average), 1); ?> điểm)
                                                            </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($decision && count($council_members) === 0): ?>
                                    <hr>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <strong>Chưa có thông tin hội đồng nghiệm thu</strong>
                                        <p class="mb-0 mt-2">Danh sách thành viên hội đồng nghiệm thu chưa được cập nhật cho quyết định số <strong><?php echo htmlspecialchars($decision['QD_SO']); ?></strong>.</p>
                                        <?php if ($user_role === 'Chủ nhiệm'): ?>
                                            <p class="mb-0 mt-1"><small class="text-muted">Vui lòng cập nhật thông tin biên bản nghiệm thu trong tab "Biên bản nghiệm thu" để thêm thành viên hội đồng.</small></p>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($council_error)): ?>
                                            <div class="mt-2">
                                                <small class="text-danger">Lỗi kỹ thuật: <?php echo htmlspecialchars($council_error); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($decision['HD_THANHVIEN'])): ?>
                                            <div class="mt-3">
                                                <h6 class="text-info">Thông tin từ biên bản:</h6>
                                                <div class="p-2 bg-light rounded">
                                                    <?php echo nl2br(htmlspecialchars($decision['HD_THANHVIEN'])); ?>
                                                </div>
                                                <small class="text-muted">Dữ liệu này cần được đồng bộ vào bảng thành viên hội đồng.</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div> <!-- End evaluation tab -->
                        </div> <!-- End tab-content -->
                    </div> <!-- End card-body -->
                </div> <!-- End card -->
            </div> <!-- End col-lg-4 -->
        </div> <!-- End row -->
    </div> <!-- End container-fluid content -->

    <!-- Modal Cập nhật tiến độ -->
    <?php if ($has_access && $user_role === 'Chủ nhiệm' && $project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
        <div class="modal fade" id="addProgressModal" tabindex="-1" role="dialog" aria-labelledby="addProgressModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addProgressModalLabel">
                            <i class="fas fa-tasks mr-2"></i>Cập nhật tiến độ đề tài
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="update_project_progress.php" method="post" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                            
                            <div class="form-group">
                                <label for="progress_title">
                                    <i class="fas fa-heading mr-1"></i> Tiêu đề <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="progress_title" name="progress_title" 
                                    placeholder="Nhập tiêu đề cập nhật tiến độ" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="progress_content">
                                    <i class="fas fa-align-left mr-1"></i> Nội dung <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="progress_content" name="progress_content" 
                                    rows="5" placeholder="Mô tả chi tiết về tiến độ đề tài" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="progress_file">
                                    <i class="fas fa-paperclip mr-1"></i> File đính kèm (nếu có)
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="progress_file" name="progress_file">
                                    <label class="custom-file-label" for="progress_file">Chọn file...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Các định dạng hỗ trợ: PDF, Word, Excel, PowerPoint, ZIP, RAR, JPG, JPEG, PNG. Kích thước tối đa: 10MB.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i> Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Lưu cập nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Close tab-content and card -->
                        </div> <!-- End tab-content -->
                    </div> <!-- End card-body -->
                </div> <!-- End card -->
            </div> <!-- End col-lg-4 -->
        </div> <!-- End row -->
    </div> <!-- End container-fluid content -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Unified Tab System -->
    <script src="/NLNganh/assets/js/student/unified_tab_system.js"></script>

    <!-- Main Application Scripts -->
    <script>
    <script>
        $(document).ready(function() {
            console.log('=== Main Application Scripts ===');
            console.log('Project Status:', '<?php echo $project['DT_TRANGTHAI']; ?>');
            
            // Wait for unified tab system to initialize
            var checkTabSystem = setInterval(function() {
                if (window.unifiedTabSystem && window.unifiedTabSystem.isInitialized()) {
                    console.log('✓ Tab system ready');
                    clearInterval(checkTabSystem);
                    initializeApplicationFeatures();
                }
            }, 100);
            
            function initializeApplicationFeatures() {
                // Auto-resize textarea
                $('textarea').on('input', function() {
                    try {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    } catch (e) {
                        console.warn('Textarea resize error:', e);
                    }
                });

                // Format budget input
                $('#total_budget').on('input', function() {
                    try {
                        let value = $(this).val().replace(/[^\d]/g, '');
                        $(this).val(value);
                    } catch (e) {
                        console.warn('Budget format error:', e);
                    }
                });

                // Date validation
                $('#start_date, #end_date').on('change', function() {
                    try {
                        const startDate = $('#start_date').val();
                        const endDate = $('#end_date').val();
                        
                        if (startDate && endDate && new Date(startDate) >= new Date(endDate)) {
                            alert('Ngày kết thúc phải sau ngày bắt đầu.');
                            if ($(this).attr('id') === 'end_date') {
                                $(this).val('');
                            }
                        }
                    } catch (e) {
                        console.warn('Date validation error:', e);
                    }
                });

                // Custom file input labels
                $('.custom-file-input').on('change', function() {
                    try {
                        const fileName = $(this).val().split('\\').pop();
                        $(this).siblings('.custom-file-label').addClass('selected').html(fileName || 'Chọn file...');
                    } catch (e) {
                        console.warn('File input label error:', e);
                    }
                });

                // Print button
                $('#printProjectBtn').on('click', function() {
                    try {
                        window.print();
                    } catch (e) {
                        console.warn('Print error:', e);
                    }
                });
                
                console.log('✓ Application features initialized');
            }
        });
    </script>

    <!-- Form Validation and Features -->
    <script>
        $(document).ready(function() {
            // Form validation handlers
            function showValidationError(message, selector) {
                alert(message);
                if (selector) {
                    $(selector).focus();
                }
            }

            function validateRequiredFields(fields) {
                for (let field of fields) {
                    const value = $(field.selector).val();
                    if (!value || (typeof value === 'string' && !value.trim())) {
                        showValidationError(field.message, field.selector);
                        return false;
                    }
                }
                return true;
            }

            function showLoading() {
                $('#loadingOverlay').show();
            }

            // Debounce function
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = function() {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Form submission handlers
            $('form[action="/NLNganh/view/student/update_proposal_file.php"]').on('submit', function(e) {
                e.preventDefault();
                
                const updateReason = $('#proposal_update_reason').val().trim();
                const fileInput = $('#proposal_file')[0];
                
                if (!updateReason) {
                    showValidationError('Vui lòng nhập lý do cập nhật.', '#proposal_update_reason');
                    return false;
                }
                
                if (!fileInput.files.length) {
                    alert('Vui lòng chọn file thuyết minh.');
                    return false;
                }
                
                const confirmation = confirm(
                    'Bạn có chắc chắn muốn cập nhật file thuyết minh?\n\n' +
                    'Thao tác này sẽ:\n' +
                    '- Thay thế file thuyết minh hiện tại\n' +
                    '- Ghi lại vào tiến độ đề tài\n' +
                    '- Không thể hoàn tác\n\n' +
                    'Lý do: ' + updateReason
                );
                
                if (confirmation) {
                    showLoading();
                    this.submit();
                }
            });

            // Contract form validation
            $('form[action="/NLNganh/view/student/update_contract_info.php"]').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const contractCode = form.find('#contract_code').val().trim();
                const contractDate = form.find('#contract_date').val();
                const startDate = form.find('#start_date').val();
                const endDate = form.find('#end_date').val();
                const totalBudget = form.find('#total_budget').val();
                const updateReason = form.find('#contract_update_reason').val().trim();
                const isUpdate = form.find('input[name="contract_id"]').length > 0;
                
                const requiredFields = [
                    { selector: '#contract_code', message: 'Vui lòng nhập mã hợp đồng.' },
                    { selector: '#contract_date', message: 'Vui lòng chọn ngày tạo hợp đồng.' },
                    { selector: '#start_date', message: 'Vui lòng chọn ngày bắt đầu.' },
                    { selector: '#end_date', message: 'Vui lòng chọn ngày kết thúc.' },
                    { selector: '#total_budget', message: 'Vui lòng nhập tổng kinh phí.' },
                    { selector: '#contract_update_reason', message: 'Vui lòng nhập lý do cập nhật.' }
                ];
                
                if (!validateRequiredFields(requiredFields)) {
                    return false;
                }
                
                if (new Date(startDate) >= new Date(endDate)) {
                    alert('Ngày kết thúc phải sau ngày bắt đầu.');
                    $('#end_date').focus();
                    return false;
                }
                
                if (parseFloat(totalBudget) <= 0) {
                    alert('Tổng kinh phí phải lớn hơn 0.');
                    $('#total_budget').focus();
                    return false;
                }
                
                const fileInput = $('#contract_file')[0];
                if (!isUpdate && !fileInput.files.length) {
                    alert('Vui lòng chọn file hợp đồng.');
                    return false;
                }
                
                const actionText = isUpdate ? 'cập nhật' : 'tạo mới';
                const confirmation = confirm(
                    `Bạn có chắc chắn muốn ${actionText} thông tin hợp đồng?\n\n` +
                    'Thông tin hợp đồng:\n' +
                    `- Mã hợp đồng: ${contractCode}\n` +
                    `- Thời gian: ${startDate} đến ${endDate}\n` +
                    `- Kinh phí: ${parseInt(totalBudget).toLocaleString('vi-VN')} VNĐ\n\n` +
                    'Thao tác này sẽ được ghi lại vào tiến độ đề tài.\n' +
                    `Lý do: ${updateReason}`
                );
                
                if (confirmation) {
                    showLoading();
                    this.submit();
                }
            });

            // Decision form validation  
            $('form[action="/NLNganh/view/student/update_decision_info.php"]').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const decisionNumber = form.find('#decision_number').val().trim();
                const decisionDate = form.find('#decision_date').val();
                const updateReason = form.find('#decision_update_reason').val().trim();
                const isUpdate = form.find('input[name="decision_id"]').length > 0;
                
                const requiredFields = [
                    { selector: '#decision_number', message: 'Vui lòng nhập số quyết định.' },
                    { selector: '#decision_date', message: 'Vui lòng chọn ngày quyết định.' },
                    { selector: '#decision_update_reason', message: 'Vui lòng nhập lý do cập nhật.' }
                ];
                
                if (!validateRequiredFields(requiredFields)) {
                    return false;
                }
                
                const fileInput = $('#decision_file')[0];
                if (!isUpdate && !fileInput.files.length) {
                    alert('Vui lòng chọn file quyết định.');
                    return false;
                }
                
                const actionText = isUpdate ? 'cập nhật' : 'tạo';
                const confirmation = confirm(
                    `Xác nhận ${actionText} thông tin quyết định nghiệm thu?\n\n` +
                    `Chi tiết:\n` +
                    `- Số quyết định: ${decisionNumber}\n` +
                    `- Ngày ra quyết định: ${decisionDate}\n\n` +
                    'Thao tác này sẽ được ghi lại vào tiến độ đề tài.\n' +
                    `Lý do: ${updateReason}`
                );
                
                if (confirmation) {
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html(`<i class="fas fa-spinner fa-spin mr-1"></i> Đang ${actionText}...`);
                    
                    this.submit();
                }
            });

            // Progress form validation
            $('form[action="update_project_progress.php"]').on('submit', function(e) {
                const progressTitle = $('#progress_title').val().trim();
                const progressContent = $('#progress_content').val().trim();

                const requiredFields = [
                    {
                        selector: '#progress_title',
                        message: 'Vui lòng nhập tiêu đề cập nhật tiến độ.'
                    },
                    {
                        selector: '#progress_content', 
                        message: 'Vui lòng nhập nội dung cập nhật tiến độ.'
                    }
                ];

                if (!validateRequiredFields(requiredFields)) {
                    e.preventDefault();
                    return false;
                }

                if (progressContent.length < 10) {
                    e.preventDefault();
                    showValidationError('Nội dung cập nhật phải có ít nhất 10 ký tự.', '#progress_content');
                    return false;
                }

                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang cập nhật...');
            });

            // Clear validation errors when user starts typing
            $(document).on('input', '.form-control', debounce(function() {
                $(this).removeClass('is-invalid');
            }, 300));

            console.log('✓ Form validation and features loaded');
        });
    </script>

</body>
</html>
                            
                            console.log('✓ Smart fallback completed to tab:', bestTab);
                        }
                    } else {
                        console.log('→ No saved tab found, activating first available tab');
                        
                        // Remove active from all
                        $('#documentTabs .nav-link').removeClass('active');
                        $('.tab-pane').removeClass('show active').hide();
                        
                        // Smart first tab activation - find first available instead of hard-coding proposal
                        console.log('→ Applying smart first tab activation...');
                        $('.tab-pane').css('display', '');
                        
                        // Smart tab selection: find the first tab with content, then first available
                        var bestTab = findBestAvailableTab();
                        console.log('→ Best available tab selected:', bestTab);
                        
                        // Activate best available tab
                        $('#documentTabs .nav-link').removeClass('active');
                        $('.tab-pane').removeClass('show active').hide();
                        $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                        $('#' + bestTab).addClass('show active').show().css({
                            'display': 'block',
                            'visibility': 'visible',
                            'opacity': '1'
                        });
                        
                        // Force layout recalculation
                        $('#' + bestTab)[0].offsetHeight;
                        
                        console.log('✓ Smart first tab activation completed:', bestTab);
                    }
                    
                    console.log('=== TAB INITIALIZATION COMPLETED ===');
                    // Run debug check after initialization
                    setTimeout(function() {
                        window.debugTabState();
                    }, 100);
                }, 100);
                
                // Handle browser back/forward with error protection
                window.addEventListener('popstate', function(event) {
                    try {
                        var urlParams = new URLSearchParams(window.location.search);
                        var urlTab = urlParams.get('tab');
                        if (urlTab && $('#' + urlTab).length) {
                            console.log('Popstate - activating tab:', urlTab);
                            
                            // Remove active from all
                            $('#documentTabs .nav-link').removeClass('active');
                            $('.tab-pane').removeClass('show active').hide();
                            
                            // Activate URL tab
                            $('#documentTabs a[href="#' + urlTab + '"]').addClass('active');
                            $('#' + urlTab).addClass('show active').show();
                        } else {
                            console.log('Popstate - activating best available tab');
                            
                            // Remove active from all
                            $('#documentTabs .nav-link').removeClass('active');
                            $('.tab-pane').removeClass('show active').hide();
                            
                            // Activate best available tab dynamically
                            var bestTab = findBestAvailableTab();
                            console.log('Popstate using best tab:', bestTab);
                            
                            $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                            $('#' + bestTab).addClass('show active').show();
                        }
                    } catch (popstateError) {
                        console.error('Popstate handler error:', popstateError);
                        // Fallback: activate best available tab
                        try {
                            $('#documentTabs .nav-link').removeClass('active');
                            $('.tab-pane').removeClass('show active').hide();
                            
                            var bestTab = findBestAvailableTab();
                            console.log('Popstate fallback using best tab:', bestTab);
                            
                            $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                            $('#' + bestTab).addClass('show active').show();
                        } catch (popstateFallbackError) {
                            console.error('Smart fallback failed:', popstateFallbackError);
                        }
                    }
                });
            } else {
                console.error('Bootstrap tabs are not available!');
                
                // Fallback: Manual tab switching without Bootstrap with error protection
                $('#documentTabs a[data-toggle="tab"]').on('click', function (e) {
                    try {
                        e.preventDefault();
                        var targetTab = $(this).attr('href');
                        console.log('Fallback tab click:', targetTab);
                        
                        // Manual tab switching
                        $('#documentTabs .nav-link').removeClass('active');
                        $('.tab-pane').removeClass('show active').hide();
                        
                        $(this).addClass('active');
                        $(targetTab).addClass('show active').show();
                        
                        // Update URL with error protection
                        try {
                            if (history.pushState) {
                                var tabName = targetTab.replace('#', '');
                                var newUrl = window.location.pathname + '?tab=' + tabName;
                                history.pushState(null, null, newUrl);
                            }
                        } catch (historyError) {
                            console.log('Fallback history API error (ignored):', historyError);
                        }
                    } catch (fallbackError) {
                        console.error('Fallback tab switching error:', fallbackError);
                        // Last resort: simple show/hide
                        try {
                            var targetTab = $(this).attr('href');
                            $('.tab-pane').hide();
                            $(targetTab).show();
                        } catch (lastResortError) {
                            console.error('Last resort tab switching failed:', lastResortError);
                        }
                    }
                });
                
                // Force activate first available tab in fallback mode
                setTimeout(function() {
                    if ($('.tab-pane.show.active:visible').length === 0) {
                        console.log('Fallback mode: Activating best available tab');
                        $('#documentTabs .nav-link').removeClass('active');
                        $('.tab-pane').removeClass('show active').hide();
                        
                        var bestTab = findBestAvailableTab();
                        console.log('Fallback mode using best tab:', bestTab);
                        
                        $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                        $('#' + bestTab).addClass('show active').show();
                        console.log('Fallback mode activated tab:', bestTab);
                    }
                }, 200);
            }
            
            // Global tab recovery function that can be called anytime with error protection
            window.recoverTabs = function() {
                try {
                    console.log('=== RECOVERING TABS ===');
                    
                    // Check project completion status for special handling
                    var projectStatus = '<?php echo $project['DT_TRANGTHAI']; ?>';
                    var isCompletedProject = projectStatus === 'Đã hoàn thành';
                    console.log('Recovery - Project status:', projectStatus, 'Is completed:', isCompletedProject);
                    
                    // Check if any tab is active
                    var hasActiveTab = $('.tab-pane.show.active:visible').length > 0;
                    
                    if (!hasActiveTab) {
                        console.log('No active tab found, recovering...');
                        
                        // Unified recovery handling for all project states
                        console.log('→ Applying unified recovery handling...');
                        $('.tab-pane').css('display', '').removeAttr('style');
                        $('#documentTabs .nav-link').css('display', '');
                        
                        // Remove any potential CSS interference
                        $('.tab-pane').each(function() {
                            var $this = $(this);
                            if ($this.attr('style') && $this.attr('style').includes('display: none')) {
                                $this.removeAttr('style');
                                console.log('→ Removed display:none from:', $this.attr('id'));
                            }
                        });
                        
                        // Try to restore from URL or localStorage
                        var urlParams = new URLSearchParams(window.location.search);
                        var urlTab = urlParams.get('tab');
                        var savedTab = localStorage.getItem('lastActiveTab') || sessionStorage.getItem('lastActiveTab');
                        var targetTab = urlTab || savedTab || 'proposal';
                        
                        if ($('#' + targetTab).length) {
                            console.log('Recovering to tab:', targetTab);
                            $('#documentTabs .nav-link').removeClass('active');
                            $('.tab-pane').removeClass('show active').hide();
                            $('#documentTabs a[href="#' + targetTab + '"]').addClass('active');
                            $('#' + targetTab).addClass('show active').show().css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '1'
                            });
                            
                            // Force layout recalculation
                            $('#' + targetTab)[0].offsetHeight;
                            
                        } else {
                            console.log('Target tab not found, using best available tab');
                            $('#documentTabs .nav-link').removeClass('active');
                            $('.tab-pane').removeClass('show active').hide();
                            
                            // Find best available tab instead of hard-coding proposal
                            var bestTab = findBestAvailableTab();
                            console.log('Recovery fallback to best available tab:', bestTab);
                            
                            $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                            $('#' + bestTab).addClass('show active').show().css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '1'
                            });
                            
                            // Force layout recalculation
                            $('#' + bestTab)[0].offsetHeight;
                        }
                        
                        // Note: Click handlers already installed via unified system
                        console.log('Tab recovery completed');
                    } else {
                        console.log('Tabs are working normally');
                    }
                    
                    try {
                        window.debugTabState();
                    } catch (debugError) {
                        console.log('Debug state error (ignored):', debugError);
                    }
                } catch (recoveryError) {
                    console.error('Tab recovery failed:', recoveryError);
                    // Last resort fallback
                    try {
                        var bestTab = findBestAvailableTab();
                        console.log('Last resort recovery with best tab:', bestTab);
                        
                        $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                        $('#' + bestTab).addClass('show active').show();
                        console.log('Last resort tab recovery completed with tab:', bestTab);
                    } catch (lastResortError) {
                        console.error('Last resort recovery failed:', lastResortError);
                    }
                }
            };
            
            // Custom file input display filename
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass('selected').html(fileName);
                
                // Validate file for evaluation uploads
                if ($(this).attr('name') === 'evaluation_file') {
                    validateEvaluationFile(this);
                }
            });

            // Validate evaluation file upload
            function validateEvaluationFile(input) {
                const file = input.files[0];
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (file) {
                    // Check file type
                    if (!allowedTypes.includes(file.type)) {
                        showAlert('error', 'Chỉ cho phép tải lên file PDF, DOC, DOCX, TXT');
                        $(input).val('').next('.custom-file-label').html('Chọn file...');
                        return false;
                    }
                    
                    // Check file size
                    if (file.size > maxSize) {
                        showAlert('error', 'Kích thước file không được vượt quá 10MB');
                        $(input).val('').next('.custom-file-label').html('Chọn file...');
                        return false;
                    }
                    
                    // Show file info
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    const label = $(input).next('.custom-file-label');
                    label.html(`${file.name} (${fileSize} MB)`);
                }
                
                return true;
            }

            // Show alert messages
            function showAlert(type, message) {
                const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
                const iconClass = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
                
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="${iconClass} mr-2"></i> ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `;
                
                $('.content').prepend(alertHtml);
                
                // Auto remove after 5 seconds
                setTimeout(function() {
                    $('.alert').first().alert('close');
                }, 5000);
            }

            // Evaluation file upload form validation
            $('form[action="upload_evaluation_file.php"]').on('submit', function(e) {
                const fileName = $(this).find('input[name="evaluation_name"]').val().trim();
                const fileInput = $(this).find('input[name="evaluation_file"]')[0];
                
                if (!fileName) {
                    e.preventDefault();
                    showAlert('error', 'Vui lòng nhập tên file đánh giá');
                    return false;
                }
                
                if (!fileInput.files || fileInput.files.length === 0) {
                    e.preventDefault();
                    showAlert('error', 'Vui lòng chọn file để tải lên');
                    return false;
                }
                
                if (!validateEvaluationFile(fileInput)) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải lên...').prop('disabled', true);
                
                // Reset button after a delay if form is still there (for error cases)
                setTimeout(function() {
                    if (submitBtn.length && submitBtn.prop('disabled')) {
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                }, 30000);
            });

            // Animate evaluation cards on load
            $('.evaluation-file-card').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
            });

            // Smooth scroll to evaluation tab
            $('#evaluation-tab').on('click', function() {
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $('#evaluation').offset().top - 100
                    }, 500);
                }, 150);
            });

            // Delete evaluation file
            $(document).on('click', '.delete-evaluation-file', function() {
                const fileId = $(this).data('file-id');
                const fileName = $(this).data('file-name');
                const projectId = '<?php echo htmlspecialchars($project['DT_MADT']); ?>';
                
                // Show confirmation dialog
                if (confirm(`Bạn có chắc chắn muốn xóa file đánh giá "${fileName}"?\n\nHành động này không thể hoàn tác.`)) {
                    // Show loading state
                    $(this).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                    
                    // Redirect to delete file
                    window.location.href = `delete_evaluation_file.php?project_id=${encodeURIComponent(projectId)}&file_id=${encodeURIComponent(fileId)}`;
                }
            });

            // Print functionality
            $('#printProjectBtn').on('click', function() {
                window.print();
            });

            // Confirmation for proposal file update
            $('form[action="/NLNganh/view/student/update_proposal_file.php"]').on('submit', function(e) {
                e.preventDefault();
                
                const updateReason = $('#proposal_update_reason').val().trim();
                const fileInput = $('#proposal_file')[0];
                
                if (!updateReason) {
                    showValidationError('Vui lòng nhập lý do cập nhật.', '#proposal_update_reason');
                    return false;
                }
                
                if (!fileInput.files.length) {
                    alert('Vui lòng chọn file thuyết minh.');
                    return false;
                }
                
                const confirmation = confirm(
                    'Bạn có chắc chắn muốn cập nhật file thuyết minh?\n\n' +
                    'Thao tác này sẽ:\n' +
                    '- Thay thế file thuyết minh hiện tại\n' +
                    '- Ghi lại vào tiến độ đề tài\n' +
                    '- Không thể hoàn tác\n\n' +
                    'Lý do: ' + updateReason
                );
                
                if (confirmation) {
                    // Show loading overlay
                    showLoading();
                    
                    // Submit form
                    this.submit();
                }
            });

            // Confirmation for contract info update
            $('form[action="/NLNganh/view/student/update_contract_info.php"]').on('submit', function(e) {
                e.preventDefault();
                
                // Cache DOM elements for better performance within form context
                const form = $(this);
                const contractCode = form.find('#contract_code').val().trim();
                const contractDate = form.find('#contract_date').val();
                const startDate = form.find('#start_date').val();
                const endDate = form.find('#end_date').val();
                const totalBudget = form.find('#total_budget').val();
                const updateReason = form.find('#contract_update_reason').val().trim();
                const isUpdate = form.find('input[name="contract_id"]').length > 0;
                
                // Validate required fields using helper function with context-specific selectors
                const requiredFields = [
                    { selector: '#contract_code', message: 'Vui lòng nhập mã hợp đồng.' },
                    { selector: '#contract_date', message: 'Vui lòng chọn ngày tạo hợp đồng.' },
                    { selector: '#start_date', message: 'Vui lòng chọn ngày bắt đầu.' },
                    { selector: '#end_date', message: 'Vui lòng chọn ngày kết thúc.' },
                    { selector: '#total_budget', message: 'Vui lòng nhập tổng kinh phí.' },
                    { selector: '#contract_update_reason', message: 'Vui lòng nhập lý do cập nhật.' }
                ];
                
                // Validate within form context
                for (let field of requiredFields) {
                    const value = form.find(field.selector).val();
                    if (!value || (typeof value === 'string' && !value.trim())) {
                        showValidationError(field.message, field.selector);
                        return false;
                    }
                }
                
                if (new Date(startDate) >= new Date(endDate)) {
                    alert('Ngày kết thúc phải sau ngày bắt đầu.');
                    $('#end_date').focus();
                    return false;
                }
                
                if (parseFloat(totalBudget) <= 0) {
                    alert('Tổng kinh phí phải lớn hơn 0.');
                    $('#total_budget').focus();
                    return false;
                }
                
                const fileInput = $('#contract_file')[0];
                if (!isUpdate && !fileInput.files.length) {
                    alert('Vui lòng chọn file hợp đồng.');
                    return false;
                }
                
                const actionText = isUpdate ? 'cập nhật' : 'tạo mới';
                const confirmation = confirm(
                    `Bạn có chắc chắn muốn ${actionText} thông tin hợp đồng?\n\n` +
                    'Thông tin hợp đồng:\n' +
                    `- Mã hợp đồng: ${contractCode}\n` +
                    `- Thời gian: ${startDate} đến ${endDate}\n` +
                    `- Kinh phí: ${parseInt(totalBudget).toLocaleString('vi-VN')} VNĐ\n\n` +
                    'Thao tác này sẽ được ghi lại vào tiến độ đề tài.\n' +
                    `Lý do: ${updateReason}`
                );
                
                if (confirmation) {
                    // Show loading overlay
                    showLoading();
                    
                    // Submit form
                    this.submit();
                }
            });

            // Validation cho form quyết định nghiệm thu
            $('form[action="/NLNganh/view/student/update_decision_info.php"]').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const decisionNumber = form.find('#decision_number').val().trim();
                const decisionDate = form.find('#decision_date').val();
                const updateReason = form.find('#decision_update_reason').val().trim();
                const isUpdate = form.find('input[name="decision_id"]').length > 0;
                
                // Validate required fields using helper function with context-specific selectors
                const requiredFields = [
                    { selector: '#decision_number', message: 'Vui lòng nhập số quyết định.' },
                    { selector: '#decision_date', message: 'Vui lòng chọn ngày quyết định.' },
                    { selector: '#decision_update_reason', message: 'Vui lòng nhập lý do cập nhật.' }
                ];
                
                // Validate within form context
                for (let field of requiredFields) {
                    const value = form.find(field.selector).val();
                    if (!value || (typeof value === 'string' && !value.trim())) {
                        showValidationError(field.message, field.selector);
                        return false;
                    }
                }
                
                // Kiểm tra file (chỉ khi tạo mới)
                const fileInput = $('#decision_file')[0];
                if (!isUpdate && !fileInput.files.length) {
                    alert('Vui lòng chọn file quyết định.');
                    return false;
                }
                
                // Hiển thị xác nhận
                const actionText = isUpdate ? 'cập nhật' : 'tạo';
                const confirmation = confirm(
                    `Xác nhận ${actionText} thông tin quyết định nghiệm thu?\n\n` +
                    `Chi tiết:\n` +
                    `- Số quyết định: ${decisionNumber}\n` +
                    `- Ngày ra quyết định: ${decisionDate}\n\n` +
                    'Thao tác này sẽ được ghi lại vào tiến độ đề tài.\n' +
                    `Lý do: ${updateReason}`
                );
                
                if (confirmation) {
                    // Show loading state
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html(`<i class="fas fa-spinner fa-spin mr-1"></i> Đang ${actionText}...`);
                    
                    // Submit form
                    this.submit();
                }
            });

            // Validation cho form thông tin biên bản cơ bản
            $('#reportBasicForm').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const acceptanceDate = form.find('#acceptance_date').val();
                const evaluationGrade = form.find('#evaluation_grade').val();
                const totalScore = form.find('#total_score').val();
                
                // Debug form data
                console.log('Report Basic Form submission data:', {
                    project_id: form.find('input[name="project_id"]').val(),
                    decision_id: form.find('input[name="decision_id"]').val(),
                    report_id: form.find('input[name="report_id"]').val(),
                    acceptance_date: acceptanceDate,
                    evaluation_grade: evaluationGrade,
                    total_score: totalScore
                });
                
                // Validate required fields using helper function with context-specific selectors
                const requiredFields = [
                    { selector: '#acceptance_date', message: 'Vui lòng chọn ngày nghiệm thu.' },
                    { selector: '#evaluation_grade', message: 'Vui lòng chọn xếp loại đánh giá.' }
                ];
                
                // Validate within form context
                for (let field of requiredFields) {
                    const value = form.find(field.selector).val();
                    if (!value || (typeof value === 'string' && !value.trim())) {
                        showValidationError(field.message, field.selector);
                        return false;
                    }
                }
                
                // Validate total score if provided
                if (totalScore && (parseFloat(totalScore) < 0 || parseFloat(totalScore) > 100)) {
                    alert('Tổng điểm đánh giá phải từ 0 đến 100.');
                    $('#total_score').focus();
                    return false;
                }
                
                // Hiển thị xác nhận với thông tin chi tiết hơn
                let confirmMessage = `Xác nhận cập nhật thông tin biên bản nghiệm thu?\n\n` +
                    `Chi tiết:\n` +
                    `- Ngày nghiệm thu: ${acceptanceDate}\n` +
                    `- Xếp loại: ${evaluationGrade}\n`;
                    
                if (totalScore) {
                    confirmMessage += `- Tổng điểm: ${totalScore}/100\n`;
                }
                
                confirmMessage += '\nThao tác này sẽ được ghi lại vào tiến độ đề tài.';
                
                const confirmation = confirm(confirmMessage);
                
                if (confirmation) {
                    // Save current tab state before submitting report basic form
                    const currentTab = $('#documentTabs .nav-link.active').attr('href');
                    if (currentTab) {
                        const tabName = currentTab.replace('#', '');
                        localStorage.setItem('lastActiveTab', tabName);
                        console.log('Saving current tab before report update:', tabName);
                    }
                    
                    // Show loading state
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang cập nhật...');
                    
                    // Submit form
                    this.submit();
                }
            });

            // Auto-resize textarea
            $('textarea').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Format budget input
            $('#total_budget').on('input', function() {
                let value = $(this).val().replace(/[^\d]/g, '');
                $(this).val(value);
            });

            // Date validation
            $('#start_date, #end_date').on('change', function() {
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                
                if (startDate && endDate && new Date(startDate) >= new Date(endDate)) {
                    alert('Ngày kết thúc phải sau ngày bắt đầu.');
                    if ($(this).attr('id') === 'end_date') {
                        $(this).val('');
                    }
                }
            });

            // Validation for progress update form
            $('form[action="update_project_progress.php"]').on('submit', function(e) {
                const progressTitle = $('#progress_title').val().trim();
                const progressContent = $('#progress_content').val().trim();

                // Validate required fields
                const requiredFields = [
                    {
                        selector: '#progress_title',
                        message: 'Vui lòng nhập tiêu đề cập nhật tiến độ.'
                    },
                    {
                        selector: '#progress_content', 
                        message: 'Vui lòng nhập nội dung cập nhật tiến độ.'
                    }
                ];

                if (!validateRequiredFields(requiredFields)) {
                    e.preventDefault();
                    return false;
                }

                // Additional validation for content length
                if (progressContent.length < 10) {
                    e.preventDefault();
                    showValidationError('Nội dung cập nhật phải có ít nhất 10 ký tự.', '#progress_content');
                    return false;
                }

                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang cập nhật...');
            });

            // Optimize form submission with loading states - use event delegation
            $(document).on('submit', 'form', function() {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"], input[type="submit"]');
                if (!submitBtn.prop('disabled')) {
                    submitBtn.prop('disabled', true);
                    // Re-enable after timeout to prevent stuck state
                    setTimeout(() => {
                        submitBtn.prop('disabled', false);
                    }, 5000);
                }
            });

            // Clear validation errors when user starts typing (with debouncing and delegation)
            $(document).on('input', '.form-control', debounce(function() {
                $(this).removeClass('is-invalid');
            }, 300));
            
            // Final tab state check after all initialization
            setTimeout(function() {
                console.log('=== FINAL TAB STATE CHECK ===');
                window.debugTabState();
                
                // Special check for completed projects
                var projectStatus = '<?php echo $project['DT_TRANGTHAI']; ?>';
                console.log('Project status:', projectStatus);
                
                // Check if this is a completed project
                var isCompletedProject = projectStatus === 'Đã hoàn thành';
                console.log('Is completed project:', isCompletedProject);
                
                // Ensure at least one tab is active and visible
                if ($('.tab-pane.show.active:visible').length === 0) {
                    console.warn('No active tab found! Force activating first tab.');
                    console.log('Project status:', projectStatus);
                    
                    // Unified force activation for all project states
                    console.log('→ Applying unified force activation');
                    
                    // Clear all states
                    $('#documentTabs .nav-link').removeClass('active').removeAttr('aria-selected');
                    $('.tab-pane').removeClass('show active').hide().removeAttr('style');
                    
                    // Force remove any CSS that might hide tabs
                    $('.tab-pane').css('display', '');
                    $('#documentTabs .nav-link').css('display', '');
                    
                    // Double-check for any inline styles that might interfere
                    $('.tab-pane').each(function() {
                        var $this = $(this);
                        if ($this.attr('style') && $this.attr('style').includes('display: none')) {
                            $this.removeAttr('style');
                            console.log('Removed display:none style from:', $this.attr('id'));
                        }
                    });
                    
                    $('#documentTabs .nav-link').removeClass('active');
                    $('.tab-pane').removeClass('show active').hide();
                    
                    // Smart tab activation - find best available tab
                    var bestTab = findBestAvailableTab();
                    console.log('Force activating best available tab:', bestTab);
                    
                    $('#documentTabs a[href="#' + bestTab + '"]').addClass('active').attr('aria-selected', 'true');
                    $('#' + bestTab).addClass('show active').show().css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                    
                    // Force layout recalculation
                    $('#' + bestTab)[0].offsetHeight;
                    
                    console.log('Forced activation of best available tab completed:', bestTab);
                    
                    // Additional verification for all projects
                    setTimeout(function() {
                        var isVisible = $('#proposal').is(':visible');
                        var hasActiveClass = $('#proposal').hasClass('active');
                        console.log('→ Post-activation check - Proposal visible:', isVisible, 'Active:', hasActiveClass);
                        
                        if (!isVisible || !hasActiveClass) {
                            console.warn('→ Tab activation failed, trying emergency fix...');
                            $('#proposal').show().addClass('show active').css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '1'
                            });
                            $('#documentTabs a:first').addClass('active').attr('aria-selected', 'true');
                            
                            // Force layout recalculation again
                            $('#proposal')[0].offsetHeight;
                            
                            console.log('→ Emergency fix applied');
                        }
                    }, 200);
                }
            }, 500);
                
                // Double check tab functionality after initialization
                setTimeout(function() {
                    console.log('=== TAB FUNCTIONALITY CHECK ===');
                    var activeTab = $('#documentTabs .nav-link.active');
                    var activePane = $('.tab-pane.show.active');
                    console.log('Active tab:', activeTab.attr('href'));
                    console.log('Active pane:', activePane.attr('id'));
                    console.log('Active pane visible:', activePane.is(':visible'));
                    
                    // Verify all tabs are clickable
                    var tabCount = $('#documentTabs .nav-link').length;
                    var clickableCount = $('#documentTabs .nav-link').filter(function() {
                        return $(this).attr('data-toggle') === 'tab';
                    }).length;
                    console.log('Total tabs:', tabCount, 'Clickable tabs:', clickableCount);
                    
                    if (clickableCount !== tabCount) {
                        console.warn('Some tabs may not be properly initialized!');
                        // Re-initialize tab functionality
                        $('#documentTabs a[data-toggle="tab"]').off('click').on('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            var targetTab = $(this).attr('href');
                            console.log('Re-initialized tab clicked:', targetTab);
                            
                            // Remove active from all tabs and content
                            $('#documentTabs .nav-link').removeClass('active');
                            $('.tab-pane').removeClass('show active').hide();
                            
                            // Add active to clicked tab
                            $(this).addClass('active');
                            
                            // Show target content
                            $(targetTab).addClass('show active').show();
                            
                            // Update URL
                            if (history.pushState) {
                                var tabName = targetTab.replace('#', '');
                                var newUrl = window.location.pathname + '?tab=' + tabName;
                                history.pushState(null, null, newUrl);
                            }
                            
                            console.log('Tab switched to:', targetTab);
                        });
                    }
                    console.log('=== TAB FUNCTIONALITY CHECK COMPLETED ===');
                }, 200);
            }, 500);
            
            // Set up a recovery mechanism that checks every 2 seconds with error protection
            setInterval(function() {
                try {
                    if ($('.tab-pane.show.active:visible').length === 0) {
                        console.warn('No active tab detected, running recovery...');
                        
                        // Unified handling for all projects in interval check
                        var projectStatus = '<?php echo $project['DT_TRANGTHAI']; ?>';
                        var isCompletedProject = projectStatus === 'Đã hoàn thành';
                        
                        console.log('→ Interval recovery for project:', projectStatus);
                        
                        // Unified recovery for all projects
                        $('.tab-pane').css('display', '').removeAttr('style');
                        $('#documentTabs .nav-link').css('display', '');
                        
                        // Force activate best available tab with unified styling
                        $('#documentTabs .nav-link').removeClass('active');
                        $('.tab-pane').removeClass('show active').hide();
                        
                        var bestTab = findBestAvailableTab();
                        console.log('Interval recovery activating best tab:', bestTab);
                        
                        $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                        $('#' + bestTab).addClass('show active').show().css({
                            'display': 'block',
                            'visibility': 'visible',
                            'opacity': '1'
                        });
                        
                        // Force layout recalculation
                        $('#' + bestTab)[0].offsetHeight;
                        
                        console.log('→ Unified recovery applied for all projects');
                    }
                } catch (intervalError) {
                    console.log('Interval check error (ignored):', intervalError);
                }
            }, 2000);
            
            } catch (readyError) {
                console.error('Document ready error:', readyError);
                // Emergency fallback: try to activate first tab
                setTimeout(function() {
                    try {
                        var bestTab = findBestAvailableTab();
                        console.log('Emergency using best available tab:', bestTab);
                        
                        $('#documentTabs a[href="#' + bestTab + '"]').addClass('active');
                        $('#' + bestTab).addClass('show active').show();
                        console.log('Emergency tab activation completed for tab:', bestTab);
                    } catch (emergencyError) {
                        console.error('Emergency tab activation failed:', emergencyError);
                    }
                }, 1000);
            }
        });
    </script>

    <!-- Modal Chọn Thành Viên Hội Đồng -->
    <div class="modal fade" id="councilMemberModal" tabindex="-1" role="dialog" aria-labelledby="councilMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="councilMemberModalLabel">
                        <i class="fas fa-users mr-2"></i>Chọn thành viên hội đồng nghiệm thu
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="searchTeacher">Tìm kiếm giảng viên:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="searchTeacher" placeholder="Nhập tên, chuyên môn...">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="$('#searchTeacher').val('').trigger('input')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="departmentFilter">Lọc theo khoa:</label>
                                        <div class="input-group">
                                            <select class="form-control" id="departmentFilter">
                                                <option value="">-- Tất cả khoa --</option>
                                            </select>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="clearFilters" title="Xóa tất cả bộ lọc">
                                                    <i class="fas fa-refresh"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="teacherSelect">Chọn giảng viên:</label>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted" id="teacherCount">Đang tải...</small>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Nhấp đúp để chọn nhanh
                                    </small>
                                </div>
                                <select class="form-control" id="teacherSelect" size="8">
                                    <option value="">Đang tải danh sách giảng viên...</option>
                                </select>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Nhấp đúp vào tên giảng viên để chọn nhanh
                                </small>
                            </div>
                            
                            <div id="currentSelection" class="alert alert-info" style="display: none;">
                                <strong>Đã chọn:</strong>
                                <div id="selectedInfo"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="memberRole">Vai trò trong hội đồng:</label>
                                <select class="form-control" id="memberRole" required>
                                    <option value="">-- Chọn vai trò --</option>
                                    <option value="Chủ tịch">Chủ tịch hội đồng</option>
                                    <option value="Phó chủ tịch">Phó chủ tịch hội đồng</option>
                                    <option value="Thành viên">Thành viên</option>
                                    <option value="Thư ký">Thư ký</option>
                                </select>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb mr-1"></i>Gợi ý:</h6>
                                <ul class="mb-0 small">
                                    <li>Nên có 1 Chủ tịch</li>
                                    <li>Có thể có 1 Phó chủ tịch</li>
                                    <li>Nên có 1 Thư ký</li>
                                    <li>Các thành viên khác</li>
                                </ul>
                            </div>
                            
                            <div class="text-center">
                                <div class="current-selection mb-3" id="currentSelection" style="display: none;">
                                    <strong>Đã chọn:</strong>
                                    <div id="selectedInfo" class="text-primary"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Hủy
                    </button>
                    <button type="button" class="btn btn-primary" id="addSelectedMember">
                        <i class="fas fa-plus mr-1"></i>Thêm thành viên
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Quản lý thành viên hội đồng
        let projectCouncilMembers = [];
        let projectTeachers = [];
        
        // Load danh sách giảng viên khi trang được tải
        $(document).ready(function() {
            loadTeachers();
            
            // Parse existing council members if any
            const existingMembers = $('#council_members').val();
            if (existingMembers) {
                parseExistingMembers(existingMembers);
            }
        });
        
        // Load danh sách giảng viên từ API
        function loadTeachers() {
            $.get('/NLNganh/api/get_teachers.php')
                .done(function(response) {
                    if (response.success) {
                        projectTeachers = response.data;
                        populateTeacherSelect(projectTeachers);
                        populateDepartmentFilter(projectTeachers);
                        updateTeacherCount(projectTeachers.length);
                        console.log('Loaded teachers:', projectTeachers.length);
                    } else {
                        console.error('Error loading teachers:', response.message);
                        // Thử API backup không cần session
                        $.get('/NLNganh/api/test_teachers_no_session.php')
                            .done(function(backupResponse) {
                                if (backupResponse.success) {
                                    projectTeachers = backupResponse.data;
                                    populateTeacherSelect(projectTeachers);
                                    populateDepartmentFilter(projectTeachers);
                                    updateTeacherCount(projectTeachers.length);
                                    console.log('Loaded teachers from backup API:', projectTeachers.length);
                                } else {
                                    $('#teacherSelect').html('<option value="">Lỗi tải danh sách giảng viên: ' + response.message + '</option>');
                                }
                            })
                            .fail(function() {
                                $('#teacherSelect').html('<option value="">Không thể tải danh sách giảng viên</option>');
                            });
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Failed to load teachers:', error);
                    console.error('Response:', xhr.responseText);
                    // Thử API backup
                    $.get('/NLNganh/api/test_teachers_no_session.php')
                        .done(function(backupResponse) {
                            if (backupResponse.success) {
                                projectTeachers = backupResponse.data;
                                populateTeacherSelect(projectTeachers);
                                populateDepartmentFilter(projectTeachers);
                                updateTeacherCount(projectTeachers.length);
                                console.log('Loaded teachers from backup API:', projectTeachers.length);
                            } else {
                                $('#teacherSelect').html('<option value="">Không thể tải danh sách giảng viên</option>');
                            }
                        })
                        .fail(function() {
                            $('#teacherSelect').html('<option value="">Không thể tải danh sách giảng viên</option>');
                        });
                });
        }
        
        // Hiển thị danh sách giảng viên trong select
        function populateTeacherSelect(teacherList) {
            const select = $('#teacherSelect');
            select.empty();
            
            if (teacherList.length === 0) {
                select.append('<option value="">Không có giảng viên nào</option>');
                updateTeacherCount(0);
                return;
            }
            
            teacherList.forEach(teacher => {
                // Hiển thị: Tên đầy đủ - Chuyên môn - Khoa
                let displayText = teacher.fullName;
                if (teacher.specialty) {
                    displayText += ` - ${teacher.specialty}`;
                }
                if (teacher.department) {
                    displayText += ` (${teacher.department})`;
                }
                
                const option = $(`<option value="${teacher.id}" 
                    data-name="${teacher.name}" 
                    data-fullname="${teacher.fullName}" 
                    data-department="${teacher.department}"
                    data-specialty="${teacher.specialty}"
                    data-email="${teacher.email}">
                    ${displayText}
                </option>`);
                select.append(option);
            });
            
            updateTeacherCount(teacherList.length);
        }
        
        // Populate department filter
        function populateDepartmentFilter(teacherList) {
            const select = $('#departmentFilter');
            select.find('option:not(:first)').remove(); // Giữ lại option "Tất cả khoa"
            
            // Lấy danh sách khoa unique
            const departments = [...new Set(teacherList.map(teacher => teacher.department))].sort();
            
            departments.forEach(department => {
                if (department && department !== 'Không xác định') {
                    select.append(`<option value="${department}">${department}</option>`);
                }
            });
        }
        
        // Cập nhật số lượng giảng viên hiển thị
        function updateTeacherCount(count) {
            $('#teacherCount').text(`Hiển thị ${count} giảng viên`);
        }
        
        // Tìm kiếm và lọc giảng viên
        $('#searchTeacher, #departmentFilter').on('input change', function() {
            filterTeachers();
        });
        
        // Clear all filters
        $('#clearFilters').click(function() {
            $('#searchTeacher').val('');
            $('#departmentFilter').val('');
            populateTeacherSelect(projectTeachers);
            $(this).blur(); // Remove focus from button
        });
        
        // Hàm lọc giảng viên
        function filterTeachers() {
            const searchQuery = $('#searchTeacher').val().toLowerCase();
            const selectedDepartment = $('#departmentFilter').val();
            
            let filtered = projectTeachers.filter(teacher => {
                // Lọc theo tìm kiếm
                const matchesSearch = !searchQuery || 
                    teacher.name.toLowerCase().includes(searchQuery) ||
                    teacher.fullName.toLowerCase().includes(searchQuery) ||
                    teacher.department.toLowerCase().includes(searchQuery) ||
                    (teacher.specialty && teacher.specialty.toLowerCase().includes(searchQuery)) ||
                    (teacher.email && teacher.email.toLowerCase().includes(searchQuery));
                
                // Lọc theo khoa
                const matchesDepartment = !selectedDepartment || teacher.department === selectedDepartment;
                
                return matchesSearch && matchesDepartment;
            });
            
            populateTeacherSelect(filtered);
        }
        
        // Mở modal chọn thành viên
        $('#addCouncilMemberBtn').click(function() {
            $('#councilMemberModal').modal('show');
            // Reset form
            $('#teacherSelect').val('');
            $('#memberRole').val('');
            $('#searchTeacher').val('');
            $('#departmentFilter').val('');
            $('#currentSelection').hide();
            // Hiển thị lại tất cả giảng viên
            populateTeacherSelect(projectTeachers);
        });
        
        // Hiển thị thông tin giảng viên được chọn
        $('#teacherSelect').on('change', function() {
            const selected = $(this).find('option:selected');
            if (selected.val()) {
                let infoHtml = `<div><strong>${selected.data('fullname')}</strong></div>`;
                if (selected.data('specialty')) {
                    infoHtml += `<small class="text-info">${selected.data('specialty')}</small><br>`;
                }
                infoHtml += `<small class="text-muted">${selected.data('department')}</small>`;
                if (selected.data('email')) {
                    infoHtml += `<br><small class="text-muted">${selected.data('email')}</small>`;
                }
                
                $('#selectedInfo').html(infoHtml);
                $('#currentSelection').show();
            } else {
                $('#currentSelection').hide();
            }
        });
        
        // Double click để chọn nhanh
        $('#teacherSelect').on('dblclick', function() {
            if ($(this).val() && $('#memberRole').val()) {
                $('#addSelectedMember').click();
            } else if ($(this).val()) {
                $('#memberRole').focus();
            }
        });
        
        // Enter để thêm nhanh
        $('#memberRole').on('keypress', function(e) {
            if (e.which === 13 && $('#teacherSelect').val() && $(this).val()) {
                $('#addSelectedMember').click();
            }
        });
        
        // Thêm thành viên được chọn
        $('#addSelectedMember').click(function() {
            const selectedTeacher = $('#teacherSelect option:selected');
            const role = $('#memberRole').val();
            
            if (!selectedTeacher.val() || !role) {
                alert('Vui lòng chọn giảng viên và vai trò.');
                return;
            }
            
            const teacherId = selectedTeacher.val();
            const teacherName = selectedTeacher.data('name');
            const teacherFullName = selectedTeacher.data('fullname');
            const department = selectedTeacher.data('department');
            
            // Kiểm tra xem giảng viên đã được thêm chưa
            const existingMember = projectCouncilMembers.find(member => member.id === teacherId);
            if (existingMember) {
                alert('Giảng viên này đã được thêm vào hội đồng.');
                return;
            }
            
            // Thêm thành viên mới
            const newMember = {
                id: teacherId,
                name: teacherName,
                fullName: teacherFullName,
                role: role,
                department: department
            };
            
            projectCouncilMembers.push(newMember);
            updateCouncilMembersDisplay();
            updateCouncilMembersInput();
            
            $('#councilMemberModal').modal('hide');
        });
        
        // Cập nhật hiển thị danh sách thành viên
        function updateCouncilMembersDisplay() {
            const container = $('#selectedCouncilMembers');
            container.empty();
            
            if (projectCouncilMembers.length === 0) {
                container.html(`
                    <div class="text-center py-4">
                        <i class="fas fa-users text-muted mb-2" style="font-size: 2em;"></i>
                        <div class="text-muted">Chưa có thành viên nào được chọn</div>
                        <small class="text-muted">Nhấn nút "Thêm thành viên hội đồng" để bắt đầu</small>
                    </div>
                `);
                return;
            }
            
            const membersList = $('<div class="council-members-list"></div>');
            
            // Sắp xếp thành viên theo thứ tự: Chủ tịch -> Phó chủ tịch -> Thành viên -> Thư ký
            const roleOrder = { 'Chủ tịch': 1, 'Phó chủ tịch': 2, 'Thành viên': 3, 'Thư ký': 4 };
            const sortedMembers = projectCouncilMembers.slice().sort((a, b) => {
                return (roleOrder[a.role] || 5) - (roleOrder[b.role] || 5);
            });
            
            sortedMembers.forEach((member, index) => {
                // Tìm index thực trong mảng gốc
                const originalIndex = projectCouncilMembers.findIndex(m => m.id === member.id);
                
                // Icon cho từng vai trò
                let roleIcon = 'fa-user';
                let badgeClass = 'badge-primary';
                switch(member.role) {
                    case 'Chủ tịch':
                        roleIcon = 'fa-crown';
                        badgeClass = 'badge-warning';
                        break;
                    case 'Phó chủ tịch':
                        roleIcon = 'fa-star';
                        badgeClass = 'badge-info';
                        break;
                    case 'Thư ký':
                        roleIcon = 'fa-pen';
                        badgeClass = 'badge-success';
                        break;
                    default:
                        roleIcon = 'fa-user';
                        badgeClass = 'badge-primary';
                }
                
                const memberCard = $(`
                    <div class="card mb-2">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <i class="fas ${roleIcon} text-primary mr-2"></i>
                                        <div>
                                            <strong>${member.fullName}</strong>
                                            <span class="badge ${badgeClass} ml-2">${member.role}</span>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-building mr-1"></i>${member.department}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCouncilMember(${originalIndex})" title="Xóa thành viên">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `);
                membersList.append(memberCard);
            });
            
            // Thêm summary
            const summary = $(`
                <div class="alert alert-light border mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle text-info mr-2"></i>
                        <span><strong>Tổng số thành viên:</strong> ${projectCouncilMembers.length}</span>
                    </div>
                </div>
            `);
            
            container.append(summary);
            container.append(membersList);
        }
        
        // Xóa thành viên
        function removeCouncilMember(index) {
            projectCouncilMembers.splice(index, 1);
            updateCouncilMembersDisplay();
            updateCouncilMembersInput();
        }
        
        // Cập nhật input ẩn
        function updateCouncilMembersInput() {
            const membersData = projectCouncilMembers.map(member => ({
                id: member.id,
                name: member.fullName, // Sử dụng tên đầy đủ
                role: member.role
            }));
            
            $('#council_members_json').val(JSON.stringify(membersData));
            
            // Cập nhật field council_members cũ để hiển thị
            const memberText = projectCouncilMembers.map(member => `${member.fullName} (${member.role})`).join('\n');
            $('#council_members').val(memberText);
        }
        
        // Parse thành viên hiện có từ chuỗi text
        function parseExistingMembers(membersText) {
            // Cố gắng parse format: "Tên (Vai trò)"
            const lines = membersText.split('\n').filter(line => line.trim());
            // Vì không có ID giảng viên trong dữ liệu cũ, chỉ hiển thị text
            if (lines.length > 0) {
                $('#selectedCouncilMembers').html(`
                    <div class="alert alert-info">
                        <strong>Thành viên hiện tại:</strong><br>
                        ${lines.map(line => `<div>${line}</div>`).join('')}
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Dữ liệu hiện tại sẽ được thay thế khi bạn chọn thành viên mới từ danh sách.
                        </small>
                    </div>
                `);
            }
        }
    </script>

    <!-- Modal chi tiết đánh giá -->
    <div class="modal fade" id="evaluationDetailModal" tabindex="-1" role="dialog" aria-labelledby="evaluationDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="evaluationDetailModalLabel">
                        <i class="fas fa-chart-line mr-2"></i>Chi tiết đánh giá đề tài
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if ($decision): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="evaluation-summary-card">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-award mr-2"></i>Tóm tắt kết quả
                                    </h6>
                                    <div class="summary-item">
                                        <strong>Ngày nghiệm thu:</strong>
                                        <span><?php echo isset($decision['BB_NGAYNGHIEMTHU']) ? formatDate($decision['BB_NGAYNGHIEMTHU']) : 'Chưa xác định'; ?></span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Xếp loại:</strong>
                                        <span class="badge <?php echo $badge_class; ?> ml-2">
                                            <?php echo htmlspecialchars($xep_loai); ?>
                                        </span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Số biên bản:</strong>
                                        <span><?php echo htmlspecialchars($decision['BB_SOBB'] ?? 'Chưa có'); ?></span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Số quyết định:</strong>
                                        <span><?php echo htmlspecialchars($decision['QD_SO'] ?? 'Chưa có'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="file-statistics-card">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-file-alt mr-2"></i>Thống kê file đánh giá
                                    </h6>
                                    <div class="summary-item">
                                        <strong>Tổng số file:</strong>
                                        <span class="badge badge-primary ml-2"><?php echo count($evaluation_files); ?></span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Ngày cập nhật gần nhất:</strong>
                                        <span>
                                            <?php 
                                            if (count($evaluation_files) > 0) {
                                                $latest_date = max(array_column($evaluation_files, 'FDG_NGAYCAP'));
                                                echo formatDate($latest_date);
                                            } else {
                                                echo 'Chưa có file';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Trạng thái hồ sơ:</strong>
                                        <span class="badge <?php echo count($evaluation_files) > 0 ? 'badge-success' : 'badge-warning'; ?> ml-2">
                                            <?php echo count($evaluation_files) > 0 ? 'Hoàn tất' : 'Chưa hoàn tất'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isset($decision['QD_NOIDUNG']) && !empty($decision['QD_NOIDUNG'])): ?>
                            <hr>
                            <div class="decision-content">
                                <h6 class="text-secondary mb-3">
                                    <i class="fas fa-file-contract mr-2"></i>Nội dung quyết định nghiệm thu
                                </h6>
                                <div class="content-box">
                                    <?php echo nl2br(htmlspecialchars($decision['QD_NOIDUNG'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <div class="evaluation-timeline">
                            <h6 class="text-warning mb-3">
                                <i class="fas fa-history mr-2"></i>Tiến trình đánh giá
                            </h6>
                            <div class="timeline-mini">
                                <div class="timeline-item-mini">
                                    <div class="timeline-icon bg-primary">1</div>
                                    <div class="timeline-content">
                                        <strong>Hoàn thành đề tài</strong>
                                        <small class="text-muted d-block">
                                            <?php echo isset($project['DT_NGAYHOANTHANH']) ? formatDate($project['DT_NGAYHOANTHANH']) : 'Đang thực hiện'; ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="timeline-item-mini">
                                    <div class="timeline-icon bg-info">2</div>
                                    <div class="timeline-content">
                                        <strong>Nghiệm thu</strong>
                                        <small class="text-muted d-block">
                                            <?php echo formatDate($decision['BB_NGAYNGHIEMTHU']); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="timeline-item-mini">
                                    <div class="timeline-icon bg-success">3</div>
                                    <div class="timeline-content">
                                        <strong>Hoàn tất đánh giá</strong>
                                        <small class="text-muted d-block">
                                            <?php echo isset($decision['QD_NGAYBANHANH']) ? formatDate($decision['QD_NGAYBANHANH']) : 'Chưa xác định'; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Chưa có thông tin đánh giá cho đề tài này.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Đóng
                    </button>
                    <?php if ($decision && count($evaluation_files) > 0): ?>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print mr-1"></i>In báo cáo đánh giá
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .evaluation-summary-card, .file-statistics-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
        }
        
        .summary-item {
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .content-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .timeline-mini {
            position: relative;
        }
        
        .timeline-item-mini {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }
        
        .timeline-item-mini:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 40px;
            height: 20px;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .timeline-content {
            flex: 1;
        }
    </style>

    <!-- Modal Cập nhật điểm thành viên hội đồng -->
    <?php if (canEditProject($project['DT_TRANGTHAI'], $user_role, true) && count($council_members) > 0): ?>
        <div class="modal fade" id="updateScoreModal" tabindex="-1" role="dialog" aria-labelledby="updateScoreModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="updateScoreModalLabel">
                            <i class="fas fa-star mr-2"></i>Cập nhật điểm đánh giá thành viên hội đồng
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="update_council_member_score.php" method="post" id="updateScoreForm">
                        <div class="modal-body">
                            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                            <input type="hidden" name="decision_id" value="<?php echo htmlspecialchars($decision['QD_SO'] ?? ''); ?>">
                            <input type="hidden" name="member_id" id="modalMemberId">
                            
                            <!-- Thông tin thành viên -->
                            <div class="card bg-light border-0 mb-3">
                                <div class="card-body">
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-user-tie mr-2"></i>Thông tin thành viên
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Họ tên:</strong> <span id="modalMemberName">-</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Vai trò:</strong> <span id="modalMemberRole" class="badge badge-info">-</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form cập nhật điểm -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="member_score">
                                            <i class="fas fa-star mr-1"></i> Điểm đánh giá <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="member_score" name="member_score" 
                                            min="0" max="100" step="0.1" placeholder="Nhập điểm từ 0 đến 100" required>
                                        <div class="score-indicator mt-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-danger">0 (Không đạt)</small>
                                                <small class="text-warning">50 (Đạt)</small>
                                                <small class="text-info">70 (Khá)</small>
                                                <small class="text-primary">80 (Tốt)</small>
                                                <small class="text-success">90+ (Xuất sắc)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="member_evaluation">
                                            <i class="fas fa-comments mr-1"></i> Nhận xét đánh giá <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="member_evaluation" name="member_evaluation" required>
                                            <option value="">-- Chọn nhận xét --</option>
                                            <option value="Xuất sắc">Xuất sắc (90-100 điểm)</option>
                                            <option value="Tốt">Tốt (80-89 điểm)</option>
                                            <option value="Khá">Khá (70-79 điểm)</option>
                                            <option value="Đạt">Đạt (50-69 điểm)</option>
                                            <option value="Không đạt">Không đạt (0-49 điểm)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="score_note">
                                    <i class="fas fa-sticky-note mr-1"></i> Ghi chú thêm (tùy chọn)
                                </label>
                                <textarea class="form-control" id="score_note" name="score_note" rows="3" 
                                    placeholder="Ghi chú chi tiết về đánh giá, ý kiến phản hồi..."></textarea>
                            </div>
                            
                            <!-- Thông báo cảnh báo -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Lưu ý:</strong> Việc cập nhật điểm đánh giá sẽ được ghi lại trong lịch sử và không thể hoàn tác. 
                                Vui lòng kiểm tra kỹ thông tin trước khi lưu.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i>Hủy
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i>Lưu điểm đánh giá
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- JavaScript cho modal cập nhật điểm -->
    <script>
        // Xử lý modal cập nhật điểm thành viên hội đồng
        $('#updateScoreModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var memberId = button.data('member-id');
            var memberName = button.data('member-name');
            var memberRole = button.data('member-role');
            var currentScore = button.data('current-score');
            var currentEvaluation = button.data('current-evaluation');
            
            var modal = $(this);
            modal.find('#modalMemberId').val(memberId);
            modal.find('#modalMemberName').text(memberName);
            modal.find('#modalMemberRole').text(memberRole);
            
            // Cập nhật màu badge theo vai trò
            var roleClass = 'badge-info';
            if (memberRole === 'Chủ tịch hội đồng') {
                roleClass = 'badge-success';
            } else if (memberRole === 'Phó chủ tịch') {
                roleClass = 'badge-primary';
            }
            modal.find('#modalMemberRole').removeClass().addClass('badge ' + roleClass).text(memberRole);
            
            // Điền điểm hiện tại nếu có
            if (currentScore && currentScore >= 0) {
                modal.find('#member_score').val(currentScore);
            } else {
                modal.find('#member_score').val('');
            }
            
            // Điền đánh giá hiện tại nếu có
            if (currentEvaluation) {
                modal.find('#member_evaluation').val(currentEvaluation);
            } else {
                modal.find('#member_evaluation').val('');
            }
        });
        
        // Tự động cập nhật nhận xét dựa trên điểm
        $('#member_score').on('input', function() {
            var score = parseFloat($(this).val());
            var evaluationSelect = $('#member_evaluation');
            
            if (score >= 90) {
                evaluationSelect.val('Xuất sắc');
            } else if (score >= 80) {
                evaluationSelect.val('Tốt');
            } else if (score >= 70) {
                evaluationSelect.val('Khá');
            } else if (score >= 50) {
                evaluationSelect.val('Đạt');
            } else if (score >= 0) {
                evaluationSelect.val('Không đạt');
            }
        });
        
        // Validate form trước khi submit
        $('#updateScoreForm').on('submit', function(e) {
            var score = parseFloat($('#member_score').val());
            var evaluation = $('#member_evaluation').val();
            
            if (score < 0 || score > 100) {
                e.preventDefault();
                alert('Điểm đánh giá phải từ 0 đến 100!');
                return false;
            }
            
            if (!evaluation) {
                e.preventDefault();
                alert('Vui lòng chọn nhận xét đánh giá!');
                return false;
            }
            
            // Confirm trước khi submit
            var memberName = $('#modalMemberName').text();
            if (!confirm('Bạn có chắc chắn muốn cập nhật điểm đánh giá cho ' + memberName + '?\n\nĐiểm: ' + score + '\nNhận xét: ' + evaluation)) {
                e.preventDefault();
                return false;
            }
        });
    </script>

    <!-- CSS cho phần hiển thị thành viên hội đồng -->
    <style>
        .council-members-section .card {
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        
        .council-members-section .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .member-info .card-title {
            font-size: 1.1rem;
        }
        
        .member-details p {
            font-size: 0.9rem;
        }
        
        .evaluation-score {
            min-width: 80px;
        }
        
        .score-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid currentColor;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .no-score {
            width: 60px;
            height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .stat-item {
            padding: 10px;
            border-radius: 8px;
            background: rgba(255,255,255,0.7);
            margin-bottom: 10px;
        }
        
        .stat-item h5 {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .score-indicator {
            background: linear-gradient(to right, #dc3545, #ffc107, #28a745);
            height: 4px;
            border-radius: 2px;
            position: relative;
        }
        
        #updateScoreModal .modal-dialog {
            max-width: 600px;
        }
        
        #updateScoreModal .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* CSS cho điểm không hợp lệ */
        .invalid-score {
            position: relative;
            opacity: 0.7;
        }
        
        .invalid-score::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px dashed #dc3545;
            border-radius: 50%;
            animation: invalid-pulse 2s infinite;
        }
        
        @keyframes invalid-pulse {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }
        
        .final-classification {
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .final-classification h3 {
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
    </style>

    <!-- JavaScript cho hệ thống đánh giá chi tiết -->
    <script>
    $(document).ready(function() {
        // Load danh sách file cho từng thành viên
        function loadMemberFiles() {
            $('.file-list').each(function() {
                const memberId = $(this).data('member-id');
                const fileListContainer = $(this);
                
                $.ajax({
                    url: '/NLNganh/api/get_member_files_new.php',
                    method: 'GET',
                    data: {
                        member_id: memberId,
                        project_id: '<?php echo $project['DT_MADT']; ?>'
                    },
                    success: function(response) {
                        if (response.success && response.files.length > 0) {
                            let filesHtml = '';
                            response.files.forEach(function(file) {
                                const fileSize = file.file_size ? `(${Math.round(file.file_size / 1024)} KB)` : '';
                                filesHtml += `
                                    <div class="file-item mb-1">
                                        <small class="text-primary">
                                            <i class="fas fa-file mr-1"></i>
                                            <a href="/NLNganh/uploads/member_evaluation_files/${file.filename}" target="_blank">
                                                ${file.original_name} ${fileSize}
                                            </a>
                                        </small>
                                        <small class="text-muted d-block">
                                            ${file.upload_date}
                                        </small>
                                        ${file.description ? `<small class="text-info d-block">${file.description}</small>` : ''}
                                    </div>
                                `;
                            });
                            fileListContainer.html(filesHtml);
                        } else {
                            fileListContainer.html('<small class="text-muted">Chưa có file đánh giá</small>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading member files:', error);
                        fileListContainer.html('<small class="text-danger">Lỗi tải danh sách file</small>');
                    }
                });
            });
        }

        // Load evaluation criteria
        $('#loadEvaluationCriteriaBtn').click(function() {
            $('#evaluationCriteriaModal').modal('show');
            
            $.ajax({
                url: '/NLNganh/api/get_evaluation_criteria.php',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.criteria) {
                        let criteriaHtml = '';
                        response.criteria.forEach(function(criterion, index) {
                            criteriaHtml += `
                                <div class="criteria-item card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">
                                            ${index + 1}. ${criterion.TC_TEN}
                                            <span class="badge badge-info float-right">${criterion.TC_DIEMTOIDA} điểm</span>
                                        </h6>
                                        <p class="card-text text-muted">${criterion.TC_MOTA || 'Không có mô tả'}</p>
                                        <div class="criteria-details">
                                            <small class="text-secondary">
                                                <i class="fas fa-weight-hanging mr-1"></i>
                                                Trọng số: ${criterion.TC_TRONGSO}%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        $('#criteriaContainer').html(criteriaHtml);
                    } else {
                        $('#criteriaContainer').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Không thể tải danh sách tiêu chí đánh giá: ${response.message || 'Lỗi không xác định'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading criteria:', error);
                    $('#criteriaContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Lỗi kết nối: Không thể tải tiêu chí đánh giá
                        </div>
                    `);
                }
            });
        });

        // Evaluate member button
        $('.evaluate-member-btn').click(function() {
            const memberId = $(this).data('member-id');
            const memberName = $(this).data('member-name');
            const memberRole = $(this).data('member-role');
            
            $('#evaluateMemberModal .modal-title').text(`Đánh giá chi tiết - ${memberName} (${memberRole})`);
            $('#evaluateMemberModal').data('member-id', memberId);
            $('#evaluateMemberModal').data('member-name', memberName);
            
            // Load evaluation criteria for scoring
            loadEvaluationCriteria(memberId);
            
            $('#evaluateMemberModal').modal('show');
        });

        // Load evaluation criteria for detailed scoring
        function loadEvaluationCriteria(memberId) {
            $.ajax({
                url: '/NLNganh/api/get_evaluation_criteria.php',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.criteria) {
                        let formHtml = '<form id="detailedEvaluationForm">';
                        formHtml += `<input type="hidden" name="member_id" value="${memberId}">`;
                        formHtml += `<input type="hidden" name="project_id" value="<?php echo $project['DT_MADT']; ?>">`;
                        
                        response.criteria.forEach(function(criterion) {
                            formHtml += `
                                <div class="criteria-scoring mb-4">
                                    <div class="criteria-header">
                                        <h6 class="text-primary mb-2">${criterion.TC_TEN}</h6>
                                        <p class="text-muted small mb-2">${criterion.TC_MOTA || 'Không có mô tả'}</p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Điểm (0-${criterion.TC_DIEMTOIDA})</label>
                                                <input type="number" 
                                                       name="scores[${criterion.TC_MATC}]" 
                                                       class="form-control criteria-score" 
                                                       min="0" 
                                                       max="${criterion.TC_DIEMTOIDA}" 
                                                       step="0.1"
                                                       data-max="${criterion.TC_DIEMTOIDA}"
                                                       data-weight="${criterion.TC_TRONGSO}"
                                                       required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nhận xét</label>
                                                <textarea name="comments[${criterion.TC_MATC}]" 
                                                          class="form-control" 
                                                          rows="2" 
                                                          placeholder="Nhận xét về tiêu chí này..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        formHtml += `
                            <div class="total-score-display mb-3">
                                <h5 class="text-primary">
                                    Tổng điểm: <span id="totalScoreDisplay">0.0</span>/100
                                </h5>
                            </div>
                            <div class="form-group">
                                <label>Nhận xét tổng quan</label>
                                <textarea name="overall_comment" class="form-control" rows="3" placeholder="Nhận xét tổng quan về thành viên..."></textarea>
                            </div>
                        `;
                        formHtml += '</form>';
                        
                        $('#evaluationCriteriaContainer').html(formHtml);
                        
                        // Calculate total score when individual scores change
                        $('.criteria-score').on('input', calculateTotalScore);
                        
                        // Load existing scores if any
                        loadExistingScores(memberId);
                    } else {
                        $('#evaluationCriteriaContainer').html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Không thể tải danh sách tiêu chí đánh giá: ${response.message || 'Lỗi không xác định'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading criteria:', error);
                    $('#evaluationCriteriaContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Lỗi kết nối: Không thể tải tiêu chí đánh giá
                        </div>
                    `);
                }
            });
        }

        // Calculate total weighted score
        function calculateTotalScore() {
            let totalScore = 0;
            let totalWeight = 0;
            
            $('.criteria-score').each(function() {
                const score = parseFloat($(this).val()) || 0;
                const maxScore = parseFloat($(this).data('max'));
                const weight = parseFloat($(this).data('weight'));
                
                // Convert to percentage of max score, then apply weight
                const normalizedScore = (score / maxScore) * weight;
                totalScore += normalizedScore;
                totalWeight += weight;
            });
            
            $('#totalScoreDisplay').text(totalScore.toFixed(1));
        }

        // Load existing detailed scores
        function loadExistingScores(memberId) {
            $.ajax({
                url: '/NLNganh/api/get_member_detailed_scores.php',
                method: 'GET',
                data: {
                    member_id: memberId,
                    project_id: '<?php echo $project['DT_MADT']; ?>'
                },
                success: function(response) {
                    if (response.success && response.scores) {
                        response.scores.forEach(function(score) {
                            $(`input[name="scores[${score.TC_MATC}]"]`).val(score.CTDD_DIEM);
                            $(`textarea[name="comments[${score.TC_MATC}]"]`).val(score.CTDD_NHANXET || '');
                        });
                        
                        if (response.overall_comment) {
                            $('textarea[name="overall_comment"]').val(response.overall_comment);
                        }
                        
                        calculateTotalScore();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading existing scores:', error);
                }
            });
        }

        // Save detailed evaluation
        $('#saveDetailedEvaluationBtn').click(function() {
            const formData = $('#detailedEvaluationForm').serialize();
            
            $.ajax({
                url: '/NLNganh/save_detailed_evaluation.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success('Đánh giá chi tiết đã được lưu thành công!');
                        $('#evaluateMemberModal').modal('hide');
                        
                        // Update display
                        const memberId = $('#evaluateMemberModal').data('member-id');
                        updateMemberDisplay(memberId, response.total_score);
                        
                        // Check if project was auto-completed
                        if (response.project_completed) {
                            setTimeout(() => {
                                toastr.success(
                                    response.completion_message,
                                    'Đề tài hoàn thành!',
                                    {
                                        timeOut: 10000,
                                        extendedTimeOut: 5000
                                    }
                                );
                                // Refresh completion status
                                checkProjectCompletion();
                            }, 1000);
                        }
                        
                        // Reload page to show updated scores
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra khi lưu đánh giá');
                    }
                },
                error: function() {
                    toastr.error('Không thể kết nối đến server');
                }
            });
        });

        // Update member display after evaluation
        function updateMemberDisplay(memberId, totalScore) {
            const memberCard = $(`.council-member-card[data-member-id="${memberId}"]`);
            memberCard.find('.score-display').html(`
                <span class="h5 text-primary">${totalScore}</span>
                <small class="text-muted">/100</small>
            `);
            memberCard.find('.status-display').html(`
                <span class="badge badge-success">Đã hoàn thành</span>
            `);
        }

        // Upload file button
        $('.upload-file-btn').click(function() {
            const memberId = $(this).data('member-id');
            const memberName = $(this).data('member-name');
            
            $('#uploadMemberFileModal .modal-title').text(`Upload file đánh giá - ${memberName}`);
            $('#uploadMemberFileModal input[name="member_id"]').val(memberId);
            $('#uploadMemberFileModal').modal('show');
        });

        // Handle file upload form
        $('#memberFileUploadForm').submit(function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: '/NLNganh/upload_member_evaluation_file.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success('File đã được tải lên thành công!');
                        $('#uploadMemberFileModal').modal('hide');
                        
                        // Check if project was auto-completed
                        if (response.project_completed) {
                            setTimeout(() => {
                                toastr.success(
                                    response.completion_message,
                                    'Đề tài hoàn thành!',
                                    {
                                        timeOut: 10000,
                                        extendedTimeOut: 5000
                                    }
                                );
                                // Refresh completion status
                                checkProjectCompletion();
                            }, 1000);
                        }
                        
                        // Reload member files
                        loadMemberFiles();
                        
                        // Reset form
                        $('#memberFileUploadForm')[0].reset();
                        
                        // Reload page if project completed
                        if (response.project_completed) {
                            setTimeout(() => location.reload(), 2000);
                        }
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra khi tải file');
                    }
                },
                error: function() {
                    toastr.error('Không thể kết nối đến server');
                }
            });
        });

        // Load member files on page load
        loadMemberFiles();
    });
    </script>

    <!-- Modal for viewing evaluation criteria -->
    <div class="modal fade" id="evaluationCriteriaModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tiêu chí đánh giá</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="criteriaContainer">
                        <div class="text-center py-3">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for detailed member evaluation -->
    <div class="modal fade" id="evaluateMemberModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Đánh giá chi tiết thành viên</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="evaluationCriteriaContainer">
                        <div class="text-center py-3">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Đang tải tiêu chí đánh giá...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="saveDetailedEvaluationBtn">
                        <i class="fas fa-save mr-1"></i>Lưu đánh giá
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for uploading member evaluation files -->
    <div class="modal fade" id="uploadMemberFileModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload file đánh giá thành viên</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="memberFileUploadForm">
                    <div class="modal-body">
                        <input type="hidden" name="member_id" value="">
                        <input type="hidden" name="project_id" value="<?php echo $project['DT_MADT']; ?>">
                        
                        <div class="form-group">
                            <label>Tên file đánh giá <span class="text-danger">*</span></label>
                            <input type="text" name="file_name" class="form-control" placeholder="Nhập tên file đánh giá" required>
                        </div>
                        
                        <div class="form-group">
                            <label>File đánh giá <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="evaluation_file" class="custom-file-input" accept=".pdf,.doc,.docx,.txt" required>
                                <label class="custom-file-label">Chọn file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Chỉ chấp nhận file PDF, DOC, DOCX, TXT (tối đa 10MB)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Mô tả (tùy chọn)</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Mô tả về file đánh giá..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload mr-1"></i>Tải lên
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // JavaScript for checking project completion status
    $(document).ready(function() {
        // Load completion status on page load
        checkProjectCompletion();
        
        // Check completion button click
        $('#checkCompletionBtn').click(function() {
            checkProjectCompletion();
        });
    });
    
    function checkProjectCompletion() {
        var projectId = '<?php echo $project["DT_MADT"]; ?>';
        
        $('#completionDetails').html(`
            <div class="text-center py-3">
                <i class="fas fa-spinner fa-spin mr-2"></i>Đang kiểm tra trạng thái...
            </div>
        `);
        
        $.ajax({
            url: '/NLNganh/api/check_project_completion_status.php',
            method: 'POST',
            data: {
                project_id: projectId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayCompletionStatus(response);
                } else {
                    $('#completionDetails').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Lỗi: ${response.error}
                        </div>
                    `);
                }
            },
            error: function() {
                $('#completionDetails').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Không thể kết nối để kiểm tra trạng thái
                    </div>
                `);
            }
        });
    }
    
    function displayCompletionStatus(data) {
        var html = `
            <div class="completion-requirements">
                <div class="row">
                    <div class="col-md-6">
                        <div class="requirement-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas ${data.requirements.has_decision ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'} mr-2"></i>
                                <span class="${data.requirements.has_decision ? 'text-success' : 'text-danger'}">
                                    Quyết định nghiệm thu
                                </span>
                                ${data.requirements.has_decision ? '<span class="badge badge-success ml-2">Hoàn tất</span>' : '<span class="badge badge-danger ml-2">Chưa có</span>'}
                            </div>
                        </div>
                        
                        <div class="requirement-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas ${data.requirements.has_member_scores ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'} mr-2"></i>
                                <span class="${data.requirements.has_member_scores ? 'text-success' : 'text-danger'}">
                                    Điểm đánh giá thành viên
                                </span>
                                ${data.requirements.has_member_scores ? '<span class="badge badge-success ml-2">Hoàn tất</span>' : '<span class="badge badge-danger ml-2">Chưa đủ</span>'}
                            </div>
                            ${!data.requirements.has_member_scores ? '<small class="text-muted ml-4">Cần đánh giá tất cả thành viên hội đồng</small>' : ''}
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="requirement-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas ${data.requirements.has_evaluation_files ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'} mr-2"></i>
                                <span class="${data.requirements.has_evaluation_files ? 'text-success' : 'text-danger'}">
                                    File đánh giá thành viên
                                </span>
                                ${data.requirements.has_evaluation_files ? '<span class="badge badge-success ml-2">Hoàn tất</span>' : '<span class="badge badge-danger ml-2">Chưa đủ</span>'}
                            </div>
                            ${!data.requirements.has_evaluation_files ? '<small class="text-muted ml-4">Cần file đánh giá từ tất cả thành viên</small>' : ''}
                        </div>
                        
                        <div class="requirement-item mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas ${data.requirements.has_final_report ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'} mr-2"></i>
                                <span class="${data.requirements.has_final_report ? 'text-success' : 'text-danger'}">
                                    Báo cáo tổng kết
                                </span>
                                ${data.requirements.has_final_report ? '<span class="badge badge-success ml-2">Hoàn tất</span>' : '<span class="badge badge-danger ml-2">Chưa có</span>'}
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="overall-status text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas ${data.is_complete ? 'fa-check-circle text-success' : 'fa-clock text-warning'} mr-2 fa-2x"></i>
                        <div>
                            <h5 class="mb-0 ${data.is_complete ? 'text-success' : 'text-warning'}">
                                ${data.is_complete ? 'Đề tài đã hoàn thành' : 'Đề tài chưa hoàn thành'}
                            </h5>
                            <small class="text-muted">
                                ${data.is_complete ? 'Tất cả yêu cầu đã được đáp ứng' : 'Cần hoàn thành các yêu cầu còn lại'}
                            </small>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <span class="badge ${data.is_complete ? 'badge-success' : 'badge-warning'}">
                            ${data.is_complete ? 'Đã hoàn thành' : 'Đang thực hiện'}
                        </span>
                    </div>
                </div>
                
                ${data.is_complete ? `
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="fas fa-trophy mr-2"></i>
                        <strong>Chúc mừng!</strong> Đề tài đã hoàn thành đầy đủ tất cả các yêu cầu đánh giá.
                        ${data.auto_completed ? '<br><small>Trạng thái đã được cập nhật tự động.</small>' : ''}
                    </div>
                ` : `
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Lưu ý:</strong> Đề tài sẽ tự động chuyển sang trạng thái hoàn thành khi đã có biên bản nghiệm thu đạt và tất cả thành viên hội đồng đã có điểm đánh giá.
                    </div>
                `}
            </div>
        `;
        
        $('#completionDetails').html(html);
    }
    </script>

    <!-- Final tab enforcement script -->
    <script>
        // Final check and enforcement for tab functionality
        (function() {
            console.log('=== FINAL TAB ENFORCEMENT ===');
            
            function finalTabCheck() {
                try {
                    var activeVisibleTabs = document.querySelectorAll('.tab-pane.show.active');
                    var visibleTabs = [];
                    
                    activeVisibleTabs.forEach(function(tab) {
                        var computedStyle = window.getComputedStyle(tab);
                        if (computedStyle.display !== 'none' && tab.offsetHeight > 0) {
                            visibleTabs.push(tab);
                        }
                    });
                    
                    console.log('Found visible active tabs:', visibleTabs.length);
                    
                    if (visibleTabs.length === 0) {
                        console.log('No visible tabs found, forcing activation...');
                        
                        // Force show first tab with strong CSS
                        var proposalTab = document.getElementById('proposal');
                        var firstTabLink = document.querySelector('#documentTabs .nav-link');
                        
                        if (proposalTab && firstTabLink) {
                            // Remove all active states
                            document.querySelectorAll('#documentTabs .nav-link').forEach(function(link) {
                                link.classList.remove('active');
                            });
                            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                                pane.classList.remove('show', 'active');
                                pane.style.display = 'none';
                            });
                            
                            // Force activate
                            firstTabLink.classList.add('active');
                            proposalTab.classList.add('show', 'active');
                            proposalTab.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
                            
                            console.log('✓ Force activated first tab');
                        }
                    } else {
                        console.log('✓ Tabs are working correctly');
                    }
                    
                } catch (error) {
                    console.error('Final tab check error:', error);
                }
            }
            
            // Run checks at different intervals
            setTimeout(finalTabCheck, 100);
            setTimeout(finalTabCheck, 500);
            setTimeout(finalTabCheck, 1000);
            
            // Set up click handlers for tabs if they don't work
            setTimeout(function() {
                var tabLinks = document.querySelectorAll('#documentTabs .nav-link');
                tabLinks.forEach(function(link) {
                    // Remove existing listeners
                    var newLink = link.cloneNode(true);
                    link.parentNode.replaceChild(newLink, link);
                    
                    // Add new click listener
                    newLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        var targetId = this.getAttribute('href');
                        if (targetId) {
                            console.log('Emergency tab click:', targetId);
                            
                            // Remove all active
                            document.querySelectorAll('#documentTabs .nav-link').forEach(function(l) {
                                l.classList.remove('active');
                            });
                            document.querySelectorAll('.tab-pane').forEach(function(p) {
                                p.classList.remove('show', 'active');
                                p.style.display = 'none';
                            });
                            
                            // Activate clicked
                            this.classList.add('active');
                            var targetPane = document.querySelector(targetId);
                            if (targetPane) {
                                targetPane.classList.add('show', 'active');
                                targetPane.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
                            }
                        }
                    });
                });
                console.log('✓ Emergency click handlers installed');
            }, 200);
            
        })();
        
        // Script cho form cập nhật điểm thành viên hội đồng
        function resetScores() {
            const scoreInputs = document.querySelectorAll('input[name^="member_scores"]');
            scoreInputs.forEach(input => {
                const originalValue = input.getAttribute('data-original-value') || input.defaultValue;
                input.value = originalValue;
            });
        }
        
        // Validation realtime cho điểm số
        function validateScoreInput(input) {
            const value = parseFloat(input.value);
            const container = input.closest('td');
            
            // Remove existing feedback
            const existingFeedback = container.querySelector('.score-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }
            
            if (input.value && input.value.trim() !== '') {
                if (isNaN(value) || value < 0 || value > 100) {
                    input.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'score-feedback invalid-feedback';
                    feedback.textContent = 'Điểm phải từ 0 đến 100';
                    container.appendChild(feedback);
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            } else {
                input.classList.remove('is-invalid', 'is-valid');
            }
        }
        
        // Lưu giá trị gốc khi trang load và thêm validation
        document.addEventListener('DOMContentLoaded', function() {
            const scoreInputs = document.querySelectorAll('input[name^="member_scores"]');
            scoreInputs.forEach(input => {
                input.setAttribute('data-original-value', input.value);
                
                // Thêm event listeners cho validation realtime
                input.addEventListener('input', function() {
                    validateScoreInput(this);
                });
                
                input.addEventListener('blur', function() {
                    validateScoreInput(this);
                });
                
                // Ngăn chặn nhập ký tự không hợp lệ
                input.addEventListener('keypress', function(e) {
                    // Cho phép: backspace, delete, tab, escape, enter, decimal point
                    if ([8, 9, 27, 13, 46, 110, 190].indexOf(e.keyCode) !== -1 ||
                        // Cho phép Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                        (e.keyCode === 65 && e.ctrlKey === true) ||
                        (e.keyCode === 67 && e.ctrlKey === true) ||
                        (e.keyCode === 86 && e.ctrlKey === true) ||
                        (e.keyCode === 88 && e.ctrlKey === true)) {
                        return;
                    }
                    // Đảm bảo chỉ nhập số
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
