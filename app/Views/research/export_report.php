<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\export_report.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Lấy tham số
$role = isset($_GET['role']) ? $_GET['role'] : 'teacher';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    // Redirect về trang danh sách nếu không có ID
    header('Location: manage_researchers.php');
    exit;
}

// Thiết lập header cho file Excel
$filename = "bao_cao_" . ($role === 'teacher' ? 'giang_vien' : 'sinh_vien') . "_" . $id . "_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Lấy thông tin nhà nghiên cứu
if ($role === 'teacher') {
    $researcher_sql = "SELECT gv.*, k.DV_TENDV
                      FROM giang_vien gv 
                      LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV
                      WHERE gv.GV_MAGV = ?";
    
    $researcher_stmt = $conn->prepare($researcher_sql);
    $researcher_stmt->bind_param("s", $id);
    $researcher_stmt->execute();
    $researcher_result = $researcher_stmt->get_result();
    $researcher = $researcher_result->fetch_assoc();
    
    // Lấy thống kê đề tài
    $projects_sql = "SELECT dt.DT_MADT, dt.DT_TENDE, dt.DT_TRANGTHAI, dt.DT_NGAYTAO, dt.DT_NGAYDUYET, 
                           ldt.LDT_TENLOAI,  
                           (SELECT COUNT(*) FROM chi_tiet_tham_gia ct WHERE ct.DT_MADT = dt.DT_MADT) AS student_count
                    FROM de_tai_nghien_cuu dt
                    LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    WHERE dt.GV_MAGV = ?
                    ORDER BY dt.DT_NGAYTAO DESC";
                    
    $projects_stmt = $conn->prepare($projects_sql);
    $projects_stmt->bind_param("s", $id);
    $projects_stmt->execute();
    $projects_result = $projects_stmt->get_result();
    $projects = [];
    while ($project = $projects_result->fetch_assoc()) {
        $projects[] = $project;
    }
    
    // Các trạng thái đề tài
    $status_sql = "SELECT dt.DT_TRANGTHAI, COUNT(dt.DT_MADT) AS count
                  FROM de_tai_nghien_cuu dt
                  WHERE dt.GV_MAGV = ?
                  GROUP BY dt.DT_TRANGTHAI";
                  
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("s", $id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $statuses = [];
    while ($status = $status_result->fetch_assoc()) {
        $statuses[$status['DT_TRANGTHAI']] = $status['count'];
    }
    
    // Xuất báo cáo Excel giảng viên
    echo "<h2>Thông tin giảng viên</h2>";
    echo "<table border='1'>
            <tr style='background-color: #4e73df; color: white;'>
                <th>Mã giảng viên</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Khoa/Đơn vị</th>
            </tr>
            <tr>
                <td>" . $researcher['GV_MAGV'] . "</td>
                <td>" . $researcher['GV_HOGV'] . ' ' . $researcher['GV_TENGV'] . "</td>
                <td>" . $researcher['GV_EMAIL'] . "</td>
                <td>" . $researcher['GV_SDT'] . "</td>
                <td>" . $researcher['DV_TENDV'] . "</td>
            </tr>
          </table>";
          
    echo "<h2>Thống kê đề tài nghiên cứu</h2>";
    echo "<table border='1'>
            <tr style='background-color: #4e73df; color: white;'>
                <th>Tổng số đề tài</th>
                <th>Đề tài mới</th>
                <th>Đề tài đã duyệt</th>
                <th>Đề tài đang thực hiện</th>
                <th>Đề tài hoàn thành</th>
                <th>Đề tài bị từ chối</th>
            </tr>
            <tr>
                <td>" . count($projects) . "</td>
                <td>" . (isset($statuses['new']) ? $statuses['new'] : 0) . "</td>
                <td>" . (isset($statuses['approved']) ? $statuses['approved'] : 0) . "</td>
                <td>" . (isset($statuses['in_progress']) ? $statuses['in_progress'] : 0) . "</td>
                <td>" . (isset($statuses['completed']) ? $statuses['completed'] : 0) . "</td>
                <td>" . (isset($statuses['rejected']) ? $statuses['rejected'] : 0) . "</td>
            </tr>
          </table>";
          
    echo "<h2>Danh sách đề tài nghiên cứu</h2>";
    echo "<table border='1'>
            <tr style='background-color: #4e73df; color: white;'>
                <th>Mã đề tài</th>
                <th>Tên đề tài</th>
                <th>Loại đề tài</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
                <th>Số SV tham gia</th>
            </tr>";
            
    foreach ($projects as $project) {
        $status_text = '';
        switch ($project['DT_TRANGTHAI']) {
            case 'new': $status_text = 'Mới'; break;
            case 'approved': $status_text = 'Đã duyệt'; break;
            case 'in_progress': $status_text = 'Đang thực hiện'; break;
            case 'completed': $status_text = 'Hoàn thành'; break;
            case 'rejected': $status_text = 'Bị từ chối'; break;
            default: $status_text = $project['DT_TRANGTHAI'];
        }
        
        echo "<tr>
                <td>" . $project['DT_MADT'] . "</td>
                <td>" . $project['DT_TENDE'] . "</td>
                <td>" . $project['LDT_TENLOAI'] . "</td>
                <td>" . date('d/m/Y', strtotime($project['DT_NGAYTAO'])) . "</td>
                <td>" . $status_text . "</td>
                <td>" . $project['student_count'] . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    // Xử lý cho sinh viên
    $researcher_sql = "SELECT sv.*, l.LOP_TEN, k.DV_TENDV
                      FROM sinh_vien sv 
                      LEFT JOIN lop l ON sv.LOP_MA = l.LOP_MA
                      LEFT JOIN khoa k ON l.DV_MADV = k.DV_MADV
                      WHERE sv.SV_MASV = ?";
    
    $researcher_stmt = $conn->prepare($researcher_sql);
    $researcher_stmt->bind_param("s", $id);
    $researcher_stmt->execute();
    $researcher_result = $researcher_stmt->get_result();
    $researcher = $researcher_result->fetch_assoc();
    
    // Lấy thống kê đề tài
    $projects_sql = "SELECT dt.DT_MADT, dt.DT_TENDE, dt.DT_TRANGTHAI, dt.DT_NGAYTAO, 
                           ldt.LDT_TENLOAI, 
                           CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN
                    FROM chi_tiet_tham_gia ct
                    JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                    LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                    LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                    WHERE ct.SV_MASV = ?
                    ORDER BY dt.DT_NGAYTAO DESC";
                    
    $projects_stmt = $conn->prepare($projects_sql);
    $projects_stmt->bind_param("s", $id);
    $projects_stmt->execute();
    $projects_result = $projects_stmt->get_result();
    $projects = [];
    while ($project = $projects_result->fetch_assoc()) {
        $projects[] = $project;
    }
    
    // Các trạng thái đề tài
    $status_sql = "SELECT dt.DT_TRANGTHAI, COUNT(dt.DT_MADT) AS count
                  FROM chi_tiet_tham_gia ct
                  JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
                  WHERE ct.SV_MASV = ?
                  GROUP BY dt.DT_TRANGTHAI";
                  
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("s", $id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $statuses = [];
    while ($status = $status_result->fetch_assoc()) {
        $statuses[$status['DT_TRANGTHAI']] = $status['count'];
    }
    
    // Xuất báo cáo Excel sinh viên
    echo "<h2>Thông tin sinh viên</h2>";
    echo "<table border='1'>
            <tr style='background-color: #1cc88a; color: white;'>
                <th>Mã sinh viên</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Lớp</th>
                <th>Khoa/Đơn vị</th>
            </tr>
            <tr>
                <td>" . $researcher['SV_MASV'] . "</td>
                <td>" . $researcher['SV_HOSV'] . ' ' . $researcher['SV_TENSV'] . "</td>
                <td>" . $researcher['SV_EMAIL'] . "</td>
                <td>" . $researcher['SV_SDT'] . "</td>
                <td>" . $researcher['LOP_TEN'] . "</td>
                <td>" . $researcher['DV_TENDV'] . "</td>
            </tr>
          </table>";
          
    echo "<h2>Thống kê đề tài nghiên cứu</h2>";
    echo "<table border='1'>
            <tr style='background-color: #1cc88a; color: white;'>
                <th>Tổng số đề tài</th>
                <th>Đề tài mới</th>
                <th>Đề tài đã duyệt</th>
                <th>Đề tài đang thực hiện</th>
                <th>Đề tài hoàn thành</th>
                <th>Đề tài bị từ chối</th>
            </tr>
            <tr>
                <td>" . count($projects) . "</td>
                <td>" . (isset($statuses['new']) ? $statuses['new'] : 0) . "</td>
                <td>" . (isset($statuses['approved']) ? $statuses['approved'] : 0) . "</td>
                <td>" . (isset($statuses['in_progress']) ? $statuses['in_progress'] : 0) . "</td>
                <td>" . (isset($statuses['completed']) ? $statuses['completed'] : 0) . "</td>
                <td>" . (isset($statuses['rejected']) ? $statuses['rejected'] : 0) . "</td>
            </tr>
          </table>";
          
    echo "<h2>Danh sách đề tài nghiên cứu</h2>";
    echo "<table border='1'>
            <tr style='background-color: #1cc88a; color: white;'>
                <th>Mã đề tài</th>
                <th>Tên đề tài</th>
                <th>Loại đề tài</th>
                <th>Giảng viên hướng dẫn</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
            </tr>";
            
    foreach ($projects as $project) {
        $status_text = '';
        switch ($project['DT_TRANGTHAI']) {
            case 'new': $status_text = 'Mới'; break;
            case 'approved': $status_text = 'Đã duyệt'; break;
            case 'in_progress': $status_text = 'Đang thực hiện'; break;
            case 'completed': $status_text = 'Hoàn thành'; break;
            case 'rejected': $status_text = 'Bị từ chối'; break;
            default: $status_text = $project['DT_TRANGTHAI'];
        }
        
        echo "<tr>
                <td>" . $project['DT_MADT'] . "</td>
                <td>" . $project['DT_TENDE'] . "</td>
                <td>" . $project['LDT_TENLOAI'] . "</td>
                <td>" . $project['GV_HOTEN'] . "</td>
                <td>" . date('d/m/Y', strtotime($project['DT_NGAYTAO'])) . "</td>
                <td>" . $status_text . "</td>
              </tr>";
    }
    echo "</table>";
}
?>
<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\export_report.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Nhận tham số từ form
$type = isset($_POST['type']) ? $_POST['type'] : '';
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$faculty = isset($_POST['faculty']) ? $_POST['faculty'] : '';

// Thiết lập header cho file PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="bao-cao-' . $type . '-' . $year . '.pdf"');

// Thông báo đang phát triển
echo "Chức năng xuất báo cáo đang được phát triển. Sẽ hoàn thành trong phiên bản tiếp theo.";

// Trong tương lai, phát triển đầy đủ với thư viện TCPDF hoặc FPDF để tạo file PDF
?>
