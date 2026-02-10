<?php

return <<<'MARKDOWN'

### Laravel Sanctum

**Purpose:** Simple API token authentication for SPAs and mobile apps

**Installation:**
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

**Configuration:**

Add Sanctum middleware to `app/Http/Kernel.php`:
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**Usage:**

Issue tokens:
```php
$token = $user->createToken('token-name')->plainTextToken;
```

Protect routes:
```php
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

Revoke tokens:
```php
$user->tokens()->delete();
$user->currentAccessToken()->delete();
```

**Best Practices:**
- Use token abilities for permissions
- Revoke tokens on logout
- Set token expiration if needed
- Use HTTPS in production

MARKDOWN;
