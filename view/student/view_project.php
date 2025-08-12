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
    
    try {
        // 1. Kiểm tra file thuyết minh
        $proposal_sql = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND DT_FILEBTM IS NOT NULL AND TRIM(DT_FILEBTM) != ''";
        $stmt = $conn->prepare($proposal_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $required_files['proposal'] = ($result->num_rows > 0);
            $stmt->close();
        }
        
        // 2. Kiểm tra file hợp đồng - chỉ kiểm tra HD_FILEHD (theo cấu trúc bảng thực tế)
        $contract_sql = "SELECT HD_FILEHD FROM hop_dong WHERE DT_MADT = ? AND HD_FILEHD IS NOT NULL AND TRIM(HD_FILEHD) != ''";
        $stmt = $conn->prepare($contract_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $required_files['contract'] = ($result->num_rows > 0);
            $stmt->close();
        }
        
        // 3. Kiểm tra file quyết định và biên bản
        $decision_sql = "SELECT qd.QD_FILE, bb.BB_SOBB 
                        FROM de_tai_nghien_cuu dt
                        INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
                        LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                        WHERE dt.DT_MADT = ?
                        AND qd.QD_FILE IS NOT NULL AND TRIM(qd.QD_FILE) != ''
                        AND bb.BB_SOBB IS NOT NULL";
        $stmt = $conn->prepare($decision_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $required_files['decision'] = ($result->num_rows > 0);
            $stmt->close();
        }
        
        // 4. Kiểm tra file đánh giá: ĐỦ khi có ít nhất 1 file đánh giá từ thành viên hội đồng
        $required_files['evaluation'] = false;
        
        // Lấy QD_SO từ đề tài
        $qd_so = null;
        $qd_sql = "SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND QD_SO IS NOT NULL";
        $stmt = $conn->prepare($qd_sql);
        if ($stmt) {
            $stmt->bind_param("s", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $qd_so = $row['QD_SO'];
            }
            $stmt->close();
        }
        
        if ($qd_so) {
            $file_count1 = 0;
            $completed_evaluations = 0;
            
            // Kiểm tra file đánh giá theo 2 cách:
            // Cách 1: Kiểm tra trong bảng file_dinh_kem với loại member_evaluation
            $eval_sql1 = "SELECT COUNT(*) as cnt FROM file_dinh_kem fd
                          INNER JOIN thanh_vien_hoi_dong tvhd ON fd.GV_MAGV = tvhd.GV_MAGV 
                          WHERE tvhd.QD_SO = ? 
                          AND fd.FDG_LOAI = 'member_evaluation' 
                          AND fd.FDG_FILE IS NOT NULL 
                          AND TRIM(fd.FDG_FILE) != ''";
            
            $stmt = $conn->prepare($eval_sql1);
            if ($stmt) {
                $stmt->bind_param("s", $qd_so);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $file_count1 = $row['cnt'] ?? 0;
                $stmt->close();
            }
            
            // Cách 2: Kiểm tra trạng thái đánh giá của thành viên hội đồng
            $eval_sql2 = "SELECT COUNT(*) as cnt FROM thanh_vien_hoi_dong 
                          WHERE QD_SO = ? 
                          AND (TV_TRANGTHAI = 'Đã hoàn thành' OR TV_DIEM IS NOT NULL)";
            
            $stmt = $conn->prepare($eval_sql2);
            if ($stmt) {
                $stmt->bind_param("s", $qd_so);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $completed_evaluations = $row['cnt'] ?? 0;
                $stmt->close();
            }
            
            // Đánh giá được coi là đầy đủ nếu có ít nhất 1 file đánh giá HOẶC có thành viên đã hoàn thành đánh giá
            $required_files['evaluation'] = ($file_count1 > 0 || $completed_evaluations > 0);
        }
        
    } catch (Exception $e) {
        // Log error nếu cần thiết
        error_log("Error in checkProjectCompleteness: " . $e->getMessage());
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

// Lấy thời hạn thực hiện (tháng) từ ghi chú đề tài nếu có
$duration_months = 6;
if (!empty($project['DT_GHICHU'])) {
    if (preg_match('/duration_months\s*=\s*(\d+)/i', $project['DT_GHICHU'], $matches)) {
        $duration_months = max(1, intval($matches[1]));
    }
}

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
    $eval_files_sql = "SELECT * FROM file_dinh_kem WHERE BB_SOBB = ? AND FDG_LOAI = 'member_evaluation'";
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

// Lấy danh sách file đánh giá của các thành viên hội đồng CHỈ CỦA ĐỀ TÀI HIỆN TẠI
$member_evaluation_files = [];
if (!empty($council_members) && !empty($project['QD_SO'])) {
    try {
        $stmt = $conn->prepare("
            SELECT fd.FDG_TENFILE as FDK_TEN, fd.FDG_FILE as FDK_DUONGDAN, fd.GV_MAGV as FDK_MEMBER_ID, 
                   fd.FDG_MOTA as FDK_MOTA, fd.FDG_NGAYTAO as FDK_NGAYTAO 
            FROM file_dinh_kem fd
            INNER JOIN thanh_vien_hoi_dong tvhd ON fd.GV_MAGV = tvhd.GV_MAGV
            WHERE fd.FDG_LOAI = 'member_evaluation' 
            AND fd.GV_MAGV IS NOT NULL
            AND tvhd.QD_SO = ?
            AND fd.FDG_FILE IS NOT NULL 
            AND TRIM(fd.FDG_FILE) != ''
        ");
        if ($stmt) {
            $stmt->bind_param("s", $project['QD_SO']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($file = $result->fetch_assoc()) {
                $member_evaluation_files[] = $file;
            }
            $stmt->close();
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
            border-radius: 20px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 15px 50px rgba(102, 126, 234, 0.2);
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
        
        .header-content {
            display: flex;
            align-items: stretch;
            position: relative;
            z-index: 2;
            padding: 40px;
        }
        
        .project-main-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .project-title-section {
            margin-bottom: 10px;
        }
        
        .project-title {
            font-weight: 800;
            font-size: 2.4rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            line-height: 1.2;
            letter-spacing: -0.5px;
        }
        
        .project-meta {
            display: flex;
            gap: 25px;
            align-items: center;
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .project-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .project-meta i {
            font-size: 0.9rem;
        }
        
        .project-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .detail-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 1.2rem;
        }
        
        .detail-content {
            flex: 1;
        }
        
        .detail-label {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .project-status-sidebar {
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            padding-left: 30px;
        }
        
        .status-container {
            text-align: center;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 28px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1rem;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .status-badge i {
            font-size: 1.2rem;
        }
        
        .status-badge.status-warning {
            color: #f39c12;
        }
        
        .status-badge.status-primary {
            color: #3498db;
        }
        
        .status-badge.status-success {
            color: #27ae60;
        }
        
        .status-badge.status-info {
            color: #17a2b8;
        }
        
        .status-badge.status-danger {
            color: #e74c3c;
        }
        
        .completion-indicators {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 25px;
            border: 2px solid #e9ecef;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            margin: 20px 0;
        }
        
        .completion-indicators:hover {
            border-color: #007bff;
            box-shadow: 0 15px 40px rgba(0, 123, 255, 0.15);
            transform: translateY(-3px);
        }
        
        /* CSS cho dropdown file đánh giá thành viên */
        .dropdown-menu {
            max-width: 350px;
            min-width: 280px;
        }
        
        .dropdown-header {
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
            padding: 8px 16px;
            margin: 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .dropdown-item {
            padding: 10px 16px;
            border: none;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);
        }
        
        .dropdown-item .font-weight-medium {
            font-weight: 500;
            color: #495057;
        }
        
        .dropdown-item small.text-muted {
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .dropdown-divider {
            margin: 0.25rem 0;
        }
        
        /* CSS cho thống kê cấu trúc hội đồng */
        .bg-light-success {
            background-color: #d4edda !important;
        }
        
        .bg-light-warning {
            background-color: #fff3cd !important;
        }
        
        .border-success {
            border-color: #28a745 !important;
        }
        
        .border-warning {
            border-color: #ffc107 !important;
        }
        
        .badge-sm {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
        
        .completion-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            color: #333;
            border-bottom: 2px solid #f1f3f4;
            padding-bottom: 10px;
        }
        
        .completion-title i {
            color: #007bff;
            font-size: 1.2em;
            margin-right: 10px;
        }
        
        .completion-title small {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em !important;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }
        
        .file-indicators {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .file-indicator {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            padding: 15px 18px;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: help;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .file-indicator::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .file-indicator:hover::before {
            left: 100%;
        }
        
        .file-indicator:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .file-indicator i {
            width: 24px;
            height: 24px;
            text-align: center;
            line-height: 24px;
            border-radius: 50%;
            transition: all 0.3s ease;
            font-size: 1.1em;
        }
        
        .file-indicator.completed {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-color: #28a745;
        }
        
        .file-indicator.completed i {
            background: #28a745;
            color: white;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }
        
        .file-indicator.completed:hover {
            border-color: #1e7e34;
            background: linear-gradient(135deg, #c3e6cb 0%, #b1dfbb 100%);
        }
        
        .file-indicator.completed:hover i {
            transform: scale(1.1) rotate(360deg);
            box-shadow: 0 0 0 5px rgba(40, 167, 69, 0.3);
        }
        
        .file-indicator.pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-color: #ffc107; /* was #c29306ff */
        }
        
        .file-indicator.pending i {
            background: #ffc107;
            color: #856404;
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.2);
        }
        
        .file-indicator.pending:hover {
            border-color: #e0a800;
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
        }
        
        .file-indicator.pending:hover i {
            transform: scale(1.1);
            box-shadow: 0 0 0 5px rgba(255, 193, 7, 0.3);
        }

        /* Remove dark-theme text override; keep readable text on light yellow */
        .file-indicator.pending {
            color: #856404; /* was rgba(255, 255, 255, 0.6) */
        }
        
        .file-indicator.pending:hover {
            color: #5c4403; /* was rgba(255, 255, 255, 0.8) */
        }
        
        .completion-summary {
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        
        .completion-summary .progress {
            height: 12px;
            margin: 10px 0;
            overflow: hidden;
            border-radius: 6px;
            background: #e9ecef;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .completion-summary .progress-bar {
            background: linear-gradient(45deg, #28a745, #20c997, #17a2b8);
            background-size: 40px 40px;
            animation: progress-stripe 1s linear infinite;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        @keyframes progress-stripe {
            0% { background-position: 0 0; }
            100% { background-position: 40px 0; }
        }
        
        .completion-summary {
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .completion-summary .progress {
            margin: 5px 0;
            overflow: hidden;
        }
        
        .header-actions {
            display: flex;
            justify-content: center;
        }
        
        .header-actions .btn {
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .header-actions .btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
                margin-bottom: 20px;
            }
            
            .header-content {
                flex-direction: column;
                padding: 30px 25px;
                gap: 25px;
            }
            
            .project-status-sidebar {
                width: 100%;
                padding-left: 0;
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
            }
            
            .project-details-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .project-title {
                font-size: 2rem;
            }
            
            .project-meta {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
                border-radius: 15px;
            }
            
            .header-content {
                padding: 25px 20px;
            }
            
            .project-title {
                font-size: 1.8rem;
                line-height: 1.3;
            }
            
            .project-meta span {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
            
            .detail-item {
                padding: 12px 15px;
            }
            
            .detail-icon {
                width: 38px;
                height: 38px;
                font-size: 1rem;
            }
            
            .status-badge {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .completion-indicators {
                padding: 20px 15px;
                margin: 15px 0;
                border-radius: 15px;
            }
            
            .completion-title {
                font-size: 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 15px;
            }
            
            .file-indicators {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .file-indicator {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .file-indicator i {
                width: 20px;
                height: 20px;
                line-height: 20px;
                font-size: 1rem;
            }
            
            .completion-summary {
                padding: 15px;
            }
            
            .project-status-sidebar {
                flex-direction: column;
                gap: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .header-content {
                padding: 20px 15px;
            }
            
            .project-title {
                font-size: 1.6rem;
            }
            
            .project-meta {
                gap: 10px;
            }
            
            .project-meta span {
                padding: 4px 10px;
                font-size: 0.85rem;
            }
            
            .detail-item {
                padding: 10px 12px;
                gap: 10px;
            }
            
            .detail-icon {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .detail-label {
                font-size: 0.8rem;
            }
            
            .detail-value {
                font-size: 0.9rem;
            }
            
            .status-badge {
                padding: 10px 16px;
                font-size: 0.9rem;
            }
            
            .completion-indicators {
                padding: 15px 12px;
                margin: 10px 0;
            }
            
            .completion-title {
                font-size: 0.95rem;
                margin-bottom: 12px;
            }
            
            .completion-title i {
                font-size: 1.1em;
            }
            
            .completion-title small {
                padding: 2px 6px;
                font-size: 0.7em !important;
            }
            
            .file-indicator {
                padding: 10px 12px;
                font-size: 0.85rem;
                gap: 10px;
            }
            
            .file-indicator i {
                width: 18px;
                height: 18px;
                line-height: 18px;
                font-size: 0.9rem;
            }
            
            .completion-summary {
                padding: 12px;
            }
            
            .completion-summary .progress {
                height: 10px;
            }
            
            .header-actions .btn {
                padding: 10px 18px;
                font-size: 0.9rem;
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
            border-radius: 12px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.8) 0%, rgba(233, 236, 239, 0.5) 100%);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .selected-council-members:not(:empty) {
            border: 2px solid rgba(0, 123, 255, 0.3);
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(102, 126, 234, 0.08) 100%);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.1);
        }
        
        .selected-council-members.empty-state {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
        }
        
        .selected-council-members.empty-state::before {
            content: "\f0c0";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 1.5rem;
            margin-right: 10px;
            opacity: 0.5;
        }
        
        /* Styled member cards */
        .member-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 249, 250, 0.8) 100%);
            border: 1px solid rgba(0, 123, 255, 0.2);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 12px;
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .member-card:last-child {
            margin-bottom: 0;
        }
        
        .member-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #007bff, #0056b3);
            border-radius: 0 4px 4px 0;
        }
        
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
            border-color: rgba(0, 123, 255, 0.4);
        }
        
        .member-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .member-details {
            flex: 1;
            min-width: 250px;
        }
        
        .member-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            font-size: 1.05rem;
        }
        
        .member-role {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 3px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .member-role.role-chairman {
            background: linear-gradient(135deg, #dc3545, #c82333);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        
        .member-role.role-secretary {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .member-role.role-member {
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
            box-shadow: 0 2px 8px rgba(111, 66, 193, 0.3);
        }
        
        .member-role.role-reviewer {
            background: linear-gradient(135deg, #fd7e14, #e85d04);
            box-shadow: 0 2px 8px rgba(253, 126, 20, 0.3);
        }
        
        /* Role Statistics Styles */
        .role-statistics {
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(102, 126, 234, 0.08) 100%);
            border: 1px solid rgba(0, 123, 255, 0.2);
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
        }
        
        .stats-header {
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(0, 123, 255, 0.1);
        }
        
        .stats-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        
        .role-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .role-stat.complete {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .role-stat.incomplete {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .role-name {
            font-weight: 600;
        }
        
        .role-count {
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        /* Disabled role options */
        #memberRole option.role-full {
            color: #6c757d;
            font-style: italic;
        }
        
        #memberRole option:disabled {
            background-color: #f8f9fa !important;
        }
        
        /* Modal improvements */
        .modal-body .alert-info {
            margin-bottom: 15px;
        }
        
        .member-department {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 4px;
            display: flex;
            align-items: center;
        }
        
        .member-department::before {
            content: "\f19c";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 6px;
            opacity: 0.7;
        }
        
        .remove-member-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            font-size: 0.9rem;
        }
        
        .remove-member-btn:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .members-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0, 123, 255, 0.1);
        }
        
        .members-header h6 {
            margin: 0;
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .members-header .fas {
            color: #007bff;
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .members-count {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: auto;
            box-shadow: 0 2px 6px rgba(40, 167, 69, 0.3);
            animation: fadeInScale 0.3s ease-out;
        }
        
        /* Animations */
        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes slideInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .member-card {
            animation: slideInUp 0.3s ease-out;
        }
        
        .member-card:nth-child(2) { animation-delay: 0.1s; }
        .member-card:nth-child(3) { animation-delay: 0.2s; }
        .member-card:nth-child(4) { animation-delay: 0.3s; }
        .member-card:nth-child(5) { animation-delay: 0.4s; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .member-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .member-details {
                min-width: 100%;
                margin-bottom: 8px;
            }
            
            .remove-member-btn {
                align-self: flex-end;
                width: auto;
            }
            
            .members-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .members-count {
                margin-left: 0;
            }
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
            <div class="header-content">
                <div class="project-main-info">
                    <div class="project-title-section">
                        <h1 class="project-title"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h1>
                        <div class="project-meta">
                            <span class="project-code">
                                <i class="fas fa-hashtag"></i>
                                <?php echo htmlspecialchars($project['DT_MADT']); ?>
                            </span>
                            <span class="project-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo formatDate($project['DT_NGAYTAO']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="project-details-grid">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-calendar-check text-primary"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Thời gian thực hiện</div>
                                <div class="detail-value"><?php echo formatDate($project['HD_NGAYBD']) . ' - ' . formatDate($project['HD_NGAYKT']); ?></div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-tag text-info"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Loại đề tài</div>
                                <div class="detail-value"><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không xác định'); ?></div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-microscope text-success"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Lĩnh vực nghiên cứu</div>
                                <div class="detail-value"><?php echo htmlspecialchars($project['LVNC_TEN'] ?? 'Không xác định'); ?></div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-user-tie text-warning"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">GVHD</div>
                                <div class="detail-value"><?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Chưa có'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="project-status-sidebar">
                    <!-- Trạng thái đề tài -->
                    <div class="status-container">
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
                        <div class="status-badge status-<?php echo $status_class; ?>">
                            <i class="fas fa-<?php echo $status_icon; ?>"></i>
                            <span><?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?></span>
                        </div>
                    </div>
                    
                
                    <?php
                    // Refresh file completeness để đảm bảo dữ liệu mới nhất và thêm timestamp
                    $file_completeness = checkProjectCompleteness($project_id, $conn);
                    $check_time = date('H:i:s');

                    // Chuẩn bị nguồn (source) cho từng mục
                    $proposal_source_label = $file_completeness['proposal'] ? 'DT_FILEBTM' : 'Chưa có';

                    // Hợp đồng: chỉ kiểm tra HD_FILEHD (theo cấu trúc bảng thực tế)
                    $contract_source_label = 'Chưa có';
                    $contract_notes = [];
                    if (!empty($project['DT_MADT'])) {
                        $madetai_esc = mysqli_real_escape_string($conn, $project['DT_MADT']);
                        $sql_contract = "SELECT TRIM(COALESCE(HD_FILEHD,'')) AS HD_FILEHD FROM hop_dong WHERE DT_MADT='$madetai_esc' LIMIT 1";
                        if ($res_c = mysqli_query($conn, $sql_contract)) {
                            $row_c = mysqli_fetch_assoc($res_c);
                            $has_hd_filehd = !empty($row_c['HD_FILEHD']);
                            if ($has_hd_filehd) {
                                $contract_source_label = 'HD_FILEHD';
                                $contract_notes[] = 'Có HD_FILEHD: ' . $row_c['HD_FILEHD'];
                            } else {
                                $contract_notes[] = 'Thiếu HD_FILEHD';
                            }
                        }
                    }

                    // Quyết định & Biên bản: cần QD_FILE + BB_SOBB
                    $decision_source_label = 'Chưa có';
                    $decision_notes = [];
                    $qd_file_ok = false; $bb_ok = false; $bb_sobb_val = '';
                    if (!empty($project['QD_SO'])) {
                        $qdso_esc = mysqli_real_escape_string($conn, $project['QD_SO']);
                        $sql_qd = "SELECT TRIM(COALESCE(QD_FILE,'')) AS QD_FILE FROM quyet_dinh_nghiem_thu WHERE QD_SO='$qdso_esc' LIMIT 1";
                        if ($res_qd = mysqli_query($conn, $sql_qd)) {
                            $row_qd = mysqli_fetch_assoc($res_qd);
                            $qd_file_ok = !empty($row_qd['QD_FILE']);
                        }
                        $sql_bb = "SELECT TRIM(COALESCE(BB_SOBB,'')) AS BB_SOBB FROM bien_ban WHERE QD_SO='$qdso_esc' LIMIT 1";
                        if ($res_bb = mysqli_query($conn, $sql_bb)) {
                            $row_bb = mysqli_fetch_assoc($res_bb);
                            $bb_sobb_val = $row_bb['BB_SOBB'];
                            $bb_ok = !empty($bb_sobb_val);
                        }
                        if ($qd_file_ok && $bb_ok) { $decision_source_label = 'QD_FILE + BB_SOBB'; }
                        $decision_notes[] = $qd_file_ok ? 'Có QD_FILE' : 'Thiếu QD_FILE';
                        $decision_notes[] = $bb_ok ? ("Có BB_SOBB: $bb_sobb_val") : 'Thiếu BB_SOBB';
                    }

                    // Đánh giá: ưu tiên qua Biên bản (BB_SOBB) rồi fallback theo DT_MADT
                    $evaluation_source_label = 'Chưa có';
                    $evaluation_notes = [];
                    $eval_chain_cnt = 0; $eval_direct_cnt = 0;
                    if (!empty($bb_sobb_val)) {
                        $bb_sobb_esc = mysqli_real_escape_string($conn, $bb_sobb_val);
                        $sql_eval_chain = "SELECT COUNT(*) AS n FROM file_danh_gia WHERE BB_SOBB='$bb_sobb_esc' AND (TRIM(COALESCE(FDG_FILE,''))<>'' OR TRIM(COALESCE(FDG_DUONGDAN,''))<>'')";
                        if ($res_ec = mysqli_query($conn, $sql_eval_chain)) { $eval_chain_cnt = (int)mysqli_fetch_assoc($res_ec)['n']; }
                    }
                    if (!empty($project['DT_MADT'])) {
                        $madetai_esc = mysqli_real_escape_string($conn, $project['DT_MADT']);
                        $sql_eval_direct = "SELECT COUNT(*) AS n FROM file_danh_gia WHERE DT_MADT='$madetai_esc' AND (TRIM(COALESCE(FDG_FILE,''))<>'' OR TRIM(COALESCE(FDG_DUONGDAN,''))<>'')";
                        if ($res_ed = mysqli_query($conn, $sql_eval_direct)) { $eval_direct_cnt = (int)mysqli_fetch_assoc($res_ed)['n']; }
                    }
                    if ($eval_chain_cnt > 0) {
                        $evaluation_source_label = 'Qua Biên bản';
                        $evaluation_notes[] = "Số file theo BB_SOBB: $eval_chain_cnt";
                    } elseif ($eval_direct_cnt > 0) {
                        $evaluation_source_label = 'Theo DT_MADT';
                        $evaluation_notes[] = "Số file theo DT_MADT: $eval_direct_cnt";
                    } else {
                        $evaluation_notes[] = 'Chưa tìm thấy file đánh giá';
                    }

                    $total_files = 4;
                    $completed_files = array_sum($file_completeness);
                    $completion_percentage = round(($completed_files / $total_files) * 100);
                    ?>

                    <!-- Trạng thái tài liệu -->
                    <div class="document-status-card no-print" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border: 1px solid #dee2e6; border-radius: 10px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0" style="color: #495057; font-weight: 600;">
                                <i class="fas fa-folder-open text-primary mr-2"></i>Trạng thái tài liệu
                            </h6>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <i class="far fa-clock mr-1"></i><?php echo $check_time; ?>
                            </small>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <!-- Thuyết minh -->
                            <div class="col-6">
                                <div class="d-flex align-items-center p-2 rounded" style="background: <?php echo !empty($file_completeness['proposal']) ? '#e8f5e8' : '#fff3cd'; ?>; border: 1px solid <?php echo !empty($file_completeness['proposal']) ? '#c3e6cb' : '#ffeaa7'; ?>;">
                                    <i class="fas fa-file-alt <?php echo !empty($file_completeness['proposal']) ? 'text-success' : 'text-warning'; ?> mr-2" style="font-size: 0.9rem;"></i>
                                    <div style="font-size: 0.8rem;">
                                        <div style="font-weight: 500; color: #495057;">Thuyết minh</div>
                                        <div style="color: <?php echo !empty($file_completeness['proposal']) ? '#28a745' : '#856404'; ?>; font-weight: 600;">
                                            <?php echo !empty($file_completeness['proposal']) ? 'Đã có' : 'Thiếu'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hợp đồng -->
                            <div class="col-6">
                                <div class="d-flex align-items-center p-2 rounded" style="background: <?php echo !empty($file_completeness['contract']) ? '#e8f5e8' : '#fff3cd'; ?>; border: 1px solid <?php echo !empty($file_completeness['contract']) ? '#c3e6cb' : '#ffeaa7'; ?>;">
                                    <i class="fas fa-file-signature <?php echo !empty($file_completeness['contract']) ? 'text-success' : 'text-warning'; ?> mr-2" style="font-size: 0.9rem;"></i>
                                    <div style="font-size: 0.8rem;">
                                        <div style="font-weight: 500; color: #495057;">Hợp đồng</div>
                                        <div style="color: <?php echo !empty($file_completeness['contract']) ? '#28a745' : '#856404'; ?>; font-weight: 600;">
                                            <?php echo !empty($file_completeness['contract']) ? 'Đã có' : 'Thiếu'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quyết định & Biên bản -->
                            <div class="col-6">
                                <div class="d-flex align-items-center p-2 rounded" style="background: <?php echo !empty($file_completeness['decision']) ? '#e8f5e8' : '#fff3cd'; ?>; border: 1px solid <?php echo !empty($file_completeness['decision']) ? '#c3e6cb' : '#ffeaa7'; ?>;">
                                    <i class="fas fa-stamp <?php echo !empty($file_completeness['decision']) ? 'text-success' : 'text-warning'; ?> mr-2" style="font-size: 0.9rem;"></i>
                                    <div style="font-size: 0.8rem;">
                                        <div style="font-weight: 500; color: #495057;">QĐ & BB</div>
                                        <div style="color: <?php echo !empty($file_completeness['decision']) ? '#28a745' : '#856404'; ?>; font-weight: 600;">
                                            <?php echo !empty($file_completeness['decision']) ? 'Đã có' : 'Thiếu'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Đánh giá -->
                            <div class="col-6">
                                <div class="d-flex align-items-center p-2 rounded" style="background: <?php echo !empty($file_completeness['evaluation']) ? '#e8f5e8' : '#fff3cd'; ?>; border: 1px solid <?php echo !empty($file_completeness['evaluation']) ? '#c3e6cb' : '#ffeaa7'; ?>;">
                                    <i class="fas fa-clipboard-check <?php echo !empty($file_completeness['evaluation']) ? 'text-success' : 'text-warning'; ?> mr-2" style="font-size: 0.9rem;"></i>
                                    <div style="font-size: 0.8rem;">
                                        <div style="font-weight: 500; color: #495057;">Đánh giá</div>
                                        <div style="color: <?php echo !empty($file_completeness['evaluation']) ? '#28a745' : '#856404'; ?>; font-weight: 600;">
                                            <?php echo !empty($file_completeness['evaluation']) ? 'Đã có' : 'Thiếu'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thanh tiến độ tổng quan -->
                        <div class="d-flex align-items-center justify-content-between">
                            <span style="font-size: 0.8rem; color: #6c757d; font-weight: 500;">
                                Hoàn thành: <?php echo $completed_files; ?>/<?php echo $total_files; ?>
                            </span>
                            <span style="font-size: 0.85rem; font-weight: 600; color: <?php echo $completion_percentage >= 75 ? '#28a745' : ($completion_percentage >= 50 ? '#ffc107' : '#dc3545'); ?>;">
                                <?php echo $completion_percentage; ?>%
                            </span>
                        </div>
                        <div class="progress mt-2" style="height: 4px; background-color: #e9ecef;">
                            <div class="progress-bar" style="background: linear-gradient(90deg, <?php echo $completion_percentage >= 75 ? '#28a745' : ($completion_percentage >= 50 ? '#ffc107' : '#dc3545'); ?>, <?php echo $completion_percentage >= 75 ? '#20c997' : ($completion_percentage >= 50 ? '#ffca2c' : '#e74c3c'); ?>); width: <?php echo $completion_percentage; ?>%;" 
                                 role="progressbar" aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                 
                    
                    <!-- Action button -->
                    <div class="header-actions">
                        <button class="btn btn-outline-primary no-print" id="printProjectBtn">
                            <i class="fas fa-print"></i>
                            <span>In báo cáo</span>
                        </button>
                    </div>
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

                        <?php if (!empty($project['DT_FILEBTM'])): ?>
                            <hr>
                            <div class="mt-3">
                                <h5 class="section-title"><i class="fas fa-file-alt"></i> File thuyết minh</h5>
                                <?php 
                                    $dtFile = $project['DT_FILEBTM'] ?? '';
                                    $proposalHref = '';
                                    if ($dtFile) {
                                        if (strpos($dtFile, '/') !== false || strpos($dtFile, '\\') !== false) {
                                            $webPath = preg_replace('#^\.\./\.\./#', '', str_replace('\\\\','/',$dtFile));
                                            $proposalHref = '/NLNganh/' . ltrim($webPath, '/');
                                        } else {
                                            $proposalHref = '/NLNganh/uploads/project_files/' . $dtFile;
                                        }
                                    }
                                ?>
                                <a href="<?php echo htmlspecialchars($proposalHref); ?>"
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
                        <?php if ($has_access && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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
                                <?php if (!empty($project['DT_FILEBTM'])): ?>
                                    <div class="proposal-file-current">
                                        <h6 class="text-primary mb-3"><i class="fas fa-file-alt mr-2"></i>File thuyết minh hiện tại</h6>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="far fa-file-pdf file-icon text-danger mr-2"></i>
                                                <span class="font-weight-medium"><?php echo htmlspecialchars($project['DT_FILEBTM']); ?></span>
                                            </div>
                                            <?php 
                                                $dtFile2 = $project['DT_FILEBTM'] ?? '';
                                                $proposalHref2 = '';
                                                if ($dtFile2) {
                                                    if (strpos($dtFile2, '/') !== false || strpos($dtFile2, '\\') !== false) {
                                                        $webPath2 = preg_replace('#^\.\./\.\./#', '', str_replace('\\\\','/',$dtFile2));
                                                        $proposalHref2 = '/NLNganh/' . ltrim($webPath2, '/');
                                                    } else {
                                                        $proposalHref2 = '/NLNganh/uploads/project_files/' . $dtFile2;
                                                    }
                                                }
                                            ?>
                                            <a href="<?php echo htmlspecialchars($proposalHref2); ?>"
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
                                        
                                        <hr>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="mt-4 mb-2"><i class="fas fa-history mr-2"></i>Lịch sử file thuyết minh</h6>
                                            <button type="button" id="btnExpandProposalHistory" class="btn btn-sm btn-outline-secondary mt-4 mb-2">
                                                <i class="fas fa-expand mr-1"></i> Phóng to
                                            </button>
                                        </div>
                                        <?php
                                            $hist = [];
                                            $hstmt = $conn->prepare("SELECT * FROM lich_su_thuyet_minh WHERE DT_MADT = ? ORDER BY NGAY_TAI DESC, ID DESC");
                                            if ($hstmt) {
                                                $hstmt->bind_param("s", $project_id);
                                                if ($hstmt->execute()) {
                                                    $hres = $hstmt->get_result();
                                                    while ($row = $hres->fetch_assoc()) { $hist[] = $row; }
                                                }
                                            }
                                            // Nếu chưa có lịch sử nhưng đã có file hiện tại từ khi đăng ký, hiển thị như một bản ghi lịch sử
                                            if (empty($hist) && !empty($project['DT_FILEBTM'])) {
                                                $hist[] = [
                                                    'FILE_TEN' => $project['DT_FILEBTM'],
                                                    'FILE_KICHTHUOC' => null,
                                                    'FILE_LOAI' => null,
                                                    'LY_DO' => 'File thuyết minh khi đăng ký đề tài',
                                                    'NGUOI_TAI' => null,
                                                    'NGAY_TAI' => $project['DT_NGAYTAO'] ?? date('Y-m-d H:i:s'),
                                                    'LA_HIEN_TAI' => 1
                                                ];
                                            }
                                        ?>
                                        <?php if (!empty($hist)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Tên file</th>
                                                            <th>Kích thước</th>
                                                            <th>Loại</th>
                                                            <th>Lý do</th>
                                                            <th>Người tải</th>
                                                            <th>Thời gian</th>
                                                            <th>Trạng thái</th>
                                                            <th>Tải</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($hist as $index => $h): ?>
                                                            <tr>
                                                                <td><?php echo $index + 1; ?></td>
                                                                <td><?php echo htmlspecialchars($h['FILE_TEN']); ?></td>
                                                                <td><?php echo isset($h['FILE_KICHTHUOC']) && is_numeric($h['FILE_KICHTHUOC']) ? number_format((float)$h['FILE_KICHTHUOC']) . ' bytes' : '—'; ?></td>
                                                                <td><?php echo htmlspecialchars($h['FILE_LOAI'] ?? '—'); ?></td>
                                                                <td style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($h['LY_DO'] ?? ''); ?>"><?php echo htmlspecialchars($h['LY_DO'] ?? ''); ?></td>
                                                                <td><?php echo htmlspecialchars($h['NGUOI_TAI'] ?? ''); ?></td>
                                                                <td><?php echo date('d/m/Y H:i', strtotime($h['NGAY_TAI'])); ?></td>
                                                                <td><?php echo $h['LA_HIEN_TAI'] ? '<span class="badge badge-success">Hiện tại</span>' : '<span class="badge badge-secondary">Lịch sử</span>'; ?></td>
                                                                <td>
                                                                    <?php 
                                                                        $histFile = $h['FILE_TEN'] ?? '';
                                                                        $histHref = '';
                                                                        if ($histFile) {
                                                                            if (strpos($histFile, '/') !== false || strpos($histFile, '\\') !== false) {
                                                                                $histWeb = preg_replace('#^\.\./\.\./#', '', str_replace('\\\\','/',$histFile));
                                                                                $histHref = '/NLNganh/' . ltrim($histWeb, '/');
                                                                            } else {
                                                                                $histHref = '/NLNganh/uploads/project_files/' . $histFile;
                                                                            }
                                                                        }
                                                                    ?>
                                                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($histHref); ?>" download>
                                                                        <i class="fas fa-download"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-light border">Chưa có lịch sử file thuyết minh.</div>
                                        <?php endif; ?>
                                        
                                        <!-- Overlay phóng to lịch sử (không dùng Bootstrap modal) -->
                                        <div id="proposalHistoryOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:20000;align-items:center;justify-content:center;padding:2vh 2.5vw;">
                                            <div id="proposalHistoryPanel" style="width:95vw;height:92vh;background:#fff;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.2);display:flex;flex-direction:column;">
                                                <div style="padding:12px 16px;border-bottom:1px solid #e9ecef;display:flex;justify-content:space-between;align-items:center;">
                                                    <h5 class="m-0"><i class="fas fa-history mr-2"></i>Lịch sử file thuyết minh</h5>
                                                    <button type="button" id="btnCloseProposalHistory" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times mr-1"></i> Đóng</button>
                                                </div>
                                                <div style="padding:12px 16px;overflow:auto;flex:1;">
                                                    <?php if (!empty($hist)): ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-hover">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Tên file</th>
                                                                        <th>Kích thước</th>
                                                                        <th>Loại</th>
                                                                        <th>Lý do</th>
                                                                        <th>Người tải</th>
                                                                        <th>Thời gian</th>
                                                                        <th>Trạng thái</th>
                                                                        <th>Tải</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($hist as $index => $h): ?>
                                                                        <tr>
                                                                            <td><?php echo $index + 1; ?></td>
                                                                            <td><?php echo htmlspecialchars($h['FILE_TEN']); ?></td>
                                                                            <td><?php echo isset($h['FILE_KICHTHUOC']) && is_numeric($h['FILE_KICHTHUOC']) ? number_format((float)$h['FILE_KICHTHUOC']) . ' bytes' : '—'; ?></td>
                                                                            <td><?php echo htmlspecialchars($h['FILE_LOAI'] ?? '—'); ?></td>
                                                                            <td style="max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo htmlspecialchars($h['LY_DO'] ?? ''); ?>"><?php echo htmlspecialchars($h['LY_DO'] ?? ''); ?></td>
                                                                            <td><?php echo htmlspecialchars($h['NGUOI_TAI'] ?? ''); ?></td>
                                                                            <td><?php echo date('d/m/Y H:i', strtotime($h['NGAY_TAI'])); ?></td>
                                                                            <td><?php echo $h['LA_HIEN_TAI'] ? '<span class="badge badge-success">Hiện tại</span>' : '<span class="badge badge-secondary">Lịch sử</span>'; ?></td>
                                                                            <td>
                                                                                <?php 
                                                                                    $histFileM = $h['FILE_TEN'] ?? '';
                                                                                    $histHrefM = '';
                                                                                    if ($histFileM) {
                                                                                        if (strpos($histFileM, '/') !== false || strpos($histFileM, '\\') !== false) {
                                                                                            $histWebM = preg_replace('#^\.\./\.\./#', '', str_replace('\\\\','/',$histFileM));
                                                                                            $histHrefM = '/NLNganh/' . ltrim($histWebM, '/');
                                                                                        } else {
                                                                                            $histHrefM = '/NLNganh/uploads/project_files/' . $histFileM;
                                                                                        }
                                                                                    }
                                                                                ?>
                                                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($histHrefM); ?>" download>
                                                                                    <i class="fas fa-download"></i>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-light border">Chưa có lịch sử file thuyết minh.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                            (function(){
                                                var overlay = document.getElementById('proposalHistoryOverlay');
                                                // Di chuyển overlay ra cuối body để tránh bị giới hạn trong card-body
                                                if (overlay && overlay.parentNode !== document.body) {
                                                    document.body.appendChild(overlay);
                                                }
                                                var panel = document.getElementById('proposalHistoryPanel');
                                                var btnOpen = document.getElementById('btnExpandProposalHistory');
                                                var btnClose = document.getElementById('btnCloseProposalHistory');
                                                function openOverlay(){
                                                    overlay.style.display = 'block';
                                                    overlay.style.display = 'flex';
                                                    document.body.style.overflow = 'hidden';
                                                }
                                                function closeOverlay(){
                                                    overlay.style.display = 'none';
                                                    document.body.style.overflow = '';
                                                }
                                                if (btnOpen) btnOpen.addEventListener('click', function(e){ e.preventDefault(); openOverlay(); });
                                                if (btnClose) btnClose.addEventListener('click', function(e){ e.preventDefault(); closeOverlay(); });
                                                if (overlay) overlay.addEventListener('click', function(e){
                                                    if (!panel.contains(e.target)) {
                                                        closeOverlay();
                                                    }
                                                });
                                                // Đảm bảo overlay không tự mở khi các form khác submit hoặc DOM thay đổi
                                                // Chỉ mở khi click đúng vào nút #btnExpandProposalHistory
                                            })();
                                        </script>

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
                                                        <small class="form-text text-muted">Thời hạn dự kiến: <?php echo (int)$duration_months; ?> tháng</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="end_date">
                                                            <i class="fas fa-stop mr-1"></i> Ngày kết thúc <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                                            value="<?php echo isset($contract['HD_NGAYKT']) ? date('Y-m-d', strtotime($contract['HD_NGAYKT'])) : ''; ?>" required>
                                                        <small class="form-text text-muted">Tự động tính dựa trên ngày bắt đầu và thời hạn</small>
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
                                            
                                            // Đồng bộ về CSDL: cập nhật BB_TONGDIEM và BB_XEPLOAI nếu khác
                                            if (!empty($decision['BB_SOBB'])) {
                                                $bb_sobb = $decision['BB_SOBB'];
                                                $rounded_score = round($actual_total_score, 2);
                                                // Lấy hiện trạng
                                                if ($stmt_sync = $conn->prepare("SELECT BB_TONGDIEM, BB_XEPLOAI FROM bien_ban WHERE BB_SOBB = ? LIMIT 1")) {
                                                    $stmt_sync->bind_param("s", $bb_sobb);
                                                    if ($stmt_sync->execute()) {
                                                        $res_sync = $stmt_sync->get_result();
                                                        if ($res_sync && $res_sync->num_rows > 0) {
                                                            $row_sync = $res_sync->fetch_assoc();
                                                            $db_score = isset($row_sync['BB_TONGDIEM']) ? (float)$row_sync['BB_TONGDIEM'] : null;
                                                            $db_class = $row_sync['BB_XEPLOAI'] ?? '';
                                                            if ($db_score !== $rounded_score || $db_class !== $actual_classification) {
                                                                if ($stmt_upd = $conn->prepare("UPDATE bien_ban SET BB_TONGDIEM = ?, BB_XEPLOAI = ? WHERE BB_SOBB = ?")) {
                                                                    $stmt_upd->bind_param("dss", $rounded_score, $actual_classification, $bb_sobb);
                                                                    $stmt_upd->execute();
                                                                    $stmt_upd->close();
                                                                }
                                                            }
                                                        }
                                                    }
                                                    $stmt_sync->close();
                                                }
                                                
                                                // Ghi tiến độ: Cập nhật điểm thành viên hội đồng (nếu chưa có bản ghi mới)
                                                // Lấy thời điểm đánh giá mới nhất
                                                $latest_eval_time = null;
                                                if ($stmt_max = $conn->prepare("SELECT MAX(TV_NGAYDANHGIA) AS latest_time FROM thanh_vien_hoi_dong WHERE QD_SO = ? AND TV_DIEM IS NOT NULL")) {
                                                    $stmt_max->bind_param("s", $decision['QD_SO']);
                                                    if ($stmt_max->execute()) {
                                                        $res_max = $stmt_max->get_result();
                                                        if ($res_max && $row_max = $res_max->fetch_assoc()) {
                                                            $latest_eval_time = $row_max['latest_time'];
                                                        }
                                                    }
                                                    $stmt_max->close();
                                                }
                                                if (!empty($latest_eval_time)) {
                                                    // Kiểm tra đã có log sau thời điểm này chưa
                                                    $exists_log = false;
                                                    if ($stmt_chk = $conn->prepare("SELECT 1 FROM tien_do_de_tai WHERE DT_MADT = ? AND TDDT_TIEUDE = 'Cập nhật điểm thành viên hội đồng' AND TDDT_NGAYCAPNHAT >= ? LIMIT 1")) {
                                                        $stmt_chk->bind_param("ss", $project_id, $latest_eval_time);
                                                        if ($stmt_chk->execute()) {
                                                            $res_chk = $stmt_chk->get_result();
                                                            $exists_log = ($res_chk && $res_chk->num_rows > 0);
                                                        }
                                                        $stmt_chk->close();
                                                    }
                                                    if (!$exists_log) {
                                                        // Xây nội dung chi tiết các thành viên
                                                        $lines = [];
                                                        if ($stmt_list = $conn->prepare("SELECT tv.TV_VAITRO, tv.TV_DIEM, tv.GV_MAGV, CONCAT(gv.GV_HOGV,' ',gv.GV_TENGV) AS GV_HOTEN FROM thanh_vien_hoi_dong tv LEFT JOIN giang_vien gv ON gv.GV_MAGV = tv.GV_MAGV WHERE tv.QD_SO = ? AND tv.TV_DIEM IS NOT NULL ORDER BY tv.TV_VAITRO")) {
                                                            $stmt_list->bind_param("s", $decision['QD_SO']);
                                                            if ($stmt_list->execute()) {
                                                                $res_list = $stmt_list->get_result();
                                                                while ($r = $res_list->fetch_assoc()) {
                                                                    $name = $r['GV_HOTEN'] ?: $r['GV_MAGV'];
                                                                    $role = $r['TV_VAITRO'];
                                                                    $score = (float)$r['TV_DIEM'];
                                                                    $lines[] = "- {$name} ({$role}): " . number_format($score, 1) . " → " . number_format($score, 0) . "/100";
                                                                }
                                                            }
                                                            $stmt_list->close();
                                                        }
                                                        // Thống kê nhanh
                                                        $cnt = 0; $avg = 0; $min = 0; $max = 0;
                                                        if ($stmt_stat = $conn->prepare("SELECT COUNT(*) c, ROUND(AVG(TV_DIEM),0) a, MIN(TV_DIEM) mn, MAX(TV_DIEM) mx FROM thanh_vien_hoi_dong WHERE QD_SO = ? AND TV_DIEM IS NOT NULL")) {
                                                            $stmt_stat->bind_param("s", $decision['QD_SO']);
                                                            if ($stmt_stat->execute()) {
                                                                $res_stat = $stmt_stat->get_result();
                                                                if ($res_stat && $row_stat = $res_stat->fetch_assoc()) {
                                                                    $cnt = (int)$row_stat['c'];
                                                                    $avg = (int)$row_stat['a'];
                                                                    $min = (float)$row_stat['mn'];
                                                                    $max = (float)$row_stat['mx'];
                                                                }
                                                            }
                                                            $stmt_stat->close();
                                                        }
                                                        $body = "Đã cập nhật điểm đánh giá thành viên hội đồng:\n\n" . implode("\n", $lines) . "\n\n" .
                                                                "📊 THỐNG KÊ:\n" .
                                                                "- Số thành viên được cập nhật: {$cnt}\n" .
                                                                "- Tổng số thành viên có điểm: {$cnt}\n" .
                                                                "- Điểm trung bình: {$avg}/100\n" .
                                                                "- Điểm thấp nhất: " . number_format($min,1) . "/100\n" .
                                                                "- Điểm cao nhất: " . number_format($max,1) . "/100\n";
                                                        // Lấy chủ nhiệm
                                                        $leader = null;
                                                        if ($stmt_lead = $conn->prepare("SELECT SV_MASV FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND CTTG_VAITRO = 'Chủ nhiệm' LIMIT 1")) {
                                                            $stmt_lead->bind_param("s", $project_id);
                                                            if ($stmt_lead->execute()) {
                                                                $res_lead = $stmt_lead->get_result();
                                                                if ($res_lead && $row_lead = $res_lead->fetch_assoc()) {
                                                                    $leader = $row_lead['SV_MASV'];
                                                                }
                                                            }
                                                            $stmt_lead->close();
                                                        }
                                                        // Tạo mã tiến độ
                                                        $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));
                                                        // Chèn tiến độ
                                                        if ($stmt_ins = $conn->prepare("INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) VALUES (?, ?, ?, 'Cập nhật điểm thành viên hội đồng', ?, NOW(), 0)")) {
                                                            $stmt_ins->bind_param("ssss", $progress_id, $project_id, $leader, $body);
                                                            $stmt_ins->execute();
                                                            $stmt_ins->close();
                                                        }
                                                    }
                                                }
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
                                                    <!-- <?php if ($actual_classification && isset($decision['BB_XEPLOAI']) && $actual_classification !== $decision['BB_XEPLOAI']): ?>
                                                        <small class="text-muted ml-2">
                                                            <i class="fas fa-info-circle" title="Xếp loại được tính toán từ điểm thành viên hội đồng"></i>
                                                            (DB: <?php echo htmlspecialchars($decision['BB_XEPLOAI']); ?>)
                                                        </small>
                                                    <?php endif; ?> -->
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

                                <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role) && $decision): ?>
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
                                                    <?php endif; ?>
                                                    
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
                                                        
                                                        <div class="alert alert-info mt-3">
                                                            <h6><i class="fas fa-users mr-2"></i>Cấu trúc hội đồng nghiệm thu bắt buộc:</h6>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <ul class="mb-0 small">
                                                                        <li><strong>1 Chủ tịch hội đồng</strong> <span class="badge badge-danger badge-sm">Bắt buộc</span></li>
                                                                        <li><strong>2 Phản biện</strong> <span class="badge badge-success badge-sm">Bắt buộc</span></li>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <ul class="mb-0 small">
                                                                        <li><strong>1 Thành viên</strong> <span class="badge badge-primary badge-sm">Bắt buộc</span></li>
                                                                        <li><strong>1 Thư ký</strong> <span class="badge badge-info badge-sm">Bắt buộc</span></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <hr class="my-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-info-circle mr-1"></i>
                                                                Tổng cộng: <strong>5 thành viên</strong> theo quy định
                                                            </small>
                                                        </div>
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
                                        <div class="card mt-3" style="display: none;">
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
                                    <div class="evaluation-result-section mb-4" style="display: none;">
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
                                                                    <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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

                                    <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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

                                <?php elseif (false && $decision && $has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): // Tạm thời ẩn ?>
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
                                        <?php
                                        // Thống kê vai trò hiện tại
                                        $role_counts = [
                                            'Chủ tịch' => 0,
                                            'Phản biện' => 0, 
                                            'Thành viên' => 0,
                                            'Thư ký' => 0,
                                            'Khác' => 0
                                        ];
                                        
                                        foreach ($council_members as $member) {
                                            $role = $member['TV_VAITRO'];
                                            $role_normalized = strtolower($role);
                                            
                                            if (strpos($role_normalized, 'chủ tịch') !== false && strpos($role_normalized, 'phó') === false) {
                                                $role_counts['Chủ tịch']++;
                                            } elseif (strpos($role_normalized, 'phản biện') !== false || strpos($role_normalized, 'phan bien') !== false) {
                                                $role_counts['Phản biện']++;
                                            } elseif (strpos($role_normalized, 'thư ký') !== false || strpos($role_normalized, 'thu ky') !== false) {
                                                $role_counts['Thư ký']++;
                                            } elseif ($role === 'Thành viên' || strpos($role_normalized, 'thành viên') !== false) {
                                                $role_counts['Thành viên']++;
                                            } else {
                                                $role_counts['Khác']++;
                                            }
                                        }
                                        
                                        $total_members = count($council_members);
                                        $is_valid_structure = ($role_counts['Chủ tịch'] == 1 && $role_counts['Phản biện'] == 2 && 
                                                              $role_counts['Thành viên'] == 1 && $role_counts['Thư ký'] == 1);
                                        ?>
                                        
                                        <!-- Thống kê cấu trúc hội đồng hiện tại -->
                                        <div class="card mb-3 <?php echo $is_valid_structure ? 'border-success' : 'border-warning'; ?>">
                                            <div class="card-header <?php echo $is_valid_structure ? 'bg-light-success' : 'bg-light-warning'; ?> py-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-chart-pie mr-2"></i>Cấu trúc hội đồng hiện tại
                                                    </h6>
                                                    <span class="badge <?php echo $is_valid_structure ? 'badge-success' : 'badge-warning'; ?>">
                                                        <?php echo $is_valid_structure ? 'Đạt chuẩn' : 'Chưa đạt chuẩn'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body py-2">
                                                <div class="row text-center">
                                                    <div class="col">
                                                        <span class="badge <?php echo $role_counts['Chủ tịch'] == 1 ? 'badge-success' : 'badge-danger'; ?>">
                                                            Chủ tịch: <?php echo $role_counts['Chủ tịch']; ?>/1
                                                        </span>
                                                    </div>
                                                    <div class="col">
                                                        <span class="badge <?php echo $role_counts['Phản biện'] == 2 ? 'badge-success' : 'badge-warning'; ?>">
                                                            Phản biện: <?php echo $role_counts['Phản biện']; ?>/2
                                                        </span>
                                                    </div>
                                                    <div class="col">
                                                        <span class="badge <?php echo $role_counts['Thành viên'] == 1 ? 'badge-success' : 'badge-warning'; ?>">
                                                            Thành viên: <?php echo $role_counts['Thành viên']; ?>/1
                                                        </span>
                                                    </div>
                                                    <div class="col">
                                                        <span class="badge <?php echo $role_counts['Thư ký'] == 1 ? 'badge-success' : 'badge-warning'; ?>">
                                                            Thư ký: <?php echo $role_counts['Thư ký']; ?>/1
                                                        </span>
                                                    </div>
                                                    <div class="col">
                                                        <span class="badge <?php echo $total_members == 5 ? 'badge-success' : 'badge-secondary'; ?>">
                                                            Tổng: <?php echo $total_members; ?>/5
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary mb-0">
                                                <i class="fas fa-users mr-2"></i>Danh sách thành viên hội đồng nghiệm thu
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
                                                        <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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
                                                                    $badge_class = 'badge-success';
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
                                                                // Lấy file đánh giá của thành viên này CHỈ CỦA ĐỀ TÀI HIỆN TẠI
                                                                $member_files = [];
                                                                $member_id = $member['GV_MAGV'] ?? $member['MAGV'] ?? $member['TV_MAGV'] ?? '';
                                                                
                                                                if (!empty($member_id) && !empty($project['QD_SO'])) {
                                                                    try {
                                                                        // Query để lấy file đánh giá của thành viên này CHỈ CỦA ĐỀ TÀI HIỆN TẠI
                                                                        // Sử dụng REGEX để lọc file theo mã đề tài trong filename
                                                                        $member_files_sql = "SELECT fd.FDG_TENFILE as FDK_TEN, fd.FDG_FILE as FDK_DUONGDAN, 
                                                                                                   fd.FDG_MOTA as FDK_MOTA, fd.FDG_NGAYTAO as FDK_NGAYTAO,
                                                                                                   fd.FDG_KICHTHUC as FDK_KICHTHUC
                                                                                            FROM file_dinh_kem fd
                                                                                            WHERE fd.FDG_LOAI = 'member_evaluation' 
                                                                                            AND fd.GV_MAGV = ?
                                                                                            AND fd.FDG_FILE REGEXP ?
                                                                                            AND fd.FDG_FILE IS NOT NULL 
                                                                                            AND TRIM(fd.FDG_FILE) != ''
                                                                                            ORDER BY fd.FDG_NGAYTAO DESC";
                                                                        
                                                                        $stmt = $conn->prepare($member_files_sql);
                                                                        if ($stmt) {
                                                                            // Tạo regex pattern để tìm mã đề tài trong filename
                                                                            $regex_pattern = $project_id . '_[0-9]+\\.(docx?|pdf|txt)$';
                                                                            $stmt->bind_param("ss", $member_id, $regex_pattern);
                                                                            $stmt->execute();
                                                                            $result = $stmt->get_result();
                                                                            while ($file = $result->fetch_assoc()) {
                                                                                $member_files[] = $file;
                                                                            }
                                                                            $stmt->close();
                                                                        }
                                                                    } catch (Exception $e) {
                                                                        error_log("Error loading member files: " . $e->getMessage());
                                                                    }
                                                                }
                                                                
                                                                if (count($member_files) > 0): ?>
                                                                    <div class="btn-group">
                                                                        <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown" title="Có <?php echo count($member_files); ?> file đánh giá của đề tài này">
                                                                            <i class="fas fa-file-check mr-1"></i><?php echo count($member_files); ?> file
                                                                        </button>
                                                                        <div class="dropdown-menu dropdown-menu-right">
                                                                            <h6 class="dropdown-header">
                                                                                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($member['GV_HOTEN'] ?? $member['TV_HOTEN'] ?? 'N/A'); ?>
                                                                            </h6>
                                                                            <div class="dropdown-divider"></div>
                                                                            <?php foreach ($member_files as $index => $file): ?>
                                                                                <a class="dropdown-item" href="/NLNganh/uploads/member_evaluations/<?php echo htmlspecialchars($file['FDK_DUONGDAN']); ?>" target="_blank">
                                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                                        <div class="flex-grow-1">
                                                                                            <i class="fas fa-download mr-1 text-primary"></i>
                                                                                            <span class="font-weight-medium"><?php echo htmlspecialchars($file['FDK_TEN']); ?></span>
                                                                                            <br>
                                                                                            <small class="text-muted">
                                                                                                <i class="fas fa-calendar-alt mr-1"></i><?php echo date('d/m/Y H:i', strtotime($file['FDK_NGAYTAO'])); ?>
                                                                                                <?php if (!empty($file['FDK_KICHTHUC'])): ?>
                                                                                                    | <i class="fas fa-file-alt mr-1"></i><?php echo number_format($file['FDK_KICHTHUC'] / 1024, 1); ?> KB
                                                                                                <?php endif; ?>
                                                                                            </small>
                                                                                        </div>
                                                                                    </div>
                                                                                </a>
                                                                                <?php if ($index < count($member_files) - 1): ?>
                                                                                    <div class="dropdown-divider"></div>
                                                                                <?php endif; ?>
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
                                                            <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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
    <?php if ($has_access && $user_role === 'Chủ nhiệm' && canEditProject($project['DT_TRANGTHAI'], $user_role)): ?>
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
    
    <!-- Council Members Management Script -->
    <script>
    $(document).ready(function() {
        // Biến lưu danh sách thành viên hội đồng
        let projectCouncilMembers = [];
        let allTeachers = [];
        
        // Mở modal thêm thành viên hội đồng
        $('#addCouncilMemberBtn').click(function() {
            $('#councilMemberModal').modal('show');
            loadTeachers();
            updateRoleDropdown(); // Cập nhật dropdown khi mở modal
        });
        
        // Load danh sách giảng viên
        function loadTeachers() {
            $.ajax({
                url: '/NLNganh/api/get_teachers.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        allTeachers = response.teachers;
                        updateTeacherSelect(allTeachers);
                    } else {
                        // Fallback với dữ liệu mẫu
                        allTeachers = [
                            {id: 'GV000001', name: 'Nguyễn Văn A', department: 'KH011', department_name: 'Trường Công nghệ thông tin và truyền thông'},
                            {id: 'GV000002', name: 'Trần Thị B', department: 'KH012', department_name: 'Trường Kinh tế'},
                            {id: 'GV000003', name: 'Lê Văn C', department: 'KH012', department_name: 'Trường Kinh tế'},
                            {id: 'GV000004', name: 'Phạm Thị D', department: 'KH007', department_name: 'Khoa Ngoại ngữ'}
                        ];
                        updateTeacherSelect(allTeachers);
                    }
                },
                error: function() {
                    // Fallback với dữ liệu mẫu
                    allTeachers = [
                        {id: 'GV000001', name: 'Nguyễn Văn A', department: 'KH011', department_name: 'Trường Công nghệ thông tin và truyền thông'},
                        {id: 'GV000002', name: 'Trần Thị B', department: 'KH012', department_name: 'Trường Kinh tế'},
                        {id: 'GV000003', name: 'Lê Văn C', department: 'KH012', department_name: 'Trường Kinh tế'},
                        {id: 'GV000004', name: 'Phạm Thị D', department: 'KH007', department_name: 'Khoa Ngoại ngữ'}
                    ];
                    updateTeacherSelect(allTeachers);
                }
            });
        }
        
        // Cập nhật danh sách giảng viên trong select
        function updateTeacherSelect(teachers) {
            const $select = $('#teacherSelect');
            $select.empty();
            
            if (teachers.length === 0) {
                $select.append('<option value="">Không có giảng viên nào</option>');
                return;
            }
            
            $select.append('<option value="">-- Chọn giảng viên --</option>');
            teachers.forEach(function(teacher) {
                const displayDept = teacher.department_name || teacher.department;
                $select.append(
                    `<option value="${teacher.id}" data-name="${teacher.name}" data-department="${displayDept}" data-department-code="${teacher.department}">
                        ${teacher.id} - ${teacher.name} (${displayDept})
                    </option>`
                );
            });
        }
        
        // Lọc theo khoa
        $('#departmentFilter').change(function() {
            const selectedDept = $(this).val();
            let filteredTeachers = allTeachers;
            
            if (selectedDept) {
                filteredTeachers = allTeachers.filter(teacher => teacher.department === selectedDept);
            }
            
            updateTeacherSelect(filteredTeachers);
        });
        
        // Tìm kiếm giảng viên
        $('#searchTeacher').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const selectedDept = $('#departmentFilter').val();
            
            let filteredTeachers = allTeachers;
            
            // Lọc theo khoa trước
            if (selectedDept) {
                filteredTeachers = filteredTeachers.filter(teacher => teacher.department === selectedDept);
            }
            
            // Sau đó lọc theo từ khóa tìm kiếm
            if (searchTerm) {
                filteredTeachers = filteredTeachers.filter(teacher => 
                    teacher.name.toLowerCase().includes(searchTerm) || 
                    teacher.id.toLowerCase().includes(searchTerm)
                );
            }
            
            updateTeacherSelect(filteredTeachers);
        });
        
        // Hiển thị thông tin giảng viên được chọn
        $('#teacherSelect').change(function() {
            const $selected = $(this).find('option:selected');
            if ($selected.val()) {
                const info = `${$selected.data('name')} - ${$selected.data('department')}`;
                $('#selectedInfo').text(info);
                $('#currentSelection').show();
            } else {
                $('#currentSelection').hide();
            }
        });
        
        // Thêm thành viên được chọn
        $('#addSelectedMember').click(function() {
            const $selectedTeacher = $('#teacherSelect option:selected');
            const role = $('#memberRole').val();
            
            if (!$selectedTeacher.val() || !role) {
                alert('Vui lòng chọn giảng viên và vai trò.');
                return;
            }
            
            // Kiểm tra giới hạn số lượng thành viên (tối đa 5)
            if (projectCouncilMembers.length >= 5) {
                alert('Hội đồng chỉ được có tối đa 5 thành viên!');
                return;
            }
            
            const teacherId = $selectedTeacher.val();
            const teacherName = $selectedTeacher.data('name');
            const department = $selectedTeacher.data('department'); // Tên khoa để hiển thị
            
            // Kiểm tra xem giảng viên đã được thêm chưa
            const existingMember = projectCouncilMembers.find(member => member.id === teacherId);
            if (existingMember) {
                alert('Giảng viên này đã được thêm vào hội đồng.');
                return;
            }
            
            // Kiểm tra ràng buộc vai trò
            const roleValidation = validateRoleConstraints(role);
            if (!roleValidation.valid) {
                alert(roleValidation.message);
                return;
            }
            
            // Thêm thành viên mới
            const newMember = {
                id: teacherId,
                name: teacherName,
                role: role,
                department: department
            };
            
            projectCouncilMembers.push(newMember);
            updateCouncilMembersDisplay();
            updateCouncilMembersInput();
            updateRoleDropdown(); // Cập nhật dropdown theo ràng buộc
            
            // Reset form và đóng modal
            $('#memberRole').val('');
            $('#teacherSelect').val('');
            $('#currentSelection').hide();
            $('#councilMemberModal').modal('hide');
        });
        
        // Cập nhật hiển thị danh sách thành viên
        function updateCouncilMembersDisplay() {
            const $container = $('#selectedCouncilMembers');
            
            if (projectCouncilMembers.length === 0) {
                $container.removeClass('has-members');
                $container.addClass('empty-state');
                $container.html('<div>Chưa có thành viên nào được chọn</div>');
                return;
            }
            
            $container.removeClass('empty-state');
            $container.addClass('has-members');
            
            let html = `
                <div class="members-header">
                    <h6><i class="fas fa-users"></i>Danh sách thành viên đã chọn</h6>
                    <span class="members-count">${projectCouncilMembers.length} thành viên</span>
                </div>
            `;
            
            projectCouncilMembers.forEach(function(member, index) {
                // Xác định màu sắc role
                let roleClass = 'member-role';
                if (member.role === 'Chủ tịch') {
                    roleClass += ' role-chairman';
                } else if (member.role === 'Thư ký') {
                    roleClass += ' role-secretary';
                } else if (member.role === 'Thành viên') {
                    roleClass += ' role-member';
                } else if (member.role === 'Phản biện') {
                    roleClass += ' role-reviewer';
                }
                
                html += `
                    <div class="member-card">
                        <div class="member-info">
                            <div class="member-details">
                                <div class="member-name">${member.name}</div>
                                <div>
                                    <span class="${roleClass}">${member.role}</span>
                                </div>
                                <div class="member-department">${member.department}</div>
                            </div>
                            <button type="button" class="remove-member-btn" onclick="removeMember(${index})" title="Xóa thành viên">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            $container.html(html);
        }
        
        // Cập nhật input ẩn
        function updateCouncilMembersInput() {
            $('#council_members_json').val(JSON.stringify(projectCouncilMembers));
            
            // Cập nhật text format cho input cũ
            let textFormat = '';
            projectCouncilMembers.forEach(function(member) {
                textFormat += `${member.name} (${member.role})\n`;
            });
            $('#council_members').val(textFormat.trim());
        }
        
        // Kiểm tra ràng buộc vai trò
        function validateRoleConstraints(newRole) {
            const roleCounts = {
                'Chủ tịch': 0,
                'Phản biện': 0,
                'Thành viên': 0,
                'Thư ký': 0
            };
            
            // Đếm số lượng vai trò hiện tại
            projectCouncilMembers.forEach(member => {
                if (roleCounts.hasOwnProperty(member.role)) {
                    roleCounts[member.role]++;
                }
            });
            
            // Kiểm tra ràng buộc cho vai trò mới
            switch(newRole) {
                case 'Chủ tịch':
                    if (roleCounts['Chủ tịch'] >= 1) {
                        return {
                            valid: false,
                            message: 'Hội đồng chỉ được có 1 Chủ tịch!'
                        };
                    }
                    break;
                    
                case 'Phản biện':
                    if (roleCounts['Phản biện'] >= 2) {
                        return {
                            valid: false,
                            message: 'Hội đồng chỉ được có tối đa 2 Phản biện!'
                        };
                    }
                    break;
                    
                case 'Thành viên':
                    if (roleCounts['Thành viên'] >= 1) {
                        return {
                            valid: false,
                            message: 'Hội đồng chỉ được có 1 Thành viên!'
                        };
                    }
                    break;
                    
                case 'Thư ký':
                    if (roleCounts['Thư ký'] >= 1) {
                        return {
                            valid: false,
                            message: 'Hội đồng chỉ được có 1 Thư ký!'
                        };
                    }
                    break;
            }
            
            return { valid: true };
        }
        
        // Cập nhật dropdown vai trò theo ràng buộc
        function updateRoleDropdown() {
            const roleCounts = {
                'Chủ tịch': 0,
                'Phản biện': 0,
                'Thành viên': 0,
                'Thư ký': 0
            };
            
            // Đếm số lượng vai trò hiện tại
            projectCouncilMembers.forEach(member => {
                if (roleCounts.hasOwnProperty(member.role)) {
                    roleCounts[member.role]++;
                }
            });
            
            // Vô hiệu hóa/kích hoạt các option
            $('#memberRole option').each(function() {
                const role = $(this).val();
                const $option = $(this);
                
                // Reset style
                $option.prop('disabled', false);
                $option.removeClass('role-full');
                
                switch(role) {
                    case 'Chủ tịch':
                        if (roleCounts['Chủ tịch'] >= 1) {
                            $option.prop('disabled', true);
                            $option.addClass('role-full');
                            $option.text('Chủ tịch hội đồng (Đã đủ)');
                        } else {
                            $option.text('Chủ tịch hội đồng');
                        }
                        break;
                        
                    case 'Phản biện':
                        if (roleCounts['Phản biện'] >= 2) {
                            $option.prop('disabled', true);
                            $option.addClass('role-full');
                            $option.text('Phản biện (Đã đủ 2/2)');
                        } else {
                            $option.text(`Phản biện (${roleCounts['Phản biện']}/2)`);
                        }
                        break;
                        
                    case 'Thành viên':
                        if (roleCounts['Thành viên'] >= 1) {
                            $option.prop('disabled', true);
                            $option.addClass('role-full');
                            $option.text('Thành viên (Đã đủ)');
                        } else {
                            $option.text('Thành viên');
                        }
                        break;
                        
                    case 'Thư ký':
                        if (roleCounts['Thư ký'] >= 1) {
                            $option.prop('disabled', true);
                            $option.addClass('role-full');
                            $option.text('Thư ký (Đã đủ)');
                        } else {
                            $option.text('Thư ký');
                        }
                        break;
                }
            });
            
            // Cập nhật thông tin thống kê
            updateRoleStatistics(roleCounts);
        }
        
        // Cập nhật thống kê vai trò
        function updateRoleStatistics(roleCounts) {
            const totalMembers = projectCouncilMembers.length;
            const maxMembers = 5;
            
            // Tạo thông tin thống kê
            let statsHtml = `
                <div class="role-statistics">
                    <div class="stats-header">
                        <small class="text-muted">Thống kê hội đồng: ${totalMembers}/${maxMembers} thành viên</small>
                    </div>
                    <div class="stats-content">
                        <div class="role-stat ${roleCounts['Chủ tịch'] >= 1 ? 'complete' : 'incomplete'}">
                            <span class="role-name">Chủ tịch:</span>
                            <span class="role-count">${roleCounts['Chủ tịch']}/1</span>
                        </div>
                        <div class="role-stat ${roleCounts['Phản biện'] >= 2 ? 'complete' : 'incomplete'}">
                            <span class="role-name">Phản biện:</span>
                            <span class="role-count">${roleCounts['Phản biện']}/2</span>
                        </div>
                        <div class="role-stat ${roleCounts['Thành viên'] >= 1 ? 'complete' : 'incomplete'}">
                            <span class="role-name">Thành viên:</span>
                            <span class="role-count">${roleCounts['Thành viên']}/1</span>
                        </div>
                        <div class="role-stat ${roleCounts['Thư ký'] >= 1 ? 'complete' : 'incomplete'}">
                            <span class="role-name">Thư ký:</span>
                            <span class="role-count">${roleCounts['Thư ký']}/1</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Thêm vào modal nếu có
            $('#roleStatistics').html(statsHtml);
        }
        
        // Xóa thành viên (global function)
        window.removeMember = function(index) {
            projectCouncilMembers.splice(index, 1);
            updateCouncilMembersDisplay();
            updateCouncilMembersInput();
            updateRoleDropdown(); // Cập nhật dropdown sau khi xóa
        };
    });
    </script>
    
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

            // Tự động tính ngày kết thúc theo thời hạn đăng ký khi chọn ngày bắt đầu
            (function() {
                const startInput = document.getElementById('start_date');
                const endInput = document.getElementById('end_date');
                const durationMonths = <?php echo (int)$duration_months; ?>;

                function addMonths(date, months) {
                    const d = new Date(date);
                    const day = d.getDate();
                    d.setMonth(d.getMonth() + months);
                    // Xử lý cuối tháng: nếu ngày bị nhảy sang tháng sau, lùi lại cuối tháng
                    if (d.getDate() < day) {
                        d.setDate(0);
                    }
                    return d;
                }

                function formatYMD(date) {
                    const y = date.getFullYear();
                    const m = ('0' + (date.getMonth() + 1)).slice(-2);
                    const da = ('0' + date.getDate()).slice(-2);
                    return `${y}-${m}-${da}`;
                }

                if (startInput && endInput) {
                    startInput.addEventListener('change', function() {
                        if (this.value) {
                            const end = addMonths(this.value, durationMonths);
                            endInput.value = formatYMD(end);
                        }
                    });
                }
            })();

            // Decision form validation  
            $('form[action="/NLNganh/view/student/update_decision_info.php"]').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const decisionNumber = form.find('#decision_number').val().trim();
                const decisionDate = form.find('#decision_date').val();
                const updateReason = form.find('#decision_update_reason').val().trim();
                const isUpdate = form.find('input[name="decision_id"]').length > 0;
                const contractEndDate = <?php echo isset($contract['HD_NGAYKT']) ? json_encode(date('Y-m-d', strtotime($contract['HD_NGAYKT']))) : 'null'; ?>;
                
                const requiredFields = [
                    { selector: '#decision_number', message: 'Vui lòng nhập số quyết định.' },
                    { selector: '#decision_date', message: 'Vui lòng chọn ngày quyết định.' },
                    { selector: '#decision_update_reason', message: 'Vui lòng nhập lý do cập nhật.' }
                ];
                
                if (!validateRequiredFields(requiredFields)) {
                    return false;
                }
                
                // Ràng buộc: Ngày quyết định phải trước hoặc bằng hạn cuối hợp đồng
                if (contractEndDate) {
                    if (new Date(decisionDate) > new Date(contractEndDate)) {
                        alert(`Ngày quyết định phải trước hoặc bằng ngày kết thúc hợp đồng (${contractEndDate}).`);
                        $('#decision_date').focus();
                        return false;
                    }
                } else {
                    alert('Chưa có hợp đồng hoặc thiếu ngày kết thúc hợp đồng. Vui lòng nhập hợp đồng trước.');
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

    <!-- Modal thêm thành viên hội đồng -->
    <div class="modal fade" id="councilMemberModal" tabindex="-1" role="dialog" aria-labelledby="councilMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="councilMemberModalLabel">
                        <i class="fas fa-user-plus mr-2"></i>Thêm thành viên hội đồng nghiệm thu
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Cột bộ lọc -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="departmentFilter">Lọc theo khoa:</label>
                                <select class="form-control" id="departmentFilter">
                                    <option value="">-- Tất cả khoa --</option>
                                    <?php
                                    // Lấy danh sách khoa từ database
                                    $khoa_sql = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV";
                                    $khoa_result = $conn->query($khoa_sql);
                                    if ($khoa_result && $khoa_result->num_rows > 0) {
                                        while ($khoa_row = $khoa_result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($khoa_row['DV_MADV']) . "'>" . 
                                                 htmlspecialchars($khoa_row['DV_TENDV']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="searchTeacher">Tìm kiếm giảng viên:</label>
                                <input type="text" class="form-control" id="searchTeacher" placeholder="Nhập tên hoặc mã giảng viên...">
                            </div>
                        </div>
                        
                        <!-- Cột chọn giảng viên và vai trò -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="teacherSelect">Chọn giảng viên:</label>
                                <select class="form-control" id="teacherSelect" size="8">
                                    <option value="">-- Đang tải danh sách giảng viên --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="memberRole">Vai trò trong hội đồng:</label>
                                <select class="form-control" id="memberRole" required>
                                    <option value="">-- Chọn vai trò --</option>
                                    <option value="Chủ tịch">Chủ tịch hội đồng</option>
                                    <option value="Phản biện">Phản biện</option>
                                    <option value="Thành viên">Thành viên</option>
                                    <option value="Thư ký">Thư ký</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb mr-1"></i>Cấu trúc bắt buộc:</h6>
                                <ul class="mb-0 small">
                                    <li>1 Chủ tịch</li>
                                    <li>2 Phản biện</li>
                                    <li>1 Thành viên</li>
                                    <li>1 Thư ký</li>
                                </ul>
                            </div>
                            
                            <!-- Thống kê thời gian thực -->
                            <div id="roleStatistics"></div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <div class="current-selection mb-3" id="currentSelection" style="display: none;">
                            <strong>Đã chọn:</strong>
                            <div id="selectedInfo" class="text-primary"></div>
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

</body>
</html>