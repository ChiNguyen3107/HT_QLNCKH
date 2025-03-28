
<?php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thông tin sinh viên từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$sql = "SELECT SV_MASV, SV_HOSV, SV_TENSV, SV_EMAIL, SV_SDT, LOP_MA FROM sinh_vien WHERE SV_MASV = ?";
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
    <title>Quản lý hồ sơ</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 220px;
            height: 100%;
            background-color: #f8f9fa;
            padding: 15px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            border-right: 2px solid #ddd;
        }

        .content {
            margin-left: 240px;
            /* Đặt khoảng cách để không bị đè lên sidebar */
            padding: 20px;
        }

        .form-group label {
            font-weight: bold;
        }

        .form-control[readonly] {
            background-color: #e9ecef;
        }
    </style>
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="container-fluid content">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Quản lý hồ sơ</h1>
                <form action="update_profile.php" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="SV_MASV">Mã sinh viên</label>
                                <input type="text" class="form-control" id="SV_MASV" name="SV_MASV"
                                    value="<?php echo $student['SV_MASV']; ?>" readonly>
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

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="LOP_MA">Mã lớp</label>
                                <input type="text" class="form-control" id="LOP_MA" name="LOP_MA"
                                    value="<?php echo $class_info['LOP_MA']; ?>" readonly data-toggle="modal" data-target="#classInfoModal">
                            </div>
                            <div class="form-group">
                                <label for="LOP_TEN">Tên lớp</label>
                                <input type="text" class="form-control" id="LOP_TEN" name="LOP_TEN"
                                    value="<?php echo $class_info['LOP_TEN']; ?>" readonly data-toggle="modal" data-target="#classInfoModal">
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
                    <button type="submit" class="btn btn-primary mt-3">Cập nhật</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="classInfoModal" tabindex="-1" role="dialog" aria-labelledby="classInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="classInfoModalLabel">Thông tin lớp</h5>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>