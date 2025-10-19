<?php

namespace Tests\Traits;

use PDO;
use PDOException;

/**
 * Trait cho database testing với transaction rollback
 */
trait DatabaseTestTrait
{
    protected ?PDO $database = null;
    protected bool $inTransaction = false;

    /**
     * Setup database cho testing
     */
    protected function setUpDatabase(): void
    {
        try {
            $this->database = new PDO('sqlite::memory:');
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Tạo schema cho testing
            $this->createTestSchema();
            
            // Begin transaction cho rollback
            $this->database->beginTransaction();
            $this->inTransaction = true;
            
        } catch (PDOException $e) {
            $this->fail("Failed to setup test database: " . $e->getMessage());
        }
    }

    /**
     * Tear down database
     */
    protected function tearDownDatabase(): void
    {
        if ($this->inTransaction) {
            $this->database->rollBack();
            $this->inTransaction = false;
        }
        
        $this->database = null;
    }

    /**
     * Get database connection
     */
    protected function getDatabaseConnection(): PDO
    {
        if ($this->database === null) {
            throw new \RuntimeException('Database connection not initialized');
        }
        
        return $this->database;
    }

    /**
     * Create test database schema
     */
    protected function createTestSchema(): void
    {
        $sql = "
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role VARCHAR(20) DEFAULT 'student',
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE faculties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(10) UNIQUE NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL,
                faculty_id INTEGER,
                year INTEGER NOT NULL,
                semester INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES faculties(id)
            );

            CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                student_id INTEGER NOT NULL,
                supervisor_id INTEGER,
                status VARCHAR(20) DEFAULT 'pending',
                budget REAL DEFAULT 0,
                start_date DATE,
                end_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id),
                FOREIGN KEY (supervisor_id) REFERENCES users(id)
            );

            CREATE TABLE notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(20) DEFAULT 'info',
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );

            CREATE TABLE sessions (
                id VARCHAR(128) PRIMARY KEY,
                user_id INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT,
                payload TEXT,
                last_activity INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
        ";

        $this->database->exec($sql);
    }

    /**
     * Insert test data vào database
     */
    protected function insertTestData(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->database->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return (int) $this->database->lastInsertId();
    }

    /**
     * Truncate table (xóa tất cả data)
     */
    protected function truncateTable(string $table): void
    {
        $this->database->exec("DELETE FROM {$table}");
        $this->database->exec("DELETE FROM sqlite_sequence WHERE name='{$table}'");
    }

    /**
     * Execute raw SQL query
     */
    protected function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get table row count
     */
    protected function getTableCount(string $table): int
    {
        $stmt = $this->database->query("SELECT COUNT(*) FROM {$table}");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Seed database với test data
     */
    protected function seedTestData(): void
    {
        // Insert test faculties
        $this->insertTestData('faculties', [
            'name' => 'Khoa Công nghệ Thông tin',
            'code' => 'CNTT',
            'description' => 'Khoa Công nghệ Thông tin và Truyền thông'
        ]);

        // Insert test users
        $this->insertTestData('users', [
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'full_name' => 'Administrator',
            'role' => 'admin'
        ]);

        $this->insertTestData('users', [
            'username' => 'teacher1',
            'email' => 'teacher1@test.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'full_name' => 'Giáo viên 1',
            'role' => 'teacher'
        ]);

        $this->insertTestData('users', [
            'username' => 'student1',
            'email' => 'student1@test.com',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'full_name' => 'Sinh viên 1',
            'role' => 'student'
        ]);
    }
}
