<?php
// Test evaluation system
session_start();
require_once 'include/connect.php';

// Set test session (replace with actual user data)
$_SESSION['user_id'] = 'SV001'; // Replace with actual student ID
$_SESSION['role'] = 'student';

echo "<h2>Test Evaluation System</h2>";

// Test 1: Check connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection: " . $e->getMessage() . "</p>";
    exit();
}

// Test 2: Check criteria table
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tieu_chi WHERE TC_TRANGTHAI = 'Hoạt động'");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "<p>✅ Criteria table: Found $count active criteria</p>";
    
    // Show criteria details
    $stmt = $conn->prepare("SELECT TC_MATC, TC_NDDANHGIA, TC_DIEMTOIDA FROM tieu_chi WHERE TC_TRANGTHAI = 'Hoạt động' ORDER BY TC_THUTU");
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['TC_MATC']) . ": " . htmlspecialchars($row['TC_NDDANHGIA']) . " (Max: " . $row['TC_DIEMTOIDA'] . ")</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Criteria table: " . $e->getMessage() . "</p>";
}

// Test 3: Check member evaluation table
try {
    $stmt = $conn->prepare("DESCRIBE thanh_vien_hoi_dong");
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<p>✅ Member evaluation table structure:</p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Member evaluation table: " . $e->getMessage() . "</p>";
}

// Test 4: Test evaluation update simulation
echo "<h3>Test Evaluation Update (Simulation)</h3>";
echo "<form method='post' action='test_evaluation_update.php'>";
echo "<p>Member ID: <input type='text' name='member_id' value='GV000002' /></p>";
echo "<p>Project ID: <input type='text' name='project_id' value='DT0001' /></p>";
echo "<p>Test Score: <input type='number' name='test_score' value='85.5' step='0.1' /></p>";
echo "<p>Test Comment: <textarea name='test_comment'>Test evaluation comment</textarea></p>";
echo "<p><button type='submit'>Test Update</button></p>";
echo "</form>";

$conn->close();
?>
