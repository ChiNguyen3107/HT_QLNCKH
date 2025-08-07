<?php
include 'include/connect.php';

echo "=== DỌN DẸP DỮ LIỆU TRÙNG LẶP ===\n\n";

try {
    $conn->begin_transaction();
    
    echo "1. Phân tích dữ liệu trùng lặp:\n";
    
    // Kiểm tra QD_SO: 123ab
    echo "\nQuyết định 123ab:\n";
    $result = $conn->query("
        SELECT BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM
        FROM bien_ban 
        WHERE QD_SO = '123ab'
        ORDER BY BB_SOBB
    ");
    while ($row = $result->fetch_assoc()) {
        echo "- BB: {$row['BB_SOBB']} | Date: {$row['BB_NGAYNGHIEMTHU']} | Grade: {$row['BB_XEPLOAI']} | Score: {$row['BB_TONGDIEM']}\n";
    }
    
    // Kiểm tra QD_SO: QDDT0
    echo "\nQuyết định QDDT0:\n";
    $result = $conn->query("
        SELECT BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI, BB_TONGDIEM
        FROM bien_ban 
        WHERE QD_SO = 'QDDT0'
        ORDER BY BB_SOBB
    ");
    while ($row = $result->fetch_assoc()) {
        echo "- BB: {$row['BB_SOBB']} | Date: {$row['BB_NGAYNGHIEMTHU']} | Grade: {$row['BB_XEPLOAI']} | Score: {$row['BB_TONGDIEM']}\n";
    }
    
    echo "\n2. Kiểm tra liên kết trong bảng quyết định:\n";
    $result = $conn->query("
        SELECT QD_SO, BB_SOBB, QD_NGAY
        FROM quyet_dinh_nghiem_thu 
        WHERE QD_SO IN ('123ab', 'QDDT0')
    ");
    while ($row = $result->fetch_assoc()) {
        echo "- QD: {$row['QD_SO']} -> BB: {$row['BB_SOBB']} | Date: {$row['QD_NGAY']}\n";
    }
    
    echo "\n3. Đề xuất giải pháp:\n";
    echo "Sẽ xóa các biên bản trùng lặp (giữ lại biên bản có dữ liệu đầy đủ nhất)\n";
    
    // Xóa biên bản trùng lặp cho QD 123ab - giữ lại BB00000005
    echo "\nXóa BB3abc (trùng với 123ab)...\n";
    $stmt = $conn->prepare("DELETE FROM bien_ban WHERE BB_SOBB = 'BB3abc' AND QD_SO = '123ab'");
    if ($stmt->execute()) {
        echo "✓ Đã xóa BB3abc\n";
    } else {
        echo "✗ Lỗi xóa BB3abc: " . $stmt->error . "\n";
    }
    
    // Xóa biên bản trùng lặp cho QD QDDT0 - giữ lại BB00000004  
    echo "\nXóa BBDT000000 (trùng với QDDT0)...\n";
    $stmt = $conn->prepare("DELETE FROM bien_ban WHERE BB_SOBB = 'BBDT000000' AND QD_SO = 'QDDT0'");
    if ($stmt->execute()) {
        echo "✓ Đã xóa BBDT000000\n";
    } else {
        echo "✗ Lỗi xóa BBDT000000: " . $stmt->error . "\n";
    }
    
    // Cập nhật quyết định để chỉ đến biên bản còn lại
    echo "\n4. Cập nhật liên kết quyết định:\n";
    
    $stmt = $conn->prepare("UPDATE quyet_dinh_nghiem_thu SET BB_SOBB = 'BB00000005' WHERE QD_SO = '123ab'");
    if ($stmt->execute()) {
        echo "✓ Cập nhật QD 123ab -> BB00000005\n";
    } else {
        echo "✗ Lỗi cập nhật QD 123ab: " . $stmt->error . "\n";
    }
    
    $stmt = $conn->prepare("UPDATE quyet_dinh_nghiem_thu SET BB_SOBB = 'BB00000004' WHERE QD_SO = 'QDDT0'");
    if ($stmt->execute()) {
        echo "✓ Cập nhật QD QDDT0 -> BB00000004\n";
    } else {
        echo "✗ Lỗi cập nhật QD QDDT0: " . $stmt->error . "\n";
    }
    
    echo "\n5. Kiểm tra kết quả:\n";
    $result = $conn->query("
        SELECT QD_SO, COUNT(*) as count 
        FROM bien_ban 
        WHERE QD_SO IS NOT NULL 
        GROUP BY QD_SO 
        HAVING count > 1
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "Vẫn còn quyết định có nhiều biên bản:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- QD_SO: {$row['QD_SO']} có {$row['count']} biên bản\n";
        }
    } else {
        echo "✓ Đã dọn dẹp xong - mỗi quyết định chỉ có 1 biên bản\n";
    }
    
    $conn->commit();
    echo "\n✓ Hoàn tất dọn dẹp dữ liệu\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "\n✗ Lỗi: " . $e->getMessage() . "\n";
}

echo "\n=== KẾT THÚC ===\n";
?>
