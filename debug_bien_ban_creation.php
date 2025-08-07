<?php
// Debug script để test tạo biên bản nghiệm thu
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== TESTING BIEN_BAN CREATION ===\n\n";

// Lấy một project ID và decision ID thực tế
$result = $conn->query("SELECT dt.DT_MADT, qd.QD_SO FROM de_tai_nghien_cuu dt JOIN quyet_dinh_nghiem_thu qd ON dt.QD_SO = qd.QD_SO LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $project_id = $row['DT_MADT'];
    $decision_id = $row['QD_SO'];
    
    echo "1. Using test data:\n";
    echo "   Project ID: $project_id\n";
    echo "   Decision ID: $decision_id\n";
    
    // Test parameters như trong form
    $acceptance_date = '2025-08-06';
    $evaluation_grade = 'Tốt';
    $total_score = 85.5;
    
    echo "\n2. Test parameters:\n";
    echo "   Acceptance Date: $acceptance_date\n";
    echo "   Evaluation Grade: $evaluation_grade\n";
    echo "   Total Score: $total_score\n";
    
    echo "\n3. Checking if biên bản already exists:\n";
    $check_sql = "SELECT BB_SOBB FROM bien_ban WHERE QD_SO = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $decision_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        echo "   ✓ Biên bản already exists: " . $existing['BB_SOBB'] . "\n";
        $report_id = $existing['BB_SOBB'];
        $is_update = true;
    } else {
        echo "   ○ No existing biên bản found\n";
        $report_id = '';
        $is_update = false;
    }
    $check_stmt->close();
    
    echo "\n4. Testing the actual operation:\n";
    $conn->autocommit(FALSE);
    
    try {
        if ($is_update) {
            // Update existing
            echo "   Testing UPDATE operation...\n";
            $sql_update = "UPDATE bien_ban SET BB_NGAYNGHIEMTHU = ?, BB_XEPLOAI = ?, BB_TONGDIEM = ? WHERE BB_SOBB = ? AND QD_SO = ?";
            $stmt_update = $conn->prepare($sql_update);
            if (!$stmt_update) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt_update->bind_param("ssdss", $acceptance_date, $evaluation_grade, $total_score, $report_id, $decision_id);
            $result = $stmt_update->execute();
            if (!$result) {
                throw new Exception("Update failed: " . $stmt_update->error);
            }
            echo "   ✓ Update successful\n";
            $stmt_update->close();
        } else {
            // Create new
            echo "   Testing INSERT operation...\n";
            
            // Generate new ID
            $sql_max_id = "SELECT MAX(CAST(SUBSTRING(BB_SOBB, 3) AS UNSIGNED)) as max_id FROM bien_ban";
            $result_max = $conn->query($sql_max_id);
            $max_result = $result_max->fetch_assoc();
            $next_id = ($max_result['max_id'] ?? 0) + 1;
            $new_report_id = 'BB' . str_pad($next_id, 8, '0', STR_PAD_LEFT);
            
            echo "   Generated ID: $new_report_id\n";
            
            $sql_insert = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if (!$stmt_insert) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt_insert->bind_param("ssssd", $new_report_id, $decision_id, $acceptance_date, $evaluation_grade, $total_score);
            $result = $stmt_insert->execute();
            if (!$result) {
                throw new Exception("Insert failed: " . $stmt_insert->error);
            }
            echo "   ✓ Insert successful\n";
            $stmt_insert->close();
        }
        
        echo "   ✓ Operation completed successfully\n";
        
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    $conn->rollback();
    echo "   ○ Changes rolled back (test mode)\n";
    
} else {
    echo "No test data available\n";
}

$conn->autocommit(TRUE);
$conn->close();

echo "\n=== TEST COMPLETE ===\n";
?>
