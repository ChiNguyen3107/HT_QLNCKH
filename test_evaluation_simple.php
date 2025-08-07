<?php
// Simple test for evaluation system
session_start();
require_once 'include/connect.php';

// Simulate student login
$_SESSION['user_id'] = 'SV001'; // Replace with real student ID that has projects
$_SESSION['role'] = 'student';

echo "<h2>Test Evaluation Update</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Test the evaluation update
    $member_id = $_POST['member_id'];
    $project_id = $_POST['project_id'];
    $test_score = floatval($_POST['test_score']);
    $test_comment = $_POST['test_comment'];
    
    // Simulate form data
    $_POST['criteria_id'] = ['TC001', 'TC002', 'TC003', 'TC004', 'TC005'];
    $_POST['score'] = [8.5, 12.0, 13.5, 25.0, 12.5];
    $_POST['criteria_comments'] = ['Good', 'Very good', 'Excellent', 'Outstanding', 'Good'];
    $_POST['overall_comment'] = $test_comment;
    $_POST['is_completed'] = '1';
    
    // Include the update script
    include 'view/student/update_member_criteria_score.php';
} else {
    // Show form
    echo "<form method='post'>";
    echo "<p>Member ID: <input type='text' name='member_id' value='GV000002' required /></p>";
    echo "<p>Project ID: <input type='text' name='project_id' value='DT0000001' required /></p>";
    echo "<p>Test Score: <input type='number' name='test_score' value='71.5' step='0.1' required /></p>";
    echo "<p>Test Comment: <textarea name='test_comment' required>This is a comprehensive evaluation of the project. The research demonstrates solid methodology and achieves meaningful results.</textarea></p>";
    echo "<p><button type='submit'>Test Evaluation Update</button></p>";
    echo "</form>";
    
    // Show available data
    echo "<h3>Available Test Data:</h3>";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    echo "<h4>Council Members:</h4>";
    $result = $conn->query("SELECT DISTINCT GV_MAGV, QD_SO FROM thanh_vien_hoi_dong LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "- Member: " . $row['GV_MAGV'] . " (Decision: " . $row['QD_SO'] . ")<br>";
    }
    
    echo "<h4>Projects:</h4>";
    $result = $conn->query("SELECT DT_MADT, DT_TENDT FROM de_tai_nghien_cuu LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['DT_MADT'] . ": " . htmlspecialchars($row['DT_TENDT']) . "<br>";
    }
    
    $conn->close();
}
?>
