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

if ($selected_year > 0) {
    $status_query .= " WHERE DT_MADT IN (
                        SELECT DT_MADT FROM hop_dong 
                        WHERE YEAR(HD_NGAYBD) = $selected_year
                    )";
}

$status_query .= " GROUP BY DT_TRANGTHAI";
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

// Code khoa và loại đề tài giữ nguyên...

// Thống kê đề tài theo khoa
$dept_query = "SELECT 
                k.DV_TENDV AS department,
                COUNT(dt.DT_MADT) AS count
              FROM de_tai_nghien_cuu dt
              JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
              JOIN khoa k ON gv.DV_MADV = k.DV_MADV";

if ($selected_year > 0) {
    $dept_query .= " WHERE dt.DT_MADT IN (
                    SELECT DT_MADT FROM hop_dong 
                    WHERE YEAR(HD_NGAYBD) = $selected_year
                )";
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
    $type_query .= " WHERE dt.DT_MADT IN (
                    SELECT DT_MADT FROM hop_dong 
                    WHERE YEAR(HD_NGAYBD) = $selected_year
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

// Top 5 giảng viên code giữ nguyên...
$teacher_query = "SELECT 
                    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS teacher_name,
                    COUNT(dt.DT_MADT) AS project_count
                  FROM giang_vien gv
                  JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV";

if ($selected_year > 0) {
    $teacher_query .= " WHERE dt.DT_MADT IN (
                        SELECT DT_MADT FROM hop_dong 
                        WHERE YEAR(HD_NGAYBD) = $selected_year
                    )";
}

if (!empty($selected_department)) {
    $teacher_query .= ($selected_year > 0) ? " AND" : " WHERE";
    $teacher_query .= " gv.DV_MADV = '" . $conn->real_escape_string($selected_department) . "'";
}

$teacher_query .= " GROUP BY gv.GV_MAGV
                   ORDER BY project_count DESC
                   LIMIT 5";

$teacher_result = $conn->query($teacher_query);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê | Admin</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Tham chiếu đến file CSS riêng -->
    <link href="/NLNganh/assets/css/admin/reports.css" rel="stylesheet">
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
                    $total_projects_query = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu dt 
                                          WHERE dt.DT_MADT IN (
                                            SELECT DT_MADT FROM hop_dong 
                                            WHERE YEAR(HD_NGAYBD) = $selected_year
                                          )";
                }
                $total_result = $conn->query($total_projects_query);
                $total_projects = ($total_result) ? $total_result->fetch_assoc()['total'] : 0;
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
                    $completed_query = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu dt 
                                      WHERE dt.DT_TRANGTHAI = 'Đã hoàn thành'
                                      AND dt.DT_MADT IN (
                                        SELECT DT_MADT FROM hop_dong 
                                        WHERE YEAR(HD_NGAYBD) = $selected_year
                                      )";
                }
                $completed_result = $conn->query($completed_query);
                $completed_projects = ($completed_result) ? $completed_result->fetch_assoc()['total'] : 0;
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
                    $ongoing_query = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu dt 
                                    WHERE dt.DT_TRANGTHAI = 'Đang thực hiện'
                                    AND dt.DT_MADT IN (
                                      SELECT DT_MADT FROM hop_dong 
                                      WHERE YEAR(HD_NGAYBD) = $selected_year
                                    )";
                }
                $ongoing_result = $conn->query($ongoing_query);
                $ongoing_projects = ($ongoing_result) ? $ongoing_result->fetch_assoc()['total'] : 0;
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
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    
    <!-- Tham chiếu đến file JS riêng -->
    <script src="/NLNganh/assets/js/admin/reports.js"></script>

    <!-- Script chuyển dữ liệu PHP sang JavaScript -->
    <script>
        // Gọi hàm khởi tạo biểu đồ với dữ liệu từ PHP
        document.addEventListener('DOMContentLoaded', function() {
            // Chuyển dữ liệu PHP sang JavaScript
            initCharts(
                <?php echo json_encode($status_data); ?>,
                <?php echo json_encode($dept_data); ?>,
                <?php echo json_encode($type_data); ?>
            );
        });
    </script>
</body>

</html>