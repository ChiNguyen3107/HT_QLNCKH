<?php
include '../../../include/session.php';
checkAdminRole();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>
    <?php include '../../../include/connect.php'; ?>

    <div class="container-fluid content">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Quản lý người dùng</h1>

                <!-- Tabs -->
                <ul class="nav nav-tabs mt-4" id="userTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="students-tab" data-toggle="tab" href="#students">Quản lý sinh
                            viên</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="teachers-tab" data-toggle="tab" href="#teachers">Quản lý giảng viên</a>
                    </li>
                </ul>

                <div class="tab-content mt-4">
                    <!-- Danh sách sinh viên -->
                    <div class="tab-pane fade show active" id="students">
                        <h2>Danh sách sinh viên</h2>
                        <div class="table-container">
                            <table id="studentsTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Họ</th>
                                        <th>Tên</th>
                                        <th>Email</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT SV_MASV, SV_HOSV, SV_TENSV, SV_EMAIL FROM sinh_vien";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['SV_MASV']}</td>
                                                    <td>{$row['SV_HOSV']}</td>
                                                    <td>{$row['SV_TENSV']}</td>
                                                    <td>{$row['SV_EMAIL']}</td>
                                                    <td>
                                                        <button class='btn btn-warning btn-sm editBtn' data-id='{$row['SV_MASV']}'>Sửa</button>
                                                        <button class='btn btn-danger btn-sm deleteBtn' data-id='{$row['SV_MASV']}'>Xóa</button>
                                                    </td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5'>Không có dữ liệu</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Danh sách giảng viên -->
                    <div class="tab-pane fade" id="teachers">
                        <h2>Danh sách giảng viên</h2>
                        <div class="table-container">
                            <table id="teachersTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã GV</th>
                                        <th>Họ</th>
                                        <th>Tên</th>
                                        <th>Email</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT GV_MAGV, GV_HOGV, GV_TENGV, GV_EMAIL FROM giang_vien";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['GV_MAGV']}</td>
                                                    <td>{$row['GV_HOGV']}</td>
                                                    <td>{$row['GV_TENGV']}</td>
                                                    <td>{$row['GV_EMAIL']}</td>
                                                    <td>
                                                        <button class='btn btn-warning btn-sm editBtn' data-id='{$row['GV_MAGV']}'>Sửa</button>
                                                        <button class='btn btn-danger btn-sm deleteBtn' data-id='{$row['GV_MAGV']}'>Xóa</button>
                                                    </td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5'>Không có dữ liệu</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                    <h5 class="modal-title" id="editModalLabel">Chỉnh sửa thông tin người dùng</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <div class="form-group">
                            <label for="editId">Mã người dùng</label>
                            <input type="text" class="form-control" id="editId" name="editId" readonly>
                        </div>
                        <div class="form-group">
                            <label for="editFirstName">Họ</label>
                            <input type="text" class="form-control" id="editFirstName" name="editFirstName" required>
                        </div>
                        <div class="form-group">
                            <label for="editLastName">Tên</label>
                            <input type="text" class="form-control" id="editLastName" name="editLastName" required>
                        </div>
                        <div class="form-group">
                            <label for="editEmail">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="editEmail" required>
                        </div>
                        <div class="form-group">
                            <label for="editPhone">Số điện thoại</label>
                            <input type="text" class="form-control" id="editPhone" name="editPhone" required>
                        </div>
                        <div class="form-group">
                            <label for="editAddress">Địa chỉ</label>
                            <input type="text" class="form-control" id="editAddress" name="editAddress" required>
                        </div>
                        <div class="form-group">
                            <label for="editBirthDate">Ngày sinh</label>
                            <input type="date" class="form-control" id="editBirthDate" name="editBirthDate" required>
                        </div>
                        <div class="form-group">
                            <label for="editGender">Giới tính</label>
                            <select class="form-control" id="editGender" name="editGender" required>
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editClass">Mã lớp</label>
                            <input type="text" class="form-control" id="editClass" name="editClass" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
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
                    <h5 class="modal-title">Xác nhận xóa</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa người dùng này không?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Xóa</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>


    <script>
        $(document).ready(function () {
            $('#studentsTable, #teachersTable').DataTable({
                "paging": true,  // Phân trang
                "ordering": true, // Cho phép sắp xếp
                "info": false,    // Ẩn thông tin tổng số dòng
                "searching": true, // Bật tìm kiếm
                "pageLength": 10,
                "lengthMenu": [5, 10, 25, 50, 100],
                "language": {
                    "search": "Tìm kiếm:",
                    "lengthMenu": "Hiển thị _MENU_ dòng",
                    "paginate": {
                        "first": "Đầu",
                        "last": "Cuối",
                        "next": "Tiếp",
                        "previous": "Trước"
                    }
                }
            });

            $('.editBtn').click(function () {
                let id = $(this).data('id');
                $.ajax({
                    url: 'get_user.php',
                    type: 'GET',
                    data: { id: id },
                    success: function (response) {
                        console.log("Phản hồi từ máy chủ:", response);
                        if (typeof response === 'string') {
                            try {
                                let user = JSON.parse(response);
                                if (user.error) {
                                    alert(user.error);
                                } else {
                                    $('#editId').val(user.SV_MASV || user.GV_MAGV);
                                    $('#editFirstName').val(user.SV_HOSV || user.GV_HOGV);
                                    $('#editLastName').val(user.SV_TENSV || user.GV_TENGV);
                                    $('#editEmail').val(user.SV_EMAIL || user.GV_EMAIL);
                                    $('#editPhone').val(user.SV_SDT || user.GV_SDT);
                                    $('#editAddress').val(user.SV_DIACHI || user.GV_DIACHI);
                                    $('#editBirthDate').val(user.SV_NGAYSINH || user.GV_NGAYSINH);
                                    // Chuyển đổi giới tính từ 0/1 thành Nam/Nữ
                                    $('#editGender').val(user.SV_GIOITINH === 0 ? 'Nam' : 'Nữ');
                                    $('#editClass').val(user.LOP_MA || '');
                                    $('#editModal').modal('show');
                                }
                            } catch (e) {
                                console.error("Lỗi phân tích JSON: ", e);
                                console.error("Phản hồi từ máy chủ: ", response);
                                alert("Đã xảy ra lỗi khi phân tích phản hồi từ máy chủ.");
                            }
                        } else if (typeof response === 'object') {
                            let user = response;
                            $('#editId').val(user.SV_MASV || user.GV_MAGV);
                            $('#editFirstName').val(user.SV_HOSV || user.GV_HOGV);
                            $('#editLastName').val(user.SV_TENSV || user.GV_TENGV);
                            $('#editEmail').val(user.SV_EMAIL || user.GV_EMAIL);
                            $('#editPhone').val(user.SV_SDT || user.GV_SDT);
                            $('#editAddress').val(user.SV_DIACHI || user.GV_DIACHI);
                            $('#editBirthDate').val(user.SV_NGAYSINH || user.GV_NGAYSINH);
                            // Chuyển đổi giới tính từ 0/1 thành Nam/Nữ
                            $('#editGender').val(user.SV_GIOITINH === 0 ? 'Nam' : 'Nữ');
                            $('#editClass').val(user.LOP_MA || '');
                            $('#editModal').modal('show');
                        } else {
                            console.error("Phản hồi không phải là chuỗi hoặc object:", response);
                            alert("Phản hồi từ máy chủ không hợp lệ.");
                        }
                    },
                    error: function (xhr, status, error) {
                        alert("Đã xảy ra lỗi: " + error);
                    }
                });
            });

            $('.deleteBtn').click(function () {
                let id = $(this).data('id');
                $('#confirmDelete').attr('href', "delete_user.php?id=" + id);
                $('#deleteModal').modal('show');
            });

            $('#editForm').submit(function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'update_user.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        location.reload();
                    },
                    error: function (xhr, status, error) {
                        alert("Đã xảy ra lỗi: " + error);
                    }
                });
            });
        });
    </script>
</body>

</html>