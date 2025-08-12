<!DOCTYPE html>
<html>
<head>
    <title>Test File Thuyết Minh</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Test File Thuyết Minh</h1>
    
    <?php
    include 'include/connect.php';
    
    // Test với đề tài DT0000001
    $project_id = 'DT0000001';
    
    // Query đơn giản
    $sql = "SELECT DT_MADT, DT_TENDT, DT_FILEBTM, DT_TRANGTHAI FROM de_tai_nghien_cuu WHERE DT_MADT = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p class='error'>❌ Không tìm thấy đề tài $project_id</p>";
        exit;
    }
    
    $project = $result->fetch_assoc();
    
    echo "<h2>Thông tin đề tài:</h2>";
    echo "<p><strong>Mã đề tài:</strong> " . htmlspecialchars($project['DT_MADT']) . "</p>";
    echo "<p><strong>Tên đề tài:</strong> " . htmlspecialchars($project['DT_TENDT']) . "</p>";
    echo "<p><strong>File thuyết minh:</strong> '" . htmlspecialchars($project['DT_FILEBTM'] ?? 'NULL') . "'</p>";
    echo "<p><strong>Trạng thái:</strong> " . htmlspecialchars($project['DT_TRANGTHAI']) . "</p>";
    
    echo "<h2>Test điều kiện:</h2>";
    echo "<p>1. if (\$project['DT_FILEBTM']): " . ($project['DT_FILEBTM'] ? '<span class="success">TRUE</span>' : '<span class="error">FALSE</span>') . "</p>";
    echo "<p>2. if (!empty(\$project['DT_FILEBTM'])): " . (!empty($project['DT_FILEBTM']) ? '<span class="success">TRUE</span>' : '<span class="error">FALSE</span>') . "</p>";
    
    echo "<h2>Test hiển thị:</h2>";
    
    // Test điều kiện cũ
    echo "<h3>Điều kiện cũ: if (\$project['DT_FILEBTM'])</h3>";
    if ($project['DT_FILEBTM']) {
        echo "<p class='success'>✅ Hiển thị file thuyết minh</p>";
        $file_path = 'uploads/project_files/' . $project['DT_FILEBTM'];
        echo "<p>File path: $file_path</p>";
        echo "<a href='$file_path' download>📥 Tải xuống file</a>";
    } else {
        echo "<p class='error'>❌ Không hiển thị file thuyết minh</p>";
    }
    
    // Test điều kiện mới
    echo "<h3>Điều kiện mới: if (!empty(\$project['DT_FILEBTM']))</h3>";
    if (!empty($project['DT_FILEBTM'])) {
        echo "<p class='success'>✅ Hiển thị file thuyết minh</p>";
        $file_path = 'uploads/project_files/' . $project['DT_FILEBTM'];
        echo "<p>File path: $file_path</p>";
        echo "<a href='$file_path' download>📥 Tải xuống file</a>";
        
        // Kiểm tra file có tồn tại không
        if (file_exists($file_path)) {
            echo "<p class='success'>✅ File tồn tại trên server</p>";
            echo "<p>Kích thước: " . filesize($file_path) . " bytes</p>";
        } else {
            echo "<p class='error'>❌ File không tồn tại trên server</p>";
        }
    } else {
        echo "<p class='error'>❌ Không hiển thị file thuyết minh</p>";
    }
    
    // Kiểm tra thư mục uploads
    echo "<h2>Kiểm tra thư mục uploads:</h2>";
    $uploads_dir = 'uploads/project_files/';
    if (is_dir($uploads_dir)) {
        echo "<p class='success'>✅ Thư mục uploads tồn tại</p>";
        $files = scandir($uploads_dir);
        echo "<p>Các file trong thư mục:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "<li>$file</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Thư mục uploads không tồn tại</p>";
    }
    ?>
</body>
</html>




