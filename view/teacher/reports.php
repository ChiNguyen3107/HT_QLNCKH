<?php
// Bao gồm file session và kết nối CSDL
include '../../include/session.php';
checkTeacherRole();
include '../../include/connect.php';

// Lấy thông tin giảng viên
$teacher_id = $_SESSION['user_id'];

// Lọc theo năm học (nếu có)
$current_year = date("Y");
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

// Lọc theo học kỳ (nếu có)
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : '';

// Lọc theo lớp (nếu có)
$selected_class = isset($_GET['class']) ? $_GET['class'] : '';

// Lấy danh sách năm học từ CSDL để hiển thị trong filter
$years_query = "SELECT DISTINCT YEAR(cttg.CTTG_NGAYTHAMGIA) AS year
                FROM chi_tiet_tham_gia cttg 
                JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                WHERE dt.GV_MAGV = ?
                ORDER BY year DESC";
$stmt = $conn->prepare($years_query);
if ($stmt === false) {
    die("Lỗi truy vấn năm học: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$years_result = $stmt->get_result();
$years = [];
while ($row = $years_result->fetch_assoc()) {
    $years[] = $row['year'];
}

// Lấy danh sách học kỳ từ CSDL
$semesters_query = "SELECT * FROM hoc_ki ORDER BY HK_MA";
$semesters_result = $conn->query($semesters_query);
if ($semesters_result === false) {
    die("Lỗi truy vấn học kỳ: " . $conn->error);
}
$semesters = [];
while ($row = $semesters_result->fetch_assoc()) {
    $semesters[] = $row;
}

// Lấy danh sách lớp từ CSDL
$classes_query = "SELECT DISTINCT l.LOP_MA, l.LOP_TEN 
                  FROM lop l 
                  JOIN sinh_vien sv ON sv.LOP_MA = l.LOP_MA
                  JOIN chi_tiet_tham_gia cttg ON cttg.SV_MASV = sv.SV_MASV
                  JOIN de_tai_nghien_cuu dt ON dt.DT_MADT = cttg.DT_MADT
                  WHERE dt.GV_MAGV = ?
                  ORDER BY l.LOP_TEN";
$stmt = $conn->prepare($classes_query);
if ($stmt === false) {
    die("Lỗi truy vấn lớp: " . $conn->error);
}
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$classes_result = $stmt->get_result();
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row;
}

// Xây dựng điều kiện lọc
$where_conditions = "dt.GV_MAGV = ?";
$params = [$teacher_id];
$param_types = "s";

if (!empty($selected_year)) {
    $where_conditions .= " AND YEAR(cttg.CTTG_NGAYTHAMGIA) = ?";
    $params[] = $selected_year;
    $param_types .= "s";
}

if (!empty($selected_semester)) {
    $where_conditions .= " AND cttg.HK_MA = ?";
    $params[] = $selected_semester;
    $param_types .= "s";
}

if (!empty($selected_class)) {
    $where_conditions .= " AND sv.LOP_MA = ?";
    $params[] = $selected_class;
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
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }

        .stats-card {
            border-left: 4px solid;
            margin-bottom: 1.5rem;
        }

        .stats-card.primary {
            border-left-color: #4e73df;
        }

        .stats-card.success {
            border-left-color: #1cc88a;
        }

        .stats-card.info {
            border-left-color: #36b9cc;
        }

        .stats-card.warning {
            border-left-color: #f6c23e;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .stats-label {
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            margin-bottom: 0;
        }

        .status-indicator {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .class-detail-card {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .class-detail-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem;
        }

        .export-buttons {
            margin-bottom: 1rem;
        }

        .filter-section {
            background-color: #f8f9fc;
            padding: 1rem;
            border-radius: 0.35rem;
            margin-bottom: 1.5rem;
        }

        .student-project-list {
            margin-top: 0.5rem;
            padding-left: 1rem;
        }

        .student-project-item {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-left: 3px solid #4e73df;
            background-color: #f8f9fc;
        }

        .student-row {
            transition: all 0.2s ease;
        }

        .student-row:hover {
            background-color: #f1f5fb;
        }

        .student-row.has-project {
            font-weight: 500;
        }

        .student-row.no-project {
            color: #6c757d;
        }

        .participation-badge {
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
        }

        .nav-item .active {
            font-weight: bold;
        }

        .student-list-section {
            border-top: 1px solid #e3e6f0;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .progress-thin {
            height: 0.5rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .chart-container {
                height: 250px !important;
            }

            .tab-pane {
                display: block !important;
                opacity: 1 !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            body {
                padding: 1cm;
            }

            .tab-content>.tab-pane {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
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

                    <!-- Filter Section -->
                    <div class="filter-section mb-4 no-print">
                        <form method="get" action="" class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label for="year">Năm học:</label>
                                <select name="year" id="year" class="form-control">
                                    <option value="">Tất cả các năm</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="semester">Học kỳ:</label>
                                <select name="semester" id="semester" class="form-control">
                                    <option value="">Tất cả học kỳ</option>
                                    <?php foreach ($semesters as $semester): ?>
                                        <option value="<?php echo $semester['HK_MA']; ?>" <?php echo ($selected_semester == $semester['HK_MA']) ? 'selected' : ''; ?>>
                                            <?php echo $semester['HK_TEN']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="class">Lớp:</label>
                                <select name="class" id="class" class="form-control">
                                    <option value="">Tất cả các lớp</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['LOP_MA']; ?>" <?php echo ($selected_class == $class['LOP_MA']) ? 'selected' : ''; ?>>
                                            <?php echo $class['LOP_TEN']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-filter mr-1"></i>Lọc
                                </button>
                                <a href="reports.php" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt mr-1"></i>Đặt lại
                                </a>
                            </div>
                        </form>
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
                                                <canvas id="studentParticipationChart"></canvas>
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
            // Khởi tạo DataTables
            $('#classStatsTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-sm btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-sm btn-danger',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> In',
                        className: 'btn btn-sm btn-primary',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ]
            });

            $('#classStatusTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                }
            });

            $('#studentStatusTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                }
            });

            $('.class-details-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                },
                pageLength: 5,
                lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "Tất cả"]]
            });

            $('.students-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]]
            });

            // Biểu đồ theo lớp
            const classCtx = document.getElementById('classChart').getContext('2d');
            const classChart = new Chart(classCtx, {
                type: 'bar',
                data: {
                    labels: classLabels,
                    datasets: [{
                        label: 'Số lượng đề tài',
                        data: classData,
                        backgroundColor: classColors,
                        borderColor: classColors.map(color => color),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `Số đề tài: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });

            // Biểu đồ trạng thái
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusData = {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [],
                    hoverOffset: 4
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

            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: statusData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Biểu đồ lớp-trạng thái
            const classStatusCtx = document.getElementById('classStatusChart').getContext('2d');

            // Tạo dataset cho từng trạng thái
            const datasets = [];
            allStatuses.forEach((status, index) => {
                const backgroundColor = statusColors[status] || `hsl(${index * (360 / allStatuses.length)}, 70%, 60%)`;

                const dataset = {
                    label: status,
                    data: classStatusData.map(classData => {
                        return classData.statuses[status] || 0;
                    }),
                    backgroundColor
                };

                datasets.push(dataset);
            });

            const classStatusChart = new Chart(classStatusCtx, {
                type: 'bar',
                data: {
                    labels: classStatusData.map(data => data.class),
                    datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Biểu đồ sinh viên theo trạng thái
            const studentStatusCtx = document.getElementById('studentStatusChart').getContext('2d');

            // Tạo dataset cho từng trạng thái
            const studentDatasets = [];
            allStatuses.forEach((status, index) => {
                const backgroundColor = statusColors[status] || `hsl(${index * (360 / allStatuses.length)}, 70%, 60%)`;

                const dataset = {
                    label: status,
                    data: classStudentsStatusData.map(classData => {
                        return classData.statuses[status] || 0;
                    }),
                    backgroundColor
                };

                studentDatasets.push(dataset);
            });

            const studentStatusChart = new Chart(studentStatusCtx, {
                type: 'bar',
                data: {
                    labels: classStudentsStatusData.map(data => data.class),
                    datasets: studentDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Biểu đồ tỷ lệ tham gia của sinh viên theo lớp
            const studentParticipationCtx = document.getElementById('studentParticipationChart').getContext('2d');
            const participationData = {
                labels: [],
                datasets: [
                    {
                        label: 'Sinh viên tham gia',
                        data: [],
                        backgroundColor: '#4e73df',
                    },
                    {
                        label: 'Sinh viên không tham gia',
                        data: [],
                        backgroundColor: '#e74a3b',
                    }
                ]
            };

            <?php
            foreach ($class_students_details as $class_code => $class_data) {
                $nonParticipating = $class_data['total_students'] - $class_data['participating_students'];
                echo "participationData.labels.push('" . addslashes($class_data['class_name']) . "');\n";
                echo "participationData.datasets[0].data.push({$class_data['participating_students']});\n";
                echo "participationData.datasets[1].data.push({$nonParticipating});\n";
            }
            ?>

            const studentParticipationChart = new Chart(studentParticipationCtx, {
                type: 'bar',
                data: participationData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Biểu đồ cho từng lớp
            <?php foreach ($class_students_details as $class_code => $class_data): ?>
                const classParticipationCtx<?php echo md5($class_code); ?> = document.getElementById('classParticipationChart<?php echo md5($class_code); ?>').getContext('2d');
                const pieData<?php echo md5($class_code); ?> = {
                    labels: ['Tham gia NCKH', 'Không tham gia'],
                    datasets: [{
                        data: [
                            <?php echo $class_data['participating_students']; ?>,
                            <?php echo $class_data['total_students'] - $class_data['participating_students']; ?>
                        ],
                        backgroundColor: ['#4e73df', '#e74a3b'],
                        borderWidth: 1
                    }]
                };

                const classParticipationChart<?php echo md5($class_code); ?> = new Chart(classParticipationCtx<?php echo md5($class_code); ?>, {
                    type: 'pie',
                    data: pieData<?php echo md5($class_code); ?>,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            <?php endforeach; ?>

            // Xử lý nút in báo cáo
            $('#printReport').click(function () {
                window.print();
            });

            // Xử lý xuất Excel
            $('#exportExcel').click(function () {
                $('#classStatsTable').DataTable().button('0').trigger();
            });

            // Xử lý xuất PDF
            $('#exportPDF').click(function () {
                $('#classStatsTable').DataTable().button('1').trigger();
            });
        });
    </script>
</body>

</html>