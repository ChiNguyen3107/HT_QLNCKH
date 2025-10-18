<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\reports.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập
include '../../include/session.php';
checkAdminRole();
// Kết nối database
include '../../include/connect.php';

// Xác định năm mặc định và năm được chọn
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';

// Tạo danh sách năm từ năm hiện tại trở về 5 năm trước
$years = [];
for ($i = 0; $i <= 5; $i++) {
    $years[] = $current_year - $i;
}

// Thống kê đề tài theo trạng thái
$status_query = "SELECT 
                    DT_TRANGTHAI AS status,
                    COUNT(*) AS count 
                 FROM de_tai_nghien_cuu";

// Lọc theo năm - Chú ý: Không phải tất cả đề tài đều có hợp đồng
if ($selected_year > 0) {
    // Sử dụng LEFT JOIN để bao gồm cả đề tài không có hợp đồng
    $status_query = "SELECT 
                        dt.DT_TRANGTHAI AS status,
                        COUNT(dt.DT_MADT) AS count 
                     FROM de_tai_nghien_cuu dt
                     LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                     WHERE (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
    
    if (!empty($selected_department)) {
        $status_query .= " AND dt.GV_MAGV IN (
                            SELECT GV_MAGV FROM giang_vien 
                            WHERE DV_MADV = '" . $conn->real_escape_string($selected_department) . "'
                          )";
    }
    
    $status_query .= " GROUP BY dt.DT_TRANGTHAI";
} else {
    if (!empty($selected_department)) {
        $status_query .= " WHERE GV_MAGV IN (
                            SELECT GV_MAGV FROM giang_vien 
                            WHERE DV_MADV = '" . $conn->real_escape_string($selected_department) . "'
                          )";
    }
    
    $status_query .= " GROUP BY DT_TRANGTHAI";
}

$status_result = $conn->query($status_query);

// Thêm kiểm tra và tạo mảng dữ liệu mặc định
$status_data = [
    'labels' => [],
    'data' => []
];

if ($status_result && $status_result->num_rows > 0) {
    while($row = $status_result->fetch_assoc()) {
        $status_data['labels'][] = $row['status'];
        $status_data['data'][] = (int)$row['count'];
    }
} else {
    // Thêm dữ liệu mẫu nếu không có dữ liệu
    $status_data['labels'] = ['Không có dữ liệu'];
    $status_data['data'] = [0];
}

// Thống kê đề tài theo khoa
$dept_query = "SELECT 
                k.DV_TENDV AS department,
                COUNT(dt.DT_MADT) AS count
              FROM de_tai_nghien_cuu dt
              JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
              JOIN khoa k ON gv.DV_MADV = k.DV_MADV";

if ($selected_year > 0) {
    $dept_query .= " LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                   WHERE (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
}

if (!empty($selected_department)) {
    $dept_query .= ($selected_year > 0) ? " AND" : " WHERE";
    $dept_query .= " gv.DV_MADV = '" . $conn->real_escape_string($selected_department) . "'";
}

$dept_query .= " GROUP BY k.DV_TENDV ORDER BY count DESC";
$dept_result = $conn->query($dept_query);

$dept_data = [
    'labels' => [],
    'data' => []
];

if ($dept_result && $dept_result->num_rows > 0) {
    while($row = $dept_result->fetch_assoc()) {
        $dept_data['labels'][] = $row['department'];
        $dept_data['data'][] = (int)$row['count'];
    }
} else {
    // Thêm dữ liệu mẫu nếu không có dữ liệu
    $dept_data['labels'] = ['Không có dữ liệu'];
    $dept_data['data'] = [0];
}

// Thống kê đề tài theo loại
$type_query = "SELECT 
                IFNULL(ldt.LDT_TENLOAI, 'Chưa phân loại') AS type,
                COUNT(dt.DT_MADT) AS count
              FROM de_tai_nghien_cuu dt
              LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA";

if ($selected_year > 0) {
    $type_query .= " LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                   WHERE (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
}

if (!empty($selected_department)) {
    $type_query .= ($selected_year > 0) ? " AND" : " WHERE";
    $type_query .= " dt.GV_MAGV IN (
                       SELECT GV_MAGV FROM giang_vien 
                       WHERE DV_MADV = '" . $conn->real_escape_string($selected_department) . "'
                     )";
}

$type_query .= " GROUP BY IFNULL(ldt.LDT_TENLOAI, 'Chưa phân loại') ORDER BY count DESC";
$type_result = $conn->query($type_query);

$type_data = [
    'labels' => [],
    'data' => []
];

if ($type_result && $type_result->num_rows > 0) {
    while($row = $type_result->fetch_assoc()) {
        $type_data['labels'][] = $row['type'];
        $type_data['data'][] = (int)$row['count'];
    }
} else {
    // Thêm dữ liệu mẫu nếu không có dữ liệu
    $type_data['labels'] = ['Không có dữ liệu'];
    $type_data['data'] = [0];
}

// Top 5 giảng viên có nhiều đề tài nhất
$teacher_query = "SELECT 
                    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS teacher_name,
                    COUNT(dt.DT_MADT) AS project_count
                  FROM giang_vien gv
                  JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV";

if ($selected_year > 0) {
    $teacher_query .= " LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                      WHERE (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
}

if (!empty($selected_department)) {
    $teacher_query .= ($selected_year > 0) ? " AND" : " WHERE";
    $teacher_query .= " gv.DV_MADV = '" . $conn->real_escape_string($selected_department) . "'";
}

$teacher_query .= " GROUP BY gv.GV_MAGV
                   ORDER BY project_count DESC
                   LIMIT 5";

$teacher_result = $conn->query($teacher_query);

// Thống kê sinh viên tham gia đề tài theo lớp
$class_query = "SELECT 
                l.LOP_TEN AS class_name,
                l.LOP_MA AS class_id,
                l.KH_NAM AS class_year,
                k.DV_TENDV AS department_name,
                COUNT(DISTINCT ct.SV_MASV) AS student_count
              FROM chi_tiet_tham_gia ct
              JOIN sinh_vien sv ON ct.SV_MASV = sv.SV_MASV
              JOIN lop l ON sv.LOP_MA = l.LOP_MA
              JOIN khoa k ON l.DV_MADV = k.DV_MADV
              JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT";

if ($selected_year > 0) {
    $class_query .= " LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                    WHERE (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
}

if (!empty($selected_department)) {
    $class_query .= ($selected_year > 0) ? " AND" : " WHERE";
    $class_query .= " l.DV_MADV = '" . $conn->real_escape_string($selected_department) . "'";
}

$class_query .= " GROUP BY l.LOP_TEN, l.LOP_MA, l.KH_NAM, k.DV_TENDV 
                ORDER BY student_count DESC";
$class_result = $conn->query($class_query);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê | Admin</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Tham chiếu đến file CSS riêng -->
    <link href="/NLNganh/assets/css/admin/reports.css" rel="stylesheet">
    <style>
        /* Định dạng cho liên kết lớp */
        .class-link {
            color: #4e73df;
            text-decoration: none;
            cursor: pointer;
        }
        
        .class-link:hover {
            text-decoration: underline;
            color: #2e59d9;
        }
        
        /* Định dạng cho bảng sinh viên */
        #studentTable {
            font-size: 0.9rem;
        }
        
        #studentTable th {
            vertical-align: middle;
        }
        
        #studentListModal .modal-xl {
            max-width: 90%;
        }
    </style>
</head>

<body>
    <?php include '../../include/admin_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Báo cáo thống kê</li>
                </ol>
            </nav>

            <h1 class="page-header mb-4">Báo cáo thống kê</h1>

            <!-- Bộ lọc báo cáo -->
            <div class="report-filters">
                <form method="get" class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label for="year">Chọn năm:</label>
                        <select name="year" id="year" class="form-control">
                            <option value="0" <?php echo ($selected_year == 0) ? 'selected' : ''; ?>>Tất cả các năm</option>
                            <?php foreach($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="department">Chọn khoa:</label>
                        <select name="department" id="department" class="form-control">
                            <option value="">Tất cả các khoa</option>
                            <?php
                            $dept_list_query = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV";
                            $dept_list_result = $conn->query($dept_list_query);
                            if ($dept_list_result && $dept_list_result->num_rows > 0) {
                                while($row = $dept_list_result->fetch_assoc()) {
                                    $selected = ($selected_department == $row['DV_MADV']) ? 'selected' : '';
                                    echo "<option value='".$row['DV_MADV']."' $selected>".$row['DV_TENDV']."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-filter mr-1"></i> Lọc
                        </button>
                        <a href="reports.php" class="btn btn-secondary">
                            <i class="fas fa-sync-alt mr-1"></i> Đặt lại
                        </a>
                    </div>
                </form>
            </div>

            <div class="mb-3 text-right">
                <button id="testModalBtn" class="btn btn-info btn-sm">Test Modal</button>
            </div>

            <!-- Các nút xuất báo cáo -->
            <div class="mb-4">
                <button class="btn btn-outline-success mr-2" id="exportExcel">
                    <i class="fas fa-file-excel mr-1"></i> Xuất Excel
                </button>
                <button class="btn btn-outline-danger mr-2" id="exportPDF">
                    <i class="fas fa-file-pdf mr-1"></i> Xuất PDF
                </button>
                <button class="btn btn-outline-info" id="exportCSV">
                    <i class="fas fa-file-csv mr-1"></i> Xuất CSV
                </button>
            </div>

            <!-- Thống kê nhanh -->
            <div class="row mb-4">
                <!-- Tổng số đề tài -->
                <?php
                $total_projects_query = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu";
                if ($selected_year > 0) {
                    $total_projects_query = "SELECT COUNT(dt.DT_MADT) AS total 
                                            FROM de_tai_nghien_cuu dt
                                            LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                                            WHERE (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
                }
                
                if (!empty($selected_department)) {
                    $total_projects_query .= (strpos($total_projects_query, 'WHERE') !== false) ? " AND" : " WHERE";
                    $total_projects_query .= " dt.GV_MAGV IN (
                                            SELECT GV_MAGV FROM giang_vien 
                                            WHERE DV_MADV = '" . $conn->real_escape_string($selected_department) . "'
                                          )";
                }
                
                $total_result = $conn->query($total_projects_query);
                $total_projects = ($total_result && $total_result->num_rows > 0) ? $total_result->fetch_assoc()['total'] : 0;
                ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2 card-stats">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1 stats-label">
                                        Tổng số đề tài
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $total_projects; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300 card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đề tài đã hoàn thành -->
                <?php
                $completed_query = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hoàn thành'";
                if ($selected_year > 0) {
                    $completed_query = "SELECT COUNT(dt.DT_MADT) AS total 
                                        FROM de_tai_nghien_cuu dt
                                        LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                                        WHERE dt.DT_TRANGTHAI = 'Đã hoàn thành'
                                        AND (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
                }
                
                if (!empty($selected_department)) {
                    $completed_query .= (strpos($completed_query, 'WHERE') !== false) ? " AND" : " WHERE";
                    $completed_query .= " dt.GV_MAGV IN (
                                        SELECT GV_MAGV FROM giang_vien 
                                        WHERE DV_MADV = '" . $conn->real_escape_string($selected_department) . "'
                                      )";
                }
                
                $completed_result = $conn->query($completed_query);
                $completed_projects = ($completed_result && $completed_result->num_rows > 0) ? $completed_result->fetch_assoc()['total'] : 0;
                ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2 card-stats">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1 stats-label">
                                        Đã hoàn thành
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $completed_projects; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300 card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đề tài đang thực hiện -->
                <?php
                $ongoing_query = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đang thực hiện'";
                if ($selected_year > 0) {
                    $ongoing_query = "SELECT COUNT(dt.DT_MADT) AS total 
                                      FROM de_tai_nghien_cuu dt
                                      LEFT JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                                      WHERE dt.DT_TRANGTHAI = 'Đang thực hiện'
                                      AND (hd.HD_MA IS NULL OR YEAR(hd.HD_NGAYBD) = $selected_year)";
                }
                
                if (!empty($selected_department)) {
                    $ongoing_query .= (strpos($ongoing_query, 'WHERE') !== false) ? " AND" : " WHERE";
                    $ongoing_query .= " dt.GV_MAGV IN (
                                      SELECT GV_MAGV FROM giang_vien 
                                      WHERE DV_MADV = '" . $conn->real_escape_string($selected_department) . "'
                                    )";
                }
                
                $ongoing_result = $conn->query($ongoing_query);
                $ongoing_projects = ($ongoing_result && $ongoing_result->num_rows > 0) ? $ongoing_result->fetch_assoc()['total'] : 0;
                ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2 card-stats">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1 stats-label">
                                        Đang thực hiện
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $ongoing_projects; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-spinner fa-2x text-gray-300 card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tỉ lệ hoàn thành -->
                <?php 
                $completion_rate = ($total_projects > 0) ? round(($completed_projects / $total_projects) * 100) : 0;
                ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2 card-stats">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1 stats-label">
                                        Tỉ lệ hoàn thành
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800 stats-number"><?php echo $completion_rate; ?>%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-percentage fa-2x text-gray-300 card-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ và bảng thống kê -->
            <div class="row">
                <!-- Biểu đồ phân bố đề tài theo trạng thái -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow report-card">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Phân bố đề tài theo trạng thái</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ phân bố đề tài theo khoa -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow report-card">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Phân bố đề tài theo khoa</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="departmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ phân bố đề tài theo loại -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow report-card">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Phân bố đề tài theo loại</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="typeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 giảng viên có nhiều đề tài nhất -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow report-card">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Top 5 giảng viên có nhiều đề tài nhất</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>STT</th>
                                            <th>Họ và tên</th>
                                            <th>Số đề tài</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($teacher_result && $teacher_result->num_rows > 0) {
                                            $i = 1;
                                            while($row = $teacher_result->fetch_assoc()) {
                                                echo "<tr>
                                                        <td>".$i++."</td>
                                                        <td>".$row['teacher_name']."</td>
                                                        <td>".$row['project_count']."</td>
                                                    </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='3' class='text-center'>Không có dữ liệu</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thống kê sinh viên tham gia đề tài theo lớp -->
                <div class="col-lg-12 mb-4">
                    <div class="card shadow report-card">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Thống kê sinh viên tham gia đề tài theo lớp</h6>
                        </div>
                        <div class="card-body">
                            <!-- Bộ lọc cho danh sách lớp -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="classDepartmentFilter">Chọn khoa:</label>
                                    <select class="form-control" id="classDepartmentFilter">
                                        <option value="">-- Chọn khoa --</option>
                                        <?php
                                        $dept_classes_query = "SELECT DISTINCT k.DV_MADV, k.DV_TENDV 
                                                              FROM khoa k 
                                                              JOIN lop l ON k.DV_MADV = l.DV_MADV 
                                                              ORDER BY k.DV_TENDV";
                                        $dept_classes_result = $conn->query($dept_classes_query);
                                        if ($dept_classes_result && $dept_classes_result->num_rows > 0) {
                                            while($row = $dept_classes_result->fetch_assoc()) {
                                                echo "<option value='".$row['DV_MADV']."'>".$row['DV_TENDV']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="classYearFilter">Chọn khóa học:</label>
                                    <select class="form-control" id="classYearFilter" disabled>
                                        <option value="">-- Vui lòng chọn khoa trước --</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button id="loadClassesBtn" class="btn btn-primary" disabled>
                                        <i class="fas fa-search mr-1"></i> Xem danh sách lớp
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Hiển thị thông tin lớp -->
                            <div id="classListContainer">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> Vui lòng chọn khoa và khóa học để xem danh sách lớp
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xem danh sách sinh viên theo lớp -->
<div class="modal fade" id="studentListModal" tabindex="-1" role="dialog" aria-labelledby="studentListModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentListModalTitle">Danh sách sinh viên lớp <span id="className"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Thông tin tổng quát -->
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Lớp:</strong> <span id="modalClassName"></span></p>
                            <p class="mb-1"><strong>Khoa:</strong> <span id="modalDepartment"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Khóa học:</strong> <span id="modalClassYear"></span></p>
                            <p class="mb-1"><strong>Tổng sinh viên tham gia đề tài:</strong> <span id="modalStudentCount"></span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Bộ lọc sinh viên -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="filterRole">Vai trò:</label>
                        <select class="form-control" id="filterRole">
                            <option value="">Tất cả</option>
                            <option value="Chủ nhiệm">Chủ nhiệm</option>
                            <option value="Thành viên">Thành viên</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterStatus">Trạng thái đề tài:</label>
                        <select class="form-control" id="filterStatus">
                            <option value="">Tất cả</option>
                            <option value="Đang thực hiện">Đang thực hiện</option>
                            <option value="Đã hoàn thành">Đã hoàn thành</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterKeyword">Tìm kiếm:</label>
                        <input type="text" class="form-control" id="filterKeyword" placeholder="MSSV hoặc tên sinh viên...">
                    </div>
                </div>
                
                <!-- Danh sách sinh viên -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="studentTable">
                        <thead class="thead-light">
                            <tr>
                                <th>STT</th>
                                <th>MSSV</th>
                                <th>Họ và tên</th>
                                <th>Vai trò</th>
                                <th>Tên đề tài</th>
                                <th>Trạng thái đề tài</th>
                                <th>GVHD</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <tr>
                                <td colspan="7" class="text-center">Đang tải dữ liệu...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Phân trang và thông tin -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>Hiển thị <span id="displayedCount">0</span> / <span id="totalCount">0</span> sinh viên</div>
                    <button class="btn btn-outline-success" id="exportClassStudents">
                        <i class="fas fa-file-excel mr-1"></i> Xuất danh sách
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

    <!-- Template cho bảng danh sách lớp -->
    <script type="text/template" id="classTableTemplate">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0" id="classStudentsTable">
                <thead class="thead-light">
                    <tr>
                        <th>STT</th>
                        <th>Mã lớp</th>
                        <th>Tên lớp</th>
                        <th>Khóa học</th>
                        <th>Khoa</th>
                        <th>Số sinh viên tham gia</th>
                        <th>Tỷ lệ (%)</th>
                    </tr>
                </thead>
                <tbody id="classTableBody">
                    <!-- Dữ liệu sẽ được thêm bằng JavaScript -->
                </tbody>
            </table>
        </div>
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    
    <!-- Tham chiếu đến file JS riêng -->
    <script src="/NLNganh/assets/js/admin/reports.js"></script>

    <!-- Script xử lý dữ liệu và sự kiện -->
    <script>
        // Khởi tạo biểu đồ
        document.addEventListener('DOMContentLoaded', function() {
            // Chuyển dữ liệu PHP sang JavaScript
            try {
                initCharts(
                    <?php echo json_encode($status_data); ?>,
                    <?php echo json_encode($dept_data); ?>,
                    <?php echo json_encode($type_data); ?>
                );
            } catch(e) {
                console.error("Error initializing charts:", e);
            }
        });

        // Xử lý sự kiện mở modal khi click vào tên lớp
        $(document).on('click', '.class-link', function(e) {
            e.preventDefault();
            console.log("Class link clicked");
            
            const classId = $(this).data('class-id');
            const className = $(this).data('class-name');
            const classYear = $(this).closest('tr').find('td:eq(3)').text(); // Lấy năm khóa từ cột thứ 4
            const department = $(this).closest('tr').find('td:eq(4)').text(); // Lấy tên khoa từ cột thứ 5
            const studentCount = $(this).closest('tr').find('td:eq(5)').text(); // Lấy số lượng SV từ cột thứ 6
            
            console.log("Class ID:", classId);
            console.log("Class name:", className);
            
            if (!classId) {
                console.error("No class ID found");
                return;
            }
            
            // Cập nhật tiêu đề và thông tin modal
            $('#className').text(className || 'N/A');
            $('#modalClassName').text(className || 'N/A');
            $('#modalClassYear').text(classYear || 'N/A');
            $('#modalDepartment').text(department || 'N/A');
            $('#modalStudentCount').text(studentCount || '0');
            
            $('#studentListModal').data('class-id', classId);
            
            // Reset các bộ lọc
            $('#filterRole, #filterStatus').val('');
            $('#filterKeyword').val('');
            
            // Hiển thị modal
            $('#studentListModal').modal('show');
            
            // Tải dữ liệu sinh viên
            loadStudents(classId);
        });

        // Xử lý sự kiện thay đổi bộ lọc
        $('#filterRole, #filterStatus').on('change', function() {
            const classId = $('#studentListModal').data('class-id');
            if (classId) {
                loadStudents(classId);
            }
        });
        
        $('#filterKeyword').on('keyup', function() {
            const classId = $('#studentListModal').data('class-id');
            if (classId) {
                loadStudents(classId);
            }
        });

        // Xử lý xuất Excel cho danh sách sinh viên
        $('#exportClassStudents').on('click', function() {
            const table = document.getElementById('studentTable');
            const className = $('#className').text();
            
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            
            // Đặt tên sheet và workbook
            XLSX.utils.book_append_sheet(wb, ws, "SinhVien_" + className);
            
            // Xuất file
            const now = new Date();
            const fileName = `DanhSachSinhVien_${className}_${now.getDate()}_${now.getMonth() + 1}_${now.getFullYear()}.xlsx`;
            XLSX.writeFile(wb, fileName);
        });

        $('#testModalBtn').on('click', function() {
            console.log("Test button clicked");
            $('#className').text("Test Class");
            $('#studentListModal').modal('show');
        });
        
        // Hàm tải dữ liệu sinh viên
        function loadStudents(classId) {
            console.log("Loading students for class ID:", classId);
            
            const role = $('#filterRole').val();
            const status = $('#filterStatus').val();
            const keyword = $('#filterKeyword').val();
            
            // Hiển thị loading
            $('#studentTableBody').html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu...</td></tr>');
            
            // Gửi AJAX request
            $.ajax({
                url: 'get_class_students.php',
                type: 'GET',
                data: {
                    class_id: classId,
                    role: role,
                    status: status,
                    keyword: keyword
                },
                dataType: 'json',
                success: function(response) {
                    console.log("AJAX response:", response);
                    
                    if (response.success) {
                        let html = '';
                        
                        if (response.data && response.data.length > 0) {
                            response.data.forEach((student, index) => {
                                let roleClass = student.role === 'Chủ nhiệm' ? 'badge-primary' : 'badge-secondary';
                                let statusClass = getBadgeClass(student.project_status);
                                
                                html += `<tr>
                                    <td>${index + 1}</td>
                                    <td>${student.SV_MASV}</td>
                                    <td>${student.full_name}</td>
                                    <td><span class="badge ${roleClass}">${student.role}</span></td>
                                    <td>${student.project_name}</td>
                                    <td><span class="badge ${statusClass}">${student.project_status}</span></td>
                                    <td>${student.advisor_name}</td>
                                </tr>`;
                            });
                        } else {
                            html = '<tr><td colspan="7" class="text-center">Không tìm thấy sinh viên nào tham gia đề tài trong lớp này</td></tr>';
                        }
                        
                        $('#studentTableBody').html(html);
                        $('#displayedCount').text(response.data ? response.data.length : 0);
                        $('#totalCount').text(response.count);
                        
                        // Cập nhật thông tin tổng quan
                        $('#modalStudentCount').text(response.count);
                    } else {
                        $('#studentTableBody').html('<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-circle mr-2"></i>Đã xảy ra lỗi khi tải dữ liệu: ' + response.message + '</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    $('#studentTableBody').html('<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-circle mr-2"></i>Đã xảy ra lỗi kết nối: ' + error + '</td></tr>');
                }
            });
        }

        // Hàm lấy class cho trạng thái đề tài
        function getBadgeClass(status) {
            switch(status) {
                case 'Đã hoàn thành':
                    return 'badge-success';
                case 'Đang thực hiện':
                    return 'badge-info';
                case 'Chờ duyệt':
                    return 'badge-warning';
                case 'Đã hủy':
                    return 'badge-danger';
                case 'Tạm dừng':
                    return 'badge-secondary';
                default:
                    return 'badge-secondary';
            }
        }

        // Hàm định dạng ngày
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
    </script>

    <script>
    $(document).ready(function() {
        // Xử lý sự kiện chọn khoa
        $('#classDepartmentFilter').on('change', function() {
            const deptId = $(this).val();
            
            // Reset khóa học và disable nút xem
            $('#classYearFilter').prop('disabled', true).html('<option value="">-- Vui lòng chọn khoa trước --</option>');
            $('#loadClassesBtn').prop('disabled', true);
            
            // Hiển thị thông báo mặc định
            $('#classListContainer').html('<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i> Vui lòng chọn khoa và khóa học để xem danh sách lớp</div>');
            
            if (deptId) {
                // Hiển thị loading
                $('#classYearFilter').html('<option value="">Đang tải khóa học...</option>');
                
                // Gọi AJAX để lấy danh sách khóa học theo khoa
                $.ajax({
                    url: 'get_class_years.php',
                    type: 'GET',
                    data: { dept_id: deptId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.years.length > 0) {
                            let options = '<option value="">-- Chọn khóa học --</option>';
                            response.years.forEach(year => {
                                options += `<option value="${year}">${year}</option>`;
                            });
                            $('#classYearFilter').html(options).prop('disabled', false);
                        } else {
                            $('#classYearFilter').html('<option value="">Không có khóa học nào</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        $('#classYearFilter').html('<option value="">Lỗi tải khóa học</option>');
                    }
                });
            }
        });
        
        // Xử lý sự kiện chọn khóa học
        $('#classYearFilter').on('change', function() {
            // Enable/disable nút xem dựa trên việc đã chọn khóa học chưa
            $('#loadClassesBtn').prop('disabled', !$(this).val());
        });
        
        // Xử lý sự kiện nhấn nút xem danh sách lớp
        $('#loadClassesBtn').on('click', function() {
            const deptId = $('#classDepartmentFilter').val();
            const year = $('#classYearFilter').val();
            
            if (deptId && year) {
                // Hiển thị loading
                $('#classListContainer').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Đang tải danh sách lớp...</p></div>');
                
                // Gọi AJAX để lấy danh sách lớp theo khoa và khóa học
                $.ajax({
                    url: 'get_department_classes.php',
                    type: 'GET',
                    data: {
                        dept_id: deptId,
                        year: year
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (response.classes.length > 0) {
                                // Lấy template bảng và hiển thị
                                const tableTemplate = $('#classTableTemplate').html();
                                $('#classListContainer').html(tableTemplate);
                                
                                // Thêm dữ liệu vào bảng
                                let tableRows = '';
                                let totalStudents = 0;
                                
                                // Tính tổng số sinh viên
                                response.classes.forEach(cls => {
                                    totalStudents += parseInt(cls.student_count);
                                });
                                
                                // Tạo các hàng dữ liệu
                                response.classes.forEach((cls, index) => {
                                    const percentage = (totalStudents > 0) ? ((cls.student_count / totalStudents) * 100).toFixed(2) : 0;
                                    
                                    tableRows += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${cls.class_id}</td>
                                        <td><a href="javascript:void(0)" class="class-link" data-class-id="${cls.class_id}" data-class-name="${cls.class_name}">${cls.class_name}</a></td>
                                        <td>${cls.class_year}</td>
                                        <td>${cls.department_name}</td>
                                        <td>${cls.student_count}</td>
                                        <td>${percentage}%</td>
                                    </tr>`;
                                });
                                
                                // Thêm hàng tổng
                                tableRows += `
                                <tr class="table-active font-weight-bold">
                                    <td colspan="5" class="text-center">Tổng cộng</td>
                                    <td>${totalStudents}</td>
                                    <td>100%</td>
                                </tr>`;
                                
                                $('#classTableBody').html(tableRows);
                            } else {
                                $('#classListContainer').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i> Không tìm thấy lớp nào cho khoa và khóa học đã chọn</div>');
                            }
                        } else {
                            $('#classListContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i> Đã xảy ra lỗi: ' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        $('#classListContainer').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i> Lỗi kết nối server: ' + error + '</div>');
                    }
                });
            }
        });
    });
    </script>
</body>

</html>