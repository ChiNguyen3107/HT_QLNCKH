<?php
require_once 'include/connect.php';

echo "=== DATABASE STRUCTURE CHECK ===\n";
echo "Database: " . mysqli_get_server_info($conn) . "\n";
echo "Current DB: " . $conn->query('SELECT DATABASE()')->fetch_row()[0] . "\n";
echo "\nTables in database:\n";

$result = $conn->query('SHOW TABLES');
while ($row = $result->fetch_row()) {
    echo "- " . $row[0] . "\n";
}

echo "\nLooking for related tables:\n";
$tables = [];
$result = $conn->query('SHOW TABLES');
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// Check for project-related tables
$project_related = ['de_tai', 'detai', 'project', 'du_an', 'de_an'];
foreach ($project_related as $search) {
    foreach ($tables as $table) {
        if (stripos($table, $search) !== false) {
            echo "Found potential project table: $table\n";
        }
    }
}

// Check for student-related tables
$student_related = ['sinh_vien', 'sinhvien', 'student', 'hoc_sinh'];
foreach ($student_related as $search) {
    foreach ($tables as $table) {
        if (stripos($table, $search) !== false) {
            echo "Found potential student table: $table\n";
        }
    }
}

// Check for teacher-related tables
$teacher_related = ['giang_vien', 'giangvien', 'teacher', 'giao_vien'];
foreach ($teacher_related as $search) {
    foreach ($tables as $table) {
        if (stripos($table, $search) !== false) {
            echo "Found potential teacher table: $table\n";
        }
    }
}
?>
