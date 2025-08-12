<?php
// API để xuất danh sách sinh viên ra file Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="danh_sach_sinh_vien_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Kết nối database
include '../include/connect.php';

try {
    // Lấy tham số từ request
    $department = isset($_GET['department']) ? $_GET['department'] : '';
    $school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';
    $class = isset($_GET['class']) ? $_GET['class'] : '';
    $research_status = isset($_GET['research_status']) ? $_GET['research_status'] : '';
    
    // Xây dựng điều kiện WHERE
    $where_conditions = [];
    $params = [];
    $param_types = '';
    
    // Điều kiện khoa
    if (!empty($department)) {
        $where_conditions[] = "l.DV_MADV = ?";
        $params[] = $department;
        $param_types .= 's';
    }
    
    // Điều kiện khóa học
    if (!empty($school_year)) {
        $where_conditions[] = "l.KH_NAM = ?";
        $params[] = $school_year;
        $param_types .= 's';
    }
    
    // Điều kiện lớp
    if (!empty($class)) {
        $where_conditions[] = "sv.LOP_MA = ?";
        $params[] = $class;
        $param_types .= 's';
    }
    
    // Điều kiện trạng thái nghiên cứu
    if (!empty($research_status)) {
        switch ($research_status) {
            case 'active':
                $where_conditions[] = "project_count > 0";
                break;
            case 'completed':
                $where_conditions[] = "completed_project_count > 0";
                break;
            case 'none':
                $where_conditions[] = "project_count = 0";
                break;
        }
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Truy vấn lấy danh sách sinh viên
    $sql = "SELECT 
                sv.SV_MASV,
                CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as SV_HOTEN,
                l.LOP_TEN,
                k.DV_TENDV,
                COALESCE(project_stats.project_count, 0) as project_count,
                COALESCE(project_stats.completed_project_count, 0) as completed_project_count,
                CASE 
                    WHEN COALESCE(project_stats.project_count, 0) = 0 THEN 'Chưa tham gia'
                    WHEN COALESCE(project_stats.completed_project_count, 0) > 0 THEN 'Đã hoàn thành'
                    ELSE 'Đang tham gia'
                END as research_status
            FROM sinh_vien sv
            JOIN lop l ON sv.LOP_MA = l.LOP_MA
            JOIN khoa k ON l.DV_MADV = k.DV_MADV
            LEFT JOIN (
                SELECT 
                    cttg.SV_MASV,
                    COUNT(DISTINCT cttg.DT_MADT) as project_count,
                    COUNT(DISTINCT CASE WHEN dt.DT_TRANGTHAI = 'Đã hoàn thành' THEN cttg.DT_MADT END) as completed_project_count
                FROM chi_tiet_tham_gia cttg
                JOIN de_tai_nghien_cuu dt ON cttg.DT_MADT = dt.DT_MADT
                GROUP BY cttg.SV_MASV
            ) project_stats ON sv.SV_MASV = project_stats.SV_MASV
            $where_clause
            ORDER BY sv.SV_HOSV, sv.SV_TENSV";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Tạo header cho file Excel
    echo '<table border="1">';
    echo '<tr style="background-color: #4e73df; color: white; font-weight: bold;">';
    echo '<td>STT</td>';
    echo '<td>Mã sinh viên</td>';
    echo '<td>Họ và tên</td>';
    echo '<td>Lớp</td>';
    echo '<td>Khoa</td>';
    echo '<td>Trạng thái nghiên cứu</td>';
    echo '<td>Số đề tài tham gia</td>';
    echo '<td>Số đề tài đã hoàn thành</td>';
    echo '</tr>';
    
    $stt = 1;
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $stt . '</td>';
        echo '<td>' . htmlspecialchars($row['SV_MASV']) . '</td>';
        echo '<td>' . htmlspecialchars($row['SV_HOTEN']) . '</td>';
        echo '<td>' . htmlspecialchars($row['LOP_TEN']) . '</td>';
        echo '<td>' . htmlspecialchars($row['DV_TENDV']) . '</td>';
        echo '<td>' . htmlspecialchars($row['research_status']) . '</td>';
        echo '<td>' . $row['project_count'] . '</td>';
        echo '<td>' . $row['completed_project_count'] . '</td>';
        echo '</tr>';
        $stt++;
    }
    
    echo '</table>';
    
} catch (Exception $e) {
    // Trả về lỗi
    echo '<h3>Lỗi: ' . htmlspecialchars($e->getMessage()) . '</h3>';
}

$conn->close();
?>
