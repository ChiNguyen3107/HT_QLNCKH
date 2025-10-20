<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BaseResource;
use App\Http\Middleware\ApiAuth;
use App\Http\Middleware\RateLimiting;

/**
 * Base API Controller
 */
abstract class BaseApiController
{
    protected $auth;
    protected $rateLimiting;

    public function __construct()
    {
        $this->auth = new ApiAuth();
        $this->rateLimiting = new RateLimiting();
    }

    /**
     * Apply middleware
     */
    protected function applyMiddleware($request, $middleware = [])
    {
        // Apply rate limiting
        $this->rateLimiting->handle($request, function($req) use ($middleware) {
            // Apply authentication if required
            if (in_array('auth', $middleware)) {
                return $this->auth->handle($req, function($req) {
                    return $req;
                });
            }
            
            return $req;
        });
    }

    /**
     * Get current user from request
     */
    protected function getCurrentUser($request)
    {
        return $request['user'] ?? null;
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission($user, $permission)
    {
        return $this->auth->hasPermission($user, $permission);
    }

    /**
     * Check if user has role
     */
    protected function hasRole($user, $role)
    {
        return $this->auth->hasRole($user, $role);
    }

    /**
     * Return success response
     */
    protected function success($data = null, $message = 'Thành công', $status = 200)
    {
        return BaseResource::success($data, $message, $status);
    }

    /**
     * Return error response
     */
    protected function error($message = 'Có lỗi xảy ra', $status = 400, $data = null)
    {
        return BaseResource::error($message, $status, $data);
    }

    /**
     * Return not found response
     */
    protected function notFound($message = 'Không tìm thấy dữ liệu')
    {
        return $this->error($message, 404);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized($message = 'Không có quyền truy cập')
    {
        return $this->error($message, 401);
    }

    /**
     * Return forbidden response
     */
    protected function forbidden($message = 'Bị cấm truy cập')
    {
        return $this->error($message, 403);
    }

    /**
     * Return validation error response
     */
    protected function validationError($errors, $message = 'Dữ liệu không hợp lệ')
    {
        return $this->error($message, 422, ['errors' => $errors]);
    }

    /**
     * Return server error response
     */
    protected function serverError($message = 'Lỗi máy chủ')
    {
        return $this->error($message, 500);
    }

    /**
     * Get pagination parameters
     */
    protected function getPaginationParams($request)
    {
        $page = (int)($request['page'] ?? 1);
        $limit = (int)($request['limit'] ?? 20);
        
        // Limit max per page
        $limit = min($limit, 100);
        
        return [
            'page' => max(1, $page),
            'limit' => max(1, $limit),
            'offset' => ($page - 1) * $limit
        ];
    }

    /**
     * Format pagination response
     */
    protected function paginatedResponse($data, $total, $page, $limit, $message = 'Thành công')
    {
        $totalPages = ceil($total / $limit);
        
        return BaseResource::success($data, $message)
            ->withPagination($page, $limit, $total, $totalPages)
            ->send();
    }

    /**
     * Log API request
     */
    protected function logRequest($method, $endpoint, $user = null, $status = 200)
    {
        $logData = [
            'method' => $method,
            'endpoint' => $endpoint,
            'user_id' => $user['user_id'] ?? null,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        // Log to file
        $logFile = __DIR__ . '/../../../../storage/logs/api.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}
