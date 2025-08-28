<?php
// Quick debug script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Quick Debug Extension Request</h1>";

// Test 1: Basic PHP
echo "<h2>1. PHP Basic Test:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

// Test 2: File existence
echo "<h2>2. File Existence:</h2>";
$files_to_check = [
    'process_extension_request.php',
    '../../include/session.php',
    '../../include/connect.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT exists<br>";
    }
}

// Test 3: Session
echo "<h2>3. Session Test:</h2>";
session_start();
if (empty($_SESSION)) {
    echo "❌ No session data<br>";
    echo "<p><a href='../../../login.php'>→ Please login first</a></p>";
} else {
    echo "✅ Session exists<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
    echo "Role: " . ($_SESSION['role'] ?? 'not set') . "<br>";
}

// Test 4: Database
echo "<h2>4. Database Test:</h2>";
try {
    include '../../include/connect.php';
    if ($conn->connect_error) {
        echo "❌ DB Error: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Database connected<br>";
        
        // Test query
        $result = $conn->query("SELECT COUNT(*) as count FROM de_tai_nghien_cuu");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Query test passed. Projects count: " . $row['count'] . "<br>";
        } else {
            echo "❌ Query test failed: " . $conn->error . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ DB Exception: " . $e->getMessage() . "<br>";
}

// Test 5: Extension table
echo "<h2>5. Extension Table Test:</h2>";
if (isset($conn)) {
    $table_check = $conn->query("SHOW TABLES LIKE 'de_tai_gia_han'");
    if ($table_check && $table_check->num_rows > 0) {
        echo "✅ de_tai_gia_han table exists<br>";
        
        // Check table structure
        $structure = $conn->query("DESCRIBE de_tai_gia_han");
        if ($structure) {
            echo "✅ Table structure accessible<br>";
            echo "Columns: ";
            $columns = [];
            while ($col = $structure->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            echo implode(', ', $columns) . "<br>";
        }
    } else {
        echo "❌ de_tai_gia_han table NOT exists<br>";
        echo "<p><a href='../../create_extension_table_if_not_exists.php'>→ Create table</a></p>";
    }
}

// Test 6: Direct POST test
echo "<h2>6. Direct POST Test:</h2>";
?>

<form method="POST" action="">
    <input type="hidden" name="test_post" value="1">
    <button type="submit">Test POST Request</button>
</form>

<?php
if (isset($_POST['test_post'])) {
    echo "✅ POST request works<br>";
    echo "POST data: ";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}

// Test 7: AJAX endpoint test
echo "<h2>7. AJAX Endpoint Test:</h2>";
?>

<button onclick="testAjax()">Test AJAX to process_extension_request.php</button>
<div id="ajax-result"></div>

<script>
function testAjax() {
    fetch('process_extension_request.php', {
        method: 'GET'  // Just test if endpoint is reachable
    })
    .then(response => {
        document.getElementById('ajax-result').innerHTML = 
            '✅ AJAX endpoint reachable. Status: ' + response.status;
        return response.text();
    })
    .then(data => {
        console.log('Response:', data);
        document.getElementById('ajax-result').innerHTML += 
            '<br>Response: ' + data.substring(0, 200) + '...';
    })
    .catch(error => {
        document.getElementById('ajax-result').innerHTML = 
            '❌ AJAX error: ' + error;
    });
}
</script>

<hr>
<p><strong>Next Steps:</strong></p>
<ol>
    <li><a href="test_extension_request.php">→ Full Test Form</a></li>
    <li><a href="debug_process_extension.php">→ Debug Process (POST)</a></li>
    <li><a href="manage_extensions.php">→ Main Extension Page</a></li>
</ol>

