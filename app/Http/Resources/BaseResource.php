<?php

namespace App\Http\Resources;

/**
 * Base Resource Class cho API responses
 */
abstract class BaseResource
{
    protected $data;
    protected $status;
    protected $message;
    protected $meta;

    public function __construct($data = null, $status = 200, $message = 'Thành công')
    {
        $this->data = $data;
        $this->status = $status;
        $this->message = $message;
        $this->meta = [];
    }

    /**
     * Tạo response thành công
     */
    public static function success($data = null, $message = 'Thành công', $status = 200)
    {
        return new static($data, $status, $message);
    }

    /**
     * Tạo response lỗi
     */
    public static function error($message = 'Có lỗi xảy ra', $status = 400, $data = null)
    {
        return new static($data, $status, $message);
    }

    /**
     * Thêm metadata
     */
    public function withMeta($key, $value)
    {
        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * Thêm pagination info
     */
    public function withPagination($page, $limit, $total, $totalPages)
    {
        $this->meta['pagination'] = [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
        return $this;
    }

    /**
     * Convert to array
     */
    public function toArray()
    {
        $response = [
            'success' => $this->status >= 200 && $this->status < 300,
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }

    /**
     * Convert to JSON
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Send response
     */
    public function send()
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        echo $this->toJson();
        exit;
    }
}
