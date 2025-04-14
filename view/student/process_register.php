<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\process_register.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Khởi tạo mảng phản hồi
$response = [
    'success' => false,
    'message' => ''
];

// Kiểm tra xem phương thức yêu cầu có phải là POST không
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Phương thức yêu cầu không hợp lệ.';
    outputResponse($response);
    exit;
}

// Lấy dữ liệu từ form
$project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$student_id = $_SESSION['user_id'];
$hk_ma = getCurrentSemesterID($conn); // Lấy học kỳ hiện tại

// Kiểm tra dữ liệu đầu vào
if (empty($project_id) || empty($role)) {
    $response['message'] = 'Vui lòng điền đầy đủ thông tin.';
} else if (empty($hk_ma)) {
    $response['message'] = 'Không tìm thấy thông tin học kỳ hiện tại.';
} else {
    // Kiểm tra xem đề tài có tồn tại không
    $check_project_sql = "SELECT DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($check_project_sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $project = $result->fetch_assoc();
            
            // Kiểm tra trạng thái đề tài
            if ($project['DT_TRANGTHAI'] == 'Đang thực hiện' || $project['DT_TRANGTHAI'] == 'Chờ duyệt') {
                // Kiểm tra xem sinh viên đã tham gia đề tài này chưa
                $check_member_sql = "SELECT * FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
                $stmt = $conn->prepare($check_member_sql);
                $stmt->bind_param("ss", $project_id, $student_id);
                $stmt->execute();
                $member_result = $stmt->get_result();
                
                if ($member_result->num_rows > 0) {
                    $response['message'] = 'Bạn đã tham gia đề tài này rồi.';
                } else {
                    // Kiểm tra nếu đăng ký làm chủ nhiệm, xem đề tài đã có chủ nhiệm chưa
                    if ($role == 'Chủ nhiệm') {
                        $check_leader_sql = "SELECT * FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND CTTG_VAITRO = 'Chủ nhiệm'";
                        $stmt = $conn->prepare($check_leader_sql);
                        $stmt->bind_param("s", $project_id);
                        $stmt->execute();
                        $leader_result = $stmt->get_result();
                        
                        if ($leader_result->num_rows > 0) {
                            $response['message'] = 'Đề tài này đã có chủ nhiệm. Vui lòng đăng ký làm thành viên.';
                        } else {
                            // Thêm sinh viên vào đề tài với vai trò chủ nhiệm
                            registerStudent($conn, $project_id, $student_id, $role, $hk_ma, $response);
                        }
                    } else {
                        // Thêm sinh viên vào đề tài với vai trò thành viên
                        registerStudent($conn, $project_id, $student_id, $role, $hk_ma, $response);
                    }
                }
            } else {
                $response['message'] = 'Không thể đăng ký đề tài này do trạng thái không phù hợp.';
            }
        } else {
            $response['message'] = 'Đề tài không tồn tại.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Lỗi hệ thống: ' . $conn->error;
    }
}

// Hàm đăng ký sinh viên vào đề tài
function registerStudent($conn, $project_id, $student_id, $role, $hk_ma, &$response) {
    $insert_sql = "INSERT INTO chi_tiet_tham_gia (DT_MADT, SV_MASV, HK_MA, CTTG_VAITRO, CTTG_NGAYTHAMGIA) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $project_id, $student_id, $hk_ma, $role);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Đăng ký đề tài thành công!';
        } else {
            $response['message'] = 'Lỗi khi đăng ký: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Lỗi hệ thống: ' . $conn->error;
    }
}

// Hàm lấy mã học kỳ hiện tại
function getCurrentSemesterID($conn) {
    $hk_sql = "SELECT HK_MA FROM hoc_ki WHERE CURDATE() BETWEEN HK_NGAYBD AND HK_NGAYKT LIMIT 1";
    $result = $conn->query($hk_sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['HK_MA'];
    } else {
        // Nếu không có học kỳ hiện tại, lấy học kỳ mới nhất
        $latest_sql = "SELECT HK_MA FROM hoc_ki ORDER BY HK_NGAYBD DESC LIMIT 1";
        $latest_result = $conn->query($latest_sql);
        
        if ($latest_result && $latest_result->num_rows > 0) {
            $row = $latest_result->fetch_assoc();
            return $row['HK_MA'];
        }
    }
    
    return null;
}

// Trả về kết quả
function outputResponse($response) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Trả về phản hồi JSON nếu là AJAX
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Nếu không phải AJAX, đặt thông báo và chuyển hướng
        if ($response['success']) {
            $_SESSION['success_message'] = $response['message'];
        } else {
            $_SESSION['error_message'] = $response['message'];
        }
        header('Location: student_manage_projects.php');
    }
    exit;
}

outputResponse($response);
?>