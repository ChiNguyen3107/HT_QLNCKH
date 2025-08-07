<?php
// File: save_council_members.php
// Xử lý việc lưu thành viên hội đồng nghiệm thu

include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';
include '../../include/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
    exit();
}

$project_id = trim($_POST['project_id'] ?? '');
$decision_id = trim($_POST['decision_id'] ?? '');
$council_members = trim($_POST['council_members'] ?? '');
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền chủ nhiệm
$check_role_sql = "SELECT CTTG_VAITRO FROM chi_tiet_tham_gia WHERE DT_MADT = ? AND SV_MASV = ?";
$stmt = $conn->prepare($check_role_sql);
$stmt->bind_param("ss", $project_id, $user_id);
$stmt->execute();
$role_result = $stmt->get_result();

if ($role_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập đề tài này']);
    exit();
}

$user_role = $role_result->fetch_assoc()['CTTG_VAITRO'];
if ($user_role !== 'Chủ nhiệm') {
    echo json_encode(['success' => false, 'message' => 'Chỉ chủ nhiệm đề tài mới có thể cập nhật thông tin hội đồng']);
    exit();
}

try {
    $conn->begin_transaction();

    // Parse council members JSON
    $members_data = json_decode($council_members, true);
    
    if (!$members_data || !is_array($members_data)) {
        throw new Exception('Dữ liệu thành viên hội đồng không hợp lệ');
    }

    // Xóa thành viên hội đồng cũ
    $delete_sql = "DELETE FROM thanh_vien_hoi_dong WHERE QD_SO = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("s", $decision_id);
    $stmt->execute();

    // Thêm thành viên mới
    $insert_sql = "INSERT INTO thanh_vien_hoi_dong (QD_SO, GV_MAGV, TC_MATC, TV_VAITRO, TV_HOTEN) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);

    foreach ($members_data as $member) {
        $gv_magv = $member['id'] ?? '';
        $vaitro = $member['role'] ?? '';
        $hoten = $member['name'] ?? '';
        $tc_matc = 'TC001'; // Default value, có thể cập nhật logic này

        if (empty($gv_magv) || empty($vaitro) || empty($hoten)) {
            continue; // Skip invalid members
        }

        $stmt->bind_param("sssss", $decision_id, $gv_magv, $tc_matc, $vaitro, $hoten);
        $stmt->execute();
    }

    // Cập nhật trường HD_THANHVIEN trong bảng quyet_dinh_nghiem_thu
    $update_sql = "UPDATE quyet_dinh_nghiem_thu SET HD_THANHVIEN = ? WHERE QD_SO = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $council_members, $decision_id);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cập nhật thành viên hội đồng thành công',
        'count' => count($members_data)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>
