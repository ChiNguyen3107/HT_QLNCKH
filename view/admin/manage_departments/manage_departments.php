<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_departments\manage_departments.php
include '../../../include/session.php';
checkAdminRole();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý các khoa</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/admin/manage_departments.css" rel="stylesheet">
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>
    <?php include '../../../include/connect.php'; ?>

    <div class="container-fluid content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/NLNganh/view/admin/admin_dashboard.php"><i
                            class="fas fa-home"></i> Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý khoa</li>
            </ol>
        </nav>

        <!-- Header Section -->
        <div class="header-section">
            <h1 class="page-header"><i class="fas fa-university mr-2"></i>Quản lý các khoa</h1>
            <button class="btn btn-success" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus mr-1"></i> Thêm khoa mới
            </button>
        </div>

        <!-- Main Content -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list mr-2"></i>Danh sách khoa</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="departmentsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã khoa</th>
                                <th>Tên khoa</th>
                                <th>Hành động</th>
                                <th>Xem lớp học</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT DV_MADV, DV_TENDV FROM khoa";
                            $result = $conn->query($sql);
                            if ($result) {
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>{$row['DV_MADV']}</td>
                                                <td>{$row['DV_TENDV']}</td>
                                                <td>
                                                    <div class='action-buttons'>
                                                        <button class='btn btn-warning btn-sm editBtn' data-id='{$row['DV_MADV']}'>
                                                            <i class='fas fa-edit'></i> Sửa
                                                        </button>
                                                        <button class='btn btn-danger btn-sm deleteBtn' data-id='{$row['DV_MADV']}'>
                                                            <i class='fas fa-trash-alt'></i> Xóa
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class='btn btn-info btn-sm viewClassesBtn' data-id='{$row['DV_MADV']}'>
                                                        <i class='fas fa-users'></i> Xem lớp học
                                                    </button>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='no-data'>Không có dữ liệu</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-danger'>Lỗi truy vấn: " . $conn->error . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm khoa mới -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel"><i class="fas fa-plus-circle mr-2"></i>Thêm khoa mới</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addForm">
                        <div class="form-group">
                            <label for="addId">Mã khoa</label>
                            <input type="text" class="form-control" id="addId" name="addId" required>
                            <small class="form-text text-muted">Nhập mã khoa (ví dụ: CNTT, KHTN,...)</small>
                        </div>
                        <div class="form-group">
                            <label for="addName">Tên khoa</label>
                            <input type="text" class="form-control" id="addName" name="addName" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i> Hủy
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i> Lưu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit mr-2"></i>Chỉnh sửa thông tin khoa
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <div class="form-group">
                            <label for="editId">Mã khoa</label>
                            <input type="text" class="form-control" id="editId" name="editId" readonly>
                        </div>
                        <div class="form-group">
                            <label for="editName">Tên khoa</label>
                            <input type="text" class="form-control" id="editName" name="editName" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i> Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Cập nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Xác nhận xóa
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa khoa này không?</p>
                    <p class="mb-0 font-italic text-danger">Lưu ý: Hành động này không thể hoàn tác và sẽ xóa tất cả các
                        lớp học và dữ liệu liên quan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">
                        <i class="fas fa-trash-alt mr-1"></i> Xác nhận xóa
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xem lớp học -->
    <div class="modal fade" id="viewClassesModal" tabindex="-1" role="dialog" aria-labelledby="viewClassesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewClassesModalLabel">
                        <i class="fas fa-users mr-2"></i>Danh sách lớp học
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="selectCourse"><i class="fas fa-filter mr-1"></i>Chọn khóa học:</label>
                        <select class="form-control" id="selectCourse">
                            <!-- Các tùy chọn khóa học sẽ được tải động -->
                        </select>
                    </div>
                    <div class="table-responsive mt-4">
                        <table id="classesTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã lớp</th>
                                    <th>Tên lớp</th>
                                    <th>Khóa học</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Danh sách lớp học sẽ được tải động -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xem danh sách sinh viên -->
    <div class="modal fade" id="viewStudentsModal" tabindex="-1" role="dialog" aria-labelledby="viewStudentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStudentsModalLabel">
                        <i class="fas fa-user-graduate mr-2"></i>Danh sách sinh viên
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mt-4">
                        <table id="studentsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã sinh viên</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Số điện thoại</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Danh sách sinh viên sẽ được tải động -->
                            </tbody>
                        </table>
                        <div id="noStudentsMessage" class="alert alert-info" style="display: none;">
                            <i class="fas fa-info-circle mr-2"></i>Không có sinh viên nào trong lớp này.
                        </div>
                    </div>
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
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/admin/manage_departments.js"></script>
</body>

</html>