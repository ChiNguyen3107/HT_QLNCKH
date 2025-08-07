<?php
// File debug để kiểm tra cấu trúc bảng và dữ liệu quyết định nghiệm thu
include 'include/connect.php';

$project_id = "DT001"; // Thay đổi theo mã đề tài thực tế

echo "<h2>Debug - Kiểm tra dữ liệu quyết định nghiệm thu</h2>";
echo "<p>Đang kiểm tra đề tài: <strong>$project_id</strong></p>";

// 1. Kiểm tra cấu trúc bảng de_tai_nghien_cuu
echo "<h3>1. Cấu trúc bảng de_tai_nghien_cuu:</h3>";
$desc_sql = "DESCRIBE de_tai_nghien_cuu";
$result = $conn->query($desc_sql);
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// 2. Kiểm tra dữ liệu đề tài
echo "<h3>2. Dữ liệu đề tài:</h3>";
$project_sql = "SELECT * FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
$stmt = $conn->prepare($project_sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();
$project_result = $stmt->get_result();
if ($project_result->num_rows > 0) {
    $project_data = $project_result->fetch_assoc();
    echo "<pre>";
    print_r($project_data);
    echo "</pre>";
} else {
    echo "<p>❌ Không tìm thấy đề tài với mã: $project_id</p>";
}

// 3. Kiểm tra cấu trúc bảng quyet_dinh_nghiem_thu
echo "<h3>3. Cấu trúc bảng quyet_dinh_nghiem_thu:</h3>";
$desc_sql2 = "DESCRIBE quyet_dinh_nghiem_thu";
$result2 = $conn->query($desc_sql2);
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = $result2->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// 4. Kiểm tra tất cả quyết định nghiệm thu
echo "<h3>4. Tất cả quyết định nghiệm thu:</h3>";
$all_decisions_sql = "SELECT * FROM quyet_dinh_nghiem_thu LIMIT 10";
$all_result = $conn->query($all_decisions_sql);
if ($all_result->num_rows > 0) {
    echo "<table border='1'>";
    $first = true;
    while ($row = $all_result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Không có dữ liệu trong bảng quyet_dinh_nghiem_thu</p>";
}

// 5. Thử các cách query khác nhau
echo "<h3>5. Thử các cách query quyết định:</h3>";

// Cách 1: Query ban đầu (có thể sai)
echo "<h4>Cách 1 - Query ban đầu:</h4>";
$sql1 = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
        FROM quyet_dinh_nghiem_thu qd
        LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
        WHERE qd.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("s", $project_id);
$stmt1->execute();
$result1 = $stmt1->get_result();
echo "Số kết quả: " . $result1->num_rows . "<br>";

// Cách 2: Query mới với JOIN
echo "<h4>Cách 2 - Query với JOIN:</h4>";
$sql2 = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
        FROM quyet_dinh_nghiem_thu qd
        LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
        LEFT JOIN de_tai_nghien_cuu dt ON qd.QD_SO = dt.QD_SO
        WHERE dt.DT_MADT = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("s", $project_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
echo "Số kết quả: " . $result2->num_rows . "<br>";

// Cách 3: Thử với các trường khác có thể liên kết
echo "<h4>Cách 3 - Tìm mối liên hệ khác:</h4>";
// Có thể quyết định liên kết qua DT_MADT trực tiếp?
$sql3 = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
        FROM quyet_dinh_nghiem_thu qd
        LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
        WHERE qd.DT_MADT = ?";
$stmt3 = $conn->prepare($sql3);
if ($stmt3) {
    $stmt3->bind_param("s", $project_id);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    echo "Số kết quả (query trực tiếp): " . $result3->num_rows . "<br>";
} else {
    echo "❌ Không có trường DT_MADT trong bảng quyet_dinh_nghiem_thu<br>";
}

// 6. Kiểm tra bảng bien_ban
echo "<h3>6. Cấu trúc bảng bien_ban:</h3>";
$desc_sql3 = "DESCRIBE bien_ban";
$result3 = $conn->query($desc_sql3);
if ($result3) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result3->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Bảng bien_ban không tồn tại<br>";
}

echo "<hr>";
echo "<p><strong>Hướng dẫn:</strong> Chạy file này để xem cấu trúc database thực tế và tìm ra cách query đúng.</p>";
?>
