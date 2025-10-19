<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Helpers\TestHelper;

/**
 * Example unit test để demo testing framework
 */
class ExampleTest extends TestCase
{
    public function testBasicAssertions(): void
    {
        $this->assertTrue(true);
        $this->assertFalse(false);
        $this->assertEquals(1, 1);
        $this->assertNotEquals(1, 2);
        $this->assertEmpty([]);
        $this->assertNotEmpty([1, 2, 3]);
    }

    public function testStringOperations(): void
    {
        $string = 'Hello World';
        
        $this->assertStringContainsString('Hello', $string);
        $this->assertStringNotContainsString('Goodbye', $string);
        $this->assertEquals(11, strlen($string));
    }

    public function testArrayOperations(): void
    {
        $array = ['apple', 'banana', 'orange'];
        
        $this->assertCount(3, $array);
        $this->assertContains('apple', $array);
        $this->assertNotContains('grape', $array);
        $this->assertArrayHasKey(0, $array);
    }

    public function testHelperMethods(): void
    {
        // Test random string generation
        $randomString = TestHelper::randomString(10);
        $this->assertEquals(10, strlen($randomString));
        
        // Test random email generation
        $email = TestHelper::randomEmail();
        $this->assertStringContainsString('@', $email);
        $this->assertStringContainsString('.com', $email);
        
        // Test random Vietnamese name
        $name = TestHelper::randomVietnameseName();
        $this->assertNotEmpty($name);
        $this->assertGreaterThan(5, strlen($name));
        
        // Test random student ID
        $studentId = TestHelper::randomStudentId();
        $this->assertMatchesRegularExpression('/^B[0-9]{5}$/', $studentId);
    }

    public function testDatabaseOperations(): void
    {
        // Test inserting data
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'full_name' => 'Test User',
            'role' => 'student',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $this->insertTestData('users', $userData);
        
        $this->assertGreaterThan(0, $userId);
        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com'
        ]);
        
        // Test updating data
        $this->executeQuery(
            "UPDATE users SET full_name = ? WHERE id = ?",
            ['Updated Name', $userId]
        );
        
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'full_name' => 'Updated Name'
        ]);
        
        // Test deleting data
        $this->executeQuery("DELETE FROM users WHERE id = ?", [$userId]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function testTemporaryFileOperations(): void
    {
        // Create temporary file
        $tempFile = TestHelper::createTempFile('test content', 'txt');
        
        $this->assertFileExists($tempFile);
        $this->assertEquals('test content', file_get_contents($tempFile));
        
        // Clean up
        TestHelper::cleanupTempFile($tempFile);
        $this->assertFileDoesNotExist($tempFile);
    }

    public function testDateGeneration(): void
    {
        $date = TestHelper::randomDate();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
        
        $datetime = TestHelper::randomDateTime();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime);
    }

    public function testArrayValidation(): void
    {
        $array = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];
        $requiredKeys = ['name', 'age'];
        
        // This should not throw exception
        TestHelper::assertArrayHasKeys($array, $requiredKeys);
        
        // Test with missing key
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        TestHelper::assertArrayHasKeys($array, ['name', 'age', 'missing']);
    }

    public function testVietnameseDataGeneration(): void
    {
        $address = TestHelper::randomVietnameseAddress();
        $this->assertNotEmpty($address);
        $this->assertStringContainsString('TP. Hồ Chí Minh', $address);
        
        $companyName = TestHelper::randomVietnameseCompanyName();
        $this->assertNotEmpty($companyName);
        $this->assertStringContainsString('Công ty', $companyName);
    }

    public function testMockeryIntegration(): void
    {
        // Test creating mock object
        $mock = $this->mock(\stdClass::class);
        $mock->shouldReceive('testMethod')->andReturn('mocked result');
        
        $result = $mock->testMethod();
        $this->assertEquals('mocked result', $result);
    }
}
