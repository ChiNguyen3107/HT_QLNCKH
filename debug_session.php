<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Session - Authentication</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-card { margin-bottom: 1rem; }
        .session-data { background: #f8f9fa; padding: 1rem; border-radius: 0.25rem; }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>🔍 Session Authentication Debug</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card debug-card">
                    <div class="card-header">
                        <h5>📊 Session Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if (session_status() === PHP_SESSION_ACTIVE): ?>
                            <p class="status-ok">✅ <strong>Session Active</strong></p>
                        <?php else: ?>
                            <p class="status-error">❌ <strong>Session Inactive</strong></p>
                        <?php endif; ?>
                        
                        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                        <p><strong>Session Status:</strong> <?php echo session_status(); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card debug-card">
                    <div class="card-header">
                        <h5>🔑 Authentication Check</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $isLoggedIn = false;
                        $userType = 'Unknown';
                        
                        if (isset($_SESSION['user_id'])) {
                            echo '<p class="status-ok">✅ user_id: ' . $_SESSION['user_id'] . '</p>';
                            $isLoggedIn = true;
                        } else {
                            echo '<p class="status-error">❌ user_id: Not set</p>';
                        }
                        
                        if (isset($_SESSION['student_id'])) {
                            echo '<p class="status-ok">✅ student_id: ' . $_SESSION['student_id'] . '</p>';
                            $userType = 'Student';
                        } else {
                            echo '<p class="status-warning">⚠️ student_id: Not set</p>';
                        }
                        
                        if (isset($_SESSION['role'])) {
                            echo '<p class="status-ok">✅ role: ' . $_SESSION['role'] . '</p>';
                            $userType = $_SESSION['role'];
                        } else {
                            echo '<p class="status-warning">⚠️ role: Not set</p>';
                        }
                        ?>
                        
                        <hr>
                        <p><strong>Authentication Status:</strong> 
                            <span class="<?php echo $isLoggedIn ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $isLoggedIn ? '✅ Logged In' : '❌ Not Logged In'; ?>
                            </span>
                        </p>
                        <p><strong>User Type:</strong> <?php echo $userType; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card debug-card">
                    <div class="card-header">
                        <h5>📋 Complete Session Data</h5>
                    </div>
                    <div class="card-body">
                        <div class="session-data">
                            <strong>All Session Variables:</strong><br>
                            <?php if (empty($_SESSION)): ?>
                                <em class="status-error">No session data found</em>
                            <?php else: ?>
                                <pre><?php print_r($_SESSION); ?></pre>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card debug-card">
                    <div class="card-header">
                        <h5>🔧 Authentication Requirements</h5>
                    </div>
                    <div class="card-body">
                        <h6>File Requirements:</h6>
                        <ul>
                            <li><code>update_member_criteria_score.php</code>: Requires <code>$_SESSION['student_id']</code></li>
                            <li><code>get_member_criteria_scores.php</code>: Requires <code>$_SESSION['student_id']</code></li>
                            <li><code>update_member_score.php</code>: Requires <code>$_SESSION['user_id']</code> AND <code>$_SESSION['role'] === 'Sinh viên'</code></li>
                            <li><code>upload_member_evaluation.php</code>: Requires <code>$_SESSION['user_id']</code> AND <code>$_SESSION['role'] === 'Sinh viên'</code></li>
                        </ul>
                        
                        <h6 class="mt-3">Current Status:</h6>
                        <?php
                        $criteriaAuth = isset($_SESSION['student_id']);
                        $memberAuth = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Sinh viên';
                        ?>
                        <ul>
                            <li>Criteria Score Files: <span class="<?php echo $criteriaAuth ? 'status-ok' : 'status-error'; ?>"><?php echo $criteriaAuth ? '✅ Authorized' : '❌ Not Authorized'; ?></span></li>
                            <li>Member Score Files: <span class="<?php echo $memberAuth ? 'status-ok' : 'status-error'; ?>"><?php echo $memberAuth ? '✅ Authorized' : '❌ Not Authorized'; ?></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card debug-card">
                    <div class="card-header">
                        <h5>🛠️ Fix Actions</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary" onclick="testAuthentication()">Test Authentication</button>
                        <button class="btn btn-warning" onclick="simulateSession()">Simulate Session</button>
                        <button class="btn btn-info" onclick="location.reload()">Refresh</button>
                        
                        <div id="testResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" defer></script>
    
    <script>
        function testAuthentication() {
            $('#testResults').html('<div class="alert alert-info">Testing authentication...</div>');
            
            $.ajax({
                url: 'get_member_criteria_scores.php',
                method: 'POST',
                data: { qd_so: 'QDDT0', gv_magv: 'GV001' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#testResults').html('<div class="alert alert-success">✅ Authentication working for criteria scores</div>');
                    } else {
                        $('#testResults').html('<div class="alert alert-danger">❌ Authentication failed: ' + response.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#testResults').html('<div class="alert alert-danger">❌ Request failed: ' + error + '</div>');
                }
            });
        }
        
        function simulateSession() {
            $('#testResults').html('<div class="alert alert-warning">⚠️ Session simulation would require backend changes</div>');
        }
    </script>
</body>
</html>
