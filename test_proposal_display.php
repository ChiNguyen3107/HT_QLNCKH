<?php
include 'include/connect.php';

// Test với đề tài DT0000001
$project_id = 'DT0000001';

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
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Không tìm thấy đề tài");
}

$project = $result->fetch_assoc();

echo "<h2>Test hiển thị file thuyết minh</h2>";
echo "<p><strong>Đề tài:</strong> " . htmlspecialchars($project['DT_TENDT']) . "</p>";
echo "<p><strong>DT_FILEBTM:</strong> '" . htmlspecialchars($project['DT_FILEBTM'] ?? 'NULL') . "'</p>";

// Test các điều kiện
echo "<h3>Test điều kiện:</h3>";
echo "<p>1. if (\$project['DT_FILEBTM']): " . ($project['DT_FILEBTM'] ? 'TRUE' : 'FALSE') . "</p>";
echo "<p>2. if (!empty(\$project['DT_FILEBTM'])): " . (!empty($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</p>";
echo "<p>3. if (isset(\$project['DT_FILEBTM']) && \$project['DT_FILEBTM']): " . (isset($project['DT_FILEBTM']) && $project['DT_FILEBTM'] ? 'TRUE' : 'FALSE') . "</p>";
echo "<p>4. if (isset(\$project['DT_FILEBTM']) && !empty(\$project['DT_FILEBTM'])): " . (isset($project['DT_FILEBTM']) && !empty($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</p>";

// Test hiển thị thực tế
echo "<h3>Test hiển thị:</h3>";

echo "<h4>Điều kiện 1: if (\$project['DT_FILEBTM'])</h4>";
if ($project['DT_FILEBTM']) {
    echo "<p style='color: green;'>✅ Hiển thị file thuyết minh</p>";
    $dtFile = $project['DT_FILEBTM'] ?? '';
    $proposalHref = '';
    if ($dtFile) {
        if (strpos($dtFile, '/') !== false || strpos($dtFile, '\\') !== false) {
            $webPath = preg_replace('#^\.\./\.\./#', '', str_replace('\\\\','/',$dtFile));
            $proposalHref = '/NLNganh/' . ltrim($webPath, '/');
        } else {
            $proposalHref = '/NLNganh/uploads/project_files/' . $dtFile;
        }
    }
    echo "<a href='" . htmlspecialchars($proposalHref) . "' class='btn btn-outline-primary' download>";
    echo "<i class='fas fa-file-download mr-2'></i> Tải xuống file thuyết minh";
    echo "</a>";
} else {
    echo "<p style='color: red;'>❌ Không hiển thị file thuyết minh</p>";
}

echo "<h4>Điều kiện 2: if (!empty(\$project['DT_FILEBTM']))</h4>";
if (!empty($project['DT_FILEBTM'])) {
    echo "<p style='color: green;'>✅ Hiển thị file thuyết minh</p>";
    $dtFile = $project['DT_FILEBTM'] ?? '';
    $proposalHref = '';
    if ($dtFile) {
        if (strpos($dtFile, '/') !== false || strpos($dtFile, '\\') !== false) {
            $webPath = preg_replace('#^\.\./\.\./#', '', str_replace('\\\\','/',$dtFile));
            $proposalHref = '/NLNganh/' . ltrim($webPath, '/');
        } else {
            $proposalHref = '/NLNganh/uploads/project_files/' . $dtFile;
        }
    }
    echo "<a href='" . htmlspecialchars($proposalHref) . "' class='btn btn-outline-primary' download>";
    echo "<i class='fas fa-file-download mr-2'></i> Tải xuống file thuyết minh";
    echo "</a>";
} else {
    echo "<p style='color: red;'>❌ Không hiển thị file thuyết minh</p>";
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
?>




