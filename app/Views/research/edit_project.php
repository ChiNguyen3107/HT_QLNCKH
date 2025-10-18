<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\edit_project.php
include '../../include/session.php';
checkResearchManagerRole();
include '../../include/database.php';

// Lấy thông tin quản lý nghiên cứu
$manager_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM quan_ly_nghien_cuu WHERE QL_MA = ?");
$stmt->bind_param("s", $manager_id);
$stmt->execute();
$result = $stmt->get_result();
$manager_info = $result->fetch_assoc();
$stmt->close();

// Kiểm tra tham số
$project_id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($project_id)) {
    $_SESSION['error_message'] = "Không tìm thấy mã đề tài!";
    header('Location: manage_projects.php');
    exit;
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Lấy dữ liệu từ form
        $project_title = trim($_POST['project_title']);
        $project_description = trim($_POST['project_description']);
        $research_field = $_POST['research_field'];
        $priority_field = $_POST['priority_field'];
        $project_category = $_POST['project_category'];
        $advisor_id = $_POST['advisor_id'];
        $implementation_time = $_POST['implementation_time'];
        $member_count = $_POST['member_count'];
        $expected_results = trim($_POST['expected_results']);

        // Validation
        if (empty($project_title)) {
            throw new Exception("Tên đề tài không được để trống!");
        }
        if (empty($project_description)) {
            throw new Exception("Mô tả đề tài không được để trống!");
        }
        if (empty($advisor_id)) {
            throw new Exception("Vui lòng chọn giảng viên hướng dẫn!");
        }

        // Xử lý file thuyết minh (nếu có upload file mới)
        $project_outline_path = null;
        if (isset($_FILES['project_outline']) && $_FILES['project_outline']['error'] === UPLOAD_ERR_OK) {
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            $file_extension = pathinfo($_FILES['project_outline']['name'], PATHINFO_EXTENSION);
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                throw new Exception("File thuyết minh phải là định dạng PDF, DOC hoặc DOCX!");
            }
            
            if ($_FILES['project_outline']['size'] > $max_file_size) {
                throw new Exception("Kích thước file thuyết minh không được vượt quá 5MB!");
            }

            $upload_dir = '../../uploads/project_outlines/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $project_outline_path = $upload_dir . uniqid('outline_') . '_' . $_FILES['project_outline']['name'];

            if (!move_uploaded_file($_FILES['project_outline']['tmp_name'], $project_outline_path)) {
                throw new Exception("Không thể lưu file thuyết minh!");
            }
        }

        // Cập nhật thông tin đề tài
        $update_sql = "UPDATE de_tai_nghien_cuu SET 
                       DT_TENDT = ?, 
                       DT_MOTA = ?, 
                       LVNC_MA = ?, 
                       LVUT_MA = ?, 
                       LDT_MA = ?, 
                       GV_MAGV = ?, 
                       DT_SLSV = ?,
                       DT_GHICHU = ?
                       WHERE DT_MADT = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("Lỗi khi chuẩn bị câu lệnh cập nhật: " . $conn->error);
        }

        $project_notes = "duration_months=$implementation_time;expected_results=" . urlencode($expected_results);
        
        $update_stmt->bind_param("sssssssss", 
            $project_title, 
            $project_description, 
            $research_field, 
            $priority_field, 
            $project_category, 
            $advisor_id, 
            $member_count,
            $project_notes,
            $project_id
        );

        if (!$update_stmt->execute()) {
            throw new Exception("Lỗi khi cập nhật đề tài: " . $update_stmt->error);
        }

        // Cập nhật file thuyết minh nếu có
        if ($project_outline_path) {
            $file_update_sql = "UPDATE de_tai_nghien_cuu SET DT_FILEBTM = ? WHERE DT_MADT = ?";
            $file_update_stmt = $conn->prepare($file_update_sql);
            $file_update_stmt->bind_param("ss", $project_outline_path, $project_id);
            $file_update_stmt->execute();
        }

        $_SESSION['success_message'] = "Cập nhật đề tài thành công!";
        header("Location: view_project.php?id=$project_id");
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        if (isset($project_outline_path) && file_exists($project_outline_path)) {
            unlink($project_outline_path);
        }
    }
}

// Lấy thông tin đề tài hiện tại
$sql = "SELECT dt.*, 
        ldt.LDT_TENLOAI, 
        lvnc.LVNC_TEN as LVNC_TEN,
        lvut.LVUT_TEN as LVUT_TEN,
        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN, 
        gv.GV_MAGV,
        gv.GV_CHUYENMON,
        k.DV_TENDV,
        k.DV_MADV
        FROM de_tai_nghien_cuu dt
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
        WHERE dt.DT_MADT = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Không tìm thấy đề tài!";
    header('Location: manage_projects.php');
    exit;
}

$project = $result->fetch_assoc();

// Lấy thông tin từ ghi chú
$project_notes = $project['DT_GHICHU'] ?? '';
$implementation_time = 6;
$expected_results = '';

if (!empty($project_notes)) {
    $notes_array = explode(';', $project_notes);
    foreach ($notes_array as $note) {
        if (strpos($note, 'duration_months=') === 0) {
            $implementation_time = intval(substr($note, 16));
        }
        if (strpos($note, 'expected_results=') === 0) {
            $expected_results = urldecode(substr($note, 17));
        }
    }
}

// Lấy danh sách dữ liệu cần thiết
$lecturers = [];
$faculties = [];
$categories = [];
$research_fields = [];
$priority_fields = [];

// Lấy giảng viên
$lecturers_result = $conn->query("SELECT gv.GV_MAGV, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, gv.GV_CHUYENMON, gv.DV_MADV, k.DV_TENDV FROM giang_vien gv LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV ORDER BY gv.GV_TENGV");
while ($row = $lecturers_result->fetch_assoc()) {
    $lecturers[] = $row;
}

// Lấy khoa
$faculties_result = $conn->query("SELECT DV_MADV, DV_TENDV FROM khoa ORDER BY DV_TENDV");
while ($row = $faculties_result->fetch_assoc()) {
    $faculties[] = $row;
}

// Lấy loại đề tài
$categories_result = $conn->query("SELECT LDT_MA, LDT_TENLOAI FROM loai_de_tai ORDER BY LDT_TENLOAI");
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Lấy lĩnh vực nghiên cứu
$fields_result = $conn->query("SELECT * FROM linh_vuc_nghien_cuu ORDER BY LVNC_TEN");
while ($row = $fields_result->fetch_assoc()) {
    $research_fields[] = $row;
}

// Lấy lĩnh vực ưu tiên
$priority_result = $conn->query("SELECT * FROM linh_vuc_uu_tien ORDER BY LVUT_TEN");
while ($row = $priority_result->fetch_assoc()) {
    $priority_fields[] = $row;
}
?>

<?php
// Set page title for the header
$page_title = "Chỉnh sửa đề tài | Quản lý nghiên cứu";

// Define any additional CSS specific to this page
$additional_css = '<style>
/* Custom styles for edit project page */
.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #224abe;
    border-color: #224abe;
}

.card-header.bg-primary {
    background-color: #4e73df !important;
}

/* Enhanced form styling */
.required-field::after {
    content: " *";
    color: #e74a3b;
    font-weight: bold;
}

.custom-file-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* File display styling */
.file-current {
    background-color: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 0.75rem;
    margin-top: 0.5rem;
}

.file-current a {
    color: #4e73df;
    text-decoration: none;
}

.file-current a:hover {
    color: #224abe;
    text-decoration: underline;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
}
</style>';

// Include the research header
include '../../include/research_header.php';
?>
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit me-3"></i>
                Chỉnh sửa đề tài: <?php echo htmlspecialchars($project['DT_MADT']); ?>
            </h1>
            <a href="view_project.php?id=<?php echo $project_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Quay lại
            </a>
        </div>

        <!-- Thông báo -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-edit mr-1"></i> Thông tin đề tài
                </h6>
            </div>
            <div class="card-body">
                <form action="edit_project.php?id=<?php echo $project_id; ?>" method="post" enctype="multipart/form-data">
                    <!-- Thông tin cơ bản -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="projectTitle" class="required-field">Tên đề tài</label>
                            <input type="text" class="form-control" id="projectTitle" name="project_title" 
                                   value="<?php echo htmlspecialchars($project['DT_TENDT']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="priorityField" class="required-field">Lĩnh vực ưu tiên</label>
                            <select class="form-control" id="priorityField" name="priority_field" required>
                                <option value="">-- Chọn lĩnh vực ưu tiên --</option>
                                <?php foreach ($priority_fields as $field): ?>
                                    <option value="<?php echo $field['LVUT_MA']; ?>" 
                                            <?php echo ($project['LVUT_MA'] === $field['LVUT_MA']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($field['LVUT_TEN']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="researchField" class="required-field">Lĩnh vực nghiên cứu</label>
                            <select class="form-control" id="researchField" name="research_field" required>
                                <option value="">-- Chọn lĩnh vực nghiên cứu --</option>
                                <?php foreach ($research_fields as $field): ?>
                                    <option value="<?php echo $field['LVNC_MA']; ?>" 
                                            <?php echo ($project['LVNC_MA'] === $field['LVNC_MA']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($field['LVNC_TEN']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="projectCategory" class="required-field">Loại đề tài</label>
                            <select class="form-control" id="projectCategory" name="project_category" required>
                                <option value="">-- Chọn loại đề tài --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['LDT_MA']; ?>" 
                                            <?php echo ($project['LDT_MA'] === $category['LDT_MA']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['LDT_TENLOAI']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="advisorId" class="required-field">Giảng viên hướng dẫn</label>
                            <select class="form-control" id="advisorId" name="advisor_id" required>
                                <option value="">-- Chọn giảng viên --</option>
                                <?php foreach ($lecturers as $lecturer): ?>
                                    <option value="<?php echo $lecturer['GV_MAGV']; ?>" 
                                            <?php echo ($project['GV_MAGV'] === $lecturer['GV_MAGV']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lecturer['GV_HOTEN']); ?> (<?php echo $lecturer['GV_MAGV']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="memberCount" class="required-field">Số lượng thành viên</label>
                            <select class="form-control" id="memberCount" name="member_count" required>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                            <?php echo ($project['DT_SLSV'] == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> thành viên
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="implementationTime" class="required-field">Thời gian thực hiện</label>
                            <select class="form-control" id="implementationTime" name="implementation_time" required>
                                <option value="3" <?php echo ($implementation_time == 3) ? 'selected' : ''; ?>>3 tháng</option>
                                <option value="6" <?php echo ($implementation_time == 6) ? 'selected' : ''; ?>>6 tháng</option>
                                <option value="9" <?php echo ($implementation_time == 9) ? 'selected' : ''; ?>>9 tháng</option>
                                <option value="12" <?php echo ($implementation_time == 12) ? 'selected' : ''; ?>>12 tháng</option>
                            </select>
                        </div>
                    </div>

                    <!-- Mô tả đề tài -->
                    <div class="form-group">
                        <label for="projectDescription" class="required-field">Mô tả đề tài</label>
                        <textarea class="form-control" id="projectDescription" name="project_description" 
                                  rows="5" required><?php echo htmlspecialchars($project['DT_MOTA']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="expectedResults" class="required-field">Kết quả dự kiến</label>
                        <textarea class="form-control" id="expectedResults" name="expected_results" 
                                  rows="4" required><?php echo htmlspecialchars($expected_results); ?></textarea>
                    </div>

                    <!-- File thuyết minh -->
                    <div class="form-group">
                        <label for="projectOutline">File thuyết minh mới (không bắt buộc)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="projectOutline" name="project_outline" accept=".pdf,.doc,.docx">
                            <label class="custom-file-label" for="projectOutline">Chọn file...</label>
                        </div>
                        <small class="form-text text-muted">Định dạng: PDF, DOC, DOCX, tối đa 5MB</small>
                        
                                                 <?php if (!empty($project['DT_FILEBTM'])): ?>
                             <div class="file-current">
                                 <strong>File hiện tại:</strong> 
                                 <a href="<?php echo $project['DT_FILEBTM']; ?>" target="_blank">
                                     <i class="fas fa-file-alt me-2"></i><?php echo basename($project['DT_FILEBTM']); ?>
                                 </a>
                             </div>
                         <?php endif; ?>
                    </div>

                                         <!-- Nút submit -->
                     <div class="text-center">
                         <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                             <i class="fas fa-save me-2"></i> Cập nhật đề tài
                         </button>
                         <a href="view_project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary btn-lg ml-2 shadow-sm">
                             <i class="fas fa-times me-2"></i> Hủy
                         </a>
                     </div>
                </form>
            </div>
        </div>
         </div>
 </div>
 <!-- /.container-fluid -->

 </div>
 <!-- End of Main Content -->

 </div>
 <!-- End of Content Wrapper -->

 </div>
 <!-- End of Page Wrapper -->

 <!-- Scroll to Top Button-->
 <a class="scroll-to-top rounded" href="#page-top">
     <i class="fas fa-angle-up"></i>
 </a>

 <!-- Logout Modal-->
 <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="exampleModalLabel">Sẵn sàng đăng xuất?</h5>
                 <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">×</span>
                 </button>
             </div>
             <div class="modal-body">Chọn "Đăng xuất" bên dưới nếu bạn đã sẵn sàng kết thúc phiên hiện tại.</div>
             <div class="modal-footer">
                 <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy</button>
                 <a class="btn btn-primary" href="/NLNganh/logout.php">Đăng xuất</a>
             </div>
         </div>
     </div>
 </div>

 <!-- Bootstrap core JavaScript-->
 <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

 <!-- Core plugin JavaScript-->
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

 <!-- Custom scripts for all pages-->
 <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>

 <!-- DataTables JavaScript -->
 <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
 <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>

 <!-- Chart.js JavaScript -->
 <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

 <!-- Research specific scripts -->
 <script src="/NLNganh/assets/js/research/sidebar-menu.js"></script>
 <script src="/NLNganh/assets/js/research/sidebar-fix.js"></script>
 <script src="/NLNganh/assets/js/research/notifications.js"></script>

 <script>
     $(document).ready(function() {
         // Hiển thị tên file khi chọn file
         $('.custom-file-input').on('change', function() {
             const fileName = $(this).val().split('\\').pop();
             $(this).siblings('.custom-file-label').addClass('selected').html(fileName || 'Chọn file...');
         });
         
         // Đánh dấu menu active
         $('.sidebar-nav a[href*="manage_projects"]').addClass('active');
         $('.sidebar-nav .has-submenu').addClass('open');
         $('.sidebar-nav .submenu').addClass('active');
     });
 </script>

 </body>
 </html>
