<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BaseResource;
use App\Http\Requests\BaseRequest;
use App\Services\AuthService;

/**
 * Authentication API Controller
 */
class AuthController extends BaseApiController
{
    private $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * POST /api/v2/auth/login
     * Đăng nhập
     */
    public function login($request)
    {
        try {
            // Validate input
            $validation = $this->validateLoginRequest($request);
            if (!$validation['valid']) {
                return $this->validationError($validation['errors'], 'Dữ liệu đăng nhập không hợp lệ');
            }

            $username = $request['username'];
            $password = $request['password'];
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // Authenticate user
            $result = $this->authService->authenticate($username, $password, $ipAddress);

            if (!$result['success']) {
                $this->logRequest('POST', '/api/v2/auth/login', null, 401);
                return $this->error($result['message'], 401);
            }

            // Generate JWT token
            $token = $this->auth->generateToken($result['user']);

            $this->logRequest('POST', '/api/v2/auth/login', $result['user'], 200);

            return $this->success([
                'user' => $result['user'],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 24 * 60 * 60 // 24 hours
            ], 'Đăng nhập thành công');

        } catch (Exception $e) {
            $this->logRequest('POST', '/api/v2/auth/login', null, 500);
            return $this->serverError('Có lỗi xảy ra trong quá trình đăng nhập');
        }
    }

    /**
     * POST /api/v2/auth/logout
     * Đăng xuất
     */
    public function logout($request)
    {
        try {
            $user = $this->getCurrentUser($request);
            
            if (!$user) {
                return $this->unauthorized('Chưa đăng nhập');
            }

            // Logout user
            $result = $this->authService->logout();

            $this->logRequest('POST', '/api/v2/auth/logout', $user, 200);

            return $this->success(null, 'Đăng xuất thành công');

        } catch (Exception $e) {
            $this->logRequest('POST', '/api/v2/auth/logout', null, 500);
            return $this->serverError('Có lỗi xảy ra khi đăng xuất');
        }
    }

    /**
     * POST /api/v2/auth/refresh
     * Làm mới token
     */
    public function refresh($request)
    {
        try {
            $user = $this->getCurrentUser($request);
            
            if (!$user) {
                return $this->unauthorized('Chưa đăng nhập');
            }

            // Get current token from header
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = null;
            
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }

            if (!$token) {
                return $this->error('Token không được cung cấp', 400);
            }

            // Refresh token
            $newToken = $this->auth->refreshToken($token);

            if (!$newToken) {
                return $this->error('Không thể làm mới token', 400);
            }

            $this->logRequest('POST', '/api/v2/auth/refresh', $user, 200);

            return $this->success([
                'token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => 24 * 60 * 60
            ], 'Token đã được làm mới');

        } catch (Exception $e) {
            $this->logRequest('POST', '/api/v2/auth/refresh', null, 500);
            return $this->serverError('Có lỗi xảy ra khi làm mới token');
        }
    }

    /**
     * GET /api/v2/auth/me
     * Lấy thông tin user hiện tại
     */
    public function me($request)
    {
        try {
            $user = $this->getCurrentUser($request);
            
            if (!$user) {
                return $this->unauthorized('Chưa đăng nhập');
            }

            $this->logRequest('GET', '/api/v2/auth/me', $user, 200);

            return $this->success($user, 'Lấy thông tin user thành công');

        } catch (Exception $e) {
            $this->logRequest('GET', '/api/v2/auth/me', null, 500);
            return $this->serverError('Có lỗi xảy ra khi lấy thông tin user');
        }
    }

    /**
     * POST /api/v2/auth/change-password
     * Đổi mật khẩu
     */
    public function changePassword($request)
    {
        try {
            $user = $this->getCurrentUser($request);
            
            if (!$user) {
                return $this->unauthorized('Chưa đăng nhập');
            }

            // Validate input
            $validation = $this->validateChangePasswordRequest($request);
            if (!$validation['valid']) {
                return $this->validationError($validation['errors'], 'Dữ liệu không hợp lệ');
            }

            $currentPassword = $request['current_password'];
            $newPassword = $request['new_password'];

            // Change password
            $result = $this->authService->changePassword(
                $user['user_id'],
                $currentPassword,
                $newPassword,
                $user['role']
            );

            if (!$result['success']) {
                return $this->error($result['message'], 400);
            }

            $this->logRequest('POST', '/api/v2/auth/change-password', $user, 200);

            return $this->success(null, 'Đổi mật khẩu thành công');

        } catch (Exception $e) {
            $this->logRequest('POST', '/api/v2/auth/change-password', null, 500);
            return $this->serverError('Có lỗi xảy ra khi đổi mật khẩu');
        }
    }

    /**
     * Validate login request
     */
    private function validateLoginRequest($request)
    {
        $errors = [];
        
        if (empty($request['username'])) {
            $errors['username'] = ['Tên đăng nhập là bắt buộc'];
        }
        
        if (empty($request['password'])) {
            $errors['password'] = ['Mật khẩu là bắt buộc'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate change password request
     */
    private function validateChangePasswordRequest($request)
    {
        $errors = [];
        
        if (empty($request['current_password'])) {
            $errors['current_password'] = ['Mật khẩu hiện tại là bắt buộc'];
        }
        
        if (empty($request['new_password'])) {
            $errors['new_password'] = ['Mật khẩu mới là bắt buộc'];
        } elseif (strlen($request['new_password']) < 6) {
            $errors['new_password'] = ['Mật khẩu mới phải có ít nhất 6 ký tự'];
        }
        
        if ($request['new_password'] !== $request['confirm_password']) {
            $errors['confirm_password'] = ['Xác nhận mật khẩu không khớp'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
