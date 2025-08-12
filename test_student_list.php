<?php
// filepath: d:\xampp\htdocs\NLNganh\test_student_list.php
/**
 * File test để kiểm tra API lấy danh sách sinh viên
 */

// Bao gồm file kết nối cơ sở dữ liệu
require_once 'include/connect.php';

echo "<h2>Test API Lấy Danh Sách Sinh Viên</h2>";

// Test 1: Kiểm tra kết nối database
echo "<h3>1. Kiểm tra kết nối database</h3>";
if ($conn->connect_error) {
    echo "❌ Lỗi kết nối: " . $conn->connect_error;
} else {
    echo "✅ Kết nối database thành công<br>";
}

// Test 2: Kiểm tra bảng sinh viên
echo "<h3>2. Kiểm tra bảng sinh viên</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM sinh_vien");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Bảng sinh viên có " . $row['count'] . " bản ghi<br>";
} else {
    echo "❌ Lỗi truy vấn bảng sinh viên: " . $conn->error . "<br>";
}

// Test 3: Kiểm tra bảng lớp
echo "<h3>3. Kiểm tra bảng lớp</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM lop");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Bảng lớp có " . $row['count'] . " bản ghi<br>";
} else {
    echo "❌ Lỗi truy vấn bảng lớp: " . $conn->error . "<br>";
}

// Test 4: Kiểm tra bảng khoa
echo "<h3>4. Kiểm tra bảng khoa</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM khoa");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Bảng khoa có " . $row['count'] . " bản ghi<br>";
} else {
    echo "❌ Lỗi truy vấn bảng khoa: " . $conn->error . "<br>";
}

// Test 5: Kiểm tra bảng chi_tiet_tham_gia
echo "<h3>5. Kiểm tra bảng chi_tiet_tham_gia</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM chi_tiet_tham_gia");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Bảng chi_tiet_tham_gia có " . $row['count'] . " bản ghi<br>";
} else {
    echo "❌ Lỗi truy vấn bảng chi_tiet_tham_gia: " . $conn->error . "<br>";
}

// Test 6: Test truy vấn cơ bản
echo "<h3>6. Test truy vấn cơ bản</h3>";
$query = "SELECT 
            sv.SV_MASV, 
            CONCAT(sv.SV_HOSV, ' ', sv.SV_TENSV) AS SV_HOTEN, 
            l.LOP_TEN, 
            k.DV_TENDV,
            (SELECT COUNT(*) FROM chi_tiet_tham_gia ct WHERE ct.SV_MASV = sv.SV_MASV) AS project_count
          FROM 
            sinh_vien sv
          LEFT JOIN 
            lop l ON sv.LOP_MA = l.LOP_MA
          LEFT JOIN 
            khoa k ON l.DV_MADV = k.DV_MADV
          ORDER BY sv.SV_MASV ASC 
          LIMIT 5";

$result = $conn->query($query);
if ($result) {
    echo "✅ Truy vấn thành công. Kết quả mẫu:<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Mã SV</th><th>Họ tên</th><th>Lớp</th><th>Khoa</th><th>Số đề tài</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['SV_MASV']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SV_HOTEN']) . "</td>";
        echo "<td>" . htmlspecialchars($row['LOP_TEN'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['DV_TENDV'] ?? 'N/A') . "</td>";
        echo "<td>" . $row['project_count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Lỗi truy vấn: " . $conn->error . "<br>";
}

// Test 7: Test lọc theo khoa
echo "<h3>7. Test lọc theo khoa</h3>";
$dept_query = "SELECT DV_MADV, DV_TENDV FROM khoa LIMIT 3";
$result = $conn->query($dept_query);
if ($result) {
    echo "✅ Danh sách khoa mẫu:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['DV_MADV'] . ": " . $row['DV_TENDV'] . "<br>";
    }
} else {
    echo "❌ Lỗi truy vấn khoa: " . $conn->error . "<br>";
}

// Test 8: Test lọc theo khóa học
echo "<h3>8. Test lọc theo khóa học</h3>";
$year_query = "SELECT DISTINCT KH_NAM FROM lop ORDER BY KH_NAM DESC LIMIT 5";
$result = $conn->query($year_query);
if ($result) {
    echo "✅ Danh sách khóa học:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['KH_NAM'] . "<br>";
    }
} else {
    echo "❌ Lỗi truy vấn khóa học: " . $conn->error . "<br>";
}

echo "<h3>✅ Hoàn thành test!</h3>";
echo "<p>Nếu tất cả các test đều thành công, API sẽ hoạt động bình thường.</p>";

$conn->close();
?>
