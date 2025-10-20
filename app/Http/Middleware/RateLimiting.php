<?php

namespace App\Http\Middleware;

/**
 * Rate Limiting Middleware cho API
 */
class RateLimiting
{
    private $maxRequests;
    private $timeWindow;
    private $storageFile;

    public function __construct($maxRequests = 100, $timeWindow = 3600)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
        $this->storageFile = __DIR__ . '/../../../storage/cache/rate_limit.json';
    }

    /**
     * Handle rate limiting
     */
    public function handle($request, $next)
    {
        $clientId = $this->getClientId($request);
        $currentTime = time();
        
        // Load rate limit data
        $rateLimitData = $this->loadRateLimitData();
        
        // Clean expired entries
        $this->cleanExpiredEntries($rateLimitData, $currentTime);
        
        // Check rate limit for client
        if ($this->isRateLimited($rateLimitData, $clientId, $currentTime)) {
            return $this->rateLimitExceeded();
        }
        
        // Record request
        $this->recordRequest($rateLimitData, $clientId, $currentTime);
        
        // Save rate limit data
        $this->saveRateLimitData($rateLimitData);
        
        return $next($request);
    }

    /**
     * Get client identifier
     */
    private function getClientId($request = null)
    {
        // Try to get user ID from JWT token first
        if ($request && isset($request['user']['user_id'])) {
            return 'user_' . $request['user']['user_id'];
        }
        
        // Fallback to IP address
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return 'ip_' . md5($ip . $userAgent);
    }

    /**
     * Load rate limit data from storage
     */
    private function loadRateLimitData()
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }
        
        $data = file_get_contents($this->storageFile);
        return json_decode($data, true) ?: [];
    }

    /**
     * Save rate limit data to storage
     */
    private function saveRateLimitData($data)
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->storageFile, json_encode($data));
    }

    /**
     * Clean expired entries
     */
    private function cleanExpiredEntries(&$data, $currentTime)
    {
        foreach ($data as $clientId => $requests) {
            $data[$clientId] = array_filter($requests, function($timestamp) use ($currentTime) {
                return ($currentTime - $timestamp) < $this->timeWindow;
            });
            
            // Remove empty entries
            if (empty($data[$clientId])) {
                unset($data[$clientId]);
            }
        }
    }

    /**
     * Check if client is rate limited
     */
    private function isRateLimited($data, $clientId, $currentTime)
    {
        if (!isset($data[$clientId])) {
            return false;
        }
        
        $requests = $data[$clientId];
        $recentRequests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < $this->timeWindow;
        });
        
        return count($recentRequests) >= $this->maxRequests;
    }

    /**
     * Record request
     */
    private function recordRequest(&$data, $clientId, $currentTime)
    {
        if (!isset($data[$clientId])) {
            $data[$clientId] = [];
        }
        
        $data[$clientId][] = $currentTime;
    }

    /**
     * Return rate limit exceeded response
     */
    private function rateLimitExceeded()
    {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . $this->timeWindow);
        
        echo json_encode([
            'success' => false,
            'status' => 429,
            'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.',
            'retry_after' => $this->timeWindow,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
        exit;
    }

    /**
     * Get rate limit info for client
     */
    public function getRateLimitInfo($clientId)
    {
        $data = $this->loadRateLimitData();
        $currentTime = time();
        
        if (!isset($data[$clientId])) {
            return [
                'limit' => $this->maxRequests,
                'remaining' => $this->maxRequests,
                'reset_time' => $currentTime + $this->timeWindow
            ];
        }
        
        $requests = $data[$clientId];
        $recentRequests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < $this->timeWindow;
        });
        
        $remaining = max(0, $this->maxRequests - count($recentRequests));
        $resetTime = empty($recentRequests) ? $currentTime + $this->timeWindow : min($recentRequests) + $this->timeWindow;
        
        return [
            'limit' => $this->maxRequests,
            'remaining' => $remaining,
            'reset_time' => $resetTime
        ];
    }

    /**
     * Set custom rate limit for specific client
     */
    public function setCustomRateLimit($clientId, $maxRequests, $timeWindow = null)
    {
        $timeWindow = $timeWindow ?: $this->timeWindow;
        
        // Store custom limits in separate file
        $customLimitsFile = __DIR__ . '/../../../storage/cache/custom_rate_limits.json';
        $customLimits = [];
        
        if (file_exists($customLimitsFile)) {
            $customLimits = json_decode(file_get_contents($customLimitsFile), true) ?: [];
        }
        
        $customLimits[$clientId] = [
            'max_requests' => $maxRequests,
            'time_window' => $timeWindow,
            'set_at' => time()
        ];
        
        file_put_contents($customLimitsFile, json_encode($customLimits));
    }
}
