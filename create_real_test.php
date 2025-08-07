<?php
include 'include/connect.php';

echo "=== TẠO FILE TEST THỰC TẾ VỚI DỮ LIỆU DATABASE ===\n\n";

// Lấy dữ liệu thực từ DT0000003 (có vấn đề)
$result = $conn->query("
    SELECT 
        dt.DT_MADT,
        dt.DT_TENDT, 
        dt.DT_TRANGTHAI,
        q.QD_SO,
        q.HD_THANHVIEN
    FROM de_tai_nghien_cuu dt
    LEFT JOIN quyet_dinh_nghiem_thu q ON dt.QD_SO = q.QD_SO
    WHERE dt.DT_MADT = 'DT0000003'
");

if ($result && $result->num_rows > 0) {
    $project = $result->fetch_assoc();
    $decision = $project;
    
    echo "Đang tạo test file với dữ liệu thực:\n";
    echo "Project: {$project['DT_MADT']}\n";
    echo "Status: {$project['DT_TRANGTHAI']}\n";
    echo "HD_THANHVIEN có " . strlen($project['HD_THANHVIEN']) . " ký tự\n";
    
    // Tạo HTML test
    $html_content = '<!DOCTYPE html>
<html>
<head>
    <title>Real Data Tab Test - ' . $project['DT_MADT'] . '</title>
    <meta charset="UTF-8">
    
    <!-- Bootstrap CSS (like in real app) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
        .tab-content > .tab-pane { display: none; }
        .tab-content > .tab-pane.show.active { display: block; }
        body { margin: 20px; }
        .test-result { margin: 20px 0; padding: 10px; border: 1px solid #ddd; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Real Data Tab Test</h1>
        <p><strong>Project:</strong> ' . htmlspecialchars($project['DT_MADT']) . ' (' . htmlspecialchars($project['DT_TRANGTHAI']) . ')</p>
        <p><strong>Data Length:</strong> ' . strlen($project['HD_THANHVIEN']) . ' characters</p>
        
        <div class="test-result" id="test-status"></div>
        
        <!-- Simulate the exact structure from view_project.php -->
        <ul class="nav nav-tabs" id="documentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="proposal-tab" data-toggle="tab" href="#proposal" role="tab">
                    <i class="fas fa-file-alt mr-1"></i> Thuyết minh
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="contract-tab" data-toggle="tab" href="#contract" role="tab">
                    <i class="fas fa-file-signature mr-1"></i> Hợp đồng
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="decision-tab" data-toggle="tab" href="#decision" role="tab">
                    <i class="fas fa-gavel mr-1"></i> Quyết định
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="report-tab" data-toggle="tab" href="#report" role="tab">
                    <i class="fas fa-file-invoice mr-1"></i> Biên bản
                </a>
            </li>
        </ul>

        <div class="tab-content" id="documentTabsContent">
            <div class="tab-pane fade show active" id="proposal" role="tabpanel">
                <h3>Tab Thuyết minh</h3>
                <p>Nội dung tab 1</p>
            </div>
            <div class="tab-pane fade" id="contract" role="tabpanel">
                <h3>Tab Hợp đồng</h3>
                <p>Nội dung tab 2</p>
            </div>
            <div class="tab-pane fade" id="decision" role="tabpanel">
                <h3>Tab Quyết định</h3>
                <p>Nội dung tab 3</p>
            </div>
            <div class="tab-pane fade" id="report" role="tabpanel">
                <h3>Tab Biên bản</h3>
                <p>Nội dung tab 4</p>
                
                <!-- PROBLEMATIC INPUT (OLD VERSION) -->
                <h4>Test OLD version (with newlines):</h4>
                <input type="hidden" id="council_members_old" name="council_members" value="' . htmlspecialchars($decision['HD_THANHVIEN'] ?? '') . '">
                
                <!-- FIXED INPUT (NEW VERSION) -->
                <h4>Test NEW version (fixed):</h4>
                <input type="hidden" id="council_members_new" name="council_members" value="' . htmlspecialchars(str_replace(array("\r", "\n"), ' ', $decision['HD_THANHVIEN'] ?? '')) . '">
                
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="testOldVersion()">Test OLD Version</button>
                    <button class="btn btn-success" onclick="testNewVersion()">Test NEW Version</button>
                </div>
                
                <div id="test-results" class="mt-3"></div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS (like in real app) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            console.log("Document ready");
            
            // Test if tabs work initially
            setTimeout(function() {
                testTabFunctionality();
            }, 1000);
        });
        
        function testTabFunctionality() {
            try {
                // Try to switch to each tab
                $("#contract-tab").tab("show");
                setTimeout(() => {
                    $("#decision-tab").tab("show");
                    setTimeout(() => {
                        $("#report-tab").tab("show");
                        setTimeout(() => {
                            $("#proposal-tab").tab("show");
                            $("#test-status").html("<div class=\'success\'>✅ Tabs are working normally!</div>");
                        }, 200);
                    }, 200);
                }, 200);
                
            } catch (e) {
                $("#test-status").html("<div class=\'error\'>❌ Tab error: " + e.message + "</div>");
                console.error("Tab error:", e);
            }
        }
        
        function testOldVersion() {
            try {
                const oldValue = document.getElementById("council_members_old").value;
                const lines = oldValue.split("\\n").length;
                const result = "OLD: " + lines + " lines, length: " + oldValue.length;
                document.getElementById("test-results").innerHTML += "<div>📊 " + result + "</div>";
                console.log("OLD value:", oldValue);
            } catch (e) {
                document.getElementById("test-results").innerHTML += "<div class=\'error\'>❌ OLD error: " + e.message + "</div>";
            }
        }
        
        function testNewVersion() {
            try {
                const newValue = document.getElementById("council_members_new").value;
                const lines = newValue.split("\\n").length;
                const result = "NEW: " + lines + " lines, length: " + newValue.length;
                document.getElementById("test-results").innerHTML += "<div>📊 " + result + "</div>";
                console.log("NEW value:", newValue);
            } catch (e) {
                document.getElementById("test-results").innerHTML += "<div class=\'error\'>❌ NEW error: " + e.message + "</div>";
            }
        }
        
        // Log any JavaScript errors
        window.addEventListener("error", function(e) {
            console.error("Global error:", e.error);
            $("#test-status").html("<div class=\'error\'>❌ JavaScript Error: " + e.message + "</div>");
        });
    </script>
</body>
</html>';
    
    // Lưu file
    file_put_contents('test_real_data.html', $html_content);
    echo "✅ Đã tạo file test_real_data.html\n";
    
} else {
    echo "❌ Không tìm thấy dữ liệu cho DT0000003\n";
}

echo "\n=== HOÀN TẤT ===\n";
?>
