<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Simulate student login for testing
if (isset($_GET['simulate']) && $_GET['simulate'] === 'student') {
    $_SESSION['user_id'] = 'SV001';
    $_SESSION['username'] = 'student@test.com';
    $_SESSION['role'] = 'student';
    $_SESSION['user_name'] = 'Test Student';
    echo "<div class='alert alert-success'>✅ Student session simulated!</div>";
}

// Clear session
if (isset($_GET['clear'])) {
    session_destroy();
    session_start();
    echo "<div class='alert alert-info'>🔄 Session cleared!</div>";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Test</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🔐 Authentication Testing Tool</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Current Session Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION)): ?>
                            <p class="text-danger">❌ No active session</p>
                        <?php else: ?>
                            <p class="text-success">✅ Session active</p>
                            <ul>
                                <?php foreach ($_SESSION as $key => $value): ?>
                                    <li><strong><?php echo $key; ?>:</strong> <?php echo $value; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="?simulate=student" class="btn btn-primary mb-2">🎭 Simulate Student Login</a><br>
                        <a href="?clear=1" class="btn btn-warning mb-2">🔄 Clear Session</a><br>
                        <a href="debug_session.php" class="btn btn-info mb-2">🔍 Debug Session</a><br>
                        <button id="testEvaluation" class="btn btn-success mb-2">✅ Test Evaluation</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Evaluation System Test</h5>
                    </div>
                    <div class="card-body">
                        <div id="testResults">
                            <p>Click "Test Evaluation" to test the evaluation system with current session.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" defer></script>
    
    <script>
        $('#testEvaluation').click(function() {
            $('#testResults').html('<div class="alert alert-info">Testing evaluation system...</div>');
            
            // Test get_member_criteria_scores.php
            $.ajax({
                url: 'view/student/get_member_criteria_scores.php',
                method: 'GET',
                data: { 
                    member_id: 'GV001',
                    project_id: 'DT001'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#testResults').html('<div class="alert alert-success">✅ Evaluation system working! Data retrieved successfully.</div>');
                    } else {
                        $('#testResults').html('<div class="alert alert-danger">❌ Evaluation failed: ' + response.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = xhr.responseText || error;
                    $('#testResults').html('<div class="alert alert-danger">❌ Request failed: ' + errorMsg + '</div>');
                }
            });
        });
    </script>
</body>
</html>
