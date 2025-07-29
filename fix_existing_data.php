<?php
include 'include/connect.php';

echo "=== SỬA DỮ LIỆU HIỆN TẠI ===\n";

try {
    $conn->begin_transaction();
    
    // 1. Tìm các quyết định không có biên bản
    echo "\n1. Tìm quyết định không có biên bản:\n";
    $result = $conn->query("SELECT qd.QD_SO, qd.QD_NGAY, qd.BB_SOBB 
                           FROM quyet_dinh_nghiem_thu qd 
                           LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO 
                           WHERE bb.BB_SOBB IS NULL");
    
    $missing_reports = [];
    while ($row = $result->fetch_assoc()) {
        $missing_reports[] = $row;
        echo "- {$row['QD_SO']} (ngày: {$row['QD_NGAY']}, BB_SOBB: {$row['BB_SOBB']})\n";
    }
    
    // 2. Tạo biên bản cho những quyết định thiếu
    if (count($missing_reports) > 0) {
        echo "\n2. Tạo biên bản cho các quyết định thiếu:\n";
        
        $insert_report_sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) 
                             VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_report_sql);
        
        foreach ($missing_reports as $decision) {
            $qd_so = $decision['QD_SO'];
            $qd_ngay = $decision['QD_NGAY'];
            $bb_sobb = $decision['BB_SOBB']; // Sử dụng BB_SOBB từ quyet_dinh_nghiem_thu
            
            // Nếu không có BB_SOBB, tạo mới
            if (!$bb_sobb) {
                $bb_sobb = "BB" . substr($qd_so, 2); // BB021 từ QD021
            }
            
            $default_grade = "Chưa nghiệm thu";
            
            $stmt->bind_param("ssss", $bb_sobb, $qd_so, $qd_ngay, $default_grade);
            
            if ($stmt->execute()) {
                echo "✓ Tạo biên bản {$bb_sobb} cho quyết định {$qd_so}\n";
                
                // Cập nhật BB_SOBB trong quyet_dinh_nghiem_thu nếu cần
                if (!$decision['BB_SOBB']) {
                    $update_qd_sql = "UPDATE quyet_dinh_nghiem_thu SET BB_SOBB = ? WHERE QD_SO = ?";
                    $update_stmt = $conn->prepare($update_qd_sql);
                    $update_stmt->bind_param("ss", $bb_sobb, $qd_so);
                    $update_stmt->execute();
                    echo "  ✓ Cập nhật BB_SOBB trong quyết định {$qd_so}\n";
                }
            } else {
                throw new Exception("Không thể tạo biên bản cho {$qd_so}: " . $stmt->error);
            }
        }
    }
    
    // 3. Kiểm tra kết quả
    echo "\n3. Kiểm tra kết quả sau khi sửa:\n";
    $result = $conn->query("SELECT 
        qd.QD_SO, 
        qd.BB_SOBB as qd_bb_ref,
        bb.BB_SOBB as bb_real,
        bb.BB_XEPLOAI 
    FROM quyet_dinh_nghiem_thu qd 
    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO 
    ORDER BY qd.QD_SO");
    
    $total_decisions = 0;
    $total_with_reports = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_decisions++;
        $has_report = $row['bb_real'] ? '✓' : '✗';
        if ($row['bb_real']) $total_with_reports++;
        
        echo "- QD: {$row['QD_SO']} -> BB: {$row['bb_real']} {$has_report}\n";
    }
    
    echo "\nTóm tắt:\n";
    echo "- Tổng số quyết định: {$total_decisions}\n";
    echo "- Có biên bản: {$total_with_reports}\n";
    echo "- Thiếu biên bản: " . ($total_decisions - $total_with_reports) . "\n";
    
    $conn->commit();
    echo "\n✓ SỬA DỮ LIỆU THÀNH CÔNG!\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "\n✗ LỖI: " . $e->getMessage() . "\n";
}

$conn->close();
echo "\n=== KẾT THÚC ===\n";
?>
