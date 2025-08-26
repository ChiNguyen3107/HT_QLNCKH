<?php
// File: get_project_supervisor.php
// API để lấy thông tin giảng viên hướng dẫn của đề tài

include '../include/session.php';
include '../include/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
    exit();
}

$project_id = trim($_GET['project_id'] ?? '');

if (empty($project_id)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đề tài']);
    exit();
}

try {
    // Lấy thông tin giảng viên hướng dẫn của đề tài
    $sql = "SELECT dt.GV_MAGV, CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN 
            FROM de_tai_nghien_cuu dt
            LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
            WHERE dt.DT_MADT = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }
    
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đề tài']);
        exit();
    }
    
    $supervisor = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $supervisor['GV_MAGV'],
            'name' => $supervisor['GV_HOTEN'] ?: $supervisor['GV_MAGV']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>


