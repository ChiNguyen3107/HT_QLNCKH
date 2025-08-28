<?php
// Debug file để kiểm tra lỗi gia hạn đề tài
include '../../include/session.php';
checkStudentRole();
include '../../include/connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Extension System</h1>";

// Kiểm tra kết nối database
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Lỗi kết nối database: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Kết nối database thành công</p>";
}

// Kiểm tra session
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$student_id = $_SESSION['user_id'];

// Kiểm tra bảng de_tai_gia_han có tồn tại không
$table_check = $conn->query("SHOW TABLES LIKE 'de_tai_gia_han'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ Bảng de_tai_gia_han tồn tại</p>";
    
    // Kiểm tra cấu trúc bảng
    $structure = $conn->query("DESCRIBE de_tai_gia_han");
    echo "<h3>Cấu trúc bảng de_tai_gia_han:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Bảng de_tai_gia_han không tồn tại</p>";
}

// Kiểm tra bảng thong_bao
$table_check2 = $conn->query("SHOW TABLES LIKE 'thong_bao'");
if ($table_check2 && $table_check2->num_rows > 0) {
    echo "<p style='color: green;'>✅ Bảng thong_bao tồn tại</p>";
    
    // Kiểm tra cấu trúc bảng
    $structure2 = $conn->query("DESCRIBE thong_bao");
    echo "<h3>Cấu trúc bảng thong_bao:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure2->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Bảng thong_bao không tồn tại</p>";
}

// Kiểm tra đề tài của sinh viên có thể gia hạn
echo "<h2>Đề tài có thể gia hạn:</h2>";
$projects_sql = "SELECT dt.DT_MADT, dt.DT_TENDT, dt.DT_TRANGTHAI, dt.DT_TRE_TIENDO, dt.DT_SO_LAN_GIA_HAN,
                        hd.HD_NGAYKT as NGAY_KET_THUC_HIENTAI,
                        CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) as GV_HOTEN,
                        cttg.CTTG_VAITRO,
                        DATEDIFF(hd.HD_NGAYKT, CURDATE()) as SO_NGAY_CON_LAI
                 FROM de_tai_nghien_cuu dt
                 INNER JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT
                 INNER JOIN hop_dong hd ON dt.DT_MADT = hd.DT_MADT
                 LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                 WHERE cttg.SV_MASV = ? 
                 AND dt.DT_TRANGTHAI IN ('Đang thực hiện', 'Chờ duyệt')
                 ORDER BY hd.HD_NGAYKT ASC";

$stmt = $conn->prepare($projects_sql);
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $projects_result = $stmt->get_result();
    $projects = [];
    while ($row = $projects_result->fetch_assoc()) {
        $projects[] = $row;
    }
    $stmt->close();
    
    if (count($projects) > 0) {
        echo "<p style='color: green;'>✅ Tìm thấy " . count($projects) . " đề tài có thể gia hạn</p>";
        echo "<table border='1'>";
        echo "<tr><th>Mã đề tài</th><th>Tên đề tài</th><th>Trạng thái</th><th>Vai trò</th><th>Ngày kết thúc</th><th>Số ngày còn lại</th></tr>";
        foreach ($projects as $project) {
            echo "<tr>";
            echo "<td>{$project['DT_MADT']}</td>";
            echo "<td>{$project['DT_TENDT']}</td>";
            echo "<td>{$project['DT_TRANGTHAI']}</td>";
            echo "<td>{$project['CTTG_VAITRO']}</td>";
            echo "<td>{$project['NGAY_KET_THUC_HIENTAI']}</td>";
            echo "<td>{$project['SO_NGAY_CON_LAI']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Không có đề tài nào có thể gia hạn</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Lỗi khi chuẩn bị truy vấn: " . $conn->error . "</p>";
}

// Kiểm tra lịch sử gia hạn
echo "<h2>Lịch sử yêu cầu gia hạn:</h2>";
$extensions_sql = "SELECT gh.*, dt.DT_TENDT, dt.DT_TRANGTHAI,
                          CONCAT(ql.QL_HO, ' ', ql.QL_TEN) as NGUOI_DUYET_HOTEN,
                          DATEDIFF(NOW(), gh.GH_NGAYYEUCAU) as SO_NGAY_CHO
                   FROM de_tai_gia_han gh
                   INNER JOIN de_tai_nghien_cuu dt ON gh.DT_MADT = dt.DT_MADT
                   LEFT JOIN quan_ly_nghien_cuu ql ON gh.GH_NGUOIDUYET = ql.QL_MA
                   WHERE gh.SV_MASV = ?
                   ORDER BY gh.GH_NGAYYEUCAU DESC";

$stmt = $conn->prepare($extensions_sql);
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $extensions_result = $stmt->get_result();
    $extensions = [];
    while ($row = $extensions_result->fetch_assoc()) {
        $extensions[] = $row;
    }
    $stmt->close();
    
    if (count($extensions) > 0) {
        echo "<p style='color: green;'>✅ Tìm thấy " . count($extensions) . " yêu cầu gia hạn</p>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Đề tài</th><th>Từ ngày</th><th>Đến ngày</th><th>Số tháng</th><th>Trạng thái</th><th>Ngày yêu cầu</th></tr>";
        foreach ($extensions as $ext) {
            echo "<tr>";
            echo "<td>{$ext['GH_ID']}</td>";
            echo "<td>{$ext['DT_MADT']}</td>";
            echo "<td>{$ext['GH_NGAYHETHAN_CU']}</td>";
            echo "<td>{$ext['GH_NGAYHETHAN_MOI']}</td>";
            echo "<td>{$ext['GH_SOTHANGGIAHAN']}</td>";
            echo "<td>{$ext['GH_TRANGTHAI']}</td>";
            echo "<td>{$ext['GH_NGAYYEUCAU']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Chưa có yêu cầu gia hạn nào</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Lỗi khi chuẩn bị truy vấn gia hạn: " . $conn->error . "</p>";
}

echo "<h2>Test Form Gia Hạn:</h2>";
if (count($projects) > 0) {
    $test_project = $projects[0];
    echo "<form method='post' action='process_extension_request.php'>";
    echo "<input type='hidden' name='project_id' value='{$test_project['DT_MADT']}'>";
    echo "<input type='hidden' name='current_deadline' value='{$test_project['NGAY_KET_THUC_HIENTAI']}'>";
    echo "<input type='hidden' name='extension_months' value='2'>";
    echo "<input type='hidden' name='new_deadline' value='" . date('Y-m-d', strtotime($test_project['NGAY_KET_THUC_HIENTAI'] . ' +2 months')) . "'>";
    echo "<textarea name='extension_reason' placeholder='Lý do gia hạn (ít nhất 20 ký tự)'>Cần thêm thời gian để hoàn thành nghiên cứu do khó khăn trong việc thu thập dữ liệu</textarea><br><br>";
    echo "<button type='submit'>Test Gửi Yêu Cầu Gia Hạn</button>";
    echo "</form>";
}

echo "<p><a href='manage_extensions.php'>← Quay lại trang gia hạn</a></p>";
?>

