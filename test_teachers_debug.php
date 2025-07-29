<?php
// File test để debug API get_teachers
session_start();

// Giả lập session để test (thay đổi theo session thực tế của bạn)
$_SESSION['user_id'] = 'test_user';
$_SESSION['role'] = 'student';

echo "<h3>Debug API get_teachers</h3>";

// Test kết nối database
include 'include/connect.php';

if ($conn->connect_error) {
    echo "<p style='color: red;'>Lỗi kết nối database: " . $conn->connect_error . "</p>";
    exit;
}

echo "<p style='color: green;'>✓ Kết nối database thành công</p>";

// Test query trực tiếp
$sql = "SELECT 
            gv.GV_MAGV as id,
            CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as name,
            gv.GV_HOGV as lastName,
            gv.GV_TENGV as firstName,
            gv.GV_EMAIL as email,
            gv.GV_CHUYENMON as specialty,
            k.DV_TENDV as department
        FROM giang_vien gv 
        LEFT JOIN khoa k ON gv.DV_MADV = k.DV_MADV 
        ORDER BY gv.GV_HOGV ASC, gv.GV_TENGV ASC";

echo "<p><strong>SQL Query:</strong></p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

$result = $conn->query($sql);

if (!$result) {
    echo "<p style='color: red;'>Lỗi SQL: " . $conn->error . "</p>";
    exit;
}

echo "<p style='color: green;'>✓ SQL query thành công</p>";
echo "<p><strong>Số bản ghi:</strong> " . $result->num_rows . "</p>";

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

echo "<p><strong>Dữ liệu trả về:</strong></p>";
echo "<pre>" . htmlspecialchars(json_encode($teachers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</pre>";

// Test API thực tế
echo "<hr>";
echo "<h4>Test API thực tế:</h4>";

// Capture output từ API
ob_start();
include 'api/get_teachers.php';
$api_output = ob_get_clean();

echo "<p><strong>API Response:</strong></p>";
echo "<pre>" . htmlspecialchars($api_output) . "</pre>";

$conn->close();
?>
