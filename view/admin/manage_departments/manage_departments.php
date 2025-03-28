
<?php
include '../../../include/session.php';
checkAdminRole();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý các khoa</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        .modal-dialog {
            max-width: 80%;
        }

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-backdrop {
            z-index: 1040 !important;
        }

        .modal {
            z-index: 1050 !important;
        }

        .modal-dialog {
            margin: 2rem auto;
        }
    </style>
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>
    <?php include '../../../include/connect.php'; ?>

    <div class="container-fluid content">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Quản lý các khoa</h1>
                <div class="table-container mt-4">
                    <table id="departmentsTable" class="table table-bordered">
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
                                                    <button class='btn btn-warning btn-sm editBtn' data-id='{$row['DV_MADV']}'>Sửa</button>
                                                    <button class='btn btn-danger btn-sm deleteBtn' data-id='{$row['DV_MADV']}'>Xóa</button>
                                                </td>
                                                <td>
                                                    <button class='btn btn-info btn-sm viewClassesBtn' data-id='{$row['DV_MADV']}'>Xem lớp học</button>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>Không có dữ liệu</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>Lỗi truy vấn: " . $conn->error . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
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
                    <h5 class="modal-title" id="editModalLabel">Chỉnh sửa thông tin khoa</h5>
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
                    Bạn có chắc chắn muốn xóa khoa này không?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Xóa</a>
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
                    <h5 class="modal-title" id="viewClassesModalLabel">Danh sách lớp học</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="selectCourse">Vui lòng chọn khóa học</label>
                        <select class="form-control" id="selectCourse">
                            <!-- Các tùy chọn khóa học sẽ được tải động -->
                        </select>
                    </div>
                    <div class="table-container mt-4">
                        <table id="classesTable" class="table table-bordered">
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
            </div>
        </div>
    </div>

    <!-- Modal xem danh sách sinh viên -->
    <div class="modal fade" id="viewStudentsModal" tabindex="-1" role="dialog" aria-labelledby="viewStudentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStudentsModalLabel">Danh sách sinh viên</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-container mt-4">
                        <table id="studentsTable" class="table table-bordered">
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
                            Không có sinh viên nào trong lớp này.
                        </div>
                    </div>
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
            var table = $('#departmentsTable').DataTable({
                "paging": true,
                "ordering": true,
                "info": false,
                "searching": true,
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

            function attachEventHandlers() {
                $('.editBtn').off('click').on('click', function () {
                    let id = $(this).data('id');
                    $.ajax({
                        url: 'get_department.php',
                        type: 'GET',
                        data: { id: id },
                        dataType: 'json',
                        success: function (department) {
                            if (department.error) {
                                alert(department.error);
                            } else {
                                $('#editId').val(department.DV_MADV);
                                $('#editName').val(department.DV_TENDV);
                                $('#editModal').modal('show');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("Lỗi AJAX:", error);
                            console.error("Phản hồi từ máy chủ:", xhr.responseText);
                            alert("Đã xảy ra lỗi khi lấy thông tin khoa.");
                        }
                    });
                });

                $('.deleteBtn').off('click').on('click', function () {
                    let id = $(this).data('id');
                    $('#confirmDelete').attr('href', "delete_department.php?id=" + id);
                    $('#deleteModal').modal('show');
                });

                $('.viewClassesBtn').off('click').on('click', function () {
                    let id = $(this).data('id');
                    $('#viewClassesModal').data('id', id).modal('show');
                    loadCourses();
                });
            }

            table.on('draw', function () {
                attachEventHandlers();
            });

            attachEventHandlers();

            $('#viewClassesModal').on('show.bs.modal', function () {
                $('#selectCourse').val('');
                $('#classesTable tbody').empty();
                if ($.fn.DataTable.isDataTable('#classesTable')) {
                    $('#classesTable').DataTable().destroy();
                }
            });

            $('#selectCourse').change(function () {
                loadClasses();
            });

            $('#editForm').submit(function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'update_department.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (result) {
                        if (result.success) {
                            alert(result.success);
                            location.reload();
                        } else {
                            alert(result.error);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Lỗi AJAX:", error);
                        console.error("Phản hồi từ máy chủ:", xhr.responseText);
                        alert("Đã xảy ra lỗi khi cập nhật khoa.");
                    }
                });
            });

            function loadCourses() {
                $.ajax({
                    url: 'get_courses.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function (courses) {
                        let options = '<option value="">Chọn khóa học</option>';
                        $.each(courses, function (index, course) {
                            options += `<option value="${course.KH_NAM}">${course.KH_NAM}</option>`;
                        });
                        $('#selectCourse').html(options);
                    },
                    error: function (xhr, status, error) {
                        console.error("Lỗi AJAX:", error);
                        console.error("Phản hồi từ máy chủ:", xhr.responseText);
                        alert("Đã xảy ra lỗi khi tải danh sách khóa học.");
                    }
                });
            }

            function loadClasses() {
                let departmentId = $('#viewClassesModal').data('id');
                let courseId = $('#selectCourse').val();
                $.ajax({
                    url: 'get_classes.php',
                    type: 'GET',
                    data: { departmentId: departmentId, courseId: courseId },
                    dataType: 'json',
                    success: function (classes) {
                        let rows = '';
                        $.each(classes, function (index, classInfo) {
                            rows += `<tr>
                                    <td>${classInfo.LOP_MA}</td>
                                    <td>${classInfo.LOP_TEN}</td>
                                    <td>${classInfo.KH_NAM}</td>
                                    <td>
                                        <button class='btn btn-info btn-sm viewStudentsBtn' data-id='${classInfo.LOP_MA}'>Xem danh sách sinh viên</button>
                                    </td>
                                </tr>`;
                        });
                        $('#classesTable tbody').html(rows);
                        $('#classesTable').DataTable({
                            "paging": true,
                            "ordering": true,
                            "info": false,
                            "searching": true,
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
                    },
                    error: function (xhr, status, error) {
                        console.error("Lỗi AJAX:", error);
                        console.error("Phản hồi từ máy chủ:", xhr.responseText);
                        alert("Đã xảy ra lỗi khi tải danh sách lớp học.");
                    }
                });
            }

            $(document).on('click', '.viewStudentsBtn', function () {
                let classId = $(this).data('id');
                $('#viewStudentsModal').data('id', classId).modal('show');
                loadStudents(classId);
            });

            $('#viewStudentsModal').on('show.bs.modal', function () {
                if ($.fn.DataTable.isDataTable('#studentsTable')) {
                    $('#studentsTable').DataTable().destroy();
                }
            });

            function loadStudents(classId) {
                $.ajax({
                    url: 'get_students.php',
                    type: 'GET',
                    data: { classId: classId },
                    dataType: 'json',
                    success: function (students) {
                        let rows = '';
                        if (students.length === 0) {
                            $('#noStudentsMessage').show();
                            $('#studentsTable').hide();
                        } else {
                            $('#noStudentsMessage').hide();
                            $('#studentsTable').show();
                            $.each(students, function (index, student) {
                                rows += `<tr>
                                        <td>${student.SV_MASV}</td>
                                        <td>${student.SV_HOTEN}</td>
                                        <td>${student.SV_EMAIL}</td>
                                        <td>${student.SV_SDT}</td>
                                    </tr>`;
                            });
                            $('#studentsTable tbody').html(rows);
                            $('#studentsTable').DataTable({
                                "paging": true,
                                "ordering": true,
                                "info": false,
                                "searching": true,
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
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Lỗi AJAX:", error);
                        console.error("Phản hồi từ máy chủ:", xhr.responseText);
                        alert("Đã xảy ra lỗi khi tải danh sách sinh viên.");
                    }
                });
            }
        });
    </script>
</body>

</html>