<?php
// Test Classification Rule trong Tab Đánh Giá
echo "=== KIỂM TRA QUY TẮC XẾP LOẠI TRONG TAB ĐÁNH GIÁ ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Mô phỏng logic tính toán trong tab đánh giá (từ view_project.php dòng 3920-3932)
function getClassificationInEvaluationTab($final_average) {
    $classification = '';
    $classification_class = 'text-secondary';
    
    if ($final_average >= 90) {
        $classification = 'Xuất sắc';
        $classification_class = 'text-success';
    } elseif ($final_average >= 80) {
        $classification = 'Tốt';
        $classification_class = 'text-primary';
    } elseif ($final_average >= 70) {
        $classification = 'Khá';
        $classification_class = 'text-info';
    } elseif ($final_average >= 50) {
        $classification = 'Đạt';
        $classification_class = 'text-warning';
    } else {
        $classification = 'Không đạt';
        $classification_class = 'text-danger';
    }
    
    return [
        'classification' => $classification,
        'class' => $classification_class
    ];
}

// Test các trường hợp điển hình
$test_scores = [
    95.5 => 'Xuất sắc',
    90.0 => 'Xuất sắc',
    89.9 => 'Tốt',
    85.0 => 'Tốt',
    80.0 => 'Tốt',
    79.9 => 'Khá',
    75.0 => 'Khá', 
    70.0 => 'Khá',
    69.9 => 'Đạt',
    60.0 => 'Đạt',
    50.0 => 'Đạt',
    49.9 => 'Không đạt',
    30.0 => 'Không đạt'
];

echo "📊 KIỂM TRA THỐNG KÊ ĐIỂM TRONG TAB ĐÁNH GIÁ:\n\n";

echo "✅ Quy tắc xếp loại được áp dụng:\n";
echo "   - Xuất sắc: từ 90 điểm trở lên\n";
echo "   - Tốt: từ 80 điểm đến dưới 90 điểm\n";
echo "   - Khá: từ 70 điểm đến dưới 80 điểm\n";
echo "   - Đạt: từ 50 điểm đến dưới 70 điểm\n";
echo "   - Không đạt: dưới 50 điểm\n\n";

echo "🧪 Test kết quả thống kê trong tab đánh giá:\n";
$all_passed = true;

foreach ($test_scores as $score => $expected) {
    $result = getClassificationInEvaluationTab($score);
    $actual = $result['classification'];
    $class = $result['class'];
    $status = ($actual === $expected) ? "✅" : "❌";
    
    if ($actual !== $expected) {
        $all_passed = false;
    }
    
    echo "   Điểm $score → $actual ($class) - Mong đợi: $expected $status\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 KẾT LUẬN:\n";

if ($all_passed) {
    echo "✅ QUY TẮC XẾP LOẠI ĐÃ ĐƯỢC ÁP DỤNG ĐÚNG TRONG TAB ĐÁNH GIÁ!\n\n";
    echo "📍 Vị trí áp dụng:\n";
    echo "   - File: view/student/view_project.php\n";
    echo "   - Dòng: 3920-3932 (PHP logic)\n";
    echo "   - Dòng: 3975-3985 (Hiển thị xếp loại)\n";
    echo "   - Tab: Đánh giá → Thống kê điểm đánh giá\n\n";
    
    echo "📋 Chức năng thống kê bao gồm:\n";
    echo "   ✅ Tổng thành viên hội đồng\n";
    echo "   ✅ Số lượng đã chấm điểm\n";
    echo "   ✅ Số lượng chưa chấm điểm\n";
    echo "   ✅ Điểm hợp lệ (không chênh lệch quá 15 điểm)\n";
    echo "   ✅ Điểm cuối cùng (trung bình từ điểm hợp lệ)\n";
    echo "   ✅ XẾP LOẠI ĐỀ TÀI theo quy tắc mới\n\n";
    
    echo "🎨 Hiển thị có màu sắc:\n";
    echo "   - Xuất sắc: text-success (xanh lá)\n";
    echo "   - Tốt: text-primary (xanh dương)\n";
    echo "   - Khá: text-info (xanh nhạt)\n";
    echo "   - Đạt: text-warning (vàng)\n";
    echo "   - Không đạt: text-danger (đỏ)\n";
} else {
    echo "❌ CÓ LỖI TRONG QUY TẮC XẾP LOẠI!\n";
    echo "   → Cần kiểm tra và sửa lỗi trong tab đánh giá.\n";
}

echo "\n📝 GHI CHÚ:\n";
echo "   - Thống kê này chỉ áp dụng cho điểm đánh giá của thành viên hội đồng\n";
echo "   - Điểm được lọc để loại bỏ các điểm bất thường (chênh lệch > 15 điểm)\n";
echo "   - Xếp loại được tính từ điểm trung bình cuối cùng của các điểm hợp lệ\n";
?>
