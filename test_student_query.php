<?php
require_once 'include/database.php';

$conn = connectDB();
echo "=== Dữ liệu mẫu từ bảng lop ===" . PHP_EOL;
$result = $conn->query('SELECT * FROM lop LIMIT 3');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo PHP_EOL . "=== Test get student info ===" . PHP_EOL;
$test_student_id = 'B2110051'; // MSSV mẫu
$sql = "SELECT 
            sv.SV_MASV,
            sv.SV_HOSV,
            sv.SV_TENSV,
            CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) as fullname,
            sv.SV_NGAYSINH,
            sv.SV_SDT,
            sv.SV_EMAIL,
            lop.LOP_TEN,
            lop.KH_NAM as KHOA
        FROM sinh_vien sv
        LEFT JOIN lop ON sv.LOP_MA = lop.LOP_MA
        WHERE sv.SV_MASV = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    echo "Student found:" . PHP_EOL;
    print_r($student);
} else {
    echo "No student found with ID: $test_student_id" . PHP_EOL;
}
?>
