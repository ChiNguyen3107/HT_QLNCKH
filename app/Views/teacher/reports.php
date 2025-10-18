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



// Xử lý bộ lọc cho tab "Theo lớp"

$class_department_filter = isset($_GET['class_department']) ? $_GET['class_department'] : '';

$class_course_filter = isset($_GET['class_course']) ? $_GET['class_course'] : '';



// Tạo điều kiện lọc riêng cho tab "Theo lớp"

$class_where_conditions = "dt.GV_MAGV = ?";

$class_params = [$teacher_id];

$class_param_types = "s";



if (!empty($class_department_filter)) {

    $class_where_conditions .= " AND l.DV_MADV = ?";

    $class_params[] = $class_department_filter;

    $class_param_types .= "s";

}



if (!empty($class_course_filter)) {

    $class_where_conditions .= " AND l.KH_NAM = ?";

    $class_params[] = $class_course_filter;

    $class_param_types .= "s";

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

    // Lấy thông tin khoa của lớp
    $class_info_query = "SELECT k.DV_MADV, k.DV_TENDV, l.KH_NAM
                        FROM lop l
                        JOIN khoa k ON l.DV_MADV = k.DV_MADV
                        WHERE l.LOP_MA = ?";
    $stmt = $conn->prepare($class_info_query);
    if ($stmt === false) {
        die("Lỗi truy vấn thông tin lớp: " . $conn->error);
    }
    $stmt->bind_param("s", $class_code);
    $stmt->execute();
    $class_info = $stmt->get_result()->fetch_assoc();

    // Truy vấn danh sách sinh viên của lớp

    $students_list_query = "SELECT sv.SV_MASV, sv.SV_HOSV, sv.SV_TENSV, 

                           CASE WHEN cttg.SV_MASV IS NOT NULL THEN 1 ELSE 0 END as has_project

                           FROM sinh_vien sv

                           LEFT JOIN (

                               SELECT DISTINCT SV_MASV 

                               FROM chi_tiet_tham_gia cttg 

                               JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT 

                           ) cttg ON sv.SV_MASV = cttg.SV_MASV

                           WHERE sv.LOP_MA = ?

                           ORDER BY sv.SV_HOSV, sv.SV_TENSV";

    $stmt = $conn->prepare($students_list_query);

    if ($stmt === false) {

        die("Lỗi truy vấn danh sách sinh viên: " . $conn->error);

    }



    $stmt->bind_param("s", $class_code);

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

                                      WHERE cttg.SV_MASV = ?";

            $stmt = $conn->prepare($student_projects_query);

            if ($stmt === false) {

                die("Lỗi truy vấn đề tài của sinh viên: " . $conn->error);

            }



            $stmt->bind_param("s", $student['SV_MASV']);

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

        'faculty_name' => $class_info['DV_TENDV'] ?? '',

        'year' => $class_info['KH_NAM'] ?? '',

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

                        WHERE $class_where_conditions

                        GROUP BY l.LOP_TEN, dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI

                        ORDER BY l.LOP_TEN, dt.DT_MADT";



$stmt = $conn->prepare($class_details_query);

if ($stmt === false) {

    die("Lỗi truy vấn chi tiết lớp: " . $conn->error . "<br>Query: " . $class_details_query);

}



$stmt->bind_param($class_param_types, ...$class_params);

$stmt->execute();

$class_details = $stmt->get_result();

$class_project_details = [];



while ($row = $class_details->fetch_assoc()) {

    if (!isset($class_project_details[$row['LOP_TEN']])) {

        $class_project_details[$row['LOP_TEN']] = [];

    }

    $class_project_details[$row['LOP_TEN']][] = $row;

}



// 10. Thống kê theo lĩnh vực nghiên cứu

$research_field_stats_query = "SELECT lvnc.LVNC_TEN, COUNT(DISTINCT dt.DT_MADT) as project_count,

                              COUNT(DISTINCT cttg.SV_MASV) as student_count

                              FROM de_tai_nghien_cuu dt

                              JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA

                              LEFT JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT

                              WHERE dt.GV_MAGV = ?

                              GROUP BY lvnc.LVNC_MA, lvnc.LVNC_TEN

                              ORDER BY project_count DESC";

$stmt = $conn->prepare($research_field_stats_query);

if ($stmt === false) {

    die("Lỗi truy vấn thống kê lĩnh vực nghiên cứu: " . $conn->error);

}

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$research_field_stats = $stmt->get_result();



// 11. Thống kê theo loại đề tài chi tiết

$project_type_detailed_query = "SELECT ldt.LDT_TENLOAI, dt.DT_TRANGTHAI, COUNT(*) as count

                               FROM de_tai_nghien_cuu dt

                               JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA

                               WHERE dt.GV_MAGV = ?

                               GROUP BY ldt.LDT_TENLOAI, dt.DT_TRANGTHAI

                               ORDER BY ldt.LDT_TENLOAI, count DESC";

$stmt = $conn->prepare($project_type_detailed_query);

if ($stmt === false) {

    die("Lỗi truy vấn thống kê loại đề tài chi tiết: " . $conn->error);

}

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$project_type_detailed = $stmt->get_result();



// 12. Thống kê tiến độ đề tài

$progress_stats_query = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI,

                        AVG(tddt.TDDT_PHANTRAMHOANTHANH) as avg_progress,

                        MAX(tddt.TDDT_PHANTRAMHOANTHANH) as max_progress,

                        MIN(tddt.TDDT_PHANTRAMHOANTHANH) as min_progress,

                        COUNT(tddt.TDDT_MA) as progress_updates

                        FROM de_tai_nghien_cuu dt

                        LEFT JOIN tien_do_de_tai tddt ON dt.DT_MADT = tddt.DT_MADT

                        WHERE dt.GV_MAGV = ?

                        GROUP BY dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI

                        ORDER BY avg_progress DESC";

$stmt = $conn->prepare($progress_stats_query);

if ($stmt === false) {

    die("Lỗi truy vấn thống kê tiến độ: " . $conn->error);

}

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$progress_stats = $stmt->get_result();



// 13. Thống kê đánh giá và nghiệm thu

$evaluation_stats_query = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI,

                          bb.BB_XEPLOAI, bb.BB_TONGDIEM,

                          COUNT(tvh.GV_MAGV) as council_members,

                          AVG(tvh.TV_DIEM) as avg_score

                          FROM de_tai_nghien_cuu dt

                          LEFT JOIN bien_ban bb ON dt.QD_SO = bb.QD_SO

                          LEFT JOIN thanh_vien_hoi_dong tvh ON dt.QD_SO = tvh.QD_SO

                          WHERE dt.GV_MAGV = ?

                          GROUP BY dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, bb.BB_XEPLOAI, bb.BB_TONGDIEM

                          ORDER BY bb.BB_TONGDIEM DESC";

$stmt = $conn->prepare($evaluation_stats_query);

if ($stmt === false) {

    die("Lỗi truy vấn thống kê đánh giá: " . $conn->error);

}

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$evaluation_stats = $stmt->get_result();



// 14. Thống kê theo thời gian (năm học)

$yearly_stats_query = "SELECT YEAR(dt.DT_NGAYTAO) as year,

                      COUNT(DISTINCT dt.DT_MADT) as project_count,

                      COUNT(DISTINCT cttg.SV_MASV) as student_count,

                      AVG(CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN 1 ELSE 0 END) * 100 as completion_rate

                      FROM de_tai_nghien_cuu dt

                      LEFT JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT

                      WHERE dt.GV_MAGV = ?

                      GROUP BY YEAR(dt.DT_NGAYTAO)

                      ORDER BY year DESC";

$stmt = $conn->prepare($yearly_stats_query);

if ($stmt === false) {

    die("Lỗi truy vấn thống kê theo năm: " . $conn->error);

}

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$yearly_stats = $stmt->get_result();



// 15. Thống kê sinh viên theo vai trò

$student_role_stats_query = "SELECT cttg.CTTG_VAITRO, COUNT(DISTINCT cttg.SV_MASV) as student_count,

                            COUNT(DISTINCT cttg.DT_MADT) as project_count

                            FROM chi_tiet_tham_gia cttg

                            JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT

                            WHERE dt.GV_MAGV = ?

                            GROUP BY cttg.CTTG_VAITRO

                            ORDER BY student_count DESC";

$stmt = $conn->prepare($student_role_stats_query);

if ($stmt === false) {

    die("Lỗi truy vấn thống kê vai trò sinh viên: " . $conn->error);

}

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$student_role_stats = $stmt->get_result();



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

            background: #f8f9fc;

        }



        .stats-card.success {

            border-left-color: #1cc88a;

            background: #f8f9fc;

        }



        .stats-card.info {

            border-left-color: #36b9cc;

            background: #f8f9fc;

        }



        .stats-card.warning {

            border-left-color: #f6c23e;

            background: #f8f9fc;

        }



        .stats-value {

            font-size: 2rem;

            font-weight: 700;

            margin-bottom: 0;

            color: #4e73df;

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

            background: #f8f9fc;

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

            background: #4e73df;

        }



        .export-buttons {

            margin-bottom: 1rem;

        }



        .filter-section {

            background: #f8f9fc;

            padding: 2rem;

            border-radius: 10px;

            margin-bottom: 2rem;

            border: 1px solid #e3e6f0;

            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);

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

            background: #f8f9fc;

            border-radius: 8px;

            transition: all 0.2s ease;

        }

        

        .student-project-item:hover {

            transform: translateX(2px);

            box-shadow: 0 2px 8px rgba(78, 115, 223, 0.1);

        }



        .student-row {

            transition: all 0.3s ease;

            border-radius: 8px;

            margin: 2px 0;

        }



        .student-row:hover {

            background: #f8f9fc;

            transform: none;

        }



        .student-row.has-project {

            font-weight: 600;

            background: #f8f9fc;

            border-left: 4px solid #1cc88a;

        }



        .student-row.no-project {

            color: #6c757d;

            background: #f8f9fc;

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

            background: #f8f9fc;

            border-radius: 10px;

            padding: 5px;

        }

        

        .nav-tabs .nav-link {

            border: none;

            border-radius: 20px;

            transition: all 0.3s ease;

            font-weight: 600;

            color: #6c757d;

        }

        

        .nav-tabs .nav-link.active {

            background: #4e73df;

            color: white;

            box-shadow: 0 2px 8px rgba(78, 115, 223, 0.2);

        }

        

        .nav-tabs .nav-link:hover {

            background: #f8f9fc;

            color: #4e73df;

            transform: none;

        }



        .student-list-section {

            border-top: 2px solid #e3e6f0;

            padding-top: 1.5rem;

            margin-top: 1.5rem;

            background: #f8f9fc;

            border-radius: 10px;

            padding: 1.5rem;

        }



        .progress-thin {

            height: 8px;

            border-radius: 10px;

            overflow: hidden;

            background: #e3e6f0;

        }

        

        .progress-thin .progress-bar {

            background: #4e73df;

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

            border-radius: 8px;

            border: 1px solid #e3e6f0;

            background: #ffffff;

            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);

            transition: all 0.2s ease;

            animation: slideInUp 0.6s ease-out;

        }

        

        .card:hover {

            transform: translateY(-2px);

            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);

        }

        

        .card-header {

            background: #f8f9fc;

            border-bottom: 1px solid #e3e6f0;

            border-radius: 8px 8px 0 0;

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

            background: #4e73df;

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

            background: #4e73df;

            border: none;

        }

        

        .btn-success {

            background: #1cc88a;

            border: none;

        }

        

        .btn-danger {

            background: #e74a3b;

            border: none;

        }

        

        .btn-warning {

            background: #f6c23e;

            border: none;

        }

        

        .btn-info {

            background: #36b9cc;

            border: none;

        }

        

        /* Enhanced Tables */

        .table {

            border-radius: 8px;

            overflow: hidden;

            background: #ffffff;

            border: 1px solid #e3e6f0;

        }

        

        .table thead th {

            background: #f8f9fc;

            color: #5a5c69;

            border: none;

            font-weight: 600;

            text-transform: none;

            letter-spacing: 0.5px;

            font-size: 0.9rem;

            border-bottom: 2px solid #e3e6f0;

        }

        

        .table tbody tr {

            transition: all 0.2s ease;

        }

        

        .table tbody tr:hover {

            background: #f8f9fc;

            transform: none;

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

            background: #4e73df;

        }

        

        .badge-success {

            background: #1cc88a;

        }

        

        .badge-warning {

            background: #f6c23e;

        }

        

        .badge-danger {

            background: #e74a3b;

        }

        

        .badge-info {

            background: #36b9cc;

        }

        

        /* Advanced Filter Section */

        .advanced-filter {
            background: #ffffff;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e3e6f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .filter-group {
            background: #f8f9fc;
            border-radius: 4px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e3e6f0;
        }
        
        .filter-title {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #4e73df;
        }

        

        .advanced-filter::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            height: 4px;

            background: linear-gradient(90deg, #4e73df, #1cc88a, #36b9cc);

        }

        

        .filter-group {

            background: #f8f9fc;

            border-radius: 10px;

            padding: 1.8rem;

            margin-bottom: 1.5rem;

            border: 1px solid #e3e6f0;

            transition: all 0.3s ease;

            position: relative;

        }

        

        .filter-group:hover {

            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);

            transform: translateY(-2px);

        }

        

        .filter-title {

            font-weight: 700;

            color: #4e73df;

            margin-bottom: 2rem;

            font-size: 1.2rem;

            display: flex;

            align-items: center;

            padding-bottom: 0.8rem;

            border-bottom: 3px solid #4e73df;

            position: relative;

        }

        

        .filter-title::before {

            content: '';

            position: absolute;

            bottom: -3px;

            left: 0;

            width: 50px;

            height: 3px;

            background: #1cc88a;

        }

        

        /* Form controls in advanced filter */

        .advanced-filter .form-control {
            border: 1px solid #e3e6f0;
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            background-color: #ffffff;
            transition: all 0.2s ease;
            font-weight: 400;
        }
        
        .advanced-filter .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
            background-color: #ffffff;
        }
        
        .advanced-filter .form-control:disabled {
            background-color: #f8f9fc;
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .advanced-filter label {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            text-transform: none;
            letter-spacing: 0.2px;
        }

        

        .advanced-filter .btn {

            border-radius: 8px;

            font-weight: 600;

            font-size: 0.85rem;

            padding: 0.75rem 1.25rem;

            transition: all 0.3s ease;

            border: none;

            text-transform: none;

            letter-spacing: 0.3px;

            position: relative;

            overflow: hidden;

        }

        

        .advanced-filter .btn::before {

            content: '';

            position: absolute;

            top: 0;

            left: -100%;

            width: 100%;

            height: 100%;

            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);

            transition: left 0.5s;

        }

        

        .advanced-filter .btn:hover::before {

            left: 100%;

        }

        

        .advanced-filter .btn-primary {

            background: linear-gradient(135deg, #4e73df, #2e59d9);

            color: #ffffff;

            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);

        }

        

        .advanced-filter .btn-primary:hover {

            background: linear-gradient(135deg, #2e59d9, #1e3a8a);

            transform: translateY(-2px);

            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);

        }

        

        .advanced-filter .btn-secondary {

            background: linear-gradient(135deg, #858796, #6c757d);

            color: #ffffff;

            box-shadow: 0 4px 15px rgba(133, 135, 150, 0.3);

        }

        

        .advanced-filter .btn-secondary:hover {

            background: linear-gradient(135deg, #6c757d, #495057);

            transform: translateY(-2px);

            box-shadow: 0 6px 20px rgba(133, 135, 150, 0.4);

        }

        

        .advanced-filter .btn-info {

            background: linear-gradient(135deg, #36b9cc, #2a96a5);

            color: #ffffff;

            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);

        }

        

        .advanced-filter .btn-info:hover {

            background: linear-gradient(135deg, #2a96a5, #1f7a8c);

            transform: translateY(-3px);

            box-shadow: 0 8px 25px rgba(54, 185, 204, 0.4);

        }

        

        .advanced-filter .btn-group-vertical .btn {

            margin-bottom: 0.75rem;

            width: 100%;

        }

        

        .advanced-filter .btn-group-vertical .btn:last-child {

            margin-bottom: 0;

        }

        

        /* Badge in filter title */

        .advanced-filter .filter-title .badge {

            font-size: 0.8rem;

            padding: 0.4rem 0.8rem;

            background: linear-gradient(135deg, #4e73df, #1cc88a);

            border-radius: 20px;

            font-weight: 600;

            box-shadow: 0 2px 8px rgba(78, 115, 223, 0.3);

            margin-left: 1rem;

        }

        

        /* Responsive improvements for advanced filter */

        @media (max-width: 768px) {

            .advanced-filter {

                padding: 0.75rem;

                margin-bottom: 0.75rem;

            }

            

            .filter-group {

                padding: 0.5rem;

                margin-bottom: 0.5rem;

            }

            

            .filter-title {

                font-size: 0.85rem;

                margin-bottom: 0.5rem;

            }

            

            .advanced-filter .btn {

                padding: 0.4rem 0.8rem;

                font-size: 0.75rem;

            }

            

            .advanced-filter .form-control {

                padding: 0.4rem 0.6rem;

                font-size: 0.8rem;

            }

        }

        

        @media (max-width: 576px) {

            .advanced-filter {

                padding: 0.5rem;

            }

            

            .filter-group {

                padding: 0.4rem;

            }

            

            .advanced-filter .btn {

                padding: 0.35rem 0.6rem;

                font-size: 0.7rem;

            }

        }

        

        /* Additional enhancements for better balance */

        .advanced-filter .row {

            align-items: stretch;

        }

        

        .advanced-filter .col-md-4 {

            display: flex;

            flex-direction: column;

        }

        

        .advanced-filter .filter-group {

            flex: 1;

            display: flex;

            flex-direction: column;

            justify-content: space-between;

        }

        

        .advanced-filter .btn-group-vertical {

            flex: 1;

            display: flex;

            flex-direction: column;

            justify-content: space-between;

        }

        

        /* Loading state for buttons */

        .advanced-filter .btn:active {

            transform: translateY(1px);

            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);

        }

        

        /* Focus states for better accessibility */

        .advanced-filter .btn:focus {

            outline: none;

            box-shadow: 0 0 0 0.3rem rgba(78, 115, 223, 0.25);

        }

        

        .advanced-filter .form-control:focus {

            outline: none;

        }

        

        /* Student Detail Cards */

        .student-detail-card {

            background: #ffffff;

            border-radius: 8px;

            padding: 1.5rem;

            margin-bottom: 1rem;

            border: 1px solid #e3e6f0;

            transition: all 0.2s ease;

        }

        

        .student-detail-card:hover {

            transform: translateX(5px);

            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.1);

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

            background: #4e73df;

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



            .students-filter-card .card-body {

                padding: 0.75rem;

            }

            

            .students-filter-card .form-control {

                font-size: 0.8rem;

                padding: 0.4rem 0.6rem;

            }

            

            .students-filter-card .btn {

                font-size: 0.75rem;

                padding: 0.4rem 0.8rem;

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

            background: #f8f9fc;

            border: 1px solid #e3e6f0;

            border-radius: 6px;

        }



        .students-filter-card .card-header {

            background: #eaecf4;

            border-bottom: 1px solid #d1d3e2;

            padding: 0.75rem 1rem;

        }



        .students-filter-card .form-control {

            background: #fff;

            border: 1px solid #d1d3e2;

            color: #5a5c69;

            border-radius: 4px;

            padding: 0.5rem 0.75rem;

            font-size: 0.85rem;

        }



        .students-filter-card .form-control::placeholder {

            color: #858796;

        }



        .students-filter-card .form-control:focus {

            background: #fff;

            border-color: #bac8f3;

            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);

            color: #5a5c69;

        }



        .students-filter-card label {

            color: #5a5c69;

            font-weight: 600;

            font-size: 0.8rem;

            margin-bottom: 0.5rem;

        }



        .students-filter-card .btn {

            border-radius: 4px;

            font-weight: 500;

            font-size: 0.8rem;

            padding: 0.5rem 1rem;

        }



        .students-filter-card .btn:hover {

            transform: translateY(-1px);

            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

        }



        /* Class filter section */

        .class-filter-card {

            background: #f8f9fc;

            border: 1px solid #e3e6f0;

            border-radius: 6px;

        }



        .class-filter-card .card-header {

            background: #eaecf4;

            border-bottom: 1px solid #d1d3e2;

            padding: 0.75rem 1rem;

        }



        .class-filter-card .form-control {

            background: #fff;

            border: 1px solid #d1d3e2;

            color: #5a5c69;

            border-radius: 4px;

            padding: 0.5rem 0.75rem;

            font-size: 0.85rem;

        }



        .class-filter-card .form-control:focus {

            background: #fff;

            border-color: #bac8f3;

            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);

            color: #5a5c69;

        }



        .class-filter-card label {

            color: #5a5c69;

            font-weight: 600;

            font-size: 0.8rem;

            margin-bottom: 0.5rem;

        }



        .class-filter-card .btn {

            border-radius: 4px;

            font-weight: 500;

            font-size: 0.8rem;

            padding: 0.5rem 1rem;

        }



        .class-filter-card .btn:hover {

            transform: translateY(-1px);

            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

        }



        @media (max-width: 768px) {

            .class-filter-card .card-body {

                padding: 0.75rem;

            }



            .class-filter-card .form-control {

                font-size: 0.8rem;

                padding: 0.4rem 0.6rem;

            }



            .class-filter-card .btn {

                font-size: 0.75rem;

                padding: 0.4rem 0.8rem;

            }

        }
        
        /* Integrated filter styles */
        .filter-info {
            background: rgba(78, 115, 223, 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            border: 1px solid rgba(78, 115, 223, 0.2);
        }
        .filter-info small {
            color: #4e73df !important;
            font-weight: 500;
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



        /* Buttons in advanced filter */

        .advanced-filter .btn {

            border-radius: 4px;

            font-weight: 500;

            font-size: 0.8rem;

            padding: 0.5rem 1rem;

            transition: all 0.2s ease;

            border: none;

            text-transform: none;

            letter-spacing: 0.2px;

        }

        

        .advanced-filter .btn-primary {

            background: #4e73df;

            color: #ffffff;

            box-shadow: 0 1px 3px rgba(78, 115, 223, 0.2);

        }

        

        .advanced-filter .btn-primary:hover {

            background: #2e59d9;

            transform: translateY(-1px);

            box-shadow: 0 2px 6px rgba(78, 115, 223, 0.3);

        }

        

        .advanced-filter .btn-secondary {

            background: #6c757d;

            color: #ffffff;

            box-shadow: 0 1px 3px rgba(108, 117, 125, 0.2);

        }

        

        .advanced-filter .btn-secondary:hover {

            background: #495057;

            transform: translateY(-1px);

            box-shadow: 0 2px 6px rgba(108, 117, 125, 0.3);

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

                            <i class="fas fa-filter mr-1"></i>Bộ lọc

                        </div>

                        <form method="get" action="" id="filterForm">

                            <div class="row">

                                <div class="col-md-4 mb-3">

                                    <div class="filter-group">

                                        <label for="department" class="font-weight-bold">
                                            <i class="fas fa-university mr-1"></i>Khoa
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
                                            <i class="fas fa-graduation-cap mr-1"></i>Khóa học
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
                                            <i class="fas fa-cogs mr-1"></i>Thao tác
                                        </label>

                                        <div class="btn-group-vertical w-100">

                                            <button type="submit" class="btn btn-primary btn-sm mb-2">

                                                <i class="fas fa-search mr-1"></i>Tìm kiếm

                                            </button>

                                            <a href="reports.php" class="btn btn-secondary btn-sm">

                                                <i class="fas fa-sync-alt mr-1"></i>Đặt lại

                                            </a>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </form>

                    </div>



                    <!-- Quick Stats Overview -->
                    <!-- <div class="row mb-4">
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
                    </div> -->



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



                    <!-- Additional Summary Cards -->

                    <div class="row">

                        <!-- Điểm trung bình đánh giá -->

                        <?php

                        $avg_evaluation_score = 0;

                        $evaluation_count = 0;

                        $evaluation_stats->data_seek(0);

                        while ($row = $evaluation_stats->fetch_assoc()) {

                            if ($row['BB_TONGDIEM']) {

                                $avg_evaluation_score += $row['BB_TONGDIEM'];

                                $evaluation_count++;

                            }

                        }

                        $avg_evaluation_score = $evaluation_count > 0 ? round($avg_evaluation_score / $evaluation_count, 1) : 0;

                        ?>

                        <div class="col-xl-3 col-md-6 mb-4">

                            <div class="card border-left-info shadow h-100 py-2">

                                <div class="card-body">

                                    <div class="row no-gutters align-items-center">

                                        <div class="col mr-2">

                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">

                                                Điểm đánh giá TB

                                            </div>

                                            <div class="h5 mb-0 font-weight-bold text-gray-800">

                                                <?php echo $avg_evaluation_score; ?>/100

                                            </div>

                                        </div>

                                        <div class="col-auto">

                                            <i class="fas fa-star fa-2x text-gray-300"></i>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- Số lĩnh vực nghiên cứu -->

                        <?php

                        $research_fields_count = $research_field_stats->num_rows;

                        ?>

                        <div class="col-xl-3 col-md-6 mb-4">

                            <div class="card border-left-success shadow h-100 py-2">

                                <div class="card-body">

                                    <div class="row no-gutters align-items-center">

                                        <div class="col mr-2">

                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">

                                                Lĩnh vực nghiên cứu

                                            </div>

                                            <div class="h5 mb-0 font-weight-bold text-gray-800">

                                                <?php echo $research_fields_count; ?>

                                            </div>

                                        </div>

                                        <div class="col-auto">

                                            <i class="fas fa-microscope fa-2x text-gray-300"></i>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- Tiến độ trung bình -->

                        <?php

                        $avg_progress = 0;

                        $progress_count = 0;

                        $progress_stats->data_seek(0);

                        while ($row = $progress_stats->fetch_assoc()) {

                            if ($row['avg_progress']) {

                                $avg_progress += $row['avg_progress'];

                                $progress_count++;

                            }

                        }

                        $avg_progress = $progress_count > 0 ? round($avg_progress / $progress_count, 1) : 0;

                        ?>

                        <div class="col-xl-3 col-md-6 mb-4">

                            <div class="card border-left-primary shadow h-100 py-2">

                                <div class="card-body">

                                    <div class="row no-gutters align-items-center">

                                        <div class="col mr-2">

                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">

                                                Tiến độ trung bình

                                            </div>

                                            <div class="h5 mb-0 font-weight-bold text-gray-800">

                                                <?php echo $avg_progress; ?>%

                                            </div>

                                        </div>

                                        <div class="col-auto">

                                            <i class="fas fa-tasks fa-2x text-gray-300"></i>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- Số đề tài xuất sắc -->

                        <?php

                        $excellent_count = 0;

                        $evaluation_stats->data_seek(0);

                        while ($row = $evaluation_stats->fetch_assoc()) {

                            if ($row['BB_XEPLOAI'] == 'Xuất sắc') {

                                $excellent_count++;

                            }

                        }

                        ?>

                        <div class="col-xl-3 col-md-6 mb-4">

                            <div class="card border-left-danger shadow h-100 py-2">

                                <div class="card-body">

                                    <div class="row no-gutters align-items-center">

                                        <div class="col mr-2">

                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">

                                                Đề tài xuất sắc

                                            </div>

                                            <div class="h5 mb-0 font-weight-bold text-gray-800">

                                                <?php echo $excellent_count; ?>

                                            </div>

                                        </div>

                                        <div class="col-auto">

                                            <i class="fas fa-trophy fa-2x text-gray-300"></i>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>



                    <!-- Nav tabs -->

                    <ul class="nav nav-tabs mb-4 no-print" id="reportTabs" role="tablist">

                        <li class="nav-item">

                            <a class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'overview') ? 'active' : ''; ?>" id="overview-tab" data-toggle="tab" href="#overview" role="tab">

                                <i class="fas fa-chart-bar mr-1"></i>Tổng quan

                            </a>

                        </li>

                        <li class="nav-item">

                            <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'classes') ? 'active' : ''; ?>" id="classes-tab" data-toggle="tab" href="#classes" role="tab">

                                <i class="fas fa-graduation-cap mr-1"></i>Theo lớp

                            </a>

                        </li>

                        <li class="nav-item">

                            <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'students') ? 'active' : ''; ?>" id="students-tab" data-toggle="tab" href="#students" role="tab">

                                <i class="fas fa-user-graduate mr-1"></i>Sinh viên

                            </a>

                        </li>

                    </ul>



                    <div class="tab-content">

                        <!-- Tab Tổng quan -->

                        <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'overview') ? 'show active' : ''; ?>" id="overview" role="tabpanel">

                            <div class="row mb-3">

                                <!-- Thống kê theo lớp -->

                                <div class="col-xl-8 col-lg-7">

                                    <div class="card shadow mb-3">

                                        <div

                                            class="card-header py-2 d-flex flex-row align-items-center justify-content-between">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê số lượng đề tài theo

                                                lớp</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="chart-container" style="height: 300px; margin-bottom: 15px;">

                                                <canvas id="classChart"></canvas>

                                            </div>

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="classStatsTable" width="100%"

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

                                    <div class="card shadow mb-3">

                                        <div

                                            class="card-header py-2 d-flex flex-row align-items-center justify-content-between">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê theo trạng thái đề

                                                tài</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="chart-container" style="height: 300px; margin-bottom: 15px;">

                                                <canvas id="statusChart"></canvas>

                                            </div>

                                            <div class="mt-3">

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



                            <div class="row mb-3">

                                <!-- Thống kê đề tài theo lớp và trạng thái -->

                                <div class="col-12">

                                    <div class="card shadow mb-3">

                                        <div class="card-header py-2">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê đề tài theo lớp và

                                                trạng thái</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="chart-container" style="height: 300px; margin-bottom: 15px;">

                                                <canvas id="classStatusChart"></canvas>

                                            </div>

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="classStatusTable" width="100%"

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



                            <!-- Thống kê theo lĩnh vực nghiên cứu -->

                            <div class="row mb-3">

                                <div class="col-xl-6 col-lg-6">

                                    <div class="card shadow mb-3">

                                        <div class="card-header py-2">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê theo lĩnh vực nghiên cứu</h6>

                        </div>

                                        <div class="card-body p-3">

                                            <div class="chart-container" style="height: 250px; margin-bottom: 15px;">

                                                <canvas id="researchFieldChart"></canvas>

                                            </div>

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="researchFieldTable" width="100%" cellspacing="0">

                                                    <thead>

                                                        <tr>

                                                            <th>Lĩnh vực nghiên cứu</th>

                                                            <th>Số đề tài</th>

                                                            <th>Số sinh viên</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php

                                                        $research_field_stats->data_seek(0);

                                                        while ($row = $research_field_stats->fetch_assoc()):

                                                        ?>

                                                            <tr>

                                                                <td><?php echo $row['LVNC_TEN']; ?></td>

                                                                <td><?php echo $row['project_count']; ?></td>

                                                                <td><?php echo $row['student_count']; ?></td>

                                                            </tr>

                                                        <?php endwhile; ?>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </div>



                                <!-- Thống kê theo loại đề tài chi tiết -->

                                <div class="col-xl-6 col-lg-6">

                                    <div class="card shadow mb-3">

                                        <div class="card-header py-2">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê theo loại đề tài</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="chart-container" style="height: 250px; margin-bottom: 15px;">

                                                <canvas id="projectTypeChart"></canvas>

                                            </div>

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="projectTypeTable" width="100%" cellspacing="0">

                                                    <thead>

                                                        <tr>

                                                            <th>Loại đề tài</th>

                                                            <th>Trạng thái</th>

                                                            <th>Số lượng</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php

                                                        $project_type_detailed->data_seek(0);

                                                        while ($row = $project_type_detailed->fetch_assoc()):

                                                        ?>

                                                            <tr>

                                                                <td><?php echo $row['LDT_TENLOAI']; ?></td>

                                                                <td>

                                                                    <span class="badge" style="background-color: <?php echo isset($status_colors[$row['DT_TRANGTHAI']]) ? $status_colors[$row['DT_TRANGTHAI']] : '#6c757d'; ?>; color: white;">

                                                                        <?php echo $row['DT_TRANGTHAI']; ?>

                                                                    </span>

                                                                </td>

                                                                <td><?php echo $row['count']; ?></td>

                                                            </tr>

                                                        <?php endwhile; ?>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>



                            <!-- Thống kê tiến độ và đánh giá -->

                            <div class="row mb-3">

                                <div class="col-xl-6 col-lg-6">

                                    <div class="card shadow mb-3">

                                        <div class="card-header py-2">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê tiến độ đề tài</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="progressTable" width="100%" cellspacing="0">

                                                    <thead>

                                                        <tr>

                                                            <th>Mã đề tài</th>

                                                            <th>Tên đề tài</th>

                                                            <th>Trạng thái</th>

                                                            <th>Tiến độ TB</th>

                                                            <th>Cập nhật</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php

                                                        $progress_stats->data_seek(0);

                                                        while ($row = $progress_stats->fetch_assoc()):

                                                            $avg_progress = round($row['avg_progress'] ?? 0, 1);

                                                        ?>

                                                            <tr>

                                                                <td><?php echo $row['DT_MADT']; ?></td>

                                                                <td><?php echo $row['DT_TENDT']; ?></td>

                                                                <td>

                                                                    <span class="badge" style="background-color: <?php echo isset($status_colors[$row['DT_TRANGTHAI']]) ? $status_colors[$row['DT_TRANGTHAI']] : '#6c757d'; ?>; color: white;">

                                                                        <?php echo $row['DT_TRANGTHAI']; ?>

                                                                    </span>

                                                                </td>

                                                                <td>

                                                                    <div class="progress" style="height: 20px;">

                                                                        <div class="progress-bar bg-success" role="progressbar" 

                                                                             style="width: <?php echo $avg_progress; ?>%" 

                                                                             aria-valuenow="<?php echo $avg_progress; ?>" 

                                                                             aria-valuemin="0" aria-valuemax="100">

                                                                            <?php echo $avg_progress; ?>%

                                                                        </div>

                                                                    </div>

                                                                </td>

                                                                <td><?php echo $row['progress_updates']; ?> lần</td>

                                                            </tr>

                                                        <?php endwhile; ?>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </div>



                                <div class="col-xl-6 col-lg-6">

                                    <div class="card shadow mb-3">

                                        <div class="card-header py-2">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê đánh giá và nghiệm thu</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="evaluationTable" width="100%" cellspacing="0">

                                                    <thead>

                                                        <tr>

                                                            <th>Mã đề tài</th>

                                                            <th>Tên đề tài</th>

                                                            <th>Xếp loại</th>

                                                            <th>Điểm</th>

                                                            <th>Thành viên HĐ</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php

                                                        $evaluation_stats->data_seek(0);

                                                        while ($row = $evaluation_stats->fetch_assoc()):

                                                        ?>

                                                            <tr>

                                                                <td><?php echo $row['DT_MADT']; ?></td>

                                                                <td><?php echo $row['DT_TENDT']; ?></td>

                                                                <td>

                                                                    <?php if ($row['BB_XEPLOAI']): ?>

                                                                        <span class="badge badge-success"><?php echo $row['BB_XEPLOAI']; ?></span>

                                                                    <?php else: ?>

                                                                        <span class="badge badge-secondary">Chưa nghiệm thu</span>

                                                                    <?php endif; ?>

                                                                </td>

                                                                <td>

                                                                    <?php if ($row['BB_TONGDIEM']): ?>

                                                                        <strong><?php echo $row['BB_TONGDIEM']; ?>/100</strong>

                                                                    <?php else: ?>

                                                                        <span class="text-muted">-</span>

                                                                    <?php endif; ?>

                                                                </td>

                                                                <td><?php echo $row['council_members']; ?> người</td>

                                                            </tr>

                                                        <?php endwhile; ?>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>



                            <!-- Thống kê theo thời gian và vai trò sinh viên -->

                            <div class="row mb-3">

                                <div class="col-xl-6 col-lg-6">

                                    <div class="card shadow mb-3">

                                        <div class="card-header py-2">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê theo năm học</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="chart-container" style="height: 250px; margin-bottom: 15px;">

                                                <canvas id="yearlyChart"></canvas>

                                            </div>

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="yearlyTable" width="100%" cellspacing="0">

                                                    <thead>

                                                        <tr>

                                                            <th>Năm</th>

                                                            <th>Số đề tài</th>

                                                            <th>Số sinh viên</th>

                                                            <th>Tỷ lệ hoàn thành</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php

                                                        $yearly_stats->data_seek(0);

                                                        while ($row = $yearly_stats->fetch_assoc()):

                                                        ?>

                                                            <tr>

                                                                <td><?php echo $row['year']; ?></td>

                                                                <td><?php echo $row['project_count']; ?></td>

                                                                <td><?php echo $row['student_count']; ?></td>

                                                                <td><?php echo round($row['completion_rate'], 1); ?>%</td>

                                                            </tr>

                                                        <?php endwhile; ?>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </div>



                                <div class="col-xl-6 col-lg-6">

                                    <div class="card shadow mb-3">

                                        <div class="card-header py-2">

                                            <h6 class="m-0 font-weight-bold text-primary">Thống kê sinh viên theo vai trò</h6>

                                        </div>

                                        <div class="card-body p-3">

                                            <div class="chart-container" style="height: 250px; margin-bottom: 15px;">

                                                <canvas id="studentRoleChart"></canvas>

                                            </div>

                                            <div class="table-responsive">

                                                <table class="table table-bordered table-sm" id="studentRoleTable" width="100%" cellspacing="0">

                                                    <thead>

                                                        <tr>

                                                            <th>Vai trò</th>

                                                            <th>Số sinh viên</th>

                                                            <th>Số đề tài</th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php

                                                        $student_role_stats->data_seek(0);

                                                        while ($row = $student_role_stats->fetch_assoc()):

                                                        ?>

                                                            <tr>

                                                                <td><?php echo $row['CTTG_VAITRO']; ?></td>

                                                                <td><?php echo $row['student_count']; ?></td>

                                                                <td><?php echo $row['project_count']; ?></td>

                                                            </tr>

                                                        <?php endwhile; ?>

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- Tab Theo lớp -->

                        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'classes') ? 'show active' : ''; ?>" id="classes" role="tabpanel">

                            <!-- Chi tiết đề tài theo lớp với bộ lọc tích hợp -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card shadow mb-4">
                                        <div class="card-header py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="m-0 font-weight-bold text-primary">
                                                    <i class="fas fa-graduation-cap mr-2"></i>Chi tiết đề tài theo lớp
                                                </h6>
                                                <?php if (!empty($class_department_filter) || !empty($class_course_filter)): ?>
                                                    <div class="filter-info">
                                                        <small class="text-muted">
                                                            <i class="fas fa-filter mr-1"></i>
                                                            <?php 
                                                            $filter_text = [];
                                                            if (!empty($class_department_filter)) {
                                                                foreach ($departments as $dept) {
                                                                    if ($dept['DV_MADV'] == $class_department_filter) {
                                                                        $filter_text[] = "Khoa: " . $dept['DV_TENDV'];
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            if (!empty($class_course_filter)) {
                                                                $filter_text[] = "Khóa: " . $class_course_filter;
                                                            }
                                                            echo implode(', ', $filter_text);
                                                            ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Bộ lọc tích hợp -->
                                        <div class="card-body border-bottom bg-light">
                                            <form id="classFilterForm" method="GET">
                                                <input type="hidden" name="tab" value="classes">
                                                <div class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <label for="classDepartmentFilter" class="small font-weight-bold text-muted">Khoa</label>
                                                        <select class="form-control form-control-sm" id="classDepartmentFilter" name="class_department">
                                                            <option value="">Tất cả khoa</option>
                                                            <?php foreach ($departments as $dept): ?>
                                                                <option value="<?php echo $dept['DV_MADV']; ?>" 
                                                                        <?php echo (isset($_GET['class_department']) && $_GET['class_department'] == $dept['DV_MADV']) ? 'selected' : ''; ?>>
                                                                    <?php echo $dept['DV_TENDV']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label for="classCourseFilter" class="small font-weight-bold text-muted">Khóa học</label>
                                                        <select class="form-control form-control-sm" id="classCourseFilter" name="class_course">
                                                            <option value="">Tất cả khóa học</option>
                                                            <?php foreach ($courses as $course): ?>
                                                                <option value="<?php echo $course['KH_NAM']; ?>" 
                                                                        <?php echo (isset($_GET['class_course']) && $_GET['class_course'] == $course['KH_NAM']) ? 'selected' : ''; ?>>
                                                                    <?php echo $course['KH_NAM']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-2 d-flex align-items-end">
                                                        <div class="btn-group w-100">
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-search mr-1"></i>Lọc
                                                            </button>
                                                            <a href="?tab=classes" class="btn btn-secondary btn-sm">
                                                                <i class="fas fa-undo mr-1"></i>Đặt lại
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="card-body">
                                            <?php if (empty($class_project_details)): ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <h6 class="text-muted">Không có dữ liệu đề tài</h6>
                                                    <p class="text-muted small">
                                                        <?php if (!empty($class_department_filter) || !empty($class_course_filter)): ?>
                                                            Không tìm thấy đề tài nào với bộ lọc hiện tại.
                                                        <?php else: ?>
                                                            Chưa có đề tài nào được tạo.
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            <?php else: ?>
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
                                        <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>



                        <!-- Tab Sinh viên -->

                        <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'students') ? 'show active' : ''; ?>" id="students" role="tabpanel">

                            <!-- Student Filter Section -->

                            <!-- <div class="row mb-3">

                                <div class="col-12">

                                    <div class="card border-0 shadow-sm students-filter-card">

                                        <div class="card-header">

                                            <h6 class="m-0 font-weight-bold">

                                                <i class="fas fa-filter mr-1"></i>Bộ lọc sinh viên

                                            </h6>

                                        </div>

                                        <div class="card-body">

                                            <div class="row">

                                                <div class="col-md-4 mb-2">

                                                    <label for="studentClassFilter" class="font-weight-bold">Lớp</label>

                                                    <select id="studentClassFilter" class="form-control">

                                                        <option value="">Tất cả các lớp</option>

                                                        <?php foreach ($classes as $class): ?>

                                                            <option value="<?php echo $class['LOP_MA']; ?>">

                                                                <?php echo $class['LOP_TEN']; ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                </div>

                                                <div class="col-md-4 mb-2">

                                                    <label for="participationFilter" class="font-weight-bold">Trạng thái tham gia</label>

                                                    <select id="participationFilter" class="form-control">

                                                        <option value="">Tất cả sinh viên</option>

                                                        <option value="participating">Đã tham gia nghiên cứu</option>

                                                        <option value="not-participating">Chưa tham gia nghiên cứu</option>

                                                    </select>

                                                </div>

                                                <div class="col-md-4 mb-2">

                                                    <label for="studentSearch" class="font-weight-bold">Tìm kiếm sinh viên</label>

                                                    <input type="text" id="studentSearch" class="form-control" placeholder="Nhập tên hoặc mã sinh viên...">

                                                </div>

                                            </div>

                                            <div class="row">

                                                <div class="col-md-12">

                                                    <button type="button" class="btn btn-primary btn-sm mr-2" onclick="filterStudents()">

                                                        <i class="fas fa-search mr-1"></i>Lọc sinh viên

                                                    </button>

                                                    <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="resetStudentFilter()">

                                                        <i class="fas fa-sync-alt mr-1"></i>Đặt lại

                                                    </button>

                                                    <button type="button" class="btn btn-success btn-sm" onclick="exportStudentList()">

                                                        <i class="fas fa-file-excel mr-1"></i>Xuất danh sách

                                                    </button>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div> -->







                            <div class="row">



                                <!-- Chi tiết sinh viên theo lớp -->

                                <div class="col-12">

                                    <div class="card shadow mb-4">

                                        <div class="card-header py-3">

                                            <h6 class="m-0 font-weight-bold text-primary">Chi tiết sinh viên theo lớp

                                            </h6>

                                        </div>

                                        <div class="card-body">
                                            
                                            <!-- Bộ lọc cho chi tiết sinh viên -->
                                            <div class="row mb-4">
                                                <div class="col-md-12">
                                                    <div class="card border-primary">
                                                        <div class="card-header bg-primary text-white">
                                                            <h6 class="mb-0">
                                                                <i class="fas fa-filter mr-2"></i>
                                                                Bộ lọc chi tiết sinh viên
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <label for="classFilter">Lọc theo lớp:</label>
                                                                    <select class="form-control" id="classFilter">
                                                                        <option value="">Tất cả lớp</option>
                                                                        <?php foreach ($class_students_details as $class_code => $class_data): ?>
                                                                            <option value="<?php echo htmlspecialchars($class_data['class_name']); ?>">
                                                                                <?php echo htmlspecialchars($class_data['class_name']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label for="facultyFilter">Lọc theo khoa:</label>
                                                                    <select class="form-control" id="facultyFilter">
                                                                        <option value="">Tất cả khoa</option>
                                                                        <?php 
                                                                        $faculties = [];
                                                                        foreach ($class_students_details as $class_data) {
                                                                            if (!in_array($class_data['faculty_name'], $faculties)) {
                                                                                $faculties[] = $class_data['faculty_name'];
                                                                            }
                                                                        }
                                                                        foreach ($faculties as $faculty): ?>
                                                                            <option value="<?php echo htmlspecialchars($faculty); ?>">
                                                                                <?php echo htmlspecialchars($faculty); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label for="participationFilter">Lọc theo tham gia:</label>
                                                                    <select class="form-control" id="participationFilter">
                                                                        <option value="">Tất cả</option>
                                                                        <option value="participating">Có tham gia NCKH</option>
                                                                        <option value="not-participating">Không tham gia NCKH</option>
                                                                        <option value="high-participation">Tham gia cao (>50%)</option>
                                                                        <option value="low-participation">Tham gia thấp (≤50%)</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label for="studentSearch">Tìm kiếm sinh viên:</label>
                                                                    <input type="text" class="form-control" id="studentSearch" placeholder="Nhập tên hoặc MSSV...">
                                                                </div>
                                                            </div>
                                                            <div class="row mt-3">
                                                                <div class="col-md-12">
                                                                    <button type="button" class="btn btn-primary mr-2" id="applyFilters">
                                                                        <i class="fas fa-search mr-1"></i>
                                                                        Áp dụng bộ lọc
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary" id="clearFilters">
                                                                        <i class="fas fa-times mr-1"></i>
                                                                        Xóa bộ lọc
                                                                    </button>
                                                                    <span class="ml-3 text-muted">
                                                                        <i class="fas fa-info-circle mr-1"></i>
                                                                        Hiển thị: <span id="filterResultsCount">0</span> lớp
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="accordion" id="classStudentsDetails">

                                                <?php foreach ($class_students_details as $class_code => $class_data): ?>

                                                    <div class="card class-detail-card mb-3" 
                                                         data-class-name="<?php echo htmlspecialchars($class_data['class_name']); ?>"
                                                         data-faculty="<?php echo htmlspecialchars($class_data['faculty_name']); ?>"
                                                         data-participation-rate="<?php echo $class_data['participation_rate']; ?>"
                                                         data-total-students="<?php echo $class_data['total_students']; ?>"
                                                         data-participating-students="<?php echo $class_data['participating_students']; ?>"
                                                         data-class-hash="<?php echo md5($class_code); ?>">

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

                                                                <!-- Bộ lọc sinh viên trong lớp -->
                                                                <div class="row mb-3">
                                                                    <div class="col-md-12">
                                                                        <div class="card">
                                                                            <div class="card-header">
                                                                                <h6 class="mb-0">
                                                                                    <i class="fas fa-filter"></i> Bộ lọc sinh viên
                                                                                </h6>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <div class="row">
                                                                                    <div class="col-md-4">
                                                                                        <label for="studentSearch<?php echo md5($class_code); ?>">Tìm kiếm sinh viên:</label>
                                                                                        <input type="text" 
                                                                                               class="form-control" 
                                                                                               id="studentSearch<?php echo md5($class_code); ?>" 
                                                                                               placeholder="Nhập tên hoặc MSSV...">
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <label for="participationFilter<?php echo md5($class_code); ?>">Lọc theo tham gia NCKH:</label>
                                                                                        <select class="form-control" id="participationFilter<?php echo md5($class_code); ?>">
                                                                                            <option value="">Tất cả</option>
                                                                                            <option value="1">Có tham gia</option>
                                                                                            <option value="0">Không tham gia</option>
                                                                                        </select>
                                                                                    </div>
                                                                                    <div class="col-md-4 d-flex align-items-end">
                                                                                        <div class="btn-group w-100">
                                                                                            <button type="button" 
                                                                                                    class="btn btn-primary btn-sm" 
                                                                                                    onclick="applyStudentFilter('<?php echo md5($class_code); ?>')">
                                                                                                <i class="fas fa-search"></i> Lọc
                                                                                            </button>
                                                                                            <button type="button" 
                                                                                                    class="btn btn-secondary btn-sm" 
                                                                                                    onclick="clearStudentFilter('<?php echo md5($class_code); ?>')">
                                                                                                <i class="fas fa-times"></i> Xóa lọc
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="row mt-2">
                                                                                    <div class="col-md-12">
                                                                                        <small class="text-muted">
                                                                                            Hiển thị: <span id="studentCount<?php echo md5($class_code); ?>"><?php echo count($class_data['students']); ?></span> 
                                                                                            / <?php echo count($class_data['students']); ?> sinh viên
                                                                                        </small>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
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

                                                                                class="student-row <?php echo $student['has_project'] ? 'has-project' : 'no-project'; ?>"
                                                                                data-student-name="<?php echo htmlspecialchars($student['SV_HOSV'] . ' ' . $student['SV_TENSV']); ?>"
                                                                                data-student-id="<?php echo htmlspecialchars($student['SV_MASV']); ?>"
                                                                                data-has-project="<?php echo $student['has_project'] ? '1' : '0'; ?>">

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



        $(document).ready(function () {

            // Initialize enhanced features

            initializeEnhancedReports();

            

            // Initialize DataTables with enhanced features

            initializeDataTables();

            

            // Initialize Charts with animations

            initializeCharts();

            

            // Initialize additional charts

            initializeAdditionalCharts();

            

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



            $('#researchFieldTable').DataTable(commonConfig);

            $('#projectTypeTable').DataTable(commonConfig);

            $('#progressTable').DataTable(commonConfig);

            $('#evaluationTable').DataTable(commonConfig);

            $('#yearlyTable').DataTable(commonConfig);

            $('#studentRoleTable').DataTable(commonConfig);



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





        }

        



        

        function initializeAdditionalCharts() {

            // Biểu đồ lĩnh vực nghiên cứu

            const researchFieldCtx = document.getElementById('researchFieldChart');

            if (researchFieldCtx) {

                const researchFieldData = {

                    labels: [],

                    datasets: [{

                        label: 'Số đề tài',

                        data: [],

                        backgroundColor: 'rgba(28, 200, 138, 0.8)',

                        borderColor: 'rgba(28, 200, 138, 1)',

                        borderWidth: 2,

                        borderRadius: 5

                    }, {

                        label: 'Số sinh viên',

                        data: [],

                        backgroundColor: 'rgba(246, 194, 62, 0.8)',

                        borderColor: 'rgba(246, 194, 62, 1)',

                        borderWidth: 2,

                        borderRadius: 5

                    }]

                };



                <?php

                $research_field_stats->data_seek(0);

                while ($row = $research_field_stats->fetch_assoc()) {

                    echo "researchFieldData.labels.push('{$row['LVNC_TEN']}');\n";

                    echo "researchFieldData.datasets[0].data.push({$row['project_count']});\n";

                    echo "researchFieldData.datasets[1].data.push({$row['student_count']});\n";

                }

                ?>



                new Chart(researchFieldCtx, {

                    type: 'bar',

                    data: researchFieldData,

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        animation: {

                            duration: 2000,

                            easing: 'easeOutQuart'

                        },

                        plugins: {

                            legend: { 

                                position: 'top',

                                labels: { usePointStyle: true }

                            },

                            tooltip: {

                                backgroundColor: 'rgba(255, 255, 255, 0.95)',

                                titleColor: '#000',

                                bodyColor: '#000'

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

            

            // Biểu đồ loại đề tài

            const projectTypeCtx = document.getElementById('projectTypeChart');

            if (projectTypeCtx) {

                const projectTypeData = {

                    labels: [],

                    datasets: [{

                        data: [],

                        backgroundColor: [],

                        borderWidth: 3,

                        borderColor: '#fff'

                    }]

                };



                <?php

                $project_type_detailed->data_seek(0);

                $type_counts = [];

                while ($row = $project_type_detailed->fetch_assoc()) {

                    $type = $row['LDT_TENLOAI'];

                    if (!isset($type_counts[$type])) {

                        $type_counts[$type] = 0;

                    }

                    $type_counts[$type] += $row['count'];

                }

                foreach ($type_counts as $type => $count) {

                    echo "projectTypeData.labels.push('{$type}');\n";

                    echo "projectTypeData.datasets[0].data.push({$count});\n";

                    echo "projectTypeData.datasets[0].backgroundColor.push('" . sprintf('#%06X', mt_rand(0, 0xFFFFFF)) . "');\n";

                }

                ?>



                new Chart(projectTypeCtx, {

                    type: 'doughnut',

                    data: projectTypeData,

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

                                bodyColor: '#000'

                            }

                        },

                        cutout: '60%'

                    }

                });

            }

            

            // Biểu đồ theo năm học

            const yearlyCtx = document.getElementById('yearlyChart');

            if (yearlyCtx) {

                const yearlyData = {

                    labels: [],

                    datasets: [{

                        label: 'Số đề tài',

                        data: [],

                        backgroundColor: 'rgba(78, 115, 223, 0.8)',

                        borderColor: 'rgba(78, 115, 223, 1)',

                        borderWidth: 2,

                        borderRadius: 5,

                        yAxisID: 'y'

                    }, {

                        label: 'Tỷ lệ hoàn thành (%)',

                        data: [],

                        backgroundColor: 'rgba(28, 200, 138, 0.8)',

                        borderColor: 'rgba(28, 200, 138, 1)',

                        borderWidth: 2,

                        borderRadius: 5,

                        yAxisID: 'y1'

                    }]

                };



                <?php

                $yearly_stats->data_seek(0);

                while ($row = $yearly_stats->fetch_assoc()) {

                    echo "yearlyData.labels.push('{$row['year']}');\n";

                    echo "yearlyData.datasets[0].data.push({$row['project_count']});\n";

                    echo "yearlyData.datasets[1].data.push(" . round($row['completion_rate'], 1) . ");\n";

                }

                ?>



                new Chart(yearlyCtx, {

                    type: 'bar',

                    data: yearlyData,

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        animation: {

                            duration: 2000,

                            easing: 'easeOutQuart'

                        },

                        plugins: {

                            legend: { 

                                position: 'top',

                                labels: { usePointStyle: true }

                            },

                            tooltip: {

                                backgroundColor: 'rgba(255, 255, 255, 0.95)',

                                titleColor: '#000',

                                bodyColor: '#000'

                            }

                        },

                        scales: {

                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.1)' }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                max: 100,
                                grid: { drawOnChartArea: false },
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }

                    }

                });

            }

            

            // Biểu đồ vai trò sinh viên

            const studentRoleCtx = document.getElementById('studentRoleChart');

            if (studentRoleCtx) {

                const studentRoleData = {

                    labels: [],

                    datasets: [{

                        label: 'Số sinh viên',

                        data: [],

                        backgroundColor: 'rgba(54, 185, 204, 0.8)',

                        borderColor: 'rgba(54, 185, 204, 1)',

                        borderWidth: 2,

                        borderRadius: 5

                    }]

                };



                <?php

                $student_role_stats->data_seek(0);

                while ($row = $student_role_stats->fetch_assoc()) {

                    echo "studentRoleData.labels.push('{$row['CTTG_VAITRO']}');\n";

                    echo "studentRoleData.datasets[0].data.push({$row['student_count']});\n";

                }

                ?>



                new Chart(studentRoleCtx, {

                    type: 'bar',

                    data: studentRoleData,

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

                                bodyColor: '#000'

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



        // Xử lý bộ lọc lớp

        $(document).ready(function() {

            // Xử lý sự kiện thay đổi bộ lọc

            $('#classDepartmentFilter, #classCourseFilter').on('change', function() {

                $('#classFilterForm').submit();

            });



            // Hiển thị thông báo khi không có dữ liệu

            if ($('#classDetails .class-detail-card').length === 0) {

                $('#classDetails').html(`

                    <div class="alert alert-info text-center">

                        <i class="fas fa-info-circle mr-2"></i>

                        Không có dữ liệu đề tài cho bộ lọc đã chọn.

                    </div>

                `);

            }

        });

        // Xử lý kích hoạt tab dựa trên tham số URL
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            
            if (activeTab && activeTab !== 'overview') {
                // Kích hoạt tab đúng
                $(`#${activeTab}-tab`).tab('show');
            }
        });

        // Bộ lọc cho chi tiết sinh viên theo lớp
        $(document).ready(function() {
            let originalClassCards = [];
            
            // Lưu trữ trạng thái ban đầu
            function saveOriginalState() {
                originalClassCards = [];
                $('.class-detail-card').each(function() {
                    originalClassCards.push($(this).clone(true));
                });
            }
            
            // Khôi phục trạng thái ban đầu
            function restoreOriginalState() {
                $('#classStudentsDetails').empty();
                originalClassCards.forEach(function(card) {
                    $('#classStudentsDetails').append(card);
                });
                updateFilterCount();
            }
            
            // Cập nhật số lượng kết quả
            function updateFilterCount() {
                const visibleCount = $('.class-detail-card:visible').length;
                $('#filterResultsCount').text(visibleCount);
            }
            
            // Lọc theo tên sinh viên hoặc MSSV
            function filterByStudent(studentSearch) {
                if (!studentSearch) return true;
                
                const searchLower = studentSearch.toLowerCase();
                let hasMatchingStudent = false;
                
                $(this).find('.student-row').each(function() {
                    const studentName = $(this).data('student-name').toLowerCase();
                    const studentId = $(this).data('student-id').toLowerCase();
                    
                    if (studentName.includes(searchLower) || studentId.includes(searchLower)) {
                        hasMatchingStudent = true;
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                return hasMatchingStudent;
            }
            
            // Áp dụng bộ lọc
            function applyFilters() {
                const classFilter = $('#classFilter').val();
                const facultyFilter = $('#facultyFilter').val();
                const participationFilter = $('#participationFilter').val();
                const studentSearch = $('#studentSearch').val();
                
                $('.class-detail-card').each(function() {
                    let showCard = true;
                    const $card = $(this);
                    
                    // Lọc theo tên lớp
                    if (classFilter && $card.data('class-name') !== classFilter) {
                        showCard = false;
                    }
                    
                    // Lọc theo khoa
                    if (facultyFilter && $card.data('faculty') !== facultyFilter) {
                        showCard = false;
                    }
                    
                    // Lọc theo tỷ lệ tham gia
                    if (participationFilter) {
                        const participationRate = parseFloat($card.data('participation-rate'));
                        const hasParticipatingStudents = parseInt($card.data('participating-students')) > 0;
                        
                        switch (participationFilter) {
                            case 'participating':
                                if (!hasParticipatingStudents) showCard = false;
                                break;
                            case 'not-participating':
                                if (hasParticipatingStudents) showCard = false;
                                break;
                            case 'high-participation':
                                if (participationRate <= 50) showCard = false;
                                break;
                            case 'low-participation':
                                if (participationRate > 50) showCard = false;
                                break;
                        }
                    }
                    
                    // Lọc theo tìm kiếm sinh viên
                    if (studentSearch && !filterByStudent.call($card, studentSearch)) {
                        showCard = false;
                    }
                    
                    if (showCard) {
                        $card.show();
                        // Hiển thị tất cả sinh viên trong lớp này nếu không có tìm kiếm
                        if (!studentSearch) {
                            $card.find('.student-row').show();
                        }
                    } else {
                        $card.hide();
                    }
                });
                
                updateFilterCount();
                
                // Hiển thị thông báo nếu không có kết quả
                if ($('.class-detail-card:visible').length === 0) {
                    if ($('#noResultsMessage').length === 0) {
                        $('#classStudentsDetails').append(`
                            <div id="noResultsMessage" class="alert alert-info text-center">
                                <i class="fas fa-search mr-2"></i>
                                Không tìm thấy lớp nào phù hợp với bộ lọc đã chọn.
                            </div>
                        `);
                    }
                } else {
                    $('#noResultsMessage').remove();
                }
            }
            
            // Xử lý sự kiện nút áp dụng bộ lọc
            $('#applyFilters').on('click', function() {
                applyFilters();
            });
            
            // Xử lý sự kiện nút xóa bộ lọc
            $('#clearFilters').on('click', function() {
                $('#classFilter').val('');
                $('#facultyFilter').val('');
                $('#participationFilter').val('');
                $('#studentSearch').val('');
                restoreOriginalState();
            });
            
            // Xử lý sự kiện Enter trong ô tìm kiếm
            $('#studentSearch').on('keypress', function(e) {
                if (e.which === 13) {
                    applyFilters();
                }
            });
            
            // Xử lý sự kiện thay đổi bộ lọc (tự động áp dụng)
            $('#classFilter, #facultyFilter, #participationFilter').on('change', function() {
                applyFilters();
            });
            
            // Khởi tạo
            saveOriginalState();
            updateFilterCount();
        });

        // Hàm lọc sinh viên trong từng lớp
        function applyStudentFilter(classHash) {
            const searchTerm = $(`#studentSearch${classHash}`).val().toLowerCase();
            const participationFilter = $(`#participationFilter${classHash}`).val();
            
            // Tìm bảng sinh viên trong lớp cụ thể
            const $card = $(`#classStudentsDetails .card[data-class-hash="${classHash}"]`);
            const $table = $card.find('.students-table');
            const $rows = $table.find('.student-row');
            let visibleCount = 0;
            
            $rows.each(function() {
                const $row = $(this);
                const studentName = $row.data('student-name') || '';
                const studentId = $row.data('student-id') || '';
                // Chuẩn hóa về chuỗi để so sánh ổn định ('1'/'0'), tránh jQuery .data() tự ép kiểu số
                const hasProject = String($row.attr('data-has-project') ?? '0');
                
                let showRow = true;
                
                // Lọc theo tìm kiếm
                if (searchTerm) {
                    if (!studentName.toLowerCase().includes(searchTerm) && !studentId.toLowerCase().includes(searchTerm)) {
                        showRow = false;
                    }
                }
                
                // Lọc theo tham gia NCKH
                if (participationFilter !== '') {
                    if (participationFilter === '1' && hasProject !== '1') {
                        showRow = false;
                    } else if (participationFilter === '0' && hasProject !== '0') {
                        showRow = false;
                    }
                }
                
                if (showRow) {
                    $row.show();
                    visibleCount++;
                } else {
                    $row.hide();
                }
            });
            
            // Cập nhật số lượng hiển thị
            $(`#studentCount${classHash}`).text(visibleCount);
            
            // Hiển thị thông báo nếu không có kết quả
            const $noResults = $(`#noStudentResults${classHash}`);
            if (visibleCount === 0) {
                if ($noResults.length === 0) {
                    $table.after(`
                        <div id="noStudentResults${classHash}" class="alert alert-info text-center mt-3">
                            <i class="fas fa-search mr-2"></i>
                            Không tìm thấy sinh viên nào phù hợp với bộ lọc đã chọn.
                        </div>
                    `);
                }
            } else {
                $noResults.remove();
            }
        }
        
        // Hàm xóa bộ lọc sinh viên
        function clearStudentFilter(classHash) {
            $(`#studentSearch${classHash}`).val('');
            $(`#participationFilter${classHash}`).val('');
            
            const $card = $(`#classStudentsDetails .card[data-class-hash="${classHash}"]`);
            const $table = $card.find('.students-table');
            const $rows = $table.find('.student-row');
            const totalStudents = $rows.length;
            
            $rows.show();
            $(`#studentCount${classHash}`).text(totalStudents);
            $(`#noStudentResults${classHash}`).remove();
        }
        
        // Xử lý sự kiện Enter trong ô tìm kiếm sinh viên
        $(document).on('keypress', '[id^="studentSearch"]', function(e) {
            if (e.which === 13) {
                const classHash = $(this).attr('id').replace('studentSearch', '');
                applyStudentFilter(classHash);
            }
        });
        
        // Xử lý sự kiện thay đổi bộ lọc tham gia (tự động áp dụng)
        $(document).on('change', '[id^="participationFilter"]', function() {
            const classHash = $(this).attr('id').replace('participationFilter', '');
            applyStudentFilter(classHash);
        });

    </script>

</body>



</html>
