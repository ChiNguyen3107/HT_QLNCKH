<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\researcher_details.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/database.php';

// Kiểm tra tham số
$role = isset($_GET['role']) ? $_GET['role'] : 'teacher';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    // Redirect về trang danh sách nếu không có ID
    header('Location: manage_researchers.php');
    exit;
}

// Lấy thông tin nhà nghiên cứu
if ($role === 'teacher') {
    $sql = "SELECT gv.*, k.DV_TENDV, 
            (SELECT COUNT(*) FROM de_tai_nghien_cuu dt WHERE dt.GV_MAGV = gv.GV_MAGV) as total_projects,
            (SELECT COUNT(*) FROM de_tai_nghien_cuu dt WHERE dt.GV_MAGV = gv.GV_MAGV AND dt.DT_TRANGTHAI = 'Đã hoàn thành') as completed_projects
            FROM giang_vien gv 
            LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
            WHERE gv.GV_MAGV = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
} else {
    $sql = "SELECT sv.*, l.LOP_TEN, k.DV_TENDV, 
            (SELECT COUNT(*) FROM chi_tiet_tham_gia ct WHERE ct.SV_MASV = sv.SV_MASV) as total_projects,
            (SELECT COUNT(*) FROM chi_tiet_tham_gia ct JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT 
             WHERE ct.SV_MASV = sv.SV_MASV AND dt.DT_TRANGTHAI = 'Đã hoàn thành') as completed_projects
            FROM sinh_vien sv 
            LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
            LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
            WHERE sv.SV_MASV = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Không tìm thấy thông tin
    header('Location: manage_researchers.php');
    exit;
}

$researcher = $result->fetch_assoc();

// Lấy 5 đề tài gần đây nhất
if ($role === 'teacher') {
    $projects_sql = "SELECT dt.*, ldt.LDT_TENLOAI
                    FROM de_tai_nghien_cuu dt
                    LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    WHERE dt.GV_MAGV = ?
                    ORDER BY dt.DT_MADT DESC  /* Thay DT_NGAYTAO bằng DT_MADT */
                    LIMIT 5";
    
    $projects_stmt = $conn->prepare($projects_sql);
    if ($projects_stmt === false) {
        die("Lỗi truy vấn: " . $conn->error);  /* Thêm xử lý lỗi */
    }
    $projects_stmt->bind_param("s", $id);
} else {
    $projects_sql = "SELECT dt.*, ldt.LDT_TENLOAI, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN
                    FROM chi_tiet_tham_gia ct
                    JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                    LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                    WHERE ct.SV_MASV = ?
                    ORDER BY dt.DT_MADT DESC  /* Thay DT_NGAYTAO bằng DT_MADT */
                    LIMIT 5";
    
    $projects_stmt = $conn->prepare($projects_sql);
    if ($projects_stmt === false) {
        die("Lỗi truy vấn: " . $conn->error);  /* Thêm xử lý lỗi */
    }
    $projects_stmt->bind_param("s", $id);
}

$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();
$recent_projects = [];

while ($project = $projects_result->fetch_assoc()) {
    $recent_projects[] = $project;
}

// Thống kê theo trạng thái
if ($role === 'teacher') {
    $status_sql = "SELECT dt.DT_TRANGTHAI, COUNT(*) as count
                  FROM de_tai_nghien_cuu dt
                  WHERE dt.GV_MAGV = ?
                  GROUP BY dt.DT_TRANGTHAI";
    
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("s", $id);
} else {
    $status_sql = "SELECT dt.DT_TRANGTHAI, COUNT(*) as count
                  FROM chi_tiet_tham_gia ct
                  JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                  WHERE ct.SV_MASV = ?
                  GROUP BY dt.DT_TRANGTHAI";
    
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("s", $id);
}

$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_stats = [];

while ($stat = $status_result->fetch_assoc()) {
    $status_stats[$stat['DT_TRANGTHAI']] = $stat['count'];
}

// Set page title
$page_title = ($role === 'teacher' ? "Giảng viên: " : "Sinh viên: ") . 
              ($role === 'teacher' ? $researcher['GV_HOGV'] . ' ' . $researcher['GV_TENGV'] : $researcher['SV_HOSV'] . ' ' . $researcher['SV_TENSV']);

// Define additional CSS for this page
$additional_css = '<style>
    /* Layout positioning */
    #content-wrapper {
        margin-left: 280px !important;
        width: calc(100% - 280px) !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    .container-fluid {
        padding-left: 15px !important;
        padding-right: 15px !important;
        max-width: none !important;
    }
    
    /* Profile image styling */
    .img-profile {
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    /* Card enhancements */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        border-bottom: none;
        padding: 20px;
    }
    
    /* Border left cards */
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
    
    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }
    
    /* Button improvements */
    .btn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .btn-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
    }
    
    /* Badge styling */
    .badge {
        padding: 8px 12px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Table styling */
    .table {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table th {
        background-color: #f8f9fc;
        border-top: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    .table-responsive {
        border-radius: 8px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .container-fluid {
            padding: 10px !important;
        }
    }
</style>';

// Include header
include '../../include/research_header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-circle me-3"></i>
            <?php echo $role === 'teacher' ? 'Thông tin giảng viên' : 'Thông tin sinh viên'; ?>
        </h1>
        <div>
            <a href="manage_projects.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Quay lại danh sách
            </a>
        </div>
    </div>
                <div class="row">
                    <div class="col-md-4">
                        <!-- Profile card -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <?php echo $role === 'teacher' ? 'Hồ sơ giảng viên' : 'Hồ sơ sinh viên'; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="img-profile rounded-circle bg-primary text-white" style="width: 120px; height: 120px; font-size: 50px; line-height: 120px; margin: 0 auto;">
                                        <?php 
                                        if ($role === 'teacher') {
                                            echo mb_substr($researcher['GV_TENGV'], 0, 1, 'UTF-8');
                                        } else {
                                            echo mb_substr($researcher['SV_TENSV'], 0, 1, 'UTF-8');
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <h4 class="text-center font-weight-bold">
                                    <?php 
                                    if ($role === 'teacher') {
                                        echo htmlspecialchars($researcher['GV_HOGV'] . ' ' . $researcher['GV_TENGV']);
                                    } else {
                                        echo htmlspecialchars($researcher['SV_HOSV'] . ' ' . $researcher['SV_TENSV']);
                                    }
                                    ?>
                                </h4>
                                
                                <div class="text-center mb-3">
                                    <span class="badge badge-<?php echo $role === 'teacher' ? 'primary' : 'success'; ?> px-3 py-2">
                                        <?php echo $role === 'teacher' ? 'Giảng viên' : 'Sinh viên'; ?>
                                    </span>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <h6 class="font-weight-bold text-primary mb-1">Mã số:</h6>
                                            <p><?php echo htmlspecialchars($role === 'teacher' ? $researcher['GV_MAGV'] : $researcher['SV_MASV']); ?></p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6 class="font-weight-bold text-primary mb-1">Email:</h6>
                                            <p><?php echo htmlspecialchars($role === 'teacher' ? $researcher['GV_EMAIL'] : $researcher['SV_EMAIL']); ?></p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6 class="font-weight-bold text-primary mb-1">Đơn vị:</h6>
                                            <p><?php echo htmlspecialchars($researcher['DV_TENDV']); ?></p>
                                        </div>
                                        
                                        <?php if ($role === 'student'): ?>
                                        <div class="mb-3">
                                            <h6 class="font-weight-bold text-primary mb-1">Lớp:</h6>
                                            <p><?php echo htmlspecialchars($researcher['LOP_TEN']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($role === 'teacher' && !empty($researcher['GV_HOCHAM'])): ?>
                                        <div class="mb-3">
                                            <h6 class="font-weight-bold text-primary mb-1">Học hàm:</h6>
                                            <p><?php echo htmlspecialchars($researcher['GV_HOCHAM']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($role === 'teacher' && !empty($researcher['GV_HOCVI'])): ?>
                                        <div class="mb-3">
                                            <h6 class="font-weight-bold text-primary mb-1">Học vị:</h6>
                                            <p><?php echo htmlspecialchars($researcher['GV_HOCVI']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact info card -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Thông tin liên hệ</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($role === 'teacher' && !empty($researcher['GV_SDT'])): ?>
                                <div class="mb-3">
                                    <h6 class="font-weight-bold text-primary mb-1">Điện thoại:</h6>
                                    <p><?php echo htmlspecialchars($researcher['GV_SDT']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($role === 'student' && !empty($researcher['SV_SDT'])): ?>
                                <div class="mb-3">
                                    <h6 class="font-weight-bold text-primary mb-1">Điện thoại:</h6>
                                    <p><?php echo htmlspecialchars($researcher['SV_SDT']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <h6 class="font-weight-bold text-primary mb-1">Email:</h6>
                                    <p><?php echo htmlspecialchars($role === 'teacher' ? $researcher['GV_EMAIL'] : $researcher['SV_EMAIL']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Research statistics -->
                        <div class="row">
                            <div class="col-xl-6 col-md-12 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Tổng số đề tài
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo $researcher['total_projects']; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-folder fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-6 col-md-12 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Đề tài đã hoàn thành
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo $researcher['completed_projects']; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Project status distribution -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Phân bố đề tài theo trạng thái</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-bar">
                                    <canvas id="statusChart" style="max-height: 200px;"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent projects -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Đề tài gần đây</h6>
                                <div class="dropdown no-arrow">
                                    <a href="researcher_projects.php?role=<?php echo $role; ?>&id=<?php echo htmlspecialchars($id); ?>" class="btn btn-sm btn-primary">
                                        Xem tất cả
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (count($recent_projects) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Mã đề tài</th>
                                                <th>Tên đề tài</th>
                                                <th>Loại</th>
                                                <th>Trạng thái</th>
                                                <?php if ($role === 'student'): ?>
                                                <th>Giảng viên</th>
                                                <?php endif; ?>
                                                <th>Tác vụ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_projects as $project): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($project['DT_MADT']); ?></td>
                                                <td><?php echo htmlspecialchars($project['DT_TENDT']); ?></td>
                                                <td><?php echo htmlspecialchars($project['LDT_TENLOAI'] ?? 'Không xác định'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        if ($project['DT_TRANGTHAI'] == 'Đã hoàn thành') echo 'success';
                                                        elseif ($project['DT_TRANGTHAI'] == 'Đang tiến hành') echo 'primary';
                                                        elseif ($project['DT_TRANGTHAI'] == 'Chờ duyệt') echo 'warning';
                                                        else echo 'danger';
                                                    ?>">
                                                        <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                                    </span>
                                                </td>
                                                <?php if ($role === 'student'): ?>
                                                <td><?php echo htmlspecialchars($project['GV_HOTEN'] ?? 'Không xác định'); ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <a href="view_project.php?id=<?php echo htmlspecialchars($project['DT_MADT']); ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-folder-open fa-3x text-gray-300 mb-3"></i>
                                    <p>Chưa có đề tài nào được ghi nhận.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include '../../include/research_footer.php'; ?>

<!-- Chart.js and custom scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Status chart data
    const statusData = {
        labels: <?php echo json_encode(array_keys($status_stats)); ?>,
        datasets: [{
            label: "Số lượng đề tài",
            backgroundColor: ["#4e73df", "#1cc88a", "#f6c23e", "#e74a3b", "#36b9cc"],
            borderColor: ["#4e73df", "#1cc88a", "#f6c23e", "#e74a3b", "#36b9cc"],
            data: <?php echo json_encode(array_values($status_stats)); ?>,
        }],
    };
    
    // Status chart
    var statusChartCanvas = document.getElementById("statusChart");
    if (statusChartCanvas) {
        new Chart(statusChartCanvas, {
            type: "bar",
            data: statusData,
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    displayColors: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return tooltipItem.yLabel + " đề tài";
                        }
                    }
                }
            }
        });
    }
});
</script>
