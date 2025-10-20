<?php

namespace App\Http\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * JWT Authentication Middleware cho API
 */
class ApiAuth
{
    private $secretKey;
    private $algorithm;

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        $this->algorithm = 'HS256';
    }

    /**
     * Handle API authentication
     */
    public function handle($request, $next)
    {
        try {
            // Kiểm tra Authorization header
            $authHeader = $this->getAuthorizationHeader();
            
            if (!$authHeader) {
                return $this->unauthorized('Token không được cung cấp');
            }

            // Extract token
            $token = $this->extractToken($authHeader);
            
            if (!$token) {
                return $this->unauthorized('Token không hợp lệ');
            }

            // Verify token
            $payload = $this->verifyToken($token);
            
            if (!$payload) {
                return $this->unauthorized('Token không hợp lệ hoặc đã hết hạn');
            }

            // Set user info to request
            $request['user'] = $payload;
            
            return $next($request);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return $this->unauthorized('Token đã hết hạn');
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return $this->unauthorized('Token không hợp lệ');
        } catch (\Exception $e) {
            return $this->unauthorized('Lỗi xác thực: ' . $e->getMessage());
        }
    }

    /**
     * Get Authorization header
     */
    private function getAuthorizationHeader()
    {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
        
        if (isset($headers['authorization'])) {
            return $headers['authorization'];
        }
        
        return null;
    }

    /**
     * Extract token from Authorization header
     */
    private function extractToken($authHeader)
    {
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Verify JWT token
     */
    private function verifyToken($token)
    {
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->secretKey, $this->algorithm));
            
            // Kiểm tra token có hợp lệ không
            if (!$this->isValidPayload($decoded)) {
                return null;
            }
            
            return (array) $decoded;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate token payload
     */
    private function isValidPayload($payload)
    {
        $payload = (array) $payload;
        
        // Kiểm tra các field bắt buộc
        if (!isset($payload['user_id']) || !isset($payload['role']) || !isset($payload['exp'])) {
            return false;
        }
        
        // Kiểm tra token chưa hết hạn
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return true;
    }

    /**
     * Generate JWT token
     */
    public function generateToken($user)
    {
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'name' => $user['name'] ?? '',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'iss' => 'nckh-api',
            'aud' => 'nckh-client'
        ];

        return \Firebase\JWT\JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken($token)
    {
        try {
            $payload = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $payload;
            
            // Tạo token mới với thời gian gia hạn
            $payload['iat'] = time();
            $payload['exp'] = time() + (24 * 60 * 60);
            
            return \Firebase\JWT\JWT::encode($payload, $this->secretKey, $this->algorithm);
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($user, $role)
    {
        return isset($user['role']) && $user['role'] === $role;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($user, $permission)
    {
        $role = $user['role'] ?? '';
        
        $permissions = [
            'admin' => ['*'],
            'research_manager' => ['projects.*', 'evaluations.*', 'reports.*'],
            'teacher' => ['projects.view', 'projects.update', 'students.view'],
            'student' => ['projects.view', 'projects.create', 'profile.update']
        ];
        
        if (!isset($permissions[$role])) {
            return false;
        }
        
        $userPermissions = $permissions[$role];
        
        // Kiểm tra wildcard permission
        if (in_array('*', $userPermissions)) {
            return true;
        }
        
        // Kiểm tra permission cụ thể
        return in_array($permission, $userPermissions);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorized($message = 'Unauthorized')
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => false,
            'status' => 401,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        
        exit;
    }
}
