<?php
/**
 * Authentication Middleware
 */

class AuthMiddleware
{
    public function handle()
    {
        if (!isset($_SESSION['user_id'])) {
            // Lưu URL hiện tại để redirect sau khi đăng nhập
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            redirect('/login');
            exit;
        }
        
        // Kiểm tra session timeout
        if (isset($_SESSION['last_activity'])) {
            $timeout = config('app.session.lifetime', 3600);
            if (time() - $_SESSION['last_activity'] > $timeout) {
                session_destroy();
                redirect('/login?timeout=1');
                exit;
            }
        }
        
        // Cập nhật thời gian hoạt động cuối
        $_SESSION['last_activity'] = time();
    }
}

