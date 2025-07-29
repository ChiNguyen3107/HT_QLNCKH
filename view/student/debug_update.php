<?php
// Debug file để kiểm tra session
ob_start();

echo "Before session check<br>";

include '../../include/session.php';

echo "After include session.php<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

try {
    checkStudentRole();
    echo "checkStudentRole passed<br>";
} catch (Exception $e) {
    echo "checkStudentRole failed: " . $e->getMessage() . "<br>";
}

include '../../include/connect.php';

echo "Database connection: " . ($conn ? "OK" : "FAILED") . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST data: <pre>" . print_r($_POST, true) . "</pre>";
    echo "FILES data: <pre>" . print_r($_FILES, true) . "</pre>";
    
    // Simulate the update process
    $_SESSION['success_message'] = "Test success message";
    
    ob_end_clean();
    header("Location: view_project.php?id=test");
    exit();
} else {
    echo "Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
}

ob_end_flush();
?>
