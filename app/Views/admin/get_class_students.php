<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\get_class_students.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập
include '../../include/session.php';
checkAdminRole();
// Kết nối database
include '../../include/connect.php';

header('Content-Type: application/json');

// Nhận tham số
$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$khoa = isset($_GET['khoa']) ? $_GET['khoa'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Kiểm tra dữ liệu
if (empty($class_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu mã lớp',
        'data' => []
    ]);
    exit;
}

// Xây dựng câu truy vấn - Thêm DT_TRANGTHAI và thông tin giảng viên hướng dẫn
$query = "SELECT 
            sv.SV_MASV,
            CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS full_name,
            l.LOP_TEN AS class_name,
            ct.CTTG_VAITRO AS role,
            dt.DT_TENDT AS project_name,
            dt.DT_TRANGTHAI AS project_status,
            CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS advisor_name,
            ct.CTTG_NGAYTHAMGIA AS join_date
          FROM chi_tiet_tham_gia ct
          JOIN sinh_vien sv ON ct.SV_MASV = sv.SV_MASV
          JOIN lop l ON sv.LOP_MA = l.LOP_MA
          JOIN de_tai_nghien_cuu dt ON ct.DT_MADT = dt.DT_MADT
          LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
          WHERE sv.LOP_MA = ?";

$params = [$class_id];
$types = "s";

// Thêm điều kiện lọc
if (!empty($department)) {
    $query .= " AND l.DV_MADV = ?";
    $params[] = $department;
    $types .= "s";
}

if (!empty($khoa)) {
    $query .= " AND l.KH_NAM = ?";
    $params[] = $khoa;
    $types .= "s";
}

if (!empty($status)) {
    $query .= " AND ct.CTTG_VAITRO = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($keyword)) {
    $query .= " AND (sv.SV_MASV LIKE ? OR sv.SV_HOSV LIKE ? OR sv.SV_TENSV LIKE ?)";
    $keyword_param = "%$keyword%";
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= "sss";
}

$query .= " ORDER BY sv.SV_MASV";

// Thực thi truy vấn
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error,
        'data' => []
    ]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$students = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Trả về kết quả
echo json_encode([
    'success' => true,
    'message' => 'OK',
    'count' => count($students),
    'data' => $students
]);
?>