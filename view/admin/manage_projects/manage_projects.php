<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\manage_projects.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập
include '../../../include/session.php';
checkAdminRole();
// Kết nối database
include '../../../include/connect.php';

// Thiết lập phân trang
$items_per_page = 10; // Số lượng đề tài trên mỗi trang
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Đếm tổng số đề tài
$count_sql = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu";
$count_result = $conn->query($count_sql);
$total_items = ($count_result) ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_items / $items_per_page);

// Kiểm tra tham số trạng thái để lọc tự động
if (isset($_GET['status'])) {
    $filter_status = $_GET['status'];
} else {
    $filter_status = '';
}

// Thêm điều kiện lọc vào câu truy vấn nếu có
$status_condition = '';
if (!empty($filter_status)) {
    $status_condition = " WHERE dt.DT_TRANGTHAI = '" . $conn->real_escape_string($filter_status) . "' ";
    
    // Cập nhật câu truy vấn đếm tổng số
    $count_sql = "SELECT COUNT(*) AS total FROM de_tai_nghien_cuu dt $status_condition";
    $count_result = $conn->query($count_sql);
    $total_items = ($count_result) ? $count_result->fetch_assoc()['total'] : 0;
    $total_pages = ceil($total_items / $items_per_page);
}

// Cập nhật câu truy vấn chính với điều kiện lọc
$sql = "SELECT 
            dt.DT_MADT, 
            dt.DT_TENDT, 
            dt.DT_TRANGTHAI,
            CONCAT(IFNULL(gv.GV_HOGV, ''), ' ', IFNULL(gv.GV_TENGV, '')) AS GV_HOTEN,
            IFNULL(ldt.LDT_TENLOAI, 'Chưa phân loại') AS LDT_TENLOAI
        FROM de_tai_nghien_cuu dt
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        $status_condition
        ORDER BY dt.DT_MADT DESC
        LIMIT {$offset}, {$items_per_page}";

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đề tài | Admin</title>
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/admin/manage_projects.css" rel="stylesheet">
</head>

<body>
    <?php include '../../../include/admin_sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../admin_dashboard.php">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quản lý đề tài</li>
                </ol>
            </nav>

            <h1 class="page-header mb-4">Quản lý đề tài nghiên cứu</h1>

            <!-- Nút thêm đề tài mới và tìm kiếm -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <a href="add_project.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i> Thêm đề tài mới
                    </a>
                </div>
                <div class="col-md-6">
                    <form class="form-inline float-md-right">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Tìm kiếm đề tài..." id="searchProject">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bảng danh sách đề tài -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0"><i class="fas fa-list mr-2"></i> Danh sách đề tài</h5>
                </div>
                <div class="card-body">
                    <!-- Bộ lọc -->
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <select class="form-control" id="filterStatus">
                                    <option value="">-- Trạng thái --</option>
                                    <option value="Chờ duyệt" <?php echo ($filter_status == 'Chờ duyệt') ? 'selected' : ''; ?>>Chờ duyệt</option>
                                    <option value="Đang thực hiện" <?php echo ($filter_status == 'Đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                                    <option value="Đã hoàn thành" <?php echo ($filter_status == 'Đã hoàn thành') ? 'selected' : ''; ?>>Đã hoàn thành</option>
                                    <option value="Tạm dừng" <?php echo ($filter_status == 'Tạm dừng') ? 'selected' : ''; ?>>Tạm dừng</option>
                                    <option value="Đã hủy" <?php echo ($filter_status == 'Đã hủy') ? 'selected' : ''; ?>>Đã hủy</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select class="form-control" id="filterType">
                                    <option value="">-- Loại đề tài --</option>
                                    <?php
                                    // Lấy danh sách loại đề tài - Với xử lý lỗi
                                    $sql_loai_de_tai = "SELECT * FROM loai_de_tai ORDER BY LDT_TENLOAI";
                                    $result_loai_de_tai = $conn->query($sql_loai_de_tai);
                                    if ($result_loai_de_tai && $result_loai_de_tai->num_rows > 0) {
                                        while ($row = $result_loai_de_tai->fetch_assoc()) {
                                            echo "<option value='" . $row['LDT_MA'] . "'>" . $row['LDT_TENLOAI'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>Không có dữ liệu</option>";
                                        if (!$result_loai_de_tai) {
                                            error_log("Query error (loai_de_tai): " . $conn->error);
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select class="form-control" id="filterDepartment">
                                    <option value="">-- Khoa --</option>
                                    <?php
                                    // Lấy danh sách khoa - Với xử lý lỗi
                                    $sql_khoa = "SELECT * FROM khoa ORDER BY DV_TENDV";
                                    $result_khoa = $conn->query($sql_khoa);
                                    if ($result_khoa && $result_khoa->num_rows > 0) {
                                        while ($row = $result_khoa->fetch_assoc()) {
                                            echo "<option value='" . $row['DV_MADV'] . "'>" . $row['DV_TENDV'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>Không có dữ liệu</option>";
                                        if (!$result_khoa) {
                                            error_log("Query error (khoa): " . $conn->error);
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button class="btn btn-outline-secondary btn-block" id="resetFilters">
                                    <i class="fas fa-sync-alt mr-1"></i> Đặt lại bộ lọc
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mã đề tài</th>
                                    <th>Tên đề tài</th>
                                    <th>Giảng viên hướng dẫn</th>
                                    <th>Loại đề tài</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Sửa câu truy vấn để bỏ trường DT_NGAYTAO
                                $result = $conn->query($sql);

                                if (!$result) {
                                    echo "<div class='alert alert-danger'>Lỗi truy vấn: " . $conn->error . "</div>";
                                }

                                // Hiển thị dữ liệu nếu có
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Xác định class cho badge trạng thái
                                        $status_class = '';
                                        switch ($row['DT_TRANGTHAI']) {
                                            case 'Chờ duyệt':
                                                $status_class = 'badge-warning';
                                                break;
                                            case 'Đang thực hiện':
                                                $status_class = 'badge-primary';
                                                break;
                                            case 'Đã hoàn thành':
                                                $status_class = 'badge-success';
                                                break;
                                            case 'Tạm dừng':
                                                $status_class = 'badge-info';
                                                break;
                                            case 'Đã hủy':
                                                $status_class = 'badge-danger';
                                                break;
                                            default:
                                                $status_class = 'badge-secondary';
                                        }

                                        echo "<tr>
                                                <td>{$row['DT_MADT']}</td>
                                                <td>{$row['DT_TENDT']}</td>
                                                <td>{$row['GV_HOTEN']}</td>
                                                <td>{$row['LDT_TENLOAI']}</td>
                                                <td><span class='badge {$status_class}'>{$row['DT_TRANGTHAI']}</span></td>
                                                <td>
                                                    <a href='view_project.php?id={$row['DT_MADT']}' class='btn btn-sm btn-info' title='Xem chi tiết'>
                                                        <i class='fas fa-eye'></i>
                                                    </a>
                                                    <a href='edit_project.php?id={$row['DT_MADT']}' class='btn btn-sm btn-primary' title='Chỉnh sửa'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                    <a href='#' class='btn btn-sm btn-danger btn-delete' data-id='{$row['DT_MADT']}' title='Xóa'>
                                                        <i class='fas fa-trash'></i>
                                                    </a>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Không có đề tài nào!</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Phân trang -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="pagination-display">
                            Hiển thị
                            <?php echo ($total_items > 0) ? $offset + 1 : 0; ?>-<?php echo min($offset + $items_per_page, $total_items); ?>
                            của <?php echo $total_items; ?> đề tài
                        </div>
                        <nav>
                            <ul class="pagination justify-content-end mb-0">
                                <!-- Nút Previous -->
                                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link page-nav" href="#"
                                        data-page="<?php echo $current_page - 1; ?>">Trước</a>
                                </li>

                                <!-- Hiển thị các số trang -->
                                <?php
                                // Xác định phạm vi trang cần hiển thị
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                // Hiển thị trang đầu nếu không nằm trong phạm vi hiển thị
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link page-number" href="#" data-page="1">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                }

                                // Hiển thị các trang trong phạm vi
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
                                    echo '<a class="page-link page-number" href="#" data-page="' . $i . '">' . $i . '</a>';
                                    echo '</li>';
                                }

                                // Hiển thị trang cuối nếu không nằm trong phạm vi hiển thị
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link page-number" href="#" data-page="' . $total_pages . '">' . $total_pages . '</a></li>';
                                }
                                ?>

                                <!-- Nút Next -->
                                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link page-nav" href="#"
                                        data-page="<?php echo $current_page + 1; ?>">Sau</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận xóa đề tài -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa đề tài này không? Hành động này không thể hoàn tác.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Xóa</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/NLNganh/assets/js/admin/manage_projects.js"></script>
</body>

</html>