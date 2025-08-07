<?php
// Simulate real form submission from view_project.php
session_start();

// Set up session like a real student login
$_SESSION['user_id'] = 'B2110051';  // Real student ID from database
$_SESSION['role'] = 'student';

echo "<h2>Real Form Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Forward to the actual script
    echo "<h3>Testing update_member_criteria_score.php:</h3>";
    ob_start();
    include 'view/student/update_member_criteria_score.php';
    $output = ob_get_clean();
    echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    echo htmlspecialchars($output);
    echo "</div>";
} else {
    // Show form that mimics the real one
    echo "<form method='post'>";
    echo "<h3>Test với dữ liệu thực từ database:</h3>";
    
    // Use real data from our debug
    echo "<input type='hidden' name='project_id' value='DT0000001'>";
    echo "<input type='hidden' name='member_id' value='GV000002'>";
    echo "<input type='hidden' name='decision_id' value='QDDT0'>";
    
    // Criteria data
    echo "<input type='hidden' name='criteria_id[]' value='TC001'>";
    echo "<input type='hidden' name='criteria_id[]' value='TC002'>";
    echo "<input type='hidden' name='criteria_id[]' value='TC003'>";
    echo "<input type='hidden' name='criteria_id[]' value='TC004'>";
    echo "<input type='hidden' name='criteria_id[]' value='TC005'>";
    
    // Scores
    echo "<input type='hidden' name='score[]' value='8.5'>";
    echo "<input type='hidden' name='score[]' value='12.0'>";
    echo "<input type='hidden' name='score[]' value='13.5'>";
    echo "<input type='hidden' name='score[]' value='25.0'>";
    echo "<input type='hidden' name='score[]' value='12.5'>";
    
    // Comments
    echo "<input type='hidden' name='criteria_comments[]' value='Good overview'>";
    echo "<input type='hidden' name='criteria_comments[]' value='Clear objectives'>";
    echo "<input type='hidden' name='criteria_comments[]' value='Sound methodology'>";
    echo "<input type='hidden' name='criteria_comments[]' value='Excellent content'>";
    echo "<input type='hidden' name='criteria_comments[]' value='Good contribution'>";
    
    echo "<input type='hidden' name='overall_comment' value='Comprehensive evaluation with good methodology.'>";
    echo "<input type='hidden' name='is_completed' value='1'>";
    
    echo "<p><strong>Test Parameters:</strong></p>";
    echo "<ul>";
    echo "<li>Student ID: B2110051 (from session)</li>";
    echo "<li>Project ID: DT0000001</li>";
    echo "<li>Member ID: GV000002</li>";
    echo "<li>Decision ID: QDDT0</li>";
    echo "</ul>";
    
    echo "<button type='submit' style='background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer;'>Test Real Form Submission</button>";
    echo "</form>";
}
?>
