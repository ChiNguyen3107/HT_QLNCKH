# Testing Framework Setup

## Tổng quan

Dự án đã được setup với testing framework hoàn chỉnh sử dụng PHPUnit 10.x với các tính năng:

- Unit Testing
- Feature/Integration Testing  
- Database Testing với SQLite in-memory
- API Testing với HTTP client
- Code Coverage reporting
- Automated testing pipeline với GitHub Actions

## Cấu trúc thư mục

```
tests/
├── Unit/                    # Unit tests
│   ├── UserModelTest.php
│   └── ProjectModelTest.php
├── Feature/                 # Feature/Integration tests
│   ├── AuthTest.php
│   └── ProjectTest.php
├── Factories/               # Test data factories
│   ├── UserFactory.php
│   ├── ProjectFactory.php
│   └── NotificationFactory.php
├── Traits/                  # Testing traits
│   ├── DatabaseTestTrait.php
│   └── ApiTestTrait.php
├── Helpers/                 # Testing helpers
├── TestCase.php            # Base test class
├── bootstrap.php           # Test bootstrap
└── README.md              # This file
```

## Chạy Tests

### Chạy tất cả tests
```bash
vendor/bin/phpunit
```

### Chạy Unit tests only
```bash
vendor/bin/phpunit tests/Unit
```

### Chạy Feature tests only
```bash
vendor/bin/phpunit tests/Feature
```

### Chạy với coverage report
```bash
vendor/bin/phpunit --coverage-html storage/coverage
```

### Chạy specific test
```bash
vendor/bin/phpunit tests/Unit/UserModelTest.php
```

### Chạy specific test method
```bash
vendor/bin/phpunit --filter testCanCreateUser
```

## Test Configuration

### phpunit.xml
- Bootstrap: `tests/bootstrap.php`
- Test suites: Unit và Feature
- Coverage: HTML, Clover, Text reports
- Environment: Testing với SQLite in-memory

### Database Testing
- Sử dụng SQLite in-memory cho testing
- Automatic transaction rollback
- Test data factories
- Database assertions helpers

### API Testing
- HTTP client với Guzzle
- Authentication helpers
- Response assertions
- File upload testing

## Test Data Factories

### UserFactory
```php
// Tạo user thông thường
$user = UserFactory::create();

// Tạo admin user
$admin = UserFactory::createAdmin();

// Tạo teacher user
$teacher = UserFactory::createTeacher();

// Tạo student user
$student = UserFactory::createStudent();

// Tạo multiple users
$users = UserFactory::createMultiple(5);
```

### ProjectFactory
```php
// Tạo project thông thường
$project = ProjectFactory::create();

// Tạo project với status cụ thể
$pendingProject = ProjectFactory::createPending();
$approvedProject = ProjectFactory::createApproved();

// Tạo multiple projects
$projects = ProjectFactory::createMultiple(3);
```

## Database Testing Helpers

### TestCase methods
```php
// Insert test data
$id = $this->insertTestData('users', $userData);

// Assert database has record
$this->assertDatabaseHas('users', ['username' => 'test']);

// Assert database missing record
$this->assertDatabaseMissing('users', ['username' => 'test']);

// Execute raw query
$this->executeQuery("SELECT * FROM users WHERE active = ?", [1]);

// Get table count
$count = $this->getTableCount('users');

// Truncate table
$this->truncateTable('users');
```

## API Testing Helpers

### HTTP Methods
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

Coverage reports được tạo trong `storage/coverage/`:
- HTML report: `storage/coverage/index.html`
- Clover XML: `storage/coverage/clover.xml`
- Text report: `storage/coverage/coverage.txt`

## GitHub Actions

Automated testing pipeline bao gồm:
- PHP 8.1, 8.2, 8.3 compatibility tests
- Database tests với MySQL
- Code coverage reporting
- Security audit
- Code style checking

## Best Practices

### Writing Tests
1. Sử dụng descriptive test names
2. Arrange-Act-Assert pattern
3. One assertion per test method
4. Use factories cho test data
5. Clean up after tests

### Test Organization
1. Group related tests trong same class
2. Use traits cho shared functionality
3. Keep tests independent
4. Mock external dependencies

### Performance
1. Use in-memory database cho unit tests
2. Use transactions cho database cleanup
3. Minimize test data
4. Run tests in parallel khi possible

## Troubleshooting

### Common Issues

1. **Database connection errors**
   - Check .env.testing configuration
   - Ensure SQLite extension enabled

2. **Memory issues**
   - Increase memory_limit trong phpunit.xml
   - Use database transactions

3. **Slow tests**
   - Use in-memory database
   - Minimize test data
   - Mock external services

### Debug Mode
```bash
# Run với verbose output
vendor/bin/phpunit --verbose

# Run với debug mode
vendor/bin/phpunit --debug
```

## Dependencies

- PHPUnit 10.x
- Mockery (mocking)
- Guzzle HTTP (API testing)
- Faker (test data generation)
- PHP Code Coverage

## Contributing

Khi thêm tests mới:
1. Follow naming conventions
2. Add proper documentation
3. Ensure tests pass
4. Update coverage reports
5. Add to appropriate test suite
