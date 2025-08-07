<?php
// Check Session Status
session_start();

echo "<h2>🔍 Kiểm tra Session Status</h2>";

echo "<h3>Session Information:</h3>";
echo "<ul>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "<li><strong>Session Status:</strong> " . session_status() . " (1=disabled, 2=active)</li>";
echo "<li><strong>Session Name:</strong> " . session_name() . "</li>";
echo "</ul>";

echo "<h3>Session Data:</h3>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>❌ Session rỗng - Chưa login!</p>";
    echo "<p><strong>Giải pháp:</strong> Hãy login vào hệ thống trước khi test upload.</p>";
    echo "<p><a href='login.php'>→ Đến trang login</a></p>";
} else {
    echo "<ul>";
    foreach ($_SESSION as $key => $value) {
        echo "<li><strong>$key:</strong> " . (is_array($value) ? json_encode($value) : $value) . "</li>";
    }
    echo "</ul>";
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        echo "<p style='color: green;'>✅ Session hợp lệ! Có thể test upload.</p>";
    } else {
        echo "<p style='color: red;'>❌ Session thiếu user_id hoặc role!</p>";
    }
}

echo "<h3>Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='test_upload_simple.html'>Test Upload Form</a></li>";
echo "<li><a href='view/student/student_login.php'>Student Login</a></li>";
echo "<li><a href='login.php'>Main Login</a></li>";
echo "</ul>";

// Kiểm tra database connection
echo "<h3>Database Status:</h3>";
try {
    require_once 'include/connect.php';
    echo "<p style='color: green;'>✅ Database connection OK</p>";
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM file_dinh_kem");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>📊 Total files in database: " . $row['count'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
