<?php

namespace App\Http\Router;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\FacultyController;

/**
 * API Router for RESTful endpoints
 */
class ApiRouter
{
    private $routes = [];
    private $request;
    private $method;
    private $path;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->getPath();
        $this->request = $this->getRequestData();
        
        $this->registerRoutes();
    }

    /**
     * Register all API routes
     */
    private function registerRoutes()
    {
        // Authentication routes
        $this->addRoute('POST', '/auth/login', [AuthController::class, 'login']);
        $this->addRoute('POST', '/auth/logout', [AuthController::class, 'logout']);
        $this->addRoute('POST', '/auth/refresh', [AuthController::class, 'refresh']);
        $this->addRoute('GET', '/auth/me', [AuthController::class, 'me']);
        $this->addRoute('POST', '/auth/change-password', [AuthController::class, 'changePassword']);

        // Student routes
        $this->addRoute('GET', '/students', [StudentController::class, 'index']);
        $this->addRoute('GET', '/students/{id}', [StudentController::class, 'show']);
        $this->addRoute('POST', '/students', [StudentController::class, 'store']);
        $this->addRoute('PUT', '/students/{id}', [StudentController::class, 'update']);
        $this->addRoute('DELETE', '/students/{id}', [StudentController::class, 'destroy']);

        // Project routes
        $this->addRoute('GET', '/projects', [ProjectController::class, 'index']);
        $this->addRoute('GET', '/projects/{id}', [ProjectController::class, 'show']);
        $this->addRoute('POST', '/projects', [ProjectController::class, 'store']);
        $this->addRoute('PUT', '/projects/{id}', [ProjectController::class, 'update']);
        $this->addRoute('DELETE', '/projects/{id}', [ProjectController::class, 'destroy']);
        $this->addRoute('POST', '/projects/{id}/members', [ProjectController::class, 'addMember']);
        $this->addRoute('DELETE', '/projects/{id}/members/{student_id}', [ProjectController::class, 'removeMember']);
        $this->addRoute('POST', '/projects/{id}/evaluation', [ProjectController::class, 'evaluate']);

        // Faculty routes
        $this->addRoute('GET', '/faculties', [FacultyController::class, 'index']);
        $this->addRoute('GET', '/faculties/{id}', [FacultyController::class, 'show']);
        $this->addRoute('POST', '/faculties', [FacultyController::class, 'store']);
        $this->addRoute('PUT', '/faculties/{id}', [FacultyController::class, 'update']);
        $this->addRoute('DELETE', '/faculties/{id}', [FacultyController::class, 'destroy']);
        $this->addRoute('GET', '/faculties/{id}/statistics', [FacultyController::class, 'statistics']);

        // Health check
        $this->addRoute('GET', '/health', [$this, 'healthCheck']);
    }

    /**
     * Add route to router
     */
    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Route the request
     */
    public function route()
    {
        $matchedRoute = $this->findMatchingRoute();
        
        if (!$matchedRoute) {
            $this->notFound();
            return;
        }

        $this->executeRoute($matchedRoute);
    }

    /**
     * Find matching route
     */
    private function findMatchingRoute()
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) {
                continue;
            }

            $pattern = $this->convertPathToRegex($route['path']);
            if (preg_match($pattern, $this->path, $matches)) {
                // Extract path parameters
                $params = $this->extractPathParams($route['path'], $matches);
                $route['params'] = $params;
                return $route;
            }
        }

        return null;
    }

    /**
     * Convert path pattern to regex
     */
    private function convertPathToRegex($path)
    {
        // Replace {param} with regex groups
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Extract path parameters
     */
    private function extractPathParams($path, $matches)
    {
        $params = [];
        preg_match_all('/\{([^}]+)\}/', $path, $paramNames);
        
        for ($i = 0; $i < count($paramNames[1]); $i++) {
            $params[$paramNames[1][$i]] = $matches[$i + 1];
        }

        return $params;
    }

    /**
     * Execute route handler
     */
    private function executeRoute($route)
    {
        $handler = $route['handler'];
        $params = $route['params'] ?? [];

        try {
            if (is_array($handler)) {
                $controller = new $handler[0]();
                $method = $handler[1];
                
                // Merge request data with path parameters
                $requestData = array_merge($this->request, $params);
                
                // Call controller method
                $controller->$method($requestData, ...array_values($params));
            } else {
                // Call function handler
                $handler($this->request, ...array_values($params));
            }
        } catch (Exception $e) {
            $this->serverError($e->getMessage());
        }
    }

    /**
     * Get request path
     */
    private function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        // Remove /api/v2 prefix
        $path = preg_replace('#^/api/v2#', '', $path);
        
        // Ensure path starts with /
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        return $path;
    }

    /**
     * Get request data
     */
    private function getRequestData()
    {
        $data = [];
        
        // Get data from different sources based on method
        switch ($this->method) {
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                
                if (strpos($contentType, 'application/json') !== false) {
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true) ?: [];
                } else {
                    $data = $_POST;
                }
                break;
        }
        
        return $data;
    }

    /**
     * Health check endpoint
     */
    public function healthCheck($request)
    {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'message' => 'API hoạt động bình thường',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '2.0.0'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return 404 Not Found
     */
    private function notFound()
    {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'status' => 404,
            'message' => 'Endpoint không tìm thấy',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Return 500 Server Error
     */
    private function serverError($message = 'Lỗi máy chủ')
    {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'status' => 500,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
}
