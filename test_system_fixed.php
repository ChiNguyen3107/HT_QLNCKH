<?php
include 'include/connect.php';

echo "=== TEST HỆ THỐNG SAU KHI SỬA LỖI ===\n";

// 1. Test query mới trong view_project.php
echo "\n1. Test query mới trong view_project.php:\n";
$test_project_id = 'DT0000011'; // Thay bằng project ID thực tế

$decision_sql = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM 
                FROM quyet_dinh_nghiem_thu qd
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE qd.QD_SO IN (SELECT QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?)";

$stmt = $conn->prepare($decision_sql);
if ($stmt) {
    $stmt->bind_param("s", $test_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "Kết quả query: " . $result->num_rows . " rows\n";
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo "Dữ liệu lấy được:\n";
        echo "- QD_SO: " . ($data['QD_SO'] ?? 'NULL') . "\n";
        echo "- QD_NGAY: " . ($data['QD_NGAY'] ?? 'NULL') . "\n";
        echo "- QD_FILE: " . ($data['QD_FILE'] ?? 'NULL') . "\n";
        echo "- BB_SOBB: " . ($data['BB_SOBB'] ?? 'NULL') . "\n";
        echo "- BB_XEPLOAI: " . ($data['BB_XEPLOAI'] ?? 'NULL') . "\n";
        echo "- BB_TONGDIEM: " . ($data['BB_TONGDIEM'] ?? 'NULL') . "\n";
    }
} else {
    echo "Lỗi query: " . $conn->error . "\n";
}

// 2. Test tạo quyết định và biên bản mới
echo "\n2. Test tạo quyết định và biên bản mới:\n";

// Lấy project không có quyết định
$result = $conn->query("SELECT DT_MADT FROM de_tai_nghien_cuu WHERE QD_SO IS NULL OR QD_SO = '' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $test_project = $row['DT_MADT'];
    echo "Test với project: $test_project\n";
    
    // Test data
    $test_decision_number = "QD" . sprintf('%03d', rand(100, 999));
    $test_decision_date = date('Y-m-d');
    $test_file = "test_decision_" . time() . ".pdf";
    
    try {
        $conn->begin_transaction();
        
        // Tạo quyết định trước
        $insert_decision_sql = "INSERT INTO quyet_dinh_nghiem_thu (QD_SO, QD_NGAY, QD_FILE, BB_SOBB) 
                               VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_decision_sql);
        $report_code = "BB" . substr($test_decision_number, 2); // BB từ QD
        $stmt->bind_param("ssss", $test_decision_number, $test_decision_date, $test_file, $report_code);
        
        if ($stmt->execute()) {
            echo "✓ Tạo quyết định thành công: $test_decision_number\n";
            
            // Tạo biên bản sau
            $insert_report_sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) 
                                 VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_report_sql);
            $default_grade = "Chưa nghiệm thu";
            $stmt->bind_param("ssss", $report_code, $test_decision_number, $test_decision_date, $default_grade);
            
            if ($stmt->execute()) {
                echo "✓ Tạo biên bản thành công: $report_code\n";
                
                // Cập nhật project
                $update_project_sql = "UPDATE de_tai_nghien_cuu SET QD_SO = ? WHERE DT_MADT = ?";
                $stmt = $conn->prepare($update_project_sql);
                $stmt->bind_param("ss", $test_decision_number, $test_project);
                
                if ($stmt->execute()) {
                    echo "✓ Cập nhật project thành công\n";
                    
                    // Test query lại
                    echo "\n3. Test query sau khi tạo:\n";
                    $stmt = $conn->prepare($decision_sql);
                    $stmt->bind_param("s", $test_project);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $data = $result->fetch_assoc();
                        echo "✓ Query thành công, dữ liệu:\n";
                        echo "- QD_SO: " . $data['QD_SO'] . "\n";
                        echo "- BB_SOBB: " . $data['BB_SOBB'] . "\n";
                        echo "- BB_XEPLOAI: " . $data['BB_XEPLOAI'] . "\n";
                    } else {
                        echo "✗ Query không trả về dữ liệu\n";
                    }
                    
                } else {
                    throw new Exception("Không thể cập nhật project: " . $stmt->error);
                }
            } else {
                throw new Exception("Không thể tạo biên bản: " . $stmt->error);
            }
        } else {
            throw new Exception("Không thể tạo quyết định: " . $stmt->error);
        }
        
        $conn->commit();
        echo "\n✓ TRANSACTION THÀNH CÔNG - Hệ thống hoạt động bình thường!\n";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "\n✗ TRANSACTION THẤT BẠI: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "Không tìm thấy project để test\n";
}

// 4. Kiểm tra các ràng buộc foreign key
echo "\n4. Kiểm tra ràng buộc foreign key:\n";
$result = $conn->query("SELECT 
    CONSTRAINT_NAME, 
    TABLE_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE CONSTRAINT_SCHEMA = 'ql_nckh' 
AND REFERENCED_TABLE_NAME IS NOT NULL 
AND (TABLE_NAME = 'bien_ban' OR TABLE_NAME = 'quyet_dinh_nghiem_thu' OR TABLE_NAME = 'de_tai_nghien_cuu')
ORDER BY TABLE_NAME");

while ($row = $result->fetch_assoc()) {
    echo "- {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
}

$conn->close();
echo "\n=== KẾT THÚC TEST ===\n";
?>
