<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\manage_projects\filter_projects.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập
include '../../../include/session.php';
checkAdminRole();
// Kết nối database
include '../../../include/connect.php';

// Thiết lập phân trang
$items_per_page = 10;
$current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Xây dựng truy vấn SQL
$base_sql = "SELECT 
                dt.DT_MADT, 
                dt.DT_TENDT, 
                dt.DT_TRANGTHAI,
                CONCAT(IFNULL(gv.GV_HOGV, ''), ' ', IFNULL(gv.GV_TENGV, '')) AS GV_HOTEN,
                IFNULL(ldt.LDT_TENLOAI, 'Chưa phân loại') AS LDT_TENLOAI
            FROM de_tai_nghien_cuu dt
            LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
            LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA";

$where_clauses = array();

// Thêm điều kiện tìm kiếm
if (!empty($_POST['search'])) {
    $search = $conn->real_escape_string($_POST['search']);
    $where_clauses[] = "(dt.DT_TENDT LIKE '%$search%' OR dt.DT_MADT LIKE '%$search%')";
}

// Thêm điều kiện lọc trạng thái
if (!empty($_POST['status'])) {
    $status = $conn->real_escape_string($_POST['status']);
    $where_clauses[] = "dt.DT_TRANGTHAI = '$status'";
}

// Thêm điều kiện lọc loại đề tài
if (!empty($_POST['type'])) {
    $type = $conn->real_escape_string($_POST['type']);
    $where_clauses[] = "dt.LDT_MA = '$type'";
}

// Thêm điều kiện lọc khoa
if (!empty($_POST['department'])) {
    $dept = $conn->real_escape_string($_POST['department']);
    $where_clauses[] = "gv.DV_MADV = '$dept'";
}

// Tạo câu truy vấn hoàn chỉnh
$where_sql = empty($where_clauses) ? "" : " WHERE " . implode(" AND ", $where_clauses);

// Đếm tổng số bản ghi để phân trang
$count_sql = "SELECT COUNT(*) as total FROM de_tai_nghien_cuu dt 
             LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV 
             LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA" . $where_sql;

$count_result = $conn->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Debug
// echo "<!-- Total items: $total_items, Total pages: $total_pages -->";

// Truy vấn dữ liệu với phân trang
$sql = $base_sql . $where_sql . " ORDER BY dt.DT_MADT DESC LIMIT $offset, $items_per_page";
$result = $conn->query($sql);

// Tạo HTML cho bảng dữ liệu
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Xác định class cho badge trạng thái
        $status_class = '';
        switch ($row['DT_TRANGTHAI']) {
            case 'Chờ duyệt': $status_class = 'badge-warning'; break;
            case 'Đang thực hiện': $status_class = 'badge-primary'; break;
            case 'Đã hoàn thành': $status_class = 'badge-success'; break;
            case 'Tạm dừng': $status_class = 'badge-info'; break;
            case 'Đã hủy': $status_class = 'badge-danger'; break;
            default: $status_class = 'badge-secondary';
        }

        echo "<tr>
                <td>{$row['DT_MADT']}</td>
                <td>{$row['DT_TENDT']}</td>
                <td>{$row['GV_HOTEN']}</td>
                <td>{$row['LDT_TENLOAI']}</td>
                <td><span class='badge $status_class'>{$row['DT_TRANGTHAI']}</span></td>
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
    
    // Thêm thông tin phân trang vào response - QUAN TRỌNG
    echo "<tr class='pagination-info' style='display:none'>
            <td colspan='6' data-total='$total_items' data-pages='$total_pages' data-current='$current_page'></td>
          </tr>";
} else {
    echo "<tr><td colspan='6' class='text-center'>Không tìm thấy đề tài nào phù hợp</td></tr>";
}
?>