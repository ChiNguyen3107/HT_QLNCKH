<?php
// Bao gồm file session để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkTeacherRole();

// Kết nối database
include '../../include/connect.php';

// Lấy thông tin giảng viên
$teacher_id = $_SESSION['user_id'];
$export_type = $_GET['export'] ?? 'csv';
$lop_ma = $_GET['lop_ma'] ?? '';

// Xác định dữ liệu cần xuất
if ($lop_ma) {
    // Xuất chi tiết lớp cụ thể
    exportClassDetail($conn, $teacher_id, $lop_ma, $export_type);
} else {
    // Xuất tổng quan các lớp
    exportClassOverview($conn, $teacher_id, $export_type);
}

function exportClassDetail($conn, $teacher_id, $lop_ma, $export_type) {
    // Kiểm tra quyền truy cập
    $permission_check = $conn->prepare("SELECT COUNT(*) as count FROM advisor_class WHERE GV_MAGV = ? AND LOP_MA = ? AND AC_COHIEULUC = 1");
    $permission_check->bind_param("ss", $teacher_id, $lop_ma);
    $permission_check->execute();
    $has_permission = $permission_check->get_result()->fetch_assoc()['count'] > 0;
    $permission_check->close();

    if (!$has_permission) {
        header("Location: class_management.php?error=permission");
        exit;
    }

    // Lấy thông tin lớp
    $class_info_sql = "SELECT * FROM v_class_overview WHERE LOP_MA = ? AND CVHT_MAGV = ?";
    $stmt = $conn->prepare($class_info_sql);
    $stmt->bind_param("ss", $lop_ma, $teacher_id);
    $stmt->execute();
    $class_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$class_info) {
        header("Location: class_management.php?error=not_found");
        exit;
    }

    // Lấy danh sách sinh viên
    $sql = "SELECT 
                sps.SV_MASV,
                sps.SV_HOSV,
                sps.SV_TENSV,
                sps.LOP_MA,
                sps.LOP_TEN,
                sps.DT_MADT,
                sps.DT_TENDT,
                sps.DT_TRANGTHAI,
                sps.TRANGTHAI_PHANLOAI,
                sps.TIENDO_PHANTRAM,
                sps.GV_HOTEN,
                sps.CTTG_VAITRO,
                sps.CTTG_NGAYTHAMGIA,
                sps.DT_NGAYTAO
            FROM v_student_project_summary sps
            JOIN advisor_class ac ON sps.LOP_MA = ac.LOP_MA
            WHERE sps.LOP_MA = ? AND ac.GV_MAGV = ? AND ac.AC_COHIEULUC = 1
            ORDER BY CONCAT(sps.SV_HOSV, ' ', sps.SV_TENSV)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $lop_ma, $teacher_id);
    $stmt->execute();
    $students = $stmt->get_result();
    $stmt->close();

    // Chuẩn bị dữ liệu xuất
    $data = [];
    $headers = [
        'MSSV',
        'Họ và tên',
        'Lớp',
        'Trạng thái đề tài',
        'Tên đề tài',
        'Giảng viên hướng dẫn',
        'Vai trò trong đề tài',
        'Tiến độ (%)',
        'Ngày đăng ký đề tài',
        'Ngày tham gia'
    ];

    $stats = ['chua_tham_gia' => 0, 'dang_tham_gia' => 0, 'da_hoan_thanh' => 0, 'bi_tu_choi' => 0];

    while ($student = $students->fetch_assoc()) {
        $data[] = [
            $student['SV_MASV'],
            $student['SV_HOSV'] . ' ' . $student['SV_TENSV'],
            $student['LOP_TEN'],
            $student['TRANGTHAI_PHANLOAI'],
            $student['DT_TENDT'] ?: 'Chưa có đề tài',
            $student['GV_HOTEN'] ?: '-',
            $student['CTTG_VAITRO'] ?: '-',
            $student['TIENDO_PHANTRAM'] ?: '-',
            $student['DT_NGAYTAO'] ? date('d/m/Y', strtotime($student['DT_NGAYTAO'])) : '-',
            $student['CTTG_NGAYTHAMGIA'] ? date('d/m/Y', strtotime($student['CTTG_NGAYTHAMGIA'])) : '-'
        ];

        // Tính thống kê
        switch ($student['TRANGTHAI_PHANLOAI']) {
            case 'Chưa tham gia': $stats['chua_tham_gia']++; break;
            case 'Đang tham gia': $stats['dang_tham_gia']++; break;
            case 'Đã hoàn thành': $stats['da_hoan_thanh']++; break;
            case 'Bị từ chối/Tạm dừng': $stats['bi_tu_choi']++; break;
        }
    }

    // Thêm hàng thống kê
    $data[] = ['', '', '', '', '', '', '', '', '', ''];
    $data[] = ['THỐNG KÊ', '', '', '', '', '', '', '', '', ''];
    $data[] = ['Chưa tham gia:', $stats['chua_tham_gia'], '', '', '', '', '', '', '', ''];
    $data[] = ['Đang tham gia:', $stats['dang_tham_gia'], '', '', '', '', '', '', '', ''];
    $data[] = ['Đã hoàn thành:', $stats['da_hoan_thanh'], '', '', '', '', '', '', '', ''];
    $data[] = ['Bị từ chối/Tạm dừng:', $stats['bi_tu_choi'], '', '', '', '', '', '', '', ''];

    $filename = "Chi_tiet_lop_" . $class_info['LOP_TEN'] . "_" . date('Y-m-d') . ".csv";
    
    exportToFile($data, $headers, $filename, $export_type);
}

function exportClassOverview($conn, $teacher_id, $export_type) {
    // Lấy danh sách lớp của giảng viên
    $sql = "SELECT 
                co.LOP_MA,
                co.LOP_TEN,
                co.KH_NAM,
                co.DV_TENDV,
                co.TONG_SV,
                co.SV_CO_DETAI,
                co.SV_CHUA_CO_DETAI,
                co.TY_LE_THAM_GIA_PHANTRAM,
                co.CVHT_HOTEN,
                co.DETAI_CHO_DUYET,
                co.DETAI_DANG_THUCHIEN,
                co.DETAI_HOAN_THANH,
                co.DETAI_TAM_DUNG
            FROM v_class_overview co
            WHERE co.CVHT_MAGV = ?
            ORDER BY co.LOP_TEN";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $classes = $stmt->get_result();
    $stmt->close();

    // Chuẩn bị dữ liệu xuất
    $data = [];
    $headers = [
        'Mã lớp',
        'Tên lớp',
        'Niên khóa',
        'Khoa',
        'Tổng sinh viên',
        'Sinh viên có đề tài',
        'Sinh viên chưa có đề tài',
        'Tỷ lệ tham gia (%)',
        'Đề tài chờ duyệt',
        'Đề tài đang thực hiện',
        'Đề tài đã hoàn thành',
        'Đề tài tạm dừng'
    ];

    $total_stats = [
        'tong_sv' => 0,
        'sv_co_detai' => 0,
        'sv_chua_co_detai' => 0,
        'detai_cho_duyet' => 0,
        'detai_dang_thuchien' => 0,
        'detai_hoan_thanh' => 0,
        'detai_tam_dung' => 0
    ];

    while ($class = $classes->fetch_assoc()) {
        $data[] = [
            $class['LOP_MA'],
            $class['LOP_TEN'],
            $class['KH_NAM'],
            $class['DV_TENDV'],
            $class['TONG_SV'],
            $class['SV_CO_DETAI'],
            $class['SV_CHUA_CO_DETAI'],
            $class['TY_LE_THAM_GIA_PHANTRAM'],
            $class['DETAI_CHO_DUYET'],
            $class['DETAI_DANG_THUCHIEN'],
            $class['DETAI_HOAN_THANH'],
            $class['DETAI_TAM_DUNG']
        ];

        // Cộng dồn thống kê
        $total_stats['tong_sv'] += $class['TONG_SV'];
        $total_stats['sv_co_detai'] += $class['SV_CO_DETAI'];
        $total_stats['sv_chua_co_detai'] += $class['SV_CHUA_CO_DETAI'];
        $total_stats['detai_cho_duyet'] += $class['DETAI_CHO_DUYET'];
        $total_stats['detai_dang_thuchien'] += $class['DETAI_DANG_THUCHIEN'];
        $total_stats['detai_hoan_thanh'] += $class['DETAI_HOAN_THANH'];
        $total_stats['detai_tam_dung'] += $class['DETAI_TAM_DUNG'];
    }

    // Thêm hàng tổng cộng
    if (!empty($data)) {
        $data[] = ['', '', '', '', '', '', '', '', '', '', '', ''];
        $data[] = ['TỔNG CỘNG', '', '', '', $total_stats['tong_sv'], $total_stats['sv_co_detai'], 
                   $total_stats['sv_chua_co_detai'], '-', $total_stats['detai_cho_duyet'], 
                   $total_stats['detai_dang_thuchien'], $total_stats['detai_hoan_thanh'], 
                   $total_stats['detai_tam_dung']];
    }

    $filename = "Tong_quan_lop_cua_GV_" . date('Y-m-d') . ".csv";
    
    exportToFile($data, $headers, $filename, $export_type);
}

function exportToFile($data, $headers, $filename, $export_type) {
    // Thiết lập header cho download
    if ($export_type === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Xuất Excel format cơ bản
        echo "\xEF\xBB\xBF"; // BOM cho UTF-8
        
        // Header
        echo implode("\t", $headers) . "\r\n";
        
        // Data
        foreach ($data as $row) {
            echo implode("\t", $row) . "\r\n";
        }
    } else {
        // Xuất CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Tạo file output
        $output = fopen('php://output', 'w');
        
        // BOM cho UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, $headers, ',');
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row, ',');
        }
        
        fclose($output);
    }
    
    exit;
}
?>
