<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\register_project_form.php
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';
require_once '../../core/Helper.php';

// Lấy thông tin sinh viên
$student_info = [];
$student_id = $_SESSION['user_id'] ?? '';

if (!empty($student_id)) {
    $student_query = "SELECT sv.SV_MASV, sv.SV_HOSV, sv.SV_TENSV, sv.SV_EMAIL, sv.SV_SDT, sv.SV_NGAYSINH, lop.LOP_TEN, 
                     kh.KH_NAM as KHOA
                     FROM sinh_vien sv 
                     LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
                     LEFT JOIN khoa_hoc kh ON lop.KH_NAM = kh.KH_NAM
                     WHERE sv.SV_MASV = ?";

    $stmt = $conn->prepare($student_query);
    if ($stmt) {
        $stmt->bind_param("s", $student_id);
        if ($stmt->execute()) {
            $student_result = $stmt->get_result();
            if ($student_result && $student_result->num_rows > 0) {
                $student_info = $student_result->fetch_assoc();
            }
        }
    }
}

// Khởi tạo các biến mặc định nếu không tìm thấy dữ liệu
if (empty($student_info)) {
    $student_info = [
        'SV_MASV' => $student_id,
        'SV_HOSV' => '',
        'SV_TENSV' => '',
        'SV_EMAIL' => '',
        'SV_SDT' => '',
        'SV_NGAYSINH' => '',
        'LOP_TEN' => '',
        'KHOA' => ''
    ];
}

// Lấy danh sách giảng viên cho đề tài mới
$lecturers = [];
$lecturers_query = "SELECT gv.GV_MAGV, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
                   gv.GV_CHUYENMON, gv.DV_MADV, k.DV_TENDV 
                   FROM giang_vien gv 
                   LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
                   ORDER BY gv.GV_TENGV";

$lecturers_result = $conn->query($lecturers_query);
if ($lecturers_result) {
    while ($row = $lecturers_result->fetch_assoc()) {
        $lecturers[] = $row;
    }
}

// Lấy danh sách khoa
$faculties = [];
$faculties_query = "SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV";
$faculties_result = $conn->query($faculties_query);
if ($faculties_result) {
    while ($row = $faculties_result->fetch_assoc()) {
        $faculties[] = $row;
    }
}

// Lấy danh sách loại đề tài cho đề tài mới
$categories = [];
$categories_query = "SELECT LDT_MA, LDT_TENLOAI FROM loai_de_tai ORDER BY LDT_TENLOAI";
$categories_result = $conn->query($categories_query);
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Lấy danh sách lĩnh vực nghiên cứu
$research_fields = [];
$fields_query = "SELECT * FROM linh_vuc_nghien_cuu ORDER BY LVNC_TEN";
$fields_result = $conn->query($fields_query);
if ($fields_result) {
    while ($row = $fields_result->fetch_assoc()) {
        $research_fields[] = $row;
    }
}

// Lấy danh sách lĩnh vực ưu tiên
$priority_fields = [];
$priority_fields_query = "SELECT * FROM linh_vuc_uu_tien ORDER BY LVUT_TEN";
$priority_fields_result = $conn->query($priority_fields_query);
if ($priority_fields_result) {
    while ($row = $priority_fields_result->fetch_assoc()) {
        $priority_fields[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký đề tài mới | Sinh viên</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/main.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/student/dashboard.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fc;
        }

        .content-wrapper {
            padding: 20px;
            transition: all 0.3s;
        }

        .form-section {
            display: none;
            margin-bottom: 30px;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        .section-title {
            background: linear-gradient(to right, #f5f7fa, #eef1f5);
            padding: 12px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #4e73df;
            font-size: 18px;
            font-weight: 500;
            border-radius: 0 5px 5px 0;
            color: #2e59d9;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem 1.25rem;
        }

        .card-header.bg-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .progress-indicator {
            display: flex;
            margin-bottom: 30px;
            border-radius: 10px;
            padding: 10px;
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
        }

        .progress-step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }

        .progress-step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 25px;
            right: -10px;
            width: 20px;
            height: 3px;
            background-color: #dee2e6;
            z-index: 1;
        }

        .progress-step.completed:not(:last-child):after {
            background-color: #1cc88a;
        }

        .step-number {
            display: inline-block;
            width: 36px;
            height: 36px;
            line-height: 36px;
            border-radius: 50%;
            background-color: #e9ecef;
            margin-bottom: 8px;
            font-weight: 600;
            z-index: 2;
            position: relative;
            transition: all 0.3s ease;
        }

        .progress-step.active .step-number {
            background-color: #4e73df;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 0 10px rgba(78, 115, 223, 0.5);
        }

        .progress-step.completed .step-number {
            background-color: #1cc88a;
            color: white;
        }

        .step-title {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .progress-step.active .step-title {
            color: #4e73df;
            font-weight: 600;
        }

        .progress-step.completed .step-title {
            color: #1cc88a;
        }

        .member-card {
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            background-color: white;
            transition: all 0.3s;
        }

        .member-card:hover {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transform: translateY(-2px);
        }

        .member-card .remove-member {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            color: #e74a3b;
            background-color: #f8f9fc;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            text-align: center;
            line-height: 25px;
            transition: all 0.3s;
        }

        .member-card .remove-member:hover {
            background-color: #e74a3b;
            color: white;
            transform: rotate(90deg);
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #d1d3e2;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            border-color: #bac8f3;
        }

        .form-control.is-invalid {
            background-image: none;
        }

        .required-field::after {
            content: " *";
            color: #e74a3b;
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background-color: #717384;
            border-color: #6b6d7d;
        }

        .file-item {
            border: 1px solid #e3e6f0;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .file-item:hover {
            background-color: #f8f9fc;
            transform: translateY(-2px);
        }

        .file-icon {
            color: #4e73df;
            margin-right: 10px;
        }

        .custom-file-label {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .alert {
            border-radius: 10px;
        }

        .reload-btn {
            background-color: #36b9cc;
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            line-height: 32px;
            padding: 0;
            margin-left: 10px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .reload-btn:hover {
            background-color: #2c9faf;
            transform: rotate(180deg);
        }

        .tooltip-info {
            color: #4e73df;
            margin-left: 5px;
            cursor: pointer;
        }

        /* Responsive for small screens */
        @media (max-width: 768px) {
            .progress-step .step-title {
                font-size: 12px;
            }

            .step-number {
                width: 30px;
                height: 30px;
                line-height: 30px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>
    <div class="container-fluid content" style="margin-left:250px; transition:all 0.3s;">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-home mr-1"></i> Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Đăng ký đề tài mới</li>
            </ol>
        </nav>

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-signature mr-2"></i>Đăng ký đề tài nghiên cứu mới
            </h1>
        </div>

        <!-- Thông báo -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle mr-1"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (empty($lecturers)): ?>
            <div class="alert alert-warning animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle mr-1"></i> Không thể tải danh sách giảng viên. Vui lòng
                liên hệ quản trị viên.
            </div>
        <?php endif; ?>

        <?php if (empty($faculties)): ?>
            <div class="alert alert-warning animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle mr-1"></i> Không thể tải danh sách khoa. Vui lòng liên hệ
                quản trị viên.
            </div>
        <?php endif; ?>

        <?php if (empty($categories)): ?>
            <div class="alert alert-warning animate__animated animate__fadeIn">
                <i class="fas fa-exclamation-triangle mr-1"></i> Không thể tải danh sách loại đề tài. Vui lòng
                liên hệ quản trị viên.
            </div>
        <?php endif; ?>

        <!-- Thông báo về file thuyết minh bắt buộc -->
        <div class="alert alert-info animate__animated animate__fadeIn">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Lưu ý quan trọng:</strong> Tất cả đề tài đăng ký <strong>bắt buộc phải có file thuyết minh</strong> 
            (định dạng PDF, DOC, DOCX, tối đa 5MB). File thuyết minh phải mô tả chi tiết về mục tiêu, nội dung, 
            phương pháp nghiên cứu và kế hoạch thực hiện đề tài.
        </div>

        <div class="card shadow mb-4 animate__animated animate__fadeIn">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-tasks mr-1"></i> Quy trình đăng ký đề tài
                </h6>
            </div>
            <div class="card-body">
                <div class="progress-indicator">
                    <div class="progress-step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-title">Thông tin đề tài</div>
                    </div>
                    <div class="progress-step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-title">Thành viên</div>
                    </div>
                    <div class="progress-step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-title">GVHD & Mô tả</div>
                    </div>
                    <div class="progress-step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-title">Xác nhận</div>
                    </div>
                </div>

                <form id="projectRegistrationForm" action="register_project_process.php" method="post"
                    enctype="multipart/form-data">
                    <?php echo Helper::csrfField('register_project'); ?>
                    <!-- Bước 1: Thông tin đề tài -->
                    <div class="form-section active" id="step1">
                        <div class="section-title">
                            <i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản về đề tài
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="projectTitle" class="required-field">Tên đề tài</label>
                                <input type="text" class="form-control" id="projectTitle"
                                    name="project_title" required maxlength="255"
                                    placeholder="Nhập tên đề tài nghiên cứu">
                                <div class="invalid-feedback">Vui lòng nhập tên đề tài</div>
                                <small class="text-muted">Tên đề tài nên ngắn gọn, súc tích và thể hiện rõ
                                    nội dung nghiên cứu</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priorityField" class="required-field">Lĩnh vực ưu tiên</label>
                                <select class="form-control" id="priorityField" name="priority_field"
                                    required>
                                    <option value="">-- Chọn lĩnh vực ưu tiên --</option>
                                    <?php if (!empty($priority_fields)): ?>
                                        <?php foreach ($priority_fields as $field): ?>
                                            <option value="<?php echo $field['LVUT_MA']; ?>">
                                                <?php echo htmlspecialchars($field['LVUT_TEN']); ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="CNTT">Công nghệ thông tin</option>
                                        <option value="KTCN">Kỹ thuật công nghệ</option>
                                        <option value="MT">Môi trường</option>
                                        <option value="YS">Y tế - Sức khỏe</option>
                                        <option value="KT">Kinh tế</option>
                                        <option value="GD">Giáo dục</option>
                                        <option value="XH">Xã hội - Nhân văn</option>
                                        <option value="other">Khác</option>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn lĩnh vực ưu tiên</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="researchField" class="required-field">Lĩnh vực nghiên
                                    cứu</label>
                                <select class="form-control" id="researchField" name="research_field"
                                    required>
                                    <option value="">-- Chọn lĩnh vực nghiên cứu --</option>
                                    <?php if (!empty($research_fields)): ?>
                                        <?php foreach ($research_fields as $field): ?>
                                            <option value="<?php echo $field['LVNC_MA']; ?>">
                                                <?php echo htmlspecialchars($field['LVNC_TEN']); ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="AI">Trí tuệ nhân tạo</option>
                                        <option value="MOBILE">Phát triển ứng dụng di động</option>
                                        <option value="WEB">Phát triển web</option>
                                        <option value="NETWORK">Mạng và an ninh mạng</option>
                                        <option value="AI">Trí tuệ nhân tạo</option>
                                        <option value="DATA">Khoa học dữ liệu</option>
                                        <option value="other">Khác</option>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn lĩnh vực nghiên cứu</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="researchType" class="required-field">Loại hình nghiên
                                    cứu</label>
                                <select class="form-control" id="researchType" name="research_type"
                                    required>
                                    <option value="">-- Chọn loại hình nghiên cứu --</option>
                                    <option value="Cơ bản">Cơ bản</option>
                                    <option value="Ứng dụng">Ứng dụng</option>
                                    <option value="Triển khai">Triển khai</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn loại hình nghiên cứu</div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1 tooltip-info" data-toggle="tooltip"
                                        title="Cơ bản: Nghiên cứu lý thuyết, Ứng dụng: Phát triển từ lý thuyết, Triển khai: Ứng dụng vào thực tế"></i>
                                    Chọn loại hình phù hợp với nội dung nghiên cứu của bạn
                                </small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="facultyId" class="required-field">Khoa chủ trì đề tài</label>
                                <select class="form-control" id="facultyId" name="faculty_id" required>
                                    <option value="">-- Chọn khoa --</option>
                                    <?php foreach ($faculties as $faculty): ?>
                                        <option value="<?php echo $faculty['DV_MADV']; ?>">
                                            <?php echo htmlspecialchars($faculty['DV_TENDV']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn khoa chủ trì</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="projectCategory" class="required-field">Loại đề tài</label>
                                <select class="form-control" id="projectCategory" name="project_category"
                                    required>
                                    <option value="">-- Chọn loại đề tài --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['LDT_MA']; ?>">
                                            <?php echo htmlspecialchars($category['LDT_TENLOAI']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn loại đề tài</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="memberCount" class="required-field">Số lượng thành viên (bao gồm
                                    chủ nhiệm)</label>
                                <select class="form-control" id="memberCount" name="member_count" required>
                                    <option value="3" selected>3 thành viên (1 chủ nhiệm + 2 thành viên)</option>
                                    <option value="4">4 thành viên (1 chủ nhiệm + 3 thành viên)</option>
                                    <option value="5">5 thành viên (1 chủ nhiệm + 4 thành viên)</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn số lượng thành viên</div>
                                <small class="text-muted">Mỗi đề tài có 1 chủ nhiệm và từ 2-4 thành viên tham gia</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="implementationTime" class="required-field">Thời gian thực
                                    hiện</label>
                                <select class="form-control" id="implementationTime" name="implementation_time" required>
                                    <option value="">Chọn thời gian thực hiện</option>
                                    <option value="3">3 tháng</option>
                                    <option value="6" selected>6 tháng</option>
                                    <option value="9">9 tháng</option>
                                    <option value="12">12 tháng</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn thời gian thực hiện</div>
                                <small class="text-muted">Thời gian thực hiện kể từ ngày đề tài được phê duyệt</small>
                            </div>
                        </div>

                        <div class="text-right mt-4">
                            <button type="button" class="btn btn-primary next-step" data-step="1">
                                Tiếp theo <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Bước 2: Thông tin thành viên -->
                    <div class="form-section" id="step2">
                        <div class="section-title">
                            <i class="fas fa-users mr-2"></i>Thông tin chủ nhiệm và thành viên tham gia
                        </div>

                        <!-- Thông tin chủ nhiệm đề tài -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-user-tie mr-1"></i> Chủ nhiệm đề tài
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="leaderName" class="required-field">Họ và tên</label>
                                        <input type="text" class="form-control" id="leaderName"
                                            name="leader_name"
                                            value="<?php echo htmlspecialchars($student_info['SV_HOSV'] . ' ' . $student_info['SV_TENSV']); ?>"
                                            readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="leaderStudentId" class="required-field">MSSV</label>
                                        <input type="text" class="form-control" id="leaderStudentId"
                                            name="leader_student_id"
                                            value="<?php echo htmlspecialchars($student_info['SV_MASV']); ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="leaderDob" class="required-field">Ngày sinh</label>
                                        <input type="date" class="form-control" id="leaderDob"
                                            name="leader_dob"
                                            value="<?php echo htmlspecialchars($student_info['SV_NGAYSINH'] ?? ''); ?>"
                                            required>
                                        <div class="invalid-feedback">Vui lòng chọn ngày sinh</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="leaderClass" class="required-field">Tên lớp</label>
                                        <input type="text" class="form-control" id="leaderClass"
                                            name="leader_class"
                                            value="<?php echo htmlspecialchars($student_info['LOP_TEN'] ?? ''); ?>"
                                            required>
                                        <div class="invalid-feedback">Vui lòng nhập tên lớp</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="leaderPhone" class="required-field">Số điện
                                            thoại</label>
                                        <input type="text" class="form-control" id="leaderPhone"
                                            name="leader_phone"
                                            value="<?php echo htmlspecialchars($student_info['SV_SDT'] ?? ''); ?>"
                                            required pattern="[0-9]{10}">
                                        <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ (10
                                            số)</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="leaderYearGroup" class="required-field">Khóa</label>
                                        <input type="text" class="form-control" id="leaderYearGroup"
                                            name="leader_year_group"
                                            value="<?php echo htmlspecialchars($student_info['KHOA'] ?? ''); ?>"
                                            required>
                                        <div class="invalid-feedback">Vui lòng nhập khóa học (VD: 47,
                                            48,...)</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="leaderEmail" class="required-field">Email</label>
                                        <input type="email" class="form-control" id="leaderEmail"
                                            name="leader_email"
                                            value="<?php echo htmlspecialchars($student_info['SV_EMAIL'] ?? ''); ?>"
                                            required>
                                        <div class="invalid-feedback">Vui lòng nhập địa chỉ email hợp lệ
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin thành viên -->
                        <div id="membersSection">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Lưu ý:</strong> Theo quy định, mỗi đề tài phải có 1 chủ nhiệm và từ 2-4 thành viên tham gia. Hệ thống sẽ kiểm tra và ngăn chặn việc thêm thành viên trùng lặp (cùng MSSV) trong danh sách.
                            </div>
                            
                            <h5 class="mb-3">
                                <i class="fas fa-users mr-2"></i>Thông tin thành viên tham gia
                                <span class="badge badge-primary ml-2">Số lượng: <span
                                        id="currentMemberCount">0</span>/<span
                                        id="maxMemberCount">0</span></span>
                            </h5>
                            <div id="membersList">
                                <!-- Thẻ thành viên sẽ được thêm vào đây bằng JavaScript -->
                            </div>

                            <button type="button" class="btn btn-success mb-4" id="addMemberBtn">
                                <i class="fas fa-user-plus mr-1"></i> Thêm thành viên
                            </button>
                        </div>

                        <div class="text-right mt-4">
                            <button type="button" class="btn btn-secondary prev-step mr-2" data-step="2">
                                <i class="fas fa-arrow-left mr-1"></i> Quay lại
                            </button>
                            <button type="button" class="btn btn-primary next-step" data-step="2">
                                Tiếp theo <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Bước 3: Thông tin GVHD và Mô tả -->
                    <div class="form-section" id="step3">
                        <div class="section-title">
                            <i class="fas fa-user-tie mr-2"></i>Thông tin giảng viên hướng dẫn
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="facultyFilter" class="required-field">Chọn khoa</label>
                                        <select class="form-control" id="facultyFilter" required>
                                            <option value="">-- Chọn khoa --</option>
                                            <?php foreach ($faculties as $faculty): ?>
                                                <option value="<?php echo $faculty['DV_MADV']; ?>">
                                                    <?php echo htmlspecialchars($faculty['DV_TENDV']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Vui lòng chọn khoa trước</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="advisorId" class="required-field">Giảng viên hướng dẫn</label>
                                        <div class="input-group">
                                            <select class="form-control" id="advisorId" name="advisor_id"
                                                required disabled>
                                                <option value="">-- Vui lòng chọn khoa trước --</option>
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" id="reloadLecturers"
                                                    class="btn reload-btn"
                                                    title="Tải lại danh sách giảng viên">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">Vui lòng chọn giảng viên hướng dẫn</div>
                                        <small class="text-muted">Chọn khoa trước để lọc giảng viên</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="advisorExpertise" class="required-field">Lĩnh vực chuyên
                                            môn</label>
                                        <input type="text" class="form-control" id="advisorExpertise"
                                            name="advisor_expertise" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="advisorRole" class="required-field">Nhiệm vụ</label>
                                        <input type="text" class="form-control" id="advisorRole"
                                            name="advisor_role" value="Hướng dẫn khoa học" required>
                                        <div class="invalid-feedback">Vui lòng nhập nhiệm vụ của giảng viên
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-file-alt mr-2"></i>Mô tả đề tài và kết quả dự kiến
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="projectDescription" class="required-field">Mô tả đề
                                        tài</label>
                                    <textarea class="form-control" id="projectDescription"
                                        name="project_description" rows="5" required
                                        placeholder="Mô tả chi tiết về mục tiêu, phạm vi, nội dung và phương pháp nghiên cứu của đề tài..."></textarea>
                                    <div class="invalid-feedback">Vui lòng nhập mô tả đề tài</div>
                                    <small class="text-muted">Mô tả chi tiết sẽ giúp giảng viên nắm rõ mục
                                        tiêu và phạm vi nghiên cứu của bạn</small>
                                </div>

                                <div class="form-group">
                                    <label for="expectedResults" class="required-field">Kết quả dự
                                        kiến</label>
                                    <textarea class="form-control" id="expectedResults"
                                        name="expected_results" rows="4" required
                                        placeholder="Mô tả những kết quả, sản phẩm cụ thể sẽ đạt được sau khi hoàn thành đề tài..."></textarea>
                                    <div class="invalid-feedback">Vui lòng nhập kết quả dự kiến</div>
                                    <small class="text-muted">Nêu rõ các sản phẩm, kết quả cụ thể mà đề tài
                                        sẽ tạo ra</small>
                                </div>

                                <div class="form-group">
                                    <label for="projectOutline" class="required-field">Đính kèm thuyết minh đề tài <span class="text-danger">*</span></label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="projectOutline"
                                            name="project_outline" accept=".pdf,.doc,.docx" required>
                                        <label class="custom-file-label" for="projectOutline">Chọn
                                            file...</label>
                                    </div>
                                    <div class="invalid-feedback">Vui lòng đính kèm file thuyết minh đề tài</div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1 text-primary"></i>
                                        <strong>Bắt buộc:</strong> Định dạng PDF, DOC, DOCX, tối đa 5MB. File thuyết minh chi tiết giúp giảng
                                        viên đánh giá tốt hơn và tăng cơ hội được duyệt.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="text-right mt-4">
                            <button type="button" class="btn btn-secondary prev-step mr-2" data-step="3">
                                <i class="fas fa-arrow-left mr-1"></i> Quay lại
                            </button>
                            <button type="button" class="btn btn-primary next-step" data-step="3">
                                Tiếp theo <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Bước 4: Xác nhận -->
                    <div class="form-section" id="step4">
                        <div class="section-title">
                            <i class="fas fa-check-circle mr-2"></i>Xác nhận thông tin đăng ký đề tài
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2 text-primary">
                                            <i class="fas fa-info-circle mr-2"></i>Thông tin đề tài
                                        </h5>
                                        <p><strong>Tên đề tài:</strong> <span id="summary-project-title"
                                                class="text-dark"></span></p>
                                        <p><strong>Loại hình nghiên cứu:</strong> <span
                                                id="summary-research-type" class="text-dark"></span></p>
                                        <p><strong>Lĩnh vực ưu tiên:</strong> <span
                                                id="summary-priority-field" class="text-dark"></span></p>
                                        <p><strong>Lĩnh vực nghiên cứu:</strong> <span
                                                id="summary-research-field" class="text-dark"></span></p>
                                        <p><strong>Loại đề tài:</strong> <span id="summary-project-category"
                                                class="text-dark"></span></p>
                                        <p><strong>Khoa chủ trì:</strong> <span id="summary-faculty"
                                                class="text-dark"></span></p>
                                        <p><strong>Thời gian thực hiện:</strong> <span id="summary-implementation-time" class="text-dark"></span></p>
                                        <p><strong>Số thành viên:</strong> <span id="summary-member-count"
                                                class="text-dark"></span> người</p>
                                    </div>

                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2 text-primary">
                                            <i class="fas fa-user-tie mr-2"></i>Giảng viên hướng dẫn
                                        </h5>
                                        <p><strong>Họ và tên:</strong> <span id="summary-advisor-name"
                                                class="text-dark"></span></p>
                                        <p><strong>Đơn vị công tác:</strong> <span
                                                id="summary-advisor-department" class="text-dark"></span>
                                        </p>
                                        <p><strong>Lĩnh vực chuyên môn:</strong> <span
                                                id="summary-advisor-expertise" class="text-dark"></span></p>
                                        <p><strong>Nhiệm vụ:</strong> <span id="summary-advisor-role"
                                                class="text-dark"></span></p>
                                    </div>
                                </div>

                                <h5 class="border-bottom pb-2 text-primary">
                                    <i class="fas fa-users mr-2"></i>Danh sách thành viên
                                </h5>
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th scope="col" style="width: 5%">#</th>
                                                <th scope="col" style="width: 25%">Họ và tên</th>
                                                <th scope="col" style="width: 15%">MSSV</th>
                                                <th scope="col" style="width: 20%">Lớp</th>
                                                <th scope="col" style="width: 25%">Email</th>
                                                <th scope="col" style="width: 10%">Vai trò</th>
                                            </tr>
                                        </thead>
                                        <tbody id="summary-members-table">
                                            <!-- Thành viên sẽ được thêm vào đây bằng JavaScript -->
                                        </tbody>
                                    </table>
                                </div>

                                <h5 class="border-bottom pb-2 text-primary">
                                    <i class="fas fa-file-alt mr-2"></i>Mô tả đề tài
                                </h5>
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <p id="summary-project-description"></p>
                                    </div>
                                </div>

                                <h5 class="border-bottom pb-2 text-primary">
                                    <i class="fas fa-clipboard-check mr-2"></i>Kết quả dự kiến
                                </h5>
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <p id="summary-expected-results"></p>
                                    </div>
                                </div>

                                <h5 class="border-bottom pb-2 text-primary">
                                    <i class="fas fa-file-alt mr-2"></i>File thuyết minh đề tài
                                </h5>
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <p><strong>Tên file:</strong> <span id="summary-project-outline-name"></span></p>
                                        <p><strong>Kích thước:</strong> <span id="summary-project-outline-size"></span></p>
                                        <p><strong>Định dạng:</strong> <span id="summary-project-outline-type"></span></p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="agreeTerms"
                                            required>
                                        <label class="custom-control-label" for="agreeTerms">
                                            Tôi xác nhận các thông tin trên là chính xác và cam kết thực
                                            hiện đề tài theo đúng quy định
                                        </label>
                                        <div class="invalid-feedback">Bạn cần đồng ý với điều khoản này để
                                            tiếp tục</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i> Sau khi gửi đăng ký, đề tài của bạn sẽ
                            được gửi đến giảng viên hướng dẫn và cán bộ quản lý để xem xét phê duyệt. Quá
                            trình này có thể mất 3-7 ngày làm việc.
                        </div>

                        <div class="text-right mt-4">
                            <button type="button" class="btn btn-secondary prev-step mr-2" data-step="4">
                                <i class="fas fa-arrow-left mr-1"></i> Quay lại
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitRegistrationBtn">
                                <i class="fas fa-paper-plane mr-1"></i> Gửi đăng ký đề tài
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Template cho thẻ thành viên -->
    <template id="memberCardTemplate">
        <div class="member-card mb-4">
            <span class="remove-member" title="Xóa thành viên này"><i class="fas fa-times"></i></span>
            <h6 class="mb-3"><i class="fas fa-user mr-2"></i> Thành viên #<span class="member-number"></span></h6>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="required-field">Họ và tên</label>
                    <input type="text" class="form-control member-name" name="member_name[]" required maxlength="100"
                        placeholder="Nhập họ và tên" readonly>
                    <div class="invalid-feedback">Vui lòng nhập họ tên thành viên</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">MSSV</label>
                    <div class="input-group">
                        <input type="text" class="form-control member-student-id" name="member_student_id[]" required
                            pattern="[A-Za-z0-9]{8}" placeholder="Nhập MSSV">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary fetch-student-info" title="Tìm thông tin sinh viên">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Vui lòng nhập MSSV hợp lệ (8 ký tự)</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="required-field">Ngày sinh</label>
                    <input type="date" class="form-control member-dob" name="member_dob[]" required readonly>
                    <div class="invalid-feedback">Vui lòng chọn ngày sinh</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="required-field">Tên lớp</label>
                    <input type="text" class="form-control member-class" name="member_class[]" required
                        placeholder="Nhập tên lớp" readonly>
                    <div class="invalid-feedback">Vui lòng nhập tên lớp</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="required-field">Số điện thoại</label>
                    <input type="text" class="form-control member-phone" name="member_phone[]" required
                        pattern="[0-9]{10}" placeholder="Nhập số điện thoại" readonly>
                    <div class="invalid-feedback">Vui lòng nhập số điện thoại hợp lệ (10 số)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="required-field">Khóa</label>
                    <input type="text" class="form-control member-year-group" name="member_year_group[]" required
                        placeholder="Nhập khóa học (VD: 47)" readonly>
                    <div class="invalid-feedback">Vui lòng nhập khóa học (VD: 47, 48,...)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="required-field">Email</label>
                    <input type="email" class="form-control member-email" name="member_email[]" required
                        placeholder="Nhập email" readonly>
                    <div class="invalid-feedback">Vui lòng nhập địa chỉ email hợp lệ</div>
                </div>
            </div>
            <div class="clearfix text-right">
                <button type="button" class="btn btn-sm btn-outline-secondary clear-student-info">
                    <i class="fas fa-eraser mr-1"></i> Xóa thông tin
                </button>
            </div>
        </div>
    </template>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Khởi tạo tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Bước hiện tại
            let currentStep = 1;

            // Khởi tạo số lượng thành viên mặc định
            updateMembersUI();

            // Lưu trữ danh sách giảng viên đầy đủ
            let allLecturers = [];

            // Khởi tạo danh sách giảng viên ban đầu
            function initLecturersList() {
                allLecturers = [];
                
                <?php if (!empty($lecturers)): ?>
                    <?php foreach ($lecturers as $lecturer): ?>
                    allLecturers.push({
                        id: <?php echo json_encode($lecturer['GV_MAGV'] ?? ''); ?>,
                        name: <?php echo json_encode($lecturer['GV_HOTEN'] ?? ''); ?>,
                        faculty: <?php echo json_encode($lecturer['DV_MADV'] ?? ''); ?>,
                        department: <?php echo json_encode($lecturer['DV_TENDV'] ?? ''); ?>,
                        expertise: <?php echo json_encode($lecturer['GV_CHUYENMON'] ?? ''); ?>
                    });
                    <?php endforeach; ?>
                <?php endif; ?>
            }

            // Gọi hàm khởi tạo
            initLecturersList();

            // Xử lý sự kiện khi chọn khoa
            $('#facultyFilter').on('change', function() {
                const selectedFaculty = $(this).val();
                
                // Reset dropdown giảng viên
                const advisorDropdown = $('#advisorId');
                advisorDropdown.empty();
                
                if (!selectedFaculty) {
                    // Nếu chưa chọn khoa
                    advisorDropdown.append('<option value="">-- Vui lòng chọn khoa trước --</option>');
                    advisorDropdown.prop('disabled', true);
                    return;
                }
                
                // Lọc giảng viên theo khoa đã chọn
                const filteredLecturers = allLecturers.filter(lecturer => lecturer.faculty === selectedFaculty);
                
                if (filteredLecturers.length === 0) {
                    advisorDropdown.append('<option value="">-- Không có giảng viên thuộc khoa này --</option>');
                } else {
                    advisorDropdown.append('<option value="">-- Chọn giảng viên --</option>');
                    
                    // Thêm các giảng viên đã lọc vào dropdown
                    filteredLecturers.forEach(lecturer => {
                        advisorDropdown.append(`<option value="${lecturer.id}" 
                            data-department="${lecturer.department}"
                            data-expertise="${lecturer.expertise}">
                            ${lecturer.name} (${lecturer.id})
                        </option>`);
                    });
                    
                    // Kích hoạt dropdown giảng viên
                    advisorDropdown.prop('disabled', false);
                }
            });

            // Xử lý chuyển đổi bước
            $('.next-step').on('click', function() {
                const step = parseInt($(this).data('step'));
                console.log('Next button clicked for step:', step);

                // Kiểm tra validate form trước khi chuyển bước
                if (!validateStep(step)) {
                    return false;
                }

                // Kiểm tra trùng lặp thành viên khi chuyển sang bước 2
                if (step === 1) {
                    const duplicateCheck = checkAllMembersForDuplicates();
                    if (duplicateCheck.hasDuplicates) {
                        alert('Có thành viên trùng lặp trong danh sách! Vui lòng kiểm tra lại các MSSV sau:\n' + duplicateCheck.duplicates.join(', '));
                        return false;
                    }
                }

                // Chuẩn bị dữ liệu cho bước xác nhận nếu chuyển sang bước 4
                if (step === 3) {
                    prepareConfirmationData();
                }

                goToStep(step + 1);
            });

            $('.prev-step').on('click', function() {
                const step = parseInt($(this).data('step'));
                goToStep(step - 1);
            });

            // Hàm chuyển bước
            function goToStep(step) {
                console.log('Going to step:', step);
                // Ẩn tất cả các bước
                $('.form-section').removeClass('active');

                // Hiển thị bước hiện tại
                $(`#step${step}`).addClass('active');

                // Cập nhật trạng thái progress
                updateProgress(step);

                // Lưu bước hiện tại
                currentStep = step;

                // Kiểm tra trùng lặp khi chuyển sang bước 2
                if (step === 2) {
                    checkAndShowOverallDuplicates();
                }

                // Cuộn lên đầu
                $('html, body').animate({
                    scrollTop: $('#projectRegistrationForm').offset().top - 100
                }, 500);
            }

            // Cập nhật thanh tiến trình
            function updateProgress(step) {
                $('.progress-step').removeClass('active completed');

                // Đánh dấu các bước đã hoàn thành
                for (let i = 1; i < step; i++) {
                    $(`.progress-step[data-step="${i}"]`).addClass('completed');
                }

                // Đánh dấu bước hiện tại
                $(`.progress-step[data-step="${step}"]`).addClass('active');
            }

            // Xử lý khi thay đổi số lượng thành viên
            $('#memberCount').on('change', function() {
                updateMembersUI();
            });

            // Cập nhật giao diện thành viên dựa trên số lượng đã chọn
            function updateMembersUI() {
                const maxMembers = parseInt($('#memberCount').val()) - 1; // Trừ 1 vì đã có chủ nhiệm
                const currentMembers = $('#membersList .member-card').length;

                // Cập nhật số lượng hiển thị
                $('#maxMemberCount').text(maxMembers);
                $('#currentMemberCount').text(Math.min(currentMembers, maxMembers));

                // Nếu số lượng thành viên giảm, xóa bớt các thành viên cuối
                if (maxMembers < currentMembers) {
                    $('#membersList .member-card').slice(maxMembers).remove();
                    $('#currentMemberCount').text(maxMembers);
                }

                // Luôn hiển thị section thành viên vì theo quy định mới luôn có ít nhất 2 thành viên
                $('#membersSection').show();

                // Tự động thêm thành viên nếu chưa đủ số lượng tối thiểu
                while ($('#membersList .member-card').length < maxMembers) {
                    addMemberCard();
                }

                // Ẩn nút thêm thành viên nếu đã đủ số lượng
                if ($('#membersList .member-card').length >= maxMembers) {
                    $('#addMemberBtn').hide();
                } else {
                    $('#addMemberBtn').show();
                }

                // Cập nhật số thứ tự thành viên
                updateMemberNumbers();
                
                // Kiểm tra và cập nhật trạng thái validation
                checkMembersCompletion();
            }

            // Xử lý nút thêm thành viên
            $('#addMemberBtn').on('click', function() {
                const maxMembers = parseInt($('#memberCount').val()) - 1;

                if ($('#membersList .member-card').length < maxMembers) {
                    addMemberCard();

                    // Ẩn nút nếu đã đủ số lượng thành viên
                    if ($('#membersList .member-card').length >= maxMembers) {
                        $('#addMemberBtn').hide();
                    }

                    // Cập nhật số lượng hiển thị
                    $('#currentMemberCount').text($('#membersList .member-card').length);
                    
                    // Kiểm tra và cập nhật trạng thái validation
                    checkMembersCompletion();
                }
            });

            // Hàm kiểm tra thành viên trùng lặp
            function checkDuplicateMember(studentId) {
                const existingMembers = [];
                
                // Lấy tất cả MSSV đã có
                $('#membersList .member-student-id').each(function() {
                    const id = $(this).val().trim();
                    if (id && id !== studentId) {
                        existingMembers.push(id);
                    }
                });
                
                // Kiểm tra với chủ nhiệm
                const leaderId = $('#leaderStudentId').val().trim();
                if (leaderId && leaderId === studentId) {
                    return {
                        duplicate: true,
                        message: 'MSSV này đã được sử dụng cho chủ nhiệm đề tài!'
                    };
                }
                
                // Kiểm tra với các thành viên khác
                if (existingMembers.includes(studentId)) {
                    return {
                        duplicate: true,
                        message: 'MSSV này đã được thêm vào danh sách thành viên!'
                    };
                }
                
                return { duplicate: false };
            }

            // Hàm kiểm tra tất cả thành viên có trùng lặp không
            function checkAllMembersForDuplicates() {
                const allStudentIds = [];
                const duplicates = [];
                
                // Lấy MSSV của chủ nhiệm
                const leaderId = $('#leaderStudentId').val().trim();
                if (leaderId) {
                    allStudentIds.push(leaderId);
                }
                
                // Lấy MSSV của các thành viên
                $('#membersList .member-student-id').each(function() {
                    const studentId = $(this).val().trim();
                    if (studentId) {
                        if (allStudentIds.includes(studentId)) {
                            duplicates.push(studentId);
                        } else {
                            allStudentIds.push(studentId);
                        }
                    }
                });
                
                return {
                    hasDuplicates: duplicates.length > 0,
                    duplicates: [...new Set(duplicates)] // Loại bỏ trùng lặp trong danh sách lỗi
                };
            }

            // Hàm kiểm tra và hiển thị cảnh báo trùng lặp tổng thể
            function checkAndShowOverallDuplicates() {
                const duplicateCheck = checkAllMembersForDuplicates();
                
                // Xóa cảnh báo cũ
                $('#duplicate-warning').remove();
                
                if (duplicateCheck.hasDuplicates) {
                    // Hiển thị cảnh báo tổng thể
                    const warningHtml = `
                        <div id="duplicate-warning" class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Cảnh báo:</strong> Có thành viên trùng lặp trong danh sách! 
                            MSSV trùng lặp: <strong>${duplicateCheck.duplicates.join(', ')}</strong>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `;
                    
                    // Thêm cảnh báo vào đầu section thành viên
                    $('#membersSection').prepend(warningHtml);
                    
                    // Disable nút "Tiếp theo" nếu có trùng lặp
                    $('.next-step[data-step="2"]').prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                } else {
                    // Enable nút "Tiếp theo" nếu không có trùng lặp
                    $('.next-step[data-step="2"]').prop('disabled', false).addClass('btn-primary').removeClass('btn-secondary');
                }
            }

            // Hàm kiểm tra đủ số lượng thành viên
            function checkMembersCompletion() {
                const maxMembers = parseInt($('#memberCount').val()) - 1;
                const currentMembers = $('#membersList .member-card').length;
                const completedMembers = $('#membersList .member-card.member-loaded').length;
                
                // Xóa cảnh báo cũ
                $('#members-completion-warning').remove();
                
                // Theo quy định mới, luôn phải có ít nhất 2 thành viên (ngoài chủ nhiệm)
                if (maxMembers >= 2) {
                    if (currentMembers < maxMembers) {
                        // Thiếu thành viên
                        const warningHtml = `
                            <div id="members-completion-warning" class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <strong>Bắt buộc:</strong> Bạn cần thêm <strong>${maxMembers - currentMembers}</strong> thành viên nữa để đủ số lượng yêu cầu!
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `;
                        $('#membersSection').prepend(warningHtml);
                        
                        // Disable nút "Tiếp theo"
                        $('.next-step[data-step="2"]').prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                        return false;
                    } else if (completedMembers < maxMembers) {
                        // Đủ số lượng nhưng chưa hoàn thành thông tin
                        const warningHtml = `
                            <div id="members-completion-warning" class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Cần hoàn thành:</strong> Bạn cần hoàn thành thông tin cho <strong>${maxMembers - completedMembers}</strong> thành viên nữa!
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `;
                        $('#membersSection').prepend(warningHtml);
                        
                        // Disable nút "Tiếp theo"
                        $('.next-step[data-step="2"]').prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                        return false;
                    } else {
                        // Đủ số lượng và hoàn thành
                        const successHtml = `
                            <div id="members-completion-warning" class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle mr-2"></i>
                                <strong>Hoàn thành:</strong> Đã đủ số lượng thành viên và hoàn thành thông tin!
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `;
                        $('#membersSection').prepend(successHtml);
                        
                        // Enable nút "Tiếp theo" nếu không có trùng lặp
                        const duplicateCheck = checkAllMembersForDuplicates();
                        if (!duplicateCheck.hasDuplicates) {
                            $('.next-step[data-step="2"]').prop('disabled', false).addClass('btn-primary').removeClass('btn-secondary');
                        }
                        return true;
                    }
                } else {
                    // Trường hợp này không nên xảy ra theo quy định mới
                    $('.next-step[data-step="2"]').prop('disabled', false).addClass('btn-primary').removeClass('btn-secondary');
                    return true;
                }
            }

            // Thêm thẻ thành viên mới
            function addMemberCard() {
                const template = document.getElementById('memberCardTemplate');
                const memberCard = document.importNode(template.content, true);

                // Thêm vào danh sách
                document.getElementById('membersList').appendChild(memberCard);

                // Cập nhật số thứ tự
                updateMemberNumbers();

                // Gắn sự kiện xóa thành viên
                $('.remove-member').last().on('click', function() {
                    $(this).closest('.member-card').remove();
                    updateMemberNumbers();
                    updateMembersUI();
                    $('#addMemberBtn').show();
                    
                    // Kiểm tra trùng lặp sau khi xóa thành viên
                    if (currentStep === 2) {
                        checkAndShowOverallDuplicates();
                    }
                    
                    // Kiểm tra và cập nhật trạng thái validation
                    checkMembersCompletion();
                });
                
                // Ẩn nút clear ban đầu
                $('.clear-student-info').last().hide();
            }

            // Cập nhật số thứ tự các thành viên
            function updateMemberNumbers() {
                $('#membersList .member-card').each(function(index) {
                    $(this).find('.member-number').text(index + 1);
                });
            }

            // Xử lý sự kiện thay đổi giảng viên
            $('#advisorId').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const department = selectedOption.data('department') || '';
                const expertise = selectedOption.data('expertise') || '';
                
                // Debug để kiểm tra
                console.log("Selected advisor expertise:", expertise);
                
                // Đảm bảo trường này tồn tại trong form của bạn
                $('#advisorExpertise').val(expertise);
            });

            // Sửa đổi hàm tải lại danh sách giảng viên
            $('#reloadLecturers').on('click', function() {
                $(this).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: 'get_lecturers.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Cập nhật danh sách giảng viên đầy đủ
                            allLecturers = response.data.map(lecturer => ({
                                id: lecturer.GV_MAGV,
                                name: lecturer.GV_HOTEN,
                                faculty: lecturer.DV_MADV,
                                department: lecturer.DV_TENDV || '',
                                expertise: lecturer.GV_CHUYENMON || '' // Thêm trường này
                            }));
                            
                            // Kích hoạt lại sự kiện thay đổi khoa để cập nhật dropdown giảng viên
                            $('#facultyFilter').trigger('change');
                            
                            // Thông báo thành công
                            alert('Đã cập nhật danh sách giảng viên!');
                        } else {
                            alert('Không thể tải danh sách giảng viên: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Đã xảy ra lỗi khi tải danh sách giảng viên. Vui lòng thử lại sau!');
                    },
                    complete: function() {
                        $('#reloadLecturers').html('<i class="fas fa-sync-alt"></i>');
                    }
                });
            });

            // Hiển thị tên file khi chọn file
            $('.custom-file-input').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).siblings('.custom-file-label').addClass('selected').html(fileName || 'Chọn file...');
            });

            // Xác thực form theo từng bước
            function validateStep(step) {
                const form = document.getElementById('projectRegistrationForm');
                let isValid = true;

                // Reset trạng thái validation
                $('.form-control').removeClass('is-invalid');

                switch (step) {
                    case 1:
                        // Kiểm tra thông tin đề tài
                        if (!$('#projectTitle').val().trim()) {
                            $('#projectTitle').addClass('is-invalid');
                            isValid = false;
                        }

                        if (!$('#priorityField').val()) {
                            $('#priorityField').addClass('is-invalid');
                            isValid = false;
                        }

                        if (!$('#researchField').val()) {
                            $('#researchField').addClass('is-invalid');
                            isValid = false;
                        }

                        if (!$('#researchType').val()) {
                            $('#researchType').addClass('is-invalid');
                            isValid = false;
                        }

                        if (!$('#facultyId').val()) {
                            $('#facultyId').addClass('is-invalid');
                            isValid = false;
                        }

                        if (!$('#projectCategory').val()) {
                            $('#projectCategory').addClass('is-invalid');
                            isValid = false;
                        }
                        break;

                    case 2:
                        // Kiểm tra thông tin chủ nhiệm và các thành viên
                        if (!$('#leaderName').val().trim() || !$('#leaderStudentId').val().trim() ||
                            !$('#leaderDob').val() || !$('#leaderClass').val().trim() ||
                            !$('#leaderPhone').val().trim() || !$('#leaderYearGroup').val().trim() ||
                            !$('#leaderEmail').val().trim()) {
                            isValid = false;
                            alert('Vui lòng điền đầy đủ thông tin chủ nhiệm đề tài!');
                        }

                        // Kiểm tra trùng lặp thành viên
                        const duplicateCheck = checkAllMembersForDuplicates();
                        if (duplicateCheck.hasDuplicates) {
                            isValid = false;
                            alert('Có thành viên trùng lặp trong danh sách! Vui lòng kiểm tra lại các MSSV sau:\n' + duplicateCheck.duplicates.join(', '));
                            return false;
                        }

                        // Kiểm tra đủ số lượng thành viên
                        const maxMembers = parseInt($('#memberCount').val()) - 1;
                        const currentMembers = $('#membersList .member-card').length;
                        const completedMembers = $('#membersList .member-card.member-loaded').length;
                        
                        // Theo quy định mới, luôn phải có ít nhất 2 thành viên (ngoài chủ nhiệm)
                        if (maxMembers >= 2) {
                            if (currentMembers < maxMembers) {
                                alert(`Bạn cần thêm ${maxMembers - currentMembers} thành viên nữa để đủ số lượng yêu cầu!`);
                                isValid = false;
                            } else if (completedMembers < maxMembers) {
                                alert(`Bạn cần hoàn thành thông tin cho ${maxMembers - completedMembers} thành viên nữa!`);
                                isValid = false;
                            }
                        }

                        // Kiểm tra từng thành viên
                        $('#membersList .member-card').each(function() {
                            const name = $(this).find('.member-name').val().trim();
                            const studentId = $(this).find('.member-student-id').val().trim();
                            const dob = $(this).find('.member-dob').val();
                            const className = $(this).find('.member-class').val().trim();
                            const phone = $(this).find('.member-phone').val().trim();
                            const yearGroup = $(this).find('.member-year-group').val().trim();
                            const email = $(this).find('.member-email').val().trim();

                            if (!name || !studentId || !dob || !className || !phone || !yearGroup || !email) {
                                isValid = false;
                                $(this).find('input').each(function() {
                                    if (!$(this).val().trim()) {
                                        $(this).addClass('is-invalid');
                                    }
                                });
                            }

                            // Kiểm tra định dạng MSSV (8 ký tự)
                            if (studentId && !studentId.match(/^[A-Za-z0-9]{8}$/)) {
                                $(this).find('.member-student-id').addClass('is-invalid');
                                isValid = false;
                            }

                            // Kiểm tra định dạng SĐT (10 số)
                            if (phone && !phone.match(/^[0-9]{10}$/)) {
                                $(this).find('.member-phone').addClass('is-invalid');
                                isValid = false;
                            }

                            // Kiểm tra định dạng email
                            if (email && !isValidEmail(email)) {
                                $(this).find('.member-email').addClass('is-invalid');
                                isValid = false;
                            }
                        });
                        break;

                    case 3:
                        // Kiểm tra thông tin GVHD và mô tả
                        if (!$('#advisorId').val() || !$('#advisorExpertise').val().trim() || !$('#advisorRole').val().trim()) {
                            isValid = false;

                            if (!$('#advisorId').val()) {
                                $('#advisorId').addClass('is-invalid');
                            }

                            if (!$('#advisorExpertise').val().trim()) {
                                $('#advisorExpertise').addClass('is-invalid');
                            }

                            if (!$('#advisorRole').val().trim()) {
                                $('#advisorRole').addClass('is-invalid');
                            }
                        }

                        if (!$('#projectDescription').val().trim()) {
                            $('#projectDescription').addClass('is-invalid');
                            isValid = false;
                        }

                        if (!$('#expectedResults').val().trim()) {
                            $('#expectedResults').addClass('is-invalid');
                            isValid = false;
                        }

                        // Kiểm tra file thuyết minh (bắt buộc)
                        const fileInput = document.getElementById('projectOutline');
                        if (fileInput.files.length === 0) {
                            $('#projectOutline').addClass('is-invalid');
                            isValid = false;
                        } else {
                            const file = fileInput.files[0];
                            const fileExtension = file.name.split('.').pop().toLowerCase();
                            const allowedExtensions = ['pdf', 'doc', 'docx'];
                            const maxFileSize = 5 * 1024 * 1024; // 5MB

                            if (allowedExtensions.indexOf(fileExtension) === -1) {
                                alert('File thuyết minh phải là định dạng PDF, DOC hoặc DOCX!');
                                $('#projectOutline').addClass('is-invalid');
                                isValid = false;
                            }
                            
                            if (file.size > maxFileSize) {
                                alert('Kích thước file thuyết minh không được vượt quá 5MB!');
                                $('#projectOutline').addClass('is-invalid');
                                isValid = false;
                            }
                        }
                        break;

                    case 4:
                        // Kiểm tra đã tích checkbox xác nhận
                        if (!$('#agreeTerms').is(':checked')) {
                            $('#agreeTerms').addClass('is-invalid');
                            isValid = false;
                        }
                        break;
                }

                if (!isValid) {
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 120
                    }, 500);
                }

                return isValid;
            }

            // Kiểm tra email hợp lệ
            function isValidEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            }

            // Hàm format kích thước file
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Chuẩn bị dữ liệu hiển thị cho bước xác nhận
            function prepareConfirmationData() {
                // Thông tin đề tài
                $('#summary-project-title').text($('#projectTitle').val());
                $('#summary-research-type').text($('#researchType option:selected').text());
                $('#summary-priority-field').text($('#priorityField option:selected').text());
                $('#summary-research-field').text($('#researchField option:selected').text());
                $('#summary-project-category').text($('#projectCategory option:selected').text());
                $('#summary-faculty').text($('#facultyId option:selected').text());
                $('#summary-implementation-time').text($('#implementationTime option:selected').text());
                $('#summary-member-count').text($('#memberCount').val());

                // Thông tin giảng viên hướng dẫn
                $('#summary-advisor-name').text($('#advisorId option:selected').text());
                $('#summary-advisor-department').text($('#advisorDepartment').val());
                $('#summary-advisor-expertise').text($('#advisorExpertise').val());
                $('#summary-advisor-role').text($('#advisorRole').val());

                // Mô tả và kết quả dự kiến
                $('#summary-project-description').text($('#projectDescription').val());
                $('#summary-expected-results').text($('#expectedResults').val());

                // Thông tin file thuyết minh
                const fileInput = document.getElementById('projectOutline');
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    $('#summary-project-outline-name').text(file.name);
                    $('#summary-project-outline-size').text(formatFileSize(file.size));
                    $('#summary-project-outline-type').text(file.name.split('.').pop().toUpperCase());
                } else {
                    $('#summary-project-outline-name').text('Chưa chọn file');
                    $('#summary-project-outline-size').text('N/A');
                    $('#summary-project-outline-type').text('N/A');
                }

                // Danh sách thành viên
                let tableHTML = '';

                // Thêm chủ nhiệm vào bảng
                tableHTML += `<tr>
                    <td>1</td>
                    <td>${$('#leaderName').val()}</td>
                    <td>${$('#leaderStudentId').val()}</td>
                    <td>${$('#leaderClass').val()}</td>
                    <td>${$('#leaderEmail').val()}</td>
                    <td><span class="badge badge-primary">Chủ nhiệm</span></td>
                </tr>`;

                // Thêm các thành viên khác
                $('#membersList .member-card').each(function(index) {
                    tableHTML += `<tr>
                        <td>${index + 2}</td>
                        <td>${$(this).find('.member-name').val()}</td>
                        <td>${$(this).find('.member-student-id').val()}</td>
                        <td>${$(this).find('.member-class').val()}</td>
                        <td>${$(this).find('.member-email').val()}</td>
                        <td><span class="badge badge-secondary">Thành viên</span></td>
                    </tr>`;
                });

                // Cập nhật bảng
                $('#summary-members-table').html(tableHTML);
            }

            // Xử lý submit form
            $('#projectRegistrationForm').on('submit', function(event) {
                // Xác thực bước cuối
                if (!validateStep(4)) {
                    event.preventDefault();
                    return false;
                }

                // Kiểm tra thành viên trùng lặp trước khi submit
                const duplicateMembers = [];
                const allStudentIds = [];
                
                // Lấy MSSV của chủ nhiệm
                const leaderId = $('#leaderStudentId').val().trim();
                if (leaderId) {
                    allStudentIds.push(leaderId);
                }
                
                // Lấy MSSV của các thành viên
                $('#membersList .member-student-id').each(function() {
                    const studentId = $(this).val().trim();
                    if (studentId) {
                        if (allStudentIds.includes(studentId)) {
                            duplicateMembers.push(studentId);
                        } else {
                            allStudentIds.push(studentId);
                        }
                    }
                });
                
                if (duplicateMembers.length > 0) {
                    event.preventDefault();
                    alert('Có thành viên trùng lặp trong danh sách! Vui lòng kiểm tra lại các MSSV sau:\n' + duplicateMembers.join(', '));
                    return false;
                }

                // Kiểm tra file thuyết minh một lần nữa trước khi submit
                const fileInput = document.getElementById('projectOutline');
                if (fileInput.files.length === 0) {
                    event.preventDefault();
                    alert('Vui lòng đính kèm file thuyết minh đề tài trước khi gửi đăng ký!');
                    $('#projectOutline').addClass('is-invalid');
                    $('html, body').animate({
                        scrollTop: $('#projectOutline').offset().top - 120
                    }, 500);
                    return false;
                }

                // Thay đổi nút submit để ngăn gửi nhiều lần
                $('#submitRegistrationBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
            });

            // Xử lý tìm kiếm thông tin sinh viên theo MSSV
            $(document).on('click', '.fetch-student-info', function() {
                const memberCard = $(this).closest('.member-card');
                const studentIdInput = memberCard.find('.member-student-id');
                const studentId = studentIdInput.val().trim();
                
                if (!studentId) {
                    alert('Vui lòng nhập MSSV trước khi tìm kiếm');
                    return;
                }
                
                if (!studentId.match(/^[A-Za-z0-9]{8}$/)) {
                    alert('MSSV phải có đúng 8 ký tự');
                    return;
                }

                // Kiểm tra trùng lặp trước khi tìm kiếm
                const duplicateCheck = checkDuplicateMember(studentId);
                if (duplicateCheck.duplicate) {
                    alert('Lỗi: ' + duplicateCheck.message);
                    studentIdInput.focus();
                    return;
                }
                
                // Hiển thị trạng thái đang tải
                $(this).html('<i class="fas fa-spinner fa-spin"></i>');
                const searchBtn = $(this);
                
                // Gửi request AJAX để lấy thông tin sinh viên
                $.ajax({
                    url: '/NLNganh/get_student_info_test.php', // Sử dụng version test
                    method: 'GET',
                    data: { student_id: studentId },
                    dataType: 'json',
                    timeout: 10000, // 10 giây timeout
                    success: function(response) {
                        console.log('Student search response:', response);
                        
                        if (response.success) {
                            const data = response.data;
                            
                            // Tự động điền thông tin vào form
                            memberCard.find('.member-name').val(data.fullname).prop('readonly', true);
                            memberCard.find('.member-dob').val(data.SV_NGAYSINH).prop('readonly', true);
                            memberCard.find('.member-class').val(data.LOP_TEN).prop('readonly', true);
                            memberCard.find('.member-phone').val(data.SV_SDT).prop('readonly', true);
                            memberCard.find('.member-year-group').val(data.KHOA).prop('readonly', true);
                            memberCard.find('.member-email').val(data.SV_EMAIL).prop('readonly', true);
                            
                            // Đánh dấu thành công
                            studentIdInput.prop('readonly', true);
                            memberCard.addClass('member-loaded');
                            
                            // Hiện nút clear
                            memberCard.find('.clear-student-info').show();
                            
                            // Thông báo thành công
                            memberCard.find('.member-name').parent().append('<small class="text-success"><i class="fas fa-check-circle mr-1"></i>Đã tải thông tin thành công</small>');
                            
                            // Kiểm tra và cập nhật trạng thái validation sau khi tải thông tin thành công
                            checkMembersCompletion();
                            
                        } else {
                            alert('Lỗi: ' + response.message);
                            console.error('API Error:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error Details:', {
                            xhr: xhr,
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            readyState: xhr.readyState,
                            statusText: xhr.statusText
                        });
                        
                        let errorMessage = 'Có lỗi xảy ra khi tìm kiếm thông tin sinh viên.';
                        
                        if (status === 'timeout') {
                            errorMessage = 'Kết nối quá chậm. Vui lòng thử lại sau.';
                        } else if (status === 'error') {
                            if (xhr.status === 404) {
                                errorMessage = 'Không tìm thấy API endpoint. Vui lòng liên hệ quản trị viên.';
                            } else if (xhr.status === 500) {
                                errorMessage = 'Lỗi server. Vui lòng thử lại sau.';
                            } else if (xhr.status === 0) {
                                errorMessage = 'Không thể kết nối đến server. Kiểm tra kết nối mạng.';
                            }
                        }
                        
                        alert(errorMessage + '\n\nChi tiết lỗi: ' + error + ' (Status: ' + xhr.status + ')');
                    },
                    complete: function() {
                        // Khôi phục nút tìm kiếm
                        searchBtn.html('<i class="fas fa-search"></i>');
                    }
                });
            });

            // Xử lý xóa thông tin sinh viên
            $(document).on('click', '.clear-student-info', function() {
                const memberCard = $(this).closest('.member-card');
                
                // Xóa tất cả thông tin
                memberCard.find('.member-name').val('').prop('readonly', false);
                memberCard.find('.member-student-id').val('').prop('readonly', false);
                memberCard.find('.member-dob').val('').prop('readonly', false);
                memberCard.find('.member-class').val('').prop('readonly', false);
                memberCard.find('.member-phone').val('').prop('readonly', false);
                memberCard.find('.member-year-group').val('').prop('readonly', false);
                memberCard.find('.member-email').val('').prop('readonly', false);
                
                // Bỏ đánh dấu đã load
                memberCard.removeClass('member-loaded');
                
                // Ẩn nút clear
                $(this).hide();
                
                // Kiểm tra và cập nhật trạng thái validation sau khi xóa thông tin
                checkMembersCompletion();
            });

            // Xử lý validation khi nhập MSSV
            $(document).on('input', '.member-student-id', function() {
                const memberCard = $(this).closest('.member-card');
                const studentId = $(this).val().trim();
                
                // Xóa thông báo lỗi cũ
                memberCard.find('.duplicate-error').remove();
                
                if (studentId && studentId.length === 8) {
                    const duplicateCheck = checkDuplicateMember(studentId);
                    if (duplicateCheck.duplicate) {
                        // Hiển thị thông báo lỗi
                        $(this).addClass('is-invalid');
                        memberCard.find('.member-name').parent().append(
                            '<small class="text-danger duplicate-error"><i class="fas fa-exclamation-triangle mr-1"></i>' + 
                            duplicateCheck.message + '</small>'
                        );
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                } else {
                    $(this).removeClass('is-invalid');
                }
                
                // Kiểm tra tổng thể và hiển thị cảnh báo nếu có trùng lặp
                if (currentStep === 2) {
                    checkAndShowOverallDuplicates();
                    checkMembersCompletion();
                }
            });

            // Thêm CSS cho trạng thái đã load và lỗi trùng lặp
            $("<style>")
    .prop("type", "text/css")
    .html(`
    .member-card.member-loaded {
        border-left: 4px solid #1cc88a;
        background-color: #f8fffa;
    }
    .clear-student-info {
        display: none;
    }
    .member-loaded .clear-student-info {
        display: inline-block;
    }
    .duplicate-error {
        display: block;
        margin-top: 5px;
        font-weight: 500;
    }
    .member-card .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    `)
    .appendTo("head");
        });
    </script>

    <script>
        window.addEventListener('load', function() {
            console.log('Window loaded');
            // Kiểm tra jQuery đã được tải chưa
            if (window.jQuery) {
                console.log('jQuery is loaded');
                
                // Kiểm tra và debug các nút "tiếp theo"
                console.log('Number of next-step buttons:', $('.next-step').length);
                
                $('.next-step').each(function(index) {
                    console.log(`Button ${index + 1} data-step:`, $(this).data('step'));
                });
                
                // Gắn một sự kiện click đơn giản để kiểm tra
                $('.next-step').on('click', function() {
                    console.log('Next button clicked with data-step:', $(this).data('step'));
                });
            } else {
                console.error('jQuery not loaded');
            }
        });
    </script>

    <script>
    // Sidebar toggle cho mobile
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.querySelector('.mobile-toggle-btn') && window.innerWidth <= 576) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'mobile-toggle-btn';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.appendChild(toggleBtn);
            toggleBtn.addEventListener('click', function() {
                const sidebar = document.querySelector('.student-sidebar');
                sidebar.classList.toggle('show');
            });
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 576 && !document.querySelector('.mobile-toggle-btn')) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'mobile-toggle-btn';
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                document.body.appendChild(toggleBtn);
                toggleBtn.addEventListener('click', function() {
                    const sidebar = document.querySelector('.student-sidebar');
                    sidebar.classList.toggle('show');
                });
            } else if (window.innerWidth > 576 && document.querySelector('.mobile-toggle-btn')) {
                document.querySelector('.mobile-toggle-btn').remove();
                document.querySelector('.student-sidebar').classList.remove('show');
            }
        });
    });
    </script>
</body>

</html>
