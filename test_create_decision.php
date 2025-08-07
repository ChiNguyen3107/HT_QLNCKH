<?php
// Test tạo quyết định và biên bản giống như trong update_decision_info.php
include 'include/connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST TẠO QUYẾT ĐỊNH VÀ BIÊN BẢN ===\n\n";

// Mock data giống như từ form
$project_id = "DT001"; // Giả sử có project này
$decision_number = "QDTEST" . time();
$decision_date = date('Y-m-d');
$decision_content = "Test quyết định nghiệm thu";
$update_reason = "Test tạo quyết định mới";
$new_filename = "test_decision.pdf";

echo "Dữ liệu test:\n";
echo "- Project ID: $project_id\n";
echo "- Decision Number: $decision_number\n";
echo "- Decision Date: $decision_date\n";
echo "- Update Reason: $update_reason\n";
echo "- Filename: $new_filename\n\n";

try {
    // Bắt đầu transaction giống như trong file gốc
    echo "1. Bắt đầu transaction...\n";
    $conn->begin_transaction();

    // Kiểm tra số quyết định đã tồn tại chưa
    echo "2. Kiểm tra số quyết định trùng lặp...\n";
    $check_decision_sql = "SELECT QD_SO FROM quyet_dinh_nghiem_thu WHERE QD_SO = ?";
    $stmt = $conn->prepare($check_decision_sql);
    $stmt->bind_param("s", $decision_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Số quyết định đã tồn tại. Vui lòng chọn số khác.");
    }
    echo "   ✓ Số quyết định chưa tồn tại\n";

    // Tạo quyết định mới trước
    echo "3. Tạo quyết định mới...\n";
    $insert_decision_sql = "INSERT INTO quyet_dinh_nghiem_thu (QD_SO, QD_NGAY, QD_FILE) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($insert_decision_sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn tạo quyết định: " . $conn->error);
    }
    
    $stmt->bind_param("sss", $decision_number, $decision_date, $new_filename);
    
    if (!$stmt->execute()) {
        throw new Exception("Không thể tạo quyết định nghiệm thu: " . $stmt->error);
    }
    echo "   ✓ Tạo quyết định thành công\n";

    // Cập nhật đề tài với số quyết định (nếu project tồn tại)
    echo "4. Liên kết quyết định với đề tài...\n";
    $update_project_sql = "UPDATE de_tai_nghien_cuu SET QD_SO = ? WHERE DT_MADT = ?";
    $stmt = $conn->prepare($update_project_sql);
    $stmt->bind_param("ss", $decision_number, $project_id);
    if (!$stmt->execute()) {
        echo "   ⚠ Không thể liên kết với đề tài (có thể đề tài không tồn tại): " . $stmt->error . "\n";
    } else {
        echo "   ✓ Liên kết với đề tài thành công\n";
    }

    // Tạo biên bản nghiệm thu
    echo "5. Tạo biên bản nghiệm thu...\n";
    $report_code = "BB" . substr($decision_number, 2); // BB021 từ QD021
    echo "   Mã biên bản: $report_code\n";
    
    $insert_report_sql = "INSERT INTO bien_ban (BB_SOBB, QD_SO, BB_NGAYNGHIEMTHU, BB_XEPLOAI) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_report_sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn tạo biên bản: " . $conn->error);
    }
    
    // Sử dụng ngày quyết định và xếp loại mặc định
    $default_acceptance_date = $decision_date;
    $default_grade = "Chưa nghiệm thu";
    
    $stmt->bind_param("ssss", $report_code, $decision_number, $default_acceptance_date, $default_grade);
    
    if (!$stmt->execute()) {
        throw new Exception("Không thể tạo biên bản nghiệm thu: " . $stmt->error);
    }
    echo "   ✓ Tạo biên bản thành công\n";
    
    // Cập nhật lại quyết định với số biên bản
    echo "6. Liên kết biên bản với quyết định...\n";
    $update_decision_with_report_sql = "UPDATE quyet_dinh_nghiem_thu SET BB_SOBB = ? WHERE QD_SO = ?";
    $stmt = $conn->prepare($update_decision_with_report_sql);
    $stmt->bind_param("ss", $report_code, $decision_number);
    if (!$stmt->execute()) {
        throw new Exception("Không thể liên kết biên bản với quyết định: " . $stmt->error);
    }
    echo "   ✓ Liên kết biên bản với quyết định thành công\n";
    
    echo "\n7. Kiểm tra kết quả...\n";
    // Kiểm tra dữ liệu đã tạo
    $check_sql = "SELECT q.QD_SO, q.BB_SOBB, b.BB_SOBB as REPORT_ID, b.QD_SO as REPORT_QD 
                  FROM quyet_dinh_nghiem_thu q
                  LEFT JOIN bien_ban b ON q.QD_SO = b.QD_SO 
                  WHERE q.QD_SO = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $decision_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "   ✓ Quyết định: {$row['QD_SO']}\n";
        echo "   ✓ Biên bản trong QD: {$row['BB_SOBB']}\n"; 
        echo "   ✓ Biên bản ID: {$row['REPORT_ID']}\n";
        echo "   ✓ QD trong biên bản: {$row['REPORT_QD']}\n";
    }

    // Rollback để không ảnh hưởng dữ liệu thực
    echo "\n8. Rollback để không ảnh hưởng dữ liệu...\n";
    $conn->rollback();
    echo "   ✓ Rollback thành công\n";
    
    echo "\n=== TEST THÀNH CÔNG - KHÔNG CÓ LỖI ===\n";
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    echo "\n✗ LỖI: " . $e->getMessage() . "\n";
    echo "MySQL Error: " . $conn->error . "\n";
    echo "MySQL Errno: " . $conn->errno . "\n";
    
} catch (Error $e) {
    // Rollback transaction  
    $conn->rollback();
    
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== KẾT THÚC TEST ===\n";
?>
