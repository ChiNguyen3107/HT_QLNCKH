<?php

/**
 * CSRF Protection Class
 * 
 * Cung cấp các chức năng để tạo, validate và quản lý CSRF tokens
 * Bảo vệ khỏi các cuộc tấn công Cross-Site Request Forgery
 */
class CSRF
{
    private static $tokenName = '_csrf_token';
    private static $sessionKey = 'csrf_tokens';
    private static $maxTokens = 10; // Giới hạn số lượng tokens trong session
    
    /**
     * Tạo CSRF token mới
     * 
     * @param string $formName Tên form (optional)
     * @return string CSRF token
     */
    public static function generateToken($formName = 'default')
    {
        // Khởi tạo session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Tạo token ngẫu nhiên
        $token = bin2hex(random_bytes(32));
        
        // Lưu token vào session với timestamp
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        // Thêm token mới
        $_SESSION[self::$sessionKey][$formName] = [
            'token' => $token,
            'timestamp' => time(),
            'used' => false
        ];
        
        // Giới hạn số lượng tokens
        self::cleanupOldTokens();
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token cần validate
     * @param string $formName Tên form (optional)
     * @return bool True nếu token hợp lệ
     */
    public static function validateToken($token, $formName = 'default')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token) || !isset($_SESSION[self::$sessionKey][$formName])) {
            return false;
        }
        
        $storedToken = $_SESSION[self::$sessionKey][$formName];
        
        // Kiểm tra token có khớp không
        if (!hash_equals($storedToken['token'], $token)) {
            return false;
        }
        
        // Kiểm tra token đã được sử dụng chưa
        if ($storedToken['used']) {
            return false;
        }
        
        // Kiểm tra token có hết hạn không (24 giờ)
        if (time() - $storedToken['timestamp'] > 86400) {
            unset($_SESSION[self::$sessionKey][$formName]);
            return false;
        }
        
        // Đánh dấu token đã được sử dụng
        $_SESSION[self::$sessionKey][$formName]['used'] = true;
        
        return true;
    }
    
    /**
     * Lấy CSRF token hiện tại (không tạo mới)
     * 
     * @param string $formName Tên form (optional)
     * @return string|null CSRF token hoặc null nếu không có
     */
    public static function getToken($formName = 'default')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION[self::$sessionKey][$formName])) {
            return $_SESSION[self::$sessionKey][$formName]['token'];
        }
        
        return null;
    }
    
    /**
     * Tạo CSRF token field cho form
     * 
     * @param string $formName Tên form (optional)
     * @return string HTML input field
     */
    public static function getTokenField($formName = 'default')
    {
        $token = self::generateToken($formName);
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Lấy tên field CSRF token
     * 
     * @return string Tên field
     */
    public static function getTokenName()
    {
        return self::$tokenName;
    }
    
    /**
     * Kiểm tra request có chứa CSRF token hợp lệ không
     * 
     * @param string $formName Tên form (optional)
     * @return bool True nếu token hợp lệ
     */
    public static function checkRequest($formName = 'default')
    {
        $token = $_POST[self::$tokenName] ?? $_GET[self::$tokenName] ?? null;
        return self::validateToken($token, $formName);
    }
    
    /**
     * Xóa token đã sử dụng
     * 
     * @param string $formName Tên form (optional)
     */
    public static function removeToken($formName = 'default')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION[self::$sessionKey][$formName])) {
            unset($_SESSION[self::$sessionKey][$formName]);
        }
    }
    
    /**
     * Xóa tất cả CSRF tokens
     */
    public static function clearAllTokens()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$sessionKey]);
    }
    
    /**
     * Dọn dẹp các token cũ
     */
    private static function cleanupOldTokens()
    {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        
        $currentTime = time();
        $tokens = $_SESSION[self::$sessionKey];
        
        // Xóa các token đã hết hạn hoặc đã sử dụng
        foreach ($tokens as $formName => $tokenData) {
            if ($tokenData['used'] || ($currentTime - $tokenData['timestamp']) > 86400) {
                unset($_SESSION[self::$sessionKey][$formName]);
            }
        }
        
        // Giới hạn số lượng tokens
        if (count($_SESSION[self::$sessionKey]) > self::$maxTokens) {
            $tokens = $_SESSION[self::$sessionKey];
            uasort($tokens, function($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            });
            
            $tokensToRemove = array_slice($tokens, 0, count($tokens) - self::$maxTokens, true);
            foreach ($tokensToRemove as $formName => $tokenData) {
                unset($_SESSION[self::$sessionKey][$formName]);
            }
        }
    }
    
    /**
     * Tạo CSRF token cho AJAX requests
     * 
     * @param string $formName Tên form (optional)
     * @return array Array chứa token name và value
     */
    public static function getAjaxToken($formName = 'ajax')
    {
        $token = self::generateToken($formName);
        return [
            'name' => self::$tokenName,
            'value' => $token
        ];
    }
    
    /**
     * Validate CSRF token từ header
     * 
     * @param string $formName Tên form (optional)
     * @return bool True nếu token hợp lệ
     */
    public static function validateHeaderToken($formName = 'ajax')
    {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
        
        if (empty($token)) {
            return false;
        }
        
        return self::validateToken($token, $formName);
    }
}
