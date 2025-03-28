<?php


// Bao gồm file session.php để kiểm tra phiên đăng nhập và quyền truy cập
include '../../include/session.php';

// Kiểm tra quyền admin
checkAdminRole();

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển quản trị</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../include/admin_sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="container-fluid">
        <h1 class="mt-4">Bảng điều khiển</h1>

        <div class="card-deck mt-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quản lý người dùng</h5>
                    <p class="card-text">Xem và quản lý tất cả người dùng.</p>
                    <a href="../user_manage/manage_users.php" class="btn btn-primary">Đi tới</a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quản lý đề tài</h5>
                    <p class="card-text">Xem và quản lý tất cả các đề tài nghiên cứu.</p>
                    <a href="../manage_projects/manage_projects.php" class="btn btn-primary">Đi tới</a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Báo cáo</h5>
                    <p class="card-text">Xem các báo cáo và thống kê.</p>
                    <a href="../reports.php" class="btn btn-primary">Đi tới</a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Cài đặt</h5>
                    <p class="card-text">Cấu hình hệ thống và cài đặt.</p>
                    <a href="../settings.php" class="btn btn-primary">Đi tới</a>
                </div>
            </div>
        </div>

        <!-- Danh sách người dùng -->
        <div class="mt-4">
            <h2>Danh sách người dùng</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Mã SV</th>
                        <th>Họ</th>
                        <th>Tên</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include '../../include/connect.php';
                    $sql = "SELECT SV_MASV, SV_HOSV, SV_TENSV, SV_EMAIL FROM sinh_vien";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['SV_MASV']}</td>
                                    <td>{$row['SV_HOSV']}</td>
                                    <td>{$row['SV_TENSV']}</td>
                                    <td>{$row['SV_EMAIL']}</td>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>