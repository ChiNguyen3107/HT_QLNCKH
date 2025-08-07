<?php
// Script to create tien_do_de_tai table if it doesn't exist and test functionality
include_once 'config/config.php';

echo "<h2>Fix Progress Table Issue</h2>";

// Kiểm tra và tạo table nếu chưa có
$create_table_sql = "
CREATE TABLE IF NOT EXISTS `tien_do_de_tai` (
  `TDDT_MA` char(10) NOT NULL,
  `DT_MADT` char(10) NOT NULL,
  `SV_MASV` char(8) NOT NULL,
  `TDDT_TIEUDE` varchar(200) NOT NULL,
  `TDDT_NOIDUNG` text NOT NULL,
  `TDDT_PHANTRAMHOANTHANH` int(11) DEFAULT 0,
  `TDDT_FILE` varchar(255) DEFAULT NULL,
  `TDDT_NGAYCAPNHAT` datetime NOT NULL,
  PRIMARY KEY (`TDDT_MA`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

echo "<h3>1. Creating/Verifying table structure...</h3>";
if (mysqli_query($conn, $create_table_sql)) {
    echo "<p style='color: green;'>✓ Table 'tien_do_de_tai' created/verified successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . mysqli_error($conn) . "</p>";
}

// Kiểm tra cấu trúc table
echo "<h3>2. Current Table Structure:</h3>";
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
} else {
    echo "<p style='color: red;'>✗ Cannot describe table: " . mysqli_error($conn) . "</p>";
}

// Test function to generate unique progress ID
echo "<h3>3. Testing Progress ID Generation:</h3>";
function generateUniqueProgressId($conn) {
    $max_attempts = 5;
    $attempt = 0;
    
    do {
        $attempt++;
        $progress_id = 'TD' . date('ymd') . sprintf('%02d', rand(10, 99));
        
        // Check if ID already exists
        $check_sql = "SELECT TDDT_MA FROM tien_do_de_tai WHERE TDDT_MA = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param("s", $progress_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows == 0) {
                return $progress_id; // Unique ID found
            }
        }
    } while ($attempt < $max_attempts);
    
    return false; // Failed to generate unique ID
}

$test_id = generateUniqueProgressId($conn);
if ($test_id) {
    echo "<p style='color: green;'>✓ Generated unique progress ID: " . $test_id . "</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to generate unique progress ID</p>";
}

// Test insert operation
echo "<h3>4. Testing Insert Operation:</h3>";
if ($test_id) {
    $test_project_id = 'TEST001';
    $test_user_id = 'SV001';
    $test_title = 'Test progress entry - ' . date('Y-m-d H:i:s');
    $test_content = 'This is a test progress entry to verify the functionality works properly.';
    
    $test_sql = "INSERT INTO tien_do_de_tai (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_NGAYCAPNHAT, TDDT_PHANTRAMHOANTHANH) 
                VALUES (?, ?, ?, ?, ?, NOW(), 100)";
    $stmt = $conn->prepare($test_sql);
    
    if (!$stmt) {
        echo "<p style='color: red;'>✗ Failed to prepare statement: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param("sssss", $test_id, $test_project_id, $test_user_id, $test_title, $test_content);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Test insert successful</p>";
            echo "<p>Inserted record details:</p>";
            echo "<ul>";
            echo "<li>ID: " . $test_id . "</li>";
            echo "<li>Project: " . $test_project_id . "</li>";
            echo "<li>User: " . $test_user_id . "</li>";
            echo "<li>Title: " . $test_title . "</li>";
            echo "</ul>";
            
            // Cleanup test data
            $cleanup = $conn->prepare("DELETE FROM tien_do_de_tai WHERE TDDT_MA = ?");
            $cleanup->bind_param("s", $test_id);
            if ($cleanup->execute()) {
                echo "<p style='color: blue;'>✓ Test data cleaned up successfully</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Warning: Could not clean up test data</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Test insert failed: " . $stmt->error . "</p>";
            echo "<p>Debug info:</p>";
            echo "<ul>";
            echo "<li>Progress ID: " . $test_id . " (length: " . strlen($test_id) . ")</li>";
            echo "<li>Project ID: " . $test_project_id . " (length: " . strlen($test_project_id) . ")</li>";
            echo "<li>User ID: " . $test_user_id . " (length: " . strlen($test_user_id) . ")</li>";
            echo "</ul>";
        }
    }
}

echo "<h3>5. Summary:</h3>";
echo "<p>If all tests above pass, the progress logging functionality should work correctly.</p>";
echo "<p>If there are still issues, check:</p>";
echo "<ul>";
echo "<li>Database permissions</li>";
echo "<li>Field length constraints (TDDT_MA: 10 chars, DT_MADT: 10 chars, SV_MASV: 8 chars)</li>";
echo "<li>Character encoding issues</li>";
echo "</ul>";

mysqli_close($conn);
?>
