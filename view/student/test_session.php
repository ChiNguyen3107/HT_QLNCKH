<?php
include '../../include/session.php';
checkStudentRole();

echo "<h2>Session Test</h2>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
echo "<p>User Type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

echo "<h3>All Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>POST Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>FILES Data:</h3>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";
?>
