<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Phương thức yêu cầu không hợp lệ.";
    header('Location: register_project_form.php');
    exit;
}

// Lấy dữ liệu từ form
$project_title = isset($_POST['project_title']) ? trim($_POST['project_title']) : '';
$project_type = isset($_POST['project_type']) ? trim($_POST['project_type']) : '';
$lecturer_id = isset($_POST['lecturer']) ? trim($_POST['lecturer']) : '';
$member_count = isset($_POST['member_count']) ? (int)$_POST['member_count'] : 3;
$project_description = isset($_POST['project_description']) ? trim($_POST['project_description']) : '';
$expected_results = isset($_POST['expected_results']) ? trim($_POST['expected_results']) : '';
$lecturer_message = isset($_POST['lecturer_message']) ? trim($_POST['lecturer_message']) : '';
$student_id = $_SESSION['user_id'];

// Kiểm tra dữ liệu đầu vào bắt buộc
if (empty($project_title) || empty($project_type) || empty($lecturer_id) || empty($project_description) || empty($expected_results)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin bắt buộc.";
    header('Location: register_project_form.php');
    exit;
}

// Kiểm tra xem sinh viên có phải là chủ nhiệm của đề tài khác không
$check_leader_sql = "SELECT 1 FROM chi_tiet_tham_gia 
                    WHERE SV_MASV = ? AND CTTG_VAITRO = 'Chủ nhiệm'";
$stmt = $conn->prepare($check_leader_sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error_message'] = "Bạn đã là chủ nhiệm của một đề tài khác. Không thể đăng ký thêm đề tài mới.";
    header('Location: register_project_form.php');
    exit;
}

// Xử lý tải lên đề cương (nếu có)
$outline_file_path = '';
if (isset($_FILES['outline_file']) && $_FILES['outline_file']['error'] == 0) {
    $file_name = $_FILES['outline_file']['name'];
    $file_size = $_FILES['outline_file']['size'];
    $file_tmp = $_FILES['outline_file']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Kiểm tra định dạng file
    $allowed_extensions = array("pdf", "doc", "docx");
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['error_message'] = "Chỉ cho phép tải lên file PDF, DOC, DOCX.";
        header('Location: register_project_form.php');
        exit;
    }
    
    // Kiểm tra kích thước file (tối đa 5MB)
    if ($file_size > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "Kích thước file không được vượt quá 5MB.";
        header('Location: register_project_form.php');
        exit;
    }
    
    // Tạo thư mục lưu trữ nếu chưa tồn tại
    $upload_directory = '../../uploads/project_outlines/';
    if (!file_exists($upload_directory)) {
        mkdir($upload_directory, 0777, true);
    }
    
    // Tạo tên file mới để tránh trùng lặp
    $new_file_name = 'outline_' . date('YmdHis') . '_' . uniqid() . '.' . $file_ext;
    $outline_file_path = $new_file_name;
    
    // Di chuyển file tải lên vào thư mục lưu trữ
    if (!move_uploaded_file($file_tmp, $upload_directory . $new_file_name)) {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi tải lên file đề cương.";
        header('Location: register_project_form.php');
        exit;
    }
}

// Tạo mã đề tài mới (format: DT + năm + tháng + ngày + số ngẫu nhiên)
$project_id = 'DT' . date('ymd') . rand(1000, 9999);

// Chuẩn bị câu lệnh SQL để thêm đề tài mới
// Mặc định thời gian thực hiện là 6 tháng
$implementation_time = 6;
$project_notes = "duration_months=$implementation_time";

$insert_project_sql = "INSERT INTO de_tai_nghien_cuu 
                      (DT_TENDT, DT_MOTA, GV_MAGV, LDT_MA, DT_TRANGTHAI, 
                      DT_KETQUADUKIEN, DT_THOIGIANBATDAU, DT_THOIGIANKETTHUC, DT_SLSV, DT_GHICHU) 
                      VALUES (?, ?, ?, ?, 'Chờ duyệt', ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH), ?, ?)";

$stmt = $conn->prepare($insert_project_sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "Lỗi chuẩn bị câu lệnh SQL: " . $conn->error;
    header('Location: register_project_form.php');
    exit;
}

$stmt->bind_param("sssssiis", 
    $project_title, 
    $project_description, 
    $lecturer_id, 
    $project_type, 
    $expected_results, 
    $implementation_time,
    $member_count,
    $project_notes
);

// Thực hiện thêm đề tài mới
if ($stmt->execute()) {
    // Thêm sinh viên như người đề xuất/chủ nhiệm đề tài
    $registration_sql = "INSERT INTO yeu_cau_dang_ky 
                        (YC_THOIGIAN, YC_NOIDUNG, YC_TRANGTHAI, DT_MADT, SV_MASV, YC_VAITRO) 
                        VALUES (NOW(), ?, 'Đang chờ duyệt', ?, ?, 'Chủ nhiệm')";
    $stmt = $conn->prepare($registration_sql);
    $stmt->bind_param("sss", $lecturer_message, $project_id, $student_id);
    
    if ($stmt->execute()) {
        // Thêm thông báo cho giảng viên
        $notification_sql = "INSERT INTO thong_bao 
                            (TB_NOIDUNG, TB_THOIGIAN, TB_DANHDOC, TB_LOAI, DT_MADT, GV_MAGV, SV_MASV) 
                            VALUES (?, NOW(), 0, 'Đề xuất đề tài', ?, ?, ?)";
        
        // Lấy thông tin sinh viên
        $student_name_query = "SELECT CONCAT(SV_HOSV, ' ', SV_TENSV) AS SV_HOTEN FROM sinh_vien WHERE SV_MASV = ?";
        $stmt = $conn->prepare($student_name_query);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_name = $result->fetch_assoc()['SV_HOTEN'];
        
        $notification_content = "Sinh viên {$student_name} đã đề xuất đề tài nghiên cứu mới: {$project_title}";
        
        $stmt = $conn->prepare($notification_sql);
        $stmt->bind_param("ssss", $notification_content, $project_id, $lecturer_id, $student_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = "Đề xuất đề tài đã được gửi thành công! Vui lòng đợi phê duyệt từ giảng viên.";
    } else {
        $_SESSION['error_message'] = "Có lỗi xảy ra khi đăng ký: " . $stmt->error;
    }
} else {
    $_SESSION['error_message'] = "Có lỗi xảy ra khi tạo đề tài mới: " . $stmt->error;
}

// Đóng kết nối
$stmt->close();
$conn->close();

// Chuyển hướng về trang quản lý đề tài
header('Location: student_manage_projects.php');
exit;
?>