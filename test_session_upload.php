<?php
session_start();

// Set session cho test
$_SESSION['user_id'] = 'B2110051'; // ID có trong database
$_SESSION['role'] = 'student';

echo "Session set successfully!\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "Role: " . $_SESSION['role'] . "\n";

// Test database connection
require_once 'include/connect.php';

echo "\nChecking user access to projects:\n";
$stmt = $conn->prepare("
    SELECT dt.DT_MADT, dt.DT_TENDT, cttg.SV_MASV, cttg.CTTG_VAITRO
    FROM de_tai_nghien_cuu dt 
    JOIN chi_tiet_tham_gia cttg ON dt.DT_MADT = cttg.DT_MADT 
    WHERE cttg.SV_MASV = ?
");
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "- Project: " . $row['DT_MADT'] . " - " . $row['DT_TENDT'] . " (Role: " . $row['CTTG_VAITRO'] . ")\n";
}
?>

<h2>Test Upload với Session đúng</h2>
<form action="test_upload_final.php" method="POST" enctype="multipart/form-data">
    <div>
        <label>Project ID:</label>
        <input type="text" name="project_id" value="DT0000001" required>
    </div><br>
    
    <div>
        <label>Member ID:</label>
        <input type="text" name="member_id" value="GV000002" required>
    </div><br>
    
    <div>
        <label>File Name:</label>
        <input type="text" name="evaluation_file_name" value="Test File" required>
    </div><br>
    
    <div>
        <label>Description:</label>
        <textarea name="file_description">Test upload with correct session</textarea>
    </div><br>
    
    <div>
        <label>File:</label>
        <input type="file" name="evaluation_file" required>
    </div><br>
    
    <button type="submit">Upload Test</button>
</form>
