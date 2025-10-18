<?php
// File đơn giản thay thế update_report_basic.php - KHÔNG CHECK SESSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

// BỎ QUA SESSION CHECK ĐỂ TEST
// session_start();
include '../../include/connect.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<p style='color:red;'>Invalid request method</p>";
    echo "<p><a href='debug_form_data.php'>Go to debug form</a></p>";
    exit();
}

// Log tất cả dữ liệu nhận được
error_log("POST data: " . print_r($_POST, true));

$project_id = $_POST['project_id'] ?? '';
$decision_id = $_POST['decision_id'] ?? '';
$report_id = $_POST['report_id'] ?? '';
$acceptance_date = $_POST['acceptance_date'] ?? '';
$evaluation_grade = $_POST['evaluation_grade'] ?? '';
$total_score = $_POST['total_score'] ?? '';

// Debug output
echo "<h3>Debug Information</h3>";
echo "<p>Project ID: " . htmlspecialchars($project_id) . "</p>";
echo "<p>Decision ID: " . htmlspecialchars($decision_id) . "</p>";
echo "<p>Report ID: " . htmlspecialchars($report_id) . "</p>";
echo "<p>Acceptance Date: " . htmlspecialchars($acceptance_date) . "</p>";
echo "<p>Evaluation Grade: " . htmlspecialchars($evaluation_grade) . "</p>";
echo "<p>Total Score: " . htmlspecialchars($total_score) . "</p>";

// Validate required fields
$errors = [];
if (empty($project_id)) $errors[] = "Project ID missing";
if (empty($decision_id)) $errors[] = "Decision ID missing";
if (empty($acceptance_date)) $errors[] = "Acceptance date missing";
if (empty($evaluation_grade)) $errors[] = "Evaluation grade missing";

if (!empty($errors)) {
    echo "<h3>Validation Errors:</h3>";
    foreach ($errors as $error) {
        echo "<p style='color:red;'>- $error</p>";
    }
    echo "<p><a href='view_project.php?id=" . urlencode($project_id) . "&tab=report'>Back to project</a></p>";
    exit();
}

echo "<h3>Starting database operation...</h3>";

try {
    $conn->autocommit(FALSE);
    
    // Kiểm tra biên bản hiện có
    echo "<p>Checking existing report...</p>";
    $check_sql = "SELECT BB_SOBB FROM bien_ban WHERE QD_SO = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Prepare check failed: " . $conn->error);
    }
    
    $check_stmt->bind_param("s", $decision_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing
        $existing = $check_result->fetch_assoc();
        $report_id = $existing['BB_SOBB'];
        echo "<p>Found existing report: $report_id</p>";
        
        $sql_update = "UPDATE bien_ban SET BB_NGAYNGHIEMTHU = ?, BB_XEPLOAI = ?, BB_TONGDIEM = ? WHERE BB_SOBB = ? AND QD_SO = ?";
        $stmt_update = $conn->prepare($sql_update);
        if (!$stmt_update) {
            throw new Exception("Prepare update failed: " . $conn->error);
        }
        
        $total_score_val = empty($total_score) ? null : floatval($total_score);
        $stmt_update->bind_param("ssdss", $acceptance_date, $evaluation_grade, $total_score_val, $report_id, $decision_id);
        
        if ($stmt_update->execute()) {
            echo "<p style='color:green;'>✓ Update successful</p>";
        } else {
            throw new Exception("Update execution failed: " . $stmt_update->error);
        }
        $stmt_update->close();
        
    } else {
        // Create new
        echo "<p>No existing report found, creating new...</p>";
        
        // Generate new ID
        $sql_max_id = "SELECT MAX(CAST(SUBSTRING(BB_SOBB, 3) AS UNSIGNED)) as max_id FROM bien_ban";
        $result_max = $conn->query($sql_max_id);
        if (!$result_max) {
            throw new Exception("Max ID query failed: " . $conn->error);
        }
        
        $max_result = $result_max->fetch_assoc();
        $next_id = ($max_result['max_id'] ?? 0) + 1;
        $new_report_id = 'BB' . str_pad($next_id, 8, '0', STR_PAD_LEFT);
        
        echo "<p>Generated new ID: $new_report_id</p>";
        
        $sql_insert = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            throw new Exception("Prepare insert failed: " . $conn->error);
        }
        
        $total_score_val = empty($total_score) ? null : floatval($total_score);
        $stmt_insert->bind_param("ssssd", $new_report_id, $decision_id, $acceptance_date, $evaluation_grade, $total_score_val);
        
        if ($stmt_insert->execute()) {
            echo "<p style='color:green;'>✓ Insert successful</p>";
        } else {
            throw new Exception("Insert execution failed: " . $stmt_insert->error);
        }
        $stmt_insert->close();
        $report_id = $new_report_id;
    }
    $check_stmt->close();
    
    $conn->commit();
    echo "<p style='color:green;'>✓ Transaction committed successfully</p>";
    
    $_SESSION['success'] = "Biên bản nghiệm thu đã được cập nhật thành công!";
    
    // Redirect về trang view_project với tab report
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
    
    $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    
    // Redirect về trang view_project với tab report
    header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");
    exit();
} finally {
    $conn->autocommit(TRUE);
}
?>
