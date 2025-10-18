<?php
// filepath: d:\xampp\htdocs\NLNganh\view\student\get_suggested_projects.php
include '../../include/session.php';
checkStudentRole();

include '../../include/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Kết nối thất bại: ' . $conn->connect_error]);
    exit;
}

// Lấy tham số tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Lấy mã sinh viên
$student_id = $_SESSION['user_id'];

// Lấy danh sách đề tài đề xuất (loại trừ các đề tài đã tham gia)
// CHỈ LẤY CÁC CỘT CỤ THỂ thay vì SELECT *
$sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, dt.DT_MOTA, ldt.LDT_TENLOAI,
               CONCAT(IFNULL(gv.GV_HOGV, ''), ' ', IFNULL(gv.GV_TENGV, '')) AS GV_HOTEN
        FROM de_tai_nghien_cuu dt
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MALOAI = ldt.LDT_MALOAI
        WHERE (dt.DT_TRANGTHAI = 'Chờ duyệt' OR dt.DT_TRANGTHAI = 'Đang thực hiện')
        AND NOT EXISTS (
            SELECT 1 FROM chi_tiet_tham_gia cttg 
            WHERE cttg.DT_MADT = dt.DT_MADT AND cttg.SV_MASV = ?
        )";

// Thêm điều kiện tìm kiếm nếu có
if (!empty($search)) {
    $sql .= " AND (dt.DT_TENDT LIKE ? OR dt.DT_MADT LIKE ? OR CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) LIKE ? OR ldt.LDT_TENLOAI LIKE ?)";
    $search_param = "%$search%";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("sssss", $student_id, $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $student_id);
}

// Thực thi truy vấn
$stmt->execute();
$result = $stmt->get_result();

// Lấy kết quả
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($projects);
$stmt->close();
?>