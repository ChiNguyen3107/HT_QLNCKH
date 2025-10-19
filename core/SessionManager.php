<?php
/**
 * Session Security Manager
 * 
 * Cung cấp các tính năng bảo mật session toàn diện:
 * - Session regeneration
 * - Session timeout với warning
 * - Session fingerprinting
 * - Concurrent session limits
 * - Session hijacking protection
 * - Session activity logging
 */

class SessionManager
{
    private static $instance = null;
    private $config;
    private $logger;
    private $db;
    
    // Session configuration
    private $sessionLifetime;
    private $warningTime;
    private $maxConcurrentSessions;
    private $fingerprintFields;
    
    public function __construct()
    {
        $this->config = require_once 'config/session.php';
        $this->logger = new Logger();
        $this->db = new Database();
        
        // Load session configuration
        $this->sessionLifetime = $this->config['lifetime'] ?? 3600;
        $this->warningTime = $this->config['warning_time'] ?? 300; // 5 phút trước khi hết hạn
        $this->maxConcurrentSessions = $this->config['max_concurrent_sessions'] ?? 3;
        $this->fingerprintFields = $this->config['fingerprint_fields'] ?? [
            'user_agent', 'ip_address', 'accept_language'
        ];
        
        $this->initializeSession();
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize secure session configuration
     */
    private function initializeSession()
    {
        // Chỉ start session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            // Cấu hình session security
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', $this->config['secure'] ? 1 : 0);
            ini_set('session.cookie_samesite', $this->config['same_site'] ?? 'Lax');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_lifetime', $this->sessionLifetime);
            ini_set('session.gc_maxlifetime', $this->sessionLifetime);
            
            // Cấu hình session name
            session_name($this->config['name'] ?? 'PHPSESSID');
            
            // Cấu hình session path
            session_set_cookie_params([
                'lifetime' => $this->sessionLifetime,
                'path' => $this->config['path'] ?? '/',
                'domain' => $this->config['domain'] ?? null,
                'secure' => $this->config['secure'] ?? false,
                'httponly' => $this->config['http_only'] ?? true,
                'samesite' => $this->config['same_site'] ?? 'Lax'
            ]);
            
            session_start();
        }
    }
    
    /**
     * Tạo session mới với bảo mật cao
     */
    public function createSecureSession($userId, $userData = [])
    {
        try {
            // Regenerate session ID để tránh session fixation
            $this->regenerateSessionId();
            
            // Tạo session fingerprint
            $fingerprint = $this->generateFingerprint();
            
            // Kiểm tra concurrent sessions
            $this->enforceConcurrentSessionLimit($userId);
            
            // Set session data
            $_SESSION['user_id'] = $userId;
            $_SESSION['session_id'] = session_id();
            $_SESSION['created_at'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['fingerprint'] = $fingerprint;
            $_SESSION['ip_address'] = $this->getClientIp();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Merge user data
            foreach ($userData as $key => $value) {
                $_SESSION[$key] = $value;
            }
            
            // Log session creation
            $this->logSessionActivity('session_created', $userId, [
                'session_id' => session_id(),
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Session creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerateSessionId($deleteOldSession = true)
    {
        $oldSessionId = session_id();
        
        if (session_regenerate_id($deleteOldSession)) {
            // Log session regeneration
            $this->logSessionActivity('session_regenerated', $_SESSION['user_id'] ?? null, [
                'old_session_id' => $oldSessionId,
                'new_session_id' => session_id()
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate session security
     */
    public function validateSession()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Kiểm tra session timeout
        if (!$this->checkSessionTimeout()) {
            $this->destroySession('timeout');
            return false;
        }
        
        // Kiểm tra session fingerprint
        if (!$this->validateFingerprint()) {
            $this->destroySession('fingerprint_mismatch');
            return false;
        }
        
        // Kiểm tra IP address (optional - có thể gây vấn đề với mobile users)
        if ($this->config['check_ip_address'] ?? false) {
            if (!$this->validateIpAddress()) {
                $this->destroySession('ip_mismatch');
                return false;
            }
        }
        
        // Cập nhật last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Kiểm tra session timeout
     */
    private function checkSessionTimeout()
    {
        if (!isset($_SESSION['last_activity'])) {
            return false;
        }
        
        $timeSinceLastActivity = time() - $_SESSION['last_activity'];
        
        // Kiểm tra warning time
        if ($timeSinceLastActivity > ($this->sessionLifetime - $this->warningTime)) {
            $this->setSessionWarning();
        }
        
        // Kiểm tra session expired
        return $timeSinceLastActivity <= $this->sessionLifetime;
    }
    
    /**
     * Set session warning
     */
    private function setSessionWarning()
    {
        if (!isset($_SESSION['warning_shown'])) {
            $_SESSION['warning_shown'] = true;
            $_SESSION['warning_time'] = time();
            
            // Log warning
            $this->logSessionActivity('session_warning', $_SESSION['user_id'] ?? null, [
                'remaining_time' => $this->sessionLifetime - (time() - $_SESSION['last_activity'])
            ]);
        }
    }
    
    /**
     * Kiểm tra session fingerprint
     */
    private function validateFingerprint()
    {
        if (!isset($_SESSION['fingerprint'])) {
            return false;
        }
        
        $currentFingerprint = $this->generateFingerprint();
        return hash_equals($_SESSION['fingerprint'], $currentFingerprint);
    }
    
    /**
     * Tạo session fingerprint
     */
    private function generateFingerprint()
    {
        $data = '';
        
        foreach ($this->fingerprintFields as $field) {
            switch ($field) {
                case 'user_agent':
                    $data .= $_SERVER['HTTP_USER_AGENT'] ?? '';
                    break;
                case 'ip_address':
                    $data .= $this->getClientIp();
                    break;
                case 'accept_language':
                    $data .= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
                    break;
                case 'accept_encoding':
                    $data .= $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
                    break;
            }
        }
        
        return hash('sha256', $data);
    }
    
    /**
     * Kiểm tra IP address
     */
    private function validateIpAddress()
    {
        if (!isset($_SESSION['ip_address'])) {
            return false;
        }
        
        $currentIp = $this->getClientIp();
        return hash_equals($_SESSION['ip_address'], $currentIp);
    }
    
    /**
     * Lấy client IP address
     */
    private function getClientIp()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                  'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Enforce concurrent session limit
     */
    private function enforceConcurrentSessionLimit($userId)
    {
        if ($this->maxConcurrentSessions <= 0) {
            return; // Không giới hạn
        }
        
        // Lấy danh sách sessions hiện tại của user
        $activeSessions = $this->getActiveSessions($userId);
        
        // Nếu vượt quá giới hạn, xóa sessions cũ nhất
        if (count($activeSessions) >= $this->maxConcurrentSessions) {
            $sessionsToRemove = array_slice($activeSessions, 0, count($activeSessions) - $this->maxConcurrentSessions + 1);
            
            foreach ($sessionsToRemove as $session) {
                $this->destroySessionById($session['session_id'], 'concurrent_limit');
            }
        }
    }
    
    /**
     * Lấy danh sách sessions đang hoạt động
     */
    private function getActiveSessions($userId)
    {
        $result = $this->db->fetchAll(
            "SELECT session_id, last_activity FROM session_logs 
             WHERE user_id = ? AND status = 'active' 
             ORDER BY last_activity DESC",
            [$userId]
        );
        
        return $result ?: [];
    }
    
    /**
     * Destroy session
     */
    public function destroySession($reason = 'logout')
    {
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        
        // Log session destruction
        $this->logSessionActivity('session_destroyed', $userId, [
            'session_id' => $sessionId,
            'reason' => $reason
        ]);
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Destroy session by ID
     */
    private function destroySessionById($sessionId, $reason = 'admin_action')
    {
        // Update session status in database
        $this->db->execute(
            "UPDATE session_logs SET status = 'destroyed', destroyed_at = NOW(), destroy_reason = ? 
             WHERE session_id = ?",
            [$reason, $sessionId]
        );
        
        // Log destruction
        $this->logSessionActivity('session_destroyed_by_id', null, [
            'session_id' => $sessionId,
            'reason' => $reason
        ]);
    }
    
    /**
     * Log session activity
     */
    private function logSessionActivity($action, $userId = null, $data = [])
    {
        try {
            $this->db->execute(
                "INSERT INTO session_logs (session_id, user_id, action, ip_address, user_agent, data, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    session_id(),
                    $userId,
                    $action,
                    $this->getClientIp(),
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    json_encode($data)
                ]
            );
        } catch (Exception $e) {
            $this->logger->error('Session logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Lấy thông tin session hiện tại
     */
    public function getSessionInfo()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return [
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'],
            'created_at' => $_SESSION['created_at'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'time_remaining' => $this->getTimeRemaining(),
            'is_warning' => isset($_SESSION['warning_shown']),
            'ip_address' => $_SESSION['ip_address'] ?? null
        ];
    }
    
    /**
     * Lấy thời gian còn lại của session
     */
    public function getTimeRemaining()
    {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = $this->sessionLifetime - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Kiểm tra có cần hiển thị warning không
     */
    public function shouldShowWarning()
    {
        return isset($_SESSION['warning_shown']) && 
               !isset($_SESSION['warning_dismissed']);
    }
    
    /**
     * Dismiss warning
     */
    public function dismissWarning()
    {
        $_SESSION['warning_dismissed'] = true;
    }
    
    /**
     * Extend session
     */
    public function extendSession()
    {
        if (isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            unset($_SESSION['warning_shown'], $_SESSION['warning_dismissed']);
            
            $this->logSessionActivity('session_extended', $_SESSION['user_id'] ?? null);
            return true;
        }
        
        return false;
    }
    
    /**
     * Lấy lịch sử session của user
     */
    public function getSessionHistory($userId, $limit = 20)
    {
        return $this->db->fetchAll(
            "SELECT * FROM session_logs 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }
    
    /**
     * Cleanup expired sessions
     */
    public function cleanupExpiredSessions()
    {
        $expiredTime = time() - $this->sessionLifetime;
        
        $this->db->execute(
            "UPDATE session_logs SET status = 'expired', destroyed_at = NOW() 
             WHERE status = 'active' AND last_activity < ?",
            [date('Y-m-d H:i:s', $expiredTime)]
        );
        
        $this->logger->info('Expired sessions cleaned up');
    }
    
    /**
     * Lấy thống kê session
     */
    public function getSessionStats()
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_sessions,
                COUNT(CASE WHEN status = 'destroyed' THEN 1 END) as destroyed_sessions,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_sessions
             FROM session_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        return $stats ?: [
            'total_sessions' => 0,
            'active_sessions' => 0,
            'destroyed_sessions' => 0,
            'expired_sessions' => 0
        ];
    }
    
    /**
     * Force logout all sessions for user
     */
    public function forceLogoutUser($userId)
    {
        $sessions = $this->getActiveSessions($userId);
        
        foreach ($sessions as $session) {
            $this->destroySessionById($session['session_id'], 'admin_force_logout');
        }
        
        $this->logger->info("All sessions force logged out for user: {$userId}");
        
        return count($sessions);
    }
}
