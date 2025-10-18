<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\get_my_projects.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Lấy thông tin đề tài nghiên cứu của sinh viên từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_MOTA, dt.DT_TRANGTHAI,
               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN,
               cttg.CTTG_VAITRO
        FROM de_tai_nghien_cuu dt
        JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        WHERE cttg.SV_MASV = ?";
        
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo '<tr><td colspan="6" class="text-danger">Lỗi chuẩn bị câu lệnh SQL: ' . htmlspecialchars($conn->error) . '</td></tr>';
    exit;
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
$stmt->close();

// Trả về HTML cho bảng
if (count($projects) > 0): 
    foreach ($projects as $project): 
        // Xác định class cho badge trạng thái
        $status_class = '';
        switch ($project['DT_TRANGTHAI']) {
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
?>
        <tr>
            <td><?php echo htmlspecialchars($project['DT_MADT']); ?></td>
            <td><?php echo htmlspecialchars($project['DT_TENDT']); ?></td>
            <td><?php echo htmlspecialchars($project['GV_HOTEN'] ?: 'Chưa có GVHD'); ?></td>
            <td><?php echo htmlspecialchars($project['CTTG_VAITRO']); ?></td>
            <td><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($project['DT_TRANGTHAI']); ?></span></td>
            <td>
                <div class="btn-group-sm">
                    <a href="view_project.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-info mb-1" 
                       data-toggle="tooltip" title="Xem chi tiết đề tài">
                        <i class="fas fa-eye"></i> Xem
                    </a>
                    <?php if ($project['DT_TRANGTHAI'] === 'Đang thực hiện'): ?>
                    <a href="submit_report.php?id=<?php echo $project['DT_MADT']; ?>" class="btn btn-sm btn-primary mb-1"
                       data-toggle="tooltip" title="Nộp báo cáo tiến độ">
                        <i class="fas fa-file-upload"></i> Nộp báo cáo
                    </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="6" class="text-center">
            <div class="empty-state">
                <i class="fas fa-clipboard"></i>
                <p>Bạn chưa đăng ký đề tài nghiên cứu nào</p>
                <a href="browse_projects.php" class="btn btn-primary btn-sm mt-2">
                    <i class="fas fa-search mr-1"></i>Tìm đề tài ngay
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>