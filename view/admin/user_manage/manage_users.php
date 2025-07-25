<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\user_manage\manage_users.php
include '../../../include/session.php';
checkAdminRole();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">

    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet>

    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/admin/manage_users.css" rel="stylesheet">

    <style>
        /* CSS để đảm bảo hiển thị phần dành cho giảng viên */
        .teacher-only {
            display: none; /* Mặc định ẩn */
        }

        .teacher-mode .teacher-only {
            display: block !important;
        }

        .student-mode .student-only {
            display: block !important;
        }
    </style>
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>
    <?php include '../../../include/connect.php'; ?>

    <div class="container-fluid content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/NLNganh/view/admin/admin_dashboard.php"><i
                            class="fas fa-home mr-1"></i> Trang chủ</a></li>
                <li class="breadcrumb-item active" aria-current="page">Quản lý người dùng</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4"><i class="fas fa-users mr-2"></i>Quản lý người dùng</h1>

                <!-- Tabs -->
                <ul class="nav nav-tabs mt-4" id="userTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="students-tab" data-toggle="tab" href="#students">
                            <i class="fas fa-user-graduate mr-1"></i> Quản lý sinh viên
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="teachers-tab" data-toggle="tab" href="#teachers">
                            <i class="fas fa-chalkboard-teacher mr-1"></i> Quản lý giảng viên
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-4">
                    <!-- Danh sách sinh viên -->
                    <div class="tab-pane fade show active" id="students">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2><i class="fas fa-list mr-2"></i>Danh sách sinh viên</h2>
                            <button id="addStudentBtn" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Thêm sinh viên mới
                            </button>
                        </div>
                        <div class="table-container">
                            <table id="studentsTable" class="table table-bordered table-hover">
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
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['SV_MASV']}</td>
                                                    <td>{$row['SV_HOSV']}</td>
                                                    <td>{$row['SV_TENSV']}</td>
                                                    <td>{$row['SV_EMAIL']}</td>
                                                    <td>
                                                        <button class='btn btn-warning btn-sm editBtn' data-id='{$row['SV_MASV']}'>
                                                            <i class='fas fa-edit mr-1'></i> Sửa
                                                        </button>
                                                        <button class='btn btn-danger btn-sm deleteBtn' data-id='{$row['SV_MASV']}'>
                                                            <i class='fas fa-trash-alt mr-1'></i> Xóa
                                                        </button>
                                                    </td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>Không có dữ liệu sinh viên</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Danh sách giảng viên -->
                    <div class="tab-pane fade" id="teachers">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2><i class="fas fa-list mr-2"></i>Danh sách giảng viên</h2>
                            <button id="addTeacherBtn" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Thêm giảng viên mới
                            </button>
                        </div>
                        <div class="table-container">
                            <table id="teachersTable" class="table table-bordered table-hover">
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
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['GV_MAGV']}</td>
                                                    <td>{$row['GV_HOGV']}</td>
                                                    <td>{$row['GV_TENGV']}</td>
                                                    <td>{$row['GV_EMAIL']}</td>
                                                    <td>
                                                        <button class='btn btn-warning btn-sm editBtn' data-id='{$row['GV_MAGV']}'>
                                                            <i class='fas fa-edit mr-1'></i> Sửa
                                                        </button>
                                                        <button class='btn btn-danger btn-sm deleteBtn' data-id='{$row['GV_MAGV']}'>
                                                            <i class='fas fa-trash-alt mr-1'></i> Xóa
                                                        </button>
                                                    </td>
                                                  </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>Không có dữ liệu giảng viên</td></tr>";
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

    <!-- Modal thêm người dùng mới -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="fas fa-user-plus mr-2"></i>Thêm người dùng mới
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addForm">
                        <input type="hidden" id="addUserType" name="userType" value="student">

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="addId">Mã người dùng</label>
                                <input type="text" class="form-control" id="addId" name="addId" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="addEmail">Email</label>
                                <input type="email" class="form-control" id="addEmail" name="addEmail" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="addFirstName">Họ</label>
                                <input type="text" class="form-control" id="addFirstName" name="addFirstName" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="addLastName">Tên</label>
                                <input type="text" class="form-control" id="addLastName" name="addLastName" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="addPassword">Mật khẩu</label>
                                <input type="password" class="form-control" id="addPassword" name="addPassword" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="addPhone">Số điện thoại</label>
                                <input type="text" class="form-control" id="addPhone" name="addPhone">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="addGender">Giới tính</label>
                                <select class="form-control" id="addGender" name="addGender" required>
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group col-md-6 student-only">
                                <label for="addClass">Mã lớp</label>
                                <select class="form-control" id="addClass" name="addClass">
                                    <option value="">-- Chọn lớp --</option>
                                    <?php
                                    $sql = "SELECT LOP_MA, LOP_TEN FROM lop";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['LOP_MA']}'>{$row['LOP_MA']} - {$row['LOP_TEN']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group col-md-6 teacher-only" style="display: none;">
                                <label for="addDepartment">Khoa</label>
                                <select class="form-control" id="addDepartment" name="addDepartment">
                                    <option value="">-- Chọn khoa --</option>
                                    <?php
                                    $sql = "SELECT DV_MADV, DV_TENDV FROM khoa";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['DV_MADV']}'>{$row['DV_MADV']} - {$row['DV_TENDV']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times mr-1"></i> Hủy
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus-circle mr-1"></i> Thêm mới
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
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-user-edit mr-2"></i>Chỉnh sửa thông tin người dùng
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="userType" name="userType" value="">

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="editId">Mã người dùng</label>
                                <input type="text" class="form-control" id="editId" name="editId" readonly>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="editEmail">Email</label>
                                <input type="email" class="form-control" id="editEmail" name="editEmail" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="editFirstName">Họ</label>
                                <input type="text" class="form-control" id="editFirstName" name="editFirstName" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="editLastName">Tên</label>
                                <input type="text" class="form-control" id="editLastName" name="editLastName" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="editPhone">Số điện thoại</label>
                                <input type="text" class="form-control" id="editPhone" name="editPhone">
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="editGender">Giới tính</label>
                                <select class="form-control" id="editGender" name="editGender" required>
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="editBirthDate">Ngày sinh</label>
                                <input type="date" class="form-control" id="editBirthDate" name="editBirthDate">
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="editAddress">Địa chỉ</label>
                                <input type="text" class="form-control" id="editAddress" name="editAddress">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6 student-only">
                                <label for="editClass">Mã lớp</label>
                                <select class="form-control" id="editClass" name="editClass">
                                    <option value="">-- Chọn lớp --</option>
                                    <?php
                                    $sql = "SELECT LOP_MA, LOP_TEN FROM lop";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['LOP_MA']}'>{$row['LOP_MA']} - {$row['LOP_TEN']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group col-md-6 teacher-only">
                                <label for="editDepartment">Khoa</label>
                                <select class="form-control" id="editDepartment" name="editDepartment">
                                    <option value="">-- Chọn khoa --</option>
                                    <?php
                                    $sql = "SELECT DV_MADV, DV_TENDV FROM khoa";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['DV_MADV']}'>{$row['DV_MADV']} - {$row['DV_TENDV']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
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
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger mr-2"></i>Xác nhận xóa
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p id="deleteConfirmMessage">Bạn có chắc chắn muốn xóa người dùng này không?</p>
                    <p class="text-danger font-italic">Lưu ý: Hành động này không thể hoàn tác!</p>
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

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/admin/manage_users.js"></script>
</body>

</html>