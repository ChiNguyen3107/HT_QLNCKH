<?php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';

// Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra nếu không phải là POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Phương thức không được hỗ trợ!";
    header('Location: register_project_form.php');
    exit();
}

// Lấy học kỳ hiện tại từ CSDL
function getCurrentSemesterID($conn)
{
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
        // Nếu không có học kỳ nào, dùng giá trị mặc định
        return 'HK2-2024';
    }
}

// Hàm tạo mã DT_MADT mới
function generateProjectID($conn)
{
    // Lấy mã đề tài cao nhất hiện tại
    $query = "SELECT MAX(SUBSTRING(DT_MADT, 3)) AS max_id FROM de_tai_nghien_cuu";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $next_id = intval($row['max_id']) + 1;
    } else {
        $next_id = 1;
    }

    // Format: DT0000001, DT0000002, ...
    return 'DT' . str_pad($next_id, 7, '0', STR_PAD_LEFT);
}

try {
    // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();

    // ===== LẤY DỮ LIỆU TỪ FORM =====
    // Thông tin cơ bản về đề tài
    $project_title = trim($_POST['project_title']);
    $priority_field = $_POST['priority_field'];
    $research_field = $_POST['research_field'];
    $research_type = $_POST['research_type'];
    $faculty_id = $_POST['faculty_id'];
    $project_category = $_POST['project_category'];
    $member_count = intval($_POST['member_count']);
    $implementation_time = intval($_POST['implementation_time']); // Số tháng (mặc định 6)

    // Thông tin chủ nhiệm
    $leader_student_id = $_POST['leader_student_id'];
    $leader_name = $_POST['leader_name'];
    $leader_dob = $_POST['leader_dob'];
    $leader_class = $_POST['leader_class'];
    $leader_phone = $_POST['leader_phone'];
    $leader_year_group = $_POST['leader_year_group'];
    $leader_email = $_POST['leader_email'];

    // Thông tin giảng viên hướng dẫn
    $advisor_id = $_POST['advisor_id'];
    $advisor_expertise = $_POST['advisor_expertise'];
    $advisor_role = $_POST['advisor_role'];

    // Mô tả và kết quả
    $project_description = trim($_POST['project_description']);
    $expected_results = trim($_POST['expected_results']);

    // Thành viên tham gia (nếu có)
    $member_names = isset($_POST['member_name']) ? $_POST['member_name'] : [];
    $member_student_ids = isset($_POST['member_student_id']) ? $_POST['member_student_id'] : [];
    $member_dobs = isset($_POST['member_dob']) ? $_POST['member_dob'] : [];
    $member_classes = isset($_POST['member_class']) ? $_POST['member_class'] : [];
    $member_phones = isset($_POST['member_phone']) ? $_POST['member_phone'] : [];
    $member_year_groups = isset($_POST['member_year_group']) ? $_POST['member_year_group'] : [];
    $member_emails = isset($_POST['member_email']) ? $_POST['member_email'] : [];

    // ===== VALIDATE DỮ LIỆU =====
    // Kiểm tra trường bắt buộc
    if (
        empty($project_title) || empty($priority_field) || empty($research_field) ||
        empty($research_type) || empty($faculty_id) || empty($project_category) ||
        empty($advisor_id) || empty($project_description)
    ) {
        throw new Exception("Vui lòng điền đầy đủ thông tin đề tài!");
    }

    // ===== XỬ LÝ FILE TẢI LÊN (NẾU CÓ) =====
    $project_outline_path = null;
    if (isset($_FILES['project_outline']) && $_FILES['project_outline']['error'] === UPLOAD_ERR_OK) {
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        $file_extension = pathinfo($_FILES['project_outline']['name'], PATHINFO_EXTENSION);

        // Kiểm tra định dạng file
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            throw new Exception("Chỉ chấp nhận file PDF, DOC, DOCX!");
        }

        // Tạo thư mục lưu trữ nếu chưa có
        $upload_dir = '../../uploads/project_outlines/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Tạo tên file duy nhất
        $project_outline_path = $upload_dir . uniqid('outline_') . '_' . $_FILES['project_outline']['name'];

        // Di chuyển file tải lên vào thư mục lưu trữ
        if (!move_uploaded_file($_FILES['project_outline']['tmp_name'], $project_outline_path)) {
            throw new Exception("Không thể lưu file đính kèm!");
        }
    }

    // ===== THÊM DỮ LIỆU VÀO DATABASE =====
    // Lấy học kỳ hiện tại
    $current_semester = getCurrentSemesterID($conn);
    $current_date = date('Y-m-d H:i:s');
    $current_date_only = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+$implementation_time months"));

    // Tạo mã đề tài mới
    $project_id = generateProjectID($conn);

    // 1. Kiểm tra ràng buộc khóa ngoại và cấu trúc bảng
    // Khi đăng ký đề tài mới, không cần tạo quyết định nghiệm thu
    // Quyết định sẽ được tạo sau khi đề tài được duyệt và cần nghiệm thu
    $decision_id = null; // Luôn để NULL cho đề tài mới

    // 2. Thêm đề tài nghiên cứu (không cần QD_SO cho đề tài mới)
    // Lưu thời lượng thực hiện (tháng) vào DT_GHICHU để dùng cho hợp đồng
    $duration_note = 'duration_months=' . $implementation_time;

    $project_query = "INSERT INTO de_tai_nghien_cuu 
                     (DT_MADT, LDT_MA, GV_MAGV, LVNC_MA, LVUT_MA, DT_TENDT, DT_MOTA, DT_TRANGTHAI, DT_FILEBTM, QD_SO, DT_NGAYTAO, DT_SLSV, HD_MA, DT_GHICHU) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ duyệt', ?, NULL, NOW(), ?, 'HD001', ?)";
    $project_stmt = $conn->prepare($project_query);

    if ($project_stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh thêm đề tài: " . $conn->error);
    }

    $project_stmt->bind_param(
        "ssssssssis",
        $project_id,
        $project_category,
        $advisor_id,
        $research_field,
        $priority_field,
        $project_title,
        $project_description,
        $project_outline_path,
        $member_count,
        $duration_note
    );

    if (!$project_stmt->execute()) {
        throw new Exception("Lỗi khi thêm đề tài: " . $project_stmt->error);
    }

    // 4. Thêm chủ nhiệm vào chi_tiet_tham_gia
    $leader_query = "INSERT INTO chi_tiet_tham_gia (SV_MASV, DT_MADT, HK_MA, CTTG_VAITRO, CTTG_NGAYTHAMGIA) 
                    VALUES (?, ?, ?, 'Chủ nhiệm', ?)";
    $leader_stmt = $conn->prepare($leader_query);
    if ($leader_stmt === false) {
        throw new Exception("Lỗi khi chuẩn bị câu lệnh thêm chủ nhiệm: " . $conn->error);
    }

    $leader_stmt->bind_param("ssss", $leader_student_id, $project_id, $current_semester, $current_date_only);

    if (!$leader_stmt->execute()) {
        throw new Exception("Lỗi khi thêm chủ nhiệm: " . $leader_stmt->error);
    }

    // 5. Thêm các thành viên khác (nếu có)
    if (count($member_student_ids) > 0) {
        foreach ($member_student_ids as $index => $member_id) {
            // Kiểm tra sinh viên trong CSDL
            $check_query = "SELECT SV_MASV FROM sinh_vien WHERE SV_MASV = ?";
            $check_stmt = $conn->prepare($check_query);
            if ($check_stmt) {
                $check_stmt->bind_param("s", $member_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                // Nếu sinh viên chưa tồn tại, thêm vào bảng sinh_vien
                if ($check_result->num_rows == 0) {
                    // Tách họ và tên
                    $name_parts = explode(' ', $member_names[$index], 2);
                    $last_name = isset($name_parts[0]) ? $name_parts[0] : '';
                    $first_name = isset($name_parts[1]) ? $name_parts[1] : $member_names[$index];

                    // Tìm lớp trong CSDL
                    $class_exists = false;
                    $class_query = "SELECT LOP_MA FROM lop WHERE LOP_TEN = ?";
                    $class_stmt = $conn->prepare($class_query);
                    if ($class_stmt) {
                        $class_stmt->bind_param("s", $member_classes[$index]);
                        $class_stmt->execute();
                        $class_result = $class_stmt->get_result();
                        if ($class_result->num_rows > 0) {
                            $class_row = $class_result->fetch_assoc();
                            $class_id = $class_row['LOP_MA'];
                            $class_exists = true;
                        }
                    }

                    // Lấy lớp mặc định nếu không tìm thấy
                    if (!$class_exists) {
                        $default_class_query = "SELECT LOP_MA FROM lop LIMIT 1";
                        $default_class_result = $conn->query($default_class_query);
                        if ($default_class_result && $default_class_result->num_rows > 0) {
                            $default_class_row = $default_class_result->fetch_assoc();
                            $class_id = $default_class_row['LOP_MA'];
                        } else {
                            $class_id = 'CNT46002'; // Mặc định
                        }
                    }

                    // Thêm sinh viên mới
                    $add_student = "INSERT INTO sinh_vien (SV_MASV, LOP_MA, SV_HOSV, SV_TENSV, SV_GIOITINH, SV_SDT, SV_EMAIL, SV_MATKHAU, SV_NGAYSINH) 
                                   VALUES (?, ?, ?, ?, 1, ?, ?, MD5('123456'), ?)";
                    $add_stmt = $conn->prepare($add_student);
                    if ($add_stmt) {
                        $add_stmt->bind_param(
                            "sssssss",
                            $member_id,
                            $class_id,
                            $last_name,
                            $first_name,
                            $member_phones[$index],
                            $member_emails[$index],
                            $member_dobs[$index]
                        );

                        $add_stmt->execute();
                    }
                }

                // Thêm thành viên vào chi_tiet_tham_gia
                $member_query = "INSERT INTO chi_tiet_tham_gia (SV_MASV, DT_MADT, HK_MA, CTTG_VAITRO, CTTG_NGAYTHAMGIA) 
                                VALUES (?, ?, ?, 'Thành viên', ?)";
                $member_stmt = $conn->prepare($member_query);
                if ($member_stmt) {
                    $member_stmt->bind_param("ssss", $member_id, $project_id, $current_semester, $current_date_only);
                    $member_stmt->execute();
                }
            }
        }
    }

    // 6. Thêm vào bảng tiến độ đề tài (giai đoạn khởi động)
    $progress_id = 'TD' . date('dmHi') . rand(0, 9);
    $progress_query = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_PHANTRAMHOANTHANH, TDDT_NGAYCAPNHAT) 
                      VALUES (?, ?, ?, 'Khởi động đề tài', ?, 0, ?)";
    $progress_stmt = $conn->prepare($progress_query);

    if ($progress_stmt) {
        $progress_content = "Đã đăng ký đề tài, đang chờ phê duyệt.";
        $progress_stmt->bind_param("sssss", $progress_id, $project_id, $leader_student_id, $progress_content, $current_date);
        $progress_stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Chuyển hướng với thông báo thành công
    $_SESSION['success_message'] = "Đăng ký đề tài thành công! Đề tài của bạn đã được gửi đến giảng viên hướng dẫn và cán bộ quản lý để xem xét phê duyệt.";
    header('Location: registration_success.php'); // Chuyển hướng đến trang thông báo thành công
    exit;

} catch (Exception $e) {
    // Rollback nếu có lỗi
    if ($conn->connect_errno == 0) {
        $conn->rollback();
    }

    // Xóa file đã upload nếu có lỗi
    if (!empty($project_outline_path) && file_exists($project_outline_path)) {
        unlink($project_outline_path);
    }

    // Log lỗi
    error_log("Lỗi đăng ký đề tài: " . $e->getMessage());

    // Thông báo lỗi và quay lại form
    $_SESSION['error_message'] = "Lỗi: " . $e->getMessage();
    header("Location: register_project_form.php");
    exit();
}
?>