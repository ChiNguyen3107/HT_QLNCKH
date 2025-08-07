<?php
session_start();

// Simulate student session for testing
if (!isset($_SESSION['user_id']) && isset($_SERVER['HTTP_X_STUDENT_ID'])) {
    $_SESSION['user_id'] = $_SERVER['HTTP_X_STUDENT_ID'];
    $_SESSION['role'] = 'student';
}

require_once '../../include/connect.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['user_id'])) {
    echo "ERROR: No session found\n";
    echo "Headers: " . print_r(getallheaders(), true);
    exit();
}

$student_id = $_SESSION['user_id'];
$project_id = $_POST['project_id'] ?? 'not_provided';

echo "=== PERMISSION DEBUG ===\n";
echo "Student ID: $student_id\n";
echo "Project ID: $project_id\n";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Test permission check
    $check_permission = $conn->prepare("
        SELECT cttg.DT_MADT, cttg.SV_MASV, cttg.CTTG_VAITRO
        FROM chi_tiet_tham_gia cttg 
        WHERE cttg.DT_MADT = ? AND cttg.SV_MASV = ?
    ");
    $check_permission->bind_param("ss", $project_id, $student_id);
    $check_permission->execute();
    $permission_result = $check_permission->get_result();
    
    echo "Permission query result: " . $permission_result->num_rows . " rows\n";
    
    if ($permission_result->num_rows > 0) {
        while ($row = $permission_result->fetch_assoc()) {
            echo "Found permission: DT_MADT=" . $row['DT_MADT'] . ", SV_MASV=" . $row['SV_MASV'] . ", Role=" . $row['CTTG_VAITRO'] . "\n";
        }
        echo "RESULT: Permission GRANTED\n";
    } else {
        echo "RESULT: Permission DENIED\n";
        
        // Debug: Show what exists
        echo "\nDEBUG: Available student-project relationships:\n";
        $debug_query = $conn->query("SELECT SV_MASV, DT_MADT, CTTG_VAITRO FROM chi_tiet_tham_gia WHERE SV_MASV = '$student_id' LIMIT 5");
        while ($row = $debug_query->fetch_assoc()) {
            echo "- Student $student_id in project " . $row['DT_MADT'] . " as " . $row['CTTG_VAITRO'] . "\n";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
