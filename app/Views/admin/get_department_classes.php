<?php
// filepath: d:\xampp\htdocs\NLNganh\view\admin\get_department_classes.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập
include '../../include/session.php';
checkResearchManagerRole();
// Kết nối database
include '../../include/connect.php';

header('Content-Type: application/json');

// Kiểm tra tham số
if (!isset($_GET['dept_id']) || empty($_GET['dept_id']) || !isset($_GET['year']) || empty($_GET['year'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu tham số khoa hoặc khóa học',
        'classes' => []
    ]);
    exit;
}

$dept_id = $conn->real_escape_string($_GET['dept_id']);
$year = $conn->real_escape_string($_GET['year']);

// Lấy danh sách lớp theo khoa và khóa học, kèm số sinh viên tham gia đề tài
$classes_query = "SELECT 
                    l.LOP_TEN AS class_name,
                    l.LOP_MA AS class_id,
                    l.KH_NAM AS class_year,
                    k.DV_TENDV AS department_name,
                    COUNT(DISTINCT ct.SV_MASV) AS student_count
                  FROM lop l
                  JOIN khoa k ON l.DV_MADV = k.DV_MADV
                  LEFT JOIN sinh_vien sv ON l.LOP_MA = sv.LOP_MA
                  LEFT JOIN chi_tiet_tham_gia ct ON sv.SV_MASV = ct.SV_MASV
                  WHERE l.DV_MADV = '$dept_id' AND l.KH_NAM = '$year'
                  GROUP BY l.LOP_TEN, l.LOP_MA, l.KH_NAM, k.DV_TENDV
                  ORDER BY student_count DESC, l.LOP_TEN";

$result = $conn->query($classes_query);
$classes = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = [
            'class_id' => $row['class_id'],
            'class_name' => $row['class_name'],
            'class_year' => $row['class_year'],
            'department_name' => $row['department_name'],
            'student_count' => $row['student_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách lớp thành công',
        'classes' => $classes
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy lớp nào cho khoa và khóa học đã chọn',
        'classes' => []
    ]);
}
?>