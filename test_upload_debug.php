<?php
// Test upload với session giả lập
session_start();

// Giả lập session để test
$_SESSION['user_id'] = 'SV001';
$_SESSION['role'] = 'student';

echo "<h2>Debug Upload Test</h2>";
echo "<p>Session User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Session Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";

// Test database connection
require_once 'include/connect.php';

echo "<h3>Database Connection Test</h3>";
if ($conn) {
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
    
    // Check tables exist
    $tables = ['file_dinh_kem', 'bien_ban', 'giang_vien', 'de_tai', 'sinh_vien'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' NOT found</p>";
        }
    }
    
    // Check bien_ban data
    echo "<h4>Bien Ban Data:</h4>";
    $bb_result = $conn->query("SELECT BB_SOBB, BB_NGAYNGHIEMTHU FROM bien_ban LIMIT 3");
    if ($bb_result && $bb_result->num_rows > 0) {
        while ($row = $bb_result->fetch_assoc()) {
            echo "<p>BB_SOBB: " . $row['BB_SOBB'] . ", Date: " . $row['BB_NGAYNGHIEMTHU'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>No bien_ban records found</p>";
    }
    
    // Check giang_vien data
    echo "<h4>Giang Vien Data:</h4>";
    $gv_result = $conn->query("SELECT GV_MAGV, GV_TENGV FROM giang_vien LIMIT 3");
    if ($gv_result && $gv_result->num_rows > 0) {
        while ($row = $gv_result->fetch_assoc()) {
            echo "<p>GV_MAGV: " . $row['GV_MAGV'] . ", Name: " . $row['GV_TENGV'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>No giang_vien records found</p>";
    }
    
    // Check de_tai and sinh_vien for project access
    echo "<h4>Project Access Test:</h4>";
    $project_result = $conn->query("
        SELECT dt.DT_MADT, dt.DT_TENDT, sv.SV_MASV 
        FROM de_tai dt 
        JOIN sinh_vien sv ON dt.DT_MADT = sv.SV_MADT 
        WHERE sv.SV_MASV = 'SV001'
        LIMIT 3
    ");
    if ($project_result && $project_result->num_rows > 0) {
        while ($row = $project_result->fetch_assoc()) {
            echo "<p>Project: " . $row['DT_MADT'] . " - " . $row['DT_TENDT'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>No projects found for SV001</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

// Check upload directory
echo "<h3>Upload Directory Test</h3>";
$upload_dir = 'uploads/member_evaluations/';
if (is_dir($upload_dir)) {
    echo "<p style='color: green;'>✓ Upload directory exists: $upload_dir</p>";
    if (is_writable($upload_dir)) {
        echo "<p style='color: green;'>✓ Upload directory is writable</p>";
    } else {
        echo "<p style='color: red;'>✗ Upload directory is NOT writable</p>";
    }
} else {
    echo "<p style='color: orange;'>Upload directory does not exist, will be created</p>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p style='color: green;'>✓ Upload directory created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create upload directory</p>";
    }
}
?>

<h3>Test Upload Form</h3>
<form action="view/student/upload_member_evaluation.php" method="POST" enctype="multipart/form-data">
    <div>
        <label>Project ID:</label>
        <input type="text" name="project_id" value="DT000001" required>
    </div><br>
    
    <div>
        <label>Member ID:</label>
        <input type="text" name="member_id" value="GV001" required>
    </div><br>
    
    <div>
        <label>File Name:</label>
        <input type="text" name="evaluation_file_name" value="Test File" required>
    </div><br>
    
    <div>
        <label>Description:</label>
        <textarea name="file_description">Test upload</textarea>
    </div><br>
    
    <div>
        <label>File:</label>
        <input type="file" name="evaluation_file" required>
    </div><br>
    
    <button type="submit">Upload File</button>
</form>
