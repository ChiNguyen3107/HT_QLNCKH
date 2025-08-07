<?php
// Test evaluation update
session_start();
require_once 'include/connect.php';

header('Content-Type: application/json');

// Set test session
$_SESSION['user_id'] = 'SV001';
$_SESSION['role'] = 'student';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $member_id = $_POST['member_id'] ?? 'GV000002';
    $project_id = $_POST['project_id'] ?? 'DT0001';
    $test_score = floatval($_POST['test_score'] ?? 85.5);
    $test_comment = $_POST['test_comment'] ?? 'Test evaluation comment';

    // Simulate criteria scores
    $criteria_scores = [
        'TC001' => ['score' => 8.5, 'comment' => 'Good overview'],
        'TC002' => ['score' => 12.0, 'comment' => 'Clear objectives'],
        'TC003' => ['score' => 13.5, 'comment' => 'Sound methodology'],
        'TC004' => ['score' => 25.0, 'comment' => 'Excellent content'],
        'TC005' => ['score' => 12.5, 'comment' => 'Good contribution'],
        'TC006' => ['score' => 4.0, 'comment' => 'Good presentation'],
        'TC007' => ['score' => 5.0, 'comment' => 'On time'],
        'TC008' => ['score' => 5.0, 'comment' => 'Published papers']
    ];

    $criteria_json = json_encode($criteria_scores, JSON_UNESCAPED_UNICODE);

    // Find member in council
    $find_member = $conn->prepare("
        SELECT thd.QD_SO, thd.GV_MAGV, thd.TC_MATC 
        FROM thanh_vien_hoi_dong thd
        JOIN quyet_dinh_nghiem_thu qd ON thd.QD_SO = qd.QD_SO
        JOIN bien_ban bb ON qd.BB_SOBB = bb.BB_SOBB
        JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO
        WHERE thd.GV_MAGV = ? AND dt.DT_MADT = ?
        LIMIT 1
    ");
    
    if (!$find_member) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $find_member->bind_param("ss", $member_id, $project_id);
    $find_member->execute();
    $member_result = $find_member->get_result();
    
    if ($member_result->num_rows === 0) {
        throw new Exception("Member not found in council for this project");
    }
    
    $member_info = $member_result->fetch_assoc();
    
    // Update member evaluation
    $update_member = $conn->prepare("
        UPDATE thanh_vien_hoi_dong 
        SET 
            TV_DIEM = ?,
            TV_DANHGIA = ?,
            TV_DIEMCHITIET = ?,
            TV_TRANGTHAI = ?,
            TV_NGAYDANHGIA = NOW()
        WHERE QD_SO = ? AND GV_MAGV = ? AND TC_MATC = ?
    ");
    
    if (!$update_member) {
        throw new Exception("Prepare update failed: " . $conn->error);
    }
    
    $status = 'Đã hoàn thành';
    $update_member->bind_param("dsssss", 
        $test_score, 
        $test_comment, 
        $criteria_json, 
        $status,
        $member_info['QD_SO'], 
        $member_info['GV_MAGV'], 
        $member_info['TC_MATC']
    );
    
    if (!$update_member->execute()) {
        throw new Exception('Update failed: ' . $update_member->error);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Test evaluation successful!',
        'data' => [
            'qd_so' => $member_info['QD_SO'],
            'member_id' => $member_info['GV_MAGV'],
            'tc_matc' => $member_info['TC_MATC'],
            'score' => $test_score,
            'criteria_count' => count($criteria_scores)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
