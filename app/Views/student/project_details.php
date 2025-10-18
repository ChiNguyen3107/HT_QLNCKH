<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\project_details.php

// Include session check and necessary files
include '../../include/session.php';
include '../../include/connect.php';

// Check if user is logged in
checkStudentRole();

// Get project ID from URL
$project_id = isset($_GET['id']) ? $_GET['id'] : 0;
$project_id = $conn->real_escape_string($project_id);

// Validate project ID
if (empty($project_id)) {
    // Redirect to browse page if no ID provided
    header('Location: browse_projects.php');
    exit;
}

// Fetch project details with LEFT JOINs to handle missing data
$query = "SELECT dt.*, 
          CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as supervisor_name,
          gv.GV_EMAIL as supervisor_email,
          gv.GV_SDT as supervisor_phone,
          IFNULL(ldt.LDT_TENLOAI, 'Chưa phân loại') as project_type_name,
          k.DV_TENDV as department_name
          FROM de_tai_nghien_cuu dt
          LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
          LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
          LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
          WHERE dt.DT_MADT = '$project_id'";

$result = $conn->query($query);

// Check if project exists
if ($result && $result->num_rows > 0) {
    $project = $result->fetch_assoc();
} else {
    // Project not found
    header('Location: browse_projects.php');
    exit;
}

// Fetch students participating in the project
$students_query = "SELECT ct.*, 
                  CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as student_name,
                  sv.SV_EMAIL as student_email,
                  l.LOP_TEN as class_name
                  FROM chi_tiet_tham_gia ct
                  JOIN sinh_vien sv ON ct.SV_MASV = sv.SV_MASV
                  JOIN lop l ON sv.LOP_MA = l.LOP_MA
                  WHERE ct.DT_MADT = '$project_id'
                  ORDER BY ct.CTTG_VAITRO DESC, student_name ASC";
                  
$students_result = $conn->query($students_query);
$students = [];

if ($students_result && $students_result->num_rows > 0) {
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch documents related to the project
$docs_query = "SELECT * FROM tai_lieu
              WHERE DT_MADT = '$project_id'
              ORDER BY TL_NGAYTAO DESC";
              
$docs_result = $conn->query($docs_query);
$documents = [];

if ($docs_result && $docs_result->num_rows > 0) {
    while ($row = $docs_result->fetch_assoc()) {
        $documents[] = $row;
    }
}

// Helper function to get the badge class for different statuses
function getStatusBadgeClass($status) {
    switch ($status) {
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

// Helper function to format date
function formatDate($dateString) {
    if (empty($dateString) || $dateString == '0000-00-00') {
        return 'Chưa xác định';
    }
    
    $date = new DateTime($dateString);
    return $date->format('d/m/Y');
}

// Helper function to get file icon based on extension
function getFileIcon($filename) {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    
    switch (strtolower($extension)) {
        case 'pdf':
            return 'fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'fa-file-excel';
        case 'ppt':
        case 'pptx':
            return 'fa-file-powerpoint';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fa-file-image';
        case 'zip':
        case 'rar':
            return 'fa-file-archive';
        default:
            return 'fa-file';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['DT_TENDT']); ?> | Chi tiết đề tài</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="/NLNganh/assets/css/styles.css">
    <style>
        .project-header {
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .project-meta {
            margin-bottom: 1.5rem;
        }
        
        .meta-item {
            margin-bottom: 0.5rem;
        }
        
        .meta-label {
            font-weight: 600;
            color: #4e73df;
        }
        
        .badge {
            font-size: 85%;
            padding: 0.4em 0.6em;
        }
        
        .project-description {
            background-color: #f8f9fc;
            padding: 1rem;
            border-radius: 0.35rem;
            margin-bottom: 1.5rem;
        }
        
        .participants-card, .documents-card {
            margin-bottom: 1.5rem;
        }
        
        .document-item {
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 0;
        }
        
        .document-item:last-child {
            border-bottom: none;
        }
        
        .document-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: #4e73df;
        }
        
        .back-button {
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="student_dashboard.php">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="browse_projects.php">Tìm kiếm đề tài</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($project['DT_TENDT']); ?></li>
                </ol>
            </nav>
            
            <!-- Back button -->
            <div class="back-button">
                <a href="browse_projects.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách đề tài
                </a>
            </div>
            
            <!-- Project header -->
            <div class="project-header">
                <h1 class="h3 mb-2"><?php echo htmlspecialchars($project['DT_TENDT']); ?></h1>
                <div>
                    <span class="badge <?php echo getStatusBadgeClass($project['DT_TRANGTHAI']); ?> mr-2">
                        <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                    </span>
                    <span class="badge badge-info">
                        <?php echo htmlspecialchars($project['project_type_name']); ?>
                    </span>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- Project description -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold text-primary">Mô tả đề tài</h5>
                        </div>
                        <div class="card-body">
                            <div class="project-description">
                                <?php if (!empty($project['DT_MOTA'])): ?>
                                    <?php echo nl2br(htmlspecialchars($project['DT_MOTA'])); ?>
                                <?php else: ?>
                                    <p class="text-muted">Không có thông tin mô tả</p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($project['DT_MUCTIEU'])): ?>
                                <h6 class="font-weight-bold">Mục tiêu nghiên cứu:</h6>
                                <p><?php echo nl2br(htmlspecialchars($project['DT_MUCTIEU'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($project['DT_YEUCAU'])): ?>
                                <h6 class="font-weight-bold">Yêu cầu:</h6>
                                <p><?php echo nl2br(htmlspecialchars($project['DT_YEUCAU'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($project['DT_KETQUADUKIEN'])): ?>
                                <h6 class="font-weight-bold">Kết quả dự kiến:</h6>
                                <p><?php echo nl2br(htmlspecialchars($project['DT_KETQUADUKIEN'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Students participating -->
                    <div class="card participants-card">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold text-primary">Sinh viên tham gia</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($students) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Họ và tên</th>
                                                <th>MSSV</th>
                                                <th>Lớp</th>
                                                <th>Vai trò</th>
                                                <th>Ngày tham gia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($student['student_name']); ?>
                                                        <?php if (!empty($student['student_email'])): ?>
                                                            <a href="mailto:<?php echo $student['student_email']; ?>" data-toggle="tooltip" title="Gửi email">
                                                                <i class="fas fa-envelope-square text-primary ml-1"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $student['SV_MASV']; ?></td>
                                                    <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ($student['CTTG_VAITRO'] == 'Chủ nhiệm') ? 'badge-primary' : 'badge-secondary'; ?>">
                                                            <?php echo htmlspecialchars($student['CTTG_VAITRO']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($student['CTTG_NGAYTHAMGIA']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Chưa có sinh viên tham gia đề tài này</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Documents -->
                    <?php if (count($documents) > 0): ?>
                    <div class="card documents-card">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold text-primary">Tài liệu</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="document-item">
                                        <div class="d-flex align-items-center">
                                            <div class="document-icon">
                                                <i class="fas <?php echo getFileIcon($doc['TL_TENFILE']); ?>"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($doc['TL_TIEUDE']); ?></h6>
                                                <p class="small text-muted mb-0">
                                                    <?php echo htmlspecialchars($doc['TL_TENFILE']); ?> - 
                                                    Ngày tạo: <?php echo formatDate($doc['TL_NGAYTAO']); ?>
                                                </p>
                                            </div>
                                            <div class="ml-auto">
                                                <a href="/NLNganh/uploads/documents/<?php echo $doc['TL_TENFILE']; ?>" class="btn btn-sm btn-outline-primary" download>
                                                    <i class="fas fa-download mr-1"></i> Tải xuống
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <!-- Project info card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold text-primary">Thông tin đề tài</h5>
                        </div>
                        <div class="card-body">
                            <div class="project-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Mã đề tài:</span>
                                    <span><?php echo $project['DT_MADT']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Ngày bắt đầu:</span>
                                    <span><?php echo isset($project['DT_NGAYBD']) ? formatDate($project['DT_NGAYBD']) : 'Chưa xác định'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Ngày kết thúc:</span>
                                    <span><?php echo isset($project['DT_NGAYKT']) ? formatDate($project['DT_NGAYKT']) : 'Chưa xác định'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Loại đề tài:</span>
                                    <span><?php echo htmlspecialchars($project['project_type_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Trạng thái:</span>
                                    <span class="badge <?php echo getStatusBadgeClass($project['DT_TRANGTHAI']); ?>">
                                        <?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?>
                                    </span>
                                </div>
                                <?php if (!empty($project['DT_KINHPHI'])): ?>
                                <div class="meta-item">
                                    <span class="meta-label">Kinh phí:</span>
                                    <span><?php echo number_format($project['DT_KINHPHI'], 0, ',', '.'); ?> VNĐ</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Supervisor info card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold text-primary">Giảng viên hướng dẫn</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($project['supervisor_name'])): ?>
                                <h5 class="card-title"><?php echo htmlspecialchars($project['supervisor_name']); ?></h5>
                                
                                <?php if (!empty($project['department_name'])): ?>
                                <p class="card-text">
                                    <i class="fas fa-university mr-2"></i> 
                                    <?php echo htmlspecialchars($project['department_name']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($project['supervisor_email'])): ?>
                                <p class="card-text">
                                    <i class="fas fa-envelope mr-2"></i>
                                    <a href="mailto:<?php echo $project['supervisor_email']; ?>">
                                        <?php echo htmlspecialchars($project['supervisor_email']); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($project['supervisor_phone'])): ?>
                                <p class="card-text">
                                    <i class="fas fa-phone mr-2"></i>
                                    <a href="tel:<?php echo $project['supervisor_phone']; ?>">
                                        <?php echo htmlspecialchars($project['supervisor_phone']); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="card-text text-muted">Chưa có thông tin giảng viên hướng dẫn</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Kích hoạt tooltip
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>