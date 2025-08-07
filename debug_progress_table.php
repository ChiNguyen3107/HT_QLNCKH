<?php
// Test script để kiểm tra table tien_do_de_tai và debug lỗi tiến độ
include_once 'config/config.php';

echo "<h2>Debug Progress Table Issue</h2>";

// Kiểm tra xem table tien_do_de_tai có tồn tại không
echo "<h3>1. Checking if table 'tien_do_de_tai' exists...</h3>";
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tien_do_de_tai'");
if (mysqli_num_rows($check_table) > 0) {
    echo "<p style='color: green;'>✓ Table 'tien_do_de_tai' exists</p>";
    
    // Kiểm tra cấu trúc table
    echo "<h3>2. Table Structure:</h3>";
    $structure = mysqli_query($conn, "DESCRIBE tien_do_de_tai");
    if ($structure) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = mysqli_fetch_assoc($structure)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test insert với sample data
    echo "<h3>3. Testing Insert Operation:</h3>";
    $test_progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));
    $test_project_id = 'TEST001';
    $test_user_id = 'SV001';
    $test_title = 'Test progress entry';
    $test_content = 'This is a test progress entry';
    
    $test_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
                VALUES (?, ?, ?, ?, ?, NOW(), 100)";
    $stmt = $conn->prepare($test_sql);
    
    if (!$stmt) {
        echo "<p style='color: red;'>✗ Failed to prepare statement: " . $conn->error . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Statement prepared successfully</p>";
        
        $stmt->bind_param("sssss", $test_progress_id, $test_project_id, $test_user_id, $test_title, $test_content);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Test insert successful with ID: " . $test_progress_id . "</p>";
            
            // Cleanup test data
            $cleanup = $conn->prepare("DELETE FROM tien_do_de_tai WHERE TDDT_MA = ?");
            $cleanup->bind_param("s", $test_progress_id);
            $cleanup->execute();
            echo "<p style='color: blue;'>Test data cleaned up</p>";
        } else {
            echo "<p style='color: red;'>✗ Test insert failed: " . $stmt->error . "</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>✗ Table 'tien_do_de_tai' does not exist</p>";
    
    // Kiểm tra các table tương tự
    echo "<h3>Looking for similar tables:</h3>";
    $all_tables = mysqli_query($conn, "SHOW TABLES");
    while ($table = mysqli_fetch_array($all_tables)) {
        if (strpos(strtolower($table[0]), 'tien') !== false || strpos(strtolower($table[0]), 'progress') !== false) {
            echo "<p>Found similar table: " . $table[0] . "</p>";
        }
    }
}

// Kiểm tra quyền INSERT
echo "<h3>4. Checking INSERT permissions:</h3>";
$test_permission = mysqli_query($conn, "SHOW GRANTS");
if ($test_permission) {
    echo "<p style='color: green;'>✓ Can execute SHOW GRANTS (database connection working)</p>";
} else {
    echo "<p style='color: red;'>✗ Cannot check permissions: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
?>
