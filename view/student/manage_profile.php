<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\manage_profile.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thông tin sinh viên từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$sql = "SELECT SV_MASV, SV_HOSV, SV_TENSV, SV_EMAIL, SV_SDT, LOP_MA, SV_NGAYSINH, SV_GIOITINH, SV_DIACHI FROM sinh_vien WHERE SV_MASV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Lấy thông tin lớp, khoa, khóa học và niên khóa từ các bảng liên quan
$lop_ma = $student['LOP_MA'];
$sql = "SELECT lop.LOP_MA, lop.LOP_TEN, khoa.DV_TENDV, khoa_hoc.KH_NAM, lop.LOP_LOAICTDT 
        FROM lop 
        JOIN khoa ON lop.DV_MADV = khoa.DV_MADV 
        JOIN khoa_hoc ON lop.KH_NAM = khoa_hoc.KH_NAM 
        WHERE lop.LOP_MA = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $lop_ma);
$stmt->execute();
$result = $stmt->get_result();
$class_info = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hồ sơ sinh viên</title>
    
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/student/manage_profile.css" rel="stylesheet">
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="container-fluid content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/NLNganh/view/student/student_dashboard.php">
                    <i class="fas fa-home mr-1"></i> Trang chủ
                </a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý hồ sơ</li>
            </ol>
        </nav>

        <h1 class="page-title">
            <i class="fas fa-user-edit mr-2"></i>Quản lý hồ sơ sinh viên
        </h1>

        <!-- Thông tin sinh viên -->
        <div class="student-info">
            <div class="student-info-header">
                <h3><i class="fas fa-user-circle mr-2"></i>Thông tin cá nhân</h3>
            </div>
            
            <div class="card profile-card">
                <div class="card-header">
                    <i class="fas fa-id-card mr-1"></i> Cập nhật thông tin
                </div>
                <div class="card-body">
                    <form action="update_profile.php" method="post" id="profileForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-section">
                                    <h4><i class="fas fa-info-circle"></i> Thông tin cơ bản</h4>
                                    
                                    <div class="form-group">
                                        <label for="SV_MASV">Mã sinh viên</label>
                                        <input type="text" class="form-control" id="SV_MASV" name="SV_MASV"
                                            value="<?php echo $student['SV_MASV']; ?>" readonly 
                                            data-toggle="tooltip" data-placement="top" title="Mã sinh viên không thể thay đổi">
                                    </div>
                                    <div class="form-group">
                                        <label for="SV_HOSV">Họ</label>
                                        <input type="text" class="form-control" id="SV_HOSV" name="SV_HOSV"
                                            value="<?php echo $student['SV_HOSV']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="SV_TENSV">Tên</label>
                                        <input type="text" class="form-control" id="SV_TENSV" name="SV_TENSV"
                                            value="<?php echo $student['SV_TENSV']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="SV_EMAIL">Email</label>
                                        <input type="email" class="form-control" id="SV_EMAIL" name="SV_EMAIL"
                                            value="<?php echo $student['SV_EMAIL']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="SV_SDT">Số điện thoại</label>
                                        <input type="text" class="form-control" id="SV_SDT" name="SV_SDT"
                                            value="<?php echo $student['SV_SDT']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-section">
                                    <h4><i class="fas fa-graduation-cap"></i> Thông tin học vấn</h4>
                                    
                                    <div class="form-group">
                                        <label for="LOP_MA">Mã lớp</label>
                                        <input type="text" class="form-control" id="LOP_MA" name="LOP_MA"
                                            value="<?php echo $class_info['LOP_MA']; ?>" readonly 
                                            data-toggle="tooltip" data-placement="top" title="Nhấn để xem thông tin lớp">
                                    </div>
                                    <div class="form-group">
                                        <label for="LOP_TEN">Tên lớp</label>
                                        <input type="text" class="form-control" id="LOP_TEN" name="LOP_TEN"
                                            value="<?php echo $class_info['LOP_TEN']; ?>" readonly
                                            data-toggle="tooltip" data-placement="top" title="Nhấn để xem thông tin lớp">
                                    </div>
                                    <div class="form-group">
                                        <label for="DV_TENDV">Tên khoa</label>
                                        <input type="text" class="form-control" id="DV_TENDV" name="DV_TENDV"
                                            value="<?php echo $class_info['DV_TENDV']; ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="KH_NAM">Khóa học</label>
                                        <input type="text" class="form-control" id="KH_NAM" name="KH_NAM"
                                            value="<?php echo $class_info['KH_NAM']; ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="LOP_LOAICTDT">Loại chương trình đào tạo</label>
                                        <input type="text" class="form-control" id="LOP_LOAICTDT" name="LOP_LOAICTDT"
                                            value="<?php echo $class_info['LOP_LOAICTDT']; ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-section">
                            <h4><i class="fas fa-address-card"></i> Thông tin bổ sung</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_NGAYSINH">Ngày sinh</label>
                                        <input type="date" class="form-control" id="SV_NGAYSINH" name="SV_NGAYSINH"
                                            value="<?php echo isset($student['SV_NGAYSINH']) ? $student['SV_NGAYSINH'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="SV_GIOITINH">Giới tính</label>
                                        <select class="form-control" id="SV_GIOITINH" name="SV_GIOITINH">
                                            <option value="0" <?php echo (isset($student['SV_GIOITINH']) && $student['SV_GIOITINH'] == 0) ? 'selected' : ''; ?>>Nam</option>
                                            <option value="1" <?php echo (isset($student['SV_GIOITINH']) && $student['SV_GIOITINH'] == 1) ? 'selected' : ''; ?>>Nữ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="SV_DIACHI">Địa chỉ</label>
                                <textarea class="form-control" id="SV_DIACHI" name="SV_DIACHI" rows="3"><?php echo isset($student['SV_DIACHI']) ? $student['SV_DIACHI'] : ''; ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg mt-3">
                            <i class="fas fa-save mr-1"></i> Cập nhật thông tin
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thông tin lớp -->
    <div class="modal fade" id="classInfoModal" tabindex="-1" role="dialog" aria-labelledby="classInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="classInfoModalLabel">
                        <i class="fas fa-info-circle mr-2"></i>Thông tin lớp
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Mã lớp:</strong> <?php echo $class_info['LOP_MA']; ?></p>
                    <p><strong>Tên lớp:</strong> <?php echo $class_info['LOP_TEN']; ?></p>
                    <p><strong>Tên khoa:</strong> <?php echo $class_info['DV_TENDV']; ?></p>
                    <p><strong>Khóa học:</strong> <?php echo $class_info['KH_NAM']; ?></p>
                    <p><strong>Loại chương trình đào tạo:</strong> <?php echo $class_info['LOP_LOAICTDT']; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/student/manage_profile.js"></script>
</body>

</html>