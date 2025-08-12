<?php
include 'include/connect.php';

// Lấy ID đề tài từ URL
$project_id = isset($_GET['id']) ? trim($_GET['id']) : 'DT0000001';

echo "<h2>Debug File Thuyết Minh - Đề tài: $project_id</h2>";

// Query để lấy thông tin đề tài
$sql = "SELECT dt.*, 
               CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
               gv.GV_EMAIL,
               ldt.LDT_TENLOAI,
               lvnc.LVNC_TEN,
               lvut.LVUT_TEN,
               hd.HD_NGAYTAO,
               hd.HD_NGAYBD,
               hd.HD_NGAYKT,
               hd.HD_TONGKINHPHI
        FROM de_tai_nghien_cuu dt
        LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
        LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
        LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
        LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
        LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
        WHERE dt.DT_MADT = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
}

$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color: red;'>Không tìm thấy đề tài với mã số $project_id</p>";
    exit;
}

$project = $result->fetch_assoc();

echo "<h3>Thông tin đề tài:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Trường</th><th>Giá trị</th></tr>";
echo "<tr><td>DT_MADT</td><td>" . htmlspecialchars($project['DT_MADT']) . "</td></tr>";
echo "<tr><td>DT_TENDT</td><td>" . htmlspecialchars($project['DT_TENDT']) . "</td></tr>";
echo "<tr><td>DT_FILEBTM</td><td>" . htmlspecialchars($project['DT_FILEBTM'] ?? 'NULL') . "</td></tr>";
echo "<tr><td>DT_FILEBTM (empty check)</td><td>" . (empty($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</td></tr>";
echo "<tr><td>DT_FILEBTM (isset check)</td><td>" . (isset($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</td></tr>";
echo "<tr><td>DT_FILEBTM (is_null check)</td><td>" . (is_null($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</td></tr>";
echo "<tr><td>DT_TRANGTHAI</td><td>" . htmlspecialchars($project['DT_TRANGTHAI']) . "</td></tr>";
echo "</table>";

// Kiểm tra trực tiếp trong database
echo "<h3>Kiểm tra trực tiếp trong database:</h3>";
$direct_sql = "SELECT DT_MADT, DT_FILEBTM, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$direct_stmt = $conn->prepare($direct_sql);
$direct_stmt->bind_param("s", $project_id);
$direct_stmt->execute();
$direct_result = $direct_stmt->get_result();

if ($direct_result->num_rows > 0) {
    $direct_row = $direct_result->fetch_assoc();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Trường</th><th>Giá trị</th></tr>";
    echo "<tr><td>DT_MADT</td><td>" . htmlspecialchars($direct_row['DT_MADT']) . "</td></tr>";
    echo "<tr><td>DT_FILEBTM</td><td>" . htmlspecialchars($direct_row['DT_FILEBTM'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>DT_TRANGTHAI</td><td>" . htmlspecialchars($direct_row['DT_TRANGTHAI']) . "</td></tr>";
    echo "</table>";
} else {
    echo "<p style='color: red;'>Không tìm thấy đề tài trong database</p>";
}

// Kiểm tra file có tồn tại không
if (!empty($project['DT_FILEBTM'])) {
    $file_path = 'uploads/project_files/' . $project['DT_FILEBTM'];
    echo "<h3>Kiểm tra file:</h3>";
    echo "<p>File path: $file_path</p>";
    echo "<p>File exists: " . (file_exists($file_path) ? 'TRUE' : 'FALSE') . "</p>";
    echo "<p>File readable: " . (is_readable($file_path) ? 'TRUE' : 'FALSE') . "</p>";
    if (file_exists($file_path)) {
        echo "<p>File size: " . filesize($file_path) . " bytes</p>";
    }
}

// Kiểm tra lịch sử file thuyết minh
echo "<h3>Lịch sử file thuyết minh:</h3>";
$hist_sql = "SELECT * FROM lich_su_thuyet_minh WHERE DT_MADT = ? ORDER BY NGAY_TAI DESC, ID DESC";
$hist_stmt = $conn->prepare($hist_sql);
$hist_stmt->bind_param("s", $project_id);
$hist_stmt->execute();
$hist_result = $hist_stmt->get_result();

if ($hist_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>File tên</th><th>Lý do</th><th>Ngày tải</th><th>Là hiện tại</th></tr>";
    while ($hist_row = $hist_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($hist_row['ID']) . "</td>";
        echo "<td>" . htmlspecialchars($hist_row['FILE_TEN']) . "</td>";
        echo "<td>" . htmlspecialchars($hist_row['LY_DO']) . "</td>";
        echo "<td>" . htmlspecialchars($hist_row['NGAY_TAI']) . "</td>";
        echo "<td>" . ($hist_row['LA_HIEN_TAI'] ? 'Có' : 'Không') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có lịch sử file thuyết minh</p>";
}

echo "<h3>Test điều kiện hiển thị:</h3>";
echo "<p>if (\$project['DT_FILEBTM']): " . ($project['DT_FILEBTM'] ? 'TRUE' : 'FALSE') . "</p>";
echo "<p>if (!empty(\$project['DT_FILEBTM'])): " . (!empty($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</p>";
echo "<p>if (isset(\$project['DT_FILEBTM']) && \$project['DT_FILEBTM']): " . (isset($project['DT_FILEBTM']) && $project['DT_FILEBTM'] ? 'TRUE' : 'FALSE') . "</p>";
?>




