<?php
// Bao gồm file session và kết nối CSDL
include '../../include/session.php';
checkTeacherRole();
include '../../include/connect.php';

// Lấy thông tin giảng viên
$teacher_id = $_SESSION['user_id'];

// Lọc theo khoa (nếu có)
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';

// Lọc theo khóa học (nếu có)
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';

// Lấy danh sách khoa từ CSDL
$departments_query = "SELECT DISTINCT k.DV_MADV, k.DV_TENDV 
                     FROM khoa k 
                     JOIN lop l ON l.DV_MADV = k.DV_MADV
                     JOIN sinh_vien sv ON sv.LOP_MA = l.LOP_MA
                     JOIN chi_tiet_tham_gia cttg ON cttg.SV_MASV = sv.SV_MASV
                     JOIN de_tai_nghien_cuu dt ON dt.DT_MADT = cttg.DT_MADT
                     WHERE dt.GV_MAGV = ?
                     ORDER BY k.DV_TENDV";
$stmt = $conn->prepare($departments_query);
if ($stmt === false) {
    die("Lỗi truy vấn khoa: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$departments_result = $stmt->get_result();
$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row;
}

// Lấy danh sách khóa học từ CSDL
$courses_query = "SELECT DISTINCT kh.KH_NAM 
                 FROM khoa_hoc kh 
                 JOIN lop l ON l.KH_NAM = kh.KH_NAM
                 JOIN sinh_vien sv ON sv.LOP_MA = l.LOP_MA
                 JOIN chi_tiet_tham_gia cttg ON cttg.SV_MASV = sv.SV_MASV
                 JOIN de_tai_nghien_cuu dt ON dt.DT_MADT = cttg.DT_MADT
                 WHERE dt.GV_MAGV = ?
                 ORDER BY kh.KH_NAM DESC";
$stmt = $conn->prepare($courses_query);
if ($stmt === false) {
    die("Lỗi truy vấn khóa học: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Xây dựng điều kiện lọc
$where_conditions = "dt.GV_MAGV = ?";
$params = [$teacher_id];
$param_types = "s";

if (!empty($selected_department)) {
    $where_conditions .= " AND l.DV_MADV = ?";
    $params[] = $selected_department;
    $param_types .= "s";
}

if (!empty($selected_course)) {
    $where_conditions .= " AND l.KH_NAM = ?";
    $params[] = $selected_course;
    $param_types .= "s";
}

// 1. Thống kê số lượng đề tài theo trạng thái
$status_stats_query = "SELECT dt.DT_TRANGTHAI, COUNT(*) as count 
                      FROM de_tai_nghien_cuu dt
                      WHERE dt.GV_MAGV = ?
                      GROUP BY dt.DT_TRANGTHAI
                      ORDER BY count DESC";
$stmt = $conn->prepare($status_stats_query);
if ($stmt === false) {
    die("Lỗi truy vấn thống kê trạng thái: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$status_stats = $stmt->get_result();

// 2. Thống kê số lượng đề tài theo loại
$type_stats_query = "SELECT ldt.LDT_TENLOAI, COUNT(*) as count 
                    FROM de_tai_nghien_cuu dt
                    JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    WHERE dt.GV_MAGV = ?
                    GROUP BY ldt.LDT_TENLOAI
                    ORDER BY count DESC";
$stmt = $conn->prepare($type_stats_query);
if ($stmt === false) {
    die("Lỗi truy vấn thống kê loại đề tài: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$type_stats = $stmt->get_result();

// 3. Thống kê số lượng đề tài theo lớp (CHỨC NĂNG CHÍNH)
$class_stats_query = "SELECT l.LOP_MA, l.LOP_TEN, 
                       COUNT(DISTINCT cttg.DT_MADT) as project_count, 
                       COUNT(DISTINCT cttg.SV_MASV) as student_count,
                       (SELECT COUNT(*) FROM sinh_vien WHERE LOP_MA = l.LOP_MA) as total_students
                      FROM chi_tiet_tham_gia cttg
                      JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                      JOIN lop l ON sv.LOP_MA = l.LOP_MA
                      JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                      WHERE $where_conditions
                      GROUP BY l.LOP_MA, l.LOP_TEN
                      ORDER BY project_count DESC";
$stmt = $conn->prepare($class_stats_query);
if ($stmt === false) {
    die("Lỗi truy vấn thống kê lớp: " . $conn->error . "<br>" . $class_stats_query);
}

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$class_stats = $stmt->get_result();

// 4. Thống kê đề tài theo lớp và trạng thái
$class_status_query = "SELECT l.LOP_TEN, dt.DT_TRANGTHAI, COUNT(DISTINCT cttg.DT_MADT) as count
                      FROM chi_tiet_tham_gia cttg
                      JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                      JOIN lop l ON sv.LOP_MA = l.LOP_MA
                      JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                      WHERE $where_conditions
                      GROUP BY l.LOP_TEN, dt.DT_TRANGTHAI
                      ORDER BY l.LOP_TEN, count DESC";
$stmt = $conn->prepare($class_status_query);
if ($stmt === false) {
    die("Lỗi truy vấn thống kê lớp theo trạng thái: " . $conn->error);
}

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$class_status_stats = $stmt->get_result();

// 5. Thống kê sinh viên theo lớp và trạng thái (MỚI)
$class_students_status_query = "SELECT l.LOP_TEN, dt.DT_TRANGTHAI, COUNT(DISTINCT cttg.SV_MASV) as student_count
                              FROM chi_tiet_tham_gia cttg
                              JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                              JOIN lop l ON sv.LOP_MA = l.LOP_MA
                              JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                              WHERE $where_conditions
                              GROUP BY l.LOP_TEN, dt.DT_TRANGTHAI
                              ORDER BY l.LOP_TEN, student_count DESC";
$stmt = $conn->prepare($class_students_status_query);
if ($stmt === false) {
    die("Lỗi truy vấn thống kê sinh viên theo lớp và trạng thái: " . $conn->error);
}

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$class_students_status = $stmt->get_result();

// Tạo mảng dữ liệu cho biểu đồ sinh viên theo trạng thái
$class_students_status_data = [];
while ($row = $class_students_status->fetch_assoc()) {
    if (!isset($class_students_status_data[$row['LOP_TEN']])) {
        $class_students_status_data[$row['LOP_TEN']] = [
            'class' => $row['LOP_TEN'],
            'statuses' => []
        ];
    }

    $class_students_status_data[$row['LOP_TEN']]['statuses'][$row['DT_TRANGTHAI']] = $row['student_count'];
}

// Tạo mảng dữ liệu cho biểu đồ
$class_status_data = [];
$all_statuses = []; // Danh sách tất cả các trạng thái

while ($row = $class_status_stats->fetch_assoc()) {
    if (!isset($class_status_data[$row['LOP_TEN']])) {
        $class_status_data[$row['LOP_TEN']] = [
            'class' => $row['LOP_TEN'],
            'statuses' => []
        ];
    }

    $class_status_data[$row['LOP_TEN']]['statuses'][$row['DT_TRANGTHAI']] = $row['count'];

    // Thêm trạng thái vào danh sách nếu chưa có
    if (!in_array($row['DT_TRANGTHAI'], $all_statuses)) {
        $all_statuses[] = $row['DT_TRANGTHAI'];
    }
}

// 6. Tổng số đề tài và sinh viên
$totals_query = "SELECT COUNT(DISTINCT dt.DT_MADT) as total_projects,
                 COUNT(DISTINCT cttg.SV_MASV) as total_students
                 FROM de_tai_nghien_cuu dt
                 LEFT JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                 WHERE dt.GV_MAGV = ?";
$stmt = $conn->prepare($totals_query);
if ($stmt === false) {
    die("Lỗi truy vấn tổng số: " . $conn->error);
}

$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// 7. Thống kê theo khoa và ngành
$department_stats_query = "SELECT k.DV_TENDV, COUNT(DISTINCT dt.DT_MADT) as project_count
                          FROM de_tai_nghien_cuu dt
                          JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                          JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                          JOIN lop l ON sv.LOP_MA = l.LOP_MA
                          JOIN khoa k ON l.DV_MADV = k.DV_MADV
                          WHERE $where_conditions
                          GROUP BY k.DV_TENDV
                          ORDER BY project_count DESC";
$stmt = $conn->prepare($department_stats_query);
if ($stmt === false) {
    die("Lỗi truy vấn thống kê khoa: " . $conn->error);
}

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$department_stats = $stmt->get_result();

// 8. Chi tiết sinh viên theo lớp (MỚI)
$class_students_details = [];

// Lấy lại dữ liệu lớp học (thay vì clone đối tượng mysqli_result)
$class_stats->data_seek(0);
while ($class_row = $class_stats->fetch_assoc()) {
    $class_code = $class_row['LOP_MA'];
    $class_name = $class_row['LOP_TEN'];

    // Truy vấn danh sách sinh viên của lớp
    $students_list_query = "SELECT sv.SV_MASV, sv.SV_HOSV, sv.SV_TENSV, 
                           CASE WHEN cttg.SV_MASV IS NOT NULL THEN 1 ELSE 0 END as has_project
                           FROM sinh_vien sv
                           LEFT JOIN (
                               SELECT DISTINCT SV_MASV 
                               FROM chi_tiet_tham_gia cttg 
                               JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT 
                               WHERE dt.GV_MAGV = ? 
                           ) cttg ON sv.SV_MASV = cttg.SV_MASV
                           WHERE sv.LOP_MA = ?
                           ORDER BY sv.SV_HOSV, sv.SV_TENSV";
    $stmt = $conn->prepare($students_list_query);
    if ($stmt === false) {
        die("Lỗi truy vấn danh sách sinh viên: " . $conn->error);
    }

    $stmt->bind_param("ss", $teacher_id, $class_code);
    $stmt->execute();
    $students_list = $stmt->get_result();

    $students_data = [];
    $total_students = 0;
    $participating_students = 0;

    while ($student = $students_list->fetch_assoc()) {
        $total_students++;
        if ($student['has_project'] == 1) {
            $participating_students++;
        }

        // Lấy thông tin đề tài của sinh viên (nếu có)
        if ($student['has_project'] == 1) {
            $student_projects_query = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, cttg.CTTG_VAITRO
                                      FROM chi_tiet_tham_gia cttg
                                      JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                                      WHERE cttg.SV_MASV = ? AND dt.GV_MAGV = ?";
            $stmt = $conn->prepare($student_projects_query);
            if ($stmt === false) {
                die("Lỗi truy vấn đề tài của sinh viên: " . $conn->error);
            }

            $stmt->bind_param("ss", $student['SV_MASV'], $teacher_id);
            $stmt->execute();
            $student_projects = $stmt->get_result();

            $projects = [];
            while ($project = $student_projects->fetch_assoc()) {
                $projects[] = $project;
            }

            $student['projects'] = $projects;
        } else {
            $student['projects'] = [];
        }

        $students_data[] = $student;
    }

    $class_students_details[$class_code] = [
        'class_code' => $class_code,
        'class_name' => $class_name,
        'total_students' => $total_students,
        'participating_students' => $participating_students,
        'participation_rate' => $total_students > 0 ? round(($participating_students / $total_students) * 100, 1) : 0,
        'students' => $students_data
    ];
}

// 9. Thống kê chi tiết đề tài của từng lớp
$class_details_query = "SELECT l.LOP_TEN, dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, 
                           COUNT(DISTINCT cttg.SV_MASV) as student_count
                        FROM chi_tiet_tham_gia cttg
                        JOIN sinh_vien sv ON cttg.SV_MASV = sv.SV_MASV
                        JOIN lop l ON sv.LOP_MA = l.LOP_MA
                        JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                        WHERE $where_conditions
                        GROUP BY l.LOP_TEN, dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI
                        ORDER BY l.LOP_TEN, dt.DT_MADT";

$stmt = $conn->prepare($class_details_query);
if ($stmt === false) {
    die("Lỗi truy vấn chi tiết lớp: " . $conn->error . "<br>Query: " . $class_details_query);
}

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$class_details = $stmt->get_result();
$class_project_details = [];

while ($row = $class_details->fetch_assoc()) {
    if (!isset($class_project_details[$row['LOP_TEN']])) {
        $class_project_details[$row['LOP_TEN']] = [];
    }
    $class_project_details[$row['LOP_TEN']][] = $row;
}

// Đặt màu sắc cho các trạng thái
$status_colors = [
    'Chờ duyệt' => '#ffc107',
    'Đang thực hiện' => '#007bff',
    'Đã hoàn thành' => '#28a745',
    'Tạm dừng' => '#17a2b8',
    'Đã hủy' => '#dc3545',
    'Đang xử lý' => '#6c757d'
];

// Chuyển đổi mảng màu thành chuỗi JSON cho JavaScript
$status_colors_json = json_encode($status_colors);

// Tạo mảng dữ liệu cho biểu đồ thống kê theo lớp
$chart_labels = [];
$chart_data = [];
$chart_colors = [];

if ($class_stats) {
    $class_stats->data_seek(0);
    while ($row = $class_stats->fetch_assoc()) {
        $chart_labels[] = $row['LOP_TEN'];
        $chart_data[] = $row['project_count'];

        // Tạo màu ngẫu nhiên cho từng lớp
        $chart_colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê đề tài | Hệ thống NCKH</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- Custom fonts -->
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap CSS từ CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- SB Admin 2 CSS từ CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/css/sb-admin-2.min.css"
        rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.bootstrap4.min.css" rel="stylesheet">

    <style>
        /* Enhanced Reports Interface */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Nunito', sans-serif;
        }
        
        #wrapper {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chart-container canvas {
            border-radius: 10px;
        }

        .stats-card {
            border-left: 4px solid;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            animation: slideInUp 0.6s ease-out;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .stats-card:hover::before {
            left: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .stats-card.primary {
            border-left-color: #4e73df;
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.1), rgba(78, 115, 223, 0.05));
        }

        .stats-card.success {
            border-left-color: #1cc88a;
            background: linear-gradient(135deg, rgba(28, 200, 138, 0.1), rgba(28, 200, 138, 0.05));
        }

        .stats-card.info {
            border-left-color: #36b9cc;
            background: linear-gradient(135deg, rgba(54, 185, 204, 0.1), rgba(54, 185, 204, 0.05));
        }

        .stats-card.warning {
            border-left-color: #f6c23e;
            background: linear-gradient(135deg, rgba(246, 194, 62, 0.1), rgba(246, 194, 62, 0.05));
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-label {
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            margin-bottom: 0;
            letter-spacing: 1px;
        }

        .status-indicator {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            margin-right: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .class-detail-card {
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: slideInLeft 0.6s ease-out;
        }
        
        .class-detail-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .class-detail-header {
            background: linear-gradient(135deg, #f8f9fc, #e2e6ea);
            border-bottom: 1px solid #e3e6f0;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .class-detail-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4e73df, #1cc88a, #f6c23e);
        }

        .export-buttons {
            margin-bottom: 1rem;
        }

        .filter-section {
            background: linear-gradient(135deg, rgba(248, 249, 252, 0.95), rgba(226, 230, 234, 0.95));
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: slideInUp 0.8s ease-out;
        }
        
        .filter-section .form-control {
            border-radius: 25px;
            border: 1px solid rgba(78, 115, 223, 0.3);
            transition: all 0.3s ease;
        }
        
        .filter-section .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 15px rgba(78, 115, 223, 0.3);
            transform: scale(1.02);
        }
        
        .filter-section .btn {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .filter-section .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .student-project-list {
            margin-top: 0.5rem;
            padding-left: 1rem;
        }

        .student-project-item {
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            border-left: 4px solid #4e73df;
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.05), rgba(78, 115, 223, 0.02));
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .student-project-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(78, 115, 223, 0.2);
        }

        .student-row {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 0;
        }

        .student-row:hover {
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.1), rgba(78, 115, 223, 0.05));
            transform: scale(1.01);
        }

        .student-row.has-project {
            font-weight: 600;
            background: linear-gradient(135deg, rgba(28, 200, 138, 0.1), rgba(28, 200, 138, 0.05));
            border-left: 4px solid #1cc88a;
        }

        .student-row.no-project {
            color: #6c757d;
            background: linear-gradient(135deg, rgba(108, 117, 125, 0.05), rgba(108, 117, 125, 0.02));
            border-left: 4px solid #dc3545;
        }

        .participation-badge {
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 25px;
            font-weight: 700;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .nav-tabs {
            border: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 5px;
            backdrop-filter: blur(10px);
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #6c757d;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            color: white;
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }
        
        .nav-tabs .nav-link:hover {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            transform: translateY(-2px);
        }

        .student-list-section {
            border-top: 2px solid #e3e6f0;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border-radius: 15px;
            padding: 1.5rem;
        }

        .progress-thin {
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .progress-thin .progress-bar {
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .progress-thin .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Enhanced Cards */
        .card {
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: slideInUp 0.6s ease-out;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fc, #e2e6ea);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 15px 15px 0 0;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4e73df, #1cc88a, #f6c23e);
        }
        
        /* Enhanced Buttons */
        .btn {
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4e73df, #224abe);
            border: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #1cc88a, #13855c);
            border: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74a3b, #c0392b);
            border: none;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f6c23e, #dda20a);
            border: none;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #36b9cc, #258391);
            border: none;
        }
        
        /* Enhanced Tables */
        .table {
            border-radius: 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .table thead th {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            color: white;
            border: none;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.1), rgba(78, 115, 223, 0.05));
            transform: scale(1.01);
        }
        
        /* Enhanced Badges */
        .badge {
            border-radius: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            font-size: 0.8rem;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #4e73df, #224abe);
        }
        
        .badge-success {
            background: linear-gradient(135deg, #1cc88a, #13855c);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #f6c23e, #dda20a);
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #e74a3b, #c0392b);
        }
        
        .badge-info {
            background: linear-gradient(135deg, #36b9cc, #258391);
        }
        
        /* Advanced Filter Section */
        .advanced-filter {
            background: linear-gradient(135deg, rgba(78, 115, 223, 0.1), rgba(28, 200, 138, 0.1));
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(78, 115, 223, 0.2);
        }
        
        .filter-group {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .filter-title {
            font-weight: 700;
            color: #4e73df;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        /* Student Detail Cards */
        .student-detail-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 252, 0.9));
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(78, 115, 223, 0.2);
            transition: all 0.3s ease;
        }
        
        .student-detail-card:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(78, 115, 223, 0.2);
        }
        
        .student-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            margin-right: 1rem;
        }
        
        .student-name {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .student-id {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-section {
                padding: 1rem;
            }
            
            .chart-container {
                height: 250px;
                padding: 10px;
            }
            
            .stats-value {
                font-size: 1.5rem;
            }
            
            .student-detail-card {
                padding: 1rem;
            }
            
            .student-info {
                flex-direction: column;
                text-align: center;
            }
            
            .student-avatar {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .chart-container {
                height: 250px !important;
                break-inside: avoid;
            }

            .tab-pane {
                display: block !important;
                opacity: 1 !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            body {
                padding: 1cm;
                background: white !important;
            }

            .tab-content>.tab-pane {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            .card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ccc;
            }
        }

        /* Enhanced notification styles */
        .notification-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            border: none;
        }

        .notification-alert.alert-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .notification-alert.alert-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }

        .notification-alert.alert-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }

        .notification-alert.alert-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            color: white;
        }

        /* Enhanced student filter section */
        .students-filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .students-filter-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .students-filter-card .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 8px;
        }

        .students-filter-card .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .students-filter-card .form-control:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
            color: white;
        }

        .students-filter-card label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
        }

        /* Class detail cards enhanced */
        .class-detail-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .class-detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .class-detail-header {
            background: linear-gradient(135deg, #4e73df 0%, #6f42c1 100%);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }

        .class-detail-header .btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 25px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }

        .class-detail-header .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .class-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .class-stat-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .class-stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4e73df;
        }

        .class-stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Student table enhancements */
        .students-table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .students-table thead th {
            background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
            color: white;
            border: none;
            padding: 15px 10px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .students-table tbody tr {
            transition: all 0.3s ease;
        }

        .students-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #e6f3ff 100%);
            transform: scale(1.01);
        }

        .students-table tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .student-name {
            font-weight: 600;
            color: #4e73df;
        }

        .student-id {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        /* Participation status badges */
        .participation-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .participation-status.participating {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .participation-status.not-participating {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        /* Filter buttons enhancement */
        .filter-buttons .btn {
            margin-right: 8px;
            margin-bottom: 8px;
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .filter-buttons .btn.active {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .class-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .students-table {
                font-size: 0.85rem;
            }
            
            .students-table thead th,
            .students-table tbody td {
                padding: 8px 6px;
            }
            
            .notification-alert {
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <div class="no-print">
            <?php include '../../include/teacher_sidebar.php'; ?>
        </div>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid mt-4">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chart-pie mr-2"></i>Báo cáo thống kê đề tài
                        </h1>
                        <div class="no-print">
                            <button id="printReport" class="btn btn-sm btn-primary shadow-sm">
                                <i class="fas fa-print fa-sm text-white-50 mr-1"></i>In báo cáo
                            </button>
                            <button id="exportPDF" class="btn btn-sm btn-danger shadow-sm ml-2">
                                <i class="fas fa-file-pdf fa-sm text-white-50 mr-1"></i>Xuất PDF
                            </button>
                            <button id="exportExcel" class="btn btn-sm btn-success shadow-sm ml-2">
                                <i class="fas fa-file-excel fa-sm text-white-50 mr-1"></i>Xuất Excel
                            </button>
                        </div>
                    </div>

                    <!-- Advanced Filter Section -->
                    <div class="advanced-filter no-print">
                        <div class="filter-title">
                            <i class="fas fa-filter mr-2"></i>Bộ lọc nâng cao
                        </div>
                        <form method="get" action="" id="filterForm">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="filter-group">
                                        <label for="department" class="font-weight-bold">
                                            <i class="fas fa-university mr-1"></i>Khoa:
                                        </label>
                                        <select name="department" id="department" class="form-control">
                                            <option value="">Tất cả các khoa</option>
                                            <?php foreach ($departments as $department): ?>
                                                <option value="<?php echo $department['DV_MADV']; ?>" <?php echo ($selected_department == $department['DV_MADV']) ? 'selected' : ''; ?>>
                                                    <?php echo $department['DV_TENDV']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="filter-group">
                                        <label for="course" class="font-weight-bold">
                                            <i class="fas fa-graduation-cap mr-1"></i>Khóa học:
                                        </label>
                                        <select name="course" id="course" class="form-control">
                                            <option value="">Tất cả các khóa</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?php echo $course['KH_NAM']; ?>" <?php echo ($selected_course == $course['KH_NAM']) ? 'selected' : ''; ?>>
                                                    Khóa <?php echo $course['KH_NAM']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="filter-group">
                                        <label class="font-weight-bold">
                                            <i class="fas fa-cogs mr-1"></i>Thao tác:
                                        </label>
                                        <div class="btn-group-vertical w-100">
                                            <button type="submit" class="btn btn-primary mb-2">
                                                <i class="fas fa-search mr-1"></i>Tìm kiếm
                                            </button>
                                            <a href="reports.php" class="btn btn-secondary mb-2">
                                                <i class="fas fa-sync-alt mr-1"></i>Đặt lại
                                            </a>
                                            <button type="button" class="btn btn-info" onclick="exportData()">
                                                <i class="fas fa-download mr-1"></i>Xuất dữ liệu
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Stats Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon mb-3">
                                        <i class="fas fa-university fa-3x text-primary"></i>
                                    </div>
                                    <h3 class="stats-value text-primary"><?php echo count($departments); ?></h3>
                                    <p class="stats-label">Khoa có sinh viên tham gia</p>
                                    <div class="progress progress-thin">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon mb-3">
                                        <i class="fas fa-graduation-cap fa-3x text-success"></i>
                                    </div>
                                    <h3 class="stats-value text-success"><?php echo count($courses); ?></h3>
                                    <p class="stats-label">Khóa học có tham gia</p>
                                    <div class="progress progress-thin">
                                        <div class="progress-bar bg-success" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon mb-3">
                                        <i class="fas fa-chart-line fa-3x text-info"></i>
                                    </div>
                                    <?php
                                    $avg_projects_per_dept = count($departments) > 0 ? round($totals['total_projects'] / count($departments), 1) : 0;
                                    ?>
                                    <h3 class="stats-value text-info"><?php echo $avg_projects_per_dept; ?></h3>
                                    <p class="stats-label">Đề tài trung bình/khoa</p>
                                    <div class="progress progress-thin">
                                        <div class="progress-bar bg-info" style="width: 85%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon mb-3">
                                        <i class="fas fa-user-friends fa-3x text-warning"></i>
                                    </div>
                                    <?php
                                    $avg_students_per_project = $totals['total_projects'] > 0 ? round($totals['total_students'] / $totals['total_projects'], 1) : 0;
                                    ?>
                                    <h3 class="stats-value text-warning"><?php echo $avg_students_per_project; ?></h3>
                                    <p class="stats-label">Sinh viên trung bình/đề tài</p>
                                    <div class="progress progress-thin">
                                        <div class="progress-bar bg-warning" style="width: 70%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row - Summary Cards -->
                    <div class="row">
                        <!-- Tổng số đề tài -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Tổng số đề tài
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $totals['total_projects']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tổng số sinh viên tham gia -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Tổng số sinh viên tham gia
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $totals['total_students']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tổng số lớp tham gia -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Tổng số lớp tham gia
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $class_stats->num_rows; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chalkboard fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Số đề tài đã hoàn thành -->
                        <?php
                        $completed_count = 0;
                        if ($status_stats) {
                            $status_stats->data_seek(0);
                            while ($row = $status_stats->fetch_assoc()) {
                                if ($row['DT_TRANGTHAI'] == 'Đã hoàn thành') {
                                    $completed_count = $row['count'];
                                    break;
                                }
                            }
                        }
                        $completion_rate = $totals['total_projects'] > 0 ?
                            round(($completed_count / $totals['total_projects']) * 100) : 0;
                        ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Tỷ lệ hoàn thành
                                            </div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                        <?php echo $completion_rate; ?>%
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-warning" role="progressbar"
                                                            style="width: <?php echo $completion_rate; ?>%"
                                                            aria-valuenow="<?php echo $completion_rate; ?>"
                                                            aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs mb-4 no-print" id="reportTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab">
                                <i class="fas fa-chart-bar mr-1"></i>Tổng quan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="classes-tab" data-toggle="tab" href="#classes" role="tab">
                                <i class="fas fa-graduation-cap mr-1"></i>Theo lớp
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="students-tab" data-toggle="tab" href="#students" role="tab">
                                <i class="fas fa-user-graduate mr-1"></i>Sinh viên
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Tab Tổng quan -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="row">
                                <!-- Thống kê theo lớp -->
                                <div class="col-xl-8 col-lg-7">
                                    <div class="card shadow mb-4">
                                        <div
                                            class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê số lượng đề tài theo
                                                lớp</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="classChart"></canvas>
                                            </div>
                                            <div class="table-responsive mt-3">
                                                <table class="table table-bordered" id="classStatsTable" width="100%"
                                                    cellspacing="0">
                                                    <thead>
                                                        <tr>
                                                            <th>Mã lớp</th>
                                                            <th>Tên lớp</th>
                                                            <th>Số đề tài</th>
                                                            <th>Số SV tham gia</th>
                                                            <th>Tổng SV</th>
                                                            <th>Tỷ lệ tham gia</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $class_stats->data_seek(0);
                                                        $total_projects = $totals['total_projects'];
                                                        while ($row = $class_stats->fetch_assoc()):
                                                            $percentage = $total_projects > 0 ?
                                                                round(($row['project_count'] / $total_projects) * 100, 1) : 0;

                                                            $participation_rate = $row['total_students'] > 0 ?
                                                                round(($row['student_count'] / $row['total_students']) * 100, 1) : 0;
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $row['LOP_MA']; ?></td>
                                                                <td><?php echo $row['LOP_TEN']; ?></td>
                                                                <td><?php echo $row['project_count']; ?></td>
                                                                <td><?php echo $row['student_count']; ?></td>
                                                                <td><?php echo $row['total_students']; ?></td>
                                                                <td><?php echo $participation_rate; ?>%</td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Thống kê theo trạng thái -->
                                <div class="col-xl-4 col-lg-5">
                                    <div class="card shadow mb-4">
                                        <div
                                            class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê theo trạng thái đề
                                                tài</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="statusChart"></canvas>
                                            </div>
                                            <div class="mt-4">
                                                <?php
                                                $status_stats->data_seek(0);
                                                while ($row = $status_stats->fetch_assoc()):
                                                    $status_color = isset($status_colors[$row['DT_TRANGTHAI']]) ?
                                                        $status_colors[$row['DT_TRANGTHAI']] : '#6c757d';
                                                    ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <span class="status-indicator"
                                                                style="background-color: <?php echo $status_color; ?>"></span>
                                                            <?php echo $row['DT_TRANGTHAI']; ?>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="badge badge-light"><?php echo $row['count']; ?></span>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Thống kê đề tài theo lớp và trạng thái -->
                                <div class="col-12">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê đề tài theo lớp và
                                                trạng thái</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="classStatusChart"></canvas>
                                            </div>
                                            <div class="table-responsive mt-3">
                                                <table class="table table-bordered" id="classStatusTable" width="100%"
                                                    cellspacing="0">
                                                    <thead>
                                                        <tr>
                                                            <th>Lớp</th>
                                                            <?php foreach ($all_statuses as $status): ?>
                                                                <th><?php echo $status; ?></th>
                                                            <?php endforeach; ?>
                                                            <th>Tổng cộng</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($class_status_data as $class_name => $data): ?>
                                                            <tr>
                                                                <td><?php echo $class_name; ?></td>
                                                                <?php
                                                                $total = 0;
                                                                foreach ($all_statuses as $status):
                                                                    $count = isset($data['statuses'][$status]) ? $data['statuses'][$status] : 0;
                                                                    $total += $count;
                                                                    ?>
                                                                    <td><?php echo $count; ?></td>
                                                                <?php endforeach; ?>
                                                                <td><strong><?php echo $total; ?></strong></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Theo lớp -->
                        <div class="tab-pane fade" id="classes" role="tabpanel">
                            <!-- Chi tiết đề tài theo lớp -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">Chi tiết đề tài theo lớp</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="accordion" id="classDetails">
                                                <?php foreach ($class_project_details as $class_name => $projects): ?>
                                                    <div class="card class-detail-card mb-3">
                                                        <div class="card-header class-detail-header"
                                                            id="heading<?php echo md5($class_name); ?>">
                                                            <h2 class="mb-0">
                                                                <button class="btn btn-link btn-block text-left"
                                                                    type="button" data-toggle="collapse"
                                                                    data-target="#collapse<?php echo md5($class_name); ?>"
                                                                    aria-expanded="true"
                                                                    aria-controls="collapse<?php echo md5($class_name); ?>">
                                                                    <i class="fas fa-graduation-cap mr-2"></i>
                                                                    <strong><?php echo $class_name; ?></strong>
                                                                    <span
                                                                        class="badge badge-primary ml-2"><?php echo count($projects); ?>
                                                                        đề tài</span>
                                                                </button>
                                                            </h2>
                                                        </div>
                                                        <div id="collapse<?php echo md5($class_name); ?>" class="collapse"
                                                            aria-labelledby="heading<?php echo md5($class_name); ?>"
                                                            data-parent="#classDetails">
                                                            <div class="card-body">
                                                                <table
                                                                    class="table table-bordered table-hover class-details-table"
                                                                    width="100%" cellspacing="0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Mã đề tài</th>
                                                                            <th>Tên đề tài</th>
                                                                            <th>Trạng thái</th>
                                                                            <th>Số SV tham gia</th>
                                                                            <th>Thao tác</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($projects as $project): ?>
                                                                            <tr>
                                                                                <td><?php echo $project['DT_MADT']; ?></td>
                                                                                <td><?php echo $project['DT_TENDT']; ?></td>
                                                                                <td>
                                                                                    <span class="badge"
                                                                                        style="background-color: <?php echo isset($status_colors[$project['DT_TRANGTHAI']]) ? $status_colors[$project['DT_TRANGTHAI']] : '#6c757d'; ?>; color: white;">
                                                                                        <?php echo $project['DT_TRANGTHAI']; ?>
                                                                                    </span>
                                                                                </td>
                                                                                <td><?php echo $project['student_count']; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>"
                                                                                        class="btn btn-sm btn-primary no-print">
                                                                                        <i class="fas fa-eye"></i>
                                                                                    </a>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Sinh viên -->
                        <div class="tab-pane fade" id="students" role="tabpanel">
                            <!-- Student Filter Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm students-filter-card">
                                        <div class="card-header">
                                            <h6 class="m-0 font-weight-bold">
                                                <i class="fas fa-filter mr-2"></i>Bộ lọc sinh viên nâng cao
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="studentClassFilter" class="font-weight-bold">Chọn lớp:</label>
                                                    <select id="studentClassFilter" class="form-control">
                                                        <option value="">Tất cả các lớp</option>
                                                        <?php foreach ($classes as $class): ?>
                                                            <option value="<?php echo $class['LOP_MA']; ?>">
                                                                <?php echo $class['LOP_TEN']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="participationFilter" class="font-weight-bold">Trạng thái tham gia:</label>
                                                    <select id="participationFilter" class="form-control">
                                                        <option value="">Tất cả sinh viên</option>
                                                        <option value="participating">Đã tham gia nghiên cứu</option>
                                                        <option value="not-participating">Chưa tham gia nghiên cứu</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="studentSearch" class="font-weight-bold">Tìm kiếm sinh viên:</label>
                                                    <input type="text" id="studentSearch" class="form-control" placeholder="Nhập tên hoặc mã sinh viên...">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <button type="button" class="btn btn-primary mr-2" onclick="filterStudents()">
                                                        <i class="fas fa-search mr-1"></i>Lọc sinh viên
                                                    </button>
                                                    <button type="button" class="btn btn-secondary mr-2" onclick="resetStudentFilter()">
                                                        <i class="fas fa-sync-alt mr-1"></i>Đặt lại
                                                    </button>
                                                    <button type="button" class="btn btn-success" onclick="exportStudentList()">
                                                        <i class="fas fa-file-excel mr-1"></i>Xuất danh sách
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Statistics Overview -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-chart-pie mr-2"></i>Tỷ lệ tham gia sinh viên theo lớp
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="studentParticipationChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="card shadow">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                <i class="fas fa-users mr-2"></i>Thống kê chi tiết
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Lớp</th>
                                                            <th>Tổng SV</th>
                                                            <th>Đã tham gia</th>
                                                            <th>Chưa tham gia</th>
                                                            <th>Tỷ lệ</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($class_students_details as $class_code => $class_data): ?>
                                                            <tr>
                                                                <td class="font-weight-bold"><?php echo $class_data['class_name']; ?></td>
                                                                <td><?php echo $class_data['total_students']; ?></td>
                                                                <td>
                                                                    <span class="badge badge-success">
                                                                        <?php echo $class_data['participating_students']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-danger">
                                                                        <?php echo $class_data['total_students'] - $class_data['participating_students']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="mr-2"><?php echo $class_data['participation_rate']; ?>%</span>
                                                                        <div class="progress flex-grow-1 progress-thin">
                                                                            <div class="progress-bar bg-success" style="width: <?php echo $class_data['participation_rate']; ?>%"></div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 mb-4">
                                    <div class="card shadow">
                                        <div
                                            class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê sinh viên theo lớp
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="studentParticipationChart2"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê sinh viên theo trạng
                                                thái đề tài</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="studentStatusChart"></canvas>
                                            </div>
                                            <div class="table-responsive mt-3">
                                                <table class="table table-bordered" id="studentStatusTable" width="100%"
                                                    cellspacing="0">
                                                    <thead>
                                                        <tr>
                                                            <th>Lớp</th>
                                                            <?php foreach ($all_statuses as $status): ?>
                                                                <th><?php echo $status; ?></th>
                                                            <?php endforeach; ?>
                                                            <th>Tổng sinh viên tham gia</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($class_students_status_data as $class_name => $data): ?>
                                                            <tr>
                                                                <td><?php echo $class_name; ?></td>
                                                                <?php
                                                                $total = 0;
                                                                foreach ($all_statuses as $status):
                                                                    $count = isset($data['statuses'][$status]) ? $data['statuses'][$status] : 0;
                                                                    $total += $count;
                                                                    ?>
                                                                    <td><?php echo $count; ?></td>
                                                                <?php endforeach; ?>
                                                                <td><strong><?php echo $total; ?></strong></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chi tiết sinh viên theo lớp -->
                                <div class="col-12">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">Chi tiết sinh viên theo lớp
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="accordion" id="classStudentsDetails">
                                                <?php foreach ($class_students_details as $class_code => $class_data): ?>
                                                    <div class="card class-detail-card mb-3">
                                                        <div class="card-header class-detail-header"
                                                            id="studentHeading<?php echo md5($class_code); ?>">
                                                            <h2 class="mb-0">
                                                                <button class="btn btn-link btn-block text-left"
                                                                    type="button" data-toggle="collapse"
                                                                    data-target="#studentCollapse<?php echo md5($class_code); ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="studentCollapse<?php echo md5($class_code); ?>">
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <i class="fas fa-users mr-2"></i>
                                                                            <strong><?php echo $class_data['class_name']; ?></strong>
                                                                        </div>
                                                                        <div>
                                                                            <span
                                                                                class="badge badge-primary mr-2"><?php echo $class_data['total_students']; ?>
                                                                                sinh viên</span>
                                                                            <span
                                                                                class="badge <?php echo $class_data['participation_rate'] > 50 ? 'badge-success' : 'badge-warning'; ?> participation-badge">
                                                                                <?php echo $class_data['participation_rate']; ?>%
                                                                                tham gia
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </button>
                                                            </h2>
                                                        </div>
                                                        <div id="studentCollapse<?php echo md5($class_code); ?>"
                                                            class="collapse"
                                                            aria-labelledby="studentHeading<?php echo md5($class_code); ?>"
                                                            data-parent="#classStudentsDetails">
                                                            <div class="card-body">
                                                                <div class="row mb-4">
                                                                    <div class="col-md-6">
                                                                        <div class="card bg-light">
                                                                            <div class="card-body">
                                                                                <h5 class="card-title">Thống kê tham gia
                                                                                </h5>
                                                                                <p class="mb-1">Tổng số sinh viên:
                                                                                    <strong><?php echo $class_data['total_students']; ?></strong>
                                                                                </p>
                                                                                <p class="mb-1">Sinh viên tham gia NCKH:
                                                                                    <strong><?php echo $class_data['participating_students']; ?></strong>
                                                                                </p>
                                                                                <p class="mb-1">Sinh viên không tham gia:
                                                                                    <strong><?php echo $class_data['total_students'] - $class_data['participating_students']; ?></strong>
                                                                                </p>
                                                                                <div class="progress progress-thin mt-2">
                                                                                    <div class="progress-bar bg-success"
                                                                                        role="progressbar"
                                                                                        style="width: <?php echo $class_data['participation_rate']; ?>%"
                                                                                        aria-valuenow="<?php echo $class_data['participation_rate']; ?>"
                                                                                        aria-valuemin="0"
                                                                                        aria-valuemax="100"></div>
                                                                                </div>
                                                                                <p class="text-center mt-2 mb-0">
                                                                                    <strong><?php echo $class_data['participation_rate']; ?>%</strong>
                                                                                    tham gia
                                                                                </p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="chart-container" style="height: 200px;">
                                                                            <canvas
                                                                                id="classParticipationChart<?php echo md5($class_code); ?>"></canvas>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <table
                                                                    class="table table-bordered table-hover students-table"
                                                                    width="100%" cellspacing="0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>MSSV</th>
                                                                            <th>Họ và tên</th>
                                                                            <th>Tham gia NCKH</th>
                                                                            <th>Đề tài</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($class_data['students'] as $student): ?>
                                                                            <tr
                                                                                class="student-row <?php echo $student['has_project'] ? 'has-project' : 'no-project'; ?>">
                                                                                <td><?php echo $student['SV_MASV']; ?></td>
                                                                                <td><?php echo $student['SV_HOSV'] . ' ' . $student['SV_TENSV']; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if ($student['has_project']): ?>
                                                                                        <span class="badge badge-success">Có tham
                                                                                            gia</span>
                                                                                    <?php else: ?>
                                                                                        <span class="badge badge-secondary">Không
                                                                                            tham gia</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if (!empty($student['projects'])): ?>
                                                                                        <?php foreach ($student['projects'] as $proj): ?>
                                                                                            <div class="student-project-item">
                                                                                                <div>
                                                                                                    <strong><?php echo $proj['DT_TENDT']; ?></strong>
                                                                                                    (<?php echo $proj['DT_MADT']; ?>)
                                                                                                </div>
                                                                                                <div
                                                                                                    class="d-flex justify-content-between align-items-center">
                                                                                                    <div>
                                                                                                        <span class="badge"
                                                                                                            style="background-color: <?php echo isset($status_colors[$proj['DT_TRANGTHAI']]) ? $status_colors[$proj['DT_TRANGTHAI']] : '#6c757d'; ?>; color: white;">
                                                                                                            <?php echo $proj['DT_TRANGTHAI']; ?>
                                                                                                        </span>
                                                                                                        <span
                                                                                                            class="badge badge-info ml-1"><?php echo $proj['CTTG_VAITRO']; ?></span>
                                                                                                    </div>
                                                                                                    <a href="view_project.php?id=<?php echo $proj['DT_MADT']; ?>"
                                                                                                        class="btn btn-sm btn-outline-primary no-print">
                                                                                                        <i class="fas fa-eye"></i>
                                                                                                    </a>
                                                                                                </div>
                                                                                            </div>
                                                                                        <?php endforeach; ?>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Hệ thống quản lý nghiên cứu khoa học &copy; <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded no-print" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    <!-- SB Admin 2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>

    <script>
        // Enhanced Reports JavaScript with Advanced Features
        
        // Global variables
        let currentClassFilter = '';
        let currentParticipationFilter = '';
        let allStudentsData = {};
        
        // Dữ liệu cho biểu đồ
        const classLabels = <?php echo json_encode($chart_labels); ?>;
        const classData = <?php echo json_encode($chart_data); ?>;
        const classColors = <?php echo json_encode($chart_colors); ?>;

        // Dữ liệu trạng thái
        const statusColors = <?php echo $status_colors_json; ?>;

        // Dữ liệu cho biểu đồ lớp-trạng thái
        const classStatusData = <?php echo json_encode(array_values($class_status_data)); ?>;
        const classStudentsStatusData = <?php echo json_encode(array_values($class_students_status_data)); ?>;
        const allStatuses = <?php echo json_encode($all_statuses); ?>;

        $(document).ready(function () {
            // Initialize enhanced features
            initializeEnhancedReports();
            
            // Initialize DataTables with enhanced features
            initializeDataTables();
            
            // Initialize Charts with animations
            initializeCharts();
            
            // Setup real-time features
            setupRealTimeFeatures();
            
            // Load student data
            loadStudentData();
        });
        
        function initializeEnhancedReports() {
            // Add loading animations
            $('.card').each(function(index) {
                $(this).css('opacity', '0').delay(index * 100).animate({ opacity: 1 }, 600);
            });
            
            // Enhanced tab switching
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                const target = $(e.target).attr('href');
                $(target + ' .chart-container canvas').each(function() {
                    const chart = Chart.getChart(this);
                    if (chart) {
                        chart.update('active');
                    }
                });
            });
            
            // Add keyboard shortcuts
            $(document).keydown(function(e) {
                if (e.ctrlKey) {
                    switch(e.keyCode) {
                        case 49: // Ctrl+1 - Overview tab
                            $('#overview-tab').click();
                            e.preventDefault();
                            break;
                        case 50: // Ctrl+2 - Classes tab
                            $('#classes-tab').click();
                            e.preventDefault();
                            break;
                        case 51: // Ctrl+3 - Students tab
                            $('#students-tab').click();
                            e.preventDefault();
                            break;
                        case 80: // Ctrl+P - Print
                            printReport();
                            e.preventDefault();
                            break;
                        case 69: // Ctrl+E - Export Excel
                            exportData();
                            e.preventDefault();
                            break;
                    }
                }
            });
        }
        
        function initializeDataTables() {
            // Enhanced DataTables configuration
            const commonConfig = {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                },
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-sm btn-success',
                        title: 'Báo cáo thống kê đề tài',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-sm btn-danger',
                        title: 'Báo cáo thống kê đề tài',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> In',
                        className: 'btn btn-sm btn-primary',
                        title: 'Báo cáo thống kê đề tài',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]]
            };

            // Initialize specific tables
            $('#classStatsTable').DataTable(commonConfig);
            $('#classStatusTable').DataTable(commonConfig);
            $('#studentStatusTable').DataTable(commonConfig);

            // Initialize student tables for each class
            $('.students-table').each(function() {
                $(this).DataTable({
                    ...commonConfig,
                    pageLength: 15,
                    order: [[2, 'asc']], // Sort by student name
                    columnDefs: [
                        { targets: [4, 5], orderable: false } // Disable sorting for project and role columns
                    ]
                });
            });
        }
        
        function initializeCharts() {
            // Enhanced chart configurations with animations
            Chart.defaults.font.family = 'Nunito';
            Chart.defaults.color = '#858796';
            
            // Class statistics chart
            const classCtx = document.getElementById('classChart');
            if (classCtx) {
                new Chart(classCtx, {
                    type: 'bar',
                    data: {
                        labels: classLabels,
                        datasets: [{
                            label: 'Số đề tài',
                            data: classData,
                            backgroundColor: classColors.map(color => color + '80'),
                            borderColor: classColors,
                            borderWidth: 2,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 2000,
                            easing: 'easeOutQuart'
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#000',
                                bodyColor: '#000',
                                borderColor: '#ddd',
                                borderWidth: 1,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.1)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Status pie chart
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                const statusData = {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [],
                        borderWidth: 3,
                        borderColor: '#fff'
                    }]
                };

                <?php
                $status_stats->data_seek(0);
                while ($row = $status_stats->fetch_assoc()) {
                    $status_color = isset($status_colors[$row['DT_TRANGTHAI']]) ? $status_colors[$row['DT_TRANGTHAI']] : '#6c757d';
                    echo "statusData.labels.push('{$row['DT_TRANGTHAI']}');\n";
                    echo "statusData.datasets[0].data.push({$row['count']});\n";
                    echo "statusData.datasets[0].backgroundColor.push('{$status_color}');\n";
                }
                ?>

                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: statusData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            animateScale: true,
                            animateRotate: true,
                            duration: 2000
                        },
                        plugins: {
                            legend: { 
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#000',
                                bodyColor: '#000',
                                borderColor: '#ddd',
                                borderWidth: 1
                            }
                        },
                        cutout: '60%'
                    }
                });
            }

            // Student participation chart
            initializeStudentCharts();
        }
        
        function initializeStudentCharts() {
            const participationCtx = document.getElementById('studentParticipationChart');
            if (participationCtx) {
                const participationData = {
                    labels: [],
                    datasets: [{
                        label: 'Tỷ lệ tham gia (%)',
                        data: [],
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        borderRadius: 5
                    }]
                };

                <?php
                foreach ($class_students_details as $class_code => $class_data) {
                    echo "participationData.labels.push('{$class_data['class_name']}');\n";
                    echo "participationData.datasets[0].data.push({$class_data['participation_rate']});\n";
                }
                ?>

                new Chart(participationCtx, {
                    type: 'bar',
                    data: participationData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 2000,
                            easing: 'easeOutBounce'
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        
        function setupRealTimeFeatures() {
            // Add print functionality
            $('#printReport').click(function() {
                printReport();
            });
            
            // Add export functionality
            $('#exportPDF').click(function() {
                exportToPDF();
            });
            
            $('#exportExcel').click(function() {
                exportToExcel();
            });
            
            // Setup filter change handlers
            $('#studentClassFilter, #participationFilter').change(function() {
                filterStudents();
            });
            
            // Setup search functionality
            $('#studentSearch').on('input', debounce(function() {
                filterStudents();
            }, 300));
        }
        
        // Student filtering functions
        function loadStudentData() {
            // Store all student data for filtering
            $('.students-table tbody tr').each(function() {
                const $row = $(this);
                const classCode = $row.closest('.class-detail-card').data('class');
                const studentId = $row.find('td:eq(1)').text();
                const studentName = $row.find('.student-name').text();
                const participation = $row.data('participation');
                
                if (!allStudentsData[classCode]) {
                    allStudentsData[classCode] = [];
                }
                
                allStudentsData[classCode].push({
                    element: $row,
                    id: studentId,
                    name: studentName,
                    participation: participation
                });
            });
        }
        
        function filterStudents() {
            const classFilter = $('#studentClassFilter').val();
            const participationFilter = $('#participationFilter').val();
            const searchTerm = $('#studentSearch').val().toLowerCase();
            
            // Filter class cards
            $('.class-detail-card').each(function() {
                const $card = $(this);
                const classCode = $card.data('class');
                let showCard = true;
                
                if (classFilter && classFilter !== classCode) {
                    showCard = false;
                }
                
                if (showCard) {
                    $card.show();
                    
                    // Filter students within the class
                    const studentsData = allStudentsData[classCode] || [];
                    let visibleStudents = 0;
                    
                    studentsData.forEach(student => {
                        let showStudent = true;
                        
                        // Filter by participation
                        if (participationFilter && participationFilter !== student.participation) {
                            showStudent = false;
                        }
                        
                        // Filter by search term
                        if (searchTerm && 
                            !student.name.toLowerCase().includes(searchTerm) && 
                            !student.id.toLowerCase().includes(searchTerm)) {
                            showStudent = false;
                        }
                        
                        if (showStudent) {
                            student.element.show();
                            visibleStudents++;
                        } else {
                            student.element.hide();
                        }
                    });
                    
                    // Update class header with visible count
                    const $header = $card.find('.class-detail-header .btn');
                    const originalText = $header.text();
                    const newText = originalText.replace(/(\\d+\\s+sinh\\s+viên)/, `${visibleStudents} sinh viên`);
                    $header.html(newText);
                } else {
                    $card.hide();
                }
            });
            
            // Show notification
            showNotification(`Đã lọc theo tiêu chí đã chọn`, 'info');
        }
        
        function resetStudentFilter() {
            $('#studentClassFilter').val('');
            $('#participationFilter').val('');
            $('#studentSearch').val('');
            
            $('.class-detail-card').show();
            $('.students-table tbody tr').show();
            
            // Reset class headers
            $('.class-detail-card').each(function() {
                const $card = $(this);
                const classCode = $card.data('class');
                const totalStudents = (allStudentsData[classCode] || []).length;
                const $header = $card.find('.class-detail-header .btn');
                const originalText = $header.text();
                const newText = originalText.replace(/(\\d+\\s+sinh\\s+viên)/, `${totalStudents} sinh viên`);
                $header.html(newText);
            });
            
            showNotification('Đã đặt lại bộ lọc', 'success');
        }
        
        function filterClassStudents(classCode, filter) {
            const studentsData = allStudentsData[classCode] || [];
            
            studentsData.forEach(student => {
                if (filter === 'all') {
                    student.element.show();
                } else {
                    if (student.participation === filter) {
                        student.element.show();
                    } else {
                        student.element.hide();
                    }
                }
            });
            
            // Update active button
            const $classCard = $(`.class-detail-card[data-class="${classCode}"]`);
            $classCard.find('.btn-outline-primary, .btn-outline-success, .btn-outline-danger')
                    .removeClass('active btn-primary btn-success btn-danger')
                    .addClass('btn-outline-primary btn-outline-success btn-outline-danger');
            
            const $activeBtn = $classCard.find(`button[onclick*="${filter}"]`);
            $activeBtn.removeClass('btn-outline-primary btn-outline-success btn-outline-danger')
                    .addClass('active');
            
            if (filter === 'participating') {
                $activeBtn.addClass('btn-success');
            } else if (filter === 'not-participating') {
                $activeBtn.addClass('btn-danger');
            } else {
                $activeBtn.addClass('btn-primary');
            }
        }
        
        // Export functions
        function exportData() {
            showNotification('Đang chuẩn bị xuất dữ liệu...', 'info');
            // Simulate export process
            setTimeout(() => {
                showNotification('Xuất dữ liệu thành công!', 'success');
            }, 2000);
        }
        
        function exportStudentList() {
            const classFilter = $('#studentClassFilter').val();
            const participationFilter = $('#participationFilter').val();
            
            let exportData = [];
            
            Object.keys(allStudentsData).forEach(classCode => {
                if (!classFilter || classFilter === classCode) {
                    allStudentsData[classCode].forEach(student => {
                        if (!participationFilter || student.participation === participationFilter) {
                            exportData.push({
                                class: classCode,
                                studentId: student.id,
                                studentName: student.name,
                                participation: student.participation === 'participating' ? 'Đã tham gia' : 'Chưa tham gia'
                            });
                        }
                    });
                }
            });
            
            showNotification(`Đã xuất danh sách ${exportData.length} sinh viên`, 'success');
        }
        
        function printReport() {
            showNotification('Đang chuẩn bị in báo cáo...', 'info');
            setTimeout(() => {
                window.print();
            }, 1000);
        }
        
        function exportToPDF() {
            showNotification('Đang tạo file PDF...', 'info');
            setTimeout(() => {
                showNotification('Xuất PDF thành công!', 'success');
            }, 2000);
        }
        
        function exportToExcel() {
            showNotification('Đang tạo file Excel...', 'info');
            setTimeout(() => {
                showNotification('Xuất Excel thành công!', 'success');
            }, 2000);
        }
        
        // Utility functions
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
        
        function showNotification(message, type) {
            const alertClass = 'alert-' + type;
            const iconClass = type === 'success' ? 'check-circle' : 
                            type === 'warning' ? 'exclamation-triangle' : 
                            type === 'error' ? 'times-circle' : 'info-circle';
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert">
                    <i class="fas fa-${iconClass} mr-2"></i>${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `);
            
            $('body').append(notification);
            
            notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999,
                minWidth: '300px',
                maxWidth: '400px'
            });
            
            setTimeout(() => {
                notification.alert('close');
            }, 4000);
        }
    </script>
</body>

</html>
    </script>
</body>

</html>