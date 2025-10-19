<?php
/**
 * Enhanced Authentication Middleware with Session Security
 */

require_once 'core/SessionManager.php';

class AuthMiddleware
{
    private $sessionManager;
    
    public function __construct()
    {
        $this->sessionManager = SessionManager::getInstance();
    }
    
    public function handle()
    {
        // Validate session security
        if (!$this->sessionManager->validateSession()) {
            // Lưu URL hiện tại để redirect sau khi đăng nhập
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            }
            
            redirect('/login?timeout=1');
            exit;
        }
        
        // Kiểm tra session warning
        if ($this->sessionManager->shouldShowWarning()) {
            $this->handleSessionWarning();
        }
        
        // Extend session on activity if configured
        $this->sessionManager->extendSession();
    }
    
    /**
     * Handle session warning
     */
    private function handleSessionWarning()
    {
        $sessionInfo = $this->sessionManager->getSessionInfo();
        $timeRemaining = $sessionInfo['time_remaining'] ?? 0;
        
        // Add warning to session for frontend display
        $_SESSION['session_warning'] = [
            'time_remaining' => $timeRemaining,
            'message' => 'Phiên làm việc của bạn sắp hết hạn. Vui lòng lưu công việc và đăng nhập lại.',
            'show_warning' => true
        ];
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        return $this->sessionManager->validateSession();
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'name' => $_SESSION['user_name'] ?? null
        ];
    }
    
    /**
     * Check user role
     */
    public function hasRole($role)
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Get session info
     */
    public function getSessionInfo()
    {
        return $this->sessionManager->getSessionInfo();
    }
}

