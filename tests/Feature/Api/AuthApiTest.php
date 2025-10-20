<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Services\AuthService;

class AuthApiTest extends TestCase
{
    private $apiUrl = 'http://localhost/api/v2';
    private $token = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiUrl = $_ENV['API_URL'] ?? 'http://localhost/api/v2';
    }

    /**
     * Test login endpoint
     */
    public function testLoginSuccess()
    {
        $response = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'status',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'username',
                            'role',
                            'name'
                        ],
                        'token',
                        'token_type',
                        'expires_in'
                    ],
                    'timestamp'
                ])
                ->assertJson([
                    'success' => true,
                    'status' => 200
                ]);

        // Store token for other tests
        $this->token = $response->json('data.token');
    }

    /**
     * Test login with invalid credentials
     */
    public function testLoginFailure()
    {
        $response = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'status' => 401
                ]);
    }

    /**
     * Test login with missing credentials
     */
    public function testLoginValidationError()
    {
        $response = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin'
            // Missing password
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    /**
     * Test get current user info
     */
    public function testGetCurrentUser()
    {
        // First login to get token
        $loginResponse = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson($this->apiUrl . '/auth/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'status',
                    'message',
                    'data' => [
                        'user_id',
                        'username',
                        'role',
                        'name'
                    ],
                    'timestamp'
                ]);
    }

    /**
     * Test get current user without authentication
     */
    public function testGetCurrentUserUnauthorized()
    {
        $response = $this->getJson($this->apiUrl . '/auth/me');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'status' => 401
                ]);
    }

    /**
     * Test refresh token
     */
    public function testRefreshToken()
    {
        // First login to get token
        $loginResponse = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson($this->apiUrl . '/auth/refresh');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'status',
                    'message',
                    'data' => [
                        'token',
                        'token_type',
                        'expires_in'
                    ],
                    'timestamp'
                ]);
    }

    /**
     * Test change password
     */
    public function testChangePassword()
    {
        // First login to get token
        $loginResponse = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson($this->apiUrl . '/auth/change-password', [
            'current_password' => 'admin123',
            'new_password' => 'newpassword123',
            'confirm_password' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    /**
     * Test change password with wrong current password
     */
    public function testChangePasswordWrongCurrent()
    {
        // First login to get token
        $loginResponse = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson($this->apiUrl . '/auth/change-password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'confirm_password' => 'newpassword123'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    /**
     * Test logout
     */
    public function testLogout()
    {
        // First login to get token
        $loginResponse = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson($this->apiUrl . '/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Đăng xuất thành công'
                ]);
    }

    /**
     * Test health check endpoint
     */
    public function testHealthCheck()
    {
        $response = $this->getJson($this->apiUrl . '/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'status',
                    'message',
                    'timestamp',
                    'version'
                ])
                ->assertJson([
                    'success' => true,
                    'status' => 200
                ]);
    }
}
