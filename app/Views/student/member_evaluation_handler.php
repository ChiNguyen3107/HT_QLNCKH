<?php
// File: member_evaluation_handler.php
// Xử lý nhập điểm và upload file đánh giá cho từng thành viên hội đồng

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
    exit();
}

$action = $_POST['action'] ?? '';
$project_id = trim($_POST['project_id'] ?? '');
$decision_id = trim($_POST['decision_id'] ?? '');
$member_id = trim($_POST['member_id'] ?? '');
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền chủ nhiệm
$check_role_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_role_sql);
$stmt->bind_param("ss", $project_id, $user_id);
$stmt->execute();
$role_result = $stmt->get_result();

if ($role_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập đề tài này']);
    exit();
}

$user_role = $role_result->fetch_assoc()['CTTG_VAITRO'];
if ($user_role !== 'Chủ nhiệm') {
    echo json_encode(['success' => false, 'message' => 'Chỉ chủ nhiệm đề tài mới có thể cập nhật đánh giá']);
    exit();
}

try {
    switch ($action) {
        case 'save_member_scores':
            saveMemberScores();
            break;
        case 'upload_member_file':
            uploadMemberFile();
            break;
        case 'delete_member_file':
            deleteMemberFile();
            break;
        case 'get_member_evaluation':
            getMemberEvaluation();
            break;
        default:
            throw new Exception('Hành động không hợp lệ');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Lưu điểm đánh giá của thành viên
function saveMemberScores() {
    global $conn, $decision_id, $member_id;
    
    $scores = [
        'content' => floatval($_POST['score_content'] ?? 0),
        'presentation' => floatval($_POST['score_presentation'] ?? 0),
        'response' => floatval($_POST['score_response'] ?? 0)
    ];
    
    $comments = [
        'positive' => trim($_POST['positive_comment'] ?? ''),
        'improvement' => trim($_POST['improvement_comment'] ?? ''),
        'suggestion' => trim($_POST['suggestion'] ?? ''),
        'general' => trim($_POST['general_comment'] ?? '')
    ];
    
    // Validate scores
    foreach ($scores as $key => $score) {
        if ($score < 0 || $score > 10) {
            throw new Exception("Điểm $key phải từ 0 đến 10");
        }
    }
    
    $conn->begin_transaction();
    
    // Tính điểm trung bình
    $average_score = ($scores['content'] + $scores['presentation'] + $scores['response']) / 3;
    
    // Cập nhật thông tin đánh giá
    $update_sql = "UPDATE thanh_vien_hoi_dong SET 
                   TV_DIEM = ?,
                   TV_DIEM_NOIDUNG = ?,
                   TV_DIEM_TRINHBAY = ?,
                   TV_DIEM_TRALOI = ?,
                   TV_DANHGIA = ?,
                   TV_NHANXET_TICHHOP = ?,
                   TV_NHANXET_CANHBAO = ?,
                   TV_KIENNGHI = ?,
                   TV_NGAYDANHGIA = NOW(),
                   TV_TRANGTHAI = 'Đã đánh giá'
                   WHERE QD_SO = ? AND GV_MAGV = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ddddssssss", 
        $average_score,
        $scores['content'],
        $scores['presentation'], 
        $scores['response'],
        $comments['general'],
        $comments['positive'],
        $comments['improvement'],
        $comments['suggestion'],
        $decision_id,
        $member_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể lưu điểm đánh giá: ' . $stmt->error);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu điểm đánh giá thành công',
        'average_score' => round($average_score, 2)
    ]);
}

// Upload file đánh giá của thành viên
function uploadMemberFile() {
    global $conn, $decision_id, $member_id;
    
    if (!isset($_FILES['evaluation_file']) || $_FILES['evaluation_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Không có file được upload hoặc có lỗi xảy ra');
    }
    
    $file = $_FILES['evaluation_file'];
    $description = trim($_POST['file_description'] ?? '');
    
    // Validate file
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Chỉ chấp nhận file PDF, DOC, DOCX, TXT');
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        throw new Exception('File quá lớn. Kích thước tối đa là 10MB');
    }
    
    // Tạo thư mục nếu chưa có
    $upload_dir = '../../uploads/member_evaluations/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Tạo tên file unique
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = $decision_id . '_' . $member_id . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Không thể lưu file');
    }
    
    $conn->begin_transaction();
    
    // Lưu thông tin file vào database
    $insert_sql = "INSERT INTO member_evaluation_files 
                   (QD_SO, GV_MAGV, MEF_FILENAME, MEF_FILEPATH, MEF_FILESIZE, MEF_MIMETYPE, MEF_DESCRIPTION) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssssiis", 
        $decision_id,
        $member_id,
        $file['name'],
        $new_filename,
        $file['size'],
        $file['type'],
        $description
    );
    
    if (!$stmt->execute()) {
        unlink($file_path); // Xóa file nếu không lưu được DB
        throw new Exception('Không thể lưu thông tin file: ' . $stmt->error);
    }
    
    $file_id = $conn->insert_id;
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload file thành công',
        'file_id' => $file_id,
        'filename' => $file['name']
    ]);
}

// Xóa file đánh giá
function deleteMemberFile() {
    global $conn;
    
    $file_id = intval($_POST['file_id'] ?? 0);
    
    if ($file_id <= 0) {
        throw new Exception('ID file không hợp lệ');
    }
    
    $conn->begin_transaction();
    
    // Lấy thông tin file
    $select_sql = "SELECT MEF_FILEPATH FROM member_evaluation_files WHERE MEF_ID = ? AND MEF_STATUS = 'Active'";
    $stmt = $conn->prepare($select_sql);
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('File không tồn tại');
    }
    
    $file_path = '../../uploads/member_evaluations/' . $result->fetch_assoc()['MEF_FILEPATH'];
    
    // Đánh dấu xóa trong database
    $update_sql = "UPDATE member_evaluation_files SET MEF_STATUS = 'Deleted' WHERE MEF_ID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $file_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Không thể xóa file: ' . $stmt->error);
    }
    
    // Xóa file vật lý
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa file thành công'
    ]);
}

// Lấy thông tin đánh giá của thành viên
function getMemberEvaluation() {
    global $conn, $decision_id, $member_id;
    
    // Lấy thông tin đánh giá
    $evaluation_sql = "SELECT tv.*, gv.GV_HOTEN 
                       FROM thanh_vien_hoi_dong tv
                       LEFT JOIN giang_vien gv ON tv.GV_MAGV = gv.GV_MAGV
                       WHERE tv.QD_SO = ? AND tv.GV_MAGV = ?";
    
    $stmt = $conn->prepare($evaluation_sql);
    $stmt->bind_param("ss", $decision_id, $member_id);
    $stmt->execute();
    $evaluation = $stmt->get_result()->fetch_assoc();
    
    if (!$evaluation) {
        throw new Exception('Không tìm thấy thông tin thành viên');
    }
    
    // Lấy danh sách file
    $files_sql = "SELECT MEF_ID, MEF_FILENAME, MEF_FILESIZE, MEF_UPLOADDATE, MEF_DESCRIPTION 
                  FROM member_evaluation_files 
                  WHERE QD_SO = ? AND GV_MAGV = ? AND MEF_STATUS = 'Active'
                  ORDER BY MEF_UPLOADDATE DESC";
    
    $stmt = $conn->prepare($files_sql);
    $stmt->bind_param("ss", $decision_id, $member_id);
    $stmt->execute();
    $files_result = $stmt->get_result();
    
    $files = [];
    while ($file = $files_result->fetch_assoc()) {
        $files[] = $file;
    }
    
    echo json_encode([
        'success' => true,
        'evaluation' => $evaluation,
        'files' => $files
    ]);
}
?>
