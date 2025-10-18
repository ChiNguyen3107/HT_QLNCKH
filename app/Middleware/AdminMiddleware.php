<?php
/**
 * Admin Middleware
 */

class AdminMiddleware
{
    public function handle()
    {
        // Kiểm tra đăng nhập trước
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
            exit;
        }
        
        // Kiểm tra quyền admin
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            view('errors/403');
            exit;
        }
    }
}

