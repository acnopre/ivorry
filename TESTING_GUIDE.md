# Unit Testing Guide for Laravel 12

## Quick Start

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Run Specific Test File
```bash
php artisan test --filter=CompleteImportJobTest
php artisan test tests/Unit/Jobs/CompleteImportJobTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
php artisan test --coverage-html=coverage
```

## Best Practices for Solo Developer

### 1. Test Before Pushing
Always run tests locally before committing:
```bash
git add .
php artisan test
git commit -m "Your message"
git push
```

### 2. Write Tests First (TDD Approach)
- Write test for new feature
- Run test (it should fail)
- Write minimal code to pass
- Refactor if needed

### 3. Test Critical Business Logic
Priority order:
1. Payment/billing logic
2. User authentication/authorization
3. Data validation
4. Import/export operations
5. API endpoints

### 4. Use Database Transactions
Always use `RefreshDatabase` trait in tests to ensure clean state.

### 5. Mock External Services
Don't test third-party APIs - mock them instead.

## Test Structure

```
tests/
├── Unit/              # Pure logic tests (no HTTP)
│   ├── Jobs/
│   ├── Models/
│   ├── Services/
│   └── Imports/
└── Feature/           # HTTP/Integration tests
    ├── Auth/
    ├── Api/
    └── Admin/
```

## CI/CD Integration (Optional)

### GitHub Actions Example
Create `.github/workflows/tests.yml`:
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

## Common Commands

```bash
# Run tests in parallel (faster)
php artisan test --parallel

# Stop on first failure
php artisan test --stop-on-failure

# Run only specific methods
php artisan test --filter=test_updates_status_to_completed

# Generate coverage report
php artisan test --coverage --min=80
```

## Tips

1. **Keep tests fast** - Use in-memory SQLite for testing
2. **Test one thing** - Each test should verify one behavior
3. **Use descriptive names** - `test_user_cannot_delete_other_users_posts`
4. **Arrange-Act-Assert** - Setup, execute, verify pattern
5. **Don't test framework** - Test your code, not Laravel's code

## Created Tests

✅ CompleteImportJobTest - Job status updates
✅ AccountServiceManagerTest - Service renewal defaults
✅ ImportLogTest - Status checking logic
✅ MblBalanceServiceTest - Balance calculations
✅ AccountTest - Auto-expiration logic
✅ AccountEndorsementServiceTest - Endorsement operations
✅ AccountImportTest - Import validation rules
