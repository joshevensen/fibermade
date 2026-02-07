# Fibermade Testing Guide

## Overview

Fibermade uses Pest v4 for testing with a focus on critical business flows and maintaining a 70% code coverage baseline. This testing approach supports vibe coding and Ralph loops while maintaining quality.

## Running Tests

### All Tests
```bash
php artisan test
```

### Specific Test File
```bash
php artisan test tests/Feature/Auth/RegistrationTest.php
```

### Filter by Test Name
```bash
php artisan test --filter="creator can complete full registration"
```

### Browser Tests Only
```bash
php artisan test tests/Browser/
```

### With Code Coverage (requires PCOV or Xdebug)
```bash
php artisan test --coverage
```

### Enforce Coverage Baseline
```bash
php artisan test --coverage --min=70
# Or use the helper script:
./tests/check-coverage.sh
```

## Test Organization

### Feature Tests (`tests/Feature/`)
- HTTP controller tests
- Authentication flow tests
- Business logic tests
- Database integration tests
- **Current status**: 104+ tests covering all major features

### Browser Tests (`tests/Browser/`)
- End-to-end user journeys using Pest v4 browser testing
- Critical business flows:
  - Creator registration ([CreatorRegistrationTest.php](Browser/CreatorRegistrationTest.php))
  - Store invite acceptance ([StoreInviteAcceptanceTest.php](Browser/StoreInviteAcceptanceTest.php))
  - Account closure ([AccountClosureTest.php](Browser/AccountClosureTest.php))
- Full frontend + backend integration
- **Current status**: 9 tests covering critical user paths

### Unit Tests (`tests/Unit/`)
- Isolated logic tests
- Helper function tests
- Model method tests
- **Current status**: Minimal - focus is on Feature and Browser tests

## Writing Tests

### Use Pest Syntax
```php
test('creator can create colorway', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $response = $this->actingAs($user)->post(route('colorways.store'), [
        'name' => 'Ocean Blue',
        'status' => ColorwayStatus::Active->value,
    ]);

    $response->assertRedirect(route('colorways.index'));
    expect(Colorway::where('name', 'Ocean Blue')->exists())->toBeTrue();
});
```

### Use Factories
Always use model factories instead of manual creation:
```php
// Good
$user = User::factory()->create();
$creator = Creator::factory()->create();

// Avoid
$user = new User(['name' => 'Test', ...]);
```

### Browser Tests
```php
test('user can navigate through registration flow', function () {
    $page = visit('/register');

    $page->fill('input[name="email"]', 'test@example.com')
        ->fill('input[name="password"]', 'password')
        ->click('button[type="submit"]')
        ->waitForNavigation()
        ->assertUrl('/dashboard')
        ->assertNoJavascriptErrors();
});
```

## Coverage Goals

- **Baseline**: 70% overall coverage
- **Critical Paths**: 100% coverage for registration, authentication, core business flows
- **New Features**: All new code should include tests

## Best Practices

1. **Test Business Value**: Focus on testing what matters to users
2. **Fast Tests**: Keep tests fast by using SQLite in-memory database
3. **Readable Tests**: Use descriptive test names and arrange-act-assert pattern
4. **Factories**: Use factories for all model creation
5. **No Mocks Unless Necessary**: Prefer real implementations for integration tests
6. **Browser Tests for Critical Flows**: Use browser tests for key user journeys

## Common Issues

### Account Model
Accounts don't have a `name` field. Use related models:
```php
// Wrong
$account = Account::factory()->create(['name' => 'Test']);

// Right
$account = Account::factory()->create();
$creator = Creator::factory()->create([
    'account_id' => $account->id,
    'name' => 'Test Creator'
]);
```

### Factory States
Use appropriate factory states:
```php
User::factory()->unverified()->create(); // For unverified users
Account::factory()->storeType()->create(); // For store accounts
```

### Browser Test Selectors
Use semantic selectors or data-test attributes:
```php
// Good - using data-test attribute
$page->click('[data-test="register-user-button"]');

// Good - using name attribute
$page->fill('input[name="email"]', 'test@example.com');

// Acceptable - semantic selector
$page->click('button[type="submit"]');
```

## Installing Code Coverage Driver

### For Laravel Herd Users
You'll need to install PCOV or Xdebug for your Herd PHP version. PCOV is recommended for its performance.

**Option 1: Using Herd's PHP (Recommended)**
Check Herd's documentation for installing extensions with their PHP distribution.

**Option 2: Install PCOV via PECL**
```bash
pecl install pcov
```

**Option 3: Use Xdebug (if available)**
Xdebug may already be available with your Herd installation - check `php -m | grep xdebug`.

### Verifying Installation
```bash
# Check if PCOV is installed
php -m | grep pcov

# Check if Xdebug is installed
php -m | grep xdebug

# Test coverage
php artisan test --coverage
```

## CI/CD Integration

Add to your deployment pipeline:

```yaml
# Example GitHub Actions
- name: Run tests
  run: php artisan test

- name: Check coverage
  run: |
    php artisan test --coverage --min=70
```

## Test Counts

- **Feature Tests**: ~104 tests
- **Browser Tests**: 9 tests (3 flows × 3 test cases)
- **Unit Tests**: 1 test
- **Total**: ~114 tests

## Recent Changes

- ✅ Fixed all failing tests (24 → 0 failures)
- ✅ Removed email verification tests (feature not enabled)
- ✅ Fixed Account factory usage (removed invalid `name` parameter)
- ✅ Added browser tests for critical user flows
- ✅ Configured coverage reporting infrastructure
- ✅ Created coverage baseline enforcement script

## Next Steps

1. **Install Coverage Driver**: Install PCOV or Xdebug to enable coverage reporting
2. **Run Coverage**: Execute `php artisan test --coverage` to see current coverage
3. **Add Tests for New Features**: Ensure all new features include tests
4. **Maintain 70% Baseline**: Use `./tests/check-coverage.sh` before commits

## Getting Help

- Test failures? Check recent git changes and factory usage
- Browser test issues? Ensure selectors match current UI
- Coverage driver not found? See "Installing Code Coverage Driver" section above
- Questions about testing patterns? Check existing tests for examples

## Trade-offs & Decisions

**Why Pest over PHPUnit?**
- More readable test syntax
- Better error messages
- Built-in browser testing (v4)
- Designed for modern PHP/Laravel apps

**Why 70% Coverage Baseline?**
- Pragmatic balance between quality and velocity for MVP stage
- Catches most regressions without perfectionism
- Can increase as codebase matures

**Why Browser Tests Only for Critical Flows?**
- Higher cost/maintenance than unit tests
- Reserve E2E testing for business-critical paths
- Feature tests cover most scenarios faster

**Why Skip JS Unit Tests?**
- Inertia apps have most logic server-side
- Vue components are primarily presentational
- Browser tests catch frontend issues while testing full stack
- Can add later if complex client-side logic emerges

---

**Happy Testing!** 🎉

For questions or improvements to this testing approach, discuss with the team or update this document.
