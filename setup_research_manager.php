<?php
// filepath: d:\xampp\htdocs\NLNganh\setup_research_manager.php
include 'include/connect.php';

// Read SQL file content
$sqlFile = file_get_contents('add_research_manager.sql');

// Split SQL commands by semicolon
$sqlCommands = explode(';', $sqlFile);

$error = false;

// Execute each command
foreach ($sqlCommands as $sql) {
    $sql = trim($sql);
    if (!empty($sql)) {
        if (!$conn->query($sql)) {
            echo "Error executing: " . $sql . "<br>";
            echo "Error message: " . $conn->error . "<br><hr>";
            $error = true;
        } else {
            echo "Successfully executed: " . substr($sql, 0, 50) . "...<br>";
        }
    }
}

if (!$error) {
    echo "<h2>Research manager role and tables set up successfully!</h2>";
}
?>
