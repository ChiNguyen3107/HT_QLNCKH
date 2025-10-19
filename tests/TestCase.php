<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\Traits\DatabaseTestTrait;
use Tests\Traits\ApiTestTrait;

/**
 * Base TestCase class cho tất cả tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    use DatabaseTestTrait, ApiTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup testing environment
        $this->setUpDatabase();
        $this->setUpTestEnvironment();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /**
     * Setup test environment
     */
    protected function setUpTestEnvironment(): void
    {
        // Set testing environment variables
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        
        // Clear any existing session data
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Start new session
        session_start();
    }

    /**
     * Helper method để tạo mock object
     */
    protected function mock(string $class): \Mockery\MockInterface
    {
        return \Mockery::mock($class);
    }

    /**
     * Helper method để tạo partial mock
     */
    protected function partialMock(string $class, array $methods = []): \Mockery\MockInterface
    {
        return \Mockery::mock($class)->makePartial()->shouldReceive($methods);
    }

    /**
     * Helper method để assert JSON response
     */
    protected function assertJsonResponse(string $json, ?array $expectedData = null): void
    {
        $this->assertJson($json);
        
        if ($expectedData !== null) {
            $actualData = json_decode($json, true);
            $this->assertEquals($expectedData, $actualData);
        }
    }

    /**
     * Helper method để assert database record exists
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $this->assertTrue(
            $this->databaseHas($table, $data),
            "Database table [{$table}] does not contain expected data: " . json_encode($data)
        );
    }

    /**
     * Helper method để assert database record missing
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $this->assertFalse(
            $this->databaseHas($table, $data),
            "Database table [{$table}] contains unexpected data: " . json_encode($data)
        );
    }

    /**
     * Helper method để check database record exists
     */
    protected function databaseHas(string $table, array $data): bool
    {
        $db = $this->getDatabase();
        $conditions = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $conditions[] = "{$key} = ?";
            $values[] = $value;
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode(' AND ', $conditions);
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get database connection
     */
    protected function getDatabase(): \PDO
    {
        return $this->getDatabaseConnection();
    }
}
