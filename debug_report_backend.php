<?php
// Debug file for testing report update functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Report Update</h1>";

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check required fields
    $required_fields = ['project_id', 'decision_id', 'acceptance_date', 'evaluation_grade', 'update_reason'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (empty($missing_fields)) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: #155724;'>✅ All required fields present</h3>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3 style='color: #721c24;'>❌ Missing required fields:</h3>";
        echo "<ul>";
        foreach ($missing_fields as $field) {
            echo "<li>$field</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Test database connection
    echo "<h2>Database Connection Test:</h2>";
    try {
        include '../../include/connect.php';
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<p style='color: #155724;'>✅ Database connection successful</p>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "<p style='color: #721c24;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
} else {
    echo "<p>This page receives POST data for debugging. Use the debug form to test.</p>";
    echo "<a href='debug_report_form.html'>Go to Debug Form</a>";
}

echo "<h2>Server Information:</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server Time: " . date('Y-m-d H:i:s') . "</li>";
echo "<li>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</li>";
echo "<li>HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'Not set') . "</li>";
echo "</ul>";

// Show session info if available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Session Information:</h2>";
if (!empty($_SESSION)) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p>No session data available</p>";
}
?>
