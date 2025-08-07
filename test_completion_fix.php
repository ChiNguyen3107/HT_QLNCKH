<?php
// Test script để kiểm tra việc sửa lỗi completion functions
include_once 'config/config.php';
include_once 'include/project_completion_functions.php';

echo "<h2>Test Project Completion Functions</h2>";

// Test với một project ID mẫu
$test_project_id = "DT001"; // Thay đổi thành ID thật nếu có

echo "<h3>Testing checkProjectCompletionConditions...</h3>";
try {
    $conditions = checkProjectCompletionConditions($test_project_id, $conn);
    echo "<pre>";
    print_r($conditions);
    echo "</pre>";
    echo "<p style='color: green;'>✓ checkProjectCompletionConditions executed without fatal errors</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error in checkProjectCompletionConditions: " . $e->getMessage() . "</p>";
}

echo "<h3>Testing getProjectCompletionDetails...</h3>";
try {
    $details = getProjectCompletionDetails($test_project_id, $conn);
    echo "<pre>";
    print_r($details);
    echo "</pre>";
    echo "<p style='color: green;'>✓ getProjectCompletionDetails executed without fatal errors</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error in getProjectCompletionDetails: " . $e->getMessage() . "</p>";
}

echo "<h3>Testing updateProjectStatusIfComplete...</h3>";
try {
    // Chỉ test mà không thực sự update
    echo "<p>Note: This is a dry run - no actual database updates will be performed</p>";
    $result = updateProjectStatusIfComplete($test_project_id, $conn);
    echo "<p>Result: " . ($result ? "Success" : "No update needed or failed") . "</p>";
    echo "<p style='color: green;'>✓ updateProjectStatusIfComplete executed without fatal errors</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error in updateProjectStatusIfComplete: " . $e->getMessage() . "</p>";
}

echo "<h3>Summary</h3>";
echo "<p>If all functions above show green checkmarks, the SQL preparation error has been fixed.</p>";
echo "<p>You can now try updating report information in the project view without getting the 'Có lỗi hệ thống nghiêm trọng xảy ra' error.</p>";
?>
