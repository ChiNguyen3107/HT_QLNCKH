<?php
include 'include/connect.php';

echo "=== KIỂM TRA CHI TIẾT VẤN ĐỀ TẠO BIÊN BẢN ===\n\n";

// Test thực sự tạo biên bản để xem lỗi gì
echo "1. Thử tạo quyết định test:\n";
$test_decision_number = "QDTEST" . time();
$test_date = date('Y-m-d');

try {
    $conn->begin_transaction();
    
    // Tạo quyết định test
    $insert_decision_sql = "INSERT INTO quyet_dinh_nghiem_thu (QD_SO, QD_NGAY, QD_FILE) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_decision_sql);
    $test_file = "test.pdf";
    $stmt->bind_param("sss", $test_decision_number, $test_date, $test_file);
    
    if ($stmt->execute()) {
        echo "✓ Tạo quyết định thành công: $test_decision_number\n";
        
        // Tạo biên bản
        $report_code = "BB" . substr($test_decision_number, 2);
        echo "Tạo biên bản với mã: $report_code\n";
        
        $insert_report_sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_report_sql);
        
        if (!$stmt) {
            throw new Exception("Lỗi prepare biên bản: " . $conn->error);
        }
        
        $default_grade = "Chưa nghiệm thu";
        $stmt->bind_param("ssss", $report_code, $test_decision_number, $test_date, $default_grade);
        
        if ($stmt->execute()) {
            echo "✓ Tạo biên bản thành công: $report_code\n";
            
            // Cập nhật quyết định với biên bản
            $update_decision_sql = "UPDATE quyet_dinh_nghiem_thu SET BB_SOBB = ? WHERE QD_SO = ?";
            $stmt = $conn->prepare($update_decision_sql);
            $stmt->bind_param("ss", $report_code, $test_decision_number);
            
            if ($stmt->execute()) {
                echo "✓ Cập nhật quyết định với biên bản thành công\n";
                echo "Test hoàn tất - sẽ rollback để không làm ảnh hưởng dữ liệu\n";
            } else {
                throw new Exception("Lỗi cập nhật quyết định: " . $stmt->error);
            }
        } else {
            throw new Exception("Lỗi tạo biên bản: " . $stmt->error);
        }
    } else {
        throw new Exception("Lỗi tạo quyết định: " . $stmt->error);
    }
    
    $conn->rollback();
    echo "\n✓ Test thành công - không có lỗi trong cấu trúc database\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "\n✗ Lỗi: " . $e->getMessage() . "\n";
}

echo "\n2. Kiểm tra dữ liệu có vấn đề:\n";
// Kiểm tra các quyết định có biên bản trùng lặp
$result = $conn->query("
    SELECT QD_SO, COUNT(*) as count 
    FROM bien_ban 
    WHERE QD_SO IS NOT NULL 
    GROUP BY QD_SO 
    HAVING count > 1
");

if ($result && $result->num_rows > 0) {
    echo "Các quyết định có nhiều biên bản:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- QD_SO: {$row['QD_SO']} có {$row['count']} biên bản\n";
    }
} else {
    echo "Không có quyết định nào có nhiều biên bản\n";
}

echo "\n3. Kiểm tra các biên bản không có quyết định:\n";
$result = $conn->query("SELECT BB_SOBB FROM bien_ban WHERE QD_SO IS NULL OR QD_SO = ''");
if ($result && $result->num_rows > 0) {
    echo "Các biên bản không có quyết định:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- BB_SOBB: {$row['BB_SOBB']}\n";
    }
} else {
    echo "Tất cả biên bản đều có quyết định\n";
}

echo "\n4. Kiểm tra ràng buộc NOT NULL:\n";
$result = $conn->query("SELECT * FROM bien_ban WHERE BB_NGAYNGHIEMTHU IS NULL OR BB_XEPLOAI IS NULL OR BB_XEPLOAI = ''");
if ($result && $result->num_rows > 0) {
    echo "Các biên bản có dữ liệu NULL:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- BB_SOBB: {$row['BB_SOBB']} | Date: {$row['BB_NGAYNGHIEMTHU']} | Grade: '{$row['BB_XEPLOAI']}'\n";
    }
} else {
    echo "Tất cả biên bản đều có dữ liệu đầy đủ\n";
}

echo "\n=== KẾT THÚC ===\n";
?>
