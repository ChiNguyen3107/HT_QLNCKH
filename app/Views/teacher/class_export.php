<?php
session_start();
require_once '../../include/database.php';
require_once '../../include/session.php';

// Kiểm tra đăng nhập
checkTeacherRole();

$teacher_id = $_SESSION['user_id'];
$export_type = $_GET['export'] ?? 'excel';
$lop_ma = $_GET['lop_ma'] ?? '';

if (empty($lop_ma)) {
    die('Thiếu thông tin lớp học');
}

// Kiểm tra quyền truy cập lớp
$check_sql = "SELECT COUNT(*) as count FROM advisor_class 
              WHERE GV_MAGV = ? AND LOP_MA = ? AND AC_COHIEULUC = 1";
$check_stmt = $connection->prepare($check_sql);
$check_stmt->bind_param("ss", $teacher_id, $lop_ma);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();

if ($check_row['count'] == 0) {
    die('Bạn không có quyền truy cập lớp này');
}

// Truy vấn thông tin lớp
$class_sql = "SELECT 
                l.LOP_MA,
                l.LOP_TEN,
                l.KH_NAM,
                k.DV_TENDV,
                CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as CVHT_HOTEN
              FROM advisor_class ac
              JOIN lop l ON ac.LOP_MA = l.LOP_MA
              JOIN khoa k ON l.DV_MADV = k.DV_MADV
              JOIN giang_vien gv ON ac.GV_MAGV = gv.GV_MAGV
              WHERE ac.GV_MAGV = ? AND ac.LOP_MA = ? AND ac.AC_COHIEULUC = 1";

$class_stmt = $connection->prepare($class_sql);
$class_stmt->bind_param("ss", $teacher_id, $lop_ma);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class_info = $class_result->fetch_assoc();

if (!$class_info) {
    die('Không tìm thấy thông tin lớp');
}

// Truy vấn danh sách sinh viên
$sql = "SELECT 
            sv.SV_MASV,
            sv.SV_HOSV,
            sv.SV_TENSV,
            sv.SV_EMAIL,
            sv.SV_SDT,
            -- Thông tin đề tài
            dt.DT_MADT,
            dt.DT_TENDT,
            dt.DT_TRANGTHAI,
            dt.DT_NGAYTAO,
            -- Thông tin tham gia
            cttg.CTTG_VAITRO,
            cttg.CTTG_NGAYTHAMGIA,
            -- Thông tin giảng viên hướng dẫn
            CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
            gv.GV_EMAIL,
            -- Phân loại trạng thái
            CASE 
                WHEN dt.DT_MADT IS NULL THEN 'Chưa tham gia'
                WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN 'Đã hoàn thành'
                WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN 'Bị từ chối/Tạm dừng'
                WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN 'Đang tham gia'
                ELSE 'Đang tham gia'
            END as TRANGTHAI_PHANLOAI,
            -- Tiến độ (giả định dựa trên trạng thái)
            CASE 
                WHEN dt.DT_MADT IS NULL THEN 0
                WHEN dt.DT_TRANGTHAI LIKE '%hoan%' THEN 100
                WHEN dt.DT_TRANGTHAI LIKE '%huy%' OR dt.DT_TRANGTHAI LIKE '%tam%' THEN 0
                WHEN dt.DT_TRANGTHAI LIKE '%thuc%' THEN 50
                ELSE 25
            END as TIENDO_PHANTRAM
        FROM advisor_class ac
        JOIN lop l ON ac.LOP_MA = l.LOP_MA
        LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
        LEFT JOIN chi_tiet_tham_gia cttg ON sv.SV_MASV = cttg.SV_MASV
        LEFT JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        WHERE ac.GV_MAGV = ? AND ac.LOP_MA = ? AND ac.AC_COHIEULUC = 1
        ORDER BY CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV)";

$stmt = $connection->prepare($sql);
$stmt->bind_param("ss", $teacher_id, $lop_ma);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

if ($export_type === 'excel') {
    // Xuất Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Danh_sach_sinh_vien_lop_' . $class_info['LOP_MA'] . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; }';
    echo '.header { background-color: #4CAF50; color: white; text-align: center; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<table>';
    echo '<tr class="header">';
    echo '<th colspan="8">DANH SÁCH SINH VIÊN LỚP ' . strtoupper($class_info['LOP_MA']) . '</th>';
    echo '</tr>';
    echo '<tr class="header">';
    echo '<th colspan="8">Tên lớp: ' . $class_info['LOP_TEN'] . ' | Khoa: ' . $class_info['DV_TENDV'] . ' | Niên khóa: ' . $class_info['KH_NAM'] . '</th>';
    echo '</tr>';
    echo '<tr class="header">';
    echo '<th colspan="8">Cố vấn học tập: ' . $class_info['CVHT_HOTEN'] . ' | Ngày xuất: ' . date('d/m/Y H:i:s') . '</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th>STT</th>';
    echo '<th>MSSV</th>';
    echo '<th>Họ và tên</th>';
    echo '<th>Email</th>';
    echo '<th>Số điện thoại</th>';
    echo '<th>Trạng thái</th>';
    echo '<th>Tên đề tài</th>';
    echo '<th>GV hướng dẫn</th>';
    echo '</tr>';
    
    $stt = 1;
    foreach ($students as $student) {
        echo '<tr>';
        echo '<td>' . $stt++ . '</td>';
        echo '<td>' . $student['SV_MASV'] . '</td>';
        echo '<td>' . $student['SV_HOSV'] . ' ' . $student['SV_TENSV'] . '</td>';
        echo '<td>' . ($student['SV_EMAIL'] ?: '-') . '</td>';
        echo '<td>' . ($student['SV_SDT'] ?: '-') . '</td>';
        echo '<td>' . $student['TRANGTHAI_PHANLOAI'] . '</td>';
        echo '<td>' . ($student['DT_TENDT'] ?: 'Chưa có đề tài') . '</td>';
        echo '<td>' . ($student['GV_HOTEN'] ?: '-') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
} elseif ($export_type === 'students') {
    // Xuất danh sách sinh viên đơn giản
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Danh_sach_SV_' . $class_info['LOP_MA'] . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; }';
    echo '.header { background-color: #2196F3; color: white; text-align: center; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<table>';
    echo '<tr class="header">';
    echo '<th colspan="3">DANH SÁCH SINH VIÊN LỚP ' . strtoupper($class_info['LOP_MA']) . '</th>';
    echo '</tr>';
    echo '<tr class="header">';
    echo '<th colspan="3">Tên lớp: ' . $class_info['LOP_TEN'] . ' | Ngày xuất: ' . date('d/m/Y H:i:s') . '</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th>STT</th>';
    echo '<th>MSSV</th>';
    echo '<th>Họ và tên</th>';
    echo '</tr>';
    
    $stt = 1;
    foreach ($students as $student) {
        echo '<tr>';
        echo '<td>' . $stt++ . '</td>';
        echo '<td>' . $student['SV_MASV'] . '</td>';
        echo '<td>' . $student['SV_HOSV'] . ' ' . $student['SV_TENSV'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
} else {
    // Xuất CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Danh_sach_sinh_vien_lop_' . $class_info['LOP_MA'] . '.csv"');
    
    // BOM cho UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, array(
        'MSSV',
        'Họ và tên',
        'Email',
        'Số điện thoại',
        'Trạng thái',
        'Tên đề tài',
        'GV hướng dẫn',
        'Tiến độ (%)'
    ));
    
    // Data
    foreach ($students as $student) {
        fputcsv($output, array(
            $student['SV_MASV'],
            $student['SV_HOSV'] . ' ' . $student['SV_TENSV'],
            $student['SV_EMAIL'] ?: '',
            $student['SV_SDT'] ?: '',
            $student['TRANGTHAI_PHANLOAI'],
            $student['DT_TENDT'] ?: 'Chưa có đề tài',
            $student['GV_HOTEN'] ?: '',
            $student['TIENDO_PHANTRAM']
        ));
    }
    
    fclose($output);
}
?>
