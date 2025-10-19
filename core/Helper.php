<?php

require_once __DIR__ . '/CSRF.php';

/**
 * Helper Functions
 * 
 * Cung cấp các helper functions cho views và controllers
 */
class Helper
{
    /**
     * Tạo CSRF token field cho form
     * 
     * @param string $formName Tên form (optional)
     * @return string HTML input field
     */
    public static function csrfField($formName = 'default')
    {
        return CSRF::getTokenField($formName);
    }
    
    /**
     * Lấy CSRF token value
     * 
     * @param string $formName Tên form (optional)
     * @return string CSRF token
     */
    public static function csrfToken($formName = 'default')
    {
        return CSRF::getToken($formName) ?: CSRF::generateToken($formName);
    }
    
    /**
     * Tạo CSRF token cho AJAX requests
     * 
     * @param string $formName Tên form (optional)
     * @return array Array chứa token name và value
     */
    public static function csrfAjaxToken($formName = 'ajax')
    {
        return CSRF::getAjaxToken($formName);
    }
    
    /**
     * Tạo form với CSRF protection
     * 
     * @param string $action Form action
     * @param string $method Form method (default: POST)
     * @param array $attributes Form attributes
     * @param string $formName Tên form cho CSRF token
     * @return string HTML form opening tag
     */
    public static function formOpen($action, $method = 'POST', $attributes = [], $formName = 'default')
    {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        $csrfField = self::csrfField($formName);
        
        return '<form action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" method="' . strtoupper($method) . '"' . $attrs . '>' . $csrfField;
    }
    
    /**
     * Tạo form closing tag
     * 
     * @return string HTML form closing tag
     */
    public static function formClose()
    {
        return '</form>';
    }
    
    /**
     * Tạo input field với CSRF protection
     * 
     * @param string $name Field name
     * @param string $type Field type (default: text)
     * @param mixed $value Field value
     * @param array $attributes Field attributes
     * @param string $formName Tên form cho CSRF token
     * @return string HTML input field
     */
    public static function input($name, $type = 'text', $value = '', $attributes = [], $formName = 'default')
    {
        $attrs = '';
        foreach ($attributes as $key => $val) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return '<input type="' . $type . '" name="' . $name . '" value="' . $value . '"' . $attrs . '>';
    }
    
    /**
     * Tạo submit button với CSRF protection
     * 
     * @param string $text Button text
     * @param array $attributes Button attributes
     * @param string $formName Tên form cho CSRF token
     * @return string HTML submit button
     */
    public static function submit($text, $attributes = [], $formName = 'default')
    {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        $csrfField = self::csrfField($formName);
        
        return $csrfField . '<button type="submit"' . $attrs . '>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</button>';
    }
    
    /**
     * Tạo JavaScript code để thêm CSRF token vào AJAX requests
     * 
     * @param string $formName Tên form cho CSRF token
     * @return string JavaScript code
     */
    public static function csrfAjaxScript($formName = 'ajax')
    {
        $token = self::csrfAjaxToken($formName);
        
        return "
        <script>
        // CSRF Token cho AJAX requests
        window.csrfToken = {
            name: '" . $token['name'] . "',
            value: '" . $token['value'] . "'
        };
        
        // Function để thêm CSRF token vào AJAX requests
        function addCSRFToken(xhr) {
            xhr.setRequestHeader('X-CSRF-Token', window.csrfToken.value);
        }
        
        // Override jQuery AJAX nếu có
        if (typeof $ !== 'undefined') {
            $(document).ajaxSend(function(event, xhr, settings) {
                if (settings.type && settings.type.toUpperCase() !== 'GET') {
                    addCSRFToken(xhr);
                }
            });
        }
        
        // Override fetch API
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (options.method && options.method.toUpperCase() !== 'GET') {
                options.headers = options.headers || {};
                options.headers['X-CSRF-Token'] = window.csrfToken.value;
            }
            return originalFetch(url, options);
        };
        </script>";
    }
    
    /**
     * Tạo meta tag cho CSRF token
     * 
     * @param string $formName Tên form cho CSRF token
     * @return string HTML meta tag
     */
    public static function csrfMetaTag($formName = 'default')
    {
        $token = self::csrfToken($formName);
        $tokenName = CSRF::getTokenName();
        
        return '<meta name="' . $tokenName . '" content="' . $token . '">';
    }
    
    /**
     * Kiểm tra xem có lỗi CSRF không
     * 
     * @return bool True nếu có lỗi CSRF
     */
    public static function hasCSRFError()
    {
        return isset($_SESSION['csrf_error']);
    }
    
    /**
     * Lấy thông báo lỗi CSRF
     * 
     * @return string|null Thông báo lỗi hoặc null
     */
    public static function getCSRFError()
    {
        if (self::hasCSRFError()) {
            $error = $_SESSION['csrf_error'];
            unset($_SESSION['csrf_error']);
            return $error;
        }
        return null;
    }
    
    /**
     * Hiển thị thông báo lỗi CSRF
     * 
     * @param string $class CSS class cho thông báo
     * @return string HTML thông báo lỗi
     */
    public static function showCSRFError($class = 'alert alert-danger')
    {
        $error = self::getCSRFError();
        if ($error) {
            return '<div class="' . $class . '">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        return '';
    }
    
    /**
     * Tạo URL với CSRF token
     * 
     * @param string $url URL gốc
     * @param string $formName Tên form cho CSRF token
     * @return string URL với CSRF token
     */
    public static function urlWithCSRF($url, $formName = 'default')
    {
        $token = self::csrfToken($formName);
        $tokenName = CSRF::getTokenName();
        
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . $tokenName . '=' . $token;
    }
    
    /**
     * Tạo JavaScript object chứa CSRF token
     * 
     * @param string $formName Tên form cho CSRF token
     * @return string JavaScript object
     */
    public static function csrfJsObject($formName = 'default')
    {
        $token = self::csrfToken($formName);
        $tokenName = CSRF::getTokenName();
        
        return json_encode([
            'name' => $tokenName,
            'value' => $token
        ]);
    }
    
    /**
     * Tạo form validation cho CSRF
     * 
     * @param string $formName Tên form
     * @return string JavaScript validation code
     */
    public static function csrfValidationScript($formName = 'default')
    {
        return "
        <script>
        // CSRF Validation cho forms
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const csrfField = form.querySelector('input[name=\"" . CSRF::getTokenName() . "\"]');
                    if (!csrfField || !csrfField.value) {
                        e.preventDefault();
                        alert('Lỗi bảo mật: CSRF token không hợp lệ. Vui lòng tải lại trang và thử lại.');
                        return false;
                    }
                });
            });
        });
        </script>";
    }
}
