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

// Tính phần trăm hoàn thành tổng thể
$overall_completion = 0;
$progress_count = count($progress_entries);
if ($progress_count > 0) {
    $latest_progress = $progress_entries[0];
    $overall_completion = $latest_progress['TDDT_PHANTRAMHOANTHANH'];
}

// Lấy thông tin file hợp đồng nếu có
$contract_sql = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$contract_result = $stmt->get_result();
$contract = $contract_result->num_rows > 0 ? $contract_result->fetch_assoc() : null;

// Lấy thông tin quyết định nghiệm thu và biên bản nếu có
$decision_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM 
                FROM bien_ban bb
                JOIN quyet_dinh_nghiem_thu qd ON bb.BB_SOBB = qd.BB_SOBB
                WHERE bb.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";

// Thêm kiểm tra lỗi sau khi prepare
$stmt = $conn->prepare($decision_sql);
if ($stmt === false) {
    // Thêm log lỗi nhưng không dừng xử lý trang
    $decision_error = "Lỗi truy vấn quyết định nghiệm thu: " . $conn->error;
    $decision = null;
} else {
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $decision_result = $stmt->get_result();
    $decision = $decision_result->num_rows > 0 ? $decision_result->fetch_assoc() : null;
}

// Lấy file đánh giá nếu có biên bản
$evaluation_files = [];
if ($decision) {
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
            margin-bottom: 20px;
        }
        
        .status-badge {
            font-size: 1rem;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .status-badge:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.25);
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

        .progress-badge {
            background-color: #e3f2fd;
            color: var(--primary);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 10px;
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

            .status-badge {
                margin-top: 20px;
                display: inline-block;
            }

            .col-md-4.text-md-right {
                text-align: center !important;
                margin-top: 20px;
            }
            
            .action-buttons {
                justify-content: center;
                flex-wrap: wrap;
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

        /* Validation error styling */
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
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
    </style>
</head>

<body>
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
                    
                    <!-- Progress bar -->
                    <div class="mt-4">
                        <div class="progress-label">
                            <span>Tiến độ tổng thể</span>
                            <span><?php echo $overall_completion; ?>%</span>
                        </div>
                        <div class="progress project-progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $overall_completion; ?>%" 
                                aria-valuenow="<?php echo $overall_completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-5 text-md-right">
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
                        <div class="status-badge bg-<?php echo $status_class; ?>-soft text-<?php echo $status_class; ?> animate-pulse">
                            <i class="fas fa-<?php echo $status_icon; ?> mr-2"></i>
                            <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                        </div>
                    </div>
                    
                    <?php if ($has_access): ?>
                        <div class="action-buttons mt-3">
                            <?php if ($project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                                <?php if ($user_role === 'Chủ nhiệm'): ?>
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addProgressModal">
                                        <i class="fas fa-tasks mr-1"></i> Cập nhật tiến độ
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="Chỉ chủ nhiệm đề tài mới có thể cập nhật tiến độ">
                                        <i class="fas fa-lock mr-1"></i> Cập nhật tiến độ
                                    </button>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle mr-1"></i> Chỉ chủ nhiệm đề tài mới có thể cập nhật tiến độ và tải file
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <button class="btn btn-sm btn-outline-primary no-print" id="printProjectBtn">
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
                                            <?php if ($entry['TDDT_PHANTRAMHOANTHANH'] > 0): ?>
                                                <span class="progress-badge">
                                                    <i class="fas fa-percentage mr-1"></i>
                                                    <?php echo $entry['TDDT_PHANTRAMHOANTHANH']; ?>%
                                                </span>
                                            <?php endif; ?>
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
                            <li class="nav-item">
                                <a class="nav-link active" id="proposal-tab" data-toggle="tab" href="#proposal" role="tab">
                                    <i class="fas fa-file-alt mr-1"></i> Thuyết minh
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="contract-tab" data-toggle="tab" href="#contract" role="tab">
                                    <i class="fas fa-file-contract mr-1"></i> Hợp đồng
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="decision-tab" data-toggle="tab" href="#decision" role="tab">
                                    <i class="fas fa-file-signature mr-1"></i> Quyết định
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="report-tab" data-toggle="tab" href="#report" role="tab">
                                    <i class="fas fa-file-invoice mr-1"></i> Biên bản
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="evaluation-tab" data-toggle="tab" href="#evaluation" role="tab">
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm'): ?>
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
                                <?php elseif ($has_access && $user_role !== 'Chủ nhiệm'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock mr-2"></i> 
                                        <strong>Quyền hạn bị hạn chế:</strong> Chỉ chủ nhiệm đề tài mới có thể cập nhật file thuyết minh.
                                        <br><small class="text-muted">Vai trò của bạn: <strong><?php echo htmlspecialchars($user_role); ?></strong></small>
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm'): ?>
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
                                                            placeholder="Nhập mã hợp đồng" required>
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
                                                    rows="3" placeholder="Nhập mô tả về nội dung hợp đồng, điều khoản đặc biệt..."><?php echo htmlspecialchars($contract['HD_MOTA'] ?? ''); ?></textarea>
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
                                <?php elseif ($has_access && $user_role !== 'Chủ nhiệm'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock mr-2"></i> 
                                        <strong>Quyền hạn bị hạn chế:</strong> Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin hợp đồng.
                                        <br><small class="text-muted">Vai trò của bạn: <strong><?php echo htmlspecialchars($user_role); ?></strong></small>
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm'): ?>
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
                                                            placeholder="Nhập số quyết định (ví dụ: QĐ001/2024)" required>
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
                                <?php elseif ($has_access && $user_role !== 'Chủ nhiệm'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-lock mr-2"></i> 
                                        <strong>Quyền hạn bị hạn chế:</strong> Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin quyết định nghiệm thu.
                                        <br><small class="text-muted">Vai trò của bạn: <strong><?php echo htmlspecialchars($user_role); ?></strong></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Biên bản nghiệm thu -->
                            <div class="tab-pane fade" id="report" role="tabpanel" aria-labelledby="report-tab">
                                <?php if ($decision): ?>
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
                                                        $xeploai = isset($decision['BB_XEPLOAI']) ? $decision['BB_XEPLOAI'] : '';
                                                        echo ($xeploai == 'Xuất sắc' || $xeploai == 'Tốt') ? 'badge-success' : 
                                                            (($xeploai == 'Khá' || $xeploai == 'Đạt') ? 'badge-primary' : 'badge-secondary'); 
                                                        ?>">
                                                        <?php echo htmlspecialchars($xeploai ?: 'Chưa xác định'); ?>
                                                    </span>
                                                </p>
                                                <p class="mb-2"><strong>Tổng điểm:</strong>
                                                    <span class="badge badge-info"><?php echo isset($decision['BB_TONGDIEM']) ? number_format($decision['BB_TONGDIEM'], 2) . '/10' : 'Chưa xác định'; ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i> Chưa có thông tin biên bản nghiệm thu. Vui lòng tạo quyết định nghiệm thu trước.
                                    </div>
                                <?php endif; ?>

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && $decision): ?>
                                    <div class="report-update-form">
                                        <h6 class="mb-3 text-center">
                                            <i class="fas fa-file-invoice mr-2"></i>
                                            Cập nhật thông tin biên bản nghiệm thu
                                        </h6>
                                        <form action="/NLNganh/view/student/update_report_info.php" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="project_id"
                                                value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                                            <input type="hidden" name="decision_id"
                                                value="<?php echo htmlspecialchars($decision['QD_SO']); ?>">
                                            <input type="hidden" name="report_id"
                                                value="<?php echo htmlspecialchars($decision['BB_SOBB'] ?? ''); ?>">
                                            
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
                                                            <option value="Xuất sắc" <?php echo (isset($decision['BB_XEPLOAI']) && $decision['BB_XEPLOAI'] === 'Xuất sắc') ? 'selected' : ''; ?>>Xuất sắc</option>
                                                            <option value="Tốt" <?php echo (isset($decision['BB_XEPLOAI']) && $decision['BB_XEPLOAI'] === 'Tốt') ? 'selected' : ''; ?>>Tốt</option>
                                                            <option value="Khá" <?php echo (isset($decision['BB_XEPLOAI']) && $decision['BB_XEPLOAI'] === 'Khá') ? 'selected' : ''; ?>>Khá</option>
                                                            <option value="Đạt" <?php echo (isset($decision['BB_XEPLOAI']) && $decision['BB_XEPLOAI'] === 'Đạt') ? 'selected' : ''; ?>>Đạt</option>
                                                            <option value="Không đạt" <?php echo (isset($decision['BB_XEPLOAI']) && $decision['BB_XEPLOAI'] === 'Không đạt') ? 'selected' : ''; ?>>Không đạt</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="total_score">
                                                    <i class="fas fa-star mr-1"></i> Tổng điểm đánh giá (0-10)
                                                </label>
                                                <input type="number" class="form-control" id="total_score" name="total_score" 
                                                    min="0" max="10" step="0.1" 
                                                    value="<?php echo isset($decision['BB_TONGDIEM']) ? $decision['BB_TONGDIEM'] : ''; ?>" 
                                                    placeholder="Nhập tổng điểm đánh giá">
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Điểm từ 0 đến 10, có thể nhập số thập phân (ví dụ: 8.5)
                                                </small>
                                            </div>
                                            
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
                                                <input type="hidden" id="council_members" name="council_members" value="<?php echo htmlspecialchars($decision['HD_THANHVIEN'] ?? ''); ?>">
                                                
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Chọn giảng viên từ danh sách và chỉ định vai trò (Chủ tịch, Thành viên, Thư ký)
                                                </small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="report_update_reason">
                                                    <i class="fas fa-edit mr-1"></i> Lý do cập nhật <span class="text-danger">*</span>
                                                </label>
                                                <textarea class="form-control" id="report_update_reason" name="update_reason" 
                                                    rows="2" placeholder="Nhập lý do cập nhật thông tin biên bản (ví dụ: cập nhật điểm số, thay đổi hội đồng...)" required></textarea>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Thông tin này sẽ được ghi lại trong tiến độ đề tài
                                                </small>
                                            </div>
                                            
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-save mr-2"></i> 
                                                    Cập nhật thông tin biên bản
                                                </button>
                                            </div>
                                        </form>
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
                                                                case 'Trung bình':
                                                                    $badge_class = 'badge-warning';
                                                                    break;
                                                                case 'Yếu':
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
                                                                    <?php if ($user_role === 'Chủ nhiệm'): ?>
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

                                    <?php if ($user_role === 'Chủ nhiệm'): ?>
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
                                    <?php endif; ?>

                                <?php elseif ($decision && $user_role === 'Chủ nhiệm'): ?>
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
                                        <?php else: ?>
                                            Chưa có file đánh giá nào.
                                            <?php if ($user_role !== 'Chủ nhiệm'): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle"></i> Chỉ chủ nhiệm đề tài mới có thể tải lên file đánh giá.
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                <label for="progress_completion">
                                    <i class="fas fa-percentage mr-1"></i> Tiến độ hoàn thành (%) <span class="text-danger">*</span>
                                </label>
                                <input type="range" class="custom-range" min="0" max="100" step="5" 
                                    id="progress_completion" name="progress_completion" value="<?php echo $overall_completion; ?>">
                                <div class="d-flex justify-content-between">
                                    <span>0%</span>
                                    <span id="progress_completion_value"><?php echo $overall_completion; ?>%</span>
                                    <span>100%</span>
                                </div>
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

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Helper function để hiển thị/ẩn loading overlay
        function showLoading() {
            $('#loadingOverlay').css('display', 'flex');
        }

        function hideLoading() {
            $('#loadingOverlay').css('display', 'none');
        }

        // Helper function để hiển thị lỗi validation hiệu quả
        function showValidationError(message, fieldSelector) {
            alert(message);
            if (fieldSelector) {
                $(fieldSelector).focus().addClass('is-invalid');
                // Auto-remove error class sau 3 giây
                setTimeout(() => {
                    $(fieldSelector).removeClass('is-invalid');
                }, 3000);
            }
        }

        // Helper function để validate form nhanh chóng
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

        // Debounce function để tối ưu performance
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        $(document).ready(function() {
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

            // Show progress completion value when slider changes
            $('#progress_completion').on('input', function() {
                $('#progress_completion_value').text($(this).val() + '%');
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

            // Validation cho form biên bản nghiệm thu
            $('form[action="/NLNganh/view/student/update_report_info.php"]').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const acceptanceDate = form.find('#acceptance_date').val();
                const evaluationGrade = form.find('#evaluation_grade').val();
                const updateReason = form.find('#report_update_reason').val().trim();
                const totalScore = form.find('#total_score').val();
                
                // Validate required fields using helper function with context-specific selectors
                const requiredFields = [
                    { selector: '#acceptance_date', message: 'Vui lòng chọn ngày nghiệm thu.' },
                    { selector: '#evaluation_grade', message: 'Vui lòng chọn xếp loại đánh giá.' },
                    { selector: '#report_update_reason', message: 'Vui lòng nhập lý do cập nhật.' }
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
                if (totalScore && (parseFloat(totalScore) < 0 || parseFloat(totalScore) > 10)) {
                    alert('Tổng điểm đánh giá phải từ 0 đến 10.');
                    $('#total_score').focus();
                    return false;
                }
                
                // Hiển thị xác nhận
                const confirmation = confirm(
                    `Xác nhận cập nhật thông tin biên bản nghiệm thu?\n\n` +
                    `Chi tiết:\n` +
                    `- Ngày nghiệm thu: ${acceptanceDate}\n` +
                    `- Xếp loại: ${evaluationGrade}\n` +
                    (totalScore ? `- Tổng điểm: ${totalScore}/10\n` : '') +
                    '\nThao tác này sẽ được ghi lại vào tiến độ đề tài.\n' +
                    `Lý do: ${updateReason}`
                );
                
                if (confirmation) {
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
        let councilMembers = [];
        let teachers = [];
        
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
                        teachers = response.data;
                        populateTeacherSelect(teachers);
                        populateDepartmentFilter(teachers);
                        updateTeacherCount(teachers.length);
                        console.log('Loaded teachers:', teachers.length);
                    } else {
                        console.error('Error loading teachers:', response.message);
                        // Thử API backup không cần session
                        $.get('/NLNganh/api/test_teachers_no_session.php')
                            .done(function(backupResponse) {
                                if (backupResponse.success) {
                                    teachers = backupResponse.data;
                                    populateTeacherSelect(teachers);
                                    populateDepartmentFilter(teachers);
                                    updateTeacherCount(teachers.length);
                                    console.log('Loaded teachers from backup API:', teachers.length);
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
                                teachers = backupResponse.data;
                                populateTeacherSelect(teachers);
                                populateDepartmentFilter(teachers);
                                updateTeacherCount(teachers.length);
                                console.log('Loaded teachers from backup API:', teachers.length);
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
            populateTeacherSelect(teachers);
            $(this).blur(); // Remove focus from button
        });
        
        // Hàm lọc giảng viên
        function filterTeachers() {
            const searchQuery = $('#searchTeacher').val().toLowerCase();
            const selectedDepartment = $('#departmentFilter').val();
            
            let filtered = teachers.filter(teacher => {
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
            populateTeacherSelect(teachers);
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
            const existingMember = councilMembers.find(member => member.id === teacherId);
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
            
            councilMembers.push(newMember);
            updateCouncilMembersDisplay();
            updateCouncilMembersInput();
            
            $('#councilMemberModal').modal('hide');
        });
        
        // Cập nhật hiển thị danh sách thành viên
        function updateCouncilMembersDisplay() {
            const container = $('#selectedCouncilMembers');
            container.empty();
            
            if (councilMembers.length === 0) {
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
            const sortedMembers = councilMembers.slice().sort((a, b) => {
                return (roleOrder[a.role] || 5) - (roleOrder[b.role] || 5);
            });
            
            sortedMembers.forEach((member, index) => {
                // Tìm index thực trong mảng gốc
                const originalIndex = councilMembers.findIndex(m => m.id === member.id);
                
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
                        <span><strong>Tổng số thành viên:</strong> ${councilMembers.length}</span>
                    </div>
                </div>
            `);
            
            container.append(summary);
            container.append(membersList);
        }
        
        // Xóa thành viên
        function removeCouncilMember(index) {
            councilMembers.splice(index, 1);
            updateCouncilMembersDisplay();
            updateCouncilMembersInput();
        }
        
        // Cập nhật input ẩn
        function updateCouncilMembersInput() {
            const membersText = councilMembers.map(member => 
                `${member.fullName} (${member.role})`
            ).join('\n');
            
            $('#council_members').val(membersText);
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
</body>
</html>
