<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\export_table.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Nhận tham số 
$type = isset($_GET['type']) ? $_GET['type'] : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';

// Thiết lập header cho file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="thong-ke-' . $type . '-' . $year . '.xls"');
header('Cache-Control: max-age=0');

// Xử lý điều kiện lọc
$year_condition = '';
$faculty_condition = '';

if ($year > 0) {
    $year_condition = "AND YEAR(dt.DT_NGAYTAO) = $year";
}

if (!empty($faculty)) {
    $faculty_condition = "AND gv.DV_MADV = '$faculty'";
}

// Bắt đầu output HTML cho Excel
echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>Báo cáo thống kê</title>';
echo '</head>';
echo '<body>';

if ($type == 'teacher') {
    // Thống kê giảng viên
    $teacher_query = "SELECT 
                    CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
                    gv.GV_MAGV,
                    gv.GV_EMAIL,
                    k.DV_TENDV,
                    COUNT(dt.DT_MADT) as project_count
                  FROM 
                    giang_vien gv
                  LEFT JOIN 
                    de_tai_nghien_cuu dt ON gv.GV_MAGV = dt.GV_MAGV
                  JOIN 
                    khoa k ON gv.DV_MADV = k.DV_MADV
                  WHERE 
                    1=1 $year_condition $faculty_condition
                  GROUP BY 
                    gv.GV_MAGV
                  ORDER BY 
                    project_count DESC";
    $teacher_result = $conn->query($teacher_query);

    echo '<h1>Thống kê giảng viên tham gia nghiên cứu năm ' . $year . '</h1>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>STT</th>';
    echo '<th>Mã giảng viên</th>';
    echo '<th>Họ và tên</th>';
    echo '<th>Email</th>';
    echo '<th>Đơn vị</th>';
    echo '<th>Số đề tài tham gia</th>';
    echo '</tr>';

    $stt = 1;
    while ($row = $teacher_result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $stt++ . '</td>';
        echo '<td>' . $row['GV_MAGV'] . '</td>';
        echo '<td>' . $row['GV_HOTEN'] . '</td>';
        echo '<td>' . $row['GV_EMAIL'] . '</td>';
        echo '<td>' . $row['DV_TENDV'] . '</td>';
        echo '<td align="center">' . $row['project_count'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} else if ($type == 'student') {
    // Thống kê sinh viên
    $student_query = "SELECT 
                    CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN,
                    sv.SV_MASV,
                    sv.SV_EMAIL,
                    l.LOP_TEN,
                    k.DV_TENDV,
                    COUNT(DISTINCT ct.DT_MADT) as project_count
                  FROM 
                    sinh_vien sv
                  JOIN 
                    chi_tiet_tham_gia ct ON sv.SV_MASV = ct.SV_MASV
                  JOIN 
                    de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                  LEFT JOIN 
                    lop l ON sv.LOP_MA = l.LOP_MA
                  LEFT JOIN
                    khoa k ON l.DV_MADV = k.DV_MADV
                  LEFT JOIN
                    giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                  WHERE 
                    1=1 $year_condition $faculty_condition
                  GROUP BY 
                    sv.SV_MASV
                  ORDER BY 
                    project_count DESC";
    $student_result = $conn->query($student_query);

    echo '<h1>Thống kê sinh viên tham gia nghiên cứu năm ' . $year . '</h1>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>STT</th>';
    echo '<th>Mã sinh viên</th>';
    echo '<th>Họ và tên</th>';
    echo '<th>Email</th>';
    echo '<th>Lớp</th>';
    echo '<th>Khoa</th>';
    echo '<th>Số đề tài tham gia</th>';
    echo '</tr>';

    $stt = 1;
    while ($row = $student_result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $stt++ . '</td>';
        echo '<td>' . $row['SV_MASV'] . '</td>';
        echo '<td>' . $row['SV_HOTEN'] . '</td>';
        echo '<td>' . $row['SV_EMAIL'] . '</td>';
        echo '<td>' . $row['LOP_TEN'] . '</td>';
        echo '<td>' . $row['DV_TENDV'] . '</td>';
        echo '<td align="center">' . $row['project_count'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}

echo '</body>';
echo '</html>';
?>
