<?php
include '../../include/session.php';
checkStudentRole();
?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'sinh_vien') {
    header("Location: ../login.php");
    exit();
}

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thông tin đề tài nghiên cứu của sinh viên từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$sql = "SELECT de_tai_nghien_cuu.DT_MADT, de_tai_nghien_cuu.DT_TENDT, de_tai_nghien_cuu.DT_MOTA, de_tai_nghien_cuu.DT_TRANGTHAI 
        FROM de_tai_nghien_cuu 
        JOIN chi_tiet_tham_gia ON de_tai_nghien_cuu.DT_MADT = chi_tiet_tham_gia.DT_MADT 
        WHERE chi_tiet_tham_gia.SV_MASV = ?";
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đề tài nghiên cứu</title>
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
            margin-left: 240px; /* Đặt khoảng cách để không bị đè lên sidebar */
            padding: 20px;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php include '../../include/student_sidebar.php'; ?>

    <div class="container-fluid content">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Quản lý đề tài nghiên cứu</h1>
                <div class="table-container">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Mã đề tài</th>
                                <th>Tên đề tài</th>
                                <th>Mô tả</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($projects) > 0): ?>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?php echo $project['DT_MADT']; ?></td>
                                        <td><?php echo $project['DT_TENDT']; ?></td>
                                        <td><?php echo $project['DT_MOTA']; ?></td>
                                        <td><?php echo $project['DT_TRANGTHAI']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">Không có đề tài nghiên cứu nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>