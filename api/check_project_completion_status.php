<?php
header('Content-Type: application/json');

include '../include/session.php';
include '../include/connect.php';
include '../include/project_completion_functions.php';

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Phương thức không được phép']);
    exit;
}

// Lấy project_id từ POST data
$project_id = isset($_POST['project_id']) ? trim($_POST['project_id']) : '';

if (empty($project_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Thiếu mã đề tài']);
    exit;
}

try {
    // Kiểm tra quyền truy cập đề tài
    $access_sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, ct.CTTG_VAITRO 
                   FROM de_tai_nghien_cuu dt 
                   LEFT JOIN chi_tiet_tham_gia ct ON dt.DT_MADT = ct.DT_MADT 
                   WHERE dt.DT_MADT = ? AND ct.SV_MASV = ?";
    $stmt = $conn->prepare($access_sql);
    $stmt->bind_param("ss", $project_id, $_SESSION['user_id']);
    $stmt->execute();
    $access_result = $stmt->get_result();

    if ($access_result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập đề tài này']);
        exit;
    }

    $project_info = $access_result->fetch_assoc();

    // Lấy thông tin chi tiết về điều kiện hoàn thành
    $completion_details = getProjectCompletionDetails($project_id, $conn);

    // Chuẩn bị response
    $response = [
        'success' => true,
        'project_info' => [
            'id' => $project_info['DT_MADT'],
            'title' => $project_info['DT_TENDT'],
            'status' => $project_info['DT_TRANGTHAI']
        ],
        'completion_details' => $completion_details,
        'requirements' => [
            [
                'name' => 'Biên bản nghiệm thu đạt',
                'status' => $completion_details['has_passing_report'],
                'details' => $completion_details['has_passing_report'] 
                    ? "Đã có biên bản với kết quả: " . ($completion_details['report_grade'] ?? 'N/A')
                    : "Chưa có biên bản nghiệm thu với kết quả đạt"
            ],
            [
                'name' => 'Điểm đánh giá từ hội đồng',
                'status' => $completion_details['all_members_scored'],
                'details' => "Đã có điểm: {$completion_details['scored_members']}/{$completion_details['total_members']} thành viên"
            ]
        ]
    ];

    // Thêm danh sách thành viên chưa có điểm nếu có
    if (!empty($completion_details['missing_members'])) {
        $missing_names = array_map(function($member) {
            return $member['name'] . ' (' . $member['role'] . ')';
        }, $completion_details['missing_members']);
        
        $response['missing_members'] = $missing_names;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("API check completion status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống']);
}
?>
