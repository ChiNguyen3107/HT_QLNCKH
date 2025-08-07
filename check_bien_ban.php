 <?php
include 'include/connect.php';

echo "<h3>Cấu trúc bảng bien_ban:</h3>";
$result = $conn->query('DESCRIBE bien_ban');
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Lỗi: " . $conn->error;
}

echo "<br><h3>Dữ liệu mẫu trong bien_ban:</h3>";
$result2 = $conn->query('SELECT * FROM bien_ban LIMIT 3');
if ($result2 && $result2->num_rows > 0) {
    echo "<table border='1'>";
    $first = true;
    while($row = $result2->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
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
}
?>
