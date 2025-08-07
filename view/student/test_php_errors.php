<?php
// Quick test để kiểm tra PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Error Check</h2>";

try {
    // Test database connection
    include '../include/connect.php';
    echo "✅ Database connection successful<br>";
    
    // Test session
    include '../include/session.php';
    echo "✅ Session file loaded<br>";
    
    // Check if view_project.php can be included without errors
    ob_start();
    $test_include = true;
    include 'view_project.php';
    $output = ob_get_clean();
    
    if (strlen($output) > 0) {
        echo "✅ view_project.php included successfully<br>";
        echo "Output length: " . strlen($output) . " characters<br>";
    } else {
        echo "❌ view_project.php produced no output<br>";
    }
    
} catch (Error $e) {
    echo "❌ PHP Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

echo "<h3>System Info</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Directory: " . getcwd() . "<br>";
?>

<script>
console.log('PHP test page loaded');

// Test if we can access the main page
fetch('view_project.php?id=test')
    .then(response => {
        console.log('Fetch response status:', response.status);
        return response.text();
    })
    .then(data => {
        console.log('Response length:', data.length);
        console.log('Response preview:', data.substring(0, 200));
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
</script>
