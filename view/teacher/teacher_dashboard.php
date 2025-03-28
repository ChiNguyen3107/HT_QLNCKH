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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển giảng viên</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body,
        html {
            overflow-x: hidden;
            /* Ngăn chặn thanh cuộn ngang */
        }

        .sidebar {
            width: 220px;
            height: 100vh;
            background-color: #f8f9fa;
            padding: 15px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            border-right: 2px solid #ddd;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            display: block;
            padding: 10px;
            border-radius: 5px;
        }

        .sidebar ul li a:hover {
            background-color: #007bff;
            color: #fff;
        }

        .container-fluid {
            margin-left: 230px;
            /* Tạo khoảng cách với sidebar */
            padding: 20px;
            max-width: calc(100vw - 230px);
            /* Đảm bảo không tràn ngang */
        }

        .card-deck .card {
            min-width: 250px;
        }
    </style>
</head>
<body>
    <?php include '../../include/teacher_sidebar.php'; ?>

    <div class="container-fluid content">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Bảng điều khiển giảng viên</h1>
                <div class="card-deck mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin cá nhân</h5>
                            <p class="card-text">Xem và cập nhật thông tin cá nhân.</p>
                            <a href="../teacher/manage_profile.php" class="btn btn-primary">Đi tới</a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Đề tài nghiên cứu</h5>
                            <p class="card-text">Xem và quản lý các đề tài nghiên cứu.</p>
                            <a href="../teacher/manage_projects.php" class="btn btn-primary">Đi tới</a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Báo cáo</h5>
                            <p class="card-text">Xem các báo cáo và thống kê.</p>
                            <a href="../teacher/reports.php" class="btn btn-primary">Đi tới</a>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h2>Danh sách đề tài</h2>
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
                            <?php
                            $sql = "SELECT DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE GV_MAGV = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['DT_MADT']}</td>
                                            <td>{$row['DT_TENDT']}</td>
                                            <td>{$row['DT_MOTA']}</td>
                                            <td>{$row['DT_TRANGTHAI']}</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>Không có dữ liệu</td></tr>";
                            }
                            ?>
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