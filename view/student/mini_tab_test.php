<?php
// Simple test page to check view_project.php functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>View Project PHP Test</h2>";

// Check if required files exist
$required_files = [
    '../include/session.php',
    '../include/connect.php', 
    '../include/functions.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ File exists: $file<br>";
    } else {
        echo "❌ File missing: $file<br>";
    }
}

echo "<h3>Test Tab Navigation</h3>";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Tab Test</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h4>Minimal Tab Test</h4>
        
        <!-- Minimal tab structure -->
        <ul class="nav nav-tabs mb-3" id="documentTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="proposal-tab" data-toggle="tab" href="#proposal" role="tab">
                    <i class="fas fa-file-alt mr-1"></i> Thuyết minh
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="report-tab" data-toggle="tab" href="#report" role="tab">
                    <i class="fas fa-file-invoice mr-1"></i> Biên bản
                </a>
            </li>
        </ul>
        
        <div class="tab-content" id="documentTabsContent">
            <div class="tab-pane fade show active" id="proposal" role="tabpanel">
                <div class="alert alert-info">
                    <h5>Tab Thuyết minh</h5>
                    <p>This tab should be active by default.</p>
                </div>
            </div>
            
            <div class="tab-pane fade" id="report" role="tabpanel">
                <div class="alert alert-success">
                    <h5>Tab Biên bản</h5>
                    <p><strong>This tab should be active when URL contains ?tab=report</strong></p>
                    <button class="btn btn-primary" onclick="testUrl()">Test URL Change</button>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <strong>Current URL:</strong> <span id="currentUrl"></span><br>
            <strong>Active Tab:</strong> <span id="activeTab"></span><br>
            <strong>Session Storage:</strong> <span id="sessionInfo"></span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function updateInfo() {
            document.getElementById('currentUrl').innerText = window.location.href;
            const activeTab = $('.nav-tabs .nav-link.active').attr('href') || 'none';
            document.getElementById('activeTab').innerText = activeTab;
            document.getElementById('sessionInfo').innerText = sessionStorage.getItem('activeTab') || 'none';
        }
        
        function testUrl() {
            window.location.href = window.location.pathname + '?tab=report';
        }
        
        $(document).ready(function() {
            console.log('Mini test page ready');
            updateInfo();
            
            // Check for URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const urlTab = urlParams.get('tab');
            
            if (urlTab === 'report') {
                console.log('Activating report tab from URL');
                $('.nav-tabs .nav-link').removeClass('active');
                $('.tab-pane').removeClass('show active');
                
                $('#report-tab').addClass('active');
                $('#report').addClass('show active');
                
                sessionStorage.setItem('activeTab', 'report');
            }
            
            // Tab click handler
            $('a[data-toggle="tab"]').on('click', function(e) {
                e.preventDefault();
                const target = $(this).attr('href');
                const tabName = target.substring(1);
                
                $('.nav-tabs .nav-link').removeClass('active');
                $('.tab-pane').removeClass('show active');
                
                $(this).addClass('active');
                $(target).addClass('show active');
                
                sessionStorage.setItem('activeTab', tabName);
                
                // Update URL
                const newUrl = window.location.pathname + '?tab=' + tabName;
                history.pushState(null, null, newUrl);
                
                updateInfo();
                console.log('Tab activated:', tabName);
            });
            
            updateInfo();
        });
    </script>
</body>
</html>
