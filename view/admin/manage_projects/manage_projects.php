<?php
include '../../../include/session.php';
checkAdminRole();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đề tài</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0;
            margin-left: 0;
            display: inline;
            border: 1px solid transparent;
            border-radius: 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            border: 1px solid #ddd;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            color: #fff !important;
            border: 1px solid #007bff;
            background-color: #007bff;
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #007bff), color-stop(100%, #0056b3));
            background: -webkit-linear-gradient(top, #007bff 0%, #0056b3 100%);
            background: -moz-linear-gradient(top, #007bff 0%, #0056b3 100%);
            background: -ms-linear-gradient(top, #007bff 0%, #0056b3 100%);
            background: -o-linear-gradient(top, #007bff 0%, #0056b3 100%);
            background: linear-gradient(to bottom, #007bff 0%, #0056b3 100%);
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

    <div class="container-fluid" style="margin-left: 220px;">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mt-4">Quản lý đề tài</h1>

                <!-- Tabs -->
                <ul class="nav nav-tabs mt-4" id="projectTabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending">Chờ duyệt</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="in-progress-tab" data-toggle="tab" href="#in-progress">Đang thực
                            hiện</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="completed-tab" data-toggle="tab" href="#completed">Đã hoàn thành</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="paused-tab" data-toggle="tab" href="#paused">Tạm dừng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cancelled-tab" data-toggle="tab" href="#cancelled">Đã hủy</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="processing-tab" data-toggle="tab" href="#processing">Đang xử lý</a>
                    </li>
                </ul>

                <div class="tab-content mt-4">
                    <!-- Danh sách đề tài chờ duyệt -->
                    <div class="tab-pane fade show active" id="pending">
                        <h2>Danh sách đề tài chờ duyệt</h2>
                        <div class="table-container">
                            <table id="pendingTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Chờ duyệt'";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['DT_MADT']}</td>
                                                    <td>{$row['DT_TENDT']}</td>
                                                    <td>{$row['DT_MOTA']}</td>
                                                    <td>{$row['DT_TRANGTHAI']}</td>
                                                    <td>
                                                        <a href='edit_project.php?id={$row['DT_MADT']}' class='btn btn-warning btn-sm'>Sửa</a>
                                                        <a href='delete_project.php?id={$row['DT_MADT']}' class='btn btn-danger btn-sm'>Xóa</a>
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

                    <!-- Danh sách đề tài đang thực hiện -->
                    <div class="tab-pane fade" id="in-progress">
                        <h2>Danh sách đề tài đang thực hiện</h2>
                        <div class="table-container">
                            <table id="inProgressTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đang thực hiện'";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['DT_MADT']}</td>
                                                    <td>{$row['DT_TENDT']}</td>
                                                    <td>{$row['DT_MOTA']}</td>
                                                    <td>{$row['DT_TRANGTHAI']}</td>
                                                    <td>
                                                        <a href='edit_project.php?id={$row['DT_MADT']}' class='btn btn-warning btn-sm'>Sửa</a>
                                                        <a href='delete_project.php?id={$row['DT_MADT']}' class='btn btn-danger btn-sm'>Xóa</a>
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

                    <!-- Danh sách đề tài đã hoàn thành -->
                    <div class="tab-pane fade" id="completed">
                        <h2>Danh sách đề tài đã hoàn thành</h2>
                        <div class="table-container">
                            <table id="completedTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hoàn thành'";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['DT_MADT']}</td>
                                                    <td>{$row['DT_TENDT']}</td>
                                                    <td>{$row['DT_MOTA']}</td>
                                                    <td>{$row['DT_TRANGTHAI']}</td>
                                                    <td>
                                                        <button class='btn btn-info btn-sm viewDetailsBtn' data-id='{$row['DT_MADT']}'>Xem chi tiết</button>
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

                    <!-- Danh sách đề tài tạm dừng -->
                    <div class="tab-pane fade" id="paused">
                        <h2>Danh sách đề tài tạm dừng</h2>
                        <div class="table-container">
                            <table id="pausedTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Tạm dừng'";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['DT_MADT']}</td>
                                                    <td>{$row['DT_TENDT']}</td>
                                                    <td>{$row['DT_MOTA']}</td>
                                                    <td>{$row['DT_TRANGTHAI']}</td>
                                                    <td>
                                                        <a href='edit_project.php?id={$row['DT_MADT']}' class='btn btn-warning btn-sm'>Sửa</a>
                                                        <a href='delete_project.php?id={$row['DT_MADT']}' class='btn btn-danger btn-sm'>Xóa</a>
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

                    <!-- Danh sách đề tài đã hủy -->
                    <div class="tab-pane fade" id="cancelled">
                        <h2>Danh sách đề tài đã hủy</h2>
                        <div class="table-container">
                            <table id="cancelledTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đã hủy'";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['DT_MADT']}</td>
                                                    <td>{$row['DT_TENDT']}</td>
                                                    <td>{$row['DT_MOTA']}</td>
                                                    <td>{$row['DT_TRANGTHAI']}</td>
                                                    <td>
                                                        <button class='btn btn-info btn-sm viewDetailsBtn' data-id='{$row['DT_MADT']}'>Xem chi tiết</button>
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

                    <!-- Danh sách đề tài đang xử lý -->
                    <div class="tab-pane fade" id="processing">
                        <h2>Danh sách đề tài đang xử lý</h2>
                        <div class="table-container">
                            <table id="processingTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã đề tài</th>
                                        <th>Tên đề tài</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT DT_MADT, DT_TENDT, DT_MOTA, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_TRANGTHAI = 'Đang xử lý'";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['DT_MADT']}</td>
                                                    <td>{$row['DT_TENDT']}</td>
                                                    <td>{$row['DT_MOTA']}</td>
                                                    <td>{$row['DT_TRANGTHAI']}</td>
                                                    <td>
                                                        <a href='edit_project.php?id={$row['DT_MADT']}' class='btn btn-warning btn-sm'>Sửa</a>
                                                        <a href='delete_project.php?id={$row['DT_MADT']}' class='btn btn-danger btn-sm'>Xóa</a>
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

    <!-- Modal xem chi tiết -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDetailsModalLabel">Chi tiết đề tài</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Nội dung chi tiết đề tài sẽ được tải động -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#pendingTable, #inProgressTable, #completedTable, #pausedTable, #cancelledTable, #processingTable').DataTable({
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

            $(document).on('click', '.viewDetailsBtn', function () {
                let projectId = $(this).data('id');
                $.ajax({
                    url: 'get_project_details.php',
                    type: 'GET',
                    data: { id: projectId },
                    success: function (data) {
                        $('#viewDetailsModal .modal-body').html(data);
                        $('#viewDetailsModal').modal('show');
                    },
                    error: function (xhr, status, error) {
                        console.error("Lỗi AJAX:", error);
                        console.error("Phản hồi từ máy chủ:", xhr.responseText);
                        alert("Đã xảy ra lỗi khi tải chi tiết đề tài.");
                    }
                });
            });
        });
    </script>
</body>

</html>