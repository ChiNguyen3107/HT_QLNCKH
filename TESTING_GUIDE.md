# Hướng dẫn Testing Framework

## Cài đặt

1. **Cài đặt dependencies:**
```bash
composer install
```

2. **Setup thư mục testing:**
```bash
make test-setup
# hoặc
composer run test:setup
```

## Chạy Tests

### Sử dụng Makefile (Khuyến nghị)

```bash
# Chạy tất cả tests
make test

# Chạy unit tests
make test-unit

# Chạy feature tests  
make test-feature

# Chạy với coverage report
make test-coverage

# Chạy với HTML coverage report
make test-coverage-html

# Chạy tests cho CI/CD
make test-ci

# Chạy với verbose output
make test-verbose

# Chạy với debug mode
make test-debug

# Chạy tests với filter
make test-filter FILTER=testCanCreateUser

# Dừng khi có lỗi
make test-stop-on-failure
```

### Sử dụng Composer Scripts

```bash
# Chạy tất cả tests
composer test

# Chạy unit tests
composer run test:unit

# Chạy feature tests
composer run test:feature

# Chạy với coverage
composer run test:coverage

# Chạy với coverage text
composer run test:coverage-text

# Chạy cho CI
composer run test:ci
```

### Sử dụng PHPUnit trực tiếp

```bash
# Chạy tất cả tests
vendor/bin/phpunit

# Chạy unit tests
vendor/bin/phpunit tests/Unit

# Chạy feature tests
vendor/bin/phpunit tests/Feature

# Chạy với coverage
vendor/bin/phpunit --coverage-html storage/coverage

# Chạy specific test
vendor/bin/phpunit tests/Unit/UserModelTest.php

# Chạy với filter
vendor/bin/phpunit --filter testCanCreateUser

# Chạy với verbose
vendor/bin/phpunit --verbose
```

## Test Examples

### Unit Test Example

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Factories\UserFactory;

class UserModelTest extends TestCase
{
    public function testCanCreateUser(): void
    {
        $userData = UserFactory::create([
            'username' => 'testuser',
            'email' => 'test@example.com'
        ]);

        $userId = $this->insertTestData('users', $userData);

        $this->assertGreaterThan(0, $userId);
        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com'
        ]);
    }
}
```

### Feature Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function testCanLoginWithValidCredentials(): void
    {
        // Arrange
        $userData = UserFactory::create([
            'username' => 'testuser',
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT)
        ]);
        $this->insertTestData('users', $userData);

        // Act
        $loginData = [
            'username' => 'testuser',
            'password' => 'password123'
        ];
        $response = $this->post('/api/v1/auth/login', $loginData);

        // Assert
        $this->assertResponseStatus($response, 200);
        $this->assertJsonResponse($response);
    }
}
```

## Test Data Factories

### UserFactory

```php
// Tạo user thông thường
$user = UserFactory::create();

// Tạo admin user
$admin = UserFactory::createAdmin(['username' => 'admin']);

// Tạo teacher user
$teacher = UserFactory::createTeacher(['email' => 'teacher@test.com']);

// Tạo student user
$student = UserFactory::createStudent(['full_name' => 'John Doe']);

// Tạo multiple users
$users = UserFactory::createMultiple(5);
```

### ProjectFactory

```php
// Tạo project thông thường
$project = ProjectFactory::create();

// Tạo project với status cụ thể
$pendingProject = ProjectFactory::createPending(['title' => 'New Project']);
$approvedProject = ProjectFactory::createApproved(['budget' => 5000000]);

// Tạo multiple projects
$projects = ProjectFactory::createMultiple(3);
```

## Database Testing

### Insert Test Data

```php
$userData = UserFactory::create();
$userId = $this->insertTestData('users', $userData);
```

### Assert Database

```php
// Assert record exists
$this->assertDatabaseHas('users', ['username' => 'testuser']);

// Assert record missing
$this->assertDatabaseMissing('users', ['username' => 'deleteduser']);
```

### Execute Raw Query

```php
$result = $this->executeQuery("SELECT * FROM users WHERE active = ?", [1]);
$users = $result->fetchAll();
```

## API Testing

### HTTP Requests

```php
// GET request
$response = $this->get('/api/users');

// POST request
$response = $this->post('/api/users', $userData);

// PUT request
$response = $this->put('/api/users/1', $updateData);

// DELETE request
$response = $this->delete('/api/users/1');
```

### Response Assertions

```php
// Assert status code
$this->assertResponseStatus($response, 200);

// Assert JSON response
$this->assertJsonResponse($response);

// Assert response contains data
$this->assertResponseContains($response, ['id' => 1]);

// Assert pagination
$this->assertResponseHasPagination($response);
```

## Code Coverage

### Xem Coverage Report

```bash
# Tạo HTML coverage report
make test-coverage-html

# Mở coverage report trong browser
open storage/coverage/index.html
```

### Coverage Reports

- **HTML Report**: `storage/coverage/index.html`
- **Clover XML**: `storage/coverage/clover.xml`
- **Text Report**: Console output

## GitHub Actions

Automated testing pipeline sẽ chạy khi:

- Push code lên main/develop branch
- Tạo Pull Request

Pipeline bao gồm:
- PHP 8.1, 8.2, 8.3 compatibility tests
- Database tests với MySQL
- Code coverage reporting
- Security audit
- Code style checking

## Troubleshooting

### Common Issues

1. **Database connection errors**
   ```bash
   # Check SQLite extension
   php -m | grep sqlite
   ```

2. **Memory issues**
   ```bash
   # Increase memory limit
   php -d memory_limit=512M vendor/bin/phpunit
   ```

3. **Slow tests**
   ```bash
   # Use in-memory database
   # Minimize test data
   # Mock external services
   ```

### Debug Mode

```bash
# Run với verbose output
make test-verbose

# Run với debug mode
make test-debug

# Run specific test
make test-filter FILTER=testCanCreateUser
```

## Best Practices

1. **Test Naming**: Sử dụng descriptive test names
2. **Arrange-Act-Assert**: Follow AAA pattern
3. **One Assertion**: One assertion per test method
4. **Use Factories**: Sử dụng factories cho test data
5. **Clean Up**: Clean up after tests
6. **Mock External**: Mock external dependencies
7. **Independent Tests**: Keep tests independent
8. **Fast Tests**: Write fast, focused tests

## Useful Commands

```bash
# Help
make help

# Clean test artifacts
make clean

# Run development server
make serve

# Security check
make security-check

# Code style check
make code-style
```
