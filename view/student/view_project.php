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

// Nếu không có ID, thử lấy từ các nguồn khác
if (empty($project_id)) {
    // Thử lấy từ POST (trong trường hợp form submit)
    $project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';
    
    // Thử lấy từ session (nếu có lưu trước đó)
    if (empty($project_id) && isset($_SESSION['current_project_id'])) {
        $project_id = $_SESSION['current_project_id'];
        // Redirect với ID để đảm bảo URL đúng
        $tab = isset($_GET['tab']) ? '&tab=' . urlencode($_GET['tab']) : '';
        header('Location: view_project.php?id=' . urlencode($project_id) . $tab);
        exit;
    }
}

if (empty($project_id)) {
    $_SESSION['error_message'] = "Thiếu mã đề tài trong URL. Vui lòng truy cập từ danh sách đề tài.";
    header('Location: student_manage_projects.php');
    exit;
}

// Lưu project_id vào session để backup
$_SESSION['current_project_id'] = $project_id;

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
                           gv.GV_EMAIL, gv.GV_SDT
                    FROM thanh_vien_hoi_dong tv
                    JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                    WHERE tv.QD_SO = ?
                    ORDER BY 
                        CASE tv.TV_VAITRO 
                            WHEN 'Chủ tịch hội đồng' THEN 1
                            WHEN 'Phó chủ tịch' THEN 2
                            WHEN 'Thành viên' THEN 3
                            WHEN 'Thư ký' THEN 4
                            ELSE 5
                        END, 
                        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) ASC";
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

// Lấy danh sách file đánh giá của các thành viên hội đồng
$member_evaluation_files = [];
if (!empty($council_members)) {
    try {
        $stmt = $conn->prepare("
            SELECT FDG_TENFILE as FDK_TEN, FDG_FILE as FDK_DUONGDAN, GV_MAGV as FDK_MEMBER_ID, FDG_MOTA as FDK_MOTA, FDG_NGAYTAO as FDK_NGAYTAO 
            FROM file_dinh_kem 
            WHERE FDG_LOAI = 'member_evaluation' AND GV_MAGV IS NOT NULL
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($file = $result->fetch_assoc()) {
                $member_evaluation_files[] = $file;
            }
        }
    } catch (Exception $e) {
        // Log error nhưng không hiển thị cho user
        error_log("Error loading member evaluation files: " . $e->getMessage());
    }
}

// Lấy danh sách tiêu chí đánh giá từ bảng tieu_chi hiện có
$evaluation_criteria = [];
try {
    $stmt = $conn->prepare("
        SELECT TC_MATC, TC_NDDANHGIA, TC_MOTA, TC_DIEMTOIDA, TC_THUTU 
        FROM tieu_chi 
        WHERE TC_TRANGTHAI = 'Hoạt động' 
        ORDER BY TC_THUTU ASC, TC_MATC ASC
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($criteria = $result->fetch_assoc()) {
            $evaluation_criteria[] = [
                'TC_MA' => $criteria['TC_MATC'],
                'TC_TEN' => $criteria['TC_NDDANHGIA'], // Sử dụng nội dung đánh giá làm tên
                'TC_MOTA' => $criteria['TC_MOTA'] ?? '',
                'TC_DIEM_TOIDAI' => $criteria['TC_DIEMTOIDA'],
                'TC_THUTU' => $criteria['TC_THUTU']
            ];
        }
    }
} catch (Exception $e) {
    error_log("Lỗi load tiêu chí từ database: " . $e->getMessage());
    // Nếu bảng chưa tồn tại hoặc có lỗi, tạo tiêu chí mặc định từ database
    $evaluation_criteria = [
        [
            'TC_MA' => 'TC001',
            'TC_TEN' => 'Tổng quan tình hình nghiên cứu, lý do chọn đề tài',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '10.00',
            'TC_THUTU' => 1
        ],
        [
            'TC_MA' => 'TC002',
            'TC_TEN' => 'Mục tiêu đề tài',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '15.00',
            'TC_THUTU' => 2
        ],
        [
            'TC_MA' => 'TC003',
            'TC_TEN' => 'Phương pháp nghiên cứu',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '15.00',
            'TC_THUTU' => 3
        ],
        [
            'TC_MA' => 'TC004',
            'TC_TEN' => 'Nội dung khoa học',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '30.00',
            'TC_THUTU' => 4
        ],
        [
            'TC_MA' => 'TC005',
            'TC_TEN' => 'Đóng góp về mặt kinh tế - xã hội, giáo dục và đào tạo, an ninh, quốc phòng',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '15.00',
            'TC_THUTU' => 5
        ],
        [
            'TC_MA' => 'TC006',
            'TC_TEN' => 'Hình thức trình bày báo cáo tổng kết đề tài',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '5.00',
            'TC_THUTU' => 6
        ],
        [
            'TC_MA' => 'TC007',
            'TC_TEN' => 'Thời gian và tiến độ thực hiện đề tài',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '5.00',
            'TC_THUTU' => 7
        ],
        [
            'TC_MA' => 'TC008',
            'TC_TEN' => 'Điểm thưởng: có bài báo đăng trên tạp chí khoa học',
            'TC_MOTA' => '',
            'TC_DIEM_TOIDAI' => '5.00',
            'TC_THUTU' => 8
        ]
    ];
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
                        <span>Ngày tạo: <?php echo formatDate($project['DT_NGAYTAO']); ?></span>
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
                            <?php elseif (canEditProject($project, $user_role)): ?>
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
                        <?php if ($has_access && canEditProject($project, $user_role)): ?>
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project, $user_role) && $decision): ?>
                                    <div class="report-update-form">
                                        <h6 class="mb-3 text-center">
                                            <i class="fas fa-file-invoice mr-2"></i>
                                            Cập nhật thông tin biên bản nghiệm thu
                                        </h6>
                                        
                                        <!-- Thông báo về khả năng cập nhật nhiều lần -->
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Thông tin:</strong> Bạn có thể cập nhật thông tin biên bản nghiệm thu nhiều lần để điều chỉnh thông tin cho phù hợp.
                                        </div>
                                        
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
                                                <!-- Thông báo về khả năng cập nhật nhiều lần -->
                                                <div class="alert alert-success">
                                                    <i class="fas fa-users mr-2"></i>
                                                    <strong>Lưu ý:</strong> Bạn có thể cập nhật danh sách thành viên hội đồng và điểm số nhiều lần để điều chỉnh thông tin.
                                                </div>
                                                
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
                                    
                                    <!-- Trạng thái hoàn thành đề tài
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
                                    </div> -->
                                <?php endif; ?>

                                <!-- File đánh giá - Đã ẩn -->
                                <?php if (false && count($evaluation_files) > 0): // Tạm thời ẩn phần file đánh giá ?>
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
                                                                    <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project, $user_role)): ?>
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

                                    <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project, $user_role)): ?>
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

                                <?php elseif (false && $decision && $has_access && $user_role === 'Chủ nhiệm' && canEditProject($project, $user_role)): // Tạm thời ẩn ?>
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
                                <?php elseif (false): // Tạm thời ẩn phần file đánh giá ?>
                                    <!-- Phần file đánh giá đã bị ẩn -->
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
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary mb-0">
                                                <i class="fas fa-users mr-2"></i>Thành viên hội đồng nghiệm thu
                                                <span class="badge badge-primary ml-2"><?php echo count($council_members); ?> thành viên</span>
                                            </h6>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="expandTableBtn" title="Phóng to bảng">
                                                    <i class="fas fa-expand-arrows-alt"></i> Phóng to
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="collapseTableBtn" title="Thu nhỏ bảng">
                                                    <i class="fas fa-compress-arrows-alt"></i> Thu nhỏ
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive" id="councilTableContainer">
                                            <table class="table table-bordered table-hover table-sm">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Họ tên</th>
                                                        <th>Vai trò</th>
                                                        <th class="text-center">Điểm</th>
                                                        <th class="text-center">Trạng thái</th>
                                                        <th class="text-center">File đánh giá</th>
                                                        <th class="text-center">Hành động</th>
                                                        <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project, $user_role)): ?>
                                                            <th class="text-center d-none">Hành động (có quyền)</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($council_members as $index => $member): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <?php 
                                                                // Lấy tên và làm sạch tên (loại bỏ phần vai trò trong ngoặc)
                                                                $full_name = $member['GV_HOTEN'] ?? $member['TV_HOTEN'] ?? 'Chưa có tên';
                                                                $clean_name = preg_replace('/\s*\([^)]*\)/', '', $full_name);
                                                                $clean_name = trim($clean_name);
                                                                ?>
                                                                <strong><?php echo htmlspecialchars($clean_name); ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                // Sử dụng logic giống như phần text-muted để xác định vai trò
                                                                $full_name = $member['GV_HOTEN'] ?? $member['TV_HOTEN'] ?? 'Chưa có tên';
                                                                $role = '';
                                                                $badge_class = 'badge-primary';
                                                                
                                                                // Logic giống như phần text-muted - ưu tiên vai trò trong ngoặc
                                                                if (preg_match('/\(([^)]+)\)/', $full_name, $matches)) {
                                                                    // Nếu có vai trò trong ngoặc trong tên
                                                                    $role = trim($matches[1]);
                                                                } else {
                                                                    // Hiển thị vai trò từ TV_VAITRO
                                                                    $role = isset($member['TV_VAITRO']) ? trim($member['TV_VAITRO']) : '';
                                                                    if (empty($role)) {
                                                                        $role = 'Thành viên';
                                                                    }
                                                                }
                                                                
                                                                // Debug: hiển thị giá trị gốc
                                                                $original_role = $role;
                                                                
                                                                // Chuẩn hóa tên vai trò và màu sắc - CHỈ khi cần thiết
                                                                $role_normalized = strtolower($role);
                                                                
                                                                if (strpos($role_normalized, 'chủ tịch') !== false && strpos($role_normalized, 'phó') === false) {
                                                                    $badge_class = 'badge-danger';
                                                                    $role = 'Chủ tịch hội đồng';
                                                                } elseif (strpos($role_normalized, 'phó chủ tịch') !== false || strpos($role_normalized, 'phó') !== false) {
                                                                    $badge_class = 'badge-warning';
                                                                    $role = 'Phó chủ tịch';
                                                                } elseif (strpos($role_normalized, 'thư ký') !== false || 
                                                                         strpos($role_normalized, 'thu ky') !== false ||
                                                                         strpos($role_normalized, 'thư kí') !== false) {
                                                                    $badge_class = 'badge-info';
                                                                    $role = 'Thư ký';
                                                                } elseif (strpos($role_normalized, 'phản biện') !== false || 
                                                                         strpos($role_normalized, 'phan bien') !== false) {
                                                                    $badge_class = 'badge-secondary';
                                                                    $role = 'Phản biện';
                                                                } else {
                                                                    $badge_class = 'badge-primary';
                                                                    // Giữ nguyên vai trò gốc nếu không khớp pattern nào
                                                                    if ($role === 'Thành viên' || empty($role)) {
                                                                        $role = 'Thành viên';
                                                                    } else {
                                                                        $role = $original_role; // Giữ nguyên giá trị gốc
                                                                    }
                                                                }
                                                                ?>
                                                                <span class="badge <?php echo $badge_class; ?>">
                                                                    <?php echo htmlspecialchars($role); ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php if (isset($member['TV_DIEM']) && is_numeric($member['TV_DIEM']) && $member['TV_DIEM'] !== null): ?>
                                                                    <span class="font-weight-bold text-primary"><?php echo number_format((float)$member['TV_DIEM'], 1); ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php if (isset($member['TV_DIEM']) && is_numeric($member['TV_DIEM']) && $member['TV_DIEM'] !== null): ?>
                                                                    <span class="badge badge-success">Đã đánh giá</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-warning">Chưa đánh giá</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php 
                                                                // Kiểm tra xem có file đánh giá nào của thành viên này không
                                                                $member_files = [];
                                                                if (isset($member_evaluation_files) && is_array($member_evaluation_files)) {
                                                                    $member_id = $member['GV_MAGV'] ?? $member['MAGV'] ?? $member['TV_MAGV'] ?? '';
                                                                    $member_files = array_filter($member_evaluation_files, function($file) use ($member_id) {
                                                                        return $file['FDK_MEMBER_ID'] === $member_id;
                                                                    });
                                                                }
                                                                
                                                                if (count($member_files) > 0): ?>
                                                                    <div class="btn-group">
                                                                        <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown" title="Có <?php echo count($member_files); ?> file">
                                                                            <i class="fas fa-file-check mr-1"></i><?php echo count($member_files); ?> file
                                                                        </button>
                                                                        <div class="dropdown-menu">
                                                                            <?php foreach ($member_files as $file): ?>
                                                                                <a class="dropdown-item" href="/NLNganh/uploads/member_evaluations/<?php echo htmlspecialchars($file['FDK_DUONGDAN']); ?>" target="_blank">
                                                                                    <i class="fas fa-download mr-1"></i><?php echo htmlspecialchars($file['FDK_TEN']); ?>
                                                                                </a>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted">
                                                                        <i class="fas fa-file-times mr-1"></i>Chưa có
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <!-- Cột hành động luôn hiển thị cho test -->
                                                            <td class="text-center">
                                                                <div class="btn-group-vertical" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary evaluate-member-btn mb-1" 
                                                                            data-member-id="<?php echo htmlspecialchars($member['GV_MAGV'] ?? $member['MAGV'] ?? $member['TV_MAGV'] ?? $member['id'] ?? ''); ?>"
                                                                            data-member-name="<?php echo htmlspecialchars($member['GV_HOTEN'] ?? $member['TV_HOTEN'] ?? ''); ?>"
                                                                            data-member-role="<?php echo htmlspecialchars($member['TV_VAITRO'] ?? ''); ?>"
                                                                            data-member-score="<?php echo htmlspecialchars($member['TV_DIEM'] ?? ''); ?>"
                                                                            title="Nhập điểm đánh giá">
                                                                        <i class="fas fa-star mr-1"></i>Nhập điểm
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-success upload-evaluation-btn" 
                                                                            data-member-id="<?php echo htmlspecialchars($member['GV_MAGV'] ?? $member['MAGV'] ?? $member['TV_MAGV'] ?? $member['id'] ?? ''); ?>"
                                                                            data-member-name="<?php echo htmlspecialchars($member['GV_HOTEN'] ?? $member['TV_HOTEN'] ?? ''); ?>"
                                                                            data-member-role="<?php echo htmlspecialchars($member['TV_VAITRO'] ?? ''); ?>"
                                                                            title="Upload file đánh giá">
                                                                        <i class="fas fa-upload mr-1"></i>Upload file
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            
                                                            <!-- Cột hành động có điều kiện -->
                                                            <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project, $user_role)): ?>
                                                                <td class="text-center d-none">
                                                                    <div class="btn-group-vertical" role="group">
                                                                        <button type="button" class="btn btn-sm btn-outline-primary evaluate-member-btn mb-1" 
                                                                                data-member-id="<?php echo htmlspecialchars($member['GV_MAGV'] ?? $member['MAGV'] ?? $member['TV_MAGV'] ?? $member['id'] ?? ''); ?>"
                                                                                data-member-name="<?php echo htmlspecialchars($member['GV_HOTEN'] ?? $member['TV_HOTEN'] ?? ''); ?>"
                                                                                data-member-role="<?php echo htmlspecialchars($member['TV_VAITRO'] ?? ''); ?>"
                                                                                data-member-score="<?php echo htmlspecialchars($member['TV_DIEM'] ?? ''); ?>"
                                                                                title="Nhập điểm đánh giá">
                                                                            <i class="fas fa-star mr-1"></i>Nhập điểm
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-success upload-evaluation-btn" 
                                                                                data-member-id="<?php echo htmlspecialchars($member['GV_MAGV'] ?? $member['MAGV'] ?? $member['TV_MAGV'] ?? $member['id'] ?? ''); ?>"
                                                                                data-member-name="<?php echo htmlspecialchars($member['GV_HOTEN'] ?? $member['TV_HOTEN'] ?? ''); ?>"
                                                                                data-member-role="<?php echo htmlspecialchars($member['TV_VAITRO'] ?? ''); ?>"
                                                                                title="Upload file đánh giá">
                                                                            <i class="fas fa-upload mr-1"></i>Upload file
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            <?php else: ?>
                                                                <td class="text-center d-none">
                                                                    <small class="text-muted">Không có quyền</small>
                                                                </td>
                                                            <?php endif; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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
    <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project, $user_role)): ?>
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

    <!-- JavaScript Libraries - Optimized Loading -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" defer></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" defer></script>

    <!-- Application Scripts - Load after core libraries -->
    <script src="/NLNganh/assets/js/student/unified_tab_system.js" defer></script>
    
    <!-- Remove debug script for production -->
    <!-- <script src="/NLNganh/assets/js/student/url_debug.js"></script> -->

    <!-- Main Application Scripts -->
    <script>
    <script>
        // Optimized initialization to avoid performance violations
        window.addEventListener('load', function() {
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

            // Function để ẩn modal phóng to bảng thành viên
            function closeTableExpandedModal() {
                if ($('#tableExpandedOverlay').length > 0) {
                    $('#tableExpandedOverlay').remove();
                    $('#expandTableBtn').removeClass('d-none');
                    $('#collapseTableBtn').addClass('d-none');
                    $('#councilTableContainer').removeClass('table-expanded');
                    $('body').css('overflow', '');
                }
            }

            // Xử lý phóng to/thu nhỏ bảng thành viên hội đồng
            $('#expandTableBtn').on('click', function() {
                const tableContainer = $('#councilTableContainer');
                const expandBtn = $('#expandTableBtn');
                const collapseBtn = $('#collapseTableBtn');
                
                // Thay đổi hiển thị nút
                expandBtn.addClass('d-none');
                collapseBtn.removeClass('d-none');
                
                // Thêm CSS tùy chỉnh cho overlay
                if (!$('#expandedTableStyle').length) {
                    $('head').append(`
                        <style id="expandedTableStyle">
                            .table-expanded-overlay {
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100vw;
                                height: 100vh;
                                background: rgba(0,0,0,0.8);
                                z-index: 9998;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                padding: 20px;
                            }
                            
                            .table-expanded-content {
                                background: white;
                                border-radius: 8px;
                                padding: 20px;
                                max-width: 95vw;
                                max-height: 90vh;
                                overflow: auto;
                                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                                z-index: 9999;
                                position: relative;
                            }
                            
                            .table-expanded-content table {
                                font-size: 14px !important;
                                min-width: 800px !important;
                                margin-bottom: 0 !important;
                            }
                            
                            .table-expanded-content .table th,
                            .table-expanded-content .table td {
                                padding: 8px 12px !important;
                                white-space: nowrap !important;
                            }
                            
                            .table-expanded-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-bottom: 15px;
                                padding-bottom: 10px;
                                border-bottom: 2px solid #dee2e6;
                            }
                        </style>
                    `);
                }
                
                // Tạo overlay để hiển thị bảng phóng to
                const tableHTML = tableContainer.html();
                const expandedHTML = `
                    <div class="table-expanded-overlay" id="tableExpandedOverlay">
                        <div class="table-expanded-content">
                            <div class="table-expanded-header">
                                <h5 class="mb-0 text-primary">
                                    <i class="fas fa-users mr-2"></i>Thành viên hội đồng nghiệm thu
                                    <span class="badge badge-primary ml-2">${$('#councilTableContainer tbody tr').length} thành viên</span>
                                </h5>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="$('#tableExpandedOverlay').remove(); $('#expandTableBtn').removeClass('d-none'); $('#collapseTableBtn').addClass('d-none'); $('#councilTableContainer').removeClass('table-expanded'); $('body').css('overflow', '');">
                                    <i class="fas fa-times mr-1"></i> Đóng
                                </button>
                            </div>
                            <div class="table-responsive">
                                ${tableHTML}
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(expandedHTML);
                
                // Ngăn cuộn trang khi overlay mở
                $('body').css('overflow', 'hidden');
            });
            
            $('#collapseTableBtn').on('click', function() {
                $('#tableExpandedOverlay').remove();
                $('#expandTableBtn').removeClass('d-none');
                $('#collapseTableBtn').addClass('d-none');
                // Đảm bảo khôi phục trạng thái bình thường
                $('#councilTableContainer').removeClass('table-expanded');
                $('body').css('overflow', '');
            });
            
            // Đóng overlay khi click bên ngoài
            $(document).on('click', '.table-expanded-overlay', function(e) {
                if (e.target === this) {
                    $(this).remove();
                    $('#expandTableBtn').removeClass('d-none');
                    $('#collapseTableBtn').addClass('d-none');
                    $('#councilTableContainer').removeClass('table-expanded');
                    $('body').css('overflow', '');
                }
            });
            
            // Đóng overlay khi nhấn ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#tableExpandedOverlay').length) {
                    $('#tableExpandedOverlay').remove();
                    $('#expandTableBtn').removeClass('d-none');
                    $('#collapseTableBtn').addClass('d-none');
                    $('#councilTableContainer').removeClass('table-expanded');
                    $('body').css('overflow', '');
                }
            });

            // Xử lý nút nhập điểm đánh giá
            $(document).on('click', '.evaluate-member-btn', function() {
                // Ẩn modal phóng to bảng thành viên nếu đang mở
                closeTableExpandedModal();
                
                const memberId = $(this).data('member-id');
                const memberName = $(this).data('member-name');
                const memberRole = $(this).data('member-role');
                const memberScore = $(this).data('member-score');
                
                $('#score_member_id').val(memberId);
                $('#score_member_name').text(memberName);
                $('#score_member_role').text('Vai trò: ' + memberRole);
                
                // Reset form
                resetCriteriaForm();
                
                // Load điểm đã có (nếu có)
                if (memberScore) {
                    loadExistingScores(memberId);
                }
                
                $('#evaluationScoreModal').modal('show');
            });
            
            // Reset form đánh giá tiêu chí
            function resetCriteriaForm() {
                $('.criteria-score').val('');
                $('textarea[name="criteria_comments[]"]').val('');
                $('#overall_comment').val('');
                updateTotalScore();
            }
            
            // Tính toán điểm tổng khi thay đổi điểm tiêu chí
            $(document).on('input', '.criteria-score', function() {
                const maxScore = parseFloat($(this).data('max'));
                const currentScore = parseFloat($(this).val()) || 0;
                
                // Kiểm tra điểm không vượt quá tối đa
                if (currentScore > maxScore) {
                    $(this).val(maxScore);
                    alert(`Điểm không được vượt quá ${maxScore} cho tiêu chí này!`);
                }
                
                updateTotalScore();
            });
            
            // Cập nhật tổng điểm
            function updateTotalScore() {
                let totalScore = 0;
                $('.criteria-score').each(function() {
                    const score = parseFloat($(this).val()) || 0;
                    totalScore += score;
                });
                
                $('#totalScore').text(totalScore.toFixed(1));
                $('#totalScoreBadge').text(`Tổng: ${totalScore.toFixed(1)}/100 điểm`);
                
                // Cập nhật xếp loại dự kiến
                updateGradePreview(totalScore);
            }
            
            // Cập nhật xếp loại dự kiến
            function updateGradePreview(totalScore) {
                let grade = 'Chưa đánh giá';
                let gradeClass = 'badge-secondary';
                
                if (totalScore >= 90) {
                    grade = 'Xuất sắc';
                    gradeClass = 'badge-success';
                } else if (totalScore >= 80) {
                    grade = 'Tốt';
                    gradeClass = 'badge-primary';
                } else if (totalScore >= 70) {
                    grade = 'Khá';
                    gradeClass = 'badge-info';
                } else if (totalScore >= 50) {
                    grade = 'Đạt';
                    gradeClass = 'badge-warning';
                } else if (totalScore > 0) {
                    grade = 'Không đạt';
                    gradeClass = 'badge-danger';
                }
                
                $('#gradePreview').removeClass('badge-secondary badge-success badge-primary badge-info badge-warning badge-danger')
                                 .addClass(gradeClass)
                                 .text(grade);
            }
            
            // Load điểm đã có từ server
            function loadExistingScores(memberId) {
                const projectId = $('input[name="project_id"]').val();
                
                $.ajax({
                    url: 'get_member_criteria_scores.php',
                    method: 'GET',
                    data: {
                        member_id: memberId,
                        project_id: projectId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.scores) {
                            // Điền điểm vào các tiêu chí
                            $.each(response.scores, function(criteriaId, data) {
                                $(`.criteria-score[data-criteria="${criteriaId}"]`).val(data.score);
                                $(`textarea[name="criteria_comments[]"]`).eq(
                                    $(`.criteria-score[data-criteria="${criteriaId}"]`).closest('tr').index()
                                ).val(data.comment);
                            });
                            
                            // Điền nhận xét tổng quan
                            if (response.overall_comment) {
                                $('#overall_comment').val(response.overall_comment);
                            }
                            
                            updateTotalScore();
                        }
                    },
                    error: function() {
                        console.log('Không thể tải điểm đã có');
                    }
                });
            }
            
            // Xử lý lưu nháp
            $('#saveAsDraftBtn').on('click', function() {
                submitCriteriaForm(false); // false = lưu nháp
            });
            
            // Xử lý form đánh giá theo tiêu chí
            $('#evaluationScoreForm').on('submit', function(e) {
                e.preventDefault();
                submitCriteriaForm(true); // true = hoàn tất
            });
            
            // Submit form đánh giá tiêu chí
            function submitCriteriaForm(isCompleted) {
                const formData = new FormData($('#evaluationScoreForm')[0]);
                formData.append('is_completed', isCompleted ? '1' : '0');
                
                // Kiểm tra ít nhất một tiêu chí có điểm
                let hasScore = false;
                $('.criteria-score').each(function() {
                    if ($(this).val() && parseFloat($(this).val()) > 0) {
                        hasScore = true;
                        return false;
                    }
                });
                
                if (!hasScore) {
                    alert('Vui lòng nhập điểm cho ít nhất một tiêu chí!');
                    return;
                }
                
                // Nếu hoàn tất, kiểm tra tất cả tiêu chí đã có điểm
                if (isCompleted) {
                    let allScored = true;
                    $('.criteria-score').each(function() {
                        if (!$(this).val() || parseFloat($(this).val()) < 0) {
                            allScored = false;
                            return false;
                        }
                    });
                    
                    if (!allScored) {
                        if (!confirm('Một số tiêu chí chưa có điểm. Bạn có muốn hoàn tất đánh giá không?')) {
                            return;
                        }
                    }
                }
                
                $.ajax({
                    url: $('#evaluationScoreForm').attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const message = isCompleted ? 'Hoàn tất đánh giá thành công!' : 'Lưu nháp thành công!';
                            alert(message);
                            $('#evaluationScoreModal').modal('hide');
                            location.reload(); // Reload để cập nhật điểm
                        } else {
                            alert('Lỗi: ' + (response.message || 'Không thể lưu đánh giá'));
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra khi lưu đánh giá!');
                    }
                });
            }
            
            // Xử lý nút upload file đánh giá
            $(document).on('click', '.upload-evaluation-btn', function() {
                // Ẩn modal phóng to bảng thành viên nếu đang mở
                closeTableExpandedModal();
                
                const memberId = $(this).data('member-id');
                const memberName = $(this).data('member-name');
                const memberRole = $(this).data('member-role');
                
                $('#upload_member_id').val(memberId);
                $('#upload_member_name').text(memberName);
                $('#upload_member_role').text('Vai trò: ' + memberRole);
                
                // Tự động điền tên file
                $('#evaluation_file_name').val('File đánh giá của ' + memberName);
                
                $('#uploadEvaluationModal').modal('show');
            });
            
            // Xử lý form upload file
            $('#uploadEvaluationForm').on('submit', function(e) {
                e.preventDefault();
                
                const fileInput = $('#evaluation_file_upload')[0];
                if (!fileInput.files.length) {
                    alert('Vui lòng chọn file để upload!');
                    return;
                }
                
                const file = fileInput.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    alert('File quá lớn! Vui lòng chọn file nhỏ hơn 10MB.');
                    return;
                }
                
                const formData = new FormData(this);
                
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Upload file thành công!');
                            $('#uploadEvaluationModal').modal('hide');
                            location.reload(); // Reload để hiển thị file mới
                        } else {
                            alert('Lỗi: ' + (response.message || 'Không thể upload file'));
                        }
                    },
                    error: function() {
                        alert('Có lỗi xảy ra khi upload file!');
                    }
                });
            });
            
            // Cập nhật label cho custom file input
            $(document).on('change', '.custom-file-input', function() {
                const fileName = $(this)[0].files[0]?.name || 'Chọn file...';
                $(this).next('.custom-file-label').text(fileName);
            });

            console.log('✓ Form validation and features loaded');
        });
    </script>

    <!-- Modal nhập điểm đánh giá theo tiêu chí -->
    <div class="modal fade" id="evaluationScoreModal" tabindex="-1" role="dialog" aria-labelledby="evaluationScoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="evaluationScoreModalLabel">
                        <i class="fas fa-star mr-2"></i>Đánh giá theo tiêu chí
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="evaluationScoreForm" action="update_member_criteria_score.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                        <input type="hidden" name="member_id" id="score_member_id">
                        <input type="hidden" name="decision_id" value="<?php echo htmlspecialchars($decision['QD_SO'] ?? ''); ?>">
                        
                        <div class="form-group">
                            <label for="member_info"><i class="fas fa-user mr-1"></i>Thành viên hội đồng:</label>
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <strong id="score_member_name"></strong><br>
                                    <small class="text-muted" id="score_member_role"></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bảng đánh giá theo tiêu chí -->
                        <div class="criteria-evaluation-section">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-list-ol mr-2"></i>Đánh giá theo từng tiêu chí
                                <span class="badge badge-info ml-2" id="totalScoreBadge">Tổng: 0/100 điểm</span>
                            </h6>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="30%">Tiêu chí</th>
                                            <th width="35%">Mô tả</th>
                                            <th width="10%" class="text-center">Điểm tối đa</th>
                                            <th width="10%" class="text-center">Điểm đánh giá</th>
                                            <th width="10%" class="text-center">Nhận xét</th>
                                        </tr>
                                    </thead>
                                    <tbody id="criteriaTableBody">
                                        <?php foreach ($evaluation_criteria as $index => $criteria): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($criteria['TC_TEN']); ?></strong>
                                                <input type="hidden" name="criteria_id[]" value="<?php echo htmlspecialchars($criteria['TC_MA']); ?>">
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($criteria['TC_MOTA']); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-secondary"><?php echo number_format((float)$criteria['TC_DIEM_TOIDAI'], 1); ?></span>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control form-control-sm criteria-score" 
                                                       name="score[]"
                                                       min="0" 
                                                       max="<?php echo $criteria['TC_DIEM_TOIDAI']; ?>" 
                                                       step="0.1" 
                                                       placeholder="0"
                                                       data-max="<?php echo $criteria['TC_DIEM_TOIDAI']; ?>"
                                                       data-criteria="<?php echo htmlspecialchars($criteria['TC_MA']); ?>">
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm" 
                                                          name="criteria_comments[]" 
                                                          rows="2" 
                                                          placeholder="Nhận xét ngắn..."></textarea>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Tổng kết đánh giá -->
                        <div class="summary-section mt-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-info mb-3">Tổng kết điểm</h6>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>Tổng điểm đạt được:</span>
                                                <strong class="text-primary" id="totalScore">0</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>Tổng điểm tối đa:</span>
                                                <strong class="text-secondary">100</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>Xếp loại dự kiến:</span>
                                                <span class="badge" id="gradePreview">Chưa đánh giá</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="overall_comment">
                                                    <i class="fas fa-comment mr-1"></i>Nhận xét tổng quan
                                                </label>
                                                <textarea class="form-control" id="overall_comment" name="overall_comment" 
                                                          rows="4" placeholder="Nhận xét tổng quan về đề tài..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Hủy
                        </button>
                        <button type="button" class="btn btn-info" id="saveAsDraftBtn">
                            <i class="fas fa-save mr-1"></i>Lưu nháp
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check mr-1"></i>Hoàn tất đánh giá
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal upload file đánh giá -->
    <div class="modal fade" id="uploadEvaluationModal" tabindex="-1" role="dialog" aria-labelledby="uploadEvaluationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadEvaluationModalLabel">
                        <i class="fas fa-upload mr-2"></i>Upload file đánh giá
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="uploadEvaluationForm" action="upload_member_evaluation.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                        <input type="hidden" name="member_id" id="upload_member_id">
                        <input type="hidden" name="decision_id" value="<?php echo htmlspecialchars($decision['QD_SO'] ?? ''); ?>">
                        
                        <div class="form-group">
                            <label for="upload_member_info"><i class="fas fa-user mr-1"></i>Thành viên hội đồng:</label>
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <strong id="upload_member_name"></strong><br>
                                    <small class="text-muted" id="upload_member_role"></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="evaluation_file_name">
                                <i class="fas fa-file-signature mr-1"></i>Tên file đánh giá <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="evaluation_file_name" name="evaluation_file_name" 
                                   placeholder="Nhập tên mô tả cho file đánh giá" required>
                            <small class="form-text text-muted">Ví dụ: "Báo cáo đánh giá của [Tên thành viên]"</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="evaluation_file_upload">
                                <i class="fas fa-file mr-1"></i>File đánh giá <span class="text-danger">*</span>
                            </label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="evaluation_file_upload" 
                                       name="evaluation_file" accept=".pdf,.doc,.docx,.txt,.xls,.xlsx" required>
                                <label class="custom-file-label" for="evaluation_file_upload">Chọn file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Chấp nhận: PDF, DOC, DOCX, TXT, XLS, XLSX (tối đa 10MB)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="file_description">
                                <i class="fas fa-comment mr-1"></i>Mô tả file (tùy chọn)
                            </label>
                            <textarea class="form-control" id="file_description" name="file_description" 
                                      rows="3" placeholder="Mô tả nội dung file đánh giá..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Hủy
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload mr-1"></i>Upload file
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
