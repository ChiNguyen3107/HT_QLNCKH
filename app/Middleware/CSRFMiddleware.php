<?php

require_once __DIR__ . '/../../core/CSRF.php';

/**
 * CSRF Middleware
 * 
 * Middleware để xử lý CSRF protection cho các requests
 * Tự động validate CSRF token cho POST/PUT/DELETE requests
 */
class CSRFMiddleware
{
    private $excludedRoutes = [];
    private $excludedMethods = ['GET', 'HEAD', 'OPTIONS'];
    private $excludedHeaders = [];
    
    /**
     * Constructor
     * 
     * @param array $excludedRoutes Các routes không cần CSRF protection
     * @param array $excludedMethods Các HTTP methods không cần CSRF protection
     * @param array $excludedHeaders Các headers không cần CSRF protection
     */
    public function __construct($excludedRoutes = [], $excludedMethods = [], $excludedHeaders = [])
    {
        $this->excludedRoutes = array_merge([
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/auth/refresh',
            '/login',
            '/logout',
            '/forgot-password',
            '/reset-password'
        ], $excludedRoutes);
        
        $this->excludedMethods = array_merge($this->excludedMethods, $excludedMethods);
        $this->excludedHeaders = array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $excludedHeaders);
    }
    
    /**
     * Xử lý middleware
     * 
     * @param array $request Request data
     * @param callable $next Next middleware
     * @return mixed Response
     */
    public function handle($request, $next)
    {
        // Kiểm tra xem request có cần CSRF protection không
        if (!$this->needsCSRFProtection($request)) {
            return $next($request);
        }
        
        // Validate CSRF token
        if (!$this->validateCSRFToken($request)) {
            return $this->handleCSRFFailure($request);
        }
        
        return $next($request);
    }
    
    /**
     * Kiểm tra xem request có cần CSRF protection không
     * 
     * @param array $request Request data
     * @return bool True nếu cần CSRF protection
     */
    private function needsCSRFProtection($request)
    {
        $method = strtoupper($request['method'] ?? $_SERVER['REQUEST_METHOD']);
        $uri = $request['uri'] ?? $_SERVER['REQUEST_URI'];
        
        // Kiểm tra HTTP method
        if (in_array($method, $this->excludedMethods)) {
            return false;
        }
        
        // Kiểm tra excluded routes
        foreach ($this->excludedRoutes as $excludedRoute) {
            if (strpos($uri, $excludedRoute) !== false) {
                return false;
            }
        }
        
        // Kiểm tra headers
        if ($this->isExcludedByHeaders()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Kiểm tra xem request có bị loại trừ bởi headers không
     * 
     * @return bool True nếu bị loại trừ
     */
    private function isExcludedByHeaders()
    {
        $headers = getallheaders();
        
        foreach ($this->excludedHeaders as $excludedHeader) {
            list($headerName, $headerValue) = explode(':', $excludedHeader, 2);
            $headerName = trim($headerName);
            $headerValue = trim($headerValue);
            
            if (isset($headers[$headerName]) && 
                strpos($headers[$headerName], $headerValue) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param array $request Request data
     * @return bool True nếu token hợp lệ
     */
    private function validateCSRFToken($request)
    {
        $method = strtoupper($request['method'] ?? $_SERVER['REQUEST_METHOD']);
        $formName = $this->getFormName($request);
        
        // Kiểm tra token từ POST data
        if ($method === 'POST' && isset($_POST[CSRF::getTokenName()])) {
            return CSRF::validateToken($_POST[CSRF::getTokenName()], $formName);
        }
        
        // Kiểm tra token từ header (cho AJAX requests)
        if (CSRF::validateHeaderToken($formName)) {
            return true;
        }
        
        // Kiểm tra token từ GET data (cho một số trường hợp đặc biệt)
        if (isset($_GET[CSRF::getTokenName()])) {
            return CSRF::validateToken($_GET[CSRF::getTokenName()], $formName);
        }
        
        return false;
    }
    
    /**
     * Lấy tên form từ request
     * 
     * @param array $request Request data
     * @return string Tên form
     */
    private function getFormName($request)
    {
        $uri = $request['uri'] ?? $_SERVER['REQUEST_URI'];
        $method = strtoupper($request['method'] ?? $_SERVER['REQUEST_METHOD']);
        
        // Tạo form name từ URI và method
        $formName = str_replace('/', '_', trim($uri, '/'));
        if (empty($formName)) {
            $formName = 'home';
        }
        
        return $formName . '_' . strtolower($method);
    }
    
    /**
     * Xử lý khi CSRF validation thất bại
     * 
     * @param array $request Request data
     * @return mixed Response
     */
    private function handleCSRFFailure($request)
    {
        $method = strtoupper($request['method'] ?? $_SERVER['REQUEST_METHOD']);
        $isAjax = $this->isAjaxRequest();
        
        // Log CSRF violation
        $this->logCSRFViolation($request);
        
        if ($isAjax) {
            $this->handleAjaxCSRFFailure();
            return;
        }
        
        if ($method === 'POST') {
            return $this->handlePostCSRFFailure();
        }
        
        return $this->handleGenericCSRFFailure();
    }
    
    /**
     * Kiểm tra xem có phải AJAX request không
     * 
     * @return bool True nếu là AJAX request
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Xử lý CSRF failure cho AJAX requests
     * 
     * @return void
     */
    private function handleAjaxCSRFFailure()
    {
        http_response_code(403);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => 'CSRF token validation failed',
            'message' => 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.',
            'code' => 'CSRF_TOKEN_INVALID'
        ]);
        exit;
    }
    
    /**
     * Xử lý CSRF failure cho POST requests
     * 
     * @return void
     */
    private function handlePostCSRFFailure()
    {
        $_SESSION['csrf_error'] = 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.';
        
        // Redirect về trang trước đó hoặc trang chủ
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
    }
    
    /**
     * Xử lý CSRF failure chung
     * 
     * @return void
     */
    private function handleGenericCSRFFailure()
    {
        http_response_code(403);
        
        // Hiển thị trang lỗi 403
        include __DIR__ . '/../../403.php';
        exit;
    }
    
    /**
     * Log CSRF violation
     * 
     * @param array $request Request data
     */
    private function logCSRFViolation($request)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'method' => $request['method'] ?? $_SERVER['REQUEST_METHOD'],
            'uri' => $request['uri'] ?? $_SERVER['REQUEST_URI'],
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'post_data' => $_POST,
            'get_data' => $_GET
        ];
        
        $logFile = __DIR__ . '/../../logs/csrf_violations.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Thêm route vào danh sách loại trừ
     * 
     * @param string $route Route cần loại trừ
     */
    public function addExcludedRoute($route)
    {
        if (!in_array($route, $this->excludedRoutes)) {
            $this->excludedRoutes[] = $route;
        }
    }
    
    /**
     * Thêm method vào danh sách loại trừ
     * 
     * @param string $method HTTP method cần loại trừ
     */
    public function addExcludedMethod($method)
    {
        if (!in_array(strtoupper($method), $this->excludedMethods)) {
            $this->excludedMethods[] = strtoupper($method);
        }
    }
    
    /**
     * Lấy danh sách routes bị loại trừ
     * 
     * @return array Danh sách routes
     */
    public function getExcludedRoutes()
    {
        return $this->excludedRoutes;
    }
    
    /**
     * Lấy danh sách methods bị loại trừ
     * 
     * @return array Danh sách methods
     */
    public function getExcludedMethods()
    {
        return $this->excludedMethods;
    }
}
