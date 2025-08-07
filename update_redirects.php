<?php
echo "=== CẬP NHẬT REDIRECT CHO TẤT CẢ FILES ===\n\n";

// Danh sách các file cần cập nhật
$files_to_update = [
    'view/student/update_report_info.php',
    'view/student/update_report_basic.php',
    'view/student/update_report_basic_info.php'
];

foreach ($files_to_update as $file) {
    $file_path = $file;
    if (!file_exists($file_path)) {
        echo "❌ File không tồn tại: $file_path\n";
        continue;
    }
    
    echo "🔧 Đang cập nhật: $file_path\n";
    
    // Đọc nội dung file
    $content = file_get_contents($file_path);
    
    // Backup file
    $backup_path = $file_path . '.backup_' . date('Ymd_His');
    file_put_contents($backup_path, $content);
    echo "  📋 Đã backup: $backup_path\n";
    
    // Cập nhật redirect patterns
    $patterns_replacements = [
        // Pattern 1: header("Location: view_project.php?id=" . urlencode($project_id));
        '/header\("Location: view_project\.php\?id=" \. urlencode\(\$project_id\)\);/' => 
        'header("Location: view_project.php?id=" . urlencode($project_id) . "&tab=report");',
        
        // Pattern 2: header("Location: view_project.php");
        '/header\("Location: view_project\.php"\);/' => 
        'header("Location: view_project.php");', // Giữ nguyên vì không có project_id
    ];
    
    $updated = false;
    foreach ($patterns_replacements as $pattern => $replacement) {
        $matches = preg_match_all($pattern, $content);
        if ($matches > 0) {
            $content = preg_replace($pattern, $replacement, $content);
            echo "  ✅ Đã cập nhật $matches redirect(s)\n";
            $updated = true;
        }
    }
    
    // Lưu file đã cập nhật
    if ($updated) {
        file_put_contents($file_path, $content);
        echo "  💾 Đã lưu file\n";
    } else {
        echo "  ℹ️ Không có thay đổi nào\n";
        // Xóa backup nếu không có thay đổi
        unlink($backup_path);
    }
    
    echo "\n";
}

echo "=== HOÀN TẤT CẬP NHẬT ===\n";
?>
