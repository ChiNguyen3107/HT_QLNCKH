<?php
// Script debug đơn giản để kiểm tra lỗi khi tạo biên bản
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'include/connect.php';

echo "=== SIMPLE DEBUG TEST ===\n\n";

// Test với dữ liệu thực tế
$project_id = 'DT0000002'; 
$decision_id = '123ab';
$acceptance_date = '2025-08-06';
$evaluation_grade = 'Tốt';
$total_score = 85.5;

echo "Test parameters:\n";
echo "- Project ID: $project_id\n";
echo "- Decision ID: $decision_id\n";
echo "- Date: $acceptance_date\n";
echo "- Grade: $evaluation_grade\n";
echo "- Score: $total_score\n\n";

echo "Starting test...\n";

try {
    $conn->autocommit(FALSE);
    
    // Kiểm tra biên bản hiện có
    $check_sql = "SELECT BB_SOBB FROM bien_ban WHERE QD_SO = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $decision_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $report_id = '';
    
    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        $report_id = $existing['BB_SOBB'];
        echo "Found existing biên bản: $report_id\n";
        
        // Update
        $sql_update = "UPDATE bien_ban SET BB_NGAYNGHIEMTHU = ?, BB_XEPLOAI = ?, BB_TONGDIEM = ? WHERE BB_SOBB = ? AND QD_SO = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssdss", $acceptance_date, $evaluation_grade, $total_score, $report_id, $decision_id);
        $result = $stmt_update->execute();
        
        if ($result) {
            echo "✓ Update successful\n";
        } else {
            echo "✗ Update failed: " . $stmt_update->error . "\n";
        }
        $stmt_update->close();
        
    } else {
        echo "No existing biên bản found, creating new...\n";
        
        // Tạo ID mới
        $sql_max_id = "SELECT MAX(CAST(SUBSTRING(BB_SOBB, 3) AS UNSIGNED)) as max_id FROM bien_ban";
        $result_max = $conn->query($sql_max_id);
        $max_result = $result_max->fetch_assoc();
        $next_id = ($max_result['max_id'] ?? 0) + 1;
        $new_report_id = 'BB' . str_pad($next_id, 8, '0', STR_PAD_LEFT);
        
        echo "Generated new ID: $new_report_id\n";
        
        // Insert
        $sql_insert = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ssssd", $new_report_id, $decision_id, $acceptance_date, $evaluation_grade, $total_score);
        $result = $stmt_insert->execute();
        
        if ($result) {
            echo "✓ Insert successful\n";
        } else {
            echo "✗ Insert failed: " . $stmt_insert->error . "\n";
        }
        $stmt_insert->close();
    }
    $check_stmt->close();
    
    // Test tiến độ update
    echo "\nTesting progress update...\n";
    $sql_max_progress = "SELECT MAX(CAST(SUBSTRING(TDDT_MA, 5) AS UNSIGNED)) as max_id FROM tien_do_de_tai";
    $result_max_progress = $conn->query($sql_max_progress);
    $max_progress = $result_max_progress->fetch_assoc();
    $next_progress_id = ($max_progress['max_id'] ?? 0) + 1;
    $progress_id = 'TDDT' . str_pad($next_progress_id, 6, '0', STR_PAD_LEFT);
    
    echo "Generated progress ID: $progress_id\n";
    
    $log_content = "Test log content for biên bản update";
    $progress_title = "Test biên bản update";
    
    $sql_progress = "
        INSERT INTO tien_do_de_tai 
        (TDDT_MA, DT_MADT, SV_MASV, TDDT_TIEUDE, TDDT_NOIDUNG, TDDT_PHANTRAMHOANTHANH, TDDT_NGAYCAPNHAT)
        SELECT ?, ?, SV_MASV, ?, ?, 100, NOW()
        FROM chi_tiet_tham_gia 
        WHERE DT_MADT = ? AND CTTG_VAITRO = 'Chủ nhiệm'
        LIMIT 1
    ";
    
    $stmt_progress = $conn->prepare($sql_progress);
    if ($stmt_progress) {
        $stmt_progress->bind_param("sssss", $progress_id, $project_id, $progress_title, $log_content, $project_id);
        if ($stmt_progress->execute()) {
            echo "✓ Progress update successful\n";
        } else {
            echo "✗ Progress update failed: " . $stmt_progress->error . "\n";
        }
        $stmt_progress->close();
    } else {
        echo "✗ Progress prepare failed: " . $conn->error . "\n";
    }
    
    echo "\n✓ All tests completed successfully\n";
    
} catch (Exception $e) {
    echo "✗ Error occurred: " . $e->getMessage() . "\n";
} finally {
    $conn->rollback();
    $conn->autocommit(TRUE);
    echo "○ Changes rolled back (test mode)\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
