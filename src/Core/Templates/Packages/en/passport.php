<?php

return <<<'MARKDOWN'

### Laravel Passport

**Purpose:** Full OAuth2 server implementation

**Installation:**
```bash
composer require laravel/passport
php artisan migrate
php artisan passport:install
```

**Configuration:**

Add `HasApiTokens` trait to User model:
```php
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}
```

Configure in `AuthServiceProvider`:
```php
use Laravel\Passport\Passport;

public function boot()
{
    Passport::tokensExpireIn(now()->addDays(15));
    Passport::refreshTokensExpireIn(now()->addDays(30));
    Passport::personalAccessTokensExpireIn(now()->addMonths(6));
}
```

**Usage:**

Protect routes:
```php
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
```

**Scopes:**
```php
Passport::tokensCan([
    'place-orders' => 'Place orders',
    'check-status' => 'Check order status',
]);
```

**Best Practices:**
- Use scopes for granular permissions
- Implement refresh token rotation
- Store client secrets securely
- Use PKCE for public clients
- Set proper token expiration times
- Revoke tokens on logout
- Monitor token usage
- Use HTTPS in production

MARKDOWN;
