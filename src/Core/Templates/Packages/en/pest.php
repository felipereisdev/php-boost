<?php

return <<<'MARKDOWN'

### Pest PHP

**Purpose:** Testing framework with elegant syntax

**Installation:**
```bash
composer require pestphp/pest --dev --with-all-dependencies
php artisan pest:install
```

**Usage:**

Basic test:
```php
test('user can be created', function () {
    $user = User::factory()->create();
    
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBeString();
});
```

Using datasets:
```php
it('validates email', function ($email, $valid) {
    $result = filter_var($email, FILTER_VALIDATE_EMAIL);
    expect((bool) $result)->toBe($valid);
})->with([
    ['test@example.com', true],
    ['invalid', false],
]);
```

HTTP testing:
```php
test('homepage returns 200', function () {
    $this->get('/')->assertStatus(200);
});
```

**Best Practices:**
- Use descriptive test names
- Leverage datasets for multiple scenarios
- Use beforeEach/afterEach for setup
- Group tests with describe()
- Use higher order tests for simple cases
- Implement custom expectations
- Use snapshots for complex assertions

MARKDOWN;
