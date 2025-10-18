<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\export_researchers.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Lấy tham số
$role = isset($_GET['role']) ? $_GET['role'] : 'teacher';
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Thiết lập header cho file Excel
$filename = "danh_sach_" . ($role === 'teacher' ? 'giang_vien' : 'sinh_vien') . "_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Xây dựng truy vấn SQL cho giảng viên
if ($role === 'teacher') {
    $sql = "SELECT gv.GV_MAGV, gv.GV_HOGV, gv.GV_TENGV, gv.GV_EMAIL, 
                   gv.GV_SDT, k.DV_TENDV, 
                   COUNT(dt.DT_MADT) AS project_count,
                   GROUP_CONCAT(DISTINCT dt.DT_TENDE SEPARATOR ' | ') AS projects  
            FROM giang_vien gv 
            LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
            LEFT JOIN de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
            WHERE 1=1";
    
    // Điều kiện lọc khoa
    if (!empty($faculty_filter)) {
        $sql .= " AND gv.DV_MADV = ?";
    }
    
    // Điều kiện tìm kiếm
    if (!empty($search_term)) {
        $sql .= " AND (gv.GV_MAGV LIKE ? OR CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) LIKE ? OR gv.GV_EMAIL LIKE ?)";
    }
    
    $sql .= " GROUP BY gv.GV_MAGV ORDER BY project_count DESC, gv.GV_TENGV ASC";
} else {
    // Truy vấn SQL cho sinh viên
    $sql = "SELECT sv.SV_MASV, sv.SV_HOSV, sv.SV_TENSV, sv.SV_EMAIL, sv.SV_SDT, 
                   l.LOP_TEN, k.DV_TENDV, 
                   COUNT(DISTINCT ct.DT_MADT) AS project_count,
                   GROUP_CONCAT(DISTINCT dt.DT_TENDE SEPARATOR ' | ') AS projects
            FROM sinh_vien sv 
            LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA 
            LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV 
            LEFT JOIN chi_tiet_tham_gia ct ON sv.SV_MASV = ct.SV_MASV
            LEFT JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
            WHERE 1=1";
    
    // Điều kiện lọc khoa
    if (!empty($faculty_filter)) {
        $sql .= " AND l.DV_MADV = ?";
    }
    
    // Điều kiện tìm kiếm
    if (!empty($search_term)) {
        $sql .= " AND (sv.SV_MASV LIKE ? OR CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) LIKE ? OR sv.SV_EMAIL LIKE ?)";
    }
    
    $sql .= " GROUP BY sv.SV_MASV ORDER BY project_count DESC, sv.SV_TENSV ASC";
}

// Chuẩn bị và thực thi truy vấn
$stmt = $conn->prepare($sql);

// Xây dựng mảng tham số và kiểu dữ liệu
$types = '';
$params = array();

// Thêm các tham số lọc
if (!empty($faculty_filter)) {
    $types .= 's';
    $params[] = $faculty_filter;
}

if (!empty($search_term)) {
    $types .= 'sss';
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Gán tham số cho prepared statement
if (!empty($params)) {
    $ref_params = array();
    $ref_params[] = &$types;
    foreach ($params as $key => $value) {
        $ref_params[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $ref_params);
}

$stmt->execute();
$result = $stmt->get_result();

// Tạo file Excel
echo "<table border='1'>";

// Header cho giảng viên
if ($role === 'teacher') {
    echo "<tr style='background-color: #4e73df; color: white; font-weight: bold;'>
            <th>Mã GV</th>
            <th>Họ</th>
            <th>Tên</th>
            <th>Email</th>
            <th>Điện thoại</th>
            <th>Khoa/Đơn vị</th>
            <th>Số đề tài</th>
            <th>Danh sách đề tài</th>
          </tr>";
          
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['GV_MAGV'] . "</td>
                <td>" . $row['GV_HOGV'] . "</td>
                <td>" . $row['GV_TENGV'] . "</td>
                <td>" . $row['GV_EMAIL'] . "</td>
                <td>" . $row['GV_SDT'] . "</td>
                <td>" . $row['DV_TENDV'] . "</td>
                <td>" . $row['project_count'] . "</td>
                <td>" . $row['projects'] . "</td>
              </tr>";
    }
} else {
    // Header cho sinh viên
    echo "<tr style='background-color: #1cc88a; color: white; font-weight: bold;'>
            <th>Mã SV</th>
            <th>Họ</th>
            <th>Tên</th>
            <th>Email</th>
            <th>Điện thoại</th>
            <th>Lớp</th>
            <th>Khoa/Đơn vị</th>
            <th>Số đề tài</th>
            <th>Danh sách đề tài</th>
          </tr>";
          
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['SV_MASV'] . "</td>
                <td>" . $row['SV_HOSV'] . "</td>
                <td>" . $row['SV_TENSV'] . "</td>
                <td>" . $row['SV_EMAIL'] . "</td>
                <td>" . $row['SV_SDT'] . "</td>
                <td>" . $row['LOP_TEN'] . "</td>
                <td>" . $row['DV_TENDV'] . "</td>
                <td>" . $row['project_count'] . "</td>
                <td>" . $row['projects'] . "</td>
              </tr>";
    }
}

echo "</table>";
?>
