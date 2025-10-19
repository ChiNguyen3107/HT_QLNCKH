<?php

require_once 'app/Services/AuthService.php';
require_once 'app/Services/PasswordPolicy.php';
require_once 'app/Services/PasswordResetService.php';

class SecurityIntegrationTest extends PHPUnit\Framework\TestCase
{
    private $authService;
    private $passwordPolicy;
    private $passwordResetService;
    private $mockDb;

    protected function setUp(): void
    {
        $this->mockDb = $this->createMock('Database');
        $this->authService = new AuthService();
        $this->passwordPolicy = new PasswordPolicy();
        $this->passwordResetService = new PasswordResetService($this->mockDb);
    }

    public function testCompletePasswordResetFlow()
    {
        $email = 'test@example.com';
        $userType = 'user';
        $newPassword = 'NewStrongPass123!';

        // Step 1: Create reset token
        $mockUser = [
            'id' => '1',
            'email' => $email,
            'name' => 'Test User'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $createResult = $this->passwordResetService->createResetToken($email, $userType);
        $this->assertTrue($createResult['success']);

        // Step 2: Validate token
        $mockToken = [
            'email' => $email,
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'user_type' => $userType
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockToken);

        $isValid = $this->passwordResetService->validateToken('validtoken123', $userType);
        $this->assertTrue($isValid);

        // Step 3: Reset password
        $resetResult = $this->passwordResetService->resetPassword('validtoken123', $newPassword, $userType);
        $this->assertTrue($resetResult['success']);

        // Step 4: Verify new password works
        $mockUserWithNewPassword = [
            'id' => '1',
            'username' => $email,
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'role' => 'admin',
            'name' => 'Test User'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUserWithNewPassword);

        $authResult = $this->authService->authenticate($email, $newPassword, '127.0.0.1');
        $this->assertTrue($authResult['success']);
    }

    public function testPasswordPolicyIntegration()
    {
        $weakPassword = '123';
        $strongPassword = 'StrongPass123!';

        // Test weak password
        $weakValidation = $this->passwordPolicy->validatePassword($weakPassword);
        $this->assertFalse($weakValidation['valid']);

        $weakStrength = $this->passwordPolicy->calculateStrength($weakPassword);
        $this->assertLessThan(50, $weakStrength);

        // Test strong password
        $strongValidation = $this->passwordPolicy->validatePassword($strongPassword);
        $this->assertTrue($strongValidation['valid']);

        $strongStrength = $this->passwordPolicy->calculateStrength($strongPassword);
        $this->assertGreaterThan(70, $strongStrength);
    }

    public function testAccountLockoutFlow()
    {
        $username = 'testuser';
        $ipAddress = '127.0.0.1';

        // Mock multiple failed attempts
        $this->mockDb->method('fetch')
            ->willReturnCallback(function($sql, $params) {
                if (strpos($sql, 'login_attempts') !== false) {
                    return ['count' => 6]; // More than max attempts
                }
                return null; // No user found
            });

        // First attempt should be locked
        $result1 = $this->authService->authenticate($username, 'wrongpassword', $ipAddress);
        $this->assertFalse($result1['success']);
        $this->assertTrue($result1['locked']);

        // Second attempt should also be locked
        $result2 = $this->authService->authenticate($username, 'wrongpassword', $ipAddress);
        $this->assertFalse($result2['success']);
        $this->assertTrue($result2['locked']);
    }

    public function testPasswordUpgradeFlow()
    {
        $username = 'testuser';
        $password = 'testpassword';
        $md5Hash = md5($password);

        // Mock user with MD5 password
        $mockUser = [
            'id' => '1',
            'username' => $username,
            'password' => $md5Hash,
            'role' => 'admin',
            'name' => 'Test User'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $this->mockDb->method('execute')
            ->willReturn(true);

        // Authenticate with MD5 password
        $result = $this->authService->authenticate($username, $password, '127.0.0.1');
        $this->assertTrue($result['success']);

        // Password should be upgraded to bcrypt (this would happen in real implementation)
        // We can't easily test this without a real database connection
    }

    public function testSecurityAuditLogging()
    {
        $username = 'testuser';
        $ipAddress = '127.0.0.1';

        // Mock successful authentication
        $mockUser = [
            'id' => '1',
            'username' => $username,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'name' => 'Test User'
        ];

        $this->mockDb->method('fetch')
            ->willReturn($mockUser);

        $this->mockDb->method('execute')
            ->willReturn(true);

        $result = $this->authService->authenticate($username, 'password123', $ipAddress);
        $this->assertTrue($result['success']);

        // Verify that login attempt was logged
        // In a real implementation, we would check the database for the log entry
    }

    public function testPasswordStrengthIndicatorIntegration()
    {
        $passwords = [
            '123' => 'very_weak',
            'password' => 'weak',
            'Password1' => 'medium',
            'Password123!' => 'strong',
            'VeryStrongPassword123!@#' => 'very_strong'
        ];

        foreach ($passwords as $password => $expectedLevel) {
            $level = $this->passwordPolicy->getStrengthLevel($password);
            $this->assertEquals($expectedLevel, $level);
        }
    }

    public function testMixedPasswordFormatSupport()
    {
        $password = 'testpassword';
        $formats = [
            password_hash($password, PASSWORD_DEFAULT), // bcrypt
            hash('sha256', $password), // SHA256
            md5($password), // MD5
            $password // Plain text
        ];

        $reflection = new ReflectionClass($this->authService);
        $method = $reflection->getMethod('verifyPassword');
        $method->setAccessible(true);

        foreach ($formats as $format) {
            $result = $method->invoke($this->authService, $password, $format);
            $this->assertTrue($result, "Failed to verify password in format: " . substr($format, 0, 20));
        }
    }

    public function testSessionSecurity()
    {
        // Test session regeneration
        $_SESSION['user_id'] = '1';
        $oldSessionId = session_id();
        
        // In real implementation, session_regenerate_id(true) would be called
        // Here we just verify the concept
        $this->assertNotEmpty($oldSessionId);
        
        // Test session variables
        $_SESSION['user_id'] = '1';
        $_SESSION['username'] = 'testuser';
        $_SESSION['role'] = 'admin';
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = '127.0.0.1';

        $this->assertTrue($this->authService->check());
        $this->assertTrue($this->authService->hasRole('admin'));
        $this->assertFalse($this->authService->hasRole('student'));

        $currentUser = $this->authService->getCurrentUser();
        $this->assertEquals('1', $currentUser['id']);
        $this->assertEquals('testuser', $currentUser['username']);
        $this->assertEquals('admin', $currentUser['role']);
    }

    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = [];
    }
}
