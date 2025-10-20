<?php

namespace App\Http\Requests;

/**
 * Base Request Class cho API validation
 */
abstract class BaseRequest
{
    protected $data;
    protected $errors = [];
    protected $rules = [];

    public function __construct($data = null)
    {
        $this->data = $data ?: $this->getRequestData();
    }

    /**
     * Lấy dữ liệu từ request
     */
    protected function getRequestData()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        switch ($method) {
            case 'POST':
            case 'PUT':
            case 'PATCH':
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (strpos($contentType, 'application/json') !== false) {
                    $input = file_get_contents('php://input');
                    return json_decode($input, true) ?: [];
                }
                return $_POST;
            case 'GET':
                return $_GET;
            default:
                return [];
        }
    }

    /**
     * Validate request data
     */
    public function validate()
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rule) {
            $this->validateField($field, $rule);
        }
        
        return empty($this->errors);
    }

    /**
     * Validate single field
     */
    protected function validateField($field, $rule)
    {
        $value = $this->get($field);
        $rules = explode('|', $rule);
        
        foreach ($rules as $singleRule) {
            $this->applyRule($field, $value, $singleRule);
        }
    }

    /**
     * Apply validation rule
     */
    protected function applyRule($field, $value, $rule)
    {
        if (strpos($rule, ':') !== false) {
            [$ruleName, $ruleValue] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $ruleValue = null;
        }

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "Trường {$field} là bắt buộc");
                }
                break;
                
            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, "Trường {$field} phải là chuỗi");
                }
                break;
                
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    $this->addError($field, "Trường {$field} phải là số nguyên");
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "Trường {$field} phải là số");
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Trường {$field} phải là email hợp lệ");
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < (int)$ruleValue) {
                    $this->addError($field, "Trường {$field} phải có ít nhất {$ruleValue} ký tự");
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > (int)$ruleValue) {
                    $this->addError($field, "Trường {$field} phải có tối đa {$ruleValue} ký tự");
                }
                break;
                
            case 'in':
                $allowedValues = explode(',', $ruleValue);
                if (!empty($value) && !in_array($value, $allowedValues)) {
                    $this->addError($field, "Trường {$field} phải là một trong các giá trị: " . implode(', ', $allowedValues));
                }
                break;
        }
    }

    /**
     * Add validation error
     */
    protected function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get field value
     */
    public function get($field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * Get all data
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get validation errors
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Check if has errors
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get first error for field
     */
    public function firstError($field)
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all errors as flat array
     */
    public function allErrors()
    {
        $allErrors = [];
        foreach ($this->errors as $field => $errors) {
            $allErrors = array_merge($allErrors, $errors);
        }
        return $allErrors;
    }
}
