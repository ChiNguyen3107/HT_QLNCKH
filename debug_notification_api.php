<?php
/**
 * Debug Notification API
 * Kiểm tra session và API thông báo
 */

include 'include/session.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Notification API</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1>🔍 Debug Notification API</h1>
    <hr>
";

// Kiểm tra session
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>📋 Thông tin Session</h5></div>";
echo "<div class='card-body'>";
echo "<table class='table table-sm'>";
echo "<tr><td><strong>Session Status:</strong></td><td>" . session_status() . " (" . 
    (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . ")</td></tr>";
echo "<tr><td><strong>Session ID:</strong></td><td>" . session_id() . "</td></tr>";
echo "<tr><td><strong>User ID:</strong></td><td>" . ($_SESSION['user_id'] ?? 'Not set') . "</td></tr>";
echo "<tr><td><strong>Role:</strong></td><td>" . ($_SESSION['role'] ?? 'Not set') . "</td></tr>";
echo "<tr><td><strong>Username:</strong></td><td>" . ($_SESSION['username'] ?? 'Not set') . "</td></tr>";
echo "</table>";

echo "<h6>Full Session Data:</h6>";
echo "<pre class='bg-light p-2'>" . print_r($_SESSION, true) . "</pre>";
echo "</div></div>";

// Test API calls
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>🧪 Test API Calls</h5></div>";
echo "<div class='card-body'>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<button id='test-count' class='btn btn-primary mb-2'>Test Get Count</button>";
echo "<button id='test-notifications' class='btn btn-success mb-2'>Test Get Notifications</button>";
echo "<button id='test-create' class='btn btn-warning mb-2'>Test Create Notification</button>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<div id='api-results'>";
echo "<div class='alert alert-info'>Nhấn các nút để test API</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div></div>";

// Direct API test
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>🔗 Direct API Test</h5></div>";
echo "<div class='card-body'>";

try {
    // Test direct database connection
    include 'include/connect.php';
    
    // Test thông báo trong database
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM thong_bao WHERE TB_MUCTIEU IN ('research_manager', 'all') AND TB_DANHDOC = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    echo "<div class='alert alert-success'>";
    echo "<strong>✅ Database Connection:</strong> OK<br>";
    echo "<strong>📊 Unread Notifications:</strong> $count";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>❌ Database Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "</div></div>";

echo "
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
<script>
document.getElementById('test-count').addEventListener('click', async () => {
    try {
        const response = await fetch('/NLNganh/api/notification_manager.php?action=get_count');
        const data = await response.json();
        
        document.getElementById('api-results').innerHTML = 
            '<div class=\"alert alert-' + (data.success ? 'success' : 'danger') + '\">' +
            '<h6>Get Count Result:</h6>' +
            '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
            '</div>';
    } catch (error) {
        document.getElementById('api-results').innerHTML = 
            '<div class=\"alert alert-danger\">' +
            '<h6>Error:</h6>' +
            '<pre>' + error.message + '</pre>' +
            '</div>';
    }
});

document.getElementById('test-notifications').addEventListener('click', async () => {
    try {
        const response = await fetch('/NLNganh/api/notification_manager.php?action=get_notifications&limit=5');
        const data = await response.json();
        
        document.getElementById('api-results').innerHTML = 
            '<div class=\"alert alert-' + (data.success ? 'success' : 'danger') + '\">' +
            '<h6>Get Notifications Result:</h6>' +
            '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
            '</div>';
    } catch (error) {
        document.getElementById('api-results').innerHTML = 
            '<div class=\"alert alert-danger\">' +
            '<h6>Error:</h6>' +
            '<pre>' + error.message + '</pre>' +
            '</div>';
    }
});

document.getElementById('test-create').addEventListener('click', async () => {
    try {
        const testData = {
            noi_dung: '🧪 Test notification từ debug page - ' + new Date().toLocaleString(),
            loai: 'test',
            muc_tieu: 'research_manager',
            muc_do: 'trung_binh'
        };
        
        const response = await fetch('/NLNganh/api/notification_manager.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(testData)
        });
        
        const data = await response.json();
        
        document.getElementById('api-results').innerHTML = 
            '<div class=\"alert alert-' + (data.success ? 'success' : 'danger') + '\">' +
            '<h6>Create Notification Result:</h6>' +
            '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
            '</div>';
    } catch (error) {
        document.getElementById('api-results').innerHTML = 
            '<div class=\"alert alert-danger\">' +
            '<h6>Error:</h6>' +
            '<pre>' + error.message + '</pre>' +
            '</div>';
    }
});
</script>
</body>
</html>";
?>

