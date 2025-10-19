<?php

require_once 'app/Services/PasswordPolicy.php';

class PasswordPolicyTest extends PHPUnit\Framework\TestCase
{
    private $passwordPolicy;

    protected function setUp(): void
    {
        $this->passwordPolicy = new PasswordPolicy();
    }

    public function testValidatePasswordWithValidPassword()
    {
        $password = 'StrongPass123!';
        $result = $this->passwordPolicy->validatePassword($password);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidatePasswordWithShortPassword()
    {
        $password = '123';
        $result = $this->passwordPolicy->validatePassword($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Mật khẩu phải có ít nhất 8 ký tự', $result['errors']);
    }

    public function testValidatePasswordWithoutUppercase()
    {
        $password = 'weakpass123!';
        $result = $this->passwordPolicy->validatePassword($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Mật khẩu phải chứa ít nhất 1 chữ cái viết hoa', $result['errors']);
    }

    public function testValidatePasswordWithoutLowercase()
    {
        $password = 'WEAKPASS123!';
        $result = $this->passwordPolicy->validatePassword($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Mật khẩu phải chứa ít nhất 1 chữ cái viết thường', $result['errors']);
    }

    public function testValidatePasswordWithoutNumbers()
    {
        $password = 'WeakPass!';
        $result = $this->passwordPolicy->validatePassword($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Mật khẩu phải chứa ít nhất 1 chữ số', $result['errors']);
    }

    public function testValidatePasswordWithoutSpecialChars()
    {
        $password = 'WeakPass123';
        $result = $this->passwordPolicy->validatePassword($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt', $result['errors']);
    }

    public function testValidatePasswordWithCommonPassword()
    {
        $password = 'password123';
        $result = $this->passwordPolicy->validatePassword($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Mật khẩu quá yếu, vui lòng chọn mật khẩu khác', $result['errors']);
    }

    public function testCalculateStrengthScore()
    {
        $weakPassword = '123';
        $strongPassword = 'StrongPass123!';
        
        $weakScore = $this->passwordPolicy->calculateStrength($weakPassword);
        $strongScore = $this->passwordPolicy->calculateStrength($strongPassword);
        
        $this->assertLessThan($strongScore, $weakScore);
        $this->assertGreaterThanOrEqual(0, $weakScore);
        $this->assertLessThanOrEqual(100, $strongScore);
    }

    public function testGetStrengthLevel()
    {
        $veryWeak = '123';
        $weak = 'password';
        $medium = 'Password1';
        $strong = 'Password123!';
        $veryStrong = 'VeryStrongPassword123!@#';
        
        $this->assertEquals('very_weak', $this->passwordPolicy->getStrengthLevel($veryWeak));
        $this->assertEquals('weak', $this->passwordPolicy->getStrengthLevel($weak));
        $this->assertEquals('medium', $this->passwordPolicy->getStrengthLevel($medium));
        $this->assertEquals('strong', $this->passwordPolicy->getStrengthLevel($strong));
        $this->assertEquals('very_strong', $this->passwordPolicy->getStrengthLevel($veryStrong));
    }

    public function testGenerateStrongPassword()
    {
        $password = $this->passwordPolicy->generateStrongPassword(12);
        
        $this->assertEquals(12, strlen($password));
        
        $validation = $this->passwordPolicy->validatePassword($password);
        $this->assertTrue($validation['valid']);
    }

    public function testHasConsecutiveChars()
    {
        $reflection = new ReflectionClass($this->passwordPolicy);
        $method = $reflection->getMethod('hasConsecutiveChars');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->passwordPolicy, 'abc123', 3));
        $this->assertFalse($method->invoke($this->passwordPolicy, 'a1b2c3', 3));
    }

    public function testHasRepeatingChars()
    {
        $reflection = new ReflectionClass($this->passwordPolicy);
        $method = $reflection->getMethod('hasRepeatingChars');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->passwordPolicy, 'aaa123', 3));
        $this->assertFalse($method->invoke($this->passwordPolicy, 'a1a2a3', 3));
    }
}
