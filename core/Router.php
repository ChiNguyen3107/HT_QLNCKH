<?php
/**
 * Class quản lý routing
 */

class Router
{
    private $routes = [];
    private $middleware = [];
    
    /**
     * Đăng ký route GET
     */
    public function get($path, $handler, $middleware = [])
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    /**
     * Đăng ký route POST
     */
    public function post($path, $handler, $middleware = [])
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    /**
     * Đăng ký route PUT
     */
    public function put($path, $handler, $middleware = [])
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    /**
     * Đăng ký route DELETE
     */
    public function delete($path, $handler, $middleware = [])
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    /**
     * Thêm route
     */
    private function addRoute($method, $path, $handler, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    /**
     * Xử lý request
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                // Thực hiện middleware
                foreach ($route['middleware'] as $middleware) {
                    $this->runMiddleware($middleware);
                }
                
                // Thực hiện handler
                $this->runHandler($route['handler']);
                return;
            }
        }
        
        // Không tìm thấy route
        http_response_code(404);
        echo "404 Not Found";
    }
    
    /**
     * Kiểm tra path có khớp không
     */
    private function matchPath($routePath, $requestPath)
    {
        $routePath = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $routePath . '$#';
        return preg_match($pattern, $requestPath);
    }
    
    /**
     * Chạy middleware
     */
    private function runMiddleware($middleware)
    {
        if (is_string($middleware)) {
            $middlewareClass = "App\\Middleware\\{$middleware}";
            if (class_exists($middlewareClass)) {
                $instance = new $middlewareClass();
                $instance->handle();
            }
        } elseif (is_callable($middleware)) {
            $middleware();
        }
    }
    
    /**
     * Chạy handler
     */
    private function runHandler($handler)
    {
        if (is_string($handler)) {
            // Format: Controller@method
            if (strpos($handler, '@') !== false) {
                list($controller, $method) = explode('@', $handler);
                $controllerClass = "App\\Controllers\\{$controller}";
                if (class_exists($controllerClass)) {
                    $instance = new $controllerClass();
                    if (method_exists($instance, $method)) {
                        $instance->$method();
                    }
                }
            }
        } elseif (is_callable($handler)) {
            $handler();
        }
    }
}

