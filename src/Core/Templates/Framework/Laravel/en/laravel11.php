<?php

return <<<'MARKDOWN'

## Laravel 11 Best Practices

### New Features
- Streamlined application structure
- Per-second rate limiting
- Health routing
- Graceful encryption key rotation
- Queue interaction testing improvements
- New Artisan commands (make:class, make:enum, make:interface, make:trait)
- Model casts improvements
- Once method for singletons
- Dumpable trait

### Streamlined Structure
- Slimmed down bootstrap/app.php
- No HTTP Kernel and Console Kernel
- No middleware directory by default
- Optional API routes
- Fewer configuration files

### Configuration

```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureEmailIsVerified::class,
        ]);
    })
    ->create();
```

### Health Check Route
- Built-in health endpoint

```php
Route::health('/up');
```

### Per-Second Rate Limiting

```php
RateLimiter::for('invoices', function (Request $request) {
    return Limit::perSecond(1)->by($request->user()->id);
});
```

### Model Casts
- Cast method for flexible casting

```php
protected function casts(): array
{
    return [
        'options' => AsCollection::class,
        'email_verified_at' => 'datetime',
    ];
}
```

### Once Helper
- Execute callbacks once per request

```php
$value = once(function () {
    return expensive_operation();
});
```

### Dumpable Trait
- Add dump/dd methods to classes

```php
use Illuminate\Support\Traits\Dumpable;

class User
{
    use Dumpable;
}

$user->dd();
```

### New Artisan Commands

```bash
php artisan make:class
php artisan make:enum
php artisan make:interface
php artisan make:trait
```

### All Laravel 8, 9, 10 Best Practices Apply
- Refer to previous Laravel versions for core practices

MARKDOWN;
