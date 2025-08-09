<?php
session_start();
require_once '../config/database.php';

// Lấy project ID từ URL hoặc mặc định
$project_id = isset($_GET['id']) ? $_GET['id'] : 'DT0000001';

echo "<h2>Debug File Status cho đề tài: $project_id</h2>";

// 1. Kiểm tra file thuyết minh
echo "<h3>1. File thuyết minh:</h3>";
$proposal_sql = "SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($proposal_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo "File thuyết minh: " . ($row['DT_FILEBTM'] ? $row['DT_FILEBTM'] : 'NULL') . "<br>";
    echo "Status: " . ($row['DT_FILEBTM'] ? '✅ Có' : '❌ Không có') . "<br>";
} else {
    echo "❌ Không tìm thấy đề tài<br>";
}

// 2. Kiểm tra cấu trúc bảng hợp đồng
echo "<h3>2. Cấu trúc bảng hợp đồng:</h3>";
$describe_sql = "DESCRIBE hop_dong";
$result = $conn->query($describe_sql);
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
}

// 3. Kiểm tra dữ liệu hợp đồng
echo "<h3>3. Dữ liệu hợp đồng:</h3>";
$contract_sql = "SELECT * FROM hop_dong WHERE DT_MADT = ?";
$stmt = $conn->prepare($contract_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo "Tìm thấy hợp đồng:<br>";
    foreach ($row as $key => $value) {
        echo "- $key: " . ($value ?: 'NULL') . "<br>";
    }
    // Kiểm tra các trường file có thể có
    $file_fields = ['HD_FILE', 'HD_FILEHD', 'FILE_PATH', 'FILE_NAME'];
    echo "<br>Kiểm tra file hợp đồng:<br>";
    foreach ($file_fields as $field) {
        if (isset($row[$field])) {
            echo "- $field: " . ($row[$field] ?: 'NULL') . " (" . ($row[$field] ? '✅' : '❌') . ")<br>";
        }
    }
} else {
    echo "❌ Không tìm thấy hợp đồng<br>";
}

// 4. Kiểm tra quyết định nghiệm thu
echo "<h3>4. Quyết định nghiệm thu:</h3>";
$decision_sql = "SELECT dt.QD_SO, qd.* FROM de_tai_nghien_cuu dt 
                LEFT JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO 
                WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($decision_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo "QD_SO trong đề tài: " . ($row['QD_SO'] ?: 'NULL') . "<br>";
    if ($row['QD_SO']) {
        echo "Thông tin quyết định:<br>";
        foreach ($row as $key => $value) {
            if ($key != 'QD_SO') {
                echo "- $key: " . ($value ?: 'NULL') . "<br>";
            }
        }
    }
} else {
    echo "❌ Không tìm thấy thông tin<br>";
}

// 5. Kiểm tra biên bản
echo "<h3>5. Biên bản nghiệm thu:</h3>";
$bb_sql = "SELECT bb.* FROM de_tai_nghien_cuu dt 
           INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
           LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
           WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($bb_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if ($row['BB_SOBB']) {
        echo "Tìm thấy biên bản:<br>";
        foreach ($row as $key => $value) {
            echo "- $key: " . ($value ?: 'NULL') . "<br>";
        }
    } else {
        echo "❌ Chưa có biên bản<br>";
    }
} else {
    echo "❌ Không tìm thấy biên bản<br>";
}

// 6. Kiểm tra file đánh giá
echo "<h3>6. File đánh giá:</h3>";
$eval_sql = "SELECT fg.*, bb.BB_SOBB FROM de_tai_nghien_cuu dt 
             INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO
             LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
             LEFT JOIN file_danh_gia fg ON bb.BB_SOBB = fg.BB_SOBB
             WHERE dt.DT_MADT = ?";
$stmt = $conn->prepare($eval_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$eval_files = [];
while ($row = $result->fetch_assoc()) {
    if ($row['BB_SOBB']) {
        $eval_files[] = $row;
    }
}

if (!empty($eval_files)) {
    echo "Tìm thấy " . count($eval_files) . " file đánh giá:<br>";
    foreach ($eval_files as $file) {
        echo "- File: " . ($file['FDG_TENFILE'] ?: 'NULL') . "<br>";
        echo "  Đường dẫn: " . ($file['FDG_DUONGDAN'] ?: 'NULL') . "<br>";
        echo "  BB_SOBB: " . ($file['BB_SOBB'] ?: 'NULL') . "<br><br>";
    }
} else {
    echo "❌ Không tìm thấy file đánh giá<br>";
}

// 7. Tóm tắt trạng thái
echo "<h3>7. Tóm tắt trạng thái:</h3>";
$current_status = [
    'proposal' => false,
    'contract' => false, 
    'decision' => false,
    'evaluation' => false
];

// Kiểm tra lại từng file
$stmt = $conn->prepare("SELECT DT_FILEBTM FROM de_tai_nghien_cuu WHERE DT_MADT = ? AND DT_FILEBTM IS NOT NULL AND DT_FILEBTM != ''");
$stmt->bind_param("s", $project_id);
$stmt->execute();
$current_status['proposal'] = ($stmt->get_result()->num_rows > 0);

// Thử cả HD_FILE và HD_FILEHD
$stmt = $conn->prepare("SELECT * FROM hop_dong WHERE DT_MADT = ? AND (HD_FILE IS NOT NULL AND HD_FILE != '' OR HD_FILEHD IS NOT NULL AND HD_FILEHD != '')");
$stmt->bind_param("s", $project_id);
$stmt->execute();
$current_status['contract'] = ($stmt->get_result()->num_rows > 0);

$stmt = $conn->prepare("SELECT qd.QD_FILE, bb.BB_SOBB FROM de_tai_nghien_cuu dt INNER JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO WHERE dt.DT_MADT = ? AND qd.QD_FILE IS NOT NULL AND qd.QD_FILE != '' AND bb.BB_SOBB IS NOT NULL");
$stmt->bind_param("s", $project_id);
$stmt->execute();
$current_status['decision'] = ($stmt->get_result()->num_rows > 0);

if ($current_status['decision']) {
    $stmt = $conn->prepare("SELECT COUNT(*) as file_count FROM file_danh_gia fg INNER JOIN bien_ban bb ON fg.BB_SOBB = bb.BB_SOBB INNER JOIN quyet_dinh_nghiem_thu qd ON bb.QD_SO = qd.QD_SO INNER JOIN de_tai_nghien_cuu dt ON dt.QD_SO = qd.QD_SO WHERE dt.DT_MADT = ?");
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_status['evaluation'] = ($row['file_count'] > 0);
}

foreach ($current_status as $type => $status) {
    echo "- " . ucfirst($type) . ": " . ($status ? '✅ Hoàn thành' : '❌ Chưa hoàn thành') . "<br>";
}

$all_complete = $current_status['proposal'] && $current_status['contract'] && $current_status['decision'] && $current_status['evaluation'];
echo "<br><strong>Tổng trạng thái: " . ($all_complete ? '✅ Đầy đủ - Có thể hoàn thành' : '❌ Chưa đầy đủ') . "</strong>";
?>
