<?php
include '../../include/session.php';
checkTeacherRole();
?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'giang_vien') {
    header("Location: ../login.php");
    exit();
}

include '../../include/connect.php';

// Lấy thông tin giảng viên từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM giang_vien WHERE GV_MAGV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Lấy thông tin khoa
$sql = "SELECT DV_TENDV FROM khoa WHERE DV_MADV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $teacher['DV_MADV']);
$stmt->execute();
$result = $stmt->get_result();
$department = $result->fetch_assoc();

// Lấy danh sách các đề tài tham gia hướng dẫn
$sql = "SELECT DT_MADT, DT_TENDT FROM de_tai_nghien_cuu WHERE GV_MAGV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

// Lấy danh sách các hội đồng tham gia
$sql = "SELECT HD_MAHD, HD_TENHD FROM hoi_dong WHERE GV_MAGV = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$councils = [];
while ($row = $result->fetch_assoc()) {
    $councils[] = $row;
}
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
    <?php include '../../include/teacher_sidebar.php'; ?>

    <div class="container-fluid content">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Quản lý hồ sơ</h1>
                <form action="update_profile.php" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="GV_MAGV">Mã giảng viên</label>
                                <input type="text" class="form-control" id="GV_MAGV" name="GV_MAGV"
                                    value="<?php echo $teacher['GV_MAGV']; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="GV_HOGV">Họ</label>
                                <input type="text" class="form-control" id="GV_HOGV" name="GV_HOGV"
                                    value="<?php echo $teacher['GV_HOGV']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_TENGV">Tên</label>
                                <input type="text" class="form-control" id="GV_TENGV" name="GV_TENGV"
                                    value="<?php echo $teacher['GV_TENGV']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_EMAIL">Email</label>
                                <input type="email" class="form-control" id="GV_EMAIL" name="GV_EMAIL"
                                    value="<?php echo $teacher['GV_EMAIL']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_SDT">Số điện thoại</label>
                                <input type="text" class="form-control" id="GV_SDT" name="GV_SDT"
                                    value="<?php echo $teacher['GV_SDT']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_DIACHI">Địa chỉ</label>
                                <input type="text" class="form-control" id="GV_DIACHI" name="GV_DIACHI"
                                    value="<?php echo $teacher['GV_DIACHI']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_NGAYSINH">Ngày sinh</label>
                                <input type="date" class="form-control" id="GV_NGAYSINH" name="GV_NGAYSINH"
                                    value="<?php echo $teacher['GV_NGAYSINH']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_GIOITINH">Giới tính</label>
                                <select class="form-control" id="GV_GIOITINH" name="GV_GIOITINH" required>
                                    <option value="Nam" <?php if ($teacher['GV_GIOITINH'] == 'Nam') echo 'selected'; ?>>Nam</option>
                                    <option value="Nữ" <?php if ($teacher['GV_GIOITINH'] == 'Nữ') echo 'selected'; ?>>Nữ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="GV_TRINHDO">Trình độ</label>
                                <input type="text" class="form-control" id="GV_TRINHDO" name="GV_TRINHDO"
                                    value="<?php echo $teacher['GV_TRINHDO']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_CHUYENMON">Chuyên môn</label>
                                <input type="text" class="form-control" id="GV_CHUYENMON" name="GV_CHUYENMON"
                                    value="<?php echo $teacher['GV_CHUYENMON']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_HOCVI">Học vị</label>
                                <input type="text" class="form-control" id="GV_HOCVI" name="GV_HOCVI"
                                    value="<?php echo $teacher['GV_HOCVI']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_HOCHAM">Học hàm</label>
                                <input type="text" class="form-control" id="GV_HOCHAM" name="GV_HOCHAM"
                                    value="<?php echo $teacher['GV_HOCHAM']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="GV_NGAYVAOLAM">Ngày vào làm</label>
                                <input type="date" class="form-control" id="GV_NGAYVAOLAM" name="GV_NGAYVAOLAM"
                                    value="<?php echo $teacher['GV_NGAYVAOLAM']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="DV_TENDV">Tên khoa</label>
                                <input type="text" class="form-control" id="DV_TENDV" name="DV_TENDV"
                                    value="<?php echo $department['DV_TENDV']; ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Cập nhật</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="projectsModal" tabindex="-1" role="dialog" aria-labelledby="projectsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectsModalLabel">Danh sách đề tài tham gia hướng dẫn</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul>
                        <?php foreach ($projects as $project): ?>
                            <li><?php echo $project['DT_MADT'] . ' - ' . $project['DT_TENDT']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="councilsModal" tabindex="-1" role="dialog" aria-labelledby="councilsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="councilsModalLabel">Danh sách hội đồng tham gia</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul>
                        <?php foreach ($councils as $council): ?>
                            <li><?php echo $council['HD_MAHD'] . ' - ' . $council['HD_TENHD']; ?></li>
                        <?php endforeach; ?>
                    </ul>
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