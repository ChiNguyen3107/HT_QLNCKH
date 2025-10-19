<?php

require_once 'app/Services/AuthService.php';
require_once 'app/Services/PasswordPolicy.php';

class AuthServiceTest extends PHPUnit\Framework\TestCase
{
    private $authService;
    private $mockDb;

    protected function setUp(): void
    {
        $this->authService = new AuthService();
        $this->mockDb = $this->createMock('Database');
    }

    public function testAuthenticateWithValidCredentials()
    {
        // Mock database response
        $mockUser = [
            'id' => '1',
            'username' => 'testuser',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'name' => 'Test User'
        ];

        // Mock db()->fetch method
        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        // Test authentication
        $result = $this->authService->authenticate('testuser', 'password123', '127.0.0.1');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertEquals('admin', $result['user']['role']);
    }

    public function testAuthenticateWithInvalidCredentials()
    {
        // Mock database response - no user found
        $this->mockDb->method('fetch')
            ->willReturn(null);

        $result = $this->authService->authenticate('invaliduser', 'wrongpassword', '127.0.0.1');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Tên đăng nhập hoặc mật khẩu không đúng', $result['message']);
    }

    public function testAuthenticateWithLockedAccount()
    {
        // Mock database response for locked account
        $this->mockDb->method('fetch')
            ->willReturnCallback(function($sql, $params) {
                if (strpos($sql, 'login_attempts') !== false) {
                    return ['count' => 6]; // More than max attempts
                }
                return null;
            });

        $result = $this->authService->authenticate('testuser', 'password123', '127.0.0.1');
        
        $this->assertFalse($result['success']);
        $this->assertTrue($result['locked']);
        $this->assertStringContainsString('Tài khoản đã bị khóa', $result['message']);
    }

    public function testVerifyPasswordWithBcrypt()
    {
        $reflection = new ReflectionClass($this->authService);
        $method = $reflection->getMethod('verifyPassword');
        $method->setAccessible(true);

        $password = 'testpassword';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue($method->invoke($this->authService, $password, $hashedPassword));
        $this->assertFalse($method->invoke($this->authService, 'wrongpassword', $hashedPassword));
    }

    public function testVerifyPasswordWithMD5()
    {
        $reflection = new ReflectionClass($this->authService);
        $method = $reflection->getMethod('verifyPassword');
        $method->setAccessible(true);

        $password = 'testpassword';
        $md5Hash = md5($password);
        
        $this->assertTrue($method->invoke($this->authService, $password, $md5Hash));
        $this->assertFalse($method->invoke($this->authService, 'wrongpassword', $md5Hash));
    }

    public function testVerifyPasswordWithSHA256()
    {
        $reflection = new ReflectionClass($this->authService);
        $method = $reflection->getMethod('verifyPassword');
        $method->setAccessible(true);

        $password = 'testpassword';
        $sha256Hash = hash('sha256', $password);
        
        $this->assertTrue($method->invoke($this->authService, $password, $sha256Hash));
        $this->assertFalse($method->invoke($this->authService, 'wrongpassword', $sha256Hash));
    }

    public function testChangePasswordWithValidCurrentPassword()
    {
        // Mock user data
        $mockUser = [
            'id' => '1',
            'password' => password_hash('oldpassword', PASSWORD_DEFAULT)
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $result = $this->authService->changePassword('1', 'oldpassword', 'NewPass123!', 'admin');
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Mật khẩu đã được thay đổi thành công', $result['message']);
    }

    public function testChangePasswordWithInvalidCurrentPassword()
    {
        // Mock user data
        $mockUser = [
            'id' => '1',
            'password' => password_hash('oldpassword', PASSWORD_DEFAULT)
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $result = $this->authService->changePassword('1', 'wrongpassword', 'NewPass123!', 'admin');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Mật khẩu hiện tại không đúng', $result['message']);
    }

    public function testChangePasswordWithWeakNewPassword()
    {
        // Mock user data
        $mockUser = [
            'id' => '1',
            'password' => password_hash('oldpassword', PASSWORD_DEFAULT)
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $result = $this->authService->changePassword('1', 'oldpassword', '123', 'admin');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Mật khẩu mới không đáp ứng yêu cầu', $result['message']);
    }

    public function testCheckPasswordStrength()
    {
        $weakPassword = '123';
        $strongPassword = 'StrongPass123!';
        
        $weakResult = $this->authService->checkPasswordStrength($weakPassword);
        $strongResult = $this->authService->checkPasswordStrength($strongPassword);
        
        $this->assertLessThan($strongResult['score'], $weakResult['score']);
        $this->assertArrayHasKey('level', $weakResult);
        $this->assertArrayHasKey('message', $weakResult);
    }

    public function testHasPermission()
    {
        $this->assertTrue($this->authService->hasPermission('admin', 'any.permission'));
        $this->assertTrue($this->authService->hasPermission('teacher', 'projects.view'));
        $this->assertFalse($this->authService->hasPermission('student', 'admin.manage'));
        $this->assertFalse($this->authService->hasPermission('invalid_role', 'any.permission'));
    }

    public function testHasRole()
    {
        $_SESSION['role'] = 'admin';
        $this->assertTrue($this->authService->hasRole('admin'));
        $this->assertFalse($this->authService->hasRole('student'));
        
        unset($_SESSION['role']);
        $this->assertFalse($this->authService->hasRole('admin'));
    }

    public function testGetCurrentUser()
    {
        $_SESSION['user_id'] = '1';
        $_SESSION['username'] = 'testuser';
        $_SESSION['role'] = 'admin';
        $_SESSION['name'] = 'Test User';
        
        $user = $this->authService->getCurrentUser();
        
        $this->assertEquals('1', $user['id']);
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('admin', $user['role']);
        $this->assertEquals('Test User', $user['name']);
        
        unset($_SESSION['user_id']);
        $this->assertNull($this->authService->getCurrentUser());
    }

    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = [];
    }
}
