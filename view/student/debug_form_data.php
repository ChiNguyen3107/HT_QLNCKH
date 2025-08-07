<?php
// Script debug đơn giản để kiểm tra dữ liệu form
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>DEBUG FORM DATA</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>1. Tất cả dữ liệu POST:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h3>2. Dữ liệu quan trọng:</h3>";
    $project_id = $_POST['project_id'] ?? '';
    $decision_id = $_POST['decision_id'] ?? '';
    $report_id = $_POST['report_id'] ?? '';
    $acceptance_date = $_POST['acceptance_date'] ?? '';
    $evaluation_grade = $_POST['evaluation_grade'] ?? '';
    $total_score = $_POST['total_score'] ?? '';
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th><th>Length</th><th>Empty?</th></tr>";
    
    $fields = [
        'project_id' => $project_id,
        'decision_id' => $decision_id,
        'report_id' => $report_id,
        'acceptance_date' => $acceptance_date,
        'evaluation_grade' => $evaluation_grade,
        'total_score' => $total_score
    ];
    
    foreach ($fields as $name => $value) {
        $length = strlen($value);
        $isEmpty = empty($value) ? 'YES' : 'NO';
        $style = empty($value) ? 'background:lightcoral;' : 'background:lightgreen;';
        
        echo "<tr style='$style'>";
        echo "<td>$name</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>$length</td>";
        echo "<td>$isEmpty</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Validation check
    echo "<h3>3. Validation:</h3>";
    $errors = [];
    if (empty($project_id)) $errors[] = "project_id is empty";
    if (empty($decision_id)) $errors[] = "decision_id is empty";
    if (empty($acceptance_date)) $errors[] = "acceptance_date is empty";
    if (empty($evaluation_grade)) $errors[] = "evaluation_grade is empty";
    
    if (empty($errors)) {
        echo "<p style='color:green;'>✓ Validation passed</p>";
        
        // Test database connection and query
        echo "<h3>4. Database test:</h3>";
        include '../include/connect.php';
        
        if ($conn) {
            echo "<p style='color:green;'>✓ Database connected</p>";
            
            // Check if decision exists
            $check_decision = "SELECT QD_SO, BB_SOBB FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
            $stmt_check = $conn->prepare($check_decision);
            if ($stmt_check) {
                $stmt_check->bind_param("s", $decision_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    $decision_data = $result_check->fetch_assoc();
                    echo "<p style='color:green;'>✓ Decision found: " . $decision_data['QD_SO'] . "</p>";
                    echo "<p>BB_SOBB in decision: " . $decision_data['BB_SOBB'] . "</p>";
                    
                    // Check existing report
                    $check_report = "SELECT BB_SOBB, BB_XEPLOAI FROM bien_ban WHERE QD_SO = ?";
                    $stmt_report = $conn->prepare($check_report);
                    $stmt_report->bind_param("s", $decision_id);
                    $stmt_report->execute();
                    $result_report = $stmt_report->get_result();
                    
                    echo "<p>Found " . $result_report->num_rows . " report(s) for this decision:</p>";
                    while ($report_row = $result_report->fetch_assoc()) {
                        echo "<p>- " . $report_row['BB_SOBB'] . " (" . $report_row['BB_XEPLOAI'] . ")</p>";
                    }
                    
                } else {
                    echo "<p style='color:red;'>✗ Decision not found: $decision_id</p>";
                }
            } else {
                echo "<p style='color:red;'>✗ Prepare failed: " . $conn->error . "</p>";
            }
            
        } else {
            echo "<p style='color:red;'>✗ Database connection failed</p>";
        }
        
    } else {
        echo "<p style='color:red;'>✗ Validation failed:</p>";
        foreach ($errors as $error) {
            echo "<p style='color:red;'>- $error</p>";
        }
    }
    
} else {
    // Display form for testing
    echo "<h3>Test Form:</h3>";
    echo '<form method="POST">';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><td>Project ID:</td><td><input type="text" name="project_id" value="DT001" /></td></tr>';
    echo '<tr><td>Decision ID:</td><td><input type="text" name="decision_id" value="123ab" /></td></tr>';
    echo '<tr><td>Report ID:</td><td><input type="text" name="report_id" value="BB3abc" /></td></tr>';
    echo '<tr><td>Acceptance Date:</td><td><input type="date" name="acceptance_date" value="2024-12-15" /></td></tr>';
    echo '<tr><td>Evaluation Grade:</td><td><select name="evaluation_grade"><option value="Xuất sắc">Xuất sắc</option><option value="Tốt">Tốt</option><option value="Khá">Khá</option><option value="Trung bình">Trung bình</option></select></td></tr>';
    echo '<tr><td>Total Score:</td><td><input type="number" name="total_score" value="90.5" step="0.1" /></td></tr>';
    echo '<tr><td colspan="2"><input type="submit" value="Test Submit" /></td></tr>';
    echo '</table>';
    echo '</form>';
}
?>
