<?php

require_once 'app/Services/PasswordResetService.php';
require_once 'app/Services/PasswordPolicy.php';

class PasswordResetServiceTest extends PHPUnit\Framework\TestCase
{
    private $passwordResetService;
    private $mockDb;

    protected function setUp(): void
    {
        $this->mockDb = $this->createMock('Database');
        $this->passwordResetService = new PasswordResetService($this->mockDb);
    }

    public function testCreateResetTokenWithValidEmail()
    {
        // Mock user found
        $mockUser = [
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $result = $this->passwordResetService->createResetToken('test@example.com', 'user');
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Email reset password đã được gửi', $result['message']);
    }

    public function testCreateResetTokenWithInvalidEmail()
    {
        // Mock user not found
        $this->mockDb->method('fetch')
            ->willReturn(null);

        $result = $this->passwordResetService->createResetToken('invalid@example.com', 'user');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Email không tồn tại trong hệ thống', $result['message']);
    }

    public function testCreateResetTokenWithTooManyAttempts()
    {
        // Mock user found
        $mockUser = [
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];

        // Mock recent attempts count
        $this->mockDb->method('fetch')
            ->willReturnCallback(function($sql, $params) use ($mockUser) {
                if (strpos($sql, 'password_reset_tokens') !== false) {
                    return ['count' => 5]; // More than max attempts
                }
                return $mockUser;
            });

        $result = $this->passwordResetService->createResetToken('test@example.com', 'user');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('quá nhiều yêu cầu reset password', $result['message']);
    }

    public function testResetPasswordWithValidToken()
    {
        // Mock valid token
        $mockToken = [
            'email' => 'test@example.com',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600), // 1 hour from now
            'user_type' => 'user'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockToken);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $result = $this->passwordResetService->resetPassword('validtoken123', 'NewPass123!', 'user');
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Mật khẩu đã được cập nhật thành công', $result['message']);
    }

    public function testResetPasswordWithInvalidToken()
    {
        // Mock invalid token
        $this->mockDb->method('fetch')
            ->willReturn(null);

        $result = $this->passwordResetService->resetPassword('invalidtoken', 'NewPass123!', 'user');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Token không hợp lệ hoặc đã hết hạn', $result['message']);
    }

    public function testResetPasswordWithExpiredToken()
    {
        // Mock expired token
        $mockToken = [
            'email' => 'test@example.com',
            'expires_at' => date('Y-m-d H:i:s', time() - 3600), // 1 hour ago
            'user_type' => 'user'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockToken);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $result = $this->passwordResetService->resetPassword('expiredtoken', 'NewPass123!', 'user');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Token đã hết hạn', $result['message']);
    }

    public function testResetPasswordWithWeakPassword()
    {
        // Mock valid token
        $mockToken = [
            'email' => 'test@example.com',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'user_type' => 'user'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockToken);

        $result = $this->passwordResetService->resetPassword('validtoken123', '123', 'user');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Mật khẩu mới không đáp ứng yêu cầu bảo mật', $result['message']);
    }

    public function testValidateTokenWithValidToken()
    {
        // Mock valid token
        $mockToken = [
            'email' => 'test@example.com',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'user_type' => 'user'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockToken);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $this->assertTrue($this->passwordResetService->validateToken('validtoken123', 'user'));
    }

    public function testValidateTokenWithInvalidToken()
    {
        // Mock invalid token
        $this->mockDb->method('fetch')
            ->willReturn(null);

        $this->assertFalse($this->passwordResetService->validateToken('invalidtoken', 'user'));
    }

    public function testValidateTokenWithExpiredToken()
    {
        // Mock expired token
        $mockToken = [
            'email' => 'test@example.com',
            'expires_at' => date('Y-m-d H:i:s', time() - 3600),
            'user_type' => 'user'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockToken);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $this->assertFalse($this->passwordResetService->validateToken('expiredtoken', 'user'));
    }

    public function testCleanupExpiredTokens()
    {
        $this->mockDb->method('execute')
            ->willReturn(true);

        // Should not throw exception
        $this->passwordResetService->cleanupExpiredTokens();
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testGenerateSecureToken()
    {
        $reflection = new ReflectionClass($this->passwordResetService);
        $method = $reflection->getMethod('generateSecureToken');
        $method->setAccessible(true);

        $token1 = $method->invoke($this->passwordResetService);
        $token2 = $method->invoke($this->passwordResetService);

        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
        $this->assertEquals(64, strlen($token2));
        $this->assertNotEquals($token1, $token2); // Should be different
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token1);
    }

    public function testFindUserByEmail()
    {
        $reflection = new ReflectionClass($this->passwordResetService);
        $method = $reflection->getMethod('findUserByEmail');
        $method->setAccessible(true);

        // Test user type
        $mockUser = [
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $result = $method->invoke($this->passwordResetService, 'test@example.com', 'user');
        $this->assertEquals($mockUser, $result);

        // Test student type
        $mockStudent = [
            'id' => 'SV001',
            'email' => 'student@example.com',
            'name' => 'Student Name'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockStudent);

        $result = $method->invoke($this->passwordResetService, 'student@example.com', 'student');
        $this->assertEquals($mockStudent, $result);

        // Test teacher type
        $mockTeacher = [
            'id' => 'GV001',
            'email' => 'teacher@example.com',
            'name' => 'Teacher Name'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockTeacher);

        $result = $method->invoke($this->passwordResetService, 'teacher@example.com', 'teacher');
        $this->assertEquals($mockTeacher, $result);
    }
}
