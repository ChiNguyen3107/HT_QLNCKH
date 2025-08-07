<?php
// File helper để test và sửa lỗi query quyết định nghiệm thu
include 'include/connect.php';

function testDecisionQueries($project_id, $conn) {
    $results = [
        'project_exists' => false,
        'queries' => [],
        'suggestions' => []
    ];
    
    // 1. Kiểm tra đề tài có tồn tại không
    $project_sql = "SELECT DT_MADT, DT_TENDT, QD_SO FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($project_sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $project_result = $stmt->get_result();
    
    if ($project_result->num_rows > 0) {
        $project_data = $project_result->fetch_assoc();
        $results['project_exists'] = true;
        $results['project_data'] = $project_data;
        
        // 2. Test query 1: JOIN với de_tai_nghien_cuu.QD_SO
        if (isset($project_data['QD_SO']) && !empty($project_data['QD_SO'])) {
            $sql1 = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
                    FROM quyet_dinh_nghiem_thu qd
                    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                    LEFT JOIN de_tai_nghien_cuu dt ON qd.QD_SO = dt.QD_SO
                    WHERE dt.DT_MADT = ?";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("s", $project_id);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $results['queries']['join_method'] = [
                'sql' => $sql1,
                'count' => $result1->num_rows,
                'data' => $result1->num_rows > 0 ? $result1->fetch_assoc() : null
            ];
        }
        
        // 3. Test query 2: Trực tiếp với DT_MADT (nếu có)
        $sql2 = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
                FROM quyet_dinh_nghiem_thu qd
                LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                WHERE qd.DT_MADT = ?";
        $stmt2 = $conn->prepare($sql2);
        if ($stmt2) {
            $stmt2->bind_param("s", $project_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $results['queries']['direct_method'] = [
                'sql' => $sql2,
                'count' => $result2->num_rows,
                'data' => $result2->num_rows > 0 ? $result2->fetch_assoc() : null
            ];
        }
        
        // 4. Test query 3: Tìm theo QD_SO trong project
        if (isset($project_data['QD_SO']) && !empty($project_data['QD_SO'])) {
            $sql3 = "SELECT qd.*, bb.BB_SOBB, bb.BB_NGAYNGHIEMTHU, bb.BB_XEPLOAI, bb.BB_TONGDIEM
                    FROM quyet_dinh_nghiem_thu qd
                    LEFT JOIN bien_ban bb ON qd.QD_SO = bb.QD_SO
                    WHERE qd.QD_SO = ?";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bind_param("s", $project_data['QD_SO']);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            $results['queries']['qd_so_method'] = [
                'sql' => $sql3,
                'count' => $result3->num_rows,
                'data' => $result3->num_rows > 0 ? $result3->fetch_assoc() : null
            ];
        }
        
        // 5. Đề xuất giải pháp
        $working_queries = array_filter($results['queries'], function($q) {
            return $q['count'] > 0;
        });
        
        if (empty($working_queries)) {
            $results['suggestions'][] = "Không tìm thấy quyết định nghiệm thu nào cho đề tài này";
            $results['suggestions'][] = "Kiểm tra xem có dữ liệu trong bảng quyet_dinh_nghiem_thu không";
            $results['suggestions'][] = "Kiểm tra cấu trúc liên kết giữa các bảng";
        } else {
            $best_query = array_keys($working_queries)[0];
            $results['suggestions'][] = "Sử dụng phương pháp: " . $best_query;
            $results['recommended_sql'] = $working_queries[$best_query]['sql'];
        }
    }
    
    return $results;
}

// Test với một project ID cụ thể
$test_project = "DT001"; // Thay đổi theo project thực tế
$results = testDecisionQueries($test_project, $conn);

echo "<h2>Kết quả test query quyết định nghiệm thu</h2>";
echo "<p>Project ID: <strong>$test_project</strong></p>";

if ($results['project_exists']) {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ Đề tài tồn tại: " . htmlspecialchars($results['project_data']['DT_TENDT']);
    if (isset($results['project_data']['QD_SO'])) {
        echo "<br>QD_SO trong project: " . htmlspecialchars($results['project_data']['QD_SO']);
    }
    echo "</div>";
    
    echo "<h3>Kết quả các query:</h3>";
    foreach ($results['queries'] as $method => $query_info) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<h4>" . ucfirst(str_replace('_', ' ', $method)) . "</h4>";
        echo "<p><strong>Số kết quả:</strong> " . $query_info['count'] . "</p>";
        if ($query_info['count'] > 0) {
            echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 3px;'>";
            echo "<strong>Dữ liệu tìm thấy:</strong><br>";
            echo "<pre>" . print_r($query_info['data'], true) . "</pre>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    echo "<h3>Đề xuất:</h3>";
    foreach ($results['suggestions'] as $suggestion) {
        echo "<p>• " . htmlspecialchars($suggestion) . "</p>";
    }
    
    if (isset($results['recommended_sql'])) {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>SQL được đề xuất:</strong><br>";
        echo "<code>" . htmlspecialchars($results['recommended_sql']) . "</code>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Không tìm thấy đề tài với ID: $test_project";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>Chạy file này với project ID thực tế để tìm ra cách query đúng.</em></p>";
?>
