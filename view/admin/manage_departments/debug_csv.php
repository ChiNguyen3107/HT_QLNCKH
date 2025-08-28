<?php
// Debug file để test CSV parsing

if (isset($_FILES['testFile'])) {
    $file = $_FILES['testFile'];
    
    echo "<h3>File Info:</h3>";
    echo "Name: " . $file['name'] . "<br>";
    echo "Size: " . $file['size'] . " bytes<br>";
    echo "Type: " . $file['type'] . "<br>";
    
    $content = file_get_contents($file['tmp_name']);
    
    echo "<h3>Raw Content:</h3>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
    
    echo "<h3>Hex Dump (first 100 chars):</h3>";
    echo "<pre>" . bin2hex(substr($content, 0, 100)) . "</pre>";
    
    // Test parsing
    $delimiter = ',';
    if (substr_count($content, ';') > substr_count($content, ',')) {
        $delimiter = ';';
    }
    
    echo "<h3>Detected Delimiter: '$delimiter'</h3>";
    
    $lines = explode("\n", $content);
    echo "<h3>Parsed Lines:</h3>";
    
    foreach ($lines as $i => $line) {
        if (empty(trim($line))) continue;
        
        $data = str_getcsv(trim($line), $delimiter);
        echo "<strong>Line " . ($i + 1) . ":</strong> " . count($data) . " columns<br>";
        echo "<pre>";
        foreach ($data as $j => $col) {
            echo "[$j] = '" . htmlspecialchars($col) . "' (length: " . strlen($col) . ")<br>";
        }
        echo "</pre><hr>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug CSV</title>
</head>
<body>
    <h2>Test CSV File</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="testFile" accept=".csv">
        <button type="submit">Test</button>
    </form>
</body>
</html>
