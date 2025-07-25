<?php
/**
 * Thư viện các hàm tiện ích cho hệ thống
 */

// Hàm format dữ liệu
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return 'Chưa xác định';
    $date_obj = is_string($date) ? new DateTime($date) : $date;
    return $date_obj->format($format);
}

// Hàm để tạo thẻ favicon cho HTML header
function addFaviconTags() {
    echo '<!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">';
}

// Hàm tính ngày còn lại đến deadline
function getDaysRemaining($deadline) {
    if (empty($deadline)) return '';
    
    $deadline_date = new DateTime($deadline);
    $today = new DateTime();
    $interval = $today->diff($deadline_date);
    if ($deadline_date < $today) {
        return '<span class="text-danger">(Quá hạn ' . $interval->days . ' ngày)</span>';
    } else {
        return '<span class="text-muted">(' . $interval->days . ' ngày nữa)</span>';
    }
}

// Hàm tạo badge theo trạng thái
function getStatusBadge($status) {
    $status_class = '';
    switch ($status) {
        case 'Chờ duyệt':
            $status_class = 'badge-warning';
            break;
        case 'Đang thực hiện':
            $status_class = 'badge-info';
            break;
        case 'Đã hoàn thành':
            $status_class = 'badge-success';
            break;
        case 'Đã hủy':
        case 'Tạm dừng':
            $status_class = 'badge-danger';
            break;
        default:
            $status_class = 'badge-secondary';
    }
    
    return '<span class="badge ' . $status_class . '">' . $status . '</span>';
}

// Hàm rút gọn văn bản (text truncate)
function truncateText($text, $length = 60, $append = '...') {
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length) . $append;
    }
    return $text;
}

// Hàm tạo ID ngẫu nhiên an toàn
function generateUniqueId($prefix = '', $length = 10) {
    $bytes = random_bytes(ceil($length / 2));
    return $prefix . substr(bin2hex($bytes), 0, $length);
}

// Hàm upload file và xác thực
function uploadFile($file, $destination, $allowed_types = ['pdf', 'doc', 'docx'], $max_size = 5242880) {
    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'Lỗi upload file: ' . $file['error']];
    }
    
    // Kiểm tra kích thước
    if ($file['size'] > $max_size) {
        return ['status' => false, 'message' => 'Kích thước file vượt quá giới hạn cho phép (' . ($max_size / 1024 / 1024) . 'MB)'];
    }
    
    // Kiểm tra loại file
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return ['status' => false, 'message' => 'Loại file không được hỗ trợ. Các loại file cho phép: ' . implode(', ', $allowed_types)];
    }
    
    // Tạo tên file mới để tránh trùng lặp
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $destination . $filename;
    
    // Di chuyển file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['status' => true, 'filepath' => $filepath, 'filename' => $filename];
    } else {
        return ['status' => false, 'message' => 'Không thể lưu file'];
    }
}

// Hàm kiểm tra và tạo thư mục nếu chưa tồn tại
function ensureDirectoryExists($dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Hàm ghi log
function logActivity($user_id, $action, $details = '') {
    global $conn;
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("ssss", $user_id, $action, $details, $ip);
    
    return $stmt->execute();
}

// Hàm kiểm tra quyền
function hasPermission($user_id, $permission) {
    global $conn;
    $sql = "SELECT r.permissions FROM roles r
            JOIN users u ON u.role_id = r.id
            WHERE u.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $permissions = json_decode($row['permissions'], true);
        return in_array($permission, $permissions);
    }
    
    return false;
}

// Hàm gửi email thông báo
function sendNotification($to, $subject, $message) {
    // Cài đặt email library (có thể dùng PHPMailer)
    // Code gửi email ở đây
    
    // Giả lập cho demo
    if (DEBUG_MODE) {
        error_log("Email sent to: {$to}, Subject: {$subject}, Message: {$message}");
        return true;
    }
    
    return false;
}

// Hàm xử lý lỗi và ghi log
function handleError($error, $error_type = 'general') {
    if (DEBUG_MODE) {
        echo '<div class="alert alert-danger">' . $error . '</div>';
    }
    
    error_log("[$error_type] $error");
}

// Hàm slugify để tạo URL thân thiện
function slugify($text) {
    // Chuyển đổi sang chữ thường
    $text = mb_strtolower($text, 'UTF-8');
    
    // Loại bỏ dấu tiếng Việt
    $text = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $text);
    $text = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $text);
    $text = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $text);
    $text = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $text);
    $text = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $text);
    $text = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $text);
    $text = preg_replace('/(đ)/', 'd', $text);
    
    // Loại bỏ ký tự đặc biệt
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    // Loại bỏ nhiều dấu cách liên tiếp
    $text = preg_replace('/[\s-]+/', '-', $text);
    // Loại bỏ dấu cách đầu và cuối chuỗi
    $text = trim($text, '-');
    
    return $text;
}

// Hàm tạo phân trang
function generatePagination($totalItems, $itemsPerPage, $currentPage, $baseUrl) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $html = '<nav aria-label="Phân trang"><ul class="pagination">';
    
    // Nút Previous
    $prevClass = ($currentPage <= 1) ? ' disabled' : '';
    $prevPage = max(1, $currentPage - 1);
    $html .= '<li class="page-item' . $prevClass . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $prevPage . '" aria-label="Previous">';
    $html .= '<span aria-hidden="true">&laquo;</span></a></li>';
    
    // Các trang
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i == $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $activeClass . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . $i . '">' . $i . '</a></li>';
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Nút Next
    $nextClass = ($currentPage >= $totalPages) ? ' disabled' : '';
    $nextPage = min($totalPages, $currentPage + 1);
    $html .= '<li class="page-item' . $nextClass . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $nextPage . '" aria-label="Next">';
    $html .= '<span aria-hidden="true">&raquo;</span></a></li>';
    
    $html .= '</ul></nav>';
    return $html;
}
