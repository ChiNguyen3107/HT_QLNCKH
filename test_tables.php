<?php
require_once 'include/connect.php';

echo "Checking database tables...\n";

$conn = new mysqli($servername, $username, $password, $dbname);

// Show all tables
$query = "SHOW TABLES";
$result = $conn->query($query);
if ($result) {
    echo "Available tables:\n";
    while($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
}

// Check if there's a table with 'de_tai' or similar
echo "\nSearching for project-related tables...\n";
$query = "SHOW TABLES LIKE '%de_tai%'";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_array()) {
        echo "Found: " . $row[0] . "\n";
    }
}

echo "\nSearching for project-related tables (alternative patterns)...\n";
$query = "SHOW TABLES LIKE '%tai%'";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_array()) {
        echo "Found: " . $row[0] . "\n";
    }
}

$conn->close();
?>
