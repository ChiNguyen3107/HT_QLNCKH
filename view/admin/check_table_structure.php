<?php
// Kiểm tra cấu trúc bảng và dữ liệu thực tế
include '../../include/connect.php';

echo "<h2>Kiểm tra cấu trúc bảng và dữ liệu</h2>";

// Kiểm tra bảng sinh_vien
echo "<h3>1. Bảng sinh_vien:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'sinh_vien'");
if ($result->num_rows > 0) {
    echo "✓ Bảng sinh_vien tồn tại<br>";
    
    // Kiểm tra số lượng sinh viên trong lớp DI2195A2
    $count_result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = 'DI2195A2'");
    $count = $count_result->fetch_assoc()['total'];
    echo "Số sinh viên lớp DI2195A2: $count<br>";
    
    // Hiển thị 5 sinh viên đầu tiên
    $sample_result = $conn->query("SELECT SV_MSSV, SV_HOSV, SV_TENSV, LOP_MA FROM sinh_vien WHERE LOP_MA = 'DI2195A2' LIMIT 5");
    echo "Mẫu sinh viên:<br>";
    while ($row = $sample_result->fetch_assoc()) {
        echo "- {$row['SV_MSSV']}: {$row['SV_HOSV']} {$row['SV_TENSV']}<br>";
    }
} else {
    echo "✗ Bảng sinh_vien KHÔNG tồn tại<br>";
}

// Kiểm tra bảng sinh_vien_de_tai
echo "<h3>2. Bảng sinh_vien_de_tai:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'sinh_vien_de_tai'");
if ($result->num_rows > 0) {
    echo "✓ Bảng sinh_vien_de_tai tồn tại<br>";
    
    // Kiểm tra số lượng sinh viên có đề tài
    $count_result = $conn->query("
        SELECT COUNT(DISTINCT sv.SV_MSSV) as total 
        FROM sinh_vien sv 
        JOIN sinh_vien_de_tai svdt ON sv.SV_MSSV = svdt.SV_MSSV 
        WHERE sv.LOP_MA = 'DI2195A2'
    ");
    $count = $count_result->fetch_assoc()['total'];
    echo "Số sinh viên có đề tài lớp DI2195A2: $count<br>";
    
    // Hiển thị mẫu
    $sample_result = $conn->query("
        SELECT sv.SV_MSSV, svdt.DT_MADT 
        FROM sinh_vien sv 
        JOIN sinh_vien_de_tai svdt ON sv.SV_MSSV = svdt.SV_MSSV 
        WHERE sv.LOP_MA = 'DI2195A2' 
        LIMIT 5
    ");
    echo "Mẫu sinh viên - đề tài:<br>";
    while ($row = $sample_result->fetch_assoc()) {
        echo "- SV: {$row['SV_MSSV']} → ĐT: {$row['DT_MADT']}<br>";
    }
} else {
    echo "✗ Bảng sinh_vien_de_tai KHÔNG tồn tại<br>";
}

// Kiểm tra bảng de_tai
echo "<h3>3. Bảng de_tai:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'de_tai'");
if ($result->num_rows > 0) {
    echo "✓ Bảng de_tai tồn tại<br>";
    
    // Kiểm tra trạng thái đề tài
    $status_result = $conn->query("SELECT DISTINCT DT_TRANGTHAI FROM de_tai");
    echo "Các trạng thái đề tài có sẵn:<br>";
    while ($row = $status_result->fetch_assoc()) {
        echo "- {$row['DT_TRANGTHAI']}<br>";
    }
    
    // Kiểm tra đề tài hoàn thành
    $completed_result = $conn->query("
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai dt 
        JOIN sinh_vien_de_tai svdt ON dt.DT_MADT = svdt.DT_MADT 
        JOIN sinh_vien sv ON svdt.SV_MSSV = sv.SV_MSSV 
        WHERE sv.LOP_MA = 'DI2195A2' AND dt.DT_TRANGTHAI = 'Hoàn thành'
    ");
    $completed_count = $completed_result->fetch_assoc()['total'];
    echo "Đề tài hoàn thành lớp DI2195A2: $completed_count<br>";
    
    // Kiểm tra đề tài đang thực hiện
    $ongoing_result = $conn->query("
        SELECT COUNT(DISTINCT dt.DT_MADT) as total 
        FROM de_tai dt 
        JOIN sinh_vien_de_tai svdt ON dt.DT_MADT = svdt.DT_MADT 
        JOIN sinh_vien sv ON svdt.SV_MSSV = sv.SV_MSSV 
        WHERE sv.LOP_MA = 'DI2195A2' AND dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Đã đăng ký', 'Đã phê duyệt')
    ");
    $ongoing_count = $ongoing_result->fetch_assoc()['total'];
    echo "Đề tài đang thực hiện lớp DI2195A2: $ongoing_count<br>";
    
} else {
    echo "✗ Bảng de_tai KHÔNG tồn tại<br>";
}

// Hiển thị thống kê tổng hợp
echo "<h3>4. Thống kê tổng hợp cho lớp DI2195A2:</h3>";
$total_result = $conn->query("SELECT COUNT(*) as total FROM sinh_vien WHERE LOP_MA = 'DI2195A2'");
$total = $total_result->fetch_assoc()['total'];

$with_projects_result = $conn->query("
    SELECT COUNT(DISTINCT sv.SV_MSSV) as total 
    FROM sinh_vien sv 
    JOIN sinh_vien_de_tai svdt ON sv.SV_MSSV = svdt.SV_MSSV 
    WHERE sv.LOP_MA = 'DI2195A2'
");
$with_projects = $with_projects_result->fetch_assoc()['total'];

echo "<strong>Tổng sinh viên: $total</strong><br>";
echo "<strong>Sinh viên có đề tài: $with_projects</strong><br>";
echo "<strong>Tỷ lệ: " . round(($with_projects/$total)*100, 1) . "%</strong><br>";
?>
