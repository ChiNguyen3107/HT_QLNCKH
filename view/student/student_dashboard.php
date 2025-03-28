<?php
include '../../include/session.php';
checkStudentRole();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển sinh viên</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
   
</head>

<body>
    <?php include '../../include/student_sidebar.php'; ?>
    <?php include '../../include/connect.php'; ?>

    <div class="container-fluid content">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Bảng điều khiển sinh viên</h1>
                <div class="card-deck mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin cá nhân</h5>
                            <p class="card-text">Xem và cập nhật thông tin cá nhân.</p>
                            <a href="../student_manage/manage_profile.php" class="btn btn-primary">Đi tới</a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Đề tài nghiên cứu</h5>
                            <p class="card-text">Xem và quản lý các đề tài nghiên cứu.</p>
                            <a href="../student_manage/manage_projects.php" class="btn btn-primary">Đi tới</a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Báo cáo</h5>
                            <p class="card-text">Xem các báo cáo và thống kê.</p>
                            <a href="../student_manage/reports.php" class="btn btn-primary">Đi tới</a>
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
                            $sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_MOTA, dt.DT_TRANGTHAI 
                                    FROM de_tai_nghien_cuu dt
                                    JOIN chi_tiet_tham_gia ct ON dt.DT_MADT = ct.DT_MADT
                                    WHERE ct.SV_MASV = ?";
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