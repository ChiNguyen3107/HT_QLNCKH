<?php
/**
 * Kiểm tra cấu trúc session storage
 * Hướng dẫn test thủ công KT15-4
 */

require_once 'include/connect.php';
require_once 'include/session.php';

session_start();

class SessionStructureChecker {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Hiển thị thông tin session hiện tại
     */
    public function showCurrentSession() {
        echo "<h3>📊 Thông tin Session hiện tại</h3>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        
        if (empty($_SESSION)) {
            echo "<p style='color: orange;'><strong>⚠️ Không có session nào đang hoạt động</strong></p>";
            echo "<p>Để test KT15-4, bạn cần đăng nhập trước.</p>";
        } else {
            echo "<p style='color: green;'><strong>✅ Có session đang hoạt động</strong></p>";
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #e9ecef;'>";
            echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Key</th>";
            echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Value</th>";
            echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Mô tả</th>";
            echo "</tr>";
            
            foreach ($_SESSION as $key => $value) {
                $description = $this->getSessionKeyDescription($key, $value);
                echo "<tr>";
                echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><code>$key</code></td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . htmlspecialchars($value) . "</td>";
                echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>$description</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    }
    
    /**
     * Mô tả cho từng key session
     */
    private function getSessionKeyDescription($key, $value) {
        switch ($key) {
            case 'user_id':
                return 'ID người dùng đang đăng nhập';
            case 'role':
                return 'Vai trò người dùng (admin/teacher/student/research_manager)';
            case 'username':
                return 'Tên đăng nhập';
            case 'last_activity':
                return 'Thời gian hoạt động cuối cùng (timestamp) - <strong>QUAN TRỌNG cho KT15-4</strong>';
            case 'ip_address':
                return 'Địa chỉ IP của người dùng';
            case 'user_agent':
                return 'User agent của trình duyệt';
            default:
                return 'Thông tin khác';
        }
    }
    
    /**
     * Hướng dẫn test thủ công chi tiết
     */
    public function showManualTestGuide() {
        echo "<h3>📖 Hướng dẫn test thủ công KT15-4</h3>";
        
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; border: 1px solid #ffeaa7; margin: 15px 0;'>";
        echo "<h4>🔧 Bước 1: Đăng nhập</h4>";
        echo "<ol>";
        echo "<li>Truy cập: <code>http://localhost/NLNganh/login.php</code></li>";
        echo "<li>Đăng nhập với tài khoản bất kỳ</li>";
        echo "<li>Xác nhận đã vào được dashboard</li>";
        echo "</ol>";
        
        echo "<h4>🔧 Bước 2: Mở Developer Tools</h4>";
        echo "<ul>";
        echo "<li><strong>Chrome/Edge:</strong> Nhấn <code>F12</code> hoặc <code>Ctrl+Shift+I</code></li>";
        echo "<li><strong>Firefox:</strong> Nhấn <code>F12</code> hoặc <code>Ctrl+Shift+I</code></li>";
        echo "<li><strong>Safari:</strong> Nhấn <code>Cmd+Option+I</code></li>";
        echo "</ul>";
        
        echo "<h4>🔧 Bước 3: Tìm Session Storage</h4>";
        echo "<ol>";
        echo "<li>Chuyển đến tab <strong>Application</strong> (Chrome) hoặc <strong>Storage</strong> (Firefox)</li>";
        echo "<li>Trong sidebar bên trái, tìm <strong>Session Storage</strong></li>";
        echo "<li>Click vào domain <code>localhost</code> hoặc <code>127.0.0.1</code></li>";
        echo "<li>Tìm key <code>last_activity</code></li>";
        echo "</ol>";
        
        echo "<h4>🔧 Bước 4: Sửa giá trị last_activity</h4>";
        echo "<p><strong>Cách 1: Xóa key</strong></p>";
        echo "<ul>";
        echo "<li>Click chuột phải vào key <code>last_activity</code></li>";
        echo "<li>Chọn <strong>Delete</strong> hoặc nhấn <code>Delete</code></li>";
        echo "</ul>";
        
        echo "<p><strong>Cách 2: Sửa giá trị</strong></p>";
        echo "<ul>";
        echo "<li>Double-click vào value của <code>last_activity</code></li>";
        echo "<li>Sửa thành timestamp quá khứ, ví dụ: <code>1703000000</code></li>";
        echo "<li>Nhấn <code>Enter</code> để lưu</li>";
        echo "</ul>";
        
        echo "<h4>🔧 Bước 5: Test kết quả</h4>";
        echo "<ol>";
        echo "<li>Refresh trang (F5) hoặc truy cập lại dashboard</li>";
        echo "<li>Xem URL có thay đổi thành <code>login.php</code> không</li>";
        echo "<li>Hoặc xem có thông báo yêu cầu đăng nhập lại không</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    /**
     * Hiển thị thông tin timeout
     */
    public function showTimeoutInfo() {
        echo "<h3>⏰ Thông tin Timeout</h3>";
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        
        $current_time = time();
        $timeout_time = $current_time - SESSION_TIMEOUT;
        $test_timeout = $current_time - SESSION_TIMEOUT - 60;
        
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #bbdefb;'>";
        echo "<th style='border: 1px solid #2196f3; padding: 8px; text-align: left;'>Mô tả</th>";
        echo "<th style='border: 1px solid #2196f3; padding: 8px; text-align: left;'>Timestamp</th>";
        echo "<th style='border: 1px solid #2196f3; padding: 8px; text-align: left;'>Thời gian</th>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'>Thời gian hiện tại</td>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'><code>$current_time</code></td>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'>" . date('Y-m-d H:i:s', $current_time) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'>Thời gian timeout</td>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'><code>$timeout_time</code></td>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'>" . date('Y-m-d H:i:s', $timeout_time) . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'>Test timeout (timeout + 1 phút)</td>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'><code>$test_timeout</code></td>";
        echo "<td style='border: 1px solid #2196f3; padding: 8px;'>" . date('Y-m-d H:i:s', $test_timeout) . "</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<p><strong>SESSION_TIMEOUT:</strong> " . SESSION_TIMEOUT . " giây (" . round(SESSION_TIMEOUT/60, 2) . " phút)</p>";
        echo "</div>";
    }
    
    /**
     * Tạo script JavaScript để test
     */
    public function showJavaScriptTest() {
        echo "<h3>🔧 Script JavaScript để test</h3>";
        echo "<div style='background: #f1f8e9; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>Copy và paste script này vào Console của Developer Tools:</p>";
        echo "<pre style='background: #263238; color: #fff; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        echo htmlspecialchars("
// Script test session timeout
console.log('=== Test Session Timeout ===');

// Kiểm tra session storage
const sessionStorage = window.sessionStorage;
console.log('Session Storage keys:', Object.keys(sessionStorage));

// Tìm last_activity
const lastActivity = sessionStorage.getItem('last_activity');
console.log('Current last_activity:', lastActivity);

if (lastActivity) {
    const currentTime = Math.floor(Date.now() / 1000);
    const timeoutTime = currentTime - " . SESSION_TIMEOUT . ";
    const testTimeout = timeoutTime - 60;
    
    console.log('Current timestamp:', currentTime);
    console.log('Timeout timestamp:', timeoutTime);
    console.log('Test timeout timestamp:', testTimeout);
    
    // Test 1: Xóa last_activity
    console.log('\\n=== Test 1: Xóa last_activity ===');
    sessionStorage.removeItem('last_activity');
    console.log('Đã xóa last_activity. Refresh trang để test.');
    
    // Test 2: Set timeout
    console.log('\\n=== Test 2: Set timeout ===');
    sessionStorage.setItem('last_activity', testTimeout);
    console.log('Đã set last_activity =', testTimeout);
    console.log('Refresh trang để test.');
} else {
    console.log('Không tìm thấy last_activity. Có thể chưa đăng nhập.');
}

// Hàm để restore last_activity
function restoreLastActivity() {
    const currentTime = Math.floor(Date.now() / 1000);
    sessionStorage.setItem('last_activity', currentTime);
    console.log('Đã restore last_activity =', currentTime);
}
        ");
        echo "</pre>";
        echo "<p><strong>Cách sử dụng:</strong></p>";
        echo "<ol>";
        echo "<li>Mở Developer Tools (F12)</li>";
        echo "<li>Chuyển đến tab <strong>Console</strong></li>";
        echo "<li>Copy và paste script trên</li>";
        echo "<li>Nhấn Enter để chạy</li>";
        echo "<li>Refresh trang để test</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    /**
     * Hiển thị checklist test
     */
    public function showTestChecklist() {
        echo "<h3>✅ Checklist Test KT15-4</h3>";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Test Cases:</h4>";
        
        $test_cases = [
            'Đăng nhập thành công và vào được dashboard',
            'Mở Developer Tools và tìm Session Storage',
            'Tìm thấy key last_activity trong session storage',
            'Xóa key last_activity và refresh trang',
            'Kết quả: Chuyển hướng đến login.php',
            'Sửa last_activity thành timestamp quá khứ',
            'Refresh trang và kiểm tra kết quả',
            'Kết quả: Chuyển hướng đến login.php',
            'Restore last_activity về thời gian hiện tại',
            'Refresh trang và kiểm tra truy cập bình thường'
        ];
        
        echo "<ul>";
        foreach ($test_cases as $index => $test_case) {
            echo "<li><input type='checkbox' id='test$index'> <label for='test$index'>$test_case</label></li>";
        }
        echo "</ul>";
        
        echo "<h4>Kết quả mong đợi:</h4>";
        echo "<ul>";
        echo "<li>✅ Khi last_activity hợp lệ → Cho phép truy cập</li>";
        echo "<li>✅ Khi last_activity hết hạn → Chuyển hướng đến login.php</li>";
        echo "<li>✅ Khi không có last_activity → Chuyển hướng đến login.php</li>";
        echo "</ul>";
        echo "</div>";
    }
}

// Hiển thị giao diện
if (isset($_GET['check_session'])) {
    $checker = new SessionStructureChecker($conn);
    $checker->showCurrentSession();
    $checker->showTimeoutInfo();
    $checker->showManualTestGuide();
    $checker->showJavaScriptTest();
    $checker->showTestChecklist();
} else {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kiểm tra Session Structure - KT15-4</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .container { max-width: 1200px; margin: 0 auto; }
            .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
            .btn-primary { background: #007bff; color: white; }
            .btn-success { background: #28a745; color: white; }
            .test-info { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
            code { background: #f1f3f4; padding: 2px 4px; border-radius: 3px; }
            pre { background: #263238; color: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Kiểm tra Session Structure - KT15-4</h1>
            
            <div class="test-info">
                <h3>📋 Mục đích:</h3>
                <p>Kiểm tra cấu trúc session storage và hướng dẫn test thủ công cho KT15-4.</p>
                
                <h3>🎯 Chức năng:</h3>
                <ul>
                    <li>Hiển thị thông tin session hiện tại</li>
                    <li>Hướng dẫn test thủ công chi tiết</li>
                    <li>Cung cấp script JavaScript để test</li>
                    <li>Checklist test cases</li>
                </ul>
            </div>
            
            <div style="margin: 20px 0;">
                <h3>🚀 Bắt đầu kiểm tra:</h3>
                <a href="?check_session=1" class="btn btn-primary">🔍 Kiểm tra session structure</a>
                <a href="test_session_timeout.php" class="btn btn-success">🧪 Test timeout tự động</a>
                <a href="test_permission_manual.php" class="btn btn-success">📊 Quay lại test tổng thể</a>
            </div>
            
            <div style="margin-top: 20px;">
                <h3>📖 Lưu ý quan trọng:</h3>
                <ul>
                    <li>Để test hiệu quả, bạn cần đăng nhập trước</li>
                    <li>Script này sẽ hiển thị thông tin session hiện tại</li>
                    <li>Có thể sử dụng để debug vấn đề session timeout</li>
                    <li>Hướng dẫn chi tiết cách test thủ công</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
