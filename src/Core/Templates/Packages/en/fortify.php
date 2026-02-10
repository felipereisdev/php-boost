<?php

return <<<'MARKDOWN'

### Laravel Fortify

**Purpose:** Backend authentication implementation (headless)

**Installation:**
```bash
composer require laravel/fortify
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
php artisan migrate
```

**Configuration:**

Register in `config/app.php`:
```php
'providers' => [
    App\Providers\FortifyServiceProvider::class,
],
```

**Features:**
- Registration, Login, Logout
- Password Reset, Email Verification
- Two-Factor Authentication
- Profile Updates

**Customization:**

In `FortifyServiceProvider`:
```php
Fortify::loginView(function () {
    return view('auth.login');
});

Fortify::authenticateUsing(function (Request $request) {
    // Custom authentication logic
});
```

**Two-Factor:**
```php
Fortify::twoFactorAuthenticationView(function () {
    return view('auth.two-factor');
});
```

**Best Practices:**
- Customize views in your service provider
- Implement rate limiting
- Use email verification
- Enable 2FA for sensitive apps
- Customize validation rules
- Add custom authentication logic when needed
- Use with Jetstream or standalone

MARKDOWN;
