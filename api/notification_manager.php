<?php
/**
 * API Quản lý Thông báo
 * Xử lý các yêu cầu liên quan đến thông báo
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include các file cần thiết
require_once '../include/config.php';
require_once '../include/database.php';

// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Khởi tạo response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Kiểm tra phương thức HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Lấy action từ URL hoặc POST data
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Debug session (sẽ xóa sau)
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Action: " . $action);
    
    // Kiểm tra đăng nhập với thông tin debug
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        $debug_info = [
            'session_status' => session_status(),
            'session_id' => session_id(),
            'session_data' => $_SESSION ?? 'No session data',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'No user agent',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'No referer'
        ];
        throw new Exception('Chưa đăng nhập. Debug: ' . json_encode($debug_info));
    }
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    switch ($action) {
        case 'get_notifications':
            $response = getNotifications($conn, $user_id, $user_role);
            break;
            
        case 'get_count':
            $response = getNotificationCount($conn, $user_id, $user_role);
            break;
            
        case 'mark_read':
            $notification_id = $_POST['notification_id'] ?? $_GET['id'] ?? 0;
            $response = markAsRead($conn, $notification_id, $user_id);
            break;
            
        case 'mark_all_read':
            $response = markAllAsRead($conn, $user_id, $user_role);
            break;
            
        case 'delete':
            $notification_id = $_POST['notification_id'] ?? $_GET['id'] ?? 0;
            $response = deleteNotification($conn, $notification_id, $user_id);
            break;
            
        case 'create':
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $response = createNotification($conn, $data, $user_id);
            break;
            
        case 'get_settings':
            $response = getNotificationSettings($conn, $user_id);
            break;
            
        case 'update_settings':
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $response = updateNotificationSettings($conn, $data, $user_id);
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
}

// Trả về JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/**
 * Lấy danh sách thông báo
 */
function getNotifications($conn, $user_id, $user_role) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $status = $_GET['status'] ?? 'all'; // all, unread, read
    
    $offset = ($page - 1) * $limit;
    
    // Xây dựng điều kiện WHERE
    $where_conditions = [];
    $params = [];
    $param_types = '';
    
    // Điều kiện theo role
    $where_conditions[] = "(tb.TB_MUCTIEU = 'all' OR (tb.TB_MUCTIEU = ? AND (
        (? = 'admin' AND tb.QL_MA = ?) OR
        (? = 'research_manager' AND tb.QL_MA = ?) OR
        (? = 'teacher' AND tb.GV_MAGV = ?) OR
        (? = 'student' AND tb.SV_MASV = ?)
    )))";
    
    $params = array_merge($params, [$user_role, $user_role, $user_id, $user_role, $user_id, $user_role, $user_id, $user_role, $user_id]);
    $param_types .= str_repeat('s', 9);
    
    // Điều kiện theo trạng thái đọc
    if ($status === 'unread') {
        $where_conditions[] = "tb.TB_DANHDOC = 0";
    } elseif ($status === 'read') {
        $where_conditions[] = "tb.TB_DANHDOC = 1";
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Query chính
    $sql = "SELECT tb.*, 
                   CASE tb.TB_MUCDO
                       WHEN 'khan_cap' THEN 4
                       WHEN 'cao' THEN 3
                       WHEN 'trung_binh' THEN 2
                       WHEN 'thap' THEN 1
                       ELSE 2
                   END as priority_level,
                   DATE_FORMAT(tb.TB_NGAYTAO, '%d/%m/%Y %H:%i') as formatted_date
            FROM thong_bao tb
            $where_clause
            ORDER BY tb.TB_DANHDOC ASC, priority_level DESC, tb.TB_NGAYTAO DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    
    // Đếm tổng số
    $count_sql = "SELECT COUNT(*) as total FROM thong_bao tb $where_clause";
    $count_params = array_slice($params, 0, -2); // Loại bỏ limit và offset
    $count_param_types = substr($param_types, 0, -2);
    
    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt) {
        if (!empty($count_params)) {
            $count_stmt->bind_param($count_param_types, ...$count_params);
        }
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $total = 0;
    }
    
    return [
        'success' => true,
        'message' => 'Lấy danh sách thông báo thành công',
        'data' => [
            'notifications' => $notifications,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit
            ]
        ]
    ];
}

/**
 * Đếm số thông báo chưa đọc
 */
function getNotificationCount($conn, $user_id, $user_role) {
    // Sử dụng query trực tiếp thay vì function
    $sql = "SELECT COUNT(*) as unread_count FROM thong_bao 
            WHERE TB_DANHDOC = 0 
            AND (TB_MUCTIEU = 'all' OR TB_MUCTIEU = ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Lỗi prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $user_role);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['unread_count'] ?? 0;
    $stmt->close();
    
    return [
        'success' => true,
        'message' => 'Lấy số thông báo chưa đọc thành công',
        'data' => [
            'count' => (int)$count
        ]
    ];
}

/**
 * Đánh dấu thông báo đã đọc
 */
function markAsRead($conn, $notification_id, $user_id) {
    if (!$notification_id) {
        throw new Exception('ID thông báo không hợp lệ');
    }
    
    $sql = "UPDATE thong_bao SET TB_DANHDOC = 1 WHERE TB_MA = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Lỗi prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $notification_id);
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    if ($success && $affected_rows > 0) {
        // Ghi log
        $log_sql = "INSERT INTO thong_bao_lich_su (TBLS_TB_MA, TBLS_HANHDONG, TBLS_NGUOIDUNG, TBLS_GHICHU) 
                    VALUES (?, 'doc', ?, 'Đánh dấu đã đọc qua API')";
        $log_stmt = $conn->prepare($log_sql);
        if ($log_stmt) {
            $log_stmt->bind_param('is', $notification_id, $user_id);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        return [
            'success' => true,
            'message' => 'Đánh dấu đã đọc thành công',
            'data' => null
        ];
    } else {
        throw new Exception('Không thể đánh dấu đã đọc');
    }
}

/**
 * Đánh dấu tất cả thông báo đã đọc
 */
function markAllAsRead($conn, $user_id, $user_role) {
    $sql = "UPDATE thong_bao SET TB_DANHDOC = 1 
            WHERE TB_DANHDOC = 0 AND (
                TB_MUCTIEU = 'all' OR
                (TB_MUCTIEU = ? AND (
                    (? = 'admin' AND QL_MA = ?) OR
                    (? = 'research_manager' AND QL_MA = ?) OR
                    (? = 'teacher' AND GV_MAGV = ?) OR
                    (? = 'student' AND SV_MASV = ?)
                ))
            )";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('ssssssss', $user_role, $user_role, $user_id, $user_role, $user_id, $user_role, $user_id, $user_role, $user_id);
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    return [
        'success' => true,
        'message' => "Đã đánh dấu $affected_rows thông báo là đã đọc",
        'data' => [
            'affected_rows' => $affected_rows
        ]
    ];
}

/**
 * Xóa thông báo
 */
function deleteNotification($conn, $notification_id, $user_id) {
    if (!$notification_id) {
        throw new Exception('ID thông báo không hợp lệ');
    }
    
    // Ghi log trước khi xóa
    $log_sql = "INSERT INTO thong_bao_lich_su (TBLS_TB_MA, TBLS_HANHDONG, TBLS_NGUOIDUNG, TBLS_GHICHU) 
                VALUES (?, 'xoa', ?, 'Xóa thông báo qua API')";
    $log_stmt = $conn->prepare($log_sql);
    if ($log_stmt) {
        $log_stmt->bind_param('is', $notification_id, $user_id);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    $sql = "DELETE FROM thong_bao WHERE TB_MA = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Lỗi prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $notification_id);
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    if ($success && $affected_rows > 0) {
        return [
            'success' => true,
            'message' => 'Xóa thông báo thành công',
            'data' => null
        ];
    } else {
        throw new Exception('Không thể xóa thông báo');
    }
}

/**
 * Tạo thông báo mới
 */
function createNotification($conn, $data, $user_id) {
    $required_fields = ['noi_dung', 'muc_tieu'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Thiếu trường bắt buộc: $field");
        }
    }
    
    $noi_dung = $data['noi_dung'];
    $loai = $data['loai'] ?? 'Thông báo';
    $muc_tieu = $data['muc_tieu']; // admin, research_manager, teacher, student, all
    $muc_do = $data['muc_do'] ?? 'trung_binh';
    $nguoi_nhan = $data['nguoi_nhan'] ?? null;
    $metadata = !empty($data['metadata']) ? json_encode($data['metadata']) : null;
    
    $sql = "INSERT INTO thong_bao (TB_NOIDUNG, TB_LOAI, TB_MUCTIEU, TB_MUCDO, TB_METADATA, TB_NGUOITAO";
    $values = "VALUES (?, ?, ?, ?, ?, ?";
    $params = [$noi_dung, $loai, $muc_tieu, $muc_do, $metadata, $user_id];
    $param_types = 'ssssss';
    
    // Thêm trường người nhận tùy theo mục tiêu
    if ($nguoi_nhan) {
        switch ($muc_tieu) {
            case 'admin':
            case 'research_manager':
                $sql .= ", QL_MA";
                $values .= ", ?";
                $params[] = $nguoi_nhan;
                $param_types .= 's';
                break;
            case 'teacher':
                $sql .= ", GV_MAGV";
                $values .= ", ?";
                $params[] = $nguoi_nhan;
                $param_types .= 's';
                break;
            case 'student':
                $sql .= ", SV_MASV";
                $values .= ", ?";
                $params[] = $nguoi_nhan;
                $param_types .= 's';
                break;
        }
    }
    
    $sql .= ") " . $values . ")";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Lỗi prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param($param_types, ...$params);
    $success = $stmt->execute();
    $notification_id = $conn->insert_id;
    $stmt->close();
    
    if ($success) {
        return [
            'success' => true,
            'message' => 'Tạo thông báo thành công',
            'data' => [
                'notification_id' => $notification_id
            ]
        ];
    } else {
        throw new Exception('Không thể tạo thông báo');
    }
}

/**
 * Lấy cài đặt thông báo của user
 */
function getNotificationSettings($conn, $user_id) {
    // Placeholder cho tương lai - có thể tạo bảng user_notification_settings
    return [
        'success' => true,
        'message' => 'Lấy cài đặt thông báo thành công',
        'data' => [
            'email_notifications' => true,
            'push_notifications' => true,
            'sms_notifications' => false
        ]
    ];
}

/**
 * Cập nhật cài đặt thông báo
 */
function updateNotificationSettings($conn, $data, $user_id) {
    // Placeholder cho tương lai
    return [
        'success' => true,
        'message' => 'Cập nhật cài đặt thông báo thành công',
        'data' => null
    ];
}
?>
