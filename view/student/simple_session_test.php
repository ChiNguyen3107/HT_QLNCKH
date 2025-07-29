<?php
// Simple session test
session_start();

echo "<h1>Simple Session Test</h1>";

// Set test session if not exists
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'TEST_USER';
    $_SESSION['role'] = 'student';
    echo "<p>Session created with test data</p>";
}

echo "<h2>Current Session:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Server Info:</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

// Test redirect
if (isset($_GET['test_redirect'])) {
    $_SESSION['test_message'] = "Redirect test successful!";
    header("Location: simple_session_test.php");
    exit();
}

if (isset($_SESSION['test_message'])) {
    echo "<div style='color: green; font-weight: bold;'>" . $_SESSION['test_message'] . "</div>";
    unset($_SESSION['test_message']);
}

echo "<p><a href='?test_redirect=1'>Test Redirect</a></p>";
?>
